<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validation Error - {{ config('app.name') }}</title>
    @vite(['resources/scripts/main.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <!-- Error Icon -->
                <div class="mx-auto h-24 w-24 text-amber-600">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>

                <h1 class="mt-4 text-8xl font-bold text-gray-900">422</h1>
                <h2 class="mt-2 text-2xl font-semibold text-gray-700">Validation Error</h2>
                <p class="mt-2 text-lg text-gray-600">
                    The data you submitted contains errors that need to be corrected.
                </p>
            </div>

            <!-- Error Details -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="space-y-6">
                    <!-- Main Error Message -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">What went wrong</h3>
                        <div class="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-amber-700">
                                        {{ $message ?? 'The data you submitted did not pass validation. Please review the errors below and try again.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Validation Errors -->
                    @if(isset($errors) && $errors->any())
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Validation Errors</h3>
                            <div class="mt-2 bg-red-50 border border-red-200 rounded-lg p-4">
                                <ul class="space-y-2">
                                    @foreach($errors->all() as $error)
                                        <li class="flex items-start">
                                            <div class="flex-shrink-0 mt-0.5">
                                                <svg class="h-4 w-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                            <p class="ml-3 text-sm text-red-700">{{ $error }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Helpful Tips -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">How to fix this</h3>
                        <div class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <ul class="space-y-2">
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <p class="ml-3 text-sm text-blue-700">Review each error message above and correct the corresponding field</p>
                                </li>
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <p class="ml-3 text-sm text-blue-700">Make sure all required fields are filled out</p>
                                </li>
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <p class="ml-3 text-sm text-blue-700">Check that email addresses, phone numbers, and dates are in the correct format</p>
                                </li>
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <p class="ml-3 text-sm text-blue-700">Ensure numeric fields contain only numbers and are within acceptable ranges</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                <button onclick="window.history.back()" class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Go Back & Fix
                </button>
                
                <a href="{{ url('/') }}" class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go to Dashboard
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-400">
                Request ID: {{ request()->header('X-Request-ID', Str::uuid()) }}<br>
                Time: {{ now()->format('Y-m-d H:i:s T') }}
            </div>
        </div>
    </div>
</body>
</html>