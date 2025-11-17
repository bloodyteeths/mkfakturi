import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

export const useReceiptScannerStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'receiptScanner',
    state: () => ({
      lastResult: null,
      isScanning: false,
    }),
    actions: {
      scanReceipt(file) {
        this.isScanning = true
        this.lastResult = null

        const formData = new FormData()
        formData.append('receipt', file)

        return new Promise((resolve, reject) => {
          axios
            .post('/receipts/scan', formData, {
              headers: {
                'Content-Type': 'multipart/form-data',
              },
            })
            .then((response) => {
              this.lastResult = response.data
              this.isScanning = false
              resolve(response)
            })
            .catch((err) => {
              this.isScanning = false
              // Surface more detail in browser console for debugging
              // (status code, message, backend payload if any)
              // eslint-disable-next-line no-console
              console.error('Receipt scan failed', {
                status: err.response?.status,
                statusText: err.response?.statusText,
                data: err.response?.data,
              })
              handleError(err)
              reject(err)
            })
        })
      },
    },
  })()
}
