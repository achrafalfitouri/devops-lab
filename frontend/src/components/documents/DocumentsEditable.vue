<script setup lang="ts">
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue';
import { RouteKey } from '@/composables/DocumentUtil';
import { router } from '@/plugins/1.router';
import { fetchClientById } from '@/services/api/client';
import { fetchStaticData } from '@/services/api/quote';
import type { DeliveryNotes, DocumentItems, InvoiceCredits, Invoices, OrderNotes, OrderReceipt, OutputNotes, ProductionNotes, QuoteRequests, Quotes, Refunds, ReturnNotes } from '@/services/models';
import { useDocumentCoreStore } from '@/stores/documents';
import { VNodeRenderer } from '@layouts/components/VNodeRenderer';
import { documentCoreDefaults, getDefaultDocumentItem, } from '@services/defaults';
import { themeConfig } from '@themeConfig';
import { isEqual } from 'lodash';
import { computed, ref, watch } from 'vue';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Emits definitions
const emit = defineEmits<{
  (e: 'close' | 'submit', ...args: any[]): void;
  (e: 'push', value: DocumentItems): void;
  (e: 'remove', id: number): void;
  (e: 'resetTriggerSubmit'): void;

}>();

// 👉 Props Interface
interface Props {
  documentType?: 'Demande de devis' | 'Devis' | 'Bon de production' | 'Bon de commande' | 'Bon de livraison' | 'Bon de sortie' | 'Bon de retour' | 'Facture' | 'Reçu de commande' | 'Facture avoir' | 'Bon de remboursement',
  data?: QuoteRequests | Quotes | ProductionNotes | OrderNotes | DeliveryNotes | OutputNotes | ReturnNotes | Invoices | InvoiceCredits | OrderReceipt | Refunds,
  triggerSubmit: boolean; isTaxable: any, order: number | null
}

// 👉 Props definition
const props = defineProps<Props>()

// 👉 Filter Interface
interface FilterItem {
  text: string;
  value: string;
}

// 👉 Filter Items 
const filterItems = ref<{
  users: FilterItem[];
  clients: FilterItem[];
  date: FilterItem[];
}>({
  users: [],
  clients: [],
  date: [],
});

// 👉 Definitions
const documentsStore = useDocumentCoreStore();
const documentsFormDefaults = ref<DocumentCore>(documentsStore.selectedDocumentCore || documentCoreDefaults());
const FetchedClientName = ref<string | null>(null);
const FetchedClientIce = ref<string | null>(null);
const selectedClient = computed(() => documentsFormDefaults.value.clientId);
const isNavigatingAway = ref(false);
const routerAlert = useRouter();
const hasUnsavedChanges = ref(false);
const isLeaveDialogVisible = ref(false);
const pendingRoute = ref<any>(null);
const route = useRoute();
const routeName = computed(() => route.name as RouteKey);
const ValidPrice = ref<boolean | null>(null);
const ValidQuantity = ref<boolean | null>(null);
const ValidDiscount = ref<boolean | null>(null);
const ValiDescription = ref<boolean | null>(null);
const min = ref<string | null>(null);
const initialDocumentData = ref(null)
const refundTypes = ['Espèce', 'Chèque', 'Virement']
const selectedRefundType = ref<string | null>(null)
// 👉 Loading state
const isLoading = ref(false);

// 👉 Required Validator
const requiredValidator = (v: any) => (v !== null && v !== undefined) || 'Ce champ est obligatoire';

// 👉 Documents Items Definition
const documentsItems = ref<DocumentItems[]>(
  documentsStore.mode === 'edit' && documentsStore.selectedDocumentCore
    ? documentsStore.selectedDocumentCore.items : []
);

function formatDateToYMD(date: any) {
  if (!date) return null;

  const dateObj = date instanceof Date ? date : new Date(date);

  const year = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const day = String(dateObj.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}
// 👉 Function to submit form
const handleFormSubmit = async () => {
  hasUnsavedChanges.value = false;
  isLoading.value = true;  // ADD THIS LINE


  try {

    const isDescriptionValid = documentsItems.value.every(item =>
      item.description != null && item.description !== ''
    );
    const isCharactersticsValid = documentsItems.value.every(item =>
      item.characteristics != null && item.characteristics !== ''
    );

    const isPriceValid = documentsItems.value.every(item =>
      item.price != null && item.price > 0
    );

    const isQuantityValid = documentsItems.value.every(item =>
      item.quantity != null && item.quantity >= 1
    );


    ValiDescription.value = isDescriptionValid
    ValidPrice.value = isPriceValid
    ValidQuantity.value = isQuantityValid

    if (!isDescriptionValid) {
      showSnackbar("Description est requise", "error");
      return;
    }
    // if (!isCharactersticsValid && props?.documentType === 'Demande de devis') {
    //   showSnackbar("Caractéristique est requise", "error");
    //   return;
    // }
    if (!isPriceValid && props?.documentType !== 'Demande de devis') {
      showSnackbar("Le prix doit être supérieur à 0", "error");
      return;
    }

    if (!isQuantityValid && props?.documentType !== 'Demande de devis') {
      showSnackbar("La quantité doit être au moins 1", "error");
      return;
    }

    const formMappings = createFormMappings(documentsFormDefaults, 'form');
    const currentMapping = formMappings[routeName.value];
    if (!currentMapping) {
      console.error("Unsupported routeName:", routeName.value);
      return;
    }
    let payload = { ...currentMapping.defaults.value };
    delete payload.userId;


    if (props.documentType === "Bon de production" && documentsStore.newProductionNote) {
      payload = {
        ...payload,
        // quote_id: documentsStore.quoteId,
        order_note_id: documentsStore.orderNoteId,
        // client_id: documentsStore.clientId,
      };
    } else {
      payload = {
        ...payload,
        client_id:
          documentsStore.selectedDocumentCore?.clientId === documentsFormDefaults.value.client
            ? documentsStore.selectedDocumentCore?.clientId
            : documentsFormDefaults.value.clientId,
      };
    }

    payload = {
      ...payload,
      items: documentsItems.value,
      is_taxable: documentsFormDefaults.value?.isTaxable ?? 0,
      amount: amount.value,
      code: documentsFormDefaults.value?.code,
      validity_date: formatDateToYMD(documentsFormDefaults.value?.validityDate),
    };
    if (documentsStore.mode === "add") {
      const response = await currentMapping.addAction(payload);
      const documentId = response[currentMapping.responseKey].id;
      documentsFormDefaults.value.code = response[currentMapping.responseKey].code;
      // updateStore(response, documentId);

      if (!isNavigatingAway.value) {
        const documentName = routeName.value.replace('documents-', '').replace('-form', '');
        router.replace({ path: `/documents/${documentName}/${documentId}` });
      }

      showSnackbar("Document créé avec succès", "success");
    } else if (documentsStore.mode === "edit") {
      const documentId = payload.id;
      const response = await currentMapping.updateAction(documentId, payload);
      // updateStore(response, documentId);

      if (!isNavigatingAway.value) {
        const documentName = routeName.value.replace('documents-', '').replace('-form', '');
        router.replace({ path: `/documents/${documentName}/${documentId}` });
      }

      showSnackbar("Document mis à jour avec succès", "success");
    }

    emit("submit");
  } catch (error) {
    const err = error as any;
    console.error("Error preparing document data:", err);
    showSnackbar(`${err.response?.data.message}`, 'error');
  } finally {
    isLoading.value = false;  // ADD THIS LINE
  }

};

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.users = mapStaticData(staticData.data?.users);
    filterItems.value.clients = mapStaticData(staticData.data?.clients);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};


// 👉 Resolve validity date and due date format with d/m/Y format
const getFormattedDate = (daysToAdd: number) => {
  const date = new Date();
  date.setDate(date.getDate() + daysToAdd);

  // Format as d/m/Y instead of ISO format
  const month = date.getMonth() + 1; // getMonth() returns 0-11
  const day = date.getDate();
  const year = date.getFullYear();

  return `${month}/${day}/${year}`;
};

// Also update the edit version to use the same format
const getFormattedDateEdit = (createdAt: string, daysToAdd: number) => {
  const date = new Date(createdAt);
  date.setDate(date.getDate() + daysToAdd);

  // Format as d/m/Y instead of ISO format
  const month = date.getMonth() + 1; // getMonth() returns 0-11
  const day = date.getDate();
  const year = date.getFullYear();

  return `${month}/${day}/${year}`;
};
const isMinReady = ref(false)

const setDefaults = (store: any, formDefaults: any, getDefaultData: Function, dateFields: string[] = []) => {
  if (documentsStore.mode === "add") {
    formDefaults.value = getDefaultData();
    dateFields.forEach((field) => {
      min.value = formDefaults.value[field] = getFormattedDate(15);
    });
    isMinReady.value = true;
  } else if (documentsStore.mode === "edit") {
    formDefaults.value = { ...documentsStore.selectedDocumentCore };
    if (documentsStore.selectedDocumentCore?.validityDate) {
      min.value = getFormattedDateEdit(documentsStore.selectedDocumentCore?.validityDate, 0);
      documentsFormDefaults.value.validityDate = min.value;
      isMinReady.value = true;
    }
  }

};



onMounted(() => {
  if (props.documentType === 'Devis') {
    const dateFields = ["validityDate", "dueDate"];
    setDefaults(documentsStore, documentsFormDefaults, documentCoreDefaults, dateFields);
  }

  documentsFormDefaults.value.createdAt = new Date().toISOString();
  loadStaticData();

  if (documentsItems.value.length > 0) {
    const maxIndex = Math.max(...documentsItems.value.map(item => item.index ?? -1));
    nextItemIndex.value = maxIndex + 1;
  }

  if (documentsStore.mode === "edit") {
    initialDocumentData.value = JSON.parse(JSON.stringify(documentsFormDefaults.value));
  }
  if ((props.documentType === 'Devis' || props.documentType === 'Demande de devis') && documentsStore.mode === 'add') {
    documentsFormDefaults.value.isTaxable = 1;
  }
});


const datePickerConfig = computed(() => {
  if (!isMinReady.value) return null;
  return {
    dateFormat: 'd/m/Y',
    minDate: min.value,


  };
});

// 👉 Add item function
const nextItemIndex = ref(0);

const addItem = () => {
  if (props.documentType === "Bon de production" && documentsItems.value.length >= 1) {
    return;
  }
  documentsStore.setShouldVerify(false);

  const newItem = {
    ...getDefaultDocumentItem(),
    index: nextItemIndex.value++
  };

  documentsItems.value.push(newItem);
  emit('push', newItem);
};

const removeProduct = (id: any) => {
  documentsItems.value.forEach((item, i) => {
  });

  if (documentsStore.mode === 'edit') {
    const itemToRemove = documentsItems.value.find(item => item.id === id);
    if (itemToRemove) {
      const indexToRemove = documentsItems.value.indexOf(itemToRemove);
      documentsItems.value.splice(indexToRemove, 1);
      emit('remove', id);
    } else {
      console.warn('Item with given uuid/id not found.');
    }
  }
  else {
    const indexToRemove = documentsItems.value.findIndex(item => item.index === id);
    if (indexToRemove !== -1) {
      documentsItems.value.splice(indexToRemove, 1);
      emit('remove', id);
    } else {
      console.warn('Item with given index not found.');
    }
  }
}

// 👉 Function that handles paylod coming from documentsitems
const handleProductDataUpdate = (payload: DocumentItems, index: number) => {
  const existingOrder = documentsItems.value[index]?.order;
  const newItem = {
    ...payload,
    order: existingOrder ?? ((documentsItems.value.length > 0)
      ? Math.max(...documentsItems.value.map(item => item.order ?? 0)) + 1
      : 1)
  };
  if (documentsItems.value[index]) {
    documentsItems.value.splice(index, 1, newItem);
  } else {
    documentsItems.value = [...documentsItems.value, newItem];
  }

};

// 👉 Amount calculation
const amount = computed(() => {
  const totalAmount = documentsItems.value.reduce((total, item) => {
    const price = item.price ?? 0;
    const quantity = item.quantity ?? 0;
    const discount = item.discount ?? 0;

    const discountedTotal = (price * quantity) - discount;
    return total + discountedTotal;
  }, 0);

  return parseFloat(totalAmount.toFixed(2));
});

// 👉 Tax calculation
const tax_amount = computed(() => {
  if (documentsFormDefaults.value?.isTaxable && [
    'documents-quoterequests-form',
    'documents-quotes-form',
    'documents-ordernotes-form',
    'documents-productionnotes-form',
    'documents-outputnotes-form',
    'documents-deliverynotes-form',
    'documents-returnnotes-form',
    'documents-invoices-form',
    'documents-orderreceipt-form',
    'documents-invoicecredits-form',
    'documents-refunds-form',

  ].includes(routeName.value)) {
    const tax = amount.value * 0.2;
    return parseFloat(tax.toFixed(2));
  }
  return 0;
});

// 👉 Final amount calculation
const final_amount = computed(() => {
  const final = amount.value + tax_amount.value;
  return parseFloat(final.toFixed(2));
});

// 👉 Fetch client data
const getClients = async (clientId: string) => {
  try {

    const response = await fetchClientById(clientId);
    const clientData = response;
    FetchedClientName.value = clientData?.legalName;
    FetchedClientIce.value = clientData?.ice;
  } catch (error) {
    console.error('Error fetching client data:', error);
  }
};




// 👉 Logic to handle Unsaved data when navigation is triggerd
const confirmNavigation = () => {
  if (pendingRoute.value) {
    isNavigatingAway.value = true;
    handleFormSubmit();
    emit("resetTriggerSubmit");

    hasUnsavedChanges.value = false;
    pendingRoute.value.next();
    pendingRoute.value = null;
  }
  isLeaveDialogVisible.value = false;
};

const cancelNavigation = () => {
  hasUnsavedChanges.value = false;
  pendingRoute.value.next();
  pendingRoute.value = null;
  isLeaveDialogVisible.value = false;

};

routerAlert.beforeEach((to, from, next) => {
  if (hasUnsavedChanges.value && documentsStore.mode === "edit") {

    isLeaveDialogVisible.value = true;
    pendingRoute.value = { to, next };
  } else {
    next();
  }
});

const confirmNavigationTab = (event: Event) => {
  if (hasUnsavedChanges.value && documentsStore.mode === "edit") {
    event.preventDefault();
    event.returnValue = true;
  }
};

window.addEventListener("beforeunload", confirmNavigationTab);

onBeforeUnmount(() => {
  window.removeEventListener("beforeunload", confirmNavigationTab);
});


watch(() => documentsStore.selectedDocumentCore, (newDocument) => {

  if (documentsStore.mode === 'edit' && newDocument) {
    documentsFormDefaults.value = { ...newDocument };


  }
}, { immediate: true });

watch(selectedClient, async (newClient, oldClient) => {
  if (newClient && newClient !== oldClient) {
    await getClients(newClient);
  }
});

watch(() => props.triggerSubmit, (newValue) => {
  if (newValue) {
    documentsStore.setShouldVerify(true);
    handleFormSubmit();
    emit("resetTriggerSubmit");
  }
});

watch(
  () => documentsFormDefaults.value,
  (newVal) => {
    if (documentsStore.mode === "edit" && initialDocumentData.value) {
      if (!isEqual(newVal, initialDocumentData.value)) {
        hasUnsavedChanges.value = true;
      } else {
        hasUnsavedChanges.value = false;
      }
    } else {
      hasUnsavedChanges.value = false;
    }
  },
  { deep: true }
);

// ⚙️ Handle Client Select Update
const handleClientUpdate = (event: any) => {
  if (!event) {
    FetchedClientName.value = null;
    FetchedClientIce.value = null;
    if (documentsStore.mode === 'edit') {
      documentsFormDefaults.value.client = { id: '', legalName: '', ice: '', balance: null };
      documentsFormDefaults.value.clientId = '';
    } else {
      documentsFormDefaults.value.clientSelect = '';
      documentsFormDefaults.value.ice = '';
      documentsFormDefaults.value.clientId = '';
    }
    return;
  }

  if (documentsStore.mode === 'add') {
    documentsFormDefaults.value.clientSelect = event.text;
    documentsFormDefaults.value.ice = event.ice;
    documentsFormDefaults.value.clientId = event.value;


  } else {
    if (!documentsFormDefaults.value.client) {
      documentsFormDefaults.value.client = {};
    }
    documentsFormDefaults.value.client.legalName = event.text;
    documentsFormDefaults.value.client.ice = event.ice;
    documentsFormDefaults.value.client.id = event.value;
    documentsFormDefaults.value.clientId = event.value;
  }
};


// 👉 Resolve client name 
const clientName = computed(() => {
  if (documentsStore.mode === 'edit') {
    if (documentsStore.selectedDocumentCore?.client.id === documentsFormDefaults.value?.clientId) {
      return documentsStore.selectedDocumentCore?.client?.legalName || '';
    }
    else {
      return documentsFormDefaults.value.client?.legalName
    }
  }
  return FetchedClientName.value || documentsFormDefaults.value.client?.legalName || '';
});

// 👉 Resolve Client ice
const clientIce = computed(() => {
  if (documentsStore.mode === 'edit') {
    if (documentsStore.selectedDocumentCore?.client?.ice === documentsFormDefaults.value.client?.ice) {
      return documentsStore.selectedDocumentCore?.client?.ice || '';
    }
    else {
      return documentsFormDefaults.value?.ice
    }
  }
  return FetchedClientIce.value || documentsFormDefaults.value?.ice || '';
});

const clientModel = computed({
  get() {
    if (documentsStore.mode === 'edit') {
      return documentsFormDefaults.value.client?.id ?
        {
          value: documentsFormDefaults.value.client.id,
          text: documentsFormDefaults.value.client.legalName,
          ice: documentsFormDefaults.value.client.ice
        } : null;
    } else {
      return documentsFormDefaults.value.clientId ?
        {
          value: documentsFormDefaults.value.clientId,
          text: documentsFormDefaults.value.clientSelect,
          ice: documentsFormDefaults.value.ice
        } : null;
    }
  },
  set(value) {
  }
});


// 👉 Resolve CreatedAt
const formattedCreatedAt = computed(() => {
  return formatDateDDMMYYYY(documentsFormDefaults.value?.createdAt);
});

const formatedValidityDate = computed(() => {
  if (documentsFormDefaults.value?.validityDate) {
    return formatDateDDMMYYYY(documentsFormDefaults.value?.validityDate);
  }
  return null;
});

watchEffect(() => {
  if (!documentsFormDefaults.value.validityDate) {
    documentsFormDefaults.value.validityDate = null
  }
});

const getClientProps = (item: any) => ({
  value: item,
  title: item.text,
  disabled: item.status !== 'Actif',
})


</script>

<template>
  <!-- Loading Overlay -->
  <VOverlay v-model="isLoading" persistent class="align-center justify-center">
    <VProgressCircular indeterminate color="primary" size="50" />
  </VOverlay>
  <VCard class="pa-6 pa-sm-12">
    <!-- SECTION Header -->
    <div
      class="d-flex flex-wrap justify-space-between flex-column rounded bg-var-theme-background flex-sm-row gap-6 pa-6 mb-6">
      <!-- 👉 Left Content -->
      <div>
        <div class="d-flex align-center app-logo mb-6">
          <!-- 👉 Logo -->
          <VNodeRenderer :nodes="themeConfig.app.logo" />

          <!-- 👉 Title -->
          <h6 class="app-logo-title">
            {{ themeConfig.app.title }}
          </h6>
        </div>
        <VAutocomplete v-if="routeName !== 'documents-productionnotes-form'" v-model="clientModel"
          @update:modelValue="handleClientUpdate($event)" :rules="[requiredValidator]" :items="filterItems.clients"
          label="Client *" item-title="text" clearable clear-icon="tabler-x" placeholder="Client" class="mb-4"
          style="inline-size: 11.875rem;" variant="outlined" :item-props="getClientProps" />
        <!-- 👉 Address -->
        <h6 v-if="documentsStore.mode === 'add'" class="text-h6 font-weight-regular">
          {{ FetchedClientName }}

        </h6>
        <h6 v-if="documentsStore.mode === 'add'" class="text-h6 font-weight-regular">
          {{ FetchedClientIce }}
        </h6>
        <h6 v-if="documentsStore.mode === 'edit'" class="text-h6 font-weight-regular">
          {{ clientName }}

        </h6>
        <h6 v-if="documentsStore.mode === 'edit'" class="text-h6 font-weight-regular">
          {{ clientIce }}
        </h6>
      </div>

      <!-- 👉 Right Content -->
      <div class="d-flex flex-column gap-2">
        <!-- 👉 Invoice Id -->
        <div class="d-flex flex-column gap-x-4 font-weight-medium text-lg">
          <span class="text-high-emphasis text-sm-end" style="inline-size: 5.625rem;"></span>
          <VTextField v-model="documentsFormDefaults.code" style="inline-size: 9.5rem;" variant="outlined" label="Code"
            readonly />
        </div>

        <!-- 👉 Création date -->
        <div class="d-flex flex-column gap-x-4">
          <span v-if="routeName === 'documents-quotes-form'" class="text-high-emphasis text-sm-end"
            style="inline-size: 5.625rem;"></span>
          <VTextField v-model="formattedCreatedAt" label="Date de création" placeholder="Date de création"
            variant="outlined" :readonly="true" style="inline-size: 9.5rem;" />
        </div>

        <!-- 👉 Expiration Date -->
        <div class="d-flex flex-column gap-x-4">
          <span v-if="routeName === 'documents-quotes-form'" class="text-high-emphasis text-sm-end"
            style="inline-size: 5.625rem;"></span>
          <AppDateTimePicker v-if="routeName === 'documents-quotes-form'" v-model="formatedValidityDate"
            label="Date d’expiration" placeholder="Date d’expiration" :config="datePickerConfig" variant="outlined"
            style="inline-size: 9.5rem;" />
        </div>

        <!-- 👉 Due Date -->
        <div class="d-flex flex-column gap-x-4">
          <span v-if="routeName === 'documents-invoices-form'" class="text-high-emphasis text-sm-end"
            style="inline-size: 5.625rem;"></span>
          <VTextField v-if="routeName === 'documents-invoices-form'" label="Due Date"
            v-model="documentsFormDefaults.dueDate" disabled placeholder="YYYY-MM-DD"
            :config="{ position: 'auto right' }" variant="outlined" style="inline-size: 9.5rem;" />
        </div>

        <!-- 👉 Due Date -->
        <div class="d-flex flex-column gap-x-4">
          <span v-if="routeName === 'documents-invoices-form'" class="text-high-emphasis text-sm-end"
            style="inline-size: 5.625rem;"></span>

          <VAutocomplete v-if="routeName === 'documents-refunds-form'" v-model="selectedRefundType" :items="refundTypes"
            variant="outlined" label="Type" placeholder="Type" class="mb-6" />
        </div>


      </div>

    </div>
    <!-- !SECTION -->
    <AlertDialog v-if="isLeaveDialogVisible" :isVisible="isLeaveDialogVisible"
      message="Vous avez des changements non sauvegardés. Voulez-vous les sauvegardés?" deleteButtonText="Oui"
      cancelButtonText="Non" deleteButtonColor="error" cancelButtonColor="secondary" :onDeleteClick="confirmNavigation"
      :onCancelClick="cancelNavigation" @update:isVisible="isLeaveDialogVisible = $event" />
    <VDivider class="my-6 border-dashed" />
    <!-- 👉 Add purchased products -->
    <div class="add-products-form">
      <!-- Fixed v-for directive syntax -->
      <div v-for="(product, index) in documentsItems"
        :key="documentsStore.mode === 'add' ? product?.index ?? index : product?.id ?? index" class="mb-4">
        <DocumentsItems :id="product?.id" :count="index" :index="product?.index" :data="product"
          @remove-product="removeProduct" @updateProductData="(payload: any) => handleProductDataUpdate(payload, index)"
          :order="props.order" :valid-description="ValiDescription" :valid-discount="ValidDiscount"
          :valid-price="ValidPrice" :valid-quantity="ValidQuantity" :document-type="props.documentType" />
      </div>


      <VBtn :disabled="props.documentType === 'Bon de production' && documentsItems.length >= 1"
        v-if="props.documentType !== 'Facture avoir'" size="small" prepend-icon="tabler-plus" @click="addItem">
        Ajouter un article </VBtn>
    </div>

    <VDivider class="my-6 border-dashed" />

    <div class="d-flex justify-space-between flex-column flex-sm-row print-row mr-8">
      <div class="mb-2" style="flex: 1; max-width: 50%;">
        <div class="mt-0 ">
          <h6 v-if="documentsFormDefaults?.isTaxable" class="text-h6 me-2">Total TTC en lettres
          </h6>
          <h6 v-else class="text-h6 me-2">Total HT en lettres:
          </h6>
          <span v-if="documentsStore.mode !== 'edit'" class="d-block" style="font-size: 14px; max-width: 100%;">{{
            documentsFormDefaults?.totalPhrase
          }}</span>
        </div>
      </div>

      <!-- 👉 Total Amount -->
      <div v-if="props.documentType !== 'Bon de production'"
        class="d-flex justify-space-between flex-wrap flex-column flex-sm-row">
        <div class="ms-auto">
          <table class="w-100">
            <tbody>
              <tr>
                <td class="pe-16">
                  Total HT
                </td>
                <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                  <h6 class="text-h6">
                    {{ amount }} DH
                  </h6>
                </td>
              </tr>
              <tr v-if="documentsFormDefaults?.isTaxable">
                <td class="pe-16">
                  Total TVA </td>
                <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                  <h6 class="text-h6">
                    {{ tax_amount }} DH
                  </h6>
                </td>
              </tr>
            </tbody>
          </table>

          <VDivider v-if="documentsFormDefaults?.isTaxable" class="mt-4 mb-3" />

          <table class="w-100">
            <tbody>
              <tr v-if="documentsFormDefaults?.isTaxable">
                <td class="pe-16">
                  Total TTC
                </td>
                <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                  <h6 class="text-h6">
                    {{ final_amount }} DH
                  </h6>
                </td>
              </tr>
              <tr>
                <td class="pe-16">
                  Avec TVA
                </td>
                <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                  <h6 class="text-h6">
                    <div>
                      <VSwitch :true-value="1" :false-value="0" v-model="documentsFormDefaults.isTaxable" />
                    </div>
                  </h6>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <VDivider class="my-6 border-dashed" />

    <div>
      <h6 class="text-h6 mb-2">
        Commentaire:
      </h6>
      <VTextarea v-if="routeName === 'documents-quotes-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.quoteComment" />
      <VTextarea v-if="routeName === 'documents-ordernotes-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.orderComment" />
      <VTextarea v-if="routeName === 'documents-returnnotes-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.returnComment" />
      <VTextarea v-if="routeName === 'documents-invoices-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.invoiceComment" />
      <VTextarea v-if="routeName === 'documents-productionnotes-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.productionComment" />
      <VTextarea v-if="routeName === 'documents-outputnotes-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.outputComment" />
      <VTextarea v-if="routeName === 'documents-deliverynotes-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.deliveryComment" />
      <VTextarea v-if="routeName === 'documents-orderreceipt-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.receiptComment" />
      <VTextarea v-if="routeName === 'documents-invoicecredits-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.creditComment" />
      <VTextarea v-if="routeName === 'documents-quoterequests-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.quoterequestComment" />
      <VTextarea v-if="routeName === 'documents-refunds-form'" placeholder="Write note here..." :rows="2"
        v-model="documentsFormDefaults.refundComment" />


    </div>
  </VCard>
</template>
