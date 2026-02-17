<script setup lang="ts">


import { fetchUserLogss } from '@/services/api/userlogs';
import { getDefaultLogsFilterParams } from '@services/defaults';
import type { Logs, LogsFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { ref } from 'vue';
import { VRow } from 'vuetify/lib/components/index.mjs';

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 Filters and search
const filterParams = ref<LogsFilterParms>(getDefaultLogsFilterParams())


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
  actions: FilterItem[];
}>({
  actions: [
    { text: 'Modification', value: 'update' },
    { text: 'Suppression', value: 'delete' },
  ],

});

// 👉 State variable for storing userlogss data
const userlogssData = ref<{
  userlogss: Logs[]
  totalUserLogss: number
}>({
  userlogss: [],
  totalUserLogss: 0,
})

// 👉 Function to fetch userlogss from the API
const loadUserLogss = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;

    } else {
      if (filterParams.value.selectedAction) filters.action = filterParams.value.selectedAction;
    }
    const data = await fetchUserLogss(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    userlogssData.value.userlogss = data.userLogs || [];
    userlogssData.value.totalUserLogss = data.totalUserLogs || 0;
    if (userlogssData.value.userlogss.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadUserLogss();
    }

  } catch (error) {
    console.error('Error fetching user logs:', error);
  } finally {
    isLoading.value = false;
  }
};


// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadUserLogss()
});

// Replace throttledLoadUserLogss with debouncedLoadUserLogss
const debouncedLoadUserLogss = debounce(async () => {
  await loadUserLogss();
}, 800);

// 👉 Watchers to reload userlogss on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadUserLogss();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadUserLogss();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadUserLogss();
};

// 👉 clear filters
const onSearchInput = (newSearch: any) => {
  if (newSearch) {
    filterParams.value.selectedAction = null;
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
  { title: 'Log', key: 'id' },
  { title: 'Utilisateur', key: 'user' },
  { title: 'Action', key: 'action' },
  { title: 'Valeur ancienne', key: 'oldValue' },
  { title: 'Nouvelle valeur', key: 'newValue' },
  { title: 'Gestion', key: 'actions', sortable: false },
]


</script>

<template>
  <section>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Utilisateurs Logs
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
              { value: 100, title: '100' }
            ]" style="inline-size: 6.25rem;" @update:model-value="onItemsPerPageChange" />


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

    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable v-model:items-per-page="queryParams.itemsPerPage" :items="userlogssData.userlogss" :headers="headers"
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
          <RouterLink :to="{ name: 'logs-users-id', params: { id: item.id } }"
            class="d-flex align-center gap-1 text-decoration-none" style="color: inherit;">
            <span
              style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ item.oldValue }}
            </span>
          </RouterLink>
        </template>

        <template #item.newValue="{ item }">
          <RouterLink :to="{ name: 'logs-users-id', params: { id: item.id } }"
            class="d-flex align-center gap-1 text-decoration-none" style="color: inherit;">
            <span
              style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ item.newValue }}
            </span>
          </RouterLink>
        </template>

        <template #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <RouterLink :to="{ name: 'logs-users-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              <IconBtn>
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="userlogssData.totalUserLogss" @update:page="onPageChange" locale="fr" />
        </template>
      </VDataTable>
    </VCard>

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
