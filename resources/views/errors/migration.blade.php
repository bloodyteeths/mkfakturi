<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Migration Error - {{ config('app.name') }}</title>
    @vite(['resources/scripts/main.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <!-- Error Icon -->
                <div class="mx-auto h-24 w-24 text-red-600">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h1 class="mt-4 text-3xl font-bold text-gray-900">Migration Process Error</h1>
                <p class="mt-2 text-lg text-gray-600">
                    An error occurred during the {{ $step ?? 'migration' }} step
                </p>
            </div>

            <!-- Error Details -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="space-y-6">
                    <!-- Error Message -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">What Happened</h3>
                        <div class="mt-2 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">{{ $exception->getMessage() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Migration Progress -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Migration Progress</h3>
                        <div class="mt-2">
                            <div class="bg-gray-100 rounded-full h-2">
                                @php
                                    $steps = ['upload', 'parsing', 'mapping', 'validation', 'transformation', 'commit'];
                                    $currentIndex = array_search($step, $steps);
                                    $progress = $currentIndex !== false ? (($currentIndex + 1) / count($steps)) * 100 : 0;
                                @endphp
                                <div class="bg-red-600 h-2 rounded-full" style="width: {{ $progress }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600 mt-1">
                                <span>Upload</span>
                                <span>Processing</span>
                                <span>Validation</span>
                                <span>Complete</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">
                                Error occurred during: <strong class="text-red-600">{{ ucfirst($step ?? 'unknown') }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Recovery Suggestions -->
                    @if(isset($suggestions) && !empty($suggestions))
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">How to Fix This</h3>
                            <div class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <ul class="space-y-2">
                                    @foreach($suggestions as $suggestion)
                                        <li class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                            <p class="ml-3 text-sm text-blue-700">{{ $suggestion }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Import Information -->
                    @if(isset($import_job_id))
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Import Details</h3>
                            <div class="mt-2 bg-gray-50 rounded-lg p-4">
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Import Job ID</dt>
                                        <dd class="text-sm text-gray-900 font-mono">{{ $import_job_id }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Error Time</dt>
                                        <dd class="text-sm text-gray-900">{{ now()->format('Y-m-d H:i:s T') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Step Failed</dt>
                                        <dd class="text-sm text-gray-900">{{ ucfirst($step ?? 'Unknown') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Error Type</dt>
                                        <dd class="text-sm text-gray-900">{{ $exception->getErrorCode() }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                @if(isset($retry_url))
                    <a href="{{ $retry_url }}" class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Retry Migration
                    </a>
                @endif
                
                <a href="{{ route('migration.wizard') }}" class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                    </svg>
                    Start New Migration
                </a>
                
                <a href="{{ url('/') }}" class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go to Dashboard
                </a>
            </div>

            <!-- Help Section -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Need Help?</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>If you continue to experience issues with your migration:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Check our <a href="{{ route('help.migration') }}" class="underline">migration troubleshooting guide</a></li>
                                <li>Contact support with the import job ID above</li>
                                <li>Try exporting your data in a different format</li>
                                <li>Ensure your file meets our <a href="{{ route('help.requirements') }}" class="underline">format requirements</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-400">
                <div>Request ID: {{ request()->header('X-Request-ID', Str::uuid()) }}</div>
                @if(isset($import_job_id))
                    <div>Import Job: {{ $import_job_id }}</div>
                @endif
                <div>Time: {{ now()->format('Y-m-d H:i:s T') }}</div>
            </div>
        </div>
    </div>
</body>
</html>