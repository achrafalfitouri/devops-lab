<script setup>
import { defineAsyncComponent } from 'vue'

const DeliveryNotesList = defineAsyncComponent(() => import('@/pages/documents/deliverynotes/index.vue'))
</script>

<template>
      <DeliveryNotesList /> 
</template>
