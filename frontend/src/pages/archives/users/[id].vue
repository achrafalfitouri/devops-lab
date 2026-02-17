<script setup>
import { defineAsyncComponent } from 'vue'

const UserPreview = defineAsyncComponent(() => import('@/pages/users/[id].vue'))
</script>

<template>
      <UserPreview /> 
</template>
