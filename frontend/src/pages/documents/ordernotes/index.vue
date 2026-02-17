<script setup lang="ts">
import TablePagination from '@/@core/components/TablePagination.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import { router } from '@/plugins/1.router';
import { deleteOrderNotes, exportOrderNotes, fetchArchivedOrderNotes, fetchOrderNotes, fetchOrderNotesById, fetchStaticData } from '@/services/api/ordernote';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useDocumentCoreStore } from '@/stores/documents';
import { getDefaultOrderNotes, getDefaultOrderNotesFilterParams } from '@services/defaults';
import type { OrderNotes, OrderNotesFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNewordernotesDrawerVisible = ref(false)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 State variable for the selected ordernotes in edit mode
const selectedordernotes = ref<OrderNotes>(getDefaultOrderNotes())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the ordernotes to delete
const ordernotesToDelete = ref<OrderNotes | null>(null)

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<OrderNotesFilterParms>(getDefaultOrderNotesFilterParams())

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

// 👉 State variable for storing ordernotess data
const ordernotesData = ref<{
  ordernotes: OrderNotes[]
  totalordernotes: number
}>({
  ordernotes: [],
  totalordernotes: 0,
})

// 👉 Function to fetch ordernotess from the API
const loadordernotes = async () => {
  isLoading.value = true; // Start loading
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
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
    }
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedOrderNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    else {
      data = await fetchOrderNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    ordernotesData.value.ordernotes = data.orderNotes.data || [];
    ordernotesData.value.totalordernotes = data.totalOrderNotes || 0;
    // Check if current page is now empty after deletion
    if (ordernotesData.value.ordernotes.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadordernotes();
    }

  } catch (error) {
    console.error('Error fetching ordernotes:', error);
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

const exportOrdernote = async () => {

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
    const response = await exportOrderNotes(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'ordernotes.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export ordernotes: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting ordernotes:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};


// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadordernotes();
  loadStaticData();
});

// 👉 Replace throttledLoadOrderNotess with debouncedLoadOrderNotess
const debouncedLoadOrderNotess = debounce(async () => {
  await loadordernotes();
}, 800);

// 👉 Watchers to reload ordernotess on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadOrderNotess();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadordernotes();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadordernotes();
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

const openView = (orderNotes: OrderNotes) => {
  store.setEditMode(orderNotes);
  store.setPreviewMode();

}


// 👉 Opens the drawer for editing a ordernotes.
const openEditordernotesDrawer = async (ordernotes: OrderNotes) => {
  const id = ordernotes.id
  const data = await fetchOrderNotesById(id)
  store.setEditMode(data);
  await router.push('/documents/ordernotes/form');

}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (ordernotes: OrderNotes) => {
  ordernotesToDelete.value = ordernotes
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleteordernotes = async () => {
  const ordernotesId = ordernotesToDelete.value?.id
  if (!ordernotesId) {
    console.error('ordernotes ID is undefined. Cannot delete ordernotes.')
    return
  }
  try {
    await deleteOrderNotes(ordernotesId)
    isDeleteDialogVisible.value = false
    await loadordernotes()
    // Show success snackbar
    showSnackbar('le bon de commande', 'success');
  } catch (error) {
    console.error('Error deleting ordernotes:', error)
    // Show error snackbar
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  ordernotesToDelete.value = null
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
          Bons de Commande
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
            <VBtn v-if="!archiveStore.isArchive" @click="exportOrdernote" variant="tonal" color="secondary"
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
      <VDataTable :headers="headers" :items="ordernotesData.ordernotes"
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
            :to="{ name: archiveStore.isArchive ? 'archives-documents-ordernotes-id' : 'documents-ordernotes-id', params: { id: item.id } }"
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
              :to="{ name: archiveStore.isArchive ? 'archives-documents-ordernotes-id' : 'documents-ordernotes-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;"
              @click="openView(item)">
              <IconBtn @click="openView(item)">
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>
            <IconBtn v-if="hasDocumentManagerPermission && !archiveStore.isArchive"
              :disabled="(item.status ? ['Validé', 'Annulé', 'Terminé'].includes(item.status) : false) || archiveStore.isArchive"
              @click="openEditordernotesDrawer(item)">
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
            :totalItems="ordernotesData.totalordernotes" @update:page="onPageChange" />
        </template>

      </VDataTable>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce bon de commande ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeleteordernotes" :onCancelClick="cancelDelete"
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
