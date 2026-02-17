<script setup lang="ts">
import { ref } from 'vue';
import type { Quotes } from '@services/models';
import { fetchQuotes } from '@services/api/quote';
import { useDocumentCoreStore } from '@/stores/documents';


// 👉 State variable for storing quotes data
const quotesData = ref<{
  quotes: Quotes[]
  totalquotes: number
}>({
  quotes: [],
  totalquotes: 0,
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
// 👉 Function to fetch quotes from the API
const loadquotes = async () => {
  isLoading.value = true; 
  try {
    const id = route.params.id

    const filters: any = {};

    filters.client = id;


    const data = await fetchQuotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    quotesData.value.quotes = data.quotes.data || [];
    quotesData.value.totalquotes = data.totalQuotes || 0;
    if (quotesData.value.quotes.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadquotes();
    }

  } catch (error) {
    console.error('Error fetching quotes:', error);
  } finally {
    isLoading.value = false;
  }
};

// 👉 Call loadQuotes on component setup
onMounted(() => {
  loadquotes()
});

const openView = (quote: Quotes) => {
  store.setEditMode(quote);
  store.setPreviewMode();
}

</script>

<template>
  <VCard>
    <VCardText>
      <div class="d-flex justify-space-between flex-wrap align-center gap-4">
        <h5 class="text-h5">
          Devis
        </h5>
      </div>
      <div>
      </div>
    </VCardText>

    <VDivider />
    <VDataTable :headers="headers" :items="quotesData.quotes" item-value="id" class="text-no-wrap"
      @update:options="updateOptions" :loading="isLoading">
      <!-- Custom circular loading indicator -->
      <template #loading>

        <div class="d-flex justify-center align-center loading-container">
          <VProgressCircular indeterminate color="primary" />
        </div>
      </template>
      

      <template #item.client="{ item }">
        <span>
          {{ item.client.legalName }}
        </span>
      </template>

      <template #item.code="{ item }">

        <RouterLink :to="{ name: 'documents-quotes-id', params: { id: item.id } }"
          class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;" @click="openView(item)">
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
      <!-- Actions -->

      <template #bottom></template>


    </VDataTable>
  </VCard>
</template>
<style>
/* Global styles */
.v-data-table-progress {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  height: 0;
  visibility: hidden;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  /* stylelint-disable-next-line order/properties-order */
  overflow: hidden;
}

/* Add more global styles as needed */
</style>
