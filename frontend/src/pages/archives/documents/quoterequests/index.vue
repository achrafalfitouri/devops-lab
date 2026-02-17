<script setup>
import { defineAsyncComponent } from 'vue'

const QuoteRequestList = defineAsyncComponent(() => import('@/pages/documents/quoterequests/index.vue'))
</script>

<template>
      <QuoteRequestList /> 
</template>
