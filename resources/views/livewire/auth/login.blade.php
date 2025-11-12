<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-8 py-10 transition-colors">
    <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6">Sign in to your account</h2>

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

        <!-- Access Help -->
        <div class="text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Need an account? Contact your DevFlow Pro administrator to request access.
            </p>
        </div>
    </form>
</div>

