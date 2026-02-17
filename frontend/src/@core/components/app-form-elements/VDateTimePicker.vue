<script setup lang="ts">
import { ref, defineProps, defineEmits, watch, computed, PropType } from 'vue';

const props = defineProps({
  modelValue: {
    type: [Date, null] as PropType<Date | null>,
    default: null,
  },
  label: String,
  placeholder: String,
  disabled: {
    type: Boolean,
    default: false,
  },
  allowedDates: {
    type: Function as PropType<(date: Date) => boolean>,
    default: () => true,
  },
});

const emit = defineEmits(['update:modelValue']);

const selectedDate = ref<Date | null>(props.modelValue);
const isMenuOpen = ref(false);

// Sync prop changes to `selectedDate`
watch(
  () => props.modelValue,
  (newValue) => {
    selectedDate.value = newValue;
  }
);

// Sync `selectedDate` changes back to parent
watch(
  () => selectedDate.value,
  (newValue) => {
    emit('update:modelValue', newValue);
  }
);

// Format `selectedDate` for display
const formattedDate = computed(() => {
  if (!selectedDate.value) return '';
  return selectedDate.value.toLocaleDateString();
});
</script>


<template>
  <div class="custom-date-time-picker">
    <v-text-field
      :label="label"
      :disabled="disabled"
      :placeholder="placeholder"
      readonly
      class="compact-input"
      :value="formattedDate" 
      @click="isMenuOpen = true"
    />

    <v-menu
      v-model="isMenuOpen"
      :close-on-content-click="false"
      transition="scale-transition"
      offset-y
      max-width="100%"
      class="scaled-picker"
    >
      <template #activator="{ props }">
        <span v-bind="props"></span>
      </template>
      <v-date-picker
        v-model="selectedDate"
        @change="isMenuOpen = false"
        class="custom-date-picker"
        color="primary"
        :hide-header="true"
        :landscape="true"
        :height="420"
        style="font-size: 20px;"
        :allowed-dates="props.allowedDates"
      />
    </v-menu>
  </div>
</template>


<style scoped>
/* Ensure v-menu is visible and properly positioned */
.v-menu__content {
  overflow: hidden; /* Remove scrollbars */
  padding: 8px; /* Adjust padding as needed */
}

/* Scale v-date-picker */
.scaled-picker {
  transform: scale(0.8);
  transform-origin: top center; 
}

/* Hide scrollbars and make picker more compact */
.custom-date-picker {
  max-height: 100%;
  overflow: hidden;
}

/* Debugging: Add borders for clarity (optional) */
/* stylelint-disable-next-line @stylistic/selector-list-comma-space-before */
.scaled-picker , .v-menu__content {
  border: 1px solid rgba(0, 0, 0, 0.1); /* Optional visual aid */
}
</style>

<style>

.v-picker__body .v-date-picker-months .v-date-picker-months__content .v-btn {
  color: #2f2b2de6 !important;
}
.v-picker__body .v-date-picker-months .v-date-picker-month__days {
  font-size: 20px !important;
}
.v-picker__body .v-date-picker-months .v-date-picker-months__content .v-btn--active {
  color: #fff !important;
}

.v-picker__body .v-date-picker-controls .v-btn__content {
  color: #2f2b2de6 !important;
}

.v-picker__body .v-date-picker-controls .text-primary {
  color: #2f2b2de6 !important;
}

.v-picker__body .v-date-picker-years .v-date-picker-years__content .v-btn {
  color: #2f2b2de6 !important;
}
.v-picker__body .v-date-picker-years .v-date-picker-years__content .v-btn--active {
  color: #fff !important;
}
</style>
<style lang="scss">
.custom-date-time-picker .v-input__control .v-field .v-field__field .v-field__input {
  cursor: pointer !important;
}

</style>
