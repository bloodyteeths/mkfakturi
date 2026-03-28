<template>
  <BasePage>
    <BasePageHeader :title="budget ? budget.name : t('budgets.edit')">
      <template #actions>
        <BaseButton variant="primary-outline" @click="$router.push({ name: 'budgets.view', params: { id: route.params.id } })">
          {{ $t('general.cancel') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-1/3"></div>
        <div class="h-4 bg-gray-200 rounded w-2/3"></div>
      </div>
    </div>

    <AdvancedBudgetForm
      v-else-if="budget"
      :initial-data="budget"
      :is-edit="true"
      @saved="onSaved"
    />
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'
import AdvancedBudgetForm from './AdvancedBudgetForm.vue'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const { t } = useI18n()

const budget = ref(null)
const isLoading = ref(true)

onMounted(async () => {
  try {
    const response = await window.axios.get(`/budgets/${route.params.id}`)
    budget.value = response.data?.data

    // Only draft budgets can be edited
    if (budget.value && budget.value.status !== 'draft') {
      router.push({ name: 'budgets.view', params: { id: route.params.id } })
      return
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || 'Error loading budget',
    })
    router.push({ name: 'budgets.index' })
  } finally {
    isLoading.value = false
  }
})

function onSaved() {
  router.push({ name: 'budgets.view', params: { id: route.params.id } })
}
</script>

<!-- CLAUDE-CHECKPOINT -->
