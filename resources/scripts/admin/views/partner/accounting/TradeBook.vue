<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.trade_book', 'Трговска книга')">
      <template #actions>
        <div v-if="canExport" class="flex space-x-2">
          <BaseButton variant="primary-outline" @click="exportCsv">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
            </template>
            CSV
          </BaseButton>
          <BaseButton variant="primary" :loading="isExportingPdf" @click="previewPdf">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="EyeIcon" />
            </template>
            PDF
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Form Type Tabs -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="-mb-px flex space-x-6" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors"
          :class="activeTab === tab.id
            ? 'border-primary-500 text-primary-600'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
          @click="switchTab(tab.id)"
        >
          {{ tab.label }}
          <span class="ml-1 text-xs text-gray-400">{{ tab.code }}</span>
        </button>
      </nav>
    </div>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="name"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <div v-if="!selectedCompanyId" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12">
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('partner.accounting.select_company_to_view') }}</p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Filters -->
      <div class="p-6 bg-white rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <BaseInputGroup :label="$t('general.from_date')" required>
            <BaseDatePicker v-model="filters.start_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('general.to_date')" required>
            <BaseDatePicker v-model="filters.end_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <div class="flex flex-col items-end">
            <BaseButton variant="primary" class="w-full" :loading="isLoading" :disabled="!canLoad" @click="loadData">
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
              </template>
              {{ $t('general.load') }}
            </BaseButton>
            <p v-if="dateError" class="mt-1 text-xs text-red-500">{{ dateError }}</p>
          </div>
        </div>

        <!-- МЕТГ: Item filter -->
        <div v-if="activeTab === 'metg'" class="mt-4">
          <BaseInputGroup :label="$t('partner.accounting.filter_by_item', 'Филтрирај по артикл')">
            <BaseMultiselect
              v-model="filters.item_id"
              :options="itemOptions"
              :searchable="true"
              track-by="name"
              label="name"
              value-prop="id"
              :placeholder="$t('partner.accounting.all_items', 'Сите артикли')"
            />
          </BaseInputGroup>
        </div>
      </div>

      <!-- Summary Cards (ЕТ) -->
      <div v-if="activeTab === 'et' && hasSearched && !isLoading && entries.length > 0" class="mb-6 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.nabavna_vrednost', 'Набавна вредност') }}</p>
          <p class="text-lg font-bold text-red-700">{{ formatMoney(summary.total_nabavna) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.prodazhna_vrednost', 'Продажна вредност') }}</p>
          <p class="text-lg font-bold text-green-700">{{ formatMoney(summary.total_prodazhna) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.dneven_promet', 'Вкупен промет') }}</p>
          <p class="text-lg font-bold text-blue-700">{{ formatMoney(summary.total_promet) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.total_entries', 'Вкупно записи') }}</p>
          <p class="text-lg font-bold text-gray-700">{{ summary.count }}</p>
        </div>
      </div>

      <!-- Summary Cards (МЕТГ) -->
      <div v-if="activeTab === 'metg' && hasSearched && !isLoading && entries.length > 0" class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.total_vlez', 'Вкупен влез') }}</p>
          <p class="text-lg font-bold text-green-700">{{ summary.total_vlez }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.total_izlez', 'Вкупен излез') }}</p>
          <p class="text-lg font-bold text-red-700">{{ summary.total_izlez }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.balance', 'Состојба') }}</p>
          <p class="text-lg font-bold text-blue-700">{{ summary.balance }}</p>
        </div>
      </div>

      <!-- Summary Cards (ЕТУ) -->
      <div v-if="activeTab === 'etu' && hasSearched && !isLoading && entries.length > 0" class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.total_with_vat', 'Вкупно со ДДВ') }}</p>
          <p class="text-lg font-bold text-green-700">{{ formatMoney(summary.total_with_vat) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-amber-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.total_vat', 'Вкупен ДДВ') }}</p>
          <p class="text-lg font-bold text-amber-700">{{ formatMoney(summary.total_vat) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.total_collected', 'Вкупно наплатено') }}</p>
          <p class="text-lg font-bold text-blue-700">{{ formatMoney(summary.total_collected) }}</p>
        </div>
      </div>

      <!-- ═══ TABLE: Образец ЕТ ═══ -->
      <div v-if="activeTab === 'et' && entries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('partner.accounting.obrazec_et', 'Образец "ЕТ" — Евиденција во трговијата на мало') }}</h3>
            <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
          </div>
          <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">
            Сл. весник 51/04
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="px-3 py-2 text-center text-xs font-medium uppercase" style="width: 5%">
                  Ред. бр.
                  <span class="block text-[10px] font-normal text-gray-400">1</span>
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.datum_na_knizhenje', 'Датум на книжење') }}
                  <span class="block text-[10px] font-normal text-gray-400">2</span>
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium uppercase" style="width: 25%">
                  {{ $t('partner.accounting.knigovodstven_dokument', 'Книговодствен документ (назив и број)') }}
                  <span class="block text-[10px] font-normal text-gray-400">3</span>
                </th>
                <th class="px-3 py-2 text-center text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.datum_na_dokument', 'Датум на документот') }}
                  <span class="block text-[10px] font-normal text-gray-400">4</span>
                </th>
                <th class="px-3 py-2 text-right text-xs font-medium uppercase" style="width: 15%">
                  {{ $t('partner.accounting.nabavna_vrednost', 'Набавна вредност') }}
                  <span class="block text-[10px] font-normal text-gray-400">5</span>
                </th>
                <th class="px-3 py-2 text-right text-xs font-medium uppercase" style="width: 15%">
                  {{ $t('partner.accounting.prodazhna_vrednost', 'Продажна вредност') }}
                  <span class="block text-[10px] font-normal text-gray-400">6</span>
                </th>
                <th class="px-3 py-2 text-right text-xs font-medium uppercase" style="width: 15%">
                  {{ $t('partner.accounting.dneven_promet', 'Дневен промет') }}
                  <span class="block text-[10px] font-normal text-gray-400">7</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="entry in entries"
                :key="entry.seq"
                class="hover:bg-gray-50"
                :class="{
                  'bg-red-50': entry.doc_type === 'credit_note',
                  'bg-blue-50/30': entry.doc_type === 'bill',
                  'bg-amber-50/30': entry.doc_type === 'expense',
                }"
              >
                <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ entry.seq }}</td>
                <td class="px-3 py-2 text-sm text-gray-900 whitespace-nowrap">{{ entry.date }}</td>
                <td class="px-3 py-2 text-sm">
                  <span class="font-medium" :class="docTypeColor(entry.doc_type)">{{ entry.doc_name }}</span>
                  <span class="text-gray-600 ml-1">{{ entry.doc_number }}</span>
                  <span v-if="entry.doc_type === 'credit_note'" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-700">КН</span>
                  <span v-if="entry.party" class="block text-xs text-gray-400 truncate" :title="entry.party">{{ entry.party }}</span>
                </td>
                <td class="px-3 py-2 text-sm text-gray-600 text-center whitespace-nowrap">{{ entry.doc_date }}</td>
                <td class="px-3 py-2 text-sm text-right font-mono" :class="entry.nabavna < 0 ? 'text-red-600' : 'text-gray-900'">
                  <template v-if="entry.nabavna !== 0">{{ formatMoney(entry.nabavna) }}</template>
                </td>
                <td class="px-3 py-2 text-sm text-right font-mono" :class="entry.prodazhna < 0 ? 'text-red-600' : 'text-gray-900'">
                  <template v-if="entry.prodazhna !== 0">{{ formatMoney(entry.prodazhna) }}</template>
                </td>
                <td class="px-3 py-2 text-sm text-right font-mono font-semibold" :class="entry.promet && entry.promet < 0 ? 'text-red-600' : 'text-blue-700'">
                  <template v-if="entry.promet !== null && entry.promet !== 0">{{ formatMoney(entry.promet) }}</template>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-800 text-white font-semibold">
              <tr>
                <td colspan="4" class="px-3 py-3 text-sm">ВКУПНО ({{ summary.count }} {{ $t('partner.accounting.entries', 'записи') }})</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ formatMoney(summary.total_nabavna) }}</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ formatMoney(summary.total_prodazhna) }}</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ formatMoney(summary.total_promet) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ═══ TABLE: Образец МЕТГ ═══ -->
      <div v-if="activeTab === 'metg' && entries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('partner.accounting.obrazec_metg', 'Образец "МЕТГ" — Материјална евиденција') }}</h3>
            <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
          </div>
          <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700">
            Сл. весник 51/04
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="px-3 py-2 text-center text-xs font-medium uppercase" style="width: 5%">
                  Ред. бр.
                  <span class="block text-[10px] font-normal text-gray-400">1</span>
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.datum_na_knizhenje', 'Датум на книжење') }}
                  <span class="block text-[10px] font-normal text-gray-400">2</span>
                </th>
                <th class="px-3 py-2 text-center text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.doc_number', 'Број') }}
                  <span class="block text-[10px] font-normal text-gray-400">3</span>
                </th>
                <th class="px-3 py-2 text-center text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.datum_na_dokument', 'Датум') }}
                  <span class="block text-[10px] font-normal text-gray-400">4</span>
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium uppercase" style="width: 20%">
                  {{ $t('partner.accounting.doc_name_party', 'Назив (добавувач/купувач)') }}
                  <span class="block text-[10px] font-normal text-gray-400">5</span>
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.item_name', 'Артикл') }}
                </th>
                <th class="px-3 py-2 text-right text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.vlez', 'Влез') }}
                  <span class="block text-[10px] font-normal text-gray-400">6</span>
                </th>
                <th class="px-3 py-2 text-right text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.izlez', 'Излез') }}
                  <span class="block text-[10px] font-normal text-gray-400">7</span>
                </th>
                <th class="px-3 py-2 text-right text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.sostojba', 'Состојба') }}
                  <span class="block text-[10px] font-normal text-gray-400">8</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="entry in entries" :key="entry.seq" class="hover:bg-gray-50">
                <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ entry.seq }}</td>
                <td class="px-3 py-2 text-sm text-gray-900 whitespace-nowrap">{{ entry.date }}</td>
                <td class="px-3 py-2 text-sm text-gray-600 text-center">{{ entry.doc_number }}</td>
                <td class="px-3 py-2 text-sm text-gray-600 text-center whitespace-nowrap">{{ entry.doc_date }}</td>
                <td class="px-3 py-2 text-sm">
                  <span class="font-medium text-gray-900">{{ entry.doc_name }}</span>
                  <span v-if="entry.party" class="block text-xs text-gray-400">{{ entry.party }}</span>
                </td>
                <td class="px-3 py-2 text-sm text-gray-700">{{ entry.item_name }}</td>
                <td class="px-3 py-2 text-sm text-right font-mono text-green-700">
                  <template v-if="entry.vlez > 0">{{ entry.vlez }}</template>
                </td>
                <td class="px-3 py-2 text-sm text-right font-mono text-red-600">
                  <template v-if="entry.izlez > 0">{{ entry.izlez }}</template>
                </td>
                <td class="px-3 py-2 text-sm text-right font-mono font-semibold" :class="entry.sostojba < 0 ? 'text-red-600' : 'text-blue-700'">
                  {{ entry.sostojba }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-800 text-white font-semibold">
              <tr>
                <td colspan="6" class="px-3 py-3 text-sm">ВКУПНО ({{ summary.count }} {{ $t('partner.accounting.entries', 'записи') }})</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ summary.total_vlez }}</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ summary.total_izlez }}</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ summary.balance }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ═══ TABLE: Образец ЕТУ ═══ -->
      <div v-if="activeTab === 'etu' && entries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('partner.accounting.obrazec_etu', 'Образец "ЕТУ" — Трговски услуги') }}</h3>
            <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
          </div>
          <span class="inline-flex items-center rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-700">
            Сл. весник 51/04
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="px-3 py-2 text-center text-xs font-medium uppercase" style="width: 5%">
                  Ред. бр.
                  <span class="block text-[10px] font-normal text-gray-400">1</span>
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.datum_na_knizhenje', 'Датум на книжење') }}
                  <span class="block text-[10px] font-normal text-gray-400">2</span>
                </th>
                <th class="px-3 py-2 text-center text-xs font-medium uppercase" style="width: 8%">
                  {{ $t('partner.accounting.doc_number', 'Број') }}
                  <span class="block text-[10px] font-normal text-gray-400">3</span>
                </th>
                <th class="px-3 py-2 text-center text-xs font-medium uppercase" style="width: 8%">
                  {{ $t('partner.accounting.datum_na_dokument', 'Датум') }}
                  <span class="block text-[10px] font-normal text-gray-400">4</span>
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium uppercase" style="width: 18%">
                  {{ $t('partner.accounting.doc_name_party', 'Назив (клиент, место)') }}
                  <span class="block text-[10px] font-normal text-gray-400">5</span>
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium uppercase" style="width: 14%">
                  {{ $t('partner.accounting.service_name', 'Назив на услуги') }}
                  <span class="block text-[10px] font-normal text-gray-400">6</span>
                </th>
                <th class="px-3 py-2 text-right text-xs font-medium uppercase" style="width: 12%">
                  {{ $t('partner.accounting.amount_with_vat', 'Износ со ДДВ') }}
                  <span class="block text-[10px] font-normal text-gray-400">7</span>
                </th>
                <th class="px-3 py-2 text-right text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.vat_amount', 'Износ ДДВ') }}
                  <span class="block text-[10px] font-normal text-gray-400">8</span>
                </th>
                <th class="px-3 py-2 text-right text-xs font-medium uppercase" style="width: 12%">
                  {{ $t('partner.accounting.collected', 'Наплатени') }}
                  <span class="block text-[10px] font-normal text-gray-400">9</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="entry in entries" :key="entry.seq" class="hover:bg-gray-50">
                <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ entry.seq }}</td>
                <td class="px-3 py-2 text-sm text-gray-900 whitespace-nowrap">{{ entry.date }}</td>
                <td class="px-3 py-2 text-sm text-gray-600 text-center">{{ entry.doc_number }}</td>
                <td class="px-3 py-2 text-sm text-gray-600 text-center whitespace-nowrap">{{ entry.doc_date }}</td>
                <td class="px-3 py-2 text-sm">
                  <span class="font-medium text-gray-900">{{ entry.doc_name }}</span>
                  <span v-if="entry.party" class="block text-xs text-gray-400">{{ entry.party }}</span>
                </td>
                <td class="px-3 py-2 text-sm text-gray-700">{{ entry.service_name }}</td>
                <td class="px-3 py-2 text-sm text-right font-mono text-gray-900">{{ formatMoney(entry.amount_with_vat) }}</td>
                <td class="px-3 py-2 text-sm text-right font-mono text-amber-600">{{ formatMoney(entry.vat_amount) }}</td>
                <td class="px-3 py-2 text-sm text-right font-mono" :class="entry.collected > 0 ? 'text-green-700' : 'text-gray-400'">
                  {{ formatMoney(entry.collected) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-800 text-white font-semibold">
              <tr>
                <td colspan="6" class="px-3 py-3 text-sm">ВКУПНО ({{ summary.count }} {{ $t('partner.accounting.entries', 'записи') }})</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ formatMoney(summary.total_with_vat) }}</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ formatMoney(summary.total_vat) }}</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ formatMoney(summary.total_collected) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="hasSearched && !isLoading && entries.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
        <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.no_trade_entries', 'Нема записи во трговската книга') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.no_trade_entries_desc', 'Нема пронајдено набавки или продажби за избраниот период.') }}</p>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden p-6">
        <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse mb-4">
          <div class="h-4 bg-gray-200 rounded w-8"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-40"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
        </div>
      </div>

      <!-- Initial State -->
      <div v-if="!hasSearched && !isLoading" class="bg-white rounded-lg shadow p-12 text-center">
        <BaseIcon name="BookOpenIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.trade_book_prompt', 'Изберете период за трговската книга') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ currentTabHint }}</p>
      </div>
    </template>

    <PdfPreviewModal
      :show="showPdfPreview"
      :pdf-url="previewPdfUrl"
      :title="$t('partner.accounting.trade_book', 'Трговска книга')"
      @close="closePdfPreview"
      @download="downloadPdf"
    />
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import PdfPreviewModal from './components/PdfPreviewModal.vue'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const activeTab = ref('et')
const selectedCompanyId = ref(null)
const isLoading = ref(false)
const isExportingPdf = ref(false)
const showPdfPreview = ref(false)
const previewPdfUrl = ref(null)
const pdfBlob = ref(null)
const hasSearched = ref(false)
const entries = ref([])
const summary = ref({})
const itemOptions = ref([])

const filters = ref({
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: new Date().toISOString().slice(0, 10),
  item_id: null,
})

const tabs = [
  { id: 'et', label: t('partner.accounting.retail_evidence', 'Евиденција на мало'), code: 'ЕТ' },
  { id: 'metg', label: t('partner.accounting.wholesale_evidence', 'Материјална (големо)'), code: 'МЕТГ' },
  { id: 'etu', label: t('partner.accounting.service_evidence', 'Трговски услуги'), code: 'ЕТУ' },
]

const companies = computed(() => consoleStore.managedCompanies || [])

const dateError = computed(() => {
  if (!filters.value.start_date || !filters.value.end_date) {
    return t('partner.accounting.dates_required', 'Изберете почетен и краен датум')
  }
  if (filters.value.start_date > filters.value.end_date) {
    return t('partner.accounting.date_order_error', 'Почетниот датум мора да биде пред крајниот')
  }
  return null
})

const canLoad = computed(() => !dateError.value && selectedCompanyId.value)
const canExport = computed(() => hasSearched.value && !isLoading.value && entries.value.length > 0)

const currentTabHint = computed(() => {
  const hints = {
    et: t('partner.accounting.trade_book_hint', 'Хронолошки регистар на набавка и продажба — Образец ЕТ (Сл. весник 51/04)'),
    metg: t('partner.accounting.metg_hint', 'Материјална евиденција по артикли — Образец МЕТГ (Сл. весник 51/04)'),
    etu: t('partner.accounting.etu_hint', 'Евиденција за извршени трговски услуги — Образец ЕТУ (Сл. весник 51/04)'),
  }
  return hints[activeTab.value] || ''
})

// API endpoint map per tab
const apiEndpoints = {
  et: { data: 'trade-book', export: 'trade-book/export' },
  metg: { data: 'metg', export: 'metg/export' },
  etu: { data: 'etu', export: 'etu/export' },
}

onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
  }
})

function switchTab(tabId) {
  activeTab.value = tabId
  entries.value = []
  summary.value = {}
  hasSearched.value = false
}

function onCompanyChange() {
  entries.value = []
  summary.value = {}
  hasSearched.value = false
  if (selectedCompanyId.value) {
    loadItemOptions()
  }
}

async function loadItemOptions() {
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/items`, {
      params: { limit: 500 },
    })
    itemOptions.value = (response.data?.data || response.data || []).map(item => ({
      id: item.id,
      name: item.name,
    }))
  } catch {
    itemOptions.value = []
  }
}

async function loadData() {
  if (!canLoad.value) return
  isLoading.value = true
  hasSearched.value = true
  entries.value = []

  try {
    const endpoint = apiEndpoints[activeTab.value]?.data || 'trade-book'
    const params = {
      from_date: filters.value.start_date,
      to_date: filters.value.end_date,
    }
    if (activeTab.value === 'metg' && filters.value.item_id) {
      params.item_id = filters.value.item_id
    }

    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/${endpoint}`, { params })
    const data = response.data?.data || response.data
    entries.value = data.entries || []
    summary.value = data.summary || {}
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('partner.accounting.trade_book_load_error', 'Грешка при вчитување'),
    })
  } finally {
    isLoading.value = false
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const value = Math.abs(amount) / 100
  const sign = amount < 0 ? '-' : ''
  return sign + new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' ден.'
}

function docTypeColor(type) {
  switch (type) {
    case 'invoice': return 'text-green-700'
    case 'credit_note': return 'text-red-600'
    case 'bill': return 'text-blue-600'
    case 'expense': return 'text-amber-600'
    default: return 'text-gray-700'
  }
}

function exportCsv() {
  if (!entries.value.length) return

  let headers, rows
  if (activeTab.value === 'et') {
    headers = ['Ред.бр.', 'Датум на книжење', 'Книговодствен документ', 'Број', 'Датум на документот', 'Контрагент', 'Набавна вредност', 'Продажна вредност', 'Дневен промет']
    rows = entries.value.map(e => [
      e.seq, e.date, e.doc_name, e.doc_number, e.doc_date, e.party || '',
      (e.nabavna / 100).toFixed(2), (e.prodazhna / 100).toFixed(2),
      e.promet !== null ? (e.promet / 100).toFixed(2) : '',
    ])
  } else if (activeTab.value === 'metg') {
    headers = ['Ред.бр.', 'Датум', 'Број', 'Датум док.', 'Назив', 'Контрагент', 'Артикл', 'Влез', 'Излез', 'Состојба']
    rows = entries.value.map(e => [
      e.seq, e.date, e.doc_number, e.doc_date, e.doc_name, e.party || '',
      e.item_name || '', e.vlez || '', e.izlez || '', e.sostojba || '',
    ])
  } else {
    headers = ['Ред.бр.', 'Датум', 'Број', 'Датум док.', 'Назив', 'Контрагент', 'Услуга', 'Износ со ДДВ', 'Износ ДДВ', 'Наплатено']
    rows = entries.value.map(e => [
      e.seq, e.date, e.doc_number, e.doc_date, e.doc_name, e.party || '',
      e.service_name || '', ((e.amount_with_vat || 0) / 100).toFixed(2),
      ((e.vat_amount || 0) / 100).toFixed(2), ((e.collected || 0) / 100).toFixed(2),
    ])
  }

  const csvContent = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `${activeTab.value}_${filters.value.start_date}_${filters.value.end_date}.csv`
  link.click()
  URL.revokeObjectURL(link.href)
}

async function previewPdf() {
  if (!selectedCompanyId.value) return
  isExportingPdf.value = true
  try {
    const endpoint = apiEndpoints[activeTab.value]?.export || 'trade-book/export'
    const params = {
      from_date: filters.value.start_date,
      to_date: filters.value.end_date,
    }
    if (activeTab.value === 'metg' && filters.value.item_id) {
      params.item_id = filters.value.item_id
    }

    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/${endpoint}`, {
      params,
      responseType: 'blob',
    })
    if (response.data.type && !response.data.type.includes('pdf')) {
      const text = await response.data.text()
      try {
        const err = JSON.parse(text)
        throw new Error(err.message || 'Server returned non-PDF response')
      } catch (parseErr) {
        if (parseErr.message !== 'Server returned non-PDF response') {
          throw new Error('Unexpected server response')
        }
        throw parseErr
      }
    }
    pdfBlob.value = new Blob([response.data], { type: 'application/pdf' })
    previewPdfUrl.value = window.URL.createObjectURL(pdfBlob.value)
    showPdfPreview.value = true
  } catch (error) {
    let message = t('partner.accounting.pdf_export_error', 'Грешка при генерирање на PDF')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const parsed = JSON.parse(text)
        message = parsed.message || message
      } catch (_) { /* ignore */ }
    } else if (error.message) {
      message = error.message
    }
    notificationStore.showNotification({ type: 'error', message })
  } finally {
    isExportingPdf.value = false
  }
}

function downloadPdf() {
  if (!pdfBlob.value) return
  const url = window.URL.createObjectURL(pdfBlob.value)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `${activeTab.value}_${filters.value.start_date}_${filters.value.end_date}.pdf`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

function closePdfPreview() {
  showPdfPreview.value = false
  if (previewPdfUrl.value) {
    window.URL.revokeObjectURL(previewPdfUrl.value)
    previewPdfUrl.value = null
  }
  pdfBlob.value = null
}
</script>

<!-- CLAUDE-CHECKPOINT -->
