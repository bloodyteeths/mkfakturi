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
      processingStep: 0,
    }),
    actions: {
      scanReceipt(file) {
        this.processingStep = 0
        this.isScanning = true
        this.lastResult = null

        const formData = new FormData()
        formData.append('receipt', file)

        const stepTimers = []

        return new Promise((resolve, reject) => {
          stepTimers.push(setTimeout(() => { this.processingStep = 1 }, 500))
          stepTimers.push(setTimeout(() => { this.processingStep = 2 }, 3000))

          axios
            .post('/receipts/scan', formData, {
              headers: {
                'Content-Type': 'multipart/form-data',
              },
            })
            .then((response) => {
              this.lastResult = response.data
              this.processingStep = 3
              setTimeout(() => {
                this.isScanning = false
                this.processingStep = 0
                resolve(response)
              }, 800)
            })
            .catch((err) => {
              stepTimers.forEach(clearTimeout)
              this.isScanning = false
              this.processingStep = 0
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
