<template>
  <BasePage>
    <!-- Page Header Section -->
    <BasePageHeader :title="$t('banking.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem
          :title="$t('banking.title')"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-3">
          <!-- Primary actions -->
          <BaseButton
            variant="primary-outline"
            @click="router.push({ name: 'banking.import' })"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowUpTrayIcon" :class="slotProps.class" />
            </template>
            {{ $t('banking.import_statement') || 'Import Statement' }}
          </BaseButton>
          <BaseButton
            variant="primary"
            @click="showAddAccountModal = true"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('banking.add_account', 'Додај сметка') }}
          </BaseButton>

          <!-- Reconciliation (most-used action) -->
          <BaseButton
            variant="primary-outline"
            @click="router.push({ name: 'banking.reconciliation' })"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowsRightLeftIcon" :class="slotProps.class" />
            </template>
            {{ $t('banking.reconciliation') }}
          </BaseButton>

          <!-- Secondary actions dropdown -->
          <BaseDropdown width-class="w-48">
            <template #activator>
              <BaseButton variant="primary-outline">
                <template #left="slotProps">
                  <BaseIcon name="EllipsisVerticalIcon" :class="slotProps.class" />
                </template>
                {{ $t('general.more', 'Повеќе') }}
              </BaseButton>
            </template>
            <BaseDropdownItem @click="router.push({ name: 'banking.matching-rules' })">
              <BaseIcon name="AdjustmentsHorizontalIcon" class="mr-3 h-5 w-5 text-gray-500" />
              {{ $t('matching_rules.title') || 'Matching Rules' }}
            </BaseDropdownItem>
            <BaseDropdownItem @click="router.push({ name: 'banking.analytics' })">
              <BaseIcon name="ChartBarIcon" class="mr-3 h-5 w-5 text-gray-500" />
              {{ $t('banking.analytics') || 'Analytics' }}
            </BaseDropdownItem>
            <BaseDropdownItem @click="router.push({ name: 'banking.import-history' })">
              <BaseIcon name="ClockIcon" class="mr-3 h-5 w-5 text-gray-500" />
              {{ $t('banking.history.title') || 'Import History' }}
            </BaseDropdownItem>
            <BaseDropdownItem @click="showManualEntryModal = true">
              <BaseIcon name="PencilSquareIcon" class="mr-3 h-5 w-5 text-gray-500" />
              {{ $t('banking.manual_entry.button', 'Manual Entry') }}
            </BaseDropdownItem>
            <BaseDropdownItem @click="exportTransactions">
              <BaseIcon name="ArrowDownTrayIcon" class="mr-3 h-5 w-5 text-gray-500" />
              {{ $t('banking.export', 'Export CSV') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </div>
      </template>
    </BasePageHeader>

    <!-- Empty State -->
    <BaseEmptyPlaceholder
      v-if="showEmptyScreen"
      :title="$t('banking.no_banks_connected')"
      :description="$t('banking.connect_bank_description')"
    >
      <BanknotesIcon class="mt-5 mb-4 w-20 h-20 text-gray-300" />

      <template #actions>
        <BaseButton
          variant="primary"
          @click="showAddAccountModal = true"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('banking.add_account', 'Додај сметка') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <!-- Connected Banks Section -->
    <div v-if="!showEmptyScreen && !isLoadingAccounts" class="mt-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="account in connectedAccounts"
          :key="account.id"
          class="bg-white rounded-lg shadow-md p-6 border-2 cursor-pointer hover:shadow-lg transition-all"
          :class="filters.account_id === account.id ? 'border-primary-500 ring-2 ring-primary-200' : 'border-gray-200'"
          @click="selectAccount(account.id)"
        >
          <!-- Bank Logo and Name -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <img
                v-if="account.bank_logo"
                :src="account.bank_logo"
                :alt="account.bank_name"
                class="h-10 w-auto mr-3"
                @error="handleImageError"
              />
              <div>
                <h3 class="text-lg font-semibold text-gray-900">
                  {{ account.bank_name }}
                </h3>
                <p class="text-sm text-gray-500">
                  {{ account.account_number }}
                </p>
              </div>
            </div>
            <BaseDropdown>
              <template #activator>
                <BaseIcon
                  name="EllipsisVerticalIcon"
                  class="h-6 w-6 text-gray-400 cursor-pointer"
                />
              </template>
              <BaseDropdownItem @click="syncAccount(account.id)">
                <BaseIcon name="ArrowPathIcon" class="mr-3 text-gray-600" />
                {{ $t('banking.sync_now') }}
              </BaseDropdownItem>
              <BaseDropdownItem @click="disconnectBank(account.id)">
                <BaseIcon name="TrashIcon" class="mr-3 text-red-600" />
                {{ $t('banking.disconnect') }}
              </BaseDropdownItem>
            </BaseDropdown>
          </div>

          <!-- Account Balance -->
          <div class="mb-4">
            <p class="text-sm text-gray-500 mb-1">
              {{ $t('banking.current_balance') }}
            </p>
            <p class="text-2xl font-bold text-gray-900">
              {{ formatMoney(account.current_balance, account.currency) }}
            </p>
          </div>

          <!-- Last Sync Info -->
          <div class="flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
              <BaseIcon name="ClockIcon" class="h-4 w-4 mr-1" />
              <span>{{ $t('banking.last_sync') }}: {{ formatDate(account.last_sync_at) }}</span>
            </div>
            <span
              v-if="account.sync_status === 'syncing'"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              {{ $t('banking.syncing') }}
            </span>
            <span
              v-else
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
            >
              {{ $t('banking.connected') }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoadingAccounts" class="mt-6 flex justify-center">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox :rounded="true" />
        <BaseContentPlaceholdersBox :rounded="true" />
        <BaseContentPlaceholdersBox :rounded="true" />
      </BaseContentPlaceholders>
    </div>

    <!-- MK Document Quick Actions -->
    <div v-if="!showEmptyScreen && !isLoadingAccounts" class="mt-6">
      <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">
        {{ $t('banking.documents', 'Документи') }}
      </h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <button
          class="flex items-center gap-3 p-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-primary-400 hover:shadow transition-all text-left"
          @click="downloadBankStatement"
        >
          <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
            <BaseIcon name="DocumentTextIcon" class="h-5 w-5 text-blue-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ $t('banking.bank_statement', 'Извод од банка') }}</p>
            <p class="text-xs text-gray-500">PDF</p>
          </div>
        </button>
        <button
          class="flex items-center gap-3 p-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-primary-400 hover:shadow transition-all text-left"
          @click="downloadIos"
        >
          <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
            <BaseIcon name="DocumentDuplicateIcon" class="h-5 w-5 text-green-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ $t('banking.ios_report', 'ИОС извештај') }}</p>
            <p class="text-xs text-gray-500">PDF</p>
          </div>
        </button>
        <button
          class="flex items-center gap-3 p-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-primary-400 hover:shadow transition-all text-left"
          @click="downloadDailyCashReport"
        >
          <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
            <BaseIcon name="BanknotesIcon" class="h-5 w-5 text-amber-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ $t('banking.daily_cash_report', 'Дневен извештај') }}</p>
            <p class="text-xs text-gray-500">PDF</p>
          </div>
        </button>
        <button
          class="flex items-center gap-3 p-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-primary-400 hover:shadow transition-all text-left"
          @click="router.push({ name: 'banking.reconciliation' })"
        >
          <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
            <BaseIcon name="ArrowsRightLeftIcon" class="h-5 w-5 text-purple-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ $t('banking.reconciliation') }}</p>
            <p class="text-xs text-gray-500">{{ $t('banking.match_invoices', 'Спои фактури') }}</p>
          </div>
        </button>
      </div>
    </div>

    <!-- Recent Transactions Section -->
    <div v-if="!showEmptyScreen" class="mt-8">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <h2 class="text-xl font-semibold text-gray-900">
            {{ selectedAccountLabel }}
          </h2>
          <button
            v-if="filters.account_id"
            @click="filters.account_id = null"
            class="text-sm text-primary-600 hover:text-primary-800 underline"
          >
            {{ $t('banking.all_accounts') }}
          </button>
        </div>
        <BaseButton
          v-if="connectedAccounts.length > 0"
          variant="primary-outline"
          size="sm"
          @click="toggleFilters"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FunnelIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>
      </div>

      <!-- Filters -->
      <BaseFilterWrapper :show="showFilters" class="mb-4" @clear="clearFilters">
        <BaseInputGroup :label="$t('banking.account')" class="text-left">
          <BaseMultiselect
            v-model="filters.account_id"
            :options="accountOptions"
            :searchable="true"
            :show-labels="false"
            :placeholder="$t('banking.select_account')"
            label="label"
            value-prop="value"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('general.from')" class="text-left">
          <BaseDatePicker
            v-model="filters.from_date"
            :calendar-button="true"
            calendar-button-icon="calendar"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('general.to')" class="text-left">
          <BaseDatePicker
            v-model="filters.to_date"
            :calendar-button="true"
            calendar-button-icon="calendar"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('banking.min_amount', 'Мин. износ')" class="text-left">
          <BaseInput
            v-model="filters.min_amount"
            type="number"
            step="0.01"
            placeholder="0.00"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('banking.max_amount', 'Макс. износ')" class="text-left">
          <BaseInput
            v-model="filters.max_amount"
            type="number"
            step="0.01"
            placeholder="0.00"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('banking.type')" class="text-left">
          <BaseMultiselect
            v-model="filters.transaction_type"
            :options="transactionTypeOptions"
            :searchable="false"
            :show-labels="false"
            :placeholder="$t('banking.all_types', 'Сите типови')"
            label="label"
            value-prop="value"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('banking.counterparty', 'Контрапартија')" class="text-left">
          <BaseInput
            v-model="filters.counterparty"
            :placeholder="$t('banking.search_counterparty', 'Пребарај...')"
          />
        </BaseInputGroup>
      </BaseFilterWrapper>

      <!-- Transactions List Component -->
      <TransactionsList
        ref="transactionsListRef"
        :filters="filters"
        :accounts="connectedAccounts"
        @categorize="openCategorizationModal"
        @reconcile="openSmartDrawer"
      />
    </div>

    <!-- Connect Bank Modal -->
    <ConnectBank
      v-model="showConnectModal"
      @connected="onBankConnected"
    />

    <!-- Transaction Categorization Modal (legacy) -->
    <TransactionCategorization
      v-model="showCategorizationModal"
      :transaction="selectedTransaction"
      @categorized="onTransactionCategorized"
    />

    <!-- Smart Reconciliation Drawer -->
    <SmartReconciliationDrawer
      v-model="showSmartDrawer"
      :transaction="selectedTransaction"
      @reconciled="onTransactionReconciled"
    />

    <!-- Manual Entry Modal -->
    <ManualEntryModal
      v-model="showManualEntryModal"
      :accounts="manualEntryAccounts"
      @created="onManualEntryCreated"
    />

    <!-- Add Bank Account Modal -->
    <BaseModal
      :show="showAddAccountModal"
      @close="showAddAccountModal = false"
    >
      <template #header>
        <h3 class="text-lg font-semibold">{{ $t('banking.add_account', 'Додај сметка') }}</h3>
      </template>
      <form @submit.prevent="createAccount">
        <BaseInputGroup
          :label="$t('banking.bank_name', 'Име на банка')"
          required
          class="mb-4"
        >
          <BaseInput
            v-model="newAccount.bank_name"
            :placeholder="$t('banking.bank_name_placeholder', 'пр. Комерцијална Банка')"
            required
          />
        </BaseInputGroup>
        <BaseInputGroup
          :label="$t('banking.account_number', 'Број на сметка')"
          required
          class="mb-4"
        >
          <BaseInput
            v-model="newAccount.account_number"
            :placeholder="$t('banking.account_number_placeholder', 'пр. 300000000000123')"
            required
          />
        </BaseInputGroup>
        <BaseInputGroup
          :label="$t('banking.opening_balance', 'Почетно салдо')"
          class="mb-4"
        >
          <BaseInput
            v-model="newAccount.opening_balance"
            type="number"
            step="0.01"
            placeholder="0.00"
          />
        </BaseInputGroup>
        <div class="flex justify-end space-x-3 mt-6">
          <BaseButton
            variant="primary-outline"
            type="button"
            @click="showAddAccountModal = false"
          >
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            type="submit"
            :loading="isCreatingAccount"
          >
            {{ $t('general.save') }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import axios from 'axios'
import ConnectBank from './ConnectBank.vue'
import TransactionsList from './TransactionsList.vue'
import TransactionCategorization from './TransactionCategorization.vue'
import ManualEntryModal from './ManualEntryModal.vue'
import SmartReconciliationDrawer from './SmartReconciliationDrawer.vue'
import { BanknotesIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const { t } = useI18n()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()

// State
const connectedAccounts = ref([])
const isLoadingAccounts = ref(true)
const showConnectModal = ref(false)
const showCategorizationModal = ref(false)
const showSmartDrawer = ref(false)
const showManualEntryModal = ref(false)
const showAddAccountModal = ref(false)
const isCreatingAccount = ref(false)
const newAccount = ref({ bank_name: '', account_number: '', opening_balance: null })
const selectedTransaction = ref(null)
const transactionsListRef = ref(null)
const showFilters = ref(false)

// Filters
const filters = ref({
  account_id: null,
  from_date: null,
  to_date: null,
  min_amount: null,
  max_amount: null,
  transaction_type: null,
  counterparty: null,
})

const transactionTypeOptions = computed(() => [
  { label: t('banking.all_types', 'Сите типови'), value: null },
  { label: t('banking.credit', 'Приход'), value: 'credit' },
  { label: t('banking.debit', 'Расход'), value: 'debit' },
])

// Computed
const showEmptyScreen = computed(() => {
  return !isLoadingAccounts.value && connectedAccounts.value.length === 0
})

const selectedAccountLabel = computed(() => {
  if (!filters.value.account_id) {
    return t('banking.recent_transactions')
  }
  const acc = connectedAccounts.value.find(a => a.id === filters.value.account_id)
  return acc ? `${acc.bank_name} — ${acc.account_number}` : t('banking.recent_transactions')
})

const accountOptions = computed(() => {
  return [
    { label: t('banking.all_accounts'), value: null },
    ...connectedAccounts.value.map(acc => ({
      label: `${acc.bank_name} - ${acc.account_number}`,
      value: acc.id
    }))
  ]
})

// Methods
const fetchConnectedAccounts = async () => {
  isLoadingAccounts.value = true
  try {
    const response = await axios.get('/banking/accounts')
    connectedAccounts.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch connected accounts:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.failed_to_load_accounts')
    })
  } finally {
    isLoadingAccounts.value = false
  }
}

const exportTransactions = async () => {
  try {
    const params = { ...filters.value }
    const response = await axios.get('/banking/transactions/export', {
      params,
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `transactions_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Failed to export transactions',
    })
  }
}

const syncAccount = async (accountId) => {
  try {
    notificationStore.showNotification({
      type: 'info',
      message: t('banking.syncing_account')
    })

    await axios.post(`/banking/sync/${accountId}`)

    notificationStore.showNotification({
      type: 'success',
      message: t('banking.sync_successful')
    })

    // Refresh accounts to get updated balance and sync time
    await fetchConnectedAccounts()
  } catch (error) {
    console.error('Failed to sync account:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.sync_failed')
    })
  }
}

const disconnectBank = async (accountId) => {
  const confirmed = await dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('banking.confirm_disconnect'),
    yesLabel: t('general.yes'),
    noLabel: t('general.no'),
    variant: 'danger',
  })

  if (!confirmed) {
    return
  }

  try {
    await axios.delete(`/banking/accounts/${accountId}`)

    notificationStore.showNotification({
      type: 'success',
      message: t('banking.disconnected_successfully')
    })

    // Refresh accounts list
    await fetchConnectedAccounts()
  } catch (error) {
    console.error('Failed to disconnect bank:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.disconnect_failed')
    })
  }
}

const onBankConnected = () => {
  showConnectModal.value = false
  fetchConnectedAccounts()
  notificationStore.showNotification({
    type: 'success',
    message: t('banking.bank_connected_successfully')
  })
}

const openCategorizationModal = (transaction) => {
  selectedTransaction.value = transaction
  showCategorizationModal.value = true
}

const onTransactionCategorized = () => {
  showCategorizationModal.value = false
  selectedTransaction.value = null
}

const openSmartDrawer = (transaction) => {
  selectedTransaction.value = transaction
  showSmartDrawer.value = true
}

const onTransactionReconciled = () => {
  showSmartDrawer.value = false
  selectedTransaction.value = null
  // Refresh the transactions list to show updated reconciliation status
  transactionsListRef.value?.refresh()
}

const manualEntryAccounts = computed(() => {
  return connectedAccounts.value.map(acc => ({
    id: acc.id,
    label: `${acc.bank_name} - ${acc.account_number}`,
  }))
})

const onManualEntryCreated = () => {
  // Refresh accounts and transactions
  fetchConnectedAccounts()
}

const createAccount = async () => {
  isCreatingAccount.value = true
  try {
    await axios.post('/banking/accounts', newAccount.value)
    showAddAccountModal.value = false
    newAccount.value = { bank_name: '', account_number: '', opening_balance: null }
    notificationStore.showNotification({
      type: 'success',
      message: t('banking.account_created', 'Сметката е додадена успешно')
    })
    await fetchConnectedAccounts()
  } catch (error) {
    console.error('Failed to create account:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('banking.account_create_failed', 'Грешка при додавање на сметка')
    })
  } finally {
    isCreatingAccount.value = false
  }
}

const toggleFilters = () => {
  showFilters.value = !showFilters.value
}

const selectAccount = (accountId) => {
  // Toggle: click same account again to deselect (show all)
  if (filters.value.account_id === accountId) {
    filters.value.account_id = null
  } else {
    filters.value.account_id = accountId
  }
}

const clearFilters = () => {
  filters.value = {
    account_id: null,
    from_date: null,
    to_date: null,
    min_amount: null,
    max_amount: null,
    transaction_type: null,
    counterparty: null,
  }
}

const downloadPdfReport = async (url, filename, extraParams = {}) => {
  try {
    const params = { ...extraParams }
    if (filters.value.account_id) params.account_id = filters.value.account_id
    if (filters.value.from_date) params.from = filters.value.from_date
    if (filters.value.to_date) params.to = filters.value.to_date
    const response = await axios.get(url, { params, responseType: 'blob' })

    // Check if response is actually a JSON error (422/500 returned as blob)
    if (response.data.type && response.data.type.includes('json')) {
      const text = await response.data.text()
      const json = JSON.parse(text)
      throw { response: { data: json } }
    }

    const blob = new Blob([response.data], { type: 'application/pdf' })
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(link.href)
  } catch (error) {
    let msg = 'Failed to generate report'
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        msg = json.message || msg
      } catch (_) {}
    } else if (error.response?.data?.message) {
      msg = error.response.data.message
    }
    notificationStore.showNotification({ type: 'error', message: msg })
  }
}

const downloadBankStatement = () => {
  const accountId = filters.value.account_id || connectedAccounts.value[0]?.id
  if (!accountId) {
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.no_accounts_error', 'Немате поврзани банкарски сметки. Додајте сметка во Подесувања → Банкарски сметки.'),
    })
    return
  }
  const date = new Date().toISOString().split('T')[0]
  downloadPdfReport('/banking/statement-report', 'bank-statement-' + date + '.pdf', { account_id: accountId })
}

const downloadIos = () => {
  notificationStore.showNotification({
    type: 'info',
    message: t('banking.ios_from_customer', 'ИОС извештајот се генерира од страницата на купувачот. Отворете Купувач → Документи → ИОС.'),
  })
}

const downloadDailyCashReport = () => {
  const date = new Date().toISOString().split('T')[0]
  downloadPdfReport('/banking/daily-cash-report', 'daily-cash-' + date + '.pdf', { date })
}

const formatMoney = (amount, currency) => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: currency || 'MKD'
  }).format(amount)
}

const formatDate = (date) => {
  if (!date) return t('banking.never')
  return new Date(date).toLocaleDateString()
}

const handleImageError = (event) => {
  // Hide the image if it fails to load
  event.target.style.display = 'none'
}

// Lifecycle
onMounted(() => {
  fetchConnectedAccounts()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
