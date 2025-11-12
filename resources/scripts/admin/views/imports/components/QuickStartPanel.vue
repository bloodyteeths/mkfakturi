<template>
  <div class="bg-gradient-to-br from-primary-50 via-purple-50 to-blue-50 border-2 border-primary-200 rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-primary-600 to-purple-600 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center text-white">
          <BaseIcon name="RocketLaunchIcon" class="w-6 h-6 mr-3" />
          <h3 class="font-bold text-xl">{{ $t('imports.quick_start_guide') }}</h3>
        </div>
        <button
          v-if="!isFirstTime"
          @click="$emit('close')"
          class="text-white/80 hover:text-white transition-colors"
        >
          <BaseIcon name="XMarkIcon" class="w-5 h-5" />
        </button>
      </div>
    </div>

    <div class="p-6 space-y-6">
      <!-- Goal Section -->
      <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-md mb-4">
          <BaseIcon name="FlagIcon" class="w-8 h-8 text-primary-600" />
        </div>
        <h4 class="text-2xl font-bold text-gray-900 mb-2">
          {{ $t('imports.quickstart_goal') }}
        </h4>
        <p class="text-lg text-gray-600">
          {{ $t('imports.quickstart_goal_description') }}
        </p>
      </div>

      <!-- Visual Progress Steps -->
      <div class="bg-white rounded-lg p-6 shadow-sm">
        <h5 class="font-semibold text-gray-900 mb-4 text-center">
          {{ $t('imports.quickstart_steps_title') }}
        </h5>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Step 1 -->
          <div class="text-center">
            <div class="relative">
              <div class="w-16 h-16 mx-auto bg-primary-100 rounded-full flex items-center justify-center mb-3">
                <BaseIcon name="CloudArrowUpIcon" class="w-8 h-8 text-primary-600" />
              </div>
              <div class="absolute top-8 left-full w-full h-0.5 bg-gray-300 hidden md:block" style="left: calc(50% + 32px); width: calc(100% - 64px);">
                <div class="absolute -right-2 -top-1.5 w-0 h-0 border-l-8 border-l-gray-300 border-t-4 border-t-transparent border-b-4 border-b-transparent"></div>
              </div>
            </div>
            <p class="font-medium text-gray-900 mb-1">{{ $t('imports.step_1_upload') }}</p>
            <p class="text-xs text-gray-600">{{ $t('imports.step_1_upload_desc') }}</p>
          </div>

          <!-- Step 2 -->
          <div class="text-center">
            <div class="relative">
              <div class="w-16 h-16 mx-auto bg-purple-100 rounded-full flex items-center justify-center mb-3">
                <BaseIcon name="ArrowsRightLeftIcon" class="w-8 h-8 text-purple-600" />
              </div>
              <div class="absolute top-8 left-full w-full h-0.5 bg-gray-300 hidden md:block" style="left: calc(50% + 32px); width: calc(100% - 64px);">
                <div class="absolute -right-2 -top-1.5 w-0 h-0 border-l-8 border-l-gray-300 border-t-4 border-t-transparent border-b-4 border-b-transparent"></div>
              </div>
            </div>
            <p class="font-medium text-gray-900 mb-1">{{ $t('imports.step_2_map') }}</p>
            <p class="text-xs text-gray-600">{{ $t('imports.step_2_map_desc') }}</p>
          </div>

          <!-- Step 3 -->
          <div class="text-center">
            <div class="relative">
              <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-3">
                <BaseIcon name="ShieldCheckIcon" class="w-8 h-8 text-blue-600" />
              </div>
              <div class="absolute top-8 left-full w-full h-0.5 bg-gray-300 hidden md:block" style="left: calc(50% + 32px); width: calc(100% - 64px);">
                <div class="absolute -right-2 -top-1.5 w-0 h-0 border-l-8 border-l-gray-300 border-t-4 border-t-transparent border-b-4 border-b-transparent"></div>
              </div>
            </div>
            <p class="font-medium text-gray-900 mb-1">{{ $t('imports.step_3_validate') }}</p>
            <p class="text-xs text-gray-600">{{ $t('imports.step_3_validate_desc') }}</p>
          </div>

          <!-- Step 4 -->
          <div class="text-center">
            <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-3">
              <BaseIcon name="CheckCircleIcon" class="w-8 h-8 text-green-600" />
            </div>
            <p class="font-medium text-gray-900 mb-1">{{ $t('imports.step_4_complete') }}</p>
            <p class="text-xs text-gray-600">{{ $t('imports.step_4_complete_desc') }}</p>
          </div>
        </div>
      </div>

      <!-- Time Estimate -->
      <div class="flex items-center justify-center space-x-2 text-gray-600">
        <BaseIcon name="ClockIcon" class="w-5 h-5" />
        <span class="font-medium">{{ $t('imports.estimated_time') }}</span>
      </div>

      <!-- Prerequisites Checklist -->
      <div class="bg-white rounded-lg p-6 shadow-sm">
        <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
          <BaseIcon name="ClipboardDocumentCheckIcon" class="w-5 h-5 mr-2 text-green-600" />
          {{ $t('imports.prerequisites_checklist') }}
        </h5>
        <div class="space-y-3">
          <div
            v-for="(item, index) in prerequisites"
            :key="index"
            class="flex items-start"
          >
            <div class="flex-shrink-0 mt-0.5">
              <div
                :class="[
                  'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                  item.checked ? 'bg-green-500 border-green-500' : 'border-gray-300',
                ]"
              >
                <BaseIcon v-if="item.checked" name="CheckIcon" class="w-3 h-3 text-white" />
              </div>
            </div>
            <div class="ml-3 flex-1">
              <p :class="['text-sm', item.checked ? 'text-gray-900' : 'text-gray-600']">
                {{ item.text }}
              </p>
              <p v-if="item.hint" class="text-xs text-gray-500 mt-1">
                {{ item.hint }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="space-y-3">
        <button
          @click="$emit('startTour')"
          class="w-full px-6 py-4 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-semibold rounded-lg hover:from-primary-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl flex items-center justify-center"
        >
          <BaseIcon name="PlayCircleIcon" class="w-6 h-6 mr-3" />
          {{ $t('imports.start_interactive_tour') }}
        </button>

        <button
          v-if="isFirstTime"
          @click="skipTour"
          class="w-full px-6 py-3 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors border border-gray-300 flex items-center justify-center"
        >
          {{ $t('imports.skip_and_start') }}
          <BaseIcon name="ArrowRightIcon" class="w-5 h-5 ml-2" />
        </button>
      </div>

      <!-- First Time Banner -->
      <div v-if="isFirstTime" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
        <div class="flex items-start">
          <BaseIcon name="LightBulbIcon" class="w-5 h-5 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" />
          <div class="text-sm">
            <p class="font-medium text-yellow-900 mb-1">
              {{ $t('imports.new_to_migration') }}
            </p>
            <p class="text-yellow-800">
              {{ $t('imports.new_to_migration_tip') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Video Tutorial -->
      <div class="border-t border-gray-200 pt-4">
        <a
          href="#"
          @click.prevent="openVideoTutorial"
          class="flex items-center justify-between px-4 py-3 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-all"
        >
          <div class="flex items-center">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
              <BaseIcon name="PlayIcon" class="w-6 h-6 text-red-600" />
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ $t('imports.watch_quick_tutorial') }}</p>
              <p class="text-sm text-gray-600">{{ $t('imports.video_duration_3min') }}</p>
            </div>
          </div>
          <BaseIcon name="ArrowTopRightOnSquareIcon" class="w-5 h-5 text-gray-400" />
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, defineEmits } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'

const { t } = useI18n()
const emit = defineEmits(['close', 'startTour', 'skip'])

const isFirstTime = ref(false)

const prerequisites = ref([
  {
    text: t('imports.prereq_data_file'),
    hint: t('imports.prereq_data_file_hint'),
    checked: true,
  },
  {
    text: t('imports.prereq_backup'),
    hint: t('imports.prereq_backup_hint'),
    checked: true,
  },
  {
    text: t('imports.prereq_format'),
    hint: t('imports.prereq_format_hint'),
    checked: true,
  },
  {
    text: t('imports.prereq_time'),
    hint: null,
    checked: true,
  },
])

const skipTour = () => {
  localStorage.setItem('migration-wizard-tour-skipped', 'true')
  emit('skip')
  emit('close')
}

const openVideoTutorial = () => {
  // This would open a video tutorial modal or external link
  window.open('https://example.com/migration-wizard-tutorial', '_blank')
}

onMounted(() => {
  // Check if this is the user's first time
  const tourCompleted = localStorage.getItem('migration-wizard-tour-completed')
  const tourSkipped = localStorage.getItem('migration-wizard-tour-skipped')
  isFirstTime.value = !tourCompleted && !tourSkipped
})
// CLAUDE-CHECKPOINT
</script>
