<script setup lang="ts">
import '@/components/documents/style.scss';
import { delliveryNoteStatus, invoiceCreditStatus, invoiceStatus, orderReceiptStatus, outputNoteStatus, productionNoteStatus, refundStatus, status, statusItems, tabs } from '@/composables/DocumentConst';
import { RouteKeyId } from '@/composables/DocumentUtil';
import { deleteDeliveryNotes, updateStatusDeliveryNotesItems } from '@/services/api/deliverynote';
import { deleteInvoices } from '@/services/api/invoice';
import { deleteInvoiceCredits } from '@/services/api/invoicecredits';
import { deleteOrderNotes } from '@/services/api/ordernote';
import { deleteOrderReceipt } from '@/services/api/orderrec';
import { deleteOutputNotes } from '@/services/api/outputnote';
import { fetchStaticData } from '@/services/api/payment';
import { deleteProductionNotes, DuplicateProductionNote } from '@/services/api/productionnote';
import { deleteQuotes, DuplicateQuote, generateDocumentNavigation } from '@/services/api/quote';
import { deleteQuoteRequests, DuplicateQuoteRequest } from '@/services/api/quoterequest';
import { deleteRefunds, updateTypeRefunds } from '@/services/api/refund';
import { deleteReturnNotes } from '@/services/api/returnnote';
import { Payment } from '@/services/models';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useDocumentCoreStore } from '@/stores/documents';
import { usePaymentStore } from '@/stores/payment';
import { documentCoreDefaults, getDefaultPayment, getDefaultProductionNotes } from '@services/defaults';
import { themeConfig } from '@themeConfig';
import html2canvas from "html2canvas";
import jsPDF from "jspdf";
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { VForm } from 'vuetify/lib/components/index.mjs';
import DocumentPreviewContent from './DocumentPreviewContent.vue';
import DocumentTable from './DocumentTable.vue';
import { printDocument } from '@/print';



// 👉 Props
const props = withDefaults(defineProps<{
  documentType: 'Demande de devis' | 'Devis' | 'Bon de production' | 'Bon de commande' | 'Bon de livraison' | 'Bon de sortie' | 'Bon de retour' | 'Facture' | 'Reçu de commande' | 'Facture avoir' | 'Bon de remboursement',
  routeId: any,
  fetchFunction: (id: string, filters?: any) => Promise<any>,
  generatePdfFunction?: (id: string) => Promise<{ status: number, downloadUrl: string }>,
  generateDocument?: (param: { ids: any[] } | string | number) => Promise<any>;
}>(), {
  generateDocument: async () => { },

});

// 👉 begin Definitions
const router = useRouter()
const documentData = ref<any | null>(null)
const documentsStore = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();
const documentsFormDefaults = ref<DocumentCore>(documentsStore.selectedDocumentCore || documentCoreDefaults());
const isLoading = ref(true);
const routeStatus = useRoute()
const routeName = computed(() => routeStatus.name as RouteKeyId);
const selectedStatus = ref<DocumentCore | string>('');
const selectedStatusTable = ref<DocumentCore | string>('');

interface FilterItem {
  text: string;
  value: string;
}
const filterItems = ref<{
  refundTypes: FilterItem[];
}>({
  refundTypes: []
});
const selectedRefundType = ref<any | null>(null);
const isNextDocumentButtonVisible = ref(false);
const nextDocumentName = ref<string | undefined>(undefined);
let isInitialized = false;
const selectedDocumentTableItems = ref<DocumentCore[]>([]);
const selectedSubItems = ref<Record<string, boolean>>({});
const selectedItemsIds = ref<string[]>([]);
const selectedIds = ref<any[]>([]);
const clientBalance = ref<number | null>(0)
const invoiceAmount = ref<number | string | null>(0);
const orderReceiptAmount = ref<number | string | null>(0);
const payedAmount = ref<any | null>(0)
const totalToPay = ref<any | null>(0)
const recoveryBalance = ref<number | null>(0)

const store = usePaymentStore();
const selectedPayment = ref<Payment>(getDefaultPayment())
const isAddNewPaymentDrawerVisible = ref(false)
const refFormAdd = ref<VForm>()
const allValidated = ref(false)
const atLeastOneReturnedOrRedo = ref(false)
const allReturned = ref(false)
const allRedo = ref(false)
const redoAndReturned = ref(false)
const selectedStatusItems = ref<any>({});
const isDeleteDialogVisible = ref(false)
const documentToDelete = ref<DocumentCore | null>(null)
const isStatusChanged = ref(false)
const documentTableProps = computed(() => ({
  documentType: props.documentType,
  TableData: TableData.value,
  isLoading: isLoading.value,
  documentData: documentData.value,
  hasDocumentManagerPermission: hasDocumentManagerPermission,
  routeMappings: routeMappings,
  selectedIds: selectedIds.value,
  selectedItemsIds: selectedItemsIds.value,
  statusItems: statusItems,
  invoiceStatus: invoiceStatus,
  orderReceiptStatus: orderReceiptStatus,
  productionNoteStatus: productionNoteStatus,
  outputNoteStatus: outputNoteStatus,
  delliveryNoteStatus: delliveryNoteStatus,
  invoiceCreditStatus: invoiceCreditStatus,
  refundStatus: refundStatus,
  status: status,
  nextDocumentName: nextDocumentName.value,
  canGenerate: canGenerate.value,
  archiveStore: archiveStore,
  isStatusChanged: isStatusChanged.value
}));

// Define statusItemsByDocumentType based on documentType
const statusItemsByDocumentType = computed(() => {
  switch (props.documentType) {
    case 'Facture':
      return invoiceStatus;
    case 'Bon de production':
      return productionNoteStatus;
    case 'Bon de sortie':
      return outputNoteStatus;
    case 'Bon de livraison':
      return delliveryNoteStatus;
    case 'Facture avoir':
      return invoiceCreditStatus;
    case 'Bon de remboursement':
      return refundStatus;
    case 'Reçu de commande':
      return orderReceiptStatus;
    default:
      return status;
  }
});

// Snackbar
const { showSnackbar } = useSnackbar();


// State variable for storing productionnotess data
const TableData = ref<{
  data: DocumentCore[]
  totaldata: number
}>({
  data: [],
  totaldata: 0,
})

//  Compute the key dynamically
const documentKey = computed(() => documentTypeMapping[props.documentType]);

// 🔚 end definitions

// 👉 Function to fetch document data
const fetchDocumentData = async (editItemId?: any) => {
  const id = editItemId ?? props.routeId
  isLoading.value = true;
  const filters: any = {};
  if (archiveStore.isArchive) {
    filters.archive = archiveStore.isArchive
  }
  try {

    documentData.value = await props.fetchFunction(id, filters);
    clientBalance.value = documentData.value.client?.balance
    invoiceAmount.value = documentData.value.finalAmount || 0;
    orderReceiptAmount.value = documentData.value.finalAmount || 0;
    payedAmount.value = documentData.value?.payedAmount || 0;
    totalToPay.value = documentData.value?.totalToPay || 0;
    recoveryBalance.value = documentData.value?.payments?.recovery?.recoveryBalance || 0;
    const documentMap: Record<string, any> = {
      'Devis': documentData.value?.quotes,
      'Demande de devis': documentData.value?.quoterequests,
      'Bon de commande': documentData.value?.orderNotes,
      'Bon de production': documentData.value?.productionNotes,
      'Bon de sortie': documentData.value?.outputNotes,
      'Bon de livraison': documentData.value?.deliveryNotes,
      'Bon de retour': documentData.value?.returnNotes,
      'Facture': documentData.value?.invoices,
      'Reçu de commande': documentData.value?.orderReceipts,
      'Facture avoir': documentData.value?.invoiceCredits,
      'Bon de remboursement': documentData.value?.refunds,
    };

    TableData.value.data = documentMap[props.documentType] || [];
    if (documentData.value?.status) {
      selectedStatus.value = documentData.value.status;
      isInitialized = true;
    }

  } catch (error) {
    console.error(`Error fetching ${props.documentType} data:`, error);
    documentData.value = null;
  } finally {
    isLoading.value = false;
  }
};
// 🔚 end fetching data


// 👉 print logic 
// Function to recursively copy computed styles
function copyStyles(sourceElement: HTMLElement, targetElement: HTMLElement) {
  const computedStyle = window.getComputedStyle(sourceElement);
  targetElement.style.color = computedStyle.color;

  Array.from(sourceElement.children).forEach((child, index) => {
    if (targetElement.children[index]) {
      copyStyles(child as HTMLElement, targetElement.children[index] as HTMLElement);
    }
  });
}

const handlePrint = () => {
  if (!props.documentType || !documentData?.value) {
    console.error('Missing document type or data');
    return;
  }

  printDocument(props.documentType, documentData.value, documentData.value?.isTaxable);
};

// 🔚 end print logic
const isGenerating = ref(false);
const isManualStatusUpdate = ref(false);
// 👉 Functions to handle generate button logic
const handleNavigateToNextDocument = async (setStatusToValide = false) => {
  isGenerating.value = true;


  try {
    if (setStatusToValide) {
      isManualStatusUpdate.value = true;
      const oldStatus = documentData.value.status;
      if (props.documentType === 'Facture' || props.documentType === 'Reçu de commande') {
        selectedStatus.value = 'Payé';
      } else {
        selectedStatus.value = 'Validé';
      }

      // Wait for the status update to complete
      if (selectedStatus.value !== oldStatus) {
        await updateStatusId(
          selectedStatus.value,
          documentsFormDefaults,
          documentsStore,
          routeName.value,
          selectedIds.value,
          props.routeId,
          props.documentType,
        );
        isStatusChanged.value = false;
        // await fetchDocumentData();
      }

    }
    // Validation for Bon de livraison with status "Validé"
    if (props.documentType === 'Bon de livraison' && documentData.value?.status === 'Validé') {
      if (allRedo.value === true || allReturned.value === true || redoAndReturned.value === true) {
        // Show error snackbar
        showSnackbar('Les documents sélectionnés sont incohérents, merci de vérifier les statuts des documents et des articles', 'error');
        return;
      }
    }

    // Validation for Bon de livraison with status "Rejeté"
    if (props.documentType === 'Bon de livraison' && documentData.value?.status === 'Rejeté') {
      if (allValidated.value === true) {
        // Show error snackbar
        showSnackbar('Les documents sélectionnés sont incohérents, merci de vérifier les statuts des documents et des articles', 'error');
        return;
      }
    }

    // Proceed with navigation if validation passes
    await navigateToNextDocument(
      props.documentType,
      routeName.value,
      props.routeId,
      props.generateDocument,
      selectedIds.value,
      router,
      documentsStore,
      selectedStatus.value,
      documentData.value.isTaxable,
      selectedItemsIds.value
    );
  } catch (error) {
    console.error('Error generating document:', error);
  } finally {
    // Ensure watcher resumes after manual update even if navigation fails
    isManualStatusUpdate.value = false;
    isGenerating.value = false;
  }
};


// Function to update the next document button visibility
const handleUpdateNextDocumentButtonVisibility = () => {
  updateNextDocumentButtonVisibility(routeName.value, selectedStatus.value, nextDocumentName, isNextDocumentButtonVisible, documentData.value?.isTaxable, allValidated.value, atLeastOneReturnedOrRedo.value, allReturned.value, allRedo.value, redoAndReturned.value);
};


// Function to navigate to the next document
const navigateToDocument = async (
  whereIamGoing: keyof typeof documentTypeMapping,
  quoteId: string,
  processGroupId?: string
) => {
  try {
    const documentKey = documentTypeMapping[whereIamGoing];

    if (!documentKey) {
      console.error(`Invalid document type: ${whereIamGoing}`);
      return;
    }

    const newDocId = await generateDocumentNavigation(whereIamGoing, quoteId, processGroupId);

    if (!newDocId) {
      console.error(`Failed to retrieve document ID for ${whereIamGoing}`);
      return;
    }

    router.push({ name: routeMappings[whereIamGoing], params: { id: newDocId } });
  } catch (error) {
    console.error(`Error navigating to ${whereIamGoing}:`, error);
  }
};

// 🔚 end generate functions logic

// 👉 Function to update the status of the document
const visibleTabs = computed(() => {
  // Define the desired order
  const tabOrder = [
    'Demande de devis',
    'Devis',
    'Bon de commande',
    'Bon de production',
    'Bon de sortie',
    'Bon de livraison',
    'Facture',
    'Reçu de commande',
    'Bon de retour',
    'Facture avoir',
    'Bon de remboursement'
  ];

  const getIsDisabled = (key: string, data: string) => {
    const length = Object.keys(documentData.value?.[data] ?? {}).length;
    return props.documentType !== key ? length === 0 : length === 0;
  };

  // Create a map of processed tabs
  const processedTabs = new Map();

  tabs.forEach(tab => {
    let isDisabled = false;
    let isVisible = true;

    switch (tab.title) {
      case 'Facture avoir':
        isDisabled = getIsDisabled('Facture avoir', 'invoiceCredits');
        isVisible = Object.keys(documentData.value?.invoiceCredits ?? {}).length > 0;
        break;

      case 'Facture':
        isDisabled = getIsDisabled('Facture', 'invoices');
        isVisible =
          props.documentType === 'Facture'
            ? documentData.value?.isTaxable === 1
            : (() => {
              const invoices = documentData.value?.invoices;
              if (!invoices) return false;
              const invoiceArray = Array.isArray(invoices) ? invoices : [invoices];
              return invoiceArray.some((inv: any) => inv.isTaxable === 1);
            })();
        break;

      case 'Bon de retour':
        isDisabled = getIsDisabled('Bon de retour', 'returnNotes');
        isVisible = Object.keys(documentData.value?.returnNotes ?? {}).length > 0;
        break;

      case 'Reçu de commande':
        isDisabled = getIsDisabled('Reçu de commande', 'orderReceipts');
        isVisible =
          props.documentType === 'Reçu de commande'
            ? documentData.value?.isTaxable === 0
            : (() => {
              const orderReceipts = documentData.value?.orderReceipts;
              if (!orderReceipts) return false;
              const receiptsArray = Array.isArray(orderReceipts) ? orderReceipts : [orderReceipts];
              return receiptsArray.some((or: any) => or.isTaxable === 0);
            })();
        break;


      case 'Demande de devis':
        isDisabled = getIsDisabled('Demande de devise', 'quoterequests');
        isVisible = Object.keys(documentData.value?.quoterequests ?? {}).length > 0;;
        break;
      case 'Devis':
        isDisabled = getIsDisabled('Devis', 'quotes');
        isVisible = true;
        break;


      case 'Bon de commande':
        isDisabled = getIsDisabled('Bon de commande', 'orderNotes');
        isVisible = true;
        break;

      case 'Bon de production':
        isDisabled = getIsDisabled('Bon de production', 'productionNotes');
        isVisible = true;
        break;

      case 'Bon de sortie':
        isDisabled = getIsDisabled('Bon de sortie', 'outputNotes');
        isVisible = true;
        break;

      case 'Bon de livraison':
        isDisabled = getIsDisabled('Bon de livraison', 'deliveryNotes');
        isVisible = true;
        break;

      case 'Bon de remboursement':
        isDisabled = getIsDisabled('Bon de remboursement', 'refunds');
        isVisible = Object.keys(documentData.value?.refunds ?? {}).length > 0;;
        break;

      default:
        break;
    }

    processedTabs.set(tab.title, { ...tab, disabled: isDisabled, visible: isVisible });
  });

  // Return tabs in the specified order
  return tabOrder
    .map(title => processedTabs.get(title))
    .filter(tab => tab !== undefined); // Filter out any tabs that don't exist
});

// Active tab
const activeTab = computed(() => {
  const filteredTabs = visibleTabs.value.filter(tab => tab.visible);
  return filteredTabs.findIndex(tab => tab.title === props.documentType);
});

// 🔚 end updating status

// 👉 begin Watcher
watch(() => props.routeId, async (newId, oldId) => {
  if (newId && newId !== oldId) {
    await fetchDocumentData();
  }
}, { immediate: true });


//  Watch selectedStatus and update statusId
watch(selectedStatus, async (newStatus, oldStatus) => {
  if (isManualStatusUpdate.value) return;

  if (!isInitialized) return;
  oldStatus = documentData.value.status

  if (newStatus !== oldStatus) {
    await updateStatusId(
      newStatus,
      documentsFormDefaults,
      documentsStore,
      routeName.value,
      selectedIds.value,
      props.routeId,
      props.documentType,
    );
    isStatusChanged.value = true;
    await fetchDocumentData();
  }
  handleUpdateNextDocumentButtonVisibility();


}, {
  deep: true

});

// 👉 Watcher to update the status of the delivery note items
watch(
  () => TableData.value?.data,
  (newData) => {
    if (newData) {

      newData.forEach((item: any) => {


        item.items?.forEach((subItem: any) => {

          if (!(subItem.id in selectedStatusItems.value)) {
            selectedStatusItems.value[subItem.id] = subItem.status || '';
          }
        });
      });
    }
  },
  { deep: true, immediate: true }
);

const selectedStatusCheckbox = ref<string[]>([]);


watch(selectedDocumentTableItems, (newSelection) => {
  selectedIds.value = newSelection
    .map((item: any) => (typeof item === 'string' ? item : item.id))
    .filter((id: any) => id);

  // ✅ Only update when full data is available (not just IDs)
  const containsFullObjects = newSelection.some(
    (item: any) => typeof item === 'object' && item !== null
  );

  // ✅ Store statuses here
  if (containsFullObjects) {
    selectedStatusCheckbox.value = newSelection.map((item: any) => item.status);
    selectedStatusTable.value = selectedStatusCheckbox.value
  }

  if (props.documentType === "Bon de livraison") {
    const check = newSelection
      .flatMap((item: any) =>
        typeof item === 'string'
          ? item
          : item.items.map((subItem: any) => subItem.status)
      )
      .filter((status: any) => status);

    allValidated.value = check.every((status: string) => status === "Validé");

    atLeastOneReturnedOrRedo.value = check.some(
      (status: string) => status === "Retourné" || status === "A refaire"
    );
    allReturned.value = check.every((status: string) => status === "Retourné");
    allRedo.value = check.every((status: string) => status === "A refaire");
    redoAndReturned.value =
      check.every((status: string) =>
        status === "Retourné" || status === "A refaire"
      ) &&
      check.includes("Retourné") &&
      check.includes("A refaire");
  }
  handleUpdateNextDocumentButtonVisibility();
}, { deep: true });


watch(selectedSubItems, (newSelection) => {
  selectedItemsIds.value = Object.keys(newSelection)
    .filter((id: any) => newSelection[id]);
}, { deep: true });





// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.refundTypes = mapStaticData(staticData.data.paymentType);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};


onMounted(() => {
  handleUpdateNextDocumentButtonVisibility();
  loadStaticData();
});

// 🔚 end watchers 

const clearPaymentData = () => {
  clientBalance.value = 0
  invoiceAmount.value = 0
  payedAmount.value = 0
  totalToPay.value = 0
  title2Render.value = '';
  recoveryBalance.value = 0;

}

// 👉 Begin Payment logic for invoice
const closeNavigationDrawer = () => {
  isAddNewPaymentDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()
  clearPaymentData();
}


// Handle drawer visibility update
const handleDrawerModelValueUpdate = (val: boolean) => {
  isAddNewPaymentDrawerVisible.value = val
}

// Modal title logic
const title1 = computed(() => store.mode === 'add' ? 'Ajouter des informations de paiement' : 'Modifier les informations de paiement');
const title2Render = ref('');

// Function to handle form submission for adding a new payment
const onSubmit = async () => {
  try {
    closeNavigationDrawer();
    await fetchDocumentData();
    showSnackbar("l'utilisateur", 'success');
  } catch (error) {
    console.error('Error adding payment:', error);
    closeNavigationDrawer();
    showSnackbar("l'utilisateur", 'error');
  }
};

const openAddNewPaymentDrawer = () => {
  selectedPayment.value = getDefaultPayment()
  // clearPaymentData();
  isAddNewPaymentDrawerVisible.value = true
  store.setAddMode();
  store.setPaymentType()
  if (props.documentType === 'Facture') {
    documentsStore.setDocType('invoice');
    documentsStore.setEditMode(documentData.value);
    if (store.selectedPayment) {
      store.selectedPayment.client = documentData.value.client?.legalName || '';
      store.selectedPayment.clientId = documentData.value.clientId;
    }
  } else if (props.documentType === 'Reçu de commande') {
    documentsStore.setDocType('orderreceipt');
    documentsStore.setEditMode(documentData.value);
    if (store.selectedPayment) {
      store.selectedPayment.client = documentData.value.client?.legalName || '';
      store.selectedPayment.clientId = documentData.value.clientId;
    }
  }
}

// 🔚 end payments


// 👉 Opens the drawer for editing a document
const openEditDrawer = async (itemId?: any) => {
  documentsStore.setQuoteId(null);
  documentsStore.setOrderNoteId(null);
  documentsStore.setClientId(null);
  documentsStore.setStatus(null);
  await fetchDocumentData(itemId);
  if (documentData.value) {
    documentsStore.setEditMode(documentData.value);
    await router.push({
      name: routeFormMappings[props.documentType],
    });
  } else {
    console.error("Document data is not available.");
  }
}
// 🔚 end edit drawer

// 👉 Delete logic for documents tables
const openDeleteDialog = (document: DocumentCore) => {
  documentToDelete.value = document
  isDeleteDialogVisible.value = true
}


// Function to handle delete action
const confirmDeleteDocument = async () => {
  const documentId = documentToDelete.value?.id;
  if (!documentId) {
    console.error('Document ID is undefined. Cannot delete document.');
    return;
  }

  try {
    const documentMapDelete: Record<string, (id: number) => Promise<void>> = {
      'Devis': deleteQuotes,
      'Demande de devis': deleteQuoteRequests,
      'Bon de commande': deleteOrderNotes,
      'Bon de production': deleteProductionNotes,
      'Bon de sortie': deleteOutputNotes,
      'Bon de livraison': deleteDeliveryNotes,
      'Bon de retour': deleteReturnNotes,
      'Facture': deleteInvoices,
      'Reçu de commande': deleteOrderReceipt,
      'Facture avoir': deleteInvoiceCredits,
      'Bon de remboursement': deleteRefunds,
    };

    const deleteFunction = documentMapDelete[props.documentType];

    if (!deleteFunction) {
      console.error(`No delete function found for document type: ${props.documentType}`);
      return;
    }

    await deleteFunction(documentId);
    isDeleteDialogVisible.value = false;
    await fetchDocumentData();
    await router.push({
      name: routeDocumentMappings[props.documentType],
    });
    showSnackbar(`Le document ${props.documentType} a été supprimé`, 'success');
  } catch (error) {
    console.error(`Error deleting ${props.documentType}:`, error);
    isDeleteDialogVisible.value = false;
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};


//  Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  documentToDelete.value = null
}

// 🔚 end delete logic


// 👉 Function to handle the next document name
const canGenerate = computed(() => {
  if (isGenerating.value) return false;

  if (props.documentType === 'Bon de livraison' && documentData.value?.status == 'Validé') {
    return (
      selectedDocumentTableItems.value.length > 0 &&
      (allValidated.value === true || atLeastOneReturnedOrRedo.value === true)
    );
  } else if (props.documentType === 'Bon de livraison' && documentData.value?.status == 'Rejeté') {
    return (
      selectedDocumentTableItems.value.length > 0 &&
      (allReturned.value === true || allRedo.value === true || allValidated.value === true || atLeastOneReturnedOrRedo.value === true)
    );

  }
  else if (props.documentType === 'Facture' && documentData.value?.status == 'Payé') {
    return selectedStatus.value === 'Payé';
  }
  else if (props.documentType === 'Reçu de commande' && documentData.value?.status == 'Validé') {
    return selectedStatus.value === 'Validé';
  }
  else if (props.documentType === 'Facture avoir') {
    return true
  }

  else {
    return (
      selectedDocumentTableItems.value.length > 0 &&
      selectedDocumentTableItems.value.every((item: any) => item.status === 'Validé')
    );
  }
});

// 🔚 end next document name logic

// 👉 Function to open the drawer for adding a new quotes
const openAddNewwProductionNote = async () => {
  documentsStore.setNewProductionNote(true);
  documentsFormDefaults.value = null;
  documentsFormDefaults.value = getDefaultProductionNotes()
  documentsStore.setAddMode();
  documentsStore.setQuoteId(documentData.value?.quoteId);
  documentsStore.setOrderNoteId(documentData.value?.orderNoteId);
  documentsStore.setClientId(documentData.value?.clientId);
  documentsStore.setStatus(documentData.value?.status);

  await router.push('/documents/productionnotes/form');
}
// 🔚 end add new production note

// 👉 Duplicate a Document.
const DuplicateDocument = async (item?: { id: string }) => {
  try {
    let response;
    if (props.documentType === 'Devis') {
      response = await DuplicateQuote(props.routeId);
    } else if (props.documentType === 'Bon de production') {
      response = await DuplicateProductionNote(item?.id ?? props.routeId);
    }
    else if (props.documentType === 'Demande de devis') {
      response = await DuplicateQuoteRequest(props.routeId);
    }
    else {
      console.warn("Unsupported document type:", props.documentType);
      return;
    }
    router.push({ name: routeMappings[props.documentType], params: { id: response.id } });
    showSnackbar(`Le document ${props.documentType} a été dupliqué`, 'success');
  } catch (error) {
    console.error("Error duplicating document:", error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 🔚 end duplicate document


// 👉 Function to update the status of the delivery note items
const handleStatusChange = async (newStatus: any, subItemId: any) => {
  if (!isInitialized) return;
  const deliveryNoteItem = TableData.value?.data?.find((item: any) =>
    item.items?.some((subItem: any) => subItem.id === subItemId)
  );


  const subItem = deliveryNoteItem?.items?.find((subItem: any) => subItem.id === subItemId);
  const deliveryNoteId = subItem?.deliveryNoteId;

  const oldStatus = selectedStatusItems.value[subItemId];

  if (newStatus !== oldStatus) {
    try {
      await updateStatusDeliveryNotesItems(deliveryNoteId, subItemId, newStatus);
      showSnackbar(`Le status de l'article a été mis à jour`, 'success');
      isStatusChanged.value = true;
      await fetchDocumentData();
    } catch (error) {
      console.error('Error updating status:', error);
      const err = error as any;
      showSnackbar(`${err.response?.data.message}`, 'error');

    }
  }
};
// 🔚 end status change

// 👉 Function to handle generate button logic
const getItemProps = (item: any) => ({
  value: item,
  title: item,
  disabled: item === 'Retourné' || item === 'Terminé',
});

// 🔚 end generate functions logic


const hasRequiredPermissions = (permissions: string[]): boolean => {
  const authStore = useAuthStore();
  return permissions.every((permission) => authStore.permissions.includes(permission));

};

// 👉 Define the required permissions for this component
const requiredPermissionsDocs = ['document_manager'];


// 👉 Computed or reactive property to check the permissions
const hasDocumentManagerPermission = hasRequiredPermissions(requiredPermissionsDocs);


const handleVselectStatus = (value: any) => {
  if (value) {
    selectedIds.value = [props.routeId];
  } else {
    selectedIds.value = [];

  }
};
const handleSelectType = async (value: any) => {
  try {
    const response = await updateTypeRefunds(props.routeId, value);

    if (response) {
      showSnackbar(`Le type de remboursement a été mis à jour`, 'success');
      await fetchDocumentData();
    }
  } catch (error) {
    const err = error as any;
    console.error("Error preparing document data:", err);
    showSnackbar(`${err.response?.data.message || 'Une erreur est survenue'}`, 'error');
  }
};

watch(
  [documentData, () => filterItems.value.refundTypes],
  ([doc, refundOptions]) => {
    if (
      props.documentType === 'Bon de remboursement' &&
      refundOptions.length &&
      doc?.paymentType?.name
    ) {
      selectedRefundType.value = refundOptions.find(item => item.text === doc.paymentType.name) || null;
    }
  },
  { immediate: true }
);

const documentTableEvents = {
  'handleStatusChange': handleStatusChange,
  'openEditDrawer': openEditDrawer,
  'openDeleteDialog': openDeleteDialog,
  'DuplicateDocument': DuplicateDocument,
  'handleNavigateToNextDocument': handleNavigateToNextDocument,
  'openAddNewwProductionNote': openAddNewwProductionNote,
};


const isDatePickerOpen = ref(false);

const handleDatePickerState = (isOpen: any) => {
  isDatePickerOpen.value = isOpen;
};


const isTableSelectDisabledSelectStatus = computed(() => {
  // Disable if any selected status is Retourné or Terminé
  if (selectedStatusCheckbox.value.some(s => ['Retourné', 'Terminé'].includes(s))) {
    return true;
  }
  // Otherwise, enable
  return false;
});

const isTableSelectDisabledGenerateButton = computed(() => {
  // Disable if any selected status is Retourné or Terminé
  if (selectedStatusCheckbox.value.some(s => ['Terminé'].includes(s))) {
    return true;
  }
  // Otherwise, enable
  return false;
});


const computedSelectedStatus = computed({
  get() {
    // If document has a status set and it's not 'Brouillon', show it directly
    if (documentData.value?.status && documentData.value.status !== 'Brouillon') return documentData.value.status;

    // Nothing selected
    if (!selectedIds.value.length) return 'Brouillon';

    // One document selected
    if (selectedStatusTable.value.length === 1) return selectedStatusTable.value[0];

    // Multiple documents selected
    const uniqueStatuses = Array.from(new Set(selectedStatusTable.value));
    if (uniqueStatuses.length === 1) return uniqueStatuses[0];

    // Multiple documents with different statuses
    return 'Brouillon';
  },
  set(value: string) {
    // Optional: if user changes the select manually, update selectedStatus
    selectedStatus.value = value;
  }
});



</script>

<template>

  <section v-if="documentData">
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-5">
      <div>
        <h4 class="text-h4 mb-5">
          {{ documentType }}
        </h4>
      </div>
    </div>


    <VTabs density="compact" v-model="activeTab" show-arrows class="v-tabs-pill mb-3 disable-tab-transition ">
      <VTab class="small-tab" v-for="(tab, index) in visibleTabs.filter(tab => tab.visible)" :key="tab.title"
        :active="index === activeTab" :disabled="tab.disabled" @click="navigateToDocument(
          tab.title as keyof typeof documentTypeMapping,
          documentType === 'Devis' && !documentData.processGroupId ? documentData.id : documentData.quoteId,
          documentData.processGroupId
        )">
        {{ tab.title }}
      </VTab>
    </VTabs>

    <DocumentTable v-bind="documentTableProps" v-model:selectedDocumentTableItems="selectedDocumentTableItems"
      v-model:selectedStatus="selectedStatus" v-model:selectedSubItems="selectedSubItems" v-on="documentTableEvents">
      <template #generatecard>
        <VCol cols="12" md="3">
          <VCard>
            <VCardText>
              <!-- 👉 Select Status-->
              <VSelect v-model="computedSelectedStatus"
                :disabled="!(['Facture avoir', 'Bon de remboursement', 'Bon de retour'].includes(documentType)) && (isTableSelectDisabledSelectStatus || !(selectedIds.length || selectedItemsIds.length) || archiveStore.isArchive)"
                :items="statusItemsByDocumentType" variant="outlined" label="Statut" placeholder="Statut" class="mb-6"
                :item-props="getItemProps" />

              <VBtn v-if="documentType === 'Bon de production' && hasDocumentManagerPermission"
                :disabled="!(documentType === 'Bon de production') || archiveStore.isArchive" color="primary"
                class="mb-4 w-100" @click="openAddNewwProductionNote()">
                Ajouter bon de production
              </VBtn>
              <div class="d-flex flex-wrap gap-2">
                <!-- 👉 Generer -->
                <VBtn
                  v-if="(documentType !== 'Bon de remboursement' && documentType !== 'Bon de retour') && hasDocumentManagerPermission"
                  @click="handleNavigateToNextDocument(false)" :disabled="!canGenerate || archiveStore.isArchive" block
                  color="success" class="mb-4 custom-large-button custom-generate-button" size="x-large"
                  prepend-icon="tabler-plus">
                  <span class="wrap-button-text">
                    {{ nextDocumentName }}

                  </span>
                </VBtn>

                <VBtn
                  v-if="(documentType !== 'Bon de remboursement' && documentType !== 'Bon de retour') && hasDocumentManagerPermission"
                  @click="handleNavigateToNextDocument(true)" block color="primary"
                  :height="documentType === 'Facture avoir' ? 75 : undefined"
                  class="mb-4 custom-large-button custom-generate-button" size="x-large" prepend-icon="tabler-plus"
                  :disabled="!(['Facture avoir', 'Bon de remboursement', 'Bon de retour'].includes(documentType)) && (isTableSelectDisabledGenerateButton || archiveStore.isArchive || !(selectedIds.length || selectedItemsIds.length))">
                  <span class="wrap-button-text">
                    Valider et générer {{ nextDocumentName?.toLowerCase() }}
                  </span>
                </VBtn>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </template>
    </DocumentTable>
    <VRow>

      <VCol cols="12" md="9">
        <DocumentPreviewContent :documentData="documentData" :documentType="documentType" :documentKey="documentKey"
          :themeConfig="themeConfig" :formatDateToddmmYYYY="formatDateToddmmYYYY" />
      </VCol>

      <VCol cols="12" md="3" class="d-print-none">

        <VCard class="mb-8">
          <VCardText>
            <!-- 👉 Select Status-->
            <VSelect v-model="selectedStatus"
              :disabled="['Retourné', 'Terminé'].includes(documentData?.status) || archiveStore.isArchive"
              :items="statusItemsByDocumentType" variant="outlined" label="Statut" placeholder="Statut" class="mb-6"
              :item-props="getItemProps" @update:modelValue="handleVselectStatus($event)" />

            <VSelect v-if="['Bon de remboursement'].includes(documentType)" v-model="selectedRefundType"
              :items="filterItems.refundTypes" item-title="text" variant="outlined" label="Type" placeholder="Type"
              class="mb-6" @update:modelValue="handleSelectType($event)" />


            <div class="d-flex flex-wrap gap-4">

              <VBtn v-if="hasDocumentManagerPermission"
                :disabled="(!(['Facture avoir', 'Bon de retour'].includes(documentType)) && ['Validé', 'Annulé', 'Terminé'].includes(documentData?.status)) || documentType === 'Bon de remboursement' || archiveStore.isArchive"
                icon="tabler-pencil" color="primary" rounded class="mb-4 flex-grow-1" @click="openEditDrawer()">
              </VBtn>

              <VBtn :disabled="archiveStore.isArchive" icon="tabler-download" color="primary" class="flex-grow-1"
                @click="handlePrint()" rounded>

              </VBtn>


              <VBtn
                v-if="(['Devis', 'Bon de production', 'Demande de devis'].includes(documentType)) && hasDocumentManagerPermission"
                :disabled="archiveStore.isArchive" icon="tabler-copy" color="primary" rounded class="mb-4 flex-grow-1"
                @click="DuplicateDocument()">

              </VBtn>

              <VBtn :disabled="archiveStore.isArchive || (['Payé'].includes(documentData?.status))"
                v-if="(documentType === 'Facture' || documentType === 'Reçu de commande') && hasDocumentManagerPermission"
                color="success" class="flex-grow-1" @click="openAddNewPaymentDrawer" prepend-icon="tabler-plus">
                Paiement
              </VBtn>
              <!-- 👉 Generer -->
              <VBtn
                v-if="(isNextDocumentButtonVisible && (documentType === 'Devis' || documentType === 'Bon de commande' || documentType === 'Demande de devis')) && hasDocumentManagerPermission"
                :disabled="selectedStatus !== 'Validé' || archiveStore.isArchive"
                @click="handleNavigateToNextDocument(false)" block color="success"
                class="mb-4 custom-large-button custom-generate-button" size="x-large" prepend-icon="tabler-plus">
                <span class="wrap-button-text">
                  {{ nextDocumentName }}
                </span>
              </VBtn>
              <VBtn
                v-if="(isNextDocumentButtonVisible && (documentType === 'Devis' || documentType === 'Bon de commande' || documentType === 'Demande de devis')) && hasDocumentManagerPermission"
                @click="handleNavigateToNextDocument(true)" block color="primary"
                class="mb-4 custom-large-button custom-generate-button" size="x-large" prepend-icon="tabler-plus"
                :disabled="selectedStatus === 'Terminé' || archiveStore.isArchive">
                <span class="wrap-button-text">
                  Valider et générer {{ nextDocumentName?.toLowerCase() }}
                </span>
              </VBtn>
            </div>
          </VCardText>
        </VCard>
        <div class="text-center mb-3">
          <span class="text-h6 font-weight-medium">Créé par : {{ documentData.user?.fullName }}</span>
        </div>
        <VDivider />
        <div class="text-center mt-3 mb-3">
          <span class="text-h6 font-weight-medium">Client : <RouterLink
              :to="{ name: archiveStore.isArchive ? 'archives-clients-id' : 'clients-id', params: { id: documentData.client?.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              {{ documentData.client?.legalName }}
            </RouterLink></span>
        </div>
        <VDivider
          v-if="documentType === 'Facture' || documentType === 'Reçu de commande' || documentType === 'Bon de livraison'" />
        <div
          v-if="documentType === 'Facture' || documentType === 'Reçu de commande' || documentType === 'Bon de livraison'"
          class="text-center mt-3 mb-3">
          <span class="text-h6 font-weight-medium">Solde : {{ documentData.client?.balance ?? 0 }} DH</span>
        </div>
      </VCol>
    </VRow>
  </section>
  <section v-if="!isLoading">
    <VAlert v-if="!documentData" type="error" variant="tonal">
      {{ props.routeId }} pas trouvé!
    </VAlert>
  </section>
  <!-- Delete Confirmation Dialog -->
  <AlertDialog :isVisible="isDeleteDialogVisible"
    :message="'Êtes-vous sûr de vouloir archiver ce ' + documentType + '?'" deleteButtonText="Confirmer"
    cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
    :onDeleteClick="confirmDeleteDocument" :onCancelClick="cancelDelete"
    @update:isVisible="isDeleteDialogVisible = $event" />

  <!-- 👉  payment drawer -->
  <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isAddNewPaymentDrawerVisible"
    @update:model-value="handleDrawerModelValueUpdate" persistent :is-date-picker-open="isDatePickerOpen">
    <!-- Dialog close button slot -->
    <template v-if="!isDatePickerOpen" #close-btn>
      <DialogCloseBtn @click="closeNavigationDrawer" />

    </template>


    <!-- Title and Subtitle slot -->
    <template #title>
      <h4 class="text-h4 text-center mb-2">
        {{ title1 }}
      </h4>
      <!-- Show for Espèce payment type -->
      <p v-if="title2Render" class="text-body-1 text-center mb-6">
        Solde :
        <span :style="{ color: (clientBalance ?? 0) >= 0 ? 'green' : 'red' }">
          {{ clientBalance }} DH
        </span>
        <br />
        {{ documentType === 'Facture' ? 'Total facture :' : 'Total reçu de commande :' }}
        <span>
          {{ documentType === 'Facture' ? invoiceAmount : orderReceiptAmount }} DH
        </span>
        <br />
        Total payé :
        <span>
          {{ payedAmount }} DH
        </span>
        <br />
        Total à payer :
        <span>
          {{ totalToPay }} DH
        </span>
      </p>

      <!-- Show for Recovery payment types -->
      <p v-else-if="recoveryBalance !== null && recoveryBalance !== 0" class="text-body-1 text-center mb-6">
        Solde :
        <span :style="{ color: recoveryBalance >= 0 ? 'green' : 'red' }">
          {{ recoveryBalance }} DH
        </span>
        <br />
        {{ documentType === 'Facture' ? 'Total facture :' : 'Total reçu de commande :' }}
        <span>
          {{ documentType === 'Facture' ? invoiceAmount : orderReceiptAmount }} DH
        </span>
        <br />
        Total payé :
        <span>
          {{ payedAmount }} DH
        </span>
        <br />
        Total à payer :
        <span>
          {{ totalToPay }} DH
        </span>
      </p>
    </template>
    <!-- Form slot -->
    <template #form>
      <PaymentForm :mode="store.mode" :selected-payment="store.selectedPayment" @close="closeNavigationDrawer"
        @submit="onSubmit" :clientBalanceProps="clientBalance" :invoiceAmount="invoiceAmount"
        :orderReceiptAmount="orderReceiptAmount" @update:title2="title2Render = $event"
        @date-picker-state="handleDatePickerState" @update:recoveryBalance="recoveryBalance = $event" />
    </template>
  </ModalForm>
  <!--generate loading animation-->
  <!-- <VOverlay v-model="isGenerating" class="align-center justify-center generating-overlay">
    <div class="generating-container">
      <div class="generating-sparkles">
        <svg class="sparkle-icon sparkle-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.582a.5.5 0 0 1 0 .962L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>
          <path d="M20 3v4"/>
          <path d="M22 5h-4"/>
          <path d="M4 17v2"/>
          <path d="M5 18H3"/>
        </svg>
        
        <svg class="sparkle-icon sparkle-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.582a.5.5 0 0 1 0 .962L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>
          <path d="M20 3v4"/>
          <path d="M22 5h-4"/>
          <path d="M4 17v2"/>
          <path d="M5 18H3"/>
        </svg>
        
        <svg class="sparkle-icon sparkle-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.582a.5.5 0 0 1 0 .962L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>
          <path d="M20 3v4"/>
          <path d="M22 5h-4"/>
          <path d="M4 17v2"/>
          <path d="M5 18H3"/>
        </svg>
      </div>
      <div class="generating-text">
        Génération en cours...
      </div>
    </div>
  </VOverlay> -->
</template>
