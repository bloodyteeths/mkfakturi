<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe - {{ config('app.name') }}</title>
    @vite(['resources/scripts/main.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo -->
            <div class="text-center">
                <img src="{{ asset('logo/facturino_logo.png') }}" alt="Facturino" class="mx-auto h-16 w-auto">
            </div>

            <!-- Unsubscribe Form -->
            <div class="bg-white shadow-md rounded-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">
                    Unsubscribe from Emails
                </h2>

                <p class="text-gray-600 text-center mb-6">
                    Are you sure you want to unsubscribe <strong>{{ $email }}</strong> from our email list?
                </p>

                <p class="text-sm text-gray-500 text-center mb-6">
                    You will no longer receive marketing emails from Facturino.
                    You may still receive important transactional emails about your account.
                </p>

                <form action="{{ route('outreach.unsubscribe.process') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="space-y-4">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Yes, Unsubscribe Me
                        </button>

                        <a href="{{ url('/') }}" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-400">
                <p>&copy; {{ date('Y') }} Facturino. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
