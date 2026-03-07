<template>
  <BasePage>
    <BasePageHeader :title="t('title')" />

    <!-- Analysis -->
    <div class="mb-8">
      <h2 class="text-sm font-semibold text-emerald-600 uppercase tracking-wide mb-4">
        {{ t('analysis') }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <router-link
          v-for="card in analysisCards"
          :key="card.route"
          :to="card.route"
          class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow group"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
              <component :is="card.icon" class="w-5 h-5 text-emerald-600" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-900 group-hover:text-emerald-600 transition-colors">
                {{ card.title }}
              </h3>
              <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ card.description }}</p>
            </div>
          </div>
        </router-link>
      </div>
    </div>

    <!-- Collections & Compliance -->
    <div>
      <h2 class="text-sm font-semibold text-amber-600 uppercase tracking-wide mb-4">
        {{ t('collections_compliance') }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <router-link
          v-for="card in collectionCards"
          :key="card.route"
          :to="card.route"
          class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow group"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
              <component :is="card.icon" class="w-5 h-5 text-amber-600" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-900 group-hover:text-amber-600 transition-colors">
                {{ card.title }}
              </h3>
              <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ card.description }}</p>
            </div>
          </div>
        </router-link>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { computed } from 'vue'
import {
  PresentationChartLineIcon,
  CalculatorIcon,
  DocumentChartBarIcon,
  ReceiptPercentIcon,
  BellAlertIcon,
} from '@heroicons/vue/24/outline'

const locale = document.documentElement.lang || 'mk'

const messages = {
  mk: {
    title: 'Финансии',
    analysis: 'Анализа',
    collections_compliance: 'Наплата и усогласеност',
    bi_dashboard: 'БИ Контролна табла',
    bi_dashboard_desc: 'Финансиски показатели, Altman Z-Score и здравствена оцена на компанијата.',
    budgets: 'Буџетирање',
    budgets_desc: 'Поставете годишен буџет по сметки. Следете реализација наспроти план.',
    custom_reports: 'Прилагодени извештаи',
    custom_reports_desc: 'Креирајте сопствени извештаи со филтри, споредби и групирање по период.',
    interest: 'Затезна камата',
    interest_desc: 'Пресметка на законска затезна камата за задоцнети плаќања.',
    collections: 'Наплата и опомени',
    collections_desc: 'Преглед на задоцнети фактури и испраќање потсетници за наплата.',
  },
  en: {
    title: 'Finance',
    analysis: 'Analysis',
    collections_compliance: 'Collections & Compliance',
    bi_dashboard: 'BI Dashboard',
    bi_dashboard_desc: 'Financial ratios, Altman Z-Score and company health assessment.',
    budgets: 'Budgets',
    budgets_desc: 'Set annual budget by account. Track actual vs. planned spending.',
    custom_reports: 'Custom Reports',
    custom_reports_desc: 'Build custom reports with filters, comparisons and period grouping.',
    interest: 'Late Interest',
    interest_desc: 'Calculate legal default interest on overdue payments.',
    collections: 'Collections & Reminders',
    collections_desc: 'View overdue invoices and send collection reminders.',
  },
}

function t(key) {
  return messages[locale]?.[key] || messages['en']?.[key] || key
}

const analysisCards = computed(() => [
  {
    title: t('bi_dashboard'),
    description: t('bi_dashboard_desc'),
    route: '/admin/bi-dashboard',
    icon: PresentationChartLineIcon,
  },
  {
    title: t('budgets'),
    description: t('budgets_desc'),
    route: '/admin/budgets',
    icon: CalculatorIcon,
  },
  {
    title: t('custom_reports'),
    description: t('custom_reports_desc'),
    route: '/admin/custom-reports',
    icon: DocumentChartBarIcon,
  },
])

const collectionCards = computed(() => [
  {
    title: t('interest'),
    description: t('interest_desc'),
    route: '/admin/interest',
    icon: ReceiptPercentIcon,
  },
  {
    title: t('collections'),
    description: t('collections_desc'),
    route: '/admin/collections',
    icon: BellAlertIcon,
  },
])
</script>

<!-- CLAUDE-CHECKPOINT -->
