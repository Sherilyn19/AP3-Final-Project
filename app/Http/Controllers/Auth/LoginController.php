<?php

// app/Http/Controllers/Auth/LoginController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * LoginController
 * 
 * Handles user authentication
 * - Displays login form
 * - Processes login with role verification
 * - Redirects to appropriate dashboard based on user role
 */
class LoginController extends Controller
{
    /**
     * Display the login form
     * 
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // Return the login view
        return view('auth.login');
    }

    /**
     * Authenticate a user and redirect them to the appropriate dashboard.
     *
     * This method:
     * 1. Validates the submitted credentials.
     * 2. Finds the account using its email address.
     * 3. Authenticates a Super Admin when applicable.
     * 4. Verifies the password for regular users.
     * 5. Determines whether the account belongs to a student or instructor.
     * 6. Confirms that the related role profile is active.
     * 7. Updates the last-login timestamp.
     * 8. Regenerates the session and redirects to the correct dashboard.
     */
    public function login(Request $request)
    {
        // === STEP 1: Validate incoming request data ===
        $credentials = $request->validate([
            'user_email' => 'required|email',
            'user_password' => 'required|string',
        ], [
            'user_email.required' => 'Please enter your email address.',
            'user_email.email' => 'Please enter a valid email address.',
            'user_password.required' => 'Please enter your password.',
        ]);

        // === STEP 2: Find user by email in user_account table ===
        $user = UserAccount::where('user_email', $credentials['user_email'])->first();

        if (!$user) {
            return back()->withErrors([
                'user_email' => 'No account found with this email address.',
            ])->withInput($request->only('user_email'));
        }

        // === STEP 3: Handle Super Admin Login (if applicable) ===
        if ($user->is_super_admin) {
            if (Hash::check($credentials['user_password'], $user->user_password)) {
                $user->last_login = now();
                $user->save();
                Auth::login($user);
                $request->session()->regenerate();
                return redirect()->route('admin.dashboard')
                    ->with('success', 'Welcome back, Administrator!');
            }
            return back()->withErrors([
                'user_password' => 'Incorrect password.',
            ])->withInput($request->only('user_email'));
        }

        // === STEP 4: Verify password for regular users ===
        if (!Hash::check($credentials['user_password'], $user->user_password)) {
            return back()->withErrors([
                'user_password' => 'Incorrect password. Please try again.',
            ])->withInput($request->only('user_email'));
        }

        // === STEP 5: Get user's actual role ===
        $actualRole = $this->getUserRole($user);

        if (!$actualRole) {
            return back()->withErrors([
                'user_email' => 'Your account has not been assigned a role. Contact support.',
            ])->withInput($request->only('user_email'));
        }

        if (!$this->isRoleActive($user, $actualRole)) {
            return back()->withErrors([
                'user_email' => 'Your account is inactive. Please contact the administrator.',
            ])->withInput($request->only('user_email'));
        }

        // === STEP 6: Update last login timestamp ===
        $user->last_login = now();
        $user->save();

        // === STEP 7: Log the user in ===
        Auth::login($user);
        $request->session()->regenerate();

        // === STEP 8: Redirect to role-specific dashboard ===
        return $this->redirectToDashboard($actualRole);
    }

    /**
     * Determine whether the user's role profile is active.
     */
    private function isRoleActive(UserAccount $user, string $role): bool
    {
        $table = match ($role) {
            'student' => 'student',
            'instructor' => 'instructor',
            default => null,
        };

        if ($table === null) {
            return false;
        }

        return DB::table($table)
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Determine the user's role from the related role profile.
     *
     * Student profiles are checked before instructor profiles.
     * Returns null when the account has no supported role profile.
     */
    private function getUserRole(UserAccount $user)
    {
        if (\DB::table('student')->where('user_id', $user->user_id)->exists()) {
            return 'student';
        }

        if (\DB::table('instructor')->where('user_id', $user->user_id)->exists()) {
            return 'instructor';
        }

        return null;
    }

    /**
     * Redirect user to their role-specific dashboard
     * 
     * @param  string  $role
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectToDashboard($role)
    {
        $dashboardRoutes = [
            'student' => 'student.dashboard',
            'instructor' => 'instructor.dashboard',
        ];

        $routeName = $dashboardRoutes[$role] ?? 'home';

        return redirect()->route($routeName)
            ->with('success', 'Welcome back! You have successfully logged in.');
    }

    /**
     * Log the user out securely
     * 
     * Invalidates session and regenerates CSRF token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
