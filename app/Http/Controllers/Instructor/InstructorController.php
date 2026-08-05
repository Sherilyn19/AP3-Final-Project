<?php

// app/Http/Controllers/Instructor/InstructorController.php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;

class InstructorController extends Controller
{
    /**
     * Fallback entry point for the instructor portal.
     * The main dashboard uses InstructorDashboardController.
     */
    public function index()
    {
        return redirect()->route('instructor.dashboard');
    }

    /**
     * Keep old calls safe by redirecting to the current profile route.
     */
    public function profile()
    {
        return redirect()->route('instructor.profile.index');
    }

    /**
     * Availability is currently edited through the profile page.
     */
    public function availability()
    {
        return redirect()->route('instructor.profile.index');
    }

    /**
     * Quick redirect to the instructor schedule page.
     */
    public function mySchedule()
    {
        return redirect()->route('instructor.schedule.index');
    }
}
