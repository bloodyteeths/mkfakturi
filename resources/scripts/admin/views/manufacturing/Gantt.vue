<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.gantt_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.gantt_title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center gap-2">
          <!-- Zoom controls -->
          <div class="flex rounded-lg border border-gray-200 bg-white">
            <button
              v-for="z in zoomLevels"
              :key="z.key"
              @click="zoom = z.key"
              class="px-3 py-1.5 text-xs font-medium transition first:rounded-l-lg last:rounded-r-lg"
              :class="zoom === z.key ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-50'"
            >
              {{ z.label }}
            </button>
          </div>

          <!-- Today button -->
          <button
            @click="scrollToToday"
            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50"
          >
            {{ t('manufacturing.gantt_today') }}
          </button>

          <!-- Auto-schedule -->
          <button
            @click="autoSchedule"
            :disabled="autoScheduling"
            class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary-700 disabled:opacity-50"
          >
            <span v-if="autoScheduling" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent inline-block mr-1"></span>
            {{ t('manufacturing.auto_schedule') }}
          </button>

          <router-link to="/admin/manufacturing">
            <BaseButton variant="primary-outline" size="sm">
              {{ t('manufacturing.title') }}
            </BaseButton>
          </router-link>
        </div>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-primary-600"></div>
    </div>

    <!-- Gantt Chart -->
    <div v-else class="mt-4 overflow-hidden rounded-lg bg-white shadow">
      <!-- Header: dates -->
      <div class="flex border-b border-gray-200">
        <!-- Left sidebar header -->
        <div class="w-64 flex-shrink-0 border-r border-gray-200 bg-gray-50 px-4 py-2">
          <span class="text-xs font-medium uppercase tracking-wider text-gray-500">
            {{ t('manufacturing.gantt_orders') }} ({{ orders.length }})
          </span>
        </div>

        <!-- Timeline header -->
        <div ref="timelineHeaderRef" class="flex-1 overflow-hidden">
          <div class="flex" :style="{ width: timelineWidth + 'px' }">
            <div
              v-for="(day, idx) in visibleDays"
              :key="idx"
              class="flex-shrink-0 border-r border-gray-100 px-1 py-2 text-center"
              :style="{ width: dayWidth + 'px' }"
              :class="{
                'bg-blue-50': day.isToday,
                'bg-gray-50': day.isWeekend && !day.isToday,
              }"
            >
              <div class="text-[10px] font-medium uppercase text-gray-400">{{ day.dayName }}</div>
              <div class="text-xs font-semibold" :class="day.isToday ? 'text-blue-700' : 'text-gray-700'">{{ day.dayNum }}</div>
              <div v-if="day.showMonth" class="text-[9px] text-gray-400">{{ day.monthName }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Body: rows -->
      <div class="flex" style="max-height: 70vh">
        <!-- Left sidebar: order names grouped by work center -->
        <div class="w-64 flex-shrink-0 overflow-y-auto border-r border-gray-200" ref="sidebarRef">
          <template v-for="group in groupedOrders" :key="group.key">
            <!-- Work center group header -->
            <div
              class="flex h-8 items-center border-b border-gray-200 bg-gray-100 px-3 cursor-pointer select-none"
              @click="toggleGroup(group.key)"
            >
              <svg class="mr-1.5 h-3 w-3 text-gray-500 transition-transform" :class="{ 'rotate-90': !collapsedGroups[group.key] }" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
              </svg>
              <span class="text-[10px] font-bold uppercase tracking-wider text-gray-600">{{ group.name }}</span>
              <span class="ml-auto text-[10px] text-gray-400">{{ group.orders.length }}</span>
            </div>
            <!-- Orders in group -->
            <template v-if="!collapsedGroups[group.key]">
              <div
                v-for="order in group.orders"
                :key="order.id"
                class="flex h-10 items-center border-b border-gray-100 px-4 transition hover:bg-gray-50 cursor-pointer"
                @click="$router.push(`/admin/manufacturing/orders/${order.id}`)"
              >
                <span
                  class="mr-2 inline-block h-2 w-2 flex-shrink-0 rounded-full"
                  :class="statusDotClass(order.status)"
                ></span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-xs font-medium text-gray-900">{{ order.item_name }}</p>
                </div>
                <span class="ml-1 text-[10px] text-gray-400">{{ order.order_number }}</span>
              </div>
            </template>
          </template>

          <!-- Empty state -->
          <div v-if="orders.length === 0" class="px-4 py-8 text-center">
            <p class="text-sm text-gray-500">{{ t('manufacturing.empty_orders') }}</p>
          </div>
        </div>

        <!-- Timeline body: bars -->
        <div ref="timelineBodyRef" class="flex-1 overflow-auto" @scroll="onTimelineScroll">
          <div :style="{ width: timelineWidth + 'px', position: 'relative' }">
            <!-- Grid lines -->
            <div class="absolute inset-0 flex">
              <div
                v-for="(day, idx) in visibleDays"
                :key="'grid-' + idx"
                class="h-full flex-shrink-0 border-r"
                :style="{ width: dayWidth + 'px' }"
                :class="{
                  'border-blue-200 bg-blue-50/30': day.isToday,
                  'bg-gray-50/50 border-gray-100': day.isWeekend && !day.isToday,
                  'border-gray-50': !day.isToday && !day.isWeekend,
                }"
              ></div>
            </div>

            <!-- Today line -->
            <div
              v-if="todayOffset >= 0"
              class="absolute top-0 bottom-0 z-10 w-0.5 bg-blue-500"
              :style="{ left: todayOffset + 'px' }"
            ></div>

            <!-- Grouped order bars -->
            <template v-for="group in groupedOrders" :key="'grp-' + group.key">
              <!-- Group header row -->
              <div class="relative h-8 border-b border-gray-200 bg-gray-100"></div>
              <!-- Order bars -->
              <template v-if="!collapsedGroups[group.key]">
                <div
                  v-for="order in group.orders"
                  :key="'bar-' + order.id"
                  class="relative h-10 border-b border-gray-50"
                >
                  <!-- Dependency indicator -->
                  <div
                    v-if="order.depends_on && order.depends_on.length > 0"
                    class="absolute top-2 z-30 flex h-3 w-3 items-center justify-center"
                    :style="{ left: (barLeft(order) - 14) + 'px' }"
                    :title="t('manufacturing.blocked_by') + ': ' + order.depends_on.map(id => flatOrders.find(o => o.id === id)?.order_number || id).join(', ')"
                  >
                    <svg class="h-3 w-3 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 005.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 01-2.828-2.828l3-3z"/>
                    </svg>
                  </div>

                  <div
                    class="absolute top-1.5 z-20 flex h-7 cursor-grab items-center rounded-md px-2 text-xs font-medium shadow-sm transition-shadow hover:shadow-md"
                    :class="barClass(order)"
                    :style="barStyle(order)"
                    @mousedown="startDrag($event, order)"
                    @touchstart.passive="startDrag($event, order)"
                    :title="`${order.order_number}: ${order.item_name} (${order.planned_quantity})`"
                  >
                    <span class="truncate">{{ order.item_name }}</span>
                    <span v-if="barDays(order) > 2" class="ml-1 opacity-70">{{ order.planned_quantity }}</span>

                    <!-- Resize handle (right edge) -->
                    <div
                      class="absolute right-0 top-0 bottom-0 w-2 cursor-col-resize"
                      @mousedown.stop="startResize($event, order)"
                      @touchstart.stop.passive="startResize($event, order)"
                    ></div>
                  </div>
                </div>
              </template>
            </template>

            <!-- Dependency arrows (SVG overlay) -->
            <svg v-if="dependencyLines.length > 0" class="absolute inset-0 z-10 pointer-events-none" :style="{ width: timelineWidth + 'px', height: totalRowHeight + 'px' }">
              <defs>
                <marker id="dep-arrow" markerWidth="6" markerHeight="4" refX="6" refY="2" orient="auto">
                  <path d="M0,0 L6,2 L0,4" fill="#f97316" />
                </marker>
              </defs>
              <line
                v-for="(line, idx) in dependencyLines"
                :key="'dep-' + idx"
                :x1="line.x1" :y1="line.y1"
                :x2="line.x2" :y2="line.y2"
                stroke="#f97316" stroke-width="1.5" stroke-dasharray="4,3"
                marker-end="url(#dep-arrow)"
              />
            </svg>
          </div>
        </div>
      </div>

      <!-- Legend -->
      <div class="flex items-center gap-4 border-t border-gray-200 bg-gray-50 px-4 py-2">
        <span class="flex items-center text-xs text-gray-500">
          <span class="mr-1 inline-block h-2.5 w-5 rounded bg-gray-300"></span> {{ t('manufacturing.status_draft') }}
        </span>
        <span class="flex items-center text-xs text-gray-500">
          <span class="mr-1 inline-block h-2.5 w-5 rounded bg-blue-500"></span> {{ t('manufacturing.status_in_progress') }}
        </span>
        <span class="flex items-center text-xs text-gray-500">
          <span class="mr-1 inline-block h-2.5 w-5 rounded bg-green-500"></span> {{ t('manufacturing.status_completed') }}
        </span>
        <span class="flex items-center text-xs text-gray-500">
          <span class="mr-1 inline-block h-2.5 w-5 rounded bg-red-400 border border-red-500"></span> {{ t('manufacturing.gantt_overdue') }}
        </span>
        <span class="ml-auto text-xs text-gray-400">{{ t('manufacturing.gantt_drag_hint') }}</span>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t, locale } = useI18n()
const notificationStore = useNotificationStore()

// ===== State =====
const loading = ref(true)
const orders = ref([])
const workCenters = ref([])
const zoom = ref('day')
const autoScheduling = ref(false)

const timelineHeaderRef = ref(null)
const timelineBodyRef = ref(null)
const sidebarRef = ref(null)
const collapsedGroups = ref({})

// Zoom levels
const zoomLevels = [
  { key: 'day', label: t('manufacturing.gantt_zoom_day') },
  { key: 'week', label: t('manufacturing.gantt_zoom_week') },
  { key: 'month', label: t('manufacturing.gantt_zoom_month') },
]

// Day width based on zoom
const dayWidth = computed(() => {
  if (zoom.value === 'day') return 40
  if (zoom.value === 'week') return 20
  return 8
})

// ===== Date range =====
const rangeStart = computed(() => {
  if (orders.value.length === 0) return new Date()
  const dates = orders.value.map(o => new Date(o.start))
  const earliest = new Date(Math.min(...dates))
  earliest.setDate(earliest.getDate() - 3) // 3 days padding
  return earliest
})

const rangeEnd = computed(() => {
  if (orders.value.length === 0) {
    const d = new Date()
    d.setDate(d.getDate() + 30)
    return d
  }
  const dates = orders.value.map(o => new Date(o.end))
  const latest = new Date(Math.max(...dates))
  latest.setDate(latest.getDate() + 7) // 7 days padding
  return latest
})

const totalDays = computed(() => {
  return Math.ceil((rangeEnd.value - rangeStart.value) / (1000 * 60 * 60 * 24))
})

const timelineWidth = computed(() => totalDays.value * dayWidth.value)

// Group orders by work center
const groupedOrders = computed(() => {
  const groups = {}
  const unassigned = { key: '__none', name: t('manufacturing.gantt_unassigned'), orders: [] }

  for (const order of orders.value) {
    if (order.work_center_id && order.work_center) {
      const key = 'wc_' + order.work_center_id
      if (!groups[key]) {
        groups[key] = { key, name: order.work_center, orders: [] }
      }
      groups[key].orders.push(order)
    } else {
      unassigned.orders.push(order)
    }
  }

  const result = Object.values(groups).sort((a, b) => a.name.localeCompare(b.name))
  if (unassigned.orders.length > 0) {
    result.push(unassigned)
  }
  return result
})

// Flat list of visible orders (respecting collapsed groups)
const flatOrders = computed(() => {
  const flat = []
  for (const group of groupedOrders.value) {
    if (!collapsedGroups.value[group.key]) {
      flat.push(...group.orders)
    }
  }
  return flat
})

// Total pixel height for SVG overlay
const totalRowHeight = computed(() => {
  let h = 0
  for (const group of groupedOrders.value) {
    h += 32 // group header
    if (!collapsedGroups.value[group.key]) {
      h += group.orders.length * 40
    }
  }
  return h
})

function toggleGroup(key) {
  collapsedGroups.value[key] = !collapsedGroups.value[key]
}

const visibleDays = computed(() => {
  const days = []
  const mkDays = ['Нед', 'Пон', 'Вто', 'Сре', 'Чет', 'Пет', 'Саб']
  const enDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  const sqDays = ['Die', 'Hën', 'Mar', 'Mër', 'Enj', 'Pre', 'Sht']
  const trDays = ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt']
  const dayMap = { mk: mkDays, en: enDays, sq: sqDays, tr: trDays }
  const shortDays = dayMap[locale.value] || mkDays
  const mkMonths = ['Јан', 'Фев', 'Мар', 'Апр', 'Мај', 'Јун', 'Јул', 'Авг', 'Сеп', 'Окт', 'Ное', 'Дек']
  const enMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  const monthMap = { mk: mkMonths, en: enMonths, sq: enMonths, tr: enMonths }
  const months = monthMap[locale.value] || mkMonths
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  for (let i = 0; i < totalDays.value; i++) {
    const d = new Date(rangeStart.value)
    d.setDate(d.getDate() + i)
    const dow = d.getDay()
    days.push({
      date: new Date(d),
      dayName: shortDays[dow] || dow,
      dayNum: d.getDate(),
      monthName: months[d.getMonth()],
      showMonth: d.getDate() === 1 || i === 0,
      isToday: d.getTime() === today.getTime(),
      isWeekend: dow === 0 || dow === 6,
    })
  }
  return days
})

const todayOffset = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const diff = (today - rangeStart.value) / (1000 * 60 * 60 * 24)
  if (diff < 0 || diff > totalDays.value) return -1
  return diff * dayWidth.value
})

// ===== Bar positioning =====
function daysBetween(start, end) {
  return (new Date(end) - new Date(start)) / (1000 * 60 * 60 * 24)
}

function barStyle(order) {
  const startDiff = daysBetween(rangeStart.value, order.start)
  const duration = Math.max(1, daysBetween(order.start, order.end))
  return {
    left: startDiff * dayWidth.value + 'px',
    width: duration * dayWidth.value - 4 + 'px',
  }
}

function barDays(order) {
  return Math.max(1, daysBetween(order.start, order.end))
}

function barLeft(order) {
  const startDiff = daysBetween(rangeStart.value, order.start)
  return startDiff * dayWidth.value
}

function barClass(order) {
  if (order.is_overdue) return 'bg-red-100 border border-red-400 text-red-800'
  if (order.status === 'completed') return 'bg-green-100 border border-green-300 text-green-800'
  if (order.status === 'in_progress') return 'bg-blue-100 border border-blue-300 text-blue-800'
  return 'bg-gray-100 border border-gray-300 text-gray-700' // draft
}

// Dependency lines: connect right edge of dependency to left edge of dependent
// Accounts for grouped rows (group headers = 32px, order rows = 40px)
const dependencyLines = computed(() => {
  const lines = []
  // Build position map for visible orders
  const posMap = {}
  let y = 0
  for (const group of groupedOrders.value) {
    y += 32 // group header
    if (collapsedGroups.value[group.key]) continue
    for (const order of group.orders) {
      posMap[order.id] = y + 20 // center of 40px row
      y += 40
    }
  }

  for (const order of flatOrders.value) {
    if (!order.depends_on || order.depends_on.length === 0) continue
    const toY = posMap[order.id]
    if (toY === undefined) continue
    const toX = barLeft(order)

    for (const depId of order.depends_on) {
      const fromY = posMap[depId]
      if (fromY === undefined) continue
      const dep = flatOrders.value.find(o => o.id === depId) || orders.value.find(o => o.id === depId)
      if (!dep) continue
      const fromDuration = Math.max(1, daysBetween(dep.start, dep.end))
      const fromX = barLeft(dep) + fromDuration * dayWidth.value - 4

      lines.push({ x1: fromX, y1: fromY, x2: toX, y2: toY })
    }
  }
  return lines
})

function statusDotClass(status) {
  return {
    draft: 'bg-gray-400',
    in_progress: 'bg-blue-500',
    completed: 'bg-green-500',
    cancelled: 'bg-red-500',
  }[status] || 'bg-gray-400'
}

// ===== Drag & Drop =====
let dragging = null
let resizing = null
let dragStartX = 0
let dragOrigStart = ''
let dragOrigEnd = ''

// Store bound handler references so we can remove them reliably
let boundOnDrag = null
let boundEndDrag = null
let boundOnResize = null
let boundEndResize = null

function removeDragListeners() {
  if (boundOnDrag) {
    document.removeEventListener('mousemove', boundOnDrag)
    document.removeEventListener('touchmove', boundOnDrag)
  }
  if (boundEndDrag) {
    document.removeEventListener('mouseup', boundEndDrag)
    document.removeEventListener('touchend', boundEndDrag)
  }
  boundOnDrag = null
  boundEndDrag = null
}

function removeResizeListeners() {
  if (boundOnResize) {
    document.removeEventListener('mousemove', boundOnResize)
    document.removeEventListener('touchmove', boundOnResize)
  }
  if (boundEndResize) {
    document.removeEventListener('mouseup', boundEndResize)
    document.removeEventListener('touchend', boundEndResize)
  }
  boundOnResize = null
  boundEndResize = null
}

function startDrag(e, order) {
  if (!order.can_reschedule) return
  e.preventDefault()
  const clientX = e.touches ? e.touches[0].clientX : e.clientX
  dragging = order
  dragStartX = clientX
  dragOrigStart = order.start
  dragOrigEnd = order.end

  // Remove any stale listeners before adding new ones
  removeDragListeners()

  boundOnDrag = onDrag
  boundEndDrag = endDrag
  document.addEventListener('mousemove', boundOnDrag)
  document.addEventListener('mouseup', boundEndDrag)
  document.addEventListener('touchmove', boundOnDrag, { passive: false })
  document.addEventListener('touchend', boundEndDrag)
}

function onDrag(e) {
  if (!dragging) return
  e.preventDefault()
  const clientX = e.touches ? e.touches[0].clientX : e.clientX
  const dx = clientX - dragStartX
  const daysDelta = Math.round(dx / dayWidth.value)

  if (daysDelta !== 0) {
    const newStart = addDays(dragOrigStart, daysDelta)
    const newEnd = addDays(dragOrigEnd, daysDelta)
    dragging.start = newStart
    dragging.end = newEnd
  }
}

function endDrag() {
  if (!dragging) return
  const order = dragging

  removeDragListeners()

  // Only save if dates changed
  if (order.start !== dragOrigStart || order.end !== dragOrigEnd) {
    saveReschedule(order)
  }
  dragging = null
}

function startResize(e, order) {
  if (!order.can_reschedule) return
  e.preventDefault()
  const clientX = e.touches ? e.touches[0].clientX : e.clientX
  resizing = order
  dragStartX = clientX
  dragOrigEnd = order.end

  // Remove any stale listeners before adding new ones
  removeResizeListeners()

  boundOnResize = onResize
  boundEndResize = endResize
  document.addEventListener('mousemove', boundOnResize)
  document.addEventListener('mouseup', boundEndResize)
  document.addEventListener('touchmove', boundOnResize, { passive: false })
  document.addEventListener('touchend', boundEndResize)
}

function onResize(e) {
  if (!resizing) return
  e.preventDefault()
  const clientX = e.touches ? e.touches[0].clientX : e.clientX
  const dx = clientX - dragStartX
  const daysDelta = Math.round(dx / dayWidth.value)
  const newEnd = addDays(dragOrigEnd, daysDelta)

  // Ensure end >= start + 1 day
  if (new Date(newEnd) > new Date(resizing.start)) {
    resizing.end = newEnd
  }
}

function endResize() {
  if (!resizing) return
  const order = resizing

  removeResizeListeners()

  if (order.end !== dragOrigEnd) {
    saveReschedule(order)
  }
  resizing = null
}

function addDays(dateStr, days) {
  const d = new Date(dateStr)
  d.setDate(d.getDate() + days)
  return d.getFullYear() + '-' +
    String(d.getMonth() + 1).padStart(2, '0') + '-' +
    String(d.getDate()).padStart(2, '0')
}

async function saveReschedule(order) {
  // Validate: end date must be >= start date
  if (new Date(order.end) < new Date(order.start)) {
    order.start = dragOrigStart
    order.end = dragOrigEnd
    notificationStore.showNotification({
      type: 'error',
      message: t('manufacturing.invalid_date_range'),
    })
    return
  }

  try {
    await window.axios.patch(`/manufacturing/orders/${order.id}/reschedule`, {
      order_date: order.start,
      expected_completion_date: order.end,
    })
  } catch (error) {
    console.error('Reschedule failed:', error)
    // Revert on failure
    order.start = dragOrigStart
    order.end = dragOrigEnd
  }
}

// ===== Scroll sync =====
function onTimelineScroll() {
  if (timelineHeaderRef.value && timelineBodyRef.value) {
    timelineHeaderRef.value.scrollLeft = timelineBodyRef.value.scrollLeft
  }
  if (sidebarRef.value && timelineBodyRef.value) {
    sidebarRef.value.scrollTop = timelineBodyRef.value.scrollTop
  }
}

function scrollToToday() {
  if (!timelineBodyRef.value || todayOffset.value < 0) return
  timelineBodyRef.value.scrollLeft = todayOffset.value - 200
}

// ===== API =====
async function fetchGanttData() {
  loading.value = true
  try {
    const res = await window.axios.get('/manufacturing/gantt')
    if (res.data?.data) {
      orders.value = res.data.data.orders || []
      workCenters.value = res.data.data.work_centers || []
    }
  } catch (error) {
    console.error('Failed to fetch gantt data:', error)
  } finally {
    loading.value = false
  }
}

async function autoSchedule() {
  const draftCount = orders.value.filter(o => o.status === 'draft').length
  if (draftCount === 0) {
    window.$utils?.showNotification?.({ type: 'warning', message: t('manufacturing.auto_schedule_no_orders') })
    return
  }
  if (!confirm(t('manufacturing.auto_schedule_confirm'))) return

  autoScheduling.value = true
  try {
    const res = await window.axios.post('/manufacturing/auto-schedule')
    const count = res.data?.data?.count || 0
    window.$utils?.showNotification?.({
      type: count > 0 ? 'success' : 'warning',
      message: count > 0 ? t('manufacturing.auto_schedule_success', { count }) : t('manufacturing.auto_schedule_no_orders'),
    })
    if (count > 0) {
      await fetchGanttData()
    }
  } catch (error) {
    window.$utils?.showNotification?.({ type: 'error', message: error.response?.data?.message || 'Auto-schedule failed' })
  } finally {
    autoScheduling.value = false
  }
}

onMounted(async () => {
  await fetchGanttData()
  // Auto-scroll to today
  setTimeout(() => scrollToToday(), 100)
})

onBeforeUnmount(() => {
  removeDragListeners()
  removeResizeListeners()
})
// CLAUDE-CHECKPOINT
</script>
