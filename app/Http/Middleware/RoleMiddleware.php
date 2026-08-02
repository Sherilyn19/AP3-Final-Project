<?php

// app/Http/Middleware/RoleMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        $userId = $user->user_id;

        // Super admin shortcut (optional)
        if (in_array('admin', $roles, true) && $user->is_super_admin) {
            return $next($request);
        }

        // Check roles based on your tables
        foreach ($roles as $role) {
            if (
                $role === 'instructor'
                && DB::table('instructor')
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->exists()
            ) {
                return $next($request);
            }

            if (
                $role === 'student'
                && DB::table('student')
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->exists()
            ) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized.');
    }
}