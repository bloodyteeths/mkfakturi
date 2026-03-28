import '../sass/invoiceshelf.scss'
import 'v-tooltip/dist/v-tooltip.css'
import '@/scripts/plugins/axios.js'
import * as VueRouter from 'vue-router'
import router from '@/scripts/router/index'
import * as pinia from 'pinia'
import * as Vue from 'vue'
import * as Vuelidate from '@vuelidate/core'

import.meta.glob([
  '../static/img/**',
  '../static/fonts/**',
]);

window.pinia = pinia
window.Vuelidate = Vuelidate
import Facturino from './Facturino.js'

window.Vue = Vue
window.router = router
window.VueRouter = VueRouter

window.Facturino = new Facturino()

// Register module i18n messages before start()
import poMessages from '@/scripts/admin/i18n/purchase-orders.js'
window.Facturino.addMessages(poMessages)

import docMessages from '@/scripts/admin/i18n/documents.js'
window.Facturino.addMessages(docMessages)

import budgetMessages from '@/scripts/admin/i18n/budgets.js'
window.Facturino.addMessages(budgetMessages)

import mfgMessages from '@/scripts/admin/i18n/manufacturing.js'
window.Facturino.addMessages(mfgMessages)

import fiscalMonitorMessages from '@/scripts/admin/i18n/fiscal-monitor.js'
window.Facturino.addMessages(fiscalMonitorMessages)

import tradeMessages from '@/scripts/admin/i18n/trade.js'
window.Facturino.addMessages(tradeMessages)

import fiscalPrinterMessages from '@/scripts/admin/i18n/fiscal-printer.js'
window.Facturino.addMessages(fiscalPrinterMessages)

import posMessages from '@/scripts/admin/i18n/pos.js'
window.Facturino.addMessages(posMessages)

import wacAuditMessages from '@/scripts/admin/i18n/wac-audit.js'
window.Facturino.addMessages(wacAuditMessages)

import demandForecastMessages from '@/scripts/admin/i18n/demand-forecast.js'
window.Facturino.addMessages(demandForecastMessages)

// Auto-connect fiscal printer on app boot (if previously granted)
import { fiscalAutoConnectPlugin } from '@/scripts/admin/plugins/fiscal-auto-connect.js'
window.Facturino.booting(fiscalAutoConnectPlugin)

// Register service worker for PWA support
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker
      .register('/service-worker.js', { scope: '/' })
      .then((registration) => {
        window.swInstalled = true
        window.swRegistration = registration
        console.log('SW registered:', registration.scope)
      })
      .catch((error) => {
        window.swInstalled = false
        console.warn('SW registration failed:', error)
      })
  })
}