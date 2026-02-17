<script setup lang="ts">
import { NumberOfClientsByActivitySector, NumberOfClientsByGamut, NumberOfClientsByType } from '@/services/api/client';
import { ref, onMounted } from 'vue';


const props = defineProps<{ loadClientGraphStats : any }>();
const emit = defineEmits(['update:loadClientGraphStats']);

const isLoading = ref(false)

const clientTypeData = ref<{ icon: string; title: string; percentage: number }[]>([]);
const clientSectorData = ref<{ icon: string; title: string; percentage: number }[]>([]);

const loadStats = async () => {

  try {
    isLoading.value = true;
    const response = await NumberOfClientsByType();
    const data = response.data;

    const totalClients = data.reduce((sum: number, item: any) => sum + item.clientCount, 0);

    clientTypeData.value = data.map((item: any) => ({
      icon: getIconForType(item.typeName),
      title: item.typeName,
      percentage: parseFloat(((item.clientCount / totalClients) * 100).toFixed(1)),
    }));
  } catch (error) {
    console.error('Error fetching client data:', error);
  }
  finally {
    isLoading.value = false;
  }

};
const loadActivitySectorStats = async () => {

  try {
    isLoading.value = true;
    const response = await NumberOfClientsByActivitySector();
    const data = response.data;

    const totalClients = data.reduce((sum: number, item: any) => sum + item.clientCount, 0);

    clientSectorData.value = data.map((item: any) => ({
      icon: getIconForType(item.sectorName),
      title: item.sectorName,
      percentage: parseFloat(((item.clientCount / totalClients) * 100).toFixed(1)),
    }));
  } catch (error) {
    console.error('Error fetching client data:', error);
  }
  finally {
    isLoading.value = false;
  }

};

onMounted(() => {
  loadStats();
  loadActivitySectorStats();
}
);

watch(
  () => props.loadClientGraphStats, 
  (newValue) => {
    if (newValue === true) {
      loadStats();
      loadActivitySectorStats();
      emit('update:loadClientGraphStats', false);
    }
  },
  { 
    immediate: true, 
  }
);

const syncedLoadClientGraphStats = computed({
  get: () => props.loadClientGraphStats,
  set: (value) => emit('update:loadClientGraphStats', value)
});

watch(syncedLoadClientGraphStats, (newValue) => {
  if (newValue) {
    loadStats();
    loadActivitySectorStats();
    syncedLoadClientGraphStats.value = false;
  }
}, { immediate: true });


// Helper function to get icons based on type_name
function getIconForType(typeName: string): string {
  switch (typeName) {
    case 'Entreprise':
      return 'tabler-building-skyscraper';
    case 'Institution':
      return 'tabler-building-bank';
    case 'Particulier':
      return 'tabler-user';
    default:
    return 'tabler-users-group';
  }
}


function getProgressBarClass(index: number, length: number): string {
  if (length === 1) return 'rounded-lg';

  if (index === 0) return 'rounded-e-0 rounded-lg';

  if (index === length - 1) return 'rounded-s-0 rounded-lg';
    return 'rounded-0';
}
</script>

<template>
  <VCard style="min-height: 380px;">
    <div v-if="clientTypeData?.length > 0  && !isLoading">
      <VCardItem title="Présentation des clients">
        <template #append>
        </template>
      </VCardItem>
      <VCardText>
       <!-- Legend/Key above progress bar -->
<div class="d-flex flex-wrap gap-4 mb-4">
  <div v-for="(clientSector, index) in clientSectorData" 
       :key="`legend-${index}`" 
       class="d-flex align-center">
    <div class="legend-color me-2" 
         :style="`background-color: ${getColor('sector' ,clientSector.title, index)}; width: 12px; height: 12px; border-radius: 2px;`">
    </div>
    <span class="text-sm">{{ clientSector.title }}</span>
  </div>
</div>

<!-- Progress bar without titles -->
<div class="d-flex mb-6" style="width: 100%;">
  <div v-for="(clientSector, index) in clientSectorData" 
       :key="`progress-${index}`" 
       :style="`flex: 0 0 ${clientSector.percentage}%;`">
    <VProgressLinear 
      :color="getColor('sector' ,clientSector.title, index)" 
      model-value="100" 
      height="46"
      style="border-radius: 0;"
      :class="getProgressBarClass(index, clientSectorData.length)">
      <div class="text-start text-sm font-weight-medium" style="color: white;">
        {{ clientSector.percentage }}%
      </div>
    </VProgressLinear>
  </div>
</div>
        <VTable class="text-no-wrap">
          <tbody>
            <tr v-for="(clienttype, index) in clientTypeData" :key="index">
              <td width="70%" style="padding-inline-start: 0 !important;">
                <div class="d-flex align-center gap-x-2">
                  <VIcon :icon="clienttype.icon" size="24" class="text-high-emphasis" />
                  <div class="text-body-1 text-high-emphasis">
                    {{ clienttype.title }}
                  </div>
                </div>
              </td>
              <td>
                <div class="text-body-1">
                  {{ clienttype.percentage }}%
                </div>
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </div>
    <div v-else-if="!isLoading && (!clientTypeData || clientTypeData.length === 0)" class="text-center py-4">
    <span class="text-medium-emphasis">Aucune donnée à afficher.</span>
  </div>
    <!-- Show loading spinner if days are not loaded -->
    <div v-else class="d-flex justify-center align-center  loading-container">
      <VProgressCircular indeterminate color="primary" />
    </div>
  </VCard>
</template>

<style lang="scss" scoped>
.clienttype-progress-label {
  padding-block-end: 1rem;
  width: 120px;
  white-space: nowrap;

  &::after {
    position: absolute;
    display: inline-block;
    background-color: rgba(var(--v-theme-on-surface), var(--v-border-opacity));
    block-size: 10px;
    content: "";
    inline-size: 2px;
    inset-block-end: 0;
    inset-inline-start: 0;

    [dir="rtl"] & {
      inset-inline: unset 0;
    }
  }
}

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
  min-height: 383px;
}
</style>

<style lang="scss">
.v-progress-linear__content {
  justify-content: start;
  padding-inline-start: 1rem;
}

#shipment-statistics .apexcharts-legend-series {
  padding-inline: 16px;
}

@media (max-width: 1080px) {
  #shipment-statistics .apexcharts-legend-series {
    padding-inline: 12px;
  }

  .v-progress-linear__content {
    padding-inline-start: 0.75rem !important;
  }
}

@media (max-width: 576px) {
  #shipment-statistics .apexcharts-legend-series {
    padding-inline: 8px;
  }

  .v-progress-linear__content {
    padding-inline-start: 0.125rem !important;
  }
}
</style>
