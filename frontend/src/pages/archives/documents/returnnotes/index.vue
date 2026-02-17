<script setup>
import { defineAsyncComponent } from 'vue'

const ReturnNoteList = defineAsyncComponent(() => import('@/pages/documents/returnnotes/index.vue'))
</script>

<template>
      <ReturnNoteList /> 
</template>
