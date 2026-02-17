<script setup lang="ts">
import {  fetchReturnNotesById, GenerateReturnNotePdfById,fetchArchivedReturnNotesById } from '@/services/api/returnnote';


import { useArchiveStoreStore } from '@/stores/archive';
const archiveStore = useArchiveStoreStore();

// Components
const route = useRoute('documents-returnnotes-id')

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Bon de retour" :fetchFunction="archiveStore.isArchive ? fetchArchivedReturnNotesById : fetchReturnNotesById"
    :generatePdfFunction="GenerateReturnNotePdfById"  >
  </DocumentPreview>
</template>
