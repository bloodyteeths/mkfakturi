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

  return config
})

/**
 * Response Interceptor - Handle limit_exceeded errors globally
 */
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    // Check if this is a limit_exceeded error (403 with specific error code)
    if (
      error.response &&
      error.response.status === 403 &&
      error.response.data &&
      error.response.data.error === 'limit_exceeded'
    ) {
      // Import and use the upgrade store dynamically to avoid circular imports
      import('@/scripts/stores/upgrade.js').then(({ useUpgradeStore }) => {
        const upgradeStore = useUpgradeStore()
        upgradeStore.showLimitExceeded(error.response.data)
      })

      // Still reject the promise so the calling code knows the request failed
      return Promise.reject(error)
    }

    // For all other errors, just pass them through
    return Promise.reject(error)
  }
)

export default axios
