<script setup lang="ts">
import ClientOrderNotesTable from './ClientOrderNotesTable.vue';
import ClientOrderNotesChart from './ClientOrderNotesChart.vue';
</script>

<template>
  <VRow>
    <!-- 👉 Pie Chart Card -->
    <VCol cols="12">
      <ClientOrderNotesChart />
    </VCol>
    
    <!-- 👉 Order Notes Table -->
    <VCol cols="12">
      <ClientOrderNotesTable />
    </VCol>
  </VRow>
</template>