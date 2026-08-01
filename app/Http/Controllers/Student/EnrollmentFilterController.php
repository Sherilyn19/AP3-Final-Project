<?php

// app/Http/Controllers/Student/EnrollmentFilterController.php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EnrollmentFilterController extends Controller
{
    public function index(Request $request)
    {
        $student = $this->getStudent();

        if (!$student) {
            return response()->json([
                'ok' => false,
                'message' => 'Student record was not found.',
                'items' => [],
                'filters' => [
                    'statuses' => [],
                    'instruments' => [],
                ],
                'pagination' => $this->emptyPagination(),
            ]);
        }

        $studentId = (int) $student->student_id;
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        $query = $this->baseQuery($studentId);
        $this->applyFilters($query, $request);

        $total = (int) $query->count();

        $this->applySort($query, (string) $request->query('sort', 'latest'));

        $items = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatEnrollment($row))
            ->values();

        return response()->json([
            'ok' => true,
            'items' => $items,
            'filters' => $this->filterOptions($studentId),
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
        return DB::table('enrollment as e')
            ->leftJoin('lesson_session as ls', 'e.session_id', '=', 'ls.session_id')
            ->leftJoin('instrument as inst', 'e.instrument_id', '=', 'inst.instrument_id')
            ->leftJoin('instructor as i', 'e.instructor_id', '=', 'i.instructor_id')
            ->where('e.student_id', $studentId)
            ->select([
                'e.enrollment_id',
                'e.student_id',
                'e.status',
                'e.instrument_id',
                'e.session_id',
                'e.instructor_id',
                $this->col('enrollment', 'enrollment_date', 'e', 'enrollment_date'),
                $this->col('enrollment', 'start_date', 'e', 'start_date'),
                $this->col('enrollment', 'end_date', 'e', 'end_date'),
                $this->col('enrollment', 'completed_sessions', 'e', 'completed_sessions'),
                $this->col('enrollment', 'remaining_sessions', 'e', 'remaining_sessions'),
                $this->col('enrollment', 'total_sessions', 'e', 'total_sessions'),
                $this->col('enrollment', 'preferred_lesson_days', 'e', 'preferred_lesson_days'),
                $this->col('enrollment', 'preferred_lesson_time', 'e', 'preferred_lesson_time'),
                $this->col('enrollment', 'paid_amount', 'e', 'paid_amount'),
                $this->col('enrollment', 'created_at', 'e', 'created_at'),
                $this->col('lesson_session', 'session_name', 'ls', 'session_name'),
                $this->col('lesson_session', 'session_count', 'ls', 'session_count'),
                $this->col('instrument', 'instrument_name', 'inst', 'instrument_name'),
                DB::raw("TRIM(CONCAT(COALESCE(i.first_name, ''), ' ', COALESCE(i.middle_name, ''), ' ', COALESCE(i.last_name, ''), ' ', COALESCE(i.suffix, ''))) as instructor_name"),
            ]);
    }

    private function col(string $table, string $column, string $alias, string $as)
    {
        if (Schema::hasColumn($table, $column)) {
            return "{$alias}.{$column} as {$as}";
        }

        return DB::raw("NULL as {$as}");
    }

    private function applyFilters($query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        $status = strtolower((string) $request->query('status', 'all'));
        $instrumentId = (string) $request->query('instrument', 'all');

        if ($status !== 'all' && $status !== '') {
            $query->whereRaw("LOWER(COALESCE(e.status, '')) = ?", [$status]);
        }

        if ($instrumentId !== 'all' && $instrumentId !== '') {
            $query->where('e.instrument_id', $instrumentId);
        }

        if ($search !== '') {
            $keyword = '%' . strtolower($search) . '%';

            $query->where(function ($q) use ($keyword) {
                $q->whereRaw("LOWER(COALESCE(e.status, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE(ls.session_name, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE(inst.instrument_name, '')) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(CONCAT(COALESCE(i.first_name, ''), ' ', COALESCE(i.middle_name, ''), ' ', COALESCE(i.last_name, ''))) LIKE ?", [$keyword]);
            });
        }
    }

    private function applySort($query, string $sort): void
    {
        if ($sort === 'oldest') {
            $query->orderBy($this->dateSortColumn())->orderBy('e.enrollment_id');
            return;
        }

        if ($sort === 'status') {
            $query->orderBy('e.status')->orderByDesc($this->dateSortColumn());
            return;
        }

        if ($sort === 'instrument') {
            $query->orderBy('inst.instrument_name')->orderByDesc($this->dateSortColumn());
            return;
        }

        $query->orderByDesc($this->dateSortColumn())->orderByDesc('e.enrollment_id');
    }

    private function dateSortColumn(): string
    {
        if (Schema::hasColumn('enrollment', 'created_at')) {
            return 'e.created_at';
        }

        if (Schema::hasColumn('enrollment', 'enrollment_date')) {
            return 'e.enrollment_date';
        }

        return 'e.enrollment_id';
    }

    private function filterOptions(int $studentId): array
    {
        $statuses = DB::table('enrollment')
            ->where('student_id', $studentId)
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->map(fn ($status) => [
                'value' => strtolower((string) $status),
                'label' => $this->statusText($status),
            ])
            ->values();

        $instruments = DB::table('enrollment as e')
            ->leftJoin('instrument as inst', 'e.instrument_id', '=', 'inst.instrument_id')
            ->where('e.student_id', $studentId)
            ->whereNotNull('e.instrument_id')
            ->select('e.instrument_id', 'inst.instrument_name')
            ->distinct()
            ->orderBy('inst.instrument_name')
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->instrument_id,
                'label' => $this->text($row->instrument_name, 'Instrument #' . $row->instrument_id),
            ])
            ->values();

        return [
            'statuses' => $statuses,
            'instruments' => $instruments,
        ];
    }

    private function formatEnrollment(object $row): array
    {
        $completed = (int) ($row->completed_sessions ?? 0);
        $remaining = (int) ($row->remaining_sessions ?? 0);
        $total = (int) ($row->total_sessions ?? $row->session_count ?? ($completed + $remaining));
        $progress = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'id' => $row->enrollment_id,
            'status' => $this->statusText($row->status),
            'instrument' => $this->text($row->instrument_name),
            'package' => $this->text($row->session_name, 'Lesson Package'),
            'instructor' => $this->text($row->instructor_name),
            'enrolled_date' => $this->dateText($row->enrollment_date ?? $row->created_at ?? null),
            'start_date' => $this->dateText($row->start_date ?? null),
            'end_date' => $this->dateText($row->end_date ?? null),
            'preferred_days' => $this->text($row->preferred_lesson_days ?? null, 'Not set'),
            'preferred_time' => $this->text($row->preferred_lesson_time ?? null, 'Not set'),
            'completed_sessions' => $completed,
            'remaining_sessions' => $remaining,
            'total_sessions' => $total,
            'progress' => $progress,
            'paid_amount' => is_numeric($row->paid_amount ?? null) ? number_format((float) $row->paid_amount, 2) : '0.00',
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
            return Carbon::parse($value)->format('M d, Y');
        } catch (Throwable $error) {
            return (string) $value;
        }
    }
}