<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-8 py-10 transition-colors">
    <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6">Sign in to your account</h2>

    {{-- First Setup Notice --}}
    @if ($this->isFirstSetup)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10 p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-500 dark:text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        Welcome to DevFlow Pro - First Time Setup
                    </h3>
                    <div class="mt-2 text-sm text-amber-700 dark:text-amber-200">
                        <p class="mb-2">Use these default credentials to log in:</p>
                        <div class="bg-white/50 dark:bg-gray-800/50 rounded px-3 py-2 font-mono text-xs">
                            <div><span class="text-gray-500 dark:text-gray-400">Email:</span> <span class="font-semibold">admin@devflow.local</span></div>
                            <div><span class="text-gray-500 dark:text-gray-400">Password:</span> <span class="font-semibold">password</span></div>
                        </div>
                        <p class="mt-2 text-xs text-amber-600 dark:text-amber-300/80">
                            <strong>Important:</strong> Change your password immediately after logging in.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-200">
            {{ session('status') }}
        </div>
    @endif
    
    <form wire:submit="login" class="space-y-6">
        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email address</label>
            <input wire:model="email" 
                   id="email" 
                   type="email" 
                   required 
                   autofocus
                   class="input @error('email') border-red-500 dark:border-red-400 @enderror">
            @error('email') 
                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
            <input wire:model="password" 
                   id="password" 
                   type="password" 
                   required
                   class="input @error('password') border-red-500 dark:border-red-400 @enderror">
            @error('password') 
                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input wire:model="remember" 
                       id="remember" 
                       type="checkbox"
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                    Remember me
                </label>
            </div>

            <div class="text-sm">
                <a href="{{ route('password.request') }}" class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors">
                    Forgot your password?
                </a>
            </div>
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" 
                    class="w-full btn btn-primary" 
                    wire:loading.attr="disabled">
                <span wire:loading.remove>Sign in</span>
                <span wire:loading>Signing in...</span>
            </button>
        </div>

        <!-- Register Link -->
        <div class="text-center">
            @if(\App\Models\SystemSetting::isRegistrationEnabled())
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors">
                        Register here
                    </a>
                </p>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Need an account? Contact your DevFlow Pro administrator to request access.
                </p>
            @endif
        </div>
    </form>
</div>

