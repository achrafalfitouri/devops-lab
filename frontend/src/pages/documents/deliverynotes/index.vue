<script setup lang="ts">
import TablePagination from '@/@core/components/TablePagination.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import { router } from '@/plugins/1.router';
import { deleteDeliveryNotes, exportDeliveryNotes, fetchArchivedDeliveryNotes, fetchDeliveryNotes, fetchDeliveryNotesById, fetchStaticData, GenerateWithMultipleDeliveryId, updateStatusDeliveryNotesItems } from '@/services/api/deliverynote';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useDocumentCoreStore } from '@/stores/documents';
import { documentCoreDefaults, getDefaultDeliveryNotes, getDefaultDeliveryNotesFilterParams } from '@services/defaults';
import type { DeliveryNotes, DeliveryNotesFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();
const documentsFormDefaults = ref<DocumentCore>(store.selectedDocumentCore || documentCoreDefaults());


// 👉 State variables for controlling drawer visibility
const isAddNewdeliverynotesDrawerVisible = ref(false)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 State variable for the selected deliverynotes in edit mode
const selecteddeliverynotes = ref<DeliveryNotes>(getDefaultDeliveryNotes())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the deliverynotes to delete
const deliverynotesToDelete = ref<DeliveryNotes | null>(null)

const taxOptions = [
  { text: 'Oui  ', value: 1 },   // is_taxable = 1
  { text: 'Non', value: 0 },   // is_taxable = 0
]

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<DeliveryNotesFilterParms>(getDefaultDeliveryNotesFilterParams())

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

// 👉 State variable for storing deliverynotess data
const deliverynotesData = ref<{
  deliverynotes: DeliveryNotes[]
  totaldeliverynotes: number
}>({
  deliverynotes: [],
  totaldeliverynotes: 0,
})

// Refs
const selectedStatusTable = ref<string[]>([]);
const selectedIds = ref<any[]>([]);
const selectedStatus = ref<string>('');
const selectedItemsIds = ref<string[]>([]);
const selectedStatusCheckbox = ref<string[]>([]);
const selectedStatusItems = ref<any>({});
const selectedDocumentTableItems = ref<any[]>([]);
const isStatusChanged = ref(false)
const allValidated = ref(false)
const atLeastOneReturnedOrRedo = ref(false)
const allReturned = ref(false)
const allRedo = ref(false)
const redoAndReturned = ref(false)

// 👉 Function to fetch deliverynotess from the API
const loaddeliverynotes = async () => {
  isLoading.value = true;
  try {
    let data;
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = {
        search: filterParams.value.searchQuery,
      };
    } else {
      if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
      if (filterParams.value.selectedUser) filters.user = filterParams.value.selectedUser;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
      if (filterParams.value.selectedTax !== null && filterParams.value.selectedTax !== undefined) {
        filters.tax = filterParams.value.selectedTax;
      }
    }

    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedDeliveryNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    } else {
      data = await fetchDeliveryNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }

    deliverynotesData.value.deliverynotes = data.deliveryNotes.data || [];
    deliverynotesData.value.totaldeliverynotes = data.totalDeliveryNotes || 0;

    // Populate selectedStatusItems after loading data
    const statusObj = deliverynotesData.value.deliverynotes.reduce((acc: any, note: any) => {
      acc[note.id] = note.status;

      if (note.items?.length) {
        note.items.forEach((item: any) => {
          acc[item.id] = item.status;
        });
      }

      return acc;
    }, {});

    // Use spread operator to ensure Vue reactivity
    selectedStatusItems.value = { ...statusObj };

    // Check if current page is now empty after deletion
    if (deliverynotesData.value.deliverynotes.length === 0 && queryParams.value.page > 1) {
      queryParams.value.page--;
      await loaddeliverynotes();
    }

  } catch (error) {
    console.error('Error fetching deliverynotes:', error);
  } finally {
    isLoading.value = false;
  }
};
// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.users = mapStaticData(staticData.data.userFilter);
    filterItems.value.clients = mapStaticData(staticData.data.clientFilter);

  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

const exportDeliveryNote = async () => {

  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = {
        search: filterParams.value.searchQuery,
      };
    } else {
      if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
      if (filterParams.value.selectedUser) filters.user = filterParams.value.selectedUser;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
      if (filterParams.value.selectedTax !== null && filterParams.value.selectedTax !== undefined) {
        filters.tax = filterParams.value.selectedTax;
      }

    }
    const response = await exportDeliveryNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'deliverynotes.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export deliverynotes: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting deliverynotes:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loaddeliverynotes();
  loadStaticData();
  store.setDocType("Bon de livraison")
  handleUpdateNextDocumentButtonVisibility();
});



// 👉 Replace throttledLoadDeliveryNotess with debouncedLoadDeliveryNotess
const debouncedLoadDeliveryNotess = debounce(async () => {
  await loaddeliverynotes();
}, 800);

// 👉 Watchers to reload deliverynotess on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadDeliveryNotess();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loaddeliverynotes();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loaddeliverynotes();
};

// 👉 clear filters
const onSearchInput = (newSearch: string) => {
  if (newSearch) {
    filterParams.value = {
      ...getDefaultDeliveryNotesFilterParams(),
      searchQuery: newSearch
    };
  }
};

// 👉 clear search
const onFilterChange = () => {
  if (filterParams.value.selectedClient || filterParams.value.selectedUser) {
    filterParams.value.searchQuery = '';
  }
};

// 👉 Table headers definition
const headers = [
  { title: 'N° Doc', key: 'code' },
  { title: 'Client', key: 'client' },
  { title: 'Date', key: 'date' },
  { title: 'Statut', key: 'status' },
  { title: 'TVA', key: 'isTaxable' },
  ...((!archiveStore.isArchive)
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),]

const openView = (deliveryNotes: DeliveryNotes) => {
  store.setEditMode(deliveryNotes);
  store.setPreviewMode();

}

// 👉 Function to open the drawer for adding a new deliverynotes
const openAddNewdeliverynotesDrawer = async () => {
  selecteddeliverynotes.value = getDefaultDeliveryNotes()
  isAddNewdeliverynotesDrawerVisible.value = true
  store.setAddMode();
  await router.push('/documents/deliverynotes/form');
}

// 👉 Opens the drawer for editing a deliverynotes.
const openEditdeliverynotesDrawer = async (deliverynotes: DeliveryNotes) => {
  const id = deliverynotes.id
  const data = await fetchDeliveryNotesById(id)
  store.setEditMode(data);
  await router.push('/documents/deliverynotes/form');

}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (deliverynotes: DeliveryNotes) => {
  deliverynotesToDelete.value = deliverynotes
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeletedeliverynotes = async () => {
  const deliverynotesId = deliverynotesToDelete.value?.id
  if (!deliverynotesId) {
    console.error('deliverynotes ID is undefined. Cannot delete deliverynotes.')
    return
  }
  try {
    await deleteDeliveryNotes(deliverynotesId)
    isDeleteDialogVisible.value = false
    await loaddeliverynotes()
    showSnackbar('le bon de livraison', 'success');
  } catch (error) {
    console.error('Error deleting deliverynotes:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  deliverynotesToDelete.value = null
}

const isClearableClient = computed(() => {
  return filterParams.value.selectedClient !== null;
});
const isClearableUser = computed(() => {
  return filterParams.value.selectedUser !== null;
});

const isClearableStatus = computed(() => {
  return filterParams.value.selectedStatus !== null;
});
const isClearableTax = computed(() => {
  return filterParams.value.selectedTax !== null;
});

const hasRequiredPermissions = (permissions: string[]): boolean => {
  const authStore = useAuthStore();
  return permissions.every((permission) => authStore.permissions.includes(permission));

};

function handleSelectionChange(newSelection: any[]) {
  selectedIds.value = [...newSelection.map((item: any) => item.id)];
  selectedStatusTable.value = [...newSelection.map((item: any) => item.status)];
  selectedStatusCheckbox.value = [...newSelection.map((item: any) => item.status)];
  selectedDocumentTableItems.value = [...newSelection];


}

const handleStatusChange = async (newStatus: any, subItemId: any) => {
  const deliveryNoteItem = deliverynotesData.value.deliverynotes.find((item: any) =>
    item.items?.some((subItem: any) => subItem.id === subItemId)
  );

  const subItem = deliveryNoteItem?.items?.find((subItem: any) => subItem.id === subItemId);
  if (!subItem) {
    console.error('Sub item not found');
    return;
  }

  const deliveryNoteId = subItem.deliveryNoteId;
  const oldStatus = (subItem as any).status || selectedStatusItems.value[subItemId];

  if (newStatus === oldStatus) {
    return;
  }

  selectedStatusItems.value[subItemId] = newStatus;

  try {
    await updateStatusDeliveryNotesItems(deliveryNoteId, subItemId, newStatus);
    showSnackbar(`Le status de l'article a été mis à jour`, 'success');
    isStatusChanged.value = true;

    await loaddeliverynotes();

  } catch (error) {
    console.error('Error updating status:', error);
    const err = error as any;

    selectedStatusItems.value[subItemId] = oldStatus;

    showSnackbar(`${err.response?.data?.message || 'Erreur lors de la mise à jour'}`, 'error');
  }
  selectedIds.value = []
  selectedStatusTable.value = []
  selectedStatusCheckbox.value = []
  selectedDocumentTableItems.value = []
};

watch(
  () => deliverynotesData.value?.deliverynotes,
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

  if (store.docType === 'Bon de livraison') {
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

const getItemProps = (item: any) => ({
  value: item,
  title: item,
  disabled: item === 'Retourné' || item === 'Terminé',
});

// Computed
const computedSelectedStatus = computed({
  get() {
    if (!selectedIds.value.length) return 'Brouillon';

    const uniqueStatuses = Array.from(new Set(selectedStatusTable.value));

    if (uniqueStatuses.length === 1) return uniqueStatuses[0];

    return 'Brouillon';
  },
  set(value: string) {
    selectedStatus.value = value;
  }
});

const isTableSelectDisabledSelectStatus = computed(() => {
  return selectedStatusCheckbox.value.some(s => ['Retourné', 'Terminé'].includes(s));
});

const onStatusSelectChange = async (newStatus: string) => {
  let oldStatus = 'Brouillon'

  if (selectedIds.value.length > 0) {
    const uniqueStatuses = Array.from(new Set(selectedStatusTable.value))
    if (uniqueStatuses.length === 1) oldStatus = uniqueStatuses[0]
  }

  if (newStatus && newStatus !== oldStatus) {
    await handleDocumentStatusChange(newStatus, oldStatus)
    selectedIds.value = []
    selectedStatusTable.value = []
    selectedStatusCheckbox.value = []
    selectedDocumentTableItems.value = []
  }
}

// Handler for status change
const handleDocumentStatusChange = async (newStatus: string, oldStatus: string) => {
  try {
    const idsToUpdate = selectedIds.value.length > 0 ? selectedIds.value : selectedItemsIds.value;

    if (!idsToUpdate || idsToUpdate.length === 0) {
      showSnackbar('Veuillez sélectionner au moins un document', 'warning');
      return;
    }

    await updateStatusId(
      newStatus,
      documentsFormDefaults,
      store,
      'documents-deliverynotes-id',
      idsToUpdate,
      undefined,
      'Bon de livraison',
    );

    showSnackbar(`Le statut du document a été mis à jour`, 'success');
    await loaddeliverynotes();
    handleUpdateNextDocumentButtonVisibility();


  } catch (error) {
    console.error('Error updating document status:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data?.message || 'Erreur lors de la mise à jour'}`, 'error');

    selectedIds.value = []
    selectedStatusTable.value = []
    selectedStatusCheckbox.value = []
    selectedDocumentTableItems.value = []

  }
};

const isGenerating = ref(false);
const canGenerate = computed(() => {
  if (isGenerating.value) return false;

  if (selectedDocumentTableItems.value.length === 0) return false;

  const selectedStatuses = selectedDocumentTableItems.value.map((item: any) => item.status);
  const allStatusesValidated = selectedStatuses.every((status: string) => status === 'Validé');
  const allStatusesRejected = selectedStatuses.every((status: string) => status === 'Rejeté');

  if (store.docType === 'Bon de livraison') {
    if (allStatusesValidated) {
      return allValidated.value === true || atLeastOneReturnedOrRedo.value === true;
    }

    if (allStatusesRejected) {
      return (
        allReturned.value === true ||
        allRedo.value === true ||
        allValidated.value === true ||
        atLeastOneReturnedOrRedo.value === true ||
        redoAndReturned.value === true
      );

    }

    return false;
  } else {
    return allStatusesValidated;
  }
});


const nextDocumentName = ref<string | undefined>(undefined);
const isNextDocumentButtonVisible = ref(false);

const taxForMultipleGeneration = ref<any | undefined>(null);
// Function to update the next document button visibility
const handleUpdateNextDocumentButtonVisibility = () => {
  // Check if all selected documents have the same tax value
  const taxValues = selectedDocumentTableItems.value.map((item: any) => item.isTaxable);
  const allSameTax = taxValues.length > 0 && taxValues.every((tax: number) => tax === taxValues[0]);
  const taxValue = allSameTax ? taxValues[0] : null;
  taxForMultipleGeneration.value = allSameTax;


  updateNextDocumentButtonVisibility(
    'documents-deliverynotes-id',
    selectedStatus.value,
    nextDocumentName,
    isNextDocumentButtonVisible,
    taxValue, // Pass the tax value (0, 1, or null if mixed)
    allValidated.value,
    atLeastOneReturnedOrRedo.value,
    allReturned.value,
    allRedo.value,
    redoAndReturned.value
  );
};
const routerDeliveryGenerate = useRouter()

const handleNavigateToNextDocument = async (setStatusToValide = false) => {
  isGenerating.value = true;

  try {
    // Extract statuses from selected documents
    const selectedStatuses = selectedDocumentTableItems.value.map((item: any) => item.status);
    const allStatusesValidated = selectedStatuses.every((status: string) => status === 'Validé');
    const allStatusesRejected = selectedStatuses.every((status: string) => status === 'Rejeté');

    // Validation for Bon de livraison only
    if (store.docType === 'Bon de livraison') {
      // Check for inconsistent states with "Validé" documents
      if (allStatusesValidated && (allRedo.value || allReturned.value || redoAndReturned.value)) {
        showSnackbar(
          'Les documents sélectionnés sont incohérents, merci de vérifier les statuts des documents et des articles',
          'error'
        );
        return;
      }

      // Check for inconsistent states with "Rejeté" documents
      if (allStatusesRejected && allValidated.value) {
        showSnackbar(
          'Les documents sélectionnés sont incohérents, merci de vérifier les statuts des documents et des articles',
          'error'
        );
        return;
      }

      // Check for mixed document statuses
      if (!allStatusesValidated && !allStatusesRejected) {
        showSnackbar(
          'Tous les documents sélectionnés doivent avoir le même statut (Validé ou Rejeté)',
          'error'
        );
        return;
      }
    }

    // Validate that documents are selected
    if (!selectedIds.value || selectedIds.value.length === 0) {
      showSnackbar('Veuillez sélectionner au moins un document', 'warning');
      return;
    }

    // Check if all documents have the same tax value
    const taxValues = selectedDocumentTableItems.value.map((item: any) => item.isTaxable);
    const allSameTax = taxValues.length > 0 && taxValues.every((tax: number) => tax === taxValues[0]);

    if (!allSameTax) {
      showSnackbar(
        'Tous les documents sélectionnés doivent avoir la même valeur de TVA',
        'error'
      );
      return;
    }

    // Determine the unified status to send
    const unifiedStatus = allStatusesValidated ? 'Validé' : allStatusesRejected ? 'Rejeté' : null;

    if (!unifiedStatus) {
      showSnackbar('Impossible de déterminer le statut unifié des documents', 'error');
      return;
    }

    // Proceed with navigation if all validations pass
    await navigateToNextDocumentMultipleDeliveryNoteProcess(
      'Bon de livraison',
      'documents-deliverynotes-id',
      null,
      GenerateWithMultipleDeliveryId,
      selectedIds.value,
      routerDeliveryGenerate,
      store,
      unifiedStatus, // Send single unified status instead of selectedStatus.value
      taxValues[0], // Use the validated tax value
      selectedItemsIds.value
    );

    // Clear selections after successful generation
    selectedIds.value = [];
    selectedStatusTable.value = [];
    selectedStatusCheckbox.value = [];
    selectedDocumentTableItems.value = [];

  } catch (error) {
    console.error('Error generating document:', error);
    const err = error as any;
    showSnackbar(
      `${err.response?.data?.message || 'Erreur lors de la génération du document'}`,
      'error'
    );
  } finally {
    isGenerating.value = false;
  }
};

// 👉 Define the required permissions for this component
const requiredPermissionsDocs = ['document_manager'];

// 👉 Computed or reactive property to check the permissions
const hasDocumentManagerPermission = hasRequiredPermissions(requiredPermissionsDocs);


// Computed properties for totals
const totalAmount = computed(() => {
  return selectedDocumentTableItems.value.reduce((sum, item) => {
    const amount = parseFloat(item.amount) || 0;
    return sum + amount;
  }, 0);
});

const totalFinalAmount = computed(() => {
  return selectedDocumentTableItems.value.reduce((sum, item) => {
    const finalAmount = parseFloat(item.finalAmount) || 0;
    return sum + finalAmount;
  }, 0);
});

// Helper function to format currency
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'MAD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
    .format(value)
    .replace('MAD', 'DH');
};

</script>

<template>
  <section>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Bons de Livraison
        </h4>
      </div>
      <div>
      </div>
    </div>

    <!-- 👉 Widgets -->
    <VCard class="mb-6">

      <!-- First Row: Items per page, Export, and Add buttons -->
      <VCardText>
        <VRow align="center" class="justify-end">
          <VCol cols="6" md="5">
            <VCardTitle class="pa-0">Filtres et recherche</VCardTitle>
          </VCol>
          <VCol cols="7" class="d-flex align-center gap-2 justify-end md-6">
            <!-- Items per Page Select -->
            <AppSelect :model-value="queryParams.itemsPerPage" :items="[
              { value: 10, title: '10' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
            ]" style="inline-size: 6.25rem;" @update:model-value="onItemsPerPageChange" />

            <!-- 👉 Export button -->
            <VBtn v-if="!archiveStore.isArchive" @click="exportDeliveryNote" variant="tonal" color="secondary"
              prepend-icon="tabler-upload">
              Exporter
            </VBtn>
            <!-- 👉 Select Status-->
            <div style="inline-size: 8rem;">
              <VSelect v-model="computedSelectedStatus"
                :disabled="(isTableSelectDisabledSelectStatus || !(selectedIds.length || selectedItemsIds.length) || archiveStore.isArchive)"
                :items="delliveryNoteStatus" variant="outlined" label="Statut" placeholder="Statut"
                :item-props="getItemProps" @update:model-value="onStatusSelectChange" />
            </div>

            <VBtn v-if="!archiveStore.isArchive" :disabled="!canGenerate" @click="handleNavigateToNextDocument(false)"
              color="primary" prepend-icon="tabler-plus">
              {{ nextDocumentName }}
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <!-- Second Row: Search, Filter 1, and Filter 2 -->
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedUser" :items="filterItems.users"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Utilisateurs"
              :clearable="isClearableUser" clear-icon="tabler-x" placeholder="Utilisateurs" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedClient" :items="filterItems.clients"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Clients"
              :clearable="isClearableClient" clear-icon="tabler-x" placeholder="Client" variant="outlined" />
          </VCol>

          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedStatus" :items="delliveryNoteStatus"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Status"
              :clearable="isClearableStatus" clear-icon="tabler-x" placeholder="Status" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedTax" :items="taxOptions" item-title="text" item-value="value"
              label="TVA" :clearable="isClearableTax" clear-icon="tabler-x" placeholder="TVA" variant="outlined"
              @update:modelValue="onFilterChange" />
          </VCol>

        </VRow>
      </VCardText>
    </VCard>
     
    <!-- 👉 Selected Documents Summary Card -->
<VCard   class="mb-6">
  <VCardText>
    <VRow>
      <VCol cols="12">
        <h5 class="text-h5 mb-0">
          Résumé des documents sélectionnés ({{ selectedDocumentTableItems.length }})
        </h5>
      </VCol>
      <VCol cols="12" md="6">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-cash" size="24" color="primary" />
          <div>
            <div class="text-caption text-disabled">Total HT</div>
            <div class="text-h6">{{ formatCurrency(totalAmount) }}</div>
          </div>
        </div>
      </VCol>
      <VCol cols="12" md="6">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-cash" size="24" color="success" />
          <div>
            <div class="text-caption text-disabled">Total TTC</div>
            <div class="text-h6">{{ formatCurrency(totalFinalAmount) }}</div>
          </div>
        </div>
      </VCol>
    </VRow>
  </VCardText>
</VCard>
    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable :headers="headers" :items="deliverynotesData.deliverynotes"
        v-model:items-per-page="queryParams.itemsPerPage" class="elevation-1" :loading="isLoading"
        v-model:model-value="selectedDocumentTableItems" expand-on-click show-select :item-selectable="(item: any) =>
          !['Terminé', 'Annulé', 'Retourné', 'Perte'].includes(item.status)"
        @update:model-value="handleSelectionChange" return-object>

        <template #loading>

          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>


        <template #expanded-row="slotProps">
          <tr v-for="subItem in slotProps.item.items" :key="subItem.id" class="v-data-table__tr">
            <td>&nbsp;</td>
            <td>{{ subItem.description || 'N/A' }}</td>
            <td>{{ subItem.amount ?? '0.00' }}</td>
            <td>
              <VSelect :disabled="slotProps.item.status ? ['Terminé'].includes(slotProps.item.status) : false"
                v-model="selectedStatusItems[subItem.id]" @update:modelValue="handleStatusChange($event, subItem.id)"
                :items="statusItems" placeholder="Statut" />
            </td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
        </template>

        <template #item.client="{ item }">
          <span>
            {{ item.client?.legalName }}
          </span>
        </template>

        <template #item.code="{ item }">

          <RouterLink
            :to="{ name: archiveStore.isArchive ? 'archives-documents-deliverynotes-id' : 'documents-deliverynotes-id', params: { id: item.id } }"
            class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;" @click="openView(item)">
            {{ item.code }}
          </RouterLink>
        </template>
        <template #item.isTaxable="{ item }">
          {{ item.isTaxable !== null ? getStatusIsTaxable(item.isTaxable) : '-' }}
        </template>


        <template #item.finalAmount="{ item }">
          <span v-if="item.isTaxable">
            {{ item.finalAmount || '0.00' }}
          </span>
          <span v-else>-</span>
        </template>

           <template #item.date="{ item }">
          <div class="d-flex align-center gap-4">
            <span class="text-body-1 text-truncate">
              {{ item?.createdAt ? formatDateToddmmYYYY(item.createdAt as string) : '-' }}
            </span>
          </div>
        </template> 

        <template #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">

            <RouterLink
              :to="{ name: archiveStore.isArchive ? 'archives-documents-deliverynotes-id' : 'documents-deliverynotes-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;"
              @click="openView(item)">
              <IconBtn @click="openView(item)">
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>

            <IconBtn v-if="hasDocumentManagerPermission && !archiveStore.isArchive"
              :disabled="(item.status ? ['Validé', 'Annulé', 'Terminé'].includes(item.status) : false) || archiveStore.isArchive"
              @click="openEditdeliverynotesDrawer(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <IconBtn :disabled="archiveStore.isArchive" v-if="hasDocumentManagerPermission && !archiveStore.isArchive"
              @click="openDeleteDialog(item)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="deliverynotesData.totaldeliverynotes" @update:page="onPageChange" />
        </template>

      </VDataTable>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce bon livraison ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeletedeliverynotes" :onCancelClick="cancelDelete"
      @update:isVisible="isDeleteDialogVisible = $event" />


  </section>
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
