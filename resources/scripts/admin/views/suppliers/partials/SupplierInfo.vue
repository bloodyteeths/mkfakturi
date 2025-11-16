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
import { computed } from 'vue'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'

const suppliersStore = useSuppliersStore()

const selectedSupplier = computed(() => suppliersStore.selectedSupplier || {})

const contentLoading = computed(() => suppliersStore.isFetchingView)

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
</script>
// CLAUDE-CHECKPOINT
