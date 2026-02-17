<script setup>
import { defineAsyncComponent } from 'vue'

const CashRegisterList = defineAsyncComponent(() => import('@/pages/cashregisters/index.vue'))
</script>

<template>
      <CashRegisterList /> 
</template>
