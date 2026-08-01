/*
|--------------------------------------------------------------------------
| Music Lab - Upcoming Lessons Filter Modal
| resources/js/student/student-dashboard-upcoming-filter.js
|--------------------------------------------------------------------------
|
| Purpose:
| - Overrides only the Upcoming Lessons stat card click behavior.
| - Shows ALL upcoming lessons, not only 3.
| - Adds modal filters: All, Today, This Week, This Month, Next 30 Days.
|
*/

(function () {
    'use strict';

    let cachedBreakdown = null;
    let currentLessons = [];

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

    function toDateOnly(value) {
        if (!value) {
            return null;
        }

        const date = new Date(value + 'T00:00:00');

        if (Number.isNaN(date.getTime())) {
            return null;
        }

        return date;
    }

    function dateOnlyNow() {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), now.getDate());
    }

    function addDays(date, days) {
        const copy = new Date(date);
        copy.setDate(copy.getDate() + days);
        return copy;
    }

    function startOfWeekMonday(date) {
        const copy = new Date(date);
        const day = copy.getDay();
        const diff = day === 0 ? -6 : 1 - day;
        copy.setDate(copy.getDate() + diff);
        return copy;
    }

    function endOfWeekSunday(date) {
        return addDays(startOfWeekMonday(date), 6);
    }

    function isBetween(date, start, end) {
        if (!date) {
            return false;
        }

        return date >= start && date <= end;
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

    function findUpcomingCard() {
        const labelElement = findTextElementExact('UPCOMING');

        if (!labelElement) {
            return null;
        }

        let current = labelElement;

        for (let i = 0; i < 8 && current; i++) {
            const text = cleanText(current.innerText || current.textContent);
            const hasLabel = normalize(text).includes('UPCOMING');
            const hasNumber = /\d+/.test(text);
            const isReasonableSize = text.length <= 160;

            if (hasLabel && hasNumber && isReasonableSize) {
                return current;
            }

            current = current.parentElement;
        }

        return labelElement.closest('div');
    }

    function getCardCount(card) {
        const text = cleanText(card?.innerText || card?.textContent || '');
        const numberMatch = text.match(/-?\d+/);

        return numberMatch ? Number(numberMatch[0]) : 0;
    }

    async function fetchBreakdown() {
        if (cachedBreakdown) {
            return cachedBreakdown;
        }

        const response = await fetch('/student/dashboard/session-breakdown', {
            headers: {
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        cachedBreakdown = data;

        return data;
    }

    function getUpcomingLessons(data) {
        if (Array.isArray(data?.upcoming_lessons)) {
            return data.upcoming_lessons;
        }

        // Fallback if the controller has not returned top-level upcoming_lessons yet.
        const enrollments = Array.isArray(data?.remaining_enrollments)
            ? data.remaining_enrollments
            : [];

        return enrollments.flatMap((enrollment) => {
            return Array.isArray(enrollment.upcoming_lessons)
                ? enrollment.upcoming_lessons
                : [];
        });
    }

    function getFilterValues() {
        return {
            range: document.getElementById('upcomingLessonRangeFilter')?.value || 'all',
            keyword: cleanText(document.getElementById('upcomingLessonSearchFilter')?.value || '').toLowerCase(),
        };
    }

    function filterLessons(lessons) {
        const filters = getFilterValues();
        const today = dateOnlyNow();
        const weekStart = startOfWeekMonday(today);
        const weekEnd = endOfWeekSunday(today);
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        const next30 = addDays(today, 30);

        return lessons.filter((lesson) => {
            const lessonDate = toDateOnly(lesson.date_key);
            const searchable = [
                lesson.title,
                lesson.date,
                lesson.time,
                lesson.instructor,
                lesson.instrument,
                lesson.package,
                lesson.status,
                lesson.lesson_topic,
                lesson.remarks,
            ].map(cleanText).join(' ').toLowerCase();

            let rangeMatch = true;

            if (filters.range === 'today') {
                rangeMatch = lessonDate && lessonDate.getTime() === today.getTime();
            }

            if (filters.range === 'week') {
                rangeMatch = isBetween(lessonDate, weekStart, weekEnd);
            }

            if (filters.range === 'month') {
                rangeMatch = isBetween(lessonDate, monthStart, monthEnd);
            }

            if (filters.range === 'next30') {
                rangeMatch = isBetween(lessonDate, today, next30);
            }

            const keywordMatch = !filters.keyword || searchable.includes(filters.keyword);
            return rangeMatch && keywordMatch;
        });
    }

    function getUniqueStatuses(lessons) {
        const statuses = lessons
            .map((lesson) => cleanText(lesson.status))
            .filter(Boolean);

        return Array.from(new Set(statuses));
    }

    function renderLessonList() {
        const list = document.getElementById('upcomingLessonList');
        const count = document.getElementById('upcomingLessonVisibleCount');

        if (!list) {
            return;
        }

        const filtered = filterLessons(currentLessons);

        if (count) {
            count.textContent = filtered.length + ' shown / ' + currentLessons.length + ' total';
        }

        if (!filtered.length) {
            list.innerHTML = `
                <div class="upcoming-filter-empty">
                    No upcoming lessons matched the selected filter.
                </div>
            `;
            return;
        }

        list.innerHTML = filtered.map((lesson, index) => {
            return `
                <div class="upcoming-filter-item">
                    <div class="upcoming-filter-item-title">
                        ${escapeHtml(index + 1)}. ${escapeHtml(lesson.title || 'Confirmed Lesson')}
                    </div>

                    <div class="upcoming-filter-grid">
                        <div>
                            <span>Date</span>
                            <strong>${escapeHtml(lesson.date || 'Not set')}</strong>
                        </div>

                        <div>
                            <span>Time</span>
                            <strong>${escapeHtml(lesson.time || 'Not set')}</strong>
                        </div>

                        <div>
                            <span>Instructor</span>
                            <strong>${escapeHtml(lesson.instructor || 'Not set')}</strong>
                        </div>

                        <div>
                            <span>Instrument</span>
                            <strong>${escapeHtml(lesson.instrument || 'Not set')}</strong>
                        </div>

                        <div>
                            <span>Package</span>
                            <strong>${escapeHtml(lesson.package || 'Lesson Package')}</strong>
                        </div>

                        <div>
                            <span>Status</span>
                            <strong>${escapeHtml(lesson.status || 'Scheduled')}</strong>
                        </div>

                        <div class="upcoming-filter-wide">
                            <span>Lesson Topic</span>
                            <strong>${escapeHtml(lesson.lesson_topic || 'No lesson topic encoded yet')}</strong>
                        </div>

                        <div class="upcoming-filter-wide">
                            <span>Remarks</span>
                            <strong>${escapeHtml(lesson.remarks || 'Confirmed upcoming schedule record.')}</strong>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function injectStyles() {
        if (document.getElementById('upcoming-lessons-filter-modal-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'upcoming-lessons-filter-modal-style';
        style.textContent = `
            .upcoming-filter-controls {
                display: grid;
                grid-template-columns: 1fr 1.2fr;
                gap: 12px;
                margin: 18px 0;
            }

            .upcoming-filter-controls label {
                display: block;
                margin-bottom: 6px;
                color: #768A96;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .upcoming-filter-controls select,
            .upcoming-filter-controls input {
                width: 100%;
                min-height: 42px;
                border: 1px solid #D8DDD8;
                border-radius: 14px;
                background: #FFFFFF;
                padding: 8px 12px;
                color: #223030;
                font-size: 13px;
                font-weight: 700;
            }

            .upcoming-filter-count {
                margin: 6px 0 14px;
                color: #44576D;
                font-size: 13px;
                font-weight: 800;
            }

            .upcoming-filter-list {
                display: grid;
                gap: 12px;
            }

            .upcoming-filter-item {
                border: 1px solid #D8DDD8;
                border-radius: 20px;
                background: #FCFCFA;
                padding: 16px;
            }

            .upcoming-filter-item-title {
                color: #223030;
                font-size: 15px;
                font-weight: 900;
                line-height: 1.35;
            }

            .upcoming-filter-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px 14px;
                margin-top: 12px;
            }

            .upcoming-filter-grid span {
                display: block;
                color: #768A96;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .upcoming-filter-grid strong {
                display: block;
                margin-top: 2px;
                color: #223030;
                font-size: 13px;
                font-weight: 800;
                line-height: 1.4;
            }

            .upcoming-filter-wide {
                grid-column: 1 / -1;
            }

            .upcoming-filter-empty {
                border: 1px solid #D8DDD8;
                border-radius: 20px;
                background: #F8F7F4;
                padding: 18px;
                color: #44576D;
                font-size: 14px;
                font-weight: 800;
            }

            @media (max-width: 700px) {
                .upcoming-filter-controls {
                    grid-template-columns: 1fr;
                }

                .upcoming-filter-grid {
                    grid-template-columns: 1fr;
                }
            }
        `;

        document.head.appendChild(style);
    }

    function closeExistingModal() {
        const existing = document.getElementById('studentStatInfoModal');

        if (existing) {
            existing.remove();
        }

        document.body.classList.remove('student-stat-modal-open');
    }

    async function openUpcomingModal(card) {
        closeExistingModal();
        injectStyles();

        const cardCount = getCardCount(card);

        let data = null;

        try {
            data = await fetchBreakdown();
        } catch (error) {
            data = {
                ok: false,
                message: 'Unable to load upcoming lessons.',
                upcoming_lessons: [],
            };
        }

        currentLessons = getUpcomingLessons(data);

        const statuses = getUniqueStatuses(currentLessons);
        const statusOptions = statuses.map((status) => {
            return `<option value="${escapeHtml(status)}">${escapeHtml(status)}</option>`;
        }).join('');

        const modal = document.createElement('div');
        modal.id = 'studentStatInfoModal';
        modal.className = 'student-stat-modal-backdrop';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', 'Upcoming Lessons');

        modal.innerHTML = `
            <div class="student-stat-modal-card">
                <div class="student-stat-modal-header">
                    <div>
                        <div class="student-stat-modal-kicker">Student Dashboard</div>
                        <h2 class="student-stat-modal-title">Upcoming Lessons</h2>
                        <div class="student-stat-modal-desc">
                            This shows confirmed upcoming lessons. Use the date filter or search field to find specific lesson details.
                        </div>
                    </div>
                    <button type="button" class="student-stat-modal-close" aria-label="Close">&times;</button>
                </div>

                <div class="student-stat-modal-body">
                    <div class="student-stat-modal-main-number">
                        <strong>${escapeHtml(cardCount)}</strong>
                        <span>Lessons shown on dashboard</span>
                    </div>

                    <div class="student-stat-modal-table">
                        <div class="student-stat-modal-row">
                            <div class="student-stat-modal-row-label">Records found</div>
                            <div class="student-stat-modal-row-value">${escapeHtml(currentLessons.length)} upcoming schedule record(s)</div>
                        </div>
                    </div>

                    <div class="upcoming-filter-controls">
                        <div>
                            <label for="upcomingLessonRangeFilter">Date Filter</label>
                            <select id="upcomingLessonRangeFilter">
                                <option value="all">All Upcoming</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="next30">Next 30 Days</option>
                            </select>
                        </div>
<div>
                            <label for="upcomingLessonSearchFilter">Search</label>
                            <input id="upcomingLessonSearchFilter" type="search" placeholder="Search instructor, instrument, topic...">
                        </div>
                    </div>

                    <div id="upcomingLessonVisibleCount" class="upcoming-filter-count"></div>

                    <div id="upcomingLessonList" class="upcoming-filter-list"></div>

                    <div class="student-stat-modal-actions">
                        <a class="student-stat-modal-action" href="/student/schedule">My Schedule</a>
                        <a class="student-stat-modal-action secondary" href="/student/dashboard">Dashboard</a>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        document.body.classList.add('student-stat-modal-open');

        modal.querySelector('.student-stat-modal-close')?.addEventListener('click', closeExistingModal);

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeExistingModal();
            }
        });

        document.getElementById('upcomingLessonRangeFilter')?.addEventListener('change', renderLessonList);
        document.getElementById('upcomingLessonSearchFilter')?.addEventListener('input', renderLessonList);

        renderLessonList();
    }

    function init() {
        const card = findUpcomingCard();

        if (!card || card.dataset.upcomingFilterReady === 'true') {
            return;
        }

        card.dataset.upcomingFilterReady = 'true';

        card.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            openUpcomingModal(card);
        }, true);

        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                event.stopImmediatePropagation();
                openUpcomingModal(card);
            }
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
