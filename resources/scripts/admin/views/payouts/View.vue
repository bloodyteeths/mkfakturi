<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('payouts.details')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payouts.title')" to="/admin/payouts" />
        <BaseBreadcrumbItem :title="`#${payoutId}`" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex flex-wrap items-center justify-end gap-3">
          <BaseButton
            v-if="payout && (payout.status === 'pending' || payout.status === 'processing')"
            @click="openMarkPaidModal"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckCircleIcon" :class="slotProps.class" />
            </template>
            {{ $t('payouts.mark_as_paid') }}
          </BaseButton>

          <BaseButton
            v-if="payout && (payout.status === 'pending' || payout.status === 'processing')"
            variant="warning-outline"
            @click="openMarkFailedModal"
          >
            <template #left="slotProps">
              <BaseIcon name="ExclamationTriangleIcon" :class="slotProps.class" />
            </template>
            {{ $t('payouts.mark_as_failed') }}
          </BaseButton>

          <BaseButton
            v-if="payout && (payout.status === 'pending' || payout.status === 'processing')"
            variant="danger-outline"
            @click="openCancelModal"
          >
            <template #left="slotProps">
              <BaseIcon name="XCircleIcon" :class="slotProps.class" />
            </template>
            {{ $t('payouts.cancel_payout') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="text-gray-400">{{ $t('payouts.loading') }}</div>
    </div>

    <div v-else-if="payout" class="mt-6 space-y-6">
      <!-- Payout Info + Partner Bank Details -->
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Payout Info Card -->
        <div class="p-6 bg-white border border-gray-200 rounded-lg">
          <h3 class="mb-4 text-lg font-medium text-gray-900">{{ $t('payouts.payout_info') }}</h3>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.amount') }}</dt>
              <dd class="text-sm font-medium text-gray-900">
                <BaseFormatMoney
                  :amount="payout.amount || 0"
                  :currency="mkdCurrency"
                />
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.status') }}</dt>
              <dd>
                <span
                  class="px-2 py-1 text-xs font-medium rounded"
                  :class="{
                    'bg-yellow-100 text-yellow-800': payout.status === 'pending',
                    'bg-blue-100 text-blue-800': payout.status === 'processing',
                    'bg-green-100 text-green-800': payout.status === 'completed',
                    'bg-red-100 text-red-800': payout.status === 'failed',
                    'bg-gray-100 text-gray-800': payout.status === 'cancelled',
                  }"
                >
                  {{ $t(`payouts.${payout.status}`) }}
                </span>
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.method') }}</dt>
              <dd class="text-sm text-gray-900">
                {{ payout.payout_method === 'stripe_connect' ? $t('payouts.stripe_connect') : $t('payouts.bank_transfer') }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.payout_date') }}</dt>
              <dd class="text-sm text-gray-900">
                {{ payout.payout_date ? new Date(payout.payout_date).toLocaleDateString() : '-' }}
              </dd>
            </div>
            <div v-if="payout.payment_reference" class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.payment_reference') }}</dt>
              <dd class="text-sm font-mono text-gray-900">{{ payout.payment_reference }}</dd>
            </div>
            <div v-if="payout.processed_at" class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.processed_at') }}</dt>
              <dd class="text-sm text-gray-900">{{ new Date(payout.processed_at).toLocaleString() }}</dd>
            </div>
            <div v-if="payout.processor" class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.processed_by') }}</dt>
              <dd class="text-sm text-gray-900">{{ payout.processor.name }}</dd>
            </div>
          </dl>
        </div>

        <!-- Partner Bank Details Card -->
        <div class="p-6 bg-white border border-gray-200 rounded-lg">
          <h3 class="mb-4 text-lg font-medium text-gray-900">{{ $t('payouts.partner_bank_details') }}</h3>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.partner') }}</dt>
              <dd class="text-sm text-gray-900">
                <router-link
                  v-if="payout.partner"
                  :to="`/admin/partners/${payout.partner_id}/view`"
                  class="font-medium text-primary-500"
                >
                  {{ payout.partner_name }}
                </router-link>
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.email') }}</dt>
              <dd class="text-sm text-gray-900">{{ payout.partner_email || $t('payouts.not_provided') }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.bank_name') }}</dt>
              <dd class="text-sm text-gray-900">{{ payout.partner_bank_name || $t('payouts.not_provided') }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">{{ $t('payouts.account_iban') }}</dt>
              <dd class="text-sm font-mono text-gray-900">{{ payout.partner_bank_account || $t('payouts.not_provided') }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Commission Events Table -->
      <div class="p-6 bg-white border border-gray-200 rounded-lg">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          {{ $t('payouts.commission_events') }}
          <span class="ml-2 text-sm font-normal text-gray-400">
            ({{ payout.events?.length || 0 }})
          </span>
        </h3>

        <div v-if="payout.events && payout.events.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">{{ $t('payouts.event_type') }}</th>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">{{ $t('payouts.event_company') }}</th>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">{{ $t('payouts.amount') }}</th>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">{{ $t('payouts.event_month') }}</th>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">{{ $t('payouts.date') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="event in payout.events" :key="event.id">
                <td class="px-3 py-2 text-sm">
                  <span
                    class="px-2 py-0.5 text-xs font-medium rounded"
                    :class="{
                      'bg-green-100 text-green-800': event.event_type === 'recurring_commission',
                      'bg-blue-100 text-blue-800': event.event_type === 'company_bounty',
                      'bg-purple-100 text-purple-800': event.event_type === 'partner_bounty',
                      'bg-red-100 text-red-800': event.event_type === 'clawback',
                    }"
                  >
                    {{ event.event_type.replace(/_/g, ' ') }}
                  </span>
                </td>
                <td class="px-3 py-2 text-sm text-gray-700">
                  {{ event.company?.name || '-' }}
                </td>
                <td class="px-3 py-2 text-sm text-right font-mono">
                  <BaseFormatMoney
                    :amount="event.amount || 0"
                    :currency="mkdCurrency"
                  />
                </td>
                <td class="px-3 py-2 text-sm text-gray-600">{{ event.month_ref || '-' }}</td>
                <td class="px-3 py-2 text-sm text-gray-600">
                  {{ event.created_at ? new Date(event.created_at).toLocaleDateString() : '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="py-8 text-center text-gray-400 text-sm">
          {{ $t('payouts.no_events') }}
        </div>
      </div>
    </div>

    <!-- Mark as Paid Modal -->
    <BaseModal :show="showPayModal" @close="showPayModal = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ $t('payouts.mark_as_paid') }}</h3>
      </template>

      <div class="p-4">
        <p class="mb-4 text-sm text-gray-600">
          {{ $t('payouts.confirm_mark_paid', { name: payout?.partner_name, amount: formatMkd(payout?.amount) }) }}
        </p>

        <BaseInputGroup :label="$t('payouts.payment_reference')" class="text-left">
          <BaseInput
            v-model="paymentReference"
            type="text"
            :placeholder="$t('payouts.payment_ref_placeholder')"
          />
        </BaseInputGroup>
      </div>

      <template #footer>
        <BaseButton variant="primary-outline" class="mr-3" @click="showPayModal = false">
          {{ $t('payouts.cancel') }}
        </BaseButton>
        <BaseButton
          :loading="isProcessing"
          :disabled="!paymentReference.trim()"
          @click="confirmMarkPaid"
        >
          {{ $t('payouts.confirm_payment') }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Mark as Failed Modal -->
    <BaseModal :show="showFailModal" @close="showFailModal = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ $t('payouts.mark_as_failed') }}</h3>
      </template>

      <div class="p-4">
        <p class="mb-4 text-sm text-gray-600">
          {{ $t('payouts.confirm_mark_failed', { name: payout?.partner_name, amount: formatMkd(payout?.amount) }) }}
        </p>

        <BaseInputGroup :label="$t('payouts.failure_reason')" class="text-left">
          <BaseInput
            v-model="failReason"
            type="text"
            :placeholder="$t('payouts.failure_reason_placeholder')"
          />
        </BaseInputGroup>
      </div>

      <template #footer>
        <BaseButton variant="primary-outline" class="mr-3" @click="showFailModal = false">
          {{ $t('payouts.cancel') }}
        </BaseButton>
        <BaseButton
          variant="danger"
          :loading="isProcessing"
          :disabled="!failReason.trim()"
          @click="confirmMarkFailed"
        >
          {{ $t('payouts.mark_as_failed') }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Cancel Payout Modal -->
    <BaseModal :show="showCancelModal" @close="showCancelModal = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ $t('payouts.cancel_payout') }}</h3>
      </template>

      <div class="p-4">
        <p class="mb-4 text-sm text-gray-600">
          {{ $t('payouts.confirm_cancel', { name: payout?.partner_name, amount: formatMkd(payout?.amount) }) }}
        </p>

        <BaseInputGroup :label="$t('payouts.cancel_reason')" class="text-left">
          <BaseInput
            v-model="cancelReason"
            type="text"
            :placeholder="$t('payouts.cancel_reason_placeholder')"
          />
        </BaseInputGroup>
      </div>

      <template #footer>
        <BaseButton variant="primary-outline" class="mr-3" @click="showCancelModal = false">
          {{ $t('payouts.cancel') }}
        </BaseButton>
        <BaseButton
          variant="danger"
          :loading="isProcessing"
          :disabled="!cancelReason.trim()"
          @click="confirmCancel"
        >
          {{ $t('payouts.cancel_payout') }}
        </BaseButton>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const globalStore = useGlobalStore()

const mkdCurrency = { id: 0, name: 'Macedonian Denar', code: 'MKD', symbol: 'ден', precision: 2, thousand_separator: '.', decimal_separator: ',' }

function formatMkd(amount) {
  return `${parseFloat(amount || 0).toLocaleString('mk-MK', { minimumFractionDigits: 2 })} ден`
}

const payoutId = route.params.id
const payout = ref(null)
const isLoading = ref(true)
const showPayModal = ref(false)
const paymentReference = ref('')
const isProcessing = ref(false)
const showFailModal = ref(false)
const failReason = ref('')
const showCancelModal = ref(false)
const cancelReason = ref('')

async function fetchPayout() {
  isLoading.value = true
  try {
    const response = await axios.get(`/payouts/${payoutId}`)
    payout.value = response.data
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Failed to load payout details.',
    })
    router.push('/admin/payouts')
  } finally {
    isLoading.value = false
  }
}

function openMarkPaidModal() {
  paymentReference.value = ''
  showPayModal.value = true
}

async function confirmMarkPaid() {
  if (!paymentReference.value.trim()) return

  isProcessing.value = true
  try {
    await axios.post(`/payouts/${payoutId}/complete`, {
      payment_reference: paymentReference.value,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('payouts.mark_as_paid'),
    })
    showPayModal.value = false
    fetchPayout()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('payouts.mark_as_failed'),
    })
  } finally {
    isProcessing.value = false
  }
}

function openMarkFailedModal() {
  failReason.value = ''
  showFailModal.value = true
}

async function confirmMarkFailed() {
  if (!failReason.value.trim()) return

  isProcessing.value = true
  try {
    await axios.post(`/payouts/${payoutId}/fail`, {
      reason: failReason.value,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('payouts.mark_as_failed'),
    })
    showFailModal.value = false
    fetchPayout()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('payouts.mark_as_failed'),
    })
  } finally {
    isProcessing.value = false
  }
}

function openCancelModal() {
  cancelReason.value = ''
  showCancelModal.value = true
}

async function confirmCancel() {
  if (!cancelReason.value.trim()) return

  isProcessing.value = true
  try {
    await axios.post(`/payouts/${payoutId}/cancel`, {
      reason: cancelReason.value,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('payouts.cancel_payout'),
    })
    showCancelModal.value = false
    fetchPayout()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('payouts.cancel_payout'),
    })
  } finally {
    isProcessing.value = false
  }
}

onMounted(() => {
  fetchPayout()
})
</script>
