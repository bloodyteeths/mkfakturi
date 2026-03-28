<template>
  <BasePage>
    <BasePageHeader :title="isEdit ? t('manufacturing.edit_work_center') : t('manufacturing.new_work_center')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.work_centers')" to="/admin/manufacturing/work-centers" />
        <BaseBreadcrumbItem :title="isEdit ? t('manufacturing.edit_work_center') : t('manufacturing.new_work_center')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <form @submit.prevent="handleSubmit" class="mx-auto max-w-2xl">
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-base font-semibold text-gray-900">{{ t('manufacturing.work_centers') }}</h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseInputGroup :label="$t('general.name')" required>
            <BaseInput v-model="form.name" :placeholder="$t('general.name')" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.bom_code')">
            <BaseInput v-model="form.code" placeholder="WC-001" />
          </BaseInputGroup>
        </div>

        <BaseInputGroup :label="t('manufacturing.description')" class="mt-4">
          <BaseInput v-model="form.description" type="textarea" :rows="2" />
        </BaseInputGroup>

        <h3 class="mb-3 mt-6 text-base font-semibold text-gray-900">{{ t('manufacturing.capacity_hours') }}</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <BaseInputGroup :label="t('manufacturing.capacity_hours')">
            <BaseInput v-model="form.capacity_hours_per_day" type="number" step="0.5" min="0" max="24" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.hourly_rate')">
            <BaseInput v-model="form.hourly_rate_display" type="number" step="1" min="0" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.overhead_rate')">
            <BaseInput v-model="form.overhead_rate_display" type="number" step="1" min="0" />
          </BaseInputGroup>
        </div>

        <h3 class="mb-3 mt-6 text-base font-semibold text-gray-900">{{ t('manufacturing.dash_oee_target') }} (%)</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <BaseInputGroup :label="t('manufacturing.dash_oee_availability')">
            <BaseInput v-model="form.target_availability" type="number" step="1" min="0" max="100" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.dash_oee_performance')">
            <BaseInput v-model="form.target_performance" type="number" step="1" min="0" max="100" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.dash_oee_quality')">
            <BaseInput v-model="form.target_quality" type="number" step="1" min="0" max="100" />
          </BaseInputGroup>
        </div>

        <div class="mt-4 flex items-center">
          <input type="checkbox" v-model="form.is_active" id="is_active" class="mr-2 rounded border-gray-300 text-primary-600" />
          <label for="is_active" class="text-sm text-gray-700">{{ t('manufacturing.is_active') }}</label>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <router-link to="/admin/manufacturing/work-centers">
            <BaseButton variant="primary-outline">{{ $t('general.cancel') }}</BaseButton>
          </router-link>
          <BaseButton type="submit" variant="primary" :loading="isSaving">
            {{ isEdit ? $t('general.update') : $t('general.save') }}
          </BaseButton>
        </div>
      </div>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const isEdit = computed(() => !!route.params.id)
const isSaving = ref(false)

const form = ref({
  name: '',
  code: '',
  description: '',
  capacity_hours_per_day: 8,
  hourly_rate_display: 0,
  overhead_rate_display: 0,
  target_availability: 90,
  target_performance: 85,
  target_quality: 95,
  is_active: true,
})

async function fetchWorkCenter() {
  if (!route.params.id) return
  try {
    const res = await window.axios.get(`/manufacturing/work-centers/${route.params.id}`)
    const wc = res.data?.data
    if (wc) {
      form.value = {
        name: wc.name,
        code: wc.code || '',
        description: wc.description || '',
        capacity_hours_per_day: wc.capacity_hours_per_day,
        hourly_rate_display: Math.round(wc.hourly_rate / 100),
        overhead_rate_display: Math.round(wc.overhead_rate / 100),
        target_availability: wc.target_availability,
        target_performance: wc.target_performance,
        target_quality: wc.target_quality,
        is_active: wc.is_active,
      }
    }
  } catch (error) {
    console.error('Failed to load work center:', error)
  }
}

async function handleSubmit() {
  isSaving.value = true
  try {
    const payload = {
      name: form.value.name,
      code: form.value.code || null,
      description: form.value.description || null,
      capacity_hours_per_day: parseFloat(form.value.capacity_hours_per_day),
      hourly_rate: Math.round(form.value.hourly_rate_display * 100),
      overhead_rate: Math.round(form.value.overhead_rate_display * 100),
      target_availability: parseFloat(form.value.target_availability),
      target_performance: parseFloat(form.value.target_performance),
      target_quality: parseFloat(form.value.target_quality),
      is_active: form.value.is_active,
    }

    if (isEdit.value) {
      await window.axios.put(`/manufacturing/work-centers/${route.params.id}`, payload)
    } else {
      await window.axios.post('/manufacturing/work-centers', payload)
    }

    router.push('/admin/manufacturing/work-centers')
  } catch (error) {
    console.error('Failed to save work center:', error)
  } finally {
    isSaving.value = false
  }
}

onMounted(fetchWorkCenter)
</script>
