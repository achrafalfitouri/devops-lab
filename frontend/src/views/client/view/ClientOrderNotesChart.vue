<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import type { OrderNotes } from '@services/models';
import { fetchOrderNotes } from '@services/api/ordernote';

const route = useRoute('clients-id');
const isLoading = ref(true);

const OrderNotestatus = ['Brouillon', 'En attente', 'Rejeté', 'Validé', 'Terminé'];

const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 State variable for storing all OrderNotes 
const allOrderNotesData = ref<OrderNotes[]>([]);

// 👉 Function to fetch ALL OrderNotes for the client
const loadAllOrderNotes = async () => {
  isLoading.value = true;
  try {
    const id = route.params.id;
    const filters: any = { client: id };
    
    // Fetch all OrderNotes 
    const data = await fetchOrderNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    
    // Fix: Access the correct path in the response
    allOrderNotesData.value = data.orderNotes?.data || [];
      } catch (error) {
    console.error('Error fetching OrderNotes for chart:', error);
    allOrderNotesData.value = [];
  } finally {
    isLoading.value = false;
  }
};

// 👉 Calculate sum of finalAmount for each status
const statusTotals = computed(() => {
  const totals: Record<string, number> = {};
  
  // Initialize all statuses with 0
  OrderNotestatus.forEach(status => {
    totals[status] = 0;
  });
  
  // Sum finalAmount for each status
  allOrderNotesData.value.forEach(orderNote => {
    if (orderNote.status && OrderNotestatus.includes(orderNote.status)) {
      const finalAmount = parseFloat(orderNote.finalAmount as any) || 0;
      totals[orderNote.status] += finalAmount;
    }
  });
  
  return totals;
});

// 👉 Calculate total of all finalAmounts
const totalAmount = computed(() => {
  return Object.values(statusTotals.value).reduce((sum, amount) => sum + amount, 0);
});

// 👉 Chart series (amounts for each status)
const chartSeries = computed(() => {
  return OrderNotestatus.map(status => statusTotals.value[status]);
});

// 👉 Chart labels with percentages
const chartLabels = computed(() => {
  return OrderNotestatus.map(status => {
    const amount = statusTotals.value[status];
    const percentage = totalAmount.value > 0 
      ? ((amount / totalAmount.value) * 100).toFixed(1) 
      : '0.0';
    return `${status} (${percentage}%)`;
  });
});

// 👉 Chart options
const chartOptions = computed(() => ({
  chart: {
    type: 'pie',
    toolbar: {
      show: true,
      tools: {
        download: true,
      },
    },
  },
  labels: chartLabels.value,
  colors: ['#9E9E9E', '#FF9800', '#F44336', '#4CAF50', '#2196F3'],
  legend: {
    position: 'bottom',
    fontSize: '14px',
  },
  dataLabels: {
    enabled: true,
    formatter: function (val: number, opts: any) {
      const amount = chartSeries.value[opts.seriesIndex];
      return formatCurrency(amount);
    },
  },
  tooltip: {
    y: {
      formatter: function (value: number) {
        const percentage = totalAmount.value > 0 
          ? ((value / totalAmount.value) * 100).toFixed(1) 
          : '0.0';
        return `${formatCurrency(value)} (${percentage}%)`;
      },
    },
  },
  plotOptions: {
    pie: {
      donut: {
        labels: {
          show: false,
        },
      },
    },
  },
  responsive: [
    {
      breakpoint: 480,
      options: {
        chart: {
          width: 300,
        },
        legend: {
          position: 'bottom',
        },
      },
    },
  ],
}));

// 👉 Format currency helper
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'MAD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value)
    .replace('MAD', 'DH');
};

onMounted(() => {
  loadAllOrderNotes();
});
</script>

<template>
  <VCard>
    <VCardText>
      <div class="d-flex justify-space-between align-center mb-4">
        <h5 class="text-h5">Répartition des bons de commande par statut</h5>
        <VChip color="primary" variant="tonal">
          Total: {{ formatCurrency(totalAmount) }}
        </VChip>
      </div>

      <VDivider class="mb-4" />

      <!-- Loading State -->
      <div v-if="isLoading" class="d-flex justify-center align-center" style="min-height: 300px;">
        <VProgressCircular indeterminate color="primary" />
      </div>

      <!-- Chart -->
      <div v-else-if="totalAmount > 0">
        <VueApexCharts
          type="pie"
          height="350"
          :options="chartOptions"
          :series="chartSeries"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="text-center pa-8">
        <VIcon icon="tabler-file-invoice" size="48" color="disabled" class="mb-4" />
        <p class="text-body-1 text-disabled">Aucun bon de commande trouvé pour ce client</p>
      </div>
    </VCardText>
  </VCard>
</template>

<style scoped>
.status-indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}
</style>