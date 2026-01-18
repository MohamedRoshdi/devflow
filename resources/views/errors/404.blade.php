@extends('errors.layout')

@section('title', '404 - Page Not Found')

@section('content')
    {{-- Error Code --}}
    <div class="mb-8">
        <div class="relative inline-block">
            <span class="text-[180px] font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-400 to-cyan-400 leading-none select-none">
                404
            </span>
            <div class="absolute inset-0 text-[180px] font-black text-emerald-500/20 blur-2xl leading-none select-none">
                404
            </div>
        </div>
    </div>

    {{-- Glass Card --}}
    <div class="glass rounded-3xl p-8 md:p-12 mb-8">
        {{-- Icon --}}
        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-2xl shadow-emerald-500/30">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">Page Not Found</h1>
        <p class="text-slate-400 text-lg mb-8 max-w-md mx-auto">
            The page you're looking for doesn't exist or has been moved to another location.
        </p>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ url('/') }}" class="group px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-300 hover:-translate-y-1 flex items-center gap-2">
                <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Go Home
            </a>
            <button onclick="history.back()" class="px-8 py-4 rounded-2xl bg-white/5 border border-white/10 text-white font-medium hover:bg-white/10 transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Go Back
            </button>
        </div>
    </div>

    {{-- Help Text --}}
    <p class="text-slate-500 text-sm">
        Need help? <a href="mailto:support@devflow.pro" class="text-emerald-400 hover:text-emerald-300 transition-colors">Contact Support</a>
    </p>
@endsection
