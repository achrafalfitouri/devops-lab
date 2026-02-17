<script setup lang="ts">
// Accept cash register as prop
const props = defineProps({
  cashRegister: Object,
  onEdit: {
    type: Function,
    default: () => {},
  },
  onDelete: {
    type: Function,
    default: () => {},
  },
});
</script>

<template>
  <VCard>
    <VRow no-gutters>
      <VCol cols="8">
        <VCardText>
          <h5 class="text-h5 text-no-wrap">
            {{ cashRegister?.name }}
          </h5>
          <p class="mb-2">
            {{ cashRegister?.code }}
          </p>
          <h4 class="text-h4 text-primary mb-1">
            {{ cashRegister?.balance }} DH
          </h4>

          <VCol cols="12" class="d-flex flex-wrap justify-start gap-4">
            <!-- Edit Button -->
            <VBtn @click="onEdit(cashRegister)">Edit</VBtn>
            <!-- Delete Button -->
            <VBtn color="secondary" @click="onDelete(cashRegister)">Delete</VBtn>
          </VCol>
        </VCardText>
      </VCol>
    </VRow>
  </VCard>
</template>
