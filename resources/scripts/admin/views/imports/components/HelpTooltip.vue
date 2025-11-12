<template>
  <div class="inline-flex items-center relative" @mouseenter="showTooltip" @mouseleave="hideTooltip">
    <button
      type="button"
      class="inline-flex items-center justify-center transition-colors focus:outline-none"
      :class="iconClass"
      @click="toggleTooltip"
    >
      <BaseIcon :name="icon" :class="iconSizeClass" />
    </button>

    <!-- Tooltip Popover -->
    <teleport to="body">
      <div
        v-if="isVisible"
        ref="tooltipRef"
        class="fixed z-50 max-w-sm transition-opacity duration-200"
        :class="{ 'opacity-0': !isFullyVisible, 'opacity-100': isFullyVisible }"
        :style="tooltipStyle"
        @mouseenter="keepTooltipOpen"
        @mouseleave="hideTooltip"
      >
        <div class="bg-gray-900 text-white rounded-lg shadow-xl overflow-hidden">
          <!-- Arrow -->
          <div
            class="absolute w-3 h-3 bg-gray-900 transform rotate-45"
            :style="arrowStyle"
          ></div>

          <!-- Content -->
          <div class="relative p-4">
            <div v-if="title" class="flex items-center justify-between mb-2">
              <h5 class="font-semibold text-sm">{{ title }}</h5>
              <button
                v-if="showCloseButton"
                @click="hideTooltip"
                class="text-white/70 hover:text-white transition-colors ml-2"
              >
                <BaseIcon name="XMarkIcon" class="w-4 h-4" />
              </button>
            </div>

            <div class="text-sm leading-relaxed" :class="{ 'text-gray-300': title }">
              <slot>{{ content }}</slot>
            </div>

            <!-- Link if provided -->
            <a
              v-if="link"
              :href="link"
              target="_blank"
              class="inline-flex items-center mt-3 text-primary-400 hover:text-primary-300 text-sm font-medium"
            >
              {{ linkText || $t('imports.learn_more') }}
              <BaseIcon name="ArrowTopRightOnSquareIcon" class="w-4 h-4 ml-1" />
            </a>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'

const { t } = useI18n()

const props = defineProps({
  content: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: 'QuestionMarkCircleIcon',
  },
  iconSize: {
    type: String,
    default: 'sm', // sm, md, lg
  },
  iconClass: {
    type: String,
    default: 'text-gray-400 hover:text-gray-600',
  },
  placement: {
    type: String,
    default: 'top', // top, bottom, left, right
  },
  trigger: {
    type: String,
    default: 'hover', // hover, click
  },
  link: {
    type: String,
    default: '',
  },
  linkText: {
    type: String,
    default: '',
  },
  showCloseButton: {
    type: Boolean,
    default: false,
  },
})

const isVisible = ref(false)
const isFullyVisible = ref(false)
const tooltipRef = ref(null)
const buttonRef = ref(null)
const tooltipStyle = ref({})
const arrowStyle = ref({})
const hoverTimeout = ref(null)
const keepOpenTimeout = ref(null)

const iconSizeClass = computed(() => {
  const sizes = {
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6',
  }
  return sizes[props.iconSize] || sizes.sm
})

const showTooltip = () => {
  if (props.trigger !== 'hover') return

  clearTimeout(hoverTimeout.value)
  hoverTimeout.value = setTimeout(() => {
    isVisible.value = true
    nextTick(() => {
      calculatePosition()
      setTimeout(() => {
        isFullyVisible.value = true
      }, 10)
    })
  }, 200)
}

const hideTooltip = () => {
  if (props.trigger !== 'hover') return

  clearTimeout(hoverTimeout.value)
  clearTimeout(keepOpenTimeout.value)

  keepOpenTimeout.value = setTimeout(() => {
    isFullyVisible.value = false
    setTimeout(() => {
      isVisible.value = false
    }, 200)
  }, 100)
}

const toggleTooltip = (event) => {
  if (props.trigger !== 'click') return

  event.stopPropagation()

  if (isVisible.value) {
    isFullyVisible.value = false
    setTimeout(() => {
      isVisible.value = false
    }, 200)
  } else {
    isVisible.value = true
    nextTick(() => {
      calculatePosition()
      setTimeout(() => {
        isFullyVisible.value = true
      }, 10)
    })
  }
}

const keepTooltipOpen = () => {
  clearTimeout(keepOpenTimeout.value)
}

const calculatePosition = () => {
  const button = event?.target?.closest('button') || document.querySelector('.help-tooltip-trigger')
  if (!button) return

  const buttonRect = button.getBoundingClientRect()
  const tooltipElement = tooltipRef.value
  if (!tooltipElement) return

  const tooltipRect = tooltipElement.getBoundingClientRect()
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop
  const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft

  let top = 0
  let left = 0
  let arrowTop = ''
  let arrowLeft = ''
  let arrowRight = ''
  let arrowBottom = ''

  const spacing = 12 // Gap between button and tooltip
  const arrowSize = 6

  switch (props.placement) {
    case 'top':
      top = buttonRect.top + scrollTop - tooltipRect.height - spacing
      left = buttonRect.left + scrollLeft + (buttonRect.width / 2) - (tooltipRect.width / 2)
      arrowBottom = `-${arrowSize}px`
      arrowLeft = '50%'
      arrowStyle.value = {
        bottom: arrowBottom,
        left: arrowLeft,
        transform: 'translateX(-50%)',
      }
      break

    case 'bottom':
      top = buttonRect.bottom + scrollTop + spacing
      left = buttonRect.left + scrollLeft + (buttonRect.width / 2) - (tooltipRect.width / 2)
      arrowTop = `-${arrowSize}px`
      arrowLeft = '50%'
      arrowStyle.value = {
        top: arrowTop,
        left: arrowLeft,
        transform: 'translateX(-50%)',
      }
      break

    case 'left':
      top = buttonRect.top + scrollTop + (buttonRect.height / 2) - (tooltipRect.height / 2)
      left = buttonRect.left + scrollLeft - tooltipRect.width - spacing
      arrowTop = '50%'
      arrowRight = `-${arrowSize}px`
      arrowStyle.value = {
        top: arrowTop,
        right: arrowRight,
        transform: 'translateY(-50%)',
      }
      break

    case 'right':
      top = buttonRect.top + scrollTop + (buttonRect.height / 2) - (tooltipRect.height / 2)
      left = buttonRect.right + scrollLeft + spacing
      arrowTop = '50%'
      arrowLeft = `-${arrowSize}px`
      arrowStyle.value = {
        top: arrowTop,
        left: arrowLeft,
        transform: 'translateY(-50%)',
      }
      break
  }

  // Keep tooltip within viewport
  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight

  if (left < 10) left = 10
  if (left + tooltipRect.width > viewportWidth - 10) {
    left = viewportWidth - tooltipRect.width - 10
  }
  if (top < 10) top = 10
  if (top + tooltipRect.height > viewportHeight + scrollTop - 10) {
    top = viewportHeight + scrollTop - tooltipRect.height - 10
  }

  tooltipStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
  }
}

const handleClickOutside = (event) => {
  if (props.trigger !== 'click') return

  const button = event.target.closest('button')
  const tooltip = tooltipRef.value

  if (!button && !tooltip?.contains(event.target)) {
    isFullyVisible.value = false
    setTimeout(() => {
      isVisible.value = false
    }, 200)
  }
}

onUnmounted(() => {
  clearTimeout(hoverTimeout.value)
  clearTimeout(keepOpenTimeout.value)
  document.removeEventListener('click', handleClickOutside)
})

// Add click outside listener if trigger is click
if (props.trigger === 'click') {
  document.addEventListener('click', handleClickOutside)
}
// CLAUDE-CHECKPOINT
</script>
