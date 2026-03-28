/**
 * Fiscal Printer Composable — Global Singleton
 *
 * Provides reactive Vue state and methods for browser-based fiscal printing
 * via WebSerial API. Connection persists across navigation (module-level singleton).
 *
 * Auto-reconnects to previously-granted USB devices on app start.
 * Listens for USB connect/disconnect events.
 *
 * Usage:
 *   const { isConnected, connect, fiscalizeInvoice } = useFiscalPrinter()
 */

import { ref, computed, shallowRef } from 'vue'
import { FiscalPrinterService, FiscalError } from '@/scripts/admin/services/fiscal/fiscal-printer-service'
import { WebSerialTransport } from '@/scripts/admin/services/fiscal/webserial-transport'
import { PAYMENT_METHOD_MAP, PAYMENT_TYPE } from '@/scripts/admin/services/fiscal/constants'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

// ─── Module-level singleton state (persists across navigation) ───

let _service = null
let _usbListenersAttached = false

const isSupported = ref(false)
const isConnected = ref(false)
const isProcessing = ref(false)
const deviceInfo = shallowRef(null)
const lastStatus = shallowRef(null)
const lastReceipt = shallowRef(null)
const error = ref(null)
const connectionLog = ref([])
const autoConnectAttempted = ref(false)

// Initialize support check immediately (safe — just checks navigator)
if (typeof navigator !== 'undefined') {
  isSupported.value = 'serial' in navigator
}

// ─── Singleton service getter ───

function _getService() {
  if (!_service) _service = new FiscalPrinterService()
  return _service
}

function _log(type, message) {
  connectionLog.value.unshift({
    type,
    message,
    timestamp: new Date().toLocaleTimeString(),
  })
  if (connectionLog.value.length > 50) {
    connectionLog.value.length = 50
  }
}

// ─── USB event listeners (attached once globally) ───

function _attachUsbListeners() {
  if (_usbListenersAttached || !isSupported.value) return
  _usbListenersAttached = true

  navigator.serial.addEventListener('connect', async () => {
    // A previously-granted device was plugged in — auto-reconnect
    if (!isConnected.value) {
      _log('usb', 'USB device plugged in — auto-connecting...')
      try {
        await _autoConnect()
      } catch (e) {
        console.warn('Auto-connect on USB plug failed:', e.message)
      }
    }
  })

  navigator.serial.addEventListener('disconnect', () => {
    if (isConnected.value) {
      isConnected.value = false
      deviceInfo.value = null
      lastStatus.value = null
      _log('disconnected', 'USB device unplugged')

      // Show warning notification
      try {
        const notificationStore = useNotificationStore()
        notificationStore.showNotification({
          type: 'warning',
          message: window.i18n?.global?.t('fiscal.device_disconnected') || 'Fiscal printer disconnected',
        })
      } catch (_e) { /* notification store may not be ready */ }
    }
  })
}

// ─── Auto-connect (uses getPorts — no user gesture needed) ───

async function _autoConnect() {
  if (isConnected.value) return null // Already connected
  if (!isSupported.value) return null

  const service = _getService()
  try {
    const result = await service.autoConnect()
    if (!result) return null // No previously-granted ports

    isConnected.value = true
    deviceInfo.value = result.deviceInfo
    lastStatus.value = result.status
    error.value = null

    _log('connected', `Auto: ${result.deviceInfo?.model || 'Fiscal device'}`)

    // Show success notification
    try {
      const notificationStore = useNotificationStore()
      notificationStore.showNotification({
        type: 'success',
        message: window.i18n?.global?.t('fiscal.auto_connected', {
          device: result.deviceInfo?.model || 'Fiscal Device',
        }) || `Connected to ${result.deviceInfo?.model || 'Fiscal Device'}`,
      })
    } catch (_e) { /* notification store may not be ready */ }

    return result
  } catch (e) {
    _log('error', `Auto-connect failed: ${e.message}`)
    return null
  }
}

// ─── Composable ───

export function useFiscalPrinter() {
  const notificationStore = useNotificationStore()
  const { t } = useI18n()

  // Attach USB listeners on first composable use
  _attachUsbListeners()

  // --- Computed ---
  const canFiscalize = computed(
    () => isSupported.value && isConnected.value && !isProcessing.value
  )

  const statusSummary = computed(() => {
    if (!lastStatus.value) return null
    return {
      ok: lastStatus.value.ok,
      paperOut: lastStatus.value.paperOut,
      paperLow: lastStatus.value.paperLow,
      fiscalMemoryFull: lastStatus.value.fiscalMemoryFull,
      receiptOpen: lastStatus.value.receiptOpen,
      fiscalized: lastStatus.value.fiscalized,
    }
  })

  // --- Actions ---

  /**
   * User-initiated connect — triggers browser port picker dialog.
   */
  async function connect(serialOptions = {}) {
    error.value = null
    const service = _getService()

    try {
      const result = await service.connect(serialOptions)
      if (!result) return null // User cancelled

      isConnected.value = true
      deviceInfo.value = result.deviceInfo
      lastStatus.value = result.status

      _log('connected', `${result.deviceInfo?.model || 'Fiscal device'}`)

      notificationStore.showNotification({
        type: 'success',
        message: t('fiscal.connected', {
          device: result.deviceInfo?.model || 'Fiscal Device',
        }),
      })

      return result
    } catch (e) {
      error.value = e.message
      isConnected.value = false
      _log('error', e.message)

      if (e.name !== 'NotFoundError') {
        notificationStore.showNotification({
          type: 'error',
          message: t('fiscal.connection_failed', { error: e.message }),
        })
      }
      throw e
    }
  }

  /**
   * Auto-connect to previously-granted ports (no user gesture needed).
   */
  async function autoConnect() {
    if (autoConnectAttempted.value) return null
    autoConnectAttempted.value = true
    return _autoConnect()
  }

  /**
   * Fiscalize an invoice via WebSerial → fiscal printer, then record on server.
   */
  async function fiscalizeInvoice(invoice, deviceId) {
    if (!canFiscalize.value) {
      throw new Error('Cannot fiscalize: device not ready')
    }

    error.value = null
    isProcessing.value = true

    try {
      const receiptData = _mapInvoiceToReceipt(invoice)
      const service = _getService()
      const result = await service.printReceipt(receiptData)
      lastReceipt.value = result

      _log('receipt', `#${result.receiptNumber}`)

      // POST result to server for record-keeping
      const serverResponse = await axios.post(
        `/fiscal-devices/${deviceId}/record-receipt`,
        {
          invoice_id: invoice.id,
          receipt_number: result.receiptNumber,
          fiscal_id: result.fiscalId,
          amount: invoice.total,
          vat_amount: invoice.tax || invoice.vat_total || 0,
          raw_response: result.rawResponse,
          source: 'webserial',
        }
      )

      notificationStore.showNotification({
        type: 'success',
        message: t('fiscal.receipt_printed', { number: result.receiptNumber }),
      })

      return {
        receipt: result,
        serverRecord: serverResponse.data?.data,
      }
    } catch (e) {
      error.value = e.message
      _log('error', e.message)

      notificationStore.showNotification({
        type: 'error',
        message: t('fiscal.print_failed', { error: e.message }),
      })
      throw e
    } finally {
      isProcessing.value = false
    }
  }

  /**
   * Check printer status.
   */
  async function getStatus() {
    try {
      const service = _getService()
      lastStatus.value = await service.getStatus()
      return lastStatus.value
    } catch (e) {
      error.value = e.message
      throw e
    }
  }

  /**
   * Print daily Z-report and record on server.
   */
  async function dailyReport(deviceId) {
    isProcessing.value = true
    try {
      const service = _getService()
      const result = await service.dailyZReport()

      await axios.post(`/fiscal-devices/${deviceId}/record-z-report`, {
        report_number: result.reportNumber,
        total_amount: result.totalAmount,
        total_vat: result.totalVat,
        receipt_count: result.receiptCount,
        raw_response: result.rawResponse,
        source: 'webserial',
      })

      _log('z-report', `#${result.reportNumber}`)

      notificationStore.showNotification({
        type: 'success',
        message: t('fiscal.z_report_printed', { number: result.reportNumber }),
      })
      return result
    } catch (e) {
      error.value = e.message
      _log('error', e.message)
      throw e
    } finally {
      isProcessing.value = false
    }
  }

  /**
   * Open the cash drawer via fiscal device.
   */
  async function cashDrawerKick() {
    if (!isConnected.value) return
    try {
      const service = _getService()
      await service.cashDrawerKick()
      _log('drawer', 'Cash drawer opened')
    } catch (e) {
      _log('error', `Drawer open failed: ${e.message}`)
    }
  }

  /**
   * Disconnect from the fiscal printer.
   */
  async function disconnect() {
    const service = _getService()
    await service.disconnect()
    isConnected.value = false
    deviceInfo.value = null
    lastStatus.value = null
    _log('disconnected', '')
  }

  // --- Helpers ---

  function _mapInvoiceToReceipt(invoice) {
    return {
      operator: '1',
      uniqueSaleNumber: _generateUSN(invoice),
      tillNumber: '0001',
      items: (invoice.items || []).map((item) => ({
        name: item.name || item.description || 'Item',
        vatRate: parseInt(item.tax?.percent || item.vat_rate || 18),
        price: item.price, // in cents
        quantity: parseFloat(item.quantity || 1),
      })),
      paymentType:
        PAYMENT_METHOD_MAP[invoice.payment_method] || PAYMENT_TYPE.CASH,
      total: invoice.total, // in cents
    }
  }

  function _generateUSN(invoice) {
    const eik = (invoice.company?.eik || invoice.company?.vat_number || '00000000')
      .replace(/\D/g, '')
      .padStart(8, '0')
      .slice(0, 8)
    const pos = '0001'
    const seq = String(invoice.invoice_number || '0000001')
      .replace(/\D/g, '')
      .padStart(7, '0')
      .slice(0, 7)
    return `${eik}-${pos}-${seq}`
  }

  return {
    // State (shared singleton refs)
    isSupported,
    isConnected,
    isProcessing,
    deviceInfo,
    lastStatus,
    lastReceipt,
    error,
    connectionLog,

    // Computed
    canFiscalize,
    statusSummary,

    // Actions
    connect,
    autoConnect,
    disconnect,
    fiscalizeInvoice,
    getStatus,
    dailyReport,
    cashDrawerKick,
  }
}

export default useFiscalPrinter

// CLAUDE-CHECKPOINT
