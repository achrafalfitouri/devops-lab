<script setup lang="ts">

import { fetchContactLogss } from '@/services/api/contactlogs';
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

// 👉 State variable for storing contactlogss data
const contactlogssData = ref<{
  contactlogss: Logs[]
  totalContactLogss: number
}>({
  contactlogss: [],
  totalContactLogss: 0,
})

// 👉 Function to fetch contactlogss from the API
const loadContactLogss = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;

    } else {
      if (filterParams.value.selectedAction) filters.action = filterParams.value.selectedAction;
    }
    const data = await fetchContactLogss(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    contactlogssData.value.contactlogss = data.contactLogs || [];
    contactlogssData.value.totalContactLogss = data.totalContactLogs || 0;
    if (contactlogssData.value.contactlogss.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadContactLogss();
    }

  } catch (error) {
    console.error('Error fetching contact logs:', error);
  } finally {
    isLoading.value = false;
  }
};



// // 👉 Fetch static data and populate filter options
// const loadStaticData = async () => {
//   try {
//     const staticData = await fetchStaticData();
//     filterItems.value.actions = mapStaticData(staticData.data.actions);
//   } catch (error) {
//     console.error("Error fetching static data:", error);
//   }
// };

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadContactLogss()
  // loadStaticData();
});

// Replace throttledLoadContactLogss with debouncedLoadContactLogss
const debouncedLoadContactLogss = debounce(async () => {
  await loadContactLogss();
}, 800);

// 👉 Watchers to reload contactlogss on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadContactLogss();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadContactLogss();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadContactLogss();
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
  { title: 'Acrion', key: 'action' },
  { title: 'Old value', key: 'oldValue' },
  { title: 'New value', key: 'newValue' },
  { title: 'Gestion', key: 'actions', sortable: false },
]


</script>

<template>
  <section>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Contacts Logs
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
      <VDataTable v-model:items-per-page="queryParams.itemsPerPage" :items="contactlogssData.contactlogss"
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
          <RouterLink :to="{ name: 'logs-contacts-id', params: { id: item.id } }"
            class="d-flex align-center gap-1 text-decoration-none" style="color: inherit;">
            <span
              style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ item.oldValue }}
            </span>
          </RouterLink>
        </template>

        <template #item.newValue="{ item }">
          <RouterLink :to="{ name: 'logs-contacts-id', params: { id: item.id } }"
            class="d-flex align-center gap-1 text-decoration-none" style="color: inherit;">
            <span
              style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ item.newValue }}
            </span>
          </RouterLink>
        </template>


        <template #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <RouterLink :to="{ name: 'logs-contacts-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              <IconBtn>
                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="contactlogssData.totalContactLogss" @update:page="onPageChange" locale="fr" />
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
