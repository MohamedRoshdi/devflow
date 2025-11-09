<div class="bg-white shadow-md rounded-lg px-8 py-10">
    <h2 class="text-2xl font-bold text-center text-gray-900 mb-8">Create your account</h2>
    
    <form wire:submit="register" class="space-y-6">
        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
            <input wire:model="name" 
                   id="name" 
                   type="text" 
                   required 
                   autofocus
                   class="input @error('name') border-red-500 @enderror">
            @error('name') 
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email address</label>
            <input wire:model="email" 
                   id="email" 
                   type="email" 
                   required
                   class="input @error('email') border-red-500 @enderror">
            @error('email') 
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <input wire:model="password" 
                   id="password" 
                   type="password" 
                   required
                   class="input @error('password') border-red-500 @enderror">
            @error('password') 
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password Confirmation -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
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
            <p class="text-sm text-gray-600">
                Already have an account? 
                <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">
                    Sign in here
                </a>
            </p>
        </div>
    </form>
</div>

