<template>
  <div class="kyc-submission">
    <div class="page-header">
      <h1 class="page-title">KYC Verification</h1>
      <p class="text-gray-600 mt-2">
        Submit your documents to enable affiliate payouts. All information is encrypted and secure.
      </p>
    </div>

    <!-- KYC Status Card -->
    <div v-if="kycStatus" class="status-card mb-6 p-6 rounded-lg" :class="statusCardClass">
      <div class="flex items-center">
        <div class="status-icon mr-4">
          <svg v-if="kycStatus.kyc_status === 'verified'" class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else-if="kycStatus.kyc_status === 'rejected'" class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h3 class="font-semibold text-lg">{{ statusTitle }}</h3>
          <p class="text-sm mt-1">{{ statusMessage }}</p>
        </div>
      </div>
    </div>

    <!-- Upload Form (only show if not verified) -->
    <div v-if="!kycStatus || kycStatus.kyc_status !== 'verified'" class="upload-section bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-semibold mb-4">Upload Documents</h2>
      <p class="text-gray-600 mb-6">
        Please upload the following documents to complete your KYC verification:
      </p>

      <form @submit.prevent="submitDocuments">
        <!-- ID Card / Passport -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            ID Card or Passport <span class="text-red-500">*</span>
          </label>
          <input
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            @change="handleFileUpload($event, 'id_card')"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            :required="!hasDocument('id_card')"
          />
          <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG (max 5MB)</p>
        </div>

        <!-- Proof of Address -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Proof of Address <span class="text-red-500">*</span>
          </label>
          <input
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            @change="handleFileUpload($event, 'proof_of_address')"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            :required="!hasDocument('proof_of_address')"
          />
          <p class="text-xs text-gray-500 mt-1">Utility bill, bank statement, or rental agreement (last 3 months)</p>
        </div>

        <!-- Bank Statement (optional) -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Bank Statement <span class="text-gray-400">(Optional)</span>
          </label>
          <input
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            @change="handleFileUpload($event, 'bank_statement')"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
          />
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-between">
          <button
            type="submit"
            :disabled="uploading || !canSubmit"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="uploading">Uploading...</span>
            <span v-else>Submit Documents</span>
          </button>
          <p class="text-sm text-gray-500">Review time: 24-48 hours</p>
        </div>
      </form>

      <!-- Error Message -->
      <div v-if="errorMessage" class="mt-4 p-4 bg-red-50 border border-red-200 rounded text-red-700">
        {{ errorMessage }}
      </div>

      <!-- Success Message -->
      <div v-if="successMessage" class="mt-4 p-4 bg-green-50 border border-green-200 rounded text-green-700">
        {{ successMessage }}
      </div>
    </div>

    <!-- Uploaded Documents List -->
    <div v-if="kycStatus && kycStatus.documents && kycStatus.documents.length > 0" class="documents-list mt-6 bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-semibold mb-4">Submitted Documents</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Filename</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uploaded</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="doc in kycStatus.documents" :key="doc.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ formatDocumentType(doc.document_type) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ doc.original_filename }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(doc.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ doc.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(doc.uploaded_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button
                  v-if="doc.status !== 'approved'"
                  @click="deleteDocument(doc.id)"
                  class="text-red-600 hover:text-red-900"
                >
                  Delete
                </button>
                <span v-else class="text-gray-400">Approved</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Rejection Reasons -->
      <div v-for="doc in rejectedDocuments" :key="'rejection-' + doc.id" class="mt-4 p-4 bg-red-50 border border-red-200 rounded">
        <p class="font-semibold text-red-700">{{ formatDocumentType(doc.document_type) }} - Rejection Reason:</p>
        <p class="text-red-600 mt-1">{{ doc.rejection_reason }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const kycStatus = ref(null)
const uploading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const uploadedFiles = ref([])

const statusCardClass = computed(() => {
  if (!kycStatus.value) return 'bg-gray-100'

  switch (kycStatus.value.kyc_status) {
    case 'verified':
      return 'bg-green-50 border border-green-200'
    case 'rejected':
      return 'bg-red-50 border border-red-200'
    default:
      return 'bg-yellow-50 border border-yellow-200'
  }
})

const statusTitle = computed(() => {
  if (!kycStatus.value) return 'Loading...'

  switch (kycStatus.value.kyc_status) {
    case 'verified':
      return 'KYC Verified'
    case 'rejected':
      return 'KYC Rejected'
    default:
      return 'KYC Pending Review'
  }
})

const statusMessage = computed(() => {
  if (!kycStatus.value) return ''

  switch (kycStatus.value.kyc_status) {
    case 'verified':
      return 'Your account is fully verified and eligible for payouts.'
    case 'rejected':
      return 'Please review the rejection reasons below and re-submit corrected documents.'
    default:
      return 'Your documents are being reviewed. You will receive an email once the review is complete.'
  }
})

const canSubmit = computed(() => {
  return uploadedFiles.value.length >= 2 // Minimum 2 required documents
})

const rejectedDocuments = computed(() => {
  if (!kycStatus.value || !kycStatus.value.documents) return []
  return kycStatus.value.documents.filter(doc => doc.status === 'rejected' && doc.rejection_reason)
})

const handleFileUpload = (event, documentType) => {
  const file = event.target.files[0]
  if (!file) return

  // Validate file size (5MB)
  if (file.size > 5 * 1024 * 1024) {
    errorMessage.value = 'File size must be less than 5MB'
    event.target.value = ''
    return
  }

  // Add to uploaded files
  uploadedFiles.value = uploadedFiles.value.filter(f => f.type !== documentType)
  uploadedFiles.value.push({ type: documentType, file })
  errorMessage.value = ''
}

const hasDocument = (type) => {
  if (!kycStatus.value || !kycStatus.value.documents) return false
  return kycStatus.value.documents.some(doc => doc.document_type === type && doc.status === 'approved')
}

const submitDocuments = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  uploading.value = true

  try {
    const formData = new FormData()
    uploadedFiles.value.forEach((item, index) => {
      formData.append(`documents[${index}][type]`, item.type)
      formData.append(`documents[${index}][file]`, item.file)
    })

    const response = await axios.post('/api/v1/partner/kyc/submit', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    successMessage.value = response.data.message
    uploadedFiles.value = []

    // Reload KYC status
    await fetchKycStatus()

  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to upload documents'
  } finally {
    uploading.value = false
  }
}

const deleteDocument = async (documentId) => {
  if (!confirm('Are you sure you want to delete this document?')) return

  try {
    await axios.delete(`/api/v1/partner/kyc/documents/${documentId}`)
    successMessage.value = 'Document deleted successfully'
    await fetchKycStatus()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to delete document'
  }
}

const fetchKycStatus = async () => {
  try {
    const response = await axios.get('/api/v1/partner/kyc/status')
    kycStatus.value = response.data
  } catch (error) {
    errorMessage.value = 'Failed to load KYC status'
  }
}

const formatDocumentType = (type) => {
  const types = {
    'id_card': 'ID Card',
    'passport': 'Passport',
    'proof_of_address': 'Proof of Address',
    'bank_statement': 'Bank Statement',
    'tax_certificate': 'Tax Certificate',
    'other': 'Other'
  }
  return types[type] || type
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'approved':
      return 'bg-green-100 text-green-800'
    case 'rejected':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-yellow-100 text-yellow-800'
  }
}

onMounted(() => {
  fetchKycStatus()
})
</script>

<style scoped>
.page-header {
  margin-bottom: 2rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 600;
  color: #1f2937;
}
</style>

<!-- CLAUDE-CHECKPOINT -->
