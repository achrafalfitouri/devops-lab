<script setup>
import { defineAsyncComponent } from 'vue'

const OutputNotesList = defineAsyncComponent(() => import('@/pages/documents/outputnotes/index.vue'))
</script>

<template>
      <OutputNotesList /> 
</template>
