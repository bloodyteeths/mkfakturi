<template>
  <BasePage>
    <BasePageHeader :title="supplier?.name || $t('suppliers.view_supplier')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('suppliers.title')" to="/admin/suppliers" />
        <BaseBreadcrumbItem
          :title="supplier?.name || $t('suppliers.view_supplier')"
          to="#"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseCard v-if="supplier">
      <BaseDescriptionList>
        <BaseDescriptionListItem :label="$t('suppliers.name')">
          {{ supplier.name }}
        </BaseDescriptionListItem>
        <BaseDescriptionListItem :label="$t('suppliers.email')">
          {{ supplier.email || '-' }}
        </BaseDescriptionListItem>
        <BaseDescriptionListItem :label="$t('suppliers.tax_id')">
          {{ supplier.tax_id || '-' }}
        </BaseDescriptionListItem>
        <BaseDescriptionListItem :label="$t('suppliers.phone')">
          {{ supplier.phone || '-' }}
        </BaseDescriptionListItem>
        <BaseDescriptionListItem :label="$t('suppliers.website')">
          {{ supplier.website || '-' }}
        </BaseDescriptionListItem>
      </BaseDescriptionList>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'

const route = useRoute()
const suppliersStore = useSuppliersStore()

const supplier = computed(() => suppliersStore.selectedSupplier)

onMounted(() => {
  suppliersStore.fetchSupplier(route.params.id)
})
</script>

