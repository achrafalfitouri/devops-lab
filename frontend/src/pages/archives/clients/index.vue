<script setup>
import { defineAsyncComponent } from 'vue'

const ClientList = defineAsyncComponent(() => import('@/pages/clients/index.vue'))
</script>

<template>
      <ClientList /> 
</template>
