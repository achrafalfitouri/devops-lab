<script setup>
import { defineAsyncComponent } from 'vue'

const UserList = defineAsyncComponent(() => import('@/pages/users/index.vue'))
</script>

<template>
      <UserList /> 
</template>
