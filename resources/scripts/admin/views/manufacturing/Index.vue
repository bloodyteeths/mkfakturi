<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <!-- Documents dropdown -->
        <div class="relative" ref="docsDropdownRef">
          <BaseButton variant="primary-outline" @click="showDocsDropdown = !showDocsDropdown">
            <template #left="slotProps">
              <DocumentTextIcon :class="slotProps.class" />
            </template>
            {{ t('manufacturing.dash_documents') }}
            <template #right="slotProps">
              <ChevronDownIcon :class="slotProps.class" />
            </template>
          </BaseButton>
          <div
            v-if="showDocsDropdown"
            class="absolute right-0 z-10 mt-2 w-64 rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
          >
            <p class="px-4 py-2 text-xs text-gray-500">{{ t('manufacturing.dash_documents_desc') }}</p>
            <div
              v-for="doc in availableDocuments"
              :key="doc.key"
              class="flex items-center px-4 py-2 text-sm text-gray-700"
            >
              <DocumentTextIcon class="mr-2 h-4 w-4 text-red-400" />
              {{ doc.label }}
            </div>
          </div>
        </div>

        <router-link to="/admin/manufacturing/shop-floor">
          <BaseButton variant="primary-outline">
            <template #left="slotProps">
              <PlayIcon :class="slotProps.class" />
            </template>
            {{ t('manufacturing.shop_floor_title') }}
          </BaseButton>
        </router-link>

        <router-link to="/admin/manufacturing/tv" target="_blank">
          <BaseButton variant="primary-outline">
            <template #left="slotProps">
              <TvIcon :class="slotProps.class" />
            </template>
            {{ t('manufacturing.tv_mode') }}
          </BaseButton>
        </router-link>

        <BaseButton variant="primary-outline" @click="showImportModal = true">
          <template #left="slotProps">
            <ArrowUpTrayIcon :class="slotProps.class" />
          </template>
          {{ t('manufacturing.import_pantheon') }}
        </BaseButton>

        <router-link to="/admin/manufacturing/work-centers">
          <BaseButton variant="primary-outline">
            <template #left="slotProps">
              <CogIcon :class="slotProps.class" />
            </template>
            {{ t('manufacturing.work_centers') }}
          </BaseButton>
        </router-link>

        <router-link to="/admin/manufacturing/orders/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('manufacturing.new_order') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- ==================== LOADING STATE ==================== -->
    <div v-if="isLoading" class="space-y-6">
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-6">
        <div v-for="i in 4" :key="i" class="animate-pulse rounded-lg bg-white p-5 shadow">
          <div class="mb-3 h-4 w-20 rounded bg-gray-200"></div>
          <div class="h-8 w-28 rounded bg-gray-200"></div>
        </div>
      </div>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div v-for="i in 2" :key="i" class="animate-pulse rounded-lg bg-white p-6 shadow">
          <div class="mb-4 h-5 w-40 rounded bg-gray-200"></div>
          <div class="space-y-3">
            <div v-for="j in 4" :key="j" class="h-4 rounded bg-gray-200"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== EMPTY STATE — GETTING STARTED ==================== -->
    <div v-else-if="isEmpty" class="mx-auto max-w-3xl">
      <div class="rounded-xl border-2 border-dashed border-gray-300 bg-white px-8 py-12 text-center">
        <CogIcon class="mx-auto h-16 w-16 text-gray-300" />
        <h2 class="mt-4 text-xl font-bold text-gray-900">
          {{ t('manufacturing.dash_getting_started') }}
        </h2>
        <p class="mx-auto mt-2 max-w-lg text-sm text-gray-500">
          {{ t('manufacturing.dash_getting_started_desc') }}
        </p>

        <div class="mx-auto mt-8 grid max-w-xl grid-cols-1 gap-4 text-left sm:grid-cols-2">
          <router-link
            to="/admin/manufacturing/boms/create"
            class="group flex items-start rounded-lg border-2 border-gray-200 p-4 transition hover:border-primary-500 hover:shadow-md"
          >
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-600">1</div>
            <div class="ml-3">
              <p class="font-semibold text-gray-900 group-hover:text-primary-600">{{ t('manufacturing.dash_step1_title') }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ t('manufacturing.dash_step1_desc') }}</p>
            </div>
          </router-link>

          <router-link
            to="/admin/manufacturing/orders/create"
            class="group flex items-start rounded-lg border-2 border-gray-200 p-4 transition hover:border-green-500 hover:shadow-md"
          >
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-600">2</div>
            <div class="ml-3">
              <p class="font-semibold text-gray-900 group-hover:text-green-600">{{ t('manufacturing.dash_step2_title') }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ t('manufacturing.dash_step2_desc') }}</p>
            </div>
          </router-link>

          <div class="flex items-start rounded-lg border-2 border-gray-100 bg-gray-50 p-4">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">3</div>
            <div class="ml-3">
              <p class="font-semibold text-gray-700">{{ t('manufacturing.dash_step3_title') }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ t('manufacturing.dash_step3_desc') }}</p>
            </div>
          </div>

          <div class="flex items-start rounded-lg border-2 border-gray-100 bg-gray-50 p-4">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600">4</div>
            <div class="ml-3">
              <p class="font-semibold text-gray-700">{{ t('manufacturing.dash_step4_title') }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ t('manufacturing.dash_step4_desc') }}</p>
            </div>
          </div>
        </div>

        <!-- AI helper in empty state too -->
        <div class="mx-auto mt-8 max-w-xl rounded-lg border border-purple-200 bg-purple-50 p-4 text-left">
          <div class="flex items-center">
            <SparklesIcon class="h-5 w-5 text-purple-600" />
            <span class="ml-2 text-sm font-semibold text-purple-900">{{ t('manufacturing.dash_ai_title') }}</span>
          </div>
          <p class="mt-1 text-xs text-purple-700">{{ t('manufacturing.dash_ai_empty_hint') }}</p>
        </div>
      </div>
    </div>

    <!-- ==================== FULL DASHBOARD ==================== -->
    <template v-else>

      <!-- ROW 1: KPI Cards -->
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-6">
        <router-link
          to="/admin/manufacturing/orders?status=in_progress"
          class="rounded-lg bg-white p-4 shadow transition hover:shadow-md xl:p-5"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_active_orders') }}</span>
            <PlayIcon class="h-5 w-5 text-blue-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-gray-900 xl:text-3xl">{{ data.kpis.active_orders }}</p>
          <p v-if="data.kpis.overdue_count > 0" class="mt-1 text-xs font-medium text-red-600">
            {{ data.kpis.overdue_count }} {{ t('manufacturing.dash_overdue') }}
          </p>
        </router-link>

        <router-link
          to="/admin/manufacturing/orders?status=completed"
          class="rounded-lg bg-white p-4 shadow transition hover:shadow-md xl:p-5"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_completed_month') }}</span>
            <CheckCircleIcon class="h-5 w-5 text-green-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-gray-900 xl:text-3xl">{{ data.kpis.completed_this_month }}</p>
          <p class="mt-1 text-xs text-gray-500">{{ data.period.label }}</p>
        </router-link>

        <router-link
          to="/admin/manufacturing/reports/cost-analysis"
          class="rounded-lg bg-white p-4 shadow transition hover:shadow-md xl:p-5"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_production_cost') }}</span>
            <BanknotesIcon class="h-5 w-5 text-indigo-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-gray-900 xl:text-3xl">{{ formatMoney(data.kpis.total_production_cost_month) }}</p>
          <p class="mt-1 text-xs text-gray-500">{{ data.period.label }}</p>
        </router-link>

        <router-link
          to="/admin/manufacturing/reports/wastage"
          class="rounded-lg bg-white p-4 shadow transition hover:shadow-md xl:p-5"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_wastage_rate') }}</span>
            <ExclamationTriangleIcon class="h-5 w-5" :class="data.kpis.wastage_percent > 10 ? 'text-red-500' : 'text-amber-500'" />
          </div>
          <p class="mt-2 text-2xl font-bold xl:text-3xl" :class="data.kpis.wastage_percent > 10 ? 'text-red-600' : 'text-gray-900'">
            {{ data.kpis.wastage_percent }}%
          </p>
          <p class="mt-1 text-xs text-gray-500">{{ t('manufacturing.of_total_production') }}</p>
        </router-link>
      </div>

      <!-- ROW 2: Production Cost Trend Chart -->
      <div class="mt-4 lg:mt-6">
        <div class="rounded-lg bg-white p-5 shadow">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.dash_cost_trend') }}</h3>
            <div class="flex items-center gap-4 text-xs text-gray-500">
              <span class="flex items-center"><span class="mr-1.5 inline-block h-2.5 w-2.5 rounded-full bg-indigo-500"></span>{{ t('manufacturing.total_production_cost') }}</span>
              <span class="flex items-center"><span class="mr-1.5 inline-block h-2.5 w-2.5 rounded-full bg-red-400"></span>{{ t('manufacturing.total_wastage_cost') }}</span>
              <span class="flex items-center"><span class="mr-1.5 inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>{{ t('manufacturing.quantity') }}</span>
            </div>
          </div>
          <div class="h-[220px]">
            <canvas ref="costChartRef"></canvas>
          </div>
        </div>
      </div>

      <!-- ROW 3: OEE Metrics (only if work centers exist) -->
      <div v-if="data.oee && data.oee.work_centers && data.oee.work_centers.length > 0" class="mt-4 lg:mt-6">
        <div class="rounded-lg bg-white p-5 shadow">
          <div class="mb-4 flex items-center justify-between">
            <div>
              <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.dash_oee_title') }}</h3>
              <p class="mt-0.5 text-xs text-gray-500">{{ t('manufacturing.dash_oee_subtitle') }}</p>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs text-gray-500">{{ t('manufacturing.dash_oee_overall') }}:</span>
              <span
                class="rounded-full px-3 py-1 text-sm font-bold"
                :class="oeeColor(data.oee.overall)"
              >{{ data.oee.overall }}%</span>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div
              v-for="wc in data.oee.work_centers"
              :key="wc.id"
              class="rounded-lg border border-gray-100 p-4 transition hover:shadow-md cursor-pointer"
              @click="$router.push(`/admin/manufacturing/work-centers/${wc.id}`)"
            >
              <div class="flex items-center justify-between">
                <p class="truncate text-sm font-semibold text-gray-900">{{ wc.name }}</p>
                <span
                  class="rounded-full px-2 py-0.5 text-xs font-bold"
                  :class="oeeColor(wc.oee)"
                >{{ wc.oee }}%</span>
              </div>
              <p class="mt-0.5 text-xs text-gray-500">{{ wc.code }} · {{ wc.order_count }} {{ t('manufacturing.dash_orders_label') }}</p>

              <!-- OEE Breakdown -->
              <div class="mt-3 space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-500">{{ t('manufacturing.dash_oee_availability') }}</span>
                  <span :class="wc.availability >= 80 ? 'text-green-600' : wc.availability >= 60 ? 'text-yellow-600' : 'text-red-600'" class="font-medium">{{ wc.availability }}%</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                  <div class="h-full rounded-full bg-blue-400 transition-all" :style="{ width: Math.min(wc.availability, 100) + '%' }"></div>
                </div>

                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-500">{{ t('manufacturing.dash_oee_performance') }}</span>
                  <span :class="wc.performance >= 80 ? 'text-green-600' : wc.performance >= 60 ? 'text-yellow-600' : 'text-red-600'" class="font-medium">{{ wc.performance }}%</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                  <div class="h-full rounded-full bg-emerald-400 transition-all" :style="{ width: Math.min(wc.performance, 100) + '%' }"></div>
                </div>

                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-500">{{ t('manufacturing.dash_oee_quality') }}</span>
                  <span :class="wc.quality >= 90 ? 'text-green-600' : wc.quality >= 75 ? 'text-yellow-600' : 'text-red-600'" class="font-medium">{{ wc.quality }}%</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                  <div class="h-full rounded-full bg-purple-400 transition-all" :style="{ width: Math.min(wc.quality, 100) + '%' }"></div>
                </div>
              </div>

              <!-- Target comparison -->
              <div v-if="wc.target_oee > 0" class="mt-2 flex items-center justify-between border-t border-gray-100 pt-2 text-xs">
                <span class="text-gray-400">{{ t('manufacturing.dash_oee_target') }}: {{ wc.target_oee }}%</span>
                <span v-if="wc.oee >= wc.target_oee" class="font-medium text-green-600">✓</span>
                <span v-else class="font-medium text-red-500">{{ (wc.oee - wc.target_oee).toFixed(1) }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ROW 3.5: QC Quality Metrics -->
      <div v-if="qcData && qcData.summary && qcData.summary.total_checks > 0" class="mt-4 lg:mt-6">
        <div class="rounded-lg bg-white shadow">
          <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.qc_metrics') }}</h3>
          </div>
          <div class="p-5">
            <!-- KPI cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
              <div class="rounded-lg bg-green-50 p-3 text-center">
                <p class="text-2xl font-bold" :class="qcData.summary.pass_rate >= 90 ? 'text-green-700' : qcData.summary.pass_rate >= 70 ? 'text-yellow-700' : 'text-red-700'">
                  {{ qcData.summary.pass_rate }}%
                </p>
                <p class="text-xs text-gray-500 mt-1">{{ t('manufacturing.qc_pass_rate') }}</p>
              </div>
              <div class="rounded-lg bg-red-50 p-3 text-center">
                <p class="text-2xl font-bold text-red-700">{{ qcData.summary.reject_rate }}%</p>
                <p class="text-xs text-gray-500 mt-1">{{ t('manufacturing.qc_reject_rate') }}</p>
              </div>
              <div class="rounded-lg bg-blue-50 p-3 text-center">
                <p class="text-2xl font-bold text-blue-700">{{ qcData.summary.total_checks }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ t('manufacturing.qc_total_inspections') }}</p>
              </div>
              <div class="rounded-lg bg-purple-50 p-3 text-center">
                <p class="text-2xl font-bold text-purple-700">{{ Math.round(qcData.summary.total_inspected) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ t('manufacturing.unit') }}</p>
              </div>
            </div>

            <!-- Quality trend chart -->
            <div v-if="qcData.trend" class="mt-5">
              <h4 class="text-sm font-medium text-gray-700 mb-2">{{ t('manufacturing.qc_quality_trend') }}</h4>
              <canvas ref="qcChartRef" height="120"></canvas>
            </div>

            <!-- Worst products -->
            <div v-if="qcData.worst_products && qcData.worst_products.length > 0" class="mt-5">
              <h4 class="text-sm font-medium text-gray-700 mb-2">{{ t('manufacturing.qc_worst_products') }}</h4>
              <div class="space-y-2">
                <div v-for="(p, i) in qcData.worst_products" :key="i" class="flex items-center justify-between text-sm">
                  <span class="text-gray-700 truncate flex-1">{{ p.item_name }}</span>
                  <span class="ml-2 text-xs text-gray-400">{{ p.inspections }} insp.</span>
                  <span
                    class="ml-3 rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="p.pass_rate >= 90 ? 'bg-green-100 text-green-700' : p.pass_rate >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                  >
                    {{ p.pass_rate }}%
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ROW 4: Material Availability + Production Timeline -->
      <div class="mt-4 grid grid-cols-1 gap-4 lg:mt-6 lg:grid-cols-2 lg:gap-6">

        <!-- Material Availability (traffic lights) -->
        <div class="rounded-lg bg-white shadow">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.dash_material_avail') }}</h3>
            <router-link to="/admin/manufacturing/boms" class="text-sm font-medium text-primary-500 hover:text-primary-600">
              {{ t('manufacturing.dash_view_all') }}
            </router-link>
          </div>
          <div v-if="data.material_availability && data.material_availability.length > 0" class="divide-y divide-gray-100">
            <div
              v-for="bom in data.material_availability"
              :key="bom.bom_id"
              class="flex items-center px-5 py-3 transition hover:bg-gray-50 cursor-pointer"
              @click="$router.push(`/admin/manufacturing/boms/${bom.bom_id}`)"
            >
              <!-- Traffic light -->
              <div class="flex-shrink-0">
                <span
                  class="inline-block h-3.5 w-3.5 rounded-full"
                  :class="{
                    'bg-green-500': bom.status === 'green',
                    'bg-yellow-400': bom.status === 'yellow',
                    'bg-red-500': bom.status === 'red',
                  }"
                ></span>
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <p class="truncate text-sm font-medium text-gray-900">{{ bom.output_item || bom.bom_name }}</p>
                <p class="text-xs text-gray-500">{{ bom.bom_code }} · {{ bom.material_count }} {{ t('manufacturing.lines').toLowerCase() }}</p>
                <!-- Show shortage details inline -->
                <div v-if="bom.shortages && bom.shortages.length > 0" class="mt-1 space-y-0.5">
                  <p v-for="(s, si) in bom.shortages.slice(0, 2)" :key="si" class="text-xs" :class="s.below_minimum ? 'text-amber-600' : 'text-red-600'">
                    {{ s.item_name }}: {{ s.deficit > 0 ? `−${s.deficit}` : '' }}{{ s.below_minimum ? ` ⚠ ${t('manufacturing.dash_below_min')}` : '' }}
                  </p>
                </div>
              </div>
              <div class="ml-3 flex-shrink-0">
                <span
                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-800': bom.status === 'green',
                    'bg-yellow-100 text-yellow-800': bom.status === 'yellow',
                    'bg-red-100 text-red-800': bom.status === 'red',
                  }"
                >
                  {{ bom.status === 'green' ? t('manufacturing.stock_ok') : bom.status === 'yellow' ? t('manufacturing.dash_stock_low') : t('manufacturing.shortage') }}
                </span>
              </div>
            </div>
          </div>
          <!-- Reorder button for shortages -->
          <div v-if="hasShortages" class="border-t border-gray-100 px-5 py-3">
            <button
              @click.stop="reorderShortages"
              :disabled="reordering"
              class="flex w-full items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <ArrowPathIcon v-if="reordering" class="mr-2 h-4 w-4 animate-spin" />
              <BanknotesIcon v-else class="mr-2 h-4 w-4" />
              {{ t('manufacturing.dash_reorder') }}
            </button>
            <p class="mt-1 text-center text-xs text-gray-500">{{ t('manufacturing.dash_reorder_desc') }}</p>
          </div>
          <div v-else-if="!data.material_availability || data.material_availability.length === 0" class="px-5 py-8 text-center">
            <ClipboardDocumentListIcon class="mx-auto h-8 w-8 text-gray-300" />
            <p class="mt-2 text-xs text-gray-500">{{ t('manufacturing.empty_boms') }}</p>
          </div>
        </div>

        <!-- Production Timeline (mini-Gantt) -->
        <div class="rounded-lg bg-white shadow">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.dash_timeline') }}</h3>
            <router-link to="/admin/manufacturing/gantt" class="text-sm font-medium text-primary-500 hover:text-primary-600">
              {{ t('manufacturing.gantt_title') }} &rarr;
            </router-link>
          </div>
          <div v-if="data.timeline && data.timeline.length > 0" class="p-5">
            <div class="space-y-2">
              <div
                v-for="order in data.timeline"
                :key="order.id"
                class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-gray-50 cursor-pointer"
                @click="$router.push(`/admin/manufacturing/orders/${order.id}`)"
              >
                <!-- Status dot -->
                <span
                  class="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                  :class="{
                    'bg-gray-400': order.status === 'draft',
                    'bg-blue-500': order.status === 'in_progress' && !order.is_overdue,
                    'bg-red-500 animate-pulse': order.is_overdue,
                  }"
                ></span>
                <!-- Order info -->
                <div class="flex-1 min-w-0">
                  <p class="truncate text-sm font-medium" :class="order.is_overdue ? 'text-red-700' : 'text-gray-900'">
                    {{ order.item_name || order.order_number }}
                  </p>
                  <p class="text-xs text-gray-500">{{ order.planned_quantity }} {{ t('manufacturing.unit') }} · {{ order.start }} → {{ order.end }}</p>
                </div>
                <!-- Gantt bar -->
                <div class="hidden w-32 sm:block">
                  <div class="relative h-4 overflow-hidden rounded-full bg-gray-100">
                    <div
                      class="absolute inset-y-0 left-0 rounded-full"
                      :class="{
                        'bg-gray-300': order.status === 'draft',
                        'bg-blue-400': order.status === 'in_progress' && !order.is_overdue,
                        'bg-red-400': order.is_overdue,
                      }"
                      :style="{ width: ganttWidth(order) + '%' }"
                    ></div>
                  </div>
                </div>
                <!-- Status badge -->
                <span
                  :class="statusBadge(order.status)"
                  class="hidden flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold md:inline-flex"
                >
                  {{ t('manufacturing.status_' + order.status) }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="px-5 py-8 text-center">
            <CalendarDaysIcon class="mx-auto h-8 w-8 text-gray-300" />
            <p class="mt-2 text-xs text-gray-500">{{ t('manufacturing.dash_no_scheduled') }}</p>
          </div>
        </div>
      </div>

      <!-- ROW 4: AI Assistant + Production Pipeline -->
      <div class="mt-4 grid grid-cols-1 gap-4 lg:mt-6 lg:grid-cols-2 lg:gap-6">

        <!-- AI Production Assistant -->
        <div class="rounded-lg bg-white shadow">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div class="flex items-center">
              <div class="rounded-lg bg-purple-100 p-2">
                <SparklesIcon class="h-5 w-5 text-purple-600" />
              </div>
              <div class="ml-3">
                <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.dash_ai_title') }}</h3>
                <p class="text-xs text-gray-500">{{ t('manufacturing.dash_ai_subtitle') }}</p>
              </div>
            </div>
            <button
              v-if="aiInsights.length > 0"
              @click="fetchAiInsights"
              :disabled="aiLoading"
              class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
            >
              <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': aiLoading }" />
            </button>
          </div>

          <div class="p-5">
            <!-- Natural Language Order Creator -->
            <div class="mb-4 rounded-lg border border-purple-200 bg-purple-50 p-3">
              <label class="mb-1.5 block text-xs font-medium text-purple-900">{{ t('manufacturing.ai_parse_order') }}</label>
              <div class="flex gap-2">
                <input
                  v-model="aiInput"
                  type="text"
                  :placeholder="t('manufacturing.ai_parse_placeholder')"
                  class="flex-1 rounded-lg border border-purple-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                  @keydown.enter="parseAiOrder"
                />
                <button
                  @click="parseAiOrder"
                  :disabled="!aiInput.trim() || aiParsing"
                  class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <ArrowPathIcon v-if="aiParsing" class="h-4 w-4 animate-spin" />
                  <span v-else>{{ t('manufacturing.ai_parse') }}</span>
                </button>
              </div>
              <!-- Parsed result -->
              <div v-if="parsedOrder" class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3">
                <p class="text-xs font-medium text-green-800">{{ t('manufacturing.dash_ai_parsed') }}:</p>
                <p class="mt-1 text-sm text-green-900">
                  {{ parsedBomName || t('manufacturing.select_bom') }} — {{ parsedOrder.quantity }} {{ t('manufacturing.unit') }}
                  <span v-if="parsedOrder.deadline" class="text-green-700">({{ parsedOrder.deadline }})</span>
                </p>
                <router-link
                  :to="parsedOrderLink"
                  class="mt-2 inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                >
                  <PlusCircleIcon class="mr-1 h-3.5 w-3.5" />
                  {{ t('manufacturing.new_order') }}
                </router-link>
              </div>
            </div>

            <!-- AI Insights -->
            <div v-if="aiLoading" class="flex items-center justify-center py-6">
              <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-purple-600"></div>
            </div>

            <div v-else-if="aiInsights.length > 0" class="space-y-2.5">
              <div
                v-for="(insight, idx) in aiInsights"
                :key="idx"
                class="rounded-lg border-l-4 bg-gray-50 p-3"
                :class="insightBorderClass(insight.type)"
              >
                <div class="flex items-start">
                  <component :is="insightIcon(insight.type)" class="mr-2 h-4 w-4 flex-shrink-0 mt-0.5" :class="insightIconClass(insight.type)" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ insight.title }}</p>
                    <p class="mt-0.5 text-xs text-gray-600">{{ insight.description }}</p>
                    <p v-if="insight.action" class="mt-1 text-xs font-medium text-purple-700">{{ insight.action }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-4">
              <button
                @click="fetchAiInsights"
                :disabled="aiLoading"
                class="rounded-lg bg-purple-100 px-4 py-2 text-sm font-medium text-purple-700 transition hover:bg-purple-200 disabled:opacity-50"
              >
                <SparklesIcon class="mr-1.5 inline h-4 w-4" />
                {{ t('manufacturing.dash_ai_generate') }}
              </button>
              <p class="mt-2 text-xs text-gray-500">{{ t('manufacturing.dash_ai_generate_desc') }}</p>
            </div>
          </div>
        </div>

        <!-- Production Pipeline -->
        <div class="rounded-lg bg-white p-5 shadow">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.dash_pipeline') }}</h3>
            <router-link to="/admin/manufacturing/orders" class="text-sm font-medium text-primary-500 hover:text-primary-600">
              {{ t('manufacturing.dash_view_all') }}
            </router-link>
          </div>

          <div class="space-y-3">
            <router-link
              v-for="s in pipelineStages"
              :key="s.key"
              :to="`/admin/manufacturing/orders?status=${s.key}`"
              class="flex items-center rounded-lg p-3 transition hover:bg-gray-50"
            >
              <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" :class="s.bgClass">
                <component :is="s.icon" class="h-5 w-5" :class="s.iconClass" />
              </div>
              <div class="ml-3 flex-1">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-gray-900">{{ s.label }}</span>
                  <span class="text-sm font-bold" :class="s.countClass">{{ s.count }}</span>
                </div>
                <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                  <div class="h-full rounded-full transition-all duration-500" :class="s.barClass" :style="{ width: pipelinePercent(s.count) + '%' }"></div>
                </div>
              </div>
            </router-link>
          </div>

          <!-- BOM + Quick Create -->
          <div class="mt-4 space-y-2">
            <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 p-3">
              <div class="flex items-center">
                <ClipboardDocumentListIcon class="h-5 w-5 text-primary-500" />
                <span class="ml-2 text-sm text-gray-700">
                  {{ t('manufacturing.boms') }}:
                  <strong>{{ data.boms.active }}</strong> {{ t('manufacturing.dash_active_of') }} {{ data.boms.total }}
                </span>
              </div>
              <router-link to="/admin/manufacturing/boms" class="text-sm font-medium text-primary-500 hover:text-primary-600">
                {{ t('manufacturing.dash_manage') }}
              </router-link>
            </div>

            <!-- Compact Quick Actions Row -->
            <div class="flex gap-2">
              <router-link to="/admin/manufacturing/boms/create" class="flex flex-1 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-2.5 text-xs font-medium text-gray-600 transition hover:border-primary-500 hover:text-primary-600">
                <ClipboardDocumentListIcon class="mr-1.5 h-4 w-4" />
                {{ t('manufacturing.new_bom') }}
              </router-link>
              <router-link to="/admin/manufacturing/orders/create" class="flex flex-1 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-2.5 text-xs font-medium text-gray-600 transition hover:border-green-500 hover:text-green-600">
                <PlusCircleIcon class="mr-1.5 h-4 w-4" />
                {{ t('manufacturing.new_order') }}
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- ROW 3: Recent Orders + Top Products & Reports -->
      <div class="mt-4 grid grid-cols-1 gap-4 lg:mt-6 lg:grid-cols-3 lg:gap-6">

        <!-- Recent Orders Table (2/3 width) -->
        <div class="rounded-lg bg-white shadow lg:col-span-2">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.dash_recent_orders') }}</h3>
            <router-link to="/admin/manufacturing/orders" class="text-sm font-medium text-primary-500 hover:text-primary-600">
              {{ t('manufacturing.dash_view_all') }}
            </router-link>
          </div>

          <div v-if="data.recent_orders.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.order_number') }}</th>
                  <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.output_item') }}</th>
                  <th class="hidden px-4 py-2.5 text-center text-xs font-medium uppercase tracking-wider text-gray-500 sm:table-cell">{{ t('manufacturing.status') }}</th>
                  <th class="hidden px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 md:table-cell">{{ t('manufacturing.planned_quantity') }}</th>
                  <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.total_production_cost') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr
                  v-for="order in data.recent_orders"
                  :key="order.id"
                  class="cursor-pointer transition hover:bg-gray-50"
                  @click="$router.push(`/admin/manufacturing/orders/${order.id}`)"
                >
                  <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-primary-600">{{ order.order_number }}</td>
                  <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{{ order.item_name || '-' }}</td>
                  <td class="hidden whitespace-nowrap px-4 py-3 text-center sm:table-cell">
                    <span :class="statusBadge(order.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold leading-5">
                      {{ t('manufacturing.status_' + order.status) }}
                    </span>
                  </td>
                  <td class="hidden whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 md:table-cell">{{ order.planned_quantity }}</td>
                  <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">
                    {{ order.total_production_cost ? formatMoney(order.total_production_cost) : '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="px-5 py-8 text-center">
            <p class="text-sm text-gray-500">{{ t('manufacturing.empty_orders') }}</p>
          </div>
        </div>

        <!-- Right Column: Top Products + Reports -->
        <div class="space-y-4 lg:space-y-6">
          <!-- Top Products -->
          <div class="rounded-lg bg-white p-5 shadow">
            <h3 class="mb-3 text-base font-semibold text-gray-900">{{ t('manufacturing.dash_top_products') }}</h3>
            <div v-if="data.top_products.length > 0" class="space-y-2.5">
              <div v-for="(product, idx) in data.top_products" :key="idx" class="rounded-lg border border-gray-100 p-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-gray-900">{{ product.item_name }}</span>
                  <span class="text-xs font-medium text-gray-500">{{ product.orders }} {{ t('manufacturing.dash_orders_label') }}</span>
                </div>
                <div class="mt-1 flex items-center justify-between">
                  <span class="text-xs text-gray-500">{{ t('manufacturing.quantity') }}: {{ formatQty(product.quantity) }}</span>
                  <span class="text-xs font-semibold text-indigo-600">{{ formatMoney(product.cost) }}</span>
                </div>
              </div>
            </div>
            <div v-else class="py-4 text-center">
              <ChartBarIcon class="mx-auto h-8 w-8 text-gray-300" />
              <p class="mt-1 text-xs text-gray-500">{{ t('manufacturing.no_completed_orders') }}</p>
            </div>
          </div>

          <!-- Reports Quick Links -->
          <div class="rounded-lg bg-white p-5 shadow">
            <h3 class="mb-3 text-base font-semibold text-gray-900">{{ t('manufacturing.reports') }}</h3>
            <div class="space-y-1.5">
              <router-link
                to="/admin/manufacturing/reports/cost-analysis"
                class="flex items-center rounded-lg p-2.5 text-sm text-gray-700 transition hover:bg-blue-50 hover:text-blue-700"
              >
                <ChartBarIcon class="mr-2.5 h-4 w-4 text-blue-500" />
                {{ t('manufacturing.cost_analysis') }}
                <ChevronRightIcon class="ml-auto h-4 w-4 text-gray-400" />
              </router-link>
              <router-link
                to="/admin/manufacturing/reports/variance"
                class="flex items-center rounded-lg p-2.5 text-sm text-gray-700 transition hover:bg-yellow-50 hover:text-yellow-700"
              >
                <ArrowsRightLeftIcon class="mr-2.5 h-4 w-4 text-yellow-500" />
                {{ t('manufacturing.variance_report') }}
                <ChevronRightIcon class="ml-auto h-4 w-4 text-gray-400" />
              </router-link>
              <router-link
                to="/admin/manufacturing/reports/wastage"
                class="flex items-center rounded-lg p-2.5 text-sm text-gray-700 transition hover:bg-red-50 hover:text-red-700"
              >
                <ExclamationTriangleIcon class="mr-2.5 h-4 w-4 text-red-500" />
                {{ t('manufacturing.wastage_report') }}
                <ChevronRightIcon class="ml-auto h-4 w-4 text-gray-400" />
              </router-link>
            </div>
          </div>
        </div>
      </div>

    </template>

    <!-- PANTHEON Import Modal -->
    <teleport to="body">
      <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="mx-4 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
          <h3 class="text-lg font-medium text-gray-900">{{ t('manufacturing.import_pantheon') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ t('manufacturing.import_desc') }}</p>

          <div class="mt-4 space-y-4">
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-6 text-center">
              <input
                ref="importFileInput"
                type="file"
                accept=".csv,.txt,.xml"
                @change="onImportFileChange"
                class="hidden"
              />
              <button
                @click="$refs.importFileInput.click()"
                class="text-sm font-medium text-primary-600 hover:text-primary-700"
              >
                {{ importFile ? importFile.name : t('manufacturing.import_choose_file') }}
              </button>
              <p class="mt-1 text-xs text-gray-400">CSV / XML (PANTHEON Sestavnice)</p>
            </div>

            <!-- Preview results -->
            <div v-if="importPreview" class="rounded-lg bg-gray-50 p-4">
              <p class="text-sm font-medium text-gray-900">
                {{ t('manufacturing.import_preview_count', { boms: importPreview.total_boms, materials: importPreview.total_materials }) }}
              </p>
              <div class="mt-2 max-h-48 overflow-y-auto space-y-1">
                <div v-for="bom in importPreview.boms" :key="bom.product_name" class="flex items-center justify-between text-xs">
                  <span :class="bom.product_matched ? 'text-green-700' : 'text-orange-600'">
                    {{ bom.product_matched ? '✓' : '●' }} {{ bom.product_name }}
                  </span>
                  <span class="text-gray-500">{{ bom.matched_count }}/{{ bom.materials.length }} {{ t('manufacturing.import_matched') }}</span>
                </div>
              </div>
            </div>

            <!-- Import result -->
            <div v-if="importResult" class="rounded-lg bg-green-50 p-4">
              <p class="text-sm font-medium text-green-800">{{ importResult }}</p>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <BaseButton variant="primary-outline" @click="closeImportModal">
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton
              v-if="importFile && !importPreview"
              variant="primary-outline"
              :loading="importLoading"
              @click="previewImport"
            >
              {{ t('manufacturing.import_preview') }}
            </BaseButton>
            <BaseButton
              v-if="importPreview"
              variant="primary"
              :loading="importLoading"
              @click="executeImport"
            >
              {{ t('manufacturing.import_execute') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </teleport>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  BarElement,
  BarController,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import {
  CogIcon,
  PlayIcon,
  CheckCircleIcon,
  BanknotesIcon,
  ExclamationTriangleIcon,
  ClipboardDocumentListIcon,
  PlusCircleIcon,
  ChartBarIcon,
  ArrowsRightLeftIcon,
  DocumentTextIcon,
  PencilSquareIcon,
  XCircleIcon,
  SparklesIcon,
  ArrowPathIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  LightBulbIcon,
  ShieldExclamationIcon,
  ArrowTrendingUpIcon,
  InformationCircleIcon,
  CalendarDaysIcon,
  TvIcon,
  ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'

// Register Chart.js components
ChartJS.register(
  CategoryScale, LinearScale, PointElement, LineElement, LineController,
  BarElement, BarController, Title, Tooltip, Legend, Filler,
)

const { t } = useI18n()
const companyStore = useCompanyStore()

// ===== Import state =====
const showImportModal = ref(false)
const importFile = ref(null)
const importPreview = ref(null)
const importResult = ref(null)
const importLoading = ref(false)

function onImportFileChange(e) {
  importFile.value = e.target.files[0] || null
  importPreview.value = null
  importResult.value = null
}

async function previewImport() {
  if (!importFile.value) return
  importLoading.value = true
  try {
    const form = new FormData()
    form.append('file', importFile.value)
    const res = await window.axios.post('/manufacturing/import/preview', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    importPreview.value = res.data?.data || null
  } catch (err) {
    window.$utils?.showNotification?.({ type: 'error', message: err.response?.data?.message || 'Preview failed' })
  } finally {
    importLoading.value = false
  }
}

async function executeImport() {
  if (!importFile.value) return
  importLoading.value = true
  try {
    const form = new FormData()
    form.append('file', importFile.value)
    form.append('create_missing_items', '1')
    const res = await window.axios.post('/manufacturing/import', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    importResult.value = res.data?.message || 'Import complete'
    importPreview.value = null
    // Refresh dashboard
    fetchDashboard()
  } catch (err) {
    window.$utils?.showNotification?.({ type: 'error', message: err.response?.data?.message || 'Import failed' })
  } finally {
    importLoading.value = false
  }
}

function closeImportModal() {
  showImportModal.value = false
  importFile.value = null
  importPreview.value = null
  importResult.value = null
}

// ===== Dashboard data =====
const isLoading = ref(true)
const data = ref({
  kpis: {
    total_production_cost_month: 0,
    active_orders: 0,
    completed_this_month: 0,
    wastage_percent: 0,
    overdue_count: 0,
    avg_cost_per_unit: 0,
    active_production_cost: 0,
  },
  pipeline: { draft: 0, in_progress: 0, completed: 0, cancelled: 0 },
  boms: { total: 0, active: 0 },
  recent_orders: [],
  top_products: [],
  chart: { labels: [], production_cost: [], wastage_cost: [], quantity: [], order_count: [] },
  material_availability: [],
  oee: { overall: 0, work_centers: [] },
  timeline: [],
  period: { month: '', label: '' },
})

// ===== QC Metrics =====
const qcData = ref(null)
const qcChartRef = ref(null)
let qcChartInstance = null

// ===== Reorder state =====
const reordering = ref(false)

const hasShortages = computed(() => {
  return (data.value.material_availability || []).some(
    b => b.status === 'red' && b.shortages && b.shortages.length > 0
  )
})

async function reorderShortages() {
  if (reordering.value) return
  reordering.value = true
  try {
    // Collect all shortage items across all BOMs
    const items = []
    for (const bom of data.value.material_availability || []) {
      if (bom.shortages) {
        for (const s of bom.shortages) {
          if (s.deficit > 0 && s.item_id) {
            // Avoid duplicates — accumulate by item_id
            const existing = items.find(i => i.item_id === s.item_id)
            if (existing) {
              existing.quantity += s.deficit
            } else {
              items.push({ item_id: s.item_id, quantity: s.deficit })
            }
          }
        }
      }
    }

    if (items.length === 0) return

    const res = await window.axios.post('/manufacturing/smart-reorder', { items })
    const created = res.data?.data?.count || 0
    if (created > 0) {
      window.$utils?.showNotification?.({
        type: 'success',
        message: t('manufacturing.dash_reorder_success', { count: created }),
      })
    } else {
      window.$utils?.showNotification?.({
        type: 'warning',
        message: t('manufacturing.dash_reorder_no_supplier'),
      })
    }
  } catch (error) {
    console.error('Smart reorder failed:', error)
    const msg = error.response?.data?.message || t('manufacturing.error_loading')
    window.$utils?.showNotification?.({
      type: 'error',
      message: msg,
    })
  } finally {
    reordering.value = false
  }
}

// ===== AI state =====
const aiInput = ref('')
const aiParsing = ref(false)
const parsedOrder = ref(null)
const aiLoading = ref(false)
const aiInsights = ref([])

// ===== Chart =====
const costChartRef = ref(null)
let chartInstance = null

// ===== Documents dropdown =====
const showDocsDropdown = ref(false)
const docsDropdownRef = ref(null)

function handleClickOutside(e) {
  if (docsDropdownRef.value && !docsDropdownRef.value.contains(e.target)) {
    showDocsDropdown.value = false
  }
}

// ===== Computed =====
const currencySymbol = computed(() => companyStore.selectedCompanyCurrency?.symbol || 'ден')

const isEmpty = computed(() => {
  const p = data.value.pipeline
  return p.draft + p.in_progress + p.completed + p.cancelled === 0 && data.value.boms.total === 0
})

const pipelineTotal = computed(() => {
  const p = data.value.pipeline
  return p.draft + p.in_progress + p.completed + p.cancelled
})

function pipelinePercent(count) {
  return pipelineTotal.value > 0 ? Math.round((count / pipelineTotal.value) * 100) : 0
}

const pipelineStages = computed(() => [
  { key: 'draft', label: t('manufacturing.status_draft'), count: data.value.pipeline.draft, icon: PencilSquareIcon, bgClass: 'bg-gray-100', iconClass: 'text-gray-600', barClass: 'bg-gray-400', countClass: 'text-gray-700' },
  { key: 'in_progress', label: t('manufacturing.status_in_progress'), count: data.value.pipeline.in_progress, icon: PlayIcon, bgClass: 'bg-blue-100', iconClass: 'text-blue-600', barClass: 'bg-blue-500', countClass: 'text-blue-700' },
  { key: 'completed', label: t('manufacturing.status_completed'), count: data.value.pipeline.completed, icon: CheckCircleIcon, bgClass: 'bg-green-100', iconClass: 'text-green-600', barClass: 'bg-green-500', countClass: 'text-green-700' },
  { key: 'cancelled', label: t('manufacturing.status_cancelled'), count: data.value.pipeline.cancelled, icon: XCircleIcon, bgClass: 'bg-red-100', iconClass: 'text-red-600', barClass: 'bg-red-400', countClass: 'text-red-700' },
])

const availableDocuments = computed(() => [
  { key: 'order', label: t('manufacturing.print_order') },
  { key: 'costing', label: t('manufacturing.print_costing') },
  { key: 'normativ', label: t('manufacturing.print_normativ') },
  { key: 'priemnica', label: t('manufacturing.print_priemnica') },
  { key: 'izdatnica', label: t('manufacturing.print_izdatnica') },
  { key: 'trebovnica', label: t('manufacturing.print_trebovnica') },
])

// ===== AI parsed order helpers =====
const parsedBomName = computed(() => {
  if (!parsedOrder.value?.bom_id) return ''
  const recent = data.value.recent_orders || []
  // Try to find BOM name from recent orders or material availability
  const bom = (data.value.material_availability || []).find(b => b.bom_id === parsedOrder.value.bom_id)
  return bom?.bom_name || bom?.output_item || `BOM #${parsedOrder.value.bom_id}`
})

const parsedOrderLink = computed(() => {
  const p = parsedOrder.value
  if (!p) return '/admin/manufacturing/orders/create'
  const params = new URLSearchParams()
  if (p.bom_id) params.set('bom', p.bom_id)
  if (p.quantity) params.set('qty', p.quantity)
  if (p.deadline) params.set('deadline', p.deadline)
  if (p.notes) params.set('notes', p.notes)
  const qs = params.toString()
  return '/admin/manufacturing/orders/create' + (qs ? '?' + qs : '')
})

// ===== AI insight helpers =====
function insightBorderClass(type) {
  return {
    warning: 'border-amber-400',
    danger: 'border-red-400',
    success: 'border-green-400',
    info: 'border-blue-400',
    suggestion: 'border-purple-400',
  }[type] || 'border-gray-300'
}

function insightIcon(type) {
  return {
    warning: ExclamationTriangleIcon,
    danger: ShieldExclamationIcon,
    success: CheckCircleIcon,
    info: InformationCircleIcon,
    suggestion: LightBulbIcon,
  }[type] || InformationCircleIcon
}

function insightIconClass(type) {
  return {
    warning: 'text-amber-500',
    danger: 'text-red-500',
    success: 'text-green-500',
    info: 'text-blue-500',
    suggestion: 'text-purple-500',
  }[type] || 'text-gray-500'
}

// ===== Formatters =====
function formatMoney(amount) {
  if (!amount) return `0 ${currencySymbol.value}`
  return `${Math.round(amount / 100).toLocaleString('mk-MK')} ${currencySymbol.value}`
}

function formatQty(qty) {
  const num = parseFloat(qty)
  return Number.isInteger(num) ? num.toLocaleString('mk-MK') : num.toLocaleString('mk-MK', { maximumFractionDigits: 2 })
}

function statusBadge(status) {
  return {
    draft: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  }[status] || 'bg-gray-100 text-gray-800'
}

// ===== OEE helpers =====
function oeeColor(value) {
  if (value >= 85) return 'bg-green-100 text-green-800'
  if (value >= 60) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

// ===== Gantt helpers =====
function ganttWidth(order) {
  if (!order.start || !order.end) return 30
  const start = new Date(order.start).getTime()
  const end = new Date(order.end).getTime()
  const now = Date.now()
  const total = end - start
  if (total <= 0) return 100
  const elapsed = now - start
  const pct = Math.round((elapsed / total) * 100)
  return Math.max(5, Math.min(pct, 100))
}

// ===== Chart rendering =====
function renderCostChart() {
  if (!costChartRef.value) return
  const chart = data.value.chart
  if (!chart || !chart.labels || chart.labels.length === 0) return

  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }

  const ctx = costChartRef.value.getContext('2d')
  chartInstance = new ChartJS(ctx, {
    type: 'bar',
    data: {
      labels: chart.labels,
      datasets: [
        {
          label: t('manufacturing.total_production_cost'),
          data: chart.production_cost.map(v => Math.round(v / 100)),
          backgroundColor: 'rgba(99, 102, 241, 0.7)',
          borderColor: 'rgb(99, 102, 241)',
          borderWidth: 1,
          borderRadius: 4,
          order: 2,
        },
        {
          label: t('manufacturing.total_wastage_cost'),
          data: chart.wastage_cost.map(v => Math.round(v / 100)),
          backgroundColor: 'rgba(248, 113, 113, 0.7)',
          borderColor: 'rgb(248, 113, 113)',
          borderWidth: 1,
          borderRadius: 4,
          order: 3,
        },
        {
          type: 'line',
          label: t('manufacturing.quantity'),
          data: chart.quantity,
          borderColor: 'rgb(16, 185, 129)',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          borderWidth: 2,
          pointRadius: 4,
          pointBackgroundColor: 'rgb(16, 185, 129)',
          fill: true,
          yAxisID: 'y1',
          order: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              if (ctx.dataset.yAxisID === 'y1') return `${ctx.dataset.label}: ${ctx.raw}`
              return `${ctx.dataset.label}: ${ctx.raw.toLocaleString('mk-MK')} ${currencySymbol.value}`
            },
          },
        },
      },
      scales: {
        x: { grid: { display: false } },
        y: {
          beginAtZero: true,
          ticks: { callback: (v) => v.toLocaleString('mk-MK') },
          grid: { color: 'rgba(0,0,0,0.05)' },
        },
        y1: {
          position: 'right',
          beginAtZero: true,
          grid: { display: false },
          ticks: { precision: 0 },
        },
      },
    },
  })
}

// ===== API calls =====
async function fetchDashboard() {
  isLoading.value = true
  try {
    const res = await window.axios.get('/manufacturing/dashboard')
    if (res.data?.data) {
      data.value = res.data.data
    }
  } catch (error) {
    console.error('Failed to fetch manufacturing dashboard:', error)
  } finally {
    isLoading.value = false
  }
}

async function parseAiOrder() {
  if (!aiInput.value.trim() || aiParsing.value) return
  aiParsing.value = true
  parsedOrder.value = null
  try {
    const res = await window.axios.post('/manufacturing/ai/parse-intent', {
      input: aiInput.value.trim(),
    })
    if (res.data?.data) {
      parsedOrder.value = res.data.data
    }
  } catch (error) {
    console.error('AI parse failed:', error)
  } finally {
    aiParsing.value = false
  }
}

async function fetchAiInsights() {
  aiLoading.value = true
  try {
    // Build context from dashboard data for the AI
    const context = {
      active_orders: data.value.kpis.active_orders,
      overdue_count: data.value.kpis.overdue_count,
      wastage_percent: data.value.kpis.wastage_percent,
      completed_this_month: data.value.kpis.completed_this_month,
      total_production_cost: data.value.kpis.total_production_cost_month,
      avg_cost_per_unit: data.value.kpis.avg_cost_per_unit,
      top_products: data.value.top_products,
      pipeline: data.value.pipeline,
    }

    const res = await window.axios.post('/manufacturing/ai/parse-intent', {
      input: `Analyze my manufacturing dashboard and give me 3-4 actionable insights. Context: ${JSON.stringify(context)}`,
    })

    // The parse-intent endpoint returns structured data, but for insights we construct them from the dashboard data
    // Generate smart insights locally from data
    const insights = []

    if (data.value.kpis.overdue_count > 0) {
      insights.push({
        type: 'danger',
        title: t('manufacturing.dash_ai_overdue_alert'),
        description: `${data.value.kpis.overdue_count} ${t('manufacturing.dash_ai_overdue_desc')}`,
        action: t('manufacturing.dash_ai_overdue_action'),
      })
    }

    if (data.value.kpis.wastage_percent > 5) {
      insights.push({
        type: data.value.kpis.wastage_percent > 10 ? 'danger' : 'warning',
        title: t('manufacturing.dash_ai_wastage_alert'),
        description: `${data.value.kpis.wastage_percent}% ${t('manufacturing.dash_ai_wastage_desc')}`,
        action: t('manufacturing.dash_ai_wastage_action'),
      })
    }

    if (data.value.kpis.completed_this_month > 0 && data.value.kpis.avg_cost_per_unit > 0) {
      insights.push({
        type: 'info',
        title: t('manufacturing.dash_ai_cost_insight'),
        description: `${t('manufacturing.dash_ai_cost_desc')} ${formatMoney(data.value.kpis.avg_cost_per_unit)}`,
      })
    }

    if (data.value.pipeline.draft > 3) {
      insights.push({
        type: 'suggestion',
        title: t('manufacturing.dash_ai_drafts_alert'),
        description: `${data.value.pipeline.draft} ${t('manufacturing.dash_ai_drafts_desc')}`,
        action: t('manufacturing.dash_ai_drafts_action'),
      })
    }

    if (data.value.kpis.active_orders > 0 && data.value.kpis.overdue_count === 0) {
      insights.push({
        type: 'success',
        title: t('manufacturing.dash_ai_on_track'),
        description: `${data.value.kpis.active_orders} ${t('manufacturing.dash_ai_on_track_desc')}`,
      })
    }

    // If we got AI response, add it as well
    if (res.data?.data?.suggestions) {
      insights.push({
        type: 'suggestion',
        title: t('manufacturing.dash_ai_suggestion'),
        description: res.data.data.suggestions,
      })
    }

    aiInsights.value = insights.length > 0 ? insights : [{
      type: 'success',
      title: t('manufacturing.dash_ai_all_good'),
      description: t('manufacturing.dash_ai_all_good_desc'),
    }]
  } catch {
    // Even if AI call fails, show data-driven insights
    const insights = []
    if (data.value.kpis.overdue_count > 0) {
      insights.push({ type: 'danger', title: t('manufacturing.dash_ai_overdue_alert'), description: `${data.value.kpis.overdue_count} ${t('manufacturing.dash_ai_overdue_desc')}` })
    }
    if (data.value.kpis.wastage_percent > 5) {
      insights.push({ type: 'warning', title: t('manufacturing.dash_ai_wastage_alert'), description: `${data.value.kpis.wastage_percent}% ${t('manufacturing.dash_ai_wastage_desc')}` })
    }
    aiInsights.value = insights.length > 0 ? insights : [{ type: 'info', title: t('manufacturing.dash_ai_all_good'), description: t('manufacturing.dash_ai_all_good_desc') }]
  } finally {
    aiLoading.value = false
  }
}

async function fetchQcMetrics() {
  try {
    const res = await window.axios.get('/manufacturing/reports/qc-metrics')
    qcData.value = res.data?.data || null
    await nextTick()
    renderQcChart()
  } catch (e) {
    // QC metrics are optional — fail silently
  }
}

function renderQcChart() {
  if (!qcChartRef.value || !qcData.value?.trend) return
  if (qcChartInstance) { qcChartInstance.destroy(); qcChartInstance = null }

  qcChartInstance = new ChartJS(qcChartRef.value, {
    type: 'line',
    data: {
      labels: qcData.value.trend.labels,
      datasets: [
        {
          label: t('manufacturing.qc_pass_rate'),
          data: qcData.value.trend.pass_rate,
          borderColor: '#22c55e',
          backgroundColor: 'rgba(34, 197, 94, 0.1)',
          fill: true,
          tension: 0.3,
          pointRadius: 3,
        },
        {
          label: t('manufacturing.qc_reject_rate'),
          data: qcData.value.trend.reject_rate,
          borderColor: '#ef4444',
          backgroundColor: 'rgba(239, 68, 68, 0.1)',
          fill: true,
          tension: 0.3,
          pointRadius: 3,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } } },
      scales: {
        y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
        x: { grid: { display: false } },
      },
    },
  })
}

onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  await fetchDashboard()
  if (!isEmpty.value) {
    fetchAiInsights()
    fetchQcMetrics()
    await nextTick()
    renderCostChart()
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  if (qcChartInstance) { qcChartInstance.destroy(); qcChartInstance = null }
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})
// CLAUDE-CHECKPOINT
</script>
