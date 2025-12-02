<template>
  <div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-8 overflow-x-auto">
      <router-link
        to="/admin/stock"
        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
        :class="isActive('/admin/stock') ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ $t('stock.inventory') }}
      </router-link>
      <router-link
        to="/admin/stock/item-card"
        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
        :class="isActive('/admin/stock/item-card') ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ $t('stock.item_card') }}
      </router-link>
      <router-link
        to="/admin/stock/low-stock"
        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
        :class="isActive('/admin/stock/low-stock') ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ $t('stock.low_stock') }}
      </router-link>
      <router-link
        to="/admin/stock/adjustments"
        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
        :class="isActive('/admin/stock/adjustments') ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ $t('stock.adjustments') }}
      </router-link>
      <router-link
        to="/admin/stock/warehouses"
        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
        :class="isActive('/admin/stock/warehouses') ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ $t('navigation.warehouses') }}
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()

const isActive = (path) => {
  // Exact match for /admin/stock (inventory page)
  if (path === '/admin/stock') {
    return route.path === '/admin/stock' || route.path === '/admin/stock/'
  }
  // Prefix match for sub-pages
  return route.path.startsWith(path)
}
</script>
