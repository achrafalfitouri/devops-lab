<script setup>
import { defineAsyncComponent } from 'vue'

const OrderReceiptList = defineAsyncComponent(() => import('@/pages/documents/orderreceipt/index.vue'))
</script>

<template>
      <OrderReceiptList /> 
</template>
