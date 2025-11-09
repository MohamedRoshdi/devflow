<div class="bg-white shadow-md rounded-lg px-8 py-10">
    <h2 class="text-2xl font-bold text-center text-gray-900 mb-8">Reset Password</h2>
    
    @if($emailSent)
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6">
            <p>We've sent you a password reset link to your email address.</p>
        </div>
    @endif
    
    <form wire:submit="sendResetLink" class="space-y-6">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email address</label>
            <input wire:model="email" 
                   id="email" 
                   type="email" 
                   required 
                   autofocus
                   class="input @error('email') border-red-500 @enderror">
            @error('email') 
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <button type="submit" 
                    class="w-full btn btn-primary" 
                    wire:loading.attr="disabled">
                <span wire:loading.remove>Send Reset Link</span>
                <span wire:loading>Sending...</span>
            </button>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                Back to login
            </a>
        </div>
    </form>
</div>

