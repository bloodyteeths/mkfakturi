import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useDocumentHubStore = defineStore('documentHub', () => {
  const documents = ref([])
  const isLoading = ref(false)
  const error = ref(null)
  const pollingIntervals = ref({})
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0,
    from: 0,
    to: 0,
  })

  // Stats computed from documents
  const stats = computed(() => {
    const all = documents.value
    return {
      total: pagination.value.total,
      pending: all.filter((d) => d.processing_status === 'pending').length,
      processing: all.filter((d) =>
        ['classifying', 'extracting'].includes(d.processing_status)
      ).length,
      extracted: all.filter((d) => d.processing_status === 'extracted').length,
      confirmed: all.filter((d) => d.processing_status === 'confirmed').length,
      failed: all.filter((d) => d.processing_status === 'failed').length,
    }
  })

  async function fetchDocuments(params = {}) {
    isLoading.value = true
    error.value = null

    try {
      const queryParams = {
        page: params.page || pagination.value.currentPage,
        per_page: params.per_page || pagination.value.perPage,
        ...params,
      }

      const { data } = await window.axios.get('/client-documents', {
        params: queryParams,
      })

      documents.value = data.data || []
      pagination.value = {
        currentPage: data.current_page || 1,
        lastPage: data.last_page || 1,
        perPage: data.per_page || 15,
        total: data.total || 0,
        from: data.from || 0,
        to: data.to || 0,
      }

      // Start polling for documents in processing state
      documents.value.forEach((doc) => {
        if (['pending', 'classifying', 'extracting'].includes(doc.processing_status)) {
          startPolling(doc.id)
        }
      })

      return data
    } catch (err) {
      error.value = err?.response?.data?.message || 'Failed to load documents.'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function uploadDocument(file, category, notes) {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('category', category)
    if (notes) {
      formData.append('notes', notes)
    }

    const { data } = await window.axios.post('/client-documents/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    // Add the new document to the list and start polling
    if (data.data) {
      documents.value.unshift(data.data)
      startPolling(data.data.id)
    }

    return data
  }

  async function fetchDocument(id) {
    const { data } = await window.axios.get(`/client-documents/${id}`)
    return data.data
  }

  async function pollProcessingStatus(id) {
    try {
      const { data } = await window.axios.get(
        `/client-documents/${id}/processing-status`
      )

      const status = data.data
      // Update document in list
      const idx = documents.value.findIndex((d) => d.id === id)
      if (idx !== -1) {
        documents.value[idx] = {
          ...documents.value[idx],
          processing_status: status.processing_status,
          ai_classification: status.ai_classification,
          extraction_method: status.extraction_method,
          error_message: status.error_message,
          linked_bill_id: status.linked_bill_id,
          linked_expense_id: status.linked_expense_id,
          linked_invoice_id: status.linked_invoice_id,
        }
      }

      // Stop polling if processing is complete
      if (['extracted', 'confirmed', 'failed'].includes(status.processing_status)) {
        stopPolling(id)
      }

      return status
    } catch {
      stopPolling(id)
      return null
    }
  }

  function startPolling(id) {
    if (pollingIntervals.value[id]) return // Already polling

    pollingIntervals.value[id] = setInterval(() => {
      pollProcessingStatus(id)
    }, 3000) // Poll every 3 seconds
  }

  function stopPolling(id) {
    if (pollingIntervals.value[id]) {
      clearInterval(pollingIntervals.value[id])
      delete pollingIntervals.value[id]
    }
  }

  function stopAllPolling() {
    Object.keys(pollingIntervals.value).forEach((id) => {
      clearInterval(pollingIntervals.value[id])
    })
    pollingIntervals.value = {}
  }

  async function confirmDocument(id, editedData = null, entityType = null) {
    const payload = {}
    if (editedData) {
      payload.extracted_data = editedData
    }
    if (entityType) {
      payload.entity_type = entityType
    }
    const { data } = await window.axios.post(
      `/client-documents/${id}/confirm`,
      payload
    )

    // Update in list
    const idx = documents.value.findIndex((d) => d.id === id)
    if (idx !== -1 && data.data?.document) {
      documents.value[idx] = data.data.document
    }

    return data
  }

  async function reprocessDocument(id) {
    const { data } = await window.axios.post(`/client-documents/${id}/reprocess`)

    // Update in list and start polling
    const idx = documents.value.findIndex((d) => d.id === id)
    if (idx !== -1 && data.data) {
      documents.value[idx] = data.data
    }

    startPolling(id)
    return data
  }

  async function deleteDocument(id) {
    await window.axios.delete(`/client-documents/${id}`)
    documents.value = documents.value.filter((d) => d.id !== id)
    stopPolling(id)
  }

  return {
    documents,
    isLoading,
    error,
    pagination,
    stats,
    fetchDocuments,
    uploadDocument,
    fetchDocument,
    pollProcessingStatus,
    startPolling,
    stopPolling,
    stopAllPolling,
    confirmDocument,
    reprocessDocument,
    deleteDocument,
  }
})
