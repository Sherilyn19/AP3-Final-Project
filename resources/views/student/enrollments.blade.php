{{-- resources/views/student/enrollments.blade.php --}}

@extends('layouts.student')

@section('content')
<div class="min-h-full bg-[#F5F7F4] px-4 py-8 sm:px-6 lg:px-8" style="font-family: 'Inter', sans-serif;">
    <div class="mx-auto max-w-7xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-[#768A96]">Lesson Management</p>
                <h1 class="mt-2 text-2xl font-bold text-[#223030] sm:text-3xl" style="font-family: 'Sora', sans-serif;">My Packages & Enrollments</h1>
                <p class="mt-2 text-sm text-[#44576D]">View your current and past lesson packages.</p>
            </div>
            <a href="{{ route('student.packages') }}" class="inline-flex w-fit rounded-2xl bg-[#29353C] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#223030]">
                Browse Packages
            </a>
        </div>

        {{-- Closeable Session Messages --}}
        @if(session('success') || session('error'))
            <div data-alert class="flex items-start justify-between gap-3 rounded-2xl border px-4 py-3 text-sm font-semibold shadow-sm {{ session('success') ? 'border-[#A7DDB5] bg-[#EAF8EE] text-[#23613B]' : 'border-[#C56B5F]/40 bg-[#F6EFEC] text-[#523D35]' }}">
                <span>{{ session('success') ?? session('error') }}</span>
                <button type="button" data-close-alert class="rounded-lg p-1 opacity-70 transition hover:bg-white/60 hover:opacity-100" aria-label="Close notification">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif

        {{-- Compact Stat Cards --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5">
            @php
                $statCards = [
                    ['label' => 'Total', 'value' => $stats['total'] ?? 0, 'hint' => 'Packages'],
                    ['label' => 'Active', 'value' => $stats['active'] ?? 0, 'hint' => 'Now'],
                    ['label' => 'Remaining', 'value' => $stats['remaining_sessions'] ?? 0, 'hint' => 'Sessions'],
                    ['label' => 'Completed', 'value' => $stats['completed_sessions'] ?? 0, 'hint' => 'Sessions'],
                    ['label' => 'Requests', 'value' => $stats['withdrawal_requests'] ?? 0, 'hint' => 'Withdraw'],
                ];
            @endphp

            @foreach($statCards as $card)
                <div class="student-stat-card text-center">
                    <p class="truncate text-[10px] font-bold uppercase tracking-wide text-[#768A96] sm:text-xs">{{ $card['label'] }}</p>
                    <p class="mt-1 text-lg font-extrabold text-[#223030] sm:text-2xl" style="font-family: 'JetBrains Mono', monospace;">{{ $card['value'] }}</p>
                    <p class="hidden text-xs text-[#44576D] sm:block">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </section>

        @if($enrollments->isEmpty())
            <div class="rounded-[28px] border border-[#D8DDD8] bg-white p-10 text-center shadow-sm">
                <h3 class="text-xl font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">No enrollments yet</h3>
                <p class="mt-2 text-sm text-[#44576D]">Enroll in a package to start your music lessons.</p>
                <a href="{{ route('student.packages') }}" class="mt-5 inline-flex rounded-2xl bg-[#29353C] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#223030]">
                    Browse Packages
                </a>
            </div>
        @else
            <div class="space-y-5">
                @foreach($enrollments as $enrollment)
                    @php
                        $progressPercent = $enrollment->progress_percent;
                        $statusClasses = match($enrollment->status) {
                            'active' => 'border-[#A7DDB5] bg-[#EAF8EE] text-[#23613B]',
                            'completed' => 'border-[#D8DDD8] bg-[#F5F7F4] text-[#29353C]',
                            'cancelled' => 'border-[#C56B5F]/40 bg-[#F6EFEC] text-[#523D35]',
                            'withdrawal_requested' => 'border-[#DDBF7A] bg-[#FFF8E6] text-[#725A19]',
                            default => 'border-[#D8DDD8] bg-[#F5F7F4] text-[#44576D]',
                        };
                        $selectedDays = collect(explode(',', $enrollment->preferred_lesson_days ?? ''))
                            ->map(fn($day) => trim($day))
                            ->filter()
                            ->values()
                            ->all();
                    @endphp

                    <article class="overflow-hidden rounded-[28px] border border-[#D8DDD8] bg-white shadow-sm transition hover:shadow-md">
                        <div class="border-b border-[#D8DDD8] bg-[#FCFCFA] px-5 py-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">{{ $enrollment->instrument->instrument_name ?? 'Instrument' }}</p>
                                    <h2 class="mt-1 text-xl font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">
                                        {{ $enrollment->lessonSession->session_count ?? $enrollment->total_sessions }}-Session Package
                                    </h2>
                                    <p class="mt-1 text-sm text-[#44576D]">
                                        Enrolled: {{ $enrollment->enrollment_date?->format('M d, Y') ?? '—' }} • Starts: {{ $enrollment->start_date?->format('M d, Y') ?? '—' }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-2xl border px-4 py-2 text-xs font-bold {{ $statusClasses }}">
                                        {{ $enrollment->status_label }}
                                    </span>

                                    {{-- View Details Button --}}
                                    <button type="button" data-open-modal="details-{{ $enrollment->enrollment_id }}" class="rounded-2xl border border-[#D8DDD8] bg-white p-2 text-[#29353C] transition hover:bg-[#F5F7F4]" title="View details">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    {{-- Edit Button --}}
                                    @if($enrollment->can_be_edited)
                                        <button type="button" data-open-modal="edit-{{ $enrollment->enrollment_id }}" class="rounded-2xl border border-[#D8DDD8] bg-white p-2 text-[#29353C] transition hover:bg-[#F5F7F4]" title="Edit enrollment">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            {{-- Progress Bar --}}
                            <div>
                                <div class="mb-2 flex justify-between text-sm">
                                    <span class="font-bold text-[#223030]">Progress</span>
                                    <span class="font-bold text-[#29353C]" style="font-family: 'JetBrains Mono', monospace;">
                                        {{ $enrollment->completed_sessions }} / {{ $enrollment->total_sessions }} ({{ $progressPercent }}%)
                                    </span>
                                </div>
                                <div class="h-3 w-full overflow-hidden rounded-full bg-[#D8DDD8]">
                                    <div class="h-3 rounded-full bg-[#44576D] transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                                </div>
                            </div>

                            {{-- Details Grid --}}
                            <div class="grid grid-cols-2 gap-3 text-sm md:grid-cols-5">
                                <div class="rounded-2xl bg-[#F5F7F4] p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Remaining</p>
                                    <p class="mt-1 font-bold text-[#223030]">{{ $enrollment->remaining_sessions }}</p>
                                </div>
                                <div class="rounded-2xl bg-[#F5F7F4] p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Paid</p>
                                    <p class="mt-1 font-bold text-[#223030]">₱{{ number_format($enrollment->amount_paid ?? 0, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-[#F5F7F4] p-4 md:col-span-2">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Instructor</p>
                                    <p class="mt-1 font-bold text-[#223030]">{{ $enrollment->instructor?->full_name ?? $enrollment->instructor?->first_name ?? 'TBA' }}</p>
                                </div>
                                <div class="rounded-2xl bg-[#F5F7F4] p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Schedule</p>
                                    <p class="mt-1 font-bold text-[#223030]">{{ $enrollment->schedules->count() ? $enrollment->schedules->count() . ' set' : 'Pending' }}</p>
                                </div>
                            </div>

                            {{-- Preferred Schedule --}}
                            <div class="rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-4">
                                <p class="text-sm font-bold text-[#223030]">Preferred schedule</p>
                                <p class="mt-1 text-sm text-[#44576D]">
                                    Days: {{ $enrollment->preferred_lesson_days ?? 'Not set' }} • Time: {{ $enrollment->preferred_lesson_time ?? 'Not set' }}
                                </p>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex flex-wrap gap-2 border-t border-[#D8DDD8] pt-4">
                                @if($enrollment->can_be_cancelled)
                                    <button type="button" data-open-modal="cancel-{{ $enrollment->enrollment_id }}" class="rounded-2xl border border-[#C56B5F]/40 bg-[#F6EFEC] px-4 py-2 text-sm font-bold text-[#523D35] transition hover:bg-[#EFE3DE]">
                                        Cancel Enrollment
                                    </button>
                                @endif

                                @if($enrollment->can_request_withdrawal)
                                    <button type="button" data-open-modal="withdraw-{{ $enrollment->enrollment_id }}" class="rounded-2xl border border-[#DDBF7A] bg-[#FFF8E6] px-4 py-2 text-sm font-bold text-[#725A19] transition hover:bg-[#FFF2CC]">
                                        Request Withdrawal
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>

                    {{-- Details Modal --}}
                    <div id="details-{{ $enrollment->enrollment_id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
                        <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-[28px] bg-white shadow-2xl">
                            <div class="sticky top-0 flex items-center justify-between border-b border-[#D8DDD8] bg-white px-6 py-4">
                                <h3 class="text-lg font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">Enrollment Details</h3>
                                <button type="button" data-close-modal="details-{{ $enrollment->enrollment_id }}" class="rounded-xl p-2 text-[#768A96] hover:bg-[#F5F7F4]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="grid gap-4 p-6 sm:grid-cols-2">
                                @php
                                    $instructor = $enrollment->instructor;

                                    $instructorName = $instructor?->full_name
                                        ?? trim(($instructor?->first_name ?? '') . ' ' . ($instructor?->last_name ?? ''));

                                    $instructorName = $instructorName !== '' ? $instructorName : 'TBA';

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Instructor Email Source
                                    |--------------------------------------------------------------------------
                                    |
                                    | Priority:
                                    | 1. user_account.user_email = safest because it belongs to the actual
                                    |    instructor login account.
                                    | 2. instructor.email = fallback if user_account email is not loaded.
                                    |
                                    */
                                    $instructorEmail = $instructor?->userAccount?->user_email
                                        ?? $instructor?->email
                                        ?? 'No email available';

                                    $instructorPhone = $instructor?->phone ?? 'No phone available';

                                    $detailRows = [
                                        'Enrollment ID' => $enrollment->enrollment_id,
                                        'Package' => ($enrollment->lessonSession->session_count ?? $enrollment->total_sessions) . '-Session Package',
                                        'Instrument' => $enrollment->instrument->instrument_name ?? '—',
                                        'Start Date' => $enrollment->start_date?->format('F d, Y') ?? '—',
                                        'End Date' => $enrollment->end_date?->format('F d, Y') ?? '—',
                                        'Preferred Days' => $enrollment->preferred_lesson_days ?? '—',
                                        'Preferred Time' => $enrollment->preferred_lesson_time ?? '—',
                                        'Payment Status' => ucfirst($enrollment->payment_status),
                                        'Total Amount' => '₱' . number_format($enrollment->total_amount ?? 0, 2),
                                    ];
                                @endphp

                                {{-- Instructor Contact Card --}}
                                <div class="rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-4 sm:col-span-2">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Instructor</p>

                                    <p class="mt-1 text-sm font-bold text-[#223030]">
                                        {{ $instructorName }}
                                    </p>

                                    <div class="mt-2 space-y-1 text-xs text-[#44576D]">
                                        <p>
                                            <span class="font-bold text-[#223030]">Email:</span>
                                            {{ $instructorEmail }}
                                        </p>

                                        <p>
                                            <span class="font-bold text-[#223030]">Phone:</span>
                                            {{ $instructorPhone }}
                                        </p>
                                    </div>
                                </div>

                                @foreach($detailRows as $label => $value)
                                    <div class="rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-4">
                                        <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">{{ $label }}</p>
                                        <p class="mt-1 text-sm font-bold text-[#223030]">{{ $value }}</p>
                                    </div>
                                @endforeach

                                <div class="sm:col-span-2 rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Notes</p>
                                    <p class="mt-1 whitespace-pre-wrap text-sm text-[#223030]">{{ $enrollment->notes ?: 'No notes provided.' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Modal --}}
                    @if($enrollment->can_be_edited)
                        <div id="edit-{{ $enrollment->enrollment_id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
                            <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-[28px] bg-white shadow-2xl">
                                <div class="sticky top-0 flex items-center justify-between border-b border-[#D8DDD8] bg-white px-6 py-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">Edit Enrollment</h3>
                                        <p class="text-xs text-[#768A96]">Allowed only before the package starts and before lessons are scheduled.</p>
                                    </div>
                                    <button type="button" data-close-modal="edit-{{ $enrollment->enrollment_id }}" class="rounded-xl p-2 text-[#768A96] hover:bg-[#F5F7F4]">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <form method="POST" action="{{ route('student.enrollments.update', $enrollment->enrollment_id) }}" class="space-y-5 p-6">
                                    @csrf
                                    @method('PATCH')

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-[#223030]">Package</label>
                                            <select name="session_id" required class="w-full rounded-2xl border border-[#D8DDD8] px-4 py-3 text-sm text-[#223030]">
                                                @foreach($formOptions['packages'] as $packageOption)
                                                    <option value="{{ $packageOption->session_id }}" {{ $enrollment->session_id == $packageOption->session_id ? 'selected' : '' }}>
                                                        {{ $packageOption->session_count }}-Session Package — ₱{{ number_format($packageOption->price, 2) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-[#223030]">Instrument</label>
                                            <select name="instrument_id" required class="edit-instrument w-full rounded-2xl border border-[#D8DDD8] px-4 py-3 text-sm text-[#223030]" data-target="edit-instructor-{{ $enrollment->enrollment_id }}" data-selected-instructor="{{ $enrollment->instructor_id }}">
                                                @foreach($formOptions['instruments'] as $instrument)
                                                    <option value="{{ $instrument->instrument_id }}" {{ $enrollment->instrument_id == $instrument->instrument_id ? 'selected' : '' }}>
                                                        {{ $instrument->instrument_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-[#223030]">Instructor</label>
                                            <select name="instructor_id" id="edit-instructor-{{ $enrollment->enrollment_id }}" required class="w-full rounded-2xl border border-[#D8DDD8] px-4 py-3 text-sm text-[#223030]">
                                                <option value="{{ $enrollment->instructor_id }}">{{ $enrollment->instructor?->full_name ?? 'Current Instructor' }}</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-[#223030]">Payment method</label>
                                            <select name="payment_method_id" class="w-full rounded-2xl border border-[#D8DDD8] px-4 py-3 text-sm text-[#223030]">
                                                <option value="">Select payment method</option>
                                                @foreach($formOptions['paymentMethods'] as $method)
                                                    <option value="{{ $method->method_id }}" {{ $enrollment->payment_method_id == $method->method_id ? 'selected' : '' }}>{{ $method->method_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-[#223030]">Start date</label>
                                            <input type="date" name="start_date" required min="{{ date('Y-m-d') }}" value="{{ $enrollment->start_date?->format('Y-m-d') }}" class="w-full rounded-2xl border border-[#D8DDD8] px-4 py-3 text-sm text-[#223030]">
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-[#223030]">Preferred time slot</label>
                                            <select name="preferred_lesson_time" required class="w-full rounded-2xl border border-[#D8DDD8] px-4 py-3 text-sm text-[#223030]">
                                                @foreach($formOptions['timeSlots'] as $slot)
                                                    <option value="{{ $slot }}" {{ $enrollment->preferred_lesson_time === $slot ? 'selected' : '' }}>{{ $slot }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-[#223030]">Preferred lesson days</label>
                                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                                            @foreach($formOptions['validDays'] as $day)
                                                <label class="flex cursor-pointer items-start gap-2 rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-3">
                                                    <input type="checkbox" name="preferred_lesson_days[]" value="{{ $day }}" {{ in_array($day, $selectedDays, true) ? 'checked' : '' }} class="mt-1 rounded border-[#959D90] text-[#29353C] focus:ring-[#29353C]">
                                                    <span>
                                                        <span class="block text-sm font-bold text-[#223030]">{{ $day }}</span>
                                                        <span class="block text-[11px] text-[#768A96]">{{ $day === 'Sunday' ? '10AM-6PM' : '9AM-8PM' }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-[#223030]">Notes</label>
                                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-[#D8DDD8] px-4 py-3 text-sm text-[#223030]">{{ $enrollment->notes }}</textarea>
                                    </div>

                                    <div class="flex justify-end gap-3 border-t border-[#D8DDD8] pt-5">
                                        <button type="button" data-close-modal="edit-{{ $enrollment->enrollment_id }}" class="rounded-2xl border border-[#D8DDD8] bg-white px-5 py-3 text-sm font-bold text-[#29353C] hover:bg-[#F5F7F4]">Close</button>
                                        <button type="submit" class="rounded-2xl bg-[#29353C] px-5 py-3 text-sm font-bold text-white hover:bg-[#223030]">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- Cancel Modal --}}
                    @if($enrollment->can_be_cancelled)
                        <div id="cancel-{{ $enrollment->enrollment_id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
                            <form method="POST" action="{{ route('student.enrollments.cancel', $enrollment->enrollment_id) }}" class="w-full max-w-xl rounded-[28px] bg-white p-6 shadow-2xl">
                                @csrf
                                <h3 class="text-lg font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">Cancel Enrollment</h3>
                                <p class="mt-2 text-sm text-[#44576D]">This is only allowed before the package starts. Please provide a reason if needed.</p>
                                <textarea name="cancellation_reason" rows="4" class="mt-4 w-full rounded-2xl border border-[#D8DDD8] px-4 py-3 text-sm text-[#223030]" placeholder="Reason for cancellation (optional)"></textarea>
                                <div class="mt-5 flex justify-end gap-3">
                                    <button type="button" data-close-modal="cancel-{{ $enrollment->enrollment_id }}" class="rounded-2xl border border-[#D8DDD8] bg-white px-5 py-3 text-sm font-bold text-[#29353C]">Close</button>
                                    <button type="submit" class="rounded-2xl bg-[#523D35] px-5 py-3 text-sm font-bold text-white">Confirm Cancel</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Withdrawal Modal --}}
                    @if($enrollment->can_request_withdrawal)
                        <div id="withdraw-{{ $enrollment->enrollment_id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
                            <form method="POST" action="{{ route('student.enrollments.withdrawal-request', $enrollment->enrollment_id) }}" class="w-full max-w-xl rounded-[28px] bg-white p-6 shadow-2xl">
                                @csrf
                                <h3 class="text-lg font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">Request Withdrawal</h3>
                                <p class="mt-2 text-sm text-[#44576D]">This request is for ongoing lessons and will be reviewed by admin or instructor.</p>
                                <textarea name="withdrawal_reason" rows="4" required class="mt-4 w-full rounded-2xl border border-[#D8DDD8] px-4 py-3 text-sm text-[#223030]" placeholder="Please explain why you want to stop or withdraw from this package."></textarea>
                                <div class="mt-5 flex justify-end gap-3">
                                    <button type="button" data-close-modal="withdraw-{{ $enrollment->enrollment_id }}" class="rounded-2xl border border-[#D8DDD8] bg-white px-5 py-3 text-sm font-bold text-[#29353C]">Close</button>
                                    <button type="submit" class="rounded-2xl bg-[#725A19] px-5 py-3 text-sm font-bold text-white">Submit Request</button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&family=Sora:wght@500;600;700;800&display=swap');
</style>
@endpush

@push('scripts')
    @vite('resources/js/student/enrollment-filter.js')
@endpush
