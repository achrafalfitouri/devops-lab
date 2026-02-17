<script setup>
import { defineAsyncComponent } from 'vue'

const DeliveryNotePreview = defineAsyncComponent(() => import('@/pages/documents/deliverynotes/[id].vue'))
</script>

<template>
      <DeliveryNotePreview /> 
</template>
