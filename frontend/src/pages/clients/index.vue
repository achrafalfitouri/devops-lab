<script setup lang="ts">
import DialogCloseBtn from '@/@core/components/DialogCloseBtn.vue';
import TablePagination from '@/@core/components/TablePagination.vue';
import ClientAppexDonutChart from '@/components/clients/ClientAppexDonutChart.vue';
import ClientForm from '@/components/clients/ClientForm.vue';
import ClientTypeGraph from '@/components/clients/ClientTypeGraph.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import ModalForm from '@/components/ModalForm.vue';
import { useArchiveStoreStore } from '@/stores/archive';
import { useClientStore } from '@/stores/client';
import { deleteClient, exportClients, fetchArchivedClients, fetchClientById, fetchClients, fetchStaticData } from '@services/api/client';
import { getDefaultClient, getDefaultClientFilterParams } from '@services/defaults';
import type { Client, ClientFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';
import { VForm } from 'vuetify/components/VForm';
import { VRow } from 'vuetify/lib/components/index.mjs';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

const apiUrl = (window as any).__ENV__?.API_BASE_URL
const baseUrl = apiUrl.replace(/\/api\/?$/, '')

// 👉 Store call
const store = useClientStore();
const archiveStore = useArchiveStoreStore();


// 👉 State variables for controlling drawer visibility
const isAddNewclientDrawerVisible = ref(false)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 References to the forms
const refFormAdd = ref<VForm>()

// 👉 State variable for the selected client in edit mode
const selectedclient = ref<Client>(getDefaultClient())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the client to delete
const clientToDelete = ref<Client | null>(null)

// 👉 State variables for image preview and file
const logo = ref<string>('')
const imageFile = ref<File | null>(null)

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1
})

// 👉 Filters and search
const filterParams = ref<ClientFilterParms>(getDefaultClientFilterParams())

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
}

// 👉 Explicitly define the type for filterItems
const filterItems = ref<{
  types: FilterItem[];
  gamuts: FilterItem[];
  status: FilterItem[];
  cities: FilterItem[];
  businessSector: FilterItem[];
}>({
  types: [],
  gamuts: [],
  status: [],
  cities: [],
  businessSector: [],
});

// 👉 State variable for storing clients data
const clientsData = ref<{
  clients: Client[]
  totalclients: number
}>({
  clients: [],
  totalclients: 0,
})

// 👉 Modal title logic
const title1 = computed(() => store.mode === 'add' ? 'Ajouter des informations du client' : 'Modifier les informations du client');

// 👉 Handle drawer visibility update
const handleDrawerModelValueUpdate = (val: boolean) => {
  isAddNewclientDrawerVisible.value = val
}

// 👉 Function to fetch clients from the API
const loadclients = async () => {
  isLoading.value = true; // Start loading
  try {
    let data;
    const filters: any = {};
    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;
    }
    else {
      if (filterParams.value.selectedType) filters.type = filterParams.value.selectedType;
      if (filterParams.value.selectedGamut) filters.gamut = filterParams.value.selectedGamut;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
      if (filterParams.value.selectedCity) filters.city = filterParams.value.selectedCity;
      if (filterParams.value.selectedBusinessSector) filters.business_sector = filterParams.value.selectedBusinessSector;
    }
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedClients(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    }
    else {
      data = await fetchClients(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    }
    clientsData.value.clients = data.clients || [];
    clientsData.value.totalclients = data.totalClients || 0;
    // Check if current page is now empty after deletion
    if (clientsData.value.clients.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadclients();
    }

  } catch (error) {
    console.error('Error fetching clients:', error);
  } finally {
    isLoading.value = false;
  }
};

const exportClient = async () => {

  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;
    } else {
      if (filterParams.value.selectedType) filters.type = filterParams.value.selectedType;
      if (filterParams.value.selectedGamut) filters.gamut = filterParams.value.selectedGamut;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
      if (filterParams.value.selectedCity) filters.city = filterParams.value.selectedCity;
      if (filterParams.value.selectedBusinessSector) filters.business_sector = filterParams.value.selectedBusinessSector;
    }

    const response = await exportClients(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'clients.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export clients: Invalid response format');
    }
  } catch (error) {
    console.error('Error exporting clients:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();

    filterItems.value.types = mapStaticData(staticData.data.clientTypesFilter);
    filterItems.value.gamuts = mapStaticData(staticData.data.gamutesFilter);
    filterItems.value.status = mapStaticData(staticData.data.statusesFilter);
    filterItems.value.cities = mapStaticData(staticData.data.citiesFilter);
    filterItems.value.businessSector = mapStaticData(staticData.data.businessSectorsFilter);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadclients();
  loadStaticData();
});

// 👉 Replace throttledLoadClients with debouncedLoadClients
const debouncedLoadClients = debounce(async () => {
  await loadclients();
}, 800);

// 👉 Watchers to reload clients on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadClients();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadclients();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadclients();
};

// 👉 clear filters
const onSearchInput = (newSearch: string) => {
  if (newSearch) {
    filterParams.value = {
      ...getDefaultClientFilterParams(),
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
  { title: 'Client', key: 'client' },
  { title: 'Nom commercial', key: 'tradeName' },
  { title: 'Secteur d’activité', key: 'businessSector' },
  { title: 'Gamme', key: 'gamut' },
  { title: 'Statut', key: 'status' },
 ...((!archiveStore.isArchive)
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),]

// 👉 Function to open the drawer for adding a new client
const openAddNewclientDrawer = () => {
  selectedclient.value = getDefaultClient()
  isAddNewclientDrawerVisible.value = true
  store.setAddMode();
}

// 👉 Function to close the drawer and reset the form for adding a new client
const closeNavigationDrawer = () => {
  isAddNewclientDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()
  logo.value = ''
  imageFile.value = null
}

const loadClientGraphStats = ref(false)
const loadClientDonnutStats = ref(false)

// 👉 Function to handle form submission for adding a new client
const onSubmit = async () => {
  try {
    loadClientGraphStats.value = true
    loadClientDonnutStats.value = true
    closeNavigationDrawer();
    await loadclients();
    showSnackbar('le client', 'success');
  } catch (error) {
    console.error('Error adding client:', error);
    closeNavigationDrawer();
    showSnackbar('le client', 'error');
  }
};


// 👉 Opens the drawer for editing a client.
const openEditclientDrawer = async (client: Client) => {
  logo.value = client.logo || ''
  const id = client.id
  const data = await fetchClientById(id)
  store.setEditMode(data);
  isAddNewclientDrawerVisible.value = true
}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (client: Client) => {
  clientToDelete.value = client
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleteclient = async () => {
  const clientId = clientToDelete.value?.id
  if (!clientId) {
    console.error('client ID is undefined. Cannot delete client.')
    return
  }
  try {
    await deleteClient(clientId)
    isDeleteDialogVisible.value = false
    await loadclients()
    loadClientGraphStats.value = true
    loadClientDonnutStats.value = true
    showSnackbar('le client', 'success');
  } catch (error) {
    console.error('Error deleting client:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');

  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  clientToDelete.value = null
}
const isClearableBusinessSector = computed(() => {
  return filterParams.value.selectedBusinessSector !== null;
});
const isClearableCity = computed(() => {
  return filterParams.value.selectedCity !== null;
});
const isClearableGamut = computed(() => {
  return filterParams.value.selectedGamut !== null;
});
const isClearableStatus = computed(() => {
  return filterParams.value.selectedStatus !== null;
});
const isClearableType = computed(() => {
  return filterParams.value.selectedType !== null;
});
</script>

<template>
  <section>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Clients
        </h4>
      </div>
      <div>
        <!-- Add Button -->
        <VBtn v-if="!archiveStore.isArchive" prepend-icon="tabler-plus" @click="openAddNewclientDrawer">
          Ajouter un client
        </VBtn>
      </div>
    </div>

    <VRow class="mb-6" v-if="!archiveStore.isArchive">
      <!-- 👉 AppexDonutChart -->
      <VCol cols="12" md="8">
        <ClientTypeGraph :loadClientGraphStats="loadClientGraphStats"
          @update:load-client-graph-stats="loadClientGraphStats = $event" />
      </VCol>
      <VCol cols="12" md="4">
        <ClientAppexDonutChart :loadClientDonnutStats="loadClientDonnutStats"
          @update:load-client-donnut-stats="loadClientDonnutStats = $event" />
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
              { value: 100, title: '100' },
            ]" style="inline-size: 6.25rem;" @update:model-value="onItemsPerPageChange" />

            <!-- 👉 Export button -->
            <VBtn v-if="!archiveStore.isArchive" variant="tonal" color="secondary" prepend-icon="tabler-upload"
              @click="exportClient">
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
            <VTextField v-model="filterParams.searchQuery" @update:modelValue="onSearchInput"
              placeholder="Rechercher par nom commercial, nom légal ou code" variant="outlined" label="Recherche" />
          </VCol>

          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedType" :items="filterItems.types"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Types"
              :clearable="isClearableType" clear-icon="tabler-x" placeholder="Types" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedGamut" :items="filterItems.gamuts"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Gammes"
              :clearable="isClearableGamut" clear-icon="tabler-x" placeholder="Gammes" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedStatus" :items="filterItems.status"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Statuts"
              :clearable="isClearableStatus" clear-icon="tabler-x" placeholder="Statut" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedCity" :items="filterItems.cities"
              @update:modelValue="onFilterChange" item-title="text" item-value="value" label="Villes"
              :clearable="isClearableCity" clear-icon="tabler-x" placeholder="Villes" variant="outlined" />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete v-model="filterParams.selectedBusinessSector" @update:modelValue="onFilterChange"
              :items="filterItems.businessSector" item-title="text" item-value="value" label="Secteurs d'activité"
              placeholder="Secteur d'activité" clear-icon="tabler-x" :clearable="isClearableBusinessSector"
              variant="outlined" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>


    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable :headers="headers" :items="clientsData.clients" v-model:items-per-page="queryParams.itemsPerPage"
        class="elevation-1" :loading="isLoading">
        <template #loading>

          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>
        <template #item.client="{ item }">
          <div class="d-flex align-center gap-4">
            <VAvatar size="38" class="text-body-1 text-uppercase" :color="!item.logo ? 'primary' : undefined">
              <VImg v-if="item.logo" :src="`${baseUrl}/${item.logo}`" cover />
              <span v-else>
                {{ avatarText(item.legalName ?? '') }}
              </span>
            </VAvatar>
            <div class="d-flex flex-column">
              <div class="d-flex align-center">
                <span class="text-body-1 text-truncate">
                  <RouterLink
                    :to="{ name: archiveStore.isArchive ? 'archives-clients-id' : 'clients-id', params: { id: item.id } }"
                    class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
                    {{ item.legalName }}
                  </RouterLink>
                </span>
                <!-- <VAvatar v-if="item.gamut.name === 'gold'" size="20" variant="tonal" color="warning" class="ms-2">
                <VIcon icon="tabler-star" size="14" />
              </VAvatar> -->
              </div>
              <small class="text-body-2 text-truncate">
                {{ item.code }}
              </small>
            </div>
          </div>
        </template>
        <template #item.balance="{ item }">
          <span v-if="item.legalName == 'Client de passage'">

            -
          </span>
          <span v-else>
            {{ (item as any).balance ?? '0' }}
          </span>
        </template>


        <template #item.status="{ item }">
          <VChip v-if="typeof (item as any).status === 'object' && (item as any).status?.name" :color="resolveClientStatusVariant((item as any).status.name)" size="small"
            class="text-body-2 text-capitalize">
            {{ (item as any).status.name }}
          </VChip>
        </template>

        <template #item.gamut="{ item }">
          <VChip v-if="typeof (item as any).gamut === 'object' && (item as any).gamut?.name" :color="resolveClientGamutVariant((item as any).gamut.name)" size="small"
            class="text-body-2 text-capitalize">
            {{ (item as any).gamut.name }}
          </VChip>
        </template>

        <template #item.businessSector="{ item }">
          <span v-if="typeof (item as any).businessSector === 'object' && (item as any).businessSector?.name">
            {{ (item as any).businessSector.name }}
          </span>
        </template>

        <template #item.tradeName="{ item }">
          <span v-if="item.tradeName">
            {{ item.tradeName }}
          </span>
          <span v-else></span>
        </template>

        <template #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <RouterLink
              :to="{ name: archiveStore.isArchive ? 'archives-clients-id' : 'clients-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              <IconBtn>
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>

            <IconBtn v-if="!archiveStore.isArchive" :disabled="item.legalName === 'Client de passage'" 
              @click="item.legalName !== 'Client de passage' && openEditclientDrawer(item)"
              :title="item.legalName === 'Client de passage' ? 'Modification désactivée' : 'Modifier le client'">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <IconBtn v-if="!archiveStore.isArchive" :disabled="item.legalName === 'Client de passage'"
              @click="openDeleteDialog(item)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="clientsData.totalclients" @update:page="onPageChange" />
        </template>

      </VDataTable>
    </VCard>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce client ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeleteclient" :onCancelClick="cancelDelete"
      @update:isVisible="isDeleteDialogVisible = $event" />

    <!-- 👉  client drawer -->
    <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isAddNewclientDrawerVisible"
      @update:model-value="handleDrawerModelValueUpdate" persistent>
      <!-- Dialog close button slot -->
      <template #close-btn>
        <DialogCloseBtn @click="closeNavigationDrawer" />

      </template>
      <!-- Title and Subtitle slot -->
      <template #title>
        <h4 class="text-h4 text-center mb-2">
          {{ title1 }}
        </h4>
      </template>
      <!-- Form slot -->
      <template #form>
        <ClientForm :mode="store.mode" :selected-client="store.selectedClient" @close="closeNavigationDrawer"
          @submit="onSubmit" />
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
