import { defineStore } from 'pinia'
import axios from 'axios'

export const useAiStore = defineStore('ai', {
  state: () => ({
    summary: null,
    riskScore: null,
    lastUpdated: null,
    loading: false,
    error: null,
  }),

  getters: {
    hasData: (state) => state.summary !== null && state.riskScore !== null,
    
    netProfit30d: (state) => state.summary?.netProfit || 0,
    
    cashRunwayDays: (state) => {
      if (!state.summary) return 90
      const monthlyBurn = Math.abs(state.summary.totalExpenses - state.summary.totalRevenue)
      if (monthlyBurn <= 0) return 90
      const runway = Math.floor((state.summary.netProfit * 30) / monthlyBurn)
      return Math.min(runway, 90)
    },
    
    riskLevel: (state) => {
      if (!state.riskScore) return 'unknown'
      if (state.riskScore < 0.3) return 'low'
      if (state.riskScore < 0.6) return 'moderate'
      return 'high'
    },
  },

  actions: {
    async fetchInsights(companyId = null) {
      this.loading = true
      this.error = null
      
      try {
        // Get company ID from header if not provided
        const company = companyId || axios.defaults.headers.common['company']
        
        if (!company) {
          throw new Error('No company selected')
        }
        
        // Fetch both endpoints in parallel
        const [summaryResponse, riskResponse] = await Promise.all([
          axios.get('/api/ai/summary', { params: { company_id: company } }),
          axios.get('/api/ai/risk', { params: { company_id: company } })
        ])
        
        this.summary = summaryResponse.data
        this.riskScore = riskResponse.data.risk_score
        this.lastUpdated = new Date().toISOString()
        
      } catch (error) {
        console.error('Failed to fetch AI insights:', error)
        this.error = error.response?.data?.message || 'Failed to fetch insights'
        
        // Set fallback data
        this.summary = {
          totalRevenue: 0,
          totalExpenses: 0,
          netProfit: 0,
          invoicesCount: 0,
          paymentsCount: 0,
          averageInvoiceValue: 0,
          currency: 'MKD',
          period: 'last_30_days',
          insights: ['Service temporarily unavailable'],
          fallback: true
        }
        this.riskScore = 0.5
        
      } finally {
        this.loading = false
      }
    },
    
    clearData() {
      this.summary = null
      this.riskScore = null
      this.lastUpdated = null
      this.error = null
    }
  }
})

