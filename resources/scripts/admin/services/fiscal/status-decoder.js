/**
 * ISL Status Byte Decoder
 *
 * Parses the 6 status bytes returned in every ISL protocol response.
 * Each byte has bit 7 always set (0x80+). Bits 0-6 carry error/warning/info flags.
 *
 * Reference: ErpNet.FP BgIslFiscalPrinter, fpgate DeviceDaisyV1.java
 */

/**
 * @typedef {Object} StatusInfo
 * @property {boolean} ok - True if no errors
 * @property {string[]} errors - Error messages (blocking)
 * @property {string[]} warnings - Warning messages (non-blocking)
 * @property {boolean} paperOut - No paper
 * @property {boolean} paperLow - Paper running low
 * @property {boolean} fiscalMemoryFull - Cannot issue more receipts
 * @property {boolean} receiptOpen - A fiscal receipt is currently open
 * @property {boolean} fiscalized - Device is in fiscal mode
 * @property {number} errorCode - Numeric error code from byte 3 (Daisy)
 * @property {string[]} raw - Hex representation of raw bytes
 */

// Status byte bit definitions
// Format: [byteIndex, bitMask, type ('error'|'warning'|'info'), message]
const STATUS_BITS = [
  // Byte 0 — General errors
  [0, 0x01, 'error', 'Syntax error in command'],
  [0, 0x02, 'error', 'Invalid command code'],
  [0, 0x04, 'warning', 'Date/time not set'],
  [0, 0x10, 'error', 'Printer mechanism error'],
  [0, 0x20, 'error', 'General error — command rejected'],

  // Byte 1 — Operation state
  [1, 0x01, 'warning', 'Overflow during operation'],
  [1, 0x02, 'error', 'Command not allowed in current fiscal mode'],
  [1, 0x04, 'warning', 'RAM has been reset'],
  [1, 0x08, 'warning', 'Low battery'],
  [1, 0x20, 'error', 'Wrong password'],
  [1, 0x40, 'warning', 'Cutter error'],

  // Byte 2 — Paper and documents
  [2, 0x01, 'error', 'No paper — load paper into the printer'],
  [2, 0x02, 'warning', 'Paper running low'],
  [2, 0x04, 'warning', 'Electronic journal memory full'],
  // Bit 3 (0x08) = receipt open — handled separately
  [2, 0x10, 'warning', 'Electronic journal near capacity'],

  // Byte 4 — Fiscal memory
  [4, 0x01, 'error', 'Fiscal memory write error'],
  [4, 0x04, 'warning', 'Fiscal memory read/write error'],
  [4, 0x08, 'warning', 'Less than 50 fiscal memory records remaining'],
  [4, 0x10, 'error', 'Fiscal memory full — contact service technician'],
  [4, 0x20, 'error', 'General fiscal memory error'],
]

/**
 * Decode 6 ISL status bytes into a structured status object.
 *
 * @param {Uint8Array} statusBytes - 6 raw status bytes from device response
 * @returns {StatusInfo}
 */
export function decodeStatus(statusBytes) {
  if (!statusBytes || statusBytes.length < 6) {
    return {
      ok: false,
      errors: ['Incomplete status data from device'],
      warnings: [],
      paperOut: false,
      paperLow: false,
      fiscalMemoryFull: false,
      receiptOpen: false,
      fiscalized: false,
      errorCode: 0,
      raw: [],
    }
  }

  const errors = []
  const warnings = []

  // Check each defined status bit
  for (const [byteIdx, mask, type, message] of STATUS_BITS) {
    // Mask out bit 7 (always set) before checking
    const val = statusBytes[byteIdx] & 0x7f
    if (val & mask) {
      if (type === 'error') {
        errors.push(message)
      } else if (type === 'warning') {
        warnings.push(message)
      }
    }
  }

  // Byte 3: Numeric error code (Daisy) — bits 0-6
  const errorCode = statusBytes[3] & 0x7f

  // Individual flag extractions
  const paperOut = !!(statusBytes[2] & 0x01)
  const paperLow = !!(statusBytes[2] & 0x02)
  const receiptOpen = !!(statusBytes[2] & 0x08)
  const fiscalMemoryFull = !!(statusBytes[4] & 0x10)

  // Byte 5: Fiscal configuration
  const fiscalized = !!(statusBytes[5] & 0x08)
  const taxRatesSet = !!(statusBytes[5] & 0x10)
  const serialNumberSet = !!(statusBytes[5] & 0x20)
  const fmReady = !!(statusBytes[5] & 0x40)

  // Add error code context if non-zero
  if (errorCode > 0 && !errors.some((e) => e.includes('error code'))) {
    errors.push(`Device error code: ${errorCode}`)
  }

  return {
    ok: errors.length === 0,
    errors,
    warnings,
    paperOut,
    paperLow,
    fiscalMemoryFull,
    receiptOpen,
    fiscalized,
    taxRatesSet,
    serialNumberSet,
    fmReady,
    errorCode,
    raw: Array.from(statusBytes).map((b) => '0x' + b.toString(16).padStart(2, '0')),
  }
}

/**
 * Check if status indicates a receipt is currently open.
 * Useful for auto-recovery (abort stale receipt before new one).
 */
export function isReceiptOpen(statusBytes) {
  if (!statusBytes || statusBytes.length < 3) return false
  return !!(statusBytes[2] & 0x08)
}

/**
 * Check if status has any error flags set.
 */
export function hasErrors(statusBytes) {
  if (!statusBytes || statusBytes.length < 6) return true
  return decodeStatus(statusBytes).errors.length > 0
}

// CLAUDE-CHECKPOINT
