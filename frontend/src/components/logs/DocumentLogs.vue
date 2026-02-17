<!-- LogsDisplay.vue -->
<script setup lang="ts">
import { fetchDocumentItemLogss, fetchDocumentLogss } from '@/services/api/documentlogs';
import { getDefaultLogsFilterParams } from '@services/defaults';
import type { Logs, LogsFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { RouteLocationAsRelativeTyped, RouteLocationAsString } from 'unplugin-vue-router';
import { onMounted, ref, watch } from 'vue';

// Define the props with proper typing for routeName
const props = defineProps({
  title: {
    type: String,
    default: 'Logs'
  },
  documentType: {
    type: String,
    default: ''
  },
  itemType: {
    type: String,
    default: ''
  },
  routeName: {
    type: String as RouteLocationAsString<Record<string, any>> | RouteLocationAsRelativeTyped<Record<string, any>>,
    required: true
  }
});

// 👉 State variables for form validation and loading status
const isLoading = ref(true);

// 👉 Filters and search
const filterParams = ref<LogsFilterParms>(getDefaultLogsFilterParams());

// 👉 Data table options
const documentQueryParams = ref({
  itemsPerPage: 10,
  page: 1,
});

const itemQueryParams = ref({
  itemsPerPage: 10,
  page: 1,
});

// 👉 State variables for the filter options
interface FilterItem {
  text: string;
  value: string | boolean;
}

// 👉 State variables for the filter options (initialized with proper structure)
const filterItems = ref<{
  actions: FilterItem[];
}>({
  actions: [
    { text: 'Modification', value: 'update' },
    { text: 'Suppression', value: 'delete' },
  ],
});

// 👉 State variable for storing logs data
const logsData = ref<{
  documentLogs: Logs[];
  itemLogs: Logs[];
  totalItemLogs: number;
  totalDocumentLogs: number;
}>({
  documentLogs: [],
  itemLogs: [],
  totalItemLogs: 0,
  totalDocumentLogs: 0,
});

// 👉 Function to fetch document logs from the API
const loadDocumentLogs = async () => {
  if (!props.documentType) return;

  isLoading.value = true;
  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;
    } else {
      if (filterParams.value.selectedAction) filters.action = filterParams.value.selectedAction;
    }

    filters.documentType = props.documentType;

    const data = await fetchDocumentLogss(filters, documentQueryParams.value.itemsPerPage, documentQueryParams.value.page);
    logsData.value.documentLogs = data.documentLogs || [];
    logsData.value.totalDocumentLogs = data.totalDocumentLogs || 0;

    if (logsData.value.documentLogs.length === 0 && documentQueryParams.value.page > 1) {
      documentQueryParams.value.page--;
      await loadDocumentLogs();
    }
  } catch (error) {
    console.error(`Error fetching ${props.documentType} logs:`, error);
  } finally {
    isLoading.value = false;
  }
};

const loadItemLogs = async () => {
  if (!props.itemType) return;

  isLoading.value = true;
  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;
    } else {
      if (filterParams.value.selectedAction) filters.action = filterParams.value.selectedAction;
    }

    filters.itemType = props.itemType;

    const data = await fetchDocumentItemLogss(filters, itemQueryParams.value.itemsPerPage, itemQueryParams.value.page);
    logsData.value.itemLogs = data.itemLogs || [];
    logsData.value.totalItemLogs = data.totalItemLogs || 0;

    if (logsData.value.itemLogs.length === 0 && itemQueryParams.value.page > 1) {
      itemQueryParams.value.page--;
      await loadItemLogs();
    }
  } catch (error) {
    console.error(`Error fetching ${props.itemType} logs:`, error);
  } finally {
    isLoading.value = false;
  }
};

// 👉 Call loadData on component mount
onMounted(() => {
  loadDocumentLogs();
  loadItemLogs();
});

// Replace with debounced loading
const debouncedLoadLogs = debounce(async () => {
  await loadDocumentLogs();
  await loadItemLogs();
}, 800);

// 👉 Watchers to reload logs on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadLogs();
}, { deep: true });

// 👉 Function to handle page change for document logs
const onDocumentPageChange = (newPage: number) => {
  documentQueryParams.value.page = newPage;
  loadDocumentLogs();
};

// 👉 Function to handle page change for item logs
const onItemPageChange = (newPage: number) => {
  itemQueryParams.value.page = newPage;
  loadItemLogs();
};

// 👉 Function to handle items per page change for document logs
const onDocumentItemsPerPageChange = (newItemsPerPage: number) => {
  documentQueryParams.value.itemsPerPage = newItemsPerPage;
  loadDocumentLogs();
};

// 👉 Function to handle items per page change for item logs
const onItemItemsPerPageChange = (newItemsPerPage: number) => {
  itemQueryParams.value.itemsPerPage = newItemsPerPage;
  loadItemLogs();
};

// 👉 clear filters
const onSearchInput = (newSearch: any) => {
  if (newSearch) {
    filterParams.value.selectedAction = null;
  }
};

// 👉 clear search
const onFilterChange = () => {
  filterParams.value.searchQuery = null;
};

// 👉 Table headers definition
const headers = [
  { title: 'Log', key: 'id' },
  { title: 'Utilisateur', key: 'user' },
  { title: 'Action', key: 'action' },
  { title: 'Valeur ancienne', key: 'oldValue' },
  { title: 'Nouvelle valeur', key: 'newValue' },
  { title: 'Gestion', key: 'actions', sortable: false },
];
</script>

<template>
  <section>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          {{ title }}
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
            <AppSelect :model-value="documentQueryParams.itemsPerPage" :items="[
              { value: 10, title: '10' },
              { value: 50, title: '50' },
              { value: 100, title: '100' }
            ]" style="inline-size: 6.25rem;" @update:model-value="onDocumentItemsPerPageChange" />
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
            <VAutocomplete v-model="filterParams.selectedAction" placeholder="Actions" label="Actions"
              :items="filterItems.actions" item-title="text" item-value="value" @update:modelValue="onFilterChange"
              clearable clear-icon="tabler-x" variant="outlined" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 Data Table document logs-->
    <VCard v-if="documentType" class="mb-6">
      <VCardTitle>Logs de Document</VCardTitle>
      <VDataTable v-model:items-per-page="documentQueryParams.itemsPerPage" :items="logsData.documentLogs"
        :headers="headers" item-value="id" class="text-no-wrap" :loading="isLoading" locale="fr">

        <template #loading>
          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>

        <template #item.user="{ item }">
          <span>
            {{ (item as any).user?.fullName }}
          </span>
        </template>

        <template #item.id="{ item }">
          <VTooltip location="top">
            <template #activator="{ props }">
              <span v-bind="props"
                style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ item.id }}
              </span>
            </template>
            <span>{{ item.id }}</span>
          </VTooltip>
        </template>

        <template #item.oldValue="{ item }">
          <RouterLink :to="{ name: routeName, params: { id: item.id }, query: { type: 'document' } }"
            class="d-flex align-center gap-1 text-decoration-none" style="color: inherit;">
            <span
              style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ item.oldValue }}
            </span>
          </RouterLink>
        </template>

        <template #item.newValue="{ item }">
          <RouterLink :to="{ name: routeName, params: { id: item.id }, query: { type: 'document' } }"
            class="d-flex align-center gap-1 text-decoration-none" style="color: inherit;">
            <span
              style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ item.newValue }}
            </span>
          </RouterLink>
        </template>

        <template #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <RouterLink :to="{ name: routeName, params: { id: item.id }, query: { type: 'document' } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              <IconBtn>
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="documentQueryParams.page" :items-per-page="documentQueryParams.itemsPerPage"
            :totalItems="logsData.totalDocumentLogs" @update:page="onDocumentPageChange" locale="fr" />
        </template>
      </VDataTable>
    </VCard>

    <!-- 👉 Data Table item logs-->
    <VCard v-if="itemType">
      <VCardTitle>Logs des articles</VCardTitle>
      <VDataTable v-model:items-per-page="itemQueryParams.itemsPerPage" :items="logsData.itemLogs" :headers="headers"
        item-value="id" class="text-no-wrap" :loading="isLoading" locale="fr">

        <template #loading>
          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>

        <template #item.user="{ item }">
          <span>
            {{ (item as any).user?.fullName }}
          </span>
        </template>

        <template #item.id="{ item }">
          <VTooltip location="top">
            <template #activator="{ props }">
              <span v-bind="props"
                style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ item.id }}
              </span>
            </template>
            <span>{{ item.id }}</span>
          </VTooltip>
        </template>

        <template #item.oldValue="{ item }">
          <RouterLink :to="{ name: routeName, params: { id: item.id }, query: { type: 'item' } }"
            class="d-flex align-center gap-1 text-decoration-none" style="color: inherit;">
            <span
              style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ item.oldValue }}
            </span>
          </RouterLink>
        </template>

        <template #item.newValue="{ item }">
          <RouterLink :to="{ name: routeName, params: { id: item.id }, query: { type: 'item' } }"
            class="d-flex align-center gap-1 text-decoration-none" style="color: inherit;">
            <span
              style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ item.newValue }}
            </span>
          </RouterLink>
        </template>

        <template #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <RouterLink :to="{ name: routeName, params: { id: item.id }, query: { type: 'item' } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              <IconBtn>
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="itemQueryParams.page" :items-per-page="itemQueryParams.itemsPerPage"
            :totalItems="logsData.totalItemLogs" @update:page="onItemPageChange" locale="fr" />
        </template>
      </VDataTable>
    </VCard>
  </section>
</template>

<style>
/* Global styles */
.v-data-table-progress {
  height: 0;
  visibility: hidden;
  overflow: hidden;
}
</style>
