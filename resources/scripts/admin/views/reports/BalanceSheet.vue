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

const formData = reactive({
  as_of_date: moment().toString(),
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
</script>

// CLAUDE-CHECKPOINT
