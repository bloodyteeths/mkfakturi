/**
 * Windows-1251 (CP1251) ↔ Unicode Charset Encoder
 *
 * Fiscal printers use CP1251 for Cyrillic text. The browser works in
 * UTF-16/UTF-8, so we need bidirectional conversion.
 *
 * Covers full Cyrillic alphabet including Macedonian-specific characters
 * (Ѓ, Ј, Ќ, Ѕ, Љ, Њ, Џ, Ѐ).
 */

// CP1251 byte values 0x80-0xFF mapped to Unicode code points
const CP1251_TO_UNICODE = {
  0x80: 0x0402, // Ђ
  0x81: 0x0403, // Ѓ (Macedonian)
  0x82: 0x201a, // ‚
  0x83: 0x0453, // ѓ (Macedonian)
  0x84: 0x201e, // „
  0x85: 0x2026, // …
  0x86: 0x2020, // †
  0x87: 0x2021, // ‡
  0x88: 0x20ac, // €
  0x89: 0x2030, // ‰
  0x8a: 0x0409, // Љ (Macedonian)
  0x8b: 0x2039, // ‹
  0x8c: 0x040a, // Њ (Macedonian)
  0x8d: 0x040c, // Ќ (Macedonian)
  0x8e: 0x040b, // Ћ
  0x8f: 0x040f, // Џ (Macedonian)
  0x90: 0x0452, // ђ
  0x91: 0x2018, // '
  0x92: 0x2019, // '
  0x93: 0x201c, // "
  0x94: 0x201d, // "
  0x95: 0x2022, // •
  0x96: 0x2013, // –
  0x97: 0x2014, // —
  0x98: 0x0098, // (undefined, pass through)
  0x99: 0x2122, // ™
  0x9a: 0x0459, // љ (Macedonian)
  0x9b: 0x203a, // ›
  0x9c: 0x045a, // њ (Macedonian)
  0x9d: 0x045c, // ќ (Macedonian)
  0x9e: 0x045b, // ћ
  0x9f: 0x045f, // џ (Macedonian)
  0xa0: 0x00a0, // non-breaking space
  0xa1: 0x040e, // Ў
  0xa2: 0x045e, // ў
  0xa3: 0x0408, // Ј (Macedonian)
  0xa4: 0x00a4, // ¤
  0xa5: 0x0490, // Ґ
  0xa6: 0x00a6, // ¦
  0xa7: 0x00a7, // §
  0xa8: 0x0401, // Ё
  0xa9: 0x00a9, // ©
  0xaa: 0x0404, // Є
  0xab: 0x00ab, // «
  0xac: 0x00ac, // ¬
  0xad: 0x00ad, // soft hyphen
  0xae: 0x00ae, // ®
  0xaf: 0x0407, // Ї
  0xb0: 0x00b0, // °
  0xb1: 0x00b1, // ±
  0xb2: 0x0406, // І
  0xb3: 0x0456, // і
  0xb4: 0x0491, // ґ
  0xb5: 0x00b5, // µ
  0xb6: 0x00b6, // ¶
  0xb7: 0x00b7, // ·
  0xb8: 0x0451, // ё
  0xb9: 0x2116, // №
  0xba: 0x0454, // є
  0xbb: 0x00bb, // »
  0xbc: 0x0458, // ј (Macedonian)
  0xbd: 0x0405, // Ѕ (Macedonian)
  0xbe: 0x0455, // ѕ (Macedonian)
  0xbf: 0x0457, // ї
  // 0xC0-0xDF: А-Я (Cyrillic uppercase) = Unicode 0x0410-0x042F
  // 0xE0-0xFF: а-я (Cyrillic lowercase) = Unicode 0x0430-0x044F
}

// Fill the Cyrillic letter ranges
for (let i = 0; i < 32; i++) {
  CP1251_TO_UNICODE[0xc0 + i] = 0x0410 + i // А-Я
}
for (let i = 0; i < 32; i++) {
  CP1251_TO_UNICODE[0xe0 + i] = 0x0430 + i // а-я
}

// Build inverse mapping: Unicode code point → CP1251 byte
const UNICODE_TO_CP1251 = {}
for (const [byte, unicode] of Object.entries(CP1251_TO_UNICODE)) {
  UNICODE_TO_CP1251[unicode] = parseInt(byte)
}

/**
 * Encode a JavaScript string to CP1251 bytes.
 * Characters not in CP1251 are replaced with '?' (0x3F).
 */
export function encodeCP1251(str) {
  const bytes = []
  for (const char of str) {
    const cp = char.codePointAt(0)
    if (cp < 0x80) {
      // ASCII passthrough
      bytes.push(cp)
    } else if (UNICODE_TO_CP1251[cp] !== undefined) {
      bytes.push(UNICODE_TO_CP1251[cp])
    } else {
      bytes.push(0x3f) // '?' for unmappable characters
    }
  }
  return new Uint8Array(bytes)
}

/**
 * Decode CP1251 bytes to a JavaScript string.
 * Unknown bytes are replaced with '?'.
 */
export function decodeCP1251(bytes) {
  let str = ''
  for (const b of bytes) {
    if (b < 0x80) {
      str += String.fromCharCode(b)
    } else if (CP1251_TO_UNICODE[b] !== undefined) {
      str += String.fromCharCode(CP1251_TO_UNICODE[b])
    } else {
      str += '?'
    }
  }
  return str
}

// CLAUDE-CHECKPOINT
