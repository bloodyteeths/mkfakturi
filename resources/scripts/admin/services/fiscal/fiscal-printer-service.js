/**
 * Fiscal Printer Service — High-Level Fiscal Operations
 *
 * Orchestrates ISL protocol commands into complete fiscal workflows:
 * - Print fiscal receipt (open → sell items → payment → close)
 * - Daily Z-report
 * - Device status check
 * - Device info retrieval
 *
 * Handles auto-recovery: if a receipt is stuck open, aborts it before retrying.
 */

import { IslProtocol } from './isl-protocol.js'
import { WebSerialTransport } from './webserial-transport.js'
import { decodeStatus, isReceiptOpen } from './status-decoder.js'
import { CMD, VAT_RATE_TO_GROUP, PAYMENT_METHOD_MAP, PAYMENT_TYPE, TAX_GROUP, USB_VENDOR_IDS, TIMING, MAX_ITEM_NAME_LENGTH } from './constants.js'
import { encodeCP1251 } from './charset-encoder.js'

export class FiscalPrinterService {
  constructor() {
    this._protocol = new IslProtocol()
    this._transport = new WebSerialTransport()
    this._connected = false
    this._deviceInfo = null
  }

  /**
   * Check if WebSerial is available in this browser.
   */
  static isSupported() {
    return WebSerialTransport.isSupported()
  }

  /**
   * Request port from user and connect to the fiscal printer.
   *
   * @param {Object} serialOptions - Override default serial settings (baudRate, etc.)
   * @returns {Object} { portInfo, deviceInfo, status } or null if user cancelled
   */
  async connect(serialOptions = {}) {
    // Step 1: Request port (triggers browser permission dialog)
    const portInfo = await this._transport.requestPort(USB_VENDOR_IDS)
    if (!portInfo && !this._transport._port) {
      return null // User cancelled
    }

    return this._openAndIdentify(serialOptions)
  }

  /**
   * Auto-connect to a previously-granted port (no user gesture needed).
   * Returns null if no previously-granted ports exist.
   */
  async autoConnect(serialOptions = {}) {
    const ports = await WebSerialTransport.getPorts()
    if (!ports || ports.length === 0) return null

    // Use the first available port
    this._transport.usePort(ports[0])
    return this._openAndIdentify(serialOptions)
  }

  /**
   * Open serial connection and identify the device.
   * @private
   */
  async _openAndIdentify(serialOptions = {}) {
    if (serialOptions.baudRate) {
      this._transport._options.baudRate = serialOptions.baudRate
    }
    await this._transport.connect()
    this._connected = true
    this._protocol.resetSequence()

    // Identify device
    try {
      this._deviceInfo = await this.getDeviceInfo()
    } catch (e) {
      this._deviceInfo = { model: 'Unknown', firmwareVersion: '', fiscalMemorySerial: '' }
    }

    // Get initial status
    const status = await this.getStatus()

    return { portInfo: this._transport.portInfo, deviceInfo: this._deviceInfo, status }
  }

  /**
   * Get device status (CMD 0x4A).
   * @returns {import('./status-decoder.js').StatusInfo}
   */
  async getStatus() {
    this._ensureConnected()
    const response = await this._sendCommand(CMD.GET_STATUS, '', false)
    return decodeStatus(response.status)
  }

  /**
   * Get device info (CMD 0x5A).
   * @returns {{ model: string, firmwareVersion: string, fiscalMemorySerial: string }}
   */
  async getDeviceInfo() {
    this._ensureConnected()
    const response = await this._sendCommand(CMD.GET_DEVICE_INFO, '', false)

    // Response format varies by device, typically comma or tab separated:
    // "Model FWVersion FWDate FWTime,Checksum,Switches,Country,SerialNum,FMNum"
    const parts = response.dataString.split(',')
    const modelParts = (parts[0] || '').split(' ')

    return {
      model: modelParts[0] || 'Unknown',
      firmwareVersion: modelParts.slice(1).join(' ').trim(),
      fiscalMemorySerial: parts[4] || parts[3] || '',
      fmNumber: parts[5] || '',
      country: parts[3] || '',
      raw: response.dataString,
    }
  }

  /**
   * Print a complete fiscal receipt.
   *
   * @param {Object} receiptData
   * @param {string} receiptData.operator - Operator number (default "1")
   * @param {string} receiptData.uniqueSaleNumber - USN format: NNNNNNNN-PPPP-SSSSSSS
   * @param {string} receiptData.tillNumber - POS terminal number (default "0001")
   * @param {Array} receiptData.items - Array of { name, vatRate, price (cents), quantity }
   * @param {string} receiptData.paymentType - Payment type code (P/C/N/D/B)
   * @param {number} receiptData.total - Total in cents
   * @returns {{ receiptNumber: string, fiscalId: string, rawResponse: string }}
   */
  async printReceipt(receiptData) {
    this._ensureConnected()

    // Auto-recovery: if a receipt is stuck open, abort it first
    await this._ensureNoOpenReceipt()

    // Step 1: Open fiscal receipt (CMD 0x30)
    // Format: "{operatorNum},{password},{tillNum},{uniqueSaleNumber}"
    const operator = receiptData.operator || '1'
    const password = receiptData.password || '0000'
    const tillNum = receiptData.tillNumber || '0001'
    const usn = receiptData.uniqueSaleNumber || ''

    const openData = usn
      ? `${operator},${password},${tillNum},${usn}`
      : `${operator},${password},${tillNum}`

    await this._sendCommand(CMD.OPEN_FISCAL_RECEIPT, openData)

    // Step 2: Sell each item (CMD 0x31)
    for (const item of receiptData.items || []) {
      await this._sellItem(item)
      await this._sleep(TIMING.INTER_COMMAND_MS)
    }

    // Step 3: Total and payment (CMD 0x35)
    const paymentChar = receiptData.paymentType || PAYMENT_TYPE.CASH
    const totalMKD = (receiptData.total / 100).toFixed(2)
    const paymentData = `\t${paymentChar}${totalMKD}`

    await this._sendCommand(CMD.TOTAL_AND_PAYMENT, paymentData)

    // Step 4: Close fiscal receipt (CMD 0x38)
    const closeResponse = await this._sendCommand(CMD.CLOSE_FISCAL_RECEIPT)

    // Parse close response: typically "receiptNumber" or "receiptNumber\tfiscalId"
    const closeParts = closeResponse.dataString.split('\t')

    return {
      receiptNumber: (closeParts[0] || '').trim(),
      fiscalId: (closeParts[1] || '').trim(),
      rawResponse: JSON.stringify({
        data: closeResponse.dataString,
        status: closeResponse.status ? Array.from(closeResponse.status) : [],
      }),
    }
  }

  /**
   * Print a daily Z-report (CMD 0x45 with param "0").
   * This zeroes the daily counters on the device.
   *
   * @returns {{ reportNumber: string, totalAmount: string, totalVat: string, receiptCount: string }}
   */
  async dailyZReport() {
    this._ensureConnected()

    // "0" = Z-report (zeroing), "2" = X-report (non-zeroing)
    const response = await this._sendCommand(CMD.DAILY_REPORT, '0')
    const parts = response.dataString.split(',')

    return {
      reportNumber: (parts[0] || '').trim(),
      totalAmount: (parts[1] || '0').trim(),
      totalVat: (parts[2] || '0').trim(),
      receiptCount: (parts[3] || '0').trim(),
      rawResponse: JSON.stringify({
        data: response.dataString,
        status: response.status ? Array.from(response.status) : [],
      }),
    }
  }

  /**
   * Print an X-report (non-zeroing daily report).
   */
  async dailyXReport() {
    this._ensureConnected()
    const response = await this._sendCommand(CMD.DAILY_REPORT, '2')
    return {
      data: response.dataString,
      status: decodeStatus(response.status),
    }
  }

  /**
   * Open cash drawer via CASH_IN_OUT command (CMD 0x46).
   * Sends a 0-amount cash-in to trigger the drawer mechanism.
   */
  async cashDrawerKick() {
    this._ensureConnected()
    // CMD 0x46 with "0" amount triggers drawer open
    await this._sendCommand(CMD.CASH_IN_OUT, '0', false)
  }

  /**
   * Abort/cancel a currently open fiscal receipt.
   * Uses Daisy-specific CMD 0x82, falls back to standard 0x3C.
   */
  async abortReceipt() {
    this._ensureConnected()
    try {
      await this._sendCommand(CMD.ABORT_FISCAL_RECEIPT_DAISY, '', false)
    } catch (_e) {
      // Fallback to standard ISL cancel
      await this._sendCommand(CMD.ABORT_FISCAL_RECEIPT, '', false)
    }
  }

  /**
   * Get date/time from device (CMD 0x3E).
   */
  async getDateTime() {
    this._ensureConnected()
    const response = await this._sendCommand(CMD.GET_DATETIME, '', false)
    return response.dataString
  }

  /**
   * Disconnect from the fiscal printer.
   */
  async disconnect() {
    this._connected = false
    this._deviceInfo = null
    await this._transport.disconnect()
  }

  get isConnected() {
    return this._connected && this._transport.isConnected
  }

  get deviceInfo() {
    return this._deviceInfo
  }

  // ─── Private methods ───

  /**
   * Send a single ISL command and validate the response status.
   *
   * @param {number} cmd - Command code
   * @param {string|Uint8Array} data - Command data
   * @param {boolean} throwOnError - Whether to throw on status errors (default true)
   * @returns {Object} Parsed response { cmd, seq, data, dataString, status }
   */
  async _sendCommand(cmd, data = '', throwOnError = true) {
    const frame = this._protocol.buildFrame(cmd, data)
    const rawResponse = await this._transport.sendAndReceive(frame)
    const parsed = this._protocol.parseResponse(rawResponse)
    const status = decodeStatus(parsed.status)

    if (throwOnError && !status.ok) {
      const errorMsg = status.errors.join('; ') || `Fiscal device error (cmd 0x${cmd.toString(16)})`
      throw new FiscalError(errorMsg, cmd, status)
    }

    return parsed
  }

  /**
   * Send a sell item command (CMD 0x31).
   *
   * Format: "[Line1][\nLine2]\t<TaxGroup>[Sign]<Price>[*Qty][,DiscountPct]"
   */
  async _sellItem(item) {
    const taxGroup = VAT_RATE_TO_GROUP[item.vatRate] || TAX_GROUP.A

    // Price in MKD with 2 decimal places (convert from cents)
    const priceMKD = (item.price / 100).toFixed(2)

    // Quantity: omit if 1, otherwise 3 decimal places
    const qty = parseFloat(item.quantity || 1)
    const qtyStr = qty !== 1 ? `*${qty.toFixed(3)}` : ''

    // Item name: max 36 chars, CP1251 encoded
    const name = (item.name || 'Item').substring(0, MAX_ITEM_NAME_LENGTH)

    // Discount (optional)
    let discountStr = ''
    if (item.discountPercent && item.discountPercent !== 0) {
      // Negative = discount, Positive = surcharge
      discountStr = `,${(-Math.abs(item.discountPercent)).toFixed(2)}`
    }

    const sellData = `${name}\t${taxGroup}${priceMKD}${qtyStr}${discountStr}`
    await this._sendCommand(CMD.SELL_ITEM, sellData)
  }

  /**
   * Check and abort any stale open receipt before starting a new one.
   */
  async _ensureNoOpenReceipt() {
    try {
      const response = await this._sendCommand(CMD.GET_STATUS, '', false)
      if (isReceiptOpen(response.status)) {
        console.warn('ISL: Aborting stale open receipt before new fiscalization')
        await this.abortReceipt()
        await this._sleep(200) // Give device time to close
      }
    } catch (_e) {
      // Status check failure is non-fatal here
    }
  }

  _ensureConnected() {
    if (!this._connected || !this._transport.isConnected) {
      throw new FiscalError('Not connected to fiscal printer. Call connect() first.', 0, null)
    }
  }

  _sleep(ms) {
    return new Promise((r) => setTimeout(r, ms))
  }
}

/**
 * Custom error class for fiscal device operations.
 */
export class FiscalError extends Error {
  constructor(message, command, status) {
    super(message)
    this.name = 'FiscalError'
    this.command = command
    this.status = status
  }
}

// CLAUDE-CHECKPOINT
