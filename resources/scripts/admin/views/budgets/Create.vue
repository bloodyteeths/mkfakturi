<template>
  <BasePage>
    <BasePageHeader :title="t('budgets.create')">
      <template #actions>
        <BaseButton variant="primary-outline" @click="$router.push({ name: 'budgets.index' })">
          {{ $t('general.cancel') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <UnifiedBudgetForm />
  </BasePage>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import UnifiedBudgetForm from './UnifiedBudgetForm.vue'

const { t } = useI18n()
</script>

<!-- CLAUDE-CHECKPOINT -->
