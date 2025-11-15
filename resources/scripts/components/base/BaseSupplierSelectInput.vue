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
  />
</template>

<script setup>
import { computed } from 'vue'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'

const props = defineProps({
  modelValue: {
    type: [String, Number, Object],
    default: '',
  },
  fetchAll: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const suppliersStore = useSuppliersStore()

const selectedSupplier = computed({
  get: () => props.modelValue,
  set: (value) => {
    emit('update:modelValue', value)
  },
})

async function searchSuppliers(search) {
  const params = {
    search,
  }

  if (props.fetchAll) {
    params.limit = 'all'
  }

  const res = await suppliersStore.fetchSuppliers(params)

  return res.data.data
}
</script>

