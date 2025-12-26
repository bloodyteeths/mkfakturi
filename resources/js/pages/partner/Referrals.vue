<template>
  <div class="referrals-page min-h-screen overflow-auto pb-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $t('partner.referrals') }}</h1>
      <p class="text-gray-600">{{ $t('partner.referrals_page.page_description') }}</p>
    </div>

    <!-- Loading Skeleton State -->
    <div v-if="loading" class="space-y-6" aria-busy="true" aria-live="polite">
      <!-- Skeleton for Referral Link Card -->
      <div class="bg-white rounded-lg shadow p-6 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-1/4 mb-4"></div>
        <div class="flex gap-3 mb-4">
          <div class="flex-1 h-10 bg-gray-200 rounded"></div>
          <div class="w-32 h-10 bg-gray-200 rounded"></div>
        </div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
      </div>

      <!-- Skeleton for Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="n in 3" :key="n" class="bg-white rounded-lg shadow p-6 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
          <div class="h-8 bg-gray-200 rounded w-1/3"></div>
        </div>
      </div>

      <!-- Skeleton for Partner Invite Card -->
      <div class="bg-white rounded-lg shadow p-6 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
        <div class="h-4 bg-gray-200 rounded w-2/3"></div>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6"
      role="alert"
      aria-live="assertive"
    >
      <p class="text-red-800">{{ error }}</p>
      <button
        @click="fetchReferralData"
        class="mt-2 text-red-600 underline hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded"
        :aria-label="$t('general.retry')"
      >
        {{ $t('partner.referrals_page.retry') }}
      </button>
    </div>

    <template v-else>
      <!-- Referral Link Generator (for client signups) -->
      <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('partner.referrals_page.your_referral_link') }}</h3>

        <!-- Generate New Link Section (shown when no active link) -->
        <div v-if="!activeLink" class="mb-6">
          <label
            for="custom-code-input"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            {{ $t('partner.referrals_page.custom_code_label') }}
          </label>
          <div class="flex gap-3">
            <input
              id="custom-code-input"
              v-model="customCode"
              type="text"
              :placeholder="$t('partner.referrals_page.custom_code_placeholder')"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
              :aria-describedby="customCode ? undefined : 'custom-code-help'"
              @input="customCode = customCode.toUpperCase()"
            />
            <button
              @click="generateLink"
              :disabled="generating"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              :aria-label="$t('partner.referrals_page.generate_link')"
            >
              <span v-if="generating" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('partner.referrals_page.generating') }}
              </span>
              <span v-else>{{ $t('partner.referrals_page.generate_link') }}</span>
            </button>
          </div>
          <p id="custom-code-help" class="text-xs text-gray-500 mt-1">{{ $t('partner.referrals_page.custom_code_help') }}</p>
        </div>

        <!-- Empty State for no active link -->
        <div
          v-if="!activeLink && !generating"
          class="text-center py-8 border-2 border-dashed border-gray-200 rounded-lg"
        >
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
          </svg>
          <p class="mt-2 text-sm text-gray-600">{{ $t('partner.referrals_page.no_active_link') }}</p>
          <p class="mt-1 text-xs text-gray-500">{{ $t('partner.referrals_page.generate_link_prompt') }}</p>
        </div>

        <!-- Active Link Section (shown when link exists) -->
        <div v-if="activeLink" class="space-y-4">
          <!-- Primary Referral Link -->
          <div>
            <label
              for="active-referral-link"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              {{ $t('partner.referrals_page.active_referral_link') }}
            </label>
            <div class="flex gap-2">
              <input
                id="active-referral-link"
                :value="activeLink?.url || ''"
                readonly
                class="flex-1 px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg font-mono text-sm"
                :aria-label="$t('partner.referrals_page.referral_link_input')"
              />
              <button
                @click="copyToClipboard(activeLink?.url)"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                :title="copied ? $t('partner.referrals_page.copied') : $t('partner.referrals_page.copy')"
                :aria-label="copied ? $t('partner.referrals_page.copied') : $t('partner.referrals_page.copy_link')"
              >
                <svg v-if="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <svg v-else class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              {{ $t('partner.referrals_page.code_label') }}: <strong>{{ activeLink?.code || '' }}</strong>
            </p>
          </div>

          <!-- QR Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('partner.referrals_page.qr_code') }}</label>
            <div class="flex items-start gap-4">
              <div class="bg-white p-4 border border-gray-200 rounded-lg inline-block">
                <canvas
                  ref="qrCanvas"
                  width="192"
                  height="192"
                  style="width: 192px; height: 192px; display: block;"
                  :aria-label="$t('partner.referrals_page.qr_code_alt')"
                  role="img"
                ></canvas>
              </div>
              <div class="flex-1">
                <p class="text-sm text-gray-600 mb-3">{{ $t('partner.referrals_page.qr_code_description') }}</p>
                <button
                  @click="downloadQRCode"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                  :aria-label="$t('partner.referrals_page.download_qr_code')"
                >
                  {{ $t('partner.referrals_page.download_qr_code') }}
                </button>
              </div>
            </div>
          </div>

          <!-- Share Buttons -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('partner.referrals_page.quick_share') }}</label>
            <div class="flex gap-2 flex-wrap">
              <button
                @click="shareViaEmail"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                :aria-label="$t('partner.referrals_page.share_via_email')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                {{ $t('partner.referrals_page.email') }}
              </button>
              <button
                @click="shareViaWhatsApp"
                class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                :aria-label="$t('partner.referrals_page.share_via_whatsapp')"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
          <div class="text-sm font-medium text-gray-500 mb-2">{{ $t('partner.referrals_page.total_clicks') }}</div>
          <div class="text-3xl font-bold text-gray-900">{{ statistics?.totalClicks ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-sm font-medium text-gray-500 mb-2">{{ $t('partner.referrals_page.signups') }}</div>
          <div class="text-3xl font-bold text-green-600">{{ statistics?.signups ?? 0 }}</div>
          <div class="text-xs text-gray-500 mt-1">
            {{ signupPercentage.toFixed(1) }}% {{ $t('partner.referrals_page.conversion_from_clicks') }}
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-sm font-medium text-gray-500 mb-2">{{ $t('partner.referrals_page.active_subscriptions') }}</div>
          <div class="text-3xl font-bold text-blue-600">{{ statistics?.activeSubscriptions ?? 0 }}</div>
          <div class="text-xs text-gray-500 mt-1">
            {{ subscriptionPercentage.toFixed(1) }}% {{ $t('partner.referrals_page.conversion_from_clicks') }}
          </div>
        </div>
      </div>

      <!-- Partner-to-Partner Invites -->
      <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('partner.referrals_page.partner_invite_title') }}</h3>
          <button
            @click="loadPartnerInvite"
            :disabled="inviteLoading"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            :aria-label="$t('partner.referrals_page.generate_partner_link')"
          >
            <span v-if="inviteLoading" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ $t('partner.referrals_page.generating') }}
            </span>
            <span v-else>{{ $t('partner.referrals_page.generate_link') }}</span>
          </button>
        </div>
        <p class="text-sm text-gray-600 mb-4">{{ $t('partner.referrals_page.partner_invite_description') }}</p>

        <div
          v-if="inviteError"
          class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-red-700"
          role="alert"
          aria-live="assertive"
        >
          {{ inviteError }}
        </div>

        <!-- Empty state for partner invite -->
        <div
          v-if="!partnerInvite && !inviteLoading && !inviteError"
          class="text-center py-6 border-2 border-dashed border-gray-200 rounded-lg"
        >
          <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
          <p class="mt-2 text-sm text-gray-600">{{ $t('partner.referrals_page.no_partner_invite') }}</p>
          <p class="mt-1 text-xs text-gray-500">{{ $t('partner.referrals_page.generate_partner_invite_prompt') }}</p>
        </div>

        <div v-if="partnerInvite" class="space-y-4">
          <div>
            <label
              for="partner-invite-link"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              {{ $t('partner.referrals_page.partner_link') }}
            </label>
            <div class="flex gap-2">
              <input
                id="partner-invite-link"
                :value="partnerInvite?.link || ''"
                readonly
                class="flex-1 px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg font-mono text-sm"
                :aria-label="$t('partner.referrals_page.partner_link_input')"
              />
              <button
                @click="copyPartnerLink"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                :title="inviteCopied ? $t('partner.referrals_page.copied') : $t('partner.referrals_page.copy')"
                :aria-label="inviteCopied ? $t('partner.referrals_page.copied') : $t('partner.referrals_page.copy_partner_link')"
              >
                <svg v-if="!inviteCopied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <svg v-else class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </button>
            </div>
          </div>

          <div class="flex flex-wrap gap-4">
            <div class="bg-white p-4 border border-gray-200 rounded-lg inline-block">
              <img
                v-if="partnerInvite?.qr_code_url"
                :src="partnerInvite.qr_code_url"
                :alt="$t('partner.referrals_page.partner_qr_alt')"
                class="w-48 h-48 object-contain"
              />
            </div>
            <div class="flex-1 min-w-[240px]">
              <p class="text-sm text-gray-600 mb-3">{{ $t('partner.referrals_page.partner_qr_description') }}</p>
            <div class="flex gap-2">
              <button
                @click="downloadPartnerQR"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                :aria-label="$t('partner.referrals_page.download_partner_qr')"
              >
                {{ $t('partner.referrals_page.download_qr') }}
              </button>
              <button
                @click="sharePartnerEmail"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                :aria-label="$t('partner.referrals_page.share_partner_email')"
              >
                {{ $t('partner.referrals_page.email_share') }}
              </button>
              <button
                @click="sharePartnerWhatsApp"
                class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                :aria-label="$t('partner.referrals_page.share_partner_whatsapp')"
              >
                WhatsApp
              </button>
            </div>
              <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-900 mb-2">{{ $t('partner.referrals_page.send_email_invite') }}</h4>
                <div class="flex flex-col gap-2">
                  <input
                    v-model="inviteEmail"
                    type="email"
                    :placeholder="$t('partner.referrals_page.email_placeholder')"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :aria-label="$t('partner.referrals_page.partner_email_input')"
                  />
                  <button
                    @click="sendPartnerEmailInvite"
                    :disabled="sendingInviteEmail || !inviteEmail"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    :aria-label="$t('partner.referrals_page.send_email_invite')"
                  >
                    <span v-if="sendingInviteEmail" class="flex items-center justify-center gap-2">
                      <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      {{ $t('partner.referrals_page.sending') }}
                    </span>
                    <span v-else>{{ $t('partner.referrals_page.send_email_invite') }}</span>
                  </button>
                </div>
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
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'
import { debounce } from 'lodash'

export default {
  setup() {
    const notificationStore = useNotificationStore()
    const { t } = useI18n()

    return {
      notificationStore,
      t
    }
  },

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
      inviteEmail: '',
      abortController: null
    }
  },

  computed: {
    signupPercentage() {
      const totalClicks = this.statistics?.totalClicks ?? 0
      const signups = this.statistics?.signups ?? 0
      if (totalClicks === 0) return 0
      return Math.min((signups / totalClicks) * 100, 100)
    },

    subscriptionPercentage() {
      const totalClicks = this.statistics?.totalClicks ?? 0
      const activeSubscriptions = this.statistics?.activeSubscriptions ?? 0
      if (totalClicks === 0) return 0
      return Math.min((activeSubscriptions / totalClicks) * 100, 100)
    }
  },

  mounted() {
    this.fetchReferralData()
  },

  beforeUnmount() {
    // Abort any pending requests when component is destroyed
    if (this.abortController) {
      this.abortController.abort()
    }
  },

  methods: {
    async fetchReferralData() {
      // Cancel any previous request
      if (this.abortController) {
        this.abortController.abort()
      }
      this.abortController = new AbortController()

      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/partner/referrals', {
          signal: this.abortController.signal
        })
        this.activeLink = response.data?.activeLink || null
        this.statistics = response.data?.statistics || {
          totalClicks: 0,
          signups: 0,
          activeSubscriptions: 0
        }

        if (this.activeLink?.url) {
          this.$nextTick(() => {
            this.generateQRCode()
          })
        }
      } catch (err) {
        // Ignore abort errors
        if (err.name === 'AbortError' || err.code === 'ERR_CANCELED') {
          return
        }
        this.error = this.t('partner.referrals_page.load_error')
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

        this.activeLink = response.data?.link || null
        this.customCode = ''

        this.notificationStore.showNotification({
          type: 'success',
          message: this.t('partner.referrals_page.link_generated_success')
        })

        if (this.activeLink?.url) {
          this.$nextTick(() => {
            this.generateQRCode()
          })
        }
      } catch (err) {
        if (err.response?.status === 422) {
          this.error = err.response?.data?.error || this.t('partner.referrals_page.code_exists_error')
        } else {
          this.error = this.t('partner.referrals_page.generate_error')
        }
        this.notificationStore.showNotification({
          type: 'error',
          message: this.error
        })
      } finally {
        this.generating = false
      }
    },

    async generateQRCode() {
      if (!this.$refs.qrCanvas || !this.activeLink?.url) return

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
        this.notificationStore.showNotification({
          type: 'error',
          message: this.t('partner.referrals_page.qr_generation_error')
        })
      }
    },

    async copyToClipboard(text) {
      if (!text) return

      try {
        await navigator.clipboard.writeText(text)
        this.copied = true
        this.notificationStore.showNotification({
          type: 'success',
          message: this.t('partner.referrals_page.link_copied')
        })
        setTimeout(() => {
          this.copied = false
        }, 2000)
      } catch (err) {
        this.notificationStore.showNotification({
          type: 'error',
          message: this.t('partner.referrals_page.copy_error')
        })
      }
    },

    downloadQRCode() {
      if (!this.$refs.qrCanvas || !this.activeLink?.code) return

      try {
        const link = document.createElement('a')
        link.download = `referral-qr-${this.activeLink.code}.png`
        link.href = this.$refs.qrCanvas.toDataURL()
        link.click()

        this.notificationStore.showNotification({
          type: 'success',
          message: this.t('partner.referrals_page.qr_downloaded')
        })
      } catch (err) {
        this.notificationStore.showNotification({
          type: 'error',
          message: this.t('partner.referrals_page.download_error')
        })
      }
    },

    shareViaEmail() {
      if (!this.activeLink?.url) return

      const subject = encodeURIComponent(this.t('partner.referrals_page.email_subject'))
      const body = encodeURIComponent(this.t('partner.referrals_page.email_body', { url: this.activeLink.url }))
      window.location.href = `mailto:?subject=${subject}&body=${body}`
    },

    shareViaWhatsApp() {
      if (!this.activeLink?.url) return

      const text = encodeURIComponent(this.t('partner.referrals_page.whatsapp_message', { url: this.activeLink.url }))
      window.open(`https://wa.me/?text=${text}`, '_blank')
    },

    // Partner-to-partner invitation helpers
    async loadPartnerInvite() {
      this.inviteLoading = true
      this.inviteError = null
      try {
        const response = await axios.post('/invitations/partner-to-partner')
        this.partnerInvite = response.data || null

        this.notificationStore.showNotification({
          type: 'success',
          message: this.t('partner.referrals_page.partner_link_generated')
        })
      } catch (err) {
        this.inviteError = err?.response?.data?.message || this.t('partner.referrals_page.partner_link_error')
        this.notificationStore.showNotification({
          type: 'error',
          message: this.inviteError
        })
      } finally {
        this.inviteLoading = false
      }
    },

    async copyPartnerLink() {
      if (!this.partnerInvite?.link) return

      try {
        await navigator.clipboard.writeText(this.partnerInvite.link)
        this.inviteCopied = true
        this.notificationStore.showNotification({
          type: 'success',
          message: this.t('partner.referrals_page.partner_link_copied')
        })
        setTimeout(() => {
          this.inviteCopied = false
        }, 2000)
      } catch (err) {
        this.notificationStore.showNotification({
          type: 'error',
          message: this.t('partner.referrals_page.copy_error')
        })
      }
    },

    downloadPartnerQR() {
      if (!this.partnerInvite?.qr_code_url) return

      try {
        const link = document.createElement('a')
        link.href = this.partnerInvite.qr_code_url
        link.download = 'partner-referral-qr.png'
        link.click()

        this.notificationStore.showNotification({
          type: 'success',
          message: this.t('partner.referrals_page.qr_downloaded')
        })
      } catch (err) {
        this.notificationStore.showNotification({
          type: 'error',
          message: this.t('partner.referrals_page.download_error')
        })
      }
    },

    sharePartnerEmail() {
      if (!this.partnerInvite?.link) return

      const subject = encodeURIComponent(this.t('partner.referrals_page.partner_email_subject'))
      const body = encodeURIComponent(this.t('partner.referrals_page.partner_email_body', { url: this.partnerInvite.link }))
      window.location.href = `mailto:?subject=${subject}&body=${body}`
    },

    sharePartnerWhatsApp() {
      if (!this.partnerInvite?.link) return

      const text = encodeURIComponent(this.t('partner.referrals_page.partner_whatsapp_message', { url: this.partnerInvite.link }))
      window.open(`https://wa.me/?text=${text}`, '_blank')
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
        this.notificationStore.showNotification({
          type: 'success',
          message: this.t('partner.referrals_page.email_invite_sent')
        })
      } catch (err) {
        this.inviteError = this.t('partner.referrals_page.email_invite_failed')
        this.notificationStore.showNotification({
          type: 'error',
          message: this.inviteError
        })
      } finally {
        this.sendingInviteEmail = false
      }
    },

    // Debounced search method (for future search functionality)
    debouncedSearch: debounce(function(query) {
      // Placeholder for search functionality
    }, 300)
  }
}
</script>

<style scoped>
.referrals-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
/* CLAUDE-CHECKPOINT */
</style>
