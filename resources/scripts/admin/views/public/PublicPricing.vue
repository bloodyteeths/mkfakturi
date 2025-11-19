<template>
  <div class="bg-slate-50 min-h-screen py-20">
    <div class="container mx-auto px-4 md:px-6">
      <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">
          {{ $t('pricing_public.title') }}
        </h1>
        <p class="text-xl text-slate-600">
          {{ $t('pricing_public.subtitle') }}
        </p>
      </div>

      <!-- Pricing Toggle (Monthly/Yearly) - Placeholder for future implementation -->
      <!-- <div class="flex justify-center mb-12">
        <div class="bg-white p-1 rounded-xl border border-slate-200 inline-flex">
          <button class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium shadow-sm">Monthly</button>
          <button class="px-6 py-2 rounded-lg text-slate-600 font-medium hover:bg-slate-50">Yearly <span class="text-xs text-green-600 font-bold ml-1">-20%</span></button>
        </div>
      </div> -->

      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8 max-w-7xl mx-auto">
        <!-- Free Plan -->
        <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col">
          <div class="mb-6">
            <h3 class="text-xl font-bold text-slate-900 mb-2">{{ $t('pricing_public.plans.free.name') }}</h3>
            <p class="text-sm text-slate-500">{{ $t('pricing_public.plans.free.description') }}</p>
          </div>
          <div class="mb-6">
            <span class="text-4xl font-extrabold text-slate-900">€0</span>
            <span class="text-slate-500">{{ $t('pricing_public.per_month') }}</span>
          </div>
          <ul class="space-y-4 mb-8 flex-grow">
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.free.features.clients') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.free.features.invoicing') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.free.features.support') }}
            </li>
          </ul>
          <router-link to="/login" class="block w-full py-3 px-4 bg-slate-100 text-slate-700 font-bold text-center rounded-xl hover:bg-slate-200 transition-colors">
            {{ $t('pricing_public.get_started') }}
          </router-link>
        </div>

        <!-- Starter Plan -->
        <div class="bg-white rounded-2xl p-8 border border-primary-200 shadow-lg ring-2 ring-primary-500 relative flex flex-col transform md:-translate-y-4">
          <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-primary-600 text-white px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
            {{ $t('pricing_public.most_popular') }}
          </div>
          <div class="mb-6">
            <h3 class="text-xl font-bold text-slate-900 mb-2">{{ $t('pricing_public.plans.starter.name') }}</h3>
            <p class="text-sm text-slate-500">{{ $t('pricing_public.plans.starter.description') }}</p>
          </div>
          <div class="mb-6">
            <span class="text-4xl font-extrabold text-slate-900">€12</span>
            <span class="text-slate-500">{{ $t('pricing_public.per_month') }}</span>
          </div>
          <ul class="space-y-4 mb-8 flex-grow">
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.starter.features.unlimited_clients') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.starter.features.estimates_quotes') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.starter.features.recurring_invoices') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.starter.features.priority_support') }}
            </li>
          </ul>
          <router-link to="/login" class="block w-full py-3 px-4 bg-primary-600 text-white font-bold text-center rounded-xl hover:bg-primary-700 transition-colors shadow-md">
            {{ $t('pricing_public.start_free_trial') }}
          </router-link>
        </div>

        <!-- Standard Plan -->
        <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col">
          <div class="mb-6">
            <h3 class="text-xl font-bold text-slate-900 mb-2">{{ $t('pricing_public.plans.standard.name') }}</h3>
            <p class="text-sm text-slate-500">{{ $t('pricing_public.plans.standard.description') }}</p>
          </div>
          <div class="mb-6">
            <span class="text-4xl font-extrabold text-slate-900">€29</span>
            <span class="text-slate-500">{{ $t('pricing_public.per_month') }}</span>
          </div>
          <ul class="space-y-4 mb-8 flex-grow">
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.standard.features.everything_in_starter') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.standard.features.expense_tracking') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.standard.features.financial_reports') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.standard.features.bank_sync') }}
            </li>
          </ul>
          <router-link to="/login" class="block w-full py-3 px-4 bg-slate-100 text-slate-700 font-bold text-center rounded-xl hover:bg-slate-200 transition-colors">
            {{ $t('pricing_public.start_free_trial') }}
          </router-link>
        </div>

        <!-- Business Plan -->
        <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col">
          <div class="mb-6">
            <h3 class="text-xl font-bold text-slate-900 mb-2">{{ $t('pricing_public.plans.business.name') }}</h3>
            <p class="text-sm text-slate-500">{{ $t('pricing_public.plans.business.description') }}</p>
          </div>
          <div class="mb-6">
            <span class="text-4xl font-extrabold text-slate-900">€59</span>
            <span class="text-slate-500">{{ $t('pricing_public.per_month') }}</span>
          </div>
          <ul class="space-y-4 mb-8 flex-grow">
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.business.features.everything_in_standard') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.business.features.multi_currency') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.business.features.team_collaboration') }}
            </li>
            <li class="flex items-start text-sm text-slate-600">
              <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ $t('pricing_public.plans.business.features.custom_fields') }}
            </li>
          </ul>
          <router-link to="/login" class="block w-full py-3 px-4 bg-slate-100 text-slate-700 font-bold text-center rounded-xl hover:bg-slate-200 transition-colors">
            {{ $t('pricing_public.start_free_trial') }}
          </router-link>
        </div>
      </div>

      <div class="mt-20 text-center">
        <h3 class="text-2xl font-bold text-slate-900 mb-4">{{ $t('pricing_public.custom_solution.title') }}</h3>
        <p class="text-slate-600 mb-8">{{ $t('pricing_public.custom_solution.description') }}</p>
        <a href="#" class="text-primary-600 font-bold hover:text-primary-700 hover:underline">{{ $t('pricing_public.custom_solution.button') }} &rarr;</a>
      </div>
    </div>
  </div>
</template>

<script setup>
// No script logic needed for static pricing page yet
</script>
