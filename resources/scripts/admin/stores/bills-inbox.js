import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

export const useBillsInboxStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'billsInbox',
    state: () => ({
      drafts: [],
      totalDrafts: 0,
      isFetching: false,
    }),
    actions: {
      fetchDraftBills(params = {}) {
        this.isFetching = true

        const query = {
          ...params,
          status: 'DRAFT',
        }

        return new Promise((resolve, reject) => {
          axios
            .get('/api/v1/bills', { params: query })
            .then((response) => {
              this.drafts = response.data.data
              this.totalDrafts =
                response.data.meta?.bill_total_count ??
                response.data.meta?.total ??
                this.drafts.length
              this.isFetching = false
              resolve(response)
            })
            .catch((err) => {
              this.isFetching = false
              handleError(err)
              reject(err)
            })
        })
      },
    },
  })()
}
