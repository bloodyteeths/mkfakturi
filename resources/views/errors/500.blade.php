<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error - {{ config('app.name') }}</title>
    @vite(['resources/scripts/main.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <!-- Error Icon -->
            <div class="mx-auto h-24 w-24 text-red-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>

            <!-- Error Code -->
            <div>
                <h1 class="text-8xl font-bold text-gray-900">500</h1>
                <h2 class="mt-4 text-2xl font-semibold text-gray-700">Internal Server Error</h2>
            </div>

            <!-- Error Message -->
            <div class="space-y-4">
                <p class="text-gray-600">
                    Something went wrong on our end. We've been notified and are working to fix the issue.
                </p>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                Our technical team has been automatically notified. Please try again in a few minutes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                <button onclick="window.location.reload()" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Try Again
                </button>
                
                <a href="{{ url('/') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go Home
                </a>
            </div>

            <!-- Help Section -->
            <div class="bg-gray-100 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-900 mb-2">If this problem persists:</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Check your internet connection</li>
                    <li>• Clear your browser cache and cookies</li>
                    <li>• Try using a different browser</li>
                    <li>• Contact our support team with the error code below</li>
                </ul>
            </div>

            <!-- Debug Information (Development Only) -->
            @if(config('app.debug') && !app()->environment('production'))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-left">
                    <h4 class="text-sm font-medium text-yellow-800 mb-2">Debug Information:</h4>
                    <div class="text-xs text-yellow-700 space-y-1">
                        @if(isset($exception))
                            <div><strong>Exception:</strong> {{ get_class($exception) }}</div>
                            <div><strong>Message:</strong> {{ $exception->getMessage() }}</div>
                            <div><strong>File:</strong> {{ $exception->getFile() }}:{{ $exception->getLine() }}</div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Footer -->
            <div class="pt-8 text-xs text-gray-400">
                Error ID: {{ request()->header('X-Request-ID', Str::uuid()) }}<br>
                Time: {{ now()->format('Y-m-d H:i:s T') }}
            </div>
        </div>
    </div>
</body>
</html>