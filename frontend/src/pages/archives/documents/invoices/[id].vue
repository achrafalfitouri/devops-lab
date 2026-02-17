<script setup>
import { defineAsyncComponent } from 'vue'

const InvoicePreview = defineAsyncComponent(() => import('@/pages/documents/invoices/[id].vue'))
</script>

<template>
      <InvoicePreview /> 
</template>
