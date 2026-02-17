<script setup lang="ts">
import { fetchStaticData, InvoiceTurnover, OrderReceiptTurnover, RealTurnover, Recovery, TotalTurnover } from '@/services/api/payment'
import { hexToRgb } from '@layouts/utils'
import { debounce } from 'lodash'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()

const currentTab = ref<number>(0)
const refVueApexChart = ref()

const props = defineProps<{
  selectedYear: String | null
  selectedClient: String | null
}>();

const emit = defineEmits(['update:selectedYear', 'update:selectedClient']);

// 👉 Filters and search
const filterParams = ref({
  selectedYear: props.selectedYear,
  selectedClient: props.selectedClient,
});

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
}

// 👉 Explicitly define the type for filterItems
const filterItems = ref<{
  clients: FilterItem[];
}>({
  clients: [],
});

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 State variable for storing total turnover data
const totalTurnoverValues = ref<number[]>([]);
const monthsTotalTurnOver = ref<string[]>([]);

// 👉 Function to fetch total turnover from the API
const loadTotalTurnover = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};
    if (filterParams.value.selectedYear) filters.year = filterParams.value.selectedYear;
    if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
    const data = await TotalTurnover(filters);
    totalTurnoverValues.value = data.totalTurnover.map((entry: { totalTurnover: string | number }) => Number(entry.totalTurnover));
    monthsTotalTurnOver.value = data.totalTurnover.map((entry: { month: string }) => entry.month);

  } catch (error) {
    console.error('Error fetching total turnover:', error);
  } finally {
    isLoading.value = false;
  }
};

// 👉 State variable for storing invoice turnover data
const invoiceTurnoverValues = ref<number[]>([]);
const monthsInvoiceTurnOver = ref<string[]>([]);

// 👉 Function to fetch invoice turnover from the API
const loadInvoiceTurnover = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};
    if (filterParams.value.selectedYear) filters.year = filterParams.value.selectedYear;
    if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
    const data = await InvoiceTurnover(filters);
    invoiceTurnoverValues.value = data.invoiceTurnover.map((entry: { invoiceTurnover: string | number }) => Number(entry.invoiceTurnover));
    monthsInvoiceTurnOver.value = data.invoiceTurnover.map((entry: { month: string }) => entry.month);

  } catch (error) {
    console.error('Error fetching invoice turnover:', error);
  } finally {
    isLoading.value = false;
  }
};
// 👉 State variable for storing order receipt turnover data
const orderReceiptTurnoverValues = ref<number[]>([]);
const monthsOrderReceiptTurnOver = ref<string[]>([]);

// 👉 Function to fetch order receipt turnover from the API
const loadOrderReceiptTurnover = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};
    if (filterParams.value.selectedYear) filters.year = filterParams.value.selectedYear;
    if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
    const data = await OrderReceiptTurnover(filters);
    orderReceiptTurnoverValues.value = data.orderReceiptTurnover.map((entry: { orderReceiptTurnover: string | number }) => Number(entry.orderReceiptTurnover));
    monthsOrderReceiptTurnOver.value = data.orderReceiptTurnover.map((entry: { month: string }) => entry.month);

  } catch (error) {
    console.error('Error fetching order receipt turnover:', error);
  } finally {
    isLoading.value = false;
  }
};

// 👉 State variable for storing real turnover data
const realTurnoverValues = ref<number[]>([]);
const monthsRealTurnOver = ref<string[]>([]);

// 👉 Function to fetch real turnover from the API
const loadRealTurnover = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};
    if (filterParams.value.selectedYear) filters.year = filterParams.value.selectedYear;
    if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;

    const data = await RealTurnover(filters);
    realTurnoverValues.value = data.realTurnover.map((entry: { realTurnover: string | number }) => Number(entry.realTurnover));
    monthsRealTurnOver.value = data.realTurnover.map((entry: { month: string }) => entry.month);

  } catch (error) {
    console.error('Error fetching real turnover:', error);
  } finally {
    isLoading.value = false;
  }
};

// 👉 State variable for storing recovery data
const recoveryValues = ref<number[]>([]);
const monthsRecovery = ref<string[]>([]);

// 👉 Function to fetch recovery from the API
const loadRecovery = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};
    if (filterParams.value.selectedYear) filters.year = filterParams.value.selectedYear;
    if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
    const data = await Recovery(filters);
    recoveryValues.value = data.recovery.map((entry: { recovery: string | number }) => Number(entry.recovery));
    monthsRecovery.value = data.recovery.map((entry: { month: string }) => entry.month);

  } catch (error) {
    console.error('Error fetching recovery:', error);
  } finally {
    isLoading.value = false;
  }
};

const isClearableYear = computed(() => {
  return filterParams.value.selectedYear !== null;
});

// Generate year options from 2020 to the current year
const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear();
  return Array.from({ length: currentYear - 2019 }, (_, i) => {
    const year = 2020 + i;
    return { text: year.toString(), value: year };
  });
});

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.clients = mapStaticData(staticData.data.client);

  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

onMounted(() => {
  loadTotalTurnover();
  loadRealTurnover();
  loadRecovery();
  loadInvoiceTurnover();
  loadOrderReceiptTurnover();
  loadStaticData();
});
const debouncedLoadPayments = debounce(async () => {
  await loadTotalTurnover();
  await loadRealTurnover();
  await loadRecovery();
  await loadInvoiceTurnover();
  await loadOrderReceiptTurnover();
}, 800);

watch(filterParams, () => {
  debouncedLoadPayments();
}, { deep: true });
// Emit selected year change to parent
const updateSelectedYear = () => {
  emit('update:selectedYear', filterParams.value.selectedYear);
};
const updateSelectedClient = () => {
  emit('update:selectedClient', filterParams.value.selectedClient);
};

// Watch for changes and emit event
watch(() => filterParams.value.selectedYear, updateSelectedYear);
watch(() => filterParams.value.selectedClient, updateSelectedClient);

const chartConfigs = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  const labelPrimaryColor = `rgba(${hexToRgb(currentTheme.primary)},${variableTheme['dragged-opacity']})`
  const legendColor = `rgba(${hexToRgb(currentTheme['on-background'])},${variableTheme['high-emphasis-opacity']})`
  const borderColor = `rgba(${hexToRgb(String(variableTheme['border-color']))},${variableTheme['border-opacity']})`
  const labelColor = `rgba(${hexToRgb(currentTheme['on-surface'])},${variableTheme['disabled-opacity']})`

  return [
    {
      title: 'CA total',
      icon: 'tabler-shopping-cart',
      chartOptions: {
        chart: {
          parentHeightOffset: 0,
          type: 'bar',
          toolbar: {
            show: false,
          },
        },
        plotOptions: {
          bar: {
            columnWidth: '32%',
            borderRadiusApplication: 'end',
            borderRadius: 4,
            distributed: true,
            dataLabels: {
              position: 'top',
            },
          },
        },
        grid: {
          show: false,
          padding: {
            top: 0,
            bottom: 0,
            left: -10,
            right: -10,
          },
        },
        colors: [
          labelPrimaryColor,
          labelPrimaryColor,
          `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
        ],
        dataLabels: {
          enabled: true,
          formatter(val: number) {
            return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
          }
          ,
          offsetY: -25,
          style: {
            fontSize: '15px',
            colors: [legendColor],
            fontWeight: '600',
            fontFamily: 'Public Sans',
          },
        },
        legend: {
          show: false,
        },
        tooltip: {
          enabled: false,
        },
        xaxis: {
          categories: monthsTotalTurnOver.value,
          axisBorder: {
            show: true,
            color: borderColor,
          },
          axisTicks: {
            show: false,
          },
          labels: {
            style: {
              colors: labelColor,
              fontSize: '13px',
              fontFamily: 'Public Sans',
            },
          },
        },
        yaxis: {
          labels: {
            offsetX: -15,
            formatter(val: number) {
              return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
            },
            style: {
              fontSize: '13px',
              colors: labelColor,
              fontFamily: 'Public Sans',
            },
            min: 0,
            max: 60000,
            tickAmount: 6,
          },
        },
        responsive: [
          {
            breakpoint: 1441,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '41%',
                },
              },
            },
          },
          {
            breakpoint: 590,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '61%',
                },
              },
              yaxis: {
                labels: {
                  show: false,
                },
              },
              grid: {
                padding: {
                  right: 0,
                  left: -20,
                },
              },
              dataLabels: {
                style: {
                  fontSize: '12px',
                  fontWeight: '400',
                },
              },
            },
          },
        ],
      },
      series: [
        {
          data: totalTurnoverValues.value,
        },
      ],
    },
    {
      title: 'CA facture',
      icon: 'tabler-shopping-cart',
      chartOptions: {
        chart: {
          parentHeightOffset: 0,
          type: 'bar',
          toolbar: {
            show: false,
          },
        },
        plotOptions: {
          bar: {
            columnWidth: '32%',
            borderRadiusApplication: 'end',
            borderRadius: 4,
            distributed: true,
            dataLabels: {
              position: 'top',
            },
          },
        },
        grid: {
          show: false,
          padding: {
            top: 0,
            bottom: 0,
            left: -10,
            right: -10,
          },
        },
        colors: [
          labelPrimaryColor,
          labelPrimaryColor,
          `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
        ],
        dataLabels: {
          enabled: true,
          formatter(val: number) {
            return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
          }
          ,
          offsetY: -25,
          style: {
            fontSize: '15px',
            colors: [legendColor],
            fontWeight: '600',
            fontFamily: 'Public Sans',
          },
        },
        legend: {
          show: false,
        },
        tooltip: {
          enabled: false,
        },
        xaxis: {
          categories: monthsInvoiceTurnOver.value,
          axisBorder: {
            show: true,
            color: borderColor,
          },
          axisTicks: {
            show: false,
          },
          labels: {
            style: {
              colors: labelColor,
              fontSize: '13px',
              fontFamily: 'Public Sans',
            },
          },
        },
        yaxis: {
          labels: {
            offsetX: -15,
            formatter(val: number) {
              return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
            },
            style: {
              fontSize: '13px',
              colors: labelColor,
              fontFamily: 'Public Sans',
            },
            min: 0,
            max: 60000,
            tickAmount: 6,
          },
        },
        responsive: [
          {
            breakpoint: 1441,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '41%',
                },
              },
            },
          },
          {
            breakpoint: 590,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '61%',
                },
              },
              yaxis: {
                labels: {
                  show: false,
                },
              },
              grid: {
                padding: {
                  right: 0,
                  left: -20,
                },
              },
              dataLabels: {
                style: {
                  fontSize: '12px',
                  fontWeight: '400',
                },
              },
            },
          },
        ],
      },
      series: [
        {
          data: invoiceTurnoverValues.value,
        },
      ],
    },
    {
      title: 'CA reçu de commande',
      icon: 'tabler-shopping-cart',
      chartOptions: {
        chart: {
          parentHeightOffset: 0,
          type: 'bar',
          toolbar: {
            show: false,
          },
        },
        plotOptions: {
          bar: {
            columnWidth: '32%',
            borderRadiusApplication: 'end',
            borderRadius: 4,
            distributed: true,
            dataLabels: {
              position: 'top',
            },
          },
        },
        grid: {
          show: false,
          padding: {
            top: 0,
            bottom: 0,
            left: -10,
            right: -10,
          },
        },
        colors: [
          labelPrimaryColor,
          labelPrimaryColor,
          `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
        ],
        dataLabels: {
          enabled: true,
          formatter(val: number) {
            return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
          }
          ,
          offsetY: -25,
          style: {
            fontSize: '15px',
            colors: [legendColor],
            fontWeight: '600',
            fontFamily: 'Public Sans',
          },
        },
        legend: {
          show: false,
        },
        tooltip: {
          enabled: false,
        },
        xaxis: {
          categories: monthsOrderReceiptTurnOver.value,
          axisBorder: {
            show: true,
            color: borderColor,
          },
          axisTicks: {
            show: false,
          },
          labels: {
            style: {
              colors: labelColor,
              fontSize: '13px',
              fontFamily: 'Public Sans',
            },
          },
        },
        yaxis: {
          labels: {
            offsetX: -15,
            formatter(val: number) {
              return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
            },
            style: {
              fontSize: '13px',
              colors: labelColor,
              fontFamily: 'Public Sans',
            },
            min: 0,
            max: 60000,
            tickAmount: 6,
          },
        },
        responsive: [
          {
            breakpoint: 1441,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '41%',
                },
              },
            },
          },
          {
            breakpoint: 590,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '61%',
                },
              },
              yaxis: {
                labels: {
                  show: false,
                },
              },
              grid: {
                padding: {
                  right: 0,
                  left: -20,
                },
              },
              dataLabels: {
                style: {
                  fontSize: '12px',
                  fontWeight: '400',
                },
              },
            },
          },
        ],
      },
      series: [
        {
          data: orderReceiptTurnoverValues.value,
        },
      ],
    },
    {
      title: 'CA réel',
      icon: 'tabler-chart-bar',
      chartOptions: {
        chart: {
          parentHeightOffset: 0,
          type: 'bar',
          toolbar: {
            show: false,
          },
        },
        plotOptions: {
          bar: {
            columnWidth: '32%',
            borderRadiusApplication: 'end',
            borderRadius: 4,
            distributed: true,
            dataLabels: {
              position: 'top',
            },
          },
        },
        grid: {
          show: false,
          padding: {
            top: 0,
            bottom: 0,
            left: -10,
            right: -10,
          },
        },
        colors: [
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
          labelPrimaryColor,
          labelPrimaryColor,
        ],
        dataLabels: {
          enabled: true,
          formatter(val: number) {
            return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
          }
          ,
          offsetY: -25,
          style: {
            fontSize: '15px',
            colors: [legendColor],
            fontWeight: '600',
            fontFamily: 'Public Sans',
          },
        },
        legend: {
          show: false,
        },
        tooltip: {
          enabled: false,
        },
        xaxis: {
          categories: monthsRealTurnOver.value,
          axisBorder: {
            show: true,
            color: borderColor,
          },
          axisTicks: {
            show: false,
          },
          labels: {
            style: {
              colors: labelColor,
              fontSize: '13px',
              fontFamily: 'Public Sans',
            },
          },
        },
        yaxis: {
          labels: {
            offsetX: -15,
            formatter(val: number) {
              return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
            }
            ,
            style: {
              fontSize: '13px',
              colors: labelColor,
              fontFamily: 'Public Sans',
            },
            min: 0,
            max: 60000,
            tickAmount: 6,
          },
        },
        responsive: [
          {
            breakpoint: 1441,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '41%',
                },
              },
            },
          },
          {
            breakpoint: 590,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '61%',
                },
              },
              grid: {
                padding: {
                  right: 0,
                },
              },
              dataLabels: {
                style: {
                  fontSize: '12px',
                  fontWeight: '400',
                },
              },
              yaxis: {
                labels: {
                  show: false,
                },
              },
            },
          },
        ],
      },
      series: [
        {
          data: realTurnoverValues.value,
        },
      ],
    },
    {
      title: 'Recouvrement',
      icon: 'tabler-currency-dollar',
      chartOptions: {
        chart: {
          parentHeightOffset: 0,
          type: 'bar',
          toolbar: {
            show: false,
          },
        },
        plotOptions: {
          bar: {
            columnWidth: '32%',
            borderRadiusApplication: 'end',
            borderRadius: 4,
            distributed: true,
            dataLabels: {
              position: 'top',
            },
          },
        },
        grid: {
          show: false,
          padding: {
            top: 0,
            bottom: 0,
            left: -10,
            right: -10,
          },
        },
        colors: [
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
          labelPrimaryColor,
        ],
        dataLabels: {
          enabled: true,
          formatter(val: number) {
            return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
          }
          ,
          offsetY: -25,
          style: {
            fontSize: '15px',
            colors: [legendColor],
            fontWeight: '600',
            fontFamily: 'Public Sans',
          },
        },
        legend: {
          show: false,
        },
        tooltip: {
          enabled: false,
        },
        xaxis: {
          categories: monthsRecovery.value,
          axisBorder: {
            show: true,
            color: borderColor,
          },
          axisTicks: {
            show: false,
          },
          labels: {
            style: {
              colors: labelColor,
              fontSize: '13px',
              fontFamily: 'Public Sans',
            },
          },
        },
        yaxis: {
          labels: {
            offsetX: -15,
            formatter(val: number) {
              return val % 1 === 0 ? `${val} DH` : `${val.toFixed(2)} DH`;
            }
            ,
            style: {
              fontSize: '13px',
              colors: labelColor,
              fontFamily: 'Public Sans',
            },
            min: 0,
            max: 60000,
            tickAmount: 6,
          },
        },
        responsive: [
          {
            breakpoint: 1441,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '41%',
                },
              },
            },
          },
          {
            breakpoint: 590,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '61%',
                },
              },
              grid: {
                padding: {
                  right: 0,
                },
              },
              dataLabels: {
                style: {
                  fontSize: '12px',
                  fontWeight: '400',
                },
              },
              yaxis: {
                labels: {
                  show: false,
                },
              },
            },
          },
        ],
      },
      series: [
        {
          data: recoveryValues.value,
        },
      ],
    },
  ]
})

</script>

<template>
  <VCard
    :title="totalTurnoverValues.length && realTurnoverValues.length && recoveryValues.length && isLoading === false ? 'Rapports de gain' : undefined"
    :subtitle="totalTurnoverValues.length && realTurnoverValues.length && recoveryValues.length && isLoading === false ? 'Aperçu des gains annuels' : undefined">
    <div v-if="totalTurnoverValues.length && realTurnoverValues.length && recoveryValues.length && isLoading === false">
      <div class="d-flex justify-end align-center mt-n16 ">
        <VCol cols="12" md="2">
          <VAutocomplete v-model="filterParams.selectedClient" :items="filterItems.clients" item-title="text"
            item-value="value" label="Clients" clearable clear-icon="tabler-x" placeholder="Client"
            variant="outlined" />
        </VCol>
        <VCol cols="12" md="2">
          <VAutocomplete v-model="filterParams.selectedYear" placeholder="Année" label="Année" item-title="text"
            item-value="value" :items="yearOptions" :clearable="isClearableYear" clear-icon="tabler-x"
            variant="outlined" />
        </VCol>


      </div>

      <VCardText>
        <VSlideGroup v-model="currentTab" show-arrows mandatory class="mb-10">
          <VSlideGroupItem v-for="(report, index) in chartConfigs" :key="report.title" v-slot="{ isSelected, toggle }"
            :value="index">
            <div style="block-size: 100px; min-width: 140px; max-width: 100%;"
              :style="isSelected ? 'border-color:rgb(var(--v-theme-primary)) !important' : ''"
              :class="isSelected ? 'border' : 'border border-dashed'"
              class="d-flex flex-column justify-center align-center cursor-pointer rounded py-4 px-5 me-4"
              @click="toggle">

              <VAvatar rounded size="38" :color="isSelected ? 'primary' : ''" variant="tonal" class="mb-2">
                <VIcon size="22" :icon="report.icon" />
              </VAvatar>
              <h6 class="text-base font-weight-medium mb-0 text-center report-title">
                {{ report.title }}
              </h6>

            </div>
          </VSlideGroupItem>
        </VSlideGroup>

        <VueApexCharts ref="refVueApexChart" :key="currentTab" :options="chartConfigs[Number(currentTab)].chartOptions"
          :series="chartConfigs[Number(currentTab)].series" height="230" class="mt-3" />
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
  width: 140px;
  margin-left: auto;
  /* stylelint-disable-next-line comment-empty-line-before */
  /* Adjust width as needed */
}

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
  min-height: 490px;
}

.report-title {
  display: block;
  min-width: 120px;
  max-width: 100%;
  text-align: center;
  word-wrap: break-word;
  white-space: normal;
}
</style>
