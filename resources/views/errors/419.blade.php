@extends('errors.layout')

@section('title', '419 - Session Expired')

@section('content')
    {{-- Error Code --}}
    <div class="mb-8">
        <div class="relative inline-block">
            <span class="text-[180px] font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-teal-400 to-emerald-400 leading-none select-none">
                419
            </span>
            <div class="absolute inset-0 text-[180px] font-black text-cyan-500/20 blur-2xl leading-none select-none">
                419
            </div>
        </div>
    </div>

    {{-- Glass Card --}}
    <div class="glass rounded-3xl p-8 md:p-12 mb-8">
        {{-- Icon --}}
        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-cyan-500 to-teal-600 flex items-center justify-center shadow-2xl shadow-cyan-500/30">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">Session Expired</h1>
        <p class="text-slate-400 text-lg mb-8 max-w-md mx-auto">
            Your session has expired due to inactivity. Please refresh the page or log in again.
        </p>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <button onclick="location.reload()" class="group px-8 py-4 rounded-2xl bg-gradient-to-r from-cyan-500 to-teal-600 text-white font-semibold shadow-lg shadow-cyan-500/30 hover:shadow-xl hover:shadow-cyan-500/40 transition-all duration-300 hover:-translate-y-1 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh Page
            </button>
            <a href="{{ route('login') }}" class="px-8 py-4 rounded-2xl bg-white/5 border border-white/10 text-white font-medium hover:bg-white/10 transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Sign In Again
            </a>
        </div>
    </div>

    {{-- Help Text --}}
    <p class="text-slate-500 text-sm">
        This happens when you've been inactive for too long
    </p>
@endsection
