{{-- resources/views/dashboards/student.blade.php --}}

@extends('layouts.student')

@section('content')
<div class="min-h-full bg-[#F5F7F4] px-4 py-8 sm:px-6 lg:px-8" style="font-family: 'Inter', sans-serif;">
    <div class="mx-auto max-w-7xl space-y-6">

        {{-- Welcome Section --}}
        <section class="overflow-hidden rounded-[28px] border border-[#D8DDD8] bg-white shadow-sm">
            <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-[#768A96]">Student Portal</p>
                    <h1 class="mt-2 text-2xl font-bold text-[#223030] sm:text-3xl" style="font-family: 'Sora', sans-serif;">Student Dashboard</h1>
                    <p class="mt-2 text-sm text-[#44576D]">
                        Welcome back,
                        <span class="font-bold text-[#223030]">
                            {{ trim($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . $student->last_name) }}
                        </span>
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:flex sm:flex-wrap">
                    <a href="{{ route('student.schedule') }}" class="rounded-2xl bg-[#29353C] px-4 py-3 text-center text-xs font-bold text-white shadow-sm transition hover:bg-[#223030] sm:text-sm">
                        My Schedule
                    </a>
                    <a href="{{ route('student.progress') }}" class="rounded-2xl border border-[#D8DDD8] bg-[#F5F7F4] px-4 py-3 text-center text-xs font-bold text-[#29353C] transition hover:bg-white sm:text-sm">
                        My Progress
                    </a>
                    <a href="{{ route('student.packages') }}" class="rounded-2xl border border-[#D8DDD8] bg-[#F5F7F4] px-4 py-3 text-center text-xs font-bold text-[#29353C] transition hover:bg-white sm:text-sm">
                        Enroll Now
                    </a>
                </div>
            </div>
        </section>

        {{-- Compact Dashboard Stat Cards --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5">
            @php
                $statCards = [
                    ['label' => 'Packages', 'value' => $dashboardStats['active_packages'] ?? 0, 'hint' => 'Active'],
                    ['label' => 'Remaining', 'value' => $dashboardStats['remaining_sessions'] ?? 0, 'hint' => 'Sessions'],
                    ['label' => 'Completed', 'value' => $dashboardStats['completed_sessions'] ?? 0, 'hint' => 'Sessions'],
                    ['label' => 'Upcoming', 'value' => $dashboardStats['upcoming_lessons'] ?? 0, 'hint' => 'Lessons'],
                    ['label' => 'Requests', 'value' => $dashboardStats['withdrawal_requests'] ?? 0, 'hint' => 'Withdraw'],
                ];
            @endphp

            @foreach($statCards as $card)
                <div class="rounded-2xl border border-[#D8DDD8] bg-white p-2 text-center shadow-sm sm:p-4">
                    <p class="truncate text-[10px] font-bold uppercase tracking-wide text-[#768A96] sm:text-xs">{{ $card['label'] }}</p>
                    <p class="mt-1 text-lg font-extrabold text-[#223030] sm:text-2xl" style="font-family: 'JetBrains Mono', monospace;">{{ $card['value'] }}</p>
                    <p class="hidden text-xs text-[#44576D] sm:block">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </section>

        {{-- Current Package & Next Lesson Row --}}
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Current Package (2 columns) --}}
            <div class="lg:col-span-2">
                @if($currentEnrollment)
                    <div class="overflow-hidden rounded-[28px] border border-[#D8DDD8] bg-white shadow-sm">
                        <div class="border-b border-[#D8DDD8] bg-[#FCFCFA] px-5 py-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Current package</p>
                                    <h2 class="mt-1 text-xl font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">
                                        {{ $currentEnrollment->lessonSession->session_count }}-Session Package
                                    </h2>
                                </div>
                                <span class="inline-flex w-fit rounded-2xl border border-[#A7DDB5] bg-[#EAF8EE] px-4 py-2 text-xs font-bold text-[#23613B]">
                                    {{ $currentEnrollment->status === 'withdrawal_requested' ? 'Withdrawal Requested' : 'Active' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="rounded-2xl bg-[#F5F7F4] p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Instrument</p>
                                    <p class="mt-1 text-sm font-bold text-[#223030]">{{ $currentEnrollment->instrument->instrument_name ?? 'â€”' }}</p>
                                </div>
                                <div class="rounded-2xl bg-[#F5F7F4] p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Instructor</p>
                                    <p class="mt-1 text-sm font-bold text-[#223030]">{{ $currentEnrollment->instructor->full_name ?? 'TBA' }}</p>
                                </div>
                                <div class="rounded-2xl bg-[#F5F7F4] p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Start date</p>
                                    <p class="mt-1 text-sm font-bold text-[#223030]">{{ $currentEnrollment->start_date?->format('M d, Y') ?? 'â€”' }}</p>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-bold text-[#223030]">Progress</span>
                                    <span class="font-bold text-[#29353C]" style="font-family: 'JetBrains Mono', monospace;">{{ $progressPercentage }}%</span>
                                </div>
                                <div class="h-3 w-full overflow-hidden rounded-full bg-[#D8DDD8]">
                                    <div class="h-3 rounded-full bg-[#44576D] transition-all duration-500" style="width: {{ $progressPercentage }}%"></div>
                                </div>
                                <p class="text-center text-xs font-semibold text-[#768A96]">
                                    {{ $currentEnrollment->completed_sessions }} completed - {{ $currentEnrollment->remaining_sessions }} remaining
                                </p>
                            </div>

                            {{-- Pending schedule note --}}
                            @if(!$nextLesson)
                                <div class="rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-4">
                                    <p class="text-sm font-bold text-[#223030]">Schedule confirmation pending</p>
                                    <p class="mt-1 text-sm text-[#44576D]">
                                        Preferred days: {{ $currentEnrollment->preferred_lesson_days ?? 'Not set' }} | Preferred time: {{ $currentEnrollment->preferred_lesson_time ?? 'Not set' }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="rounded-[28px] border border-[#D8DDD8] bg-white p-8 text-center shadow-sm">
                        <h3 class="text-xl font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">No active package</h3>
                        <p class="mt-2 text-sm text-[#44576D]">Enroll in a package to begin your lessons.</p>
                        <a href="{{ route('student.packages') }}" class="mt-5 inline-flex rounded-2xl bg-[#29353C] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#223030]">
                            Browse Packages
                        </a>
                    </div>
                @endif
            </div>

            {{-- Next Lesson Card --}}
            <div>
                @if($nextLesson)
                    <div class="h-full rounded-[28px] bg-[#29353C] p-6 text-white shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-[#D8DDD8]">Next lesson</p>
                        <h2 class="mt-2 text-xl font-bold" style="font-family: 'Sora', sans-serif;">{{ $nextLesson->instrument->instrument_name ?? 'Lesson' }}</h2>
                        <p class="mt-4 text-sm font-semibold">{{ $nextLesson->schedule_date->format('l, F d') }}</p>
                        <p class="mt-1 text-sm text-[#D8D9DA]">{{ $nextLesson->start_time->format('g:i A') }} â€“ {{ $nextLesson->end_time->format('g:i A') }}</p>
                        <div class="mt-5 space-y-2 rounded-2xl bg-white/10 p-4 text-sm">
                            <p>Instructor: <span class="font-bold">{{ $nextLesson->instructor->full_name ?? 'TBA' }}</span></p>
                            <p>Room: <span class="font-bold">{{ $nextLesson->room_number ?? 'TBA' }}</span></p>
                        </div>
                    </div>
                @else
                    <div class="h-full rounded-[28px] border border-[#D8DDD8] bg-white p-6 text-center shadow-sm">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F5F7F4] text-[#768A96]">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-[#223030]">No upcoming lessons</h3>
                        <p class="mt-2 text-sm text-[#44576D]">Your confirmed schedule will appear here after admin or instructor confirmation.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- Recent Progress --}}
        <section class="rounded-[28px] border border-[#D8DDD8] bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">Learning updates</p>
                    <h2 class="mt-1 text-lg font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">Recent progress</h2>
                </div>
                <a href="{{ route('student.progress') }}" class="text-sm font-bold text-[#29353C] underline">View All</a>
            </div>

            @if($recentProgress->isEmpty())
                <div class="rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-5">
                    <p class="text-sm font-semibold text-[#44576D]">
                        No progress updates yet. Your instructor will add feedback after your lessons.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($recentProgress as $progress)
                        <div class="rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-4 transition hover:bg-white hover:shadow-sm">
                            <div class="flex justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-[#223030]">{{ $progress->progress_date->format('M d, Y') }}</p>
                                    <p class="mt-1 text-xs font-semibold text-[#768A96]">{{ $progress->lesson_topic ?? 'Regular Lesson' }}</p>
                                </div>
                                @if($progress->performance_rating)
                                    <span class="h-fit rounded-xl bg-[#E8ECE8] px-2 py-1 text-xs font-bold text-[#29353C]">{{ $progress->performance_rating }}/10</span>
                                @endif
                            </div>
                            @if($progress->homework)
                                <p class="mt-3 text-xs text-[#44576D]"><strong>Homework:</strong> {{ Str::limit($progress->homework, 60) }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&family=Sora:wght@500;600;700;800&display=swap');
</style>
@endpush

@push('scripts')
@vite([
        'resources/js/student/student-dashboard-stat-cards.js',
        'resources/js/student/student-dashboard-upcoming-filter.js',
    ])
@endpush
