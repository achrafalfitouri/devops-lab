<script setup lang="ts">
import { router } from '@/plugins/1.router';
import { assignUserRole, checkAuthUser, fetchStaticData } from '@/services/api/user';
import { useAuthStore } from '@stores/auth';
import { useUserStore } from '@stores/user';
import { onMounted, ref, watch } from 'vue';


const store = useUserStore();
const authStore = useAuthStore();

const emit = defineEmits(['close', 'submit']);

// 👉 Loading state
const loading = ref(false);
const initialLoading = ref(true); // Add this for initial data loading

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Roles Switches
const selectedRoles = ref<string[]>([]);
const switches = ref<{ name: string; title: string; description: string; active: boolean }[]>([]);
const rolesToUpdate = ref<{ name: string; active: boolean }[]>([]);

// 👉 Color pairs for switchers
const colorPairs = ['primary'];


const verifyAuthentication = async () => {
  try {
    const response = await checkAuthUser();
    if (response.authenticated) {
      authStore.login();
      localStorage.setItem('isAuthenticated', 'true');
    } else {
      authStore.logout();
      localStorage.setItem('isAuthenticated', 'false');

    }
  } catch (error) {
    console.error('Auth verification failed:', error);
    authStore.logout();
    localStorage.setItem('isAuthenticated', 'false');
    router.replace({ name: 'login' });
  }
};

// 👉 Fetch static data and map to switches
const loadStaticData = async () => {
  try {
    initialLoading.value = true; // Start loading
    const response = await fetchStaticData();
    const allRoles = response?.data?.role || [];
    switches.value = allRoles.map((role: { name: string; title: string; description: string | null }) => ({
      name: role.name,
      title: role.title,
      description: role.description || 'Aucune description disponible',
      active: selectedRoles.value.includes(role.name),
    }));

  } catch (error) {
    console.error('Error fetching static data:', error);
  } finally {
    initialLoading.value = false; // Stop loading
  }
};

// 👉 Watch selected user roles and update selectedRoles only
watch(
  () => store.selectedUser?.roles,
  (newRoles) => {
    if (newRoles && Array.isArray(newRoles)) {
      selectedRoles.value = newRoles.map((role: { name: string }) => role.name);
    } else {
      console.warn('Roles are not defined or not an array:', newRoles);
      selectedRoles.value = [];
    }
  },
  { immediate: true }
);

// 👉 Track changes to be submitted later
const toggleRole = (roleName: string, isActive: boolean) => {
  const updateSwitch = (name: string, active: boolean) => {
    const index = switches.value.findIndex(s => s.name === name);
    if (index !== -1) {
      switches.value[index] = { ...switches.value[index], active };
    }

    const updateIndex = rolesToUpdate.value.findIndex(r => r.name === name);
    if (updateIndex === -1) {
      rolesToUpdate.value.push({ name, active });
    } else {
      rolesToUpdate.value[updateIndex] = { name, active };
    }
  };
  updateSwitch(roleName, isActive);

  const parts = roleName.split('_');
  const type = parts.pop();
  const base = parts.join('_');

  if (type === 'manager') {
    const viewerRole = `${base}_viewer`;
    if (isActive) {
      updateSwitch(viewerRole, true);
    }
  }

  if (type === 'viewer' && !isActive) {
    const managerRole = `${base}_manager`;
    const managerSwitch = switches.value.find(s => s.name === managerRole);
    if (managerSwitch?.active) {
      updateSwitch(managerRole, false);
    }
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
    const rolesToAssign = [];
    const rolesToRevoke = [];

    for (const role of rolesToUpdate.value) {
      if (role.active) {
        rolesToAssign.push(role.name);
      } else {
        rolesToRevoke.push(role.name);
      }
    }

    if (rolesToAssign.length === 0 && rolesToRevoke.length === 0) {
      console.warn('No roles to assign or revoke');
      return;
    }

    await assignUserRole(userId, rolesToAssign, rolesToRevoke);

    if (store.selectedUser) {
      const updatedRoles = switches.value
        .filter((switchItem) => switchItem.active)
        .map((item) => ({
          name: item.name,
          description: item.description || null,
        }));

      store.selectedUser.roles = updatedRoles;
    } else {
      console.error('Selected user is null, cannot update roles');
    }

    rolesToUpdate.value = [];
    verifyAuthentication();
    emit('submit');
    closeDrawer();
  } catch (error) {
    console.error('Error saving role changes:', error);
    closeDrawer();
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  } finally {
    loading.value = false;
  }
};

// 👉 Close drawer and reset form
const closeDrawer = () => {
  emit('close');
};

// 👉 Initialize roles and load switches once on mount
onMounted(() => {
  if (store.selectedUser && Array.isArray(store.selectedUser.roles)) {
    selectedRoles.value = store.selectedUser.roles.map((role: { name: string }) => role.name);
  } else {
    console.warn('Roles are not an array or undefined:', store.selectedUser?.roles);
    selectedRoles.value = [];
  }
  loadStaticData();
});
</script>

<template>
  <VRow>
    <!-- Loading state - only shown while fetching initial data -->
    <VCol v-if="initialLoading" cols="12" class="d-flex justify-center align-center loading-container" style="min-height: 200px;">
      <VProgressCircular indeterminate color="primary" size="35" />
    </VCol>

    <!-- Role switches - only shown after loading -->
    <template v-else>
      <VCol v-for="(role, index) in switches" :key="index" cols="6" class="d-flex flex-column align-items-start">
        <VSwitch v-model="role.active" :label="role.title"
          :color="role.active ? colorPairs[Math.floor(index / 2) % colorPairs.length] : 'error'"
          @change="toggleRole(role.name, role.active)" />
        <span class="mt-1">{{ role.description }}</span>
      </VCol>
      
      <!-- Action buttons -->
      <VCol cols="12" class="d-flex flex-wrap justify-center gap-4">
        <VBtn :disabled="loading" @click="closeDrawer" type="reset" variant="tonal" color="error">Annuler</VBtn>
        <VBtn :loading="loading" class="me-3" type="submit" @click="saveChanges">Enregistrer</VBtn>
      </VCol>
    </template>
  </VRow>
</template>
