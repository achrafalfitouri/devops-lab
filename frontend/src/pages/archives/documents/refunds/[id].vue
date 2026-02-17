<script setup>
import { defineAsyncComponent } from 'vue'

const RefundPreview = defineAsyncComponent(() => import('@/pages/documents/refunds/[id].vue'))
</script>

<template>
      <RefundPreview /> 
</template>
