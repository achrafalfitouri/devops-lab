<script setup lang="ts">
import dayjs from 'dayjs';
import { computed } from 'vue';
import { useTheme } from 'vuetify';

const vuetifyTheme = useTheme();

// 👉 Accept cash register as prop
const props = defineProps({
  cashRegister: Object,
  selectedFilter: Boolean
});

// 👉 Initialize dailyStats as a computed property
const dailyStats = computed(() => props.cashRegister?.dailyStats || []);

// 👉 Filtered Stats mapped to days of the week
const filteredStats = computed(() => {
  return dailyStats.value.map((stat: { date: string; inflows: number; outflows: number }) => ({
    day: dayjs(stat.date).format('ddd'),
    inflows: stat.inflows || 0,
    outflows: stat.outflows || 0,
  }));
});

// 👉 Chart data series based on dailyStats
const series = computed(() => {
  if (!dailyStats.value.length) {
    return [];
  }


  //👉 Extract inflows and outflows from filteredStats
  const inflows = filteredStats.value.map((stat: { inflows: number }) => stat.inflows);
  const outflows = filteredStats.value.map((stat: { outflows: number }) => -stat.outflows);

  return [
    {
      name: 'Entrés',
      data: inflows,
    },
    {
      name: 'Sorties',
      data: outflows,
    },
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
// Chart options based on the theme
const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors;

  return {
    chart: {
      parentHeightOffset: 0,
      stacked: true,
      type: 'bar',
      toolbar: { show: false },
    },
    tooltip: {
      enabled: true,
    },
    legend: {
      show: true,
    },
    stroke: {
      curve: 'smooth',
      width: 6,
      lineCap: 'round',
      colors: [currentTheme.surface],
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '45%',
        borderRadius: 8,
        borderRadiusApplication: 'around',
        borderRadiusWhenStacked: 'all',
      },
    },
    colors: ['rgba(var(--v-theme-success),1)', 'rgba(var(--v-theme-primary),1)'],
    dataLabels: {
      enabled: false,
    },
    grid: {
      show: false,
      padding: {
        top: -40,
        bottom: 0,
        left: -10,
        right: -2,
      },
    },
    xaxis: {
      categories: days.value,
      labels: {
        show: true,
      },
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
    yaxis: {
      labels: {
        show: false,
      },
    },
    responsive: [
      {
        breakpoint: 1600,
        options: {
          plotOptions: {
            bar: {
              columnWidth: '50%',
              borderRadius: 8,
            },
          },
        },
      },
    ],
  };
});
</script>

<template>
  <VCard>
    <div v-if="days.length">
      <VCardItem class="pb-0">
        <VCardTitle>Balance</VCardTitle>
      </VCardItem>

      <VCardText>
        <VueApexCharts :options="chartOptions" :series="series" height="191" class="my-2" />
      </VCardText>
    </div>

    <template v-else>
      <!-- Show loading spinner if days are not loaded -->
      <div class="d-flex justify-center align-center loading-container">
        <VProgressCircular indeterminate color="primary" />
      </div>
    </template>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 16px;
}

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
  min-height: 280px;

}
</style>
