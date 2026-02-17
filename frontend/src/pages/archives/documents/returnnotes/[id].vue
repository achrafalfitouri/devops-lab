<script setup>
import { defineAsyncComponent } from 'vue'

const ReturnNotePreview = defineAsyncComponent(() => import('@/pages/documents/returnnotes/[id].vue'))
</script>

<template>
      <ReturnNotePreview /> 
</template>
