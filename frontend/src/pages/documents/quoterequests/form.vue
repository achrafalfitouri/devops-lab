<script setup lang="ts">
import { fetchStaticData } from '@/services/api/quote';
import type { DocumentItems, QuoteRequests } from '@/services/models';
import { useDocumentCoreStore } from '@/stores/documents';
import { documentCoreDefaults, getDefaultQuoteRequests } from '@services/defaults';
import { ref, watch } from 'vue';

const store = useDocumentCoreStore();
const emit = defineEmits(['close', 'submit', 'save']);
const quoteFormDefaults = ref<QuoteRequests>(store.selectedDocumentCore || getDefaultQuoteRequests());
const documentsStore = useDocumentCoreStore();
const documentsFormDefaults = ref<DocumentCore>(documentsStore.selectedDocumentCore || documentCoreDefaults());
const quoteData = ref<QuoteRequests>(getDefaultQuoteRequests())
const orderItems = ref<number | null>(null);
const shouldSubmit = ref(false);
const isTaxable = ref(0);

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
}

// 👉 Explicitly define the type for filterItems
const filterItems = ref<{
  users: FilterItem[];
  clients: FilterItem[];
  date: FilterItem[];
}>({
  users: [],
  clients: [],
  date: [],
});


// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();

    filterItems.value.users = mapStaticData(staticData.data.users);
    filterItems.value.clients = mapStaticData(staticData.data.clients);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

watch(() => store.selectedDocumentCore, (newQuote) => {
  if (store.mode === 'edit' && newQuote) {
    quoteFormDefaults.value = { ...newQuote };
  }
}, { immediate: true });

onMounted(async () => {
  if (store.mode === 'add') {
    quoteFormDefaults.value = getDefaultQuoteRequests();
  } else if (store.mode === 'edit' && store.selectedDocumentCore) {
    quoteFormDefaults.value = { ...store.selectedDocumentCore };

  }
  loadStaticData();
});

const addProduct = (value: DocumentItems) => {
  if (quoteData.value?.documentsItems && store.mode === 'add') {
    value.order = (quoteData.value.documentsItems.length > 0 ? Math.max(...quoteData.value.documentsItems.map(item => item.order ?? 0)) : 0) + 1;
    orderItems.value = value.order

    quoteData.value.documentsItems.push(value);
  }
  else if (store.mode === 'edit' && store.selectedDocumentCore.items.length > 0) {
    value.order = (store.selectedDocumentCore.items.length > 0 ? Math.max(...store.selectedDocumentCore.items.map((item: any) => item.order ?? 0)) : 0) + 1;
    orderItems.value = value.order
    quoteData.value.documentsItems.push(value);
  }
};

const submitQuote = () => {
  isTaxable.value = documentsFormDefaults.value.isTaxable;
  shouldSubmit.value = true;

};

const resetTriggerSubmit = () => {
  shouldSubmit.value = false;
};

const handleSubmission = () => {
  shouldSubmit.value = false;
};

const router = useRouter();

const GoBack = () => {
  router.back();
};

</script>

<template>
  <!-- 👉 Header  -->
  <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
    <div>
      <h4 class="text-h4 mb-1">
        Demande de devis
      </h4>
    </div>
  </div>
  <VRow>
    <!-- 👉 InvoiceEditable -->
    <VCol cols="12" md="9">
      <DocumentsEditable documentType='Demande de devis' :data="quoteData" @push="addProduct" :triggerSubmit="shouldSubmit"
        :isTaxable="isTaxable" @submit="handleSubmission" :order="orderItems"
        @resetTriggerSubmit="resetTriggerSubmit" />
    </VCol>

    <!-- 👉 Right Column: Invoice Action -->
    <VCol cols="12" md="3">
      <VCard class="mb-8">
        <VCardText>
          <!-- 👉 Save -->
          <VBtn block color="primary" class="mb-4" @click="submitQuote">
            Enregistrer
          </VBtn>
          <VBtn block variant="tonal" color="secondary" @click="GoBack">
            Annuler
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>


</template>
<style scoped>
.rounded-circle {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  top: 0;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  left: 0;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  width: 100%;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  height: 100%;
  background-color: white;
  /* stylelint-disable-next-line order/properties-order */
  border: 2px dashed #ddd;
  border-radius: 50%;
}
</style>
