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
import { useI18n } from 'vue-i18n'
import {
  PresentationChartLineIcon,
  CalculatorIcon,
  DocumentChartBarIcon,
  ReceiptPercentIcon,
  BellAlertIcon,
} from '@heroicons/vue/24/outline'

const { locale } = useI18n({ useScope: 'global' })

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
  tr: {
    title: 'Finans',
    analysis: 'Analiz',
    collections_compliance: 'Tahsilat ve Uyumluluk',
    bi_dashboard: 'BI Kontrol Paneli',
    bi_dashboard_desc: 'Finansal oranlar, Altman Z-Skoru ve sirket saglik degerlendirmesi.',
    budgets: 'Butceler',
    budgets_desc: 'Hesaplara gore yillik butce belirleyin. Gerceklesen ile planlanan harcamayi takip edin.',
    custom_reports: 'Ozel Raporlar',
    custom_reports_desc: 'Filtreler, karsilastirmalar ve donem gruplamasi ile ozel raporlar olusturun.',
    interest: 'Gecikme Faizi',
    interest_desc: 'Geciken odemeler icin yasal temerrut faizi hesaplayin.',
    collections: 'Tahsilat ve Hatirlatmalar',
    collections_desc: 'Vadesi gecmis faturalari goruntuleyin ve tahsilat hatirlatmalari gonderin.',
  },
  sq: {
    title: 'Financa',
    analysis: 'Analiza',
    collections_compliance: 'Arkëtimi dhe Pajtueshmëria',
    bi_dashboard: 'Paneli BI',
    bi_dashboard_desc: 'Raportet financiare, Altman Z-Score dhe vlerësimi i shëndetit të kompanisë.',
    budgets: 'Buxhetet',
    budgets_desc: 'Vendosni buxhetin vjetor sipas llogarive. Ndiqni shpenzimet aktuale kundrejt planit.',
    custom_reports: 'Raportet e Personalizuara',
    custom_reports_desc: 'Ndërtoni raporte me filtra, krahasime dhe grupim sipas periudhës.',
    interest: 'Interesi i Vonuar',
    interest_desc: 'Llogaritni interesin ligjor për pagesat e vonuara.',
    collections: 'Arkëtimi dhe Kujtesave',
    collections_desc: 'Shikoni faturat e vonuara dhe dërgoni kujtime arkëtimi.',
  },
}

function t(key) {
  return messages[locale.value]?.[key] || messages['en']?.[key] || key
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
