<script setup lang="ts">
import { fetchOutputNotesById, GenerateOutputNoteById, GenerateOutputNotePdfById,fetchArchivedOutputNotesById } from '@/services/api/outputnote';
import { useArchiveStoreStore } from '@/stores/archive';

// Components
const archiveStore = useArchiveStoreStore();

const route = useRoute('documents-outputnotes-id')

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Bon de sortie" :fetchFunction="archiveStore.isArchive ? fetchArchivedOutputNotesById : fetchOutputNotesById"
    :generatePdfFunction="GenerateOutputNotePdfById" :generateDocument="GenerateOutputNoteById" > >
  </DocumentPreview>
</template>


