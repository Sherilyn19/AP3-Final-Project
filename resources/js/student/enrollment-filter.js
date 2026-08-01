// resources/js/student/enrollment-filter.js

(function () {
    'use strict';

    const state = {
        cards: [],
        page: 1,
        perPage: 10,
        search: '',
        status: 'all',
        instrument: 'all',
        sort: 'latest',
        listContainer: null,
    };

    function cleanText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function normalize(value) {
        return cleanText(value).toLowerCase();
    }

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
            return normalize(heading.textContent).includes('my packages');
        });
    }

    function findStatsWrapper() {
        const possibleWrappers = Array.from(document.querySelectorAll('div, section'));

        return possibleWrappers.find((element) => {
            const text = normalize(element.innerText || element.textContent || '');
            const hasStats =
                text.includes('total') &&
                text.includes('active') &&
                text.includes('remaining') &&
                text.includes('completed') &&
                text.includes('requests');

            return hasStats && text.length < 900;
        }) || null;
    }

    function createRoot() {
        let root = document.getElementById('studentEnrollmentFilterRoot');

        if (root) {
            return root;
        }

        root = document.createElement('section');
        root.id = 'studentEnrollmentFilterRoot';
        root.className = 'my-6';

        const statsWrapper = findStatsWrapper();
        const title = findTitle();

        if (statsWrapper) {
            statsWrapper.insertAdjacentElement('afterend', root);
            return root;
        }

        if (title) {
            const header = title.closest('div') || title;
            header.insertAdjacentElement('afterend', root);
            return root;
        }

        return null;
    }

    function findEnrollmentCards() {
        const root = document.getElementById('studentEnrollmentFilterRoot');
        const candidates = [];

        Array.from(document.querySelectorAll('div, article, section')).forEach((element) => {
            if (root && root.contains(element)) {
                return;
            }

            const text = cleanText(element.innerText || element.textContent || '');
            const lower = normalize(text);

            const enrolledCount = (text.match(/Enrolled:/g) || []).length;
            const looksLikeSingleEnrollment =
                enrolledCount === 1 &&
                lower.includes('package') &&
                lower.includes('progress') &&
                lower.includes('remaining') &&
                lower.includes('instructor') &&
                !lower.includes('lesson management') &&
                !lower.includes('my packages & enrollments');

            if (!looksLikeSingleEnrollment) {
                return;
            }

            if (text.length < 80 || text.length > 3500) {
                return;
            }

            candidates.push(element);
        });

        // Keep only the largest useful card per enrollment by removing nested duplicates.
        const unique = [];

        candidates.forEach((candidate) => {
            const isInsideExisting = unique.some((existing) => existing.contains(candidate));
            const containsExisting = unique.some((existing) => candidate.contains(existing));

            if (isInsideExisting) {
                return;
            }

            if (containsExisting) {
                for (let i = unique.length - 1; i >= 0; i--) {
                    if (candidate.contains(unique[i])) {
                        unique.splice(i, 1);
                    }
                }
            }

            unique.push(candidate);
        });

        return unique;
    }

    function extractInstrument(lines) {
        const packageIndex = lines.findIndex((line) => normalize(line).includes('package'));

        if (packageIndex > 0) {
            return lines[packageIndex - 1] || 'Not set';
        }

        return 'Not set';
    }

    function extractStatus(text) {
        const lower = normalize(text);

        if (lower.includes('cancelled') || lower.includes('canceled')) return 'cancelled';
        if (lower.includes('active')) return 'active';
        if (lower.includes('pending')) return 'pending';
        if (lower.includes('completed')) return 'completed';
        if (lower.includes('withdraw')) return 'withdraw';

        return 'other';
    }

    function parseEnrollmentDate(text) {
        const match = String(text || '').match(/Enrolled:\s*([A-Za-z]+\s+\d{1,2},\s+\d{4})/i);

        if (!match) {
            return 0;
        }

        const date = new Date(match[1]);

        return Number.isNaN(date.getTime()) ? 0 : date.getTime();
    }

    function getCardData(element) {
        const text = element.innerText || element.textContent || '';
        const lines = String(text)
            .split('\n')
            .map(cleanText)
            .filter(Boolean);

        return {
            element,
            text: cleanText(text),
            searchText: normalize(text),
            status: extractStatus(text),
            instrument: extractInstrument(lines),
            dateValue: parseEnrollmentDate(text),
        };
    }

    function getStatusOptions() {
        const labels = {
            active: 'Active',
            cancelled: 'Cancelled',
            pending: 'Pending',
            completed: 'Completed',
            withdraw: 'Withdraw',
            other: 'Other',
        };

        return Array.from(new Set(state.cards.map((card) => card.status)))
            .filter(Boolean)
            .sort()
            .map((value) => ({
                value,
                label: labels[value] || value,
            }));
    }

    function getInstrumentOptions() {
        return Array.from(new Set(state.cards.map((card) => card.instrument)))
            .filter((value) => value && value !== 'Not set')
            .sort()
            .map((value) => ({
                value,
                label: value,
            }));
    }

    function render() {
        const root = createRoot();

        if (!root) {
            return;
        }

        root.innerHTML = `
            <div class="rounded-[26px] border border-[#D8DDD8] bg-white p-4 shadow-sm">
                <div class="mb-3">
                    <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-[#768A96]">Filter Section</p>
                    <h2 class="mt-1 text-lg font-extrabold text-[#223030]">Search Enrollments</h2>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="text-xs font-extrabold uppercase tracking-wide text-[#768A96]">Search</label>
                        <input id="enrollmentSearchInput"
                               type="search"
                               value="${escapeHtml(state.search)}"
                               placeholder="Package, instrument, instructor..."
                               class="mt-2 w-full rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2.5 text-sm font-bold text-[#223030] outline-none focus:border-[#223030]">
                    </div>

                    <div>
                        <label class="text-xs font-extrabold uppercase tracking-wide text-[#768A96]">Status</label>
                        <select id="enrollmentStatusFilter"
                                class="mt-2 w-full rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2.5 text-sm font-bold text-[#223030] outline-none focus:border-[#223030]">
                            ${option('all', 'All Statuses', state.status)}
                            ${getStatusOptions().map((item) => option(item.value, item.label, state.status)).join('')}
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-extrabold uppercase tracking-wide text-[#768A96]">Instrument</label>
                        <select id="enrollmentInstrumentFilter"
                                class="mt-2 w-full rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2.5 text-sm font-bold text-[#223030] outline-none focus:border-[#223030]">
                            ${option('all', 'All Instruments', state.instrument)}
                            ${getInstrumentOptions().map((item) => option(item.value, item.label, state.instrument)).join('')}
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-extrabold uppercase tracking-wide text-[#768A96]">Sort</label>
                        <select id="enrollmentSortFilter"
                                class="mt-2 w-full rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2.5 text-sm font-bold text-[#223030] outline-none focus:border-[#223030]">
                            ${option('latest', 'Newest First', state.sort)}
                            ${option('oldest', 'Oldest First', state.sort)}
                            ${option('status', 'Status', state.sort)}
                            ${option('instrument', 'Instrument', state.sort)}
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button id="enrollmentApplyFilter"
                            type="button"
                            class="rounded-2xl bg-[#223030] px-5 py-2.5 text-sm font-extrabold text-white transition hover:bg-[#29353C]">
                        Apply Filter
                    </button>

                    <button id="enrollmentResetFilter"
                            type="button"
                            class="rounded-2xl border border-[#D8DDD8] bg-white px-5 py-2.5 text-sm font-extrabold text-[#223030] transition hover:bg-[#F8F7F4]">
                        Reset
                    </button>

                    <span id="enrollmentFilterCount" class="text-sm font-extrabold text-[#44576D]"></span>
                </div>

                <div id="enrollmentFilterPagination"
                     class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                </div>
            </div>
        `;

        bindEvents();
        applyFilter();
    }

    function option(value, label, selected) {
        return `<option value="${escapeHtml(value)}" ${String(value) === String(selected) ? 'selected' : ''}>${escapeHtml(label)}</option>`;
    }

    function getFilteredCards() {
        let cards = state.cards.filter((card) => {
            const searchMatch = !state.search || card.searchText.includes(normalize(state.search));
            const statusMatch = state.status === 'all' || card.status === state.status;
            const instrumentMatch = state.instrument === 'all' || card.instrument === state.instrument;

            return searchMatch && statusMatch && instrumentMatch;
        });

        if (state.sort === 'oldest') {
            cards.sort((a, b) => a.dateValue - b.dateValue);
        } else if (state.sort === 'status') {
            cards.sort((a, b) => a.status.localeCompare(b.status));
        } else if (state.sort === 'instrument') {
            cards.sort((a, b) => a.instrument.localeCompare(b.instrument));
        } else {
            cards.sort((a, b) => b.dateValue - a.dateValue);
        }

        return cards;
    }

    function applyFilter() {
        const filtered = getFilteredCards();
        const totalPages = Math.max(1, Math.ceil(filtered.length / state.perPage));

        state.page = Math.min(Math.max(1, state.page), totalPages);

        const start = (state.page - 1) * state.perPage;
        const visibleCards = filtered.slice(start, start + state.perPage);

        state.cards.forEach((card) => {
            card.element.classList.add('hidden');
        });

        visibleCards.forEach((card) => {
            card.element.classList.remove('hidden');

            if (state.listContainer) {
                state.listContainer.appendChild(card.element);
            }
        });

        const count = document.getElementById('enrollmentFilterCount');

        if (count) {
            count.textContent = `Showing ${visibleCards.length} of ${filtered.length} record(s)`;
        }

        renderPagination(totalPages, filtered.length);
    }

    function renderPagination(totalPages, totalFiltered) {
        const pagination = document.getElementById('enrollmentFilterPagination');

        if (!pagination) {
            return;
        }

        if (totalFiltered <= state.perPage) {
            pagination.innerHTML = totalFiltered === 0
                ? `<span class="text-sm font-extrabold text-[#44576D]">No enrollment record matched your filter.</span>`
                : '';
            return;
        }

        pagination.innerHTML = `
            <button type="button"
                    data-enrollment-page="prev"
                    ${state.page <= 1 ? 'disabled' : ''}
                    class="rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2 text-sm font-extrabold text-[#223030] transition hover:bg-[#F8F7F4] disabled:cursor-not-allowed disabled:opacity-40">
                Previous
            </button>

            <span class="text-center text-sm font-extrabold text-[#223030]">
                Page ${state.page} of ${totalPages} â€¢ 10 per page
            </span>

            <button type="button"
                    data-enrollment-page="next"
                    ${state.page >= totalPages ? 'disabled' : ''}
                    class="rounded-2xl border border-[#D8DDD8] bg-white px-4 py-2 text-sm font-extrabold text-[#223030] transition hover:bg-[#F8F7F4] disabled:cursor-not-allowed disabled:opacity-40">
                Next
            </button>
        `;

        pagination.querySelectorAll('[data-enrollment-page]').forEach((button) => {
            button.addEventListener('click', function () {
                if (this.dataset.enrollmentPage === 'prev') {
                    state.page = Math.max(1, state.page - 1);
                }

                if (this.dataset.enrollmentPage === 'next') {
                    state.page += 1;
                }

                applyFilter();
            });
        });
    }

    function bindEvents() {
        document.getElementById('enrollmentApplyFilter')?.addEventListener('click', function () {
            state.search = document.getElementById('enrollmentSearchInput')?.value || '';
            state.status = document.getElementById('enrollmentStatusFilter')?.value || 'all';
            state.instrument = document.getElementById('enrollmentInstrumentFilter')?.value || 'all';
            state.sort = document.getElementById('enrollmentSortFilter')?.value || 'latest';
            state.page = 1;
            applyFilter();
        });

        document.getElementById('enrollmentResetFilter')?.addEventListener('click', function () {
            state.search = '';
            state.status = 'all';
            state.instrument = 'all';
            state.sort = 'latest';
            state.page = 1;
            render();
        });
    }

    function init() {
        createRoot();

        state.cards = findEnrollmentCards().map(getCardData);
        state.listContainer = state.cards.length ? state.cards[0].element.parentElement : null;

        render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();