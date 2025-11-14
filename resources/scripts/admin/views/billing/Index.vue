<template>
  <BasePage>
    <BasePageHeader title="Billing & Subscription">
      <template #actions>
        <BaseButton
          v-if="!subscription || subscription.status === 'canceled'"
          variant="primary"
          @click="$router.push({ name: 'billing.pricing' })"
        >
          <template #left>
            <CreditCardIcon class="h-5 w-5" />
          </template>
          Subscribe Now
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <BaseSpinner class="h-8 w-8" />
      <p class="ml-3 text-gray-600">Loading subscription details...</p>
    </div>

    <!-- Error State -->
    <BaseAlert v-if="error" type="danger" class="mb-6">
      {{ error }}
    </BaseAlert>

    <div v-if="!isLoading">
      <!-- Current Subscription Card -->
      <BaseCard v-if="subscription" class="mb-6">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Current Subscription</h2>
              <p class="text-sm text-gray-500 mt-1">Manage your subscription and billing</p>
            </div>
            <BaseBadge
              :variant="getStatusVariant(subscription.status)"
              size="lg"
            >
              {{ formatStatus(subscription.status) }}
            </BaseBadge>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
              <p class="text-sm text-gray-500">Plan</p>
              <div class="flex items-center mt-1">
                <BaseBadge :variant="getPlanVariant(subscription.plan_name)" class="mr-2">
                  {{ subscription.plan_name }}
                </BaseBadge>
                <span class="text-2xl font-bold text-gray-900">
                  €{{ subscription.price }}
                </span>
                <span class="text-gray-500 ml-1">/month</span>
              </div>
            </div>

            <div v-if="subscription.next_billing_date">
              <p class="text-sm text-gray-500">Next Billing Date</p>
              <p class="text-lg font-semibold text-gray-900 mt-1">
                {{ formatDate(subscription.next_billing_date) }}
              </p>
            </div>

            <div v-if="subscription.trial_ends_at">
              <p class="text-sm text-gray-500">Trial Ends</p>
              <p class="text-lg font-semibold text-gray-900 mt-1">
                {{ formatDate(subscription.trial_ends_at) }}
              </p>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
            <BaseButton
              v-if="canUpgrade"
              variant="primary"
              @click="$router.push({ name: 'billing.pricing' })"
            >
              <template #left>
                <ArrowUpIcon class="h-5 w-5" />
              </template>
              Upgrade Plan
            </BaseButton>

            <BaseButton
              variant="white"
              @click="openUpdatePaymentMethod"
            >
              <template #left>
                <CreditCardIcon class="h-5 w-5" />
              </template>
              Update Payment Method
            </BaseButton>

            <BaseButton
              v-if="subscription.status === 'active'"
              variant="danger-outline"
              @click="showCancelModal = true"
            >
              <template #left>
                <XCircleIcon class="h-5 w-5" />
              </template>
              Cancel Subscription
            </BaseButton>

            <BaseButton
              v-if="subscription.status === 'canceled' && subscription.ends_at"
              variant="primary"
              @click="resumeSubscription"
              :disabled="isProcessing"
            >
              Resume Subscription
            </BaseButton>
          </div>
        </div>
      </BaseCard>

      <!-- No Subscription State -->
      <BaseCard v-else class="mb-6">
        <div class="p-12 text-center">
          <CreditCardIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-lg font-medium text-gray-900">No Active Subscription</h3>
          <p class="mt-1 text-sm text-gray-500">
            Subscribe to a plan to unlock all features
          </p>
          <div class="mt-6">
            <BaseButton
              variant="primary"
              @click="$router.push({ name: 'billing.pricing' })"
            >
              View Plans
            </BaseButton>
          </div>
        </div>
      </BaseCard>

      <!-- Billing History -->
      <BaseCard>
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing History</h3>

          <!-- Invoices Table -->
          <div v-if="invoices.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Description
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Amount
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="invoice in invoices" :key="invoice.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(invoice.date) }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    {{ invoice.description }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    €{{ invoice.amount }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <BaseBadge :variant="getInvoiceStatusVariant(invoice.status)">
                      {{ invoice.status }}
                    </BaseBadge>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a
                      v-if="invoice.pdf_url"
                      :href="invoice.pdf_url"
                      target="_blank"
                      class="text-primary-600 hover:text-primary-900"
                    >
                      Download PDF
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-8">
            <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500">No invoices yet</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Cancel Subscription Modal -->
    <BaseDialog v-model="showCancelModal" title="Cancel Subscription">
      <div class="space-y-4">
        <p class="text-sm text-gray-700">
          Are you sure you want to cancel your subscription? You will lose access to premium features at the end of your billing period.
        </p>
        <p class="text-sm text-gray-500">
          Your subscription will remain active until {{ formatDate(subscription?.next_billing_date) }}.
        </p>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <BaseButton
            variant="white"
            @click="showCancelModal = false"
          >
            Keep Subscription
          </BaseButton>
          <BaseButton
            variant="danger"
            :disabled="isProcessing"
            @click="cancelSubscription"
          >
            Cancel Subscription
          </BaseButton>
        </div>
      </template>
    </BaseDialog>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  CreditCardIcon,
  ArrowUpIcon,
  XCircleIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'

// Import base components
import BasePage from '@/scripts/components/base/BasePage.vue'
import BasePageHeader from '@/scripts/components/base/BasePageHeader.vue'
import BaseCard from '@/scripts/components/base/BaseCard.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseBadge from '@/scripts/components/base/BaseBadge.vue'
import BaseAlert from '@/scripts/components/base/BaseAlert.vue'
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'
import BaseDialog from '@/scripts/components/base/BaseDialog.vue'

const router = useRouter()
const isLoading = ref(true)
const isProcessing = ref(false)
const error = ref(null)
const subscription = ref(null)
const invoices = ref([])
const showCancelModal = ref(false)

// Paddle configuration
const paddleClientToken = import.meta.env.VITE_PADDLE_CLIENT_TOKEN
const paddleSandbox = import.meta.env.VITE_PADDLE_SANDBOX === 'true'

// Fetch subscription data
const fetchSubscription = async () => {
  try {
    const response = await axios.get('/api/billing/subscription')
    subscription.value = response.data.data
  } catch (err) {
    if (err.response?.status !== 404) {
      error.value = 'Failed to load subscription details'
      console.error('Failed to fetch subscription:', err)
    }
  }
}

// Fetch invoices
const fetchInvoices = async () => {
  try {
    const response = await axios.get('/api/billing/invoices')
    invoices.value = response.data.data || []
  } catch (err) {
    console.error('Failed to fetch invoices:', err)
  } finally {
    isLoading.value = false
  }
}

// Format date
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

// Format status
const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
}

// Get status badge variant
const getStatusVariant = (status) => {
  const variants = {
    active: 'success',
    trialing: 'info',
    past_due: 'warning',
    canceled: 'danger',
    paused: 'secondary',
  }
  return variants[status] || 'secondary'
}

// Get plan badge variant
const getPlanVariant = (plan) => {
  const variants = {
    starter: 'secondary',
    professional: 'primary',
    business: 'success',
  }
  return variants[plan?.toLowerCase()] || 'secondary'
}

// Get invoice status variant
const getInvoiceStatusVariant = (status) => {
  const variants = {
    paid: 'success',
    pending: 'warning',
    failed: 'danger',
  }
  return variants[status?.toLowerCase()] || 'secondary'
}

// Check if can upgrade
const canUpgrade = computed(() => {
  if (!subscription.value) return false
  const plan = subscription.value.plan_name?.toLowerCase()
  return plan === 'starter' || plan === 'professional'
})

// Initialize Paddle
const initializePaddle = async () => {
  if (!window.Paddle) {
    const script = document.createElement('script')
    script.src = 'https://cdn.paddle.com/paddle/v2/paddle.js'
    script.async = true

    await new Promise((resolve, reject) => {
      script.onload = resolve
      script.onerror = reject
      document.head.appendChild(script)
    })
  }

  if (window.Paddle) {
    window.Paddle.Environment.set(paddleSandbox ? 'sandbox' : 'production')
    window.Paddle.Initialize({
      token: paddleClientToken,
    })
  }
}

// Open update payment method
const openUpdatePaymentMethod = async () => {
  try {
    await initializePaddle()

    if (!window.Paddle) {
      throw new Error('Paddle not initialized')
    }

    // Open Paddle update payment method overlay
    window.Paddle.PaymentMethod.update({
      subscriptionId: subscription.value.paddle_subscription_id,
    })
  } catch (err) {
    console.error('Failed to open payment method update:', err)
    error.value = 'Failed to open payment method update. Please try again.'
  }
}

// Cancel subscription
const cancelSubscription = async () => {
  isProcessing.value = true
  error.value = null

  try {
    await axios.delete('/api/billing/subscription')
    showCancelModal.value = false

    // Refresh subscription data
    await fetchSubscription()

    // Show success message (you can add a toast notification here)
    alert('Subscription canceled successfully')
  } catch (err) {
    console.error('Failed to cancel subscription:', err)
    error.value = err.response?.data?.message || 'Failed to cancel subscription'
  } finally {
    isProcessing.value = false
  }
}

// Resume subscription
const resumeSubscription = async () => {
  isProcessing.value = true
  error.value = null

  try {
    await axios.post('/api/billing/subscription/resume')

    // Refresh subscription data
    await fetchSubscription()

    // Show success message
    alert('Subscription resumed successfully')
  } catch (err) {
    console.error('Failed to resume subscription:', err)
    error.value = err.response?.data?.message || 'Failed to resume subscription'
  } finally {
    isProcessing.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchSubscription(), fetchInvoices()])
})
</script>

<!-- CLAUDE-CHECKPOINT -->
