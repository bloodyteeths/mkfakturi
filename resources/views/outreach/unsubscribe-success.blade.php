<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribed - {{ config('app.name') }}</title>
    @vite(['resources/scripts/main.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo -->
            <div class="text-center">
                <img src="{{ asset('logo/facturino_logo.png') }}" alt="Facturino" class="mx-auto h-16 w-auto">
            </div>

            <!-- Success Message -->
            <div class="bg-white shadow-md rounded-lg p-8">
                <!-- Success Icon -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 text-center mb-4">
                    Successfully Unsubscribed
                </h2>

                <p class="text-gray-600 text-center mb-6">
                    <strong>{{ $email }}</strong> has been removed from our mailing list.
                </p>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                You may still receive important transactional emails about your account, such as password resets or invoice notifications.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <a href="{{ url('/') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Go to Homepage
                    </a>
                </div>
            </div>

            <!-- Resubscribe Option -->
            <div class="text-center">
                <p class="text-sm text-gray-500">
                    Changed your mind?
                    <a href="mailto:support@facturino.mk?subject=Resubscribe%20Request" class="text-indigo-600 hover:text-indigo-500">
                        Contact us to resubscribe
                    </a>
                </p>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-400">
                <p>&copy; {{ date('Y') }} Facturino. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
