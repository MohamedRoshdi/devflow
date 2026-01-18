@extends('errors.layout')

@section('title', '429 - Too Many Requests')

@section('content')
    {{-- Error Code --}}
    <div class="mb-8">
        <div class="relative inline-block">
            <span class="text-[180px] font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-400 via-amber-400 to-yellow-400 leading-none select-none">
                429
            </span>
            <div class="absolute inset-0 text-[180px] font-black text-orange-500/20 blur-2xl leading-none select-none">
                429
            </div>
        </div>
    </div>

    {{-- Glass Card --}}
    <div class="glass rounded-3xl p-8 md:p-12 mb-8">
        {{-- Icon --}}
        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center shadow-2xl shadow-orange-500/30">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>

        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">Too Many Requests</h1>
        <p class="text-slate-400 text-lg mb-8 max-w-md mx-auto">
            Slow down! You're making too many requests. Please wait a moment before trying again.
        </p>

        {{-- Countdown Timer --}}
        <div class="max-w-sm mx-auto mb-8">
            <div class="p-4 rounded-2xl bg-orange-500/10 border border-orange-500/30">
                <p class="text-orange-400 text-sm">Please wait before retrying</p>
                <p class="text-2xl font-bold text-white mt-2" id="countdown">60 seconds</p>
            </div>
        </div>

        {{-- Actions --}}
        <button onclick="location.reload()" class="px-8 py-4 rounded-2xl bg-gradient-to-r from-orange-500 to-amber-600 text-white font-semibold shadow-lg shadow-orange-500/30 hover:shadow-xl hover:shadow-orange-500/40 transition-all duration-300 hover:-translate-y-1 flex items-center gap-2 mx-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Try Again
        </button>
    </div>

    {{-- Help Text --}}
    <p class="text-slate-500 text-sm">
        Rate limiting helps protect our servers from abuse
    </p>

    <script>
        let seconds = 60;
        const countdown = document.getElementById('countdown');
        const timer = setInterval(() => {
            seconds--;
            countdown.textContent = seconds + ' seconds';
            if (seconds <= 0) {
                clearInterval(timer);
                countdown.textContent = 'Ready!';
            }
        }, 1000);
    </script>
@endsection
