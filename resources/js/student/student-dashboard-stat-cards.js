/*
|--------------------------------------------------------------------------
| Music Lab - Student Dashboard Clickable Stat Cards
|
| resources/js/student/student-dashboard-stat-cards.js
|--------------------------------------------------------------------------
|
| Updated:
| - Remaining Sessions no longer shows a general summary.
| - Completed Sessions no longer shows a general summary.
| - These two cards now load specific session details from Laravel.
|
*/

(function () {
    'use strict';

    const CARD_CONFIGS = {
        packages: {
            label: 'PACKAGES',
            title: 'Lesson Packages',
            description: 'This shows the active lesson package records connected to your student account.',
            actionTexts: ['Lesson Packages', 'My Enrollments', 'Enroll Now'],
        },
        remaining: {
            label: 'REMAINING',
            title: 'Remaining Sessions',
            description: '',
            actionTexts: ['My Schedule', 'My Progress'],
        },
        completed: {
            label: 'COMPLETED',
            title: 'Completed Sessions',
            description: 'This shows the specific lesson sessions already completed and counted in your progress.',
            actionTexts: ['My Progress', 'My Schedule'],
        },
        upcoming: {
            label: 'UPCOMING',
            title: 'Upcoming Lessons',
            description: 'This shows confirmed upcoming lessons. If this is zero, your schedule may still be pending confirmation.',
            actionTexts: ['My Schedule'],
        },
        requests: {
            label: 'REQUESTS',
            title: 'Withdrawal Requests',
            description: 'This shows your current withdrawal-related request count, if any request is pending or recorded.',
            actionTexts: ['My Enrollments', 'Enroll Now'],
        },
    };

    const ORDERED_KEYS = ['packages', 'remaining', 'completed', 'upcoming', 'requests'];

    let sessionBreakdownCache = null;

    function cleanText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function normalize(value) {
        return cleanText(value).toUpperCase();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function findTextElementExact(text) {
        const target = normalize(text);

        return Array.from(document.querySelectorAll('body *')).find((element) => {
            const elementText = normalize(element.textContent);

            if (element.children.length > 2) {
                return false;
            }

            return elementText === target;
        });
    }

    function findCardContainer(label) {
        const labelElement = findTextElementExact(label);

        if (!labelElement) {
            return null;
        }

        let current = labelElement;

        for (let i = 0; i < 8 && current; i++) {
            const text = cleanText(current.innerText || current.textContent);
            const hasLabel = normalize(text).includes(label);
            const hasNumber = /\d+/.test(text);
            const isReasonableSize = text.length <= 160;

            if (hasLabel && hasNumber && isReasonableSize) {
                return current;
            }

            current = current.parentElement;
        }

        return labelElement.closest('div');
    }

    function getCardInfo(card, label) {
        const text = cleanText(card.innerText || card.textContent);
        const parts = text.split(' ').filter(Boolean);

        let value = '0';
        const numberMatch = text.match(/-?\d+/);

        if (numberMatch) {
            value = numberMatch[0];
        }

        const labelIndex = parts.findIndex((part) => normalize(part) === normalize(label));
        let subtitle = '';

        if (labelIndex !== -1) {
            const afterLabel = parts.slice(labelIndex + 1);
            const numberIndex = afterLabel.findIndex((part) => /^-?\d+$/.test(part));

            if (numberIndex !== -1) {
                subtitle = afterLabel.slice(numberIndex + 1).join(' ');
            }
        }

        return {
            value,
            subtitle: subtitle || '',
            rawText: text,
        };
    }

    function findLinkByText(possibleTexts) {
        const links = Array.from(document.querySelectorAll('a[href]'));

        for (const wanted of possibleTexts) {
            const match = links.find((link) => {
                return normalize(link.textContent).includes(normalize(wanted));
            });

            if (match) {
                return {
                    text: cleanText(match.textContent),
                    href: match.href,
                };
            }
        }

        return null;
    }

    function extractNearbyValue(label) {
        const labelElement = findTextElementExact(label);

        if (!labelElement) {
            return 'Not shown';
        }

        const parent = labelElement.closest('div');

        if (!parent) {
            return 'Not shown';
        }

        const text = cleanText(parent.innerText || parent.textContent);
        const labelPattern = new RegExp(label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
        const value = cleanText(text.replace(labelPattern, ''));

        return value || 'Not shown';
    }

    function extractCurrentPackageName() {
        const currentLabel = findTextElementExact('CURRENT PACKAGE') || findTextElementExact('Current Package');

        if (!currentLabel) {
            return 'Not shown';
        }

        let wrapper = currentLabel;

        for (let i = 0; i < 6 && wrapper; i++) {
            const text = cleanText(wrapper.innerText || wrapper.textContent);

            if (text.toUpperCase().includes('CURRENT PACKAGE') && text.length > 20) {
                const lines = String(wrapper.innerText || wrapper.textContent)
                    .split('\n')
                    .map(cleanText)
                    .filter(Boolean);

                const index = lines.findIndex((line) => normalize(line) === 'CURRENT PACKAGE');

                if (index !== -1 && lines[index + 1]) {
                    return lines[index + 1];
                }
            }

            wrapper = wrapper.parentElement;
        }

        return 'Not shown';
    }

    function getDashboardSnapshot() {
        const progressText = Array.from(document.querySelectorAll('body *'))
            .map((element) => cleanText(element.textContent))
            .find((text) => /^Progress\s+\d+%$/i.test(text));

        const noUpcoming = cleanText(document.body.innerText).toLowerCase().includes('no upcoming lessons');

        return {
            packageName: extractCurrentPackageName(),
            instrument: extractNearbyValue('INSTRUMENT'),
            instructor: extractNearbyValue('INSTRUCTOR'),
            startDate: extractNearbyValue('START DATE'),
            progress: progressText ? progressText.replace(/Progress/i, '').trim() : 'Not shown',
            scheduleStatus: noUpcoming ? 'No confirmed upcoming lesson shown' : 'Upcoming lesson information is shown on the dashboard.',
        };
    }

    async function fetchSessionBreakdown() {
        if (sessionBreakdownCache) {
            return sessionBreakdownCache;
        }

        try {
            const response = await fetch('/student/dashboard/session-breakdown', {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load session breakdown.');
            }

            sessionBreakdownCache = await response.json();

            return sessionBreakdownCache;
        } catch (error) {
            console.error(error);

            sessionBreakdownCache = {
                ok: false,
                message: error.message || 'Unable to load session details.',
                completed: [],
                remaining: [],
                total_sessions: null,
            };

            return sessionBreakdownCache;
        }
    }

    function getRowsForCard(key, cardInfo) {
        const snapshot = getDashboardSnapshot();

        if (key === 'packages') {
            return [
                ['Active package count', cardInfo.value + ' ' + (cardInfo.subtitle || '')],
                ['Current package', snapshot.packageName],
                ['Instrument', snapshot.instrument],
                ['Instructor', snapshot.instructor],
                ['Start date', snapshot.startDate],
                ['Progress', snapshot.progress],
            ];
        }

        if (key === 'upcoming') {
            return [
                ['Upcoming lessons', cardInfo.value + ' ' + (cardInfo.subtitle || '')],
                ['Schedule status', snapshot.scheduleStatus],
                ['Current package', snapshot.packageName],
                ['Instructor', snapshot.instructor],
                ['Reminder', 'Confirmed schedules will appear once approved or assigned.'],
            ];
        }

        if (key === 'requests') {
            return [
                ['Request count', cardInfo.value + ' ' + (cardInfo.subtitle || '')],
                ['Status', Number(cardInfo.value) === 0 ? 'No active withdrawal request shown.' : 'There is a request count shown on your dashboard.'],
                ['Current package', snapshot.packageName],
                ['Meaning', 'This helps you monitor withdrawal-related records from your enrollment.'],
            ];
        }

        return [];
    }

    function injectStyles() {
        if (document.getElementById('student-stat-card-modal-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'student-stat-card-modal-style';
        style.textContent = `
            .student-stat-clickable {
                cursor: pointer;
                position: relative;
                transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease, background-color 180ms ease;
            }

            .student-stat-clickable:hover {
                transform: translateY(-3px);
                box-shadow: 0 18px 40px rgba(34, 48, 48, 0.10);
                border-color: #768A96 !important;
                background-color: #FCFCFA;
            }

            .student-stat-clickable:focus {
                outline: 3px solid rgba(118, 138, 150, 0.35);
                outline-offset: 3px;
            }

            .student-stat-clickable::after {
                content: "View details";
                position: absolute;
                right: 14px;
                bottom: 10px;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #768A96;
                opacity: 0;
                transform: translateY(4px);
                transition: opacity 180ms ease, transform 180ms ease;
            }

            .student-stat-clickable:hover::after,
            .student-stat-clickable:focus::after {
                opacity: 1;
                transform: translateY(0);
            }

            .student-stat-modal-open {
                overflow: hidden;
            }

            .student-stat-modal-backdrop {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 18px;
                background: rgba(22, 29, 31, 0.42);
                backdrop-filter: blur(8px);
            }

            .student-stat-modal-card {
                width: min(680px, 100%);
                max-height: 88vh;
                overflow: auto;
                border: 1px solid #D8DDD8;
                border-radius: 28px;
                background: #FFFFFF;
                box-shadow: 0 24px 80px rgba(34, 48, 48, 0.22);
                animation: studentStatModalIn 180ms ease-out;
            }

            @keyframes studentStatModalIn {
                from {
                    opacity: 0;
                    transform: translateY(10px) scale(0.98);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            .student-stat-modal-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                padding: 24px 24px 18px;
                border-bottom: 1px solid #EEF1EC;
                background: #FCFCFA;
            }

            .student-stat-modal-kicker {
                margin-bottom: 6px;
                color: #768A96;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.16em;
                text-transform: uppercase;
            }

            .student-stat-modal-title {
                margin: 0;
                color: #223030;
                font-size: 24px;
                font-weight: 800;
                line-height: 1.2;
            }

            .student-stat-modal-desc {
                margin-top: 8px;
                color: #44576D;
                font-size: 14px;
                line-height: 1.55;
            }

            .student-stat-modal-close {
                flex: 0 0 auto;
                width: 38px;
                height: 38px;
                border: 1px solid #D8DDD8;
                border-radius: 999px;
                background: #FFFFFF;
                color: #223030;
                font-size: 22px;
                line-height: 1;
                cursor: pointer;
            }

            .student-stat-modal-close:hover {
                background: #F4F5F2;
                border-color: #768A96;
            }

            .student-stat-modal-body {
                padding: 20px 24px 24px;
            }

            .student-stat-modal-main-number {
                display: flex;
                align-items: baseline;
                gap: 10px;
                margin-bottom: 18px;
                padding: 18px;
                border: 1px solid #EEF1EC;
                border-radius: 22px;
                background: #F8F7F4;
            }

            .student-stat-modal-main-number strong {
                color: #223030;
                font-size: 38px;
                font-weight: 900;
                line-height: 1;
            }

            .student-stat-modal-main-number span {
                color: #44576D;
                font-size: 14px;
                font-weight: 700;
            }

            .student-stat-modal-table {
                display: grid;
                gap: 10px;
            }

            .student-stat-modal-row {
                display: grid;
                grid-template-columns: 160px 1fr;
                gap: 12px;
                padding: 12px 0;
                border-bottom: 1px solid #EEF1EC;
            }

            .student-stat-modal-row-label {
                color: #768A96;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .student-stat-modal-row-value {
                color: #223030;
                font-size: 14px;
                font-weight: 700;
                line-height: 1.45;
            }

            .student-stat-session-heading {
                margin: 18px 0 10px;
                color: #223030;
                font-size: 15px;
                font-weight: 900;
            }

            .student-stat-session-list {
                display: grid;
                gap: 12px;
                margin-top: 12px;
            }

            .student-stat-session-item {
                border: 1px solid #D8DDD8;
                border-radius: 20px;
                background: #FCFCFA;
                padding: 16px;
            }

            .student-stat-session-title {
                color: #223030;
                font-size: 15px;
                font-weight: 900;
                line-height: 1.35;
            }

            .student-stat-session-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px 14px;
                margin-top: 12px;
            }

            .student-stat-session-label {
                display: block;
                color: #768A96;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .student-stat-session-value {
                display: block;
                margin-top: 2px;
                color: #223030;
                font-size: 13px;
                font-weight: 700;
                line-height: 1.4;
            }

            .student-stat-modal-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 22px;
            }

            .student-stat-modal-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 42px;
                padding: 10px 16px;
                border-radius: 16px;
                background: #223030;
                color: #FFFFFF;
                font-size: 13px;
                font-weight: 800;
                text-decoration: none;
            }

            .student-stat-modal-action:hover {
                background: #29353C;
            }

            .student-stat-modal-action.secondary {
                border: 1px solid #D8DDD8;
                background: #FFFFFF;
                color: #223030;
            }

            .student-stat-modal-action.secondary:hover {
                background: #F4F5F2;
            }

            @media (max-width: 640px) {
                .student-stat-modal-row,
                .student-stat-session-grid {
                    grid-template-columns: 1fr;
                }

                .student-stat-modal-title {
                    font-size: 21px;
                }

                .student-stat-modal-main-number strong {
                    font-size: 32px;
                }
            }
        `;

        document.head.appendChild(style);
    }

    function closeModal() {
        const modal = document.getElementById('studentStatInfoModal');

        if (modal) {
            modal.remove();
        }

        document.body.classList.remove('student-stat-modal-open');
    }

    function toInt(value) {
        const number = Number(value || 0);
        return Number.isFinite(number) ? Math.max(0, Math.round(number)) : 0;
    }

    function renderInfoRows(rows) {
        return rows.map(([label, value]) => {
            return `
                <div class="student-stat-modal-row">
                    <div class="student-stat-modal-row-label">${escapeHtml(label)}</div>
                    <div class="student-stat-modal-row-value">${escapeHtml(value)}</div>
                </div>
            `;
        }).join('');
    }

    function renderSmallNotice(text) {
        return `
            <div class="student-stat-session-item" style="background:#F8F7F4;">
                <div class="student-stat-session-title">${escapeHtml(text)}</div>
            </div>
        `;
    }

    function renderCompletedDetails(cardInfo, breakdown) {
        const dashboardCount = toInt(cardInfo.value);
        const items = Array.isArray(breakdown.completed) ? breakdown.completed : [];
        const counts = breakdown.counts || {};
        const completedFromEnrollment = toInt(counts.completed_from_enrollment || dashboardCount);

        const summaryRows = [
            ['Dashboard completed count', completedFromEnrollment + ' sessions'],
            ['Detailed records found', items.length + ' record(s)'],
            ['Detail source', 'attendance, schedule, and progress records'],
        ];

        let html = `
            <h3 class="student-stat-session-heading">Completed Sessions Verification</h3>
            <div class="student-stat-modal-table">
                ${renderInfoRows(summaryRows)}
            </div>
        `;

        if (items.length < completedFromEnrollment) {
            html += renderSmallNotice(
                'The dashboard count exists in the enrollment table, but not all completed sessions have matching detailed attendance/schedule/progress records yet.'
            );
        }

        html += `<h3 class="student-stat-session-heading">Specific Completed Session Details</h3>`;

        if (!items.length) {
            html += renderSmallNotice(
                'No specific completed lesson detail was found. This means the completed count is saved in enrollment, but the detailed lesson records may not be encoded yet.'
            );

            return html;
        }

        html += `<div class="student-stat-session-list">`;

        html += items.map((item, index) => {
            return `
                <div class="student-stat-session-item">
                    <div class="student-stat-session-title">${escapeHtml(index + 1)}. ${escapeHtml(item.title || 'Completed Lesson')}</div>

                    <div class="student-stat-session-grid">
                        <div>
                            <span class="student-stat-session-label">Date</span>
                            <span class="student-stat-session-value">${escapeHtml(item.date || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Time</span>
                            <span class="student-stat-session-value">${escapeHtml(item.time || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Instructor</span>
                            <span class="student-stat-session-value">${escapeHtml(item.instructor || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Instrument</span>
                            <span class="student-stat-session-value">${escapeHtml(item.instrument || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Package</span>
                            <span class="student-stat-session-value">${escapeHtml(item.package || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Status</span>
                            <span class="student-stat-session-value">${escapeHtml(item.status || 'Completed')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Source</span>
                            <span class="student-stat-session-value">${escapeHtml(item.source || 'Database record')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Lesson Topic</span>
                            <span class="student-stat-session-value">${escapeHtml(item.lesson_topic || 'No lesson topic encoded')}</span>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <span class="student-stat-session-label">Remarks</span>
                            <span class="student-stat-session-value">${escapeHtml(item.remarks || 'None')}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        html += `</div>`;

        return html;
    }

    function renderRemainingDetails(cardInfo, breakdown) {
        const dashboardCount = toInt(cardInfo.value);
        const enrollments = Array.isArray(breakdown.remaining_enrollments)
            ? breakdown.remaining_enrollments
            : [];

        const counts = breakdown.counts || {};
        const remainingFromEnrollment = toInt(counts.remaining_from_enrollment || dashboardCount);

        let html = `
            <h3 class="student-stat-session-heading">Remaining Sessions Verification</h3>
            <div class="student-stat-modal-table">
                ${renderInfoRows([
                    ['Dashboard remaining count', remainingFromEnrollment + ' sessions'],
                    ['Active enrollment records', enrollments.length + ' package(s)'],
                ])}
            </div>
        `;

        html += `<h3 class="student-stat-session-heading">Remaining Session Details</h3>`;

        if (!enrollments.length) {
            html += renderSmallNotice('No active enrollment record was found for remaining sessions.');

            return html;
        }

        html += `<div class="student-stat-session-list">`;

        html += enrollments.map((item, index) => {
            const nextLesson = item.next_lesson
                ? `${item.next_lesson.date || 'Not set'} - ${item.next_lesson.time || 'Not set'}`
                : 'No confirmed upcoming lesson yet';

            const upcomingLessons = Array.isArray(item.upcoming_lessons)
                ? item.upcoming_lessons
                : [];

            const upcomingHtml = upcomingLessons.length
                ? `
                    <div style="grid-column: 1 / -1;">
                        <span class="student-stat-session-label">Next Confirmed Lesson(s)</span>
                        <span class="student-stat-session-value">
                            ${upcomingLessons.map((lesson, lessonIndex) => {
                                return escapeHtml(
                                    (lessonIndex + 1) + '. ' +
                                    (lesson.date || 'Not set') + ' - ' +
                                    (lesson.time || 'Not set') + ' - ' +
                                    (lesson.status || 'Scheduled')
                                );
                            }).join('<br>')}
                        </span>
                    </div>
                `
                : `
                    <div style="grid-column: 1 / -1;">
                        <span class="student-stat-session-label">Next Confirmed Lesson(s)</span>
                        <span class="student-stat-session-value">No confirmed upcoming schedule yet.</span>
                    </div>
                `;

            return `
                <div class="student-stat-session-item">
                    <div class="student-stat-session-title">${escapeHtml(index + 1)}. ${escapeHtml(item.instrument || 'Instrument')} - ${escapeHtml(item.package || 'Lesson Package')}</div>

                    <div class="student-stat-session-grid">
                        <div>
                            <span class="student-stat-session-label">Remaining Sessions</span>
                            <span class="student-stat-session-value">${escapeHtml(item.remaining_sessions ?? 0)} session(s)</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Completed Sessions</span>
                            <span class="student-stat-session-value">${escapeHtml(item.completed_sessions ?? 0)} session(s)</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Total Package Sessions</span>
                            <span class="student-stat-session-value">${escapeHtml(item.total_sessions ?? 0)} session(s)</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Confirmed Upcoming</span>
                            <span class="student-stat-session-value">${escapeHtml(item.confirmed_upcoming_count ?? 0)} lesson(s)</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Unscheduled Remaining Slots</span>
                            <span class="student-stat-session-value">${escapeHtml(item.unscheduled_remaining_slots ?? 0)} slot(s)</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Status</span>
                            <span class="student-stat-session-value">${escapeHtml(item.status || 'Active')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Instructor</span>
                            <span class="student-stat-session-value">${escapeHtml(item.instructor || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Instrument</span>
                            <span class="student-stat-session-value">${escapeHtml(item.instrument || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Preferred Days</span>
                            <span class="student-stat-session-value">${escapeHtml(item.preferred_days || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Preferred Time</span>
                            <span class="student-stat-session-value">${escapeHtml(item.preferred_time || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">Start Date</span>
                            <span class="student-stat-session-value">${escapeHtml(item.start_date || 'Not set')}</span>
                        </div>

                        <div>
                            <span class="student-stat-session-label">End Date</span>
                            <span class="student-stat-session-value">${escapeHtml(item.end_date || 'Not set')}</span>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <span class="student-stat-session-label">Next Lesson</span>
                            <span class="student-stat-session-value">${escapeHtml(nextLesson)}</span>
                        </div>

                        ${upcomingHtml}
                    </div>
                </div>
            `;
        }).join('');

        html += `</div>`;

        return html;
    }

    function renderSessionDetails(key, cardInfo, breakdown) {
        if (!breakdown || breakdown.ok === false) {
            return `
                <h3 class="student-stat-session-heading">Session Details</h3>
                ${renderSmallNotice(breakdown?.message || 'Unable to load session details.')}
            `;
        }

        if (key === 'completed') {
            return renderCompletedDetails(cardInfo, breakdown);
        }

        return renderRemainingDetails(cardInfo, breakdown);
    }

    async function openModal(key, cardInfo) {
        closeModal();

        const config = CARD_CONFIGS[key];
        const primaryLink = findLinkByText(config.actionTexts);
        const secondaryLink = key !== 'packages'
            ? findLinkByText(['Student Dashboard', 'Dashboard'])
            : findLinkByText(['Enroll Now']);

        let rowsHtml = '';
        let sessionDetailsHtml = '';

        if (key === 'completed' || key === 'remaining') {
            const breakdown = await fetchSessionBreakdown();
            sessionDetailsHtml = renderSessionDetails(key, cardInfo, breakdown);
        } else {
            const rows = getRowsForCard(key, cardInfo);

            rowsHtml = rows.map(([label, value]) => {
                return `
                    <div class="student-stat-modal-row">
                        <div class="student-stat-modal-row-label">${escapeHtml(label)}</div>
                        <div class="student-stat-modal-row-value">${escapeHtml(value)}</div>
                    </div>
                `;
            }).join('');
        }

        const primaryAction = primaryLink
            ? `<a class="student-stat-modal-action" href="${escapeHtml(primaryLink.href)}">${escapeHtml(primaryLink.text || 'Open')}</a>`
            : '';

        const secondaryAction = secondaryLink && (!primaryLink || secondaryLink.href !== primaryLink.href)
            ? `<a class="student-stat-modal-action secondary" href="${escapeHtml(secondaryLink.href)}">${escapeHtml(secondaryLink.text || 'Open Dashboard')}</a>`
            : '';

        const modal = document.createElement('div');
        modal.id = 'studentStatInfoModal';
        modal.className = 'student-stat-modal-backdrop';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', config.title);

        modal.innerHTML = `
            <div class="student-stat-modal-card">
                <div class="student-stat-modal-header">
                    <div>
                        <div class="student-stat-modal-kicker">Student Dashboard</div>
                        <h2 class="student-stat-modal-title">${escapeHtml(config.title)}</h2>
                        <div class="student-stat-modal-desc">${escapeHtml(config.description)}</div>
                    </div>
                    <button type="button" class="student-stat-modal-close" aria-label="Close">&times;</button>
                </div>

                <div class="student-stat-modal-body">
                    <div class="student-stat-modal-main-number">
                        <strong>${escapeHtml(cardInfo.value)}</strong>
                        <span>${escapeHtml(cardInfo.subtitle || config.title)}</span>
                    </div>

                    <div class="student-stat-modal-table">
                        ${rowsHtml}
                    </div>

                    ${sessionDetailsHtml}

                    <div class="student-stat-modal-actions">
                        ${primaryAction}
                        ${secondaryAction}
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        document.body.classList.add('student-stat-modal-open');

        const closeButton = modal.querySelector('.student-stat-modal-close');
        closeButton?.focus();

        closeButton?.addEventListener('click', closeModal);

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    function makeCardClickable(key) {
        const config = CARD_CONFIGS[key];
        const card = findCardContainer(config.label);

        if (!card || card.dataset.statClickableReady === 'true') {
            return;
        }

        card.dataset.statClickableReady = 'true';
        card.classList.add('student-stat-clickable');
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');
        card.setAttribute('aria-label', 'View details for ' + config.title);

        card.addEventListener('click', function () {
            openModal(key, getCardInfo(card, config.label));
        });

        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openModal(key, getCardInfo(card, config.label));
            }
        });
    }

    function init() {
        injectStyles();

        ORDERED_KEYS.forEach(makeCardClickable);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

