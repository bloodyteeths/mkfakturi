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
        <!-- Left sidebar: order names -->
        <div class="w-64 flex-shrink-0 overflow-y-auto border-r border-gray-200" ref="sidebarRef">
          <div
            v-for="order in orders"
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

            <!-- Order bars -->
            <div
              v-for="order in orders"
              :key="'bar-' + order.id"
              class="relative h-10 border-b border-gray-50"
            >
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

const { t } = useI18n()

// ===== State =====
const loading = ref(true)
const orders = ref([])
const workCenters = ref([])
const zoom = ref('day')

const timelineHeaderRef = ref(null)
const timelineBodyRef = ref(null)
const sidebarRef = ref(null)

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

const visibleDays = computed(() => {
  const days = []
  const shortDays = [
    t('general.sun') || 'Sun',
    t('general.mon') || 'Mon',
    t('general.tue') || 'Tue',
    t('general.wed') || 'Wed',
    t('general.thu') || 'Thu',
    t('general.fri') || 'Fri',
    t('general.sat') || 'Sat',
  ]
  const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
  ]
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

function barClass(order) {
  if (order.is_overdue) return 'bg-red-100 border border-red-400 text-red-800'
  if (order.status === 'completed') return 'bg-green-100 border border-green-300 text-green-800'
  if (order.status === 'in_progress') return 'bg-blue-100 border border-blue-300 text-blue-800'
  return 'bg-gray-100 border border-gray-300 text-gray-700' // draft
}

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

function startDrag(e, order) {
  if (!order.can_reschedule) return
  e.preventDefault()
  const clientX = e.touches ? e.touches[0].clientX : e.clientX
  dragging = order
  dragStartX = clientX
  dragOrigStart = order.start
  dragOrigEnd = order.end

  document.addEventListener('mousemove', onDrag)
  document.addEventListener('mouseup', endDrag)
  document.addEventListener('touchmove', onDrag, { passive: false })
  document.addEventListener('touchend', endDrag)
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

  document.removeEventListener('mousemove', onDrag)
  document.removeEventListener('mouseup', endDrag)
  document.removeEventListener('touchmove', onDrag)
  document.removeEventListener('touchend', endDrag)

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

  document.addEventListener('mousemove', onResize)
  document.addEventListener('mouseup', endResize)
  document.addEventListener('touchmove', onResize, { passive: false })
  document.addEventListener('touchend', endResize)
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

  document.removeEventListener('mousemove', onResize)
  document.removeEventListener('mouseup', endResize)
  document.removeEventListener('touchmove', onResize)
  document.removeEventListener('touchend', endResize)

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

onMounted(async () => {
  await fetchGanttData()
  // Auto-scroll to today
  setTimeout(() => scrollToToday(), 100)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousemove', onDrag)
  document.removeEventListener('mouseup', endDrag)
  document.removeEventListener('mousemove', onResize)
  document.removeEventListener('mouseup', endResize)
  document.removeEventListener('touchmove', onDrag)
  document.removeEventListener('touchend', endDrag)
  document.removeEventListener('touchmove', onResize)
  document.removeEventListener('touchend', endResize)
})
</script>
