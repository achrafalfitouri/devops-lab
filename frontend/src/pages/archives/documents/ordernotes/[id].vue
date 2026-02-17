<script setup>
import { defineAsyncComponent } from 'vue'

const OrderNotesPreview = defineAsyncComponent(() => import('@/pages/documents/ordernotes/[id].vue'))
</script>

<template>
      <OrderNotesPreview /> 
</template>
