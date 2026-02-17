<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useUserStore } from '@stores/user';
import { assignUserCashRegister, fetchStaticData } from '@/services/api/user';

const store = useUserStore();
const emit = defineEmits(['close', 'submit']);


// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Loading state
const loading = ref(false);

// 👉 CashRegisters Switches
const selectedCashRegisters = ref<string[]>([]);
const switches = ref<{ name: string; active: boolean }[]>([]);
const cashregistersToUpdate = ref<{ name: string; active: boolean }[]>([]);

// 👉 Color pairs for switchers
const colorPairs = ['primary'];

// 👉 Fetch static data and map to switches
const loadStaticData = async () => {
  try {
    const response = await fetchStaticData();
    const allCashRegisters = response?.data?.cashregister || [];
    switches.value = allCashRegisters.map((cashregister: { name: string }) => ({
      name: cashregister.name,
      active: selectedCashRegisters.value.includes(cashregister.name),
    }));

  } catch (error) {
    console.error('Error fetching static data:', error);
  }
};

// 👉 Watch selected user cashregisters and update switches
watch(
  () => store.selectedUser?.cashregisters,
  (newCashRegisters) => {
    if (newCashRegisters && Array.isArray(newCashRegisters)) {
      selectedCashRegisters.value = newCashRegisters.map((cashregister: { name: string }) => cashregister.name);
      loadStaticData();
    } else {
      console.warn('CashRegisters are not defined or not an array:', newCashRegisters);
      selectedCashRegisters.value = [];
      switches.value = [];
    }
  },
  { immediate: true }
);

// 👉 Track changes to be submitted later
const toggleCashRegister = (cashregisterName: string, isActive: boolean) => {
  const cashregisterIndex = cashregistersToUpdate.value.findIndex((cashregister) => cashregister.name === cashregisterName);

  if (cashregisterIndex === -1) {
    cashregistersToUpdate.value.push({ name: cashregisterName, active: isActive });
  } else {
    cashregistersToUpdate.value[cashregisterIndex].active = isActive;
  }
};

// 👉 Submit changes
const saveChanges = async () => {
  const userId = store.selectedUser?.id;
  if (!userId) {
    console.error('User ID is missing');
    return;
  }
  loading.value = true;

  try {
    const cashRegistersToAssign = [];
    const cashRegistersToRevoke = [];

    for (const cashregister of cashregistersToUpdate.value) {
      if (cashregister.active) {
        cashRegistersToAssign.push(cashregister.name);
      } else {
        cashRegistersToRevoke.push(cashregister.name);
      }
    }
    if (cashRegistersToAssign.length === 0 && cashRegistersToRevoke.length === 0) {
      console.error('No cash registers to assign or revoke');
      return;
    }
    await assignUserCashRegister(userId, cashRegistersToAssign);

    cashregistersToUpdate.value = [];
    emit('submit');
    closeDrawer();
  } catch (error) {
    console.error('Error saving cashregister changes:', error);
    closeDrawer();
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  } finally {
    loading.value = false;
  }
};

// 👉 Close drawer and reset form
const closeDrawer = () => {
  store.closeDrawer();
  emit('close');
};

// 👉 Initialize cashregisters on mount
onMounted(() => {
  if (store.selectedUser && Array.isArray(store.selectedUser.cashregisters)) {
    selectedCashRegisters.value = store.selectedUser.cashregisters.map((cashregister: { name: string }) => cashregister.name);
    loadStaticData();
  } else {
    console.warn('CashRegisters are not an array or undefined:', store.selectedUser?.cashregisters);
    selectedCashRegisters.value = [];
  }
});
</script>

<template>
  <VRow>
    <VCol v-for="(cashregister, index) in switches" :key="index" cols="6" class="d-flex flex-column align-items-start">
      <VSwitch v-model="cashregister.active" :label="cashregister.name"
        :color="cashregister.active ? colorPairs[Math.floor(index / 2) % colorPairs.length] : 'error'"
        @change="toggleCashRegister(cashregister.name, cashregister.active)" />
    </VCol>
    <!-- Action buttons -->
    <VCol cols="12" class="d-flex flex-wrap justify-center gap-4">
      <VBtn :disabled="loading" @click="closeDrawer" type="reset" variant="tonal" color="error">Annuler</VBtn>
      <VBtn :loading="loading" class="me-3" type="submit" @click="saveChanges" color="primary">Enregistrer</VBtn>
    </VCol>
  </VRow>
</template>
