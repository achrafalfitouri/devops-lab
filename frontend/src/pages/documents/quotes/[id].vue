<script setup lang="ts">
import { fetchQuotesById, GenerateQuoteById, GenerateQuotePdfById,fetchArchivedQuotesById } from '@/services/api/quote';
import DocumentPreview from '@/components/documents/DocumentPreview.vue';
import { useArchiveStoreStore } from '@/stores/archive';


const archiveStore = useArchiveStoreStore();

// Components
const route = useRoute('documents-quotes-id')

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Devis" :fetchFunction="archiveStore.isArchive ? fetchArchivedQuotesById :fetchQuotesById"
    :generatePdfFunction="GenerateQuotePdfById" :generateDocument="GenerateQuoteById" >
 
  </DocumentPreview>
</template>


