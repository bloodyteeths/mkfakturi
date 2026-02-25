<template>
  <div class="pt-6 mt-5 border-t border-solid lg:pt-8 md:pt-4 border-gray-200">
    <!-- Basic Info -->
    <BaseHeading>
      {{ $t('customers.basic_info') }}
    </BaseHeading>

    <BaseDescriptionList>
      <BaseDescriptionListItem
        v-if="selectedSupplier.name"
        :content-loading="contentLoading"
        :label="$t('suppliers.name')"
        :value="selectedSupplier?.name"
      />

      <BaseDescriptionListItem
        v-if="selectedSupplier.contact_name"
        :content-loading="contentLoading"
        :label="$t('customers.primary_contact_name')"
        :value="selectedSupplier?.contact_name"
      />
      <BaseDescriptionListItem
        v-if="selectedSupplier.email"
        :content-loading="contentLoading"
        :label="$t('suppliers.email')"
        :value="selectedSupplier?.email"
      />
    </BaseDescriptionList>

    <BaseDescriptionList class="mt-5">
      <BaseDescriptionListItem
        :content-loading="contentLoading"
        :label="$t('wizard.currency')"
        :value="
          selectedSupplier?.currency
            ? `${selectedSupplier?.currency?.code} (${selectedSupplier?.currency?.symbol})`
            : ''
        "
      />

      <BaseDescriptionListItem
        v-if="selectedSupplier.phone"
        :content-loading="contentLoading"
        :label="$t('suppliers.phone')"
        :value="selectedSupplier?.phone"
      />
      <BaseDescriptionListItem
        v-if="selectedSupplier.website"
        :content-loading="contentLoading"
        :label="$t('suppliers.website')"
      >
        <a
          v-if="selectedSupplier.website"
          :href="selectedSupplier.website"
          target="_blank"
          rel="noopener noreferrer"
          class="text-primary-500 hover:underline"
        >
          {{ selectedSupplier.website }}
        </a>
      </BaseDescriptionListItem>
    </BaseDescriptionList>

    <!-- Linked Customer -->
    <BaseHeading class="mt-8">
      {{ $t('suppliers.linked_customer') }}
    </BaseHeading>

    <div class="mt-3">
      <div v-if="selectedSupplier.linked_customer" class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div>
          <router-link
            :to="`/admin/customers/${selectedSupplier.linked_customer.id}/view`"
            class="text-sm font-semibold text-primary-600 hover:underline"
          >
            {{ selectedSupplier.linked_customer.name }}
          </router-link>
          <p v-if="selectedSupplier.linked_customer.tax_id" class="text-xs text-gray-500 mt-0.5">
            ЕМБС: {{ selectedSupplier.linked_customer.tax_id }}
          </p>
        </div>
        <button
          class="text-xs text-red-500 hover:text-red-700 font-medium px-2 py-1 rounded hover:bg-red-50"
          @click="unlinkCustomer"
        >
          {{ $t('suppliers.unlink_customer') }}
        </button>
      </div>

      <div v-else>
        <div v-if="!showLinkSearch" class="flex items-center gap-2">
          <button
            class="text-sm text-primary-600 hover:text-primary-800 font-medium"
            @click="showLinkSearch = true"
          >
            + {{ $t('suppliers.link_to_customer') }}
          </button>
        </div>

        <div v-else class="space-y-2">
          <input
            v-model="customerSearchQuery"
            type="text"
            :placeholder="$t('suppliers.search_customer')"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500"
            @input="searchCustomers"
          />
          <div v-if="customerResults.length > 0" class="border border-gray-200 rounded-lg max-h-40 overflow-y-auto">
            <button
              v-for="c in customerResults"
              :key="c.id"
              class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 border-b last:border-b-0"
              @click="linkCustomer(c.id)"
            >
              <span class="font-medium">{{ c.name }}</span>
              <span v-if="c.tax_id" class="text-gray-400 ml-2">{{ c.tax_id }}</span>
            </button>
          </div>
          <button
            class="text-xs text-gray-400 hover:text-gray-600"
            @click="showLinkSearch = false; customerSearchQuery = ''; customerResults = []"
          >
            {{ $t('general.cancel') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Address -->
    <BaseHeading
      v-if="hasAddress"
      class="mt-8"
    >
      {{ $t('customers.address') }}
    </BaseHeading>

    <BaseDescriptionList v-if="hasAddress" class="mt-5">
      <BaseDescriptionListItem
        :content-loading="contentLoading"
        :label="$t('customers.billing_address')"
      >
        <div v-if="selectedSupplier.address_street_1">
          <p>{{ selectedSupplier.address_street_1 }}</p>
          <p v-if="selectedSupplier.address_street_2">{{ selectedSupplier.address_street_2 }}</p>
          <p>
            <span v-if="selectedSupplier.city">{{ selectedSupplier.city }}</span>
            <span v-if="selectedSupplier.state">, {{ selectedSupplier.state }}</span>
            <span v-if="selectedSupplier.zip"> {{ selectedSupplier.zip }}</span>
          </p>
          <p v-if="selectedSupplier.country">{{ selectedSupplier.country }}</p>
        </div>
      </BaseDescriptionListItem>
    </BaseDescriptionList>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
const route = useRoute()
const suppliersStore = useSuppliersStore()
const notificationStore = useNotificationStore()

const selectedSupplier = computed(() => suppliersStore.selectedSupplier || {})
const contentLoading = computed(() => suppliersStore.isFetchingView)

const showLinkSearch = ref(false)
const customerSearchQuery = ref('')
const customerResults = ref([])

const hasAddress = computed(() => {
  if (!selectedSupplier.value) return false
  return !!(
    selectedSupplier.value.address_street_1 ||
    selectedSupplier.value.address_street_2 ||
    selectedSupplier.value.city ||
    selectedSupplier.value.state ||
    selectedSupplier.value.zip ||
    selectedSupplier.value.country
  )
})

let searchTimeout = null
function searchCustomers() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(async () => {
    if (customerSearchQuery.value.length < 2) {
      customerResults.value = []
      return
    }
    try {
      const response = await axios.get('/customers', {
        params: { search: customerSearchQuery.value, limit: 10 },
      })
      customerResults.value = response.data.data || []
    } catch (e) {
      customerResults.value = []
    }
  }, 300)
}

async function linkCustomer(customerId) {
  try {
    await axios.post(`/customers/${customerId}/link-supplier`, {
      supplier_id: parseInt(route.params.id),
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('suppliers.linked_successfully'),
    })
    showLinkSearch.value = false
    customerSearchQuery.value = ''
    customerResults.value = []
    suppliersStore.fetchSupplier(route.params.id)
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.error || 'Error linking customer',
    })
  }
}

async function unlinkCustomer() {
  if (!selectedSupplier.value?.linked_customer?.id) return
  try {
    await axios.delete(`/customers/${selectedSupplier.value.linked_customer.id}/link-supplier`)
    notificationStore.showNotification({
      type: 'success',
      message: t('suppliers.unlinked_successfully'),
    })
    suppliersStore.fetchSupplier(route.params.id)
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Error unlinking customer',
    })
  }
}
</script>
