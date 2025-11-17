import axios from 'axios'
import { defineStore } from 'pinia'
import { useCompanyStore } from './company'
import { handleError } from '@/scripts/helpers/error-handling'

export const useInstallationStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n
  const companyStore = useCompanyStore()

  return defineStoreFunc({
    id: 'installation',

    state: () => ({
      currentDataBaseData: {
        database_connection: 'mysql',
        database_hostname: '127.0.0.1',
        database_port: '3306',
        database_name: null,
        database_username: null,
        database_password: null,
        database_overwrite: false,
        app_url: window.location.origin,
        app_locale: null
      },
    }),

    actions: {
      fetchInstallationLanguages() {
        return new Promise((resolve, reject) => {
          axios
            .get(`/installation/languages`)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchInstallationRequirements() {
        return new Promise((resolve, reject) => {
          axios
            .get(`/installation/requirements`)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchInstallationStep() {
        return new Promise((resolve, reject) => {
          axios
            .get(`/installation/wizard-step`)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addInstallationStep(data) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/installation/wizard-step`, data)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addInstallationLanguage(data) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/installation/wizard-language`, data)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchInstallationPermissions() {
        return new Promise((resolve, reject) => {
          axios
            .get(`/installation/permissions`)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchInstallationDatabase(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/installation/database/config`, { params })
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addInstallationDatabase(data) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/installation/database/config`, data)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addInstallationFinish() {
        return new Promise((resolve, reject) => {
          axios
            .post(`/installation/finish`)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      setInstallationDomain(data) {
        return new Promise((resolve, reject) => {
          axios
            .put(`/installation/set-domain`, data)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      installationLogin() {
        return new Promise((resolve, reject) => {
          // Use absolute URL to bypass axios baseURL (/api/v1)
          axios.get(window.location.origin + '/sanctum/csrf-cookie').then((response) => {
            if (response) {
              axios
                .post('/installation/login')
                .then((response) => {
                  companyStore.setSelectedCompany(response.data.company)
                  
                  // Store auth token if provided
                  if (response.data.token) {
                    window.Ls.set('auth.token', response.data.token)
                  }
                  
                  resolve(response)
                })
                .catch((err) => {
                  handleError(err)
                  reject(err)
                })
            }
          })
        })
      },

      checkAutheticated() {
        return new Promise((resolve, reject) => {
          axios
            .get(`/auth/check`)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              reject(err)
            })
        })
      },
    },
  })()
}
