<script setup lang="ts">
import DocumentLogs from '@/components/logs/DocumentLogs.vue';

</script>

<template>
  <DocumentLogs
    title="Facture Logs"
    documentType="Invoice"
    itemType="InvoiceItem"
    routeName="logs-documents-invoices-id"
  />
</template>
