<script setup>
import { useI18n } from 'vue-i18n'
import { computed, reactive, ref, watch, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { debounce } from 'lodash'

import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useModalStore } from '@/scripts/stores/modal'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useNotificationStore } from '@/scripts/stores/notification'

import SendInvoiceModal from '@/scripts/admin/components/modal-components/SendInvoiceModal.vue'
import ExportXmlModal from '@/scripts/admin/components/modal-components/ExportXmlModal.vue'
import InvoiceDropdown from '@/scripts/admin/components/dropdowns/InvoiceIndexDropdown.vue'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'
import EInvoiceTab from '@/scripts/admin/views/invoices/partials/EInvoiceTab.vue'
import EInvoiceInboxTab from '@/scripts/admin/views/invoices/partials/EInvoiceInboxTab.vue'
import ProfitTab from '@/scripts/admin/views/invoices/partials/ProfitTab.vue'
import FiscalizeButton from '@/scripts/admin/components/FiscalizeButton.vue'

import abilities from '@/scripts/admin/stub/abilities'

const modalStore = useModalStore()
const invoiceStore = useInvoiceStore()
const userStore = useUserStore()
const dialogStore = useDialogStore()
const globalStore = useGlobalStore()
const notificationStore = useNotificationStore()

const { t } = useI18n()
const invoiceData = ref(null)
const route = useRoute()

const isMarkAsSent = ref(false)
const isLoading = ref(false)
const isCpayProcessing = ref(false)

const invoiceList = ref(null)
const currentPageNumber = ref(1)
const lastPageNumber = ref(1)
const invoiceListSection = ref(null)

const searchData = reactive({
  orderBy: null,
  orderByField: null,
  searchText: null,
})

const pageTitle = computed(() => {
  let title = invoiceData.value.invoice_number
  if (invoiceData.value.type === 'advance') {
    title = `${t('invoices.type_advance')} — ${title}`
  } else if (invoiceData.value.type === 'final') {
    title = `${t('invoices.type_final')} — ${title}`
  }
  return title
})

const getOrderBy = computed(() => {
  if (searchData.orderBy === 'asc' || searchData.orderBy == null) {
    return true
  }
  return false
})

const getOrderName = computed(() => {
  if (getOrderBy.value) {
    return t('general.ascending')
  }
  return t('general.descending')
})

const shareableLink = computed(() => {
  return `/invoices/pdf/${invoiceData.value.unique_hash}`
})

const getCurrentInvoiceId = computed(() => {
  if (invoiceData.value && invoiceData.value.id) {
    return invoiceData.value.id
  }
  return null
})

watch(route, (to, from) => {
  if (to.name === 'invoices.view') {
    loadInvoice()
  }
})

async function onMarkAsSent() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.invoice_mark_as_sent'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      isMarkAsSent.value = false
      if (response) {
        await invoiceStore.markAsSent({
          id: invoiceData.value.id,
          status: 'SENT',
        })
        invoiceData.value.status = 'SENT'
        isMarkAsSent.value = true
      }
      isMarkAsSent.value = false
    })
}

async function onSendInvoice(id) {
  modalStore.openModal({
    title: t('invoices.send_invoice'),
    componentName: 'SendInvoiceModal',
    id: invoiceData.value.id,
    data: invoiceData.value,
  })
}

async function onPayWithCpay() {
  isCpayProcessing.value = true
  try {
    await invoiceStore.initiateCpayCheckout(invoiceData.value.id)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('payments.cpay.checkout_error'),
    })
  } finally {
    isCpayProcessing.value = false
  }
}

function hasActiveUrl(id) {
  return route.params.id == id
}

async function loadInvoices(pageNumber, fromScrollListener = false) {
  if (isLoading.value) {
    return
  }

  let params = {}
  if (
    searchData.searchText !== '' &&
    searchData.searchText !== null &&
    searchData.searchText !== undefined
  ) {
    params.search = searchData.searchText
  }

  if (searchData.orderBy !== null && searchData.orderBy !== undefined) {
    params.orderBy = searchData.orderBy
  }

  if (
    searchData.orderByField !== null &&
    searchData.orderByField !== undefined
  ) {
    params.orderByField = searchData.orderByField
  }

  isLoading.value = true
  let response = await invoiceStore.fetchInvoices({
    page: pageNumber,
    ...params,
  })
  isLoading.value = false

  invoiceList.value = invoiceList.value ? invoiceList.value : []
  invoiceList.value = [...invoiceList.value, ...response.data.data]

  currentPageNumber.value = pageNumber ? pageNumber : 1
  lastPageNumber.value = response.data.meta.last_page
  let invoiceFound = invoiceList.value.find((inv) => inv.id == route.params.id)

  if (
    fromScrollListener == false &&
    !invoiceFound &&
    currentPageNumber.value < lastPageNumber.value &&
    Object.keys(params).length === 0
  ) {
    loadInvoices(++currentPageNumber.value)
  }

  if (invoiceFound) {
    setTimeout(() => {
      if (fromScrollListener == false) {
        scrollToInvoice()
      }
    }, 500)
  }
}

function scrollToInvoice() {
  const el = document.getElementById(`invoice-${route.params.id}`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
    el.classList.add('shake')
    addScrollListener()
  }
}

// Store the scroll handler reference for cleanup
let scrollHandler = null

function addScrollListener() {
  if (invoiceListSection.value && !scrollHandler) {
    scrollHandler = (ev) => {
      if (
        ev.target.scrollTop > 0 &&
        ev.target.scrollTop + ev.target.clientHeight >
          ev.target.scrollHeight - 200
      ) {
        if (currentPageNumber.value < lastPageNumber.value) {
          loadInvoices(++currentPageNumber.value, true)
        }
      }
    }
    invoiceListSection.value.addEventListener('scroll', scrollHandler)
  }
}

// CLAUDE-CHECKPOINT
onUnmounted(() => {
  if (invoiceListSection.value && scrollHandler) {
    invoiceListSection.value.removeEventListener('scroll', scrollHandler)
    scrollHandler = null
  }
})

async function loadInvoice() {
  let response = await invoiceStore.fetchInvoice(route.params.id)
  if (response.data) {
    invoiceData.value = { ...response.data.data }
  }
}

async function onSearched() {
  invoiceList.value = []
  loadInvoices()
}

function sortData() {
  if (searchData.orderBy === 'asc') {
    searchData.orderBy = 'desc'
    onSearched()
    return true
  }
  searchData.orderBy = 'asc'
  onSearched()
  return true
}

function updateSentInvoice() {
  const invoice = invoiceList.value.find(
    (inv) => inv.id === invoiceData.value.id
  )

  if (invoice) {
    invoice.status = 'SENT'
    invoiceData.value.status = 'SENT'
  }
}
// CLAUDE-CHECKPOINT

loadInvoices()
loadInvoice()
onSearched = debounce(onSearched, 500)
</script>

<template>
  <SendInvoiceModal @update="updateSentInvoice" />
  <ExportXmlModal />

  <BasePage v-if="invoiceData" class="xl:pl-96 xl:ml-8">
    <BasePageHeader :title="pageTitle">
      <template #actions>
        <div class="text-sm mr-3">
          <BaseButton
            v-if="
              invoiceData.status === 'DRAFT' &&
              userStore.hasAbilities(abilities.EDIT_INVOICE)
            "
            :disabled="isMarkAsSent"
            variant="primary-outline"
            @click="onMarkAsSent"
          >
            {{ $t('invoices.mark_as_sent') }}
          </BaseButton>
        </div>

        <BaseButton
          v-if="
            invoiceData.status === 'DRAFT' &&
            userStore.hasAbilities(abilities.SEND_INVOICE)
          "
          variant="primary"
          class="text-sm"
          @click="onSendInvoice"
        >
          {{ $t('invoices.send_invoice') }}
        </BaseButton>

        <!-- Pay with CPAY  -->
        <BaseButton
          v-if="
            (invoiceData.status === 'SENT' || invoiceData.status === 'VIEWED') &&
            invoiceData.status !== 'PAID' &&
            userStore.hasAbilities(abilities.CREATE_PAYMENT) &&
            globalStore.featureFlags?.['advanced_payments'] === true
          "
          variant="primary"
          class="mr-3"
          :disabled="isCpayProcessing"
          @click="onPayWithCpay"
        >
          <LoadingIcon
            v-if="isCpayProcessing"
            class="h-5 w-5 mr-2 animate-spin"
          />
          {{ isCpayProcessing ? $t('payments.cpay.processing') : $t('payments.cpay.pay_now') }}
        </BaseButton>

        <!-- Record Payment  -->
        <router-link
          v-if="userStore.hasAbilities(abilities.CREATE_PAYMENT)"
          :to="`/admin/payments/${$route.params.id}/create`"
        >
          <BaseButton
            v-if="
              invoiceData.status === 'SENT' || invoiceData.status === 'VIEWED'
            "
            variant="primary"
          >
            {{ $t('invoices.record_payment') }}
          </BaseButton>
        </router-link>

        <!-- Fiscal Receipt (WebSerial) -->
        <FiscalizeButton
          v-if="invoiceData.status !== 'DRAFT'"
          :invoice="invoiceData"
          class="ml-3"
        />

        <!-- Invoice Dropdown  -->
        <InvoiceDropdown
          class="ml-3"
          :row="invoiceData"
          :load-data="loadInvoices"
        />
      </template>
    </BasePageHeader>

    <!-- sidebar -->
    <div
      :class="[
        'fixed top-0 left-0 hidden h-full pt-16 pb-[6.4rem] bg-white w-88 xl:block',
        globalStore.isSidebarCollapsed ? 'ml-16' : 'ml-56 xl:ml-64'
      ]"
    >
      <div
        class="
          flex
          items-center
          justify-between
          px-4
          pt-8
          pb-2
          border border-gray-200 border-solid
          height-full
        "
      >
        <div class="mb-6">
          <BaseInput
            v-model="searchData.searchText"
            :placeholder="$t('general.search')"
            type="text"
            variant="gray"
            @input="onSearched()"
          >
            <template #right>
              <BaseIcon name="MagnifyingGlassIcon" class="h-5 text-gray-400" />
            </template>
          </BaseInput>
        </div>

        <div class="flex mb-6 ml-3" role="group" aria-label="First group">
          <BaseDropdown class="ml-3" position="bottom-start">
            <template #activator>
              <BaseButton size="md" variant="gray">
                <BaseIcon name="FunnelIcon" />
              </BaseButton>
            </template>
            <div
              class="
                px-2
                py-1
                pb-2
                mb-1 mb-2
                text-sm
                border-b border-gray-200 border-solid
              "
            >
              {{ $t('general.sort_by') }}
            </div>

            <BaseDropdownItem class="flex px-1 py-2 cursor-pointer">
              <BaseInputGroup class="-mt-3 font-normal">
                <BaseRadio
                  id="filter_invoice_date"
                  v-model="searchData.orderByField"
                  :label="$t('reports.invoices.invoice_date')"
                  size="sm"
                  name="filter"
                  value="invoice_date"
                  @update:modelValue="onSearched"
                />
              </BaseInputGroup>
            </BaseDropdownItem>

            <BaseDropdownItem class="flex px-1 py-2 cursor-pointer">
              <BaseInputGroup class="-mt-3 font-normal">
                <BaseRadio
                  id="filter_due_date"
                  v-model="searchData.orderByField"
                  :label="$t('invoices.due_date')"
                  value="due_date"
                  size="sm"
                  name="filter"
                  @update:modelValue="onSearched"
                />
              </BaseInputGroup>
            </BaseDropdownItem>

            <BaseDropdownItem class="flex px-1 py-2 cursor-pointer">
              <BaseInputGroup class="-mt-3 font-normal">
                <BaseRadio
                  id="filter_invoice_number"
                  v-model="searchData.orderByField"
                  :label="$t('invoices.invoice_number')"
                  value="invoice_number"
                  size="sm"
                  name="filter"
                  @update:modelValue="onSearched"
                />
              </BaseInputGroup>
            </BaseDropdownItem>
          </BaseDropdown>

          <BaseButton class="ml-1" size="md" variant="gray" @click="sortData">
            <BaseIcon v-if="getOrderBy" name="BarsArrowUpIcon" />
            <BaseIcon v-else name="BarsArrowDownIcon" />
          </BaseButton>
        </div>
      </div>

      <div
        ref="invoiceListSection"
        class="
          h-full
          overflow-y-scroll
          border-l border-gray-200 border-solid
          base-scroll
        "
      >
        <div v-for="(invoice, index) in invoiceList" :key="index">
          <router-link
            v-if="invoice"
            :id="'invoice-' + invoice.id"
            :to="`/admin/invoices/${invoice.id}/view`"
            :class="[
              'flex justify-between side-invoice p-4 cursor-pointer hover:bg-gray-100 items-center border-l-4 border-transparent',
              {
                'bg-gray-100 border-l-4 border-primary-500 border-solid':
                  hasActiveUrl(invoice.id),
              },
            ]"
            style="border-bottom: 1px solid rgba(185, 193, 209, 0.41)"
          >
            <div class="flex-2">
              <BaseText
                :text="invoice.customer.name"
                class="
                  pr-2
                  mb-2
                  text-sm
                  not-italic
                  font-normal
                  leading-5
                  text-black
                  capitalize
                  truncate
                "
              />

              <div
                class="
                  mt-1
                  mb-2
                  text-xs
                  not-italic
                  font-medium
                  leading-5
                  text-gray-600
                  flex items-center gap-1
                "
              >
                {{ invoice.invoice_number }}
                <span
                  v-if="invoice.type === 'advance'"
                  class="inline-flex px-1 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800"
                >
                  {{ $t('invoices.type_advance') }}
                </span>
                <span
                  v-if="invoice.type === 'final'"
                  class="inline-flex px-1 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800"
                >
                  {{ $t('invoices.type_final') }}
                </span>
              </div>
              <BaseEstimateStatusBadge
                :status="invoice.status"
                class="px-1 text-xs"
              >
                <BaseInvoiceStatusLabel :status="invoice.status" />
              </BaseEstimateStatusBadge>
            </div>

            <div class="flex-1 whitespace-nowrap right">
              <BaseFormatMoney
                class="
                  mb-2
                  text-xl
                  not-italic
                  font-semibold
                  leading-8
                  text-right text-gray-900
                  block
                "
                :amount="invoice.total"
                :currency="invoice.customer.currency"
              />
              <div
                class="
                  text-sm
                  not-italic
                  font-normal
                  leading-5
                  text-right text-gray-600
                  est-date
                "
              >
                {{ invoice.formatted_invoice_date }}
              </div>
            </div>
          </router-link>
        </div>
        <div v-if="isLoading" class="flex justify-center p-4 items-center">
          <LoadingIcon class="h-6 m-1 animate-spin text-primary-400" />
        </div>
        <p
          v-if="!invoiceList?.length && !isLoading"
          class="flex justify-center px-4 mt-5 text-sm text-gray-600"
        >
          {{ $t('invoices.no_matching_invoices') }}
        </p>
      </div>
    </div>

    <BaseCard class="mt-8">
      <BaseTabGroup>
        <BaseTab
          tab-panel-container="py-4 mt-px"
          :title="$t('invoices.details')"
        >
          <div
            class="flex flex-col min-h-0 overflow-hidden"
            style="height: 75vh"
          >
            <iframe
              :src="`${shareableLink}`"
              class="
                flex-1
                border border-gray-400 border-solid
                bg-white
                rounded-md
                frame-style
              "
            />
          </div>
        </BaseTab>

        <!-- Advance Settlement Tab (only for final invoices with linked advances) -->
        <BaseTab
          v-if="invoiceData.type === 'final' && invoiceData.advance_invoices?.length"
          tab-panel-container="py-4 mt-px"
          :title="$t('invoices.settlement_details')"
        >
          <div class="p-4 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ $t('invoices.linked_advances') }}
            </h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                      {{ $t('invoices.invoice_number') }}
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                      {{ $t('invoices.date') }}
                    </th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                      {{ $t('invoices.total') }}
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="adv in invoiceData.advance_invoices" :key="adv.id">
                    <td class="px-4 py-2 text-sm">
                      <router-link
                        :to="`/admin/invoices/${adv.id}/view`"
                        class="text-primary-500 font-medium"
                      >
                        {{ adv.invoice_number }}
                      </router-link>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-600">
                      {{ adv.formatted_invoice_date }}
                    </td>
                    <td class="px-4 py-2 text-sm text-right font-medium">
                      <BaseFormatMoney
                        :amount="adv.total"
                        :currency="invoiceData.currency"
                      />
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                  <tr>
                    <td colspan="2" class="px-4 py-2 text-sm font-semibold text-gray-700">
                      {{ $t('invoices.total_advances') }}
                    </td>
                    <td class="px-4 py-2 text-sm text-right font-bold text-amber-700">
                      <BaseFormatMoney
                        :amount="invoiceData.total_advances_amount"
                        :currency="invoiceData.currency"
                      />
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2" class="px-4 py-2 text-sm font-semibold text-gray-700">
                      {{ $t('invoices.remaining_after_advances') }}
                    </td>
                    <td class="px-4 py-2 text-sm text-right font-bold text-green-700">
                      <BaseFormatMoney
                        :amount="invoiceData.remaining_after_advances"
                        :currency="invoiceData.currency"
                      />
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </BaseTab>

        <BaseTab
          tab-panel-container="py-4 mt-px"
          :title="$t('e_invoice.title')"
        >
          <EInvoiceTab :invoice="invoiceData" />
        </BaseTab>

        <BaseTab
          tab-panel-container="py-4 mt-px"
          :title="$t('e_invoice.incoming_inbox')"
        >
          <EInvoiceInboxTab />
        </BaseTab>

        <!-- Profit Tab (only shown when stock is enabled) -->
        <BaseTab
          v-if="invoiceData.profit !== undefined"
          tab-panel-container="py-4 mt-px"
          :title="$t('profit.title')"
        >
          <ProfitTab
            :profit="invoiceData.profit"
            :currency="invoiceData.currency"
          />
        </BaseTab>

        <!-- Email History Tab -->
        <BaseTab
          v-if="invoiceData.email_logs?.length"
          tab-panel-container="py-4 mt-px"
          :title="`${$t('general.email_history')} (${invoiceData.email_logs.length})`"
        >
          <div class="p-4 space-y-3">
            <div
              v-for="log in invoiceData.email_logs"
              :key="log.id"
              class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100"
            >
              <BaseIcon name="EnvelopeIcon" class="h-5 w-5 text-gray-400 mt-0.5 flex-shrink-0" />
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <span class="text-sm font-medium text-gray-900 truncate">{{ log.to }}</span>
                  <span class="text-xs text-gray-500 whitespace-nowrap">
                    {{ new Date(log.created_at).toLocaleString() }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ log.subject }}</p>
              </div>
            </div>
          </div>
        </BaseTab>

        <!-- Source Document Tab (shown when original document was attached via AI Hub) -->
        <BaseTab
          v-if="invoiceData.source_document_url"
          tab-panel-container="py-4 mt-px"
          :title="$t('invoices.source_document', 'Source Document')"
        >
          <BaseCard>
            <div class="p-6">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                  {{ $t('invoices.source_document', 'Source Document') }}
                </h3>
                <a
                  :href="invoiceData.source_document_url"
                  target="_blank"
                  download
                >
                  <BaseButton variant="primary-outline">
                    {{ $t('general.download') }}
                  </BaseButton>
                </a>
              </div>
              <div class="border rounded-lg overflow-hidden bg-gray-50">
                <iframe
                  :src="invoiceData.source_document_url"
                  class="w-full"
                  style="height: 800px;"
                  frameborder="0"
                />
              </div>
            </div>
          </BaseCard>
        </BaseTab>
      </BaseTabGroup>
    </BaseCard>
  </BasePage>
</template>
