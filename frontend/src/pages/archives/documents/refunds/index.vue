<script setup>
import { defineAsyncComponent } from 'vue'

const RefundList = defineAsyncComponent(() => import('@/pages/documents/refunds/index.vue'))
</script>

<template>
      <RefundList /> 
</template>
