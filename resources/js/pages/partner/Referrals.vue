<template>
  <div class="referrals-page">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Referral Management</h1>
      <p class="text-gray-600">Generate and manage your referral links</p>
    </div>

    <!-- Referral Link Generator -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Referral Link</h3>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Custom Code (optional)</label>
        <div class="flex gap-3">
          <input
            v-model="customCode"
            type="text"
            placeholder="e.g., MYCODE2025"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            :disabled="hasActiveLink"
          />
          <button
            v-if="!hasActiveLink"
            @click="generateLink"
            :disabled="generating"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            {{ generating ? 'Generating...' : 'Generate' }}
          </button>
        </div>
        <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate a unique code</p>
      </div>

      <div v-if="activeLink" class="space-y-4">
        <!-- Primary Referral Link -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Primary Referral Link</label>
          <div class="flex gap-2">
            <input
              :value="activeLink.url"
              readonly
              class="flex-1 px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg"
            />
            <button
              @click="copyToClipboard(activeLink.url)"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
            >
              <svg v-if="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
              </svg>
              <svg v-else class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- QR Code -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">QR Code</label>
          <div class="flex items-start gap-4">
            <div class="bg-white p-4 border border-gray-200 rounded-lg">
              <canvas ref="qrCanvas" class="w-48 h-48"></canvas>
            </div>
            <div class="flex-1">
              <p class="text-sm text-gray-600 mb-3">Share this QR code with potential clients. They can scan it to sign up with your referral link.</p>
              <button
                @click="downloadQRCode"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                Download QR Code
              </button>
            </div>
          </div>
        </div>

        <!-- Share Buttons -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Quick Share</label>
          <div class="flex gap-2">
            <button
              @click="shareViaEmail"
              class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
              Email
            </button>
            <button
              @click="shareViaWhatsApp"
              class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition flex items-center gap-2"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path>
              </svg>
              WhatsApp
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Click Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600">Total Clicks</h3>
          <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
          </svg>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ statistics.totalClicks }}</div>
        <p class="text-xs text-gray-500 mt-1">People who clicked your link</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600">Signups</h3>
          <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
          </svg>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ statistics.signups }}</div>
        <p class="text-xs text-gray-500 mt-1">Completed registrations</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600">Conversion Rate</h3>
          <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ conversionRate }}%</div>
        <p class="text-xs text-gray-500 mt-1">Clicks to signups ratio</p>
      </div>
    </div>

    <!-- Conversion Funnel -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Conversion Funnel</h3>
      <div class="space-y-4">
        <!-- Clicks -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Clicks</span>
            <span class="text-sm font-semibold text-gray-900">{{ statistics.totalClicks }}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-blue-600 h-3 rounded-full" style="width: 100%"></div>
          </div>
        </div>

        <!-- Signups -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Signups</span>
            <span class="text-sm font-semibold text-gray-900">{{ statistics.signups }}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-green-600 h-3 rounded-full" :style="`width: ${signupPercentage}%`"></div>
          </div>
        </div>

        <!-- Active Subscriptions -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Active Subscriptions</span>
            <span class="text-sm font-semibold text-gray-900">{{ statistics.activeSubscriptions }}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-purple-600 h-3 rounded-full" :style="`width: ${subscriptionPercentage}%`"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import QRCode from 'qrcode'

export default {
  name: 'PartnerReferrals',

  data() {
    return {
      customCode: '',
      activeLink: null,
      hasActiveLink: false,
      generating: false,
      copied: false,
      statistics: {
        totalClicks: 0,
        signups: 0,
        activeSubscriptions: 0
      }
    }
  },

  computed: {
    conversionRate() {
      if (this.statistics.totalClicks === 0) return 0
      return ((this.statistics.signups / this.statistics.totalClicks) * 100).toFixed(1)
    },

    signupPercentage() {
      if (this.statistics.totalClicks === 0) return 0
      return (this.statistics.signups / this.statistics.totalClicks) * 100
    },

    subscriptionPercentage() {
      if (this.statistics.totalClicks === 0) return 0
      return (this.statistics.activeSubscriptions / this.statistics.totalClicks) * 100
    }
  },

  mounted() {
    this.fetchReferralData()
  },

  methods: {
    async fetchReferralData() {
      try {
        const response = await axios.get('/api/partner/referrals')
        this.activeLink = response.data.activeLink
        this.statistics = response.data.statistics
        this.hasActiveLink = !!this.activeLink

        if (this.activeLink) {
          this.$nextTick(() => {
            this.generateQRCode()
          })
        }
      } catch (error) {
        console.error('Failed to fetch referral data:', error)
      }
    },

    async generateLink() {
      this.generating = true
      try {
        const response = await axios.post('/api/partner/referrals', {
          custom_code: this.customCode || null
        })
        this.activeLink = response.data.link
        this.hasActiveLink = true
        this.customCode = ''

        this.$nextTick(() => {
          this.generateQRCode()
        })
      } catch (error) {
        console.error('Failed to generate referral link:', error)
        alert('Failed to generate link. Please try again.')
      } finally {
        this.generating = false
      }
    },

    async generateQRCode() {
      if (!this.$refs.qrCanvas || !this.activeLink) return

      try {
        await QRCode.toCanvas(this.$refs.qrCanvas, this.activeLink.url, {
          width: 192,
          margin: 2,
          color: {
            dark: '#1F2937',
            light: '#FFFFFF'
          }
        })
      } catch (error) {
        console.error('Failed to generate QR code:', error)
      }
    },

    async copyToClipboard(text) {
      try {
        await navigator.clipboard.writeText(text)
        this.copied = true
        setTimeout(() => {
          this.copied = false
        }, 2000)
      } catch (error) {
        console.error('Failed to copy to clipboard:', error)
      }
    },

    downloadQRCode() {
      if (!this.$refs.qrCanvas) return

      const link = document.createElement('a')
      link.download = `referral-qr-${this.activeLink.code}.png`
      link.href = this.$refs.qrCanvas.toDataURL()
      link.click()
    },

    shareViaEmail() {
      const subject = encodeURIComponent('Join Facturino')
      const body = encodeURIComponent(`Check out Facturino - the best invoicing solution for Macedonian businesses!\n\nSign up using my referral link: ${this.activeLink.url}`)
      window.location.href = `mailto:?subject=${subject}&body=${body}`
    },

    shareViaWhatsApp() {
      const text = encodeURIComponent(`Check out Facturino! Sign up here: ${this.activeLink.url}`)
      window.open(`https://wa.me/?text=${text}`, '_blank')
    }
  }
}
</script>

<style scoped>
.referrals-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
</style>
<!-- CLAUDE-CHECKPOINT -->
