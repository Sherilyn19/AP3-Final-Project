{{-- resources/views/auth/login.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Music Lab</title>
    
    {{-- Tailwind CSS via Vite --}}
    @vite(['resources/css/style.css', 'resources/js/script.js'])
    
    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center p-6 font-poppins">
    
    <div class="w-full max-w-sm">
        
        {{-- Login Card --}}
        <div class="bg-white rounded-xl shadow-xl overflow-hidden">
            
            {{-- Header Section with Official Music Lab Logo --}}
            <div class="bg-[#272829] p-5 text-center">
                {{-- Clickable Logo - Links to Home/Welcome Page --}}
                <a href="{{ route('home') }}" class="inline-block hover:opacity-90 transition-opacity duration-300">
                    <img 
                        src="https://res.cloudinary.com/dibojpqg2/image/upload/v1766933637/music-lab-logo_1_lfcsqw.png" 
                        alt="Music Lab - Lessons & Instruments" 
                        class="mx-auto h-16 md:h-20 object-contain drop-shadow-2xl"
                    >
                </a>
            </div>

            {{-- Form Section --}}
            <div class="p-6">
                
                {{-- Welcome Message --}}
                <div class="text-center mb-6">
                    <h2 class="text-xl font-bold text-[#272829] mb-1">Welcome back</h2>
                    <p class="text-[#61677A] text-xs">Sign in to access your dashboard</p>
                </div>

                {{-- Success Message (after registration) --}}
                @if(session('success'))
                <div class="mb-4 p-3 bg-[#E8F5E9] border-l-4 border-[#377357] rounded-r">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-[#377357] mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-[#377357] font-medium text-xs">{{ session('success') }}</p>
                    </div>
                </div>
                @endif

                {{-- Error Messages --}}
                @if($errors->any())
                <div class="mb-4 p-3 bg-[#FFEBEE] border-l-4 border-[#E07A5F] rounded-r">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-[#E07A5F] mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-[#E07A5F] font-medium text-xs">{{ $errors->first() }}</p>
                    </div>
                </div>
                @endif

                {{-- Login Form --}}
                <form method="POST" action="{{ route('login.process') }}" class="space-y-4">
                    @csrf

                    {{-- Email Address Field --}}
                    <div>
                        <label for="user_email" class="block text-xs font-semibold text-[#272829] mb-1">
                            Email address
                        </label>
                        <div class="relative">
                            {{-- Email Icon --}}
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-[#61677A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                            <input 
                                type="email" 
                                id="user_email" 
                                name="user_email" 
                                value="{{ old('user_email') }}"
                                placeholder="your.email@example.com"
                                required
                                autocomplete="username"
                                class="input-field text-sm"
                            >
                        </div>
                        @error('user_email')
                        <p class="mt-1 text-xs text-[#E07A5F]">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password Field --}}
                    <div>
                        <label for="user_password" class="block text-xs font-semibold text-[#272829] mb-1">
                            Password
                        </label>
                        <div class="relative">
                            {{-- Lock Icon --}}
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-[#61677A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input 
                                type="password" 
                                id="user_password" 
                                name="user_password" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                                class="input-field text-sm pr-10"
                            >
                            {{-- Toggle Password Visibility Button --}}
                            <button 
                                type="button" 
                                onclick="togglePassword(this)"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer hover:opacity-70 transition-opacity z-10"
                                aria-label="Toggle password visibility"
                            >
                                <svg id="eye-icon" class="h-4 w-4 text-[#61677A] pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        @error('user_password')
                        <p class="mt-1 text-xs text-[#E07A5F]">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Sign In Button --}}
                    <button 
                        type="submit"
                        class="btn-primary text-sm py-2"
                    >
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Sign in
                        </span>
                    </button>

                </form>

                {{-- Register Link --}}
                <div class="mt-6 text-center">
                    <div class="inline-flex items-center justify-center p-3 border-2 border-[#61677A] rounded-lg hover:bg-[#FFF6E0] transition-all duration-300">
                        <p class="text-xs">
                            <span class="text-[#61677A] font-medium">Don't have an account?</span>
                            <a href="{{ route('register.student.form') }}" class="font-bold text-[#272829] hover:text-[#61677A] underline ml-1 transition-colors">
                                Register
                            </a>
                        </p>
                    </div>
                </div>

                {{-- Forgot Password Link --}}
                <div class="mt-3 text-center">
                    <span class="text-[10px] leading-tight text-[#61677A]">
                        Forgot your password? Please contact the administrator.
                    </span>
                </div>

            </div>
        </div>

    </div>

    {{-- Forgot Password Modal - Clean Design with Color Palette --}}
    <div id="forgotPasswordModal" class="hidden fixed inset-0 bg-[#272829] bg-opacity-60 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-opacity duration-300 opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
            
            {{-- Step 1: Email Input Form --}}
            <div id="emailStep">
                {{-- Header --}}
                <div class="bg-[#272829] px-6 py-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-[#61677A] p-2 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Reset Password</h3>
                        </div>
                        <button onclick="closeForgotPasswordModal()" class="text-white hover:bg-[#61677A] rounded-full p-1.5 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Form Content --}}
                <div class="px-6 py-5">
                    <p class="text-sm text-[#61677A] mb-5 leading-relaxed">Enter your registered email address and we'll generate a new secure password for you.</p>
                    
                    <form id="forgotPasswordForm" onsubmit="handleForgotPassword(event)" class="space-y-4">
                        <div>
                            <label for="reset_email" class="block text-xs font-semibold text-[#272829] mb-2 uppercase tracking-wide">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-[#61677A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <input 
                                    type="email" 
                                    id="reset_email" 
                                    name="email" 
                                    required 
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-[#D8D9DA] rounded-lg text-sm focus:border-[#61677A] focus:ring-2 focus:ring-[#61677A] focus:ring-opacity-20 transition-all"
                                    placeholder="your.email@example.com"
                                >
                            </div>
                        </div>
                        
                        {{-- Error Alert --}}
                        <div id="modalError" class="hidden">
                            <div class="bg-[#FFEBEE] border-l-4 border-[#E07A5F] p-3 rounded-r-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-[#E07A5F] mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-[#E07A5F] text-xs font-medium"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <button 
                            type="submit" 
                            class="w-full font-semibold py-3 rounded-lg hover:shadow-lg transform hover:scale-[1.02] transition-all duration-200 flex items-center justify-center gap-2"
                            style="background-color: #377357; color: #FFFFFF;"
                        >
                            <svg id="submitBtnIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            <span id="submitBtnText">Reset Password</span>
                            <svg id="submitBtnLoader" class="hidden animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>

                    {{-- Helper Text --}}
                    <p class="text-xs text-[#61677A] text-center mt-4">
                        Remember your password? 
                        <button onclick="closeForgotPasswordModal()" class="text-[#272829] hover:text-[#61677A] font-semibold underline">
                            Back to Login
                        </button>
                    </p>
                </div>
            </div>

            {{-- Step 2: Success with New Password --}}
            <div id="successStep" class="hidden">
                {{-- Success Header --}}
                <div class="px-6 py-5 text-center" style="background-color: #377357;">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-3">
                        <svg class="w-10 h-10" style="color: #377357;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold" style="color: #FFFFFF;">Password Reset!</h4>
                    <p class="text-sm mt-1" style="color: #FFFFFF; opacity: 0.9;">Your new credentials are ready</p>
                </div>

                <div class="px-6 py-5">
                    {{-- Email Confirmation --}}
                    <div class="bg-[#FFF6E0] border-l-4 border-[#C2922F] p-3 rounded-r-lg mb-4">
                        <p class="text-xs text-[#272829]">
                            <span class="font-semibold">Confirmed Email: </span> 
                            <span id="userEmail" class="text-[#61677A] font-mono"></span>
                        </p>
                    </div>

                    {{-- Password Display Card --}}
                    <div class="bg-[#F7F7F8] border-2 border-[#D8D9DA] rounded-xl p-4 mb-4">
                        <label class="block text-xs font-bold text-[#61677A] uppercase tracking-wider mb-2">Your New Password</label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="text" 
                                id="newPasswordDisplay" 
                                readonly 
                                class="flex-1 bg-white text-[#272829] font-mono font-bold text-base px-4 py-3 rounded-lg border-2 border-[#D8D9DA] text-center select-all focus:outline-none focus:border-[#377357] transition-colors"
                            >
                            <button onclick="copyPassword()" class="px-4 py-3 rounded-lg transition-all transform hover:scale-105 flex-shrink-0" style="background-color: #377357;">
                                <svg class="w-5 h-5" style="color: #FFFFFF;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Important Notice --}}
                    <div class="bg-[#FFF6E0] border-l-4 border-[#C2922F] p-3 rounded-r-lg mb-5">
                        <div class="flex gap-2">
                            <svg class="w-5 h-5 text-[#C2922F] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-xs font-semibold text-[#272829] mb-1">Important Security Note</p>
                                <p class="text-xs text-[#61677A]">Copy this password now. Change it in <strong>Settings → Profile</strong> after logging in.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-3">
                        <button onclick="closeForgotPasswordModal()" class="flex-1 font-semibold py-3 rounded-lg hover:shadow-lg transform hover:scale-[1.02] transition-all duration-200" style="background-color: #272829; color: #FFFFFF;">
                            Close & Login
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>