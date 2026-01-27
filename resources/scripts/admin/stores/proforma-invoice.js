import axios from 'axios'
import moment from 'moment'
import Guid from 'guid'
import _ from 'lodash'
import { defineStore } from 'pinia'
import { useRoute } from 'vue-router'
import { handleError } from '@/scripts/helpers/error-handling'
import invoiceItemStub from '../stub/invoice-item'
import taxStub from '../stub/tax'
import proformaInvoiceStub from '../stub/proforma-invoice'

import { useNotificationStore } from '@/scripts/stores/notification'
import { useCustomerStore } from './customer'
import { useTaxTypeStore } from './tax-type'
import { useCompanyStore } from './company'
import { useItemStore } from './item'
import { useUserStore } from './user'
import { useNotesStore } from './note'

export const useProformaInvoiceStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n
  const notificationStore = useNotificationStore()

  return defineStoreFunc({
    id: 'proformaInvoice',
    state: () => ({
      templates: [],
      proformaInvoices: [],
      selectedProformaInvoices: [],
      selectAllField: false,
      proformaInvoiceTotalCount: 0,
      showExchangeRate: false,
      isFetchingInitialSettings: false,
      isFetchingProformaInvoice: false,

      newProformaInvoice: {
        ...proformaInvoiceStub(),
      },

      currentProformaInvoice: null,
    }),

    getters: {
      getProformaInvoice: (state) => (id) => {
        let proformaId = parseInt(id)
        return state.proformaInvoices.find((proforma) => proforma.id === proformaId)
      },

      getSubTotal() {
        return this.newProformaInvoice.items.reduce(function (a, b) {
          return a + b['total']
        }, 0)
      },

      getTotalSimpleTax() {
        return _.sumBy(this.newProformaInvoice.taxes, function (tax) {
          if (!tax.compound_tax) {
            return tax.amount
          }
          return 0
        })
      },

      getTotalCompoundTax() {
        return _.sumBy(this.newProformaInvoice.taxes, function (tax) {
          if (tax.compound_tax) {
            return tax.amount
          }
          return 0
        })
      },

      getTotalTax() {
        if (
          this.newProformaInvoice.tax_per_item === 'NO' ||
          this.newProformaInvoice.tax_per_item === null
        ) {
          return this.getTotalSimpleTax + this.getTotalCompoundTax
        }
        return _.sumBy(this.newProformaInvoice.items, function (tax) {
          return tax.tax
        })
      },

      getSubtotalWithDiscount() {
        return this.getSubTotal - this.newProformaInvoice.discount_val
      },

      getTotal() {
        return this.getSubtotalWithDiscount + this.getTotalTax
      },

      isEdit: (state) => (state.newProformaInvoice.id ? true : false),
    },

    actions: {
      resetCurrentProformaInvoice() {
        this.newProformaInvoice = {
          ...proformaInvoiceStub(),
        }
      },

      fetchProformaInvoices(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/proforma-invoices`, { params })
            .then((response) => {
              this.proformaInvoices = response.data.data
              this.proformaInvoiceTotalCount = response.data.meta.proforma_invoice_total_count
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchProformaInvoice(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/proforma-invoices/${id}`)
            .then((response) => {
              this.setProformaInvoiceData(response.data.data)
              this.currentProformaInvoice = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      setProformaInvoiceData(proformaInvoice) {
        const companyStore = useCompanyStore()
        Object.assign(this.newProformaInvoice, proformaInvoice)

        // Fall back to company settings if tax_per_item is null (old records)
        if (this.newProformaInvoice.tax_per_item === null) {
          this.newProformaInvoice.tax_per_item = companyStore.selectedCompanySettings.tax_per_item
        }
        if (this.newProformaInvoice.discount_per_item === null) {
          this.newProformaInvoice.discount_per_item = companyStore.selectedCompanySettings.discount_per_item
        }

        if (this.newProformaInvoice.tax_per_item === 'YES') {
          this.newProformaInvoice.items.forEach((_i) => {
            if (_i.taxes && !_i.taxes.length)
              _i.taxes.push({ ...taxStub, id: Guid.raw() })
          })
        }

        if (this.newProformaInvoice.discount_per_item === 'YES') {
          this.newProformaInvoice.items.forEach((_i, index) => {
            if (_i.discount_type === 'fixed')
              this.newProformaInvoice.items[index].discount = _i.discount / 100
          })
        }
        else {
          if (this.newProformaInvoice.discount_type === 'fixed')
            this.newProformaInvoice.discount = this.newProformaInvoice.discount / 100
        }
      },

      addProformaInvoice(data) {
        return new Promise((resolve, reject) => {
          axios
            .post('/proforma-invoices', data)
            .then((response) => {
              this.proformaInvoices = [...this.proformaInvoices, response.data.data]

              notificationStore.showNotification({
                type: 'success',
                message: global.t('proforma_invoices.created_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateProformaInvoice(data) {
        return new Promise((resolve, reject) => {
          axios
            .put(`/proforma-invoices/${data.id}`, data)
            .then((response) => {
              let pos = this.proformaInvoices.findIndex(
                (proforma) => proforma.id === response.data.data.id
              )
              this.proformaInvoices[pos] = response.data.data

              notificationStore.showNotification({
                type: 'success',
                message: global.t('proforma_invoices.updated_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteProformaInvoice(id) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/proforma-invoices/delete`, { ids: [id] })
            .then((response) => {
              let index = this.proformaInvoices.findIndex(
                (proforma) => proforma.id === id
              )
              this.proformaInvoices.splice(index, 1)

              notificationStore.showNotification({
                type: 'success',
                message: global.t('proforma_invoices.deleted_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteMultipleProformaInvoices() {
        return new Promise((resolve, reject) => {
          axios
            .post(`/proforma-invoices/delete`, { ids: this.selectedProformaInvoices })
            .then((response) => {
              this.selectedProformaInvoices.forEach((proformaId) => {
                let index = this.proformaInvoices.findIndex(
                  (_p) => _p.id === proformaId
                )
                this.proformaInvoices.splice(index, 1)
              })
              this.selectedProformaInvoices = []

              notificationStore.showNotification({
                type: 'success',
                message: global.t('proforma_invoices.deleted_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      previewProformaInvoice(data) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/proforma-invoices/${data.id}/send/preview`, { params: data })
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      sendProformaInvoice(data) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/proforma-invoices/${data.id}/send`, data)
            .then((response) => {
              notificationStore.showNotification({
                type: 'success',
                message: global.t('proforma_invoices.sent_successfully'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsViewed(id) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/proforma-invoices/${id}/mark-as-viewed`)
            .then((response) => {
              let pos = this.proformaInvoices.findIndex(
                (proforma) => proforma.id === id
              )
              if (this.proformaInvoices[pos]) {
                this.proformaInvoices[pos].status = 'VIEWED'
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('proforma_invoices.marked_as_viewed'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsExpired(id) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/proforma-invoices/${id}/mark-as-expired`)
            .then((response) => {
              let pos = this.proformaInvoices.findIndex(
                (proforma) => proforma.id === id
              )
              if (this.proformaInvoices[pos]) {
                this.proformaInvoices[pos].status = 'EXPIRED'
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('proforma_invoices.marked_as_expired'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsRejected(id) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/proforma-invoices/${id}/mark-as-rejected`)
            .then((response) => {
              let pos = this.proformaInvoices.findIndex(
                (proforma) => proforma.id === id
              )
              if (this.proformaInvoices[pos]) {
                this.proformaInvoices[pos].status = 'REJECTED'
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('proforma_invoices.marked_as_rejected'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      convertToInvoice(id) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/proforma-invoices/${id}/convert-to-invoice`)
            .then((response) => {
              let pos = this.proformaInvoices.findIndex(
                (proforma) => proforma.id === id
              )
              if (this.proformaInvoices[pos]) {
                this.proformaInvoices[pos].status = 'CONVERTED'
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('proforma_invoices.converted_successfully'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      getNextNumber(params, setState = false) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/next-number?key=proforma_invoice`, { params })
            .then((response) => {
              if (setState) {
                this.newProformaInvoice.proforma_invoice_number = response.data.nextNumber
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      selectProformaInvoice(data) {
        this.selectedProformaInvoices = data
        if (this.selectedProformaInvoices.length === this.proformaInvoices.length) {
          this.selectAllField = true
        } else {
          this.selectAllField = false
        }
      },

      selectAllProformaInvoices() {
        if (this.selectedProformaInvoices.length === this.proformaInvoices.length) {
          this.selectedProformaInvoices = []
          this.selectAllField = false
        } else {
          let allProformaIds = this.proformaInvoices.map((proforma) => proforma.id)
          this.selectedProformaInvoices = allProformaIds
          this.selectAllField = true
        }
      },

      selectCustomer(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/customers/${id}`)
            .then((response) => {
              this.newProformaInvoice.customer = response.data.data
              this.newProformaInvoice.customer_id = response.data.data.id
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchProformaInvoiceTemplates(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/proforma-invoices/templates`, { params })
            .then((response) => {
              this.templates = response.data.proformaInvoiceTemplates
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      selectNote(data) {
        this.newProformaInvoice.selectedNote = null
        this.newProformaInvoice.selectedNote = data
      },

      setTemplate(data) {
        this.newProformaInvoice.template_name = data
      },

      resetSelectedCustomer() {
        this.newProformaInvoice.customer = null
        this.newProformaInvoice.customer_id = null
      },

      addItem() {
        this.newProformaInvoice.items.push({
          ...invoiceItemStub,
          id: Guid.raw(),
          taxes: [{ ...taxStub, id: Guid.raw() }],
        })
      },

      updateItem(data) {
        Object.assign(this.newProformaInvoice.items[data.index], { ...data })
      },

      removeItem(index) {
        this.newProformaInvoice.items.splice(index, 1)
      },

      deselectItem(index) {
        this.newProformaInvoice.items[index] = {
          ...invoiceItemStub,
          id: Guid.raw(),
          taxes: [{ ...taxStub, id: Guid.raw() }],
        }
      },

      resetSelectedNote() {
        this.newProformaInvoice.selectedNote = null
      },

      // On Load actions
      async fetchProformaInvoiceInitialSettings(isEdit) {
        const companyStore = useCompanyStore()
        const customerStore = useCustomerStore()
        const itemStore = useItemStore()
        const taxTypeStore = useTaxTypeStore()
        const route = useRoute()
        const userStore = useUserStore()
        const notesStore = useNotesStore()

        this.isFetchingInitialSettings = true

        this.newProformaInvoice.selectedCurrency = companyStore.selectedCompanyCurrency

        if (route.query.customer) {
          let response = await customerStore.fetchCustomer(route.query.customer)
          this.newProformaInvoice.customer = response.data.data
          this.newProformaInvoice.customer_id = response.data.data.id
        }

        let editActions = []

        if (!isEdit) {
          await notesStore.fetchNotes()
          this.newProformaInvoice.notes = notesStore.getDefaultNoteForType('ProformaInvoice')?.notes || notesStore.getDefaultNoteForType('Estimate')?.notes
          this.newProformaInvoice.tax_per_item =
            companyStore.selectedCompanySettings.tax_per_item
          this.newProformaInvoice.sales_tax_type = companyStore.selectedCompanySettings.sales_tax_type
          this.newProformaInvoice.sales_tax_address_type = companyStore.selectedCompanySettings.sales_tax_address_type
          this.newProformaInvoice.discount_per_item =
            companyStore.selectedCompanySettings.discount_per_item

          this.newProformaInvoice.proforma_invoice_date = moment().format('YYYY-MM-DD')
          this.newProformaInvoice.expiry_date = moment()
            .add(30, 'days')
            .format('YYYY-MM-DD')
        } else {
          editActions = [this.fetchProformaInvoice(route.params.id)]
        }

        Promise.all([
          itemStore.fetchItems({
            filter: {},
            orderByField: '',
            orderBy: '',
          }),
          this.resetSelectedNote(),
          this.fetchProformaInvoiceTemplates(),
          this.getNextNumber(),
          taxTypeStore.fetchTaxTypes({ limit: 'all' }),
          ...editActions,
        ])
          .then(async ([res1, res2, res3, res4, res5, res6]) => {
            if (!isEdit) {
              if (res4.data) {
                this.newProformaInvoice.proforma_invoice_number = res4.data.nextNumber
              }

              if (res3.data) {
                this.setTemplate(this.templates[0]?.name || 'proforma_invoice1')
                this.newProformaInvoice.template_name =
                userStore.currentUserSettings.default_proforma_template ?
                userStore.currentUserSettings.default_proforma_template : this.newProformaInvoice.template_name
              }
            }

            this.isFetchingInitialSettings = false
          })
          .catch((err) => {
            handleError(err)
          })
      },
    },
  })()
}
// CLAUDE-CHECKPOINT
