<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <router-link
          :to="{ name: 'partner.portfolio' }"
          class="text-sm text-blue-600 hover:text-blue-800 mb-2 inline-block"
        >
          &larr; Back to Portfolio
        </router-link>
        <h1 class="text-3xl font-bold text-gray-900">Add Company</h1>
        <p class="mt-2 text-sm text-gray-600">
          Add a client company to your portfolio. The company will get a 14-day Standard trial.
        </p>
      </div>

      <!-- Form -->
      <div class="bg-white shadow rounded-lg p-6">
        <form @submit.prevent="createCompany">
          <div class="space-y-4">
            <div>
              <label for="company-name" class="block text-sm font-medium text-gray-700">
                Company Name <span class="text-red-500">*</span>
              </label>
              <input
                id="company-name"
                v-model="form.name"
                type="text"
                required
                class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter company name"
              />
            </div>

            <div>
              <label for="tax-id" class="block text-sm font-medium text-gray-700">
                Tax ID (EDB) <span class="text-red-500">*</span>
              </label>
              <input
                id="tax-id"
                v-model="form.tax_id"
                type="text"
                required
                class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                placeholder="e.g., 4030006123456"
              />
            </div>

            <div>
              <label for="vat-number" class="block text-sm font-medium text-gray-700">
                VAT Number (optional)
              </label>
              <input
                id="vat-number"
                v-model="form.vat_number"
                type="text"
                class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                placeholder="e.g., MK4030006123456"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="currency" class="block text-sm font-medium text-gray-700">Currency</label>
                <select
                  id="currency"
                  v-model="form.currency"
                  class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="MKD">MKD (Macedonian Denar)</option>
                  <option value="EUR">EUR (Euro)</option>
                  <option value="USD">USD (US Dollar)</option>
                </select>
              </div>
              <div>
                <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                <select
                  id="language"
                  v-model="form.language"
                  class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="mk">Macedonian</option>
                  <option value="sq">Albanian</option>
                  <option value="en">English</option>
                  <option value="tr">Turkish</option>
                </select>
              </div>
            </div>
          </div>

          <div v-if="error" class="mt-4 p-3 bg-red-50 text-red-700 rounded-md text-sm">
            {{ error }}
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <router-link
              :to="{ name: 'partner.portfolio' }"
              class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm font-medium"
            >
              Cancel
            </router-link>
            <button
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium disabled:opacity-50"
            >
              {{ submitting ? 'Creating...' : 'Add Company' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const form = ref({
  name: '',
  tax_id: '',
  vat_number: '',
  currency: 'MKD',
  language: 'mk',
})

const submitting = ref(false)
const error = ref(null)

const createCompany = async () => {
  submitting.value = true
  error.value = null

  try {
    await axios.post('/partner/portfolio-companies', form.value)
    router.push({ name: 'partner.portfolio' })
  } catch (e) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors
      error.value = Object.values(errors).flat().join('. ')
    } else {
      error.value = e.response?.data?.error || 'Failed to create company'
    }
  } finally {
    submitting.value = false
  }
}
</script>
