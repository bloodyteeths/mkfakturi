<template>
  <BasePage>
    <BasePageHeader :title="t('title')" />

    <!-- Documents & Orders -->
    <div class="mb-8">
      <h2 class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-4">
        {{ t('documents_orders') }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <router-link
          v-for="card in documentCards"
          :key="card.route"
          :to="card.route"
          class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow group"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
              <component :is="card.icon" class="w-5 h-5 text-blue-600" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                {{ card.title }}
              </h3>
              <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ card.description }}</p>
            </div>
          </div>
        </router-link>
      </div>
    </div>

    <!-- Management -->
    <div>
      <h2 class="text-sm font-semibold text-purple-600 uppercase tracking-wide mb-4">
        {{ t('management') }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <router-link
          v-for="card in managementCards"
          :key="card.route"
          :to="card.route"
          class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow group"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition-colors">
              <component :is="card.icon" class="w-5 h-5 text-purple-600" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">
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
  ArrowsRightLeftIcon,
  ShoppingCartIcon,
  BanknotesIcon,
  TruckIcon,
  TagIcon,
  CubeIcon,
  FolderOpenIcon,
} from '@heroicons/vue/24/outline'

const { locale } = useI18n({ useScope: 'global' })

const messages = {
  mk: {
    title: 'Операции',
    documents_orders: 'Документи и налози',
    management: 'Управување',
    compensations: 'Компензации',
    compensations_desc: 'Компензации меѓу ваши побарувања и обврски. Намалете банкарски трансакции со меѓусебно порамнување.',
    purchase_orders: 'Нарачки за набавка',
    purchase_orders_desc: 'Креирајте нарачки за набавка, следете ги до прием на стока и автоматски генерирајте фактури.',
    payment_orders: 'Налози за плаќање',
    payment_orders_desc: 'Подгответе налози за плаќање за банка. Групирајте повеќе фактури во еден налог.',
    travel_orders: 'Патни налози',
    travel_orders_desc: 'Патни налози со дневници, превоз и сместување. Автоматска пресметка на трошоци.',
    cost_centers: 'Трошковни центри',
    cost_centers_desc: 'Распоредете приходи и трошоци по центри на трошоци (оддели, проекти, локации).',
    stock: 'Залиха',
    stock_desc: 'Магацинско работење: прием, издавање, попис и евиденција на залихи.',
    projects: 'Проекти',
    projects_desc: 'Следете приходи и трошоци по проект. Поврзете фактури и трошоци со проекти.',
  },
  en: {
    title: 'Operations',
    documents_orders: 'Documents & Orders',
    management: 'Management',
    compensations: 'Compensations',
    compensations_desc: 'Offset receivables against payables. Reduce bank transactions with mutual settlement.',
    purchase_orders: 'Purchase Orders',
    purchase_orders_desc: 'Create purchase orders, track through goods receipt, and auto-generate bills.',
    payment_orders: 'Payment Orders',
    payment_orders_desc: 'Prepare bank payment orders. Group multiple bills into a single order.',
    travel_orders: 'Travel Orders',
    travel_orders_desc: 'Travel orders with per diem, transport and accommodation. Auto-calculate expenses.',
    cost_centers: 'Cost Centers',
    cost_centers_desc: 'Allocate revenue and expenses by cost centers (departments, projects, locations).',
    stock: 'Stock Inventory',
    stock_desc: 'Warehouse operations: receiving, issuing, inventory count and stock tracking.',
    projects: 'Projects',
    projects_desc: 'Track revenue and expenses per project. Link invoices and expenses to projects.',
  },
  tr: {
    title: 'Operasyonlar',
    documents_orders: 'Belgeler ve Siparisler',
    management: 'Yonetim',
    compensations: 'Mahsuplasmalar',
    compensations_desc: 'Alacaklar ile borclari mahsuplastirin. Karsilikli hesaplasma ile banka islemlerini azaltin.',
    purchase_orders: 'Satin Alma Siparisleri',
    purchase_orders_desc: 'Satin alma siparisleri olusturun, mal kabulune kadar takip edin ve otomatik fatura olusturun.',
    payment_orders: 'Odeme Emirleri',
    payment_orders_desc: 'Banka odeme emirleri hazirlayin. Birden fazla faturayi tek emirde gruplayin.',
    travel_orders: 'Seyahat Emirleri',
    travel_orders_desc: 'Harcirahlari, ulasim ve konaklama ile seyahat emirleri. Otomatik masraf hesaplama.',
    cost_centers: 'Maliyet Merkezleri',
    cost_centers_desc: 'Gelir ve giderleri maliyet merkezlerine (departmanlar, projeler, lokasyonlar) dagatin.',
    stock: 'Stok Envanteri',
    stock_desc: 'Depo islemleri: mal kabul, cikis, sayim ve stok takibi.',
    projects: 'Projeler',
    projects_desc: 'Proje bazinda gelir ve giderleri takip edin. Fatura ve giderleri projelere baglain.',
  },
  sq: {
    title: 'Operacionet',
    documents_orders: 'Dokumentet dhe Porositë',
    management: 'Menaxhimi',
    compensations: 'Kompensime',
    compensations_desc: 'Kompensoni kërkesat me detyrimet. Ulni transaksionet bankare me pajtim të ndërsjelltë.',
    purchase_orders: 'Porositë e Blerjes',
    purchase_orders_desc: 'Krijoni porosi blerje, ndiqni deri në pranimin e mallit dhe gjeneroni fatura automatikisht.',
    payment_orders: 'Urdhërat e Pagesës',
    payment_orders_desc: 'Përgatitni urdhëra pagese bankare. Gruponi disa fatura në një urdhër të vetëm.',
    travel_orders: 'Urdhërat e Udhëtimit',
    travel_orders_desc: 'Urdhëra udhëtimi me dieta, transport dhe akomodim. Llogaritje automatike e shpenzimeve.',
    cost_centers: 'Qendrat e Kostos',
    cost_centers_desc: 'Shpërndani të ardhurat dhe shpenzimet sipas qendrave të kostos (departamente, projekte, lokacione).',
    stock: 'Inventari i Stokut',
    stock_desc: 'Operacione magazine: pranim, lëshim, inventar dhe gjurmim i stokut.',
    projects: 'Projektet',
    projects_desc: 'Ndiqni të ardhurat dhe shpenzimet për projekt. Lidhni faturat dhe shpenzimet me projektet.',
  },
}

function t(key) {
  return messages[locale.value]?.[key] || messages['en']?.[key] || key
}

const documentCards = computed(() => [
  {
    title: t('compensations'),
    description: t('compensations_desc'),
    route: '/admin/compensations',
    icon: ArrowsRightLeftIcon,
  },
  {
    title: t('purchase_orders'),
    description: t('purchase_orders_desc'),
    route: '/admin/purchase-orders',
    icon: ShoppingCartIcon,
  },
  {
    title: t('payment_orders'),
    description: t('payment_orders_desc'),
    route: '/admin/payment-orders',
    icon: BanknotesIcon,
  },
  {
    title: t('travel_orders'),
    description: t('travel_orders_desc'),
    route: '/admin/travel-orders',
    icon: TruckIcon,
  },
])

const managementCards = computed(() => [
  {
    title: t('cost_centers'),
    description: t('cost_centers_desc'),
    route: '/admin/cost-centers',
    icon: TagIcon,
  },
  {
    title: t('stock'),
    description: t('stock_desc'),
    route: '/admin/stock',
    icon: CubeIcon,
  },
  {
    title: t('projects'),
    description: t('projects_desc'),
    route: '/admin/projects',
    icon: FolderOpenIcon,
  },
])
</script>

<!-- CLAUDE-CHECKPOINT -->
