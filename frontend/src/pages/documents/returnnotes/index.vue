<script setup lang="ts">
import TablePagination from '@/@core/components/TablePagination.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import { router } from '@/plugins/1.router';
import { deleteReturnNotes, exportReturnNotes, fetchArchivedReturnNotes, fetchReturnNotes, fetchReturnNotesById, fetchStaticData } from '@/services/api/returnnote';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useDocumentCoreStore } from '@/stores/documents';
import { getDefaultReturnNotes, getDefaultReturnNotesFilterParams } from '@services/defaults';
import type { ReturnNotes, ReturnNotesFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNewreturnnotesDrawerVisible = ref(false)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 State variable for the selected returnnotes in edit mode
const selectedreturnnotes = ref<ReturnNotes>(getDefaultReturnNotes())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the returnnotes to delete
const returnnotesToDelete = ref<ReturnNotes | null>(null)

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<ReturnNotesFilterParms>(getDefaultReturnNotesFilterParams())

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

// 👉 State variable for storing returnnotess data
const returnnotesData = ref<{
  returnnotes: ReturnNotes[]
  totalreturnnotes: number
}>({
  returnnotes: [],
  totalreturnnotes: 0,
})

// 👉 Function to fetch returnnotess from the API
const loadreturnnotes = async () => {
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
    }
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedReturnNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    }
    else {
      data = await fetchReturnNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    returnnotesData.value.returnnotes = data.returnNotes.data || [];
    returnnotesData.value.totalreturnnotes = data.totalReturnNotes || 0;
    if (returnnotesData.value.returnnotes.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadreturnnotes();
    }

  } catch (error) {
    console.error('Error fetching returnnotes:', error);
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

const exportReturnNote = async () => {

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
    const response = await exportReturnNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'returnnotes.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export returnnotes: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting returnnotes:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadreturnnotes();
  loadStaticData();
});

// 👉 Replace throttledLoadReturnNotess with debouncedLoadReturnNotess
const debouncedLoadReturnNotess = debounce(async () => {
  await loadreturnnotes();
}, 800);

// 👉 Watchers to reload returnnotess on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadReturnNotess();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadreturnnotes();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadreturnnotes();
};

// 👉 clear filters
const onSearchInput = (newSearch: string) => {
  if (newSearch) {
    filterParams.value = {
      ...getDefaultReturnNotesFilterParams(),
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

const openView = (returnNote: ReturnNotes) => {
  store.setEditMode(returnNote);
  store.setPreviewMode();

}

// 👉 Function to open the drawer for adding a new returnnotes
const openAddNewreturnnotesDrawer = async () => {
  selectedreturnnotes.value = getDefaultReturnNotes()
  isAddNewreturnnotesDrawerVisible.value = true
  store.setAddMode();
  await router.push('/documents/returnnotes/form');
}

// 👉 Opens the drawer for editing a returnnotes.
const openEditreturnnotesDrawer = async (returnnotes: ReturnNotes) => {
  const id = returnnotes.id
  const data = await fetchReturnNotesById(id)
  store.setEditMode(data);
  await router.push('/documents/returnnotes/form');

}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (returnnotes: ReturnNotes) => {
  returnnotesToDelete.value = returnnotes
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeletereturnnotes = async () => {
  const returnnotesId = returnnotesToDelete.value?.id
  if (!returnnotesId) {
    console.error('returnnotes ID is undefined. Cannot delete returnnotes.')
    return
  }
  try {
    await deleteReturnNotes(returnnotesId)
    isDeleteDialogVisible.value = false
    await loadreturnnotes()
    showSnackbar('le bon de retour', 'success');
  } catch (error) {
    console.error('Error deleting returnnotes:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  returnnotesToDelete.value = null
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
          Bons de retour
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
              { value: 10, title: '10' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
            ]" style="inline-size: 6.25rem;" @update:model-value="onItemsPerPageChange" />

            <!-- 👉 Export button -->
            <VBtn v-if="!archiveStore.isArchive" @click="exportReturnNote" variant="tonal" color="secondary"
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
            <VAutocomplete v-model="filterParams.selectedStatus" :items="status" @update:modelValue="onFilterChange"
              item-title="text" item-value="value" label="Statut" :clearable="isClearableStatus" clear-icon="tabler-x"
              placeholder="Statut" variant="outlined" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable :headers="headers" :items="returnnotesData.returnnotes"
        v-model:items-per-page="queryParams.itemsPerPage" class="elevation-1" :loading="isLoading">

        <!-- Custom circular loading indicator -->
        <template #loading>

          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>

        <template #item.code="{ item }">
          <RouterLink
            :to="{ name: archiveStore.isArchive ? 'archives-documents-returnnotes-id' : 'documents-returnnotes-id', params: { id: item.id } }"
            class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;" @click="openView(item)">
            {{ item.code }}
          </RouterLink>
        </template>

        <template #item.client="{ item }">
          <span>
            {{ item.client?.legalName }}
          </span>
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
              :to="{ name: archiveStore.isArchive ? 'archives-documents-returnnotes-id' : 'documents-returnnotes-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;"
              @click="openView(item)">
              <IconBtn @click="openView(item)">
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>
            <IconBtn :disabled="archiveStore.isArchive" v-if="hasDocumentManagerPermission && !archiveStore.isArchive"
              @click="openEditreturnnotesDrawer(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <IconBtn v-if="hasDocumentManagerPermission && !archiveStore.isArchive"
              :disabled="(item.status ? ['Validé', 'Annulé', 'Terminé'].includes(item.status) : false) || archiveStore.isArchive"
              @click="openDeleteDialog(item)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="returnnotesData.totalreturnnotes" @update:page="onPageChange" />
        </template>

      </VDataTable>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce bon de retour ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeletereturnnotes" :onCancelClick="cancelDelete"
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
