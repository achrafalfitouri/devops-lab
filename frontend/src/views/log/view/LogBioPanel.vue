<script setup lang="ts">
import JsonPretty from 'vue-json-pretty'
import 'vue-json-pretty/lib/styles.css'


//👉 Props and ref declarations
const props = defineProps<{ logData: any }>()
const emit = defineEmits(['userlog-updated'])

const parseLogData = (value: any) => {
  if (typeof value === 'string') {
    try {
      return JSON.parse(value); 
    } catch (e) {
      return value; 
    }
  }
  return value;
}
</script>

<template>
  <VRow>
    <!-- SECTION Customer Details -->
    <VCol cols="12">
      <VCard v-if="props.logData">
        <!-- 👉 Customer Details -->
        <VCardText>
          <h5 class="text-h5">Details</h5>

          <VDivider class="my-4" />

          <VList class="card-list mt-2">
            <VListItem>
              <h6 class="text-h6">
                Action:
                <span v-if="props.logData?.action" class="text-body-1 d-inline-block">
                  {{ props.logData?.action }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Utilisateur:
                <span v-if="props.logData?.user" class="text-body-1 d-inline-block">
                  {{ props.logData?.user }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Entité Id:
                <span v-if="props.logData?.entityId" class="text-body-1 d-inline-block">
                  {{ props.logData?.entityId }}
                </span>
              </h6>
            </VListItem>
            <VListItem>
              <h6 class="text-h6">
                Date:
                <span v-if="props.logData?.entityId" class="text-body-1 d-inline-block">
                  {{ formatDateDDMMYYYY(props.logData?.createdAt) }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Valeur ancienne:
              </h6>
              <div v-if="props.logData?.oldValue">
                <JsonPretty :data="parseLogData(props.logData.oldValue)" />
              </div>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Nouvelle valeur:
              </h6>
              <div v-if="props.logData?.newValue">
                <JsonPretty :data="parseLogData(props.logData.newValue)" />
              </div>
            </VListItem>

          </VList>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.current-plan {
  background: linear-gradient(45deg, rgb(var(--v-theme-primary)) 0%, #9e95f5 100%);
  color: #fff;
}

.rounded-circle {
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: white;
  border: 2px dashed #ddd;
  border-radius: 50%;
}
</style>
