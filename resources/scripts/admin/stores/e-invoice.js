import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'

export const useEInvoiceStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n
  const notificationStore = useNotificationStore()

  return defineStoreFunc({
    id: 'e-invoice',
    state: () => ({
      currentEInvoice: null,
      submissions: [],
      portalStatus: null,
      isLoading: false,
    }),

    getters: {
      hasEInvoice: (state) => !!state.currentEInvoice,
      eInvoiceStatus: (state) => state.currentEInvoice?.status || null,
    },

    actions: {
      /**
       * Generate UBL XML for an invoice
       * @param {number} invoiceId
       * @returns {Promise}
       */
      generateEInvoice(invoiceId) {
        return new Promise((resolve, reject) => {
          this.isLoading = true
          axios
            .post(`/api/v1/e-invoices/generate/${invoiceId}`)
            .then((response) => {
              this.currentEInvoice = response.data.data
              notificationStore.showNotification({
                type: 'success',
                message: global.t('e_invoice.generated_successfully'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
            .finally(() => {
              this.isLoading = false
            })
        })
      },

      /**
       * Sign e-invoice with certificate
       * @param {number} eInvoiceId
       * @param {string} passphrase
       * @returns {Promise}
       */
      signEInvoice(eInvoiceId, passphrase) {
        return new Promise((resolve, reject) => {
          this.isLoading = true
          axios
            .post(`/api/v1/e-invoices/${eInvoiceId}/sign`, { passphrase })
            .then((response) => {
              this.currentEInvoice = response.data.data
              notificationStore.showNotification({
                type: 'success',
                message: global.t('e_invoice.signed_successfully'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
            .finally(() => {
              this.isLoading = false
            })
        })
      },

      /**
       * Submit e-invoice to portal
       * @param {number} eInvoiceId
       * @returns {Promise}
       */
      submitEInvoice(eInvoiceId) {
        return new Promise((resolve, reject) => {
          this.isLoading = true
          axios
            .post(`/api/v1/e-invoices/${eInvoiceId}/submit`)
            .then((response) => {
              this.currentEInvoice = response.data.data
              if (response.data.submission) {
                this.submissions.unshift(response.data.submission)
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('e_invoice.submitted_successfully'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
            .finally(() => {
              this.isLoading = false
            })
        })
      },

      /**
       * Simulate submission (validation only)
       * @param {number} eInvoiceId
       * @returns {Promise}
       */
      simulateSubmission(eInvoiceId) {
        return new Promise((resolve, reject) => {
          this.isLoading = true
          axios
            .post(`/api/v1/e-invoices/${eInvoiceId}/simulate`)
            .then((response) => {
              notificationStore.showNotification({
                type: 'success',
                message: global.t('e_invoice.validation_successful'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
            .finally(() => {
              this.isLoading = false
            })
        })
      },

      /**
       * Fetch e-invoice status for an invoice
       * @param {number} invoiceId
       * @returns {Promise}
       */
      fetchEInvoiceStatus(invoiceId) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/e-invoices/${invoiceId}`)
            .then((response) => {
              this.currentEInvoice = response.data.data
              this.submissions = response.data.submissions || []
              resolve(response)
            })
            .catch((err) => {
              // Not an error if e-invoice doesn't exist yet
              if (err.response?.status === 404) {
                this.currentEInvoice = null
                this.submissions = []
                resolve(null)
              } else {
                handleError(err)
                reject(err)
              }
            })
        })
      },

      /**
       * Fetch submission history
       * @param {number} eInvoiceId
       * @returns {Promise}
       */
      fetchSubmissions(eInvoiceId) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/e-invoices/${eInvoiceId}/submissions`)
            .then((response) => {
              this.submissions = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Download signed XML file
       * @param {number} eInvoiceId
       * @returns {Promise}
       */
      downloadXml(eInvoiceId) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/e-invoices/${eInvoiceId}/download-xml`, {
              responseType: 'blob',
            })
            .then((response) => {
              const url = window.URL.createObjectURL(new Blob([response.data]))
              const link = document.createElement('a')
              link.href = url
              link.setAttribute(
                'download',
                `e-invoice-${eInvoiceId}.xml`
              )
              document.body.appendChild(link)
              link.click()
              link.remove()
              window.URL.revokeObjectURL(url)

              notificationStore.showNotification({
                type: 'success',
                message: global.t('e_invoice.downloaded_successfully'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Check portal status
       * @returns {Promise}
       */
      checkPortalStatus() {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/e-invoices/portal-status`)
            .then((response) => {
              this.portalStatus = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Resubmit a failed submission
       * @param {number} submissionId
       * @returns {Promise}
       */
      resubmit(submissionId) {
        return new Promise((resolve, reject) => {
          this.isLoading = true
          axios
            .post(`/api/v1/e-invoices/submissions/${submissionId}/resubmit`)
            .then((response) => {
              this.currentEInvoice = response.data.data
              if (response.data.submission) {
                this.submissions.unshift(response.data.submission)
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('e_invoice.resubmitted_successfully'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
            .finally(() => {
              this.isLoading = false
            })
        })
      },

      /**
       * Reset store state
       */
      resetEInvoice() {
        this.currentEInvoice = null
        this.submissions = []
        this.portalStatus = null
        this.isLoading = false
      },
    },
  })()
}
// CLAUDE-CHECKPOINT
