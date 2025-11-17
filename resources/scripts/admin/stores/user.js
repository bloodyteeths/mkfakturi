import axios from 'axios'
import { defineStore } from 'pinia'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

export const useUserStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'user',

    state: () => ({
      currentUser: null,
      currentAbilities: [],
      currentUserSettings: {},

      userForm: {
        name: '',
        email: '',
        password: '',
        confirm_password: '',
        language: '',
      },
    }),

    getters: {
      currentAbilitiesCount: (state) => state.currentAbilities.length,
    },

    actions: {
      updateCurrentUser(data) {
        return new Promise((resolve, reject) => {
          axios
            .put('/me', data)
            .then((response) => {
              this.currentUser = response.data.data
              Object.assign(this.userForm, response.data.data)
              const notificationStore = useNotificationStore()
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.account_settings.updated_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchCurrentUser(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/me`, params)
            .then((response) => {
              this.currentUser = response.data.data
              this.userForm = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      uploadAvatar(data) {
        return new Promise((resolve, reject) => {
          axios
            .post('/me/upload-avatar', data)
            .then((response) => {
              this.currentUser.avatar = response.data.data.avatar
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchUserSettings(settings) {
        return new Promise((resolve, reject) => {
          axios
            .get('/me/settings', {
              params: {
                settings,
              },
            })
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateUserSettings(data) {
        return new Promise((resolve, reject) => {
          axios
            .put('/me/settings', data)
            .then((response) => {
              if (data.settings.language) {
                this.currentUserSettings.language = data.settings.language
                global.locale.value = data.settings.language
              }
              if (data.settings.default_estimate_template) {
                this.currentUserSettings.default_estimate_template =
                  data.settings.default_estimate_template
              }
              if (data.settings.default_invoice_template) {
                this.currentUserSettings.default_invoice_template =
                  data.settings.default_invoice_template
              }
              if (data.settings.show_ai_insights !== undefined) {
                this.currentUserSettings.show_ai_insights = data.settings.show_ai_insights
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      hasAbilities(abilities) {
        // Owners have all abilities - check if user is owner first
        if (this.currentUser && this.currentUser.is_owner) {
          return true
        }
        
        // Check abilities array
        return !!this.currentAbilities.find((ab) => {
          if (ab.name === '*') return true
          if (typeof abilities === 'string') {
            return ab.name === abilities
          }
          return !!abilities.find((p) => {
            return ab.name === p
          })
        })
      },

      hasAllAbilities(abilities) {
        let isAvailable = true
        this.currentAbilities.filter((ab) => {
          let hasContain = !!abilities.find((p) => {
            return ab.name === p
          })
          if (!hasContain) {
            isAvailable = false
          }
        })

        return isAvailable
      },
    },
  })()
}
