{{-- resources/views/student/partials/sidebar.blade.php --}}

{{-- ── Sidebar Header ──────────────────────────────────────────────── --}}
<div class="p-6 border-b border-[#61677A] flex items-center justify-between">
    <h1 class="text-xl font-bold text-[#D8D9DA]">Student Portal</h1>

    {{-- Close button visible only on mobile --}}
    <button data-close-sidebar class="lg:hidden text-[#D8D9DA] hover:text-[#FFF6E0] focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

{{-- ── Navigation Menu ─────────────────────────────────────────────── --}}
<nav class="flex-1 px-4 py-6">
    <ul class="space-y-2">

        {{-- Dashboard --}}
        <li>
            <a href="{{ route('student.dashboard') }}"
               class="flex items-center px-4 py-3 rounded-lg transition
                      {{ request()->routeIs('student.dashboard') ? 'bg-[#61677A] text-white' : 'text-[#D8D9DA] hover:bg-[#61677A]/30' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
        </li>

        {{-- My Schedule --}}
        <li>
            <a href="{{ route('student.schedule') }}"
               class="flex items-center px-4 py-3 rounded-lg transition
                      {{ request()->routeIs('student.schedule') ? 'bg-[#61677A] text-white' : 'text-[#D8D9DA] hover:bg-[#61677A]/30' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                My Schedule
            </a>
        </li>

        {{-- My Progress --}}
        <li>
            <a href="{{ route('student.progress') }}"
               class="flex items-center px-4 py-3 rounded-lg transition
                      {{ request()->routeIs('student.progress') ? 'bg-[#61677A] text-white' : 'text-[#D8D9DA] hover:bg-[#61677A]/30' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                My Progress
            </a>
        </li>

        {{-- Lesson Packages --}}
        <li>
            <a href="{{ route('student.packages') }}"
               class="flex items-center px-4 py-3 rounded-lg transition
                      {{ request()->routeIs('student.packages') ? 'bg-[#61677A] text-white' : 'text-[#D8D9DA] hover:bg-[#61677A]/30' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Lesson Packages
            </a>
        </li>

        {{-- My Enrollments --}}
        <li>
            <a href="{{ route('student.enrollments') }}"
               class="flex items-center px-4 py-3 rounded-lg transition
                      {{ request()->routeIs('student.enrollments') ? 'bg-[#61677A] text-white' : 'text-[#D8D9DA] hover:bg-[#61677A]/30' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                My Enrollments
            </a>
        </li>

        {{-- Profile --}}
        <li>
            <a href="{{ route('student.profile') }}"
               class="flex items-center px-4 py-3 rounded-lg transition
                      {{ request()->routeIs('student.profile') ? 'bg-[#61677A] text-white' : 'text-[#D8D9DA] hover:bg-[#61677A]/30' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile
            </a>
        </li>

        {{-- ── Sound Check ──────────────────────────────────────────────────────── --}}
        <li>
            <a href="{{ route('student.guitar.index') }}"
               class="flex items-center px-4 py-3 rounded-lg transition
                      {{ request()->routeIs('student.guitar.index') ? 'bg-[#61677A] text-white' : 'text-[#D8D9DA] hover:bg-[#61677A]/30' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2
                             1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2
                             1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                </svg>
                Sound Check
            </a>
        </li>

        {{-- ── Pitch Monitor ─────────────────────────────────────────────────────── --}}
        <li>
            <a href="{{ route('student.pitch-monitor.index') }}"
               class="flex items-center px-4 py-3 rounded-lg transition
                      {{ request()->routeIs('student.pitch-monitor.index') ? 'bg-[#61677A] text-white' : 'text-[#D8D9DA] hover:bg-[#61677A]/30' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5L6 9H4a1 1 0 00-1 1v4a1 1 0 001 1h2l5 4V5zm4.5 3.5a4 4 0 010 7m2.5-9.5a7 7 0 010 12"/>
                </svg>
                Pitch Monitor
            </a>
        </li>

        {{-- 
        |----------------------------------------------------------------------
        | Practice History
        |----------------------------------------------------------------------
        |
        | Purpose:
        | - This is the only history link in the student sidebar.
        | - It opens Sound Check / String Pitch Detection History by default.
        | - Pitch Monitor History is accessed inside the history page using
        |   the Switch History button, not as a separate sidebar item.
        |
        --}}
        <li>
            <a href="{{ route('student.guitar.history') }}"
               class="flex items-center px-4 py-3 rounded-lg transition
                      {{ request()->routeIs('student.guitar.history') || request()->routeIs('student.pitch-monitor.history') ? 'bg-[#61677A] text-white' : 'text-[#D8D9DA] hover:bg-[#61677A]/30' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Practice History
            </a>
        </li>

    </ul>
</nav>

{{-- ── Logout ──────────────────────────────────────────────────────── --}}
<div class="p-4 border-t border-[#61677A] mt-auto">
    <form action="{{ route('logout') }}" method="POST">
        @csrf

        <button type="submit"
                class="flex w-full items-center px-4 py-3 text-left text-[#FFF6E0] hover:bg-[#61677A]/30 rounded-lg transition">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 013-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Logout
        </button>
    </form>
</div>
