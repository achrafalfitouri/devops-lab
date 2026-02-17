<script setup>
import { defineAsyncComponent } from 'vue'

const QuoteRequestsPreview = defineAsyncComponent(() => import('@/pages/documents/quoterequests/[id].vue'))
</script>

<template>
      <QuoteRequestsPreview /> 
</template>
