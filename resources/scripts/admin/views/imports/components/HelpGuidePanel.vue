<template>
  <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
          <BaseIcon name="QuestionMarkCircleIcon" class="w-5 h-5 mr-2 text-primary-600" />
          {{ $t('imports.help_guide') }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <BaseIcon name="XMarkIcon" class="w-5 h-5" />
        </button>
      </div>
    </div>

    <div class="p-4 space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto">
      <!-- Step-specific Help Content -->
      <div v-if="currentStep === 1" class="space-y-4">
        <!-- Visual Walkthrough -->
        <div class="space-y-3">
          <h4 class="font-semibold text-gray-900 flex items-center">
            <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary-100 text-primary-700 text-sm mr-2">1</span>
            {{ $t('imports.help_step1_title') }}
          </h4>

          <!-- Visual Guide -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
              <BaseIcon name="CloudArrowUpIcon" class="w-8 h-8 text-blue-500 mr-3 flex-shrink-0" />
              <div class="text-sm">
                <p class="font-medium text-blue-900 mb-2">{{ $t('imports.help_upload_visual_title') }}</p>
                <ul class="space-y-1 text-blue-700">
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_upload_step1') }}
                  </li>
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_upload_step2') }}
                  </li>
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_upload_step3') }}
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Common Pitfalls -->
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h5 class="font-medium text-yellow-900 mb-2 flex items-center">
              <BaseIcon name="ExclamationTriangleIcon" class="w-4 h-4 mr-2" />
              {{ $t('imports.common_pitfalls') }}
            </h5>
            <ul class="space-y-1 text-sm text-yellow-800">
              <li class="flex items-start">
                <span class="mr-2">•</span>
                {{ $t('imports.pitfall_file_size') }}
              </li>
              <li class="flex items-start">
                <span class="mr-2">•</span>
                {{ $t('imports.pitfall_file_format') }}
              </li>
              <li class="flex items-start">
                <span class="mr-2">•</span>
                {{ $t('imports.pitfall_encoding') }}
              </li>
            </ul>
          </div>

          <!-- Tips -->
          <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h5 class="font-medium text-green-900 mb-2 flex items-center">
              <BaseIcon name="LightBulbIcon" class="w-4 h-4 mr-2" />
              {{ $t('imports.pro_tips') }}
            </h5>
            <ul class="space-y-1 text-sm text-green-800">
              <li class="flex items-start">
                <BaseIcon name="SparklesIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0 text-green-600" />
                {{ $t('imports.tip_use_templates') }}
              </li>
              <li class="flex items-start">
                <BaseIcon name="SparklesIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0 text-green-600" />
                {{ $t('imports.tip_test_small_file') }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Step 2: Mapping Help -->
      <div v-if="currentStep === 2" class="space-y-4">
        <div class="space-y-3">
          <h4 class="font-semibold text-gray-900 flex items-center">
            <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary-100 text-primary-700 text-sm mr-2">2</span>
            {{ $t('imports.help_step2_title') }}
          </h4>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
              <BaseIcon name="ArrowsRightLeftIcon" class="w-8 h-8 text-blue-500 mr-3 flex-shrink-0" />
              <div class="text-sm">
                <p class="font-medium text-blue-900 mb-2">{{ $t('imports.help_mapping_visual_title') }}</p>
                <ul class="space-y-1 text-blue-700">
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_mapping_step1') }}
                  </li>
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_mapping_step2') }}
                  </li>
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_mapping_step3') }}
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <h5 class="font-medium text-purple-900 mb-2">{{ $t('imports.example_mappings') }}</h5>
            <div class="text-sm text-purple-800 space-y-2">
              <div class="flex items-center">
                <code class="bg-white px-2 py-1 rounded text-xs">{{ $t('imports.example_csv_column_name') }}</code>
                <BaseIcon name="ArrowRightIcon" class="w-4 h-4 mx-2" />
                <span class="font-medium">{{ $t('imports.example_field_customer_name') }}</span>
              </div>
              <div class="flex items-center">
                <code class="bg-white px-2 py-1 rounded text-xs">{{ $t('imports.example_csv_column_email') }}</code>
                <BaseIcon name="ArrowRightIcon" class="w-4 h-4 mx-2" />
                <span class="font-medium">{{ $t('imports.example_field_email') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3: Validation Help -->
      <div v-if="currentStep === 3" class="space-y-4">
        <div class="space-y-3">
          <h4 class="font-semibold text-gray-900 flex items-center">
            <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary-100 text-primary-700 text-sm mr-2">3</span>
            {{ $t('imports.help_step3_title') }}
          </h4>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
              <BaseIcon name="ShieldCheckIcon" class="w-8 h-8 text-blue-500 mr-3 flex-shrink-0" />
              <div class="text-sm">
                <p class="font-medium text-blue-900 mb-2">{{ $t('imports.help_validation_visual_title') }}</p>
                <ul class="space-y-1 text-blue-700">
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_validation_step1') }}
                  </li>
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_validation_step2') }}
                  </li>
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_validation_step3') }}
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h5 class="font-medium text-red-900 mb-2">{{ $t('imports.common_validation_errors') }}</h5>
            <ul class="space-y-1 text-sm text-red-800">
              <li class="flex items-start">
                <span class="mr-2">•</span>
                {{ $t('imports.validation_error_missing_required') }}
              </li>
              <li class="flex items-start">
                <span class="mr-2">•</span>
                {{ $t('imports.validation_error_invalid_format') }}
              </li>
              <li class="flex items-start">
                <span class="mr-2">•</span>
                {{ $t('imports.validation_error_duplicate') }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Step 4: Commit Help -->
      <div v-if="currentStep === 4" class="space-y-4">
        <div class="space-y-3">
          <h4 class="font-semibold text-gray-900 flex items-center">
            <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary-100 text-primary-700 text-sm mr-2">4</span>
            {{ $t('imports.help_step4_title') }}
          </h4>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
              <BaseIcon name="RocketLaunchIcon" class="w-8 h-8 text-blue-500 mr-3 flex-shrink-0" />
              <div class="text-sm">
                <p class="font-medium text-blue-900 mb-2">{{ $t('imports.help_commit_visual_title') }}</p>
                <ul class="space-y-1 text-blue-700">
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_commit_step1') }}
                  </li>
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_commit_step2') }}
                  </li>
                  <li class="flex items-start">
                    <BaseIcon name="CheckCircleIcon" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                    {{ $t('imports.help_commit_step3') }}
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h5 class="font-medium text-yellow-900 mb-2 flex items-center">
              <BaseIcon name="ExclamationTriangleIcon" class="w-4 h-4 mr-2" />
              {{ $t('imports.important_note') }}
            </h5>
            <p class="text-sm text-yellow-800">
              {{ $t('imports.commit_warning') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Video Tutorial Link -->
      <div class="border-t border-gray-200 pt-4">
        <a
          href="#"
          @click.prevent="openVideoTutorial"
          class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-primary-50 to-purple-50 border border-primary-200 rounded-lg hover:shadow-md transition-all"
        >
          <div class="flex items-center">
            <BaseIcon name="PlayCircleIcon" class="w-6 h-6 text-primary-600 mr-3" />
            <div>
              <p class="font-medium text-gray-900">{{ $t('imports.watch_video_tutorial') }}</p>
              <p class="text-sm text-gray-600">{{ $t('imports.video_tutorial_duration') }}</p>
            </div>
          </div>
          <BaseIcon name="ArrowTopRightOnSquareIcon" class="w-5 h-5 text-gray-400" />
        </a>
      </div>

      <!-- Quick Links -->
      <div class="border-t border-gray-200 pt-4">
        <h5 class="font-medium text-gray-900 mb-3">{{ $t('imports.quick_links') }}</h5>
        <div class="space-y-2">
          <a
            href="#"
            class="flex items-center text-sm text-primary-600 hover:text-primary-700"
          >
            <BaseIcon name="DocumentTextIcon" class="w-4 h-4 mr-2" />
            {{ $t('imports.full_documentation') }}
          </a>
          <a
            href="#"
            class="flex items-center text-sm text-primary-600 hover:text-primary-700"
          >
            <BaseIcon name="QuestionMarkCircleIcon" class="w-4 h-4 mr-2" />
            {{ $t('imports.faq') }}
          </a>
          <a
            href="#"
            class="flex items-center text-sm text-primary-600 hover:text-primary-700"
          >
            <BaseIcon name="ChatBubbleLeftRightIcon" class="w-4 h-4 mr-2" />
            {{ $t('imports.contact_support') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'

const props = defineProps({
  currentStep: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['close'])

const openVideoTutorial = () => {
  // This would open a video tutorial modal or external link
  window.open('https://example.com/migration-wizard-tutorial', '_blank')
}
// CLAUDE-CHECKPOINT
</script>
