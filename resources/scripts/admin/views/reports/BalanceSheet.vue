<template>
  <div class="grid gap-8 md:grid-cols-12 pt-10">
    <div class="col-span-8 md:col-span-4">
      <BaseInputGroup
        :label="$t('reports.accounting.as_of_date')"
        class="col-span-12 md:col-span-8"
      >
        <BaseDatePicker v-model="formData.as_of_date" />
      </BaseInputGroup>

      <BaseButton
        variant="primary-outline"
        class="content-center hidden mt-0 w-md md:flex md:mt-8"
        type="submit"
        @click.prevent="getReports"
      >
        {{ $t('reports.update_report') }}
      </BaseButton>
    </div>

    <div class="col-span-8">
      <!-- Export Dropdown (Desktop) -->
      <div class="hidden md:flex justify-end mb-4">
        <BaseDropdown width-class="w-48">
          <template #activator>
            <BaseButton
              variant="primary-outline"
              :loading="isExporting"
            >
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
              </template>
              {{ $t('general.export') }}
              <template #right="slotProps">
                <BaseIcon :class="slotProps.class" name="ChevronDownIcon" />
              </template>
            </BaseButton>
          </template>

          <BaseDropdownItem>
            <div class="flex items-center" @click="exportReport('csv')">
              <BaseIcon name="DocumentTextIcon" class="h-5 w-5 mr-3 text-gray-400" />
              <span>{{ $t('general.export_csv') }}</span>
            </div>
          </BaseDropdownItem>

          <BaseDropdownItem>
            <div class="flex items-center" @click="exportReport('pdf')">
              <BaseIcon name="DocumentArrowDownIcon" class="h-5 w-5 mr-3 text-gray-400" />
              <span>{{ $t('general.export_pdf') }}</span>
            </div>
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <iframe
        :src="getReportUrl"
        class="
          hidden
          w-full
          h-screen
          border-gray-100 border-solid
          rounded
          md:flex
        "
      />
      <a
        class="
          flex
          items-center
          justify-center
          h-10
          px-5
          py-1
          text-sm
          font-medium
          leading-none
          text-center text-white
          rounded
          whitespace-nowrap
          md:hidden
          bg-primary-500
        "
        @click="viewReportsPDF"
      >
        <BaseIcon name="DocumentTextIcon" class="h-5 mr-2" />
        <span>{{ $t('reports.view_pdf') }}</span>
      </a>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import moment from 'moment'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const globalStore = useGlobalStore()
const companyStore = useCompanyStore()

globalStore.downloadReport = downloadReport

let url = ref(null)
let siteURL = ref(null)
const isExporting = ref(false)

const formData = reactive({
  as_of_date: moment().format('YYYY-MM-DD'),
})

const getReportUrl = computed(() => {
  return url.value
})

const getSelectedCompany = computed(() => {
  return companyStore.selectedCompany
})

const dateUrl = computed(() => {
  return `${siteURL.value}?as_of_date=${moment(formData.as_of_date).format(
    'YYYY-MM-DD'
  )}`
})

onMounted(() => {
  siteURL.value = `/reports/balance-sheet/${getSelectedCompany.value.unique_hash}`
  url.value = dateUrl.value
})

async function viewReportsPDF() {
  let data = await getReports()
  window.open(getReportUrl.value, '_blank')
  return data
}

function getReports() {
  url.value = dateUrl.value
  return true
}

function downloadReport() {
  if (!getReports()) {
    return false
  }

  window.open(getReportUrl.value + '&download=true')
  setTimeout(() => {
    url.value = dateUrl.value
  }, 200)
}

async function exportReport(format) {
  isExporting.value = true

  try {
    const response = await window.axios.get(
      `/api/v1/reports/export/balance-sheet/${getSelectedCompany.value.unique_hash}`,
      {
        params: {
          as_of_date: moment(formData.as_of_date).format('YYYY-MM-DD'),
          format: format,
        },
        responseType: 'blob',
      }
    )

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url

    const asOfDate = moment(formData.as_of_date).format('YYYY-MM-DD')
    const extension = format === 'csv' ? 'csv' : 'pdf'
    const filename = `balance_sheet_${asOfDate}.${extension}`
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export report:', error)
  } finally {
    isExporting.value = false
  }
}
</script>

// CLAUDE-CHECKPOINT
