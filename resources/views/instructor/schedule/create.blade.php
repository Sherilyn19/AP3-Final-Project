{{-- resources/views/instructor/schedule/create.blade.php --}}
@extends('layouts.instructor')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#B4833D]">Schedule</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#2F4F4F]" style="font-family: 'Sora', sans-serif;">Create Schedule</h1>
            <p class="mt-2 text-sm text-[#61677A]">Only students with active enrollments and remaining sessions are shown.</p>
        </div>
        <a href="{{ route('instructor.schedule.index') }}" class="rounded-2xl border border-[#959D90] bg-white px-4 py-2 text-sm font-bold text-[#2F4F4F] hover:bg-[#FFF6E0]">Back</a>
    </header>

    <form method="POST" action="{{ route('instructor.schedule.store') }}" class="rounded-[28px] border border-[#D8D9DA] bg-white p-5 shadow-sm sm:p-6">
        @csrf

        @if($errors->any())
            <div class="mb-5 rounded-2xl border border-[#B4833D] bg-[#FFF6E0] p-4 text-sm text-[#523D35]">
                <strong>Please check the form.</strong>
                <ul class="mt-2 list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-bold text-[#2F4F4F]">Student / Enrollment</label>
                <select name="student_id" required class="w-full rounded-2xl border border-[#D8D9DA] px-4 py-3 text-sm focus:border-[#959D90] focus:ring-[#959D90]">
                    <option value="">Select student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->student_id }}" data-preferred-days="{{ $student->preferred_lesson_days ?? '' }}" data-preferred-time="{{ $student->preferred_lesson_time ?? '' }}" @selected(old('student_id') == $student->student_id)>
                            {{ $student->student_name }} — {{ $student->instrument_name }} — {{ $student->remaining_sessions }} sessions left
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-[#2F4F4F]">Date</label>
                <input type="date" name="schedule_date" value="{{ old('schedule_date') }}" required class="w-full rounded-2xl border border-[#D8D9DA] px-4 py-3 text-sm focus:border-[#959D90] focus:ring-[#959D90]">
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-[#2F4F4F]">Room</label>
                <select name="room_number" class="w-full rounded-2xl border border-[#D8D9DA] px-4 py-3 text-sm focus:border-[#959D90] focus:ring-[#959D90]">
                    <option value="">No room yet</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->room_number }}" @selected(old('room_number') == $room->room_number)>
                            {{ $room->room_number }}{{ $room->room_name ? ' - ' . $room->room_name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-[#2F4F4F]">Start Time</label>
                <input type="time" name="start_time" value="{{ old('start_time') }}" required class="w-full rounded-2xl border border-[#D8D9DA] px-4 py-3 text-sm focus:border-[#959D90] focus:ring-[#959D90]" style="font-family: 'JetBrains Mono', monospace;">
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-[#2F4F4F]">End Time</label>
                <input type="time" name="end_time" value="{{ old('end_time') }}" required class="w-full rounded-2xl border border-[#D8D9DA] px-4 py-3 text-sm focus:border-[#959D90] focus:ring-[#959D90]" style="font-family: 'JetBrains Mono', monospace;">
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-bold text-[#2F4F4F]">Lesson Topic</label>
                <input type="text" name="lesson_topic" value="{{ old('lesson_topic') }}" class="w-full rounded-2xl border border-[#D8D9DA] px-4 py-3 text-sm focus:border-[#959D90] focus:ring-[#959D90]" placeholder="Example: Basic chord transitions">
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-bold text-[#2F4F4F]">Notes</label>
                <textarea name="notes" rows="4" class="w-full rounded-2xl border border-[#D8D9DA] px-4 py-3 text-sm focus:border-[#959D90] focus:ring-[#959D90]" placeholder="Optional reminders or lesson details">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('instructor.schedule.index') }}" class="rounded-2xl border border-[#959D90] px-5 py-3 text-center text-sm font-bold text-[#2F4F4F] hover:bg-[#FFF6E0]">Cancel</a>
            <button type="submit" class="rounded-2xl bg-[#2F4F4F] px-5 py-3 text-sm font-bold text-white hover:bg-[#B4833D]">Save Schedule</button>
        </div>
    </form>
</div>
@endsection

@once
<script>
(function initPreferredScheduleAutofill() {
    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    ready(function () {
        const studentSelect = document.querySelector('select[name="student_id"]');
        const dateInput = document.querySelector('input[name="schedule_date"]');
        const startInput = document.querySelector('input[name="start_time"]');
        const endInput = document.querySelector('input[name="end_time"]');
        const notesInput = document.querySelector('textarea[name="notes"]');

        if (!studentSelect || !dateInput || !startInput || !endInput || !notesInput) {
            return;
        }

        const dayIndex = {
            sunday: 0,
            monday: 1,
            tuesday: 2,
            wednesday: 3,
            thursday: 4,
            friday: 5,
            saturday: 6
        };

        function clean(value) {
            return String(value || '').replace(/\s+/g, ' ').trim();
        }

        function pad(value) {
            return String(value).padStart(2, '0');
        }

        function toDateValue(date) {
            return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
        }

        function firstPreferredDay(daysText) {
            const value = clean(daysText).toLowerCase();

            return Object.keys(dayIndex).find(function (day) {
                return value.includes(day);
            }) || '';
        }

        function nextDateForDay(dayName) {
            if (!dayName) {
                return '';
            }

            const today = new Date();
            let diff = dayIndex[dayName] - today.getDay();

            if (diff < 0) {
                diff += 7;
            }

            const result = new Date(today);
            result.setDate(today.getDate() + diff);

            return toDateValue(result);
        }

        function to24Hour(hour, minute, period) {
            let h = Number(hour);

            if (period.toLowerCase() === 'pm' && h !== 12) {
                h += 12;
            }

            if (period.toLowerCase() === 'am' && h === 12) {
                h = 0;
            }

            return pad(h) + ':' + pad(minute || '00');
        }

        function parsePreferredTime(timeText) {
            const matches = Array.from(clean(timeText).matchAll(/(\d{1,2})(?::(\d{2}))?\s*(AM|PM)/gi));

            if (!matches.length) {
                return {
                    start: '',
                    end: ''
                };
            }

            return {
                start: to24Hour(matches[0][1], matches[0][2] || '00', matches[0][3]),
                end: matches[1] ? to24Hour(matches[1][1], matches[1][2] || '00', matches[1][3]) : ''
            };
        }

        function displayDate(value) {
            if (!value) {
                return 'Not set';
            }

            const date = new Date(value + 'T00:00:00');

            if (Number.isNaN(date.getTime())) {
                return 'Not set';
            }

            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
        }

        function setAutoReadonly(isReadonly) {
            [dateInput, startInput, endInput].forEach(function (input) {
                input.readOnly = isReadonly;
                input.classList.toggle('bg-[#F8F7F4]', isReadonly);
            });
        }

        function buildUi() {
            if (document.getElementById('preferredScheduleBox')) {
                return;
            }

            const preferredBox = document.createElement('div');
            preferredBox.id = 'preferredScheduleBox';
            preferredBox.className = 'mt-3 hidden rounded-2xl border border-[#D8D9DA] bg-[#FFFDF6] p-4';

            preferredBox.innerHTML =
                '<p class="text-xs font-bold uppercase tracking-[0.18em] text-[#B4833D]">Student Preferred Schedule</p>' +
                '<div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">' +
                    '<div class="rounded-xl bg-white p-3">' +
                        '<p class="text-[11px] font-bold uppercase text-[#61677A]">Preferred Days</p>' +
                        '<p id="preferredDaysText" class="mt-1 text-sm font-bold text-[#2F4F4F]">Not set</p>' +
                    '</div>' +
                    '<div class="rounded-xl bg-white p-3">' +
                        '<p class="text-[11px] font-bold uppercase text-[#61677A]">Preferred Time</p>' +
                        '<p id="preferredTimeText" class="mt-1 text-sm font-bold text-[#2F4F4F]">Not set</p>' +
                    '</div>' +
                    '<div class="rounded-xl bg-white p-3">' +
                        '<p class="text-[11px] font-bold uppercase text-[#61677A]">Auto Date</p>' +
                        '<p id="autoDateText" class="mt-1 text-sm font-bold text-[#2F4F4F]">Not set</p>' +
                    '</div>' +
                    '<div class="rounded-xl bg-white p-3">' +
                        '<p class="text-[11px] font-bold uppercase text-[#61677A]">Auto Time</p>' +
                        '<p id="autoTimeText" class="mt-1 text-sm font-bold text-[#2F4F4F]">Not set</p>' +
                    '</div>' +
                '</div>';

            studentSelect.insertAdjacentElement('afterend', preferredBox);

            const manualBox = document.createElement('div');
            manualBox.id = 'manualScheduleBox';
            manualBox.className = 'sm:col-span-2';

            manualBox.innerHTML =
                '<label class="flex items-start gap-3 rounded-2xl border border-[#D8D9DA] bg-white px-4 py-3">' +
                    '<input id="useDifferentSchedule" type="checkbox" name="use_different_schedule" value="1" class="mt-1 rounded border-[#959D90] text-[#2F4F4F] focus:ring-[#959D90]">' +
                    '<span>' +
                        '<span class="block text-sm font-bold text-[#2F4F4F]">Use different schedule</span>' +
                        '<span class="block text-xs font-semibold text-[#61677A]">Check this if the student preferred schedule cannot be followed. Add the reason in Notes.</span>' +
                    '</span>' +
                '</label>';

            const dateWrapper = dateInput.closest('div');

            if (dateWrapper) {
                dateWrapper.insertAdjacentElement('beforebegin', manualBox);
            }
        }

        function applyPreferredSchedule() {
            buildUi();

            const selected = studentSelect.options[studentSelect.selectedIndex];
            const preferredBox = document.getElementById('preferredScheduleBox');
            const manualToggle = document.getElementById('useDifferentSchedule');
            const preferredDaysText = document.getElementById('preferredDaysText');
            const preferredTimeText = document.getElementById('preferredTimeText');
            const autoDateText = document.getElementById('autoDateText');
            const autoTimeText = document.getElementById('autoTimeText');

            if (!selected || !selected.value) {
                preferredBox.classList.add('hidden');
                dateInput.value = '';
                startInput.value = '';
                endInput.value = '';
                notesInput.required = false;
                setAutoReadonly(false);
                return;
            }

            const days = clean(selected.dataset.preferredDays || '');
            const time = clean(selected.dataset.preferredTime || '');
            const firstDay = firstPreferredDay(days);
            const autoDate = nextDateForDay(firstDay);
            const parsedTime = parsePreferredTime(time);

            preferredBox.classList.remove('hidden');

            preferredDaysText.textContent = days || 'Not set';
            preferredTimeText.textContent = time || 'Not set';

            if (!manualToggle.checked) {
                dateInput.value = autoDate || '';
                startInput.value = parsedTime.start || '';
                endInput.value = parsedTime.end || '';
                notesInput.required = false;
                notesInput.placeholder = 'Optional reminders or lesson details';
                setAutoReadonly(true);
            }

            autoDateText.textContent = displayDate(dateInput.value);
            autoTimeText.textContent = (startInput.value || 'Not set') + ' - ' + (endInput.value || 'Not set');
        }

        function applyManualMode() {
            const manualToggle = document.getElementById('useDifferentSchedule');
            const autoDateText = document.getElementById('autoDateText');
            const autoTimeText = document.getElementById('autoTimeText');

            if (manualToggle.checked) {
                dateInput.value = '';
                startInput.value = '';
                endInput.value = '';
                notesInput.required = true;
                notesInput.placeholder = 'Required: explain why the student preferred schedule cannot be followed.';
                setAutoReadonly(false);

                if (autoDateText) autoDateText.textContent = 'Manual';
                if (autoTimeText) autoTimeText.textContent = 'Manual';
                return;
            }

            applyPreferredSchedule();
        }

        buildUi();

        studentSelect.addEventListener('change', function () {
            const manualToggle = document.getElementById('useDifferentSchedule');

            if (manualToggle) {
                manualToggle.checked = false;
            }

            applyPreferredSchedule();
        });

        document.addEventListener('change', function (event) {
            if (event.target && event.target.id === 'useDifferentSchedule') {
                applyManualMode();
            }
        });

        applyPreferredSchedule();
    });
})();
</script>
@endonce
