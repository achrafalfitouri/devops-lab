<!-- LogsDetailView.vue -->
<script setup lang="ts">
import { fetchDocumentItemLogsById, fetchDocumentLogsById } from '@/services/api/documentlogs';
import LogBioPanel from '@/views/log/view/LogBioPanel.vue';
import type { Logs } from '@services/models';
import { ref, watch, onMounted } from 'vue';
import { RouteLocationNormalizedLoaded, useRoute } from 'vue-router';

interface LogRouteParams {
  id: string;
  logType?: string;
}

const route = useRoute() as RouteLocationNormalizedLoaded & { params: LogRouteParams };

const logsData = ref<{
  log: Logs | null;
  loading: boolean;
  error: string | null;
}>({
  log: null,
  loading: false,
  error: null
});

// Determine log type from route params
const getLogType = () => {
  return route.query.type?.toString() ;
};

// Fetch log data based on ID and type
const fetchLogData = async () => {
  const id = route.params.id?.toString();
  if (!id) {
    logsData.value.error = "ID de log non fourni";
    return;
  }

  logsData.value.loading = true;
  logsData.value.error = null;
  
  try {
    const logType = getLogType();
    
    if (logType === 'item') {
      // Fetch item log
      const data = await fetchDocumentItemLogsById(id);
      logsData.value.log = data;
    } else {
      // Default to document log
      const data = await fetchDocumentLogsById(id);
      logsData.value.log = data;
    }
  } catch (error) {
    console.error("Error fetching log data:", error);
    logsData.value.error = "Erreur lors du chargement des données du log";
    logsData.value.log = null;
  } finally {
    logsData.value.loading = false;
  }
};

// Get document type name based on the current route
const getDocumentTypeName = () => {
  const routeName = route.name?.toString() || '';
  
  if (routeName.includes('deliverynotes')) return 'bon de livraison';
  if (routeName.includes('invoicecredits')) return 'facture avoir';
  if (routeName.includes('invoices')) return 'facture';
  if (routeName.includes('ordernotes')) return 'bon de commande';
  if (routeName.includes('orderreceipts')) return 'reçu de commande';
  if (routeName.includes('outputnotes')) return 'bon de sortie';
  if (routeName.includes('productionnotes')) return 'bon de production';
  if (routeName.includes('quotes')) return 'devis';
  if (routeName.includes('refunds')) return 'bon de remboursement';
  if (routeName.includes('returnnotes')) return 'bon de retour';
  
  return 'document';
};

// Watch for changes in route params
watch(
  () => [route.params.id, route.params.logType],
  () => {
    fetchLogData();
  }
);

// Initial data load
onMounted(() => {
  fetchLogData();
});
</script>

<template>
  <div>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Détails de {{ getDocumentTypeName() }} logs
        </h4>
        <div v-if="logsData.log" class="text-body-1">
          {{ logsData.log.id }}
        </div>
      </div>
    </div>
    
    <!-- Loading state -->
    <VProgressCircular v-if="logsData.loading" indeterminate color="primary" class="d-block mx-auto my-8" />
    
    <!-- Error state -->
    <VAlert v-else-if="logsData.error" type="error" variant="tonal" class="mb-6">
      {{ logsData.error }}
    </VAlert>
    
    <!-- No data state -->
    <VAlert v-else-if="!logsData.log" type="error" variant="tonal" class="mb-6">
      {{ route.params.id }} pas trouvé!
    </VAlert>
    
    <!-- 👉 Log Data Display -->
    <VRow v-else>
      <VCol cols="12" md="12" lg="12">
        <LogBioPanel :log-data="logsData.log" @log-updated="fetchLogData" />
      </VCol>
    </VRow>
  </div>
</template>
