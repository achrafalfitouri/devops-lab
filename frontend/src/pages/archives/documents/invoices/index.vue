<script setup>
import { defineAsyncComponent } from 'vue'

const InvoiceList = defineAsyncComponent(() => import('@/pages/documents/invoices/index.vue'))
</script>

<template>
      <InvoiceList /> 
</template>
