<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Method Not Allowed - {{ config('app.name') }}</title>
    @vite(['resources/scripts/main.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <!-- Error Icon -->
            <div class="mx-auto h-24 w-24 text-orange-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                </svg>
            </div>

            <!-- Error Code -->
            <div>
                <h1 class="text-8xl font-bold text-gray-900">405</h1>
                <h2 class="mt-4 text-2xl font-semibold text-gray-700">Method Not Allowed</h2>
            </div>

            <!-- Error Message -->
            <div class="space-y-4">
                <p class="text-gray-600">
                    {{ $message ?? 'The method used for this request is not allowed.' }}
                </p>
                
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-orange-700">
                                This endpoint does not support the HTTP method you used. Please check the API documentation for the correct method.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Method Information -->
            <div class="bg-gray-100 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-900 mb-2">Request Information:</h3>
                <div class="text-sm text-gray-600 space-y-1">
                    <div><strong>URL:</strong> {{ request()->fullUrl() }}</div>
                    <div><strong>Method Used:</strong> <span class="font-mono bg-red-100 text-red-800 px-2 py-1 rounded">{{ request()->method() }}</span></div>
                    @if(isset($allowed_methods))
                        <div><strong>Allowed Methods:</strong> 
                            @foreach(explode(',', $allowed_methods) as $method)
                                <span class="font-mono bg-green-100 text-green-800 px-2 py-1 rounded mr-1">{{ trim($method) }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Common Solutions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">Common Solutions:</h3>
                <ul class="text-sm text-blue-700 space-y-1 text-left">
                    <li>• Check if you're using the correct HTTP method (GET, POST, PUT, DELETE, etc.)</li>
                    <li>• Verify the API documentation for the correct endpoint method</li>
                    <li>• Ensure your form or AJAX request is using the right method</li>
                    <li>• Check if CSRF protection requires a POST method</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                <a href="{{ url()->previous() }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Go Back
                </a>
                
                <a href="{{ url('/') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go Home
                </a>
            </div>

            <!-- API Documentation Link -->
            <div class="pt-4">
                <a href="{{ url('/api/documentation') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                    View API Documentation →
                </a>
            </div>

            <!-- Footer -->
            <div class="pt-8 text-xs text-gray-400">
                Request ID: {{ request()->header('X-Request-ID', Str::uuid()) }}
            </div>
        </div>
    </div>
</body>
</html>