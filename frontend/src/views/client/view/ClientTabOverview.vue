<script setup lang="ts">
import ClientContactTable from './ClientContactTable.vue'
import ClientInformationTable from './ClientInformationTable.vue';

const props = defineProps<{ clientId: any , data : any }>();

</script>

<template>
  <VRow>
    <!-- <VCol cols="12" md="6">
      <VCard>
        <VCardText class="d-flex gap-y-2 flex-column">
          <VAvatar variant="tonal" color="success" icon="tabler-gift" rounded />
          <h5 class="text-h5">
            Chiffre d’affaire </h5>
          <div>
            <VChip color="success" class="mb-2" label size="small">
              Platinum member
            </VChip>
            <p class="mb-0">
              3000 points to next tier
            </p>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" md="6">
      <VCard>
        <VCardText class="d-flex gap-y-2 flex-column">
          <VAvatar variant="tonal" color="primary" icon="tabler-currency-dollar" rounded />
          <h5 class="text-h5">
            Impayés
          </h5>
          <div>
            <h5 class="text-h5 text-primary mb-1">
              $7480
              <span class="text-body-1 d-inline-block">Credit Left</span>
            </h5>
            <p class="mb-0">
              Account balance for next purchase
            </p>
          </div>
        </VCardText>
      </VCard>
    </VCol> -->


    <VCol>
      <ClientInformationTable  :clientId="props.clientId" :data="props.data"  />
      <div class="my-4" >
        </div>
      <ClientContactTable :clientId="props.clientId" :data="props.data"  />
    </VCol>
  </VRow>
</template>
