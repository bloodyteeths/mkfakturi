<template>
  <BasePage>
    <form @submit.prevent="handleSubmit">
      <BasePageHeader :title="pageTitle">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
          <BaseBreadcrumbItem :title="$t('suppliers.title')" to="/admin/suppliers" />
          <BaseBreadcrumbItem :title="pageTitle" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <div class="flex items-center justify-end">
            <BaseButton type="submit" :loading="isSaving" :disabled="isSaving">
              <template #left="slotProps">
                <BaseIcon name="ArrowDownOnSquareIcon" :class="slotProps.class" />
              </template>
              {{ isEdit ? $t('suppliers.update_supplier') : $t('suppliers.save_supplier') }}
            </BaseButton>
          </div>
        </template>
      </BasePageHeader>

      <BaseCard class="mt-5">
        <!-- Basic Info -->
        <div class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ $t('suppliers.basic_info') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup
              :label="$t('suppliers.name')"
              required
              :error="v$.form.name.$error && v$.form.name.$errors[0].$message"
            >
              <BaseInput
                v-model="form.name"
                type="text"
                :invalid="v$.form.name.$error"
                @input="v$.form.name.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="$t('suppliers.email')"
              required
              :error="v$.form.email.$error && v$.form.email.$errors[0].$message"
            >
              <BaseInput
                v-model="form.email"
                type="email"
                :invalid="v$.form.email.$error"
                @input="v$.form.email.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.phone')">
              <BaseInput v-model="form.phone" type="text" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.website')">
              <BaseInput v-model="form.website" type="url" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.vat_number')">
              <BaseInput v-model="form.vat_number" type="text" :placeholder="$t('suppliers.vat_number_placeholder')" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.tax_id')">
              <BaseInput v-model="form.tax_id" type="text" :placeholder="$t('suppliers.tax_id_placeholder')" />
            </BaseInputGroup>

            <!-- Tax ID match suggestion -->
            <div v-if="matchedCustomer" class="col-span-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <p class="text-sm text-blue-800">
                {{ $t('suppliers.link_suggestion', { name: matchedCustomer.name }) }}
              </p>
              <div class="flex items-center gap-2 mt-2">
                <button
                  type="button"
                  class="text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded"
                  @click="linkAfterCreate = true; matchedCustomerId = matchedCustomer.id"
                >
                  {{ $t('suppliers.link_to_customer') }}
                </button>
                <button
                  type="button"
                  class="text-xs font-medium text-gray-500 hover:text-gray-700"
                  @click="matchedCustomer = null"
                >
                  {{ $t('general.dismiss') }}
                </button>
              </div>
            </div>

            <BaseInputGroup :label="$t('suppliers.company_registration_number')">
              <BaseInput v-model="form.company_registration_number" type="text" />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <BaseDivider class="mb-5 md:mb-8" />

        <!-- Contact Person -->
        <div class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ $t('suppliers.contact_person') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup :label="$t('suppliers.contact_name')">
              <BaseInput v-model="form.contact_name" type="text" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.contact_email')">
              <BaseInput v-model="form.contact_email" type="email" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.contact_phone')">
              <BaseInput v-model="form.contact_phone" type="text" />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <BaseDivider class="mb-5 md:mb-8" />

        <!-- Address -->
        <div class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ $t('suppliers.address') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup :label="$t('suppliers.country')">
              <BaseMultiselect
                v-model="form.country_id"
                value-prop="id"
                label="name"
                track-by="name"
                searchable
                :options="globalStore.countries"
                :placeholder="$t('general.select_country')"
                class="w-full"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.state')">
              <BaseInput v-model="form.state" type="text" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.city')">
              <BaseInput v-model="form.city" type="text" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.zip')">
              <BaseInput v-model="form.zip" type="text" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.address_line_1')">
              <BaseTextarea
                v-model="form.address_line_1"
                :placeholder="$t('general.street_1')"
                rows="2"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('suppliers.address_line_2')">
              <BaseTextarea
                v-model="form.address_line_2"
                :placeholder="$t('general.street_2')"
                rows="2"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <BaseDivider class="mb-5 md:mb-8" />

        <!-- Notes -->
        <div class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ $t('suppliers.notes') }}
          </h6>

          <div class="col-span-5 lg:col-span-4">
            <BaseInputGroup :label="$t('suppliers.notes')">
              <BaseTextarea
                v-model="form.notes"
                :placeholder="$t('suppliers.notes_placeholder')"
                rows="4"
              />
            </BaseInputGroup>
          </div>
        </div>
      </BaseCard>
    </form>

    <DuplicateWarningModal
      :show="showDuplicateWarning"
      :duplicates="duplicateRecords"
      entity-type="suppliers"
      @close="closeDuplicateWarning"
      @confirm="saveWithDuplicate"
    />
  </BasePage>
</template>

<script setup>
import { reactive, computed, ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useVuelidate } from '@vuelidate/core'
import { required, email, helpers } from '@vuelidate/validators'
import DuplicateWarningModal from '@/scripts/admin/components/modal-components/DuplicateWarningModal.vue'
import axios from 'axios'

const { t } = useI18n()
const suppliersStore = useSuppliersStore()
const globalStore = useGlobalStore()
const route = useRoute()
const router = useRouter()

const isSaving = ref(false)
const matchedCustomer = ref(null)
const linkAfterCreate = ref(false)
const matchedCustomerId = ref(null)
const showDuplicateWarning = ref(false)
const duplicateRecords = ref([])

const form = reactive({
  id: null,
  name: '',
  email: '',
  phone: '',
  website: '',
  vat_number: '',
  tax_id: '',
  company_registration_number: '',
  contact_name: '',
  contact_email: '',
  contact_phone: '',
  address_line_1: '',
  address_line_2: '',
  city: '',
  state: '',
  zip: '',
  country_id: null,
  notes: '',
})

const rules = {
  form: {
    name: {
      required: helpers.withMessage(t('validation.required'), required),
    },
    email: {
      required: helpers.withMessage(t('validation.required'), required),
      email: helpers.withMessage(t('validation.email_incorrect'), email),
    },
  },
}

const v$ = useVuelidate(rules, { form })

const isEdit = computed(() => !!route.params.id)

// Watch tax_id for matching customer suggestion
let taxIdTimeout = null
watch(() => form.tax_id, (val) => {
  clearTimeout(taxIdTimeout)
  matchedCustomer.value = null
  linkAfterCreate.value = false
  if (!val || val.length < 7 || isEdit.value) return
  taxIdTimeout = setTimeout(async () => {
    try {
      const res = await axios.get('/customers/match-by-tax-id', {
        params: { tax_id: val },
      })
      if (res.data.data && res.data.data.length > 0) {
        matchedCustomer.value = res.data.data[0]
      }
    } catch (e) {
      // silently ignore
    }
  }, 500)
})

const pageTitle = computed(() =>
  isEdit.value ? t('suppliers.edit_supplier') : t('suppliers.new_supplier')
)

function hydrateForm(data) {
  form.id = data.id
  form.name = data.name || ''
  form.email = data.email || ''
  form.phone = data.phone || ''
  form.website = data.website || ''
  form.vat_number = data.vat_number || ''
  form.tax_id = data.tax_id || ''
  form.company_registration_number = data.company_registration_number || ''
  form.contact_name = data.contact_name || ''
  form.contact_email = data.contact_email || ''
  form.contact_phone = data.contact_phone || ''
  form.address_line_1 = data.address_line_1 || ''
  form.address_line_2 = data.address_line_2 || ''
  form.city = data.city || ''
  form.state = data.state || ''
  form.zip = data.zip || ''
  form.country_id = data.country_id || null
  form.notes = data.notes || ''
}

async function handleSubmit(allowDuplicate = false) {
  if (typeof allowDuplicate !== 'boolean') allowDuplicate = false
  v$.value.$touch()
  if (v$.value.$invalid) {
    return
  }

  isSaving.value = true

  const payload = { ...form }

  try {
    let response
    if (isEdit.value) {
      response = await suppliersStore.updateSupplier(payload)
    } else {
      response = await suppliersStore.createSupplier(payload, allowDuplicate)
    }

    if (response?.data?.is_duplicate_warning) {
      isSaving.value = false
      duplicateRecords.value = response.data.duplicates || []
      showDuplicateWarning.value = true
      return
    }

    // Link to matched customer after successful create
    if (!isEdit.value && linkAfterCreate.value && matchedCustomerId.value && response?.data?.data?.id) {
      try {
        await axios.post(`/customers/${matchedCustomerId.value}/link-supplier`, {
          supplier_id: response.data.data.id,
        })
      } catch (e) {
        // linking failed silently, supplier was still created
      }
    }

    router.push('/admin/suppliers')
  } catch (err) {
    // Error handled by store
  } finally {
    isSaving.value = false
  }
}

function closeDuplicateWarning() {
  showDuplicateWarning.value = false
  duplicateRecords.value = []
}

function saveWithDuplicate() {
  closeDuplicateWarning()
  handleSubmit(true)
}

onMounted(async () => {
  // Fetch countries for dropdown
  if (!globalStore.countries || globalStore.countries.length === 0) {
    await globalStore.fetchCountries()
  }

  if (isEdit.value) {
    const response = await suppliersStore.fetchSupplier(route.params.id)
    hydrateForm(response.data.data)
  }
})
</script>
