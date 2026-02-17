<script lang="ts" setup>
import { defineProps, defineEmits, ref, onMounted, onUpdated } from 'vue'

const props = defineProps({
  isVisible: {
    type: Boolean,
    required: true
  },
  message: {
    type: String,
    required: true
  },
  color: {
    type: String,
    default: 'success'
  }
})

const emit = defineEmits(['close'])

const localVisible = ref(props.isVisible)

onMounted(() => {
  localVisible.value = props.isVisible;
})

onUpdated(() => {
  localVisible.value = props.isVisible;
})

const handleClose = () => {
  localVisible.value = false;
  emit('close');
}
</script>

<template>
  <VSnackbar
    v-model="localVisible"
    :timeout="5000"
    :color="props.color"
    location="top end"
    variant="flat"
    @update:modelValue="handleClose"
  >
    {{ props.message }}
    <template #actions>
      <VBtn color="white" text @click="handleClose">OK</VBtn>
    </template>
  </VSnackbar>
</template>
