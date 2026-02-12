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