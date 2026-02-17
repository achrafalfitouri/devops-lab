<script setup lang="ts">
import { ref } from 'vue';
import type { OrderNotes } from '@services/models';
import { fetchOrderNotes } from '@services/api/ordernote';
import { useDocumentCoreStore } from '@/stores/documents';

// 👉 State variable for storing order notes data
const OrderNotesData = ref<{
  OrderNotes: OrderNotes[]
  totalOrderNotes: number
}>({
  OrderNotes: [],
  totalOrderNotes: 0,
})
const route = useRoute('clients-id')

const isLoading = ref(true)

// 👉 Data table options
const sortBy = ref<string | undefined>(undefined)
const orderBy = ref<string | undefined>(undefined)

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// 👉 Table headers definition
const headers = [
  { title: 'N° Doc', key: 'code' },
  { title: 'Client', key: 'client' },
  { title: 'HT', key: 'amount' },
  { title: 'TVA', key: 'isTaxable' },
  { title: 'TTC', key: 'finalAmount' },
  { title: 'Statut', key: 'status' },
]

// 👉 Store call
const store = useDocumentCoreStore();

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Function to fetch order notes from the API
const loadOrderNotes = async () => {
  isLoading.value = true; 
  try {
    const id = route.params.id
    const filters: any = { client: id };

    const data = await fetchOrderNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    
    // Fix: Access the correct path in the response
    OrderNotesData.value.OrderNotes = data.orderNotes?.data || [];
    OrderNotesData.value.totalOrderNotes = data.totalOrderNotes  || 0;
    
    
    if (OrderNotesData.value.OrderNotes.length === 0 && queryParams.value.page > 1) {
      queryParams.value.page--;
      await loadOrderNotes();
    }

  } catch (error) {
    console.error('Error fetching order notes:', error);
    OrderNotesData.value.OrderNotes = [];
    OrderNotesData.value.totalOrderNotes = 0;
  } finally {
    isLoading.value = false;
  }
};

// 👉 Call loadOrderNotes on component setup
onMounted(() => {
  loadOrderNotes()
});

const openView = (note: OrderNotes) => {
  store.setEditMode(note);
  store.setPreviewMode();
}
</script>

<template>
  <VCard>
    <VCardText>
      <div class="d-flex justify-space-between flex-wrap align-center gap-4">
        <h5 class="text-h5">
          Bons de commande
        </h5>
      </div>
    </VCardText>

    <VDivider />
    <VDataTable 
      :headers="headers" 
      :items="OrderNotesData.OrderNotes" 
      item-value="id" 
      class="text-no-wrap"
      @update:options="updateOptions" 
      :loading="isLoading"
    >
      <template #loading>
        <div class="d-flex justify-center align-center loading-container">
          <VProgressCircular indeterminate color="primary" />
        </div>
      </template>
      
      <template #item.client="{ item }">
        <span>
          {{ item.client?.legalName }}
        </span>
      </template>
      
      <template #item.code="{ item }">
        <RouterLink 
          :to="{ name: 'documents-ordernotes-id', params: { id: item.id } }"
          class="text-link font-weight-medium d-inline-block" 
          style="line-height: 1.375rem;" 
          @click="openView(item)"
        >
          {{ item.code }}
        </RouterLink>
      </template>

      <template #item.isTaxable="{ item }">
        <span v-if="item.isTaxable">
          {{ item.taxAmount || '0.00' }}
        </span>
        <span v-else>-</span>
      </template>

      <template #item.finalAmount="{ item }">
        <span v-if="item.isTaxable">
          {{ item.finalAmount || '0.00' }}
        </span>
        <span v-else>-</span>
      </template>

      <template #bottom></template>
    </VDataTable>
  </VCard>
</template>

<style>
.v-data-table-progress {
  height: 0;
  visibility: hidden;
  overflow: hidden;
}
</style>