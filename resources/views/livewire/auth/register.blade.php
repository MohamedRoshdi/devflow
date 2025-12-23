<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-8 py-10 transition-colors">
    <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-8">Create your account</h2>
    
    <form wire:submit="register" class="space-y-6">
        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
            <input wire:model="name" 
                   id="name" 
                   type="text" 
                   required 
                   autofocus
                   class="input @error('name') border-red-500 dark:border-red-400 @enderror">
            @error('name') 
                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email address</label>
            <input wire:model="email" 
                   id="email" 
                   type="email" 
                   required
                   class="input @error('email') border-red-500 dark:border-red-400 @enderror">
            @error('email') 
                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password with Strength Indicator -->
        <div x-data="{
            password: '',
            get strength() {
                let score = 0;
                if (this.password.length >= 8) score++;
                if (this.password.length >= 12) score++;
                if (/[a-z]/.test(this.password)) score++;
                if (/[A-Z]/.test(this.password)) score++;
                if (/[0-9]/.test(this.password)) score++;
                if (/[^a-zA-Z0-9]/.test(this.password)) score++;
                return score;
            },
            get strengthLabel() {
                if (this.password.length === 0) return '';
                if (this.strength <= 2) return 'Weak';
                if (this.strength <= 4) return 'Fair';
                if (this.strength <= 5) return 'Good';
                return 'Strong';
            },
            get strengthColor() {
                if (this.strength <= 2) return 'bg-red-500';
                if (this.strength <= 4) return 'bg-yellow-500';
                if (this.strength <= 5) return 'bg-blue-500';
                return 'bg-green-500';
            },
            get strengthTextColor() {
                if (this.strength <= 2) return 'text-red-500 dark:text-red-400';
                if (this.strength <= 4) return 'text-yellow-500 dark:text-yellow-400';
                if (this.strength <= 5) return 'text-blue-500 dark:text-blue-400';
                return 'text-green-500 dark:text-green-400';
            }
        }">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
            <input wire:model="password"
                   x-model="password"
                   id="password"
                   type="password"
                   required
                   class="input @error('password') border-red-500 dark:border-red-400 @enderror">

            <!-- Password Strength Indicator -->
            <div x-show="password.length > 0" x-cloak class="mt-2">
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-300 rounded-full"
                             :class="strengthColor"
                             :style="'width: ' + (strength / 6 * 100) + '%'"></div>
                    </div>
                    <span class="text-xs font-medium" :class="strengthTextColor" x-text="strengthLabel"></span>
                </div>
                <ul class="mt-2 text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                    <li :class="password.length >= 8 ? 'text-green-500 dark:text-green-400' : ''">
                        <span x-text="password.length >= 8 ? '✓' : '○'"></span> At least 8 characters
                    </li>
                    <li :class="/[A-Z]/.test(password) ? 'text-green-500 dark:text-green-400' : ''">
                        <span x-text="/[A-Z]/.test(password) ? '✓' : '○'"></span> Uppercase letter
                    </li>
                    <li :class="/[a-z]/.test(password) ? 'text-green-500 dark:text-green-400' : ''">
                        <span x-text="/[a-z]/.test(password) ? '✓' : '○'"></span> Lowercase letter
                    </li>
                    <li :class="/[0-9]/.test(password) ? 'text-green-500 dark:text-green-400' : ''">
                        <span x-text="/[0-9]/.test(password) ? '✓' : '○'"></span> Number
                    </li>
                    <li :class="/[^a-zA-Z0-9]/.test(password) ? 'text-green-500 dark:text-green-400' : ''">
                        <span x-text="/[^a-zA-Z0-9]/.test(password) ? '✓' : '○'"></span> Special character
                    </li>
                </ul>
            </div>

            @error('password')
                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password Confirmation -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm Password</label>
            <input wire:model="password_confirmation" 
                   id="password_confirmation" 
                   type="password" 
                   required
                   class="input">
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" 
                    class="w-full btn btn-primary" 
                    wire:loading.attr="disabled">
                <span wire:loading.remove>Register</span>
                <span wire:loading>Creating account...</span>
            </button>
        </div>

        <!-- Login Link -->
        <div class="text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Already have an account? 
                <a href="{{ route('login') }}" class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors">
                    Sign in here
                </a>
            </p>
        </div>
    </form>
</div>

