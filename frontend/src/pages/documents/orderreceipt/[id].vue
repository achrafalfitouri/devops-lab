<script setup lang="ts">
import { fetchOrderReceiptById, GenerateOrderReceiptById, GenerateOrderRecieptPdfById,fetchArchivedOrderReceiptById } from '@/services/api/orderrec';

import { useArchiveStoreStore } from '@/stores/archive';

const archiveStore = useArchiveStoreStore();


// Components
const route = useRoute('documents-orderreceipt-id')

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Reçu de commande" :fetchFunction="archiveStore.isArchive ? fetchArchivedOrderReceiptById : fetchOrderReceiptById"
    :generatePdfFunction="GenerateOrderRecieptPdfById" :generateDocument="GenerateOrderReceiptById">
  </DocumentPreview>
</template>
