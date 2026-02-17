<script setup lang="ts">
import { assignUserCashRegister, fetchStaticData } from '@/services/api/user';
import { useAuthStore } from '@stores/auth';
import { useCashRegisterStore } from '@stores/cashregister';
import { onMounted, ref, watch } from 'vue';
import { VAutocomplete } from 'vuetify/lib/components/index.mjs';

const store = useCashRegisterStore();
const authStore = useAuthStore();
const emit = defineEmits(['close', 'submit']);

interface FilterItem {
  text: string;
  value: string;
}

// 👉 Explicitly define the type for filterItems
const filterItems = ref<{
  userItems: FilterItem[];
}>({
  userItems: [],
});

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Loading state
const loading = ref(false);

const assignedusers = ref<any[]>([]);
// 👉 Users and selection
const selectedUsers = ref<any[]>([]);
const isDataReady = ref(false);

const users = ref<{ fullName: string; id: string }[]>([]);
const UsersToUpdate = ref<{ fullName: string; active: boolean, id: string }[]>([]);

// 👉 Fetch static data and populate options
const loadStaticData = async () => {
  try {
    isDataReady.value = false;
    const response = await fetchStaticData();
    const allUsers = response?.data?.user || [];
    users.value = allUsers.map((user: { fullName: string; id: string }) => ({
      fullName: user.fullName,
      id: user.id,
    }));

    filterItems.value.userItems = allUsers.map((user: { fullName: string; id: string }) => ({
      text: user.fullName,
      value: user.id,
    }));

    if (store.selectedCashRegister?.managedBy) {
      selectedUsers.value = store.selectedCashRegister.managedBy.map((user: any) => ({
        text: user.fullName,
        value: user.id
      }));
    }

    isDataReady.value = true;

  } catch (error) {
    console.error('Error fetching static data:', error);
    isDataReady.value = true;
  }
};

// 👉 Watch selected users for changes
watch(
  () => selectedUsers.value,
  (newSelection) => {

    UsersToUpdate.value = newSelection.map((user: { text: string; value: string } | string) => {
      if (typeof user === 'object' && 'text' in user && 'value' in user) {
        return {
          fullName: user.text,
          id: user.value,
          active: true,
        };
      }

      const matchedUser = users.value.find(u => u.fullName === user);
      return {
        fullName: user,
        id: matchedUser?.id || '',
        active: true,
      };
    });
  },
  { immediate: true }
);

const saveChanges = async () => {
  const cashregisterId = store.selectedCashRegister?.id;
  if (!cashregisterId) {
    console.error('cashregister ID is missing');
    return;
  }
  loading.value = true;

  try {
    const currentActiveUsers = store.selectedCashRegister?.managedBy?.map(
      (user: { id: string }) => user.id
    ) || [];

    const selectedUserIds = users.value
      .filter(user => selectedUsers.value.includes(user.fullName))
      .map(user => user.id);

    const usersToAssign = selectedUserIds.filter(id => !currentActiveUsers.includes(id));
    await assignUserCashRegister(cashregisterId, assignedusers.value);

    const updatedCashRegister = {
      ...store.selectedCashRegister,
      users: users.value.filter((user) => selectedUserIds.includes(user.id)),
    };

    if (authStore.user?.cashregisters) {
      const index = authStore.user.cashregisters.findIndex(
        (reg: { id: string }) => reg.id === cashregisterId
      );

      if (index !== -1) {
        authStore.user.cashregisters[index] = updatedCashRegister;
      } else {
        authStore.user.cashregisters.push(updatedCashRegister);
      }
    }

    UsersToUpdate.value = [];
    emit('submit');
    closeDrawer();
  } catch (error) {
    console.error('Error saving user changes:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
    closeDrawer();
  } finally {
    loading.value = false;
  }
};

// 👉 Close drawer
const closeDrawer = () => {
  store.closeDrawer();
  emit('close');
};

// 👉 Initialize users on mount
onMounted(() => {
  loadStaticData();
});

// 👉 Handle user selection
const handleSelect = (selected: any[]) => {
  assignedusers.value = selected.map(user => {
    if (typeof user === 'object' && 'value' in user) {
      return user.value;
    }
    const match = users.value.find(u => u.fullName === user);
    return match?.id || null;
  }).filter(id => id !== null);
};
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VAutocomplete v-if="isDataReady || !store.selectedCashRegister?.managedBy?.length" v-model="selectedUsers"
        :items="filterItems.userItems" placeholder="Select Users" label="Utilisateurs *" chips multiple closable-chips
        return-object @update:modelValue="handleSelect($event)" item-title="text" item-value="value"
        variant="outlined" />
      <div v-else class="d-flex justify-center align-center w-100 h-100">
        <VProgressCircular indeterminate color="primary" />
      </div>
    </VCol>

    <!-- Action buttons -->
    <VCol cols="12" class="d-flex flex-wrap justify-center gap-4">
      <VBtn :disabled="loading" @click="closeDrawer" type="reset" variant="tonal" color="error">Annuler</VBtn>
      <VBtn :loading="loading" class="me-3" type="submit" @click="saveChanges" color="primary">Enregistrer</VBtn>
    </VCol>
  </VRow>
</template>
