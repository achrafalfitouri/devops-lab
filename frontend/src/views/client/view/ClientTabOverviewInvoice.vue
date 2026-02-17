<script setup lang="ts">
import ClientInvoicesTable from './ClientInvoicesTable.vue';
import ClientInvoicesChart from './ClientInvoicesChart.vue';
</script>

<template>
  <VRow>
    <!-- 👉 Pie Chart Card -->
    <VCol cols="12">
      <ClientInvoicesChart />
    </VCol>
    
    <!-- 👉 Invoices Table -->
    <VCol cols="12">
      <ClientInvoicesTable />
    </VCol>
  </VRow>
</template>