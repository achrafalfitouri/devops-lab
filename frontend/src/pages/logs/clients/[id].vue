<script setup lang="ts">
import { fetchClientLogsById } from '@/services/api/clientlogs';
import LogBioPanel from '@/views/log/view/LogBioPanel.vue';
import type { Logs } from '@services/models';

const route = useRoute('logs-clients-id')

const clientlogsData = ref<{
  clientlogs: Logs | null
}>({ clientlogs: null })


// 👉 fetch clientlogs data by id
const fetchClientLogsData = async () => {
  try {
    const id = await route.params.id
    const data = await fetchClientLogsById(id)
    clientlogsData.value.clientlogs = data
  } catch (error) {
    console.error("Error fetching clientlogs data:", error)
    clientlogsData.value.clientlogs = null
  }
}

onMounted(() => {
  fetchClientLogsData()
});


watch(
  () => route.params.id,
  () => {
    fetchClientLogsData()
  }
)

</script>

<template>
  <div>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Détails de client logs
        </h4>
        <div class="text-body-1">
          {{ clientlogsData.clientlogs?.id }}
        </div>
      </div>
    </div>
    <!-- 👉 ClientLogs Profile  -->
    <VRow v-if="clientlogsData">
      <VCol v-if="clientlogsData" cols="12" md="12" lg="12">
        <LogBioPanel :log-data="clientlogsData.clientlogs" @clientlogs-updated="fetchClientLogsData" />
      </VCol>
    </VRow>
    <div v-else>
      <VAlert type="error" variant="tonal">
        {{ route.params.id }} pas trouvé!
      </VAlert>
    </div>
  </div>
</template>
