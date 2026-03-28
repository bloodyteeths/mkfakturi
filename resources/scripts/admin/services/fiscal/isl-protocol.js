/**
 * ISL Binary Protocol — Frame encoding/decoding + BCC checksum
 *
 * Implements the Datecs/Daisy ISL (Inter-System Link) binary protocol
 * used by ALL Macedonian fiscal devices.
 *
 * Frame format (host → device):
 *   [01h] [LEN] [SEQ] [CMD] [DATA...] [05h] [BCC×4] [03h]
 *
 * Frame format (device → host):
 *   [01h] [LEN] [SEQ] [CMD] [DATA...] [04h] [STATUS×6] [05h] [BCC×4] [03h]
 *
 * References:
 *   - ErpNet.FP: BgIslFiscalPrinter.Frame.cs
 *   - fpgate: ProtocolV10.java
 *   - Daisy Protocol v2023.08.24 EN (official PDF)
 */

import { CONTROL, SEQ_MIN, SEQ_MAX } from './constants.js'
import { encodeCP1251, decodeCP1251 } from './charset-encoder.js'

export class IslProtocol {
  constructor() {
    this._seq = SEQ_MIN
  }

  /**
   * Build a command frame to send to the fiscal device.
   *
   * @param {number} cmd - ISL command code (0x20-0x7F or 0x80+ for Daisy-specific)
   * @param {string|Uint8Array} data - Command parameters (string will be CP1251-encoded)
   * @returns {Uint8Array} Complete frame ready to send
   */
  buildFrame(cmd, data = '') {
    const dataBytes =
      typeof data === 'string' ? encodeCP1251(data) : data instanceof Uint8Array ? data : new Uint8Array(data)

    // LEN counts bytes from LEN (inclusive) through PA (inclusive), offset by 0x20
    // That's: LEN(1) + SEQ(1) + CMD(1) + DATA(n) + PA(1) = 4 + n, then + 0x20
    const len = 4 + dataBytes.length + 0x20
    const seq = this._nextSeq()

    // Total frame: SOH(1) + LEN(1) + SEQ(1) + CMD(1) + DATA(n) + PA(1) + BCC(4) + ETX(1)
    const frameSize = 1 + 1 + 1 + 1 + dataBytes.length + 1 + 4 + 1
    const frame = new Uint8Array(frameSize)

    let pos = 0
    frame[pos++] = CONTROL.SOH // 0x01

    // BCC covers LEN through PA (inclusive)
    const bccStart = pos
    frame[pos++] = len
    frame[pos++] = seq
    frame[pos++] = cmd & 0xff
    for (let i = 0; i < dataBytes.length; i++) {
      frame[pos++] = dataBytes[i]
    }
    frame[pos++] = CONTROL.PA // 0x05
    const bccEnd = pos

    // Calculate BCC: sum bytes from LEN through PA
    const bcc = this._computeBCC(frame, bccStart, bccEnd)
    frame[pos++] = bcc[0]
    frame[pos++] = bcc[1]
    frame[pos++] = bcc[2]
    frame[pos++] = bcc[3]

    frame[pos++] = CONTROL.ETX // 0x03

    return frame
  }

  /**
   * Parse a response frame from the fiscal device.
   *
   * @param {Uint8Array} buffer - Raw bytes received from device
   * @returns {Object} { cmd, seq, data: Uint8Array, dataString: string, status: Uint8Array(6) }
   * @throws {Error} On invalid frame structure or BCC mismatch
   */
  parseResponse(buffer) {
    if (!buffer || buffer.length < 10) {
      throw new Error(`ISL: Response too short (${buffer ? buffer.length : 0} bytes, minimum 10)`)
    }

    // Find SOH
    let sohIdx = -1
    for (let i = 0; i < buffer.length; i++) {
      if (buffer[i] === CONTROL.SOH) {
        sohIdx = i
        break
      }
    }
    if (sohIdx === -1) {
      throw new Error('ISL: No SOH (0x01) found in response')
    }

    // Find ETX
    let etxIdx = -1
    for (let i = buffer.length - 1; i > sohIdx; i--) {
      if (buffer[i] === CONTROL.ETX) {
        etxIdx = i
        break
      }
    }
    if (etxIdx === -1) {
      throw new Error('ISL: No ETX (0x03) found in response')
    }

    // Extract header
    const len = buffer[sohIdx + 1]
    const seq = buffer[sohIdx + 2]
    const cmd = buffer[sohIdx + 3]

    // Find SEP (0x04) — separates DATA from STATUS
    let sepIdx = -1
    for (let i = sohIdx + 4; i < etxIdx; i++) {
      if (buffer[i] === CONTROL.SEP) {
        sepIdx = i
        break
      }
    }

    // Find PA (0x05) — marks end of status before BCC
    let paIdx = -1
    for (let i = etxIdx - 5; i > sohIdx; i--) {
      if (buffer[i] === CONTROL.PA) {
        paIdx = i
        break
      }
    }
    if (paIdx === -1) {
      throw new Error('ISL: No PA (0x05) found in response')
    }

    // Validate BCC
    const expectedBCC = this._computeBCC(buffer, sohIdx + 1, paIdx + 1)
    const actualBCC = buffer.slice(paIdx + 1, paIdx + 5)
    for (let i = 0; i < 4; i++) {
      if (expectedBCC[i] !== actualBCC[i]) {
        throw new Error(
          `ISL: BCC mismatch at byte ${i}: expected 0x${expectedBCC[i].toString(16)}, got 0x${actualBCC[i].toString(16)}`
        )
      }
    }

    // Extract DATA and STATUS
    let dataBytes, statusBytes

    if (sepIdx !== -1) {
      // Response has both DATA and STATUS
      dataBytes = buffer.slice(sohIdx + 4, sepIdx)
      statusBytes = buffer.slice(sepIdx + 1, paIdx)
    } else {
      // Response has only DATA (no status separator)
      dataBytes = buffer.slice(sohIdx + 4, paIdx)
      statusBytes = new Uint8Array(0)
    }

    // Ensure status is exactly 6 bytes (pad with 0x80 if needed)
    const status = new Uint8Array(6)
    for (let i = 0; i < 6; i++) {
      status[i] = i < statusBytes.length ? statusBytes[i] : 0x80
    }

    return {
      cmd,
      seq,
      data: dataBytes,
      dataString: decodeCP1251(dataBytes),
      status,
    }
  }

  /**
   * Compute ISL BCC checksum.
   *
   * Sum all bytes in range [start, end) as unsigned values.
   * Encode the 16-bit sum as 4 nibbles, each offset by 0x30.
   *
   * @param {Uint8Array} bytes - Source buffer
   * @param {number} start - Start index (inclusive)
   * @param {number} end - End index (exclusive)
   * @returns {Uint8Array} 4-byte BCC
   */
  _computeBCC(bytes, start, end) {
    let sum = 0
    for (let i = start; i < end; i++) {
      sum += bytes[i]
    }
    sum &= 0xffff // Keep 16-bit

    return new Uint8Array([
      ((sum >> 12) & 0x0f) + 0x30,
      ((sum >> 8) & 0x0f) + 0x30,
      ((sum >> 4) & 0x0f) + 0x30,
      (sum & 0x0f) + 0x30,
    ])
  }

  /**
   * Advance and return the next sequence number.
   * Range: 0x20..0xFF, wrapping around.
   */
  _nextSeq() {
    this._seq++
    if (this._seq > SEQ_MAX) {
      this._seq = SEQ_MIN
    }
    return this._seq
  }

  /**
   * Reset sequence counter (e.g. after reconnection).
   */
  resetSequence() {
    this._seq = SEQ_MIN
  }
}

// CLAUDE-CHECKPOINT
