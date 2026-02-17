<script setup>
import { defineAsyncComponent } from 'vue'

const QuotesPreview = defineAsyncComponent(() => import('@/pages/documents/quotes/[id].vue'))
</script>

<template>
      <QuotesPreview /> 
</template>
