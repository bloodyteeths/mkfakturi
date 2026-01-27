import axios from 'axios'
import { defineStore } from 'pinia'
import { useCompanyStore } from './company'
import { useUserStore } from './user'
import { useModuleStore } from './module'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'
import _ from 'lodash'

export const useGlobalStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'global',
    state: () => ({
      // Global Configuration
      config: null,
      globalSettings: null,
      featureFlags: {},

      // Global Lists
      timeZones: [],
      dateFormats: [],
      timeFormats: [],
      currencies: [],
      countries: [],
      languages: [],
      fiscalYears: [],

      // Menus
      mainMenu: [],
      settingMenu: [],

      // Boolean Flags
      isAppLoaded: false,
      isSidebarOpen: false,
      isSidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
      areCurrenciesLoading: false,

      // Super Admin Support Mode
      supportMode: null,

      downloadReport: () => {},
    }),

    getters: {
      menuGroups: (state) => {
        return Object.values(_.groupBy(state.mainMenu, 'group'))
      },
    },

    actions: {
      bootstrap() {
        return new Promise((resolve, reject) => {
          axios
            .get('/bootstrap')
            .then((response) => {
              const companyStore = useCompanyStore()
              const userStore = useUserStore()
              const moduleStore = useModuleStore()
              
              if (!companyStore) {
                console.error('Company store is not available')
                reject(new Error('Company store initialization failed'))
                return
              }

              this.mainMenu = response.data.main_menu
              this.settingMenu = response.data.setting_menu

              this.config = response.data.config
              this.globalSettings = response.data.global_settings
              this.featureFlags = response.data.feature_flags || {}

              // Super admin support mode
              this.supportMode = response.data.support_mode || null

              // user store
              userStore.currentUser = response.data.current_user
              userStore.currentUserSettings =
                response.data.current_user_settings
              userStore.currentAbilities = response.data.current_user_abilities

              // Module store
              moduleStore.apiToken = response.data.global_settings.api_token
              moduleStore.enableModules = response.data.modules

                // company store
                if (response.data.companies) {
                  companyStore.companies = response.data.companies
                }
                if (response.data.current_company) {
                  companyStore.selectedCompany = response.data.current_company
                  companyStore.setSelectedCompany(response.data.current_company)
                }
                if (response.data.current_company_settings) {
                  companyStore.selectedCompanySettings = response.data.current_company_settings
                }
                if (response.data.current_company_currency) {
                  companyStore.selectedCompanyCurrency = response.data.current_company_currency
                }

              if(typeof global.locale !== 'string') {
                // Check localStorage first, then server settings, then default to Macedonian
                const savedLocale = localStorage.getItem('invoiceshelf_locale')
                global.locale.value = savedLocale || 
                  response.data.current_company_settings?.language || 
                  response.data.current_user_settings?.language || 
                  'mk'
              }

              // Set app as loaded immediately after data is populated
              // This allows UI to render while any remaining async operations complete
              this.isAppLoaded = true
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchCurrencies() {
        return new Promise((resolve, reject) => {
          if (this.currencies.length || this.areCurrenciesLoading) {
            resolve(this.currencies)
          } else {
            this.areCurrenciesLoading = true
            axios
              .get('/currencies')
              .then((response) => {
                this.currencies = response.data.data.filter((currency) => {
                  return (currency.name = `${currency.code} - ${currency.name}`)
                })
                this.areCurrenciesLoading = false
                resolve(response)
              })
              .catch((err) => {
                handleError(err)
                this.areCurrenciesLoading = false
                reject(err)
              })
          }
        })
      },

      fetchConfig(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/config`, { params })
            .then((response) => {
              if (response.data.languages) {
                this.languages = response.data.languages
              } else {
                this.fiscalYears = response.data.fiscal_years
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchDateFormats() {
        return new Promise((resolve, reject) => {
          if (this.dateFormats.length) {
            resolve(this.dateFormats)
          } else {
            axios
              .get('/date/formats')
              .then((response) => {
                this.dateFormats = response.data.date_formats
                resolve(response)
              })
              .catch((err) => {
                handleError(err)
                reject(err)
              })
          }
        })
      },

      fetchTimeFormats() {
        return new Promise((resolve, reject) => {
          if (this.timeFormats.length) {
            resolve(this.timeFormats)
          } else {
            axios
              .get('/time/formats')
              .then((response) => {
                this.timeFormats = response.data.time_formats
                resolve(response)
              })
              .catch((err) => {
                handleError(err)
                reject(err)
              })
          }
        })
      },

      fetchTimeZones() {
        return new Promise((resolve, reject) => {
          if (this.timeZones.length) {
            resolve(this.timeZones)
          } else {
            axios
              .get('/timezones')
              .then((response) => {
                this.timeZones = response.data.time_zones
                resolve(response)
              })
              .catch((err) => {
                handleError(err)
                reject(err)
              })
          }
        })
      },

      fetchCountries() {
        return new Promise((resolve, reject) => {
          if (this.countries.length) {
            resolve(this.countries)
          } else {
            axios
              .get('/countries')
              .then((response) => {
                this.countries = response.data.data
                resolve(response)
              })
              .catch((err) => {
                handleError(err)
                reject(err)
              })
          }
        })
      },

      fetchPlaceholders(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/number-placeholders`, { params })
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      setSidebarVisibility(val) {
        this.isSidebarOpen = val
      },

      toggleSidebarCollapsed() {
        this.isSidebarCollapsed = !this.isSidebarCollapsed
        localStorage.setItem('sidebarCollapsed', this.isSidebarCollapsed)
      },

      setSidebarCollapsed(val) {
        this.isSidebarCollapsed = val
        localStorage.setItem('sidebarCollapsed', val)
      },

      setIsAppLoaded(isAppLoaded) {
        this.isAppLoaded = isAppLoaded
      },

      updateGlobalSettings({ data, message }) {
        return new Promise((resolve, reject) => {
          axios
            .post('/settings', data)
            .then((response) => {
              Object.assign(this.globalSettings, data.settings)

              if (message) {
                const notificationStore = useNotificationStore()

                notificationStore.showNotification({
                  type: 'success',
                  message: global.t(message),
                })
              }

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateLanguage(newLocale) {
        // Update i18n locale
        if(typeof global.locale !== 'string') {
          global.locale.value = newLocale
        } else {
          global.locale = newLocale
        }
        
        // Save to localStorage for persistence
        localStorage.setItem('invoiceshelf_locale', newLocale)
        
        // Optionally save to user settings via API (can be enhanced later)
        console.log(`Language updated to: ${newLocale}`)
      },
    },
  })()
}
