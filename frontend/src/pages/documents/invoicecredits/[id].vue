<script setup lang="ts">
import { fetchInvoiceCreditsById, GenerateInvoicecreditById, GenerateInvoiceCreditsPdfById ,fetchArchivedInvoiceCreditsById} from '@/services/api/invoicecredits';
import { useDocumentCoreStore } from '@/stores/documents';
import { useArchiveStoreStore } from '@/stores/archive';


// Components
const route = useRoute('documents-orderreceipt-id')
const router = useRouter();
const documentsStore = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();



// 👉 Opens the drawer for editing a invoicecredits.
const openEditinvoicecreditsDrawer = async () => {
  const id = route.params.id
  const data = await fetchInvoiceCreditsById(id)
  documentsStore.setEditMode(data);
  await router.push('/documents/invoicecredits/form');

}

</script>

<template>
  <DocumentPreview :route-id="route.params.id" documentType="Facture avoir" :fetchFunction="archiveStore.isArchive ? fetchArchivedInvoiceCreditsById :fetchInvoiceCreditsById"
    :generatePdfFunction="GenerateInvoiceCreditsPdfById"
    :generateDocument="GenerateInvoicecreditById"
    >

  </DocumentPreview>
</template>
