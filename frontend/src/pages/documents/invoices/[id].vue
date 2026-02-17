<script setup lang="ts">
import { fetchInvoicesById, GenerateInvoiceById, GenerateInvoicePdfById,fetchArchivedInvoicesById } from '@/services/api/invoice';
import { useArchiveStoreStore } from '@/stores/archive';


const archiveStore = useArchiveStoreStore();


// Components
const route = useRoute('documents-invoices-id')

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Facture" :fetchFunction="archiveStore.isArchive ? fetchArchivedInvoicesById  :  fetchInvoicesById"
    :generatePdfFunction="GenerateInvoicePdfById" :generateDocument="GenerateInvoiceById">

  </DocumentPreview>


</template>
