import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'
import expenseStub from '@/scripts/admin/stub/expense'
import utils from '@/scripts/helpers/utilities'

export const useExpenseStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'expense',

    state: () => ({
      expenses: [],
      totalExpenses: 0,
      selectAllField: false,
      selectedExpenses: [],
      paymentModes: [],
      showExchangeRate: false,
      currentExpense: {
        ...expenseStub,
      },
    }),

    getters: {
      getCurrentExpense: (state) => state.currentExpense,
      getSelectedExpenses: (state) => state.selectedExpenses,
    },

    actions: {
      resetCurrentExpenseData() {
        this.currentExpense = {
          ...expenseStub,
        }
      },

      fetchExpenses(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/expenses`, { params })
            .then((response) => {
              this.expenses = response.data.data
              this.totalExpenses = response.data.meta.expense_total_count
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchExpense(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/expenses/${id}`)
            .then((response) => {
              if (response.data) {
                Object.assign(this.currentExpense, response.data.data)
                this.currentExpense.selectedCurrency =
                  response.data.data.currency
                this.currentExpense.attachment_receipt = null
                if (response.data.data.attachment_receipt_url) {
                  if (
                    utils.isImageFile(
                      response.data.data.attachment_receipt_meta.mime_type
                    )
                  ) {
                    this.currentExpense.receiptFiles = [
                      { image: `/reports/expenses/${id}/receipt?${response.data.data.attachment_receipt_meta.uuid}` },
                    ]
                  } else {
                    this.currentExpense.receiptFiles = [
                      {
                        type: 'document',
                        name: response.data.data.attachment_receipt_meta
                          .file_name,
                      },
                    ]
                  }
                } else {
                  this.currentExpense.receiptFiles = []
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

      addExpense(data, allowDuplicate = false) {
        const formData = utils.toFormData(data)
        if (allowDuplicate) {
          formData.append('allow_duplicate', '1')
        }

        return new Promise((resolve, reject) => {
          axios
            .post('/expenses', formData)
            .then((response) => {
              // Check if this is a duplicate warning response
              if (response.data?.is_duplicate_warning) {
                // Return the response without showing success notification
                // The component will handle showing the duplicate warning modal
                resolve(response)
                return
              }

              this.expenses.push(response.data)

              const notificationStore = useNotificationStore()

              notificationStore.showNotification({
                type: 'success',
                message: global.t('expenses.created_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateExpense({ id, data, isAttachmentReceiptRemoved, allowDuplicate = false }) {
        const notificationStore = useNotificationStore()

        const formData = utils.toFormData(data)

        formData.append('_method', 'PUT')
        formData.append('is_attachment_receipt_removed', isAttachmentReceiptRemoved)
        if (allowDuplicate) {
          formData.append('allow_duplicate', '1')
        }

        return new Promise((resolve, reject) => {
          axios.post(`/expenses/${id}`, formData).then((response) => {
            // Check if this is a duplicate warning response
            if (response.data?.is_duplicate_warning) {
              // Return the response without showing success notification
              // The component will handle showing the duplicate warning modal
              resolve(response)
              return
            }

            let pos = this.expenses.findIndex(
              (expense) => expense.id === response.data.id
            )

            this.expenses[pos] = data.expense

            notificationStore.showNotification({
              type: 'success',
              message: global.t('expenses.updated_message'),
            })

            resolve(response)
          }).catch((err) => {
            handleError(err)
            reject(err)
          })
        })
      },

      setSelectAllState(data) {
        this.selectAllField = data
      },

      selectExpense(data) {
        this.selectedExpenses = data
        if (this.selectedExpenses.length === this.expenses.length) {
          this.selectAllField = true
        } else {
          this.selectAllField = false
        }
      },

      selectAllExpenses(data) {
        if (this.selectedExpenses.length === this.expenses.length) {
          this.selectedExpenses = []
          this.selectAllField = false
        } else {
          let allExpenseIds = this.expenses.map((expense) => expense.id)
          this.selectedExpenses = allExpenseIds
          this.selectAllField = true
        }
      },

      deleteExpense(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/expenses/delete`, id)
            .then((response) => {
              let index = this.expenses.findIndex(
                (expense) => expense.id === id
              )
              this.expenses.splice(index, 1)

              notificationStore.showNotification({
                type: 'success',
                message: global.tc('expenses.deleted_message', 1),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteMultipleExpenses() {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/expenses/delete`, { ids: this.selectedExpenses })
            .then((response) => {
              this.selectedExpenses.forEach((expense) => {
                let index = this.expenses.findIndex(
                  (_expense) => _expense.id === expense.id
                )
                this.expenses.splice(index, 1)
              })
              notificationStore.showNotification({
                type: 'success',
                message: global.tc('expenses.deleted_message', 2),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },
      fetchPaymentModes(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/payment-methods`, { params })
            .then((response) => {
              this.paymentModes = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },
    },
  })()
}
