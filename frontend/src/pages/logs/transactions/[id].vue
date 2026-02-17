<script setup lang="ts">
import { fetchTransactionLogsById } from '@/services/api/transactionlogs';
import LogBioPanel from '@/views/log/view/LogBioPanel.vue';
import type { Logs } from '@services/models';

const route = useRoute('logs-transactions-id')

const transactionlogsData = ref<{
  transactionlogs: Logs | null
}>({ transactionlogs: null })


// 👉 fetch transactionlogs data by id
const fetchTransactionLogsData = async () => {
  try {
    const id = await route.params.id
    const data = await fetchTransactionLogsById(id)
    transactionlogsData.value.transactionlogs = data
  } catch (error) {
    console.error("Error fetching transactionlogs data:", error)
    transactionlogsData.value.transactionlogs = null
  }
}

onMounted(() => {
  fetchTransactionLogsData()
});


watch(
  () => route.params.id,
  () => {
    fetchTransactionLogsData()
  }
)

</script>

<template>
  <div>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Détails de transaction logs
        </h4>
        <div class="text-body-1">
          {{ transactionlogsData.transactionlogs?.id }}
        </div>
      </div>
    </div>
    <!-- 👉 TransactionLogs Profile  -->
    <VRow v-if="transactionlogsData">
      <VCol v-if="transactionlogsData" cols="12" md="12" lg="12">
        <LogBioPanel :log-data="transactionlogsData.transactionlogs" @transactionlogs-updated="fetchTransactionLogsData" />
      </VCol>
    </VRow>
    <div v-else>
      <VAlert type="error" variant="tonal">
        {{ route.params.id }} pas trouvé!
      </VAlert>
    </div>
  </div>
</template>
