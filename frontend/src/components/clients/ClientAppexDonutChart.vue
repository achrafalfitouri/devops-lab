<script lang="ts" setup>
import { NumberOfClientsByGamut } from '@/services/api/client';
import { getDonutChartConfig } from '@core/libs/apex-chart/apexCharConfig';
import { useTheme } from 'vuetify';

const vuetifyTheme = useTheme()

const props = defineProps<{ loadClientDonnutStats: any }>();
const emit = defineEmits(['update:loadClientDonnutStats']);
const isLoading = ref(false)

const nbClientByGamut = ref<{ gamutName: string; clientCount: number }[]>([])

// Define the desired order for gamut names
const gamutOrder = ['Platinum', 'Gold', 'Silver', 'Bronze']

const fetchData = async () => {
  try {
    isLoading.value = true
    const filters: any = {};
    const data = await NumberOfClientsByGamut();
    nbClientByGamut.value = data.data
  } catch (error) {
    console.error("Error fetching data:", error)
  }
  finally {
    isLoading.value = false
  }
}

// Sort the data according to the specified order
const sortedNbClientByGamut = computed(() => {
  return [...nbClientByGamut.value].sort((a, b) => {
    const indexA = gamutOrder.indexOf(a.gamutName)
    const indexB = gamutOrder.indexOf(b.gamutName)

    // If both items are in the order array, sort by their position
    if (indexA !== -1 && indexB !== -1) {
      return indexA - indexB
    }

    // If only one item is in the order array, prioritize it
    if (indexA !== -1) return -1
    if (indexB !== -1) return 1

    // If neither item is in the order array, maintain original order
    return 0
  })
})

const series = computed(() => {
  return sortedNbClientByGamut.value.map(item => item.clientCount)
})

onMounted(() => {
  fetchData()
})

watch(
  () => props.loadClientDonnutStats,
  (newValue) => {
    if (newValue === true) {
      fetchData();
      emit('update:loadClientDonnutStats', false);
    }
  },
  {
    immediate: true,
  }
);

const syncedLoadClientDonnutStats = computed({
  get: () => props.loadClientDonnutStats,
  set: (value) => emit('update:loadClientDonnutStats', value)
});

watch(syncedLoadClientDonnutStats, (newValue) => {
  if (newValue) {
    fetchData();
    syncedLoadClientDonnutStats.value = false;
  }
}, { immediate: true });

const labels = computed(() =>
  sortedNbClientByGamut.value.map(item => item.gamutName)
)

const colors = computed(() => {
  return sortedNbClientByGamut.value.map(item => getColor('gamut', item.gamutName))
})

const expenseRationChartConfig = computed(() => {
  const baseConfig = getDonutChartConfig(vuetifyTheme.current.value)
  return {
    ...baseConfig,
    labels: labels.value,
    colors: colors.value,
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
  <VCard style="min-height: 380px;">
    <div v-if="sortedNbClientByGamut.length > 0 && !isLoading">
      <VCardItem :title="sortedNbClientByGamut.length ? 'Clients par gamme' : undefined"
        :subtitle="sortedNbClientByGamut.length ? 'Nombre des clients par gamme' : undefined">

        <VCardText>
          <VueApexCharts type="donut" height="264" :options="expenseRationChartConfig" :series="series" />
        </VCardText>
      </VCardItem>
    </div>
    <div v-else-if="!isLoading && (!nbClientByGamut || nbClientByGamut.length === 0)" class="text-center py-4">
      <span class="text-medium-emphasis">Aucune donnée à afficher.</span>
    </div>
    <!-- Show loading spinner if days are not loaded -->
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
  min-height: 383px;
}
</style>
