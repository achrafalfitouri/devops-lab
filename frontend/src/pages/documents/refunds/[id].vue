<script setup lang="ts">
import { fetchRefundsById, GenerateRefundsPdfById , fetchArchivedRefundsById } from '@/services/api/refund';
import { useArchiveStoreStore } from '@/stores/archive';

const archiveStore = useArchiveStoreStore();

// Components
const route = useRoute('documents-refunds-id');

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Bon de remboursement" :fetchFunction="archiveStore.isArchive ? fetchArchivedRefundsById : fetchRefundsById"
    :generatePdfFunction="GenerateRefundsPdfById">

  </DocumentPreview>
</template>
