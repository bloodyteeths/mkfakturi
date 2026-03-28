/**
 * WebSerial Transport Layer
 *
 * Wraps the browser WebSerial API for communication with fiscal printers.
 * Handles connect/disconnect, read/write with timeout, SYN/NAK retry logic.
 *
 * WebSerial API: https://developer.mozilla.org/en-US/docs/Web/API/Web_Serial_API
 * Supported: Chrome 89+, Edge 89+, Opera 76+
 */

import { CONTROL, SERIAL_DEFAULTS, TIMING } from './constants.js'

export class WebSerialTransport {
  constructor(options = {}) {
    this._port = null
    this._reader = null
    this._writer = null
    this._options = { ...SERIAL_DEFAULTS, ...options }
  }

  /**
   * Check if WebSerial API is available in this browser.
   */
  static isSupported() {
    return typeof navigator !== 'undefined' && 'serial' in navigator
  }

  /**
   * Get previously-granted serial ports (no user gesture needed).
   * Returns ports the user already approved — enables auto-reconnect.
   */
  static async getPorts() {
    if (!WebSerialTransport.isSupported()) return []
    return navigator.serial.getPorts()
  }

  /**
   * Use an already-granted port (for auto-reconnect via getPorts).
   * @param {SerialPort} port - A port from getPorts()
   */
  usePort(port) {
    this._port = port
  }

  /**
   * Request a serial port from the user via browser permission dialog.
   * @param {Array} filters - USB vendor ID filters for the port picker
   * @returns {Object} Port info { usbVendorId, usbProductId }
   */
  async requestPort(filters = []) {
    if (!WebSerialTransport.isSupported()) {
      throw new Error('WebSerial API is not supported in this browser. Use Chrome or Edge.')
    }

    try {
      this._port = await navigator.serial.requestPort({
        filters: filters.length > 0 ? filters : undefined,
      })
      return this._port.getInfo()
    } catch (e) {
      if (e.name === 'NotFoundError') {
        // User cancelled the port picker — not an error
        return null
      }
      throw e
    }
  }

  /**
   * Open the serial connection with configured baud rate and settings.
   */
  async connect() {
    if (!this._port) {
      throw new Error('No port selected. Call requestPort() first.')
    }

    await this._port.open(this._options)

    const readable = this._port.readable
    const writable = this._port.writable
    if (!readable || !writable) {
      throw new Error('Port opened but streams are not available')
    }

    this._reader = readable.getReader()
    this._writer = writable.getWriter()
  }

  /**
   * Write raw bytes to the serial port.
   * @param {Uint8Array} data - Bytes to send
   */
  async write(data) {
    if (!this._writer) throw new Error('Port not open')
    await this._writer.write(data instanceof Uint8Array ? data : new Uint8Array(data))
  }

  /**
   * Read a complete ISL response frame with timeout.
   * Handles SYN (device busy) transparently.
   *
   * @param {number} timeoutMs - Max wait for a complete frame
   * @returns {Uint8Array} Complete response frame (SOH to ETX)
   */
  async readFrame(timeoutMs = TIMING.READ_TIMEOUT_MS) {
    const deadline = Date.now() + timeoutMs
    let buffer = new Uint8Array(0)

    while (Date.now() < deadline) {
      const remaining = deadline - Date.now()
      if (remaining <= 0) break

      let chunk
      try {
        const result = await Promise.race([
          this._reader.read(),
          this._timeout(remaining),
        ])
        if (result.done) throw new Error('Serial port stream closed unexpectedly')
        chunk = result.value
      } catch (e) {
        if (e.message === 'TIMEOUT') break
        throw e
      }

      if (!chunk || chunk.length === 0) continue

      // Append to buffer
      buffer = this._concat(buffer, chunk)

      // Handle SYN (0x16): device is busy, strip it and keep reading
      if (buffer.length === 1 && buffer[0] === CONTROL.SYN) {
        buffer = new Uint8Array(0)
        await this._sleep(TIMING.SYN_WAIT_MS)
        continue
      }

      // Strip any leading SYN bytes
      while (buffer.length > 0 && buffer[0] === CONTROL.SYN) {
        buffer = buffer.slice(1)
      }

      // Check for NAK (0x15)
      if (buffer.length === 1 && buffer[0] === CONTROL.NAK) {
        return buffer // Caller handles retry
      }

      // Check for complete frame: must have SOH and ETX
      if (buffer.length >= 10 && buffer[0] === CONTROL.SOH) {
        const etxIdx = buffer.indexOf(CONTROL.ETX)
        if (etxIdx > 0) {
          return buffer.slice(0, etxIdx + 1)
        }
      }
    }

    if (buffer.length > 0) {
      throw new Error(
        `ISL: Incomplete frame received (${buffer.length} bytes, no ETX). ` +
          `Raw: ${Array.from(buffer.slice(0, 20))
            .map((b) => '0x' + b.toString(16).padStart(2, '0'))
            .join(' ')}`
      )
    }

    throw new Error(`ISL: Read timeout — no response within ${timeoutMs}ms`)
  }

  /**
   * Send a frame and wait for a complete response, with retry on NAK.
   *
   * @param {Uint8Array} frame - Complete ISL frame to send
   * @param {number} maxRetries - Max retries on NAK
   * @param {number} timeoutMs - Read timeout per attempt
   * @returns {Uint8Array} Response frame
   */
  async sendAndReceive(frame, maxRetries = TIMING.WRITE_RETRY_COUNT, timeoutMs = TIMING.READ_TIMEOUT_MS) {
    for (let attempt = 0; attempt <= maxRetries; attempt++) {
      await this.write(frame)

      const response = await this.readFrame(timeoutMs)

      // Check for NAK — retry with same frame
      if (response.length === 1 && response[0] === CONTROL.NAK) {
        if (attempt < maxRetries) {
          await this._sleep(100 * (attempt + 1))
          continue
        }
        throw new Error(`ISL: Device rejected command after ${maxRetries + 1} attempts (NAK)`)
      }

      return response
    }

    throw new Error('ISL: Send/receive failed — should not reach here')
  }

  /**
   * Disconnect and release the serial port.
   */
  async disconnect() {
    try {
      if (this._reader) {
        this._reader.releaseLock()
        this._reader = null
      }
      if (this._writer) {
        this._writer.releaseLock()
        this._writer = null
      }
      if (this._port) {
        await this._port.close()
        this._port = null
      }
    } catch (e) {
      // Port may already be closed
      console.warn('WebSerial disconnect:', e.message)
      this._reader = null
      this._writer = null
      this._port = null
    }
  }

  /**
   * Check if transport is connected.
   */
  get isConnected() {
    return this._port !== null && this._reader !== null && this._writer !== null
  }

  /**
   * Get info about the connected port.
   */
  get portInfo() {
    return this._port ? this._port.getInfo() : null
  }

  // --- Private helpers ---

  _concat(a, b) {
    const result = new Uint8Array(a.length + b.length)
    result.set(a, 0)
    result.set(b, a.length)
    return result
  }

  _sleep(ms) {
    return new Promise((r) => setTimeout(r, ms))
  }

  _timeout(ms) {
    return new Promise((_, reject) => setTimeout(() => reject(new Error('TIMEOUT')), ms))
  }
}

// CLAUDE-CHECKPOINT
