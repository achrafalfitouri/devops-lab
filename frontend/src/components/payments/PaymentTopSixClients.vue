<script setup lang="ts">
import bootstrapLogo from '@images/icons/brands/bootstrap-logo.png'
import figmaLogo from '@images/icons/brands/figma-logo.png'
import laravelLogo from '@images/icons/brands/laravel-logo.png'
import reactLogo from '@images/icons/brands/react-logo.png'
import sketchLogo from '@images/icons/brands/sketch-logo.png'
import vuejsLogo from '@images/icons/brands/vuejs-logo.png'
import { debounce } from 'lodash'
import { ref } from 'vue'


const props = defineProps<{
  statsData?: any
}>();

// 👉 State variable for storing city data
const topClients = ref<{ clientName: string; totalTurnover: number; percentage: string }[]>([]);
const activeProjects = ref<any[]>([]);

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 Function to load Top Six Clients from the API
const loadTopSixClients = async () => {
  isLoading.value = true;
  try {
    const data = props.statsData;

    topClients.value = data.map((entry: { clientName: string; totalTurnover: number, percentageOfTotal: number }) => ({
      ...entry,
      percentage: entry.percentageOfTotal,
    }));

    const logos = [laravelLogo, figmaLogo, vuejsLogo, reactLogo, bootstrapLogo, sketchLogo];

    activeProjects.value = topClients.value.map((client, index) => ({
      avatarImg: logos[index % logos.length],
      title: client.clientName,
      subtitle: client.totalTurnover,
      stats: client.percentage,
      progressColor: 'primary',
    }));
  } catch (error) {
    console.error('Error fetching top six clients:', error);
  } finally {
    isLoading.value = false;
  }
};
const debouncedLoadClients = debounce(loadTopSixClients, 500)


watch(
  () => props.statsData,
  (newData) => {
    if (newData && newData.length) {
      isLoading.value = true;
      debouncedLoadClients();
    }
  },
  { immediate: true }
)

</script>

<template>
  <VCard style="min-height: 455px;">
    <div v-if="!isLoading && topClients && topClients.length > 0">
      <VCardItem>
        <VCardTitle>Les meilleurs six clients</VCardTitle>
        <VCardSubtitle>
          Les meilleurs clients de l'année
        </VCardSubtitle>
        <template #append>
          <div class="mt-n4 me-n2">
          </div>
        </template>
      </VCardItem>

      <VCardText>
        <VList class="card-list">
          <VListItem v-for="project in activeProjects" :key="project.title">
            <VListItemTitle class="font-weight-medium">
              {{ project.title }}
            </VListItemTitle>
            <VListItemSubtitle class="me-4">
              {{ project.subtitle }}
            </VListItemSubtitle>

            <template #append>
              <div class="d-flex align-center gap-x-4">
                <div style="inline-size: 4.875rem;">
                  <VProgressLinear :model-value="project.stats" :color="project.progressColor" height="8" rounded-bar
                    rounded />
                </div>
                <span class="text-disabled">{{ project.stats }}%</span>
              </div>
            </template>
          </VListItem>
        </VList>
      </VCardText>
    </div>
    <div v-else-if="!topClients.length" class="d-flex justify-center align-center  loading-container">
      <VProgressCircular indeterminate color="primary" />
    </div>

    <div v-else class="text-center py-4">
      <span class="text-medium-emphasis">Aucune donnée à afficher.</span>
    </div>

  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 16px;
}

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
  min-height: 455px;
}
</style>
