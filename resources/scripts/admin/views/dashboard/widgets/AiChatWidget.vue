<template>
  <div class="bg-white rounded-lg shadow-md p-6 min-h-[400px] flex flex-col">
    <!-- Widget Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="p-2 bg-indigo-100 rounded-lg">
          <ChatBubbleLeftRightIcon class="w-6 h-6 text-indigo-600" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('ai.chat.title') }}</h3>
          <div class="flex items-center space-x-2">
            <p class="text-sm text-gray-500">{{ $t('ai.chat.subtitle') }}</p>
            <!-- Conversation indicator -->
            <span
              v-if="conversationId"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
              :title="`Conversation ID: ${conversationId}`"
            >
              <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span>
              Continuing ({{ messages.length }} msgs)
            </span>
          </div>
        </div>
      </div>
      <div class="flex items-center space-x-2">
        <button
          v-if="conversationId"
          @click="startNewConversation"
          class="px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors flex items-center space-x-1"
          :title="$t('ai.chat.new_conversation')"
        >
          <PlusCircleIcon class="w-4 h-4" />
          <span class="hidden sm:inline">{{ $t('ai.chat.new_conversation') }}</span>
        </button>
        <button
          v-if="messages.length > 0"
          @click="clearChat"
          class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
          :title="$t('ai.chat.clear')"
        >
          <TrashIcon class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Chat Messages -->
    <div
      ref="chatContainer"
      class="flex-1 overflow-y-auto mb-4 space-y-4 min-h-[200px] max-h-[400px] pr-2"
      style="scrollbar-width: thin;"
    >
      <!-- Empty State -->
      <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-center py-8">
        <div>
          <ChatBubbleLeftRightIcon class="h-12 w-12 mx-auto text-gray-300 mb-3" />
          <p class="text-gray-500 text-sm">{{ $t('ai.chat.empty_state') }}</p>
          <p class="text-gray-400 text-xs mt-2">{{ $t('ai.chat.example_questions') }}</p>
        </div>
      </div>

      <!-- Messages -->
      <div
        v-for="(message, index) in messages"
        :key="index"
        :class="[
          'flex',
          message.role === 'user' ? 'justify-end' : 'justify-start'
        ]"
      >
        <div
          :class="[
            'max-w-[80%] rounded-lg px-4 py-3',
            message.role === 'user'
              ? 'bg-indigo-600 text-white'
              : 'bg-gray-100 text-gray-900'
          ]"
        >
          <p class="text-sm whitespace-pre-wrap">{{ message.content }}</p>
          <span
            :class="[
              'text-xs mt-1 block',
              message.role === 'user' ? 'text-indigo-200' : 'text-gray-500'
            ]"
          >
            {{ formatTime(message.timestamp) }}
          </span>
        </div>
      </div>

      <!-- Loading indicator -->
      <div v-if="isLoading" class="flex justify-start">
        <div class="bg-gray-100 rounded-lg px-4 py-3">
          <div class="flex space-x-2">
            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Chat Input -->
    <div class="border-t pt-4">
      <form @submit.prevent="sendMessage" class="flex space-x-2">
        <input
          v-model="currentMessage"
          type="text"
          :placeholder="$t('ai.chat.placeholder')"
          :disabled="isLoading"
          class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
          maxlength="500"
        />
        <button
          type="submit"
          :disabled="!currentMessage.trim() || isLoading"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <PaperAirplaneIcon class="w-5 h-5" />
          <span class="hidden sm:inline">{{ $t('ai.chat.send') }}</span>
        </button>
      </form>
      <p class="text-xs text-gray-400 mt-2">
        {{ $t('ai.chat.disclaimer') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import {
  ChatBubbleLeftRightIcon,
  PaperAirplaneIcon,
  TrashIcon,
  PlusCircleIcon
} from '@heroicons/vue/24/outline'

const { t } = useI18n()

// Reactive data
const messages = ref([])
const currentMessage = ref('')
const isLoading = ref(false)
const chatContainer = ref(null)
const conversationId = ref(null)

// Methods
async function sendMessage() {
  if (!currentMessage.value.trim() || isLoading.value) return

  const userMessage = {
    role: 'user',
    content: currentMessage.value.trim(),
    timestamp: new Date()
  }

  messages.value.push(userMessage)
  const messageToSend = currentMessage.value.trim()
  currentMessage.value = ''

  // Scroll to bottom
  await nextTick()
  scrollToBottom()

  // Send to API
  isLoading.value = true
  try {
    console.log('[AI Chat] Sending message:', messageToSend)
    console.log('[AI Chat] Conversation ID:', conversationId.value)

    const requestData = {
      message: messageToSend
    }

    // Include conversation_id if exists
    if (conversationId.value) {
      requestData.conversation_id = conversationId.value
    }

    const response = await axios.post('/ai/insights/chat', requestData)
    console.log('[AI Chat] Chat response:', response.data)

    // Store conversation_id from response
    if (response.data.conversation_id) {
      conversationId.value = response.data.conversation_id
      saveConversationId()
    }

    const assistantMessage = {
      role: 'assistant',
      content: response.data.response || response.data.message || t('ai.chat.no_response'),
      timestamp: new Date()
    }

    messages.value.push(assistantMessage)

    // Scroll to bottom after response
    await nextTick()
    scrollToBottom()
  } catch (err) {
    console.error('Failed to send message:', err)

    const errorMessage = {
      role: 'assistant',
      content: t('ai.chat.error_response'),
      timestamp: new Date()
    }

    messages.value.push(errorMessage)
  } finally {
    isLoading.value = false
  }
}

async function clearChat() {
  // Optionally call backend to clear server-side cache
  if (conversationId.value) {
    try {
      await axios.delete(`/ai/insights/chat/${conversationId.value}`)
      console.log('[AI Chat] Conversation cleared on server')
    } catch (err) {
      console.error('Failed to clear conversation on server:', err)
    }
  }

  messages.value = []
  currentMessage.value = ''
  conversationId.value = null
  saveConversationId()
  saveChatHistory()
}

function startNewConversation() {
  messages.value = []
  currentMessage.value = ''
  conversationId.value = null
  saveConversationId()
  saveChatHistory()

  // Show notification
  showNotification('New conversation started')
}

function showNotification(message) {
  // Simple toast notification - you can enhance this with a proper toast library
  const toast = document.createElement('div')
  toast.className = 'fixed bottom-4 right-4 bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity duration-300'
  toast.textContent = message
  document.body.appendChild(toast)

  setTimeout(() => {
    toast.style.opacity = '0'
    setTimeout(() => {
      document.body.removeChild(toast)
    }, 300)
  }, 2000)
}

function scrollToBottom() {
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

function formatTime(timestamp) {
  if (!timestamp) return ''
  try {
    return new Date(timestamp).toLocaleTimeString('mk-MK', {
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return ''
  }
}

// Load chat history from localStorage
function loadChatHistory() {
  try {
    const saved = localStorage.getItem('ai_chat_history')
    if (saved) {
      const parsed = JSON.parse(saved)
      messages.value = parsed.map(msg => ({
        ...msg,
        timestamp: new Date(msg.timestamp)
      }))
      nextTick(() => scrollToBottom())
    }
  } catch (err) {
    console.error('Failed to load chat history:', err)
  }
}

// Save chat history to localStorage
function saveChatHistory() {
  try {
    localStorage.setItem('ai_chat_history', JSON.stringify(messages.value))
  } catch (err) {
    console.error('Failed to save chat history:', err)
  }
}

// Load conversation ID from localStorage
function loadConversationId() {
  try {
    const saved = localStorage.getItem('ai_conversation_id')
    if (saved) {
      conversationId.value = saved
      console.log('[AI Chat] Loaded conversation ID:', conversationId.value)
    }
  } catch (err) {
    console.error('Failed to load conversation ID:', err)
  }
}

// Save conversation ID to localStorage
function saveConversationId() {
  try {
    if (conversationId.value) {
      localStorage.setItem('ai_conversation_id', conversationId.value)
    } else {
      localStorage.removeItem('ai_conversation_id')
    }
  } catch (err) {
    console.error('Failed to save conversation ID:', err)
  }
}

// Watch messages and save to localStorage
import { watch } from 'vue'
watch(messages, () => {
  saveChatHistory()
}, { deep: true })

// Lifecycle
onMounted(() => {
  loadConversationId()
  loadChatHistory()
})
</script>

<style scoped>
/* Custom scrollbar styling */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}
</style>

// CLAUDE-CHECKPOINT - Added conversation memory support with conversation_id tracking,
// localStorage persistence, new conversation button, conversation indicator badge,
// and server-side cache clearing on chat clear
