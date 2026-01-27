<template>
  <div ref="companySwitchBar" class="relative rounded">
    <CompanyModal />

    <!-- Support Mode Indicator -->
    <div
      v-if="supportMode"
      class="
        absolute
        -top-1
        -right-1
        w-3
        h-3
        bg-orange-500
        rounded-full
        animate-pulse
        z-10
      "
      :title="$t('company_switcher.support_mode_active')"
    />

    <div
      class="
        flex
        items-center
        justify-center
        px-3
        h-8
        md:h-9
        ml-2
        text-sm text-white
        rounded
        cursor-pointer
      "
      :class="supportMode ? 'bg-orange-500 bg-opacity-80' : 'bg-white bg-opacity-20'"
      @click="isShow = !isShow"
    >
      <span
        v-if="companyStore.selectedCompany"
        class="w-16 text-sm font-medium truncate sm:w-auto"
      >
        {{ companyStore.selectedCompany.name }}
      </span>
      <BaseIcon name="ChevronDownIcon" class="h-5 ml-1 text-white" />
    </div>

    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-1 opacity-0"
    >
      <div
        v-if="isShow"
        class="absolute right-0 mt-2 bg-white rounded-md shadow-lg"
      >
        <div
          class="
            overflow-y-auto
            scrollbar-thin scrollbar-thumb-rounded-full
            w-[250px]
            max-h-[350px]
            scrollbar-thumb-gray-300 scrollbar-track-gray-10
            pb-4
          "
        >
          <!-- Regular Companies Section -->
          <label
            class="
              px-3
              py-2
              text-xs
              font-semibold
              text-gray-400
              mb-0.5
              block
              uppercase
            "
          >
            {{ $t('company_switcher.label') }}
          </label>

          <div
            v-if="companyStore.companies.length < 1 && !isPartner"
            class="
              flex flex-col
              items-center
              justify-center
              p-2
              px-3
              mt-4
              text-base text-gray-400
            "
          >
            <BaseIcon name="ExclamationCircleIcon" class="h-5 text-gray-400" />
            {{ $t('company_switcher.no_results_found') }}
          </div>
          <div v-else-if="!isPartner">
            <div v-if="companyStore.companies.length > 0">
              <div
                v-for="(company, index) in companyStore.companies"
                :key="index"
                class="
                  p-2
                  px-3
                  rounded-md
                  cursor-pointer
                  hover:bg-gray-100 hover:text-primary-500
                "
                :class="{
                  'bg-gray-100 text-primary-500':
                    companyStore.selectedCompany.id === company.id,
                }"
                @click="changeCompany(company)"
              >
                <div class="flex items-center">
                  <span
                    class="
                      flex
                      items-center
                      justify-center
                      mr-3
                      overflow-hidden
                      text-base
                      font-semibold
                      bg-gray-200
                      rounded-md
                      w-9
                      h-9
                      text-primary-500
                    "
                  >
                    <span v-if="!company.logo">
                      {{ initGenerator(company.name) }}
                    </span>
                    <img
                      v-else
                      :src="company.logo"
                      alt="Company logo"
                      class="w-full h-full object-contain"
                    />
                  </span>
                  <div class="flex flex-col">
                    <span class="text-sm">{{ company.name }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Partner Companies Section -->
          <div v-if="isPartner">
            <label
              class="
                px-3
                py-2
                text-xs
                font-semibold
                text-blue-500
                mb-0.5
                block
                uppercase
              "
            >
              {{ $t('company_switcher.partner_clients') }} ({{ consoleStore.companies.length }})
            </label>
            
            <div
              v-if="consoleStore.companies.length < 1"
              class="
                flex flex-col
                items-center
                justify-center
                p-2
                px-3
                mt-4
                text-base text-gray-400
              "
            >
              <BaseIcon name="ExclamationCircleIcon" class="h-5 text-gray-400" />
              {{ $t('company_switcher.no_partner_companies_found') }}
            </div>
            
            <div v-else>
              <div
                v-for="(company, index) in consoleStore.companies"
                :key="`partner-${index}`"
                class="
                  p-2
                  px-3
                  rounded-md
                  cursor-pointer
                  hover:bg-blue-50 hover:text-blue-600
                "
                :class="{
                  'bg-blue-50 text-blue-600 border-l-2 border-blue-500':
                    consoleStore.currentCompany?.id === company.id,
                }"
                @click="changeToPartnerCompany(company)"
              >
                <div class="flex items-center">
                  <span
                    class="
                      flex
                      items-center
                      justify-center
                      mr-3
                      overflow-hidden
                      text-base
                      font-semibold
                      bg-blue-100
                      rounded-md
                      w-9
                      h-9
                      text-blue-600
                    "
                  >
                    <span v-if="!company.logo">
                      {{ initGenerator(company.name) }}
                    </span>
                    <img
                      v-else
                      :src="company.logo"
                      alt="Company logo"
                      class="w-full h-full object-contain"
                    />
                  </span>
                  <div class="flex flex-col">
                    <span class="text-sm font-medium">{{ company.name }}</span>
                    <div class="flex items-center text-xs text-gray-500">
                      <span>{{ company.commission_rate }}% {{ $t('company_switcher.commission') }}</span>
                      <span v-if="company.is_primary" class="ml-2 text-blue-500">• {{ $t('company_switcher.primary') }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Partner Console Link -->
            <div
              class="
                flex
                items-center
                justify-center
                p-3
                mt-2
                border-t-2 border-gray-100
                cursor-pointer
                text-blue-500
                hover:text-blue-600
                hover:bg-blue-50
              "
              @click="goToConsole"
            >
              <BaseIcon name="CogIcon" class="h-5 mr-2" />
              <span class="font-medium">{{ $t('company_switcher.manage_clients') }}</span>
            </div>
          </div>

          <!-- Super Admin Support Mode Section -->
          <div v-if="isSuperAdmin" class="border-t-2 border-gray-100 mt-2 pt-2">
            <!-- Support Mode Active Banner -->
            <div
              v-if="supportMode"
              class="mx-2 mb-2 p-2 bg-orange-100 border border-orange-300 rounded-md"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center text-orange-700">
                  <BaseIcon name="EyeIcon" class="h-4 w-4 mr-1" />
                  <span class="text-xs font-medium">{{ $t('company_switcher.support_mode') }}</span>
                </div>
                <button
                  class="text-xs text-orange-600 hover:text-orange-800 font-medium"
                  @click="exitSupportMode"
                >
                  {{ $t('company_switcher.exit') }}
                </button>
              </div>
              <p class="text-xs text-orange-600 mt-1">
                {{ supportMode.company_name }}
              </p>
            </div>

            <label
              class="
                px-3
                py-2
                text-xs
                font-semibold
                text-orange-500
                mb-0.5
                block
                uppercase
              "
            >
              {{ $t('company_switcher.admin_support') }}
            </label>

            <!-- Search Input -->
            <div class="px-3 mb-2">
              <input
                v-model="adminSearchQuery"
                type="text"
                :placeholder="$t('company_switcher.search_all_companies')"
                class="
                  w-full
                  px-3
                  py-2
                  text-sm
                  border
                  border-gray-300
                  rounded-md
                  focus:outline-none
                  focus:ring-2
                  focus:ring-orange-500
                  focus:border-transparent
                "
                @input="debouncedAdminSearch"
              />
            </div>

            <!-- Search Results -->
            <div v-if="adminSearchLoading" class="px-3 py-2 text-center">
              <div class="animate-spin h-5 w-5 border-2 border-orange-500 border-t-transparent rounded-full mx-auto"></div>
            </div>

            <div v-else-if="adminSearchResults.length > 0" class="max-h-48 overflow-y-auto">
              <div
                v-for="company in adminSearchResults"
                :key="`admin-${company.id}`"
                class="
                  p-2
                  px-3
                  cursor-pointer
                  hover:bg-orange-50
                  hover:text-orange-600
                "
                :class="{
                  'bg-orange-50 text-orange-600': supportMode?.company_id === company.id,
                }"
                @click="enterSupportMode(company)"
              >
                <div class="flex items-center">
                  <span
                    class="
                      flex
                      items-center
                      justify-center
                      mr-3
                      overflow-hidden
                      text-base
                      font-semibold
                      bg-orange-100
                      rounded-md
                      w-9
                      h-9
                      text-orange-600
                    "
                  >
                    {{ initGenerator(company.name) }}
                  </span>
                  <div class="flex flex-col">
                    <span class="text-sm font-medium">{{ company.name }}</span>
                    <span class="text-xs text-gray-500">{{ company.owner_email }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-else-if="adminSearchQuery.length >= 2 && !adminSearchLoading"
              class="px-3 py-2 text-sm text-gray-500 text-center"
            >
              {{ $t('company_switcher.no_results_found') }}
            </div>

            <div
              v-else-if="adminSearchQuery.length < 2"
              class="px-3 py-2 text-xs text-gray-400 text-center"
            >
              {{ $t('company_switcher.type_to_search') }}
            </div>
          </div>
        </div>
        <div
          v-if="userStore.currentUser.is_owner"
          class="
            flex
            items-center
            justify-center
            p-4
            pl-3
            border-t-2 border-gray-100
            cursor-pointer
            text-primary-400
            hover:text-primary-500
          "
          @click="addNewCompany"
        >
          <BaseIcon name="PlusIcon" class="h-5 mr-2" />

          <span class="font-medium">
            {{ $t('company_switcher.add_new_company') }}
          </span>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { onClickOutside, useDebounceFn } from '@vueuse/core'
import { useRoute, useRouter } from 'vue-router'
import { useModalStore } from '../stores/modal'
import { useI18n } from 'vue-i18n'
import { useGlobalStore } from '@/scripts/admin//stores/global'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

import CompanyModal from '@/scripts/admin/components/modal-components/CompanyModal.vue'
import abilities from '@/scripts/admin/stub/abilities'

const companyStore = useCompanyStore()
const consoleStore = useConsoleStore()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const route = useRoute()
const router = useRouter()
const globalStore = useGlobalStore()
const { t } = useI18n()
const userStore = useUserStore()
const isShow = ref(false)
const name = ref('')
const companySwitchBar = ref(null)

// Super Admin Search
const adminSearchQuery = ref('')
const adminSearchResults = ref([])
const adminSearchLoading = ref(false)

// Check if current user is a partner
const isPartner = computed(() => {
  // Check user role directly - only show partner UI for actual partner users
  return userStore.currentUser?.role === 'partner'
})

// Check if current user is a super admin
const isSuperAdmin = computed(() => {
  return userStore.currentUser?.role === 'super admin'
})

// Get support mode from global store
const supportMode = computed(() => {
  return globalStore.supportMode
})

watch(route, () => {
  isShow.value = false
  name.value = ''
})

onClickOutside(companySwitchBar, () => {
  isShow.value = false
})

function initGenerator(name) {
  if (name) {
    const nameSplit = name.split(' ')
    const initials = nameSplit[0].charAt(0).toUpperCase()
    return initials
  }
}

function addNewCompany() {
  modalStore.openModal({
    title: t('company_switcher.new_company'),
    componentName: 'CompanyModal',
    size: 'sm',
  })
}

// Initialize console store only for partner users
onMounted(async () => {
  if (userStore.currentUser?.role === 'partner') {
    try {
      await consoleStore.initialize()
    } catch (error) {
      console.debug('Failed to initialize partner console:', error)
    }
  }
})

async function changeCompany(company) {
  await companyStore.setSelectedCompany(company)
  router.push('/admin/dashboard')
  await globalStore.setIsAppLoaded(false)
  await globalStore.bootstrap()
}

async function changeToPartnerCompany(company) {
  try {
    await consoleStore.switchCompany(company.id)
    isShow.value = false
    // Redirect to company-specific dashboard or stay on current page
    router.push(`/admin/dashboard`)
  } catch (error) {
    console.error('Failed to switch to partner company:', error)
  }
}

function goToConsole() {
  isShow.value = false
  router.push('/admin/console')
}

// Super Admin Support Mode Functions
async function searchAdminCompanies() {
  if (adminSearchQuery.value.length < 2) {
    adminSearchResults.value = []
    return
  }

  adminSearchLoading.value = true
  try {
    const response = await axios.get('/support/admin/companies/search', {
      params: { q: adminSearchQuery.value, limit: 10 }
    })
    adminSearchResults.value = response.data.companies
  } catch (error) {
    console.error('Failed to search companies:', error)
    adminSearchResults.value = []
  } finally {
    adminSearchLoading.value = false
  }
}

const debouncedAdminSearch = useDebounceFn(searchAdminCompanies, 300)

async function enterSupportMode(company) {
  try {
    const response = await axios.post(`/support/admin/companies/${company.id}/enter-support-mode`)

    if (response.data.success) {
      notificationStore.showNotification({
        type: 'success',
        message: t('company_switcher.entered_support_mode', { name: company.name }),
      })

      isShow.value = false
      adminSearchQuery.value = ''
      adminSearchResults.value = []

      // Refresh bootstrap to load the support company context
      await globalStore.setIsAppLoaded(false)
      await globalStore.bootstrap()

      router.push('/admin/dashboard')
    }
  } catch (error) {
    console.error('Failed to enter support mode:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('company_switcher.support_mode_failed'),
    })
  }
}

async function exitSupportMode() {
  try {
    await axios.post('/support/admin/support-mode/exit')

    notificationStore.showNotification({
      type: 'success',
      message: t('company_switcher.exited_support_mode'),
    })

    isShow.value = false

    // Refresh bootstrap to restore normal context
    await globalStore.setIsAppLoaded(false)
    await globalStore.bootstrap()

    router.push('/admin/dashboard')
  } catch (error) {
    console.error('Failed to exit support mode:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('company_switcher.exit_support_mode_failed'),
    })
  }
}
</script>

