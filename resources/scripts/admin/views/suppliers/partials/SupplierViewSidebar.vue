<template>
  <div
    class="
      fixed
      top-0
      left-0
      hidden
      h-full
      pt-16
      pb-[6.6rem]
      ml-56
      bg-white
      xl:ml-64
      w-88
      xl:block
    "
  >
    <div
      class="
        flex
        items-center
        justify-between
        px-4
        pt-8
        pb-2
        border border-gray-200 border-solid
        height-full
      "
    >
      <BaseInput
        v-model="searchData.searchText"
        :placeholder="$t('general.search')"
        container-class="mb-6"
        type="text"
        variant="gray"
        @input="onSearch()"
      >
        <BaseIcon name="MagnifyingGlassIcon" class="text-gray-500" />
      </BaseInput>

      <div class="flex mb-6 ml-3" role="group" aria-label="First group">
        <BaseDropdown
          :close-on-select="false"
          position="bottom-start"
          width-class="w-40"
          position-class="left-0"
        >
          <template #activator>
            <BaseButton variant="gray">
              <BaseIcon name="FunnelIcon" />
            </BaseButton>
          </template>

          <div
            class="
              px-4
              py-3
              pb-2
              mb-2
              text-sm
              border-b border-gray-200 border-solid
            "
          >
            {{ $t('general.sort_by') }}
          </div>

          <div class="px-2">
            <BaseDropdownItem
              class="flex px-1 py-2 mt-1 cursor-pointer hover:rounded-md"
            >
              <BaseInputGroup class="pt-2 -mt-4">
                <BaseRadio
                  id="filter_create_date"
                  v-model="searchData.orderByField"
                  :label="$t('customers.create_date')"
                  size="sm"
                  name="filter"
                  value="created_at"
                  @update:modelValue="onSearch"
                />
              </BaseInputGroup>
            </BaseDropdownItem>
          </div>

          <div class="px-2">
            <BaseDropdownItem class="flex px-1 cursor-pointer hover:rounded-md">
              <BaseInputGroup class="pt-2 -mt-4">
                <BaseRadio
                  id="filter_display_name"
                  v-model="searchData.orderByField"
                  :label="$t('suppliers.name')"
                  size="sm"
                  name="filter"
                  value="name"
                  @update:modelValue="onSearch"
                />
              </BaseInputGroup>
            </BaseDropdownItem>
          </div>
        </BaseDropdown>

        <BaseButton class="ml-1" size="md" variant="gray" @click="sortData">
          <BaseIcon v-if="getOrderBy" name="SortAscendingIcon" />
          <BaseIcon v-else name="SortDescendingIcon" />
        </BaseButton>
      </div>
    </div>

    <div
      ref="supplierListSection"
      class="
        h-full
        overflow-y-scroll
        border-l border-gray-200 border-solid
        sidebar
        base-scroll
      "
    >
      <div v-for="(supplier, index) in supplierList" :key="index">
        <router-link
          v-if="supplier"
          :id="'supplier-' + supplier.id"
          :to="`/admin/suppliers/${supplier.id}/view`"
          :class="[
            'flex justify-between p-4 items-center cursor-pointer hover:bg-gray-100 border-l-4 border-transparent',
            {
              'bg-gray-100 border-l-4 border-primary-500 border-solid':
                hasActiveUrl(supplier.id),
            },
          ]"
          style="border-top: 1px solid rgba(185, 193, 209, 0.41)"
        >
          <div>
            <BaseText
              :text="supplier.name"
              class="
                pr-2
                text-sm
                not-italic
                font-normal
                leading-5
                text-black
                capitalize
                truncate
              "
            />

            <BaseText
              v-if="supplier.contact_name"
              :text="supplier.contact_name"
              class="
                mt-1
                text-xs
                not-italic
                font-medium
                leading-5
                text-gray-600
              "
            />
          </div>
          <div class="flex-1 font-bold text-right whitespace-nowrap">
            <BaseFormatMoney
              :amount="supplier.due_amount !== null ? supplier.due_amount : 0"
              :currency="supplier.currency"
            />
          </div>
        </router-link>
      </div>
      <div v-if="isFetching" class="flex justify-center p-4 items-center">
        <LoadingIcon
          class="h-6 m-1 animate-spin text-primary-400"
        />
      </div>
      <p
        v-if="!supplierList?.length && !isFetching"
        class="flex justify-center px-4 mt-5 text-sm text-gray-600"
      >
        {{ $t('suppliers.no_matching_suppliers') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'
import { debounce } from 'lodash'

const suppliersStore = useSuppliersStore()
const route = useRoute()
const { t } = useI18n()

let isFetching = ref(false)

let searchData = reactive({
  orderBy: null,
  orderByField: null,
  searchText: null,
})

const supplierList = ref(null)
const currentPageNumber = ref(1)
const lastPageNumber = ref(1)
const supplierListSection = ref(null)

onSearch = debounce(onSearch, 500)

const getOrderBy = computed(() => {
  if (searchData.orderBy === 'asc' || searchData.orderBy == null) {
    return true
  }
  return false
})

const getOrderName = computed(() =>
  getOrderBy.value ? t('general.ascending') : t('general.descending')
)

function hasActiveUrl(id) {
  return route.params.id == id
}

async function loadSuppliers(pageNumber, fromScrollListener = false) {
  if (isFetching.value) {
    return
  }

  let params = {}
  if (
    searchData.searchText !== '' &&
    searchData.searchText !== null &&
    searchData.searchText !== undefined
  ) {
    params.search = searchData.searchText
  }

  if (searchData.orderBy !== null && searchData.orderBy !== undefined) {
    params.orderBy = searchData.orderBy
  }

  if (
    searchData.orderByField !== null &&
    searchData.orderByField !== undefined
  ) {
    params.orderByField = searchData.orderByField
  }

  params.page = pageNumber

  isFetching.value = true

  try {
    let response = await suppliersStore.fetchSuppliers(params)

    if (fromScrollListener) {
      supplierList.value = [
        ...supplierList.value,
        ...response.data.data,
      ]
    } else {
      supplierList.value = response.data.data
    }

    currentPageNumber.value = response.data.meta.current_page
    lastPageNumber.value = response.data.meta.last_page
  } catch (error) {
    console.error(error)
  } finally {
    isFetching.value = false
  }
}

async function onSearch() {
  loadSuppliers(1, false)
}

function sortData() {
  if (searchData.orderBy === 'asc') {
    searchData.orderBy = 'desc'
    loadSuppliers(1, false)
    return true
  }
  searchData.orderBy = 'asc'
  loadSuppliers(1, false)
  return true
}

function scrollListener() {
  const element = supplierListSection.value

  if (element.scrollTop + element.clientHeight >= element.scrollHeight - 10) {
    if (currentPageNumber.value < lastPageNumber.value) {
      loadSuppliers(currentPageNumber.value + 1, true)
    }
  }
}

onMounted(() => {
  loadSuppliers(1, false)
  if (supplierListSection.value) {
    supplierListSection.value.addEventListener('scroll', scrollListener)
  }
})
</script>
// CLAUDE-CHECKPOINT
