<script setup lang="ts">
import TablePagination from '@/@core/components/TablePagination.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import { router } from '@/plugins/1.router';
import { deleteOrderReceipt, exportOrderReceipt, fetchArchivedOrderReceipt, fetchOrderReceipt, fetchOrderReceiptById, fetchStaticData } from '@/services/api/orderrec';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useDocumentCoreStore } from '@/stores/documents';
import { usePaymentStore } from '@/stores/payment';
import { getDefaultOrderReceipt, getDefaultOrderReceiptFilterParams } from '@services/defaults';
import type { OrderReceipt, OrderReceiptFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';
import { VForm } from 'vuetify/lib/components/index.mjs';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNeworderreceiptDrawerVisible = ref(false)

const OrderReceiptStatus = ['Brouillon', 'Payé', 'Non payé', 'Payé partiellement', 'Terminé']


// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 State variable for the selected orderreceipt in edit mode
const selectedorderreceipt = ref<OrderReceipt>(getDefaultOrderReceipt())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the orderreceipt to delete
const orderreceiptToDelete = ref<OrderReceipt | null>(null)

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<OrderReceiptFilterParms>(getDefaultOrderReceiptFilterParams())

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

// 👉 State variable for storing orderreceipts data
const orderreceiptData = ref<{
  orderreceipt: OrderReceipt[]
  totalorderreceipt: number
}>({
  orderreceipt: [],
  totalorderreceipt: 0,
})

// 👉 Store call
const storePayement = usePaymentStore();

// 👉 State variables for controlling drawer visibility
const isAddNewPaymentDrawerVisible = ref(false)

// 👉 References to the forms
const refFormAdd = ref<VForm>()


const clientBalance = ref<number | null>(0)
const recoveryBalance = ref<number | null>(0)
const orderReceiptAmount = ref<number | string | null>(0);
const payedAmount = ref<any | null>(0)
const totalToPay = ref<any | null>(0)



// 👉 Function to fetch orderreceipts from the API
const loadorderreceipt = async () => {
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
      if (filterParams.value.selectedDate) filters.date = filterParams.value.selectedDate;
    }
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedOrderReceipt(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    }
    else {
      data = await fetchOrderReceipt(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    orderreceiptData.value.orderreceipt = data.orderReceipt.data || [];
    orderreceiptData.value.totalorderreceipt = data.totalOrderReceipt || 0;
    if (orderreceiptData.value.orderreceipt.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadorderreceipt();
    }

  } catch (error) {
    console.error('Error fetching orderreceipt:', error);
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

const exportOrderreceipt = async () => {

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
      if (filterParams.value.selectedDate) filters.date = filterParams.value.selectedDate;

    }
    const response = await exportOrderReceipt(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'orderreceipt.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export orderreceipt: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting orderreceipt:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};


// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadorderreceipt();
  loadStaticData();
});

// 👉 Replace throttledLoadOrderReceipts with debouncedLoadOrderReceipts
const debouncedLoadOrderReceipts = debounce(async () => {
  await loadorderreceipt();
}, 800);

// 👉 Watchers to reload orderreceipts on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadOrderReceipts();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadorderreceipt();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadorderreceipt();
};

// 👉 clear filters
const onSearchInput = (newSearch: string) => {
  if (newSearch) {
    filterParams.value = {
      ...getDefaultOrderReceiptFilterParams(),
      searchQuery: newSearch
    };
  }
};

// 👉 clear search
const onFilterChange = () => {
  if (filterParams.value.selectedClient || filterParams.value.selectedUser || filterParams.value.selectedStatus || filterParams.value.selectedDate) {
    filterParams.value.searchQuery = '';
  }
};

// 👉 Table headers definition
const headers = [
  { title: 'N° Doc', key: 'code' },
  { title: 'Client', key: 'client' },
  { title: 'Total Payé', key: 'payedAmount' },
  { title: 'Date', key: 'date' },
  { title: 'Statut', key: 'status' },
  ...((!archiveStore.isArchive)
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),]

const openView = (orderReceipt: OrderReceipt) => {
  store.setEditMode(orderReceipt);
  store.setPreviewMode();

}


const clearPaymentData = () => {
  clientBalance.value = 0
  recoveryBalance.value = 0
  orderReceiptAmount.value = 0
  payedAmount.value = 0
  totalToPay.value = 0
  title2Render.value = '';
}

// 👉 Function to open the drawer for adding a new payment
const openAddNewPaymentDrawer = async (orderReceipt: OrderReceipt) => {
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()
  // clearPaymentData();
  isAddNewPaymentDrawerVisible.value = true
  store.setEditMode(orderReceipt);
  store.setDocType('orderreceipt');
  // Set client data in payment store for pre-filling
  storePayement.setAddMode();
  storePayement.setPaymentType()
  if (storePayement.selectedPayment && orderReceipt.client) {
    storePayement.selectedPayment.clientId = orderReceipt.client.id;
    storePayement.selectedPayment.client = orderReceipt.client.legalName;
  }

  clientBalance.value = orderReceipt.client?.balance || 0;
  orderReceiptAmount.value = orderReceipt.finalAmount || 0;
  payedAmount.value = orderReceipt.payedAmount || 0;
  totalToPay.value = orderReceipt.totalToPay || 0;
  recoveryBalance.value = 0;

}

// 👉 Handle drawer visibility update
const handleDrawerModelValueUpdate = (val: boolean) => {
  isAddNewPaymentDrawerVisible.value = val
}

// 👉 Function to close the drawer and reset the form for adding a new payment
const closeNavigationDrawer = () => {
  isAddNewPaymentDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()
  clearPaymentData();
}

// 👉 Modal title logic
const title1 = computed(() => storePayement.mode === 'add' ? 'Ajouter des informations de paiement' : 'Modifier les informations de paiement');
const title2Render = ref('');

// 👉 Function to handle form submission for adding a new payment
const onSubmit = async () => {
  try {
    await closeNavigationDrawer();
    loadorderreceipt();
    // Show success snackbar
    showSnackbar("l'utilisateur", 'success');
  } catch (error) {
    console.error('Error adding payment:', error);
    // Show error snackbar
    closeNavigationDrawer();
    showSnackbar("l'utilisateur", 'error');
  }
};

// 👉 Opens the drawer for editing a orderreceipt.
const openEditorderreceiptDrawer = async (orderreceipt: OrderReceipt) => {
  const id = orderreceipt.id
  const data = await fetchOrderReceiptById(id)
  store.setEditMode(data);
  await router.push('/documents/orderreceipt/form');

}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (orderreceipt: OrderReceipt) => {
  orderreceiptToDelete.value = orderreceipt
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleteorderreceipt = async () => {
  const orderreceiptId = orderreceiptToDelete.value?.id
  if (!orderreceiptId) {
    console.error('orderreceipt ID is undefined. Cannot delete orderreceipt.')
    return
  }
  try {
    await deleteOrderReceipt(orderreceiptId)
    isDeleteDialogVisible.value = false
    await loadorderreceipt()
    showSnackbar('le reçus de commande', 'success');
  } catch (error) {
    console.error('Error deleting orderreceipt:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  orderreceiptToDelete.value = null
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

const isClearableDate = computed(() => {
  return filterParams.value.selectedDate !== null;
});

const hasRequiredPermissions = (permissions: string[]): boolean => {
  const authStore = useAuthStore();
  return permissions.every((permission) => authStore.permissions.includes(permission));

};

// 👉 Define the required permissions for this component
const requiredPermissionsDocs = ['document_manager'];
const requiredPermissionsPayment = ['document_manager'];


// 👉 Computed or reactive property to check the permissions
const hasDocumentManagerPermission = hasRequiredPermissions(requiredPermissionsDocs);
const hasPaymentManagerPermission = hasRequiredPermissions(requiredPermissionsPayment);


const isDatePickerOpen = ref(false);

const handleDatePickerState = (isOpen: any) => {
  isDatePickerOpen.value = isOpen;
};
</script>

<template>
  <section>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Reçus de commande
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
            <VBtn v-if="!archiveStore.isArchive" @click="exportOrderreceipt" variant="tonal" color="secondary"
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
            <VAutocomplete v-model="filterParams.selectedStatus" :items="OrderReceiptStatus"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Status"
              :clearable="isClearableStatus" clear-icon="tabler-x" placeholder="Status" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <AppDateTimePicker v-model="filterParams.selectedDate" @update:modelValue="onFilterChange"
              placeholder="Date" :clearable="isClearableDate" clear-icon="tabler-x" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable :headers="headers" :items="orderreceiptData.orderreceipt"
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
            :to="{ name: archiveStore.isArchive ? 'archives-documents-orderreceipts-id' : 'documents-orderreceipt-id', params: { id: item.id } }"
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

        <template #item.balance="{ item }">
          <span v-if="item.client">
            {{ item.client.balance || '0.00' }}
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
              :to="{ name: archiveStore.isArchive ? 'archives-documents-orderreceipts-id' : 'documents-orderreceipt-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;"
              @click="openView(item)">
              <IconBtn @click="openView(item)">
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>

            <IconBtn :disabled="archiveStore.isArchive"
              v-if="hasDocumentManagerPermission && hasPaymentManagerPermission && !archiveStore.isArchive"
              @click="openAddNewPaymentDrawer(item)">
              <VIcon icon="tabler-brand-cashapp" />
            </IconBtn>

            <IconBtn v-if="hasDocumentManagerPermission && !archiveStore.isArchive"
              :disabled="(item.status ? ['Validé', 'Annulé', 'Terminé'].includes(item.status) : false) || archiveStore.isArchive"
              @click="openEditorderreceiptDrawer(item)">
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
            :totalItems="orderreceiptData.totalorderreceipt" @update:page="onPageChange" />
        </template>

      </VDataTable>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce reçus de commande ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeleteorderreceipt" :onCancelClick="cancelDelete"
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
        <p v-if="title2Render" class="text-body-1 text-center mb-6">
          Solde :
          <span :style="{ color: (clientBalance ?? 0) >= 0 ? 'green' : 'red' }">
            {{ clientBalance }} DH
          </span>
          <br />
          Total reçu de commande :
          <span>
            {{ orderReceiptAmount }} DH
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
        <p v-else-if="recoveryBalance !== null && recoveryBalance !== 0" class="text-body-1 text-center mb-6">
          Solde :
          <span :style="{ color: (recoveryBalance ?? 0) >= 0 ? 'green' : 'red' }">
            {{ recoveryBalance }} DH
          </span>
          <br />
          Total reçu de commande :
          <span>
            {{ orderReceiptAmount }} DH
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
        <PaymentForm :mode="store.mode" :selected-payment="storePayement.selectedPayment" @close="closeNavigationDrawer"
          @submit="onSubmit" @update:title2="title2Render = $event" :clientBalanceProps="clientBalance"
          :orderReceiptAmount="orderReceiptAmount" @date-picker-state="handleDatePickerState"
          @update:recoveryBalance="recoveryBalance = $event" />
      </template>
    </ModalForm>
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
