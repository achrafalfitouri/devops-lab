<script setup>
import { defineAsyncComponent } from 'vue'

const ProductionNotesPreview = defineAsyncComponent(() => import('@/pages/documents/productionnotes/[id].vue'))
</script>

<template>
      <ProductionNotesPreview /> 
</template>
