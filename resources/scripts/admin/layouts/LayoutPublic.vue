<template>
  <div class="min-h-screen flex flex-col bg-white font-sans text-slate-900">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-slate-100">
      <div class="container mx-auto px-4 md:px-6">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <div class="flex-shrink-0 cursor-pointer" @click="$router.push('/')">
             <MainLogo class="h-8 w-auto" variant="dark" />
          </div>

          <!-- Desktop Menu -->
          <div class="hidden md:flex items-center space-x-8">
            <a href="#features" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors">Features</a>
            <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors">How it Works</a>
            <router-link to="/pricing" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors">Pricing</router-link>
            <a href="#faq" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors">FAQ</a>
          </div>

          <!-- Auth Buttons -->
          <div class="hidden md:flex items-center space-x-4">
            <!-- Language Switcher -->
            <BaseDropdown width-class="w-48">
              <template #activator>
                <button class="flex items-center text-gray-500 hover:text-gray-900 focus:outline-none">
                  <BaseIcon name="LanguageIcon" class="h-6 w-6" />
                  <span class="ml-1 uppercase">{{ currentLocale }}</span>
                </button>
              </template>
              <BaseDropdownItem @click="setLanguage('en')">
                <div class="flex items-center">
                  <span class="mr-3 text-base">🇺🇸</span> English
                  <span v-if="currentLocale === 'en'" class="ml-auto text-green-500">✓</span>
                </div>
              </BaseDropdownItem>
              <BaseDropdownItem @click="setLanguage('mk')">
                <div class="flex items-center">
                  <span class="mr-3 text-base">🇲🇰</span> Македонски
                  <span v-if="currentLocale === 'mk'" class="ml-auto text-green-500">✓</span>
                </div>
              </BaseDropdownItem>
              <BaseDropdownItem @click="setLanguage('sq')">
                <div class="flex items-center">
                  <span class="mr-3 text-base">🇦🇱</span> Shqip
                  <span v-if="currentLocale === 'sq'" class="ml-auto text-green-500">✓</span>
                </div>
              </BaseDropdownItem>
              <BaseDropdownItem @click="setLanguage('tr')">
                <div class="flex items-center">
                  <span class="mr-3 text-base">🇹🇷</span> Türkçe
                  <span v-if="currentLocale === 'tr'" class="ml-auto text-green-500">✓</span>
                </div>
              </BaseDropdownItem>
            </BaseDropdown>

            <router-link
              to="/login"
              class="text-base font-medium text-gray-500 hover:text-gray-900"
            >
              Log in
            </router-link>
            <router-link to="/login" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-sm hover:shadow-md">
              Get Started
            </router-link>
          </div>

          <!-- Mobile Menu Button -->
          <div class="md:hidden flex items-center">
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-500 hover:text-slate-700 focus:outline-none">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div v-if="mobileMenuOpen" class="md:hidden bg-white border-b border-slate-100">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
          <a href="#features" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-slate-50">Features</a>
          <a href="#how-it-works" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-slate-50">How it Works</a>
          <router-link to="/pricing" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-slate-50">Pricing</router-link>
          <a href="#faq" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-slate-50">FAQ</a>
          <div class="border-t border-slate-100 my-2 pt-2">
            <router-link to="/login" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-slate-50">Log in</router-link>
            <router-link to="/login" class="block px-3 py-2 rounded-md text-base font-medium text-primary-600 font-bold hover:bg-slate-50">Get Started</router-link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-300 py-12 border-t border-slate-800">
      <div class="container mx-auto px-4 md:px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          <div class="col-span-1 md:col-span-1">
            <MainLogo class="h-8 w-auto mb-4 brightness-0 invert" variant="clear" />
            <p class="text-sm text-slate-400 leading-relaxed">
              Smart invoicing and accounting for modern businesses. Automate your finances with AI.
            </p>
          </div>
          <div>
            <h4 class="text-white font-semibold mb-4">Product</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
              <li><router-link to="/pricing" class="hover:text-white transition-colors">Pricing</router-link></li>
              <li><a href="#" class="hover:text-white transition-colors">API</a></li>
            </ul>
          </div>
          <div>
            <h4 class="text-white font-semibold mb-4">Company</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="hover:text-white transition-colors">About Us</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
            </ul>
          </div>
          <div>
            <h4 class="text-white font-semibold mb-4">Legal</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
            </ul>
          </div>
        </div>
        <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center">
          <p class="text-sm text-slate-500">
            &copy; {{ new Date().getFullYear() }} Facturino DOOEL. All rights reserved.
          </p>
          <div class="flex space-x-4 mt-4 md:mt-0">
            <!-- Social Icons Placeholder -->
            <a href="#" class="text-slate-400 hover:text-white transition-colors">
              <span class="sr-only">Facebook</span>
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="#" class="text-slate-400 hover:text-white transition-colors">
              <span class="sr-only">Twitter</span>
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'

const { locale } = useI18n()
const globalStore = useGlobalStore()
const currentLocale = computed(() => locale.value)

const navigation = [
  { name: 'Features', href: '#features' },
  { name: 'How it works', href: '#how-it-works' },
  { name: 'Pricing', href: '/pricing' },
  { name: 'FAQ', href: '#faq' },
]

const mobileMenuOpen = ref(false)

function setLanguage(newLocale) {
  locale.value = newLocale
  localStorage.setItem('invoiceshelf_locale', newLocale)
  try {
    globalStore.updateLanguage(newLocale)
  } catch (error) {
    console.log('Global store updateLanguage not available, using localStorage only')
  }
}
</script>

<style scoped>
/* Add any specific scoped styles here if needed */
</style>
