<script setup lang="ts">
interface Props {
  icon?: string
  iconSize?: string
}

const props = withDefaults(defineProps<Props>(), {
  icon: 'tabler-x',
  iconSize: '25',
})
</script>

<template>
  <IconBtn
    variant="text"
    size="30"
    :ripple="false"
    class="v-dialog-close-btn"
  >
    <VIcon
      :icon="props.icon"
      :size="props.iconSize"
    />
  </IconBtn>
</template>
