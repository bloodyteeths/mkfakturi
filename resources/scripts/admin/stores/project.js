import { defineStore } from 'pinia'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

/**
 * Project stub for initializing new project forms
 */
const projectStub = () => ({
  id: null,
  name: '',
  code: '',
  description: '',
  customer_id: null,
  status: 'open',
  budget_amount: null,
  currency_id: null,
  start_date: '',
  end_date: '',
  notes: '',
})

/**
 * Project Store
 *
 * Manages project state and API interactions.
 * Part of Phase 1.1 - Project Dimension feature for accountants.
 */
export const useProjectStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'project',

    state: () => ({
      projects: [],
      totalProjects: 0,
      openCount: 0,
      closedCount: 0,
      currentProject: { ...projectStub() },
      selectedProjects: [],
      projectList: [], // Simplified list for dropdowns
      currentProjectDocuments: {
        invoices: [],
        expenses: [],
        payments: [],
      },
      currentProjectSummary: null,
      isFetching: false, // Loading state for consistency with other stores
    }),

    getters: {
      /**
       * Check if we're editing an existing project
       */
      isEdit: (state) => (state.currentProject.id ? true : false),

      /**
       * Get selected project IDs
       */
      selectedIds: (state) => state.selectedProjects.map((p) => p.id),

      /**
       * Get project by ID from loaded projects
       */
      getProjectById: (state) => (id) =>
        state.projects.find((p) => p.id === id),
    },

    actions: {
      /**
       * Reset current project to empty state
       */
      resetCurrentProject() {
        this.currentProject = { ...projectStub() }
        this.currentProjectDocuments = {
          invoices: [],
          expenses: [],
          payments: [],
        }
        this.currentProjectSummary = null
      },

      /**
       * Select all projects
       */
      selectAllProjects() {
        this.selectedProjects = this.projects
      },

      /**
       * Select a single project
       */
      selectProject(project) {
        const index = this.selectedProjects.findIndex((p) => p.id === project.id)
        if (index === -1) {
          this.selectedProjects.push(project)
        }
      },

      /**
       * Deselect a project
       */
      deselectProject(project) {
        const index = this.selectedProjects.findIndex((p) => p.id === project.id)
        if (index !== -1) {
          this.selectedProjects.splice(index, 1)
        }
      },

      /**
       * Clear all selections
       */
      clearSelectedProjects() {
        this.selectedProjects = []
      },

      /**
       * Fetch all projects with filters
       */
      async fetchProjects(params = {}) {
        this.isFetching = true
        try {
          const response = await axios.get('/projects', { params })

          this.projects = response.data.data
          this.totalProjects = response.data.meta?.project_total_count || 0
          this.openCount = response.data.meta?.open_count || 0
          this.closedCount = response.data.meta?.closed_count || 0

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isFetching = false
        }
      },

      /**
       * Fetch simplified project list for dropdowns
       */
      async fetchProjectList(params = {}) {
        try {
          const response = await axios.get('/projects/list', { params })

          this.projectList = response.data.data || []

          return response
        } catch (err) {
          handleError(err)
          throw err
        }
      },

      /**
       * Fetch a single project by ID
       */
      async fetchProject(id) {
        try {
          const response = await axios.get(`/projects/${id}`)

          this.currentProject = response.data.data

          return response
        } catch (err) {
          handleError(err)
          throw err
        }
      },

      /**
       * Create a new project
       */
      async addProject(data) {
        const notificationStore = useNotificationStore()

        try {
          const response = await axios.post('/projects', data)

          // Add to local state
          this.projects.unshift(response.data.data)
          this.totalProjects++
          if (response.data.data.status === 'open') {
            this.openCount++
          }

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('projects.created_message'),
          })

          return response
        } catch (err) {
          handleError(err)
          throw err
        }
      },

      /**
       * Update an existing project
       */
      async updateProject(data) {
        const notificationStore = useNotificationStore()

        try {
          const response = await axios.put(`/projects/${data.id}`, data)

          // Update in local state
          const index = this.projects.findIndex((p) => p.id === data.id)
          if (index !== -1) {
            this.projects[index] = response.data.data
          }

          this.currentProject = response.data.data

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('projects.updated_message'),
          })

          return response
        } catch (err) {
          handleError(err)
          throw err
        }
      },

      /**
       * Delete selected projects
       */
      async deleteProjects(ids) {
        const notificationStore = useNotificationStore()

        try {
          const response = await axios.post('/projects/delete', { ids })

          // Remove from local state
          ids.forEach((id) => {
            const index = this.projects.findIndex((p) => p.id === id)
            if (index !== -1) {
              const project = this.projects[index]
              if (project.status === 'open') {
                this.openCount--
              } else if (project.status === 'closed') {
                this.closedCount--
              }
              this.projects.splice(index, 1)
              this.totalProjects--
            }
          })

          this.clearSelectedProjects()

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('projects.deleted_message'),
          })

          return response
        } catch (err) {
          handleError(err)
          throw err
        }
      },

      /**
       * Fetch project summary (financial totals)
       *
       * @param {number} projectId
       * @param {object} params - Optional params { from_date, to_date }
       */
      async fetchProjectSummary(projectId, params = {}) {
        try {
          const response = await axios.get(`/projects/${projectId}/summary`, { params })

          this.currentProjectSummary = response.data.data

          return response
        } catch (err) {
          handleError(err)
          throw err
        }
      },

      /**
       * Fetch project documents (invoices, expenses, payments)
       */
      async fetchProjectDocuments(projectId, type = 'all') {
        try {
          const response = await axios.get(`/projects/${projectId}/documents`, {
            params: { type },
          })

          if (type === 'all') {
            this.currentProjectDocuments = response.data.data
          } else {
            this.currentProjectDocuments[type] = response.data.data[type] || []
          }

          return response
        } catch (err) {
          handleError(err)
          throw err
        }
      },

      /**
       * Set current project from loaded data (for editing)
       */
      setCurrentProject(project) {
        this.currentProject = {
          ...projectStub(),
          ...project,
        }
      },
    },
  })()
}

// CLAUDE-CHECKPOINT
