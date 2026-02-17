<script setup lang="ts">
import TablePagination from '@/@core/components/TablePagination.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import { router } from '@/plugins/1.router';
import { deleteRefunds, exportRefunds, fetchArchivedRefunds, fetchRefunds, fetchRefundsById, fetchStaticData } from '@/services/api/refund';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useDocumentCoreStore } from '@/stores/documents';
import { getDefaultRefunds, getDefaultRefundsFilterParams } from '@services/defaults';
import type { Refunds, RefundsFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNewrefundsDrawerVisible = ref(false)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 State variable for the selected refunds in edit mode
const selectedrefunds = ref<Refunds>(getDefaultRefunds())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the refunds to delete
const refundsToDelete = ref<Refunds | null>(null)

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<RefundsFilterParms>(getDefaultRefundsFilterParams())

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

// 👉 State variable for storing refundss data
const refundsData = ref<{
  refunds: Refunds[]
  totalrefunds: number
}>({
  refunds: [],
  totalrefunds: 0,
})

// 👉 Function to fetch refundss from the API
const loadrefunds = async () => {
  isLoading.value = true; // Start loading
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
      data = await fetchArchivedRefunds(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    }
    else {
      data = await fetchRefunds(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    refundsData.value.refunds = data.refunds.data || [];
    refundsData.value.totalrefunds = data.totalRefunds || 0;
    // Check if current page is now empty after deletion
    if (refundsData.value.refunds.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadrefunds();
    }

  } catch (error) {
    console.error('Error fetching refunds:', error);
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

const exportRefund = async () => {

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
    const response = await exportRefunds(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'refunds.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export refunds: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting refunds:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadrefunds();
  loadStaticData();
});

// 👉 Replace throttledLoadRefundss with debouncedLoadRefundss
const debouncedLoadRefundss = debounce(async () => {
  await loadrefunds();
}, 800);

// 👉 Watchers to reload refundss on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadRefundss();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadrefunds();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadrefunds();
};

// 👉 clear filters
const onSearchInput = (newSearch: string) => {
  if (newSearch) {
    filterParams.value = {
      ...getDefaultRefundsFilterParams(),
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

const openView = (refunds: Refunds) => {
  store.setEditMode(refunds);
  store.setPreviewMode();

}

// 👉 Function to open the drawer for adding a new refunds
const openAddNewrefundsDrawer = async () => {
  selectedrefunds.value = getDefaultRefunds()
  isAddNewrefundsDrawerVisible.value = true
  store.setAddMode();
  await router.push('/documents/refunds/form');
}

// 👉 Opens the drawer for editing a refunds.
const openEditrefundsDrawer = async (refunds: Refunds) => {
  const id = refunds.id
  const data = await fetchRefundsById(id)
  store.setEditMode(data);
  await router.push('/documents/refunds/form');

}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (refunds: Refunds) => {
  refundsToDelete.value = refunds
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleterefunds = async () => {
  const refundsId = refundsToDelete.value?.id
  if (!refundsId) {
    console.error('refunds ID is undefined. Cannot delete refunds.')
    return
  }
  try {
    await deleteRefunds(refundsId)
    isDeleteDialogVisible.value = false
    await loadrefunds()
    showSnackbar('la facture proforma', 'success');
  } catch (error) {
    console.error('Error deleting refunds:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  refundsToDelete.value = null
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
          Bons de remboursement
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
            <VBtn v-if="!archiveStore.isArchive" @click="exportRefund" variant="tonal" color="secondary"
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
            <VAutocomplete v-model="filterParams.selectedStatus" :items="refundStatus"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Statut"
              :clearable="isClearableStatus" clear-icon="tabler-x" placeholder="Statut" variant="outlined" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable :headers="headers" :items="refundsData.refunds" v-model:items-per-page="queryParams.itemsPerPage"
        class="elevation-1" :loading="isLoading">
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
            :to="{ name: archiveStore.isArchive ? 'archives-documents-refunds-id' : 'documents-refunds-id', params: { id: item.id } }"
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
              :to="{ name: archiveStore.isArchive ? 'archives-documents-refunds-id' : 'documents-refunds-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;"
              @click="openView(item)">
              <IconBtn @click="openView(item)">
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>

            <IconBtn v-if="hasDocumentManagerPermission &&  !archiveStore.isArchive" disabled @click="openEditrefundsDrawer(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <IconBtn :disabled="archiveStore.isArchive" v-if="hasDocumentManagerPermission &&  !archiveStore.isArchive"
              @click="openDeleteDialog(item)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="refundsData.totalrefunds" @update:page="onPageChange" />
        </template>

      </VDataTable>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible"
      message="Êtes-vous sûr de vouloir archiver cette facture proforma ?" deleteButtonText="Confirmer"
      cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeleterefunds" :onCancelClick="cancelDelete"
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
