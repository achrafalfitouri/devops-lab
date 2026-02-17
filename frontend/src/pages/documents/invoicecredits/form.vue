<script setup lang="ts">
import { ref, watch } from 'vue';
import { useDocumentCoreStore } from '@/stores/documents';
import { fetchStaticData } from '@/services/api/ordernote';
import type { DocumentItems, InvoiceCredits } from '@/services/models';
import { documentCoreDefaults, getDefaultInvoiceCredits } from '@services/defaults';

const store = useDocumentCoreStore();
const emit = defineEmits(['close', 'submit', 'save']);
const creditsFormDefaults = ref<InvoiceCredits>(store.selectedDocumentCore || getDefaultInvoiceCredits());
const documentsStore = useDocumentCoreStore();
const documentsFormDefaults = ref<DocumentCore>(documentsStore.selectedDocumentCore || documentCoreDefaults());
const shouldSubmit = ref(false);
const isTaxable = ref(0);
const creditData = ref<InvoiceCredits>(getDefaultInvoiceCredits())
const orderItems = ref<number | null>(null);

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

watch(() => store.selectedDocumentCore, (newCredit) => {
  if (store.mode === 'edit' && newCredit) {
    creditsFormDefaults.value = { ...newCredit };
  }
}, { immediate: true });

onMounted(async () => {
  if (store.mode === 'add') {
    creditsFormDefaults.value = getDefaultInvoiceCredits();
  } else if (store.mode === 'edit' && store.selectedDocumentCore) {
    creditsFormDefaults.value = { ...store.selectedDocumentCore };

  }
  loadStaticData();
});

const addProduct = (value: DocumentItems) => {
  if (creditData.value?.documentsItems && store.mode === 'add') {
    value.order = (creditData.value.documentsItems.length > 0 ? Math.max(...creditData.value.documentsItems.map(item => item.order ?? 0)) : 0) + 1;
    orderItems.value = value.order
    creditData.value.documentsItems.push(value);
  }
  else if (store.mode === 'edit' && store.selectedDocumentCore.items.length > 0) {
    value.order = (store.selectedDocumentCore.items.length > 0 ? Math.max(...store.selectedDocumentCore.items.map((item:any) => item.order ?? 0)) : 0) + 1;
    orderItems.value = value.order
    creditData.value.documentsItems.push(value);
  }
};

const removeProduct = (id: number) => {
  creditData.value?.documentsItems.splice(id, 1)
}

const submitCredit = () => {
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
        Facture Avoir
      </h4>
    </div>
    <div>
    </div>
  </div>
  <VRow>
    <!-- 👉 InvoiceEditable -->
    <VCol cols="12" md="9">
      <DocumentsEditable documentType='Facture avoir' :data="creditData" @push="addProduct" @remove="removeProduct" :triggerSubmit="shouldSubmit"
        @submit="handleSubmission" :isTaxable="isTaxable" :order="orderItems"
        @resetTriggerSubmit="resetTriggerSubmit" />
    </VCol>

    <!-- 👉 Right Column: Invoice Action -->
    <VCol cols="12" md="3">
      <VCard class="mb-8">
        <VCardText>
          <!-- 👉 Save -->
          <VBtn block color="primary" class="mb-4" @click="submitCredit">
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
