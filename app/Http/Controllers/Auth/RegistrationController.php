<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * app/Http/Controllers/Auth/RegistrationController.php
 * 
 * SESSION-BASED REGISTRATION FLOW
 * ================================
 * All registration data is stored in encrypted Laravel session
 * Database insertion only happens AFTER password creation
 * This prevents "user already exists" issues during multi-step registration
 * 
 * Flow:
 * 1. User fills multi-step form (data stored in session only)
 * 2. Email uniqueness validated before storing in session
 * 3. User redirected to password creation page
 * 4. After password entry, ALL data saved to DB in one transaction
 * 5. User auto-logged in and redirected to dashboard
 */
class RegistrationController extends Controller
{
    // ============================================================================
    // STUDENT REGISTRATION
    // ============================================================================

    /**
     * Display the student registration form
     */
    public function showStudentRegistrationForm()
    {
        $instruments = DB::table('instrument')
            ->whereRaw('is_active = TRUE')
            ->orderBy('instrument_name')
            ->get();

        $genres = DB::table('genre')
            ->whereRaw('is_active = TRUE')
            ->orderBy('genre_name')
            ->get();

        return view('auth.register.student', compact('instruments', 'genres'));
    }

    /**
     * Process student registration - STORES IN SESSION ONLY
     */
    public function registerStudent(Request $request)
    {
        // Validate ALL data including email uniqueness
        $validated = $request->validate([
            // Account
            'user_email' => 'required|email|unique:user_account,user_email',
            
            // Personal Information
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:20',
            'phone' => 'required|regex:/^[0-9]{11}$/',
            
            // Address (ALL REQUIRED)
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            
            // Personal Details
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other,Prefer not to say',
            'nationality' => 'nullable|string|max:100',
            
            // Medical Information (Optional)
            'medical_conditions' => 'nullable|string',
            'allergies' => 'nullable|string',
            'special_needs' => 'nullable|string',
            
            // Emergency Contact (ALL REQUIRED)
            'emergency_contact_name' => 'required|string|max:200',
            'emergency_contact_relationship' => 'required|string|max:100',
            'emergency_contact_phone' => 'required|regex:/^[0-9]{11}$/',
            
            // Parent/Guardian (ALL REQUIRED)
            'parent_guardian_name' => 'required|string|max:200',
            'parent_guardian_relationship' => 'required|string|max:50',
            'parent_guardian_phone' => 'required|regex:/^[0-9]{11}$/',
            'parent_guardian_email' => 'nullable|email|max:255',
            'parent_guardian_address' => 'nullable|string',
            
            // Musical Background
            'instrument_id' => 'required|exists:instrument,instrument_id',
            'secondary_instruments' => 'nullable|array',
            'secondary_instruments.*' => 'nullable|string|max:100',
            'previous_music_experience' => 'nullable|string',
            'skill_level' => 'required|in:beginner,intermediate,advanced,expert',
            'music_goals' => 'nullable|string',
            'preferred_genre_id' => 'nullable|exists:genre,genre_id',
            
            // Educational Background
            'school_name' => 'nullable|string|max:200',
            'grade_level' => 'nullable|string|max:50',
        ], [
            'user_email.unique' => 'This email is already registered.',
            'phone.regex' => 'Phone number must be exactly 11 digits.',
            'emergency_contact_phone.regex' => 'Emergency contact phone must be exactly 11 digits.',
            'parent_guardian_phone.regex' => 'Parent/Guardian phone must be exactly 11 digits.',
            'instrument_id.required' => 'Please select your primary instrument.',
            'skill_level.required' => 'Please select your skill level.',
        ]);

        // Convert secondary instruments array to comma-separated string
        if (isset($validated['secondary_instruments']) && is_array($validated['secondary_instruments'])) {
            $validated['secondary_instruments'] = implode(', ', array_filter($validated['secondary_instruments']));
        }

        // ✅ STORE ALL DATA IN SESSION - NOT DATABASE YET
        session([
            'registration_data' => $validated,
            'registration_role' => 'student'
        ]);

        // Redirect to password creation page
        return redirect()->route('account.create')
            ->with('success', 'Please create your password to complete registration.');
    }

    // ============================================================================
    // INSTRUCTOR REGISTRATION - SESSION-BASED
    // ============================================================================

    /**
     * Display the instructor registration form
     */
    public function showInstructorRegistrationForm()
    {
        $specializations = DB::table('specialization')
            ->whereRaw('is_active = TRUE')
            ->orderBy('specialization_name')
            ->get();

        return view('auth.register.instructor', compact('specializations'));
    }

    /**
     * Process instructor registration - STORES IN SESSION ONLY
     * NO DATABASE INSERTION UNTIL PASSWORD CREATION
     */
    public function registerInstructor(Request $request)
    {
        // Validate ALL data including email uniqueness
        $validated = $request->validate([
            // Account - Email uniqueness check BEFORE storing
            'user_email' => 'required|email|unique:user_account,user_email',
            
            // Personal Info
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:20',
            'phone' => 'required|regex:/^[0-9]{11}$/',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other,Prefer not to say',
            'nationality' => 'nullable|string|max:100',
            
            // Address (ALL REQUIRED)
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            
            // Emergency Contact (ALL REQUIRED)
            'emergency_contact_name' => 'required|string|max:100',
            'emergency_contact_relationship' => 'required|string|max:100',
            'emergency_contact_phone' => 'required|regex:/^[0-9]{11}$/',
            
            // Professional
            'education_level' => 'required|string|max:100',
            'music_degree' => 'nullable|string|max:200',
            'years_of_experience' => 'required|integer|min:0',
            'languages_spoken' => 'nullable|string|max:255',
            'teaching_style' => 'nullable|string',
            'bio' => 'nullable|string',
            'certifications' => 'nullable|array',
            'certifications.*' => 'nullable|string|max:255',
            
            // Specializations
            'specializations' => 'required|array|min:1',
            'specializations.*' => 'exists:specialization,specialization_id',
            'primary_specialization' => ['required', Rule::in($request->input('specializations', []))],
        ], [
            'user_email.unique' => 'This email is already registered. Please use a different email or login.',
            'phone.regex' => 'Phone number must be exactly 11 digits.',
            'emergency_contact_phone.regex' => 'Emergency contact phone must be exactly 11 digits.',
            'specializations.required' => 'Please select at least one specialization.',
            'primary_specialization.in' => 'Primary specialization must be one of your selected specializations.',
        ]);

        // STORE ALL DATA IN SESSION - NOT DATABASE YET
        session([
            'registration_data' => $validated,
            'registration_role' => 'instructor'
        ]);

        // Redirect to password creation page
        return redirect()->route('account.create')
            ->with('success', 'Please create your password to complete registration.');
    }


    // ============================================================================
    // PASSWORD SETUP (CREATE ACCOUNT) - THIS IS WHERE DATABASE INSERTION HAPPENS
    // ============================================================================

    /**
     * Show the create account (password setup) form
     * Retrieves registration data from session
     */
    public function showCreateAccountForm()
    {
        // Get registration data from session
        $registrationData = session('registration_data');
        $role = session('registration_role');
        
        // Validate session data exists
        if (!$registrationData || !$role) {
            return redirect()->route('register')
                ->withErrors(['error' => 'Registration session expired. Please start registration again.']);
        }

        $email = $registrationData['user_email'];

        // Double-check email is still available (in case session was old)
        $existingUser = UserAccount::where('user_email', $email)->first();
        if ($existingUser) {
            // Clear invalid session
            session()->forget(['registration_data', 'registration_role']);
            
            return redirect()->route('register')
                ->withErrors(['error' => 'This email is already registered. Please login or use a different email.']);
        }

        return view('auth.create-account', compact('email', 'role'));
    }

    /**
     * Process password setup and CREATE ALL DATABASE RECORDS
     * THIS IS WHERE EVERYTHING GETS SAVED TO DATABASE
     */
    public function processCreateAccount(Request $request)
    {
        // Validate password
        $passwordValidated = $request->validate([
            'user_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Get registration data from session
        $registrationData = session('registration_data');
        $role = session('registration_role');

        // Validate session still exists
        if (!$registrationData || !$role) {
            return redirect()->route('register')
                ->withErrors(['error' => 'Registration session expired. Please start again.']);
        }

        // Extract email from session data
        $email = $registrationData['user_email'];

        // Final email uniqueness check before insertion
        if (UserAccount::where('user_email', $email)->exists()) {
            session()->forget(['registration_data', 'registration_role']);
            return redirect()->route('register')
                ->withErrors(['error' => 'This email was just registered by someone else. Please use a different email.']);
        }

        // ============================================================================
        // BEGIN TRANSACTION - SAVE EVERYTHING TO DATABASE
        // ============================================================================
        DB::beginTransaction();

        try {
            // Step 1: Create user_account with password
            $user = UserAccount::create([
                'user_email' => $email,
                'user_password' => $passwordValidated['user_password'], // Will be hashed by mutator
            ]);

            // Step 2: Create role-specific record based on registration type
            switch ($role) {
                case 'student':
                    $this->createStudentRecord($user->user_id, $registrationData);
                    $dashboardRoute = 'student.dashboard';
                    break;

                case 'instructor':
                    $this->createInstructorRecord($user->user_id, $registrationData);
                    $dashboardRoute = 'instructor.dashboard';
                    break;

                default:
                    throw new \Exception('Invalid role type');
            }
            DB::commit();

            // Clear registration session data
            session()->forget(['registration_data', 'registration_role']);

            // Auto-login the user and regenerate the session ID.
            Auth::login($user);
            $request->session()->regenerate();

            // Redirect to appropriate dashboard
            $redirect = redirect()->route($dashboardRoute)
                ->with('success', 'Welcome! Your account has been created successfully.');

            /*
            |--------------------------------------------------------------------------
            | Clear Student Registration Browser Draft After Successful Account Creation
            |--------------------------------------------------------------------------
            |
            | The browser localStorage draft should only be cleared after the account
            | is successfully created and the user is logged in.
            |
            | Important:
            | - Do not clear the draft after Step 3 Complete Registration.
            | - Do not clear the draft when the user clicks Back to registration.
            | - The JavaScript reads this temporary cookie and clears localStorage.
            |
            */
            if ($role === 'student') {
                $redirect->withCookie(cookie(
                    'musiclab_clear_student_registration_draft',
                    '1',
                    10,
                    '/',
                    null,
                    false,
                    false,
                    false,
                    'Lax'
                ));
            }

            return $redirect;

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log detailed error for developers
            \Log::error('Registration failed', [
                'email' => $email,
                'role' => $role,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // User-friendly error message
            return back()
                ->withErrors(['error' => 'We encountered a problem creating your account. Please try again or contact support if the issue persists.'])
                ->withInput();
        }
    }

    // ============================================================================
    // HELPER METHODS - CREATE ROLE-SPECIFIC RECORDS
    // ============================================================================

    /*
     * Create student record in database
     * Called from processCreateAccount() after password is set
     */
    private function createStudentRecord($userId, $data)
    {
        $activeStatusId = DB::table('student_status')
            ->where('status_name', 'Active')
            ->value('status_id');

        if (!$activeStatusId) {
            throw new \Exception('Active student status not found in database.');
        }

        DB::table('student')->insert([
            'user_id' => $userId,
            
            // Personal Information
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'suffix' => $data['suffix'] ?? null,
            'phone' => $data['phone'],
            'email' => $data['user_email'],
            
            // Address
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'],
            'province' => $data['province'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
            
            // Personal Details
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'nationality' => $data['nationality'] ?? null,
            
            // Medical Information
            'medical_conditions' => $data['medical_conditions'] ?? null,
            'allergies' => $data['allergies'] ?? null,
            'special_needs' => $data['special_needs'] ?? null,
            
            // Emergency Contact
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_relationship' => $data['emergency_contact_relationship'],
            'emergency_contact_phone' => $data['emergency_contact_phone'],
            
            // Parent/Guardian
            'parent_guardian_name' => $data['parent_guardian_name'],
            'parent_guardian_relationship' => $data['parent_guardian_relationship'],
            'parent_guardian_phone' => $data['parent_guardian_phone'],
            'parent_guardian_email' => $data['parent_guardian_email'] ?? null,
            'parent_guardian_address' => $data['parent_guardian_address'] ?? null,
            
            // Musical Background
            'instrument_id' => $data['instrument_id'],
            'secondary_instruments' => $data['secondary_instruments'] ?? null,
            'previous_music_experience' => $data['previous_music_experience'] ?? null,
            'skill_level' => $data['skill_level'],
            'music_goals' => $data['music_goals'] ?? null,
            'preferred_genre_id' => $data['preferred_genre_id'] ?? null,
            
            // Educational Background
            'school_name' => $data['school_name'] ?? null,
            'grade_level' => $data['grade_level'] ?? null,
            
            // System Fields
            'student_status_id' => $activeStatusId,
            'enrollment_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create instructor record with specializations in database
     */
    private function createInstructorRecord($userId, $data)
    {
        // Insert instructor record
        DB::table('instructor')->insert([
            'user_id' => $userId,
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'suffix' => $data['suffix'] ?? null,
            'phone' => $data['phone'],
            'email' => $data['user_email'],
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'nationality' => $data['nationality'] ?? null,
            
            // Address
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'],
            'province' => $data['province'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
            
            // Emergency contact
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_relationship' => $data['emergency_contact_relationship'],
            'emergency_contact_phone' => $data['emergency_contact_phone'],
            
            // Professional
            'years_of_experience' => $data['years_of_experience'],
            'education_level' => $data['education_level'],
            'music_degree' => $data['music_degree'] ?? null,
            'languages_spoken' => $data['languages_spoken'] ?? null,
            'teaching_style' => $data['teaching_style'] ?? null,
            'bio' => $data['bio'] ?? null,
            
            'hire_date' => now(),
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get the instructor_id we just created
        $instructorId = DB::table('instructor')
            ->where('user_id', $userId)
            ->value('instructor_id');

        if (!$instructorId) {
            throw new \Exception('Failed to retrieve instructor_id after creation.');
        }

        // Insert specializations
        foreach ($data['specializations'] as $specializationId) {
            DB::table('instructor_specialization')->insert([
                'instructor_id' => $instructorId,
                'specialization_id' => $specializationId,
                'is_primary' => ($specializationId == $data['primary_specialization']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

}