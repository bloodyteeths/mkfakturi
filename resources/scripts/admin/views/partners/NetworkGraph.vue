<template>
  <BasePage>
    <BasePageHeader :title="$t('partners.network_graph')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem :title="$t('partners.partners')" to="/admin/partners" />
        <BaseBreadcrumbItem :title="$t('partners.network_graph')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="mt-6 space-y-6">
      <!-- Controls -->
      <div class="p-4 bg-white border border-gray-200 rounded-lg shadow">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div>
              <label class="flex items-center text-sm">
                <input
                  v-model="filters.showPartners"
                  type="checkbox"
                  class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span class="ml-2">{{ $t('partners.show_partners') }}</span>
              </label>
            </div>
            <div>
              <label class="flex items-center text-sm">
                <input
                  v-model="filters.showCompanies"
                  type="checkbox"
                  class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span class="ml-2">{{ $t('partners.show_companies') }}</span>
              </label>
            </div>
            <div>
              <label class="flex items-center text-sm">
                <input
                  v-model="filters.showInactive"
                  type="checkbox"
                  class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span class="ml-2">{{ $t('partners.show_inactive') }}</span>
              </label>
            </div>
          </div>

          <BaseButton variant="secondary" size="sm" @click="loadNetwork">
            <template #left="slotProps">
              <BaseIcon name="ArrowPathIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.refresh') }}
          </BaseButton>
        </div>
      </div>

      <!-- Graph Container -->
      <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
        <div v-if="loading" class="flex items-center justify-center h-96">
          <BaseLoader />
        </div>

        <div v-else-if="graphData.nodes.length === 0" class="text-center py-12">
          <BaseIcon name="InboxIcon" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
          <p class="text-gray-500">{{ $t('partners.no_network_data') }}</p>
        </div>

        <div v-else>
          <div ref="graphContainer" class="relative bg-gray-50 rounded border border-gray-200" style="height: 600px;">
            <svg ref="svg" class="w-full h-full"></svg>
          </div>

          <!-- Legend -->
          <div class="mt-4 flex items-center justify-center gap-6 text-sm">
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
              <span>{{ $t('partners.partners') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-green-500 rounded-full"></div>
              <span>{{ $t('general.companies') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-0.5 bg-gray-400"></div>
              <span>{{ $t('partners.referral_link') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-0.5 bg-purple-400"></div>
              <span>{{ $t('partners.upline_link') }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Selected Node Details -->
      <div v-if="selectedNode" class="p-6 bg-white border border-gray-200 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('partners.node_details') }}</h3>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-sm text-gray-500">{{ $t('general.name') }}</div>
            <div class="text-base font-medium text-gray-900">{{ selectedNode.label }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">{{ $t('general.type') }}</div>
            <div class="text-base font-medium text-gray-900">{{ selectedNode.type === 'partner' ? $t('partners.partner') : $t('general.company') }}</div>
          </div>
          <div v-if="selectedNode.email">
            <div class="text-sm text-gray-500">{{ $t('general.email') }}</div>
            <div class="text-base font-medium text-gray-900">{{ selectedNode.email }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">{{ $t('general.status') }}</div>
            <span :class="selectedNode.active ? 'text-green-600' : 'text-gray-500'" class="text-base font-medium">
              {{ selectedNode.active ? $t('general.active') : $t('general.inactive') }}
            </span>
          </div>
          <div v-if="selectedNode.type === 'partner'">
            <div class="text-sm text-gray-500">{{ $t('partners.total_clients') }}</div>
            <div class="text-base font-medium text-gray-900">{{ selectedNode.total_clients || 0 }}</div>
          </div>
          <div v-if="selectedNode.type === 'partner'">
            <div class="text-sm text-gray-500">{{ $t('partners.tier') }}</div>
            <div class="text-base font-medium text-gray-900">{{ selectedNode.tier || 'Standard' }}</div>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import * as d3 from 'd3'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const loading = ref(true)
const graphContainer = ref(null)
const svg = ref(null)
const selectedNode = ref(null)

const filters = ref({
  showPartners: true,
  showCompanies: true,
  showInactive: false,
})

const graphData = ref({
  nodes: [],
  edges: [],
})

let simulation = null

async function loadNetwork() {
  loading.value = true
  try {
    const response = await axios.get('/referral-network/graph', {
      params: {
        include_partners: filters.value.showPartners,
        include_companies: filters.value.showCompanies,
        include_inactive: filters.value.showInactive,
      },
    })

    graphData.value = response.data
    await nextTick()
    renderGraph()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('partners.network_load_failed'),
    })
  } finally {
    loading.value = false
  }
}

function renderGraph() {
  if (!svg.value || graphData.value.nodes.length === 0) return

  // Clear previous graph
  d3.select(svg.value).selectAll('*').remove()

  const width = graphContainer.value.clientWidth
  const height = 600

  const svgElement = d3.select(svg.value)
    .attr('viewBox', [0, 0, width, height])

  // Create simulation
  simulation = d3.forceSimulation(graphData.value.nodes)
    .force('link', d3.forceLink(graphData.value.edges).id(d => d.id).distance(150))
    .force('charge', d3.forceManyBody().strength(-400))
    .force('center', d3.forceCenter(width / 2, height / 2))
    .force('collision', d3.forceCollide().radius(40))

  // Add zoom behavior
  const g = svgElement.append('g')

  svgElement.call(d3.zoom()
    .scaleExtent([0.5, 3])
    .on('zoom', (event) => {
      g.attr('transform', event.transform)
    }))

  // Draw edges
  const link = g.append('g')
    .selectAll('line')
    .data(graphData.value.edges)
    .enter()
    .append('line')
    .attr('stroke', d => d.type === 'upline' ? '#a855f7' : '#9ca3af')
    .attr('stroke-width', 2)
    .attr('stroke-dasharray', d => d.type === 'upline' ? '5,5' : 'none')

  // Draw nodes
  const node = g.append('g')
    .selectAll('g')
    .data(graphData.value.nodes)
    .enter()
    .append('g')
    .call(d3.drag()
      .on('start', dragStarted)
      .on('drag', dragged)
      .on('end', dragEnded))
    .on('click', (event, d) => {
      selectedNode.value = d
    })

  // Node circles
  node.append('circle')
    .attr('r', 20)
    .attr('fill', d => {
      if (d.type === 'partner') return d.active ? '#3b82f6' : '#9ca3af'
      return d.active ? '#10b981' : '#9ca3af'
    })
    .attr('stroke', '#fff')
    .attr('stroke-width', 2)

  // Node labels
  node.append('text')
    .text(d => d.label)
    .attr('text-anchor', 'middle')
    .attr('dy', 35)
    .attr('font-size', '12px')
    .attr('fill', '#374151')

  // Update positions on tick
  simulation.on('tick', () => {
    link
      .attr('x1', d => d.source.x)
      .attr('y1', d => d.source.y)
      .attr('x2', d => d.target.x)
      .attr('y2', d => d.target.y)

    node.attr('transform', d => `translate(${d.x},${d.y})`)
  })
}

function dragStarted(event) {
  if (!event.active) simulation.alphaTarget(0.3).restart()
  event.subject.fx = event.subject.x
  event.subject.fy = event.subject.y
}

function dragged(event) {
  event.subject.fx = event.x
  event.subject.fy = event.y
}

function dragEnded(event) {
  if (!event.active) simulation.alphaTarget(0)
  event.subject.fx = null
  event.subject.fy = null
}

watch(filters, () => {
  loadNetwork()
}, { deep: true })

onMounted(() => {
  loadNetwork()
})
</script>

// CLAUDE-CHECKPOINT
