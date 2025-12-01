<script setup>
import { useI18n } from 'vue-i18n'
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { debounce } from 'lodash'

import { useProformaInvoiceStore } from '@/scripts/admin/stores/proforma-invoice'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'

import ProformaInvoiceDropdown from '@/scripts/admin/components/dropdowns/ProformaInvoiceIndexDropdown.vue'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'

import abilities from '@/scripts/admin/stub/abilities'

const proformaInvoiceStore = useProformaInvoiceStore()
const userStore = useUserStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const { t } = useI18n()
const proformaInvoiceData = ref(null)
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isConverting = ref(false)

const proformaInvoiceList = ref(null)
const currentPageNumber = ref(1)
const lastPageNumber = ref(1)
const proformaInvoiceListSection = ref(null)

const searchData = reactive({
  orderBy: null,
  orderByField: null,
  searchText: null,
})

const pageTitle = computed(() => proformaInvoiceData.value?.proforma_invoice_number || '')

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
  return `/proforma-invoices/pdf/${proformaInvoiceData.value?.unique_hash}`
})

watch(route, (to, from) => {
  if (to.name === 'proforma-invoices.view') {
    loadProformaInvoice()
  }
})

async function onConvertToInvoice() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_convert'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      if (response) {
        isConverting.value = true
        try {
          const result = await proformaInvoiceStore.convertToInvoice(proformaInvoiceData.value.id)
          proformaInvoiceData.value.status = 'CONVERTED'

          // Navigate to the new invoice
          if (result.data?.invoice_id) {
            router.push(`/admin/invoices/${result.data.invoice_id}/view`)
          }
        } catch (error) {
          console.error('Convert error:', error)
        } finally {
          isConverting.value = false
        }
      }
    })
}

async function onMarkAsExpired() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_mark_expired'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      if (response) {
        await proformaInvoiceStore.markAsExpired(proformaInvoiceData.value.id)
        proformaInvoiceData.value.status = 'EXPIRED'
      }
    })
}

async function onMarkAsRejected() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_mark_rejected'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      if (response) {
        await proformaInvoiceStore.markAsRejected(proformaInvoiceData.value.id)
        proformaInvoiceData.value.status = 'REJECTED'
      }
    })
}

function hasActiveUrl(id) {
  return route.params.id == id
}

async function loadProformaInvoices(pageNumber, fromScrollListener = false) {
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
  let response = await proformaInvoiceStore.fetchProformaInvoices({
    page: pageNumber,
    ...params,
  })
  isLoading.value = false

  proformaInvoiceList.value = proformaInvoiceList.value ? proformaInvoiceList.value : []
  proformaInvoiceList.value = [...proformaInvoiceList.value, ...response.data.data]

  currentPageNumber.value = pageNumber ? pageNumber : 1
  lastPageNumber.value = response.data.meta.last_page
  let proformaFound = proformaInvoiceList.value.find((p) => p.id == route.params.id)

  if (
    fromScrollListener == false &&
    !proformaFound &&
    currentPageNumber.value < lastPageNumber.value &&
    Object.keys(params).length === 0
  ) {
    loadProformaInvoices(++currentPageNumber.value)
  }

  if (proformaFound) {
    setTimeout(() => {
      if (fromScrollListener == false) {
        scrollToProformaInvoice()
      }
    }, 500)
  }
}

function scrollToProformaInvoice() {
  const el = document.getElementById(`proforma-invoice-${route.params.id}`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
    el.classList.add('shake')
    addScrollListener()
  }
}

function addScrollListener() {
  proformaInvoiceListSection.value?.addEventListener('scroll', (ev) => {
    if (
      ev.target.scrollTop > 0 &&
      ev.target.scrollTop + ev.target.clientHeight >
        ev.target.scrollHeight - 200
    ) {
      if (currentPageNumber.value < lastPageNumber.value) {
        loadProformaInvoices(++currentPageNumber.value, true)
      }
    }
  })
}

async function loadProformaInvoice() {
  let response = await proformaInvoiceStore.fetchProformaInvoice(route.params.id)
  if (response.data) {
    proformaInvoiceData.value = { ...response.data.data }
  }
}

async function onSearched() {
  proformaInvoiceList.value = []
  loadProformaInvoices()
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

function getStatusColor(status) {
  const colors = {
    DRAFT: '#6B7280',
    SENT: '#3B82F6',
    VIEWED: '#F59E0B',
    EXPIRED: '#EF4444',
    CONVERTED: '#10B981',
    REJECTED: '#EF4444',
  }
  return colors[status] || '#6B7280'
}

loadProformaInvoices()
loadProformaInvoice()
onSearched = debounce(onSearched, 500)
</script>

<template>
  <BasePage v-if="proformaInvoiceData" class="xl:pl-96 xl:ml-8">
    <BasePageHeader :title="pageTitle">
      <template #actions>
        <!-- Convert to Invoice Button -->
        <BaseButton
          v-if="
            (proformaInvoiceData.status === 'SENT' || proformaInvoiceData.status === 'VIEWED') &&
            !proformaInvoiceData.is_expired &&
            userStore.hasAbilities(abilities.CREATE_INVOICE)
          "
          variant="primary"
          class="mr-3"
          :disabled="isConverting"
          @click="onConvertToInvoice"
        >
          <LoadingIcon
            v-if="isConverting"
            class="h-5 w-5 mr-2 animate-spin"
          />
          {{ isConverting ? $t('proforma_invoices.converting') : $t('proforma_invoices.convert_to_invoice') }}
        </BaseButton>

        <!-- Mark as Expired -->
        <BaseButton
          v-if="
            proformaInvoiceData.status !== 'CONVERTED' &&
            proformaInvoiceData.status !== 'EXPIRED' &&
            proformaInvoiceData.status !== 'REJECTED' &&
            userStore.hasAbilities(abilities.EDIT_ESTIMATE)
          "
          variant="danger-outline"
          class="mr-3"
          @click="onMarkAsExpired"
        >
          {{ $t('proforma_invoices.mark_as_expired') }}
        </BaseButton>

        <!-- Proforma Invoice Dropdown  -->
        <ProformaInvoiceDropdown
          class="ml-3"
          :row="proformaInvoiceData"
          :load-data="loadProformaInvoices"
        />
      </template>
    </BasePageHeader>

    <!-- Proforma Notice Banner -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-yellow-600 mr-2" />
        <span class="text-sm text-yellow-800 font-medium">
          {{ $t('proforma_invoices.not_fiscal_notice') }}
        </span>
      </div>
    </div>

    <!-- Status Badge -->
    <div class="mb-4">
      <BaseBadge
        :bg-color="getStatusColor(proformaInvoiceData.status)"
        class="px-3 py-1"
      >
        {{ $t(`proforma_invoices.statuses.${proformaInvoiceData.status.toLowerCase()}`) }}
      </BaseBadge>
      <span v-if="proformaInvoiceData.is_expired" class="ml-2 text-red-500 text-sm font-medium">
        ({{ $t('proforma_invoices.expired') }})
      </span>
      <span v-if="proformaInvoiceData.converted_invoice_id" class="ml-2 text-green-600 text-sm">
        <router-link :to="`/admin/invoices/${proformaInvoiceData.converted_invoice_id}/view`" class="underline">
          {{ $t('proforma_invoices.view_converted_invoice') }}
        </router-link>
      </span>
    </div>

    <!-- sidebar -->
    <div
      class="
        fixed
        top-0
        left-0
        hidden
        h-full
        pt-16
        pb-[6.4rem]
        ml-56
        bg-white
        xl:ml-64
        w-88
        xl:block
      "
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
                  id="filter_proforma_invoice_date"
                  v-model="searchData.orderByField"
                  :label="$t('proforma_invoices.date')"
                  size="sm"
                  name="filter"
                  value="proforma_invoice_date"
                  @update:modelValue="onSearched"
                />
              </BaseInputGroup>
            </BaseDropdownItem>

            <BaseDropdownItem class="flex px-1 py-2 cursor-pointer">
              <BaseInputGroup class="-mt-3 font-normal">
                <BaseRadio
                  id="filter_expiry_date"
                  v-model="searchData.orderByField"
                  :label="$t('proforma_invoices.expiry_date')"
                  value="expiry_date"
                  size="sm"
                  name="filter"
                  @update:modelValue="onSearched"
                />
              </BaseInputGroup>
            </BaseDropdownItem>

            <BaseDropdownItem class="flex px-1 py-2 cursor-pointer">
              <BaseInputGroup class="-mt-3 font-normal">
                <BaseRadio
                  id="filter_proforma_invoice_number"
                  v-model="searchData.orderByField"
                  :label="$t('proforma_invoices.proforma_invoice_number')"
                  value="proforma_invoice_number"
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
        ref="proformaInvoiceListSection"
        class="
          h-full
          overflow-y-scroll
          border-l border-gray-200 border-solid
          base-scroll
        "
      >
        <div v-for="(proforma, index) in proformaInvoiceList" :key="index">
          <router-link
            v-if="proforma"
            :id="'proforma-invoice-' + proforma.id"
            :to="`/admin/proforma-invoices/${proforma.id}/view`"
            :class="[
              'flex justify-between side-invoice p-4 cursor-pointer hover:bg-gray-100 items-center border-l-4 border-transparent',
              {
                'bg-gray-100 border-l-4 border-primary-500 border-solid':
                  hasActiveUrl(proforma.id),
              },
            ]"
            style="border-bottom: 1px solid rgba(185, 193, 209, 0.41)"
          >
            <div class="flex-2">
              <BaseText
                :text="proforma.customer?.name || '-'"
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
                "
              >
                {{ proforma.proforma_invoice_number }}
              </div>
              <BaseBadge
                :bg-color="getStatusColor(proforma.status)"
                class="px-1 text-xs"
              >
                {{ $t(`proforma_invoices.statuses.${proforma.status.toLowerCase()}`) }}
              </BaseBadge>
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
                :amount="proforma.total"
                :currency="proforma.currency"
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
                {{ proforma.formatted_proforma_invoice_date }}
              </div>
            </div>
          </router-link>
        </div>
        <div v-if="isLoading" class="flex justify-center p-4 items-center">
          <LoadingIcon class="h-6 m-1 animate-spin text-primary-400" />
        </div>
        <p
          v-if="!proformaInvoiceList?.length && !isLoading"
          class="flex justify-center px-4 mt-5 text-sm text-gray-600"
        >
          {{ $t('proforma_invoices.no_matching_proforma_invoices') }}
        </p>
      </div>
    </div>

    <BaseCard class="mt-8">
      <BaseTabGroup>
        <BaseTab
          tab-panel-container="py-4 mt-px"
          :title="$t('proforma_invoices.details')"
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
      </BaseTabGroup>
    </BaseCard>
  </BasePage>
</template>
// CLAUDE-CHECKPOINT
