<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Success State -->
    <div v-if="submitted" class="max-w-2xl mx-auto mt-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <BaseIcon name="CheckCircleIcon" class="h-8 w-8 text-green-600" />
        </div>
        <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ t('success_title') }}</h2>
        <p class="text-gray-600 mb-4">{{ t('success_message') }}</p>
        <div class="bg-gray-50 rounded-md p-4 mb-6">
          <p class="text-sm text-gray-500">{{ t('reference') }}</p>
          <p class="text-lg font-mono font-semibold text-primary-600">{{ referenceNumber }}</p>
        </div>
        <p class="text-sm text-gray-500 mb-6">{{ t('response_time') }}</p>
        <BaseButton variant="primary" @click="resetForm">
          {{ t('new_request') }}
        </BaseButton>
      </div>
    </div>

    <!-- Contact Form -->
    <div v-else class="max-w-2xl mx-auto mt-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ t('form_title') }}</h2>
        <p class="text-sm text-gray-500 mb-6">{{ t('form_subtitle') }}</p>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Name & Email -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseInputGroup :label="t('name')" required :error="errors.name">
              <BaseInput v-model="form.name" :placeholder="t('name_placeholder')" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('email')" required :error="errors.email">
              <BaseInput v-model="form.email" type="email" :placeholder="t('email_placeholder')" />
            </BaseInputGroup>
          </div>

          <!-- Subject -->
          <BaseInputGroup :label="t('subject')" required :error="errors.subject">
            <BaseInput v-model="form.subject" :placeholder="t('subject_placeholder')" />
          </BaseInputGroup>

          <!-- Category & Priority -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseInputGroup :label="t('category')" required :error="errors.category">
              <BaseMultiselect
                v-model="form.category"
                :options="categoryOptions"
                label="name"
                value-prop="id"
                :placeholder="t('select_category')"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('priority')" required :error="errors.priority">
              <BaseMultiselect
                v-model="form.priority"
                :options="priorityOptions"
                label="name"
                value-prop="id"
                :placeholder="t('select_priority')"
              />
            </BaseInputGroup>
          </div>

          <!-- Message -->
          <BaseInputGroup :label="t('message')" required :error="errors.message">
            <BaseTextarea
              v-model="form.message"
              :placeholder="t('message_placeholder')"
              rows="6"
            />
            <p class="mt-1 text-xs text-gray-400">{{ form.message.length }} / 2000</p>
          </BaseInputGroup>

          <!-- File Upload -->
          <BaseInputGroup :label="t('attachments')">
            <input
              ref="fileInput"
              type="file"
              multiple
              accept=".jpg,.jpeg,.png,.gif,.pdf"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
              @change="handleFiles"
            />
            <p class="mt-1 text-xs text-gray-400">{{ t('attachments_hint') }}</p>
            <div v-if="form.attachments.length" class="mt-2 space-y-1">
              <div
                v-for="(file, i) in form.attachments"
                :key="i"
                class="flex items-center justify-between bg-gray-50 rounded px-3 py-1.5 text-sm"
              >
                <span class="text-gray-700 truncate">{{ file.name }}</span>
                <button type="button" class="text-red-500 hover:text-red-700 ml-2" @click="removeFile(i)">
                  <BaseIcon name="XMarkIcon" class="h-4 w-4" />
                </button>
              </div>
            </div>
          </BaseInputGroup>

          <!-- Server error -->
          <div v-if="serverError" class="bg-red-50 border border-red-200 rounded-md p-3">
            <p class="text-sm text-red-700">{{ serverError }}</p>
          </div>

          <!-- Submit -->
          <div class="flex justify-end pt-2">
            <BaseButton type="submit" variant="primary" :loading="isSubmitting" :disabled="isSubmitting">
              {{ t('submit') }}
            </BaseButton>
          </div>
        </form>
      </div>

      <!-- Previous Submissions -->
      <div v-if="previousContacts.length" class="mt-8">
        <h3 class="text-base font-semibold text-gray-900 mb-3">{{ t('previous') }}</h3>
        <div class="space-y-3">
          <div
            v-for="contact in previousContacts"
            :key="contact.id"
            class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
          >
            <div class="flex items-start justify-between">
              <div>
                <h4 class="font-medium text-gray-900">{{ contact.subject }}</h4>
                <p class="text-sm text-gray-500 mt-1">{{ contact.reference_number }} &middot; {{ formatDate(contact.created_at) }}</p>
              </div>
              <span
                :class="statusClass(contact.status)"
                class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
              >
                {{ statusLabel(contact.status) }}
              </span>
            </div>
            <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ contact.message }}</p>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { locale } = useI18n({ useScope: 'global' })

const messages = {
  en: {
    title: 'Support',
    form_title: 'Contact Support',
    form_subtitle: 'Describe your issue and our team will get back to you within 48 hours.',
    name: 'Name',
    name_placeholder: 'Your full name',
    email: 'Email',
    email_placeholder: 'your@email.com',
    subject: 'Subject',
    subject_placeholder: 'Brief description of your issue',
    category: 'Category',
    select_category: 'Select a category',
    priority: 'Priority',
    select_priority: 'Select priority',
    message: 'Message',
    message_placeholder: 'Please describe your issue in detail (min 20 characters)...',
    attachments: 'Attachments',
    attachments_hint: 'JPG, PNG, GIF, or PDF. Max 5 files, 5MB each.',
    submit: 'Send Message',
    success_title: 'Message Sent!',
    success_message: 'Your support request has been submitted successfully.',
    reference: 'Reference Number',
    response_time: 'We typically respond within 48 hours during business days.',
    new_request: 'Submit Another Request',
    previous: 'Previous Requests',
    status_new: 'New',
    status_in_progress: 'In Progress',
    status_resolved: 'Resolved',
    cat_technical: 'Technical Issue',
    cat_billing: 'Billing Question',
    cat_feature: 'Feature Request',
    cat_general: 'General Inquiry',
    pri_low: 'Low',
    pri_medium: 'Medium',
    pri_high: 'High',
    pri_urgent: 'Urgent',
  },
  mk: {
    title: 'Поддршка',
    form_title: 'Контактирајте ја поддршката',
    form_subtitle: 'Опишете го проблемот и нашиот тим ќе ви одговори во рок од 48 часа.',
    name: 'Име',
    name_placeholder: 'Вашето целосно име',
    email: 'Е-пошта',
    email_placeholder: 'vasha@email.com',
    subject: 'Предмет',
    subject_placeholder: 'Краток опис на вашиот проблем',
    category: 'Категорија',
    select_category: 'Изберете категорија',
    priority: 'Приоритет',
    select_priority: 'Изберете приоритет',
    message: 'Порака',
    message_placeholder: 'Ве молиме опишете го проблемот детално (мин 20 карактери)...',
    attachments: 'Прилози',
    attachments_hint: 'JPG, PNG, GIF или PDF. Максимум 5 датотеки, 5MB секоја.',
    submit: 'Испрати порака',
    success_title: 'Пораката е испратена!',
    success_message: 'Вашето барање за поддршка е успешно поднесено.',
    reference: 'Референтен број',
    response_time: 'Обично одговараме во рок од 48 часа за време на работни денови.',
    new_request: 'Поднеси ново барање',
    previous: 'Претходни барања',
    status_new: 'Ново',
    status_in_progress: 'Во тек',
    status_resolved: 'Решено',
    cat_technical: 'Технички проблем',
    cat_billing: 'Прашање за наплата',
    cat_feature: 'Барање за функционалност',
    cat_general: 'Општо прашање',
    pri_low: 'Низок',
    pri_medium: 'Среден',
    pri_high: 'Висок',
    pri_urgent: 'Итно',
  },
  sq: {
    title: 'Mbeshtetja',
    form_title: 'Kontaktoni Mbeshtetjen',
    form_subtitle: 'Pershkruani problemin tuaj dhe ekipi yne do t\'ju pergjigjet brenda 48 oreve.',
    name: 'Emri',
    name_placeholder: 'Emri juaj i plote',
    email: 'Email',
    email_placeholder: 'juaji@email.com',
    subject: 'Subjekti',
    subject_placeholder: 'Pershkrim i shkurter i problemit',
    category: 'Kategoria',
    select_category: 'Zgjidhni kategorine',
    priority: 'Prioriteti',
    select_priority: 'Zgjidhni prioritetin',
    message: 'Mesazhi',
    message_placeholder: 'Ju lutem pershkruani problemin ne detaje (min 20 karaktere)...',
    attachments: 'Bashkengjitjet',
    attachments_hint: 'JPG, PNG, GIF ose PDF. Maks 5 skedare, 5MB secili.',
    submit: 'Dergo Mesazhin',
    success_title: 'Mesazhi u Dergua!',
    success_message: 'Kerkesa juaj per mbeshtetje u dergua me sukses.',
    reference: 'Numri i References',
    response_time: 'Zakonisht pergjigjemi brenda 48 oreve gjate diteve te punes.',
    new_request: 'Dergo Kerkese Tjeter',
    previous: 'Kerkesat e Meparshme',
    status_new: 'E re',
    status_in_progress: 'Ne progres',
    status_resolved: 'E zgjidhur',
    cat_technical: 'Problem Teknik',
    cat_billing: 'Pyetje Faturimi',
    cat_feature: 'Kerkese Funksionaliteti',
    cat_general: 'Pyetje e Pergjithshme',
    pri_low: 'I ulet',
    pri_medium: 'Mesatar',
    pri_high: 'I larte',
    pri_urgent: 'Urgjent',
  },
  tr: {
    title: 'Destek',
    form_title: 'Destek ile Iletisim',
    form_subtitle: 'Sorununuzu aciklain, ekibimiz 48 saat icinde size donecektir.',
    name: 'Ad',
    name_placeholder: 'Tam adiniz',
    email: 'E-posta',
    email_placeholder: 'sizin@email.com',
    subject: 'Konu',
    subject_placeholder: 'Sorununuzun kisa aciklamasi',
    category: 'Kategori',
    select_category: 'Kategori secin',
    priority: 'Oncelik',
    select_priority: 'Oncelik secin',
    message: 'Mesaj',
    message_placeholder: 'Lutfen sorununuzu ayrintili olarak aciklayin (min 20 karakter)...',
    attachments: 'Ekler',
    attachments_hint: 'JPG, PNG, GIF veya PDF. Maksimum 5 dosya, her biri 5MB.',
    submit: 'Mesaj Gonder',
    success_title: 'Mesaj Gonderildi!',
    success_message: 'Destek talebiniz basariyla gonderildi.',
    reference: 'Referans Numarasi',
    response_time: 'Genellikle is gunlerinde 48 saat icinde yanitliyoruz.',
    new_request: 'Yeni Talep Gonder',
    previous: 'Onceki Talepler',
    status_new: 'Yeni',
    status_in_progress: 'Devam Ediyor',
    status_resolved: 'Cozuldu',
    cat_technical: 'Teknik Sorun',
    cat_billing: 'Faturalama Sorusu',
    cat_feature: 'Ozellik Istegi',
    cat_general: 'Genel Soru',
    pri_low: 'Dusuk',
    pri_medium: 'Orta',
    pri_high: 'Yuksek',
    pri_urgent: 'Acil',
  },
}

const t = (key) => {
  const lang = locale.value || 'mk'
  return messages[lang]?.[key] || messages['en']?.[key] || key
}

const form = ref({
  name: '',
  email: '',
  subject: '',
  category: null,
  priority: null,
  message: '',
  attachments: [],
})

const errors = ref({})
const serverError = ref('')
const isSubmitting = ref(false)
const submitted = ref(false)
const referenceNumber = ref('')
const previousContacts = ref([])
const fileInput = ref(null)

const categoryOptions = computed(() => [
  { id: 'technical', name: t('cat_technical') },
  { id: 'billing', name: t('cat_billing') },
  { id: 'feature', name: t('cat_feature') },
  { id: 'general', name: t('cat_general') },
])

const priorityOptions = computed(() => [
  { id: 'low', name: t('pri_low') },
  { id: 'medium', name: t('pri_medium') },
  { id: 'high', name: t('pri_high') },
  { id: 'urgent', name: t('pri_urgent') },
])

const handleFiles = (e) => {
  const files = Array.from(e.target.files)
  const total = form.value.attachments.length + files.length
  if (total > 5) {
    form.value.attachments = [...form.value.attachments, ...files].slice(0, 5)
  } else {
    form.value.attachments = [...form.value.attachments, ...files]
  }
}

const removeFile = (index) => {
  form.value.attachments.splice(index, 1)
}

const submitForm = async () => {
  errors.value = {}
  serverError.value = ''

  // Client-side validation
  if (!form.value.name) errors.value.name = 'Required'
  if (!form.value.email) errors.value.email = 'Required'
  if (!form.value.subject) errors.value.subject = 'Required'
  if (!form.value.category) errors.value.category = 'Required'
  if (!form.value.priority) errors.value.priority = 'Required'
  if (!form.value.message || form.value.message.length < 20) errors.value.message = 'Min 20 characters'

  if (Object.keys(errors.value).length) return

  isSubmitting.value = true

  try {
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('email', form.value.email)
    formData.append('subject', form.value.subject)
    formData.append('category', form.value.category)
    formData.append('priority', form.value.priority)
    formData.append('message', form.value.message)

    form.value.attachments.forEach((file) => {
      formData.append('attachments[]', file)
    })

    const { data } = await axios.post('/support/contact', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    referenceNumber.value = data.reference_number
    submitted.value = true
    loadPreviousContacts()
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      Object.keys(serverErrors).forEach((key) => {
        errors.value[key] = serverErrors[key][0]
      })
    } else {
      serverError.value = err.response?.data?.message || 'Something went wrong. Please try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    subject: '',
    category: null,
    priority: null,
    message: '',
    attachments: [],
  }
  submitted.value = false
  referenceNumber.value = ''
  if (fileInput.value) fileInput.value.value = ''
}

const loadPreviousContacts = async () => {
  try {
    const { data } = await axios.get('/support/contact')
    previousContacts.value = data.data || []
  } catch {
    // Silently fail — not critical
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

const statusClass = (status) => {
  const classes = {
    new: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
  }
  return classes[status] || classes.new
}

const statusLabel = (status) => {
  const map = { new: 'status_new', in_progress: 'status_in_progress', resolved: 'status_resolved' }
  return t(map[status] || 'status_new')
}

// Pre-fill name/email from current user if available
onMounted(async () => {
  try {
    const { data } = await axios.get('/me')
    if (data?.data) {
      form.value.name = data.data.name || ''
      form.value.email = data.data.email || ''
    } else if (data?.name) {
      form.value.name = data.name || ''
      form.value.email = data.email || ''
    }
  } catch {
    // Not critical
  }
  loadPreviousContacts()
})
</script>
