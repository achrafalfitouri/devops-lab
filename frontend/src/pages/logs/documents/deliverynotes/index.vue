<script setup lang="ts">
import DocumentLogs from '@/components/logs/DocumentLogs.vue';

</script>

<template>
  <DocumentLogs
    title="Bon de livraison Logs"
    documentType="DeliveryNote"
    itemType="DeliveryNoteItem"
    routeName="logs-documents-deliverynotes-id"
  />
</template>
