<script setup>
import { defineAsyncComponent } from 'vue'

const InvoiceCreditList = defineAsyncComponent(() => import('@/pages/documents/invoicecredits/index.vue'))
</script>

<template>
      <InvoiceCreditList /> 
</template>
