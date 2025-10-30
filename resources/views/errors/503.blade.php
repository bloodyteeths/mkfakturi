<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Unavailable - {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/scripts/app.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <!-- Error Icon -->
            <div class="mx-auto h-24 w-24 text-orange-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
            </div>

            <!-- Error Code -->
            <div>
                <h1 class="text-8xl font-bold text-gray-900">503</h1>
                <h2 class="mt-4 text-2xl font-semibold text-gray-700">Service Unavailable</h2>
            </div>

            <!-- Error Message -->
            <div class="space-y-4">
                <p class="text-gray-600">
                    {{ $message ?? 'The service is temporarily unavailable. We are working to restore it as quickly as possible.' }}
                </p>
                
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-orange-700">
                                This is usually temporary and resolves itself quickly. Our team has been automatically notified if this is an unexpected outage.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Info -->
            @if(isset($scheduled_maintenance) && $scheduled_maintenance)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-900 mb-2">Scheduled Maintenance</h3>
                    <div class="text-sm text-blue-700 space-y-1">
                        @if(isset($maintenance_start))
                            <p>Started: {{ Carbon\Carbon::parse($maintenance_start)->format('M j, Y \a\t g:i A T') }}</p>
                        @endif
                        @if(isset($maintenance_end))
                            <p>Expected completion: {{ Carbon\Carbon::parse($maintenance_end)->format('M j, Y \a\t g:i A T') }}</p>
                        @endif
                        @if(isset($maintenance_reason))
                            <p>Reason: {{ $maintenance_reason }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                <button onclick="window.location.reload()" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Check Again
                </button>
                
                @if(isset($status_page_url))
                    <a href="{{ $status_page_url }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Status Page
                    </a>
                @endif
            </div>

            <!-- Auto-Refresh Timer -->
            <div class="bg-gray-100 rounded-lg p-4">
                <div class="flex items-center justify-center space-x-2">
                    <svg class="h-4 w-4 text-gray-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <p class="text-sm text-gray-600">
                        Auto-refreshing in <span id="refresh-timer">30</span> seconds
                    </p>
                </div>
            </div>

            <!-- What can you do -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">While you wait:</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Try refreshing the page in a few minutes</li>
                    <li>• Check our social media for service updates</li>
                    <li>• Save any work you might lose when the service returns</li>
                    <li>• Contact support if this continues for an extended period</li>
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

    <!-- Auto-refresh Script -->
    <script>
        let refreshCounter = 30;
        const timerElement = document.getElementById('refresh-timer');
        
        const updateTimer = () => {
            timerElement.textContent = refreshCounter;
            if (refreshCounter <= 0) {
                window.location.reload();
                return;
            }
            refreshCounter -= 1;
            setTimeout(updateTimer, 1000);
        };
        
        updateTimer();
    </script>
</body>
</html>