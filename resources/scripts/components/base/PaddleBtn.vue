<template>
  <div class="paddle-button-wrapper">
    <BaseButton
      :loading="loading"
      :disabled="disabled || loading"
      class="paddle-checkout-btn"
      variant="primary"
      @click="openPaddleCheckout"
    >
      <template #left>
        <CreditCardIcon class="h-5 w-5" />
      </template>
      {{ $t('general.pay_with_paddle') }}
    </BaseButton>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { CreditCardIcon } from '@heroicons/vue/24/outline'
import BaseButton from './BaseButton.vue'

const props = defineProps({
  productId: {
    type: [String, Number],
    required: true
  },
  customerId: {
    type: [String, Number],
    default: null
  },
  customerEmail: {
    type: String,
    default: null
  },
  amount: {
    type: [String, Number],
    default: null
  },
  currency: {
    type: String,
    default: 'EUR'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  passthrough: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['success', 'error', 'cancelled'])

const loading = ref(false)
const paddleLoaded = ref(false)

onMounted(() => {
  loadPaddleScript()
})

const loadPaddleScript = () => {
  if (window.Paddle) {
    paddleLoaded.value = true
    initializePaddle()
    return
  }

  const script = document.createElement('script')
  script.src = 'https://cdn.paddle.com/paddle/paddle.js'
  script.onload = () => {
    paddleLoaded.value = true
    initializePaddle()
  }
  script.onerror = () => {
    console.error('Failed to load Paddle script')
    emit('error', new Error('Failed to load Paddle script'))
  }
  document.head.appendChild(script)
}

const initializePaddle = () => {
  const vendorId = import.meta.env.VITE_PADDLE_VENDOR_ID
  const environment = import.meta.env.VITE_PADDLE_ENVIRONMENT || 'sandbox'
  
  if (!vendorId) {
    console.error('Paddle Vendor ID not configured')
    return
  }

  window.Paddle.Setup({
    vendor: parseInt(vendorId),
    eventCallback: handlePaddleEvent
  })

  if (environment === 'sandbox') {
    window.Paddle.Environment.set('sandbox')
  }
}

const handlePaddleEvent = (data) => {
  switch (data.event) {
    case 'Checkout.Complete':
      loading.value = false
      emit('success', {
        checkoutId: data.eventData.checkout.id,
        orderId: data.eventData.order.id,
        passthrough: data.eventData.passthrough
      })
      break
    case 'Checkout.Close':
      loading.value = false
      emit('cancelled')
      break
    case 'Checkout.Error':
      loading.value = false
      emit('error', new Error(data.eventData.error?.message || 'Checkout error'))
      break
  }
}

const openPaddleCheckout = () => {
  if (!paddleLoaded.value || !window.Paddle) {
    emit('error', new Error('Paddle not initialized'))
    return
  }

  loading.value = true

  const checkoutOptions = {
    product: props.productId,
    passthrough: JSON.stringify({
      customer_id: props.customerId,
      ...props.passthrough
    }),
    frameTarget: 'self',
    frameInitialHeight: 366,
    frameStyle: 'width:100%; min-width:312px; background-color: transparent; border: none;'
  }

  // Add customer information if provided
  if (props.customerEmail) {
    checkoutOptions.email = props.customerEmail
  }

  // Add custom pricing if amount is specified
  if (props.amount) {
    checkoutOptions.prices = [`${props.currency.toLowerCase()}:${props.amount}`]
  }

  try {
    window.Paddle.Checkout.open(checkoutOptions)
  } catch (error) {
    loading.value = false
    emit('error', error)
  }
}
</script>

<style scoped>
.paddle-checkout-btn {
  @apply bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
}

.paddle-button-wrapper {
  @apply w-full;
}
</style>