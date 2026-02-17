<script setup lang="ts">
import { fetchPaymentLogsById, fetchRecoveryLogsById } from '@/services/api/paymentlogs';
import LogBioPanel from '@/views/log/view/LogBioPanel.vue';
import type { Logs } from '@services/models';

const route = useRoute('logs-payments-id')

const paymentlogsData = ref<{
  paymentlogs: Logs | null
  isRecovery: boolean
}>({
  paymentlogs: null,
  isRecovery: false
})


// 👉 fetch paymentlogs data by id
const fetchPaymentLogsData = async () => {
  try {
    const id = await route.params.id
    const logType = route.query.type // Get the type from query parameter

    // If type is 'recovery', fetch recovery logs only
    if (logType === 'recovery') {
      const data = await fetchRecoveryLogsById(id)
      paymentlogsData.value.paymentlogs = data
      paymentlogsData.value.isRecovery = true
      return
    }

    // If type is 'payment' or no type specified, try payment logs first
    try {
      const data = await fetchPaymentLogsById(id)
      if (data) {
        paymentlogsData.value.paymentlogs = data
        paymentlogsData.value.isRecovery = false
        return
      }
    } catch (paymentError) {
      // If payment log not found and no type specified, try recovery logs
      if (!logType) {
        try {
          const data = await fetchRecoveryLogsById(id)
          if (data) {
            paymentlogsData.value.paymentlogs = data
            paymentlogsData.value.isRecovery = true
            return
          }
        } catch (recoveryError) {
          // Both failed
        }
      }
    }

    // If not found, set to null
    paymentlogsData.value.paymentlogs = null
  } catch (error) {
    console.error("Error fetching logs data:", error)
    paymentlogsData.value.paymentlogs = null
  }
}

onMounted(() => {
  fetchPaymentLogsData()
});


watch(
  () => route.params.id,
  () => {
    fetchPaymentLogsData()
  }
)

</script>

<template>
  <div>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          {{ paymentlogsData.isRecovery ? 'Détails de recouvrement logs' : 'Détails de paiement logs' }}
        </h4>
        <div class="text-body-1">
          {{ paymentlogsData.paymentlogs?.id }}
        </div>
      </div>
    </div>
    <!-- 👉 PaymentLogs Profile  -->
    <VRow v-if="paymentlogsData">
      <VCol v-if="paymentlogsData" cols="12" md="12" lg="12">
        <LogBioPanel :log-data="paymentlogsData.paymentlogs" @paymentlogs-updated="fetchPaymentLogsData" />
      </VCol>
    </VRow>
    <div v-else>
      <VAlert type="error" variant="tonal">
        {{ route.params.id }} pas trouvé!
      </VAlert>
    </div>
  </div>
</template>
