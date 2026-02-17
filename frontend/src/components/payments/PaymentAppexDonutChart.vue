<script lang="ts" setup>
import { getDonutChartConfig } from "@core/libs/apex-chart/apexCharConfig"
import { debounce } from "lodash"
import { computed, ref } from "vue"
import { useTheme } from "vuetify"

const vuetifyTheme = useTheme()

const props = defineProps<{
  statsData?: any
}>();

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

const turnoverData = ref<{ clientType: string; totalTurnover: number }[]>([])

const fetchData = async () => {
  isLoading.value = true;
  try {
    turnoverData.value = props.statsData
  } catch (error) {
    console.error("Error fetching data:", error)
  } finally {
    isLoading.value = false;
  }
}

const series = computed(() => {
  return turnoverData.value.map(item => item.totalTurnover)
})

const debouncedLoadClientType = debounce(fetchData, 500)


watch(
  () => props.statsData,
  (newData) => {
    if (newData && newData.length) {
      isLoading.value = true;
      debouncedLoadClientType();
    }
  },
  { immediate: true }
)

const labels = computed(() =>
  turnoverData.value.map(item => item.clientType)
)

const expenseRationChartConfig = computed(() => {
  const baseConfig = getDonutChartConfig(vuetifyTheme.current.value)
  return {
    ...baseConfig,
    labels: labels.value,
    plotOptions: {
      ...baseConfig.plotOptions,
      pie: {
        ...baseConfig.plotOptions.pie,
        donut: {
          ...baseConfig.plotOptions.pie.donut,
          labels: {
            ...baseConfig.plotOptions.pie.donut.labels,
            total: {
              ...baseConfig.plotOptions.pie.donut.labels.total,

            },
          },
        },
      },
    },
  }
})

</script>

<template>
  <VCard :title="turnoverData.length && isLoading === false ? 'Revenue par clients types' : undefined"
    :subtitle="turnoverData.length && isLoading === false ? 'Revenue par diverses types' : undefined">
    <div v-if="turnoverData.length && isLoading === false">
      <VCardText>
        <VueApexCharts type="donut" height="338" :options="expenseRationChartConfig" :series="series"
          :labels="turnoverData.map(item => item.clientType)" />
      </VCardText>
    </div>
    <div v-else class="d-flex justify-center align-center  loading-container">
      <VProgressCircular indeterminate color="primary" />
    </div>
  </VCard>
</template>
<style scoped>
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
  min-height: 455px;
}
</style>
