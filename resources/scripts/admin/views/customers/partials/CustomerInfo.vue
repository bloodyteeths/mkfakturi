<template>
  <div class="pt-6 mt-5 border-t border-solid lg:pt-8 md:pt-4 border-gray-200">
    <!-- Basic Info -->
    <BaseHeading>
      {{ $t('customers.basic_info') }}
    </BaseHeading>

    <BaseDescriptionList>
      <BaseDescriptionListItem
        v-if="selectedViewCustomer.name"
        :content-loading="contentLoading"
        :label="$t('customers.display_name')"
        :value="selectedViewCustomer?.name"
      />

      <BaseDescriptionListItem
        v-if="selectedViewCustomer.contact_name"
        :content-loading="contentLoading"
        :label="$t('customers.primary_contact_name')"
        :value="selectedViewCustomer?.contact_name"
      />
      <BaseDescriptionListItem
        v-if="selectedViewCustomer.email"
        :content-loading="contentLoading"
        :label="$t('customers.email')"
        :value="selectedViewCustomer?.email"
      />
    </BaseDescriptionList>

    <BaseDescriptionList class="mt-5">
      <BaseDescriptionListItem
        :content-loading="contentLoading"
        :label="$t('wizard.currency')"
        :value="
          selectedViewCustomer?.currency
            ? `${selectedViewCustomer?.currency?.code} (${selectedViewCustomer?.currency?.symbol})`
            : ''
        "
      />

      <BaseDescriptionListItem
        v-if="selectedViewCustomer.phone"
        :content-loading="contentLoading"
        :label="$t('customers.phone_number')"
        :value="selectedViewCustomer?.phone"
      />
      <BaseDescriptionListItem
        v-if="selectedViewCustomer.website"
        :content-loading="contentLoading"
        :label="$t('customers.website')"
        :value="selectedViewCustomer?.website"
      />
    </BaseDescriptionList>

    <!-- Address -->
    <BaseHeading
      v-if="selectedViewCustomer.billing || selectedViewCustomer.shipping"
      class="mt-8"
    >
      {{ $t('customers.address') }}
    </BaseHeading>

    <BaseDescriptionList class="mt-5">
      <BaseDescriptionListItem
        v-if="selectedViewCustomer.billing"
        :content-loading="contentLoading"
        :label="$t('customers.billing_address')"
      >
        <BaseCustomerAddressDisplay :address="selectedViewCustomer.billing" />
      </BaseDescriptionListItem>

      <BaseDescriptionListItem
        v-if="selectedViewCustomer.shipping"
        :content-loading="contentLoading"
        :label="$t('customers.shipping_address')"
      >
        <BaseCustomerAddressDisplay :address="selectedViewCustomer.shipping" />
      </BaseDescriptionListItem>
    </BaseDescriptionList>

    <!-- Linked Supplier -->
    <BaseHeading class="mt-8">
      {{ $t('customers.linked_supplier') }}
    </BaseHeading>

    <div class="mt-3">
      <div v-if="selectedViewCustomer.linked_supplier" class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div>
          <router-link
            :to="`/admin/suppliers/${selectedViewCustomer.linked_supplier.id}/view`"
            class="text-sm font-semibold text-primary-600 hover:underline"
          >
            {{ selectedViewCustomer.linked_supplier.name }}
          </router-link>
          <p v-if="selectedViewCustomer.linked_supplier.tax_id" class="text-xs text-gray-500 mt-0.5">
            ЕМБС: {{ selectedViewCustomer.linked_supplier.tax_id }}
          </p>
        </div>
        <button
          class="text-xs text-red-500 hover:text-red-700 font-medium px-2 py-1 rounded hover:bg-red-50"
          @click="unlinkSupplier"
        >
          {{ $t('customers.unlink_supplier') }}
        </button>
      </div>

      <div v-else>
        <div v-if="!showLinkSearch" class="flex items-center gap-2">
          <button
            class="text-sm text-primary-600 hover:text-primary-800 font-medium"
            @click="showLinkSearch = true"
          >
            + {{ $t('customers.link_to_supplier') }}
          </button>
        </div>

        <div v-else class="space-y-2">
          <input
            v-model="supplierSearchQuery"
            type="text"
            :placeholder="$t('customers.search_supplier')"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500"
            @input="searchSuppliers"
          />
          <div v-if="supplierResults.length > 0" class="border border-gray-200 rounded-lg max-h-40 overflow-y-auto">
            <button
              v-for="s in supplierResults"
              :key="s.id"
              class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 border-b last:border-b-0"
              @click="linkSupplier(s.id)"
            >
              <span class="font-medium">{{ s.name }}</span>
              <span v-if="s.tax_id" class="text-gray-400 ml-2">{{ s.tax_id }}</span>
            </button>
          </div>
          <button
            class="text-xs text-gray-400 hover:text-gray-600"
            @click="showLinkSearch = false; supplierSearchQuery = ''; supplierResults = []"
          >
            {{ $t('general.cancel') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Custom Fields -->
    <BaseHeading v-if="customerCustomFields.length > 0" class="mt-8">
      {{ $t('settings.custom_fields.title') }}
    </BaseHeading>

    <BaseDescriptionList class="mt-5">
      <BaseDescriptionListItem
        v-for="(field, index) in customerCustomFields"
        :key="index"
        :content-loading="contentLoading"
        :label="field.custom_field.label"
      >
        <p
          v-if="field.type === 'Switch'"
          class="text-sm font-bold leading-5 text-black non-italic"
        >
          <span v-if="field.default_answer === 1"> {{ $t('general.yes') }} </span>
          <span v-else> {{ $t('general.no') }} </span>
        </p>
        <p v-else class="text-sm font-bold leading-5 text-black non-italic">
          {{ field.default_answer }}
        </p>
      </BaseDescriptionListItem>
    </BaseDescriptionList>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
const route = useRoute()
const customerStore = useCustomerStore()
const notificationStore = useNotificationStore()

const selectedViewCustomer = computed(() => customerStore.selectedViewCustomer)
const contentLoading = computed(() => customerStore.isFetchingViewData)

const showLinkSearch = ref(false)
const supplierSearchQuery = ref('')
const supplierResults = ref([])

const customerCustomFields = computed(() => {
  if (selectedViewCustomer?.value?.fields) {
    return selectedViewCustomer?.value?.fields
  }
  return []
})

let searchTimeout = null
function searchSuppliers() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(async () => {
    if (supplierSearchQuery.value.length < 2) {
      supplierResults.value = []
      return
    }
    try {
      const response = await axios.get('/suppliers', {
        params: { search: supplierSearchQuery.value, limit: 10 },
      })
      supplierResults.value = response.data.data || []
    } catch (e) {
      supplierResults.value = []
    }
  }, 300)
}

async function linkSupplier(supplierId) {
  try {
    await axios.post(`/customers/${route.params.id}/link-supplier`, {
      supplier_id: supplierId,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('customers.linked_successfully'),
    })
    showLinkSearch.value = false
    supplierSearchQuery.value = ''
    supplierResults.value = []
    customerStore.fetchViewCustomer({ id: route.params.id })
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.error || t('general.something_went_wrong'),
    })
  }
}

async function unlinkSupplier() {
  try {
    await axios.delete(`/customers/${route.params.id}/link-supplier`)
    notificationStore.showNotification({
      type: 'success',
      message: t('customers.unlinked_successfully'),
    })
    customerStore.fetchViewCustomer({ id: route.params.id })
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: t('general.something_went_wrong'),
    })
  }
}
</script>
