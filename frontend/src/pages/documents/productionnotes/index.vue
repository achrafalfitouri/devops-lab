<script setup lang="ts">
import TablePagination from '@/@core/components/TablePagination.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import { productionNoteStatus } from '@/composables/DocumentConst';
import { router } from '@/plugins/1.router';
import { deleteProductionNotes, DuplicateProductionNote, exportProductionNotes, fetchArchivedProductionNotes, fetchProductionNotes, fetchProductionNotesById, fetchStaticData } from '@/services/api/productionnote';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useDocumentCoreStore } from '@/stores/documents';
import { getDefaultProductionNotes, getDefaultProductionNotesFilterParams } from '@services/defaults';
import type { ProductionNotes, ProductionNotesFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNewproductionnotesDrawerVisible = ref(false)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 State variable for the selected productionnotes in edit mode
const selectedproductionnotes = ref<ProductionNotes>(getDefaultProductionNotes())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the productionnotes to delete
const productionnotesToDelete = ref<ProductionNotes | null>(null)

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<ProductionNotesFilterParms>(getDefaultProductionNotesFilterParams())

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

// 👉 State variable for storing productionnotess data
const productionnotesData = ref<{
  productionnotes: ProductionNotes[]
  totalproductionnotes: number
}>({
  productionnotes: [],
  totalproductionnotes: 0,
})

// 👉 Function to fetch productionnotess from the API
const loadproductionnotes = async () => {
  isLoading.value = true;
  try {
    let data
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = {
        search: filterParams.value.searchQuery,
      };
    } else {
      if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
      if (filterParams.value.selectedUser) filters.user = filterParams.value.selectedUser;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
    }
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedProductionNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    }
    else {
      data = await fetchProductionNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    productionnotesData.value.productionnotes = data.productionNotes.data || [];
    productionnotesData.value.totalproductionnotes = data.totalProductionNotes || 0;
    if (productionnotesData.value.productionnotes.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadproductionnotes();
    }

  } catch (error) {
    console.error('Error fetching productionnotes:', error);
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


// 👉 Duplicate a productionNote
const DuplicateDocument = async (productionnotes: ProductionNotes) => {
  try {
    const id = productionnotes.id

    const response = await DuplicateProductionNote(id)
    await router.push({ name: routeMappings["Bon de production"], params: { id: response.id } });
    await loadproductionnotes();
    showSnackbar(`Le document bon de production a été dupliqué`, 'success');
  }
  catch (error) {
    console.error("Error duplicating document:", error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

const exportProductionNote = async () => {

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
    }
    const response = await exportProductionNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'productionnotes.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export productionnotes: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting productionnotes:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadproductionnotes();
  loadStaticData();
});

// 👉 Replace throttledLoadProductionNotess with debouncedLoadProductionNotess
const debouncedLoadProductionNotess = debounce(async () => {
  await loadproductionnotes();
}, 800);

// 👉 Watchers to reload productionnotess on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadProductionNotess();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadproductionnotes();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadproductionnotes();
};

// 👉 clear filters
const onSearchInput = (newSearch: string) => {
  if (newSearch) {
    filterParams.value = {
      ...getDefaultProductionNotesFilterParams(),
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
  ...((!archiveStore.isArchive)
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),]

const openView = (productionNotes: ProductionNotes) => {
  store.setEditMode(productionNotes);
  store.setPreviewMode();

}

// 👉 Opens the drawer for editing a productionnotes.
const openEditproductionnotesDrawer = async (productionnotes: ProductionNotes) => {
  const id = productionnotes.id
  const data = await fetchProductionNotesById(id)
  store.setEditMode(data);
  await router.push('/documents/productionnotes/form');

}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (productionnotes: ProductionNotes) => {
  productionnotesToDelete.value = productionnotes
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleteproductionnotes = async () => {
  const productionnotesId = productionnotesToDelete.value?.id
  if (!productionnotesId) {
    console.error('productionnotes ID is undefined. Cannot delete productionnotes.')
    return
  }
  try {
    await deleteProductionNotes(productionnotesId)
    isDeleteDialogVisible.value = false
    await loadproductionnotes()
    showSnackbar('le bon de production', 'success');
  } catch (error) {
    console.error('Error deleting productionnotes:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  productionnotesToDelete.value = null
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

const hasRequiredPermissions = (permissions: string[]): boolean => {
  const authStore = useAuthStore();
  return permissions.every((permission) => authStore.permissions.includes(permission));

};

// 👉 Define the required permissions for this component
const requiredPermissionsDocs = ['document_manager'];


// 👉 Computed or reactive property to check the permissions
const hasDocumentManagerPermission = hasRequiredPermissions(requiredPermissionsDocs);
</script>

<template>
  <section>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Bons de production
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
          <VCol>
            <VCardTitle class="pa-0">Filtres et recherche</VCardTitle>
          </VCol>
          <VCol class="d-flex align-center gap-2 justify-end">
            <!-- Items per Page Select -->
            <AppSelect :model-value="queryParams.itemsPerPage" :items="[
              // { value: 5, title: '5' },
              { value: 10, title: '10' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
              // { value: -1, title: 'All' },
            ]" style="inline-size: 6.25rem;" @update:model-value="onItemsPerPageChange" />

            <!-- 👉 Export button -->
            <VBtn v-if="!archiveStore.isArchive" @click="exportProductionNote" variant="tonal" color="secondary"
              prepend-icon="tabler-upload">
              Exporter
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
            <VAutocomplete v-model="filterParams.selectedStatus" :items="productionNoteStatus"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Statut"
              :clearable="isClearableStatus" clear-icon="tabler-x" placeholder="Statut" variant="outlined" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable :headers="headers" :items="productionnotesData.productionnotes"
        v-model:items-per-page="queryParams.itemsPerPage" class="elevation-1" :loading="isLoading">
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
            :to="{ name: archiveStore.isArchive ? 'archives-documents-productionnotes-id' : 'documents-productionnotes-id', params: { id: item.id } }"
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
              :to="{ name: archiveStore.isArchive ? 'archives-documents-productionnotes-id' : 'documents-productionnotes-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem; text-decoration: none;">
              <IconBtn>
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>
            <IconBtn v-if="hasDocumentManagerPermission && !archiveStore.isArchive" :disabled="archiveStore.isArchive"
              @click="DuplicateDocument(item)">
              <VIcon icon="tabler-copy" />
            </IconBtn>
            <IconBtn v-if="hasDocumentManagerPermission && !archiveStore.isArchive"
              :disabled="(item.status ? ['Validé', 'Annulé', 'Terminé'].includes(item.status) : false) || archiveStore.isArchive"
              @click="openEditproductionnotesDrawer(item)">
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
            :totalItems="productionnotesData.totalproductionnotes" @update:page="onPageChange" />
        </template>
      </VDataTable>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce bon de production ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeleteproductionnotes" :onCancelClick="cancelDelete"
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
