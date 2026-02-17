<script setup lang="ts">
import { ref, watch } from 'vue';
import { useDocumentCoreStore } from '@/stores/documents';
import { fetchStaticData } from '@/services/api/invoicecredits';
import type { DocumentItems, OrderReceipt } from '@/services/models';
import { documentCoreDefaults, getDefaultOrderReceipt } from '@services/defaults';

const store = useDocumentCoreStore();
const emit = defineEmits(['close', 'submit', 'save']);
const orderreceiptFormDefaults = ref<OrderReceipt>(store.selectedDocumentCore || getDefaultOrderReceipt());
const documentsStore = useDocumentCoreStore();
const documentsFormDefaults = ref<DocumentCore>(documentsStore.selectedDocumentCore || documentCoreDefaults());
const orderItems = ref<number | null>(null);
const orderreceiptData = ref<OrderReceipt>(getDefaultOrderReceipt())
const isTaxable = ref(0);
const shouldSubmit = ref(false);



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

watch(() => store.selectedDocumentCore, (newOrderreceipt) => {
  if (store.mode === 'edit' && newOrderreceipt) {
    orderreceiptFormDefaults.value = { ...newOrderreceipt };
  }
}, { immediate: true });

onMounted(async () => {
  if (store.mode === 'add') {
    orderreceiptFormDefaults.value = getDefaultOrderReceipt();
  } else if (store.mode === 'edit' && store.selectedDocumentCore) {
    orderreceiptFormDefaults.value = { ...store.selectedDocumentCore };

  }
  loadStaticData();
});


const addProduct = (value: DocumentItems) => {
  if (orderreceiptData.value?.documentsItems && store.mode === 'add') {
    value.order = (orderreceiptData.value.documentsItems.length > 0 ? Math.max(...orderreceiptData.value.documentsItems.map(item => item.order ?? 0)) : 0) + 1;
    orderItems.value = value.order
    orderreceiptData.value.documentsItems.push(value);
  }
  else if (store.mode === 'edit' && store.selectedDocumentCore.items.length > 0) {
    value.order = (store.selectedDocumentCore.items.length > 0 ? Math.max(...store.selectedDocumentCore.items.map((item: any) => item.order ?? 0)) : 0) + 1;
    orderItems.value = value.order
    orderreceiptData.value.documentsItems.push(value);
  }
};

const removeProduct = (id: number) => {
  orderreceiptData.value?.documentsItems.splice(id, 1)
}

const submitOrdernote = () => {
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
        Reçu de commande
      </h4>
    </div>
    <div>
    </div>
  </div>

  <VRow>
    <!-- 👉 InvoiceEditable -->
    <VCol cols="12" md="9">
      <DocumentsEditable documentType='Reçu de commande'  :data="orderreceiptData" @push="addProduct" @remove="removeProduct"
        :triggerSubmit="shouldSubmit" @submit="handleSubmission" :isTaxable="isTaxable" :order="orderItems"
        @resetTriggerSubmit="resetTriggerSubmit" />
    </VCol>

    <!-- 👉 Right Column: Invoice Action -->
    <VCol cols="12" md="3">
      <VCard class="mb-8">
        <VCardText>
          <!-- 👉 Save -->
          <VBtn block color="primary" class="mb-4" @click="submitOrdernote">
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
