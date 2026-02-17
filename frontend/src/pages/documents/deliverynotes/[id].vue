<script setup lang="ts">
import { fetchDeliveryNotesById, GenerateDeliveryNoteById, GenerateDeliveryNotePdfById ,fetchArchivedDeliveryNotesById} from '@/services/api/deliverynote';
import { useArchiveStoreStore } from '@/stores/archive';


// Components
const route = useRoute('documents-deliverynotes-id')
const archiveStore = useArchiveStoreStore();

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Bon de livraison" :fetchFunction="archiveStore.isArchive ? fetchArchivedDeliveryNotesById :  fetchDeliveryNotesById"
    :generatePdfFunction="GenerateDeliveryNotePdfById" :generateDocument="GenerateDeliveryNoteById">

  </DocumentPreview>
</template>
