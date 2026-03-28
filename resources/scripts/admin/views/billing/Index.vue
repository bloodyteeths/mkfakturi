<template>
  <BasePage>
    <BasePageHeader :title="$t('billing.title')">
      <template #actions>
        <BaseButton
          v-if="!subscription || subscription.status === 'canceled'"
          variant="primary"
          @click="$router.push({ name: 'billing.pricing' })"
        >
          <template #left>
            <CreditCardIcon class="h-5 w-5" />
          </template>
          {{ $t('billing.subscribe_now') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <BaseSpinner class="h-8 w-8" />
      <p class="ml-3 text-gray-600">{{ $t('billing.loading_subscription') }}</p>
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
              <h2 class="text-xl font-semibold text-gray-900">{{ $t('billing.current_subscription') }}</h2>
              <p class="text-sm text-gray-500 mt-1">{{ $t('billing.manage_subscription') }}</p>
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
              <p class="text-sm text-gray-500">{{ $t('billing.plan') }}</p>
              <div class="flex items-center mt-1">
                <BaseBadge :variant="getPlanVariant(subscription.tier)" class="mr-2">
                  {{ formatTierName(subscription.tier) }}
                </BaseBadge>
                <span class="text-2xl font-bold text-gray-900">
                  {{ formatSubscriptionPrice(subscription) }}
                </span>
                <span class="text-gray-500 ml-1">{{ $t('billing.per_month') }}</span>
              </div>
            </div>

            <div v-if="subscription.next_billing_date">
              <p class="text-sm text-gray-500">{{ $t('billing.next_billing_date') }}</p>
              <p class="text-lg font-semibold text-gray-900 mt-1">
                {{ formatDate(subscription.next_billing_date) }}
              </p>
            </div>

            <div v-if="subscription.trial_ends_at">
              <p class="text-sm text-gray-500">{{ $t('billing.trial_ends') }}</p>
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
              {{ $t('billing.upgrade_plan') }}
            </BaseButton>

            <BaseButton
              variant="white"
              @click="openStripePortal"
              :disabled="isProcessing"
            >
              <template #left>
                <CreditCardIcon class="h-5 w-5" />
              </template>
              {{ $t('billing.update_payment_method') }}
            </BaseButton>

            <BaseButton
              v-if="subscription.status === 'active' || subscription.status === 'trial'"
              variant="danger-outline"
              @click="showCancelModal = true"
            >
              <template #left>
                <XCircleIcon class="h-5 w-5" />
              </template>
              {{ $t('billing.cancel_subscription') }}
            </BaseButton>

            <BaseButton
              v-if="subscription.status === 'canceled'"
              variant="primary"
              @click="resumeSubscription"
              :disabled="isProcessing"
            >
              {{ $t('billing.resume_subscription') }}
            </BaseButton>
          </div>
        </div>
      </BaseCard>

      <!-- No Subscription State -->
      <BaseCard v-else class="mb-6">
        <div class="p-12 text-center">
          <CreditCardIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-lg font-medium text-gray-900">{{ $t('billing.no_active_subscription') }}</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ $t('billing.subscribe_to_unlock') }}
          </p>
          <div class="mt-6">
            <BaseButton
              variant="primary"
              @click="$router.push({ name: 'billing.pricing' })"
            >
              {{ $t('billing.view_plans') }}
            </BaseButton>
          </div>
        </div>
      </BaseCard>

      <!-- Billing History -->
      <BaseCard>
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('billing.billing_history') }}</h3>

          <!-- Invoices Table -->
          <div v-if="invoices.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('billing.date') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('billing.invoice_number') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('billing.description') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('billing.amount') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('billing.status') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('billing.actions') }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="invoice in invoices" :key="invoice.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(invoice.date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {{ invoice.invoice_number || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    {{ invoice.description }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ invoice.currency === 'MKD' ? `${invoice.amount} ден` : `€${invoice.amount}` }}
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
                      {{ $t('billing.download_pdf') }}
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-8">
            <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500">{{ $t('billing.no_invoices_yet') }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Cancel Subscription Modal -->
    <BaseDialog v-model="showCancelModal" :title="$t('billing.cancel_modal_title')">
      <div class="space-y-4">
        <p class="text-sm text-gray-700">
          {{ $t('billing.cancel_modal_text') }}
        </p>
        <p v-if="subscription?.next_billing_date" class="text-sm text-gray-500">
          {{ $t('billing.subscription_active_until', { date: formatDate(subscription.next_billing_date) }) }}
        </p>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <BaseButton
            variant="white"
            @click="showCancelModal = false"
          >
            {{ $t('billing.keep_subscription') }}
          </BaseButton>
          <BaseButton
            variant="danger"
            :disabled="isProcessing"
            @click="cancelSubscription"
          >
            {{ $t('billing.cancel_subscription') }}
          </BaseButton>
        </div>
      </template>
    </BaseDialog>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  CreditCardIcon,
  ArrowUpIcon,
  XCircleIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'

import BasePage from '@/scripts/components/base/BasePage.vue'
import BasePageHeader from '@/scripts/components/base/BasePageHeader.vue'
import BaseCard from '@/scripts/components/base/BaseCard.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseBadge from '@/scripts/components/base/BaseBadge.vue'
import BaseAlert from '@/scripts/components/base/BaseAlert.vue'
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'
import BaseDialog from '@/scripts/components/base/BaseDialog.vue'

const TIER_PRICES = {
  free: { eur: 0, mkd: 0 },
  starter: { eur: 12, mkd: 740 },
  standard: { eur: 39, mkd: 2400 },
  business: { eur: 59, mkd: 3630 },
  max: { eur: 149, mkd: 9170 },
}

const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()
const isLoading = ref(true)
const isProcessing = ref(false)
const error = ref(null)
const subscription = ref(null)
const invoices = ref([])
const showCancelModal = ref(false)

const companyId = computed(() => companyStore.selectedCompany?.id)

// Fetch subscription data
const fetchSubscription = async () => {
  try {
    const response = await axios.get('/api/billing/subscription')
    // Backend returns { tiers, current_plan, stripe_customer_id }
    const data = response.data
    if (data.current_plan && data.current_plan.status !== 'none') {
      subscription.value = {
        tier: data.current_plan.tier,
        status: data.current_plan.status,
        next_billing_date: data.current_plan.ends_at,
        trial_ends_at: data.current_plan.trial_ends_at,
        stripe_customer_id: data.stripe_customer_id,
      }
    } else {
      subscription.value = null
    }
  } catch (err) {
    if (err.response?.status !== 404) {
      error.value = t('billing.error_load_subscription')
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

// Format date — MK locale (dd.MM.yyyy)
const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}.${month}.${year}`
}

// Format tier display name
const formatTierName = (tier) => {
  const names = {
    free: 'Free',
    starter: 'Starter',
    standard: 'Standard',
    business: 'Business',
    max: 'Max',
  }
  return names[tier] || tier
}

// Format subscription price with currency
const formatSubscriptionPrice = (sub) => {
  const tierPrices = TIER_PRICES[sub.tier]
  if (!tierPrices) return '€0'
  // Show both EUR and MKD
  return `€${tierPrices.eur} (${tierPrices.mkd.toLocaleString()} ден)`
}

// Format status
const formatStatus = (status) => {
  const labels = {
    active: 'Активен',
    trial: 'Пробен период',
    trialing: 'Пробен период',
    past_due: 'Доцнење',
    canceled: 'Откажан',
    paused: 'Паузиран',
    none: 'Неактивен',
  }
  return labels[status] || status.charAt(0).toUpperCase() + status.slice(1)
}

// Badge variants
const getStatusVariant = (status) => {
  const variants = {
    active: 'success',
    trial: 'info',
    trialing: 'info',
    past_due: 'warning',
    canceled: 'danger',
    paused: 'secondary',
  }
  return variants[status] || 'secondary'
}

const getPlanVariant = (tier) => {
  const variants = {
    free: 'secondary',
    starter: 'secondary',
    standard: 'primary',
    business: 'success',
    max: 'warning',
  }
  return variants[tier?.toLowerCase()] || 'secondary'
}

const getInvoiceStatusVariant = (status) => {
  const variants = {
    paid: 'success',
    pending: 'warning',
    failed: 'danger',
  }
  return variants[status?.toLowerCase()] || 'secondary'
}

// Can upgrade check — aligned with backend tiers
const canUpgrade = computed(() => {
  if (!subscription.value) return false
  const tier = subscription.value.tier
  return tier === 'free' || tier === 'starter' || tier === 'standard' || tier === 'business'
})

// Open Stripe Customer Portal for payment method updates
const openStripePortal = async () => {
  isProcessing.value = true
  error.value = null

  try {
    if (!companyId.value) {
      throw new Error('No company selected')
    }
    const response = await axios.get(`/api/v1/companies/${companyId.value}/subscription/manage`)
    if (response.data.portal_url) {
      window.location.href = response.data.portal_url
    }
  } catch (err) {
    console.error('Failed to open Stripe portal:', err)
    error.value = t('billing.error_payment_method')
  } finally {
    isProcessing.value = false
  }
}

// Cancel subscription — uses company-scoped route
const cancelSubscription = async () => {
  isProcessing.value = true
  error.value = null

  try {
    if (!companyId.value) {
      throw new Error('No company selected')
    }
    await axios.post(`/api/v1/companies/${companyId.value}/subscription/cancel`)
    showCancelModal.value = false

    await fetchSubscription()

    notificationStore.showNotification({
      type: 'success',
      message: t('billing.success_canceled'),
    })
  } catch (err) {
    console.error('Failed to cancel subscription:', err)
    error.value = err.response?.data?.message || t('billing.error_cancel')
  } finally {
    isProcessing.value = false
  }
}

// Resume subscription — uses company-scoped route
const resumeSubscription = async () => {
  isProcessing.value = true
  error.value = null

  try {
    if (!companyId.value) {
      throw new Error('No company selected')
    }
    await axios.post(`/api/v1/companies/${companyId.value}/subscription/resume`)

    await fetchSubscription()

    notificationStore.showNotification({
      type: 'success',
      message: t('billing.success_resumed'),
    })
  } catch (err) {
    console.error('Failed to resume subscription:', err)
    error.value = err.response?.data?.message || t('billing.error_resume')
  } finally {
    isProcessing.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchSubscription(), fetchInvoices()])
})
</script>

<!-- CLAUDE-CHECKPOINT -->
