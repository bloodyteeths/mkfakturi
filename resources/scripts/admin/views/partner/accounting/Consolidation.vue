<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton
          v-if="activeTab === 'groups'"
          variant="primary"
          @click="openCreateModal"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PlusIcon" />
          </template>
          {{ t('create_group') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium"
          :class="[
            activeTab === tab.key
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
          ]"
          @click="activeTab = tab.key"
        >
          {{ t(tab.key) }}
        </button>
      </nav>
    </div>

    <!-- Groups Tab -->
    <div v-if="activeTab === 'groups'">
      <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="i in 3" :key="i" class="rounded-lg border border-gray-200 p-5 animate-pulse">
            <div class="h-5 bg-gray-200 rounded w-2/3 mb-3"></div>
            <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
            <div class="flex gap-2">
              <div class="h-6 bg-gray-200 rounded-full w-16"></div>
              <div class="h-6 bg-gray-200 rounded-full w-16"></div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="groups.length === 0" class="text-center py-12">
        <div class="text-gray-400 mb-2">
          <BaseIcon name="FolderOpenIcon" class="h-12 w-12 mx-auto" />
        </div>
        <h3 class="text-lg font-medium text-gray-900">{{ t('no_groups') }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ t('no_groups_description') }}</p>
        <BaseButton variant="primary" class="mt-4" @click="openCreateModal">
          {{ t('create_group') }}
        </BaseButton>
      </div>

      <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="group in groups"
          :key="group.id"
          class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
          :class="{ 'ring-2 ring-primary-300': selectedGroupId === group.id }"
          @click="selectGroup(group)"
        >
          <div class="flex items-start justify-between">
            <div>
              <h3 class="text-base font-semibold text-gray-900">{{ group.name }}</h3>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('parent_company') }}: {{ group.parent_company?.name || '-' }}
              </p>
            </div>
            <div class="flex gap-1">
              <button
                class="p-1 text-gray-400 hover:text-primary-600"
                @click.stop="openEditModal(group)"
              >
                <BaseIcon name="PencilIcon" class="h-4 w-4" />
              </button>
              <button
                class="p-1 text-gray-400 hover:text-red-600"
                @click.stop="confirmDelete(group)"
              >
                <BaseIcon name="TrashIcon" class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div class="mt-4 flex items-center gap-4 text-sm text-gray-600">
            <div>
              <span class="font-medium">{{ group.members_count }}</span>
              {{ t('members') }}
            </div>
            <div>
              <span class="font-medium">{{ group.currency_code }}</span>
              {{ t('currency') }}
            </div>
          </div>

          <div v-if="group.members && group.members.length > 0" class="mt-3">
            <div class="flex flex-wrap gap-1">
              <span
                v-for="member in group.members.slice(0, 5)"
                :key="member.id"
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="member.is_parent
                  ? 'bg-primary-100 text-primary-800'
                  : 'bg-gray-100 text-gray-700'"
              >
                {{ member.company_name }}
                <span v-if="member.ownership_pct < 100" class="ml-1 text-gray-500">
                  ({{ member.ownership_pct }}%)
                </span>
              </span>
              <span
                v-if="group.members.length > 5"
                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-500"
              >
                +{{ group.members.length - 5 }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reports Tab -->
    <div v-if="activeTab === 'reports'">
      <!-- Group Selector -->
      <div v-if="!selectedGroupId" class="text-center py-12 text-gray-500">
        {{ t('select_group') }}
      </div>

      <div v-else>
        <!-- Selected Group Header -->
        <div class="mb-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <select
              v-model="selectedGroupId"
              class="rounded-md border-gray-300 text-sm"
              @change="onGroupChange"
            >
              <option v-for="g in groups" :key="g.id" :value="g.id">
                {{ g.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Report Sub-tabs -->
        <div class="mb-4 border-b border-gray-200">
          <nav class="-mb-px flex space-x-6">
            <button
              v-for="st in reportTabs"
              :key="st.key"
              class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium"
              :class="[
                activeReportTab === st.key
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
              @click="activeReportTab = st.key"
            >
              {{ t(st.label) }}
            </button>
          </nav>
        </div>

        <!-- Date Range Picker -->
        <div class="mb-6 flex items-end gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
          <div v-if="activeReportTab !== 'balance_sheet'">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('start_date') }}</label>
            <input
              v-model="reportFilters.start_date"
              type="date"
              class="rounded-md border-gray-300 text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
              {{ activeReportTab === 'balance_sheet' ? t('as_of_date') : t('end_date') }}
            </label>
            <input
              v-model="reportFilters.end_date"
              type="date"
              class="rounded-md border-gray-300 text-sm"
            />
          </div>
          <BaseButton
            variant="primary"
            size="sm"
            :loading="isLoadingReport"
            @click="loadReport"
          >
            {{ t('loading').replace('...', '') }}
          </BaseButton>
        </div>

        <!-- Trial Balance Report -->
        <div v-if="activeReportTab === 'trial_balance'">
          <div v-if="isLoadingReport" class="bg-white rounded-lg p-6 animate-pulse"><div class="space-y-3"><div v-for="i in 5" :key="i" class="h-8 bg-gray-200 rounded"></div></div></div>
          <div v-else-if="reportData.trial_balance" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('account_type') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('closing_debit') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('closing_credit') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('elimination_debit') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('elimination_credit') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('net_debit') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('net_credit') }}</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="row in reportData.trial_balance.accounts" :key="row.account_type">
                  <td class="px-4 py-2 text-sm text-gray-900">{{ row.account_type_label || row.account_type }}</td>
                  <td class="px-4 py-2 text-sm text-right text-gray-700">{{ formatNumber(row.closing_debit) }}</td>
                  <td class="px-4 py-2 text-sm text-right text-gray-700">{{ formatNumber(row.closing_credit) }}</td>
                  <td class="px-4 py-2 text-sm text-right text-red-600">{{ formatNumber(row.elimination_debit) }}</td>
                  <td class="px-4 py-2 text-sm text-right text-red-600">{{ formatNumber(row.elimination_credit) }}</td>
                  <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">{{ formatNumber(row.net_debit) }}</td>
                  <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">{{ formatNumber(row.net_credit) }}</td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50 font-semibold">
                <tr>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ t('total') }}</td>
                  <td class="px-4 py-3 text-sm text-right" colspan="4"></td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">
                    {{ formatNumber(reportData.trial_balance.totals?.net_debit) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">
                    {{ formatNumber(reportData.trial_balance.totals?.net_credit) }}
                  </td>
                </tr>
                <tr>
                  <td colspan="7" class="px-4 py-2 text-xs text-center">
                    <span
                      :class="reportData.trial_balance.totals?.is_balanced ? 'text-green-600' : 'text-red-600'"
                    >
                      {{ reportData.trial_balance.totals?.is_balanced ? t('balanced') : t('not_balanced') }}
                    </span>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Income Statement Report -->
        <div v-if="activeReportTab === 'income_statement'">
          <div v-if="isLoadingReport" class="bg-white rounded-lg p-6 animate-pulse"><div class="space-y-3"><div v-for="i in 5" :key="i" class="h-8 bg-gray-200 rounded"></div></div></div>
          <div v-else-if="reportData.income_statement" class="space-y-4">
            <!-- Summary Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('revenue') }}</p>
                <p class="text-lg font-semibold text-gray-900 mt-1">
                  {{ formatNumber(reportData.income_statement.consolidated?.total_revenue) }}
                </p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('revenue_elimination') }}</p>
                <p class="text-lg font-semibold text-red-600 mt-1">
                  -{{ formatNumber(reportData.income_statement.consolidated?.revenue_elimination) }}
                </p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('expenses') }}</p>
                <p class="text-lg font-semibold text-gray-900 mt-1">
                  {{ formatNumber(reportData.income_statement.consolidated?.total_expenses) }}
                </p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('net_income') }}</p>
                <p class="text-lg font-semibold mt-1" :class="reportData.income_statement.consolidated?.net_income >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ formatNumber(reportData.income_statement.consolidated?.net_income) }}
                </p>
              </div>
            </div>

            <!-- Minority Interest -->
            <div v-if="reportData.income_statement.consolidated?.minority_interest > 0" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
              <p class="text-sm text-yellow-800">
                {{ t('minority_interest') }}: {{ formatNumber(reportData.income_statement.consolidated?.minority_interest) }}
              </p>
            </div>

            <!-- Company Breakdown -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('company') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('ownership_pct') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('revenue') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('expenses') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('net_income') }}</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="comp in reportData.income_statement.companies" :key="comp.company_id">
                    <td class="px-4 py-2 text-sm text-gray-900">
                      {{ comp.company_name }}
                      <span v-if="comp.is_parent" class="ml-1 text-xs text-primary-600">({{ t('is_parent') }})</span>
                    </td>
                    <td class="px-4 py-2 text-sm text-right text-gray-700">{{ comp.ownership_pct }}%</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-700">{{ formatNumber(comp.revenue) }}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-700">{{ formatNumber(comp.expenses) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium" :class="comp.net_income >= 0 ? 'text-green-600' : 'text-red-600'">
                      {{ formatNumber(comp.net_income) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Balance Sheet Report -->
        <div v-if="activeReportTab === 'balance_sheet'">
          <div v-if="isLoadingReport" class="bg-white rounded-lg p-6 animate-pulse"><div class="space-y-3"><div v-for="i in 5" :key="i" class="h-8 bg-gray-200 rounded"></div></div></div>
          <div v-else-if="reportData.balance_sheet" class="space-y-4">
            <!-- Summary Cards -->
            <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('assets') }}</p>
                <p class="text-lg font-semibold text-gray-900 mt-1">
                  {{ formatNumber(reportData.balance_sheet.consolidated?.net_assets) }}
                </p>
                <p v-if="reportData.balance_sheet.consolidated?.asset_elimination > 0" class="text-xs text-red-500 mt-0.5">
                  {{ t('asset_elimination') }}: -{{ formatNumber(reportData.balance_sheet.consolidated?.asset_elimination) }}
                </p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('liabilities') }}</p>
                <p class="text-lg font-semibold text-gray-900 mt-1">
                  {{ formatNumber(reportData.balance_sheet.consolidated?.net_liabilities) }}
                </p>
                <p v-if="reportData.balance_sheet.consolidated?.liability_elimination > 0" class="text-xs text-red-500 mt-0.5">
                  {{ t('liability_elimination') }}: -{{ formatNumber(reportData.balance_sheet.consolidated?.liability_elimination) }}
                </p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('equity') }}</p>
                <p class="text-lg font-semibold text-gray-900 mt-1">
                  {{ formatNumber(reportData.balance_sheet.consolidated?.net_equity) }}
                </p>
              </div>
              <div v-if="reportData.balance_sheet.consolidated?.minority_interest > 0" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                <p class="text-xs text-yellow-700">{{ t('minority_interest') }}</p>
                <p class="text-lg font-semibold text-yellow-800 mt-1">
                  {{ formatNumber(reportData.balance_sheet.consolidated?.minority_interest) }}
                </p>
              </div>
            </div>

            <!-- Company Breakdown -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('company') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('ownership_pct') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('assets') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('liabilities') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('equity') }}</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="comp in reportData.balance_sheet.companies" :key="comp.company_id">
                    <td class="px-4 py-2 text-sm text-gray-900">
                      {{ comp.company_name }}
                      <span v-if="comp.is_parent" class="ml-1 text-xs text-primary-600">({{ t('is_parent') }})</span>
                    </td>
                    <td class="px-4 py-2 text-sm text-right text-gray-700">{{ comp.ownership_pct }}%</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-700">{{ formatNumber(comp.assets) }}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-700">{{ formatNumber(comp.liabilities) }}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-700">{{ formatNumber(comp.equity) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Intercompany Report -->
        <div v-if="activeReportTab === 'intercompany'">
          <div v-if="isLoadingReport" class="bg-white rounded-lg p-6 animate-pulse"><div class="space-y-3"><div v-for="i in 5" :key="i" class="h-8 bg-gray-200 rounded"></div></div></div>
          <div v-else-if="reportData.intercompany" class="space-y-4">
            <!-- Summary -->
            <div class="grid gap-4 sm:grid-cols-3">
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('intercompany') }}</p>
                <p class="text-lg font-semibold text-gray-900 mt-1">{{ reportData.intercompany.count }}</p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('amount') }}</p>
                <p class="text-lg font-semibold text-gray-900 mt-1">
                  {{ formatNumber(reportData.intercompany.total_amount) }}
                </p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">{{ t('total_eliminated') }}</p>
                <p class="text-lg font-semibold text-red-600 mt-1">
                  {{ formatNumber(reportData.eliminations?.total_eliminated || 0) }}
                </p>
              </div>
            </div>

            <!-- Transactions Table -->
            <div v-if="reportData.intercompany.count > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('date') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('seller') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('buyer') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount') }}</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(txn, idx) in reportData.intercompany.transactions" :key="idx">
                    <td class="px-4 py-2 text-sm text-gray-900">{{ txn.document_number }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ txn.date }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ txn.seller_company_name }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ txn.buyer_company_name }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">{{ formatNumber(txn.amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              {{ t('no_intercompany') }}
            </div>

            <!-- Eliminations Detail -->
            <div v-if="reportData.eliminations && reportData.eliminations.eliminations.length > 0" class="mt-6">
              <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ t('eliminations') }}</h3>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document') }}</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('seller') }}</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('buyer') }}</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('original_amount') }}</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('elimination_amount') }}</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('minority_interest') }}</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('effective_rate') }}</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(elim, idx) in reportData.eliminations.eliminations" :key="idx">
                      <td class="px-4 py-2 text-sm text-gray-900">{{ elim.document_number }}</td>
                      <td class="px-4 py-2 text-sm text-gray-700">{{ elim.seller_company_name }}</td>
                      <td class="px-4 py-2 text-sm text-gray-700">{{ elim.buyer_company_name }}</td>
                      <td class="px-4 py-2 text-sm text-right text-gray-700">{{ formatNumber(elim.original_amount) }}</td>
                      <td class="px-4 py-2 text-sm text-right text-red-600 font-medium">{{ formatNumber(elim.elimination_amount) }}</td>
                      <td class="px-4 py-2 text-sm text-right text-yellow-700">{{ formatNumber(elim.minority_interest) }}</td>
                      <td class="px-4 py-2 text-sm text-right text-gray-700">{{ elim.effective_rate }}%</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="closeModal"
    >
      <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          {{ isEditing ? t('edit_group') : t('create_group') }}
        </h2>

        <div class="space-y-4">
          <!-- Group Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('group_name') }}</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full rounded-md border-gray-300 text-sm"
              maxlength="150"
            />
          </div>

          <!-- Parent Company -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('parent_company') }}</label>
            <select
              v-model="form.parent_company_id"
              class="w-full rounded-md border-gray-300 text-sm"
            >
              <option value="">{{ t('select_company') }}</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <!-- Currency -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('currency') }}</label>
            <select
              v-model="form.currency_code"
              class="w-full rounded-md border-gray-300 text-sm"
            >
              <option value="MKD">MKD</option>
              <option value="EUR">EUR</option>
              <option value="USD">USD</option>
            </select>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('notes') }}</label>
            <textarea
              v-model="form.notes"
              rows="2"
              class="w-full rounded-md border-gray-300 text-sm"
            ></textarea>
          </div>

          <!-- Members -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-gray-700">{{ t('members') }}</label>
              <BaseButton variant="gray" size="sm" @click="addMember">
                {{ t('add_member') }}
              </BaseButton>
            </div>

            <div class="space-y-2">
              <div
                v-for="(member, index) in form.members"
                :key="index"
                class="flex items-center gap-3 rounded border border-gray-200 bg-gray-50 p-3"
              >
                <div class="flex-1">
                  <select
                    v-model="member.company_id"
                    class="w-full rounded-md border-gray-300 text-sm"
                  >
                    <option value="">{{ t('select_company') }}</option>
                    <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
                <div class="w-28">
                  <div class="flex items-center gap-1">
                    <input
                      v-model.number="member.ownership_pct"
                      type="number"
                      min="0"
                      max="100"
                      step="0.01"
                      class="w-full rounded-md border-gray-300 text-sm"
                    />
                    <span class="text-sm text-gray-500">%</span>
                  </div>
                </div>
                <button
                  v-if="form.members.length > 1"
                  class="p-1 text-gray-400 hover:text-red-600"
                  @click="removeMember(index)"
                >
                  <BaseIcon name="TrashIcon" class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <BaseButton variant="gray" @click="closeModal">
            {{ t('cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            :loading="isSaving"
            :disabled="!isFormValid"
            @click="saveGroup"
          >
            {{ t('save') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import consolidationMessages from '@/scripts/admin/i18n/consolidation.js'

const axios = window.axios

const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function t(key) {
  return consolidationMessages[locale]?.consolidation?.[key]
    || consolidationMessages['en']?.consolidation?.[key]
    || key
}

function formatNumber(val) {
  if (val === null || val === undefined) return '0.00'
  return Number(val).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// State
const activeTab = ref('groups')
const activeReportTab = ref('trial_balance')
const isLoading = ref(false)
const isLoadingReport = ref(false)
const isSaving = ref(false)
const showModal = ref(false)
const isEditing = ref(false)
const editingGroupId = ref(null)
const selectedGroupId = ref(null)

const groups = ref([])

const tabs = [
  { key: 'groups' },
  { key: 'reports' },
]

const reportTabs = [
  { key: 'trial_balance', label: 'trial_balance' },
  { key: 'income_statement', label: 'income_statement' },
  { key: 'balance_sheet', label: 'balance_sheet' },
  { key: 'intercompany', label: 'intercompany' },
]

const reportFilters = reactive({
  start_date: new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
})

const reportData = reactive({
  trial_balance: null,
  income_statement: null,
  balance_sheet: null,
  intercompany: null,
  eliminations: null,
})

const form = reactive({
  name: '',
  parent_company_id: '',
  currency_code: 'MKD',
  notes: '',
  members: [{ company_id: '', ownership_pct: 100 }],
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const isFormValid = computed(() => {
  return form.name.trim().length > 0
    && form.parent_company_id
    && form.members.length > 0
    && form.members.every(m => m.company_id)
})

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
  } catch (error) {
    // Companies may already be loaded
  }

  await fetchGroups()
})

// Methods
async function fetchGroups() {
  isLoading.value = true
  try {
    const response = await axios.get('/partner/consolidation/groups')
    groups.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch consolidation groups:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

function selectGroup(group) {
  selectedGroupId.value = group.id
  activeTab.value = 'reports'
  // Clear previous report data
  reportData.trial_balance = null
  reportData.income_statement = null
  reportData.balance_sheet = null
  reportData.intercompany = null
  reportData.eliminations = null
}

function onGroupChange() {
  // Clear report data when changing group
  reportData.trial_balance = null
  reportData.income_statement = null
  reportData.balance_sheet = null
  reportData.intercompany = null
  reportData.eliminations = null
}

async function loadReport() {
  if (!selectedGroupId.value) return

  isLoadingReport.value = true

  try {
    if (activeReportTab.value === 'trial_balance') {
      const response = await axios.get(`/partner/consolidation/groups/${selectedGroupId.value}/trial-balance`, {
        params: {
          start_date: reportFilters.start_date,
          end_date: reportFilters.end_date,
        }
      })
      reportData.trial_balance = response.data.data
    } else if (activeReportTab.value === 'income_statement') {
      const response = await axios.get(`/partner/consolidation/groups/${selectedGroupId.value}/income-statement`, {
        params: {
          start_date: reportFilters.start_date,
          end_date: reportFilters.end_date,
        }
      })
      reportData.income_statement = response.data.data
    } else if (activeReportTab.value === 'balance_sheet') {
      const response = await axios.get(`/partner/consolidation/groups/${selectedGroupId.value}/balance-sheet`, {
        params: {
          date: reportFilters.end_date,
        }
      })
      reportData.balance_sheet = response.data.data
    } else if (activeReportTab.value === 'intercompany') {
      const [icResponse, elimResponse] = await Promise.all([
        axios.get(`/partner/consolidation/groups/${selectedGroupId.value}/intercompany`, {
          params: {
            start_date: reportFilters.start_date,
            end_date: reportFilters.end_date,
          }
        }),
        axios.post(`/partner/consolidation/groups/${selectedGroupId.value}/eliminations`, {
          start_date: reportFilters.start_date,
          end_date: reportFilters.end_date,
        }),
      ])
      reportData.intercompany = icResponse.data.data
      reportData.eliminations = elimResponse.data.data
    }
  } catch (error) {
    console.error('Failed to load report:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading'),
    })
  } finally {
    isLoadingReport.value = false
  }
}

function openCreateModal() {
  isEditing.value = false
  editingGroupId.value = null
  form.name = ''
  form.parent_company_id = ''
  form.currency_code = 'MKD'
  form.notes = ''
  form.members = [{ company_id: '', ownership_pct: 100 }]
  showModal.value = true
}

function openEditModal(group) {
  isEditing.value = true
  editingGroupId.value = group.id
  form.name = group.name
  form.parent_company_id = group.parent_company_id
  form.currency_code = group.currency_code || 'MKD'
  form.notes = group.notes || ''
  form.members = (group.members || []).map(m => ({
    company_id: m.company_id,
    ownership_pct: parseFloat(m.ownership_pct) || 100,
  }))

  if (form.members.length === 0) {
    form.members = [{ company_id: '', ownership_pct: 100 }]
  }

  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function addMember() {
  form.members.push({ company_id: '', ownership_pct: 100 })
}

function removeMember(index) {
  form.members.splice(index, 1)
}

async function saveGroup() {
  isSaving.value = true

  try {
    const payload = {
      name: form.name,
      parent_company_id: form.parent_company_id,
      currency_code: form.currency_code,
      notes: form.notes,
      members: form.members.filter(m => m.company_id),
    }

    if (isEditing.value && editingGroupId.value) {
      await axios.put(`/partner/consolidation/groups/${editingGroupId.value}`, payload)
      notificationStore.showNotification({
        type: 'success',
        message: t('group_updated'),
      })
    } else {
      await axios.post('/partner/consolidation/groups', payload)
      notificationStore.showNotification({
        type: 'success',
        message: t('group_created'),
      })
    }

    closeModal()
    await fetchGroups()
  } catch (error) {
    console.error('Failed to save group:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading'),
    })
  } finally {
    isSaving.value = false
  }
}

async function confirmDelete(group) {
  if (!confirm(t('confirm_delete'))) return

  try {
    await axios.delete(`/partner/consolidation/groups/${group.id}`)
    notificationStore.showNotification({
      type: 'success',
      message: t('group_deleted'),
    })

    if (selectedGroupId.value === group.id) {
      selectedGroupId.value = null
    }

    await fetchGroups()
  } catch (error) {
    console.error('Failed to delete group:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading'),
    })
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
