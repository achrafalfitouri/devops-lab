<script setup lang="ts">
import ClientDeliveryNotesTable from './ClientDeliveryNotesTable.vue';
import ClientDeliveryNotesChart from './ClientDeliveryNotesChart.vue';
</script>

<template>
  <VRow>
    <!-- 👉 Pie Chart Card -->
    <VCol cols="12">
      <ClientDeliveryNotesChart />
    </VCol>
    
    <!-- 👉 Delivery Notes Table -->
    <VCol cols="12">
      <ClientDeliveryNotesTable />
    </VCol>
  </VRow>
</template>