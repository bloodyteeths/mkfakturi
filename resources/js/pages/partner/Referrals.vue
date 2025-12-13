<template>
  <div class="referrals-page min-h-screen overflow-auto pb-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Препораки</h1>
      <p class="text-gray-600">Генерирајте и управувајте со линкови за препораки и покани за партнери</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
      <button @click="fetchReferralData" class="mt-2 text-red-600 underline">Обиди се повторно</button>
    </div>

    <template v-else>
      <!-- Referral Link Generator (for client signups) -->
      <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Вашиот линк за препораки</h3>

        <!-- Generate New Link Section (shown when no active link) -->
        <div v-if="!activeLink" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Сопствен код (опционално)</label>
          <div class="flex gap-3">
            <input
              v-model="customCode"
              type="text"
              placeholder="пр. MOJKOD2025"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
              @input="customCode = customCode.toUpperCase()"
            />
            <button
              @click="generateLink"
              :disabled="generating"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              {{ generating ? 'Се генерира...' : 'Генерирај линк' }}
            </button>
          </div>
          <p class="text-xs text-gray-500 mt-1">Оставете празно за автоматски генериран код</p>
        </div>

        <!-- Active Link Section (shown when link exists) -->
        <div v-if="activeLink" class="space-y-4">
          <!-- Primary Referral Link -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Активен линк за препораки</label>
            <div class="flex gap-2">
              <input
                :value="activeLink.url"
                readonly
                class="flex-1 px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg font-mono text-sm"
              />
              <button
                @click="copyToClipboard(activeLink.url)"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                :title="copied ? 'Копирано!' : 'Копирај'"
              >
                <svg v-if="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <svg v-else class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Код: <strong>{{ activeLink.code }}</strong></p>
          </div>

          <!-- QR Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">QR Код</label>
            <div class="flex items-start gap-4">
              <div class="bg-white p-4 border border-gray-200 rounded-lg inline-block">
                <canvas ref="qrCanvas" width="192" height="192" style="width: 192px; height: 192px; display: block;"></canvas>
              </div>
              <div class="flex-1">
                <p class="text-sm text-gray-600 mb-3">Споделете го овој QR код со потенцијални клиенти. Тие можат да го скенираат за да се регистрираат преку вашиот линк.</p>
                <button
                  @click="downloadQRCode"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  Преземи QR код
                </button>
              </div>
            </div>
          </div>

          <!-- Share Buttons -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Брзо споделување</label>
            <div class="flex gap-2 flex-wrap">
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
          <div class="text-sm font-medium text-gray-500 mb-2">Вкупно кликови</div>
          <div class="text-3xl font-bold text-gray-900">{{ statistics.totalClicks }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-sm font-medium text-gray-500 mb-2">Регистрации</div>
          <div class="text-3xl font-bold text-green-600">{{ statistics.signups }}</div>
          <div class="text-xs text-gray-500 mt-1">
            {{ signupPercentage.toFixed(1) }}% конверзија од кликови
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-sm font-medium text-gray-500 mb-2">Активни претплати</div>
          <div class="text-3xl font-bold text-blue-600">{{ statistics.activeSubscriptions }}</div>
          <div class="text-xs text-gray-500 mt-1">
            {{ subscriptionPercentage.toFixed(1) }}% конверзија од кликови
          </div>
        </div>
      </div>

      <!-- Partner-to-Partner Invites -->
      <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Покана за нов партнер</h3>
          <button
            @click="loadPartnerInvite"
            :disabled="inviteLoading"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            {{ inviteLoading ? 'Се генерира...' : 'Генерирај линк' }}
          </button>
        </div>
        <p class="text-sm text-gray-600 mb-4">Споделете партнерска покана (рефераль за партнери) со вашиот линк или QR код.</p>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Email на партнерот</label>
          <input
            v-model="inviteEmail"
            type="email"
            placeholder="partner@example.com"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <div v-if="inviteError" class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-red-700">
          {{ inviteError }}
        </div>

        <div v-if="partnerInvite" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Партнер линк</label>
            <div class="flex gap-2">
              <input
                :value="partnerInvite.link"
                readonly
                class="flex-1 px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg font-mono text-sm"
              />
              <button
                @click="copyPartnerLink"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                :title="inviteCopied ? 'Копирано!' : 'Копирај'"
              >
                <svg v-if="!inviteCopied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <svg v-else class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </button>
            </div>
          </div>

          <div class="flex flex-wrap gap-4">
            <div class="bg-white p-4 border border-gray-200 rounded-lg inline-block">
              <img :src="partnerInvite.qr_code_url" alt="Partner QR" class="w-48 h-48 object-contain" />
            </div>
            <div class="flex-1 min-w-[240px]">
              <p class="text-sm text-gray-600 mb-3">Скенирајте или споделете го QR кодот за партнер покана.</p>
            <div class="flex gap-2">
              <button
                @click="downloadPartnerQR"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                Преземи QR
              </button>
              <button
                @click="sendPartnerEmailInvite"
                :disabled="sendingInviteEmail"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ sendingInviteEmail ? 'Се праќа...' : 'Испрати email покана' }}
              </button>
            </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import axios from 'axios'
import QRCode from 'qrcode'

export default {
  data() {
    return {
      loading: true,
      generating: false,
      error: null,
      activeLink: null,
      customCode: '',
      statistics: {
        totalClicks: 0,
        signups: 0,
        activeSubscriptions: 0
      },
      copied: false,
      partnerInvite: null,
      inviteLoading: false,
      inviteError: null,
      inviteCopied: false,
      sendingInviteEmail: false,
      inviteEmail: ''
    }
  },

  computed: {
    signupPercentage() {
      if (this.statistics.totalClicks === 0) return 0
      return Math.min((this.statistics.signups / this.statistics.totalClicks) * 100, 100)
    },

    subscriptionPercentage() {
      if (this.statistics.totalClicks === 0) return 0
      return Math.min((this.statistics.activeSubscriptions / this.statistics.totalClicks) * 100, 100)
    }
  },

  mounted() {
    this.fetchReferralData()
  },

  methods: {
    async fetchReferralData() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/partner/referrals')
        this.activeLink = response.data.activeLink || null
        this.statistics = response.data.statistics || {
          totalClicks: 0,
          signups: 0,
          activeSubscriptions: 0
        }

        if (this.activeLink && this.activeLink.url) {
          this.$nextTick(() => {
            this.generateQRCode()
          })
        }
      } catch (err) {
        console.error('Failed to fetch referral data:', err)
        this.error = 'Не можеше да се вчитаат податоците. Обидете се повторно.'
      } finally {
        this.loading = false
      }
    },

    async generateLink() {
      this.generating = true
      this.error = null
      try {
        const response = await axios.post('/partner/referrals', {
          custom_code: this.customCode || null
        })

        this.activeLink = response.data.link
        this.customCode = ''

        if (this.activeLink && this.activeLink.url) {
          this.$nextTick(() => {
            this.generateQRCode()
          })
        }
      } catch (err) {
        console.error('Failed to generate referral link:', err)
        if (err.response?.status === 422) {
          this.error = err.response.data.error || 'Кодот веќе постои. Обидете се со друг код.'
        } else {
          this.error = 'Не можеше да се генерира линк. Обидете се повторно.'
        }
      } finally {
        this.generating = false
      }
    },

    async generateQRCode() {
      if (!this.$refs.qrCanvas || !this.activeLink || !this.activeLink.url) return

      try {
        await QRCode.toCanvas(this.$refs.qrCanvas, this.activeLink.url, {
          width: 192,
          margin: 2,
          color: {
            dark: '#1F2937',
            light: '#FFFFFF'
          }
        })
      } catch (err) {
        console.error('Failed to generate QR code:', err)
      }
    },

    async copyToClipboard(text) {
      try {
        await navigator.clipboard.writeText(text)
        this.copied = true
        setTimeout(() => {
          this.copied = false
        }, 2000)
      } catch (err) {
        console.error('Failed to copy to clipboard:', err)
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
      const subject = encodeURIComponent('Приклучи се на Facturino')
      const body = encodeURIComponent(`Погледни ја Facturino - најдоброто решение за фактурирање за македонски бизниси!\n\nРегистрирај се преку мојот линк: ${this.activeLink.url}`)
      window.location.href = `mailto:?subject=${subject}&body=${body}`
    },

    shareViaWhatsApp() {
      const text = encodeURIComponent(`Погледни ја Facturino! Регистрирај се тука: ${this.activeLink.url}`)
      window.open(`https://wa.me/?text=${text}`, '_blank')
    },

    // Partner-to-partner invitation helpers
    async loadPartnerInvite() {
      if (!this.inviteEmail) {
        this.inviteError = 'Внесете email на партнерот.'
        return
      }
      this.inviteLoading = true
      this.inviteError = null
      try {
        const response = await axios.post('/invitations/partner-to-partner', {
          invitee_email: this.inviteEmail,
        })
        this.partnerInvite = response.data
      } catch (err) {
        console.error('Failed to load partner invite link:', err)
        this.inviteError = err?.response?.data?.message || 'Не можеше да се генерира партнерски линк.'
      } finally {
        this.inviteLoading = false
      }
    },

    async copyPartnerLink() {
      if (!this.partnerInvite?.link) return
      try {
        await navigator.clipboard.writeText(this.partnerInvite.link)
        this.inviteCopied = true
        setTimeout(() => {
          this.inviteCopied = false
        }, 2000)
      } catch (err) {
        console.error('Failed to copy partner link:', err)
      }
    },

    downloadPartnerQR() {
      if (!this.partnerInvite?.qr_code_url) return
      const link = document.createElement('a')
      link.href = this.partnerInvite.qr_code_url
      link.download = 'partner-referral-qr.png'
      link.click()
    },

    async sendPartnerEmailInvite() {
      if (!this.partnerInvite?.link || !this.inviteEmail) return
      this.sendingInviteEmail = true
      try {
        await axios.post('/invitations/send-partner-email', {
          email: this.inviteEmail,
          link: this.partnerInvite.link,
        })
        this.inviteEmail = ''
      } catch (err) {
        console.error('Failed to send partner invite email:', err)
        this.inviteError = 'Email поканата не успеа.'
      } finally {
        this.sendingInviteEmail = false
      }
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
