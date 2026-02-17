<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import type { Client } from '@services/models';

const props = defineProps<{ clientId: any, data: any }>();

// 👉 State variable for storing info data
const infoData = ref<{
  info: Client | null
  totalinfo: number
}>({
  info: null,
  totalinfo: 0,
})

// 👉 Watch for changes in props.data
watch(
  () => props.data,
  (newData) => {
    if (newData) {
      infoData.value.info = newData;
      infoData.value.totalinfo = 1; // Single client, so total is 1
    }
  },
  { immediate: true, deep: true }
);

const isLoading = ref(true)

// 👉 Data table options
const sortBy = ref<string | undefined>(undefined)
const orderBy = ref<string | undefined>(undefined)

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// 👉 Table headers definition
const headers = [
  { title: 'Nom juridique', key: 'legalName' },
  { title: 'ICE', key: 'ice' },
  { title: 'IF', key: 'if' },
  { title: 'TP', key: 'tp' },
  { title: 'RC', key: 'rc' },
]

// 👉 Function to load info from props
const loadinfo = async () => {
  isLoading.value = true;
  try {
    // Only set data if props.data exists
    if (props.data) {
      infoData.value.info = props.data;
      infoData.value.totalinfo = 1; // Single client
    }
  } catch (error) {
    console.error('Error fetching info:', error);
  } finally {
    isLoading.value = false;
  }
};

// 👉 Call loadInfo on component setup
onMounted(() => {
  loadinfo();
});
</script>

<template>
  <VCard>
    <VDataTable 
      :headers="headers" 
      :items="infoData.info ? [infoData.info] : []" 
      item-value="id" 
      class="text-no-wrap"
      @update:options="updateOptions" 
      :loading="isLoading"
    >
      <template #loading>
        <div class="d-flex justify-center align-center loading-container">
          <VProgressCircular indeterminate color="primary" />
        </div>
      </template>

      <template #item.legalName="{ item }">
        <span>{{ item.legalName || '-' }}</span>
      </template>

      <template #item.ice="{ item }">
        <span>{{ item.ice || '-' }}</span>
      </template>

      <template #item.if="{ item }">
        <span>{{ item.if || '-' }}</span>
      </template>

      <template #item.tp="{ item }">
        <span>{{ item.tp || '-' }}</span>
      </template>

      <template #item.rc="{ item }">
        <span>{{ item.rc || '-' }}</span>
      </template>

      <template #bottom></template>
    </VDataTable>
  </VCard>
</template>

<style>
.v-data-table-progress {
  height: 0;
  visibility: hidden;
  overflow: hidden;
}
</style>