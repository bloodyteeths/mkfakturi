<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 overflow-hidden"
  >
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-500/50" @click="close" />

    <!-- Drawer panel -->
    <div class="absolute inset-y-0 right-0 flex max-w-full pl-10">
      <div class="w-screen max-w-md">
        <div class="flex h-full flex-col bg-white shadow-xl">
          <!-- Header -->
          <div class="bg-primary-50 px-6 py-4 border-b">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900">
                {{ $t('banking.smart_reconcile', 'Reconcile Transaction') }}
              </h2>
              <button @click="close" class="text-gray-400 hover:text-gray-600">
                <BaseIcon name="XMarkIcon" class="h-5 w-5" />
              </button>
            </div>

            <!-- Transaction Summary -->
            <div v-if="transaction" class="mt-3 space-y-1">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">{{ formatDate(transaction.transaction_date) }}</span>
                <span
                  class="text-lg font-bold"
                  :class="isCredit ? 'text-green-600' : 'text-red-600'"
                >
                  {{ formatAmount(transaction) }}
                </span>
              </div>
              <p v-if="transaction.counterparty_name" class="text-sm font-medium text-gray-800">
                {{ transaction.counterparty_name }}
              </p>
              <p class="text-xs text-gray-500 truncate">
                {{ transaction.description || transaction.remittance_info }}
              </p>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto px-6 py-4">
            <!-- Loading -->
            <div v-if="isLoading" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500" />
              <span class="ml-3 text-sm text-gray-600">{{ $t('banking.analyzing', 'Analyzing transaction...') }}</span>
            </div>

            <!-- AI Suggestion (Primary) -->
            <div v-else-if="suggestion" class="space-y-4">
              <div class="rounded-lg border-2 p-4" :class="suggestionBorderClass">
                <!-- Action icon + label -->
                <div class="flex items-start space-x-3">
                  <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="suggestionIconBgClass">
                    <BaseIcon :name="suggestionIcon" class="h-5 w-5 text-white" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">
                      {{ suggestionTitle }}
                    </p>
                    <p v-if="suggestion.target_label" class="text-sm text-gray-700 mt-0.5">
                      {{ suggestion.target_label }}
                    </p>
                    <p v-if="suggestion.category_name" class="text-sm text-gray-700 mt-0.5">
                      {{ $t('banking.category', 'Category') }}: {{ suggestion.category_name }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ suggestion.reason }}
                    </p>
                    <div class="flex items-center mt-1">
                      <div class="w-20 bg-gray-200 rounded-full h-1.5 mr-2">
                        <div
                          class="h-1.5 rounded-full"
                          :class="confidenceBarClass"
                          :style="{ width: `${Math.round(suggestion.confidence * 100)}%` }"
                        />
                      </div>
                      <span class="text-xs text-gray-500">
                        {{ Math.round(suggestion.confidence * 100) }}%
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Payroll warning — no matching payroll run -->
                <div
                  v-if="isPayrollWarning"
                  class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg"
                >
                  <div class="flex items-start space-x-2">
                    <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-amber-500 flex-shrink-0 mt-0.5" />
                    <div>
                      <p class="text-sm text-amber-800">{{ suggestion.reason }}</p>
                      <a
                        href="/admin/payroll"
                        class="mt-2 inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-800"
                      >
                        {{ $t('banking.go_to_payroll', 'Go to Payroll →') }}
                      </a>
                    </div>
                  </div>
                </div>

                <!-- Accept Button -->
                <button
                  v-if="!isPayrollWarning"
                  @click="acceptSuggestion(suggestion)"
                  :disabled="isAccepting"
                  class="mt-3 w-full flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                >
                  <BaseIcon name="CheckIcon" class="h-4 w-4 mr-2" />
                  {{ $t('banking.accept', 'Accept') }}
                </button>
              </div>

              <!-- Alternatives -->
              <div v-if="suggestion.alternatives && suggestion.alternatives.length > 0">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
                  {{ $t('banking.alternatives', 'Other Options') }}
                </p>
                <div class="space-y-2">
                  <button
                    v-for="(alt, idx) in suggestion.alternatives"
                    :key="idx"
                    @click="acceptSuggestion(alt)"
                    :disabled="isAccepting"
                    class="w-full text-left p-3 rounded-lg border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-colors"
                  >
                    <p class="text-sm font-medium text-gray-800">
                      {{ getActionLabel(alt.action) }}
                    </p>
                    <p v-if="alt.target_label" class="text-xs text-gray-500">{{ alt.target_label }}</p>
                    <p v-if="alt.category_name" class="text-xs text-gray-500">{{ alt.category_name }}</p>
                  </button>
                </div>
              </div>

              <!-- Manual Override Section -->
              <div class="border-t pt-4 mt-4">
                <button
                  @click="showManualOptions = !showManualOptions"
                  class="flex items-center text-sm text-gray-600 hover:text-gray-900"
                >
                  <BaseIcon
                    :name="showManualOptions ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="h-4 w-4 mr-1"
                  />
                  {{ $t('banking.manual_options', 'Manual Options') }}
                </button>

                <div v-if="showManualOptions" class="mt-3 space-y-2">
                  <!-- Create Expense (debits) -->
                  <button
                    v-if="!isCredit"
                    @click="manualAction = 'expense'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'expense' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="ReceiptPercentIcon" class="h-4 w-4 mr-2 text-red-500" />
                      <span class="text-sm font-medium">{{ $t('banking.create_expense', 'Create Expense') }}</span>
                    </div>
                  </button>

                  <!-- Record Income (credits) -->
                  <button
                    v-if="isCredit"
                    @click="manualAction = 'income'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'income' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="BanknotesIcon" class="h-4 w-4 mr-2 text-green-500" />
                      <span class="text-sm font-medium">{{ $t('banking.record_income', 'Record as Income') }}</span>
                    </div>
                  </button>

                  <!-- Link to Bill (debits) -->
                  <button
                    v-if="!isCredit"
                    @click="manualAction = 'bill'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'bill' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="DocumentTextIcon" class="h-4 w-4 mr-2 text-blue-500" />
                      <span class="text-sm font-medium">{{ $t('banking.link_to_bill', 'Link to Bill') }}</span>
                    </div>
                  </button>

                  <!-- Link to Invoice (credits) -->
                  <button
                    v-if="isCredit"
                    @click="manualAction = 'invoice'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'invoice' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="DocumentCheckIcon" class="h-4 w-4 mr-2 text-blue-500" />
                      <span class="text-sm font-medium">{{ $t('banking.link_to_invoice', 'Link to Invoice') }}</span>
                    </div>
                  </button>

                  <!-- Link to Payroll (debits) — for NET SALARY only -->
                  <button
                    v-if="!isCredit"
                    @click="manualAction = 'payroll'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'payroll' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="UserGroupIcon" class="h-4 w-4 mr-2 text-purple-500" />
                      <span class="text-sm font-medium">{{ $t('banking.link_to_payroll', 'Link to Payroll (Net Salary)') }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5 ml-6">{{ $t('banking.payroll_hint', 'For contributions (ФПИОМ, ФЗОМ, PIT) use Tax Payment instead') }}</p>
                  </button>

                  <!-- Owner Contribution (credits) -->
                  <button
                    v-if="isCredit"
                    @click="manualAction = 'owner_contribution'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'owner_contribution' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="BuildingLibraryIcon" class="h-4 w-4 mr-2 text-indigo-500" />
                      <span class="text-sm font-medium">{{ $t('banking.owner_contribution', 'Owner Capital Contribution') }}</span>
                    </div>
                  </button>

                  <!-- Owner Withdrawal (debits) -->
                  <button
                    v-if="!isCredit"
                    @click="manualAction = 'owner_withdrawal'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'owner_withdrawal' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="BuildingLibraryIcon" class="h-4 w-4 mr-2 text-indigo-500" />
                      <span class="text-sm font-medium">{{ $t('banking.owner_withdrawal', 'Owner Capital Withdrawal') }}</span>
                    </div>
                  </button>

                  <!-- Loan Received (credits) -->
                  <button
                    v-if="isCredit"
                    @click="manualAction = 'loan_received'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'loan_received' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="BuildingOffice2Icon" class="h-4 w-4 mr-2 text-cyan-500" />
                      <span class="text-sm font-medium">{{ $t('banking.loan_received', 'Loan Received') }}</span>
                    </div>
                  </button>

                  <!-- Loan Repayment (debits) -->
                  <button
                    v-if="!isCredit"
                    @click="manualAction = 'loan_repayment'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'loan_repayment' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="BuildingOffice2Icon" class="h-4 w-4 mr-2 text-cyan-500" />
                      <span class="text-sm font-medium">{{ $t('banking.loan_repayment', 'Loan Repayment') }}</span>
                    </div>
                  </button>

                  <!-- Loan Given (debits) -->
                  <button
                    v-if="!isCredit"
                    @click="manualAction = 'loan_given'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'loan_given' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="ArrowUpOnSquareIcon" class="h-4 w-4 mr-2 text-teal-500" />
                      <span class="text-sm font-medium">{{ $t('banking.loan_given', 'Loan Given (Позајмица дадена)') }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5 ml-6">{{ $t('banking.loan_given_hint', 'GL: DR 1240 Краткорочни побарувања по дадени заеми') }}</p>
                  </button>

                  <!-- Tax Payment (debits) -->
                  <button
                    v-if="!isCredit"
                    @click="manualAction = 'tax_payment'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'tax_payment' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="CalculatorIcon" class="h-4 w-4 mr-2 text-amber-600" />
                      <span class="text-sm font-medium">{{ $t('banking.tax_payment', 'Tax Payment') }}</span>
                    </div>
                  </button>

                  <!-- Cash Deposit (credits) -->
                  <button
                    v-if="isCredit"
                    @click="manualAction = 'cash_deposit'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'cash_deposit' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="BanknotesIcon" class="h-4 w-4 mr-2 text-emerald-600" />
                      <span class="text-sm font-medium">{{ $t('banking.cash_deposit', 'Cash Deposit') }}</span>
                    </div>
                  </button>

                  <!-- Cash Withdrawal (debits) -->
                  <button
                    v-if="!isCredit"
                    @click="manualAction = 'cash_withdrawal'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'cash_withdrawal' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="BanknotesIcon" class="h-4 w-4 mr-2 text-emerald-600" />
                      <span class="text-sm font-medium">{{ $t('banking.cash_withdrawal', 'Cash Withdrawal') }}</span>
                    </div>
                  </button>

                  <!-- Advance Received (credits) -->
                  <button
                    v-if="isCredit"
                    @click="manualAction = 'advance_received'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'advance_received' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="ClockIcon" class="h-4 w-4 mr-2 text-orange-500" />
                      <span class="text-sm font-medium">{{ $t('banking.advance_received', 'Advance Payment Received') }}</span>
                    </div>
                  </button>

                  <!-- Advance Paid (debits) -->
                  <button
                    v-if="!isCredit"
                    @click="manualAction = 'advance_paid'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'advance_paid' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="ClockIcon" class="h-4 w-4 mr-2 text-orange-500" />
                      <span class="text-sm font-medium">{{ $t('banking.advance_paid', 'Advance Payment to Supplier') }}</span>
                    </div>
                  </button>

                  <!-- Internal Transfer (both) -->
                  <button
                    @click="manualAction = 'internal_transfer'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'internal_transfer' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="ArrowsRightLeftIcon" class="h-4 w-4 mr-2 text-gray-600" />
                      <span class="text-sm font-medium">{{ $t('banking.internal_transfer', 'Internal Transfer') }}</span>
                    </div>
                  </button>

                  <!-- Mark as Reviewed -->
                  <button
                    @click="manualAction = 'reviewed'"
                    class="w-full text-left p-3 rounded-lg border hover:bg-gray-50"
                    :class="manualAction === 'reviewed' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <div class="flex items-center">
                      <BaseIcon name="CheckCircleIcon" class="h-4 w-4 mr-2 text-gray-500" />
                      <span class="text-sm font-medium">{{ $t('banking.mark_reviewed', 'Mark as Reviewed') }}</span>
                    </div>
                    <p class="text-xs text-amber-600 mt-0.5 ml-6">{{ $t('banking.reviewed_warning', '⚠ No GL entry will be created — status only') }}</p>
                  </button>

                  <!-- Manual Sub-Forms -->
                  <div v-if="manualAction === 'expense'" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-3">
                    <select
                      v-model="selectedCategoryId"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    >
                      <option :value="null" disabled>{{ $t('banking.select_category', 'Select category...') }}</option>
                      <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                      </option>
                    </select>
                    <textarea
                      v-model="manualNotes"
                      :placeholder="$t('banking.notes', 'Notes...')"
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    />
                    <button
                      @click="submitManualExpense"
                      :disabled="!selectedCategoryId || isAccepting"
                      class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
                    >
                      {{ $t('banking.create_expense', 'Create Expense') }}
                    </button>
                  </div>

                  <div v-if="manualAction === 'bill'" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-3">
                    <select
                      v-model="selectedBillId"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    >
                      <option :value="null" disabled>{{ $t('banking.select_bill', 'Select bill...') }}</option>
                      <option v-for="bill in unpaidBills" :key="bill.id" :value="bill.id">
                        {{ bill.bill_number }} — {{ bill.supplier_name }} ({{ bill.total }} ден)
                      </option>
                    </select>
                    <button
                      @click="submitManualBill"
                      :disabled="!selectedBillId || isAccepting"
                      class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                      {{ $t('banking.link_to_bill', 'Link to Bill') }}
                    </button>
                  </div>

                  <div v-if="manualAction === 'invoice'" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-3">
                    <select
                      v-model="selectedInvoiceId"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    >
                      <option :value="null" disabled>{{ $t('banking.select_invoice', 'Select invoice...') }}</option>
                      <option v-for="inv in unpaidInvoices" :key="inv.id" :value="inv.id">
                        {{ inv.invoice_number }} — {{ inv.customer_name }} ({{ inv.total }} ден)
                      </option>
                    </select>
                    <button
                      @click="submitManualInvoice"
                      :disabled="!selectedInvoiceId || isAccepting"
                      class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                      {{ $t('banking.match_invoice', 'Match to Invoice') }}
                    </button>
                  </div>

                  <div v-if="manualAction === 'payroll'" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-3">
                    <select
                      v-model="selectedPayrollId"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    >
                      <option :value="null" disabled>{{ $t('banking.select_payroll', 'Select payroll run...') }}</option>
                      <option v-for="run in payrollRuns" :key="run.id" :value="run.id">
                        {{ run.period }} — {{ run.total_net }} ден ({{ run.status }})
                      </option>
                    </select>
                    <button
                      @click="submitManualPayroll"
                      :disabled="!selectedPayrollId || isAccepting"
                      class="w-full px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50"
                    >
                      {{ $t('banking.link_to_payroll', 'Link to Payroll') }}
                    </button>
                  </div>

                  <div v-if="manualAction === 'income'" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-3">
                    <textarea
                      v-model="manualNotes"
                      :placeholder="$t('banking.income_notes', 'e.g. Bank interest, Refund from...')"
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    />
                    <button
                      @click="submitManualIncome"
                      :disabled="isAccepting"
                      class="w-full px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50"
                    >
                      {{ $t('banking.record_income', 'Record as Income') }}
                    </button>
                  </div>

                  <div v-if="manualAction === 'reviewed'" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-3">
                    <textarea
                      v-model="manualNotes"
                      :placeholder="$t('banking.review_notes', 'Notes (optional)...')"
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    />
                    <button
                      @click="submitManualReviewed"
                      :disabled="isAccepting"
                      class="w-full px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700 disabled:opacity-50"
                    >
                      {{ $t('banking.mark_reviewed', 'Mark as Reviewed') }}
                    </button>
                  </div>

                  <!-- Financial transaction sub-forms -->
                  <div v-if="['owner_contribution', 'owner_withdrawal', 'loan_received', 'loan_repayment', 'loan_given', 'internal_transfer', 'cash_deposit', 'cash_withdrawal', 'advance_received', 'advance_paid'].includes(manualAction)" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-3">
                    <!-- Interest amount for loan repayment -->
                    <div v-if="manualAction === 'loan_repayment'" class="space-y-1">
                      <label class="text-xs font-medium text-gray-600">{{ $t('banking.interest_portion', 'Interest portion (optional)') }}</label>
                      <input
                        v-model="interestAmount"
                        type="number"
                        step="0.01"
                        min="0"
                        :placeholder="$t('banking.interest_amount_placeholder', 'e.g. 1500.00')"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      />
                      <p class="text-xs text-gray-400">{{ $t('banking.interest_help', 'If set, principal and interest will be posted to separate GL accounts') }}</p>
                    </div>
                    <textarea
                      v-model="manualNotes"
                      :placeholder="$t('banking.financial_notes', 'Notes (e.g. reason, reference number)...')"
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    />
                    <button
                      @click="submitFinancialTransaction(manualAction)"
                      :disabled="isAccepting"
                      class="w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                    >
                      {{ getActionLabel(manualAction) }}
                    </button>
                  </div>

                  <div v-if="manualAction === 'tax_payment'" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-3">
                    <select
                      v-model="taxSubType"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    >
                      <option :value="null">{{ $t('banking.select_tax_type', 'Select tax type...') }}</option>
                      <option value="ДДВ">{{ $t('banking.tax_vat', 'ДДВ (VAT)') }}</option>
                      <option value="Данок на добивка">{{ $t('banking.tax_profit', 'Данок на добивка (Profit Tax)') }}</option>
                      <option value="Персонален данок">{{ $t('banking.tax_personal', 'Персонален данок (PIT)') }}</option>
                      <option value="ФПИОМ">{{ $t('banking.tax_pension', 'ФПИОМ (Pension Fund)') }}</option>
                      <option value="ФЗОМ">{{ $t('banking.tax_health', 'ФЗОМ (Health Fund)') }}</option>
                      <option value="Вработување">{{ $t('banking.tax_employment', 'Вработување (Employment Fund)') }}</option>
                      <option value="Професионален придонес">{{ $t('banking.tax_additional', 'Професионален придонес (Additional 0.5%)') }}</option>
                      <option value="Аконтација">{{ $t('banking.tax_advance', 'Аконтација (Advance Tax)') }}</option>
                      <option value="Царина">{{ $t('banking.tax_customs', 'Царина (Customs Duties)') }}</option>
                      <option value="Акциза">{{ $t('banking.tax_excise', 'Акциза (Excise Tax)') }}</option>
                      <option value="Комунална такса">{{ $t('banking.tax_communal', 'Комунална такса (Communal Tax)') }}</option>
                      <option value="Друг данок">{{ $t('banking.tax_other', 'Друг данок (Other Tax)') }}</option>
                    </select>
                    <textarea
                      v-model="manualNotes"
                      :placeholder="$t('banking.tax_notes', 'Reference number, period...')"
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    />
                    <button
                      @click="submitFinancialTransaction('tax_payment')"
                      :disabled="isAccepting"
                      class="w-full px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 disabled:opacity-50"
                    >
                      {{ $t('banking.tax_payment', 'Tax Payment') }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Error -->
            <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
              <p class="text-sm text-red-600">{{ error }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const props = defineProps({
  modelValue: Boolean,
  transaction: Object,
})

const emit = defineEmits(['update:modelValue', 'reconciled'])

const { t } = useI18n()
const notificationStore = useNotificationStore()

// State
const isLoading = ref(false)
const isAccepting = ref(false)
const suggestion = ref(null)
const error = ref(null)
const showManualOptions = ref(false)
const manualAction = ref(null)
const manualNotes = ref('')
const selectedCategoryId = ref(null)
const selectedBillId = ref(null)
const selectedInvoiceId = ref(null)
const selectedPayrollId = ref(null)
const taxSubType = ref(null)
const interestAmount = ref(null)

// Reference data
const expenseCategories = ref([])
const unpaidBills = ref([])
const unpaidInvoices = ref([])
const payrollRuns = ref([])

const isCredit = computed(() => props.transaction?.transaction_type === 'credit')

const isPayrollWarning = computed(() => {
  if (!suggestion.value) return false
  return suggestion.value.confidence <= 0.3 && suggestion.value.action === 'mark_reviewed' &&
    (suggestion.value.reason?.includes('payroll') || suggestion.value.reason?.includes('плата') ||
     suggestion.value.reason?.includes('платен') || suggestion.value.reason?.includes('pagash') ||
     suggestion.value.reason?.includes('bordro'))
})

// Fetch smart suggestion when drawer opens
watch(() => props.modelValue, async (isOpen) => {
  if (isOpen && props.transaction) {
    resetState()
    await fetchSuggestion()
    await fetchReferenceData()
  }
})

const resetState = () => {
  suggestion.value = null
  error.value = null
  isLoading.value = false
  isAccepting.value = false
  showManualOptions.value = false
  manualAction.value = null
  manualNotes.value = ''
  selectedCategoryId.value = null
  selectedBillId.value = null
  selectedInvoiceId.value = null
  selectedPayrollId.value = null
  taxSubType.value = null
  interestAmount.value = null
}

const fetchSuggestion = async () => {
  isLoading.value = true
  try {
    const { data } = await axios.post('/banking/reconciliation/smart-suggest', {
      transaction_id: props.transaction.id,
    })
    suggestion.value = data.suggestion
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to analyze transaction'
  } finally {
    isLoading.value = false
  }
}

const fetchReferenceData = async () => {
  const results = await Promise.allSettled([
    axios.get('/banking/reconciliation/expense-categories'),
    axios.get('/banking/reconciliation/unpaid-bills'),
    axios.get('/banking/reconciliation/unpaid-invoices'),
    axios.get('/banking/reconciliation/payroll-runs'),
  ])
  expenseCategories.value = results[0].status === 'fulfilled' ? (results[0].value.data.data || []) : []
  unpaidBills.value = results[1].status === 'fulfilled' ? (results[1].value.data.data || []) : []
  unpaidInvoices.value = results[2].status === 'fulfilled' ? (results[2].value.data.data || []) : []
  payrollRuns.value = results[3].status === 'fulfilled' ? (results[3].value.data.data || []) : []
}

const acceptSuggestion = async (s) => {
  isAccepting.value = true
  error.value = null

  try {
    const txId = props.transaction.id

    switch (s.action) {
      case 'create_expense':
        await axios.post('/banking/reconciliation/record-expense', {
          transaction_id: txId,
          expense_category_id: s.category_id,
          category_name: s.category_name || null,
          notes: s.reason,
        })
        break

      case 'link_bill':
        await axios.post('/banking/reconciliation/link-bill', {
          transaction_id: txId,
          bill_id: s.target_id,
        })
        break

      case 'link_invoice':
        await axios.post('/banking/reconciliation/manual-match', {
          transaction_id: txId,
          invoice_id: s.target_id,
        })
        break

      case 'link_payroll':
        await axios.post('/banking/reconciliation/link-payroll', {
          transaction_id: txId,
          payroll_run_id: s.target_id,
        })
        break

      case 'record_income':
        await axios.post('/banking/reconciliation/record-income', {
          transaction_id: txId,
          notes: s.reason,
        })
        break

      case 'mark_reviewed':
        await axios.post('/banking/reconciliation/mark-reviewed', {
          transaction_id: txId,
          notes: s.reason,
        })
        break

      case 'owner_contribution':
      case 'owner_withdrawal':
      case 'loan_received':
      case 'loan_repayment':
      case 'loan_given':
      case 'tax_payment':
      case 'internal_transfer':
      case 'cash_deposit':
      case 'cash_withdrawal':
      case 'advance_received':
      case 'advance_paid':
        await axios.post('/banking/reconciliation/record-financial', {
          transaction_id: txId,
          action: s.action,
          notes: s.reason,
          sub_type: s.sub_type || (s.action === 'tax_payment' ? s.category_name : null) || null,
          interest_amount: s.action === 'loan_repayment' ? (s.interest_amount || null) : null,
        })
        break

      default:
        throw new Error(`Unknown action: ${s.action}`)
    }

    notificationStore.showNotification({
      type: 'success',
      message: t('banking.reconciled_success', 'Transaction reconciled'),
    })
    emit('reconciled')
    close()
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to reconcile'
  } finally {
    isAccepting.value = false
  }
}

// Manual form submissions
const submitManualExpense = () => acceptSuggestion({
  action: 'create_expense',
  category_id: selectedCategoryId.value,
  reason: manualNotes.value || '',
})

const submitManualBill = () => acceptSuggestion({
  action: 'link_bill',
  target_id: selectedBillId.value,
})

const submitManualInvoice = () => acceptSuggestion({
  action: 'link_invoice',
  target_id: selectedInvoiceId.value,
})

const submitManualPayroll = () => acceptSuggestion({
  action: 'link_payroll',
  target_id: selectedPayrollId.value,
})

const submitManualIncome = () => acceptSuggestion({
  action: 'record_income',
  reason: manualNotes.value || '',
})

const submitManualReviewed = () => acceptSuggestion({
  action: 'mark_reviewed',
  reason: manualNotes.value || '',
})

const submitFinancialTransaction = (action) => acceptSuggestion({
  action,
  reason: manualNotes.value || '',
  sub_type: action === 'tax_payment' ? taxSubType.value : null,
  interest_amount: action === 'loan_repayment' && interestAmount.value ? parseFloat(interestAmount.value) : null,
})

const close = () => {
  emit('update:modelValue', false)
}

// Display helpers
const actionLabels = {
  link_bill: 'Link to Bill',
  link_invoice: 'Match to Invoice',
  link_payroll: 'Link to Payroll',
  create_expense: 'Create Expense',
  record_income: 'Record as Income',
  mark_reviewed: 'Mark as Reviewed',
  owner_contribution: 'Owner Capital Contribution',
  owner_withdrawal: 'Owner Capital Withdrawal',
  loan_received: 'Loan Received',
  loan_repayment: 'Loan Repayment',
  tax_payment: 'Tax Payment',
  internal_transfer: 'Internal Transfer',
  cash_deposit: 'Cash Deposit',
  cash_withdrawal: 'Cash Withdrawal',
  advance_received: 'Advance Payment Received',
  advance_paid: 'Advance Payment to Supplier',
  loan_given: 'Loan Given (Позајмица дадена)',
}

const getActionLabel = (action) => {
  return t(`banking.action_${action}`, actionLabels[action] || action)
}

const suggestionTitle = computed(() => {
  if (!suggestion.value) return ''
  return getActionLabel(suggestion.value.action)
})

const suggestionIcon = computed(() => {
  const icons = {
    link_bill: 'DocumentTextIcon',
    link_invoice: 'DocumentCheckIcon',
    link_payroll: 'UserGroupIcon',
    create_expense: 'ReceiptPercentIcon',
    record_income: 'BanknotesIcon',
    mark_reviewed: 'CheckCircleIcon',
    owner_contribution: 'BuildingLibraryIcon',
    owner_withdrawal: 'BuildingLibraryIcon',
    loan_received: 'BuildingOffice2Icon',
    loan_repayment: 'BuildingOffice2Icon',
    tax_payment: 'CalculatorIcon',
    internal_transfer: 'ArrowsRightLeftIcon',
    cash_deposit: 'BanknotesIcon',
    cash_withdrawal: 'BanknotesIcon',
    advance_received: 'ClockIcon',
    advance_paid: 'ClockIcon',
    loan_given: 'ArrowUpOnSquareIcon',
  }
  return icons[suggestion.value?.action] || 'SparklesIcon'
})

const suggestionBorderClass = computed(() => {
  const conf = suggestion.value?.confidence || 0
  if (conf >= 0.8) return 'border-green-300 bg-green-50'
  if (conf >= 0.6) return 'border-yellow-300 bg-yellow-50'
  return 'border-gray-300 bg-gray-50'
})

const suggestionIconBgClass = computed(() => {
  const map = {
    link_bill: 'bg-blue-500',
    link_invoice: 'bg-blue-500',
    link_payroll: 'bg-purple-500',
    create_expense: 'bg-red-500',
    record_income: 'bg-green-500',
    mark_reviewed: 'bg-gray-500',
    owner_contribution: 'bg-indigo-500',
    owner_withdrawal: 'bg-indigo-500',
    loan_received: 'bg-cyan-500',
    loan_repayment: 'bg-cyan-500',
    tax_payment: 'bg-amber-600',
    internal_transfer: 'bg-gray-600',
    cash_deposit: 'bg-emerald-600',
    cash_withdrawal: 'bg-emerald-600',
    advance_received: 'bg-orange-500',
    advance_paid: 'bg-orange-500',
    loan_given: 'bg-teal-500',
  }
  return map[suggestion.value?.action] || 'bg-gray-500'
})

const confidenceBarClass = computed(() => {
  const conf = suggestion.value?.confidence || 0
  if (conf >= 0.8) return 'bg-green-500'
  if (conf >= 0.6) return 'bg-yellow-500'
  return 'bg-red-500'
})

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('mk-MK', {
    year: 'numeric', month: 'short', day: 'numeric',
  })
}

const formatAmount = (tx) => {
  if (!tx) return '-'
  const sign = tx.transaction_type === 'credit' ? '+' : '-'
  return `${sign} ${new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: tx.currency || 'MKD',
  }).format(Math.abs(tx.amount))}`
}
</script>

<!-- CLAUDE-CHECKPOINT -->
