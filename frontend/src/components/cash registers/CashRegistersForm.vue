<script setup lang="ts">
import { addCashRegister, updateCashRegister } from '@/services/api/cashregister';
import { getDefaultCashRegister } from '@services/defaults';
import type { CashRegister } from '@services/models';
import { useCashRegisterStore } from '@stores/cashregister';
import { computed, ref, watch } from 'vue';
import { VForm } from 'vuetify/components/VForm';

const store = useCashRegisterStore();
const emit = defineEmits(['close', 'submit']);


// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Loading state
const loading = ref(false);

const refForm = ref<VForm | null>(null);
const isFormValid = ref(false);
const CashRegisterFormDefaults = ref<CashRegister>(store.selectedCashRegister || getDefaultCashRegister());

const requiredValidator = (v: any) => !!v || 'Ce champ est obligatoire';

const formTitle = computed(() => 'Enregistrer');

const closeDrawer = () => {
  store.closeDrawer();
  refForm.value?.reset();
  refForm.value?.resetValidation();
  emit('close');
};

const handleFormSubmit = () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loading.value = true;
      try {

        const payload = {
          ...CashRegisterFormDefaults.value,
        };

        if (store.mode === 'add') {
          const response = await addCashRegister(payload);
          const cashregisterId = response.id;
          store.cashregisters.push({ ...payload, id: cashregisterId });
        } else if (store.mode === 'edit') {
          const cashregisterId = payload.id;
          await updateCashRegister(cashregisterId, payload);

          const index = store.cashregisters.findIndex((c) => c.id === cashregisterId);
          if (index !== -1) {
            store.cashregisters[index] = { ...payload, id: cashregisterId };
          }
        }
        closeDrawer();
        emit('submit');
      } catch (error) {
        console.error('Error preparing user data:', error);
        closeDrawer();
        const err = error as any; 
        showSnackbar(`${err.response?.data.message}`, 'error');      } finally {
        loading.value = false;
      }
    }
  });
};

// 👉 Store form logic and call loadStaticData on component mount  
watch(() => store.selectedCashRegister, (newCashRegister) => {
  if (store.mode === 'edit' && newCashRegister) {
    CashRegisterFormDefaults.value = { ...newCashRegister };
  }
}, { immediate: true });

onMounted(async () => {
  if (store.mode === 'add') {
    CashRegisterFormDefaults.value = getDefaultCashRegister();
  } else if (store.mode === 'edit' && store.selectedCashRegister) {
    CashRegisterFormDefaults.value = { ...store.selectedCashRegister };
  }
});
</script>

<template>
  <VForm class="mt-6" ref="refForm" v-model="isFormValid" @submit.prevent="handleFormSubmit">

    <VRow justify="center" class="mt-6 center">


      <!-- Name -->
      <VCol cols="12" md="10">
        <VTextField v-model="CashRegisterFormDefaults.name" :rules="store.mode === 'add' ? [requiredValidator] : []" label="Nom *"
          placeholder="Nom" variant="outlined" />
      </VCol>


      <!-- Action buttons -->

      <VCol cols="12" class="d-flex flex-wrap justify-center gap-4 mt-4">

        <VBtn :disabled="loading" @click="closeDrawer" type="reset" variant="tonal" color="error">Annuler</VBtn>
        <VBtn :loading="loading" class="me-3" type="submit">{{ formTitle }}</VBtn>
      </VCol>
    </VRow>

  </VForm>
</template>
<style scoped>
.rounded-circle {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  top: 0;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  left: 0;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  width: 100%;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  height: 100%;
  background-color: white;
  /* stylelint-disable-next-line order/properties-order */
  border: 2px dashed #ddd;
  border-radius: 50%;
}
</style>
