<?php

// app/Http/Controllers/Admin/UserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/*
|--------------------------------------------------------------------------
| UserController
|--------------------------------------------------------------------------
|
| Handles Admin user management:
| - All Users list
| - Add New User
| - View user details
| - Inline user update
| - Activate / deactivate
| - Reset password
| - Safe delete with deletion-impact checking
| - Bulk deactivate
| - Safe bulk delete
|
| Important:
| - The real authentication table is user_account.
| - The default Laravel users table exists but is not the main login table.
| - Student and Instructor profile rows are connected through user_id.
|
*/

class UserController extends Controller
{
    /**
     * Display user management page with filters and pagination.
     */
    public function index(Request $request)
    {
        $query = $this->baseUserListQuery();

        $this->applyUserFilters($query, $request);

        // Get paginated users for display.
        $users = $query
            ->orderBy('ua.user_id', 'asc')
            ->paginate(20)
            ->withQueryString();

        // Calculate accurate statistics from the entire filtered dataset.
        $statsQuery = DB::table('user_account as ua')
            ->leftJoin('student as s', 'ua.user_id', '=', 's.user_id')
            ->leftJoin('instructor as i', 'ua.user_id', '=', 'i.user_id')
            ->select(
                DB::raw('COUNT(*) as total_users'),
                DB::raw('SUM(CASE WHEN s.is_active = TRUE OR i.is_active = TRUE THEN 1 ELSE 0 END) as active_users'),
                DB::raw("SUM(CASE WHEN (s.user_id IS NOT NULL AND s.is_active = FALSE) OR (i.user_id IS NOT NULL AND i.is_active = FALSE) THEN 1 ELSE 0 END) as inactive_users")
            );

        $this->applyUserFilters($statsQuery, $request);

        $stats = $statsQuery->first();

        // Calculate the most common role from the filtered result set.
        $roleStats = DB::table('user_account as ua')
            ->leftJoin('student as s', 'ua.user_id', '=', 's.user_id')
            ->leftJoin('instructor as i', 'ua.user_id', '=', 'i.user_id')
            ->select(
                DB::raw("
                    CASE
                        WHEN ua.is_super_admin = TRUE THEN 'super_admin'
                        WHEN s.user_id IS NOT NULL THEN 'student'
                        WHEN i.user_id IS NOT NULL THEN 'instructor'
                        ELSE 'unknown'
                    END as user_role
                "),
                DB::raw('COUNT(*) as role_count')
            );

        $this->applyUserFilters($roleStats, $request);

        $mostCommonRole = $roleStats
            ->groupBy('user_role')
            ->orderByDesc('role_count')
            ->first();

        return view('admin.users.index', compact('users', 'stats', 'mostCommonRole'));
    }

    /**
     * Show the Add New User form.
     */
    public function create()
    {
        $specializations = DB::table('specialization')
            ->where('is_active', true)
            ->orderBy('specialization_name')
            ->get();

        return view('admin.users.create', compact('specializations'));
    }

    /**
     * Store a newly created user with proper database structure.
     *
     * Steps:
     * 1. Validate request.
     * 2. Format name and email.
     * 3. Create user_account.
     * 4. Create student or instructor profile.
     * 5. Attach instructor specializations when applicable.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:user_account,user_email',
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()],
            'phone' => 'nullable|string|regex:/^\d{11}$/',
            'role' => 'required|string|in:student,instructor',

            // Student-specific validation.
            'student_status_id' => 'required_if:role,student|exists:student_status,status_id',

            // Instructor-specific validation.
            'instructor.years_of_experience' => 'nullable|integer|min:0',
            'instructor.education_level' => 'nullable|string|max:100',
            'instructor.music_degree' => 'nullable|string|max:200',
            'instructor.bio' => 'nullable|string',
            'instructor.certifications' => 'nullable|string',
            'instructor.teaching_style' => 'nullable|string|max:255',
            'instructor.languages_spoken' => 'nullable|string|max:255',
            'instructor.max_students_per_day' => 'nullable|integer|min:1',
            'instructor.preferred_time_slots' => 'nullable|string|max:255',
            'instructor.available_days' => 'nullable|array',
            'instructor.available_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',

            // Instructor specializations.
            'specializations' => 'nullable|array',
            'specializations.*' => 'exists:specialization,specialization_id',
            'primary_specialization' => 'nullable|exists:specialization,specialization_id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $firstName = ucwords(strtolower(trim($request->first_name)));
        $lastName = ucwords(strtolower(trim($request->last_name)));
        $email = strtolower(trim($request->email));
        $phone = $request->phone ? trim($request->phone) : null;

        DB::beginTransaction();

        try {
            $userId = DB::table('user_account')->insertGetId([
                'user_email' => $email,
                'user_password' => Hash::make($request->password),
                'is_super_admin' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'user_id');

            switch ($request->role) {
                case 'student':
                    DB::table('student')->insert([
                        'user_id' => $userId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => $phone,
                        'email' => $email,
                        'student_status_id' => $request->input('student_status_id'),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    break;

                case 'instructor':
                    $instructorId = DB::table('instructor')->insertGetId([
                        'user_id' => $userId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => $phone,
                        'email' => $email,
                        'years_of_experience' => $request->input('instructor.years_of_experience'),
                        'education_level' => $request->input('instructor.education_level'),
                        'music_degree' => $request->input('instructor.music_degree'),
                        'bio' => $request->input('instructor.bio'),
                        'certifications' => $request->input('instructor.certifications'),
                        'teaching_style' => $request->input('instructor.teaching_style'),
                        'languages_spoken' => $request->input('instructor.languages_spoken'),
                        'is_available' => $request->has('instructor.is_available'),
                        'available_days' => $request->input('instructor.available_days')
                            ? json_encode($request->input('instructor.available_days'))
                            : null,
                        'preferred_time_slots' => $request->input('instructor.preferred_time_slots'),
                        'max_students_per_day' => $request->input('instructor.max_students_per_day', 8),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], 'instructor_id');

                    $specializations = $request->input('specializations', []);
                    $primarySpecializationId = $request->input('primary_specialization');

                    foreach ($specializations as $specializationId) {
                        DB::table('instructor_specialization')->insert([
                            'instructor_id' => $instructorId,
                            'specialization_id' => $specializationId,
                            'is_primary' => (int) $specializationId === (int) $primarySpecializationId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    break;
            }

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User created successfully! Email: ' . $email);

        } catch (\Throwable $error) {
            DB::rollBack();

            \Log::error('User creation failed: ' . $error->getMessage(), [
                'request' => $request->except('password'),
                'trace' => $error->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'A server error occurred while creating the user. Please check the system logs for details.');
        }
    }

    /**
     * Get user details for modal view.
     */
    public function show($id)
    {
        $user = DB::table('user_account')
            ->where('user_id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'error' => 'User not found.',
            ], 404);
        }

        [$role, $roleData, $fullName, $isActive] = $this->getRoleAndDetailsByUserId((int) $user->user_id);

        if ((bool) $user->is_super_admin) {
            $fullName = 'Super Admin';
        }

        return response()->json([
            'user' => $user,
            'roleData' => $roleData ?? (object) [
                'first_name' => '',
                'last_name' => '',
                'phone' => '',
            ],
            'activityData' => $this->getActivityData((int) $user->user_id, $role, $roleData),
            'full_name' => $fullName,
            'is_active' => $isActive,
            'user_role' => $role ?? 'unknown',
        ]);
    }

    /**
     * Update user details from inline editing.
     */
    public function update(Request $request, $id)
    {
        $user = DB::table('user_account')
            ->where('user_id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'error' => 'User not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ((bool) $user->is_super_admin ? 'nullable' : 'required') . '|string|max:100',
            'last_name' => ((bool) $user->is_super_admin ? 'nullable' : 'required') . '|string|max:100',
            'user_email' => 'required|email|max:255|unique:user_account,user_email,' . $id . ',user_id',
            'phone' => 'nullable|string|regex:/^\d{11}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $id, $user) {
            $email = strtolower(trim($request->input('user_email')));

            DB::table('user_account')
                ->where('user_id', $id)
                ->update([
                    'user_email' => $email,
                    'updated_at' => now(),
                ]);

            [$role] = $this->getRoleAndDetailsByUserId((int) $id);

            if ($role && !(bool) $user->is_super_admin) {
                $tableMap = [
                    'student' => 'student',
                    'instructor' => 'instructor',
                ];

                if (!isset($tableMap[$role])) {
                    return;
                }

                DB::table($tableMap[$role])
                    ->where('user_id', $id)
                    ->update([
                        'first_name' => ucwords(strtolower(trim($request->input('first_name')))),
                        'last_name' => ucwords(strtolower(trim($request->input('last_name')))),
                        'phone' => $request->input('phone'),
                        'email' => $email,
                        'updated_at' => now(),
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
        ]);
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(Request $request, $id)
    {
        $user = DB::table('user_account')
            ->where('user_id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ((bool) $user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin password cannot be reset from User Management.',
            ], 403);
        }

        $newPassword = Str::password(12, true, true, true, false);

        DB::table('user_account')
            ->where('user_id', $id)
            ->update([
                'user_password' => Hash::make($newPassword),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'password' => $newPassword,
        ]);
    }

    /**
     * Deactivate a user.
     */
    public function deactivate($id)
    {
        return $this->updateUserStatus((int) $id, false);
    }

    /**
     * Activate a user.
     */
    public function activate($id)
    {
        return $this->updateUserStatus((int) $id, true);
    }

    /**
     * Delete a user only when it is safe.
     *
     * Safety rules:
     * - Super Admin cannot be deleted.
     * - Users with connected operational records cannot be deleted.
     * - Clean role profiles may be deleted together with user_account.
     */
    public function destroy($id)
    {
        $user = DB::table('user_account')
            ->where('user_id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ((bool) $user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin account cannot be deleted.',
            ], 403);
        }

        $impact = $this->buildDeletionImpact((int) $id);

        if (array_sum($impact) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This user cannot be deleted because connected records still exist. Deactivate the user instead.',
                'impact' => $impact,
            ], 409);
        }

        DB::transaction(function () use ($id) {
            $student = DB::table('student')
                ->where('user_id', $id)
                ->first();

            $instructor = DB::table('instructor')
                ->where('user_id', $id)
                ->first();

            if ($student) {
                DB::table('student')
                    ->where('user_id', $id)
                    ->delete();
            }

            if ($instructor) {
                DB::table('instructor_specialization')
                    ->where('instructor_id', $instructor->instructor_id)
                    ->delete();

                DB::table('instructor')
                    ->where('user_id', $id)
                    ->delete();
            }

            DB::table('user_account')
                ->where('user_id', $id)
                ->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Return the real deletion impact of a user.
     */
    public function getDeletionImpact($id)
    {
        $user = DB::table('user_account')
            ->where('user_id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'impact' => [],
            ], 404);
        }

        if ((bool) $user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin account cannot be deleted.',
                'impact' => [
                    'protected_account' => 1,
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'impact' => $this->buildDeletionImpact((int) $id),
        ]);
    }

    /**
     * Bulk deactivate selected users.
     */
    public function bulkDeactivate(Request $request)
    {
        $userIds = array_values(array_unique(array_map('intval', $request->input('user_ids', []))));

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No users selected.',
            ], 422);
        }

        DB::transaction(function () use ($userIds) {
            $usersByRole = [
                'student' => [],
                'instructor' => [],
            ];

            foreach ($userIds as $userId) {
                [$role] = $this->getRoleAndDetailsByUserId($userId);

                if (isset($usersByRole[$role])) {
                    $usersByRole[$role][] = $userId;
                }
            }

            if (!empty($usersByRole['student'])) {
                DB::table('student')
                    ->whereIn('user_id', $usersByRole['student'])
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
            }

            if (!empty($usersByRole['instructor'])) {
                DB::table('instructor')
                    ->whereIn('user_id', $usersByRole['instructor'])
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => count($userIds) . ' user(s) deactivated successfully.',
        ]);
    }

    /**
     * Bulk delete users only when every selected user is safe to delete.
     */
    public function bulkDestroy(Request $request)
    {
        $userIds = array_values(array_unique(array_map('intval', $request->input('user_ids', []))));

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No users selected.',
            ], 422);
        }

        $blockedUsers = [];

        foreach ($userIds as $userId) {
            $user = DB::table('user_account')
                ->where('user_id', $userId)
                ->first();

            if (!$user) {
                $blockedUsers[$userId] = [
                    'missing_user' => 1,
                ];
                continue;
            }

            if ((bool) $user->is_super_admin) {
                $blockedUsers[$userId] = [
                    'protected_account' => 1,
                ];
                continue;
            }

            $impact = $this->buildDeletionImpact($userId);

            if (array_sum($impact) > 0) {
                $blockedUsers[$userId] = $impact;
            }
        }

        if (!empty($blockedUsers)) {
            return response()->json([
                'success' => false,
                'message' => 'Some users cannot be deleted because they still have connected records. Deactivate them instead.',
                'blocked_users' => $blockedUsers,
            ], 409);
        }

        DB::transaction(function () use ($userIds) {
            $studentUserIds = DB::table('student')
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            $instructors = DB::table('instructor')
                ->whereIn('user_id', $userIds)
                ->select('instructor_id', 'user_id')
                ->get();

            $instructorIds = $instructors->pluck('instructor_id')->toArray();
            $instructorUserIds = $instructors->pluck('user_id')->toArray();

            if (!empty($studentUserIds)) {
                DB::table('student')
                    ->whereIn('user_id', $studentUserIds)
                    ->delete();
            }

            if (!empty($instructorIds)) {
                DB::table('instructor_specialization')
                    ->whereIn('instructor_id', $instructorIds)
                    ->delete();

                DB::table('instructor')
                    ->whereIn('user_id', $instructorUserIds)
                    ->delete();
            }

            DB::table('user_account')
                ->whereIn('user_id', $userIds)
                ->delete();
        });

        return response()->json([
            'success' => true,
            'message' => count($userIds) . ' user(s) deleted successfully.',
        ]);
    }

    /**
     * Build the main user query.
     */
    private function baseUserListQuery()
    {
        return DB::table('user_account as ua')
            ->leftJoin('student as s', 'ua.user_id', '=', 's.user_id')
            ->leftJoin('instructor as i', 'ua.user_id', '=', 'i.user_id')
            ->select(
                'ua.user_id',
                'ua.user_email',
                'ua.last_login',
                'ua.created_at',
                'ua.updated_at',
                'ua.is_super_admin',
                DB::raw("
                    CASE
                        WHEN ua.is_super_admin = TRUE THEN 'super_admin'
                        WHEN s.user_id IS NOT NULL THEN 'student'
                        WHEN i.user_id IS NOT NULL THEN 'instructor'
                        ELSE 'unknown'
                    END as user_role
                "),
                DB::raw("
                    CASE
                        WHEN ua.is_super_admin = TRUE THEN 'Super Admin'

                        WHEN s.user_id IS NOT NULL THEN COALESCE(
                            NULLIF(TRIM(CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name, s.suffix)), ''),
                            ua.user_email
                        )

                        WHEN i.user_id IS NOT NULL THEN COALESCE(
                            NULLIF(TRIM(CONCAT_WS(' ', i.first_name, i.middle_name, i.last_name, i.suffix)), ''),
                            ua.user_email
                        )

                        ELSE COALESCE(ua.user_email, 'N/A')
                    END as full_name
                "),
                DB::raw("
                    CASE
                        WHEN s.user_id IS NOT NULL THEN s.is_active
                        WHEN i.user_id IS NOT NULL THEN i.is_active
                        ELSE FALSE
                    END as is_active
                ")
            );
    }

    /**
     * Apply shared filters to user queries, stats queries, and role-stat queries.
     */
    private function applyUserFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('ua.user_email', 'ILIKE', "%{$search}%")
                    ->orWhere(DB::raw('CAST(ua.user_id AS TEXT)'), 'ILIKE', "%{$search}%")
                    ->orWhere(DB::raw("CONCAT(s.first_name, ' ', s.last_name)"), 'ILIKE', "%{$search}%")
                    ->orWhere(DB::raw("CONCAT(i.first_name, ' ', i.last_name)"), 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $role = $request->role;

            $query->where(function ($q) use ($role) {
                if ($role === 'student') {
                    $q->whereNotNull('s.user_id');
                } elseif ($role === 'instructor') {
                    $q->whereNotNull('i.user_id');
                } elseif ($role === 'super_admin') {
                    $q->where('ua.is_super_admin', true);
                }
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $isActive = $request->status === 'active';

            $query->where(function ($q) use ($isActive) {
                $q->where(function ($subQ) use ($isActive) {
                    $subQ->whereNotNull('s.user_id')
                        ->where('s.is_active', $isActive);
                })->orWhere(function ($subQ) use ($isActive) {
                    $subQ->whereNotNull('i.user_id')
                        ->where('i.is_active', $isActive);
                });
            });
        }

        $dateColumn = $request->input('date_filter_by', 'created_at') === 'updated_at'
            ? 'ua.updated_at'
            : 'ua.created_at';

        if ($request->filled('date_from')) {
            $query->whereDate($dateColumn, '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate($dateColumn, '<=', $request->date_to);
        }
    }

    /**
     * Get the role profile connected to a user_account row.
     */
    private function getRoleAndDetailsByUserId(int $userId): array
    {
        $user = DB::table('user_account')
            ->where('user_id', $userId)
            ->first();

        if (!$user) {
            return [null, null, 'N/A', false];
        }

        $student = DB::table('student')
            ->where('user_id', $userId)
            ->first();

        if ($student) {
            $fullName = trim($student->first_name . ' ' . $student->last_name);
            return ['student', $student, $fullName, (bool) $student->is_active];
        }

        $instructor = DB::table('instructor')
            ->where('user_id', $userId)
            ->first();

        if ($instructor) {
            $fullName = trim($instructor->first_name . ' ' . $instructor->last_name);
            return ['instructor', $instructor, $fullName, (bool) $instructor->is_active];
        }

        $fullName = (bool) $user->is_super_admin ? 'Super Admin' : 'N/A';

        return [null, null, $fullName, false];
    }

    /**
     * Get activity data based on role.
     */
    private function getActivityData(int $userId, ?string $role, $roleData): array
    {
        if (!$roleData) {
            return [];
        }

        switch ($role) {
            case 'student':
                return [
                    'enrollments' => DB::table('enrollment')
                        ->where('student_id', $roleData->student_id)
                        ->count(),

                    'payments' => DB::table('payment')
                        ->where('student_id', $roleData->student_id)
                        ->sum('amount'),

                    'attendance' => DB::table('attendance')
                        ->where('student_id', $roleData->student_id)
                        ->where('attendance_type', 'lesson')
                        ->count(),
                ];

            case 'instructor':
                return [
                    'assigned_students' => DB::table('enrollment')
                        ->where('instructor_id', $roleData->instructor_id)
                        ->where('status', 'active')
                        ->distinct('student_id')
                        ->count('student_id'),

                    'total_lessons' => DB::table('schedule')
                        ->where('instructor_id', $roleData->instructor_id)
                        ->where('status', 'completed')
                        ->count(),
                ];

            default:
                return [];
        }
    }

    /**
     * Update active/inactive status for the user's role profile.
     */
    private function updateUserStatus(int $id, bool $isActive)
    {
        $user = DB::table('user_account')
            ->where('user_id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ((bool) $user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin status cannot be changed here.',
            ], 403);
        }

        [$role] = $this->getRoleAndDetailsByUserId($id);

        $tableMap = [
            'student' => 'student',
            'instructor' => 'instructor',
        ];

        if (!$role || !isset($tableMap[$role])) {
            return response()->json([
                'success' => false,
                'message' => 'User has no role profile to update.',
            ], 404);
        }

        DB::table($tableMap[$role])
            ->where('user_id', $id)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
        ]);
    }

    /**
     * Build safe deletion impact counts.
     *
     * Only operational records are counted here. A clean role profile can be
     * deleted together with the user account, but connected operational records
     * should block deletion.
     */
    private function buildDeletionImpact(int $userId): array
    {
        $impact = [];

        $student = DB::table('student')
            ->where('user_id', $userId)
            ->first();

        $instructor = DB::table('instructor')
            ->where('user_id', $userId)
            ->first();

        if ($student) {
            $studentId = $student->student_id;

            $impact['student_enrollments'] = DB::table('enrollment')
                ->where('student_id', $studentId)
                ->count();

            $impact['student_schedules'] = DB::table('schedule')
                ->where('student_id', $studentId)
                ->count();

            $impact['student_attendance_records'] = DB::table('attendance')
                ->where('student_id', $studentId)
                ->count();

            $impact['student_progress_records'] = DB::table('progress')
                ->where('student_id', $studentId)
                ->count();

            $impact['student_payments'] = DB::table('payment')
                ->where('student_id', $studentId)
                ->count();
        }

        if ($instructor) {
            $instructorId = $instructor->instructor_id;

            $impact['instructor_enrollments'] = DB::table('enrollment')
                ->where('instructor_id', $instructorId)
                ->count();

            $impact['instructor_schedules'] = DB::table('schedule')
                ->where('instructor_id', $instructorId)
                ->count();

            $impact['instructor_attendance_records'] = DB::table('attendance')
                ->where('instructor_id', $instructorId)
                ->count();

            $impact['instructor_progress_records'] = DB::table('progress')
                ->where('instructor_id', $instructorId)
                ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Direct user_account relationships
        |--------------------------------------------------------------------------
        |
        | These tables point directly to user_account.user_id.
        | If records exist, deleting user_account could break foreign keys.
        |
        */
        $impact['attendance_user_records'] = DB::table('attendance')
            ->where('user_id', $userId)
            ->count();

        $impact['bookings_created'] = DB::table('booking')
            ->where('user_id', $userId)
            ->count();

        $impact['bookings_confirmed'] = DB::table('booking')
            ->where('confirmed_by', $userId)
            ->count();

        $impact['bookings_cancelled'] = DB::table('booking')
            ->where('cancelled_by', $userId)
            ->count();

        $impact['payments_processed'] = DB::table('payment')
            ->where('processed_by', $userId)
            ->count();

        $impact['payments_approved'] = DB::table('payment')
            ->where('approved_by', $userId)
            ->count();

        $impact['reports_generated'] = DB::table('report')
            ->where('generated_by', $userId)
            ->count();

        $impact['sound_check_sessions'] = DB::table('guitar_sessions')
            ->where('user_id', $userId)
            ->count();

        $impact['pitch_monitor_sessions'] = DB::table('pitch_monitor_sessions')
            ->where('user_id', $userId)
            ->count();

        return array_filter($impact, function ($count) {
            return (int) $count > 0;
        });
    }
}