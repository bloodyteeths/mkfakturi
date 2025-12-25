<template>
  <BasePage>
    <BasePageHeader :title="$t('reports.report', 2)">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem
          :title="$t('reports.report', 2)"
          to="/admin/reports"
          active
        />
      </BaseBreadcrumb>
      <template #actions>
        <BaseButton variant="primary" class="ml-4" @click="onDownload">
          <template #left="slotProps">
            <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
          </template>
          {{ $t('reports.download_pdf') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Tabs -->
    <BaseTabGroup class="p-2">
      <BaseTab
        :title="$t('reports.sales.sales')"
        tab-panel-container="px-0 py-0"
      >
        <SalesReport ref="report" />
      </BaseTab>
      <BaseTab
        :title="$t('reports.profit_loss.profit_loss')"
        tab-panel-container="px-0 py-0"
      >
        <ProfitLossReport ref="report" />
      </BaseTab>
      <BaseTab
        :title="$t('reports.expenses.expenses')"
        tab-panel-container="px-0 py-0"
      >
        <ExpenseReport ref="report" />
      </BaseTab>
      <BaseTab
        :title="$t('reports.taxes.taxes')"
        tab-panel-container="px-0 py-0"
      >
        <TaxReport ref="report" />
      </BaseTab>
      <BaseTab
        :title="$t('reports.projects.title')"
        tab-panel-container="px-0 py-0"
      >
        <ProjectReport ref="report" />
      </BaseTab>
      <BaseTab
        v-if="accountingBackboneEnabled"
        :title="$t('reports.accounting.accounting')"
        tab-panel-container="px-0 py-0"
      >
        <BaseTabGroup class="p-2">
          <BaseTab
            :title="$t('reports.accounting.trial_balance.title')"
            tab-panel-container="px-0 py-0"
          >
            <TrialBalance ref="report" />
          </BaseTab>
          <BaseTab
            :title="$t('reports.accounting.balance_sheet')"
            tab-panel-container="px-0 py-0"
          >
            <BalanceSheet ref="report" />
          </BaseTab>
          <BaseTab
            :title="$t('reports.accounting.income_statement')"
            tab-panel-container="px-0 py-0"
          >
            <IncomeStatement ref="report" />
          </BaseTab>
          <BaseTab
            :title="$t('reports.accounting.general_ledger.title')"
            tab-panel-container="px-0 py-0"
          >
            <GeneralLedger ref="report" />
          </BaseTab>
          <BaseTab
            :title="$t('reports.accounting.journal_entries.title')"
            tab-panel-container="px-0 py-0"
          >
            <JournalEntries ref="report" />
          </BaseTab>
        </BaseTabGroup>
      </BaseTab>
    </BaseTabGroup>
  </BasePage>
</template>

<script setup>
import { ref, computed } from 'vue'
import SalesReport from '../SalesReports.vue'
import ExpenseReport from '../ExpensesReport.vue'
import ProfitLossReport from '../ProfitLossReport.vue'
import TaxReport from '../TaxReport.vue'
import ProjectReport from '../ProjectReport.vue'
import TrialBalance from '../TrialBalance.vue'
import BalanceSheet from '../BalanceSheet.vue'
import IncomeStatement from '../IncomeStatement.vue'
import GeneralLedger from '../GeneralLedger.vue'
import JournalEntries from '../JournalEntries.vue'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const globalStore = useGlobalStore()

const accountingBackboneEnabled = computed(() => {
  return globalStore.featureFlags?.['accounting_backbone'] === true
})

function onDownload() {
  globalStore.downloadReport()
}
</script>

// CLAUDE-CHECKPOINT: Added GeneralLedger and JournalEntries tabs
// CLAUDE-CHECKPOINT
