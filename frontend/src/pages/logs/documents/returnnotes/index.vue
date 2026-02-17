<script setup lang="ts">
import DocumentLogs from '@/components/logs/DocumentLogs.vue';

</script>

<template>
  <DocumentLogs
    title="Bon de retour Logs"
    documentType="ReturnNote"
    itemType="ReturnNoteItem"
    routeName="logs-documents-returnnotes-id"
  />
</template>
