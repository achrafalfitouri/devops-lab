<script setup lang="ts">
import { fetchOrderNotesById, GenerateOrderNoteById, GenerateOrdernotePdfById,fetchArchivedOrderNotesById } from '@/services/api/ordernote';
import { useArchiveStoreStore } from '@/stores/archive';


const archiveStore = useArchiveStoreStore();

// Components
const route = useRoute('documents-ordernotes-id')

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Bon de commande" :fetchFunction="archiveStore.isArchive ? fetchArchivedOrderNotesById : fetchOrderNotesById"
    :generatePdfFunction="GenerateOrdernotePdfById" :generateDocument="GenerateOrderNoteById">
  </DocumentPreview>
</template>
