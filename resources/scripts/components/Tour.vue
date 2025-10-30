<template>
  <div
    v-if="isVisible"
    class="fixed inset-0 bg-black bg-opacity-50 z-[9999]"
    @click="handleOverlayClick"
  >
    <!-- Tour Spotlight -->
    <div
      v-if="currentStep && currentStep.element"
      class="absolute bg-white rounded-lg shadow-xl border border-gray-200 p-6 max-w-sm"
      :style="tooltipPosition"
    >
      <!-- Step Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ currentStep.title }}
        </h3>
        <button
          @click="closeTour"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
          </svg>
        </button>
      </div>

      <!-- Step Content -->
      <p class="text-gray-600 mb-6">{{ currentStep.description }}</p>

      <!-- Step Navigation -->
      <div class="flex items-center justify-between">
        <div class="flex space-x-1">
          <div
            v-for="(step, index) in tourSteps"
            :key="index"
            class="w-2 h-2 rounded-full transition-colors"
            :class="index === currentStepIndex ? 'bg-indigo-600' : 'bg-gray-300'"
          ></div>
        </div>

        <div class="flex space-x-3">
          <button
            v-if="currentStepIndex > 0"
            @click="previousStep"
            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 transition-colors"
          >
            {{ t('tour.previous') }}
          </button>
          <button
            v-if="currentStepIndex < tourSteps.length - 1"
            @click="nextStep"
            class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition-colors"
          >
            {{ t('tour.next') }}
          </button>
          <button
            v-else
            @click="completeTour"
            class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition-colors"
          >
            {{ t('tour.complete') }}
          </button>
          <button
            @click="skipTour"
            class="px-3 py-1 text-sm text-gray-500 hover:text-gray-700 transition-colors"
          >
            {{ t('tour.skip_tour') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Welcome Modal for New Users -->
    <div
      v-if="showWelcome"
      class="fixed inset-0 flex items-center justify-center z-[10000]"
    >
      <div class="bg-white rounded-lg shadow-2xl p-8 max-w-md mx-4">
        <div class="text-center">
          <!-- Logo or Icon -->
          <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>

          <h2 class="text-2xl font-bold text-gray-900 mb-4">
            {{ t('tour.welcome_title') }}
          </h2>
          <p class="text-gray-600 mb-6">
            {{ t('tour.welcome_message') }}
          </p>

          <div class="flex space-x-4 justify-center">
            <button
              @click="startTour"
              class="px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors font-medium"
            >
              {{ t('tour.start_tour') }}
            </button>
            <button
              @click="skipWelcome"
              class="px-6 py-3 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors"
            >
              {{ t('tour.skip') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

// Props
const props = defineProps({
  autoStart: {
    type: Boolean,
    default: false
  },
  steps: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['tour-completed', 'tour-skipped', 'step-changed'])

// Composables
const { t } = useI18n()

// Reactive state
const isVisible = ref(false)
const showWelcome = ref(false)
const currentStepIndex = ref(0)
const tooltipPosition = ref({ top: '50px', left: '50px' })

// Default tour steps for Facturino
const defaultSteps = computed(() => [
  {
    element: '[data-tour="dashboard"]',
    title: t('tour.dashboard_overview_title'),
    description: t('tour.dashboard_overview_description')
  },
  {
    element: '[data-tour="customers"]',
    title: t('tour.customer_management_title'),
    description: t('tour.customer_management_description')
  },
  {
    element: '[data-tour="invoices"]',
    title: t('tour.invoice_creation_title'),
    description: t('tour.invoice_creation_description')
  },
  {
    element: '[data-tour="migration"]',
    title: t('tour.migration_wizard_title'),
    description: t('tour.migration_wizard_description')
  },
  {
    element: '[data-tour="banking"]',
    title: t('tour.banking_integration_title'),
    description: t('tour.banking_integration_description')
  },
  {
    element: '[data-tour="tax-returns"]',
    title: t('tour.tax_compliance_title'),
    description: t('tour.tax_compliance_description')
  }
])

// Computed properties
const tourSteps = computed(() => props.steps.length > 0 ? props.steps : defaultSteps.value)
const currentStep = computed(() => tourSteps.value[currentStepIndex.value])

// Check if user is new (hasn't seen the tour)
const isNewUser = () => {
  return !localStorage.getItem('facturino_tour_completed')
}

// Position tooltip near target element
const positionTooltip = async () => {
  if (!currentStep.value?.element) return

  await nextTick()
  
  const targetElement = document.querySelector(currentStep.value.element)
  if (!targetElement) {
    console.warn(`Tour target element not found: ${currentStep.value.element}`)
    return
  }

  const rect = targetElement.getBoundingClientRect()
  const tooltipWidth = 320 // Approximate tooltip width
  const tooltipHeight = 200 // Approximate tooltip height
  
  let top = rect.bottom + 10
  let left = rect.left + (rect.width / 2) - (tooltipWidth / 2)

  // Adjust if tooltip goes off screen
  if (left < 10) left = 10
  if (left + tooltipWidth > window.innerWidth - 10) {
    left = window.innerWidth - tooltipWidth - 10
  }
  
  if (top + tooltipHeight > window.innerHeight - 10) {
    top = rect.top - tooltipHeight - 10
  }

  tooltipPosition.value = {
    top: `${top}px`,
    left: `${left}px`
  }

  // Highlight target element
  highlightElement(targetElement)
}

// Highlight target element
const highlightElement = (element) => {
  // Remove previous highlights
  document.querySelectorAll('.tour-highlight').forEach(el => {
    el.classList.remove('tour-highlight')
  })

  // Add highlight to current element
  element.classList.add('tour-highlight')
  element.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

// Tour navigation methods
const nextStep = async () => {
  if (currentStepIndex.value < tourSteps.value.length - 1) {
    currentStepIndex.value++
    await positionTooltip()
    emit('step-changed', currentStepIndex.value)
  }
}

const previousStep = async () => {
  if (currentStepIndex.value > 0) {
    currentStepIndex.value--
    await positionTooltip()
    emit('step-changed', currentStepIndex.value)
  }
}

const startTour = async () => {
  showWelcome.value = false
  isVisible.value = true
  currentStepIndex.value = 0
  await positionTooltip()
}

const completeTour = () => {
  localStorage.setItem('facturino_tour_completed', 'true')
  closeTour()
  emit('tour-completed')
}

const skipTour = () => {
  localStorage.setItem('facturino_tour_completed', 'true')
  closeTour()
  emit('tour-skipped')
}

const skipWelcome = () => {
  localStorage.setItem('facturino_tour_completed', 'true')
  showWelcome.value = false
  isVisible.value = false
  emit('tour-skipped')
}

const closeTour = () => {
  isVisible.value = false
  showWelcome.value = false
  // Remove highlights
  document.querySelectorAll('.tour-highlight').forEach(el => {
    el.classList.remove('tour-highlight')
  })
}

const handleOverlayClick = (event) => {
  // Only close if clicking on the overlay, not the tooltip
  if (event.target === event.currentTarget) {
    closeTour()
  }
}

// Initialize tour
const initTour = () => {
  if (isNewUser() && props.autoStart) {
    showWelcome.value = true
    isVisible.value = true
  }
}

// Handle window resize
const handleResize = () => {
  if (isVisible.value && !showWelcome.value) {
    positionTooltip()
  }
}

// Lifecycle hooks
onMounted(() => {
  initTour()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  // Clean up highlights
  document.querySelectorAll('.tour-highlight').forEach(el => {
    el.classList.remove('tour-highlight')
  })
})

// Expose public methods
defineExpose({
  startTour,
  completeTour,
  skipTour,
  isVisible: () => isVisible.value
})
</script>

<style scoped>
/* Tour highlight effect */
:global(.tour-highlight) {
  position: relative;
  z-index: 9998;
  box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.4), 0 0 0 8px rgba(79, 70, 229, 0.2);
  border-radius: 4px;
  transition: box-shadow 0.3s ease;
}

/* Ensure tooltip is above highlighted elements */
.fixed {
  z-index: 9999;
}

/* Animation for smooth transitions */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>

