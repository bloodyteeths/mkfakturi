<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business Logic Error - {{ config('app.name') }}</title>
    @vite(['resources/scripts/main.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <!-- Error Icon -->
                <div class="mx-auto h-24 w-24 text-orange-600">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h1 class="mt-4 text-3xl font-bold text-gray-900">Business Rule Violation</h1>
                <p class="mt-2 text-lg text-gray-600">
                    This action violates a business rule or logical constraint.
                </p>
            </div>

            <!-- Error Details -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="space-y-6">
                    <!-- Error Message -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">What happened</h3>
                        <div class="mt-2 bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-orange-700">{{ $user_message ?? $exception->getMessage() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Code and Type -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Error Details</h3>
                        <div class="mt-2 bg-gray-50 rounded-lg p-4">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Error Code</dt>
                                    <dd class="text-sm text-gray-900 font-mono">{{ $error_code ?? 'BUSINESS_LOGIC_ERROR' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Error Type</dt>
                                    <dd class="text-sm text-gray-900">Business Logic</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Timestamp</dt>
                                    <dd class="text-sm text-gray-900">{{ now()->format('Y-m-d H:i:s T') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Request ID</dt>
                                    <dd class="text-sm text-gray-900 font-mono">{{ request()->header('X-Request-ID', Str::uuid()) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Context Information -->
                    @if(isset($context) && !empty($context))
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Additional Context</h3>
                            <div class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <dl class="space-y-2">
                                    @foreach($context as $key => $value)
                                        <div class="flex justify-between">
                                            <dt class="text-sm font-medium text-blue-900">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                                            <dd class="text-sm text-blue-700">
                                                @if(is_array($value))
                                                    {{ json_encode($value) }}
                                                @elseif(is_bool($value))
                                                    {{ $value ? 'Yes' : 'No' }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        </div>
                    @endif

                    <!-- Common Solutions -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">How to resolve this</h3>
                        <div class="mt-2 bg-green-50 border border-green-200 rounded-lg p-4">
                            <ul class="space-y-2">
                                @switch($error_code ?? 'BUSINESS_LOGIC_ERROR')
                                    @case('INVOICE_GENERATION_ERROR')
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Verify that all required invoice fields are properly filled</p>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Check that invoice items have valid prices and quantities</p>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Ensure customer information is complete and valid</p>
                                        </li>
                                        @break

                                    @case('PAYMENT_PROCESSING_ERROR')
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Verify payment method details are correct</p>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Check that the payment amount is within acceptable limits</p>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Try using a different payment method</p>
                                        </li>
                                        @break

                                    @case('INSUFFICIENT_PERMISSIONS')
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Contact your administrator to request appropriate permissions</p>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Verify you're logged in with the correct account</p>
                                        </li>
                                        @break

                                    @case('RESOURCE_LIMIT_EXCEEDED')
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Consider upgrading your plan for higher limits</p>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Remove some existing items to free up space</p>
                                        </li>
                                        @break

                                    @default
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Review the error message above for specific guidance</p>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Double-check your input data for accuracy</p>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <p class="ml-3 text-sm text-green-700">Try the operation again after making necessary corrections</p>
                                        </li>
                                @endswitch
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

            <!-- Help Section -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Need Help?</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>If you continue to experience this error:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Check our <a href="{{ route('help.business-rules') }}" class="underline">business rules documentation</a></li>
                                <li>Contact support with the error code and request ID above</li>
                                <li>Review your account settings and permissions</li>
                                <li>Verify that your data meets all system requirements</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-400">
                <div>Request ID: {{ request()->header('X-Request-ID', Str::uuid()) }}</div>
                <div>Error Code: {{ $error_code ?? 'BUSINESS_LOGIC_ERROR' }}</div>
                <div>Time: {{ now()->format('Y-m-d H:i:s T') }}</div>
            </div>
        </div>
    </div>
</body>
</html>