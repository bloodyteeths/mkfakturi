<template>
  <teleport to="body">
    <div v-if="isActive && currentTourStep !== null" class="fixed inset-0 z-50">
      <!-- Overlay with highlight -->
      <div class="absolute inset-0 bg-black/50 transition-all duration-300"></div>

      <!-- Highlighted Element Spotlight -->
      <div
        v-if="highlightPosition"
        class="absolute border-4 border-primary-500 rounded-lg shadow-2xl transition-all duration-300 pointer-events-none"
        :style="{
          top: highlightPosition.top + 'px',
          left: highlightPosition.left + 'px',
          width: highlightPosition.width + 'px',
          height: highlightPosition.height + 'px',
          boxShadow: '0 0 0 9999px rgba(0, 0, 0, 0.5), 0 0 20px rgba(59, 130, 246, 0.5)',
        }"
      ></div>

      <!-- Tooltip/Popover -->
      <div
        v-if="tooltipPosition"
        class="absolute bg-white rounded-lg shadow-2xl max-w-md z-[60] transition-all duration-300"
        :style="{
          top: tooltipPosition.top + 'px',
          left: tooltipPosition.left + 'px',
        }"
      >
        <!-- Arrow -->
        <div
          class="absolute w-4 h-4 bg-white transform rotate-45"
          :style="tooltipArrowStyle"
        ></div>

        <!-- Content -->
        <div class="relative bg-white rounded-lg overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-primary-600 to-purple-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center text-white">
                <BaseIcon :name="currentStep.icon" class="w-6 h-6 mr-3" />
                <h3 class="font-semibold text-lg">{{ currentStep.title }}</h3>
              </div>
              <button
                @click="endTour"
                class="text-white/80 hover:text-white transition-colors"
              >
                <BaseIcon name="XMarkIcon" class="w-5 h-5" />
              </button>
            </div>
          </div>

          <!-- Body -->
          <div class="px-6 py-4">
            <p class="text-gray-700 leading-relaxed">{{ currentStep.content }}</p>

            <!-- Action hint if available -->
            <div v-if="currentStep.action" class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3">
              <p class="text-sm text-blue-900 flex items-start">
                <BaseIcon name="CursorArrowRaysIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                <span>{{ currentStep.action }}</span>
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <!-- Progress -->
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">
                  {{ $t('imports.tour_step_counter', { current: currentTourStep + 1, total: tourSteps.length }) }}
                </span>
                <div class="flex space-x-1">
                  <div
                    v-for="(step, index) in tourSteps"
                    :key="index"
                    :class="[
                      'w-2 h-2 rounded-full transition-all',
                      index === currentTourStep ? 'bg-primary-600 w-6' : 'bg-gray-300',
                    ]"
                  ></div>
                </div>
              </div>

              <!-- Navigation -->
              <div class="flex space-x-2">
                <button
                  v-if="currentTourStep > 0"
                  @click="previousStep"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                >
                  {{ $t('general.previous') }}
                </button>
                <button
                  v-if="currentTourStep < tourSteps.length - 1"
                  @click="nextStep"
                  class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 transition-colors flex items-center"
                >
                  {{ $t('general.next') }}
                  <BaseIcon name="ArrowRightIcon" class="w-4 h-4 ml-2" />
                </button>
                <button
                  v-else
                  @click="endTour"
                  class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors flex items-center"
                >
                  {{ $t('imports.finish_tour') }}
                  <BaseIcon name="CheckIcon" class="w-4 h-4 ml-2" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'

const { t } = useI18n()

const props = defineProps({
  isActive: {
    type: Boolean,
    default: false,
  },
  wizardStep: {
    type: Number,
    default: 1,
  },
})

const emit = defineEmits(['update:isActive', 'tourComplete'])

const currentTourStep = ref(null)
const highlightPosition = ref(null)
const tooltipPosition = ref(null)
const tooltipArrowStyle = ref({})

// Define tour steps based on wizard step
const tourSteps = computed(() => {
  if (props.wizardStep === 1) {
    return [
      {
        target: '.import-wizard-header',
        title: t('imports.tour_welcome_title'),
        content: t('imports.tour_welcome_content'),
        icon: 'SparklesIcon',
        action: null,
      },
      {
        target: '.upload-area',
        title: t('imports.tour_upload_title'),
        content: t('imports.tour_upload_content'),
        icon: 'CloudArrowUpIcon',
        action: t('imports.tour_upload_action'),
      },
      {
        target: '.template-download-section',
        title: t('imports.tour_template_title'),
        content: t('imports.tour_template_content'),
        icon: 'DocumentArrowDownIcon',
        action: t('imports.tour_template_action'),
      },
      {
        target: '.tips-section',
        title: t('imports.tour_tips_title'),
        content: t('imports.tour_tips_content'),
        icon: 'LightBulbIcon',
        action: null,
      },
      {
        target: '.progress-sidebar',
        title: t('imports.tour_progress_title'),
        content: t('imports.tour_progress_content'),
        icon: 'ChartBarIcon',
        action: null,
      },
    ]
  } else if (props.wizardStep === 2) {
    return [
      {
        target: '.field-mapping-table',
        title: t('imports.tour_mapping_title'),
        content: t('imports.tour_mapping_content'),
        icon: 'ArrowsRightLeftIcon',
        action: t('imports.tour_mapping_action'),
      },
      {
        target: '.auto-detect-button',
        title: t('imports.tour_autodetect_title'),
        content: t('imports.tour_autodetect_content'),
        icon: 'SparklesIcon',
        action: t('imports.tour_autodetect_action'),
      },
    ]
  } else if (props.wizardStep === 3) {
    return [
      {
        target: '.validation-results',
        title: t('imports.tour_validation_title'),
        content: t('imports.tour_validation_content'),
        icon: 'ShieldCheckIcon',
        action: null,
      },
      {
        target: '.error-list',
        title: t('imports.tour_errors_title'),
        content: t('imports.tour_errors_content'),
        icon: 'ExclamationCircleIcon',
        action: t('imports.tour_errors_action'),
      },
    ]
  } else if (props.wizardStep === 4) {
    return [
      {
        target: '.commit-summary',
        title: t('imports.tour_commit_title'),
        content: t('imports.tour_commit_content'),
        icon: 'RocketLaunchIcon',
        action: null,
      },
      {
        target: '.commit-button',
        title: t('imports.tour_final_title'),
        content: t('imports.tour_final_content'),
        icon: 'CheckCircleIcon',
        action: t('imports.tour_final_action'),
      },
    ]
  }
  return []
})

const currentStep = computed(() => {
  if (currentTourStep.value !== null && tourSteps.value[currentTourStep.value]) {
    return tourSteps.value[currentTourStep.value]
  }
  return null
})

const startTour = () => {
  currentTourStep.value = 0
  nextTick(() => {
    updateHighlight()
  })
}

const nextStep = () => {
  if (currentTourStep.value < tourSteps.value.length - 1) {
    currentTourStep.value++
    nextTick(() => {
      updateHighlight()
    })
  }
}

const previousStep = () => {
  if (currentTourStep.value > 0) {
    currentTourStep.value--
    nextTick(() => {
      updateHighlight()
    })
  }
}

const endTour = () => {
  currentTourStep.value = null
  highlightPosition.value = null
  tooltipPosition.value = null
  emit('update:isActive', false)
  emit('tourComplete')

  // Store that tour has been completed
  localStorage.setItem('migration-wizard-tour-completed', 'true')
}

const updateHighlight = () => {
  if (!currentStep.value || !currentStep.value.target) return

  const element = document.querySelector(currentStep.value.target)
  if (!element) {
    console.warn('Tour target not found:', currentStep.value.target)
    return
  }

  const rect = element.getBoundingClientRect()
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop
  const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft

  // Scroll element into view
  element.scrollIntoView({ behavior: 'smooth', block: 'center' })

  setTimeout(() => {
    const updatedRect = element.getBoundingClientRect()

    // Set highlight position
    highlightPosition.value = {
      top: updatedRect.top + scrollTop - 8,
      left: updatedRect.left + scrollLeft - 8,
      width: updatedRect.width + 16,
      height: updatedRect.height + 16,
    }

    // Calculate tooltip position (try to position it to the side or below)
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight
    const tooltipWidth = 384 // max-w-md in pixels
    const tooltipHeight = 300 // approximate height

    let tooltipTop = updatedRect.top + scrollTop
    let tooltipLeft = updatedRect.right + scrollLeft + 20

    // If tooltip goes off right edge, position it on the left
    if (tooltipLeft + tooltipWidth > viewportWidth) {
      tooltipLeft = updatedRect.left + scrollLeft - tooltipWidth - 20
    }

    // If still off screen, position below
    if (tooltipLeft < 0) {
      tooltipLeft = updatedRect.left + scrollLeft
      tooltipTop = updatedRect.bottom + scrollTop + 20
    }

    // If off bottom, position above
    if (tooltipTop + tooltipHeight > viewportHeight + scrollTop) {
      tooltipTop = updatedRect.top + scrollTop - tooltipHeight - 20
    }

    tooltipPosition.value = {
      top: tooltipTop,
      left: tooltipLeft,
    }

    // Calculate arrow position
    if (tooltipLeft > updatedRect.right + scrollLeft) {
      // Arrow on left side
      tooltipArrowStyle.value = {
        left: '-8px',
        top: '60px',
      }
    } else if (tooltipLeft + tooltipWidth < updatedRect.left + scrollLeft) {
      // Arrow on right side
      tooltipArrowStyle.value = {
        right: '-8px',
        top: '60px',
      }
    } else {
      // Arrow on top
      tooltipArrowStyle.value = {
        top: '-8px',
        left: '24px',
      }
    }
  }, 500)
}

const handleResize = () => {
  if (currentTourStep.value !== null) {
    updateHighlight()
  }
}

watch(() => props.isActive, (newValue) => {
  if (newValue) {
    startTour()
  } else {
    currentTourStep.value = null
  }
})

watch(() => props.wizardStep, () => {
  if (props.isActive) {
    startTour()
  }
})

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
// CLAUDE-CHECKPOINT
</script>
