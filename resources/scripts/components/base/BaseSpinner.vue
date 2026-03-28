<template>
  <div class="facturino-spinner" :class="sizeClass">
    <!-- Ambient glow pulse -->
    <div class="spinner-glow" />

    <!-- Outer ring — gradient arc, slow spin -->
    <svg class="spinner-ring spinner-ring-outer" viewBox="0 0 100 100">
      <defs>
        <linearGradient id="grad-outer" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#4f46e5" stop-opacity="1" />
          <stop offset="50%" stop-color="#06b6d4" stop-opacity="0.8" />
          <stop offset="100%" stop-color="#4f46e5" stop-opacity="0" />
        </linearGradient>
      </defs>
      <circle
        cx="50" cy="50" r="46"
        fill="none"
        stroke="url(#grad-outer)"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-dasharray="200 90"
      />
    </svg>

    <!-- Inner ring — gradient arc, fast reverse spin -->
    <svg class="spinner-ring spinner-ring-inner" viewBox="0 0 100 100">
      <defs>
        <linearGradient id="grad-inner" x1="100%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#818cf8" stop-opacity="1" />
          <stop offset="50%" stop-color="#4f46e5" stop-opacity="0.6" />
          <stop offset="100%" stop-color="#818cf8" stop-opacity="0" />
        </linearGradient>
      </defs>
      <circle
        cx="50" cy="50" r="39"
        fill="none"
        stroke="url(#grad-inner)"
        stroke-width="2"
        stroke-linecap="round"
        stroke-dasharray="160 90"
      />
    </svg>

    <!-- Orbiting dot — accent particle -->
    <div class="spinner-dot-track">
      <div class="spinner-dot" />
    </div>

    <!-- Logo with breathing + glow -->
    <div class="spinner-logo">
      <MainLogo variant="icon" alt-text="Loading" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'

const props = defineProps({
  size: {
    type: String,
    default: 'md', // 'sm', 'md', 'lg'
  },
})

const sizeClass = computed(() => `spinner-${props.size}`)
</script>

<style scoped>
.facturino-spinner {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* ── Sizes ──────────────────────────────── */
.spinner-sm { width: 36px;  height: 36px; }
.spinner-md { width: 56px;  height: 56px; }
.spinner-lg { width: 96px;  height: 96px; }

/* ── Ambient glow ───────────────────────── */
.spinner-glow {
  position: absolute;
  inset: -30%;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(79,70,229,0.25) 0%, rgba(6,182,212,0.08) 40%, transparent 70%);
  animation: glowPulse 2.4s ease-in-out infinite;
  pointer-events: none;
}

/* hide glow on small size */
.spinner-sm .spinner-glow { display: none; }

/* ── SVG gradient rings ─────────────────── */
.spinner-ring {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.spinner-ring-outer {
  animation: spinSmooth 2.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

.spinner-ring-inner {
  animation: spinSmooth 1.8s cubic-bezier(0.4, 0, 0.2, 1) infinite reverse;
}

/* ── Orbiting dot ───────────────────────── */
.spinner-dot-track {
  position: absolute;
  inset: -12%;
  animation: spinSmooth 3.2s linear infinite;
}

.spinner-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #06b6d4;
  box-shadow: 0 0 6px 2px rgba(6,182,212,0.6);
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  animation: dotPulse 1.6s ease-in-out infinite;
}

.spinner-sm .spinner-dot-track { display: none; }
.spinner-sm .spinner-ring-outer { animation-duration: 2s; }
.spinner-sm .spinner-ring-inner { animation-duration: 1.2s; }

/* ── Logo ───────────────────────────────── */
.spinner-logo {
  position: relative;
  width: 58%;
  height: 58%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: logoBreathe 2.4s ease-in-out infinite;
  z-index: 1;
}

.spinner-logo :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 0 8px rgba(79, 70, 229, 0.35));
}

/* ── Keyframes ──────────────────────────── */
@keyframes spinSmooth {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

@keyframes glowPulse {
  0%, 100% { transform: scale(0.85); opacity: 0.4; }
  50%      { transform: scale(1.15); opacity: 0.8; }
}

@keyframes logoBreathe {
  0%, 100% { transform: scale(1);    filter: brightness(1);   }
  50%      { transform: scale(1.08); filter: brightness(1.15); }
}

@keyframes dotPulse {
  0%, 100% { opacity: 0.4; transform: translateX(-50%) scale(0.7); }
  50%      { opacity: 1;   transform: translateX(-50%) scale(1.2); }
}
</style>
