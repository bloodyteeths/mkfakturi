import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

export const useActivityLogStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'activityLog',
    state: () => ({
      logs: [],
      totalLogs: 0,
      currentLog: null,
    }),

    actions: {
      fetchActivityLogs(params) {
        return new Promise((resolve, reject) => {
          axios
            .get('/audit-logs', { params })
            .then((response) => {
              this.logs = response.data.data
              this.totalLogs = response.data.meta?.total || 0
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchActivityLog(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/audit-logs/${id}`)
            .then((response) => {
              this.currentLog = response.data.data
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
