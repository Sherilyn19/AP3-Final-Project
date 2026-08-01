// resources/js/student/schedule-filter-cards.js

(function () {
    'use strict';

    const state = {
        page: 1,
        search: '',
        status: 'all',
        sort: 'date_desc',
        dateFrom: '',
        dateTo: '',
        stats: { total: 0, upcoming: 0, approved: 0, completed: 0 },
        items: [],
        pagination: { page: 1, per_page: 10, total: 0, total_pages: 1 },
    };

    const statCards = [
        { key: 'total', label: 'All Lessons' },
        { key: 'upcoming', label: 'Upcoming' },
        { key: 'approved', label: 'Approved' },
        { key: 'completed', label: 'Completed' },
    ];

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function findTitle() {
        return Array.from(document.querySelectorAll('h1, h2')).find((heading) => {
            return heading.textContent.trim().toLowerCase().includes('my schedule');
        });
    }

    function createRoot() {
        let root = document.getElementById('studentScheduleCleanFilterRoot');

        if (root) {
            return root;
        }

        const title = findTitle();

        if (!title) {
            return null;
        }

        root = document.createElement('section');
        root.id = 'studentScheduleCleanFilterRoot';
        root.className = 'mb-6';

        const wrapper = title.closest('div') || title;
        wrapper.insertAdjacentElement('afterend', root);

        return root;
    }

    function hideOriginalScheduleList() {
        const root = document.getElementById('studentScheduleCleanFilterRoot');

        Array.from(document.querySelectorAll('a, button, span, div, article, section')).forEach((element) => {
            if (root && root.contains(element)) {
                return;
            }

            const text = element.innerText || element.textContent || '';
            const hasDate = /(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2},\s+\d{4}/i.test(text);
            const hasScheduleWords = text.includes('Instrument:') || text.includes('Instructor:') || text.includes('Room:') || text.includes('Topic:') || text.toLowerCase().includes('view details');

            if (!hasDate || !hasScheduleWords) {
                return;
            }

            let current = element;

            for (let i = 0; i < 8 && current; i++) {
                const currentText = current.innerText || current.textContent || '';
                const currentHasDate = /(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2},\s+\d{4}/i.test(currentText);
                const currentHasScheduleWords = currentText.includes('Instrument:') || currentText.includes('Instructor:') || currentText.includes('Room:') || currentText.includes('Topic:') || currentText.toLowerCase().includes('view details');

                if (currentHasDate && currentHasScheduleWords && currentText.length < 900) {
                    current.classList.add('hidden');
                    return;
                }

                current = current.parentElement;
            }
        });
    }

    async function fetchData() {
        const params = new URLSearchParams({
            page: state.page,
            search: state.search,
            status: state.status,
            sort: state.sort,
            date_from: state.dateFrom,
            date_to: state.dateTo,
        });

        const response = await fetch('/student/schedule/filter-data?' + params.toString(), {
            headers: { Accept: 'application/json' },
        });

        const data = await response.json();

        if (!response.ok || data.ok === false) {
            throw new Error(data.message || 'Unable to load schedule data.');
        }

        state.stats = data.stats || state.stats;
        state.items = Array.isArray(data.items) ? data.items : [];
        state.pagination = data.pagination || state.pagination;
    }

    async function reload() {
        const root = createRoot();

        if (!root) {
            return;
        }

        hideOriginalScheduleList();

        root.innerHTML = `
            <div class="rounded-[28px] border border-[#D8DDD8] bg-white p-4 shadow-sm">
                <p class="text-sm font-extrabold text-[#44576D]">Loading schedule records...</p>
            </div>
        `;

        try {
            await fetchData();
            hideOriginalScheduleList();
            render();
        } catch (error) {
            root.innerHTML = `
                <div class="rounded-[28px] border border-[#D8DDD8] bg-white p-4 shadow-sm">
                    <h2 class="text-base font-extrabold text-[#223030]">Unable to load schedule data</h2>
                    <p class="mt-1 text-sm font-semibold text-[#44576D]">${escapeHtml(error.message)}</p>
                </div>
            `;
        }
    }

    function render() {
        const root = createRoot();

        if (!root) {
            return;
        }

        root.innerHTML = `
            <div class="space-y-3">
                ${renderStats()}
                ${renderFilters()}
                ${renderList()}
            </div>
        `;

        bindEvents();
    }

    function renderStats() {
        return `
            <div class="rounded-[28px] border border-[#D8DDD8] bg-white p-4 shadow-sm">
                <div class="mb-3">
                    <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-[#768A96]">Schedule Stats</p>
                    <h2 class="mt-1 text-base font-extrabold text-[#223030]">Lesson Overview</h2>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    ${statCards.map((card) => {
                        const value = state.stats[card.key] ?? 0;

                        return `
                            <div class="rounded-[24px] border border-[#D8DDD8] bg-white p-3 shadow-sm">
                                <p class="text-xl font-black text-[#223030]">${escapeHtml(value)}</p>
                                <h3 class="mt-3 text-sm font-extrabold text-[#223030]">${escapeHtml(card.label)}</h3>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    function renderFilters() {
        return `
            <div class="rounded-[28px] border border-[#D8DDD8] bg-white p-4 shadow-sm">
                <div class="mb-3">
                    <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-[#768A96]">Filter Section</p>
                    <h2 class="mt-1 text-base font-extrabold text-[#223030]">Search and Sort</h2>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    ${inputBlock('Search', 'scheduleSearchInput', 'search', state.search, 'Topic, room, instructor...')}

                    <div>
                        <label class="text-xs font-extrabold uppercase tracking-wide text-[#768A96]">Status</label>
                        <select id="scheduleStatusFilter" class="mt-2 w-full rounded-2xl border border-[#D8DDD8] bg-white px-4 py-3 text-sm font-bold text-[#223030] outline-none focus:border-[#223030]">
                            ${option('all', 'All Statuses', state.status)}
                            ${option('upcoming', 'Upcoming', state.status)}
                            ${option('approved', 'Approved', state.status)}
                            ${option('completed', 'Completed', state.status)}
                            ${option('past', 'Past', state.status)}
                        </select>
                    </div>

                    ${inputBlock('From Date', 'scheduleDateFrom', 'date', state.dateFrom, '')}
                    ${inputBlock('To Date', 'scheduleDateTo', 'date', state.dateTo, '')}
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="text-xs font-extrabold uppercase tracking-wide text-[#768A96]">Sort</label>
                        <select id="scheduleSortFilter" class="mt-2 w-full rounded-2xl border border-[#D8DDD8] bg-white px-4 py-3 text-sm font-bold text-[#223030] outline-none focus:border-[#223030]">
                            ${option('date_desc', 'Newest Date First', state.sort)}
                            ${option('date_asc', 'Oldest Date First', state.sort)}
                            ${option('time_asc', 'Earliest Time First', state.sort)}
                            ${option('time_desc', 'Latest Time First', state.sort)}
                        </select>
                    </div>

                    <div class="flex items-end gap-3 xl:col-span-3">
                        <button id="scheduleApplyFilter" type="button" class="rounded-2xl bg-[#223030] px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-[#29353C]">Apply Filter</button>
                        <button id="scheduleResetFilter" type="button" class="rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2.5 text-sm font-extrabold text-[#223030] transition hover:bg-[#F8F7F4]">Reset</button>
                    </div>
                </div>
            </div>
        `;
    }

    function inputBlock(label, id, type, value, placeholder) {
        return `
            <div>
                <label class="text-xs font-extrabold uppercase tracking-wide text-[#768A96]">${escapeHtml(label)}</label>
                <input id="${escapeHtml(id)}" type="${escapeHtml(type)}" value="${escapeHtml(value)}" placeholder="${escapeHtml(placeholder)}" class="mt-2 w-full rounded-2xl border border-[#D8DDD8] bg-white px-4 py-3 text-sm font-bold text-[#223030] outline-none focus:border-[#223030]">
            </div>
        `;
    }

    function option(value, label, selected) {
        return `<option value="${escapeHtml(value)}" ${value === selected ? 'selected' : ''}>${escapeHtml(label)}</option>`;
    }

    function renderList() {
        return `
            <div class="rounded-[28px] border border-[#D8DDD8] bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-[#768A96]">Schedule Records</p>
                        <h2 class="mt-1 text-base font-extrabold text-[#223030]">My Lessons</h2>
                    </div>

                    <div class="rounded-2xl border border-[#D8DDD8] bg-[#F8F7F4] px-4 py-2 text-sm font-extrabold text-[#223030]">
                        Showing ${escapeHtml(state.items.length)} of ${escapeHtml(state.pagination.total)} record(s)
                    </div>
                </div>

                <div class="space-y-3">
                    ${state.items.length ? state.items.map(renderScheduleItem).join('') : renderEmpty()}
                </div>

                ${renderPagination()}
            </div>
        `;
    }

    function renderScheduleItem(item) {
        return `
            <article class="rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-3">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-[#223030]">${escapeHtml(item.date)}</h3>
                        <p class="mt-1 text-sm font-bold text-[#44576D]">${escapeHtml(item.time)}</p>
                    </div>

                    <span class="w-fit rounded-full border border-[#D8DDD8] bg-white px-4 py-2 text-xs font-extrabold text-[#223030]">
                        ${escapeHtml(item.status)}
                    </span>
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    ${miniBox('Instrument', item.instrument)}
                    ${miniBox('Instructor', item.instructor)}
                    ${miniBox('Room', item.room)}
                    ${miniBox('Topic', item.topic)}
                    ${miniBox('Package', item.package)}
                    ${miniBox('Enrollment', item.enrollment_status)}
                    ${miniBox('Completed', item.completed_sessions)}
                    ${miniBox('Remaining', item.remaining_sessions)}
                </div>
            </article>
        `;
    }

    function miniBox(label, value) {
        return `
            <div class="rounded-2xl bg-white p-3">
                <p class="text-[11px] font-extrabold uppercase tracking-wide text-[#768A96]">${escapeHtml(label)}</p>
                <p class="mt-1 text-sm font-extrabold text-[#223030]">${escapeHtml(value)}</p>
            </div>
        `;
    }

    function renderEmpty() {
        return `
            <div class="rounded-2xl border border-[#D8DDD8] bg-[#FCFCFA] p-6 text-center">
                <h3 class="text-base font-extrabold text-[#223030]">No records found</h3>
                <p class="mt-1 text-sm font-semibold text-[#44576D]">Try changing your filters.</p>
            </div>
        `;
    }

    function renderPagination() {
        const page = Number(state.pagination.page || 1);
        const totalPages = Number(state.pagination.total_pages || 1);

        return `
            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" data-page-action="prev" ${page <= 1 ? 'disabled' : ''} class="rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2 text-sm font-extrabold text-[#223030] transition hover:bg-[#F8F7F4] disabled:cursor-not-allowed disabled:opacity-40">Previous</button>
                <span class="text-center text-sm font-extrabold text-[#223030]">Page ${page} of ${totalPages}</span>
                <button type="button" data-page-action="next" ${page >= totalPages ? 'disabled' : ''} class="rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2 text-sm font-extrabold text-[#223030] transition hover:bg-[#F8F7F4] disabled:cursor-not-allowed disabled:opacity-40">Next</button>
            </div>
        `;
    }

    function bindEvents() {
        document.getElementById('scheduleApplyFilter')?.addEventListener('click', function () {
            state.search = document.getElementById('scheduleSearchInput')?.value || '';
            state.status = document.getElementById('scheduleStatusFilter')?.value || 'all';
            state.sort = document.getElementById('scheduleSortFilter')?.value || 'date_desc';
            state.dateFrom = document.getElementById('scheduleDateFrom')?.value || '';
            state.dateTo = document.getElementById('scheduleDateTo')?.value || '';
            state.page = 1;
            reload();
        });

        document.getElementById('scheduleResetFilter')?.addEventListener('click', function () {
            state.search = '';
            state.status = 'all';
            state.sort = 'date_desc';
            state.dateFrom = '';
            state.dateTo = '';
            state.page = 1;
            reload();
        });

        document.querySelectorAll('[data-page-action]').forEach((button) => {
            button.addEventListener('click', function () {
                const action = this.dataset.pageAction;

                if (action === 'prev') {
                    state.page = Math.max(1, state.page - 1);
                }

                if (action === 'next') {
                    state.page += 1;
                }

                reload();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', reload);
    } else {
        reload();
    }
})();