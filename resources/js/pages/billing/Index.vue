<template>
  <div class="billing-page">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Billing & Subscription</h1>
        <p class="text-gray-600 mt-2">Manage your subscription and billing information</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-red-800">{{ error }}</p>
      </div>

      <!-- Content -->
      <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Subscription Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Current Subscription Card -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Current Subscription</h2>

            <div v-if="subscription">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <div class="flex items-center gap-3">
                    <h3 class="text-2xl font-bold text-gray-900">
                      {{ formatPlanName(subscription.plan) }}
                    </h3>
                    <span :class="statusBadgeClass(subscription.status)" class="px-3 py-1 text-xs font-semibold rounded-full">
                      {{ subscription.status.toUpperCase() }}
                    </span>
                  </div>
                  <p class="text-3xl font-bold text-blue-600 mt-2">
                    €{{ subscription.price }}<span class="text-lg text-gray-600">/month</span>
                  </p>
                </div>
              </div>

              <div class="border-t pt-4 mt-4 space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Status:</span>
                  <span class="font-medium">{{ subscription.status }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Next billing date:</span>
                  <span class="font-medium">{{ formatDate(subscription.next_billing_date) }}</span>
                </div>
                <div v-if="subscription.trial_ends_at" class="flex justify-between text-sm">
                  <span class="text-gray-600">Trial ends:</span>
                  <span class="font-medium">{{ formatDate(subscription.trial_ends_at) }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="mt-6 flex flex-wrap gap-3">
                <button
                  v-if="canUpgrade"
                  @click="showUpgradeModal = true"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors"
                >
                  Upgrade Plan
                </button>
                <button
                  @click="updatePaymentMethod"
                  class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors"
                >
                  Update Payment Method
                </button>
                <button
                  v-if="subscription.status === 'active'"
                  @click="showCancelModal = true"
                  class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 font-medium transition-colors"
                >
                  Cancel Subscription
                </button>
                <button
                  v-if="subscription.status === 'canceled'"
                  @click="resumeSubscription"
                  class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors"
                >
                  Resume Subscription
                </button>
              </div>
            </div>

            <div v-else class="text-center py-8">
              <p class="text-gray-600 mb-4">You don't have an active subscription</p>
              <button
                @click="$router.push('/pricing')"
                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors"
              >
                View Plans
              </button>
            </div>
          </div>

          <!-- Billing History -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Billing History</h2>

            <div v-if="invoices && invoices.length > 0" class="overflow-x-auto">
              <table class="w-full">
                <thead class="border-b">
                  <tr class="text-left text-sm text-gray-600">
                    <th class="pb-3 font-medium">Date</th>
                    <th class="pb-3 font-medium">Description</th>
                    <th class="pb-3 font-medium">Amount</th>
                    <th class="pb-3 font-medium">Status</th>
                    <th class="pb-3 font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="invoice in invoices" :key="invoice.id" class="text-sm">
                    <td class="py-3">{{ formatDate(invoice.created_at) }}</td>
                    <td class="py-3">{{ invoice.description }}</td>
                    <td class="py-3 font-medium">€{{ invoice.amount }}</td>
                    <td class="py-3">
                      <span :class="invoiceStatusClass(invoice.status)" class="px-2 py-1 text-xs font-semibold rounded">
                        {{ invoice.status }}
                      </span>
                    </td>
                    <td class="py-3">
                      <button
                        v-if="invoice.pdf_url"
                        @click="downloadInvoice(invoice.pdf_url)"
                        class="text-blue-600 hover:text-blue-700 font-medium"
                      >
                        Download
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="text-center py-8 text-gray-600">
              No billing history yet
            </div>
          </div>
        </div>

        <!-- Right Column: Quick Info -->
        <div class="space-y-6">
          <!-- Plan Features -->
          <div class="bg-blue-50 rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Your Plan Includes</h3>
            <ul class="space-y-2 text-sm text-gray-700">
              <li v-for="feature in currentPlanFeatures" :key="feature" class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ feature }}
              </li>
            </ul>
          </div>

          <!-- Support Card -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Need Help?</h3>
            <p class="text-sm text-gray-600 mb-4">
              Our support team is here to assist you with any billing questions.
            </p>
            <button
              @click="$router.push('/support')"
              class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors"
            >
              Contact Support
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Upgrade Modal -->
    <div v-if="showUpgradeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full p-6">
        <h3 class="text-2xl font-bold text-gray-900 mb-4">Upgrade Your Plan</h3>
        <p class="text-gray-600 mb-6">Choose a higher tier to unlock more features</p>

        <div class="space-y-4">
          <div v-for="plan in availablePlans" :key="plan.name"
               class="border-2 rounded-lg p-4 cursor-pointer hover:border-blue-500 transition-colors"
               :class="{ 'border-blue-500 bg-blue-50': selectedUpgradePlan === plan.name }"
               @click="selectedUpgradePlan = plan.name">
            <div class="flex justify-between items-center">
              <div>
                <h4 class="font-semibold text-gray-900">{{ formatPlanName(plan.name) }}</h4>
                <p class="text-sm text-gray-600">{{ plan.description }}</p>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold text-gray-900">€{{ plan.price }}</p>
                <p class="text-sm text-gray-600">/month</p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex gap-3">
          <button
            @click="showUpgradeModal = false"
            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors"
          >
            Cancel
          </button>
          <button
            @click="confirmUpgrade"
            :disabled="!selectedUpgradePlan || upgrading"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors disabled:opacity-50"
          >
            {{ upgrading ? 'Processing...' : 'Confirm Upgrade' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Cancel Modal -->
    <div v-if="showCancelModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h3 class="text-2xl font-bold text-gray-900 mb-4">Cancel Subscription?</h3>
        <p class="text-gray-600 mb-6">
          Your subscription will remain active until {{ formatDate(subscription?.next_billing_date) }}.
          After that, you'll lose access to premium features.
        </p>

        <div class="mb-4">
          <label class="flex items-start">
            <input type="checkbox" v-model="cancelReason.checked" class="mt-1 mr-2">
            <span class="text-sm text-gray-700">
              I understand that canceling will result in loss of data and features
            </span>
          </label>
        </div>

        <div class="mt-6 flex gap-3">
          <button
            @click="showCancelModal = false"
            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors"
          >
            Keep Subscription
          </button>
          <button
            @click="confirmCancel"
            :disabled="!cancelReason.checked || canceling"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors disabled:opacity-50"
          >
            {{ canceling ? 'Canceling...' : 'Confirm Cancel' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'BillingIndex',
  setup() {
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    const subscription = ref(null)
    const invoices = ref([])
    const showUpgradeModal = ref(false)
    const showCancelModal = ref(false)
    const selectedUpgradePlan = ref(null)
    const upgrading = ref(false)
    const canceling = ref(false)
    const cancelReason = ref({ checked: false })

    const planFeatures = {
      starter: [
        'Unlimited invoices',
        'Bank feed integration',
        '1 company',
        'Basic support'
      ],
      professional: [
        'Everything in Starter',
        '3 companies',
        'QES e-invoices',
        'CASYS payments',
        'Priority support'
      ],
      business: [
        'Everything in Professional',
        '10 companies',
        'Multi-user access',
        'API access',
        'Dedicated support'
      ]
    }

    const availablePlans = [
      { name: 'professional', price: 29, description: '3 companies, e-invoices, CASYS' },
      { name: 'business', price: 59, description: '10 companies, multi-user, API' }
    ]

    const currentPlanFeatures = computed(() => {
      return subscription.value ? planFeatures[subscription.value.plan] || [] : []
    })

    const canUpgrade = computed(() => {
      if (!subscription.value) return false
      const currentPlan = subscription.value.plan
      return currentPlan === 'starter' || currentPlan === 'professional'
    })

    const formatPlanName = (plan) => {
      return plan.charAt(0).toUpperCase() + plan.slice(1)
    }

    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const statusBadgeClass = (status) => {
      const classes = {
        active: 'bg-green-100 text-green-800',
        trial: 'bg-blue-100 text-blue-800',
        past_due: 'bg-red-100 text-red-800',
        canceled: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const invoiceStatusClass = (status) => {
      const classes = {
        paid: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const fetchSubscription = async () => {
      try {
        const response = await fetch('/api/billing/subscription', {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'include'
        })

        if (response.ok) {
          const data = await response.json()
          subscription.value = data.subscription
        }
      } catch (err) {
        console.error('Failed to fetch subscription:', err)
      }
    }

    const fetchInvoices = async () => {
      try {
        const response = await fetch('/api/billing/invoices', {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'include'
        })

        if (response.ok) {
          const data = await response.json()
          invoices.value = data.invoices || []
        }
      } catch (err) {
        console.error('Failed to fetch invoices:', err)
      }
    }

    const updatePaymentMethod = () => {
      // Open Paddle update payment method overlay
      if (window.Paddle && subscription.value) {
        window.Paddle.Checkout.updatePaymentMethod({
          subscriptionId: subscription.value.paddle_subscription_id
        })
      }
    }

    const confirmUpgrade = async () => {
      if (!selectedUpgradePlan.value || upgrading.value) return

      upgrading.value = true
      error.value = null

      try {
        const response = await fetch('/api/billing/subscription/swap', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
          },
          credentials: 'include',
          body: JSON.stringify({ plan: selectedUpgradePlan.value })
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to upgrade plan')
        }

        await fetchSubscription()
        showUpgradeModal.value = false
        selectedUpgradePlan.value = null
      } catch (err) {
        error.value = err.message
      } finally {
        upgrading.value = false
      }
    }

    const confirmCancel = async () => {
      if (!cancelReason.value.checked || canceling.value) return

      canceling.value = true
      error.value = null

      try {
        const response = await fetch('/api/billing/subscription', {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
          },
          credentials: 'include'
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to cancel subscription')
        }

        await fetchSubscription()
        showCancelModal.value = false
        cancelReason.value.checked = false
      } catch (err) {
        error.value = err.message
      } finally {
        canceling.value = false
      }
    }

    const resumeSubscription = async () => {
      try {
        const response = await fetch('/api/billing/subscription/resume', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
          },
          credentials: 'include'
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to resume subscription')
        }

        await fetchSubscription()
      } catch (err) {
        error.value = err.message
      }
    }

    const downloadInvoice = (url) => {
      window.open(url, '_blank')
    }

    onMounted(async () => {
      try {
        await Promise.all([fetchSubscription(), fetchInvoices()])
      } catch (err) {
        error.value = 'Failed to load billing information'
      } finally {
        loading.value = false
      }
    })

    return {
      loading,
      error,
      subscription,
      invoices,
      showUpgradeModal,
      showCancelModal,
      selectedUpgradePlan,
      upgrading,
      canceling,
      cancelReason,
      currentPlanFeatures,
      canUpgrade,
      availablePlans,
      formatPlanName,
      formatDate,
      statusBadgeClass,
      invoiceStatusClass,
      updatePaymentMethod,
      confirmUpgrade,
      confirmCancel,
      resumeSubscription,
      downloadInvoice
    }
  }
}
</script>

<style scoped>
.billing-page {
  @apply min-h-screen bg-gray-50;
}
</style>
// CLAUDE-CHECKPOINT
