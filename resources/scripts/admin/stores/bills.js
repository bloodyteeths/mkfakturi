import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'

export const useBillsStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'bills',
    state: () => ({
      bills: [],
      billTotalCount: 0,
      selectedBill: null,
      selectedBills: [],
      selectAllField: false,
      billPayments: [],
      isFetchingInitial: false,
      isFetchingList: false,
      isFetchingView: false,
    }),
    actions: {
      fetchBills(params) {
        this.isFetchingList = true

        return new Promise((resolve, reject) => {
          axios
            .get('/bills', { params })
            .then((response) => {
              this.bills = response.data.data
              this.billTotalCount =
                response.data.meta?.bill_total_count ??
                response.data.meta?.total ??
                this.bills.length
              this.isFetchingList = false
              resolve(response)
            })
            .catch((err) => {
              this.isFetchingList = false
              handleError(err)
              reject(err)
            })
        })
      },

      fetchBill(id) {
        this.isFetchingView = true

        return new Promise((resolve, reject) => {
          axios
            .get(`/bills/${id}`)
            .then((response) => {
              this.selectedBill = response.data.data
              this.isFetchingView = false
              resolve(response)
            })
            .catch((err) => {
              this.isFetchingView = false
              handleError(err)
              reject(err)
            })
        })
      },

      createBill(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post('/bills', data)
            .then((response) => {
              this.bills.push(response.data.data)
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.created_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateBill(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .put(`/bills/${data.id}`, data)
            .then((response) => {
              const updated = response.data.data
              const idx = this.bills.findIndex((b) => b.id === updated.id)
              if (idx !== -1) {
                this.bills[idx] = updated
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.updated_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteBill(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post('/bills/delete', { ids: [id] })
            .then((response) => {
              const index = this.bills.findIndex((bill) => bill.id === id)
              this.bills.splice(index, 1)
              notificationStore.showNotification({
                type: 'success',
                message: global.tc('bills.deleted_message', 1),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteMultipleBills() {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post('/bills/delete', { ids: this.selectedBills })
            .then((response) => {
              this.selectedBills.forEach((bill) => {
                const index = this.bills.findIndex((_b) => _b.id === bill.id)
                this.bills.splice(index, 1)
              })
              this.selectedBills = []
              notificationStore.showNotification({
                type: 'success',
                message: global.tc('bills.deleted_message', 2),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchBillPayments(billId, params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/bills/${billId}/payments`, { params })
            .then((response) => {
              this.billPayments = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      createBillPayment(billId, data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/bills/${billId}/payments`, data)
            .then((response) => {
              this.billPayments.push(response.data.data)
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.payment_created_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateBillPayment(billId, paymentId, data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .put(`/bills/${billId}/payments/${paymentId}`, data)
            .then((response) => {
              const updated = response.data.data
              const idx = this.billPayments.findIndex(
                (p) => p.id === updated.id
              )
              if (idx !== -1) {
                this.billPayments[idx] = updated
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.payment_updated_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteBillPayment(billId, paymentId) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .delete(`/bills/${billId}/payments/${paymentId}`)
            .then((response) => {
              this.billPayments = this.billPayments.filter(
                (p) => p.id !== paymentId
              )
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.payment_deleted_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      sendBill(bill) {
        const notificationStore = useNotificationStore()

        const to = bill.supplier?.email
        const subject = global.t('bills.default_send_subject', {
          number: bill.bill_number,
        })
        const body = global.t('bills.default_send_body')

        return new Promise((resolve, reject) => {
          axios
            .post(`/bills/${bill.id}/send`, {
              to,
              subject,
              body,
            })
            .then((response) => {
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.sent_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsSent(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/bills/${data.id}/mark-as-sent`, { status: data.status })
            .then((response) => {
              if (this.selectedBill && this.selectedBill.id === data.id) {
                this.selectedBill.status = 'SENT'
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.marked_sent_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsViewed(billId) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/bills/${billId}/mark-as-viewed`)
            .then((response) => {
              if (this.selectedBill && this.selectedBill.id === billId) {
                this.selectedBill.status = 'VIEWED'
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.marked_viewed_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsCompleted(billId) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/bills/${billId}/mark-as-completed`)
            .then((response) => {
              if (this.selectedBill && this.selectedBill.id === billId) {
                this.selectedBill.status = 'COMPLETED'
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.marked_completed_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      selectBill(data) {
        this.selectedBills = data
        if (this.selectedBills.length === this.bills.length) {
          this.selectAllField = true
        } else {
          this.selectAllField = false
        }
      },

      selectAllBills() {
        if (this.selectedBills.length === this.bills.length) {
          this.selectedBills = []
          this.selectAllField = false
        } else {
          const allBillIds = this.bills.map((bill) => bill.id)
          this.selectedBills = allBillIds
          this.selectAllField = true
        }
      },

      resetSelectedBills() {
        this.selectedBills = []
        this.selectAllField = false
      },

      setSelectAllState(val) {
        this.selectAllField = val
      },

      cloneBill(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/bills/${id}/clone`)
            .then((response) => {
              notificationStore.showNotification({
                type: 'success',
                message: global.t('bills.cloned_message'),
              })
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
// CLAUDE-CHECKPOINT
