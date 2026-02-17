<script setup lang="ts">
import { useTheme } from 'vuetify';
import ScrollToTop from '@core/components/ScrollToTop.vue';
import initCore from '@core/initCore';
import { initConfigStore, useConfigStore } from '@core/stores/config';
import { hexToRgb } from '@layouts/utils';
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { checkAuthUser } from './services/api/user';
import { useSnackbar } from '@/composables/useSnackbar';

const { global } = useTheme();
initCore();
initConfigStore();

const configStore = useConfigStore();
const authStore = useAuthStore();

onMounted(async () => {
  if (authStore.isAuthenticated) {
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
      console.error('Auth check failed:', error);
      authStore.logout();
    }
  }
});

const { snackbarVisible, snackbarMessage, snackbarColor, closeSnackbar } = useSnackbar();
</script>

<template>
  <VLocaleProvider :rtl="configStore.isAppRTL">
    <VApp :style="{ '--v-global-theme-primary': hexToRgb(global.current.value.colors.primary) }">
      <RouterView />
      <ScrollToTop />
      <SnackBar
        :isVisible="snackbarVisible"
        :message="snackbarMessage"
        :color="snackbarColor"
        @close="closeSnackbar"
      />
    </VApp>
  </VLocaleProvider>
</template>
