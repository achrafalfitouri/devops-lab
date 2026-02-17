<script setup lang="ts">
import { fetchStaticData } from '@/services/api/transaction';
import { getDefaultCashRegisterFilterParams } from '@/services/defaults';
import { CashRegisterFilterParms } from '@/services/models';
import { hexToRgb } from '@layouts/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import { PropType } from 'vue';
import { useTheme } from 'vuetify';

dayjs.locale('fr');

const vuetifyTheme = useTheme()
const props = defineProps({
  cashRegister: Object,
  seC: String as PropType<any>


});

const emit = defineEmits(['updateFilterParams', 'openAddCashRegister', 'update-load-static-data']);

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
}

const filterItems = ref<{
  types: FilterItem[];
  cashregisters: FilterItem[];
}>({
  types: [],
  cashregisters: []

});

interface EarningsReport {
  color: string;
  icon: string;
  title: string;
  amount: string | number;
  progress: string;
}

const earningsReports: Ref<EarningsReport[]> = ref([]);

// 👉 Filters and search
const filterParamsCash = ref<CashRegisterFilterParms>(getDefaultCashRegisterFilterParams())

// 👉 computed property that returns true if the selected value is not "All," and false otherwise.
const isClearable = computed(() => {
  return filterParamsCash.value.selectedCashRegister !== null;
});

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    // Call the loadStaticData function passed as a prop
    const staticData = await fetchStaticData();
    filterItems.value.types = mapStaticData(staticData.data.transactionType);
    filterItems.value.cashregisters = mapStaticData(staticData.data.cashRegisterFilter);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  // loadStaticData();
  updateEarningsReports()
});

const openAddCashRegister = () => {
  emit('openAddCashRegister');
}

// 👉 Function to update the earnings report based on selected cash register
const updateEarningsReports = () => {
  const selectedCashRegister = filterParamsCash.value.selectedCashRegister;
  const stats = selectedCashRegister ? props.cashRegister?.stats : props.cashRegister?.stats;

  if (stats) {
    earningsReports.value = computeEarningsReports(stats);
  } else {
    earningsReports.value = computeEarningsReports(props.cashRegister?.stats);
  }
};

// 👉 Watch `selectedCashRegister` and emit an event when it changes
watch(() => props.seC, (newVal) => {

  earningsReports.value = [];
  emit('updateFilterParams', { selectedCashRegister: newVal });
  updateEarningsReports();
});

watch(() => props.cashRegister, (newVal) => {
  if (newVal?.stats) {
    updateEarningsReports();
    // loadStaticData();

  }
}, { immediate: true });

const selectedCashRegister = computed(() => {
  return props.cashRegister?.cashRegisters?.find(
    (register: { id: string | null }) => register.id === props.seC
  );
});

const computeEarningsReports = (stats: any): EarningsReport[] => {
  const selectedRegister = selectedCashRegister.value;

  const total = stats?.cashRegistersTotal ?? 0;
  const totalCashTransactions = props.cashRegister?.totalCashTransactions ?? 0;
  const transactionNumber = selectedRegister ? selectedRegister.transactionsCount ?? 'N/A' : totalCashTransactions;
  const transactionNumberProgress = totalCashTransactions > 0 ? (transactionNumber / totalCashTransactions) * 100 : 0;
  const inflows = stats?.inflows ?? 0;
  const outflows = stats?.outflows ?? 0;
  const inflowProgress = total > 0 ? (inflows / total) * 100 : 0;
  const outflowProgress = total > 0 ? (outflows / total) * 100 : 0;

  return [
    {
      color: 'info',
      icon: 'tabler-number',
      title: 'Nombre de transactions',
      amount: transactionNumber ?? 'N/A',
      progress: transactionNumberProgress.toFixed(2)
    },
    {
      color: 'success',
      icon: 'tabler-arrow-big-up',
      title: 'Volume des entrées',
      amount: `${inflows} DH`,
      progress: inflowProgress.toFixed(2)
    },
    {
      color: 'primary',
      icon: 'tabler-arrow-big-down',
      title: 'Volume des sorties',
      amount: `${outflows} DH`,
      progress: outflowProgress.toFixed(2)
    },

  ];
};

// 👉 Initialize dailyStats as a computed property
const dailyStats = computed(() => props.cashRegister?.dailyStats || []);

// 👉 Filtered Stats mapped to days of the week
const filteredStats = computed(() => {
  return dailyStats.value.map((stat: { date: string; totalBalance: number }) => ({
    day: dayjs(stat.date).format('ddd'),
    totalBalance: stat.totalBalance || 0,
  }));
});

// 👉 Chart data series based on dailyStats
const series = computed(() => {
  if (!dailyStats.value.length) {
    return [];
  }


  //👉 Extract inflows and outflows from filteredStats
  const totalBalance = filteredStats.value.map((stat: { totalBalance: number }) => stat.totalBalance);

  return [
    {
      name: 'Solde',
      data: totalBalance,
    }

  ];
});



// 👉 Chart days (X-axis)
const days = computed(() => {
  if (!dailyStats.value.length) {
    return [];
  }

  return dailyStats.value.map((stat: { date: string }) => {
    const formatted = dayjs(stat.date).format('ddd');
    return formatted.charAt(0).toUpperCase() + formatted.slice(1).replace(/\.$/, '');
  });
});




const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  return {
    chart: {
      parentHeightOffset: 0,
      type: 'bar',
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        barHeight: '60%',
        columnWidth: '38%',
        startingShape: 'rounded',
        endingShape: 'rounded',
        borderRadius: 4,
        distributed: true,
      },
    },
    grid: {
      show: false,
      padding: {
        top: -30,
        bottom: 0,
        left: -10,
        right: -10,
      },
    },
    colors: [
      `rgba(${hexToRgb(currentTheme.primary)}, 1)`,  // Full opacity for primary color for all days
      `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
      `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
      `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
      `rgba(${hexToRgb(currentTheme.primary)}, 1)`,  // Wednesday is the fifth day
      `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
      `rgba(${hexToRgb(currentTheme.primary)}, 1)`
    ],

    dataLabels: {
      enabled: false,
    },
    legend: {
      show: false,
    },
    xaxis: {
      categories: days.value,
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      labels: {
        style: {
          colors: `rgba(${hexToRgb(currentTheme['on-surface'])},${variableTheme['disabled-opacity']})`,
          fontSize: '13px',
          fontFamily: 'Public Sans',
        },
      },
    },
    yaxis: {
      labels: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
    },
    responsive: [
      {
        breakpoint: 1025,
        options: {
          chart: {
            height: 199,
          },
        },
      },
    ],
  }
})

</script>

<template>
  <VCard>
    <div v-if="days.length">
      <VCardItem class="pb-sm-0">

        <!-- <template v-if="selectedCashRegister">
          <VCardTitle>
            {{ selectedCashRegister.name || 'N/A' }}
          </VCardTitle>
          <VCardSubtitle>
            {{ selectedCashRegister.code || 'N/A' }}
          </VCardSubtitle>
        </template>

<template v-else>
          <VCardTitle>
            Toutes les caisses
          </VCardTitle>
        </template> -->

        <template #append>
          <!-- <div class="app-user-search-filter d-flex align-center flex-wrap gap-1 justify-between"> -->
          <!-- 👉 Select input on the left -->
          <!-- <div class="custom-select-width me-4">
      <VCol>
        <AppSelect 
          v-model="filterParamsCash.selectedCashRegister" 
          :items="filterItems.cashregisters"
          item-title="text" 
          item-value="value" 
          clear-icon="tabler-x" 
          placeholder="Cash register" 
           :clearable="isClearable"
        />
      </VCol>
    </div> -->

          <!-- 👉 Add Cash register button on the right -->
          <!-- <VBtn icon="tabler-plus" @click="openAddCashRegister">
    </VBtn>
  </div> -->
        </template>

      </VCardItem>

      <VCardText>
        <VRow>
          <VCol cols="12" md="6" class="d-flex flex-column justify-center gap-4">
            <div class="pa-4 rounded"
              style="background-color: rgba(var(--v-theme-success), 0.08); border-left: 4px solid rgb(var(--v-theme-success));">
              <div class="d-flex align-center gap-2 mb-2">
                <VIcon icon="tabler-cash-banknote" color="success" size="24" />
                <h5 class="text-h6 font-weight-medium">
                  Solde total : {{ selectedCashRegister ? (selectedCashRegister.name || 'N/A') : 'Toutes les caisses' }}
                </h5>
              </div>
              <h2 class="text-h3 font-weight-medium">
                {{ cashRegister?.stats?.cashRegistersAllTimeTotal ?? 'N/A' }} DH
              </h2>
            </div>
            <div class="pa-4 rounded"
              style="background-color: rgba(var(--v-theme-info), 0.08); border-left: 4px solid rgb(var(--v-theme-info));">
              <div class="d-flex align-center gap-2 mb-2">
                <VIcon icon="tabler-arrows-diff" color="info" size="24" />
                <h5 class="text-h6 font-weight-medium">
                  Différence 7 jours :
                  {{ selectedCashRegister ? (selectedCashRegister.name || 'N/A') : 'Toutes les caisses' }}
                </h5>
              </div>
              <h2 class="text-h3 font-weight-medium">
                {{ cashRegister?.stats?.cashRegistersTotal ?? 'N/A' }} DH
              </h2>
            </div>
          </VCol>

          <VCol cols="12" md="6" class="d-flex flex-column justify-space-between">
            <h5 class="text-h6 font-weight-medium">Différence par jour</h5>
            <VueApexCharts :options="chartOptions" :series="series" height="161" />
          </VCol>
        </VRow>

        <div class="border rounded mt-5 pa-5">
          <VRow>
            <VCol v-for="report in earningsReports" :key="report.title" cols="12" sm="4">
              <div class="d-flex align-center">
                <VAvatar rounded size="26" :color="report.color" variant="tonal" class="me-2">
                  <VIcon size="18" :icon="report.icon" />
                </VAvatar>

                <h6 class="text-base font-weight-regular">
                  {{ report.title }}
                </h6>
              </div>
              <h6 class="text-h4 my-2">
                {{ report.amount }}
              </h6>
              <VProgressLinear :model-value="report.progress" :color="report.color" height="4" rounded rounded-bar />
            </VCol>
          </VRow>
        </div>
      </VCardText>
    </div>
    <!-- Show loading spinner if days are not loaded -->
    <div v-else class="d-flex justify-center align-center  loading-container">
      <VProgressCircular indeterminate color="primary" />
    </div>
  </VCard>
</template>

<style scoped>
.custom-select-width {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  width: 250px;
  /* stylelint-disable-next-line comment-empty-line-before */
  /* Adjust width as needed */
}



.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
  min-height: 380px;
}
</style>
