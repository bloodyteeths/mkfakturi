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

    <BaseCard v-if="supplier" class="mt-6">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
        </BaseDescriptionList>

        <BaseDescriptionList>
          <BaseDescriptionListItem :label="$t('suppliers.phone')">
            {{ supplier.phone || '-' }}
          </BaseDescriptionListItem>
          <BaseDescriptionListItem :label="$t('suppliers.website')">
            <a
              v-if="supplier.website"
              :href="supplier.website"
              target="_blank"
              rel="noopener noreferrer"
              class="text-primary-500 hover:underline"
            >
              {{ supplier.website }}
            </a>
            <span v-else>-</span>
          </BaseDescriptionListItem>
          <BaseDescriptionListItem
            v-if="supplier.created_at"
            :label="$t('items.added_on')"
          >
            {{ supplier.formatted_created_at || supplier.created_at }}
          </BaseDescriptionListItem>
        </BaseDescriptionList>
      </div>

      <!-- Address Information -->
      <div v-if="hasAddress" class="mt-6 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-medium mb-4">{{ $t('general.address') }}</h3>
        <BaseDescriptionList>
          <BaseDescriptionListItem
            v-if="supplier.address_street_1"
            :label="$t('customers.address')"
          >
            <div>
              <p v-if="supplier.address_street_1">{{ supplier.address_street_1 }}</p>
              <p v-if="supplier.address_street_2">{{ supplier.address_street_2 }}</p>
              <p>
                <span v-if="supplier.city">{{ supplier.city }}</span>
                <span v-if="supplier.state">, {{ supplier.state }}</span>
                <span v-if="supplier.zip"> {{ supplier.zip }}</span>
              </p>
              <p v-if="supplier.country">{{ supplier.country }}</p>
            </div>
          </BaseDescriptionListItem>
        </BaseDescriptionList>
      </div>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useUserStore } from '@/scripts/admin/stores/user'
import SupplierDropdown from '@/scripts/admin/components/dropdowns/SupplierIndexDropdown.vue'
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

const hasAddress = computed(() => {
  if (!supplier.value) return false
  return !!(
    supplier.value.address_street_1 ||
    supplier.value.address_street_2 ||
    supplier.value.city ||
    supplier.value.state ||
    supplier.value.zip ||
    supplier.value.country
  )
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

onMounted(() => {
  suppliersStore.fetchSupplier(route.params.id)
})
</script>
// CLAUDE-CHECKPOINT

