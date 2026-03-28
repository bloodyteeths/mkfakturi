<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <StockTabNavigation />

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </div>

    <div v-else>
      <!-- KPI Cards Row -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Total Value -->
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
          <div class="text-sm font-medium text-gray-500 mb-1">Вкупна вредност</div>
          <div class="text-xl font-bold text-gray-900">{{ formatMkd(data.total_value) }}</div>
          <div class="text-xs text-gray-400 mt-1">МКД</div>
        </div>

        <!-- Total Items -->
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
          <div class="text-sm font-medium text-gray-500 mb-1">Артикли на залиха</div>
          <div class="text-xl font-bold text-gray-900">{{ data.total_items }}</div>
          <div class="text-xs text-gray-400 mt-1">{{ data.warehouses_count }} {{ data.warehouses_count === 1 ? 'магацин' : 'магацини' }}</div>
        </div>

        <!-- Low Stock -->
        <router-link
          to="/admin/stock/low-stock"
          class="bg-white rounded-lg shadow p-4 border-l-4 hover:shadow-md transition-shadow"
          :class="data.low_stock_count > 0 ? 'border-yellow-500' : 'border-gray-300'"
        >
          <div class="text-sm font-medium text-gray-500 mb-1">Ниска залиха</div>
          <div class="text-xl font-bold" :class="data.low_stock_count > 0 ? 'text-yellow-600' : 'text-gray-900'">
            {{ data.low_stock_count }}
          </div>
          <div class="text-xs text-gray-400 mt-1">под минимум</div>
        </router-link>

        <!-- Critical Stock -->
        <router-link
          to="/admin/stock/low-stock?severity=critical"
          class="bg-white rounded-lg shadow p-4 border-l-4 hover:shadow-md transition-shadow"
          :class="data.critical_stock_count > 0 ? 'border-red-500' : 'border-gray-300'"
        >
          <div class="text-sm font-medium text-gray-500 mb-1">Критична залиха</div>
          <div class="text-xl font-bold" :class="data.critical_stock_count > 0 ? 'text-red-600' : 'text-gray-900'">
            {{ data.critical_stock_count }}
          </div>
          <div class="text-xs text-gray-400 mt-1">0 или негативна</div>
        </router-link>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Movement Trend (line chart) -->
        <div class="bg-white rounded-lg shadow p-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Движења (последни 30 дена)</h3>
          <div class="h-[250px]">
            <canvas ref="trendCanvas" />
          </div>
        </div>

        <!-- Stock by Warehouse (doughnut chart) -->
        <div class="bg-white rounded-lg shadow p-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Вредност по магацин</h3>
          <div v-if="data.stock_by_warehouse.length === 0" class="flex items-center justify-center h-[250px] text-gray-400 text-sm">
            Нема податоци за магацини
          </div>
          <div v-else class="h-[250px]">
            <canvas ref="warehouseCanvas" />
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="flex flex-wrap gap-3 mb-6">
        <router-link
          to="/admin/stock/documents/create"
          class="inline-flex items-center px-4 py-2 bg-primary-500 text-white rounded-lg text-sm font-medium hover:bg-primary-600 transition-colors"
        >
          + Нов документ
        </router-link>
        <router-link
          to="/admin/stock/counts"
          class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors"
        >
          Попис
        </router-link>
        <router-link
          to="/admin/stock/wac-audit"
          class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors"
        >
          WAC Ревизија
        </router-link>
        <router-link
          to="/admin/stock/inventory"
          class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors"
        >
          Инвентар
        </router-link>
      </div>

      <!-- Recent Movements Table -->
      <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-4 py-3 border-b border-gray-200">
          <h3 class="text-sm font-semibold text-gray-700">Последни движења</h3>
        </div>
        <div v-if="data.recent_movements.length === 0" class="px-4 py-8 text-center text-gray-400 text-sm">
          Нема движења на залиха
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Датум</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Артикл</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Магацин</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Количина</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="m in data.recent_movements" :key="m.id" class="hover:bg-gray-50">
                <td class="px-4 py-2 text-sm text-gray-600 whitespace-nowrap">{{ m.date }}</td>
                <td class="px-4 py-2 text-sm text-gray-900">{{ m.item_name }}</td>
                <td class="px-4 py-2 text-sm text-gray-600">{{ m.source_type_label || m.source_type }}</td>
                <td class="px-4 py-2 text-sm text-gray-600">{{ m.warehouse_name }}</td>
                <td class="px-4 py-2 text-sm text-right whitespace-nowrap font-medium" :class="m.is_stock_in ? 'text-green-600' : 'text-red-600'">
                  {{ m.is_stock_in ? '+' : '' }}{{ m.quantity }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Items by Value -->
      <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b border-gray-200">
          <h3 class="text-sm font-semibold text-gray-700">Топ 5 артикли по вредност</h3>
        </div>
        <div v-if="data.top_items_by_value.length === 0" class="px-4 py-8 text-center text-gray-400 text-sm">
          Нема артикли на залиха
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Артикл</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Количина</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Вредност</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in data.top_items_by_value" :key="item.item_id" class="hover:bg-gray-50">
                <td class="px-4 py-2 text-sm text-gray-900 font-medium">{{ item.name }}</td>
                <td class="px-4 py-2 text-sm text-gray-500">{{ item.sku || '-' }}</td>
                <td class="px-4 py-2 text-sm text-right text-gray-600">{{ item.quantity }} {{ item.unit_name || '' }}</td>
                <td class="px-4 py-2 text-sm text-right text-gray-900 font-medium whitespace-nowrap">{{ formatMkd(item.total_value) }}</td>
                <td class="px-4 py-2 text-sm">
                  <router-link
                    :to="`/admin/stock/item-card/${item.item_id}`"
                    class="text-primary-600 hover:text-primary-700 text-xs"
                  >
                    Картица
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  ArcElement,
  DoughnutController,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  ArcElement,
  DoughnutController,
  Title,
  Tooltip,
  Legend
)

const loading = ref(true)
const trendCanvas = ref(null)
const warehouseCanvas = ref(null)

const data = ref({
  total_items: 0,
  total_value: 0,
  warehouses_count: 0,
  low_stock_count: 0,
  critical_stock_count: 0,
  recent_movements: [],
  top_items_by_value: [],
  movement_trend: [],
  stock_by_warehouse: [],
})

const formatMkd = (cents) => {
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

const warehouseColors = [
  '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
  '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1',
]

const renderTrendChart = () => {
  if (!trendCanvas.value || data.value.movement_trend.length === 0) return
  const ctx = trendCanvas.value.getContext('2d')
  if (!ctx) return

  new ChartJS(ctx, {
    type: 'line',
    data: {
      labels: data.value.movement_trend.map((d) => {
        const parts = d.date.split('-')
        return `${parts[2]}.${parts[1]}`
      }),
      datasets: [
        {
          label: 'Движења',
          data: data.value.movement_trend.map((d) => d.count),
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          fill: true,
          tension: 0.3,
          pointRadius: 2,
          pointHoverRadius: 5,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: (items) => {
              if (!items.length) return ''
              const idx = items[0].dataIndex
              return data.value.movement_trend[idx]?.date || ''
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 },
        },
        x: {
          ticks: {
            maxTicksLimit: 10,
            font: { size: 10 },
          },
        },
      },
    },
  })
}

const renderWarehouseChart = () => {
  if (!warehouseCanvas.value || data.value.stock_by_warehouse.length === 0) return
  const ctx = warehouseCanvas.value.getContext('2d')
  if (!ctx) return

  new ChartJS(ctx, {
    type: 'doughnut',
    data: {
      labels: data.value.stock_by_warehouse.map((w) => w.name),
      datasets: [
        {
          data: data.value.stock_by_warehouse.map((w) => w.value / 100),
          backgroundColor: data.value.stock_by_warehouse.map((_, i) => warehouseColors[i % warehouseColors.length]),
          borderWidth: 2,
          borderColor: '#fff',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: { font: { size: 11 } },
        },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const val = ctx.parsed
              return ` ${val.toLocaleString('mk-MK')} МКД`
            },
          },
        },
      },
    },
  })
}

onMounted(async () => {
  try {
    const { data: response } = await window.axios.get('/stock/dashboard')
    data.value = response
  } catch (err) {
    console.error('StockOverview: Failed to load dashboard data', err)
  } finally {
    loading.value = false
  }

  await nextTick()
  renderTrendChart()
  renderWarehouseChart()
})
</script>
// CLAUDE-CHECKPOINT
