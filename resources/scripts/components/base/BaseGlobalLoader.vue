<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center global-loader-bg">
    <div class="flex flex-col items-center gap-6">
      <!-- Main spinner -->
      <BaseSpinner size="lg" />

      <!-- Brand name with shimmer sweep -->
      <div class="brand-shimmer">
        <span class="text-xl font-bold tracking-widest" style="color: #4f46e5">
          FACTURINO
        </span>
        <div class="shimmer-sweep" />
      </div>

      <!-- Morphing dot loader -->
      <div class="flex gap-2">
        <div
          v-for="i in 3"
          :key="i"
          class="dot"
          :style="{ animationDelay: `${(i - 1) * 0.15}s` }"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'

defineProps({
  showBgOverlay: {
    default: false,
    type: Boolean,
  },
})
</script>

<style scoped>
.global-loader-bg {
  background: linear-gradient(135deg, #ffffff 0%, #eef2ff 40%, #ecfeff 70%, #ffffff 100%);
}

/* ── Brand shimmer ──────────────────────── */
.brand-shimmer {
  position: relative;
  overflow: hidden;
  padding: 0 4px;
}

.shimmer-sweep {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255,255,255,0.9) 45%,
    rgba(255,255,255,0.9) 55%,
    transparent 100%
  );
  background-size: 250% 100%;
  animation: shimmer 2.5s ease-in-out infinite;
}

/* ── Dots ───────────────────────────────── */
.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #4f46e5;
  animation: dotBounce 1.4s ease-in-out infinite;
}

/* ── Keyframes ──────────────────────────── */
@keyframes shimmer {
  0%   { background-position: 250% 0; }
  100% { background-position: -250% 0; }
}

@keyframes dotBounce {
  0%, 80%, 100% {
    transform: scale(0.5) translateY(0);
    opacity: 0.3;
  }
  40% {
    transform: scale(1) translateY(-6px);
    opacity: 1;
    background: #06b6d4;
  }
}
</style>
