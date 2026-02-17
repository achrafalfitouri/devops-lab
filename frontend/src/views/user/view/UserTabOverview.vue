<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue';
import { useUserStore } from '@stores/user';
import { fetchStaticData } from '@/services/api/user';
import { VIcon } from 'vuetify/lib/components/index.mjs';

const store = useUserStore();
const emit = defineEmits(['close', 'submit']);

// Selected roles
const selectedRoles = ref<string[]>([]);

// Raw roles from API
const allRoles = ref<
  { name: string; title: string; description: string; icon: string; color: string }[]
>([]);

// Fetch static roles once
const loadStaticData = async () => {
  try {
    const response = await fetchStaticData();
    allRoles.value = response?.data?.role || [];
  } catch (error) {
    console.error('Error fetching static data:', error);
    allRoles.value = [];
  }
};

// Update selectedRoles when store changes
watch(
  () => store.selectedUser?.roles,
  (newRoles) => {
    selectedRoles.value = Array.isArray(newRoles)
      ? newRoles.map(role => role.name)
      : [];
  },
  { immediate: true }
);

// Fetch roles on mount
onMounted(() => {
  const roles = store.selectedUser?.roles;
  selectedRoles.value = Array.isArray(roles) ? roles.map(r => r.name) : [];
  loadStaticData();
});

// Derived switches based on selectedRoles and allRoles
const switches = computed(() => {
  return allRoles.value.map(role => ({
    name: role.name,
    title: role.title,
    description: role.description || 'Aucune description disponible',
    icon: role.icon || 'tabler-circle',
    active: selectedRoles.value.includes(role.name),
  }));
});

const sortedRoles = computed(() => {
  return [...switches.value].sort((a, b) => Number(b.active) - Number(a.active));
});
</script>


<template>
  <VRow>
    <VCol v-for="(role, index) in sortedRoles" :key="index" cols="12" md="4">
      <VCard :style="{ height: '205px', width: '100%' }">
        <VCardText class="d-flex gap-y-2 flex-column">
          <VAvatar variant="tonal" rounded size="40">
            <VIcon
              :icon="role.active ? 'tabler-check' : 'tabler-ban'"
              :style="{
                filter: role.active ? 'none' : 'grayscale(100%)',
                opacity: role.active ? 1 : 0.5,
                fontSize: '40px',
                borderRadius: '50%',
                color: role.active ? '#E10A17' : '#BDBDBD',
              }"
            />
          </VAvatar>

          <h5
            :class="role.active ? 'text-h6' : 'text-h6 text-grey lighten-2'"
            :style="{ color: role.active ? '#2F2B3DE6' : '#bdbdbd' }"
          >
            {{ role.title }}
          </h5>

          <p
            :class="role.active ? 'text-body-1' : 'text-body-1 text-grey lighten-2'"
            :style="{ color: role.active ? '#2F2B3DB3' : '#bdbdbd' }"
          >
            {{ role.description }}
          </p>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

