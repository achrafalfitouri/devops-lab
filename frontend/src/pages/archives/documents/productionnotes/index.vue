<script setup>
import { defineAsyncComponent } from 'vue'

const ProductionNoteList = defineAsyncComponent(() => import('@/pages/documents/productionnotes/index.vue'))
</script>

<template>
      <ProductionNoteList /> 
</template>
