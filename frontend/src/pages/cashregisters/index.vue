<script setup lang="ts">
import DialogCloseBtn from '@/@core/components/DialogCloseBtn.vue';
import TablePagination from '@/@core/components/TablePagination.vue';
import ModalForm from '@/components/ModalForm.vue';
import CashRegisterStats from '@/components/cash registers/CashRegisterStats.vue';
import CashRegistersGragh from '@/components/cash registers/CashRegistersGragh.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import TransactionForm from '@/components/transactions/TransactionForm.vue';
import CashRegisterUser from '@/components/users/CashRegisterUser.vue';
import { getAllCashRegistersAndTransactions, getArchivedAllCashRegistersAndTransactions, updateStatusCashregister } from '@/services/api/cashregister';
import { deleteTransaction, exportTransactions, fetchStaticData, fetchTransactionById, getAllTransactions, getArchivedAllTransactions } from '@/services/api/transaction';
import { useArchiveStoreStore } from '@/stores/archive';
import { useAuthStore } from '@/stores/auth';
import { useCashRegisterStore } from '@/stores/cashregister';
import { useTransactionStore } from '@/stores/transaction';
import { getDefaultCashRegister, getDefaultCashRegisterFilterParams, getDefaultTransaction, getDefaultTransactionFilterParams } from '@services/defaults';
import type { CashRegister, CashRegisterFilterParms, Transaction, TransactionFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';
import { VForm } from 'vuetify/components/VForm';
import { VRow } from 'vuetify/lib/components/index.mjs';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

const apiUrl = "https://print-backend-devops-production.up.railway.app/api"
const baseUrl = apiUrl.replace(/\/api\/?$/, '')

// 👉 Store call
const store = {
  transaction: useTransactionStore(),
  cashregister: useCashRegisterStore(),
  auth: useAuthStore()
};
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNewTransactionDrawerVisible = ref(false)
const isAddNewCashRegisterDrawerVisible = ref(false)
const isToggleCashRegisterDrawerVisible = ref(false)

// 👉 State variables for selected Filter
const selectedFilter = ref(false);

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 References to the forms
const refFormAdd = ref<VForm>()

// 👉 State variable for the selected transaction and cash register  in edit mode
const selectedTransaction = ref<Transaction>(getDefaultTransaction())
const selectedCashRegister = ref<CashRegister>(getDefaultCashRegister())

// 👉 State for dialog visibility
const dialogState = ref<{
  isVisible: boolean,
  message: string,
  onConfirm: () => void,
  onCancel: () => void,
  data: any // You can replace 'any' with a more specific type if known
}>({
  isVisible: false,
  message: '',
  onConfirm: () => { },
  onCancel: () => { },
  data: null
})

// 👉 State for storing the transaction and cash register to delete
const transactionToDelete = ref<Transaction | null>(null)
const cashregisterToDelete = ref<CashRegister | null>(null)
const cashregisterToUpdate = ref<CashRegister | null>(null)

// 👉 State for storing cash registers
const cashRegisters = ref([]);
const cashRegistersFetch = ref([]);

const hasRequiredPermissions = (permissions: string[]): boolean => {
  const authStore = useAuthStore();
  return permissions.every((permission) => authStore.permissions.includes(permission));
};

// 👉 Define the required permissions for this component
const requiredPermissionsCr = ['cashregister_manager'];
const requiredPermissionsCrViewer = ['cashregister_viewer'];
const requiredPermissionsTr = ['transaction_manager'];
const requiredPermissionsTrViewer = ['transaction_viewer'];

// 👉 Computed or reactive property to check the permissions
const hasCashRegisterManagerPermission = hasRequiredPermissions(requiredPermissionsCr);
const hasTransactionManagerPermission = hasRequiredPermissions(requiredPermissionsTr);
const hasCashRegisterViewerPermission = hasRequiredPermissions(requiredPermissionsCrViewer);
const hasTransactionViewerPermission = hasRequiredPermissions(requiredPermissionsTrViewer);




// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<TransactionFilterParms>(getDefaultTransactionFilterParams())
const filterParamsCash = ref<CashRegisterFilterParms>(getDefaultCashRegisterFilterParams())

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
}

// 👉 Explicitly define the type for filterItems
const filterItems = ref<{
  types: FilterItem[];
  cashregisters: FilterItem[];
  cashregisterFilter: FilterItem[];
  users: FilterItem[];
}>({
  types: [],
  cashregisters: [],
  cashregisterFilter: [],
  users: []

});

// 👉 State variable for storing transactions data
const transactionsData = ref<{
  transactions: Transaction[]
  totaltransactions: number
}>({
  transactions: [],
  totaltransactions: 0,
})
const cashRegistersDataTable = ref<{
  cashregisters: CashRegister[],
  cashregisterstb: CashRegister[],
  totalcashregisters: number,
  totalcashregisterstb: number
}>({
  cashregisters: [],
  totalcashregisters: 0,
  cashregisterstb: [],
  totalcashregisterstb: 0
})

// 👉 Modal title logic
const titles = {
  title1: computed(() => store.transaction.mode === 'add' ? 'Ajouter des informations de transaction' : 'Modifier les informations de transaction'),
  title3: computed(() => store.cashregister.mode === 'add' ? 'Ajouter des informations de caisse' : 'Modifier les informations de caisse'),
};

// 👉 Handle drawer visibility update
const handleDrawerModelValueUpdate = (val: boolean) => {
  isAddNewTransactionDrawerVisible.value = val
}

const loadCashRegistersTb = async () => {
  isLoading.value = true;

  try {
    let data;
    const filters: any = {};

    if (filterParamsCash.value.selectedCashRegister) {
      filters.cash = filterParamsCash.value.selectedCashRegister;
    }
    if (filterParamsCash.value.selectedDate) {
      filters.dateRange = filterParamsCash.value.selectedDate;
    }
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await getArchivedAllCashRegistersAndTransactions(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    else {
      data = await getAllCashRegistersAndTransactions(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    cashRegisters.value = data || [];
    cashRegistersDataTable.value.cashregisters = data.cashRegisters || [];
    cashRegistersDataTable.value.cashregisterstb = data.cashRegisters || [];
    cashRegistersDataTable.value.totalcashregisters = data.totalCashRegisters || 0;
    cashRegistersDataTable.value.totalcashregisterstb = data.totalCashRegisters || 0;
    cashRegistersFetch.value = data.cashRegisters || [];

  } catch (error) {
    console.error('Error fetching cash registers:', error);
  }
  finally {
    isLoading.value = false;
  }
};


const loadTransactions = async () => {
  isLoading.value = true;

  try {
    let data;
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;
    } else {
      if (filterParams.value.selectedTransactionType) {
        filters.transactionType = filterParams.value.selectedTransactionType;
      }
      if (filterParams.value.selectedCashRegister) {
        filters.cash = filterParams.value.selectedCashRegister;
      }
      if (filterParams.value.range) {
        filters.range = filterParams.value.range;
      }
      if (filterParams.value.selectedUser) {
        filters.user = filterParams.value.selectedUser;
      }
    }

    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await getArchivedAllTransactions(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    }
    else {
      data = await getAllTransactions(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }

    transactionsData.value.transactions = data.transactions || [];
    transactionsData.value.totaltransactions = data.totalTransactions || 0;
  } catch (error) {
    console.error('Error fetching cash registers:', error);
  }
  finally {
    isLoading.value = false;
  }
};

const filterCrManager = ref<FilterItem[]>([])
// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.types = mapStaticData(staticData.data.transactionTypeFilter);
    filterItems.value.cashregisterFilter = mapStaticData(staticData.data.cashRegisterFilter
    );
    filterItems.value.cashregisters = mapStaticData(staticData.data.cashRegister
    );
    filterItems.value.users = mapStaticData(staticData.data.user
    );
    filterCrManager.value = hasCashRegisterManagerPermission ? filterItems.value.cashregisters : filterItems.value.cashregisterFilter;
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

const exportTransaction = async () => {

  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;
    } else {
      if (filterParams.value.selectedTransactionType) {
        filters.transactionType = filterParams.value.selectedTransactionType;
      }
      if (filterParams.value.selectedCashRegister) {
        filters.cash = filterParams.value.selectedCashRegister;
      }
      if (filterParams.value.range) {
        filters.range = filterParams.value.range;
      }
      if (filterParams.value.selectedUser) {
        filters.user = filterParams.value.selectedUser;
      }
    }
    const response = await exportTransactions(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'transactions.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export transactions: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting transactions:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadStaticData();
  // loadCashRegisters();
  loadTransactions();
  loadCashRegistersTb();
});

// 👉 Replace throttledLoadTransactions with debouncedLoadTransactions
const debouncedLoadTransactions = debounce(async () => {
  await loadTransactions();

}, 800);

// 👉 Watchers to reload transactions on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadTransactions();
}, { deep: true });

watch(filterParamsCash, () => {
  loadCashRegistersTb();
}, { deep: true });


// 👉 Handle the event from the child and update the filterParams
const updateFilterParams = (newParams: any) => {
  Object.assign(filterParamsCash.value, newParams);
  selectedFilter.value = !!newParams.selectedCashRegister;

};

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadTransactions();

};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadTransactions();


};

// 👉 clear filters
const onSearchInput = (newSearch: string) => {
  if (newSearch) {
    filterParams.value = {
      ...getDefaultTransactionFilterParams(),
      searchQuery: newSearch
    };
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
  { title: 'Utilisateur', key: 'user' },
  { title: 'Caisse', key: 'caisse' },
  { title: 'Type', key: 'cashTransactionType' },
  { title: 'Émetteur/Récepteur', key: 'name' },
  { title: 'Date', key: 'date' },
  { title: 'Montant', key: 'amount' },
  { title: 'Commentaire', key: 'comment' },

  ...((hasCashRegisterManagerPermission || hasTransactionManagerPermission) && !archiveStore.isArchive
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),
]

const headers2 = [
  { title: 'Caisse', key: 'name' },
  { title: 'Solde', key: 'balance' },
  { title: 'Caissiers', key: 'managedBy' },
  { title: 'Statut', key: 'status' },
  ...((hasCashRegisterManagerPermission && !archiveStore.isArchive)
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),]

// 👉 Functions to open the drawer for adding a new transaction and cash register
const openAddNewtransactionDrawer = () => {
  selectedTransaction.value = getDefaultTransaction()
  isAddNewTransactionDrawerVisible.value = true
  store.transaction.setAddMode();
}

const openAddNewCashRegisterDrawer = () => {
  selectedCashRegister.value = getDefaultCashRegister()
  isAddNewCashRegisterDrawerVisible.value = true
  store.cashregister.setAddMode();
}

// 👉 Functions to close the drawer and reset the form for adding a new transaction and cash register
const closeNavigationDrawer = () => {
  isAddNewTransactionDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()

}

const closeNavigationDrawerCashRegister = () => {
  isAddNewCashRegisterDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()

}
const closeNavigationDrawerCashRegisterUser = () => {
  isToggleCashRegisterDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()

}

// 👉 Functions to handle form submission for adding a new transaction and cash register
const onSubmitTransaction = async () => {
  try {
    closeNavigationDrawer();
    await loadTransactions();
    await loadCashRegistersTb();
    await loadStaticData();
    showSnackbar('la transaction', 'success');
  } catch (error) {
    console.error('Error adding transaction:', error);
    closeNavigationDrawer();
    showSnackbar('la transaction', 'error');
  }
};

const onSubmitCashRegister = async () => {
  try {
    closeNavigationDrawerCashRegister();
    await loadCashRegistersTb();
    await loadStaticData();
    showSnackbar('le cash register', 'success');
  } catch (error) {
    console.error('Error adding cash register:', error);
    closeNavigationDrawerCashRegister();
    showSnackbar('le cash register', 'error');
  }
};

const onSubmit = async () => {
  try {
    closeNavigationDrawerCashRegister();
    await loadCashRegistersTb();
    await loadStaticData();
    showSnackbar("l'utilisateur", 'success');
  } catch (error) {
    console.error('Error updating user:', error);
    closeNavigationDrawerCashRegister();
    showSnackbar("l'utilisateur", 'error');
  }
}

// 👉 Opens the drawer for editing a transaction and cash register.
const openEdittransactionDrawer = async (transaction: Transaction) => {
  const id = transaction.id
  const data = await fetchTransactionById(id)
  store.transaction.setEditMode(data);
  isAddNewTransactionDrawerVisible.value = true;
}

const openEditCashRegisterDrawer = (cashregister: CashRegister) => {
  store.cashregister.setEditMode(cashregister);
  isAddNewCashRegisterDrawerVisible.value = true
}
const openEditUserCashRegisterDrawer = (cashregister: CashRegister) => {
  store.cashregister.setEditMode(cashregister);
  isToggleCashRegisterDrawerVisible.value = true
}

// 👉 Functions to open the delete confirmation dialog and cash register
const openDeleteDialogTransaction = (transaction: Transaction) => {
  dialogState.value = {
    isVisible: true,
    message: 'Êtes-vous sûr de vouloir archiver cette transaction?',
    data: transaction,
    onConfirm: confirmDeletetransaction,
    onCancel: () => { dialogState.value.isVisible = false; dialogState.value.data = null; }
  }
}

const openUpdateStatusDialogCashRegister = (cashregister: CashRegister, newStatus: any) => {
  const message = newStatus === 1
    ? 'Êtes-vous sûr de vouloir activer cette caisse ?'
    : 'Êtes-vous sûr de vouloir désactiver cette caisse ?';

  dialogState.value = {
    isVisible: true,
    message,
    data: { ...cashregister, status: newStatus },
    onConfirm: updateStatus,
    onCancel: () => { dialogState.value.isVisible = false; dialogState.value.data = null; }
  }
}
// 👉 Functions to handle delete action and cash register
const confirmDeletetransaction = async () => {
  const transactionId = dialogState.value.data?.id
  if (!transactionId) {
    console.error('transaction ID is undefined. Cannot delete transaction.')
    return
  }
  try {
    await deleteTransaction(transactionId)
    dialogState.value.isVisible = false
    await loadStaticData()
    await loadTransactions()
    await loadCashRegistersTb();
    showSnackbar('la transaction', 'success');
  } catch (error) {
    console.error('Error deleting transaction:', error)
    dialogState.value.isVisible = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
  dialogState.value.data = null;
}

// 👉 computed property that returns true if the selected value is not "All," and false otherwise.
const isClearableCash = computed(() => {
  return filterParamsCash.value.selectedCashRegister !== null;
});
const isClearableCashTra = computed(() => {
  return filterParams.value.selectedCashRegister !== null;
});
const isClearableTransaction = computed(() => {
  return filterParams.value.selectedTransactionType !== null;
});
const isClearableUser = computed(() => {
  return filterParams.value.selectedUser !== null;
});
const datePickerConfig = createDatePickerConfig();

const updateStatus = async () => {
  if (!dialogState.value.data) return;

  try {
    const newStatus = dialogState.value.data.status ?? false;
    const updatedItem = await updateStatusCashregister(dialogState.value.data.id, { status: newStatus });

    if (newStatus === true) {
      showSnackbar('Statut mis à jour vers Active', 'success');
    } else {
      showSnackbar('Statut mis à jour vers Désactivé', 'success');
    }

    loadCashRegistersTb();
    dialogState.value.isVisible = false;
    dialogState.value.data = null;
  } catch (error) {
    console.error('Error updating status:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

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
          Caisses
        </h4>
      </div>
      <div>
        <VBtn v-if="hasCashRegisterManagerPermission && !archiveStore.isArchive" prepend-icon="tabler-plus"
          @click="openAddNewCashRegisterDrawer">
          Ajouter une caisse
        </VBtn>

      </div>
    </div>
    <VRow v-if="(hasCashRegisterManagerPermission || hasCashRegisterViewerPermission) && !archiveStore.isArchive"
      class="match-height">
      <!-- 👉 Cash register Stats -->
      <VCol cols="12" md="8">
        <CashRegisterStats :cashRegister="cashRegisters" @updateFilterParams="updateFilterParams"
          @open-add-cash-register="openAddNewCashRegisterDrawer" :seC="filterParamsCash.selectedCashRegister" />
      </VCol>

      <!-- 👉 VCard on top and Cash Register Graph on bottom -->
      <VCol cols="12" sm="6" lg="4" class="d-flex flex-column">
        <VCard class="mb-4">
          <VCardText class="p-4">
            <VRow class="align-start justify-space-between">

              <!-- Left Side: Select + Date -->
              <VCol cols="12" class="d-flex gap-4">
                <!-- Select: Caisse -->
                <div style="width: 55%;">
                  <VAutocomplete v-model="filterParamsCash.selectedCashRegister" :items="filterCrManager"
                    item-title="text" item-value="value" clear-icon="tabler-x" placeholder="Caisse"
                    :clearable="isClearableCash" variant="outlined" label="Caisse" class="v-select-custom" />
                </div>

                <!-- Date Picker -->
                <div style="width: 45%;">
                  <AppDateTimePicker v-model="filterParamsCash.selectedDate" :config="datePickerConfig"
                    placeholder="Date" clearable clear-icon="tabler-x" />
                </div>
              </VCol>


            </VRow>
          </VCardText>
        </VCard>


        <!-- Cash Registers Graph on Bottom -->
        <div class="flex-grow-1" style="flex-basis: 80%;">
          <CashRegistersGragh :cashRegister="cashRegisters" :selectedFilter="selectedFilter" />
        </div>
      </VCol>
    </VRow>
    <div class="my-6"></div>
    <VCard>
      <VDataTable v-if="hasCashRegisterManagerPermission || hasCashRegisterViewerPermission" :headers="headers2"
        :items="cashRegistersDataTable.cashregisterstb" v-model:items-per-page="queryParams.itemsPerPage"
        class="elevation-1 " :loading="isLoading">

        <template #loading>

          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>


        <template #item.status="{ item }">
          <VChip :color="resolveUserStatusVariant(item.status)" size="small" class="text-body-2 text-capitalize">
            {{ getStatusTitle(item.status) }}
          </VChip>
        </template>

        <!-- Action buttons for each item -->
        <template v-if="!archiveStore.isArchive" #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <IconBtn :disabled="archiveStore.isArchive" @click="openEditUserCashRegisterDrawer(item)">
              <VIcon icon="tabler-user" />
            </IconBtn>
            <IconBtn :disabled="archiveStore.isArchive" @click="openEditCashRegisterDrawer(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>
            <template v-if="item.status === 1">
              <IconBtn class='text-error' :disabled="archiveStore.isArchive"
                @click="openUpdateStatusDialogCashRegister(item, 0)">
                <VIcon icon="tabler-ban" />
              </IconBtn>
            </template>
            <template v-else>
              <IconBtn class='text-success' :disabled="archiveStore.isArchive"
                @click="openUpdateStatusDialogCashRegister(item, 1)">
                <VIcon icon="tabler-check" />
              </IconBtn>
            </template>
          </div>
        </template>


        <!-- User information for each item -->

        <!-- Template for Name Column with Code Below -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-4">
            <VAvatar size="38" :color="resolveUserRoleVariant(item.name).color" class="text-body-1 text-uppercase">
              {{ avatarText(item.name ?? '') }}
            </VAvatar>
            <div class="d-flex flex-column">
              <small class="text-body-2">{{ item.name }}</small>
              <small class="text-muted">{{ item.code }}</small>
            </div>
          </div>
        </template>

        <!-- Template for Managed By Column (Names Only) -->
        <template #item.managedBy="{ item }">
          <div class="managed-by-container d-flex flex-wrap">
            <div v-for="(manager, index) in item.managedBy" :key="index" class="managed-by-item">
              <VChip color="primary" text-color="white" size="small" outlined dense class="ma-1">
                {{ manager.fullName }}
              </VChip>
            </div>
          </div>
        </template>

        <!-- Pagination at the bottom -->
        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="cashRegistersDataTable.totalcashregisterstb" @update:page="onPageChange" />
        </template>
      </VDataTable>
    </VCard>
    <div class="my-6"></div>

    <!-- 👉 Widgets -->
    <VCard v-if="hasTransactionManagerPermission || hasCashRegisterManagerPermission || hasTransactionViewerPermission"
      class="mb-6">

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
            <VBtn v-if="!archiveStore.isArchive && hasTransactionManagerPermission" @click="exportTransaction"
              variant="tonal" color="secondary" prepend-icon="tabler-upload">
              Exporter
            </VBtn>
            <!-- 👉 Add transaction button -->
            <VBtn v-if="!archiveStore.isArchive && hasTransactionManagerPermission" prepend-icon="tabler-plus"
              @click="openAddNewtransactionDrawer">
              Ajouter une transaction
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <!-- Second Row: Search, Filter 1, and Filter 2 -->
      <VCardText>
        <VRow>
          <!-- 👉 Search  -->
          <VCol cols="12" md="4">
            <VTextField v-model="filterParams.searchQuery" @update:modelValue="onSearchInput" placeholder="Recherche"
              variant="outlined" label="Recherche" />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete @update:modelValue="onFilterChange" v-model="filterParams.selectedUser"
              :items="filterItems.users" item-title="text" item-value="value" clear-icon="tabler-x" label="Utilisateurs"
              placeholder="Utilisateurs" :clearable="isClearableUser" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete @update:modelValue="onFilterChange" v-model="filterParams.selectedCashRegister"
              :items="filterItems.cashregisterFilter" item-title="text" item-value="value" clear-icon="tabler-x"
              label="Caisses" placeholder="Caisses" :clearable="isClearableCashTra"
              :disabled="!!(filterItems.cashregisterFilter.length === 1)" variant="outlined" />
          </VCol>

          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedTransactionType" :items="filterItems.types"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Types"
              :clearable="isClearableTransaction" clear-icon="tabler-x" placeholder="Type" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <AppDateTimePicker @update:modelValue="onFilterChange" v-model="filterParams.range" placeholder="Date "
              :config="{ mode: 'range' }" clearable clear-icon="tabler-x" />
          </VCol>


        </VRow>
      </VCardText>
    </VCard>


    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable
        v-if="hasTransactionManagerPermission || hasTransactionViewerPermission || hasCashRegisterManagerPermission"
        :headers="headers" :items="transactionsData.transactions" v-model:items-per-page="queryParams.itemsPerPage"
        class="elevation-1 " :loading="isLoading">

        <template #loading>

          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>

        <!-- User information for each item -->
        <template #item.user="{ item }">
          <div class="user-item d-flex align-center gap-4">
            <template v-if="(item as any).user?.photo">
              <img :src="`${baseUrl}/${(item as any).user?.photo}`" alt="User Photo" class="user-photo rounded-circle">
              <div class="user-info d-flex flex-column">
                <small class="text-body-2 text-truncate">
                  {{ (item as any).user?.fullName }}
                </small>
              </div>
            </template>
            <template v-else>
              <div class="d-flex align-center gap-4">
                <VAvatar size="38" :color="resolveUserRoleVariant((item as any).user?.fullName).color"
                  class="text-body-1 text-uppercase">
                  {{ avatarText((item as any).user?.fullName ?? '') }}
                </VAvatar>
                <small class="text-body-2 text-truncate">
                  {{ (item as any).user?.fullName }}
                </small>
              </div>
            </template>
          </div>
        </template>
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-4">
            <span v-if="(item as any).client && typeof (item as any).client === 'object' && (item as any).client.legalName && (item as any).client.legalName !== 'Inconnue'"
              class="text-body-1 text-truncate">
              {{ (item as any).client.legalName }}
            </span>
            <span
              v-else-if="(item as any).targetCashRegister && typeof (item as any).targetCashRegister === 'object' && (item as any).targetCashRegister.name && (item as any).targetCashRegister.name !== 'Inconnue'"
              class="text-body-1 text-truncate">
              {{ (item as any).targetCashRegister.name }}
            </span>
            <span v-else-if="(item as any).targetUser && typeof (item as any).targetUser === 'object' && (item as any).targetUser.fullName" class="text-body-1 text-truncate">
              {{ (item as any).targetUser.fullName }}
            </span>
            <span v-else-if="item.seller" class="text-body-1 text-truncate">
              {{ item.seller }}
            </span>
            <span v-else-if="item.bank" class="text-body-1 text-truncate">
              {{ item.bank }}
            </span>
            <span v-else class="text-body-1 text-truncate">
              -
            </span>
          </div>
        </template>


        <template #item.cashTransactionType="{ item }">
          <div class="d-flex align-center gap-4">
            <span class="text-body-1 text-truncate">
              {{ (item as any)?.transactionType?.name || item?.cashTransactionType }}
            </span>
          </div>
        </template>

        <template #item.caisse="{ item }">
          <div class="d-flex align-center gap-4">
            <span class="text-body-1 text-truncate">
              {{ typeof (item as any)?.cashRegister === 'object' ? (item as any)?.cashRegister?.name : item?.cashRegister }}
            </span>
          </div>
        </template>

        <template #item.date="{ item }">
          <div class="d-flex align-center gap-4">
            <span class="text-body-1 text-truncate">
              {{ item?.date ? formatDateToddmmYYYY(item.date as string) : '-' }}
            </span>
          </div>
        </template>

        <template #item.comment="{ item }">
          <div class="d-flex align-center gap-4">
            <span class="text-body-1 text-truncate">
              {{ item?.comment }}
            </span>
          </div>
        </template>

        <template #item.amount="{ item }">
          <div class="d-flex align-center gap-4">
            <span class="text-body-1 text-truncate">
              {{ item?.amount }}
            </span>
          </div>
        </template> 


        <!-- Action buttons for each item -->
        <template v-if="(hasCashRegisterManagerPermission  || hasTransactionManagerPermission )&& !archiveStore.isArchive" #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <IconBtn
              :disabled="!hasTransactionManagerPermission || archiveStore.isArchive"
              @click="openEdittransactionDrawer(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>
            <IconBtn
              :disabled="!hasTransactionManagerPermission || archiveStore.isArchive"
              @click="openDeleteDialogTransaction(item)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>

        <!-- Pagination at the bottom -->
        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="transactionsData.totaltransactions" @update:page="onPageChange" />
        </template>
      </VDataTable>
    </VCard>

    <!--Alerte Confirmation Dialog -->
    <AlertDialog :isVisible="dialogState.isVisible" :message="dialogState.message" deleteButtonText="Confirmer"
      cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="dialogState.onConfirm" :onCancelClick="dialogState.onCancel"
      @update:isVisible="dialogState.isVisible = $event" />

    <!-- 👉  transaction drawer -->
    <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isAddNewTransactionDrawerVisible"
      @update:model-value="handleDrawerModelValueUpdate" persistent :is-date-picker-open="isDatePickerOpen">
      <!-- Dialog close button slot -->
      <template #close-btn>
        <DialogCloseBtn @click="closeNavigationDrawer" />

      </template>
      <!-- Title and Subtitle slot -->
      <template #title>
        <h4 class="text-h4 text-center mb-2">
          {{ titles.title1.value }}
        </h4>
      </template>
      <!-- Form slot -->
      <template #form>
        <TransactionForm :mode="store.transaction.mode" :selected-transaction="store.transaction.selectedTransaction"
          @close="closeNavigationDrawer" @submit="onSubmitTransaction" @date-picker-state="handleDatePickerState" />
      </template>
    </ModalForm>
    <!-- 👉  Cash Register drawer -->
    <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isAddNewCashRegisterDrawerVisible"
      @update:model-value="handleDrawerModelValueUpdate" persistent>
      <!-- Dialog close button slot -->
      <template #close-btn>
        <DialogCloseBtn @click="closeNavigationDrawerCashRegister" />

      </template>
      <!-- Title and Subtitle slot -->
      <template #title>
        <h4 class="text-h4 text-center mb-2">
          {{ titles.title3.value }}
        </h4>
      </template>
      <!-- Form slot -->
      <template #form>
        <CashRegistersForm :mode="store.cashregister.mode"
          :selected-cashregister="store.cashregister.selectedCashRegister" @close="closeNavigationDrawerCashRegister"
          @submit="onSubmitCashRegister" />
      </template>
    </ModalForm>

    <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isToggleCashRegisterDrawerVisible"
      @update:model-value="handleDrawerModelValueUpdate" persistent>
      <!-- Dialog close button slot -->
      <template #close-btn>
        <DialogCloseBtn @click="closeNavigationDrawerCashRegisterUser" />

      </template>
      <!-- Title and Subtitle slot -->
      <template #title>
        <h4 class="text-h4 text-center mb-2">
          {{ titles.title3.value }}
        </h4>
      </template>
      <!-- Form slot -->
      <template #form>
        <CashRegisterUser :mode="store.cashregister.mode" :selected-user="store.cashregister.selectedCashRegister"
          @close="closeNavigationDrawerCashRegisterUser" @submit="onSubmit" />
      </template>
    </ModalForm>
  </section>
</template>
<style scoped>
.user-item {
  display: flex;
  align-items: center;
  gap: 16px;
  /* stylelint-disable-next-line comment-empty-line-before */
  /* Adjusted to match gap-4 */
}

.user-photo {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  width: 38px;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  height: 38px;
  border-radius: 50%;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.loading-container {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  height: 100px;
}

.custom-select-width {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  width: 160px;
  /* stylelint-disable-next-line comment-empty-line-before */
  /* Adjust width as needed */
}

/* stylelint-disable-next-line @stylistic/block-opening-brace-space-before */
.add-new-cash-register {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  width: 40px;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  height: 38px;
  border-radius: 8px;
}
</style>
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
