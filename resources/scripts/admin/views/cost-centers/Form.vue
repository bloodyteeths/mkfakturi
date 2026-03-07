<template>
  <!-- Side panel overlay -->
  <div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black bg-opacity-25" @click="$emit('close')"></div>

    <div class="absolute inset-y-0 right-0 max-w-md w-full">
      <div class="h-full bg-white shadow-xl flex flex-col">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
          <h3 class="text-lg font-medium text-gray-900">
            {{ isEditing ? $t('general.edit') : $t('general.create') }}
            {{ t('title') }}
          </h3>
          <button class="text-gray-400 hover:text-gray-600" @click="$emit('close')">
            <BaseIcon name="XMarkIcon" class="h-5 w-5" />
          </button>
        </div>

        <!-- Form body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-5">
          <!-- Name -->
          <BaseInputGroup :label="t('name')" required>
            <BaseInput
              v-model="form.name"
              :placeholder="t('name')"
              @keyup.enter="submit"
            />
          </BaseInputGroup>

          <!-- Code -->
          <BaseInputGroup :label="t('code')">
            <BaseInput
              v-model="form.code"
              :placeholder="t('code')"
              maxlength="20"
              class="font-mono"
            />
          </BaseInputGroup>

          <!-- Color picker -->
          <BaseInputGroup :label="t('color')">
            <div class="flex flex-wrap gap-2">
              <button
                v-for="color in presetColors"
                :key="color.value"
                class="w-8 h-8 rounded-full border-2 flex items-center justify-center transition-transform"
                :class="form.color === color.value ? 'border-gray-900 scale-110' : 'border-transparent hover:border-gray-300'"
                :style="{ backgroundColor: color.value }"
                :title="color.name"
                type="button"
                @click="form.color = color.value"
              >
                <BaseIcon
                  v-if="form.color === color.value"
                  name="CheckIcon"
                  class="h-4 w-4 text-white"
                />
              </button>
            </div>
            <div class="flex items-center mt-2 space-x-2">
              <input
                v-model="form.color"
                type="color"
                class="h-8 w-8 rounded cursor-pointer border border-gray-200"
              />
              <span class="text-xs text-gray-500 font-mono">{{ form.color }}</span>
            </div>
          </BaseInputGroup>

          <!-- Parent -->
          <BaseInputGroup :label="t('parent')">
            <BaseMultiselect
              v-model="form.parent_id"
              :options="parentOptions"
              :searchable="true"
              label="name"
              value-prop="id"
              :placeholder="$t('general.none')"
              :can-clear="true"
            />
          </BaseInputGroup>

          <!-- Description -->
          <BaseInputGroup :label="$t('general.description')">
            <textarea
              v-model="form.description"
              rows="3"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              :placeholder="$t('general.description')"
            ></textarea>
          </BaseInputGroup>

          <!-- Active toggle -->
          <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-gray-700">{{ $t('general.active') }}</label>
            <button
              type="button"
              class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
              :class="form.is_active ? 'bg-primary-600' : 'bg-gray-200'"
              @click="form.is_active = !form.is_active"
            >
              <span
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"
              ></span>
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3 bg-gray-50">
          <BaseButton variant="primary-outline" @click="$emit('close')">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            :loading="isSaving"
            @click="submit"
          >
            {{ isEditing ? $t('general.update') : $t('general.save') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import ccMessages from '@/scripts/admin/i18n/cost-centers.js'

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return ccMessages[locale]?.cost_centers?.[key]
    || ccMessages['en']?.cost_centers?.[key]
    || key
}

const props = defineProps({
  costCenter: {
    type: Object,
    default: null,
  },
  parentId: {
    type: Number,
    default: null,
  },
  costCenters: {
    type: Array,
    default: () => [],
  },
  isSaving: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['save', 'close'])

const presetColors = [
  { name: 'Indigo', value: '#6366f1' },
  { name: 'Blue', value: '#3b82f6' },
  { name: 'Cyan', value: '#06b6d4' },
  { name: 'Green', value: '#22c55e' },
  { name: 'Yellow', value: '#eab308' },
  { name: 'Orange', value: '#f97316' },
  { name: 'Red', value: '#ef4444' },
  { name: 'Purple', value: '#a855f7' },
  { name: 'Pink', value: '#ec4899' },
  { name: 'Gray', value: '#6b7280' },
]

const isEditing = computed(() => !!props.costCenter?.id)

const form = ref({
  name: '',
  code: '',
  color: '#6366f1',
  parent_id: null,
  description: '',
  is_active: true,
})

// Filter out self and descendants from parent options to prevent circular references
const parentOptions = computed(() => {
  if (!props.costCenters) return []

  const selfId = props.costCenter?.id
  if (!selfId) {
    return props.costCenters.filter(cc => cc.is_active)
  }

  // Exclude self and any cost center whose full_path includes self
  return props.costCenters.filter(cc => {
    if (cc.id === selfId) return false
    if (!cc.is_active) return false
    return true
  })
})

onMounted(() => {
  if (props.costCenter) {
    form.value = {
      name: props.costCenter.name || '',
      code: props.costCenter.code || '',
      color: props.costCenter.color || '#6366f1',
      parent_id: props.costCenter.parent_id || null,
      description: props.costCenter.description || '',
      is_active: props.costCenter.is_active !== false,
    }
  } else if (props.parentId) {
    form.value.parent_id = props.parentId
  }
})

function submit() {
  if (!form.value.name?.trim()) return
  emit('save', { ...form.value })
}
</script>

<!-- CLAUDE-CHECKPOINT -->
