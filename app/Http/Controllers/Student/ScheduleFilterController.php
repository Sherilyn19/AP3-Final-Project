<?php

// app/Http/Controllers/Student/ScheduleFilterController.php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ScheduleFilterController extends Controller
{
    public function index(Request $request)
    {
        $student = $this->getStudent();

        if (!$student) {
            return response()->json([
                'ok' => false,
                'message' => 'Student record was not found.',
                'stats' => $this->emptyStats(),
                'items' => [],
                'pagination' => $this->emptyPagination(),
            ]);
        }

        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));

        $base = $this->baseQuery((int) $student->student_id);
        $stats = $this->getStats((int) $student->student_id);

        $filtered = clone $base;
        $this->applyFilters($filtered, $request);

        $total = (int) $filtered->count();

        $this->applySorting($filtered, (string) $request->query('sort', 'date_desc'));

        $items = $filtered
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow($row))
            ->values();

        return response()->json([
            'ok' => true,
            'stats' => $stats,
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    private function getStudent(): ?object
    {
        $user = Auth::user();

        if (!$user || !Schema::hasTable('student')) {
            return null;
        }

        $userId = $user->user_id ?? Auth::id();

        if ($userId && Schema::hasColumn('student', 'user_id')) {
            $student = DB::table('student')->where('user_id', $userId)->first();

            if ($student) {
                return $student;
            }
        }

        $email = $user->user_email ?? $user->email ?? null;

        if ($email && Schema::hasColumn('student', 'email')) {
            return DB::table('student')->where('email', $email)->first();
        }

        return null;
    }

    private function baseQuery(int $studentId)
    {
        return DB::table('schedule as s')
            ->leftJoin('enrollment as e', 's.enrollment_id', '=', 'e.enrollment_id')
            ->leftJoin('lesson_session as ls', 'e.session_id', '=', 'ls.session_id')
            ->leftJoin('instrument as inst', 'e.instrument_id', '=', 'inst.instrument_id')
            ->leftJoin('instructor as i', 's.instructor_id', '=', 'i.instructor_id')
            ->where('s.student_id', $studentId)
            ->select([
                's.schedule_id',
                's.enrollment_id',
                's.schedule_date',
                's.start_time',
                's.end_time',
                's.status',
                's.room_number',
                's.lesson_topic',
                's.lesson_content',
                's.notes',
                's.created_at',
                's.updated_at',
                'e.status as enrollment_status',
                'e.completed_sessions',
                'e.remaining_sessions',
                'e.total_sessions',
                'e.start_date',
                'e.end_date',
                'ls.session_name',
                'ls.session_count',
                'inst.instrument_name',
                DB::raw("TRIM(CONCAT(COALESCE(i.first_name, ''), ' ', COALESCE(i.middle_name, ''), ' ', COALESCE(i.last_name, ''), ' ', COALESCE(i.suffix, ''))) as instructor_name"),
            ]);
    }

    private function getStats(int $studentId): array
    {
        $base = $this->baseQuery($studentId);
        $today = now()->toDateString();

        $total = (int) (clone $base)->count();

        $upcoming = (int) (clone $base)
            ->whereDate('s.schedule_date', '>=', $today)
            ->whereNotIn(DB::raw("LOWER(COALESCE(s.status, ''))"), ['completed', 'done', 'finished', 'cancelled', 'canceled', 'rejected'])
            ->count();

        $approved = (int) (clone $base)
            ->whereRaw("LOWER(COALESCE(s.status, '')) = 'approved'")
            ->count();

        $completed = (int) (clone $base)
            ->whereIn(DB::raw("LOWER(COALESCE(s.status, ''))"), ['completed', 'done', 'finished'])
            ->count();

        return [
            'total' => $total,
            'upcoming' => $upcoming,
            'approved' => $approved,
            'completed' => $completed,
        ];
    }

    private function applyFilters($query, Request $request): void
    {
        $status = strtolower((string) $request->query('status', 'all'));
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($status === 'upcoming') {
            $query->whereDate('s.schedule_date', '>=', now()->toDateString())
                ->whereNotIn(DB::raw("LOWER(COALESCE(s.status, ''))"), ['completed', 'done', 'finished', 'cancelled', 'canceled', 'rejected']);
        }

        if ($status === 'approved') {
            $query->whereRaw("LOWER(COALESCE(s.status, '')) = 'approved'");
        }

        if ($status === 'completed') {
            $query->whereIn(DB::raw("LOWER(COALESCE(s.status, ''))"), ['completed', 'done', 'finished']);
        }

        if ($status === 'past') {
            $query->whereDate('s.schedule_date', '<', now()->toDateString());
        }

        if ($dateFrom) {
            $query->whereDate('s.schedule_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('s.schedule_date', '<=', $dateTo);
        }

        if ($search !== '') {
            $keyword = '%' . strtolower($search) . '%';

            $query->where(function ($q) use ($keyword) {
                $q->whereRaw("LOWER(COALESCE(s.lesson_topic, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE(s.room_number, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE(s.status, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE(inst.instrument_name, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(CONCAT(COALESCE(i.first_name, ''), ' ', COALESCE(i.middle_name, ''), ' ', COALESCE(i.last_name, ''))) LIKE ?", [$keyword]);
            });
        }
    }

    private function applySorting($query, string $sort): void
    {
        if ($sort === 'date_asc') {
            $query->orderBy('s.schedule_date')->orderBy('s.start_time');
            return;
        }

        if ($sort === 'time_asc') {
            $query->orderBy('s.start_time')->orderByDesc('s.schedule_date');
            return;
        }

        if ($sort === 'time_desc') {
            $query->orderByDesc('s.start_time')->orderByDesc('s.schedule_date');
            return;
        }

        $query->orderByDesc('s.schedule_date')->orderByDesc('s.start_time');
    }

    private function formatRow(object $row): array
    {
        return [
            'id' => $row->schedule_id,
            'enrollment_id' => $row->enrollment_id,
            'date' => $this->dateText($row->schedule_date),
            'time' => $this->timeText($row->start_time, $row->end_time),
            'status' => $this->displayStatus($row),
            'room' => $this->text($row->room_number, 'Not set'),
            'topic' => $this->text($row->lesson_topic, 'No topic encoded'),
            'lesson_content' => $this->text($row->lesson_content, 'No lesson content encoded'),
            'notes' => $this->text($row->notes, 'No notes encoded'),
            'instrument' => $this->text($row->instrument_name, 'Not set'),
            'instructor' => $this->text($row->instructor_name, 'Not set'),
            'package' => $this->text($row->session_name, 'Lesson Package'),
            'enrollment_status' => $this->statusText($row->enrollment_status),
            'completed_sessions' => (int) ($row->completed_sessions ?? 0),
            'remaining_sessions' => (int) ($row->remaining_sessions ?? 0),
            'total_sessions' => (int) ($row->total_sessions ?? $row->session_count ?? 0),
            'start_date' => $this->dateText($row->start_date),
            'end_date' => $this->dateText($row->end_date),
            'created_at' => $this->dateTimeText($row->created_at),
            'updated_at' => $this->dateTimeText($row->updated_at),
        ];
    }

    private function displayStatus(object $row): string
    {
        $raw = strtolower($this->text($row->status, ''));

        if (in_array($raw, ['completed', 'done', 'finished'], true)) {
            return 'Completed';
        }

        if (in_array($raw, ['cancelled', 'canceled', 'rejected'], true)) {
            return 'Cancelled';
        }

        try {
            if ($row->schedule_date && Carbon::parse($row->schedule_date)->toDateString() < now()->toDateString()) {
                return 'Past';
            }
        } catch (Throwable $e) {
            return $this->statusText($row->status);
        }

        return 'Upcoming';
    }

    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'upcoming' => 0,
            'approved' => 0,
            'completed' => 0,
        ];
    }

    private function emptyPagination(): array
    {
        return [
            'page' => 1,
            'per_page' => 10,
            'total' => 0,
            'total_pages' => 1,
        ];
    }

    private function text($value, string $default = 'Not set'): string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) ($value ?? '')));

        return $text !== '' ? $text : $default;
    }

    private function statusText($value): string
    {
        return ucwords(str_replace('_', ' ', strtolower($this->text($value))));
    }

    private function dateText($value): string
    {
        if (!$value) {
            return 'Not set';
        }

        try {
            return Carbon::parse($value)->format('F d, Y');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }

    private function dateTimeText($value): string
    {
        if (!$value) {
            return 'Not set';
        }

        try {
            return Carbon::parse($value)->format('M d, Y h:i A');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }

    private function timeText($start, $end): string
    {
        if (!$start && !$end) {
            return 'Not set';
        }

        try {
            $startText = $start ? Carbon::parse($start)->format('h:i A') : 'Not set';
            $endText = $end ? Carbon::parse($end)->format('h:i A') : null;

            return $endText ? $startText . ' - ' . $endText : $startText;
        } catch (Throwable $e) {
            return trim((string) $start . ($end ? ' - ' . $end : ''));
        }
    }
}