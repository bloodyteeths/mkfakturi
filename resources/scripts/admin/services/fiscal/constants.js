/**
 * ISL Fiscal Printer Protocol Constants
 *
 * Shared across all Macedonian fiscal devices (Datecs/Daisy ISL family).
 * Reference: Daisy Protocol v2023.08.24 EN, ErpNet.FP BgIslFiscalPrinter
 */

// --- Frame control bytes ---
export const CONTROL = {
  SOH: 0x01, // Start of header (preamble)
  SEP: 0x04, // Separator between DATA and STATUS (response only)
  PA: 0x05, // Post-amble
  ETX: 0x03, // End of text (terminator)
  NAK: 0x15, // Negative acknowledgment — retransmit
  SYN: 0x16, // Device busy — keep waiting
}

// --- Sequence number range ---
export const SEQ_MIN = 0x20
export const SEQ_MAX = 0xff

// --- ISL Command codes ---
export const CMD = {
  // Non-fiscal operations
  OPEN_NON_FISCAL: 0x26,
  CLOSE_NON_FISCAL: 0x27,
  PRINT_NON_FISCAL_TEXT: 0x2a,
  PAPER_FEED: 0x2c,

  // Fiscal receipt operations
  OPEN_FISCAL_RECEIPT: 0x30,
  SELL_ITEM: 0x31,
  SELL_ITEM_DEPT: 0x32,
  SUBTOTAL: 0x33,
  TOTAL_AND_PAYMENT: 0x35,
  PRINT_FISCAL_TEXT: 0x36,
  CLOSE_FISCAL_RECEIPT: 0x38,
  PRINT_CUSTOMER_DATA: 0x39,

  // Receipt management
  ABORT_FISCAL_RECEIPT: 0x3c, // Standard ISL cancel
  ABORT_FISCAL_RECEIPT_DAISY: 0x82, // Daisy-specific cancel

  // Date/Time
  SET_DATETIME: 0x3d,
  GET_DATETIME: 0x3e,

  // Reports
  LAST_FISCAL_RECORD: 0x40,
  DAILY_REPORT: 0x45, // Z-report (param "0") or X-report (param "2")
  CASH_IN_OUT: 0x46,

  // Status & Info
  GET_STATUS: 0x4a,
  GET_RECEIPT_STATUS: 0x4c,
  GET_DEVICE_INFO: 0x5a,
  GET_TAX_RATES: 0x61,
  GET_TAX_ID: 0x63,
  SET_OPERATOR: 0x66,
  CURRENT_CHECK_INFO: 0x67,

  // Misc
  PRINT_DUPLICATE: 0x6d,
  GET_LAST_DOC_NUMBER: 0x71,
  READ_QR_CODE: 0x74,

  // Daisy-specific extended commands
  GET_DEVICE_CONSTANTS: 0x80,
  SELL_BY_DEPARTMENT: 0x8a,
}

// --- Macedonian VAT Tax Groups (Cyrillic letters) ---
export const TAX_GROUP = {
  A: '\u0410', // А = 18% standard
  B: '\u0411', // Б = 5% reduced
  V: '\u0412', // В = 10% restaurant/tourism (P7-01)
  G: '\u0413', // Г = 0% exempt/zero-rate
  D: '\u0414', // Д = additional group 5
  E: '\u0415', // Е = additional group 6
  ZH: '\u0416', // Ж = additional group 7
  Z: '\u0417', // З = additional group 8
}

// --- Map VAT percentage to tax group ---
export const VAT_RATE_TO_GROUP = {
  18: TAX_GROUP.A,
  5: TAX_GROUP.B,
  10: TAX_GROUP.V,
  0: TAX_GROUP.G,
}

// --- Payment type codes ---
export const PAYMENT_TYPE = {
  CASH: 'P', // По брой (cash)
  CHECK: 'N', // Payment method 1 (check)
  CARD: 'C', // Payment method 2 (card)
  DEBIT: 'D', // Payment method 3 (debit)
  BANK: 'B', // Payment method 4 (bank transfer)
}

// --- Map Facturino payment methods to ISL codes ---
export const PAYMENT_METHOD_MAP = {
  cash: PAYMENT_TYPE.CASH,
  card: PAYMENT_TYPE.CARD,
  credit_card: PAYMENT_TYPE.CARD,
  debit_card: PAYMENT_TYPE.DEBIT,
  check: PAYMENT_TYPE.CHECK,
  transfer: PAYMENT_TYPE.BANK,
  bank_transfer: PAYMENT_TYPE.BANK,
  wire: PAYMENT_TYPE.BANK,
}

// --- Reverse map: ISL payment code → human-readable payment type ---
export const ISL_PAYMENT_TO_TYPE = {
  P: 'cash',
  N: 'check',
  C: 'card',
  D: 'card',
  B: 'bank_transfer',
}

// --- Default serial port settings ---
export const SERIAL_DEFAULTS = {
  baudRate: 9600,
  dataBits: 8,
  stopBits: 1,
  parity: 'none',
  flowControl: 'none',
  bufferSize: 4096,
}

// --- Known USB-to-Serial chip vendor IDs (for WebSerial port filter) ---
export const USB_VENDOR_IDS = [
  { usbVendorId: 0x10c4 }, // Silicon Labs CP2102/CP2104 (common on Daisy, David)
  { usbVendorId: 0x0403 }, // FTDI FT232 (common on fiscal devices)
  { usbVendorId: 0x067b }, // Prolific PL2303
  { usbVendorId: 0x1a86 }, // QinHeng CH340/CH341
]

// --- Communication timing ---
export const TIMING = {
  READ_TIMEOUT_MS: 5000, // Max wait for a complete response frame
  WRITE_RETRY_COUNT: 3, // Retries on NAK
  SYN_WAIT_MS: 50, // Wait after SYN (device busy)
  INTER_COMMAND_MS: 50, // Delay between consecutive commands
}

// --- Max data lengths ---
export const MAX_SEND_DATA_LENGTH = 213
export const MAX_RECV_DATA_LENGTH = 218
export const MAX_ITEM_NAME_LENGTH = 36

// CLAUDE-CHECKPOINT
