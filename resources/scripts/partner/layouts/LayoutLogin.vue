<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">
          Partner Portal
        </h1>
        <p class="mt-2 text-sm text-gray-600">
          Портал за сметководствени партнери
        </p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <router-view />
      </div>
    </div>

    <!-- Footer -->
    <div class="mt-8 text-center">
      <p class="text-sm text-gray-500">
        © 2025 Facturino MK. Сите права задржани.
      </p>
    </div>
  </div>
</template>

<script setup>
// Login layout for partner authentication pages
</script>

