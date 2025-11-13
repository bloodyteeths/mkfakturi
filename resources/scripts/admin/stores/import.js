import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'

export const useImportStore = defineStore('import', {
  state: () => ({
      // Wizard state
      currentStep: 1,
      totalSteps: 4,
      canProceed: false,
      isLoading: false,

      // Import job data
      importJob: null,
      importId: null,

      // Step 1 - Upload
      uploadedFile: null,
      fileInfo: null,
      supportedFormats: ['csv', 'xls', 'xlsx', 'xml'],
      uploadProgress: 0,
      isUploading: false,

      // Step 2 - Mapping
      detectedFields: [],
      mappingSuggestions: {},
      fieldMappings: {},
      mappingErrors: [],
      autoMappingConfidence: 0,

      // Step 3 - Validation
      validationResults: null,
      validationErrors: [],
      validationWarnings: [],
      conflictResolutions: {},
      isValidating: false,

      // Step 4 - Commit
      commitProgress: 0,
      commitStatus: 'pending', // pending, processing, completed, failed
      commitResults: null,
      isCommitting: false,

      // Progress polling
      pollingInterval: null,
      
      // Error handling
      errors: {},
      hasErrors: false,
    }),

    getters: {
      // Step validation
      isStep1Valid() {
        return this.uploadedFile && this.fileInfo && !this.isUploading
      },

      isStep2Valid() {
        return Object.keys(this.fieldMappings).length > 0 && 
               this.mappingErrors.length === 0
      },

      isStep3Valid() {
        return this.validationResults && 
               this.validationErrors.length === 0 && 
               !this.isValidating
      },

      isStep4Valid() {
        return this.commitStatus === 'completed'
      },

      // Progress calculations
      overallProgress() {
        const stepProgress = (this.currentStep - 1) / this.totalSteps * 100
        
        if (this.currentStep === 4 && this.isCommitting) {
          return stepProgress + (this.commitProgress / this.totalSteps)
        }
        
        return stepProgress
      },

      // File type detection
      fileType() {
        if (!this.fileInfo) return null
        
        const extension = this.fileInfo.name.split('.').pop().toLowerCase()
        return this.supportedFormats.includes(extension) ? extension : null
      },

      // Statistics
      totalRecords() {
        return this.validationResults?.total_records || 0
      },

      validRecords() {
        return this.validationResults?.valid_records || 0
      },

      invalidRecords() {
        return this.validationResults?.invalid_records || 0
      },
    },

    actions: {
      // Navigation
      nextStep() {
        if (this.currentStep < this.totalSteps && this.canProceedToNextStep()) {
          this.currentStep++
          this.updateCanProceed()
        }
      },

      previousStep() {
        if (this.currentStep > 1) {
          this.currentStep--
          this.updateCanProceed()
        }
      },

      goToStep(step) {
        if (step >= 1 && step <= this.totalSteps) {
          this.currentStep = step
          this.updateCanProceed()
        }
      },

      canProceedToNextStep() {
        switch (this.currentStep) {
          case 1: return this.isStep1Valid
          case 2: return this.isStep2Valid
          case 3: return this.isStep3Valid
          case 4: return false // Can't proceed beyond final step
          default: return false
        }
      },

      updateCanProceed() {
        const canProceed = this.canProceedToNextStep()
        console.log('[importStore] updateCanProceed', {
          currentStep: this.currentStep,
          canProceed,
          isStep1Valid: this.isStep1Valid,
          uploadedFile: this.uploadedFile,
          fileInfo: this.fileInfo,
          isUploading: this.isUploading,
        })
        this.canProceed = canProceed
      },

      // Step 1 - File Upload
      async uploadFile(file) {
        this.isUploading = true
        this.uploadProgress = 0
        this.resetErrors()

        try {
          const formData = new FormData()
          formData.append('file', file)
          formData.append('type', 'universal_migration')

          const response = await axios.post('/api/v1/admin/imports', formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
              this.uploadProgress = Math.round(
                (progressEvent.loaded * 100) / progressEvent.total
              )
            },
          })

          console.log('[importStore] Upload response:', response.data)

          this.importJob = response.data.data
          this.importId = this.importJob.id
          this.uploadedFile = file
          this.fileInfo = {
            name: file.name,
            size: file.size,
            type: file.type,
            lastModified: file.lastModified,
          }

          console.log('[importStore] State after upload:', {
            importJob: this.importJob,
            importId: this.importId,
            uploadedFile: this.uploadedFile,
            fileInfo: this.fileInfo,
            isUploading: this.isUploading,
          })

          const notificationStore = useNotificationStore()
          const { global } = window.i18n
          notificationStore.showNotification({
            type: 'success',
            message: global.t('imports.file_uploaded_successfully'),
          })

          return response.data

        } catch (error) {
          this.setError('upload', error.response?.data?.message || 'Upload failed')
          handleError(error)
          throw error
        } finally {
          this.isUploading = false
          // Update canProceed AFTER isUploading is set to false
          this.updateCanProceed()
        }
      },

      removeFile() {
        this.uploadedFile = null
        this.fileInfo = null
        this.uploadProgress = 0
        this.importJob = null
        this.importId = null
        this.resetState()
        this.updateCanProceed()
      },

      // Step 2 - Field Mapping
      async detectFields() {
        if (!this.importId) return

        this.isLoading = true
        this.resetErrors()

        try {
          const response = await axios.get(`/api/v1/admin/imports/${this.importId}`)
          const jobData = response.data.data

          this.detectedFields = jobData.detected_fields || []
          this.mappingSuggestions = jobData.mapping_suggestions || {}
          this.autoMappingConfidence = jobData.auto_mapping_confidence || 0

          // Apply auto-mapping suggestions
          this.applyAutoMapping()

        } catch (error) {
          this.setError('detection', error.response?.data?.message || 'Field detection failed')
          handleError(error)
        } finally {
          this.isLoading = false
        }
      },

      applyAutoMapping() {
        this.fieldMappings = { ...this.mappingSuggestions }
        this.validateMappings()
        this.updateCanProceed()
      },

      updateMapping(sourceField, targetField) {
        if (targetField) {
          this.fieldMappings[sourceField] = targetField
        } else {
          delete this.fieldMappings[sourceField]
        }
        
        this.validateMappings()
        this.updateCanProceed()
      },

      validateMappings() {
        this.mappingErrors = []
        
        // Check for required field mappings
        const requiredFields = ['name', 'email'] // Add more required fields
        const mappedTargets = Object.values(this.fieldMappings)
        
        requiredFields.forEach(field => {
          if (!mappedTargets.includes(field)) {
            this.mappingErrors.push(`Required field '${field}' is not mapped`)
          }
        })

        // Check for duplicate mappings
        const duplicates = mappedTargets.filter((item, index) => mappedTargets.indexOf(item) !== index)
        duplicates.forEach(field => {
          this.mappingErrors.push(`Field '${field}' is mapped multiple times`)
        })
      },

      async saveMapping() {
        if (!this.importId) return

        this.isLoading = true
        this.resetErrors()

        try {
          const response = await axios.post(`/api/v1/admin/imports/${this.importId}/mapping`, {
            mappings: this.fieldMappings,
          })

          this.importJob = response.data.data
          this.updateCanProceed()

          const notificationStore = useNotificationStore()
          const { global } = window.i18n
          notificationStore.showNotification({
            type: 'success',
            message: global.t('imports.mapping_saved_successfully'),
          })

          return response.data

        } catch (error) {
          this.setError('mapping', error.response?.data?.message || 'Mapping save failed')
          handleError(error)
          throw error
        } finally {
          this.isLoading = false
        }
      },

      // Step 3 - Validation
      async validateData() {
        if (!this.importId) return

        this.isValidating = true
        this.resetErrors()

        try {
          const response = await axios.post(`/api/v1/admin/imports/${this.importId}/validate`)
          
          this.validationResults = response.data.data
          this.validationErrors = response.data.data.errors || []
          this.validationWarnings = response.data.data.warnings || []

          this.updateCanProceed()

          // Start polling for validation progress if still processing
          if (response.data.data.status === 'validating') {
            this.startProgressPolling()
          }

          return response.data

        } catch (error) {
          this.setError('validation', error.response?.data?.message || 'Validation failed')
          handleError(error)
          throw error
        } finally {
          this.isValidating = false
        }
      },

      resolveConflict(recordId, resolution) {
        this.conflictResolutions[recordId] = resolution
      },

      // Step 4 - Commit
      async commitImport() {
        if (!this.importId) return

        this.isCommitting = true
        this.commitStatus = 'processing'
        this.commitProgress = 0
        this.resetErrors()

        try {
          const response = await axios.post(`/api/v1/admin/imports/${this.importId}/commit`, {
            conflict_resolutions: this.conflictResolutions,
          })

          this.commitResults = response.data.data
          
          // Start polling for commit progress
          this.startProgressPolling()

          return response.data

        } catch (error) {
          this.commitStatus = 'failed'
          this.setError('commit', error.response?.data?.message || 'Commit failed')
          handleError(error)
          throw error
        }
      },

      // Progress polling
      async startProgressPolling() {
        if (this.pollingInterval) {
          clearInterval(this.pollingInterval)
        }

        this.pollingInterval = setInterval(async () => {
          try {
            await this.fetchProgress()
          } catch (error) {
            console.error('Progress polling error:', error)
            this.stopProgressPolling()
          }
        }, 2000) // Poll every 2 seconds
      },

      stopProgressPolling() {
        if (this.pollingInterval) {
          clearInterval(this.pollingInterval)
          this.pollingInterval = null
        }
      },

      async fetchProgress() {
        if (!this.importId) return

        try {
          const response = await axios.get(`/api/v1/admin/imports/${this.importId}/progress`)
          const progress = response.data.data

          // Update progress based on current step
          if (this.currentStep === 3 && this.isValidating) {
            // Validation progress
            if (progress.status === 'completed') {
              this.validationResults = progress.results
              this.isValidating = false
              this.stopProgressPolling()
              this.updateCanProceed()
            }
          } else if (this.currentStep === 4 && this.isCommitting) {
            // Commit progress
            this.commitProgress = progress.progress || 0
            
            if (progress.status === 'completed') {
              this.commitStatus = 'completed'
              this.commitResults = progress.results
              this.isCommitting = false
              this.stopProgressPolling()
              this.updateCanProceed()

              const notificationStore = useNotificationStore()
              const { global } = window.i18n
              notificationStore.showNotification({
                type: 'success',
                message: global.t('imports.import_completed_successfully'),
              })
            } else if (progress.status === 'failed') {
              this.commitStatus = 'failed'
              this.isCommitting = false
              this.stopProgressPolling()
              this.setError('commit', progress.error || 'Import failed')
            }
          }

          return progress

        } catch (error) {
          console.error('Progress fetch error:', error)
        }
      },

      // Utility methods
      resetState() {
        this.currentStep = 1
        this.canProceed = false
        this.detectedFields = []
        this.mappingSuggestions = {}
        this.fieldMappings = {}
        this.mappingErrors = []
        this.validationResults = null
        this.validationErrors = []
        this.validationWarnings = []
        this.conflictResolutions = {}
        this.commitProgress = 0
        this.commitStatus = 'pending'
        this.commitResults = null
        this.resetErrors()
        this.stopProgressPolling()
      },

      resetErrors() {
        this.errors = {}
        this.hasErrors = false
      },

      setError(field, message) {
        this.errors[field] = message
        this.hasErrors = true
      },

      getError(field) {
        return this.errors[field] || null
      },

      // Cleanup
      async cancelImport() {
        if (!this.importId) return

        try {
          await axios.delete(`/api/v1/admin/imports/${this.importId}`)

          const notificationStore = useNotificationStore()
          const { global } = window.i18n
          notificationStore.showNotification({
            type: 'success',
            message: global.t('imports.import_cancelled_successfully'),
          })

        } catch (error) {
          handleError(error)
        } finally {
          this.resetState()
          this.removeFile()
        }
      },

      // Logs
      async fetchLogs() {
        if (!this.importId) return []

        try {
          const response = await axios.get(`/api/v1/admin/imports/${this.importId}/logs`)
          return response.data.data
        } catch (error) {
          handleError(error)
          return []
        }
      },
    }
})