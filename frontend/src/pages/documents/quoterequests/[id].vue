<script setup lang="ts">
import { fetchQuoteRequestsById, GenerateQuoteById, GenerateQuotePdfById,fetchArchivedQuoteRequestsById } from '@/services/api/quoterequest';
import DocumentPreview from '@/components/documents/DocumentPreview.vue';
import { useArchiveStoreStore } from '@/stores/archive';


const archiveStore = useArchiveStoreStore();

// Components
const route = useRoute('documents-quoterequests-id')

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Demande de devis" :fetchFunction="archiveStore.isArchive ? fetchArchivedQuoteRequestsById :fetchQuoteRequestsById"
    :generatePdfFunction="GenerateQuotePdfById" :generateDocument="GenerateQuoteById" >
 
  </DocumentPreview>
</template>


