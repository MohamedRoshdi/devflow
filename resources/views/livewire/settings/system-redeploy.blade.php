<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                <svg class="w-7 h-7 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                System Redeploy
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Pull latest changes and redeploy DevFlow Pro</p>
        </div>
    </div>

    <!-- Warning Notice -->
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <svg class="w-5 h-5 text-yellow-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div>
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Important</h3>
                <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                    This will pull the latest code from Git, install dependencies, build assets, run migrations, and restart services. 
                    The system may be briefly unavailable during deployment.
                </p>
            </div>
        </div>
    </div>

    <!-- Redeploy Button -->
    <div class="mb-6">
        <button 
            wire:click="redeploy" 
            wire:loading.attr="disabled"
            @disabled($isDeploying)
            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
            
            <span wire:loading.remove wire:target="redeploy">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Redeploy System
            </span>
            
            <span wire:loading wire:target="redeploy">
                <svg class="animate-spin w-5 h-5 mr-2 inline" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Deploying...
            </span>
        </button>
    </div>

    <!-- Deployment Output -->
    @if($deploymentOutput)
        <div class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Deployment Log</h3>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-sm text-green-400 font-mono whitespace-pre-wrap">{{ $deploymentOutput }}</pre>
            </div>
        </div>
    @endif

    <!-- Status Messages -->
    @if($deploymentStatus === 'success')
        <div class="mt-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4">
            <div class="flex">
                <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-sm text-green-700 dark:text-green-300 font-medium">Deployment completed successfully!</p>
            </div>
        </div>
    @elseif($deploymentStatus === 'failed')
        <div class="mt-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-sm text-red-700 dark:text-red-300 font-medium">Deployment failed. Check the log above for details.</p>
            </div>
        </div>
    @endif

    <!-- Process Description -->
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">What Happens During Redeploy?</h3>
        <ol class="space-y-3 text-gray-600 dark:text-gray-400">
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-sm font-bold mr-3">1</span>
                <span><strong>Git Pull:</strong> Fetches latest code from the master branch</span>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-sm font-bold mr-3">2</span>
                <span><strong>Composer Install:</strong> Updates PHP dependencies</span>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-sm font-bold mr-3">3</span>
                <span><strong>NPM Install & Build:</strong> Updates and compiles frontend assets</span>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-sm font-bold mr-3">4</span>
                <span><strong>Database Migrations:</strong> Runs any pending migrations</span>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-sm font-bold mr-3">5</span>
                <span><strong>Cache Rebuild:</strong> Clears and rebuilds all caches</span>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-sm font-bold mr-3">6</span>
                <span><strong>Queue Restart:</strong> Restarts background queue workers</span>
            </li>
        </ol>
    </div>
</div>
