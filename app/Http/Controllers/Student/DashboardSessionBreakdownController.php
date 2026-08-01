<?php

// app/Http/Controllers/Student/DashboardSessionBreakdownController.php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DashboardSessionBreakdownController extends Controller
{
    public function index(Request $request)
    {
        try {
            $student = $this->getAuthenticatedStudent();

            if (!$student) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Student record was not found for the current logged-in account.',
                    'completed' => [],
                    'remaining_enrollments' => [],
                    'counts' => [
                        'completed_from_enrollment' => 0,
                        'remaining_from_enrollment' => 0,
                    ],
                ]);
            }

            $activeEnrollments = $this->getActiveEnrollments((int) $student->student_id);
            $counts = [
                'active_packages' => (int) $activeEnrollments->where('status', 'active')->count(),
                'completed_from_enrollment' => (int) $activeEnrollments->sum('completed_sessions'),
                'remaining_from_enrollment' => (int) $activeEnrollments->sum('remaining_sessions'),
                'total_from_enrollment' => (int) $activeEnrollments->sum('total_sessions'),
            ];

            $completed = $this->getCompletedDetails((int) $student->student_id);
            $remainingEnrollments = $this->getRemainingEnrollmentDetails((int) $student->student_id, $activeEnrollments);

            return response()->json([
                'ok' => true,
                'student_id' => (int) $student->student_id,
                'counts' => $counts,
                'completed' => $completed,
                'remaining_enrollments' => $remainingEnrollments,
                'sources' => [
                    'dashboard_counts' => 'enrollment.completed_sessions and enrollment.remaining_sessions',
                    'completed_details' => 'attendance + schedule, with progress as supporting detail',
                    'remaining_details' => 'enrollment remaining_sessions + confirmed schedule records',
                ],
                'verification_note' => 'If the dashboard count is greater than the detailed list, the count exists in enrollment but the matching attendance/schedule/progress detail may not be encoded yet.',
            ]);
        } catch (Throwable $error) {
            Log::error('Student dashboard session breakdown failed.', [
                'user_id' => Auth::id(),
                'message' => $error->getMessage(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Unable to load session breakdown.',
                'error' => config('app.debug') ? $error->getMessage() : null,
                'completed' => [],
                'remaining_enrollments' => [],
                'counts' => [
                    'completed_from_enrollment' => 0,
                    'remaining_from_enrollment' => 0,
                ],
            ]);
        }
    }

    private function getAuthenticatedStudent(): ?object
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $userId = $user->user_id ?? Auth::id();

        if (!$userId || !Schema::hasTable('student')) {
            return null;
        }

        $student = DB::table('student')
            ->where('user_id', $userId)
            ->first();

        if ($student) {
            return $student;
        }

        $email = $user->user_email ?? $user->email ?? null;

        if ($email && Schema::hasColumn('student', 'email')) {
            return DB::table('student')
                ->where('email', $email)
                ->first();
        }

        return null;
    }

    private function getActiveEnrollments(int $studentId)
    {
        return DB::table('enrollment as e')
            ->leftJoin('lesson_session as ls', 'e.session_id', '=', 'ls.session_id')
            ->leftJoin('instrument as inst', 'e.instrument_id', '=', 'inst.instrument_id')
            ->leftJoin('instructor as i', 'e.instructor_id', '=', 'i.instructor_id')
            ->where('e.student_id', $studentId)
            ->whereIn('e.status', ['active', 'withdrawal_requested'])
            ->select(
                'e.*',
                'ls.session_count',
                'ls.session_name',
                'ls.duration_minutes',
                'inst.instrument_name',
                DB::raw("TRIM(CONCAT(i.first_name, ' ', COALESCE(i.middle_name, ''), ' ', i.last_name, ' ', COALESCE(i.suffix, ''))) as instructor_full_name")
            )
            ->orderByDesc('e.enrollment_date')
            ->orderByDesc('e.created_at')
            ->get();
    }

    private function getCompletedDetails(int $studentId): array
    {
        $items = [];

        if (Schema::hasTable('attendance')) {
            $attendanceRows = DB::table('attendance as a')
                ->join('schedule as s', 'a.schedule_id', '=', 's.schedule_id')
                ->leftJoin('enrollment as e', 's.enrollment_id', '=', 'e.enrollment_id')
                ->leftJoin('instrument as inst', 'e.instrument_id', '=', 'inst.instrument_id')
                ->leftJoin('instructor as i', 's.instructor_id', '=', 'i.instructor_id')
                ->leftJoin('lesson_session as ls', 'e.session_id', '=', 'ls.session_id')
                ->where('a.student_id', $studentId)
                ->where('a.attendance_type', 'lesson')
                ->whereIn(DB::raw('LOWER(a.attendance_status)'), ['present', 'completed', 'attended'])
                ->select(
                    'a.attendance_date',
                    'a.attendance_status',
                    's.schedule_id',
                    's.schedule_date',
                    's.start_time',
                    's.end_time',
                    's.status as schedule_status',
                    's.lesson_topic',
                    's.lesson_content',
                    'inst.instrument_name',
                    'ls.session_name',
                    'e.enrollment_id',
                    DB::raw("TRIM(CONCAT(i.first_name, ' ', COALESCE(i.middle_name, ''), ' ', i.last_name, ' ', COALESCE(i.suffix, ''))) as instructor_full_name")
                )
                ->orderByDesc('a.attendance_date')
                ->orderByDesc('s.start_time')
                ->limit(30)
                ->get();

            foreach ($attendanceRows as $row) {
                $items[] = [
                    'source' => 'Attendance record',
                    'title' => $this->makeCompletedTitle($row),
                    'date' => $this->formatDate($row->attendance_date ?? $row->schedule_date ?? null),
                    'time' => $this->formatTime($row->start_time ?? null, $row->end_time ?? null),
                    'instructor' => $this->clean($row->instructor_full_name ?? null, 'Not set'),
                    'instrument' => $this->clean($row->instrument_name ?? null, 'Not set'),
                    'package' => $this->clean($row->session_name ?? null, 'Not set'),
                    'status' => $this->formatStatus($row->attendance_status ?? 'Completed'),
                    'lesson_topic' => $this->clean($row->lesson_topic ?? null, 'No lesson topic encoded'),
                    'remarks' => $this->clean($row->lesson_content ?? null, 'Attendance confirms this lesson as completed.'),
                ];
            }
        }

        if (Schema::hasTable('schedule')) {
            $scheduleRows = DB::table('schedule as s')
                ->leftJoin('enrollment as e', 's.enrollment_id', '=', 'e.enrollment_id')
                ->leftJoin('instrument as inst', 'e.instrument_id', '=', 'inst.instrument_id')
                ->leftJoin('instructor as i', 's.instructor_id', '=', 'i.instructor_id')
                ->leftJoin('lesson_session as ls', 'e.session_id', '=', 'ls.session_id')
                ->where('s.student_id', $studentId)
                ->whereIn('s.status', ['completed', 'done', 'finished'])
                ->select(
                    's.*',
                    'inst.instrument_name',
                    'ls.session_name',
                    'e.enrollment_id',
                    DB::raw("TRIM(CONCAT(i.first_name, ' ', COALESCE(i.middle_name, ''), ' ', i.last_name, ' ', COALESCE(i.suffix, ''))) as instructor_full_name")
                )
                ->orderByDesc('s.schedule_date')
                ->orderByDesc('s.start_time')
                ->limit(30)
                ->get();

            foreach ($scheduleRows as $row) {
                $items[] = [
                    'source' => 'Schedule status',
                    'title' => $this->makeCompletedTitle($row),
                    'date' => $this->formatDate($row->schedule_date ?? null),
                    'time' => $this->formatTime($row->start_time ?? null, $row->end_time ?? null),
                    'instructor' => $this->clean($row->instructor_full_name ?? null, 'Not set'),
                    'instrument' => $this->clean($row->instrument_name ?? null, 'Not set'),
                    'package' => $this->clean($row->session_name ?? null, 'Not set'),
                    'status' => $this->formatStatus($row->status ?? 'Completed'),
                    'lesson_topic' => $this->clean($row->lesson_topic ?? null, 'No lesson topic encoded'),
                    'remarks' => $this->clean($row->lesson_content ?? $row->notes ?? null, 'Schedule is marked as completed.'),
                ];
            }
        }

        if (Schema::hasTable('progress')) {
            $progressRows = DB::table('progress as p')
                ->leftJoin('enrollment as e', 'p.enrollment_id', '=', 'e.enrollment_id')
                ->leftJoin('instrument as inst', 'e.instrument_id', '=', 'inst.instrument_id')
                ->leftJoin('instructor as i', 'p.instructor_id', '=', 'i.instructor_id')
                ->leftJoin('lesson_session as ls', 'e.session_id', '=', 'ls.session_id')
                ->where('p.student_id', $studentId)
                ->select(
                    'p.*',
                    'inst.instrument_name',
                    'ls.session_name',
                    'e.enrollment_id',
                    DB::raw("TRIM(CONCAT(i.first_name, ' ', COALESCE(i.middle_name, ''), ' ', i.last_name, ' ', COALESCE(i.suffix, ''))) as instructor_full_name")
                )
                ->orderByDesc('p.progress_date')
                ->limit(10)
                ->get();

            foreach ($progressRows as $row) {
                $items[] = [
                    'source' => 'Progress record',
                    'title' => 'Progress Record - ' . $this->formatDate($row->progress_date ?? null),
                    'date' => $this->formatDate($row->progress_date ?? null),
                    'time' => 'Not set',
                    'instructor' => $this->clean($row->instructor_full_name ?? null, 'Not set'),
                    'instrument' => $this->clean($row->instrument_name ?? null, 'Not set'),
                    'package' => $this->clean($row->session_name ?? null, 'Not set'),
                    'status' => 'Recorded progress',
                    'lesson_topic' => $this->clean($row->lesson_topic ?? $row->topic ?? null, 'Progress record'),
                    'remarks' => $this->clean($row->notes ?? $row->remarks ?? $row->comment ?? null, 'Instructor progress record exists for this student.'),
                ];
            }
        }

        return array_values($this->uniqueItems($items));
    }

    private function getRemainingEnrollmentDetails(int $studentId, $activeEnrollments): array
    {
        $details = [];

        foreach ($activeEnrollments as $enrollment) {
            $remaining = (int) ($enrollment->remaining_sessions ?? 0);
            $completed = (int) ($enrollment->completed_sessions ?? 0);
            $total = (int) ($enrollment->total_sessions ?? $enrollment->session_count ?? 0);

            $scheduledLessons = DB::table('schedule as s')
                ->leftJoin('instructor as i', 's.instructor_id', '=', 'i.instructor_id')
                ->where('s.student_id', $studentId)
                ->where('s.enrollment_id', $enrollment->enrollment_id)
                ->whereIn('s.status', ['scheduled', 'in_progress'])
                ->select(
                    's.*',
                    DB::raw("TRIM(CONCAT(i.first_name, ' ', COALESCE(i.middle_name, ''), ' ', i.last_name, ' ', COALESCE(i.suffix, ''))) as instructor_full_name")
                )
                ->orderBy('s.schedule_date')
                ->orderBy('s.start_time')
                ->get();

            $futureLessons = $scheduledLessons->filter(function ($lesson) {
                return $this->isTodayOrFuture($lesson->schedule_date ?? null);
            })->values();

            $nextLesson = $futureLessons->first();

            $scheduledRemainingCount = (int) $scheduledLessons->count();
            $confirmedUpcomingCount = (int) $futureLessons->count();
            $unscheduledSlots = max($remaining - $scheduledRemainingCount, 0);

            $details[] = [
                'enrollment_id' => $enrollment->enrollment_id,
                'package' => $this->clean($enrollment->session_name ?? null, 'Lesson Package'),
                'instrument' => $this->clean($enrollment->instrument_name ?? null, 'Not set'),
                'instructor' => $this->clean($enrollment->instructor_full_name ?? null, 'Not set'),
                'status' => $this->formatStatus($enrollment->status ?? 'active'),
                'remaining_sessions' => $remaining,
                'completed_sessions' => $completed,
                'total_sessions' => $total,
                'scheduled_remaining_count' => $scheduledRemainingCount,
                'confirmed_upcoming_count' => $confirmedUpcomingCount,
                'unscheduled_remaining_slots' => $unscheduledSlots,
                'start_date' => $this->formatDate($enrollment->start_date ?? null),
                'end_date' => $this->formatDate($enrollment->end_date ?? null),
                'preferred_days' => $this->clean($enrollment->preferred_lesson_days ?? null, 'Not set'),
                'preferred_time' => $this->clean($enrollment->preferred_lesson_time ?? null, 'Not set'),
                'next_lesson' => $nextLesson ? $this->formatLessonItem($nextLesson, $enrollment) : null,
                'upcoming_lessons' => $futureLessons->take(3)->map(function ($lesson) use ($enrollment) {
                    return $this->formatLessonItem($lesson, $enrollment);
                })->values()->all(),
                'source_note' => 'Remaining count comes from enrollment.remaining_sessions. Confirmed lessons come from schedule records.',
            ];
        }

        return $details;
    }

    private function getUpcomingLessons(int $studentId): array
    {
        if (!Schema::hasTable('schedule')) {
            return [];
        }

        $rows = DB::table('schedule as s')
            ->leftJoin('enrollment as e', 's.enrollment_id', '=', 'e.enrollment_id')
            ->leftJoin('lesson_session as ls', 'e.session_id', '=', 'ls.session_id')
            ->leftJoin('instrument as inst', 'e.instrument_id', '=', 'inst.instrument_id')
            ->leftJoin('instructor as i', 's.instructor_id', '=', 'i.instructor_id')
            ->where('s.student_id', $studentId)
            ->whereDate('s.schedule_date', '>=', now()->toDateString())
            ->whereRaw("LOWER(COALESCE(s.status, 'scheduled')) NOT IN ('completed', 'done', 'finished', 'cancelled', 'canceled', 'rejected', 'withdrawn')")
            ->select(
                's.schedule_id',
                's.schedule_date',
                's.start_time',
                's.end_time',
                's.status',
                's.lesson_topic',
                's.lesson_content',
                's.notes',
                'e.enrollment_id',
                'ls.session_name',
                'inst.instrument_name',
                DB::raw("TRIM(CONCAT(i.first_name, ' ', COALESCE(i.middle_name, ''), ' ', i.last_name, ' ', COALESCE(i.suffix, ''))) as instructor_full_name")
            )
            ->orderBy('s.schedule_date')
            ->orderBy('s.start_time')
            ->limit(200)
            ->get();

        return $rows->map(function ($row) {
            return [
                'schedule_id' => $row->schedule_id,
                'title' => $this->clean($row->lesson_topic ?? null, 'Confirmed Lesson'),
                'date' => $this->formatDate($row->schedule_date ?? null),
                'date_key' => $row->schedule_date ? Carbon::parse($row->schedule_date)->toDateString() : null,
                'time' => $this->formatTime($row->start_time ?? null, $row->end_time ?? null),
                'instructor' => $this->clean($row->instructor_full_name ?? null, 'Not set'),
                'instrument' => $this->clean($row->instrument_name ?? null, 'Not set'),
                'package' => $this->clean($row->session_name ?? null, 'Lesson Package'),
                'status' => $this->formatStatus($row->status ?? 'Scheduled'),
                'lesson_topic' => $this->clean($row->lesson_topic ?? null, 'No lesson topic encoded yet'),
                'remarks' => $this->clean($row->lesson_content ?? $row->notes ?? null, 'Confirmed upcoming schedule record.'),
                'source' => 'schedule table',
            ];
        })->values()->all();
    }
    private function formatLessonItem(object $lesson, object $enrollment): array
    {
        return [
            'title' => 'Confirmed Lesson - ' . $this->formatDate($lesson->schedule_date ?? null),
            'date' => $this->formatDate($lesson->schedule_date ?? null),
            'time' => $this->formatTime($lesson->start_time ?? null, $lesson->end_time ?? null),
            'instructor' => $this->clean($lesson->instructor_full_name ?? $enrollment->instructor_full_name ?? null, 'Not set'),
            'instrument' => $this->clean($enrollment->instrument_name ?? null, 'Not set'),
            'package' => $this->clean($enrollment->session_name ?? null, 'Lesson Package'),
            'status' => $this->formatStatus($lesson->status ?? 'scheduled'),
            'lesson_topic' => $this->clean($lesson->lesson_topic ?? null, 'No lesson topic encoded yet'),
            'remarks' => $this->clean($lesson->notes ?? $lesson->lesson_content ?? null, 'Confirmed schedule record.'),
        ];
    }

    private function makeCompletedTitle(object $row): string
    {
        $topic = $this->clean($row->lesson_topic ?? null, '');

        if ($topic !== '') {
            return $topic;
        }

        $date = $this->formatDate($row->attendance_date ?? $row->schedule_date ?? $row->progress_date ?? null);

        return 'Completed Lesson - ' . $date;
    }

    private function uniqueItems(array $items): array
    {
        $seen = [];
        $unique = [];

        foreach ($items as $item) {
            $key = strtolower(
                ($item['date'] ?? '') . '|' .
                ($item['time'] ?? '') . '|' .
                ($item['instrument'] ?? '') . '|' .
                ($item['lesson_topic'] ?? '') . '|' .
                ($item['source'] ?? '')
            );

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return 'Not set';
        }

        try {
            return Carbon::parse($value)->format('M d, Y');
        } catch (Throwable $error) {
            return (string) $value;
        }
    }

    private function formatTime($start, $end = null): string
    {
        if (!$start && !$end) {
            return 'Not set';
        }

        try {
            $startText = $start ? Carbon::parse($start)->format('h:i A') : 'Not set';
            $endText = $end ? Carbon::parse($end)->format('h:i A') : null;

            return $endText ? $startText . ' - ' . $endText : $startText;
        } catch (Throwable $error) {
            return trim((string) $start . ($end ? ' - ' . $end : ''));
        }
    }

    private function formatStatus($value): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return 'Not set';
        }

        return ucwords(str_replace('_', ' ', strtolower($text)));
    }

    private function clean($value, string $default = 'Not set'): string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) ($value ?? '')));

        return $text !== '' ? $text : $default;
    }

    private function isTodayOrFuture($date): bool
    {
        if (!$date) {
            return false;
        }

        try {
            return Carbon::parse($date)->toDateString() >= now()->toDateString();
        } catch (Throwable $error) {
            return false;
        }
    }
}
