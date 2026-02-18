<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader title="Payout Details">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem title="Payouts" to="/admin/payouts" />
        <BaseBreadcrumbItem :title="`#${payoutId}`" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-3">
          <BaseButton
            v-if="payout && (payout.status === 'pending' || payout.status === 'processing')"
            @click="openMarkPaidModal"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckCircleIcon" :class="slotProps.class" />
            </template>
            Mark as Paid
          </BaseButton>

          <BaseButton
            v-if="payout && payout.status === 'pending'"
            variant="danger-outline"
            @click="cancelPayout"
          >
            <template #left="slotProps">
              <BaseIcon name="XCircleIcon" :class="slotProps.class" />
            </template>
            Cancel Payout
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="text-gray-400">Loading...</div>
    </div>

    <div v-else-if="payout" class="mt-6 space-y-6">
      <!-- Payout Info + Partner Bank Details -->
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Payout Info Card -->
        <div class="p-6 bg-white border border-gray-200 rounded-lg">
          <h3 class="mb-4 text-lg font-medium text-gray-900">Payout Info</h3>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Amount</dt>
              <dd class="text-sm font-medium text-gray-900">
                <span v-if="globalStore.companySettings?.currency">
                  <BaseFormatMoney
                    :amount="payout.amount || 0"
                    :currency="globalStore.companySettings.currency"
                  />
                </span>
                <span v-else>{{ payout.amount }} {{ payout.currency || 'MKD' }}</span>
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Status</dt>
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
                  {{ payout.status }}
                </span>
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Method</dt>
              <dd class="text-sm text-gray-900">
                {{ payout.payout_method === 'stripe_connect' ? 'Stripe Connect' : 'Bank Transfer' }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Payout Date</dt>
              <dd class="text-sm text-gray-900">
                {{ payout.payout_date ? new Date(payout.payout_date).toLocaleDateString() : '-' }}
              </dd>
            </div>
            <div v-if="payout.payment_reference" class="flex justify-between">
              <dt class="text-sm text-gray-500">Payment Reference</dt>
              <dd class="text-sm font-mono text-gray-900">{{ payout.payment_reference }}</dd>
            </div>
            <div v-if="payout.processed_at" class="flex justify-between">
              <dt class="text-sm text-gray-500">Processed At</dt>
              <dd class="text-sm text-gray-900">{{ new Date(payout.processed_at).toLocaleString() }}</dd>
            </div>
            <div v-if="payout.processor" class="flex justify-between">
              <dt class="text-sm text-gray-500">Processed By</dt>
              <dd class="text-sm text-gray-900">{{ payout.processor.name }}</dd>
            </div>
          </dl>
        </div>

        <!-- Partner Bank Details Card -->
        <div class="p-6 bg-white border border-gray-200 rounded-lg">
          <h3 class="mb-4 text-lg font-medium text-gray-900">Partner Bank Details</h3>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Partner</dt>
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
              <dt class="text-sm text-gray-500">Email</dt>
              <dd class="text-sm text-gray-900">{{ payout.partner_email }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Bank Name</dt>
              <dd class="text-sm text-gray-900">{{ payout.partner_bank_name }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Account (IBAN)</dt>
              <dd class="text-sm font-mono text-gray-900">{{ payout.partner_bank_account }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Commission Events Table -->
      <div class="p-6 bg-white border border-gray-200 rounded-lg">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          Commission Events
          <span class="ml-2 text-sm font-normal text-gray-400">
            ({{ payout.events?.length || 0 }} events)
          </span>
        </h3>

        <div v-if="payout.events && payout.events.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Type</th>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Company</th>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Amount</th>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Month</th>
                <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date</th>
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
                  <span v-if="globalStore.companySettings?.currency">
                    <BaseFormatMoney
                      :amount="event.amount || 0"
                      :currency="globalStore.companySettings.currency"
                    />
                  </span>
                  <span v-else>{{ event.amount }}</span>
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
          No commission events linked to this payout.
        </div>
      </div>
    </div>

    <!-- Mark as Paid Modal -->
    <BaseModal :show="showPayModal" @close="showPayModal = false">
      <template #header>
        <h3 class="text-lg font-medium">Mark Payout as Paid</h3>
      </template>

      <div class="p-4">
        <p class="mb-4 text-sm text-gray-600">
          Confirm payment of <strong>{{ payout?.amount }} {{ payout?.currency || 'MKD' }}</strong>
          to <strong>{{ payout?.partner_name }}</strong>
        </p>

        <BaseInputGroup label="Payment Reference (SEPA / Transaction ID)" class="text-left">
          <BaseInput
            v-model="paymentReference"
            type="text"
            placeholder="e.g. SEPA-2026-02-001"
          />
        </BaseInputGroup>
      </div>

      <template #footer>
        <BaseButton variant="primary-outline" class="mr-3" @click="showPayModal = false">
          Cancel
        </BaseButton>
        <BaseButton
          :loading="isProcessing"
          :disabled="!paymentReference.trim()"
          @click="confirmMarkPaid"
        >
          Confirm Payment
        </BaseButton>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const globalStore = useGlobalStore()

const payoutId = route.params.id
const payout = ref(null)
const isLoading = ref(true)
const showPayModal = ref(false)
const paymentReference = ref('')
const isProcessing = ref(false)

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
      message: 'Payout marked as completed.',
    })
    showPayModal.value = false
    fetchPayout()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || 'Failed to mark payout as completed.',
    })
  } finally {
    isProcessing.value = false
  }
}

async function cancelPayout() {
  if (!confirm('Cancel this payout? Commission events will be released back to unpaid.')) return

  try {
    await axios.post(`/payouts/${payoutId}/cancel`, {
      reason: 'Cancelled by admin',
    })
    notificationStore.showNotification({
      type: 'success',
      message: 'Payout cancelled.',
    })
    fetchPayout()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || 'Failed to cancel payout.',
    })
  }
}

onMounted(() => {
  fetchPayout()
})
</script>
