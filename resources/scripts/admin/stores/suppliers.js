import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'

export const useSuppliersStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'suppliers',
    state: () => ({
      suppliers: [],
      supplierTotalCount: 0,
      selectedSupplier: null,
      isFetchingList: false,
      isFetchingView: false,
      selectAllField: false,
      selectedSupplierIds: [],
    }),
    actions: {
      fetchSuppliers(params) {
        this.isFetchingList = true

        return new Promise((resolve, reject) => {
          axios
            .get('/api/v1/suppliers', { params })
            .then((response) => {
              this.suppliers = response.data.data
              this.supplierTotalCount =
                response.data.meta?.supplier_total_count ??
                response.data.meta?.total ??
                response.data.meta?.pagination?.total ??
                this.suppliers.length
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

      fetchSupplier(id) {
        this.isFetchingView = true

        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/suppliers/${id}`)
            .then((response) => {
              this.selectedSupplier = response.data.data
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

      fetchViewSupplier(params) {
        this.isFetchingView = true

        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/suppliers/${params.id}/stats`, { params })
            .then((response) => {
              this.selectedSupplier = response.data.data
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

      createSupplier(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post('/api/v1/suppliers', data)
            .then((response) => {
              this.suppliers.push(response.data.data)
              notificationStore.showNotification({
                type: 'success',
                message: global.t('suppliers.created_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateSupplier(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .put(`/api/v1/suppliers/${data.id}`, data)
            .then((response) => {
              const updated = response.data.data
              const idx = this.suppliers.findIndex(
                (s) => s.id === updated.id
              )
              if (idx !== -1) {
                this.suppliers[idx] = updated
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('suppliers.updated_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteSupplier(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post('/api/v1/suppliers/delete', { ids: [id] })
            .then((response) => {
              const idx = this.suppliers.findIndex((s) => s.id === id)
              if (idx !== -1) {
                this.suppliers.splice(idx, 1)
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('suppliers.deleted_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteMultipleSuppliers() {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post('/api/v1/suppliers/delete', {
              ids: this.selectedSupplierIds,
            })
            .then((response) => {
              this.suppliers = this.suppliers.filter(
                (s) => !this.selectedSupplierIds.includes(s.id)
              )
              this.selectedSupplierIds = []
              this.selectAllField = false
              notificationStore.showNotification({
                type: 'success',
                message: global.t('suppliers.deleted_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      setSelectAllState(val) {
        this.selectAllField = val
      },

      selectSuppliers(ids) {
        this.selectedSupplierIds = ids
        this.selectAllField =
          this.selectedSupplierIds.length === this.suppliers.length
      },
    },
  })()
}
// CLAUDE-CHECKPOINT

