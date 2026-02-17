<script setup lang="ts">
import ClientBioPanel from '@/views/client/view/ClientBioPanel.vue'
import ClientTabOverview from '@/views/client/view/ClientTabOverview.vue'
import type { Client } from '@services/models';
import { fetchArchivedClientById, fetchClientById } from '@services/api/client'
import ClientTabOverviewInvoice from '@/views/client/view/ClientTabOverviewInvoice.vue';
import { useArchiveStoreStore } from '@/stores/archive';
import ClientTabOverviewDeliveryNotes from '@/views/client/view/ClientTabOverviewDeliveryNotes.vue';
import ClientTabOverviewOrderNotes from '@/views/client/view/ClientTabOverviewOrderNotes.vue';

const route = useRoute('clients-id')
const archiveStore = useArchiveStoreStore();


const clientData = ref<{
  client: Client | null
}>({ client: null })

const clientTab = ref(null)

const tabs = [
  { title: 'Général', icon: 'tabler-user' },
  { title: 'Bons de commande', icon: 'tabler-file-description' },
  { title: 'Bons de livraison', icon: 'tabler-truck-delivery' },
  { title: 'Factures', icon: 'tabler-file-dollar' }
]

// 👉 fetch client data by id
const fetchClientData = async () => {
  try {
    let data;
    const id = route.params.id
    const filters: any = {};
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
       data = await fetchArchivedClientById(id, filters);
    }
    else {
       data = await fetchClientById(id, filters);
    }
    clientData.value.client = data

  } catch (error) {
    clientData.value.client = null
  }
}

onMounted(() => {
  fetchClientData()
});

</script>

<template>
  <div>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Informations du client
        </h4>
        <div class="text-body-1">
          {{ clientData.client?.code }}
        </div>
      </div>
    </div>

    <!-- 👉 Client Profile  -->
    <VRow v-if="clientData">
      <VCol v-if="clientData" cols="12" md="5" lg="4">
        <ClientBioPanel :client-data="clientData?.client" @client-updated="fetchClientData" />
      </VCol>
      <VCol cols="12" md="7" lg="8">
        <VTabs v-model="clientTab" class="v-tabs-pill mb-3 disable-tab-transition">
          <VTab v-for="tab in tabs" :key="tab.title">
            <VIcon size="20" start :icon="tab.icon" />
            {{ tab.title }}
          </VTab>
        </VTabs>
        <VWindow v-model="clientTab" class="disable-tab-transition" :touch="false">
          <VWindowItem>
            <ClientTabOverview :clientId="route.params.id" :data="clientData?.client"/> 
          </VWindowItem>
          <VWindowItem>
            <ClientTabOverviewOrderNotes /> 
          </VWindowItem>
             <VWindowItem>
            <ClientTabOverviewDeliveryNotes /> 
          </VWindowItem>
          <VWindowItem>
            <ClientTabOverviewInvoice /> 
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>
    <div v-else>
      <VAlert type="error" variant="tonal">
       {{ route.params.id }} pas trouvé!
      </VAlert>
    </div>
  </div>
</template>
