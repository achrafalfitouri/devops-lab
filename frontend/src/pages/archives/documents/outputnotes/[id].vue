<script setup>
import { defineAsyncComponent } from 'vue'

const OutputNotesPreview = defineAsyncComponent(() => import('@/pages/documents/outputnotes/[id].vue'))
</script>

<template>
      <OutputNotesPreview /> 
</template>
