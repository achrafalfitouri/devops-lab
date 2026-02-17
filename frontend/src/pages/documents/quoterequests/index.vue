<script setup lang="ts">
import TablePagination from '@/@core/components/TablePagination.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import { router } from '@/plugins/1.router';
import { deleteQuoteRequests, DuplicateQuoteRequest, exportQuoteRequests, fetchArchivedQuoteRequests, fetchQuoteRequests, fetchQuoteRequestsById, fetchStaticData } from '@/services/api/quoterequest';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useDocumentCoreStore } from '@/stores/documents';
import { getDefaultQuoteRequests, getDefaultQuoteRequestsFilterParams } from '@services/defaults';
import type { QuoteRequests, QuoteRequestsFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNewquoterequestsDrawerVisible = ref(false)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 State variable for the selected quoterequests in edit mode
const selectedquoterequests = ref<QuoteRequests | null>(getDefaultQuoteRequests())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the quoterequests to delete
const quoterequestsToDelete = ref<QuoteRequests | null>(null)

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<QuoteRequestsFilterParms>(getDefaultQuoteRequestsFilterParams())

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

// 👉 State variable for storing quoterequestss data
const quoterequestsData = ref<{
  quoterequests: QuoteRequests[]
  totalquoterequests: number
}>({
  quoterequests: [],
  totalquoterequests: 0,
})

// 👉 Function to fetch quoterequestss from the API
const loadquoterequests = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};
    let data;
    if (filterParams.value.searchQuery) {
      filters.search = {
        search: filterParams.value.searchQuery,
      };
    } else {
      if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
      if (filterParams.value.selectedUser) filters.user = filterParams.value.selectedUser;
      if (filterParams.value.selectedDate) filters.date = filterParams.value.selectedDate;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
    }

    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedQuoteRequests(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    else {
      data = await fetchQuoteRequests(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    quoterequestsData.value.quoterequests = data.quoterequests.data || [];
    quoterequestsData.value.totalquoterequests = data.totalQuoteRequests || 0;
    if (quoterequestsData.value.quoterequests.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadquoterequests();
    }

  } catch (error) {
    console.error('Error fetching quoterequests:', error);
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

const exportQuote = async () => {
  try {
    const filters: any = {};
    if (filterParams.value.searchQuery) {
      filters.search = {
        search: filterParams.value.searchQuery,
      };
    } else {
      if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
      if (filterParams.value.selectedUser) filters.user = filterParams.value.selectedUser;
      if (filterParams.value.selectedDate) filters.date = filterParams.value.selectedDate;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
    }
    const response = await exportQuoteRequests(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'quoterequests.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export quoterequests: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting quoterequests:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadquoterequests();
  loadStaticData();
});

// 👉 Replace throttledLoadQuoteRequestss with debouncedLoadQuoteRequestss
const debouncedLoadQuoteRequestss = debounce(async () => {
  await loadquoterequests();
}, 800);

// 👉 Watchers to reload quoterequestss on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadQuoteRequestss();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadquoterequests();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadquoterequests();
};

// 👉 clear filters
const onSearchInput = (newSearch: string) => {
  if (newSearch) {
    filterParams.value = {
      ...getDefaultQuoteRequestsFilterParams(),
      searchQuery: newSearch
    };
  }
};

// 👉 clear search
const onFilterChange = () => {
  if (filterParams.value.selectedClient || filterParams.value.selectedUser || filterParams.value.selectedDate) {
    filterParams.value.searchQuery = '';
  }
};

const isClearableStatus = computed(() => {
  return filterParams.value.selectedStatus !== null;
});

// 👉 Table headers definition
const headers = [
  { title: 'N° Doc', key: 'code' },
  { title: 'Client', key: 'client' },
  { title: 'Date', key: 'date' },
  { title: 'Statut', key: 'status' },
  ...((!archiveStore.isArchive)
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),]

const openView = (quote: QuoteRequests) => {
  store.setEditMode(quote);
  store.setPreviewMode();
}

// 👉 Function to open the drawer for adding a new quoterequests
const openAddNewquoterequestsDrawer = async () => {
  selectedquoterequests.value = null;
  selectedquoterequests.value = getDefaultQuoteRequests()
  isAddNewquoterequestsDrawerVisible.value = true
  store.setAddMode();
  await router.push('/documents/quoterequests/form');
}

// 👉 Duplicate a quote.
const DuplicateDocument = async (quoterequests: QuoteRequests) => {
  try {
    const id = quoterequests.id
    const response = await DuplicateQuoteRequest(id)
    await router.push({ name: routeMappings["Demande de devis"], params: { id: response.id } });
    await loadquoterequests();
    showSnackbar(`Le document demande de devis a été dupliqué`, 'success');
  }
  catch (error) {
    console.error("Error duplicating document:", error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}
// 👉 Opens the drawer for editing a quoterequests.
const openEditquoterequestsDrawer = async (quoterequests: QuoteRequests) => {
  const id = quoterequests.id
  const data = await fetchQuoteRequestsById(id)
  store.setEditMode(data);
  await router.push('/documents/quoterequests/form');

}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (quoterequests: QuoteRequests) => {
  quoterequestsToDelete.value = quoterequests
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeletequoterequests = async () => {
  const quoterequestsId = quoterequestsToDelete.value?.id
  if (!quoterequestsId) {
    console.error('quoterequests ID is undefined. Cannot delete quoterequests.')
    return
  }
  try {
    await deleteQuoteRequests(quoterequestsId)
    isDeleteDialogVisible.value = false
    await loadquoterequests()
    showSnackbar('le demande de devis', 'success');
  } catch (error) {
    console.error('Error deleting quoterequests:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  quoterequestsToDelete.value = null
}

const isClearableClient = computed(() => {
  return filterParams.value.selectedClient !== null;
});
const isClearableUser = computed(() => {
  return filterParams.value.selectedUser !== null;
});
const isClearableDate = computed(() => {
  return filterParams.value.selectedDate !== null;
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
          Demande de devis
        </h4>
      </div>
      <div>
        <!-- Add Button -->
        <VBtn v-if="!archiveStore.isArchive" prepend-icon="tabler-plus" @click="openAddNewquoterequestsDrawer">
          Ajouter une demande de devis
        </VBtn>
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
            <VBtn v-if="!archiveStore.isArchive" @click="exportQuote" variant="tonal" color="secondary"
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
          <VCol cols="12" md="4">
            <AppDateTimePicker v-model="filterParams.selectedDate" @update:modelValue="onFilterChange"
              placeholder="Date " :clearable="isClearableDate" clear-icon="tabler-x" />
          </VCol>

        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable :headers="headers" :items="quoterequestsData.quoterequests"
        v-model:items-per-page="queryParams.itemsPerPage" :loading="isLoading">

        <template #loading>

          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>

        <template #item.code="{ item }">

          <RouterLink
            :to="{ name: archiveStore.isArchive ? 'archives-documents-quoterequests-id' : 'documents-quoterequests-id', params: { id: item.id } }"
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
              :to="{ name: archiveStore.isArchive ? 'archives-documents-quoterequests-id' : 'documents-quoterequests-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;"
              @click="openView(item)">
              <IconBtn @click="openView(item)">
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>
            <IconBtn :disabled="archiveStore.isArchive" v-if="hasDocumentManagerPermission && !archiveStore.isArchive"
              @click="DuplicateDocument(item)">
              <VIcon icon="tabler-copy" />
            </IconBtn>
            <IconBtn v-if="hasDocumentManagerPermission && !archiveStore.isArchive"
              :disabled="(item.status ? ['Validé', 'Annulé', 'Terminé'].includes(item.status) : false) || archiveStore.isArchive"
              @click="openEditquoterequestsDrawer(item)">
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
            :totalItems="quoterequestsData.totalquoterequests" @update:page="onPageChange" />
        </template>
      </VDataTable>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce demande de devis ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeletequoterequests" :onCancelClick="cancelDelete"
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
