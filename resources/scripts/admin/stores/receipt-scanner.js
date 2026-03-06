import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

function compressImage(file, maxDim = 1600, quality = 0.8) {
  return new Promise((resolve) => {
    if (!file.type.startsWith('image/')) {
      resolve(file)
      return
    }
    const img = new Image()
    img.onload = () => {
      const { width, height } = img
      if (width <= maxDim && height <= maxDim && file.size <= 1024 * 1024) {
        URL.revokeObjectURL(img.src)
        resolve(file)
        return
      }
      const scale = Math.min(maxDim / width, maxDim / height, 1)
      const canvas = document.createElement('canvas')
      canvas.width = Math.round(width * scale)
      canvas.height = Math.round(height * scale)
      const ctx = canvas.getContext('2d')
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height)
      URL.revokeObjectURL(img.src)
      canvas.toBlob(
        (blob) => {
          const compressed = new File([blob], file.name, { type: 'image/jpeg' })
          resolve(compressed)
        },
        'image/jpeg',
        quality
      )
    }
    img.onerror = () => resolve(file)
    img.src = URL.createObjectURL(file)
  })
}

export const useReceiptScannerStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'receiptScanner',
    state: () => ({
      lastResult: null,
      isScanning: false,
      processingStep: 0,
      // Holds scanned bill data for passing to Create page
      scannedBillData: null,
      // Holds scanned invoice data for passing to Invoice Create page
      scannedInvoiceData: null,
    }),
    actions: {
      setScannedBillData(data) {
        this.scannedBillData = data
      },
      consumeScannedBillData() {
        const data = this.scannedBillData
        this.scannedBillData = null
        return data
      },
      setScannedInvoiceData(data) {
        this.scannedInvoiceData = data
      },
      consumeScannedInvoiceData() {
        const data = this.scannedInvoiceData
        this.scannedInvoiceData = null
        return data
      },
      async scanReceipt(file) {
        this.processingStep = 0
        this.isScanning = true
        this.lastResult = null

        const compressed = await compressImage(file)

        const formData = new FormData()
        formData.append('receipt', compressed)

        const stepTimers = []

        return new Promise((resolve, reject) => {
          stepTimers.push(setTimeout(() => { this.processingStep = 1 }, 500))
          stepTimers.push(setTimeout(() => { this.processingStep = 2 }, 3000))

          axios
            .post('/receipts/scan', formData, {
              headers: {
                'Content-Type': 'multipart/form-data',
              },
              timeout: 120000,
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
