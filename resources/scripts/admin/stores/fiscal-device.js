import axios from 'axios'
import { defineStore } from 'pinia'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

export const useFiscalDeviceStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'fiscalDevice',

    state: () => ({
      fiscalDevices: [],
      supportedTypes: [],
      currentFiscalDevice: {
        id: null,
        device_type: '',
        name: '',
        serial_number: '',
        connection_type: 'tcp',
        ip_address: '',
        port: 4999,
        serial_port: '',
        is_active: true,
      },
    }),

    getters: {
      isEdit: (state) => (state.currentFiscalDevice.id ? true : false),
    },

    actions: {
      resetCurrentFiscalDevice() {
        this.currentFiscalDevice = {
          id: null,
          device_type: '',
          name: '',
          serial_number: '',
          connection_type: 'tcp',
          ip_address: '',
          port: 4999,
          serial_port: '',
          is_active: true,
        }
      },

      fetchFiscalDevices(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/fiscal-devices`, { params })
            .then((response) => {
              this.fiscalDevices = response.data.data
              this.supportedTypes = response.data.supported_types || []
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchFiscalDevice(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/fiscal-devices/${id}`)
            .then((response) => {
              this.currentFiscalDevice = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addFiscalDevice(data) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .post('/fiscal-devices', data)
            .then((response) => {
              this.fiscalDevices.push(response.data.data)
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.fiscal_devices.created_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateFiscalDevice(data) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .patch(`/fiscal-devices/${data.id}`, data)
            .then((response) => {
              if (response.data) {
                let pos = this.fiscalDevices.findIndex(
                  (device) => device.id === response.data.data.id
                )
                if (pos > -1) {
                  this.fiscalDevices[pos] = response.data.data
                }
                notificationStore.showNotification({
                  type: 'success',
                  message: global.t('settings.fiscal_devices.updated_message'),
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

      deleteFiscalDevice(id) {
        return new Promise((resolve, reject) => {
          axios
            .delete(`/fiscal-devices/${id}`)
            .then((response) => {
              let index = this.fiscalDevices.findIndex(
                (device) => device.id === id
              )
              if (index > -1) {
                this.fiscalDevices.splice(index, 1)
              }
              const notificationStore = useNotificationStore()
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.fiscal_devices.deleted_message'),
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
