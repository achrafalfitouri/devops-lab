<script setup lang="ts">
import { fetchUserLogsById } from '@/services/api/userlogs';
import LogBioPanel from '@/views/log/view/LogBioPanel.vue';
import type { Logs } from '@services/models';

const route = useRoute('logs-users-id')

const userlogsData = ref<{
  userlogs: Logs | null
}>({ userlogs: null })


// 👉 fetch userlogs data by id
const fetchUserLogsData = async () => {
  try {
    const id = await route.params.id
    const data = await fetchUserLogsById(id)
    userlogsData.value.userlogs = data
  } catch (error) {
    console.error("Error fetching userlogs data:", error)
    userlogsData.value.userlogs = null
  }
}

onMounted(() => {
  fetchUserLogsData()
});


watch(
  () => route.params.id,
  () => {
    fetchUserLogsData()
  }
)

</script>

<template>
  <div>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Détails d’utilisateur logs
        </h4>
        <div class="text-body-1">
          {{ userlogsData.userlogs?.id }}
        </div>
      </div>
    </div>
    <!-- 👉 UserLogs Profile  -->
    <VRow v-if="userlogsData">
      <VCol v-if="userlogsData" cols="12" md="12" lg="12">
        <LogBioPanel :log-data="userlogsData.userlogs" @userlogs-updated="fetchUserLogsData" />
      </VCol>
    </VRow>
    <div v-else>
      <VAlert type="error" variant="tonal">
        {{ route.params.id }} pas trouvé!
      </VAlert>
    </div>
  </div>
</template>
