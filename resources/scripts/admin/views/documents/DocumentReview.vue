<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b px-6 py-4">
      <div class="flex items-center justify-between max-w-7xl mx-auto">
        <div class="flex items-center space-x-4">
          <button @click="$router.push({ name: 'client-documents' })" class="text-gray-500 hover:text-gray-700">
            <ArrowLeftIcon class="h-5 w-5" />
          </button>
          <div>
            <h1 class="text-lg font-semibold text-gray-900">{{ $t('documents.review_title', 'Review Document') }}</h1>
            <p class="text-sm text-gray-500">{{ document?.original_filename }}</p>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <!-- Delete button (always visible) -->
          <button
            @click="deleteDocument"
            :disabled="isSubmitting"
            class="px-3 py-2 bg-white hover:bg-red-50 text-red-500 hover:text-red-700 border border-red-300 rounded-md text-sm font-medium"
            :title="$t('documents.delete', 'Delete')"
          >
            <TrashIcon class="h-4 w-4" />
          </button>
          <!-- Already confirmed: show status + link to entity -->
          <template v-if="isConfirmed">
            <span class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-green-100 text-green-800">
              &#10003; {{ $t('documents.already_confirmed', 'Confirmed') }}
            </span>
            <button
              v-if="linkedEntityRoute"
              @click="$router.push(linkedEntityRoute)"
              class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-md text-sm font-medium"
            >
              {{ linkedEntityLabel }}
            </button>
          </template>
          <!-- Not yet confirmed: show reprocess + confirm -->
          <template v-else>
            <button
              @click="reprocess"
              :disabled="isSubmitting"
              class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md text-sm font-medium"
            >
              {{ $t('documents.reprocess', 'Reprocess') }}
            </button>
            <button
              @click="confirmEntity"
              :disabled="isSubmitting || !isExtracted"
              class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-md text-sm font-medium disabled:opacity-50"
              :title="!isExtracted ? $t('documents.not_extracted_yet', 'Document must be fully processed before confirming') : ''"
            >
              {{ isSubmitting ? '...' : confirmButtonLabel }}
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <!-- Split Layout -->
    <div v-else-if="document" class="flex flex-col lg:flex-row max-w-7xl mx-auto" style="height: calc(100vh - 80px)">
      <!-- Left: Document Preview -->
      <div class="lg:w-1/2 p-4 overflow-auto border-r border-gray-200">
        <div class="bg-white rounded-lg shadow h-full">
          <div v-if="previewError" class="flex flex-col items-center justify-center h-full text-gray-500 p-8">
            <DocumentTextIcon class="h-16 w-16 text-gray-300 mb-4" />
            <p class="text-sm font-medium text-gray-700 mb-1">{{ $t('documents.preview_unavailable', 'Preview unavailable') }}</p>
            <p class="text-xs text-gray-400 text-center">{{ $t('documents.preview_unavailable_hint', 'The original file may have been stored on a different server. You can reprocess the document to re-upload it.') }}</p>
          </div>
          <iframe
            v-else-if="document.mime_type === 'application/pdf'"
            :src="previewUrl"
            class="w-full h-full rounded-lg"
            style="min-height: 600px"
            @error="previewError = true"
          />
          <img
            v-else-if="document.mime_type?.startsWith('image/')"
            :src="previewUrl"
            class="max-w-full mx-auto p-4"
            :alt="document.original_filename"
            @error="previewError = true"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            {{ $t('documents.no_preview', 'Preview not available for this file type') }}
          </div>
        </div>
      </div>

      <!-- Right: Editable Form -->
      <div class="lg:w-1/2 p-4 overflow-auto">
        <!-- AI Classification Summary -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-700">{{ $t('documents.ai_classification', 'AI Classification') }}</h3>
            <span v-if="document.ai_classification" :class="getCategoryBadgeClass(document.ai_classification.type)">
              {{ getCategoryLabel(document.ai_classification.type) }}
              <span v-if="document.ai_classification.confidence" class="ml-1 opacity-75">
                ({{ Math.round(document.ai_classification.confidence * 100) }}%)
              </span>
            </span>
          </div>
          <p v-if="document.ai_classification?.summary" class="text-sm text-gray-600 mb-3">{{ document.ai_classification.summary }}</p>

          <!-- Entity Type Selector -->
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.create_as', 'Create as') }}</label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="et in availableEntityTypes"
                :key="et.value"
                @click="selectedEntityType = et.value"
                :class="[
                  'px-3 py-1.5 rounded-md text-sm font-medium border transition-colors',
                  selectedEntityType === et.value
                    ? 'bg-primary-500 text-white border-primary-500'
                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                ]"
              >
                {{ et.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- ===== BILL FORM ===== -->
        <template v-if="selectedEntityType === 'bill'">
          <!-- Supplier Info -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.supplier', 'Supplier') }}</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.supplier_name', 'Name') }}</label>
                <input v-model="form.supplier.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.tax_id', 'Tax ID') }}</label>
                <input v-model="form.supplier.tax_id" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.supplier_address', 'Address') }}</label>
                <input v-model="form.supplier.address" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.supplier_email', 'Email') }}</label>
                <input v-model="form.supplier.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
            </div>
          </div>

          <!-- Bill Info -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.bill_info', 'Bill Details') }}</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.bill_number', 'Bill Number') }}</label>
                <input v-model="form.bill.bill_number" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.bill_date', 'Date') }}</label>
                <input v-model="form.bill.bill_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.due_date', 'Due Date') }}</label>
                <input v-model="form.bill.due_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.currency', 'Currency') }}</label>
                <input :value="'MKD'" disabled class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm bg-gray-50 text-gray-500" />
              </div>
            </div>
          </div>

          <!-- Line Items -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-medium text-gray-700">{{ $t('documents.line_items', 'Line Items') }}</h3>
              <button @click="addBillItem" class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                + {{ $t('documents.add_item', 'Add Item') }}
              </button>
            </div>
            <div v-if="form.items.length === 0" class="text-center text-sm text-gray-500 py-4">
              {{ $t('documents.no_items', 'No line items extracted') }}
            </div>
            <div v-else class="space-y-3">
              <div v-for="(item, idx) in form.items" :key="idx" class="border border-gray-200 rounded-md p-3">
                <div class="flex items-start justify-between mb-2">
                  <input v-model="item.name" type="text" :placeholder="$t('documents.item_name', 'Item name')" class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm focus:ring-primary-500 focus:border-primary-500" />
                  <button @click="form.items.splice(idx, 1)" class="ml-2 text-red-400 hover:text-red-600">
                    <XMarkIcon class="h-4 w-4" />
                  </button>
                </div>
                <div class="grid grid-cols-4 gap-2">
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.qty', 'Qty') }}</label>
                    <input v-model.number="item.quantity" type="number" step="0.01" min="0" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.price', 'Price') }}</label>
                    <input :value="formatCents(item.price)" @input="item.price = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.tax', 'Tax') }}</label>
                    <input :value="formatCents(item.tax)" @input="item.tax = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.total', 'Total') }}</label>
                    <input :value="formatCents(item.total)" @input="item.total = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Totals -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.totals', 'Totals') }}</h3>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('documents.subtotal', 'Subtotal') }}</span>
                <span class="font-medium">{{ formatCents(form.bill.sub_total) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('documents.tax_total', 'Tax') }}</span>
                <span class="font-medium">{{ formatCents(form.bill.tax) }}</span>
              </div>
              <div class="flex justify-between text-sm font-semibold border-t pt-2">
                <span>{{ $t('documents.grand_total', 'Total') }}</span>
                <span class="text-primary-600">{{ formatCents(form.bill.total) }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- ===== EXPENSE FORM ===== -->
        <template v-if="selectedEntityType === 'expense'">
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.supplier', 'Supplier') }}</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.supplier_name', 'Name') }}</label>
                <input v-model="form.supplier.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.tax_id', 'Tax ID') }}</label>
                <input v-model="form.supplier.tax_id" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.expense_details', 'Expense Details') }}</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.expense_date', 'Date') }}</label>
                <input v-model="form.expense.expense_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.expense_category', 'Category') }}</label>
                <input v-model="form.expense.category" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.expense_amount', 'Amount') }}</label>
                <input :value="formatCents(form.expense.amount)" @input="form.expense.amount = parseCents($event.target.value)" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.currency', 'Currency') }}</label>
                <input :value="'MKD'" disabled class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm bg-gray-50 text-gray-500" />
              </div>
              <div class="col-span-2">
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.notes', 'Notes') }}</label>
                <textarea v-model="form.expense.notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500"></textarea>
              </div>
            </div>
          </div>
        </template>

        <!-- ===== INVOICE (OUTGOING) FORM ===== -->
        <template v-if="selectedEntityType === 'invoice'">
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.customer', 'Customer') }}</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.customer_name', 'Name') }}</label>
                <input v-model="form.customer.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.customer_email', 'Email') }}</label>
                <input v-model="form.customer.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.customer_phone', 'Phone') }}</label>
                <input v-model="form.customer.phone" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.tax_id', 'Tax ID') }}</label>
                <input v-model="form.customer.tax_id" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.invoice_details', 'Invoice Details') }}</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.invoice_number', 'Invoice Number') }}</label>
                <input v-model="form.invoice.invoice_number" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.invoice_date', 'Date') }}</label>
                <input v-model="form.invoice.invoice_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.due_date', 'Due Date') }}</label>
                <input v-model="form.invoice.due_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.currency', 'Currency') }}</label>
                <input :value="'MKD'" disabled class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm bg-gray-50 text-gray-500" />
              </div>
            </div>
          </div>

          <!-- Invoice Line Items (same pattern as bill) -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-medium text-gray-700">{{ $t('documents.line_items', 'Line Items') }}</h3>
              <button @click="addBillItem" class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                + {{ $t('documents.add_item', 'Add Item') }}
              </button>
            </div>
            <div v-if="form.items.length === 0" class="text-center text-sm text-gray-500 py-4">
              {{ $t('documents.no_items', 'No line items extracted') }}
            </div>
            <div v-else class="space-y-3">
              <div v-for="(item, idx) in form.items" :key="idx" class="border border-gray-200 rounded-md p-3">
                <div class="flex items-start justify-between mb-2">
                  <input v-model="item.name" type="text" :placeholder="$t('documents.item_name', 'Item name')" class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm" />
                  <button @click="form.items.splice(idx, 1)" class="ml-2 text-red-400 hover:text-red-600">
                    <XMarkIcon class="h-4 w-4" />
                  </button>
                </div>
                <div class="grid grid-cols-4 gap-2">
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.qty', 'Qty') }}</label>
                    <input v-model.number="item.quantity" type="number" step="0.01" min="0" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.price', 'Price') }}</label>
                    <input :value="formatCents(item.price)" @input="item.price = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.tax', 'Tax') }}</label>
                    <input :value="formatCents(item.tax)" @input="item.tax = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.total', 'Total') }}</label>
                    <input :value="formatCents(item.total)" @input="item.total = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Invoice Totals -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.totals', 'Totals') }}</h3>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('documents.subtotal', 'Subtotal') }}</span>
                <span class="font-medium">{{ formatCents(form.invoice.sub_total) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('documents.tax_total', 'Tax') }}</span>
                <span class="font-medium">{{ formatCents(form.invoice.tax) }}</span>
              </div>
              <div class="flex justify-between text-sm font-semibold border-t pt-2">
                <span>{{ $t('documents.grand_total', 'Total') }}</span>
                <span class="text-primary-600">{{ formatCents(form.invoice.total) }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- ===== BANK TRANSACTIONS FORM ===== -->
        <template v-if="selectedEntityType === 'bank_transactions'">
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.bank_account', 'Bank Account') }}</h3>
            <p class="text-xs text-gray-500 mb-2">{{ $t('documents.bank_account_hint', 'Select the bank account to import transactions into') }}</p>
            <select
              v-model="form.bankAccountId"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500"
            >
              <option :value="null" disabled>{{ $t('documents.select_bank_account', '-- Select bank account --') }}</option>
              <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                {{ acc.bank_name }} — {{ acc.account_number }}
              </option>
            </select>
            <p v-if="bankAccounts.length === 0" class="text-xs text-amber-600 mt-1">
              {{ $t('documents.no_bank_accounts', 'No bank accounts found. Add one in Banking settings first.') }}
            </p>
          </div>

          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-medium text-gray-700">{{ $t('documents.transactions_preview', 'Transactions') }}</h3>
              <span class="text-xs text-gray-500">{{ form.transactions.length }} {{ $t('documents.transactions_count', 'transactions') }}</span>
            </div>
            <div v-if="form.transactions.length === 0" class="text-center text-sm text-gray-500 py-4">
              {{ $t('documents.no_transactions', 'No transactions extracted') }}
            </div>
            <div v-else class="space-y-2 max-h-96 overflow-auto">
              <div v-for="(txn, idx) in form.transactions" :key="idx" class="border border-gray-200 rounded-md p-3 text-sm">
                <div class="flex justify-between mb-1">
                  <span class="font-medium text-gray-900">{{ txn.counterparty_name || txn.counterparty || txn.description || '-' }}</span>
                  <span :class="txn.credit ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                    {{ txn.credit ? '+' : '-' }}{{ formatCents(txn.credit || txn.debit || 0) }}
                  </span>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                  <span>{{ txn.date }}</span>
                  <span>{{ txn.counterparty_account || txn.reference || '' }}</span>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ===== ITEMS (PRODUCT LIST) FORM ===== -->
        <template v-if="selectedEntityType === 'items'">
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-medium text-gray-700">{{ $t('documents.items_preview', 'Products to Import') }}</h3>
              <button @click="addProductItem" class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                + {{ $t('documents.add_product', 'Add Product') }}
              </button>
            </div>
            <div v-if="form.products.length === 0" class="text-center text-sm text-gray-500 py-4">
              {{ $t('documents.no_products', 'No products extracted') }}
            </div>
            <div v-else class="space-y-3">
              <div v-for="(product, idx) in form.products" :key="idx" class="border border-gray-200 rounded-md p-3">
                <div class="flex items-start justify-between mb-2">
                  <input v-model="product.name" type="text" :placeholder="$t('documents.product_name', 'Product name')" class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm" />
                  <button @click="form.products.splice(idx, 1)" class="ml-2 text-red-400 hover:text-red-600">
                    <XMarkIcon class="h-4 w-4" />
                  </button>
                </div>
                <div class="grid grid-cols-4 gap-2">
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.product_code', 'Code/SKU') }}</label>
                    <input v-model="product.code" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.product_unit', 'Unit') }}</label>
                    <input v-model="product.unit" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.price', 'Price') }}</label>
                    <input :value="formatCents(product.unit_price)" @input="product.unit_price = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-400">{{ $t('documents.product_barcode', 'Barcode') }}</label>
                    <input v-model="product.barcode" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-3 text-xs text-gray-500">
              {{ form.products.length }} {{ $t('documents.products_to_import', 'products to import') }}
            </div>
          </div>
        </template>

        <!-- ===== TAX FORM (EDITABLE) ===== -->
        <template v-if="selectedEntityType === 'tax_form'">
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-medium text-gray-700">{{ $t('documents.tax_form_type', 'Tax Form') }}</h3>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                {{ form.taxForm.form_type || 'UJP' }}
              </span>
            </div>
          </div>

          <!-- Declarant -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.declarant', 'Declarant') }}</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.declarant_name', 'Name') }}</label>
                <input v-model="form.taxForm.declarant.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.tax_id', 'Tax ID (EDB)') }}</label>
                <input v-model="form.taxForm.declarant.tax_id" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
              <div class="col-span-2">
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.supplier_address', 'Address') }}</label>
                <input v-model="form.taxForm.declarant.address" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
              </div>
            </div>
          </div>

          <!-- Period -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.tax_period', 'Period') }}</h3>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.tax_year', 'Year') }}</label>
                <input v-model.number="form.taxForm.period.year" type="number" min="2020" max="2030" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.tax_month', 'Month') }}</label>
                <input v-model.number="form.taxForm.period.month" type="number" min="1" max="12" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.tax_quarter', 'Quarter') }}</label>
                <input v-model.number="form.taxForm.period.quarter" type="number" min="1" max="4" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
            </div>
          </div>

          <!-- Fields (key-value pairs) -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-medium text-gray-700">{{ $t('documents.tax_fields', 'Form Fields') }}</h3>
              <button @click="addTaxField" class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                + {{ $t('documents.add_field', 'Add Field') }}
              </button>
            </div>
            <div class="space-y-2">
              <div v-for="(field, idx) in form.taxForm.fieldsList" :key="idx" class="flex gap-2">
                <input v-model="field.key" type="text" :placeholder="$t('documents.field_name', 'Field label')" class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm" />
                <input v-model="field.value" type="text" :placeholder="$t('documents.field_value', 'Value')" class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm" />
                <button @click="form.taxForm.fieldsList.splice(idx, 1)" class="text-red-400 hover:text-red-600">
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Totals -->
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.tax_totals', 'Totals') }}</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.total_income', 'Total Income') }}</label>
                <input v-model="form.taxForm.totals.total_income" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.total_deductions', 'Total Deductions') }}</label>
                <input v-model="form.taxForm.totals.total_deductions" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.total_tax', 'Total Tax') }}</label>
                <input v-model="form.taxForm.totals.total_tax" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.total_to_pay', 'Amount to Pay') }}</label>
                <input v-model="form.taxForm.totals.total_to_pay" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
            </div>
          </div>
        </template>

        <!-- ===== CONTRACT (EDITABLE) ===== -->
        <template v-if="selectedEntityType === 'contract'">
          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.contract_summary', 'Summary') }}</h3>
            <textarea v-model="form.contract.summary" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500"></textarea>
          </div>

          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.contract_parties', 'Parties') }}</h3>
            <div v-for="(party, idx) in form.contract.parties" :key="idx" class="flex gap-2 mb-2">
              <input v-model="party.name" type="text" :placeholder="$t('documents.party_name', 'Name')" class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm" />
              <input v-model="party.role" type="text" :placeholder="$t('documents.party_role', 'Role')" class="w-32 px-2 py-1 border border-gray-300 rounded text-sm" />
              <button @click="form.contract.parties.splice(idx, 1)" class="text-red-400 hover:text-red-600">
                <XMarkIcon class="h-4 w-4" />
              </button>
            </div>
            <button @click="form.contract.parties.push({ name: '', role: '' })" class="text-xs text-primary-600 hover:text-primary-800 font-medium">
              + {{ $t('documents.add_party', 'Add Party') }}
            </button>
          </div>

          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.contract_dates', 'Dates') }}</h3>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.start_date', 'Start Date') }}</label>
                <input v-model="form.contract.dates.start" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.end_date', 'End Date') }}</label>
                <input v-model="form.contract.dates.end" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.execution_date', 'Execution Date') }}</label>
                <input v-model="form.contract.dates.execution" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.contract_amounts', 'Amounts') }}</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.contract_value', 'Value') }}</label>
                <input v-model="form.contract.amounts.value" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.currency', 'Currency') }}</label>
                <input v-model="form.contract.amounts.currency" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" />
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.contract_notes', 'Notes') }}</h3>
            <textarea v-model="form.contract.notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500"></textarea>
          </div>
        </template>
      </div>
    </div>

    <!-- Not found -->
    <div v-else class="flex items-center justify-center py-20">
      <p class="text-gray-500">{{ $t('documents.not_found', 'Document not found.') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDocumentHubStore } from '@/scripts/admin/stores/document-hub'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { ArrowLeftIcon, XMarkIcon, DocumentTextIcon, TrashIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const store = useDocumentHubStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()

const document = ref(null)
const isLoading = ref(true)
const isSubmitting = ref(false)
const previewUrl = ref('')
const previewError = ref(false)
const selectedEntityType = ref('bill')
const bankAccounts = ref([])

const form = reactive({
  supplier: { name: '', tax_id: '', address: '', email: '' },
  customer: { name: '', email: '', phone: '', tax_id: '' },
  bill: { bill_number: '', bill_date: '', due_date: '', currency_id: null, sub_total: 0, tax: 0, total: 0, discount: 0, discount_val: 0, due_amount: 0, exchange_rate: 1 },
  invoice: { invoice_number: '', invoice_date: '', due_date: '', sub_total: 0, tax: 0, total: 0 },
  expense: { expense_date: '', category: '', amount: 0, notes: '' },
  items: [],
  transactions: [],
  products: [],
  bankAccountId: null,
  taxForm: {
    form_type: '',
    declarant: { name: '', tax_id: '', address: '' },
    period: { year: null, month: null, quarter: null },
    fieldsList: [],
    totals: { total_income: null, total_deductions: null, total_tax: null, total_to_pay: null },
  },
  contract: {
    summary: '',
    parties: [],
    dates: { start: '', end: '', execution: '' },
    amounts: { value: '', currency: 'MKD' },
    notes: '',
  },
})

// Map AI classification to default entity type
const inferEntityType = (doc) => {
  const type = doc.ai_classification?.type || 'other'
  const map = {
    invoice: 'bill',
    receipt: 'expense',
    bank_statement: 'bank_transactions',
    product_list: 'items',
    tax_form: 'tax_form',
    contract: 'contract',
  }
  return map[type] || 'bill'
}

// Available entity types for the selector
const availableEntityTypes = computed(() => [
  { value: 'bill', label: t('documents.confirm_as_bill', 'Bill') },
  { value: 'expense', label: t('documents.confirm_as_expense', 'Expense') },
  { value: 'invoice', label: t('documents.confirm_as_invoice', 'Invoice') },
  { value: 'bank_transactions', label: t('documents.confirm_as_transactions', 'Transactions') },
  { value: 'items', label: t('documents.confirm_as_items', 'Items') },
  { value: 'tax_form', label: t('documents.confirm_as_tax_form', 'Tax Form') },
  { value: 'contract', label: t('documents.confirm_as_contract', 'Contract') },
])

// State checks
const isConfirmed = computed(() => document.value?.processing_status === 'confirmed')
const isExtracted = computed(() => document.value?.processing_status === 'extracted')

// Link to the created entity (for confirmed documents)
const linkedEntityRoute = computed(() => {
  const doc = document.value
  if (!doc) return null
  if (doc.linked_bill_id) return { name: 'bills.view', params: { id: doc.linked_bill_id } }
  if (doc.linked_expense_id) return { name: 'expenses.edit', params: { id: doc.linked_expense_id } }
  if (doc.linked_invoice_id) return { name: 'invoices.view', params: { id: doc.linked_invoice_id } }
  return null
})

const linkedEntityLabel = computed(() => {
  const doc = document.value
  if (!doc) return ''
  if (doc.linked_bill_id) return t('documents.view_bill', 'View Bill')
  if (doc.linked_expense_id) return t('documents.view_expense', 'View Expense')
  if (doc.linked_invoice_id) return t('documents.view_invoice', 'View Invoice')
  return ''
})

// Dynamic confirm button label
const confirmButtonLabel = computed(() => {
  const labels = {
    bill: t('documents.confirm_create_bill', 'Confirm & Create Bill'),
    expense: t('documents.confirm_create_expense', 'Confirm & Create Expense'),
    invoice: t('documents.confirm_create_invoice', 'Confirm & Create Invoice'),
    bank_transactions: t('documents.confirm_import_transactions', 'Confirm & Import'),
    items: t('documents.confirm_import_items', 'Confirm & Import Items'),
    tax_form: t('documents.save_tax_form', 'Save Tax Form'),
    contract: t('documents.save_contract', 'Save Contract'),
  }
  return labels[selectedEntityType.value] || labels.bill
})

onMounted(async () => {
  const id = route.params.id
  try {
    const doc = await store.fetchDocument(id)
    document.value = doc
    const companyId = window.Ls?.get('selectedCompany') || doc.company_id
    previewUrl.value = `/api/v1/client-documents/${id}/download?company=${companyId}`

    // Check if file exists on storage (server-side check)
    if (!doc.file_available) {
      previewError.value = true
    }

    // Set default entity type from AI classification
    selectedEntityType.value = inferEntityType(doc)

    // Pre-fill form from extracted data
    if (doc.extracted_data) {
      prefillForm(doc.extracted_data, doc.ai_classification?.type)
    }

    // Fetch bank accounts for bank_transactions form
    try {
      const { data } = await window.axios.get('/banking/accounts')
      bankAccounts.value = data.data || []
    } catch {
      // Banking accounts may not be available (requires Business tier)
      bankAccounts.value = []
    }
  } catch {
    document.value = null
  } finally {
    isLoading.value = false
  }
})

const prefillForm = (data, aiType) => {
  // Bill/Invoice common fields
  if (data.supplier) {
    form.supplier = { ...form.supplier, ...data.supplier }
  }
  if (data.bill) {
    form.bill = { ...form.bill, ...data.bill }
  }
  if (data.items) {
    form.items = data.items.map((item) => ({ ...item }))
  }

  // Customer (for outgoing invoice)
  if (data.customer) {
    form.customer = { ...form.customer, ...data.customer }
  }
  if (data.invoice) {
    form.invoice = { ...form.invoice, ...data.invoice }
  }
  // When AI extracts as invoice, totals may be in data.bill — copy to form.invoice too
  if (data.bill && !data.invoice?.sub_total) {
    form.invoice.sub_total = data.bill.sub_total || 0
    form.invoice.tax = data.bill.tax || 0
    form.invoice.total = data.bill.total || 0
  }

  // Expense
  if (data.expense) {
    form.expense = { ...form.expense, ...data.expense }
  } else if (aiType === 'receipt' && data.bill) {
    // Map bill data to expense for receipts
    form.expense.expense_date = data.bill.bill_date || ''
    form.expense.amount = data.bill.total || 0
    form.expense.category = data.summary || ''
  }

  // Bank transactions
  if (data.transactions) {
    form.transactions = data.transactions.map((t) => ({ ...t }))
  }

  // Products
  if (data.products) {
    form.products = data.products.map((p) => ({ ...p }))
  }

  // Tax form
  if (data.form_type || data.declarant || data.fields) {
    form.taxForm.form_type = data.form_type || ''
    if (data.declarant) form.taxForm.declarant = { ...form.taxForm.declarant, ...data.declarant }
    if (data.period) form.taxForm.period = { ...form.taxForm.period, ...data.period }
    if (data.fields) {
      form.taxForm.fieldsList = Object.entries(data.fields).map(([key, value]) => ({ key, value: String(value) }))
    }
    if (data.totals) form.taxForm.totals = { ...form.taxForm.totals, ...data.totals }
  }

  // Contract
  if (data.summary !== undefined && (aiType === 'contract' || data.parties)) {
    form.contract.summary = data.summary || ''
    if (data.parties) form.contract.parties = data.parties.map((p) => ({ ...p }))
    if (data.dates) form.contract.dates = { ...form.contract.dates, ...data.dates }
    if (data.amounts) form.contract.amounts = { ...form.contract.amounts, ...data.amounts }
    if (data.notes) form.contract.notes = data.notes
  }
}

const confirmEntity = async () => {
  // Validate required fields per entity type
  if (selectedEntityType.value === 'bank_transactions' && !form.bankAccountId) {
    notificationStore.showNotification({
      type: 'error',
      message: t('documents.select_bank_account_required', 'Please select a bank account before importing transactions.'),
    })
    return
  }

  isSubmitting.value = true
  try {
    let payload = {}

    switch (selectedEntityType.value) {
      case 'bill':
        payload = { supplier: form.supplier, bill: form.bill, items: form.items }
        break
      case 'expense':
        payload = { supplier: form.supplier, expense: form.expense }
        break
      case 'invoice':
        payload = { customer: form.customer, invoice: form.invoice, items: form.items }
        break
      case 'bank_transactions':
        payload = { bank_account_id: form.bankAccountId, transactions: form.transactions }
        break
      case 'items':
        payload = { products: form.products, currency: 'MKD' }
        break
      case 'tax_form': {
        // Convert fieldsList back to object
        const fields = {}
        form.taxForm.fieldsList.forEach((f) => { if (f.key) fields[f.key] = f.value })
        payload = {
          form_type: form.taxForm.form_type,
          declarant: form.taxForm.declarant,
          period: form.taxForm.period,
          fields,
          totals: form.taxForm.totals,
        }
        break
      }
      case 'contract':
        payload = {
          summary: form.contract.summary,
          parties: form.contract.parties,
          dates: form.contract.dates,
          amounts: form.contract.amounts,
          notes: form.contract.notes,
        }
        break
    }

    const data = await store.confirmDocument(document.value.id, payload, selectedEntityType.value)

    notificationStore.showNotification({
      type: 'success',
      message: t('documents.entity_created', 'Confirmed successfully!'),
    })

    // Navigate to destination
    const result = data.data || {}
    const routes = {
      bill: result.bill_id ? { name: 'bills.view', params: { id: result.bill_id } } : null,
      expense: result.expense_id ? { name: 'expenses.edit', params: { id: result.expense_id } } : null,
      invoice: result.invoice_id ? { name: 'invoices.view', params: { id: result.invoice_id } } : null,
      bank_transactions: { name: 'banking.transactions' },
      items: { name: 'items.index' },
    }

    const dest = routes[selectedEntityType.value]
    if (dest) {
      router.push(dest)
    } else {
      // tax_form / contract — stay on page with success
      document.value.processing_status = 'confirmed'
      document.value.status = 'reviewed'
    }
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || t('documents.confirm_failed', 'Confirmation failed.'),
    })
  } finally {
    isSubmitting.value = false
  }
}

const reprocess = async () => {
  const confirmed = await dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('documents.confirm_reprocess', 'This will reset all extracted data and re-run AI processing. Continue?'),
    yesLabel: t('general.ok'),
    noLabel: t('general.cancel'),
    variant: 'primary',
    hideNoButton: false,
    size: 'lg',
  })

  if (!confirmed) return

  isSubmitting.value = true
  try {
    await store.reprocessDocument(document.value.id)
    notificationStore.showNotification({
      type: 'success',
      message: t('documents.reprocess_started', 'Reprocessing started.'),
    })
    router.push({ name: 'client-documents' })
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || t('documents.reprocess_failed', 'Reprocess failed.'),
    })
  } finally {
    isSubmitting.value = false
  }
}
const deleteDocument = async () => {
  const confirmed = await dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('documents.confirm_delete', 'This will permanently delete the document and its file. This cannot be undone.'),
    yesLabel: t('general.delete'),
    noLabel: t('general.cancel'),
    variant: 'danger',
    hideNoButton: false,
    size: 'lg',
  })

  if (!confirmed) return

  isSubmitting.value = true
  try {
    await store.deleteDocument(document.value.id)
    notificationStore.showNotification({
      type: 'success',
      message: t('documents.deleted', 'Document deleted successfully.'),
    })
    router.push({ name: 'client-documents' })
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || t('documents.delete_failed', 'Failed to delete document.'),
    })
  } finally {
    isSubmitting.value = false
  }
}
// CLAUDE-CHECKPOINT

const addBillItem = () => {
  form.items.push({
    name: '', description: null, quantity: 1, price: 0, tax: 0, total: 0,
    discount: 0, discount_val: 0, base_price: 0, base_total: 0, base_tax: 0, base_discount_val: 0,
  })
}

const addProductItem = () => {
  form.products.push({ name: '', code: '', unit: '', unit_price: 0, quantity: null, barcode: '' })
}

const addTaxField = () => {
  form.taxForm.fieldsList.push({ key: '', value: '' })
}

const formatCents = (cents) => {
  if (!cents && cents !== 0) return '0.00'
  return (Number(cents) / 100).toFixed(2)
}

const parseCents = (val) => {
  const num = parseFloat(val)
  return isNaN(num) ? 0 : Math.round(num * 100)
}

const getCategoryLabel = (type) => {
  const labels = {
    invoice: t('documents.type_invoice', 'Invoice'),
    receipt: t('documents.type_receipt', 'Receipt'),
    contract: t('documents.type_contract', 'Contract'),
    bank_statement: t('documents.type_bank_statement', 'Bank Statement'),
    tax_form: t('documents.type_tax_form', 'Tax Form'),
    product_list: t('documents.type_product_list', 'Product List'),
    other: t('documents.type_other', 'Other'),
  }
  return labels[type] || type
}

const getCategoryBadgeClass = (type) => {
  const classes = {
    invoice: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
    receipt: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800',
    contract: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800',
    bank_statement: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800',
    tax_form: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800',
    product_list: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
    other: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
  }
  return classes[type] || classes.other
}
</script>
