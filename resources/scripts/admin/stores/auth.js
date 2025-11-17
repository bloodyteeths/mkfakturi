import axios from 'axios'
import { defineStore } from 'pinia'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'
import { useUserStore } from './user'
import { useGlobalStore } from './global'

export const useAuthStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'auth',
    state: () => ({
      status: '',

      loginData: {
        email: '',
        password: '',
        remember: '',
      },
    }),

    actions: {
      login(data) {
        return new Promise((resolve, reject) => {
          // Use absolute URL to bypass axios baseURL (/api/v1)
          axios.get(window.location.origin + '/sanctum/csrf-cookie').then((response) => {
            if (response) {
              axios
                .post('/auth/login', data)
                .then((response) => {
                  resolve(response)

                  setTimeout(() => {
                    this.loginData.email = ''
                    this.loginData.password = ''
                  }, 1000)
                })
                .catch((err) => {
                  handleError(err)
                  reject(err)
                })
            }
          })
        })
      },

      logout() {
        return new Promise((resolve, reject) => {
          axios
            .post('/auth/logout')
            .then((response) => {
              // Clear user and global state
              const userStore = useUserStore()
              const globalStore = useGlobalStore()

              // Reset user state
              userStore.currentUser = null
              userStore.currentAbilities = []
              userStore.currentUserSettings = {}

              // Reset global state
              globalStore.isAppLoaded = false
              globalStore.mainMenu = []
              globalStore.settingMenu = []

              // Clear login data
              this.loginData.email = ''
              this.loginData.password = ''

              const notificationStore = useNotificationStore()
              notificationStore.showNotification({
                type: 'success',
                message: 'Logged out successfully.',
              })

              // Redirect to login page
              window.router.push('/login')
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              window.router.push('/login')
              reject(err)
            })
        })
      },
    },
  })()
}