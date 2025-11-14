<template>
  <div class="text-center py-12 px-4">
    <!-- Icon -->
    <BaseIcon
      :name="icon"
      class="mx-auto h-24 w-24 text-gray-400 mb-4"
    />

    <!-- Title -->
    <h3 class="mt-4 text-lg font-medium text-gray-900">{{ title }}</h3>

    <!-- Description -->
    <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">{{ description }}</p>

    <!-- Action Button -->
    <div v-if="actionText" class="mt-6">
      <BaseButton
        variant="primary"
        @click="$emit('action')"
        class="min-h-[44px]"
      >
        <template v-if="actionIcon" #left="slotProps">
          <BaseIcon :name="actionIcon" :class="slotProps.class" />
        </template>
        {{ actionText }}
      </BaseButton>
    </div>

    <!-- Secondary Action -->
    <div v-if="secondaryActionText" class="mt-3">
      <BaseButton
        variant="primary-outline"
        @click="$emit('secondary-action')"
        class="min-h-[44px]"
      >
        {{ secondaryActionText }}
      </BaseButton>
    </div>

    <!-- Custom Slot for Additional Content -->
    <div v-if="$slots.custom" class="mt-6">
      <slot name="custom" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon: {
    type: String,
    default: 'InboxIcon',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  actionText: {
    type: String,
    default: '',
  },
  actionIcon: {
    type: String,
    default: '',
  },
  secondaryActionText: {
    type: String,
    default: '',
  },
})

defineEmits(['action', 'secondary-action'])
</script>

// CLAUDE-CHECKPOINT
