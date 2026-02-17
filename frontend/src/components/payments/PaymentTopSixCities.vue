<script setup lang="ts">
import { TopSixCities } from '@/services/api/payment'
import bootstrapLogo from '@images/icons/brands/bootstrap-logo.png'
import figmaLogo from '@images/icons/brands/figma-logo.png'
import laravelLogo from '@images/icons/brands/laravel-logo.png'
import reactLogo from '@images/icons/brands/react-logo.png'
import sketchLogo from '@images/icons/brands/sketch-logo.png'
import vuejsLogo from '@images/icons/brands/vuejs-logo.png'
import { debounce } from 'lodash'
import { ref, onMounted } from 'vue'


const props = defineProps<{
  selectedYear: String | null
}>();

// 👉 State variable for storing city data
const topCities = ref<{ cityName: string; totalTurnover: number; percentage: string }[]>([]);
const activeProjects = ref<any[]>([]);

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 Function to load Top Six Cities from the API
const loadTopSixCities = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};
    if (props.selectedYear) filters.year = props.selectedYear;
    const data = await TopSixCities(filters);

    topCities.value = data.map((entry: { cityName: string; totalTurnover: number , percentageOfTotal : number }) => ({
      ...entry,
      percentage: entry.percentageOfTotal,
    }));

    const logos = [laravelLogo, figmaLogo, vuejsLogo, reactLogo, bootstrapLogo, sketchLogo];

    activeProjects.value = topCities.value.map((city, index) => ({
      avatarImg: logos[index % logos.length],
      title: city.cityName,
      subtitle: 'CA',
      stats: city.percentage,
      progressColor: 'primary',
    }));
  } catch (error) {
    console.error('Error fetching top six cities:', error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  loadTopSixCities();
});

const debouncedLoadPayments = debounce(async () => {
  await loadTopSixCities();
}, 800);

watch(() => props.selectedYear, () => {
  debouncedLoadPayments();
}, { deep: true });


</script>

<template>
  <VCard>
    <div v-if="topCities.length  && isLoading === false">
      <VCardItem>
        <VCardTitle>Active Projects</VCardTitle>
        <VCardSubtitle>
          Average 72% completed
        </VCardSubtitle>
        <template #append>
          <div class="mt-n4 me-n2">
          </div>
        </template>
      </VCardItem>

      <VCardText>
        <VList class="card-list">
          <VListItem v-for="project in activeProjects" :key="project.title">
            <template #prepend>
              <VAvatar size="34" rounded class="me-1">
                <VImg :src="project.avatarImg" />
              </VAvatar>
            </template>

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
    <div  v-else class="d-flex justify-center align-center  loading-container">
      <VProgressCircular indeterminate color="primary" />
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
