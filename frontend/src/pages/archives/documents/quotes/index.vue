<script setup>
import { defineAsyncComponent } from 'vue'

const QuoteList = defineAsyncComponent(() => import('@/pages/documents/quotes/index.vue'))
</script>

<template>
      <QuoteList /> 
</template>
