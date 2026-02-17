<script setup>
import { defineAsyncComponent } from 'vue'

const OrderReceiptPreview = defineAsyncComponent(() => import('@/pages/documents/orderreceipt/[id].vue'))
</script>

<template>
      <OrderReceiptPreview /> 
</template>
