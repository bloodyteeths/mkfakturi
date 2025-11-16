<template>
  <BasePage>
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('suppliers.title')" to="/admin/suppliers" />
        <BaseBreadcrumbItem
          :title="supplier?.name || $t('suppliers.view_supplier')"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <router-link
          v-if="userStore.hasAbilities(abilities.EDIT_SUPPLIER)"
          :to="`/admin/suppliers/${route.params.id}/edit`"
        >
          <BaseButton
            class="mr-3"
            variant="primary-outline"
            :content-loading="isLoading"
          >
            {{ $t('general.edit') }}
          </BaseButton>
        </router-link>

        <SupplierDropdown
          v-if="hasAtleastOneAbility()"
          :class="{
            'ml-3': isLoading,
          }"
          :row="supplier || {}"
          :load-data="refreshData"
        />
      </template>
    </BasePageHeader>

    <!-- Chart -->
    <SupplierChart />
  </BasePage>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useUserStore } from '@/scripts/admin/stores/user'
import SupplierDropdown from '@/scripts/admin/components/dropdowns/SupplierIndexDropdown.vue'
import SupplierChart from './partials/SupplierChart.vue'
import abilities from '@/scripts/admin/stub/abilities'

const route = useRoute()
const router = useRouter()
const suppliersStore = useSuppliersStore()
const userStore = useUserStore()

const supplier = computed(() => suppliersStore.selectedSupplier)

const pageTitle = computed(() => {
  return supplier.value ? supplier.value.name : ''
})

const isLoading = computed(() => {
  return suppliersStore.isFetchingView || false
})

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_SUPPLIER,
    abilities.EDIT_SUPPLIER,
  ])
}

function refreshData() {
  router.push('/admin/suppliers')
}
</script>
// CLAUDE-CHECKPOINT

