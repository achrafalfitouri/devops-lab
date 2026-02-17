<script setup>
import { defineAsyncComponent } from 'vue'

const ClientPreview = defineAsyncComponent(() => import('@/pages/clients/[id].vue'))
</script>

<template>
      <ClientPreview /> 
</template>
