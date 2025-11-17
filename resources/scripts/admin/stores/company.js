import axios from 'axios'
import { defineStore } from 'pinia'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'
import Ls from '@/scripts/services/ls'

export const useCompanyStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'company',

    state: () => ({
      companies: [],
      selectedCompany: null,
      selectedCompanySettings: {},
      selectedCompanyCurrency: null,
      companyForm: {
        name: '',
      },
    }),

    actions: {
      setSelectedCompany(data) {
        if (data && data.id) {
          window.Ls.set('selectedCompany', data.id)
          this.selectedCompany = data
        }
      },

      fetchBasicMailConfig() {
        return new Promise((resolve, reject) => {
          axios
            .get('/company/mail/config')
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateCompany(data) {
        return new Promise((resolve, reject) => {
          axios
            .put('/company', data)
            .then((response) => {
              const notificationStore = useNotificationStore()

              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.company_info.updated_message'),
              })

              this.selectedCompany = response.data.data
              const companyIndex = this.companies.findIndex((company) => company.unique_hash === this.selectedCompany.unique_hash);
              if (companyIndex !== -1) {
                this.companies[companyIndex] = this.selectedCompany;
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateCompanyLogo(data) {
        return new Promise((resolve, reject) => {
          axios
            .post('/company/upload-logo', data)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addNewCompany(data) {
        return new Promise((resolve, reject) => {
          axios
            .post('/companies', data)
            .then((response) => {
              const notificationStore = useNotificationStore()
              notificationStore.showNotification({
                type: 'success',
                message: global.t('company_switcher.created_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchCompany(params) {
        return new Promise((resolve, reject) => {
          axios
            .get('/current-company', params)
            .then((response) => {
              if (response.data.data) {
                if (response.data.data.address) {
                  Object.assign(this.companyForm, response.data.data.address)
                }
                if (response.data.data.name) {
                  this.companyForm.name = response.data.data.name
                }
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchUserCompanies() {
        return new Promise((resolve, reject) => {
          axios
            .get('/companies')
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchCompanySettings(settings) {
        return new Promise((resolve, reject) => {
          axios
            .get('/company/settings', {
              params: {
                settings,
              },
            })
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateCompanySettings({ data, message }) {
        return new Promise((resolve, reject) => {
          axios
            .post('/company/settings', data)
            .then((response) => {
              Object.assign(this.selectedCompanySettings, data.settings)

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

      deleteCompany(data) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/companies/delete`, data)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      setDefaultCurrency(data) {
        this.defaultCurrency = data.currency
      },
    },
  })()
}
