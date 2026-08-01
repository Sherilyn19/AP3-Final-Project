{{-- resources/views/student/schedule.blade.php --}}

@extends('layouts.student')

@section('content')
<div class="min-h-full bg-[#F5F7F4] px-4 py-8 sm:px-6 lg:px-8" style="font-family: 'Inter', sans-serif;">
    <div class="mx-auto max-w-7xl space-y-6">

        {{-- Page Header --}}
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-[#768A96]">Lesson Calendar</p>
            <h1 class="mt-2 text-2xl font-bold text-[#223030] sm:text-3xl" style="font-family: 'Sora', sans-serif;">My Schedule</h1>
            <p class="mt-2 text-sm text-[#44576D]">Your confirmed lessons and pending schedule preferences.</p>
        </div>

        {{-- Pending Schedule Confirmation --}}
        @if(isset($pendingEnrollments) && $pendingEnrollments->isNotEmpty())
            <section class="rounded-[28px] border border-[#D8DDD8] bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">Pending schedule confirmation</h2>
                    <p class="mt-1 text-sm text-[#44576D]">These packages are enrolled, but final lesson schedules have not been created yet.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($pendingEnrollments as $enrollment)
                        <div class="rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-[#768A96]">{{ $enrollment->instrument_name ?? 'Instrument' }}</p>
                            <h3 class="mt-1 font-bold text-[#223030]">{{ $enrollment->session_count }}-Session Package</h3>
                            <div class="mt-3 space-y-1 text-sm text-[#44576D]">
                                <p>Instructor: <span class="font-bold text-[#223030]">{{ $enrollment->instructor_full_name ?? 'TBA' }}</span></p>
                                <p>Start date: <span class="font-bold text-[#223030]">{{ $enrollment->start_date ? \Carbon\Carbon::parse($enrollment->start_date)->format('M d, Y') : '—' }}</span></p>
                                <p>Preferred days: <span class="font-bold text-[#223030]">{{ $enrollment->preferred_lesson_days ?? 'Not set' }}</span></p>
                                <p>Preferred time: <span class="font-bold text-[#223030]">{{ $enrollment->preferred_lesson_time ?? 'Not set' }}</span></p>
                            </div>
                            <div class="mt-4 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-[#768A96]">
                                Admin or instructor will confirm the actual date, time, and room.
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Empty State --}}
        @if($schedules->isEmpty())
            <div class="rounded-[28px] border border-[#D8DDD8] bg-white p-10 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#F5F7F4] text-[#768A96]">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-xl font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">No confirmed lessons yet</h3>
                <p class="mt-2 text-sm text-[#44576D]">Your confirmed lessons will appear here once admin or instructor creates your schedule.</p>
            </div>
        @else
            {{-- Schedule List Grouped by Date --}}
            <div class="space-y-5">
                @foreach($schedules as $date => $dailySchedules)
                    <section class="overflow-hidden rounded-[28px] border border-[#D8DDD8] bg-white shadow-sm">
                        {{-- Date Header --}}
                        <div class="border-b border-[#D8DDD8] bg-[#FCFCFA] px-5 py-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <h2 class="text-lg font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">
                                    {{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}
                                </h2>
                                @if($date === now()->toDateString())
                                    <span class="w-fit rounded-2xl bg-[#29353C] px-3 py-1 text-xs font-bold text-white">Today</span>
                                @elseif(\Carbon\Carbon::parse($date)->isFuture())
                                    <span class="w-fit rounded-2xl border border-[#D8DDD8] bg-[#F5F7F4] px-3 py-1 text-xs font-bold text-[#29353C]">Upcoming</span>
                                @else
                                    <span class="w-fit rounded-2xl border border-[#D8DDD8] bg-[#F5F7F4] px-3 py-1 text-xs font-bold text-[#768A96]">Past</span>
                                @endif
                            </div>
                        </div>

                        {{-- Lessons for this Date --}}
                        <div class="divide-y divide-[#EEF1EC]">
                            @foreach($dailySchedules as $schedule)
                                @php
                                    $statusClasses = match($schedule->status) {
                                        'scheduled' => 'border-[#B9C6D6] bg-[#EEF2F4] text-[#29353C]',
                                        'completed' => 'border-[#A7DDB5] bg-[#EAF8EE] text-[#23613B]',
                                        'cancelled' => 'border-[#C56B5F]/40 bg-[#F6EFEC] text-[#523D35]',
                                        'in_progress' => 'border-[#DDBF7A] bg-[#FFF8E6] text-[#725A19]',
                                        default => 'border-[#D8DDD8] bg-[#F5F7F4] text-[#44576D]',
                                    };
                                @endphp

                                <div class="px-5 py-5 transition hover:bg-[#FCFCFA]">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <div class="flex items-center gap-2 text-[#223030]">
                                                    <svg class="h-5 w-5 text-[#44576D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span class="font-bold" style="font-family: 'JetBrains Mono', monospace;">
                                                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                                                    </span>
                                                </div>

                                                <span class="rounded-2xl border px-3 py-1 text-xs font-bold {{ $statusClasses }}">
                                                    {{ ucfirst(str_replace('_', ' ', $schedule->status)) }}
                                                </span>
                                            </div>

                                            <div class="mt-3 grid gap-2 text-sm text-[#44576D] sm:grid-cols-2 lg:grid-cols-4">
                                                <p>Instrument: <span class="font-bold text-[#223030]">{{ $schedule->enrollment?->instrument?->instrument_name ?? 'Lesson' }}</span></p>
                                                <p>Instructor: <span class="font-bold text-[#223030]">{{ trim(($schedule->instructor->first_name ?? '') . ' ' . ($schedule->instructor->middle_name ?? '') . ' ' . ($schedule->instructor->last_name ?? '') . ' ' . ($schedule->instructor->suffix ?? '')) ?: 'TBA' }}</span></p>
                                                <p>Room: <span class="font-bold text-[#223030]">{{ $schedule->room_number ?? 'TBA' }}</span></p>
                                                <p>Topic: <span class="font-bold text-[#223030]">{{ $schedule->lesson_topic ?? 'Regular Lesson' }}</span></p>
                                            </div>
                                        </div>

                                        <button type="button" onclick="showLessonDetails({{ $schedule->schedule_id }})" class="rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2 text-sm font-bold text-[#29353C] transition hover:bg-[#F5F7F4]">
                                            View details
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Lesson Details Modal --}}
<div id="lessonModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-[28px] bg-white shadow-2xl">
        <div class="sticky top-0 flex items-center justify-between border-b border-[#D8DDD8] bg-white px-6 py-4">
            <h3 class="text-lg font-bold text-[#223030]" style="font-family: 'Sora', sans-serif;">Lesson details</h3>
            <button type="button" onclick="closeLessonModal()" class="rounded-xl p-2 text-[#768A96] hover:bg-[#F5F7F4]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="lessonDetailsContent" class="p-6">
            <p class="text-sm font-semibold text-[#44576D]">Loading lesson details...</p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&family=Sora:wght@500;600;700;800&display=swap');
</style>
@endpush

@push('scripts')
    @vite('resources/js/student/schedule-filter-cards.js')
@endpush
