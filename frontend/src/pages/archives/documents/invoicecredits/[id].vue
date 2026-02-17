<script setup>
import { defineAsyncComponent } from 'vue'

const InvoiceCreditPreview = defineAsyncComponent(() => import('@/pages/documents/invoicecredits/[id].vue'))
</script>

<template>
      <InvoiceCreditPreview /> 
</template>
