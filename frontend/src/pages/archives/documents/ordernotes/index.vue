<script setup>
import { defineAsyncComponent } from 'vue'

const OrderNoteList = defineAsyncComponent(() => import('@/pages/documents/ordernotes/index.vue'))
</script>

<template>
      <OrderNoteList /> 
</template>
