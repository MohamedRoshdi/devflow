@extends('errors.layout')

@section('title', '401 - Unauthorized')

@section('content')
    {{-- Error Code --}}
    <div class="mb-8">
        <div class="relative inline-block">
            <span class="text-[180px] font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 leading-none select-none">
                401
            </span>
            <div class="absolute inset-0 text-[180px] font-black text-blue-500/20 blur-2xl leading-none select-none">
                401
            </div>
        </div>
    </div>

    {{-- Glass Card --}}
    <div class="glass rounded-3xl p-8 md:p-12 mb-8">
        {{-- Icon --}}
        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-2xl shadow-blue-500/30">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>

        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">Authentication Required</h1>
        <p class="text-slate-400 text-lg mb-8 max-w-md mx-auto">
            You need to sign in to access this page. Please log in with your credentials.
        </p>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('login') }}" class="group px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition-all duration-300 hover:-translate-y-1 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Sign In
            </a>
            <a href="{{ url('/') }}" class="px-8 py-4 rounded-2xl bg-white/5 border border-white/10 text-white font-medium hover:bg-white/10 transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Go Home
            </a>
        </div>
    </div>

    {{-- Help Text --}}
    <p class="text-slate-500 text-sm">
        Don't have an account? <a href="{{ route('register') }}" class="text-blue-400 hover:text-blue-300 transition-colors">Create one</a>
    </p>
@endsection
