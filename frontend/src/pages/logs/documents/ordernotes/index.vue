<script setup lang="ts">
import DocumentLogs from '@/components/logs/DocumentLogs.vue';

</script>

<template>
  <DocumentLogs
    title="Bon de commande Logs"
    documentType="OrderNote"
    itemType="OrderNoteItem"
    routeName="logs-documents-ordernotes-id"
  />
</template>
