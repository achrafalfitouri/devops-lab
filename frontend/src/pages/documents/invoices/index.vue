<script setup lang="ts">
import TablePagination from '@/@core/components/TablePagination.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import { router } from '@/plugins/1.router';
import { deleteInvoices, exportInvoices, fetchArchivedInvoices, fetchInvoices, fetchInvoicesById, fetchStaticData } from '@/services/api/invoice';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useDocumentCoreStore } from '@/stores/documents';
import { usePaymentStore } from '@/stores/payment';
import { getDefaultInvoices, getDefaultInvoicesFilterParams } from '@services/defaults';
import type { Invoices, InvoicesFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';
import { VForm } from 'vuetify/components/VForm';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = useDocumentCoreStore();
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNewinvoicesDrawerVisible = ref(false)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 State variable for the selected invoices in edit mode
const selectedinvoices = ref<Invoices>(getDefaultInvoices())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the invoices to delete
const invoicesToDelete = ref<Invoices | null>(null)

const invoiceStatus = ['Brouillon', 'Payé', 'Non payé', 'Payé partiellement', 'Terminé']


// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<InvoicesFilterParms>(getDefaultInvoicesFilterParams())

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

// 👉 State variable for storing invoicess data
const invoicesData = ref<{
  invoices: Invoices[]
  totalinvoices: number
}>({
  invoices: [],
  totalinvoices: 0,
})

// 👉 Store call
const storePayement = usePaymentStore();

// 👉 State variables for controlling drawer visibility
const isAddNewPaymentDrawerVisible = ref(false)

// 👉 References to the forms
const refFormAdd = ref<VForm>()

// 👉 Function to close the drawer and reset the form for adding a new payment
const closeNavigationDrawer = () => {
  isAddNewPaymentDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()
  clearPaymentData()


}

const clientBalance = ref<number | null>(0)
const invoiceAmount = ref<any | null>(0)
const payedAmount = ref<any | null>(0)
const totalToPay = ref<any | null>(0)
const recoveryBalance = ref<number | null>(0)


const clearPaymentData = () => {
  clientBalance.value = 0
  invoiceAmount.value = 0
  payedAmount.value = 0
  totalToPay.value = 0
  title2Render.value = '';
  recoveryBalance.value = 0;
}

// 👉 Function to open the drawer for adding a new payment
const openAddNewPaymentDrawer = async (invoices: Invoices) => {
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()
  // clearPaymentData();
  isAddNewPaymentDrawerVisible.value = true
  storePayement.setAddMode();
  storePayement.setPaymentType()
  store.setDocType('invoice');
  store.setEditMode(invoices);
  if (storePayement.selectedPayment) {
    storePayement.selectedPayment.client = invoices.client?.legalName || '';
    storePayement.selectedPayment.clientId = invoices.clientId;
  }

  clientBalance.value = invoices.client?.balance || 0;
  invoiceAmount.value = invoices.finalAmount || 0;
  payedAmount.value = invoices.payedAmount || 0;
  totalToPay.value = invoices.totalToPay || 0;
  recoveryBalance.value = 0;


}

// 👉 Handle drawer visibility update
const handleDrawerModelValueUpdate = (val: boolean) => {
  isAddNewPaymentDrawerVisible.value = val
}

// 👉 Modal title logic
const title1 = computed(() => storePayement.mode === 'add' ? 'Ajouter des informations de paiement' : 'Modifier les informations de paiement');
const title2Render = ref('');

// 👉 Function to handle form submission for adding a new payment
const onSubmit = async () => {
  try {
    await closeNavigationDrawer();
    loadinvoices();
    // Show success snackbar
    showSnackbar("l'utilisateur", 'success');
  } catch (error) {
    console.error('Error adding payment:', error);
    // Show error snackbar
    closeNavigationDrawer();
    showSnackbar("l'utilisateur", 'error');
  }
};

// 👉 Function to fetch invoicess from the API
const loadinvoices = async () => {
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
      if (filterParams.value.selectedDate) filters.date = filterParams.value.selectedDate;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
    }
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedInvoices(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    }
    else {
      data = await fetchInvoices(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    invoicesData.value.invoices = data.invoices.data || [];
    invoicesData.value.totalinvoices = data.totalInvoices || 0;
    if (invoicesData.value.invoices.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadinvoices();
    }

  } catch (error) {
    console.error('Error fetching invoices:', error);
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

const exportInvoice = async () => {
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
    const response = await exportInvoices(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'invoices.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export invoices: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting invoices:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};


// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadinvoices();
  loadStaticData();
});

// 👉 Replace throttledLoadInvoicess with debouncedLoadInvoicess
const debouncedLoadInvoicess = debounce(async () => {
  await loadinvoices();
}, 800);

// 👉 Watchers to reload invoicess on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadInvoicess();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadinvoices();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadinvoices();
};

// 👉 clear filters
const onSearchInput = (newSearch: string) => {
  if (newSearch) {
    filterParams.value = {
      ...getDefaultInvoicesFilterParams(),
      searchQuery: newSearch
    };
  }
};

// 👉 clear search
const onFilterChange = () => {
  if (filterParams.value.selectedClient || filterParams.value.selectedUser || filterParams.value.selectedDate || filterParams.value.selectedStatus) {
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

// 👉 Opens the drawer for editing a invoices.
const openEditinvoicesDrawer = async (invoices: Invoices) => {
  const id = invoices.id
  const data = await fetchInvoicesById(id)
  store.setEditMode(data);
  await router.push('/documents/invoices/form');

}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (invoices: Invoices) => {
  invoicesToDelete.value = invoices
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleteinvoices = async () => {
  const invoicesId = invoicesToDelete.value?.id
  if (!invoicesId) {
    console.error('invoices ID is undefined. Cannot delete invoices.')
    return
  }
  try {
    await deleteInvoices(invoicesId)
    isDeleteDialogVisible.value = false
    await loadinvoices()
    showSnackbar('la facture', 'success');
  } catch (error) {
    console.error('Error deleting invoices:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  invoicesToDelete.value = null
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

const isClearableStatus = computed(() => {
  return filterParams.value.selectedStatus !== null;
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
          Factures
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
            <VBtn v-if="!archiveStore.isArchive" @click="exportInvoice" variant="tonal" color="secondary"
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
            <VAutocomplete v-model="filterParams.selectedStatus" :items="invoiceStatus"
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
      <VDataTable :headers="headers" :items="invoicesData.invoices" v-model:items-per-page="queryParams.itemsPerPage"
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

        <template #item.code="{ item }">

          <RouterLink
            :to="{ name: archiveStore.isArchive ? 'archives-documents-invoices-id' : 'documents-invoices-id', params: { id: item.id } }"
            class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
            {{ item.code }}
          </RouterLink>
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
              :to="{ name: archiveStore.isArchive ? 'archives-documents-invoices-id' : 'documents-invoices-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              <IconBtn>
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>

            <IconBtn :disabled="archiveStore.isArchive"
              v-if="hasDocumentManagerPermission && hasPaymentManagerPermission && !archiveStore.isArchive"
              @click="openAddNewPaymentDrawer(item)">
              <VIcon icon="tabler-brand-cashapp" />
            </IconBtn>
            <IconBtn v-if="hasPaymentManagerPermission && !archiveStore.isArchive"
              :disabled="(item.status ? ['Validé', 'Annulé', 'Terminé'].includes(item.status) : false) || archiveStore.isArchive"
              @click="openEditinvoicesDrawer(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <IconBtn :disabled="archiveStore.isArchive" v-if="hasPaymentManagerPermission && !archiveStore.isArchive"
              @click="openDeleteDialog(item)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>


        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="invoicesData.totalinvoices" @update:page="onPageChange" />
        </template>

      </VDataTable>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver cette facture ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeleteinvoices" :onCancelClick="cancelDelete"
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
          Total facture :
          <span>
            {{ invoiceAmount }} DH
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
          Total facture :
          <span>
            {{ invoiceAmount }} DH
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
          :invoiceAmount="invoiceAmount" @date-picker-state="handleDatePickerState"
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
