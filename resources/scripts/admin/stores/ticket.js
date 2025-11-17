import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'

export const useTicketStore = defineStore({
  id: 'ticket',

  state: () => ({
    tickets: [],
    currentTicket: null,
    selectedTickets: [],
    selectAllField: false,
    ticketTotalCount: 0,
    isFetchingTickets: false,
    isFetchingTicket: false,
    categories: [],
    labels: [],

    // Filter states
    filters: {
      status: '',
      priority: '',
      category_id: null,
      search: '',
    },
  }),

  getters: {
    getTicket: (state) => (id) => {
      const ticketId = parseInt(id)
      return state.tickets.find((ticket) => ticket.id === ticketId)
    },

    openTicketsCount: (state) => {
      return state.tickets.filter((t) => t.status === 'open').length
    },

    urgentTicketsCount: (state) => {
      return state.tickets.filter((t) => t.priority === 'urgent').length
    },
  },

  actions: {
    /**
     * Fetch all tickets with optional filters
     */
    fetchTickets(params) {
      return new Promise((resolve, reject) => {
        this.isFetchingTickets = true

        axios
          .get('/support/tickets', { params })
          .then((response) => {
            this.tickets = response.data.data
            this.ticketTotalCount = response.data.meta.ticket_total_count
            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
          .finally(() => {
            this.isFetchingTickets = false
          })
      })
    },

    /**
     * Fetch single ticket with messages
     */
    fetchTicket(id) {
      return new Promise((resolve, reject) => {
        this.isFetchingTicket = true

        axios
          .get(`/support/tickets/${id}`)
          .then((response) => {
            this.currentTicket = response.data.data
            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
          .finally(() => {
            this.isFetchingTicket = false
          })
      })
    },

    /**
     * Create new ticket
     */
    createTicket(data) {
      return new Promise((resolve, reject) => {
        axios
          .post('/support/tickets', data)
          .then((response) => {
            this.tickets.unshift(response.data.data)
            this.ticketTotalCount++
            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
      })
    },

    /**
     * Update ticket (status, priority, etc.)
     */
    updateTicket(id, data) {
      return new Promise((resolve, reject) => {
        axios
          .put(`/support/tickets/${id}`, data)
          .then((response) => {
            const index = this.tickets.findIndex((t) => t.id === id)
            if (index !== -1) {
              this.tickets[index] = response.data.data
            }

            if (this.currentTicket && this.currentTicket.id === id) {
              this.currentTicket = response.data.data
            }

            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
      })
    },

    /**
     * Delete ticket
     */
    deleteTicket(id) {
      return new Promise((resolve, reject) => {
        axios
          .delete(`/support/tickets/${id}`)
          .then((response) => {
            const index = this.tickets.findIndex((t) => t.id === id)
            if (index !== -1) {
              this.tickets.splice(index, 1)
              this.ticketTotalCount--
            }

            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
      })
    },

    /**
     * Delete multiple tickets
     */
    deleteMultipleTickets(ids) {
      return new Promise((resolve, reject) => {
        axios
          .post('/support/tickets/delete', { ids })
          .then((response) => {
            ids.forEach((id) => {
              const index = this.tickets.findIndex((t) => t.id === id)
              if (index !== -1) {
                this.tickets.splice(index, 1)
                this.ticketTotalCount--
              }
            })

            this.selectedTickets = []
            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
      })
    },

    /**
     * Fetch ticket messages
     */
    fetchTicketMessages(ticketId) {
      return new Promise((resolve, reject) => {
        axios
          .get(`/support/tickets/${ticketId}/messages`)
          .then((response) => {
            if (this.currentTicket && this.currentTicket.id === ticketId) {
              this.currentTicket.messages = response.data.data
            }
            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
      })
    },

    /**
     * Reply to ticket
     */
    replyToTicket(ticketId, data) {
      return new Promise((resolve, reject) => {
        axios
          .post(`/support/tickets/${ticketId}/messages`, data)
          .then((response) => {
            if (this.currentTicket && this.currentTicket.id === ticketId) {
              if (!this.currentTicket.messages) {
                this.currentTicket.messages = []
              }
              this.currentTicket.messages.push(response.data.data)
            }

            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
      })
    },

    /**
     * Update message
     */
    updateMessage(ticketId, messageId, data) {
      return new Promise((resolve, reject) => {
        axios
          .put(`/support/tickets/${ticketId}/messages/${messageId}`, data)
          .then((response) => {
            if (this.currentTicket && this.currentTicket.id === ticketId) {
              const index = this.currentTicket.messages.findIndex((m) => m.id === messageId)
              if (index !== -1) {
                this.currentTicket.messages[index] = response.data.data
              }
            }

            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
      })
    },

    /**
     * Delete message
     */
    deleteMessage(ticketId, messageId) {
      return new Promise((resolve, reject) => {
        axios
          .delete(`/support/tickets/${ticketId}/messages/${messageId}`)
          .then((response) => {
            if (this.currentTicket && this.currentTicket.id === ticketId) {
              const index = this.currentTicket.messages.findIndex((m) => m.id === messageId)
              if (index !== -1) {
                this.currentTicket.messages.splice(index, 1)
              }
            }

            resolve(response)
          })
          .catch((err) => {
            handleError(err)
            reject(err)
          })
      })
    },

    /**
     * Select all tickets
     */
    selectAllTickets() {
      this.selectedTickets = this.tickets.map((ticket) => ticket.id)
      this.selectAllField = true
    },

    /**
     * Deselect all tickets
     */
    deselectAllTickets() {
      this.selectedTickets = []
      this.selectAllField = false
    },

    /**
     * Select ticket
     */
    selectTicket(data) {
      this.selectedTickets = data
      if (this.selectedTickets.length === this.tickets.length) {
        this.selectAllField = true
      } else {
        this.selectAllField = false
      }
    },

    /**
     * Reset current ticket
     */
    resetCurrentTicket() {
      this.currentTicket = null
    },

    /**
     * Set filters
     */
    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
    },

    /**
     * Clear filters
     */
    clearFilters() {
      this.filters = {
        status: '',
        priority: '',
        category_id: null,
        search: '',
      }
    },
  },
})
// CLAUDE-CHECKPOINT
