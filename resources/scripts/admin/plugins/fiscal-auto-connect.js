/**
 * Fiscal Auto-Connect Plugin
 *
 * Registered via Facturino.booting() — runs on app startup.
 * Attempts to auto-reconnect to previously-granted USB fiscal devices.
 *
 * Flow:
 *   1. App boots → this plugin runs
 *   2. Checks navigator.serial.getPorts() (no user gesture needed)
 *   3. If a previously-granted port exists → auto-connects → toast notification
 *   4. USB connect/disconnect events handled by the composable
 */

import { useFiscalPrinter } from '@/scripts/admin/composables/useFiscalPrinter'

export function fiscalAutoConnectPlugin(app, router) {
  // Wait for router to be ready (ensures Pinia/i18n are initialized)
  router.isReady().then(() => {
    // Small delay to let the app fully hydrate
    setTimeout(() => {
      try {
        const fiscal = useFiscalPrinter()
        if (fiscal.isSupported.value) {
          fiscal.autoConnect().catch(() => {
            // Silent — no previously-granted ports is normal
          })
        }
      } catch (e) {
        console.warn('Fiscal auto-connect plugin:', e.message)
      }
    }, 1500)
  })
}

// CLAUDE-CHECKPOINT
