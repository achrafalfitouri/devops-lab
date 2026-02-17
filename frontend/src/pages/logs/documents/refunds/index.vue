<script setup lang="ts">
import DocumentLogs from '@/components/logs/DocumentLogs.vue';

</script>

<template>
  <DocumentLogs
    title="Bon de remboursement Logs"
    documentType="RefundNote"
    itemType="RefundNoteItem"
    routeName="logs-documents-refunds-id"
  />
</template>
