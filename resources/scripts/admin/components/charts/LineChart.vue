<template>
  <div class="graph-container h-[300px]">
    <canvas id="graph" ref="graph" />
  </div>
</template>

<script setup>
// Import Chart.js v4.5.0 with explicit import
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend
)

import { ref, reactive, computed, onMounted, watchEffect, inject } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const utils = inject('utils')

const props = defineProps({
  labels: {
    type: Array,
    required: false,
    default: () => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  },
  values: {
    type: Array,
    required: false,
    default: () => [],
  },
  invoices: {
    type: Array,
    required: false,
    default: () => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
  },
  expenses: {
    type: Array,
    required: false,
    default: () => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
  },
  receipts: {
    type: Array,
    required: false,
    default: () => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
  },
  income: {
    type: Array,
    required: false,
    default: () => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
  },
})

let myLineChart = null
const graph = ref(null)
const companyStore = useCompanyStore()
const defaultCurrency = computed(() => {
  return companyStore.selectedCompanyCurrency
})

watchEffect(() => {
  if (props.labels && props.labels.length > 0) {
    if (myLineChart) {
      try {
        myLineChart.reset()
        update()
      } catch (error) {
        console.error('LineChart: Error updating chart:', error)
      }
    }
  }
})

onMounted(() => {
  if (!graph.value) {
    console.error('LineChart: Canvas element not found')
    return
  }
  
  let context = graph.value.getContext('2d')
  if (!context) {
    console.error('LineChart: Could not get canvas context')
    return
  }
  
  let options = reactive({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      tooltip: {
        enabled: true,
        callbacks: {
          label: function (context) {
            return utils.formatMoney(
              Math.round(context.parsed.y * 100),
              defaultCurrency.value
            )
          },
        },
      },
      legend: {
        display: false,
      },
    },
  })

  let data = reactive({
    labels: props.labels,
    datasets: [
      {
        label: 'Sales',
        fill: false,
        tension: 0.3,
        backgroundColor: 'rgba(230, 254, 249)',
        borderColor: '#040405',
        borderCapStyle: 'butt',
        borderDash: [],
        borderDashOffset: 0.0,
        borderJoinStyle: 'miter',
        pointBorderColor: '#040405',
        pointBackgroundColor: '#fff',
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBackgroundColor: '#040405',
        pointHoverBorderColor: 'rgba(220,220,220,1)',
        pointHoverBorderWidth: 2,
        pointRadius: 4,
        pointHitRadius: 10,
        data: props.invoices.map((invoice) => invoice / 100),
      },
      {
        label: 'Receipts',
        fill: false,
        tension: 0.3,
        backgroundColor: 'rgba(230, 254, 249)',
        borderColor: 'rgb(2, 201, 156)',
        borderCapStyle: 'butt',
        borderDash: [],
        borderDashOffset: 0.0,
        borderJoinStyle: 'miter',
        pointBorderColor: 'rgb(2, 201, 156)',
        pointBackgroundColor: '#fff',
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBackgroundColor: 'rgb(2, 201, 156)',
        pointHoverBorderColor: 'rgba(220,220,220,1)',
        pointHoverBorderWidth: 2,
        pointRadius: 4,
        pointHitRadius: 10,
        data: props.receipts.map((receipt) => receipt / 100),
      },
      {
        label: 'Expenses',
        fill: false,
        tension: 0.3,
        backgroundColor: 'rgba(245, 235, 242)',
        borderColor: 'rgb(255,0,0)',
        borderCapStyle: 'butt',
        borderDash: [],
        borderDashOffset: 0.0,
        borderJoinStyle: 'miter',
        pointBorderColor: 'rgb(255,0,0)',
        pointBackgroundColor: '#fff',
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBackgroundColor: 'rgb(255,0,0)',
        pointHoverBorderColor: 'rgba(220,220,220,1)',
        pointHoverBorderWidth: 2,
        pointRadius: 4,
        pointHitRadius: 10,
        data: props.expenses.map((expense) => expense / 100),
      },
      {
        label: 'Net Income',
        fill: false,
        tension: 0.3,
        backgroundColor: 'rgba(236, 235, 249)',
        borderColor: 'rgba(88, 81, 216, 1)',
        borderCapStyle: 'butt',
        borderDash: [],
        borderDashOffset: 0.0,
        borderJoinStyle: 'miter',
        pointBorderColor: 'rgba(88, 81, 216, 1)',
        pointBackgroundColor: '#fff',
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBackgroundColor: 'rgba(88, 81, 216, 1)',
        pointHoverBorderColor: 'rgba(220,220,220,1)',
        pointHoverBorderWidth: 2,
        pointRadius: 4,
        pointHitRadius: 10,
        data: props.income.map((_i) => _i / 100),
      },
    ],
  })

  myLineChart = new ChartJS(context, {
    type: 'line',
    data: data,
    options: options,
  })
})

function update() {
  if (!myLineChart) {
    console.error('LineChart: Chart instance not found')
    return
  }
  
  try {
    myLineChart.data.labels = props.labels || []
    myLineChart.data.datasets[0].data = (props.invoices || []).map(
      (invoice) => invoice / 100
    )
    myLineChart.data.datasets[1].data = (props.receipts || []).map(
      (receipt) => receipt / 100
    )
    myLineChart.data.datasets[2].data = (props.expenses || []).map(
      (expense) => expense / 100
    )
    myLineChart.data.datasets[3].data = (props.income || []).map((_i) => _i / 100)
    myLineChart.update()
  } catch (error) {
    console.error('LineChart: Error updating chart data:', error)
  }
}
</script>
