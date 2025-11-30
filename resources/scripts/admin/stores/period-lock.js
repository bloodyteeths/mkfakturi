import axios from 'axios'
import { defineStore } from 'pinia'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

export const usePeriodLockStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'periodLock',

    state: () => ({
      dailyClosings: [],
      periodLocks: [],
      currentClosing: {
        id: null,
        date: null,
        type: 'all',
        notes: '',
      },
      currentLock: {
        id: null,
        period_start: null,
        period_end: null,
        notes: '',
      },
      isLoading: false,
    }),

    getters: {
      isEditClosing: (state) => (state.currentClosing.id ? true : false),
      isEditLock: (state) => (state.currentLock.id ? true : false),
    },

    actions: {
      resetCurrentClosing() {
        this.currentClosing = {
          id: null,
          date: null,
          type: 'all',
          notes: '',
        }
      },

      resetCurrentLock() {
        this.currentLock = {
          id: null,
          period_start: null,
          period_end: null,
          notes: '',
        }
      },

      /**
       * Fetch all daily closings for the company
       */
      fetchDailyClosings(params) {
        return new Promise((resolve, reject) => {
          this.isLoading = true
          axios
            .get('/accounting/daily-closings', { params })
            .then((response) => {
              this.dailyClosings = response.data.data
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
       * Create a new daily closing
       */
      createDailyClosing(data) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .post('/accounting/daily-closings', data)
            .then((response) => {
              this.dailyClosings.unshift(response.data.data)
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.period_lock.day_closed'),
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
       * Delete a daily closing (unlock a day)
       */
      deleteDailyClosing(id) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .delete(`/accounting/daily-closings/${id}`)
            .then((response) => {
              const index = this.dailyClosings.findIndex((c) => c.id === id)
              if (index > -1) {
                this.dailyClosings.splice(index, 1)
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.period_lock.day_unlocked'),
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
       * Fetch all period locks for the company
       */
      fetchPeriodLocks(params) {
        return new Promise((resolve, reject) => {
          this.isLoading = true
          axios
            .get('/accounting/period-locks', { params })
            .then((response) => {
              this.periodLocks = response.data.data
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
       * Create a new period lock
       */
      createPeriodLock(data) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .post('/accounting/period-locks', data)
            .then((response) => {
              this.periodLocks.unshift(response.data.data)
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.period_lock.period_locked'),
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
       * Delete a period lock (unlock a period)
       */
      deletePeriodLock(id) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .delete(`/accounting/period-locks/${id}`)
            .then((response) => {
              const index = this.periodLocks.findIndex((l) => l.id === id)
              if (index > -1) {
                this.periodLocks.splice(index, 1)
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.period_lock.period_unlocked'),
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
       * Check if a specific date is locked
       */
      checkDateLock(date, type = 'all') {
        return new Promise((resolve, reject) => {
          axios
            .get('/accounting/check-date', { params: { date, type } })
            .then((response) => {
              resolve(response.data)
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
