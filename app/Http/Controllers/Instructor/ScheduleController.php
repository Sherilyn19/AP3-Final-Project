<?php
// app/Http/Controllers/Instructor/ScheduleController.php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    /**
     * Return the logged-in instructor ID or block access.
     */
    private function instructorIdOrAbort(): int
    {
        $instructorId = DB::table('instructor')
            ->where('user_id', Auth::user()->user_id)
            ->value('instructor_id');

        if (!$instructorId) {
            abort(403, 'Instructor profile not found.');
        }

        return (int) $instructorId;
    }

    /**
     * Show instructor schedules.
     * Default is ALL to avoid the page looking empty when demo schedules are mostly past records.
     */
    public function index(Request $request)
    {
        $instructorId = $this->instructorIdOrAbort();
        $today = Carbon::today()->toDateString();
        $filter = $request->query('filter', 'all');
        $q = trim((string) $request->query('q', ''));

        $base = DB::table('schedule as s')
            ->join('student as st', 'st.student_id', '=', 's.student_id')
            ->leftJoin('enrollment as e', 'e.enrollment_id', '=', 's.enrollment_id')
            ->leftJoin('instrument as ins', 'ins.instrument_id', '=', 'e.instrument_id')
            ->leftJoin('attendance as a', function ($join) {
                $join->on('a.schedule_id', '=', 's.schedule_id')
                    ->where('a.attendance_type', '=', 'lesson');
            })
            ->where('s.instructor_id', $instructorId);

        if ($filter === 'today') {
            $base->whereDate('s.schedule_date', $today);
        } elseif ($filter === 'week') {
            $base->whereBetween('s.schedule_date', [
                Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
                Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            ]);
        } elseif ($filter === 'upcoming') {
            $base->whereDate('s.schedule_date', '>=', $today);
        } elseif ($filter === 'past') {
            $base->whereDate('s.schedule_date', '<', $today);
        }

        if ($q !== '') {
            $base->where(function ($query) use ($q) {
                $query->where('st.first_name', 'ilike', "%{$q}%")
                    ->orWhere('st.last_name', 'ilike', "%{$q}%")
                    ->orWhere('s.lesson_topic', 'ilike', "%{$q}%")
                    ->orWhere('s.room_number', 'ilike', "%{$q}%")
                    ->orWhere('ins.instrument_name', 'ilike', "%{$q}%");
            });
        }

        $schedules = $base
            ->select([
                's.schedule_id',
                's.enrollment_id',
                's.student_id',
                's.room_number',
                's.schedule_date',
                's.start_time',
                's.end_time',
                's.duration_minutes',
                's.status',
                's.lesson_topic',
                's.notes',
                'a.attendance_status',
                'e.total_sessions',
                'e.completed_sessions',
                'e.remaining_sessions',
                'e.preferred_lesson_days',
                'e.preferred_lesson_time',
                'e.status as enrollment_status',
                'ins.instrument_name',
                DB::raw("TRIM(st.first_name || ' ' || st.last_name) as student_name"),
            ])
            ->orderBy('s.schedule_date')
            ->orderBy('s.start_time')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'all' => DB::table('schedule')->where('instructor_id', $instructorId)->count(),
            'today' => DB::table('schedule')->where('instructor_id', $instructorId)->whereDate('schedule_date', $today)->count(),
            'upcoming' => DB::table('schedule')->where('instructor_id', $instructorId)->whereDate('schedule_date', '>=', $today)->count(),
            'past' => DB::table('schedule')->where('instructor_id', $instructorId)->whereDate('schedule_date', '<', $today)->count(),
        ];

        return view('instructor.schedule.index', compact('schedules', 'filter', 'q', 'stats'));
    }

    /**
     * Show schedule creation form.
     */
    public function create()
    {
        $instructorId = $this->instructorIdOrAbort();

        $students = DB::table('enrollment as e')
            ->join('student as st', 'st.student_id', '=', 'e.student_id')
            ->join('instrument as ins', 'ins.instrument_id', '=', 'e.instrument_id')
            ->where('e.instructor_id', $instructorId)
            ->where('e.status', 'active')
            ->where('e.remaining_sessions', '>', 0)
            ->select([
                'st.student_id',
                DB::raw("TRIM(st.first_name || ' ' || st.last_name) as student_name"),
                'ins.instrument_name',
                'e.enrollment_id',
                'e.remaining_sessions',
                'e.preferred_lesson_days',
                'e.preferred_lesson_time',
            ])
            ->orderBy('st.last_name')
            ->orderBy('st.first_name')
            ->get();

        $rooms = DB::table('room')
            ->where('is_active', true)
            ->orderBy('room_number')
            ->get(['room_number', 'room_name', 'capacity']);

        return view('instructor.schedule.create', compact('students', 'rooms'));
    }

    /**
     * Save new schedule for the logged-in instructor only.
     */
    public function store(Request $request)
    {
        $instructorId = $this->instructorIdOrAbort();
        $data = $this->validatedScheduleData($request);

        $enrollment = $this->activeEnrollmentForStudent($instructorId, (int) $data['student_id']);

        if (!$enrollment) {
            return back()->withInput()->with('error', 'This student has no active enrollment with remaining sessions under your account.');
        }

        if ($this->hasInstructorConflict($instructorId, $data['schedule_date'], $data['start_time'], $data['end_time'])) {
            return back()->withInput()->with('error', 'You already have another class within this time range.');
        }

        if (!empty($data['room_number']) && $this->hasRoomConflict($data['room_number'], $data['schedule_date'], $data['start_time'], $data['end_time'])) {
            return back()->withInput()->with('error', 'This room is already booked or scheduled within this time range.');
        }

        Schedule::create([
            'student_id' => $data['student_id'],
            'instructor_id' => $instructorId,
            'enrollment_id' => $enrollment->enrollment_id,
            'schedule_date' => $data['schedule_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $this->durationMinutes($data['start_time'], $data['end_time']),
            'room_number' => $data['room_number'] ?? null,
            'lesson_topic' => $data['lesson_topic'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'scheduled',
        ]);

        return redirect()->route('instructor.schedule.index')->with('success', 'Schedule created successfully.');
    }

    /**
     * Show schedule edit form.
     */
    public function edit($scheduleId)
    {
        $instructorId = $this->instructorIdOrAbort();

        $schedule = DB::table('schedule as s')
            ->join('student as st', 'st.student_id', '=', 's.student_id')
            ->leftJoin('enrollment as e', 'e.enrollment_id', '=', 's.enrollment_id')
            ->leftJoin('instrument as ins', 'ins.instrument_id', '=', 'e.instrument_id')
            ->where('s.schedule_id', $scheduleId)
            ->where('s.instructor_id', $instructorId)
            ->select([
                's.*',
                DB::raw("TRIM(st.first_name || ' ' || st.last_name) as student_name"),
                'ins.instrument_name',
                'e.remaining_sessions',
                'e.preferred_lesson_days',
                'e.preferred_lesson_time',
            ])
            ->first();

        if (!$schedule) {
            abort(404, 'Schedule not found.');
        }

        $rooms = DB::table('room')
            ->where('is_active', true)
            ->orderBy('room_number')
            ->get(['room_number', 'room_name', 'capacity']);

        return view('instructor.schedule.edit', compact('schedule', 'rooms'));
    }

    /**
     * Update schedule owned by the logged-in instructor.
     */
    public function update(Request $request, $scheduleId)
    {
        $instructorId = $this->instructorIdOrAbort();

        $schedule = Schedule::where('schedule_id', $scheduleId)
            ->where('instructor_id', $instructorId)
            ->firstOrFail();

        $data = $request->validate([
            'schedule_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room_number' => ['nullable', 'string', 'max:50', Rule::exists('room', 'room_number')],
            'lesson_topic' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:scheduled,in_progress,completed,cancelled,no_class,rescheduled'],
        ]);

        if ($this->hasInstructorConflict($instructorId, $data['schedule_date'], $data['start_time'], $data['end_time'], (int) $schedule->schedule_id)) {
            return back()->withInput()->with('error', 'You already have another class within this time range.');
        }

        if (!empty($data['room_number']) && $this->hasRoomConflict($data['room_number'], $data['schedule_date'], $data['start_time'], $data['end_time'], (int) $schedule->schedule_id)) {
            return back()->withInput()->with('error', 'This room is already booked or scheduled within this time range.');
        }

        $schedule->update([
            'schedule_date' => $data['schedule_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $this->durationMinutes($data['start_time'], $data['end_time']),
            'room_number' => $data['room_number'] ?? null,
            'lesson_topic' => $data['lesson_topic'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'],
        ]);

        return redirect()->route('instructor.schedule.index')->with('success', 'Schedule updated successfully.');
    }

    /**
     * Central validation for create schedule.
     */
    private function validatedScheduleData(Request $request): array
    {
        return $request->validate([
            'student_id' => ['required', 'integer', 'exists:student,student_id'],
            'schedule_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room_number' => ['nullable', 'string', 'max:50', Rule::exists('room', 'room_number')],
            'lesson_topic' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    /**
     * Get the latest active enrollment for the selected student under this instructor.
     */
    private function activeEnrollmentForStudent(int $instructorId, int $studentId)
    {
        return DB::table('enrollment')
            ->where('student_id', $studentId)
            ->where('instructor_id', $instructorId)
            ->where('status', 'active')
            ->where('remaining_sessions', '>', 0)
            ->orderByDesc('enrollment_date')
            ->orderByDesc('enrollment_id')
            ->first();
    }

    /**
     * Prevent instructor time overlap.
     */
    private function hasInstructorConflict(int $instructorId, string $date, string $start, string $end, ?int $ignoreScheduleId = null): bool
    {
        return DB::table('schedule')
            ->where('instructor_id', $instructorId)
            ->whereDate('schedule_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_class', 'rescheduled'])
            ->when($ignoreScheduleId, fn ($q) => $q->where('schedule_id', '!=', $ignoreScheduleId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }

    /**
     * Prevent room overlap against both lesson schedules and room bookings.
     */
    private function hasRoomConflict(string $roomNumber, string $date, string $start, string $end, ?int $ignoreScheduleId = null): bool
    {
        $scheduleConflict = DB::table('schedule')
            ->where('room_number', $roomNumber)
            ->whereDate('schedule_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_class', 'rescheduled'])
            ->when($ignoreScheduleId, fn ($q) => $q->where('schedule_id', '!=', $ignoreScheduleId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        $bookingConflict = DB::table('booking')
            ->where('room_number', $roomNumber)
            ->whereDate('booking_date', $date)
            ->whereNotIn('booking_status', ['cancelled'])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        return $scheduleConflict || $bookingConflict;
    }

    /**
     * Calculate lesson duration in minutes.
     */
    private function durationMinutes(string $start, string $end): int
    {
        return Carbon::parse($start)->diffInMinutes(Carbon::parse($end));
    }
}