<template>
  <BasePage>
    <BasePageHeader :title="t('create')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('home') || 'Home'" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="../travel-orders" />
        <BaseBreadcrumbItem :title="t('create')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <form @submit.prevent="saveTravelOrder" class="space-y-6 pb-24">

      <!-- ═══════════════ Basic Info ═══════════════ -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">{{ t('step1_basic') }}</h3>

        <!-- Transport Type Category - Radio Cards -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-3">{{ t('transport_type_category') }}</label>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button
              v-for="cat in transportCategoryOptions"
              :key="cat.value"
              type="button"
              :class="[
                'relative flex flex-col items-center p-5 border-2 rounded-lg transition-all text-center',
                form.transport_type_category === cat.value
                  ? 'border-primary-600 bg-primary-50 ring-1 ring-primary-600'
                  : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
              ]"
              @click="form.transport_type_category = cat.value"
            >
              <BaseIcon :name="cat.icon" class="h-8 w-8 mb-2" :class="form.transport_type_category === cat.value ? 'text-primary-600' : 'text-gray-400'" />
              <span class="text-sm font-semibold" :class="form.transport_type_category === cat.value ? 'text-primary-700' : 'text-gray-700'">{{ cat.label }}</span>
              <span class="text-xs mt-1" :class="form.transport_type_category === cat.value ? 'text-primary-500' : 'text-gray-400'">{{ cat.description }}</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <BaseInputGroup :label="t('type')" required>
            <BaseMultiselect
              v-model="form.type"
              :options="typeOptions"
              label="label"
              value-prop="value"
              :searchable="false"
            />
          </BaseInputGroup>

          <BaseInputGroup v-if="showTransportMode" :label="t('transport_mode')">
            <BaseMultiselect
              v-model="form.transport_mode"
              :options="transportModeOptions"
              label="label"
              value-prop="value"
              :searchable="false"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="t('employee')">
            <BaseMultiselect
              v-model="form.employee_id"
              :options="employees"
              :searchable="true"
              label="name"
              value-prop="id"
              :placeholder="t('employee')"
              :loading="isLoadingEmployees"
            />
          </BaseInputGroup>
        </div>

        <div class="mt-6">
          <BaseInputGroup :label="t('purpose')" required>
            <textarea
              v-model="form.purpose"
              rows="2"
              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
              :placeholder="t('purpose')"
            />
          </BaseInputGroup>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
          <BaseInputGroup :label="t('advance_amount')">
            <BaseInput
              v-model="form.advance_amount_display"
              type="number"
              step="0.01"
              min="0"
              :placeholder="'0.00'"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="t('notes')">
            <BaseInput
              v-model="form.notes"
              type="text"
              :placeholder="t('notes_placeholder')"
            />
          </BaseInputGroup>
        </div>

        <!-- Auto-derived dates from segments -->
        <div v-if="form.segments.length > 0 && form.segments[0].departure_at" class="mt-4 text-xs text-gray-500">
          {{ t('departure') }}: <strong>{{ formatDateTime(form.departure_date) }}</strong>
          &mdash;
          {{ t('return_date') }}: <strong>{{ formatDateTime(form.return_date) }}</strong>
          <span class="text-gray-400 ml-1">({{ t('auto_from_segments') }})</span>
        </div>
      </div>

      <!-- ═══════════════ Vehicles ═══════════════ -->
      <div v-if="showVehicles" class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <h3 class="text-lg font-medium text-gray-900">{{ t('vehicles') }}</h3>
            <span v-if="form.vehicles.length > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">{{ form.vehicles.length }}</span>
          </div>
          <BaseButton type="button" variant="primary-outline" size="sm" @click="addVehicle">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('add_vehicle') }}
          </BaseButton>
        </div>

        <div v-if="form.vehicles.length === 0" class="text-center py-6 text-sm text-gray-500">
          {{ t('no_vehicles_yet') }}
        </div>

        <div v-for="(vehicle, index) in form.vehicles" :key="index" class="border border-gray-200 rounded-lg p-4 mb-4 last:mb-0">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">{{ t('vehicle') }} #{{ index + 1 }}</h4>
            <button
              v-if="form.vehicles.length > 1"
              type="button"
              class="text-red-500 hover:text-red-700 text-sm"
              @click="removeVehicle(index)"
            >
              {{ t('remove') }}
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <BaseInputGroup :label="t('vehicle_type')" required>
              <BaseMultiselect
                v-model="vehicle.vehicle_type"
                :options="vehicleTypeOptions"
                label="label"
                value-prop="value"
                :searchable="false"
                @change="onVehicleTypeChange(vehicle)"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('vehicle_make')">
              <BaseInput v-model="vehicle.make" type="text" :placeholder="t('vehicle_make')" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('vehicle_model')">
              <BaseInput v-model="vehicle.model" type="text" :placeholder="t('vehicle_model')" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('registration_plate')" required>
              <BaseInput v-model="vehicle.registration_plate" type="text" placeholder="SK-1234-AB" />
            </BaseInputGroup>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-4">
            <BaseInputGroup :label="t('capacity_tonnes')">
              <BaseInput v-model="vehicle.capacity_tonnes" type="number" step="0.1" min="0" placeholder="0" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('fuel_type')">
              <BaseMultiselect
                v-model="vehicle.fuel_type"
                :options="fuelTypeOptions"
                label="label"
                value-prop="value"
                :searchable="false"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('odometer_start')">
              <BaseInput v-model="vehicle.odometer_start" type="number" min="0" placeholder="0" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('fuel_start_liters')">
              <BaseInput v-model="vehicle.fuel_start_liters" type="number" step="0.1" min="0" placeholder="0" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('fuel_norm')">
              <div class="flex items-center space-x-2">
                <BaseInput v-model="vehicle.fuel_norm_per_100km" type="number" step="0.1" min="0" placeholder="0" />
                <span class="text-xs text-gray-500 whitespace-nowrap">L/100km</span>
              </div>
            </BaseInputGroup>
          </div>
        </div>
      </div>

      <!-- ═══════════════ Crew / Drivers ═══════════════ -->
      <div v-if="showVehicles" class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
              <h3 class="text-lg font-medium text-gray-900">{{ t('crew') }}</h3>
              <span v-if="form.crew.length > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ form.crew.length }}</span>
            </div>
          <BaseButton type="button" variant="primary-outline" size="sm" @click="addCrew">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('add_crew') }}
          </BaseButton>
        </div>

        <div v-if="form.crew.length === 0" class="text-center py-6 text-sm text-gray-500">
          {{ t('no_crew_yet') }}
        </div>

        <div v-for="(member, index) in form.crew" :key="index" class="border border-gray-200 rounded-lg p-4 mb-4 last:mb-0">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">{{ t('crew') }} #{{ index + 1 }}</h4>
            <button type="button" class="text-red-500 hover:text-red-700 text-sm" @click="removeCrew(index)">
              {{ t('remove') }}
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <BaseInputGroup :label="t('crew_name')" required>
              <BaseInput v-model="member.name" type="text" :placeholder="t('crew_name')" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('crew_role')">
              <BaseMultiselect
                v-model="member.role"
                :options="crewRoleOptions"
                label="label"
                value-prop="value"
                :searchable="false"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('license_number')">
              <BaseInput v-model="member.license_number" type="text" :placeholder="t('license_number')" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('license_category')">
              <BaseInput v-model="member.license_category" type="text" placeholder="C+E" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('cpc_number')">
              <BaseInput v-model="member.cpc_number" type="text" :placeholder="t('cpc_number')" />
            </BaseInputGroup>
          </div>
        </div>
      </div>

      <!-- ═══════════════ Route Segments ═══════════════ -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
              <h3 class="text-lg font-medium text-gray-900">{{ t('segments') }}</h3>
              <span v-if="form.segments.length > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ form.segments.length }}</span>
            </div>
          <BaseButton type="button" variant="primary-outline" size="sm" @click="addSegment">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('add_segment') }}
          </BaseButton>
        </div>

        <div v-for="(seg, index) in form.segments" :key="index" class="border border-gray-200 rounded-lg p-4 mb-4 last:mb-0">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">{{ t('segments') }} #{{ index + 1 }}</h4>
            <button
              v-if="form.segments.length > 1"
              type="button"
              class="text-red-500 hover:text-red-700 text-sm"
              @click="removeSegment(index)"
            >
              {{ t('remove') }}
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <BaseInputGroup :label="t('from_city')" required>
              <input
                v-model="seg.from_city"
                type="text"
                :list="form.type === 'domestic' ? 'mk-cities-list' : undefined"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                :placeholder="t('from_city')"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('to_city')" required>
              <input
                v-model="seg.to_city"
                type="text"
                :list="form.type === 'domestic' ? 'mk-cities-list' : undefined"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                :placeholder="t('to_city')"
              />
            </BaseInputGroup>

            <div>
              <BaseInputGroup v-if="form.type === 'foreign'" :label="t('country')">
                <BaseMultiselect
                  v-model="seg.country_code"
                  :options="countriesList"
                  label="name"
                  track-by="name"
                  value-prop="code"
                  :searchable="true"
                  :placeholder="t('select_country')"
                  @select="onCountryChange(seg)"
                />
              </BaseInputGroup>
              <div v-if="form.type === 'foreign' && seg.per_diem_rate" class="mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ t('per_diem') }}: {{ seg.per_diem_rate }} {{ seg.per_diem_currency || 'EUR' }}/{{ t('full_day') }}
                </span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            <BaseInputGroup :label="t('departure')" required>
              <input
                v-model="seg.departure_at"
                type="datetime-local"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('arrival')" required>
              <input
                v-model="seg.arrival_at"
                type="datetime-local"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('transport_type')" required>
              <BaseMultiselect
                v-model="seg.transport_type"
                :options="transportOptions"
                label="label"
                value-prop="value"
                :searchable="false"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('distance')">
              <BaseInput v-model="seg.distance_km" type="number" step="0.1" min="0" :placeholder="'0'" />
            </BaseInputGroup>
          </div>

          <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-4">
            <label class="flex items-center space-x-2 text-sm">
              <input type="checkbox" v-model="seg.accommodation_provided" class="h-4 w-4 rounded border-gray-300 text-primary-600" />
              <span>{{ t('accommodation_provided') }}</span>
            </label>
            <label class="flex items-center space-x-2 text-sm">
              <input type="checkbox" v-model="seg.breakfast_provided" class="h-4 w-4 rounded border-gray-300 text-primary-600" />
              <span>{{ t('breakfast') }} <span class="text-gray-400">(-10%)</span></span>
            </label>
            <label class="flex items-center space-x-2 text-sm">
              <input type="checkbox" v-model="seg.lunch_provided" class="h-4 w-4 rounded border-gray-300 text-primary-600" />
              <span>{{ t('lunch') }} <span class="text-gray-400">(-30%)</span></span>
            </label>
            <label class="flex items-center space-x-2 text-sm">
              <input type="checkbox" v-model="seg.dinner_provided" class="h-4 w-4 rounded border-gray-300 text-primary-600" />
              <span>{{ t('dinner') }} <span class="text-gray-400">(-30%)</span></span>
            </label>
          </div>

          <!-- Per-diem calculation info -->
          <div v-if="segmentPerDiem(seg)" class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
            <div class="flex items-center justify-between text-sm">
              <div class="flex flex-wrap gap-x-4 gap-y-1">
                <span class="text-blue-700">{{ segmentPerDiem(seg).hours }}h = {{ segmentPerDiem(seg).days }} {{ t('days') }}</span>
                <span class="text-blue-700">{{ segmentPerDiem(seg).rate }} {{ segmentPerDiem(seg).currency }}</span>
                <span v-if="segmentPerDiem(seg).reductions > 0" class="text-orange-600">-{{ segmentPerDiem(seg).reductions }}%</span>
              </div>
              <span class="font-bold text-blue-800">{{ formatMoney(segmentPerDiem(seg).mkdAmount) }} MKD</span>
            </div>
          </div>
        </div>

        <!-- Total per-diem -->
        <div v-if="totalPerDiemMkd > 0" class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3">
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-green-800">{{ t('total_per_diem') }}</span>
            <span class="text-lg font-bold text-green-900">{{ formatMoney(totalPerDiemMkd) }} MKD</span>
          </div>
        </div>
      </div>

      <!-- ═══════════════ Cargo & CMR ═══════════════ -->
      <div v-if="showCargo" class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
              <h3 class="text-lg font-medium text-gray-900">{{ t('cargo_cmr') }}</h3>
              <span v-if="form.cargo_items.length > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-700">{{ form.cargo_items.length }}</span>
            </div>
          <BaseButton type="button" variant="primary-outline" size="sm" @click="addCargo">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('add_cargo') }}
          </BaseButton>
        </div>

        <div v-if="form.cargo_items.length === 0" class="text-center py-6 text-sm text-gray-500">
          {{ t('no_cargo_yet') }}
        </div>

        <div v-for="(cargo, index) in form.cargo_items" :key="index" class="border border-gray-200 rounded-lg p-4 mb-4 last:mb-0">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">{{ t('cargo_item') }} #{{ index + 1 }}</h4>
            <button type="button" class="text-red-500 hover:text-red-700 text-sm" @click="removeCargo(index)">
              {{ t('remove') }}
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseInputGroup :label="t('cmr_number')">
              <BaseInput v-model="cargo.cmr_number" type="text" :placeholder="t('cmr_number')" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('goods_description')" required>
              <BaseInput v-model="cargo.goods_description" type="text" :placeholder="t('goods_description')" />
            </BaseInputGroup>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            <BaseInputGroup :label="t('sender_name')">
              <BaseInput v-model="cargo.sender_name" type="text" :placeholder="t('sender_name')" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('receiver_name')">
              <BaseInput v-model="cargo.receiver_name" type="text" :placeholder="t('receiver_name')" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('packages_count')">
              <BaseInput v-model="cargo.packages_count" type="number" min="0" placeholder="0" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('gross_weight_kg')">
              <BaseInput v-model="cargo.gross_weight_kg" type="number" step="0.1" min="0" placeholder="0" />
            </BaseInputGroup>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            <BaseInputGroup :label="t('sender_address')">
              <BaseInput v-model="cargo.sender_address" type="text" :placeholder="t('sender_address')" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('receiver_address')">
              <BaseInput v-model="cargo.receiver_address" type="text" :placeholder="t('receiver_address')" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('loading_place')">
              <BaseInput v-model="cargo.loading_place" type="text" :placeholder="t('loading_place')" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('unloading_place')">
              <BaseInput v-model="cargo.unloading_place" type="text" :placeholder="t('unloading_place')" />
            </BaseInputGroup>
          </div>
        </div>
      </div>

      <!-- ═══════════════ Expenses ═══════════════ -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
              <h3 class="text-lg font-medium text-gray-900">{{ t('expenses') }}</h3>
              <span v-if="form.expenses.length > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">{{ form.expenses.length }}</span>
            </div>
          <BaseButton type="button" variant="primary-outline" size="sm" @click="addExpense">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('add_expense') }}
          </BaseButton>
        </div>

        <div v-if="form.expenses.length === 0" class="text-center py-6 text-sm text-gray-500">
          {{ t('no_expenses_yet') }}
        </div>

        <div v-for="(exp, index) in form.expenses" :key="index" class="border border-gray-200 rounded-lg p-4 mb-4 last:mb-0">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
              <h4 class="text-sm font-semibold text-gray-700">{{ t('expenses') }} #{{ index + 1 }}</h4>
              <span v-if="getGlCode(exp.category)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                {{ getGlCode(exp.category) }}
              </span>
            </div>
            <button type="button" class="text-red-500 hover:text-red-700 text-sm" @click="removeExpense(index)">
              {{ t('remove') }}
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <BaseInputGroup :label="t('category')" required>
              <BaseMultiselect
                v-model="exp.category"
                :options="expenseCategoryOptions"
                label="label"
                value-prop="value"
                :searchable="false"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('description')">
              <BaseInput v-model="exp.description" type="text" :placeholder="t('description')" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('currency')" required>
              <BaseMultiselect
                v-model="exp.currency_code"
                :options="currencyOptions"
                :searchable="true"
                @select="onExpenseCurrencyChange(exp)"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('amount') + (exp.currency_code && exp.currency_code !== 'MKD' ? ' (' + exp.currency_code + ')' : '')" required>
              <BaseInput v-model="exp.amount_display" type="number" step="0.01" min="0" :placeholder="'0.00'" />
            </BaseInputGroup>

            <BaseInputGroup v-if="exp.currency_code && exp.currency_code !== 'MKD'" :label="t('exchange_rate')">
              <BaseInput v-model="exp.exchange_rate" type="number" step="0.0001" min="0" :placeholder="'1.0000'" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('receipt_number')">
              <BaseInput v-model="exp.receipt_number" type="text" :placeholder="t('receipt_number')" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('vat_amount')">
              <BaseInput v-model="exp.vat_amount_display" type="number" step="0.01" min="0" :placeholder="'0.00'" />
            </BaseInputGroup>
          </div>

          <div v-if="exp.currency_code !== 'MKD' && exp.amount_display" class="mt-2 text-xs text-gray-500">
            {{ t('mkd_equivalent') }}: <span class="font-medium text-gray-700">{{ formatDecimal(expenseMkdAmount(exp)) }} MKD</span>
          </div>
        </div>

        <!-- Expense summary -->
        <div v-if="expensesByCategory.length > 0" class="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">{{ t('expense_summary') }}</h4>
          <div class="space-y-2">
            <div v-for="group in expensesByCategory" :key="group.category" class="flex items-center justify-between text-sm">
              <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono bg-white text-gray-500 border border-gray-200">{{ group.glCode }}</span>
                <span class="text-gray-700">{{ group.label }}</span>
              </div>
              <span class="font-medium text-gray-900">{{ formatDecimal(group.totalMkd) }} MKD</span>
            </div>
            <div class="pt-2 border-t border-gray-300 flex items-center justify-between">
              <span class="text-sm font-bold text-gray-800">{{ t('total_expenses') }}</span>
              <span class="font-bold text-gray-900">{{ formatDecimal(totalExpensesMkd) }} MKD</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════ Financial Summary & Save ═══════════════ -->
      <div class="bg-primary-50 rounded-lg shadow p-6 border border-primary-200">
        <h3 class="text-lg font-medium text-primary-900 mb-4">{{ t('financial_summary') }}</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ t('total_per_diem') }}</span>
              <span class="font-medium text-gray-900">{{ formatMoney(totalPerDiemMkd) }} MKD</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ t('total_mileage') }}</span>
              <span class="font-medium text-gray-900">{{ totalDistanceKm }} km</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ t('total_expenses') }}</span>
              <span class="font-medium text-gray-900">{{ formatDecimal(totalExpensesMkd) }} MKD</span>
            </div>
            <div class="border-t border-primary-200 pt-3 flex justify-between">
              <span class="font-semibold text-gray-800">{{ t('grand_total') }}</span>
              <span class="font-bold text-lg text-gray-900">{{ formatDecimal(grandTotalMkd) }} MKD</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ t('advance_amount') }}</span>
              <span class="font-medium text-gray-900">- {{ formatMoney(advanceInCents) }} MKD</span>
            </div>
            <div class="border-t-2 border-primary-300 pt-3 flex justify-between">
              <span class="font-bold text-primary-800">{{ t('reimbursement') }}</span>
              <span class="font-bold text-xl" :class="estimatedReimbursementMkd >= 0 ? 'text-green-700' : 'text-red-700'">
                {{ formatDecimal(estimatedReimbursementMkd) }} MKD
              </span>
            </div>
          </div>

          <!-- Fuel analysis -->
          <div v-if="showVehicles && form.vehicles.length > 0 && totalDistanceKm > 0">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">{{ t('fuel_analysis') }}</h4>
            <div v-for="(v, i) in form.vehicles" :key="i" class="flex items-center justify-between text-sm py-1">
              <span class="text-gray-600">{{ v.registration_plate || t('vehicle') + ' #' + (i + 1) }}</span>
              <span class="text-gray-900">
                {{ v.fuel_norm_per_100km || 0 }} L/100km &rarr; {{ formatDecimal(estimatedFuelConsumption(v)) }} L
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Macedonian cities datalist for domestic travel autocomplete -->
      <datalist id="mk-cities-list">
        <option v-for="city in mkCities" :key="city" :value="city" />
      </datalist>
    </form>

    <!-- Sticky save bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50 px-4 sm:px-6 py-3">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="hidden sm:flex items-center space-x-6 text-sm text-gray-600">
          <span v-if="totalPerDiemMkd > 0">{{ t('per_diem') }}: <strong>{{ formatMoney(totalPerDiemMkd) }}</strong></span>
          <span v-if="totalExpensesMkd > 0">{{ t('expenses') }}: <strong>{{ formatDecimal(totalExpensesMkd) }}</strong></span>
        </div>
        <div class="flex items-center justify-between w-full sm:w-auto gap-3">
          <span class="font-semibold text-gray-900 text-sm">{{ t('grand_total') }}: {{ formatDecimal(grandTotalMkd) }} MKD</span>
          <div class="flex items-center space-x-2">
            <span v-if="!canSave" class="text-xs text-red-500 hidden sm:inline">{{ t('fill_required_fields') }}</span>
            <BaseButton
              type="button"
              variant="primary"
              :loading="isSaving"
              :disabled="!canSave"
              @click="saveTravelOrder"
            >
              <template #left="slotProps">
                <BaseIcon name="CheckIcon" :class="slotProps.class" />
              </template>
              {{ t('save_draft') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import travelMessages from '@/scripts/admin/i18n/travel-orders.js'

const router = useRouter()
const notificationStore = useNotificationStore()
const { locale: i18nLocale } = useI18n()

function t(key) {
  const l = i18nLocale.value || 'mk'
  return travelMessages[l]?.travel_orders?.[key]
    || travelMessages['en']?.travel_orders?.[key]
    || key
}

// ==================== State ====================
const isSaving = ref(false)
const isLoadingEmployees = ref(false)
const employees = ref([])
const perDiemRates = ref({})
const exchangeRates = ref({})
const expenseCategories = ref([])
const countriesList = ref([])

const defaultFuelNorms = { car: 8, van: 12, truck: 35, trailer: 0 }

// Major Macedonian cities for domestic travel autocomplete
const mkCities = [
  'Скопје', 'Битола', 'Куманово', 'Прилеп', 'Тетово', 'Велес', 'Штип', 'Охрид',
  'Гостивар', 'Струмица', 'Кавадарци', 'Кочани', 'Кичево', 'Струга', 'Гевгелија',
  'Неготино', 'Радовиш', 'Дебар', 'Крива Паланка', 'Свети Николе', 'Берово',
  'Виница', 'Делчево', 'Пробиштип', 'Валандово', 'Македонски Брод', 'Демир Хисар',
  'Ресен', 'Крушево', 'Демир Капија', 'Богданци', 'Василево', 'Ново Село',
  'Кратово', 'Дојран', 'Росоман', 'Пехчево', 'Маврово', 'Сарај',
  'Аеродром', 'Бутел', 'Гази Баба', 'Ѓорче Петров', 'Карпош', 'Кисела Вода',
  'Центар', 'Чаир', 'Шуто Оризари',
]

function getLocalDateTimeString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

const form = reactive({
  transport_type_category: 'goods_transport',
  type: 'domestic',
  transport_mode: 'public',
  purpose: '',
  departure_date: getLocalDateTimeString(),
  return_date: getLocalDateTimeString(),
  employee_id: null,
  advance_amount_display: '',
  notes: '',
  vehicles: [createVehicle()],
  segments: [createSegment()],
  crew: [],
  cargo_items: [],
  expenses: [],
})

// ==================== Conditional Sections ====================

const showVehicles = computed(() =>
  form.transport_type_category === 'goods_transport' || form.transport_type_category === 'passenger_transport'
)

const showCargo = computed(() => form.transport_type_category === 'goods_transport')

const showTransportMode = computed(() =>
  form.transport_type_category === 'goods_transport' || form.transport_type_category === 'passenger_transport'
)

// ==================== Options ====================

const transportCategoryOptions = computed(() => [
  { value: 'goods_transport', label: t('goods_transport'), description: t('goods_transport_desc'), icon: 'TruckIcon' },
  { value: 'passenger_transport', label: t('passenger_transport'), description: t('passenger_transport_desc'), icon: 'UserGroupIcon' },
  { value: 'business_trip', label: t('business_trip'), description: t('business_trip_desc'), icon: 'BriefcaseIcon' },
])

const typeOptions = computed(() => [
  { value: 'domestic', label: t('domestic') },
  { value: 'foreign', label: t('foreign') },
])

const transportModeOptions = computed(() => [
  { value: 'public', label: t('transport_mode_public') },
  { value: 'own_needs', label: t('transport_mode_own') },
])

const vehicleTypeOptions = computed(() => [
  { value: 'truck', label: t('vehicle_truck') },
  { value: 'trailer', label: t('vehicle_trailer') },
  { value: 'car', label: t('transport_car') },
  { value: 'van', label: t('vehicle_van') },
])

const fuelTypeOptions = computed(() => [
  { value: 'diesel', label: t('fuel_diesel') },
  { value: 'petrol', label: t('fuel_petrol') },
  { value: 'lpg', label: t('fuel_lpg') },
  { value: 'cng', label: t('fuel_cng') },
])

const crewRoleOptions = computed(() => [
  { value: 'driver', label: t('crew_driver') },
  { value: 'co_driver', label: t('crew_co_driver') },
  { value: 'crew', label: t('crew') },
])

const transportOptions = computed(() => [
  { value: 'car', label: t('transport_car') },
  { value: 'bus', label: t('transport_bus') },
  { value: 'train', label: t('transport_train') },
  { value: 'plane', label: t('transport_plane') },
  { value: 'other', label: t('transport_other') },
])

const currencyOptions = [
  'MKD', 'EUR', 'USD', 'CHF', 'GBP', 'BGN', 'RSD', 'ALL', 'TRY', 'CZK', 'DKK', 'HUF', 'NOK', 'PLN', 'RON', 'SEK'
]

const glCodeMap = {
  per_diem: '440', fuel: '403', tolls: '449', forwarding: '419',
  accommodation: '440', transport: '440', vehicle_maintenance: '410',
  communication: '419', meals: '440', other: '449',
}

const expenseCategoryOptions = computed(() => {
  if (expenseCategories.value.length > 0) {
    return expenseCategories.value.map(c => ({
      value: c.value || c.key,
      label: c.label || c.name,
      gl_code: c.gl_code || glCodeMap[c.value || c.key] || '',
    }))
  }
  return [
    { value: 'fuel', label: t('category_fuel') },
    { value: 'tolls', label: t('category_tolls') },
    { value: 'forwarding', label: t('category_forwarding') },
    { value: 'accommodation', label: t('category_accommodation') },
    { value: 'transport', label: t('category_transport') },
    { value: 'vehicle_maintenance', label: t('category_vehicle_maintenance') },
    { value: 'communication', label: t('category_communication') },
    { value: 'meals', label: t('category_meals') },
    { value: 'other', label: t('category_other') },
  ]
})

// ==================== Computed ====================

const canSave = computed(() => {
  if (!form.transport_type_category || !form.type || !form.purpose) return false
  if (form.segments.length === 0) return false
  return form.segments.every(s => s.from_city && s.to_city && s.departure_at && s.arrival_at && s.transport_type)
})

const advanceInCents = computed(() => Math.round(parseFloat(form.advance_amount_display || 0) * 100))

const totalPerDiemMkd = computed(() => {
  return form.segments.reduce((sum, seg) => {
    const pd = segmentPerDiem(seg)
    return sum + (pd ? pd.mkdAmount : 0)
  }, 0)
})

const totalDistanceKm = computed(() => {
  return form.segments.reduce((sum, seg) => sum + (parseFloat(seg.distance_km) || 0), 0)
})

const totalExpensesMkd = computed(() => {
  return form.expenses.reduce((sum, exp) => sum + expenseMkdAmount(exp), 0)
})

const grandTotalMkd = computed(() => (totalPerDiemMkd.value / 100) + totalExpensesMkd.value)

const estimatedReimbursementMkd = computed(() => grandTotalMkd.value - (advanceInCents.value / 100))

const expensesByCategory = computed(() => {
  const groups = {}
  for (const exp of form.expenses) {
    const cat = exp.category || 'other'
    if (!groups[cat]) {
      groups[cat] = { category: cat, label: expenseCategoryLabel(cat), glCode: getGlCode(cat), totalMkd: 0 }
    }
    groups[cat].totalMkd += expenseMkdAmount(exp)
  }
  return Object.values(groups)
})

// ==================== Factory Functions ====================

function createVehicle() {
  return {
    vehicle_type: 'truck', make: '', model: '', registration_plate: '',
    capacity_tonnes: '', fuel_type: 'diesel', odometer_start: '',
    fuel_start_liters: '', fuel_norm_per_100km: defaultFuelNorms.truck,
  }
}

function createSegment() {
  return {
    from_city: '', to_city: '', country_code: '',
    departure_at: getLocalDateTimeString(), arrival_at: getLocalDateTimeString(),
    transport_type: 'car', distance_km: '',
    per_diem_rate: '', per_diem_currency: 'EUR',
    accommodation_provided: false, breakfast_provided: false, lunch_provided: false, dinner_provided: false,
  }
}

function createCargo() {
  return {
    cmr_number: '', sender_name: '', sender_address: '',
    receiver_name: '', receiver_address: '', goods_description: '',
    packages_count: '', gross_weight_kg: '', loading_place: '', unloading_place: '',
  }
}

function createCrew() {
  return { name: '', role: 'driver', license_number: '', license_category: '', cpc_number: '' }
}

function createExpense() {
  return { category: 'fuel', description: '', currency_code: 'MKD', amount_display: '', exchange_rate: 1, receipt_number: '', vat_amount_display: '' }
}

// ==================== Helpers ====================

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

function formatMoney(cents) {
  if (!cents && cents !== 0) return '0.00'
  const fmtLocale = localeMap[i18nLocale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(cents / 100)
}

function formatDecimal(val) {
  if (!val && val !== 0) return '0.00'
  const fmtLocale = localeMap[i18nLocale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val)
}

function formatDateTime(dt) {
  if (!dt) return ''
  const d = new Date(dt)
  if (isNaN(d)) return dt
  return d.toLocaleDateString(localeMap[i18nLocale.value] || 'mk-MK', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function expenseCategoryLabel(cat) {
  const opt = expenseCategoryOptions.value.find(o => o.value === cat)
  return opt ? opt.label : cat
}

function getGlCode(category) {
  const opt = expenseCategoryOptions.value.find(o => o.value === category)
  if (opt && opt.gl_code) return opt.gl_code
  return glCodeMap[category] || ''
}

function expenseMkdAmount(exp) {
  const amount = parseFloat(exp.amount_display || 0)
  const rate = parseFloat(exp.exchange_rate || 1)
  return amount * rate
}

function segmentPerDiem(seg) {
  if (!seg.departure_at || !seg.arrival_at) return null
  const dep = new Date(seg.departure_at)
  const arr = new Date(seg.arrival_at)
  const diffMs = arr - dep
  if (diffMs <= 0) return null

  const hours = Math.round(diffMs / (1000 * 60 * 60) * 10) / 10
  const days = hours <= 8 ? 0 : hours <= 12 ? 0.5 : Math.ceil(hours / 24)

  let rate = 0
  let currency = 'MKD'
  if (form.type === 'foreign' && seg.per_diem_rate) {
    rate = parseFloat(seg.per_diem_rate) || 0
    currency = seg.per_diem_currency || 'EUR'
  } else {
    rate = hours > 12 ? 3430 : hours > 8 ? 1715 : 0
    currency = 'MKD'
  }

  let reductions = 0
  if (seg.breakfast_provided) reductions += 10
  if (seg.lunch_provided) reductions += 30
  if (seg.dinner_provided) reductions += 30

  let baseAmount = form.type === 'foreign' ? rate * days : rate
  const netAmount = baseAmount - baseAmount * (reductions / 100)

  let mkdAmount
  if (currency === 'MKD') {
    mkdAmount = Math.round(netAmount * 100)
  } else {
    const exRate = parseFloat(exchangeRates.value[currency]) || 61.5
    mkdAmount = Math.round(netAmount * exRate * 100)
  }

  return { hours, days, rate, currency, reductions, baseAmount, netAmount, mkdAmount }
}

function estimatedFuelConsumption(vehicle) {
  const norm = parseFloat(vehicle.fuel_norm_per_100km) || 0
  return (totalDistanceKm.value * norm) / 100
}

// ==================== Add / Remove ====================

function addVehicle() { form.vehicles.push(createVehicle()) }
function removeVehicle(index) { form.vehicles.splice(index, 1) }
function onVehicleTypeChange(vehicle) {
  const norm = defaultFuelNorms[vehicle.vehicle_type]
  if (norm !== undefined) vehicle.fuel_norm_per_100km = norm
}

function addCrew() { form.crew.push(createCrew()) }
function removeCrew(index) { form.crew.splice(index, 1) }

function addSegment() {
  const newSeg = createSegment()
  if (form.segments.length > 0) {
    newSeg.from_city = form.segments[form.segments.length - 1].to_city || ''
  }
  form.segments.push(newSeg)
}
function removeSegment(index) { form.segments.splice(index, 1) }

function addCargo() { form.cargo_items.push(createCargo()) }
function removeCargo(index) { form.cargo_items.splice(index, 1) }

function addExpense() { form.expenses.push(createExpense()) }
function removeExpense(index) { form.expenses.splice(index, 1) }

// ==================== Auto-fill Handlers ====================

function onCountryChange(seg) {
  // Called after @select fires — seg.country_code is already updated by v-model
  setTimeout(() => {
    const code = seg.country_code
    if (code && perDiemRates.value[code]) {
      seg.per_diem_rate = perDiemRates.value[code].rate
      seg.per_diem_currency = perDiemRates.value[code].currency || 'EUR'
    }
  }, 0)
}

function onExpenseCurrencyChange(exp) {
  // Called after @select fires — exp.currency_code is already updated by v-model
  setTimeout(() => {
    if (exp.currency_code === 'MKD') {
      exp.exchange_rate = 1
    } else if (exchangeRates.value[exp.currency_code]) {
      exp.exchange_rate = exchangeRates.value[exp.currency_code]
    }
  }, 0)
}

// Auto-chain: new segment starts where previous ended
watch(
  () => form.segments.map(s => s.to_city),
  (newVals) => {
    for (let i = 1; i < form.segments.length; i++) {
      if (!form.segments[i].from_city && newVals[i - 1]) {
        form.segments[i].from_city = newVals[i - 1]
      }
    }
  },
  { deep: true }
)

// Auto-derive departure_date / return_date from first/last segment
watch(
  () => [
    ...form.segments.map(s => s.departure_at),
    ...form.segments.map(s => s.arrival_at),
  ],
  () => {
    const deps = form.segments.map(s => s.departure_at).filter(Boolean).sort()
    const arrs = form.segments.map(s => s.arrival_at).filter(Boolean).sort()
    if (deps.length) form.departure_date = deps[0]
    if (arrs.length) form.return_date = arrs[arrs.length - 1]
  },
  { deep: true }
)

// ==================== API Calls ====================

async function fetchEmployees() {
  isLoadingEmployees.value = true
  try {
    const response = await window.axios.get('/travel-orders/employees')
    employees.value = response.data?.data || []
  } catch { employees.value = [] }
  finally { isLoadingEmployees.value = false }
}

async function fetchPerDiemRates() {
  try {
    const response = await window.axios.get('/travel-orders/per-diem-rates')
    const arr = response.data?.data || response.data || []
    // Convert array to object keyed by country_code for fast lookup
    const map = {}
    for (const r of arr) {
      map[r.country_code] = { rate: r.rate, currency: r.currency, name_mk: r.country_name_mk, name_en: r.country_name_en }
    }
    perDiemRates.value = map
    // Also build countries list from same data
    countriesList.value = arr.map(r => ({ code: r.country_code, name: r.country_name_en || r.country_code }))
  } catch { perDiemRates.value = {} }
}

async function fetchExchangeRates() {
  try {
    const response = await window.axios.get('/travel-orders/exchange-rates')
    const arr = response.data?.data || response.data || []
    // Convert array to object keyed by currency_code
    const map = {}
    for (const r of arr) {
      map[r.currency_code] = r.rate_to_mkd
    }
    exchangeRates.value = map
  } catch { exchangeRates.value = { EUR: 61.5395, USD: 56.5516, CHF: 62.1109, GBP: 71.5 } }
}

async function fetchExpenseCategories() {
  try {
    const response = await window.axios.get('/travel-orders/expense-categories')
    expenseCategories.value = response.data?.data || response.data || []
  } catch { expenseCategories.value = [] }
}

// ==================== Save ====================

async function saveTravelOrder() {
  isSaving.value = true

  const segments = form.segments.map(s => ({
    from_city: s.from_city, to_city: s.to_city, country_code: s.country_code || null,
    departure_at: s.departure_at, arrival_at: s.arrival_at, transport_type: s.transport_type,
    distance_km: s.distance_km ? parseFloat(s.distance_km) : null,
    per_diem_rate: s.per_diem_rate ? parseFloat(s.per_diem_rate) : null,
    per_diem_currency: s.per_diem_currency || null,
    accommodation_provided: s.accommodation_provided,
    breakfast_provided: s.breakfast_provided, lunch_provided: s.lunch_provided, dinner_provided: s.dinner_provided,
  }))

  const expenses = form.expenses.map(e => ({
    category: e.category, description: e.description,
    amount: Math.round(parseFloat(e.amount_display || 0) * 100),
    currency_code: e.currency_code || 'MKD', exchange_rate: parseFloat(e.exchange_rate) || 1,
    receipt_number: e.receipt_number || null,
    vat_amount: e.vat_amount_display ? Math.round(parseFloat(e.vat_amount_display) * 100) : null,
  }))

  const vehicles = showVehicles.value ? form.vehicles.map(v => ({
    vehicle_type: v.vehicle_type, make: v.make || null, model: v.model || null,
    registration_plate: v.registration_plate,
    capacity_tonnes: v.capacity_tonnes ? parseFloat(v.capacity_tonnes) : null,
    fuel_type: v.fuel_type || null,
    odometer_start: v.odometer_start ? parseInt(v.odometer_start) : null,
    fuel_start_liters: v.fuel_start_liters ? parseFloat(v.fuel_start_liters) : null,
    fuel_norm_per_100km: v.fuel_norm_per_100km ? parseFloat(v.fuel_norm_per_100km) : null,
  })) : []

  const cargoItems = showCargo.value ? form.cargo_items.map(c => ({
    cmr_number: c.cmr_number || null, sender_name: c.sender_name || null,
    sender_address: c.sender_address || null, receiver_name: c.receiver_name || null,
    receiver_address: c.receiver_address || null, goods_description: c.goods_description,
    packages_count: c.packages_count ? parseInt(c.packages_count) : null,
    gross_weight_kg: c.gross_weight_kg ? parseFloat(c.gross_weight_kg) : null,
    loading_place: c.loading_place || null, unloading_place: c.unloading_place || null,
  })) : []

  try {
    const response = await window.axios.post('/travel-orders', {
      transport_type_category: form.transport_type_category,
      type: form.type,
      transport_mode: showTransportMode.value ? form.transport_mode : null,
      purpose: form.purpose,
      departure_date: form.departure_date,
      return_date: form.return_date,
      employee_id: form.employee_id,
      advance_amount: advanceInCents.value,
      notes: form.notes,
      vehicles, segments, crew: showVehicles.value ? form.crew.filter(c => c.name) : [],
      cargo: cargoItems, expenses,
    })

    notificationStore.showNotification({ type: 'success', message: response.data?.message || t('created_success') })

    const orderId = response.data?.data?.id
    router.push({ path: orderId ? `/admin/travel-orders/${orderId}` : '/admin/travel-orders' })
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_creating') })
  } finally {
    isSaving.value = false
  }
}

// ==================== Lifecycle ====================

onMounted(() => {
  fetchEmployees()
  fetchPerDiemRates()
  fetchExchangeRates()
  fetchExpenseCategories()
})

// CLAUDE-CHECKPOINT
</script>
