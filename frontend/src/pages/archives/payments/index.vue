<script setup>
import { defineAsyncComponent } from 'vue'

const PaymentList = defineAsyncComponent(() => import('@/pages/payments/index.vue'))
</script>

<template>
      <PaymentList /> 
</template>
