<template>
  <BaseMultiselect
    v-model="selectedSupplier"
    v-bind="$attrs"
    track-by="name"
    value-prop="id"
    label="name"
    :filter-results="false"
    resolve-on-load
    :delay="500"
    :searchable="true"
    :options="searchSuppliers"
    label-value="name"
    :placeholder="$t('suppliers.type_or_click')"
    :can-deselect="false"
    class="w-full"
  >
    <template v-if="showAction" #action>
      <BaseSelectAction
        v-if="userStore.hasAbilities(abilities.CREATE_SUPPLIER)"
        @click="addSupplier"
      >
        <BaseIcon
          name="UserPlusIcon"
          class="h-4 mr-2 -ml-2 text-center text-primary-400"
        />

        {{ $t('suppliers.add_new_supplier') }}
      </BaseSelectAction>
    </template>
  </BaseMultiselect>
</template>

<script setup>
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import { useRouter } from 'vue-router'

const props = defineProps({
  modelValue: {
    type: [String, Number, Object],
    default: '',
  },
  fetchAll: {
    type: Boolean,
    default: false,
  },
  showAction: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()

const emit = defineEmits(['update:modelValue'])

const suppliersStore = useSuppliersStore()
const userStore = useUserStore()
const router = useRouter()

const selectedSupplier = computed({
  get: () => props.modelValue,
  set: (value) => {
    emit('update:modelValue', value)
  },
})

async function searchSuppliers(search) {
  let data = {
    search,
  }

  if (props.fetchAll) {
    data.limit = 'all'
  }

  let res = await suppliersStore.fetchSuppliers(data)
  if (res.data.data.length > 0 && suppliersStore.selectedSupplier) {
    let supplierFound = res.data.data.find(
      (s) => s.id == suppliersStore.selectedSupplier.id
    )
    if (!supplierFound) {
      let selected_supplier = Object.assign({}, suppliersStore.selectedSupplier)
      res.data.data.unshift(selected_supplier)
    }
  }

  return res.data.data
}

async function addSupplier() {
  router.push({ name: 'suppliers.create' })
}
</script>
// CLAUDE-CHECKPOINT

