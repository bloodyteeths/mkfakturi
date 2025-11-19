<template>
  <BasePage v-if="partner">
    <!-- Page Header -->
    <BasePageHeader :title="partner.name">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('partners.title', 2)" to="/admin/partners" />
        <BaseBreadcrumbItem :title="partner.name" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-3">
          <span
            v-if="partner.is_partner_plus"
            class="px-3 py-2 text-sm font-medium bg-purple-100 text-purple-800 rounded"
          >
            Partner Plus
          </span>
          <BaseButton
            variant="primary-outline"
            @click="$router.push(`/admin/partners/${partner.id}/edit`)"
          >
            <template #left="slotProps">
              <BaseIcon name="PencilIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.edit') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Partner Info Cards -->
    <div class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-4">
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('partners.total_earnings') }}</div>
        <div class="text-2xl font-semibold">
          <BaseFormatMoney
            :amount="partner.total_earnings || 0"
            :currency="currency"
          />
        </div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('partners.pending_payout') }}</div>
        <div class="text-2xl font-semibold text-orange-600">
          <BaseFormatMoney
            :amount="partner.pending_payout || 0"
            :currency="currency"
          />
        </div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('partners.companies_managed') }}</div>
        <div class="text-2xl font-semibold text-blue-600">{{ partner.companies_count || 0 }}</div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('partners.commission_rate') }}</div>
        <div class="text-2xl font-semibold text-green-600">
          {{ partner.effective_commission_rate ? (partner.effective_commission_rate * 100).toFixed(0) : 0 }}%
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mt-8">
      <BaseTabGroup>
        <BaseTab :title="$t('partners.tabs.info')">
          <div class="p-6 bg-white rounded-lg shadow">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.name') }}</label>
                <div class="mt-1 text-gray-900">{{ partner.name }}</div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.email') }}</label>
                <div class="mt-1 text-gray-900">{{ partner.email }}</div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.phone') }}</label>
                <div class="mt-1 text-gray-900">{{ partner.phone || '-' }}</div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.company_name') }}</label>
                <div class="mt-1 text-gray-900">{{ partner.company_name || '-' }}</div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.tax_id') }}</label>
                <div class="mt-1 text-gray-900">{{ partner.tax_id || '-' }}</div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.registration_number') }}</label>
                <div class="mt-1 text-gray-900">{{ partner.registration_number || '-' }}</div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.bank_account') }}</label>
                <div class="mt-1 text-gray-900">{{ partner.bank_account || '-' }}</div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.bank_name') }}</label>
                <div class="mt-1 text-gray-900">{{ partner.bank_name || '-' }}</div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('general.status') }}</label>
                <div class="mt-1">
                  <span
                    class="px-2 py-1 text-xs font-medium rounded"
                    :class="partner.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                  >
                    {{ partner.is_active ? $t('general.active') : $t('general.inactive') }}
                  </span>
                </div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.kyc_status') }}</label>
                <div class="mt-1">
                  <span
                    class="px-2 py-1 text-xs font-medium rounded"
                    :class="{
                      'bg-gray-100 text-gray-800': partner.kyc_status === 'pending',
                      'bg-yellow-100 text-yellow-800': partner.kyc_status === 'under_review',
                      'bg-green-100 text-green-800': partner.kyc_status === 'approved',
                      'bg-red-100 text-red-800': partner.kyc_status === 'rejected'
                    }"
                  >
                    {{ $t(`partners.kyc.${partner.kyc_status || 'pending'}`) }}
                  </span>
                </div>
              </div>
              <div v-if="partner.notes" class="col-span-2">
                <label class="text-sm font-medium text-gray-500">{{ $t('partners.notes') }}</label>
                <div class="mt-1 text-gray-900">{{ partner.notes }}</div>
              </div>
            </div>
          </div>
        </BaseTab>

        <BaseTab :title="$t('partners.tabs.companies')" :count="partner.companies ? partner.companies.length : 0">
          <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200">
              <BaseButton @click="openAssignCompanyModal">
                <template #left="slotProps">
                  <BaseIcon name="PlusIcon" :class="slotProps.class" />
                </template>
                {{ $t('partners.assign_company') }}
              </BaseButton>
            </div>

            <div v-if="partner.companies && partner.companies.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      {{ $t('partners.company_name') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      {{ $t('partners.commission_rate') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      {{ $t('partners.permissions') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      {{ $t('general.status') }}
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      {{ $t('general.actions') }}
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="company in partner.companies" :key="company.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div>
                          <div class="text-sm font-medium text-gray-900">{{ company.name }}</div>
                          <span
                            v-if="company.pivot.is_primary"
                            class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded"
                          >
                            {{ $t('partners.primary') }}
                          </span>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ company.pivot.override_commission_rate || partner.commission_rate || 0 }}%
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <span class="inline-flex px-2 py-1 text-xs bg-gray-100 rounded">
                        {{ getPermissionsCount(company.pivot.permissions) }} {{ $t('partners.permissions_label') }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        class="px-2 py-1 text-xs font-medium rounded"
                        :class="company.pivot.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                      >
                        {{ company.pivot.is_active ? $t('general.active') : $t('general.inactive') }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <BaseButton
                        variant="primary-outline"
                        size="sm"
                        @click="editPermissions(company)"
                        class="mr-2"
                      >
                        {{ $t('partners.edit_permissions') }}
                      </BaseButton>
                      <BaseButton
                        variant="danger-outline"
                        size="sm"
                        @click="unassignCompany(company.id)"
                      >
                        {{ $t('partners.unassign') }}
                      </BaseButton>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="p-8 text-center text-gray-500">
              {{ $t('partners.no_companies_assigned') }}
            </div>
          </div>
        </BaseTab>

        <BaseTab :title="$t('partners.tabs.commissions')">
          <div class="bg-white rounded-lg shadow">
            <div v-if="partner.monthly_commissions && partner.monthly_commissions.length > 0" class="p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('partners.monthly_commissions') }}</h3>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        {{ $t('partners.month') }}
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        {{ $t('partners.amount') }}
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="commission in partner.monthly_commissions" :key="commission.month">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ commission.month }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                        <BaseFormatMoney
                          :amount="commission.total"
                          :currency="currency"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="p-8 text-center text-gray-500">
              {{ $t('partners.no_commissions') }}
            </div>
          </div>
        </BaseTab>

        <BaseTab :title="$t('partners.tabs.payouts')">
          <div class="bg-white rounded-lg shadow">
            <div v-if="partner.payouts && partner.payouts.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      {{ $t('partners.date') }}
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      {{ $t('partners.amount') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      {{ $t('general.status') }}
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="payout in partner.payouts" :key="payout.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ formatDate(payout.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                      <BaseFormatMoney
                        :amount="payout.amount"
                        :currency="currency"
                      />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
                        {{ payout.status || 'Paid' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="p-8 text-center text-gray-500">
              {{ $t('partners.no_payouts') }}
            </div>
          </div>
        </BaseTab>
      </BaseTabGroup>
    </div>
  </BasePage>
  <div v-else class="flex items-center justify-center h-screen">
    <BaseLoader />
  </div>

  <!-- Assign/Edit Company Modal (AC-13 integration) -->
  <AssignCompanyModal
    :show="showAssignModal"
    :partner-id="partner?.id"
    :company="editingCompany"
    @close="closeAssignModal"
    @saved="onCompanyAssigned"
  />
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import AssignCompanyModal from './components/AssignCompanyModal.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const notificationStore = useNotificationStore()
const globalStore = useGlobalStore()

const partner = ref(null)
const showAssignModal = ref(false)
const editingCompany = ref(null)

// Add null-safe currency with fallback
const currency = computed(() => {
  return globalStore.companySettings?.currency || {
    code: 'MKD',
    symbol: 'ден',
    precision: 2,
    thousand_separator: '.',
    decimal_separator: ',',
    swap_currency_symbol: false
  }
})

async function fetchPartner() {
  try {
    const response = await axios.get(`/partners/${route.params.id}`)
    partner.value = response.data
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('partners.fetch_failed'),
    })
    router.push('/admin/partners')
  }
}

function getPermissionsCount(permissions) {
  if (!permissions) return 0
  try {
    const parsed = typeof permissions === 'string' ? JSON.parse(permissions) : permissions
    return Array.isArray(parsed) ? parsed.length : 0
  } catch {
    return 0
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString()
}

function openAssignCompanyModal() {
  editingCompany.value = null
  showAssignModal.value = true
}

function editPermissions(company) {
  editingCompany.value = company
  showAssignModal.value = true
}

function closeAssignModal() {
  showAssignModal.value = false
  editingCompany.value = null
}

function onCompanyAssigned() {
  fetchPartner()
}

async function unassignCompany(companyId) {
  if (!confirm(t('partners.confirm_unassign'))) return

  try {
    await axios.delete(`/partners/${partner.value.id}/companies/${companyId}`)
    notificationStore.showNotification({
      type: 'success',
      message: t('partners.company_unassigned_successfully'),
    })
    fetchPartner()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('partners.unassign_failed'),
    })
  }
}

onMounted(() => {
  fetchPartner()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
