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
          <!-- User messages: plain text -->
          <p v-if="message.role === 'user'" class="text-sm whitespace-pre-wrap">{{ message.content }}</p>
          <!-- Assistant messages: render markdown -->
          <!-- CLAUDE-CHECKPOINT: sanitized v-html (AI response) -->
          <div
            v-else
            class="text-sm prose prose-sm prose-indigo max-w-none ai-markdown"
            v-html="sanitizeHtml(renderMarkdown(message.content))"
          ></div>
          <!-- Action button when AI created a draft -->
          <div v-if="message.draftId && message.redirectUrl" class="mt-3">
            <button
              @click="navigateToDraft(message.redirectUrl)"
              class="w-full px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors flex items-center justify-center space-x-2"
            >
              <DocumentPlusIcon class="w-4 h-4" />
              <span>{{ getDraftButtonLabel(message.entityType) }}</span>
            </button>
          </div>
          <div class="flex items-center justify-between mt-2">
            <span
              :class="[
                'text-xs',
                message.role === 'user' ? 'text-indigo-200' : 'text-gray-500'
              ]"
            >
              {{ formatTime(message.timestamp) }}
            </span>
            <!-- Copy button for assistant messages -->
            <button
              v-if="message.role === 'assistant'"
              @click="copyMessage(message.content)"
              class="text-xs text-gray-400 hover:text-gray-600 transition-colors flex items-center space-x-1"
              :title="$t('ai.chat.copy')"
            >
              <ClipboardDocumentIcon class="w-4 h-4" />
            </button>
          </div>
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
import { ref, nextTick, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { sanitizeHtml } from '@/scripts/helpers/utilities'
import axios from 'axios'
import {
  ChatBubbleLeftRightIcon,
  PaperAirplaneIcon,
  TrashIcon,
  PlusCircleIcon,
  ClipboardDocumentIcon,
  DocumentPlusIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()

const { t } = useI18n()
const companyStore = useCompanyStore()

// Company-scoped localStorage key prefix
const storagePrefix = computed(() => {
  const companyId = companyStore.selectedCompany?.id || 'default'
  return `ai_chat_${companyId}`
})

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

  // Send to API — try NL assistant first, fall back to regular chat
  isLoading.value = true
  try {
    let assistantMessage = null

    // Try the NL assistant endpoint first (for commands like "Фактура за...")
    try {
      const nlResponse = await axios.post('/ai/assistant', { message: messageToSend })
      const nlData = nlResponse.data

      if (nlData.draft_id && nlData.redirect_url) {
        // AI created a draft — show action button
        assistantMessage = {
          role: 'assistant',
          content: nlData.message || t('ai.chat.no_response'),
          timestamp: new Date(),
          draftId: nlData.draft_id,
          redirectUrl: nlData.redirect_url,
          entityType: nlData.intent
        }
      } else if (nlData.intent === 'question' && nlData.message) {
        // AI answered a question — fall through to regular chat for richer answers
        assistantMessage = null
      } else if (nlData.clarification_needed) {
        // AI needs clarification
        assistantMessage = {
          role: 'assistant',
          content: nlData.clarification_needed,
          timestamp: new Date()
        }
      }
    } catch (nlErr) {
      // NL assistant unavailable (tier gated or error) — fall through to regular chat
      console.debug('[AI Chat] NL assistant unavailable, using regular chat')
    }

    // Fall back to regular chat if NL assistant didn't produce a result
    if (!assistantMessage) {
      const requestData = { message: messageToSend }
      if (conversationId.value) {
        requestData.conversation_id = conversationId.value
      }

      const response = await axios.post('/ai/insights/chat', requestData)

      if (response.data.conversation_id) {
        conversationId.value = response.data.conversation_id
        saveConversationId()
      }

      assistantMessage = {
        role: 'assistant',
        content: response.data.response || response.data.message || t('ai.chat.no_response'),
        timestamp: new Date()
      }
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

/**
 * Navigate to a draft form page
 */
function navigateToDraft(redirectUrl) {
  router.push(redirectUrl)
}

/**
 * Get button label based on entity type
 */
function getDraftButtonLabel(entityType) {
  const labels = {
    'create_invoice': t('ai.assistant.review_invoice', 'Review Invoice'),
    'create_bill': t('ai.assistant.review_bill', 'Review Bill'),
    'create_expense': t('ai.assistant.review_expense', 'Review Expense'),
    'record_payment': t('ai.assistant.review_payment', 'Review Payment')
  }
  return labels[entityType] || t('ai.assistant.review_draft', 'Review Draft')
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

/**
 * Lightweight markdown renderer for AI responses
 * Handles: bold, italic, code blocks, inline code, lists, links, headers, tables
 */
function renderMarkdown(text) {
  if (!text) return ''

  // Escape HTML entities first to prevent XSS
  let html = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')

  // Code blocks (```...```)
  html = html.replace(/```(\w*)\n([\s\S]*?)```/g, (match, lang, code) => {
    return `<pre class="bg-gray-800 text-gray-100 rounded-lg p-3 my-2 overflow-x-auto text-xs"><code>${code.trim()}</code></pre>`
  })

  // Inline code (`...`)
  html = html.replace(/`([^`]+)`/g, '<code class="bg-gray-200 text-gray-800 px-1 py-0.5 rounded text-xs">$1</code>')

  // Headers (### Header, ## Header, # Header)
  html = html.replace(/^### (.+)$/gm, '<h3 class="font-bold text-sm mt-3 mb-1">$1</h3>')
  html = html.replace(/^## (.+)$/gm, '<h2 class="font-bold text-base mt-3 mb-1">$1</h2>')
  html = html.replace(/^# (.+)$/gm, '<h1 class="font-bold text-lg mt-3 mb-1">$1</h1>')

  // Bold (**text** or __text__)
  html = html.replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold">$1</strong>')
  html = html.replace(/__(.+?)__/g, '<strong class="font-semibold">$1</strong>')

  // Italic (*text* or _text_)
  html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>')
  html = html.replace(/_([^_]+)_/g, '<em>$1</em>')

  // Unordered lists (- item or * item)
  html = html.replace(/^[\-\*] (.+)$/gm, '<li class="ml-4 list-disc">$1</li>')

  // Ordered lists (1. item)
  html = html.replace(/^\d+\. (.+)$/gm, '<li class="ml-4 list-decimal">$1</li>')

  // Wrap consecutive list items in ul/ol
  html = html.replace(/(<li class="ml-4 list-disc">[\s\S]*?<\/li>)(?!\s*<li)/g, '<ul class="my-2">$1</ul>')
  html = html.replace(/(<li class="ml-4 list-decimal">[\s\S]*?<\/li>)(?!\s*<li)/g, '<ol class="my-2">$1</ol>')

  // Links [text](url)
  html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">$1</a>')

  // Simple table rendering (| col | col |)
  const tableRegex = /^\|(.+)\|$/gm
  const tables = html.match(/(\|.+\|\n?)+/g)
  if (tables) {
    tables.forEach(table => {
      const rows = table.trim().split('\n').filter(row => row.trim())
      if (rows.length >= 2) {
        let tableHtml = '<table class="min-w-full border-collapse my-2 text-xs">'

        rows.forEach((row, idx) => {
          // Skip separator row (|---|---|)
          if (row.match(/^\|[\s\-:]+\|$/)) return

          const cells = row.split('|').filter(cell => cell.trim() !== '')
          const tag = idx === 0 ? 'th' : 'td'
          const cellClass = idx === 0
            ? 'border border-gray-300 px-2 py-1 bg-gray-50 font-semibold text-left'
            : 'border border-gray-300 px-2 py-1'

          tableHtml += '<tr>'
          cells.forEach(cell => {
            tableHtml += `<${tag} class="${cellClass}">${cell.trim()}</${tag}>`
          })
          tableHtml += '</tr>'
        })

        tableHtml += '</table>'
        html = html.replace(table, tableHtml)
      }
    })
  }

  // Horizontal rules (---, ___, ***)
  html = html.replace(/^(---|\*\*\*|___)$/gm, '<hr class="my-3 border-gray-300">')

  // Line breaks - preserve paragraph structure
  html = html.replace(/\n\n/g, '</p><p class="my-2">')
  html = html.replace(/\n/g, '<br>')

  // Wrap in paragraph if not already wrapped
  if (!html.startsWith('<')) {
    html = `<p class="my-1">${html}</p>`
  }

  return html
}

/**
 * Copy message content to clipboard
 */
async function copyMessage(content) {
  try {
    await navigator.clipboard.writeText(content)
    showNotification(t('ai.chat.copied') || 'Copied to clipboard')
  } catch (err) {
    console.error('Failed to copy:', err)
    // Fallback for older browsers
    const textarea = document.createElement('textarea')
    textarea.value = content
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    showNotification(t('ai.chat.copied') || 'Copied to clipboard')
  }
}

// Load chat history from localStorage (scoped by company)
function loadChatHistory() {
  try {
    const saved = localStorage.getItem(`${storagePrefix.value}_history`)
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

// Save chat history to localStorage (scoped by company)
function saveChatHistory() {
  try {
    localStorage.setItem(`${storagePrefix.value}_history`, JSON.stringify(messages.value))
  } catch (err) {
    console.error('Failed to save chat history:', err)
  }
}

// Load conversation ID from localStorage (scoped by company)
function loadConversationId() {
  try {
    const saved = localStorage.getItem(`${storagePrefix.value}_conversation_id`)
    if (saved) {
      conversationId.value = saved
      console.log('[AI Chat] Loaded conversation ID:', conversationId.value)
    }
  } catch (err) {
    console.error('Failed to load conversation ID:', err)
  }
}

// Save conversation ID to localStorage (scoped by company)
function saveConversationId() {
  try {
    if (conversationId.value) {
      localStorage.setItem(`${storagePrefix.value}_conversation_id`, conversationId.value)
    } else {
      localStorage.removeItem(`${storagePrefix.value}_conversation_id`)
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

/* AI Markdown styles */
.ai-markdown :deep(p) {
  margin: 0.25rem 0;
}

.ai-markdown :deep(ul),
.ai-markdown :deep(ol) {
  margin: 0.5rem 0;
  padding-left: 1rem;
}

.ai-markdown :deep(li) {
  margin: 0.125rem 0;
}

.ai-markdown :deep(pre) {
  margin: 0.5rem 0;
}

.ai-markdown :deep(table) {
  margin: 0.5rem 0;
  font-size: 0.75rem;
}

.ai-markdown :deep(h1),
.ai-markdown :deep(h2),
.ai-markdown :deep(h3) {
  margin-top: 0.75rem;
  margin-bottom: 0.25rem;
}

.ai-markdown :deep(a) {
  word-break: break-word;
}

.ai-markdown :deep(code) {
  word-break: break-word;
}
</style>

// CLAUDE-CHECKPOINT - Added conversation memory support with conversation_id tracking,
// localStorage persistence, new conversation button, conversation indicator badge,
// and server-side cache clearing on chat clear
