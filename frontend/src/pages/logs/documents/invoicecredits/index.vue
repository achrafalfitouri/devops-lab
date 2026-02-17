<script setup lang="ts">
import DocumentLogs from '@/components/logs/DocumentLogs.vue';

</script>

<template>
  <DocumentLogs
    title="Facture avoir Logs"
    documentType="InvoiceCredit"
    itemType="InvoiceCreditItem"
    routeName="logs-documents-invoicecredits-id"
  />
</template>
