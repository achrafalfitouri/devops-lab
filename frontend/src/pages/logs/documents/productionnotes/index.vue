<script setup lang="ts">
import DocumentLogs from '@/components/logs/DocumentLogs.vue';

</script>

<template>
  <DocumentLogs
    title="Bon de production Logs"
    documentType="ProductionNote"
    itemType="ProductionNoteItem"
    routeName="logs-documents-productionnotes-id"
  />
</template>
