import axios from 'axios'
import Ls from '@/scripts/services/ls.js'

window.Ls = Ls
window.axios = axios
axios.defaults.withCredentials = true
axios.defaults.baseURL = '/api/v1'

axios.defaults.headers.common = {
  'X-Requested-With': 'XMLHttpRequest',
}

/**
 * Request Interceptor
 */
axios.interceptors.request.use(function (config) {
  // Pass selected company to header on all requests
  const companyId = Ls.get('selectedCompany')

  const authToken = Ls.get('auth.token')

  // Ensure headers object exists
  if (!config.headers) {
    config.headers = {}
  }

  if (authToken) {
    config.headers.Authorization = authToken
  }

  if (companyId) {
    config.headers['company'] = companyId
  }

  // Auto-append partner_id for super admin impersonation on partner API routes
  if (config.url && config.url.startsWith('/partner/')) {
    const urlParams = new URLSearchParams(window.location.search)
    const partnerId = urlParams.get('partner_id')
    if (partnerId) {
      config.params = { ...config.params, partner_id: partnerId }
    }
  }

  return config
})

/**
 * Response Interceptor - Handle limit_exceeded errors globally
 */
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 403 && error.response.data) {
      const errorCode = error.response.data.error

      // Company limit exceeded — show upgrade modal
      if (errorCode === 'limit_exceeded') {
        import('@/scripts/stores/upgrade.js').then(({ useUpgradeStore }) => {
          const upgradeStore = useUpgradeStore()
          upgradeStore.showLimitExceeded(error.response.data)
        })
        return Promise.reject(error)
      }

      // Partner limit exceeded — show partner upgrade modal
      if (errorCode === 'partner_limit_exceeded') {
        import('@/scripts/stores/upgrade.js').then(({ useUpgradeStore }) => {
          const upgradeStore = useUpgradeStore()
          upgradeStore.showPartnerLimitExceeded(error.response.data)
        })
        return Promise.reject(error)
      }

      // View-only mode — show view-only notice
      if (errorCode === 'view_only_mode') {
        import('@/scripts/stores/upgrade.js').then(({ useUpgradeStore }) => {
          const upgradeStore = useUpgradeStore()
          upgradeStore.showViewOnlyMode(error.response.data)
        })
        return Promise.reject(error)
      }
    }

    // For all other errors, just pass them through
    return Promise.reject(error)
  }
)

export default axios
