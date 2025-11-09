<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Too Many Requests - {{ config('app.name') }}</title>
    @vite(['resources/scripts/main.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <!-- Error Icon -->
            <div class="mx-auto h-24 w-24 text-purple-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>

            <!-- Error Code -->
            <div>
                <h1 class="text-8xl font-bold text-gray-900">429</h1>
                <h2 class="mt-4 text-2xl font-semibold text-gray-700">Too Many Requests</h2>
            </div>

            <!-- Error Message -->
            <div class="space-y-4">
                <p class="text-gray-600">
                    You've made too many requests in a short period. Please slow down and try again in a moment.
                </p>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-purple-700">
                                This limit helps protect our service and ensures good performance for all users. The limit will reset automatically.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rate Limit Info -->
            @if(isset($retry_after))
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-900 mb-2">Rate Limit Information</h3>
                    <div class="text-sm text-blue-700 space-y-1">
                        <p>You can try again in: <strong>{{ $retry_after }} seconds</strong></p>
                        <div class="mt-3">
                            <div class="bg-blue-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-1000" 
                                     id="countdown-bar" style="width: 100%"></div>
                            </div>
                            <p class="text-xs text-blue-600 mt-1">
                                Time remaining: <span id="countdown-text">{{ $retry_after }}s</span>
                            </p>
                        </div>
                    </div>
                </div>

                <script>
                    let retryAfter = {{ $retry_after }};
                    const countdownText = document.getElementById('countdown-text');
                    const countdownBar = document.getElementById('countdown-bar');
                    const totalTime = retryAfter;

                    const updateCountdown = () => {
                        if (retryAfter <= 0) {
                            countdownText.textContent = 'Ready!';
                            countdownBar.style.width = '0%';
                            // Enable retry button if it exists
                            const retryButton = document.getElementById('retry-button');
                            if (retryButton) {
                                retryButton.disabled = false;
                                retryButton.classList.remove('opacity-50', 'cursor-not-allowed');
                                retryButton.textContent = 'Try Again Now';
                            }
                            return;
                        }

                        countdownText.textContent = `${retryAfter}s`;
                        const percentage = (retryAfter / totalTime) * 100;
                        countdownBar.style.width = `${percentage}%`;
                        retryAfter -= 1;
                        setTimeout(updateCountdown, 1000);
                    };

                    updateCountdown();
                </script>
            @endif

            <!-- Action Buttons -->
            <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                <button id="retry-button" onclick="window.location.reload()" 
                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ isset($retry_after) ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ isset($retry_after) ? 'disabled' : '' }}>
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    {{ isset($retry_after) ? 'Please Wait...' : 'Try Again' }}
                </button>
                
                <a href="{{ url('/') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go Home
                </a>
            </div>

            <!-- Tips -->
            <div class="bg-gray-100 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-900 mb-2">Tips to avoid rate limits:</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Wait a moment between requests</li>
                    <li>• Avoid rapidly clicking buttons or refreshing pages</li>
                    <li>• Use batch operations when available</li>
                    <li>• Contact support if you need higher limits</li>
                </ul>
            </div>

            <!-- Footer -->
            <div class="pt-8 text-xs text-gray-400">
                Request ID: {{ request()->header('X-Request-ID', Str::uuid()) }}<br>
                Time: {{ now()->format('Y-m-d H:i:s T') }}
                @if(isset($retry_after))
                    <br>Retry After: {{ $retry_after }} seconds
                @endif
            </div>
        </div>
    </div>
</body>
</html>