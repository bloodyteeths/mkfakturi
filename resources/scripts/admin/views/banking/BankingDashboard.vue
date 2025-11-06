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
        <div class="flex items-center justify-end space-x-5">
          <BaseButton
            variant="primary"
            @click="showConnectModal = true"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('banking.connect_bank') }}
          </BaseButton>
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
          variant="primary-outline"
          @click="showConnectModal = true"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('banking.connect_first_bank') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <!-- Connected Banks Section -->
    <div v-if="!showEmptyScreen && !isLoadingAccounts" class="mt-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="account in connectedAccounts"
          :key="account.id"
          class="bg-white rounded-lg shadow-md p-6 border border-gray-200 hover:shadow-lg transition-shadow"
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

    <!-- Recent Transactions Section -->
    <div v-if="!showEmptyScreen" class="mt-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-gray-900">
          {{ $t('banking.recent_transactions') }}
        </h2>
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
          <BaseSelect
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
      </BaseFilterWrapper>

      <!-- Transactions List Component -->
      <TransactionsList
        :filters="filters"
        :accounts="connectedAccounts"
        @categorize="openCategorizationModal"
      />
    </div>

    <!-- Connect Bank Modal -->
    <ConnectBank
      v-model="showConnectModal"
      @connected="onBankConnected"
    />

    <!-- Transaction Categorization Modal -->
    <TransactionCategorization
      v-model="showCategorizationModal"
      :transaction="selectedTransaction"
      @categorized="onTransactionCategorized"
    />
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'
import ConnectBank from './ConnectBank.vue'
import TransactionsList from './TransactionsList.vue'
import TransactionCategorization from './TransactionCategorization.vue'
import { BanknotesIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()
const notificationStore = useNotificationStore()

// State
const connectedAccounts = ref([])
const isLoadingAccounts = ref(true)
const showConnectModal = ref(false)
const showCategorizationModal = ref(false)
const selectedTransaction = ref(null)
const showFilters = ref(false)

// Filters
const filters = ref({
  account_id: null,
  from_date: null,
  to_date: null
})

// Computed
const showEmptyScreen = computed(() => {
  return !isLoadingAccounts.value && connectedAccounts.value.length === 0
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
    const response = await axios.get('/api/v1/banking/accounts')
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

const syncAccount = async (accountId) => {
  try {
    notificationStore.showNotification({
      type: 'info',
      message: t('banking.syncing_account')
    })

    await axios.post(`/api/v1/banking/sync/${accountId}`)

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
  if (!confirm(t('banking.confirm_disconnect'))) {
    return
  }

  try {
    await axios.delete(`/api/v1/banking/accounts/${accountId}`)

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
  // Optionally refresh transactions list
}

const toggleFilters = () => {
  showFilters.value = !showFilters.value
}

const clearFilters = () => {
  filters.value = {
    account_id: null,
    from_date: null,
    to_date: null
  }
}

const formatMoney = (amount, currency) => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('en-US', {
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
