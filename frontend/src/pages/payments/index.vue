<script setup lang="ts">
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import ModalForm from '@/components/ModalForm.vue';
import PaymentAppexDonutChart from '@/components/payments/PaymentAppexDonutChart.vue';
import PaymentForm from '@/components/payments/PaymentForm.vue';
import PaymentStatsGraph from '@/components/payments/PaymentStatsGraph.vue';
import PaymentTopSixActivitySectors from '@/components/payments/PaymentTopSixActivitySectors.vue';
import PaymentTopSixClients from '@/components/payments/PaymentTopSixClients.vue';
import { deletePayment, deleteRecovery, exportPayments, fetchArchivedPayments, fetchArchivedRecoveries, fetchPaymentById, fetchPayments, fetchRecoveries, fetchRecoveryById, fetchStaticData, PaymentStats } from '@/services/api/payment';
import { useArchiveStoreStore } from '@/stores/archive';
import { usePaymentStore } from '@/stores/payment';
import { getDefaultPayment, getDefaultPaymentFilterParams } from '@services/defaults';
import type { Payment, PaymentFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';
import { VForm } from 'vuetify/components/VForm';
import { VRow } from 'vuetify/lib/components/index.mjs';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = usePaymentStore();
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNewPaymentDrawerVisible = ref(false)

const clientBalance = ref<number | null>(0)
const recoveryBalance = ref<number | null>(0)
const invoiceAmount = ref<number | null>(0)
const orderReceiptAmount = ref<number | null>(0)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 References to the forms
const refFormAdd = ref<VForm>()

// 👉 State variable for the selected payment in edit mode
const selectedPayment = ref<Payment>(getDefaultPayment())

// 👉 State variables for image preview and file
const photo = ref<string>('')
const imageFile = ref<File | null>(null)

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the payment to delete
const paymentToDelete = ref<Payment | null>(null)

// 👉 Filters and search
const filterParams = ref<PaymentFilterParms>(getDefaultPaymentFilterParams())

const title2Render = ref('');

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1,
})

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string | boolean;
}

// 👉 State variables for the filter options (initialized with proper structure)
const filterItems = ref<{
  types: FilterItem[];
  clients: FilterItem[];
}>({
  types: [],
  clients: [],

});


// 👉 State variable for storing payments data
const paymentsData = ref<{
  payments: Payment[]
  recoveries: Payment[]
  totalPayments: number
  totalRecoveries: number
}>({
  payments: [],
  recoveries: [],
  totalPayments: 0,
  totalRecoveries: 0
})

// Centralized selected year
const selectedYear = ref<String | null>(new Date().getFullYear().toString());
const selectedClient = ref<String | null>(null);

// 👉 Modal title logic
const title1 = computed(() => store.mode === 'add' ? 'Ajouter un paiement' : 'Modifier le paiement');
const title2 = computed(() => store.mode === 'add' ? 'Ajouter un recouvrement' : 'Modifier le recouvrement');

// 👉 Handle drawer visibility update
const handleDrawerModelValueUpdate = (val: boolean) => {
  isAddNewPaymentDrawerVisible.value = val
}

// 👉 Function to fetch payments from the API
const loadPayments = async () => {
  isLoading.value = true;
  try {
    let data;
    let data2;
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;

    } else {
      if (filterParams.value.selectedPaymentType) filters.type = filterParams.value.selectedPaymentType;
      if (filterParams.value.selectedDate) filters.date = filterParams.value.selectedDate;
      if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
    }
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedPayments(filters, queryParams.value.itemsPerPage, queryParams.value.page);
      data2 = await fetchArchivedRecoveries(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    else {
      [data, data2] = await Promise.all([
        fetchPayments(filters, queryParams.value.itemsPerPage, queryParams.value.page),
        fetchRecoveries(filters, queryParams.value.itemsPerPage, queryParams.value.page)
      ]);
    }
    paymentsData.value.payments = data.payments || [];
    paymentsData.value.totalPayments = data.totalPayments || 0;
    paymentsData.value.recoveries = data2.recoveries || [];
    paymentsData.value.totalRecoveries = data2.totalRecoveries || 0;
    if (paymentsData.value.payments.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadPayments();
    }

    if (paymentsData.value.recoveries.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadPayments();
    }

  } catch (error) {
    console.error('Error fetching clients:', error);
  } finally {
    isLoading.value = false;
  }
};


const statsData = ref<{
  byClientType?: any[]
  byCity?: any[]
  bySector?: any[]
  byClient?: any[]
}>({})

// 👉 Function to fetch payments stats from the API
const loadPaymentsStats = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};
    if (selectedYear.value) filters.year = selectedYear.value;
    if (selectedClient.value) filters.client = selectedClient.value;
    const data = await PaymentStats(filters);
    statsData.value.byClientType = data.byClientType || [];
    statsData.value.byCity = data.byCity || [];
    statsData.value.bySector = data.bySector || [];
    statsData.value.byClient = data.byClient || [];

  } catch (error) {
    console.error('Error fetching payment stats:', error);
  } finally {
    isLoading.value = false;
  }
};

const exportPayment = async () => {

  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;
    } else {
      if (filterParams.value.selectedPaymentType) filters.type = filterParams.value.selectedPaymentType;
      if (filterParams.value.selectedDate) filters.date = filterParams.value.selectedDate;
      if (filterParams.value.selectedClient) filters.client = filterParams.value.selectedClient;
    }

    const response = await exportPayments(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'payments.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export payments: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting payments:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.types = mapStaticData(staticData.data.paymentType);
    filterItems.value.clients = mapStaticData(staticData.data.client);

  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadPayments()
  loadStaticData();
  loadPaymentsStats();
});

// Replace throttledLoadPayments with debouncedLoadPayments
const debouncedLoadPayments = debounce(async () => {
  await loadPayments();
}, 800);
const debouncedLoadPaymentsStats = debounce(async () => {
  await loadPaymentsStats();
}, 800);



watch(filterParams, () => {
  debouncedLoadPayments();
}, { deep: true });


// 👉 Watchers to reload payments on filter, search, pagination, or sorting change
watch([selectedYear, selectedClient], () => {
  debouncedLoadPaymentsStats();
}, { deep: true });



// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadPayments();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadPayments();
};

// 👉 clear filters
const onSearchInput = (newSearch: any) => {
  if (newSearch) {
    filterParams.value.selectedDate = null;
    filterParams.value.selectedClient = null;
    filterParams.value.selectedPaymentType = null;
  }
};

// 👉 clear search
const onFilterChange = () => {
  {
    filterParams.value.searchQuery = null;
  }
};

// 👉 Table headers definition
const headers = [
  { title: 'Paiement', key: 'code' },
  { title: 'Client', key: 'client' },
  { title: 'Montant', key: 'amount' },
  { title: 'Type', key: 'paymentType' },
  { title: 'Date', key: 'date' },
  { title: 'Document', key: 'document' },
  { title: 'Commentaire', key: 'comment' },

  ...((!archiveStore.isArchive)
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),]

const headers2 = [
  { title: 'Recouvrement', key: 'code' },
  { title: 'Client', key: 'client' },
  { title: 'Montant', key: 'amount' },
  { title: 'Type', key: 'paymentType' },
  { title: 'Date', key: 'date' },
  { title: 'Commentaire', key: 'comment' },

  ...((!archiveStore.isArchive)
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),]


// 👉 Function to close the drawer and reset the form for adding a new payment
const closeNavigationDrawer = () => {
  isAddNewPaymentDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()
  photo.value = ''
  imageFile.value = null
}

// 👉 Function to handle form submission for adding a new payment
const onSubmit = async () => {
  try {
    closeNavigationDrawer();
    await loadPayments();
    showSnackbar("l'payment", 'success');
  } catch (error) {
    console.error('Error adding payment:', error);
    closeNavigationDrawer();
    showSnackbar("l'payment", 'error');
  }
};

const openEditPaymentDrawer = async (payment: Payment) => {
  store.setPaymentType();
  const id = payment.id
  const data = await fetchPaymentById(id)
  store.setEditMode(data);
  clientBalance.value = data.clientBalance;
  invoiceAmount.value = data.invoiceAmount;
  orderReceiptAmount.value = data.orderReceiptAmount;

  isAddNewPaymentDrawerVisible.value = true
}
const openEditRecoveryDrawer = async (payment: Payment) => {
  store.setRecoveryType();
  const id = payment.id
  const data = await fetchRecoveryById(id)
  store.setEditMode(data);
  clientBalance.value = data.clientBalance;
  invoiceAmount.value = data.invoiceAmount;
  orderReceiptAmount.value = data.orderReceiptAmount;

  isAddNewPaymentDrawerVisible.value = true
}



const openAddNewPaymentDrawer = () => {
  store.setRecoveryType();
  selectedPayment.value = getDefaultPayment();
  isAddNewPaymentDrawerVisible.value = true
  store.setAddMode();

}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (payment: Payment) => {
  paymentToDelete.value = payment
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeletePayment = async () => {
  const paymentId = paymentToDelete.value?.id
  if (!paymentId) {
    console.error('Payment ID is undefined. Cannot delete payment.')
    return
  }
  try {
    await deletePayment(paymentId)
    isDeleteDialogVisible.value = false
    await loadPayments()
    showSnackbar("le paiement", 'success');
  } catch (error) {
    console.error('Error deleting payment:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  paymentToDelete.value = null
}

const isDeleteDialogVisibleRecovery = ref(false)
const recoveryToDelete = ref<Payment | null>(null)

// 👉 Function to open the delete confirmation dialog
const openDeleteDialogRecovery = (payment: Payment) => {
  recoveryToDelete.value = payment
  isDeleteDialogVisibleRecovery.value = true
}

// 👉 Function to handle delete action
const confirmDeleteRecovery = async () => {
  const recoveryId = recoveryToDelete.value?.id
  if (!recoveryId) {
    console.error('Recovery ID is undefined. Cannot delete recovery.')
    return
  }
  try {
    await deleteRecovery(recoveryId)
    isDeleteDialogVisibleRecovery.value = false
    await loadPayments()
    showSnackbar("le paiement", 'success');
  } catch (error) {
    console.error('Error deleting recovery:', error)
    isDeleteDialogVisibleRecovery.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDeleteRecovery = () => {
  isDeleteDialogVisibleRecovery.value = false
  recoveryToDelete.value = null
}

const isClearableDate = computed(() => {
  return filterParams.value.selectedDate !== null;
});
const isClearableClient = computed(() => {
  return filterParams.value.selectedClient !== null;
});
const isClearableType = computed(() => {
  return filterParams.value.selectedPaymentType !== null;
});


// Update selectedYear when event is received
const handleYearChange = (year: any) => {
  selectedYear.value = year;
};
// Update selectedClient when event is received
const handleClientChange = (client: any) => {
  selectedClient.value = client;
};

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
          Paiements
        </h4>
      </div>
      <div>
        <VBtn v-if="!archiveStore.isArchive" prepend-icon="tabler-plus" @click="openAddNewPaymentDrawer">
          Ajouter un recouvrement
        </VBtn>
      </div>
    </div>

    <!-- 👉 Graph -->
    <VRow class="mb-6" v-if="!archiveStore.isArchive">
      <VCol cols="12">
        <PaymentStatsGraph :selectedYear="selectedYear" :selectedClient="selectedClient"
          @update:selectedYear="handleYearChange" @update:selectedClient="handleClientChange" />
      </VCol>

      <VCol cols="12" md="4">
        <PaymentTopSixClients :statsData="statsData?.byClient || []" />
      </VCol>

      <!-- 👉 AppexDonutChart -->
      <VCol cols="12" md="4">
        <PaymentAppexDonutChart :statsData="statsData?.byClientType || []" />
      </VCol>

      <!-- 👉 PaymentTopSixStats -->
      <VCol cols="12" md="4">
        <PaymentTopSixActivitySectors :statsData="statsData?.bySector || []" />
      </VCol>
    </VRow>

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
              { value: 100, title: '100' }
            ]" style="inline-size: 6.25rem;" @update:model-value="onItemsPerPageChange" />

            <!-- Export Button -->
            <VBtn v-if="!archiveStore.isArchive" @click="exportPayment" variant="tonal" color="secondary"
              prepend-icon="tabler-upload">
              Exporter
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <!-- Second Row: Search, Filter 1, and Filter 2 -->
      <VCardText>
        <VRow>
          <!-- Search Field -->
          <VCol cols="12" sm="4">
            <VTextField v-model="filterParams.searchQuery" @input="onSearchInput" placeholder="Recherche"
              variant="outlined" label="Recherche" />
          </VCol>

          <VCol cols="12" sm="4">
            <VAutocomplete v-model="filterParams.selectedClient" placeholder="Client" label="Clients"
              :items="filterItems.clients" item-title="text" item-value="value" @update:modelValue="onFilterChange"
              :clearable="isClearableClient" clear-icon="tabler-x" variant="outlined" />
          </VCol>
          <VCol cols="12" sm="4">
            <VAutocomplete v-model="filterParams.selectedPaymentType" placeholder="Type" label="Types"
              :items="filterItems.types" item-title="text" item-value="value" @update:modelValue="onFilterChange"
              :clearable="isClearableType" clear-icon="tabler-x" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <AppDateTimePicker @update:modelValue="onFilterChange" v-model="filterParams.selectedDate"
              placeholder="Date" :config="{ mode: 'range' }" :clearable="isClearableDate" clear-icon="tabler-x" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 Data Table -->

    <VCard>
      <VCol cols="12" md="4">
        <VCardTitle class="pa-0">Paiements</VCardTitle>
      </VCol>
      <VDataTable v-model:items-per-page="queryParams.itemsPerPage" :items="paymentsData.payments" :headers="headers"
        item-value="id" class="text-no-wrap" :loading="isLoading" locale="fr">

        <template #loading>

          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>
        <template #item.date="{ item }">
          {{ item.date ? formatDateToddmmYYYY(String(item.date)) : '-' }}
        </template>

        <template #item.client="{ item }">
          <span class="text-body-1 text-truncate">
            {{ typeof (item as any).client === 'object' ? (item as any).client?.legalName : item.client }}
          </span>
        </template>

        <template #item.document="{ item }">
          <span class="text-body-1 text-truncate">
            <template v-if="item?.orderReceiptId || item?.invoiceId">
              <RouterLink
                :to="{ name: item?.orderReceiptId ? 'documents-orderreceipt-id' : 'documents-invoices-id', params: { id: (item?.orderReceiptId || item?.invoiceId) as string } }"
                class="text-link d-inline-block" style="line-height: 1.375rem;">
                {{ item?.orderReceiptId ? 'Reçu de commande' : 'Facture' }}
              </RouterLink>
            </template>
          </span>
        </template>

        <template #item.paymentType="{ item }">
          {{ typeof (item as any).paymentType === 'object' ? (item as any).paymentType?.name : item.paymentType }}
        </template>

        <template #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <IconBtn v-if="!archiveStore.isArchive" @click="openEditPaymentDrawer(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <IconBtn v-if="!archiveStore.isArchive" @click="openDeleteDialog(item)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="paymentsData.totalPayments" @update:page="onPageChange" locale="fr" />
        </template>
      </VDataTable>
    </VCard>

    <v-spacer class="my-6"></v-spacer>
    <!--Recovery table -->

    <VCard>
      <VCol cols="12" md="4">
        <VCardTitle class="pa-0">Recouvrements</VCardTitle>
      </VCol>
      <VDataTable v-model:items-per-page="queryParams.itemsPerPage" :items="paymentsData.recoveries" :headers="headers2"
        item-value="id" class="text-no-wrap" :loading="isLoading" locale="fr">

        <template #loading>

          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>
        <template #item.date="{ item }">
          {{ item.date ? formatDateToddmmYYYY(String(item.date)) : '-' }}
        </template>

        <template #item.client="{ item }">
          <span class="text-body-1 text-truncate">
            {{ typeof (item as any).client === 'object' ? (item as any).client?.legalName : item.client }}
          </span>
        </template>


        <template #item.paymentType="{ item }">
          {{ typeof (item as any).paymentType === 'object' ? (item as any).paymentType?.name : item.paymentType }}
        </template>

        <template #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <IconBtn v-if="!archiveStore.isArchive" @click="openEditRecoveryDrawer(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <IconBtn v-if="!archiveStore.isArchive" @click="openDeleteDialogRecovery(item)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="paymentsData.totalRecoveries" @update:page="onPageChange" locale="fr" />
        </template>
      </VDataTable>
    </VCard>


    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce paiement ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeletePayment" :onCancelClick="cancelDelete"
      @update:isVisible="isDeleteDialogVisible = $event" />
    <!-- Delete Confirmation Dialog  Recovery -->
    <AlertDialog :isVisible="isDeleteDialogVisibleRecovery" message="Êtes-vous sûr de vouloir archiver ce paiement ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeleteRecovery" :onCancelClick="cancelDeleteRecovery"
      @update:isVisible="isDeleteDialogVisibleRecovery = $event" />

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
          {{ store.type === 'recovery' ? title2 : title1 }}
        </h4>
        <p v-if="title2Render" class="text-body-1 text-center mb-6">
          Solde :
          <span :style="{ color: (clientBalance ?? 0) >= 0 ? 'green' : 'red' }">
            {{ clientBalance }} DH
          </span>
        </p>
              <p v-else-if="recoveryBalance !== null && recoveryBalance !== 0" class="text-body-1 text-center mb-6">
          Solde :
          <span :style="{ color: recoveryBalance >= 0 ? 'green' : 'red' }">
            {{ recoveryBalance }} DH
          </span>
          <br />
          </p>
      </template>
      <!-- Form slot -->
      <template #form>
        <PaymentForm :mode="store.mode" @close="closeNavigationDrawer" @submit="onSubmit"
          :clientBalanceProps="clientBalance" :invoiceAmount="invoiceAmount" :orderReceiptAmount="orderReceiptAmount"
          @update:title2="title2Render = $event" @date-picker-state="handleDatePickerState"
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
