<template>
  <BasePage>
    <BasePageHeader :title="order ? order.travel_number : t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="../travel-orders" />
        <BaseBreadcrumbItem
          :title="order ? order.travel_number : '...'"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <div v-if="order" class="flex flex-wrap items-center gap-2">
          <BaseButton
            variant="primary-outline"
            :loading="isDownloading"
            @click="downloadPdf"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            <span class="hidden sm:inline">{{ t('download_pdf') }}</span>
            <span class="sm:hidden">PDF</span>
          </BaseButton>

          <BaseButton
            v-if="order.status === 'draft' || order.status === 'pending_approval'"
            variant="primary"
            :loading="isApproving"
            @click="showApproveDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckIcon" :class="slotProps.class" />
            </template>
            {{ t('approve') }}
          </BaseButton>

          <BaseButton
            v-if="order.status === 'approved'"
            variant="primary"
            :loading="isSettling"
            @click="showSettleDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="CalculatorIcon" :class="slotProps.class" />
            </template>
            {{ t('settle') }}
          </BaseButton>

          <BaseButton
            v-if="order.status === 'draft' || order.status === 'pending_approval'"
            variant="danger"
            :loading="isRejecting"
            @click="showRejectDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="XMarkIcon" :class="slotProps.class" />
            </template>
            {{ t('reject') }}
          </BaseButton>

          <BaseButton
            v-if="order.status === 'draft'"
            variant="danger"
            :loading="isDeleting"
            @click="showDeleteDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="TrashIcon" :class="slotProps.class" />
            </template>
            {{ t('delete') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4">
        <div v-for="i in 6" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="order" class="space-y-6">
      <!-- Header Card -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ order.travel_number }}</h3>
            <p class="text-sm text-gray-500">{{ order.purpose }}</p>
          </div>
          <span :class="statusBadgeClass(order.status)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
            {{ statusLabel(order.status) }}
          </span>
        </div>

        <div class="p-6">
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('type') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ order.type === 'domestic' ? t('domestic') : t('foreign') }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('employee') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ order.employee ? `${order.employee.first_name} ${order.employee.last_name}` : '-' }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('departure') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ formatDate(order.departure_date) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('return_date') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ formatDate(order.return_date) }}</p>
            </div>
            <div v-if="order.approved_by_user">
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('approved_by') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ order.approved_by_user.name }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Totals Summary Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
          <p class="text-xs text-blue-600 uppercase font-medium">{{ t('total_per_diem') }}</p>
          <p class="text-xl font-bold text-blue-800">{{ formatMoney(order.total_per_diem) }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
          <p class="text-xs text-amber-600 uppercase font-medium">{{ t('total_mileage') }}</p>
          <p class="text-xl font-bold text-amber-800">{{ formatMoney(order.total_mileage_cost) }}</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
          <p class="text-xs text-purple-600 uppercase font-medium">{{ t('total_expenses') }}</p>
          <p class="text-xl font-bold text-purple-800">{{ formatMoney(order.total_expenses) }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
          <p class="text-xs text-green-600 uppercase font-medium">{{ t('grand_total') }}</p>
          <p class="text-xl font-bold text-green-800">{{ formatMoney(order.grand_total) }}</p>
        </div>
        <div :class="order.reimbursement_amount >= 0 ? 'bg-indigo-50 border-indigo-200' : 'bg-red-50 border-red-200'" class="rounded-lg p-4 border">
          <p :class="order.reimbursement_amount >= 0 ? 'text-indigo-600' : 'text-red-600'" class="text-xs uppercase font-medium">{{ t('reimbursement') }}</p>
          <p :class="order.reimbursement_amount >= 0 ? 'text-indigo-800' : 'text-red-800'" class="text-xl font-bold">{{ formatMoney(order.reimbursement_amount) }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ t('advance') }}: {{ formatMoney(order.advance_amount) }}</p>
        </div>
      </div>

      <!-- Segments Table -->
      <div v-if="order.segments && order.segments.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-blue-50 border-b border-blue-200">
          <h3 class="text-sm font-semibold text-blue-800">{{ t('segments') }} ({{ order.segments.length }})</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('from_city') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('to_city') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('transport_type') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('distance') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('days') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('per_diem') }}</th>
                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ t('accommodation_provided') }}</th>
                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ t('meals_provided') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="seg in order.segments" :key="seg.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">{{ seg.from_city }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ seg.to_city }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ transportLabel(seg.transport_type) }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ seg.distance_km || '-' }} km</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ seg.per_diem_days || '-' }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-blue-700">{{ formatMoney(seg.per_diem_amount) }}</td>
                <td class="px-4 py-3 text-sm text-center">
                  <span v-if="seg.accommodation_provided" class="text-green-600">&#10003;</span>
                  <span v-else class="text-gray-300">-</span>
                </td>
                <td class="px-4 py-3 text-sm text-center">
                  <span v-if="seg.meals_provided" class="text-green-600">&#10003;</span>
                  <span v-else class="text-gray-300">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Expenses Table -->
      <div v-if="order.expenses && order.expenses.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-purple-50 border-b border-purple-200">
          <h3 class="text-sm font-semibold text-purple-800">{{ t('expenses') }} ({{ order.expenses.length }})</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('category') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('description') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('currency') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="exp in order.expenses" :key="exp.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-500">{{ categoryLabel(exp.category) }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ exp.description }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(exp.amount) }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ exp.currency_code }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="order.notes" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ t('notes') }}</h3>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ order.notes }}</p>
      </div>

      <!-- Vehicles Section -->
      <div v-if="hasVehicles" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-amber-50 border-b border-amber-200">
          <h3 class="text-sm font-semibold text-amber-800">{{ t('vehicles') }} ({{ order.vehicles.length }})</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('vehicle_type') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('vehicle_make') }} / {{ t('vehicle_model') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('registration_plate') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('capacity_tonnes') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('fuel_type') }}</th>
                <th v-if="vehiclesWithFuel.length" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('odometer_start') }}</th>
                <th v-if="vehiclesWithFuel.length" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('odometer_end') }}</th>
                <th v-if="vehiclesWithFuel.length" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_km') }}</th>
                <th v-if="vehiclesWithFuel.length" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('fuel_start') }}</th>
                <th v-if="vehiclesWithFuel.length" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('fuel_end') }}</th>
                <th v-if="vehiclesWithFuel.length" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('fuel_added') }}</th>
                <th v-if="vehiclesWithFuel.length" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('fuel_consumed') }}</th>
                <th v-if="vehiclesWithFuel.length" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('fuel_norm') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="v in order.vehicles" :key="v.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">{{ vehicleTypeLabel(v.vehicle_type) }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ v.make }} {{ v.model }}</td>
                <td class="px-4 py-3 text-sm font-mono text-gray-700">{{ v.registration_plate }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ v.capacity_tonnes || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ fuelTypeLabel(v.fuel_type) }}</td>
                <td v-if="vehiclesWithFuel.length" class="px-4 py-3 text-sm text-right text-gray-500">{{ v.odometer_start != null ? v.odometer_start : '-' }}</td>
                <td v-if="vehiclesWithFuel.length" class="px-4 py-3 text-sm text-right text-gray-500">{{ v.odometer_end != null ? v.odometer_end : '-' }}</td>
                <td v-if="vehiclesWithFuel.length" class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ vehicleTotalKm(v) != null ? vehicleTotalKm(v) : '-' }}</td>
                <td v-if="vehiclesWithFuel.length" class="px-4 py-3 text-sm text-right text-gray-500">{{ v.fuel_start != null ? formatNumber(v.fuel_start) : '-' }}</td>
                <td v-if="vehiclesWithFuel.length" class="px-4 py-3 text-sm text-right text-gray-500">{{ v.fuel_end != null ? formatNumber(v.fuel_end) : '-' }}</td>
                <td v-if="vehiclesWithFuel.length" class="px-4 py-3 text-sm text-right text-gray-500">{{ v.fuel_added != null ? formatNumber(v.fuel_added) : '-' }}</td>
                <td v-if="vehiclesWithFuel.length" class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ vehicleFuelConsumed(v) != null ? formatNumber(vehicleFuelConsumed(v)) : '-' }}</td>
                <td v-if="vehiclesWithFuel.length" class="px-4 py-3 text-sm text-right text-gray-500">{{ v.fuel_norm_per_100km != null ? formatNumber(v.fuel_norm_per_100km) : '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Crew Section -->
      <div v-if="hasCrew" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-indigo-50 border-b border-indigo-200">
          <h3 class="text-sm font-semibold text-indigo-800">{{ t('crew') }} ({{ order.crew.length }})</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('crew_name') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('crew_role') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('license_number') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('license_category') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('cpc_number') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="c in order.crew" :key="c.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">{{ c.name }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ crewRoleLabel(c.role) }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ c.license_number || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ c.license_category || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ c.cpc_number || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Cargo / CMR Section -->
      <div v-if="hasCargo" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-teal-50 border-b border-teal-200">
          <h3 class="text-sm font-semibold text-teal-800">{{ t('cargo') }} ({{ order.cargo.length }})</h3>
        </div>
        <div class="space-y-4 p-6">
          <div v-for="item in order.cargo" :key="item.id" class="border border-gray-200 rounded-lg p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <p class="text-xs text-gray-500 uppercase font-medium">{{ t('cmr_number') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">{{ item.cmr_number || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 uppercase font-medium">{{ t('sender_name') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">{{ item.sender_name || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 uppercase font-medium">{{ t('receiver_name') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">{{ item.receiver_name || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 uppercase font-medium">{{ t('goods_description') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">{{ item.goods_description || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 uppercase font-medium">{{ t('packages_count') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">{{ item.packages_count != null ? item.packages_count : '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 uppercase font-medium">{{ t('gross_weight') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">{{ item.gross_weight_kg != null ? formatNumber(item.gross_weight_kg, 0) + ' kg' : '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 uppercase font-medium">{{ t('loading_place') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">{{ item.loading_place || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 uppercase font-medium">{{ t('unloading_place') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">{{ item.unloading_place || '-' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Fuel Analysis Section -->
      <div v-if="hasFuelData" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-orange-50 border-b border-orange-200">
          <h3 class="text-sm font-semibold text-orange-800">{{ t('fuel_analysis') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('registration_plate') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_km') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('fuel_consumed') }} (l)</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('fuel_norm_consumption') }} (l)</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('fuel_variance') }} (l)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="v in vehiclesWithFuel" :key="v.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-mono text-gray-700">{{ v.registration_plate }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ vehicleTotalKm(v) != null ? vehicleTotalKm(v) : '-' }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ vehicleFuelConsumed(v) != null ? formatNumber(vehicleFuelConsumed(v)) : '-' }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ vehicleNormConsumption(v) != null ? formatNumber(vehicleNormConsumption(v)) : '-' }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium">
                  <span v-if="vehicleFuelVariance(v) != null" :class="vehicleFuelVariance(v) >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ formatNumber(vehicleFuelVariance(v)) }}
                    <span class="text-xs font-normal ml-1">{{ vehicleFuelVariance(v) >= 0 ? t('under_norm') : t('over_norm') }}</span>
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- GL Posting Breakdown -->
      <div v-if="hasGlPosting" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-slate-50 border-b border-slate-200">
          <h3 class="text-sm font-semibold text-slate-800">{{ t('gl_posting') }}</h3>
        </div>
        <div class="p-6">
          <div v-if="order.gl_entries && order.gl_entries.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('gl_code') }}</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('description') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('gl_debit') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('gl_credit') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(entry, idx) in order.gl_entries" :key="idx" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm font-mono text-gray-700">{{ entry.account_code }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ entry.account_name || entry.description || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                    {{ entry.debit_amount ? formatMoney(entry.debit_amount) : '' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                    {{ entry.credit_amount ? formatMoney(entry.credit_amount) : '' }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                <tr>
                  <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-700 text-right">{{ t('grand_total') }}</td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                    {{ formatMoney(order.gl_entries.reduce((sum, e) => sum + (e.debit_amount || 0), 0)) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                    {{ formatMoney(order.gl_entries.reduce((sum, e) => sum + (e.credit_amount || 0), 0)) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
          <p v-else class="text-sm text-gray-500">-</p>
        </div>
      </div>
    </div>

    <!-- Not found -->
    <div v-else class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16">
      <BaseIcon name="ExclamationCircleIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ t('not_found') }}</p>
    </div>

    <!-- Approve Dialog -->
    <div v-if="showApproveDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showApproveDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('approve') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('confirm_approve') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showApproveDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="primary" :loading="isApproving" @click="approveOrder">
            {{ t('approve') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Settle Dialog -->
    <div v-if="showSettleDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showSettleDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('settle') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('confirm_settle') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showSettleDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="primary" :loading="isSettling" @click="settleOrder">
            {{ t('settle') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Reject Dialog -->
    <div v-if="showRejectDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showRejectDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('reject') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('confirm_reject') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showRejectDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="danger" :loading="isRejecting" @click="rejectOrder">
            {{ t('reject') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Delete Dialog -->
    <div v-if="showDeleteDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showDeleteDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('delete') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('confirm_delete') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showDeleteDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="danger" :loading="isDeleting" @click="deleteOrder">
            {{ t('delete') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import travelMessages from '@/scripts/admin/i18n/travel-orders.js'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const { locale: i18nLocale } = useI18n()

function t(key) {
  const l = i18nLocale.value || 'mk'
  return travelMessages[l]?.travel_orders?.[key]
    || travelMessages['en']?.travel_orders?.[key]
    || key
}

function transportLabel(type) {
  const labels = { car: t('transport_car'), bus: t('transport_bus'), train: t('transport_train'), plane: t('transport_plane'), other: t('transport_other') }
  return labels[type] || type
}

function categoryLabel(cat) {
  const labels = {
    transport: t('category_transport'), accommodation: t('category_accommodation'),
    meals: t('category_meals'), other: t('category_other'),
    fuel: t('category_fuel'), tolls: t('category_tolls'),
    forwarding: t('category_forwarding'), vehicle_maintenance: t('category_vehicle_maintenance'),
    communication: t('category_communication'), per_diem: t('category_per_diem'),
  }
  return labels[cat] || cat
}

// State
const order = ref(null)
const isLoading = ref(false)
const isApproving = ref(false)
const isSettling = ref(false)
const isRejecting = ref(false)
const isDeleting = ref(false)
const isDownloading = ref(false)
const showApproveDialog = ref(false)
const showSettleDialog = ref(false)
const showRejectDialog = ref(false)
const showDeleteDialog = ref(false)

// Methods
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

function formatMoney(cents) {
  if (!cents && cents !== 0) return '-'
  const fmtLocale = localeMap[i18nLocale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  const fmtLocale = localeMap[i18nLocale.value] || 'mk-MK'
  return d.toLocaleDateString(fmtLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function statusBadgeClass(status) {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-700'
    case 'pending_approval': return 'bg-yellow-100 text-yellow-800'
    case 'approved': return 'bg-green-100 text-green-800'
    case 'settled': return 'bg-blue-100 text-blue-800'
    case 'rejected': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusLabel(status) {
  const key = `status_${status}`
  return t(key)
}

async function fetchOrder() {
  const id = route.params.id
  if (!id) return

  isLoading.value = true
  try {
    const response = await window.axios.get(`/travel-orders/${id}`)
    order.value = response.data?.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

async function downloadPdf() {
  isDownloading.value = true
  try {
    const response = await window.axios.get(`/travel-orders/${order.value.id}/pdf`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `paten-nalog-${order.value.travel_number}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_downloading'),
    })
  } finally {
    isDownloading.value = false
  }
}

async function approveOrder() {
  isApproving.value = true
  try {
    const response = await window.axios.post(`/travel-orders/${order.value.id}/approve`)
    order.value = response.data?.data || order.value
    showApproveDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('approved_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_approving'),
    })
  } finally {
    isApproving.value = false
  }
}

async function settleOrder() {
  isSettling.value = true
  try {
    const response = await window.axios.post(`/travel-orders/${order.value.id}/settle`)
    order.value = response.data?.data || order.value
    showSettleDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('settled_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_settling'),
    })
  } finally {
    isSettling.value = false
  }
}

async function rejectOrder() {
  isRejecting.value = true
  try {
    const response = await window.axios.post(`/travel-orders/${order.value.id}/reject`)
    order.value = response.data?.data || order.value
    showRejectDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('rejected_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_rejecting'),
    })
  } finally {
    isRejecting.value = false
  }
}

async function deleteOrder() {
  isDeleting.value = true
  try {
    await window.axios.delete(`/travel-orders/${order.value.id}`)
    showDeleteDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: t('deleted_success'),
    })
    router.push({ path: '/admin/travel-orders' })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_deleting'),
    })
  } finally {
    isDeleting.value = false
  }
}

// Transport-specific computed
const isTransportOrder = computed(() => {
  return order.value && order.value.transport_type_category !== 'business_trip'
})

const hasVehicles = computed(() => {
  return isTransportOrder.value && order.value.vehicles?.length > 0
})

const hasCrew = computed(() => {
  return order.value?.crew?.length > 0
})

const hasCargo = computed(() => {
  return order.value?.cargo?.length > 0
})

const vehiclesWithFuel = computed(() => {
  if (!hasVehicles.value) return []
  return order.value.vehicles.filter(v =>
    v.fuel_start != null || v.fuel_end != null || v.fuel_added != null
  )
})

const hasFuelData = computed(() => {
  return vehiclesWithFuel.value.length > 0
})

const hasGlPosting = computed(() => {
  return order.value?.status === 'settled' && order.value?.ifrs_transaction_id
})

const glEntries = computed(() => {
  if (!hasGlPosting.value || !order.value.gl_entries) return { debit: [], credit: [] }
  const debit = order.value.gl_entries.filter(e => e.type === 'debit' || e.amount > 0)
  const credit = order.value.gl_entries.filter(e => e.type === 'credit' || e.amount < 0)
  return { debit, credit }
})

function vehicleTypeLabel(type) {
  const map = { truck: t('vehicle_truck'), trailer: t('vehicle_trailer'), car: t('vehicle_car'), van: t('vehicle_van') }
  return map[type] || type
}

function fuelTypeLabel(type) {
  const map = { diesel: t('fuel_diesel'), petrol: t('fuel_petrol'), lpg: t('fuel_lpg'), cng: t('fuel_cng') }
  return map[type] || type
}

function crewRoleLabel(role) {
  const map = { driver: t('crew_driver'), co_driver: t('crew_co_driver'), member: t('crew_member') }
  return map[role] || role
}

function vehicleTotalKm(v) {
  if (v.odometer_start != null && v.odometer_end != null) {
    return v.odometer_end - v.odometer_start
  }
  return null
}

function vehicleFuelConsumed(v) {
  if (v.fuel_start != null && v.fuel_end != null) {
    return (v.fuel_start || 0) + (v.fuel_added || 0) - (v.fuel_end || 0)
  }
  return null
}

function vehicleNormConsumption(v) {
  const km = vehicleTotalKm(v)
  if (km != null && v.fuel_norm_per_100km) {
    return v.fuel_norm_per_100km * km / 100
  }
  return null
}

function vehicleFuelVariance(v) {
  const norm = vehicleNormConsumption(v)
  const actual = vehicleFuelConsumed(v)
  if (norm != null && actual != null) {
    return norm - actual
  }
  return null
}

function formatNumber(val, decimals = 1) {
  if (val == null) return '-'
  return Number(val).toFixed(decimals)
}

// Lifecycle
onMounted(() => {
  fetchOrder()
})
// CLAUDE-CHECKPOINT
</script>
