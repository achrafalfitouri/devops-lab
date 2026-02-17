<script setup lang="ts">
import {  fetchProductionNotesById, GenerateProductionNoteById, GenerateProductionNotePdfById,fetchArchivedProductionNotesById } from '@/services/api/productionnote';
import { useDocumentCoreStore } from '@/stores/documents';

import { useArchiveStoreStore } from '@/stores/archive';


const archiveStore = useArchiveStoreStore();

// Components
const route = useRoute('documents-productionnotes-id')

</script>

<template>

   <DocumentPreview :route-id="route.params.id" documentType='Bon de production' :fetchFunction="archiveStore.isArchive ? fetchArchivedProductionNotesById :  fetchProductionNotesById"
    :generatePdfFunction="GenerateProductionNotePdfById" :generateDocument="GenerateProductionNoteById"  >
  </DocumentPreview>
</template>
