# POS Roadmap to 100% — Implementation Plan

**Created**: 2026-03-28
**Status**: In Progress
**Current**: ~55% complete (Phase A done, cashier UX improvements in progress)

---

## Phase A: Quick Wins — COMPLETE ✓

- [x] **A1. Camera barcode scanner** — Native BarcodeDetector API in SearchBar, setting-gated
- [x] **A2. Product images in grid** — 64px thumbnails with lazy loading + placeholder icon
- [x] **A3. Invoice PDF QR code** — CASYS QR on invoice PDFs via CpayMerchantService
- [x] **A4. Qty tap-to-edit** — NumPad overlay in Cart via Teleport, 60px touch buttons
- [x] **A5. Warehouse selector** — Dropdown in TopBar, persisted to localStorage

## Phase A.5: Cashier UX (Legacy Compatibility) — IN PROGRESS

- [x] **A6. i18n hardcoded strings** — 50+ strings across 10 components → `t()` calls, 4 locales
- [x] **A7. PLU quick codes** — Type numeric code + Enter to add item from catalog by SKU/PLU
- [x] **A8. Quantity multiplier** — Type `3*` then scan → adds 3 units, visual badge indicator
- [x] **A9. Cash drawer kick** — ISL CMD 0x46 via WebSerial, composable method
- [ ] **A10. Receipt reprint from history** — F11 shortcut, lookup past receipts
- [ ] **A11. Embedded weight barcode** — Parse prefix 27/28 EAN barcodes for weight-based items
- [ ] **A12. X-Report** — Mid-day non-zeroing report via ISL CMD 0x45 param "2"
- [ ] **A13. Blind cash counting** — Cashier enters count before seeing expected amount
- [ ] **A14. Cash-in/Cash-out** — Petty cash transactions, logged with reason

## Phase B: Receipt Printing & Z-Report (3-5 days)

- [ ] **B1. Receipt HTML template** — Thermal-friendly receipt layout (80mm width, monospace)
- [ ] **B2. WebSerial thermal printing** — ESC/POS command builder for thermal printers
- [ ] **B3. Browser print fallback** — `window.print()` with receipt-optimized CSS
- [x] **B4. Z-Report view** — Daily closing summary (totals by payment, tax breakdown, cash reconciliation)
- [x] **B5. Z-Report print/export** — Summary data from shift close API
- [x] **B6. Cash drawer kick** — ISL CMD 0x46 via WebSerial (moved to A9)

## Phase C: Customer & Discount System (3-4 days)

- [ ] **C1. Customer search in POS** — Modal with search, select existing, or create new customer
- [ ] **C2. Cart-level discount** — Fixed amount or percentage discount on entire order
- [ ] **C3. Discount reason tracking** — Required reason field for discounts > threshold
- [ ] **C4. Coupon/promo codes** — Code validation, auto-apply discount, usage tracking
- [ ] **C5. Manager approval for large discounts** — PIN or confirmation for discounts > X%

## Phase D: POS Reports (5-7 days)

- [ ] **D1. Daily sales summary** — Total sales, returns, net, payment breakdown, tax totals
- [ ] **D2. Sales by product/category** — Top sellers, quantity, revenue, margin
- [ ] **D3. Sales by cashier/employee** — Per-user sales, returns, discounts given
- [ ] **D4. Sales by payment method** — Cash/card/mixed/CASYS breakdown for bank reconciliation
- [ ] **D5. Hourly sales distribution** — Chart of sales volume by hour (peak analysis)
- [ ] **D6. POS data CSV export** — Download daily/period data as CSV

## Phase E: Audit Trail & Security (3-4 days)

- [ ] **E1. POS action log** — Log every sale, return, void, discount, drawer open, shift change
- [ ] **E2. Cashier PIN login** — Fast PIN switch between cashiers without full logout
- [ ] **E3. Manager override workflow** — Required approval for returns, large discounts, voids
- [ ] **E4. Void sale flow** — Cancel completed sale with reason, reverse stock + payment
- [ ] **E5. Session timeout** — Auto-lock POS after inactivity, require PIN to resume

## Phase F: Offline & Resilience (5-7 days)

- [ ] **F1. Service worker** — Cache catalog, static assets, POS shell for offline use
- [ ] **F2. Offline sale queue** — Store sales in IndexedDB when offline, sync when back
- [ ] **F3. Background sync** — Auto-retry queued sales, conflict resolution
- [ ] **F4. PWA manifest** — Installable app with app icon, splash screen, standalone mode
- [ ] **F5. Connection status banner** — Real-time online/offline indicator with queue count

## Phase G: Advanced Features (future)

- [ ] **G1. Customer-facing display** — Second screen showing cart, total, payment status
- [ ] **G2. Kitchen orders to backend** — Persist restaurant orders to DB for multi-device KDS
- [ ] **G3. Fiscal relay HTTP fallback** — Fall back to ErpNet.FP API when no WebSerial device
- [ ] **G4. Loyalty/points system** — Earn/redeem points per transaction
- [ ] **G5. Bill splitting** — Split order between multiple customers/payments
- [ ] **G6. Employee time tracking** — Clock in/out, hours worked, sales per hour
- [ ] **G7. Speed-of-service metrics** — Time from order to payment, kitchen turnaround

---

## Implementation Order

```
Phase A (quick wins) → Phase B (receipts) → Phase C (customer/discount) →
Phase D (reports) → Phase E (security) → Phase F (offline) → Phase G (future)
```

Each phase is independently deployable. E2E tests added per phase.
