<script setup lang="ts">
import DocumentLogs from '@/components/logs/DocumentLogs.vue';

</script>

<template>
  <DocumentLogs
    title="Reçu de commande Logs"
    documentType="OrderReceipt"
    itemType="OrderReceiptItem"
    routeName="logs-documents-orderreceipts-id"
  />
</template>
