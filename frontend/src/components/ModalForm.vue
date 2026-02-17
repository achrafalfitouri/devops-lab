<script setup lang="ts">
defineProps<{
  isDatePickerOpen?: boolean;
}>()
</script>

<template>
  <VDialog no-click-animation>
    <slot name="close-btn"></slot>


    <VCard class="pa-sm-10 pa-2" :class="{ 'overflow-visible-lg': isDatePickerOpen }">

      <VCardText>
        <slot name="title"></slot>
        <div class="mb-10"></div>
        <slot name="form"></slot>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
  .overflow-visible-lg {
    overflow: visible !important;
  }

</style>
