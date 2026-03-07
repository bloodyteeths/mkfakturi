<template>
  <BasePage>
    <BasePageHeader :title="t('create')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="../travel-orders" />
        <BaseBreadcrumbItem :title="t('create')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Step Indicator -->
    <div class="mb-8">
      <nav aria-label="Progress">
        <ol class="flex items-center">
          <li v-for="(stepInfo, index) in steps" :key="index" class="relative flex-1">
            <div class="flex items-center">
              <span
                :class="[
                  'relative flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                  step > index + 1 ? 'bg-primary-600 text-white' :
                  step === index + 1 ? 'bg-primary-600 text-white ring-2 ring-primary-600 ring-offset-2' :
                  'bg-gray-200 text-gray-600'
                ]"
              >
                <BaseIcon v-if="step > index + 1" name="CheckIcon" class="h-4 w-4" />
                <span v-else>{{ index + 1 }}</span>
              </span>
              <span
                v-if="index < steps.length - 1"
                :class="[
                  'ml-2 flex-1 h-0.5',
                  step > index + 1 ? 'bg-primary-600' : 'bg-gray-200'
                ]"
              />
            </div>
            <p class="mt-1 text-xs font-medium" :class="step >= index + 1 ? 'text-primary-600' : 'text-gray-500'">
              {{ stepInfo }}
            </p>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Step 1: Basic Info -->
    <div v-if="step === 1" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('step1_basic') }}</h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <BaseInputGroup :label="t('type')" required>
          <BaseMultiselect
            v-model="form.type"
            :options="typeOptions"
            label="label"
            value-prop="value"
            :searchable="false"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('employee')">
          <BaseMultiselect
            v-model="form.employee_id"
            :options="employees"
            :searchable="true"
            label="name"
            value-prop="id"
            :placeholder="t('employee')"
            :loading="isLoadingEmployees"
          />
        </BaseInputGroup>
      </div>

      <div class="mt-6">
        <BaseInputGroup :label="t('purpose')" required>
          <textarea
            v-model="form.purpose"
            rows="3"
            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
            :placeholder="t('purpose')"
          />
        </BaseInputGroup>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <BaseInputGroup :label="t('departure')" required>
          <BaseDatePicker
            v-model="form.departure_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('return_date')" required>
          <BaseDatePicker
            v-model="form.return_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <BaseInputGroup :label="t('advance_amount')">
          <BaseInput
            v-model="form.advance_amount_display"
            type="number"
            step="0.01"
            min="0"
            :placeholder="'0.00'"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('notes')">
          <BaseInput
            v-model="form.notes"
            type="text"
            :placeholder="t('notes_placeholder')"
          />
        </BaseInputGroup>
      </div>

      <div class="flex justify-end mt-6">
        <BaseButton
          variant="primary"
          :disabled="!canProceedStep1"
          @click="step = 2"
        >
          {{ t('next') }}
          <template #right="slotProps">
            <BaseIcon name="ArrowRightIcon" :class="slotProps.class" />
          </template>
        </BaseButton>
      </div>
    </div>

    <!-- Step 2: Segments -->
    <div v-if="step === 2" class="space-y-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">{{ t('segments') }}</h3>
          <BaseButton variant="primary-outline" size="sm" @click="addSegment">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('add_segment') }}
          </BaseButton>
        </div>

        <div v-for="(seg, index) in form.segments" :key="index" class="border border-gray-200 rounded-lg p-4 mb-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">{{ t('segments') }} #{{ index + 1 }}</h4>
            <button
              v-if="form.segments.length > 1"
              class="text-red-500 hover:text-red-700 text-sm"
              @click="removeSegment(index)"
            >
              {{ t('remove') }}
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <BaseInputGroup :label="t('from_city')" required>
              <BaseInput v-model="seg.from_city" type="text" :placeholder="t('from_city')" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('to_city')" required>
              <BaseInput v-model="seg.to_city" type="text" :placeholder="t('to_city')" />
            </BaseInputGroup>

            <BaseInputGroup v-if="form.type === 'foreign'" :label="t('country')">
              <BaseInput v-model="seg.country_code" type="text" maxlength="2" :placeholder="'MK'" />
            </BaseInputGroup>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <BaseInputGroup :label="t('departure')" required>
              <BaseDatePicker
                v-model="seg.departure_at"
                :calendar-button="true"
                calendar-button-icon="CalendarDaysIcon"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('return_date')" required>
              <BaseDatePicker
                v-model="seg.arrival_at"
                :calendar-button="true"
                calendar-button-icon="CalendarDaysIcon"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('transport_type')" required>
              <BaseMultiselect
                v-model="seg.transport_type"
                :options="transportOptions"
                label="label"
                value-prop="value"
                :searchable="false"
              />
            </BaseInputGroup>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <BaseInputGroup v-if="seg.transport_type === 'car'" :label="t('distance')">
              <BaseInput v-model="seg.distance_km" type="number" step="0.1" min="0" :placeholder="'0'" />
            </BaseInputGroup>

            <BaseInputGroup v-if="form.type === 'foreign'" :label="t('per_diem') + ' (' + t('rate') + ')'">
              <BaseInput v-model="seg.per_diem_rate" type="number" step="0.01" min="0" :placeholder="'0.00'" />
            </BaseInputGroup>

            <div class="flex items-end space-x-4">
              <label class="flex items-center space-x-2 text-sm">
                <input type="checkbox" v-model="seg.accommodation_provided" class="h-4 w-4 rounded border-gray-300 text-primary-600" />
                <span>{{ t('accommodation_provided') }}</span>
              </label>
              <label class="flex items-center space-x-2 text-sm">
                <input type="checkbox" v-model="seg.meals_provided" class="h-4 w-4 rounded border-gray-300 text-primary-600" />
                <span>{{ t('meals_provided') }}</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-between">
        <BaseButton variant="primary-outline" @click="step = 1">
          <template #left="slotProps">
            <BaseIcon name="ArrowLeftIcon" :class="slotProps.class" />
          </template>
          {{ t('back') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          :disabled="!canProceedStep2"
          @click="step = 3"
        >
          {{ t('next') }}
          <template #right="slotProps">
            <BaseIcon name="ArrowRightIcon" :class="slotProps.class" />
          </template>
        </BaseButton>
      </div>
    </div>

    <!-- Step 3: Expenses -->
    <div v-if="step === 3" class="space-y-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">{{ t('expenses') }}</h3>
          <BaseButton variant="primary-outline" size="sm" @click="addExpense">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('add_expense') }}
          </BaseButton>
        </div>

        <div v-if="form.expenses.length === 0" class="text-center py-8 text-sm text-gray-500">
          {{ t('no_expenses_yet') }}
        </div>

        <div v-for="(exp, index) in form.expenses" :key="index" class="border border-gray-200 rounded-lg p-4 mb-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">{{ t('expenses') }} #{{ index + 1 }}</h4>
            <button
              class="text-red-500 hover:text-red-700 text-sm"
              @click="removeExpense(index)"
            >
              {{ t('remove') }}
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <BaseInputGroup :label="t('category')" required>
              <BaseMultiselect
                v-model="exp.category"
                :options="categoryOptions"
                label="label"
                value-prop="value"
                :searchable="false"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('description')" required class="md:col-span-2">
              <BaseInput v-model="exp.description" type="text" :placeholder="t('description')" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('amount')" required>
              <BaseInput v-model="exp.amount_display" type="number" step="0.01" min="0" :placeholder="'0.00'" />
            </BaseInputGroup>
          </div>
        </div>
      </div>

      <div class="flex justify-between">
        <BaseButton variant="primary-outline" @click="step = 2">
          <template #left="slotProps">
            <BaseIcon name="ArrowLeftIcon" :class="slotProps.class" />
          </template>
          {{ t('back') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          @click="step = 4"
        >
          {{ t('next') }}
          <template #right="slotProps">
            <BaseIcon name="ArrowRightIcon" :class="slotProps.class" />
          </template>
        </BaseButton>
      </div>
    </div>

    <!-- Step 4: Review -->
    <div v-if="step === 4" class="space-y-6">
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">{{ t('step4_review') }}</h3>
        </div>

        <div class="p-6 space-y-6">
          <!-- Basic Info -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('type') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ form.type === 'domestic' ? t('domestic') : t('foreign') }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('departure') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ formatDate(form.departure_date) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('return_date') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ formatDate(form.return_date) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('advance_amount') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ formatMoney(advanceInCents) }}</p>
            </div>
          </div>

          <div>
            <p class="text-xs text-gray-500 uppercase font-medium">{{ t('purpose') }}</p>
            <p class="text-sm text-gray-900 mt-1">{{ form.purpose }}</p>
          </div>

          <!-- Segments Table -->
          <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ t('segments') }} ({{ form.segments.length }})</h4>
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('from_city') }}</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('to_city') }}</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('transport_type') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('distance') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(seg, i) in form.segments" :key="i">
                  <td class="px-4 py-2 text-sm text-gray-900">{{ seg.from_city }}</td>
                  <td class="px-4 py-2 text-sm text-gray-900">{{ seg.to_city }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ transportLabel(seg.transport_type) }}</td>
                  <td class="px-4 py-2 text-sm text-right text-gray-500">{{ seg.distance_km || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Expenses Table -->
          <div v-if="form.expenses.length > 0">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ t('expenses') }} ({{ form.expenses.length }})</h4>
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('category') }}</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('description') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(exp, i) in form.expenses" :key="i">
                  <td class="px-4 py-2 text-sm text-gray-500">{{ categoryLabel(exp.category) }}</td>
                  <td class="px-4 py-2 text-sm text-gray-900">{{ exp.description }}</td>
                  <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">{{ formatMoney(expenseAmountCents(exp)) }}</td>
                </tr>
                <tr class="bg-gray-50">
                  <td colspan="2" class="px-4 py-2 text-sm font-semibold text-gray-700 text-right">{{ t('total_expenses') }}</td>
                  <td class="px-4 py-2 text-sm text-right font-bold text-gray-900">{{ formatMoney(totalExpensesCents) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="flex justify-between">
        <BaseButton variant="primary-outline" @click="step = 3">
          <template #left="slotProps">
            <BaseIcon name="ArrowLeftIcon" :class="slotProps.class" />
          </template>
          {{ t('back') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          :loading="isSaving"
          @click="saveTravelOrder"
        >
          <template #left="slotProps">
            <BaseIcon name="CheckIcon" :class="slotProps.class" />
          </template>
          {{ t('save_draft') }}
        </BaseButton>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import travelMessages from '@/scripts/admin/i18n/travel-orders.js'

const router = useRouter()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return travelMessages[locale]?.travel_orders?.[key]
    || travelMessages['en']?.travel_orders?.[key]
    || key
}

// State
const step = ref(1)
const isSaving = ref(false)
const isLoadingEmployees = ref(false)
const employees = ref([])

function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const form = reactive({
  type: 'domestic',
  purpose: '',
  departure_date: getLocalDateString(),
  return_date: getLocalDateString(),
  employee_id: null,
  advance_amount_display: '',
  notes: '',
  segments: [
    {
      from_city: '',
      to_city: '',
      country_code: '',
      departure_at: getLocalDateString(),
      arrival_at: getLocalDateString(),
      transport_type: 'car',
      distance_km: '',
      accommodation_provided: false,
      meals_provided: false,
      per_diem_rate: '',
    }
  ],
  expenses: [],
})

const steps = [t('step1_basic'), t('step2_segments'), t('step3_expenses'), t('step4_review')]

const typeOptions = [
  { value: 'domestic', label: t('domestic') },
  { value: 'foreign', label: t('foreign') },
]

const transportOptions = [
  { value: 'car', label: t('transport_car') },
  { value: 'bus', label: t('transport_bus') },
  { value: 'train', label: t('transport_train') },
  { value: 'plane', label: t('transport_plane') },
  { value: 'other', label: t('transport_other') },
]

const categoryOptions = [
  { value: 'transport', label: t('category_transport') },
  { value: 'accommodation', label: t('category_accommodation') },
  { value: 'meals', label: t('category_meals') },
  { value: 'other', label: t('category_other') },
]

// Computed
const canProceedStep1 = computed(() => {
  return form.type && form.purpose && form.departure_date && form.return_date
})

const canProceedStep2 = computed(() => {
  return form.segments.length > 0 && form.segments.every(s => s.from_city && s.to_city && s.departure_at && s.arrival_at && s.transport_type)
})

const advanceInCents = computed(() => {
  return Math.round(parseFloat(form.advance_amount_display || 0) * 100)
})

const totalExpensesCents = computed(() => {
  return form.expenses.reduce((sum, exp) => {
    return sum + Math.round(parseFloat(exp.amount_display || 0) * 100)
  }, 0)
})

// Methods
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function formatMoney(cents) {
  if (!cents && cents !== 0) return '-'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(fmtLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function transportLabel(type) {
  const opt = transportOptions.find(o => o.value === type)
  return opt ? opt.label : type
}

function categoryLabel(cat) {
  const opt = categoryOptions.find(o => o.value === cat)
  return opt ? opt.label : cat
}

function expenseAmountCents(exp) {
  return Math.round(parseFloat(exp.amount_display || 0) * 100)
}

function addSegment() {
  form.segments.push({
    from_city: '',
    to_city: '',
    country_code: '',
    departure_at: getLocalDateString(),
    arrival_at: getLocalDateString(),
    transport_type: 'car',
    distance_km: '',
    accommodation_provided: false,
    meals_provided: false,
    per_diem_rate: '',
  })
}

function removeSegment(index) {
  form.segments.splice(index, 1)
}

function addExpense() {
  form.expenses.push({
    category: 'transport',
    description: '',
    amount_display: '',
  })
}

function removeExpense(index) {
  form.expenses.splice(index, 1)
}

async function fetchEmployees() {
  isLoadingEmployees.value = true
  try {
    const response = await window.axios.get('/payroll/employees', { params: { limit: 'all' } })
    const data = response.data?.data || response.data?.employees?.data || []
    employees.value = data.map(e => ({
      id: e.id,
      name: `${e.first_name} ${e.last_name}`,
    }))
  } catch {
    employees.value = []
  } finally {
    isLoadingEmployees.value = false
  }
}

async function saveTravelOrder() {
  isSaving.value = true

  const segments = form.segments.map(s => ({
    from_city: s.from_city,
    to_city: s.to_city,
    country_code: s.country_code || null,
    departure_at: s.departure_at,
    arrival_at: s.arrival_at,
    transport_type: s.transport_type,
    distance_km: s.distance_km ? parseFloat(s.distance_km) : null,
    accommodation_provided: s.accommodation_provided,
    meals_provided: s.meals_provided,
  }))

  const expenses = form.expenses.map(e => ({
    category: e.category,
    description: e.description,
    amount: Math.round(parseFloat(e.amount_display || 0) * 100),
    currency_code: 'MKD',
  }))

  try {
    const response = await window.axios.post('/travel-orders', {
      type: form.type,
      purpose: form.purpose,
      departure_date: form.departure_date,
      return_date: form.return_date,
      employee_id: form.employee_id,
      advance_amount: advanceInCents.value,
      notes: form.notes,
      segments,
      expenses,
    })

    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('created_success'),
    })

    const orderId = response.data?.data?.id
    if (orderId) {
      router.push({ path: `/admin/travel-orders/${orderId}` })
    } else {
      router.push({ path: '/admin/travel-orders' })
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_creating'),
    })
  } finally {
    isSaving.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchEmployees()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
