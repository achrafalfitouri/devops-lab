<template>
  <VDialog no-click-animation max-width="500" v-model="isDialogVisible" persistent>
    <VCard class="text-center px-10 py-6">
      <VCardText>
        <VBtn icon variant="outlined" color="error" class="my-4" style="
        block-size: 88px;
        inline-size: 88px;
        pointer-events: none;
        border-width: 4px; 
        border-radius: 50%; 
        ">
          <span class="text-5xl">!</span>
        </VBtn>

        <h6 class="text-lg font-weight-medium">
          {{ message }}
        </h6>
      </VCardText>

      <VCardText class="d-flex align-center justify-center gap-2">

        <VBtn :color="deleteButtonColor" variant="elevated" @click="onDeleteClick">
          {{ deleteButtonText }}
        </VBtn>

        <VBtn :color="cancelButtonColor" variant="tonal" @click="onCancelClick">
          {{ cancelButtonText }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script>
export default {
  name: 'AlertDialog',
  props: {
    isVisible: {
      type: Boolean,
      required: true,
    },
    message: {
      type: String,
      default: 'Are you sure you want to delete this item? This action cannot be undone.',
    },
    deleteButtonText: {
      type: String,
      default: 'Delete',
    },
    cancelButtonText: {
      type: String,
      default: 'Cancel',
    },
    deleteButtonColor: {
      type: String,
      default: 'error',
    },
    cancelButtonColor: {
      type: String,
      default: 'secondary',
    },
    onDeleteClick: {
      type: Function,
      default: () => { },
    },
    onCancelClick: {
      type: Function,
      default: () => { },
    },
  },
  emits: ['update:isVisible'],
  computed: {
    isDialogVisible: {
      get() {
        return this.isVisible;
      },
      set(value) {
        this.$emit('update:isVisible', value);
      },
    },
  },
};
</script>
