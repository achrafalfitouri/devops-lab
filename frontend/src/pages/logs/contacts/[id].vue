<script setup lang="ts">
import { fetchContactLogsById } from '@/services/api/contactlogs';
import LogBioPanel from '@/views/log/view/LogBioPanel.vue';
import type { Logs } from '@services/models';

const route = useRoute('logs-contacts-id')

const contactlogsData = ref<{
  contactlogs: Logs | null
}>({ contactlogs: null })


// 👉 fetch contactlogs data by id
const fetchContactLogsData = async () => {
  try {
    const id = await route.params.id
    const data = await fetchContactLogsById(id)
    contactlogsData.value.contactlogs = data
  } catch (error) {
    console.error("Error fetching contactlogs data:", error)
    contactlogsData.value.contactlogs = null
  }
}

onMounted(() => {
  fetchContactLogsData()
});


watch(
  () => route.params.id,
  () => {
    fetchContactLogsData()
  }
)

</script>

<template>
  <div>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Détails de contact logs
        </h4>
        <div class="text-body-1">
          {{ contactlogsData.contactlogs?.id }}
        </div>
      </div>
    </div>
    <!-- 👉 ContactLogs Profile  -->
    <VRow v-if="contactlogsData">
      <VCol v-if="contactlogsData" cols="12" md="12" lg="12">
        <LogBioPanel :log-data="contactlogsData.contactlogs" @contactlogs-updated="fetchContactLogsData" />
      </VCol>
    </VRow>
    <div v-else>
      <VAlert type="error" variant="tonal">
        {{ route.params.id }} pas trouvé!
      </VAlert>
    </div>
  </div>
</template>
