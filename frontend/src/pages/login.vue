<script setup lang="ts">
import { router } from '@/plugins/1.router';
import { loginUser, passwordChangeRequest } from '@/services/api/user';
import { useAuthStore } from '@/stores/auth';
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant';
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png';
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png';
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.jpeg';
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.jpeg';
import authV2MaskDark from '@images/pages/misc-mask-dark.png';
import authV2MaskLight from '@images/pages/misc-mask-light.png';
import { themeConfig } from '@themeConfig';
import { ref, computed } from 'vue';
import { VNodeRenderer } from './../@layouts/components/VNodeRenderer';
import { layoutConfig } from '@layouts';
import { axiosInstance } from '@/plugins/axios';

definePage({
  meta: {
    layout: 'blank',
  },
});

// 👉 Composables
const { showSnackbar } = useSnackbar();
const authStore = useAuthStore();

// 👉 Reactive State
const form = ref({
  email: '',
  password: '',
  remember: false,
});

const isLoading = ref(false);
const isForgotPassword = ref(false);
const isPasswordVisible = ref(false);
const formErrors = ref<Record<string, string>>({});

// 👉 Computed properties
const isFormValid = computed(() => {
  const errors: Record<string, string> = {};

  // Email validation
  if (!form.value.email) {
    errors.email = 'L\'email est requis';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.email = 'Veuillez entrer un email valide';
  }

  // Password validation (only when not in forgot password mode)
  if (!isForgotPassword.value && !form.value.password) {
    errors.password = 'Le mot de passe est requis';
  }

  formErrors.value = errors;
  return Object.keys(errors).length === 0;
});

// 👉 Theme images
const authThemeImg = useGenerateImageVariant(
  authV2LoginIllustrationLight,
  authV2LoginIllustrationDark,
  authV2LoginIllustrationBorderedLight,
  authV2LoginIllustrationBorderedDark,
  true
);

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark);

// 👉 Methods
// Helper function to find the first accessible route 
const getFirstAccessibleRoute = (userPermissions: string[], userId?: string | number): string | object => {
  const routePriority = [
    'users',
    'clients',
    'cashregisters',
    'payments',
    'documents',
    'email',
    'logs',
    'archives'
  ];

  for (const routeName of routePriority) {
    const permissionConfig = getRequiredPermissionsForRoute(routeName);
    if (hasRequiredPermissions(userPermissions, permissionConfig)) {
      return `/${routeName}`;
    }
  }

  if (userId) {
    const profilePath = `/profil/${userId}`;
    return profilePath;
  }

  return '/profil';
};

const handleLogin = async () => {
  try {
    if (!isFormValid.value) return;

    isLoading.value = true;
    console.log('[axiosInstance] baseURL =', axiosInstance.defaults.baseURL);


    const response = await loginUser(form.value);

    const roles = response.user?.roles?.map((role: { name: string }) => role.name) || [];
    const cashregisters = response.user?.cashregisters?.map((cashregister: { name: string }) => cashregister.name) || [];
    const user = response.user || {};
    const userId = response.user?.id;

    authStore.permissionCheck(response.user?.roles || [], roles);
    authStore.CashCheck(cashregisters);
    authStore.UserCheck(user);

    // Complete login process 
    authStore.login();
    localStorage.setItem('isAuthenticated', 'true');
    if (response.hideToast !== true) {
      showSnackbar('Connexion réussie', 'success');
    }

    const accessibleRoute = getFirstAccessibleRoute(authStore.permissions, userId);

    if (typeof accessibleRoute === 'string') {
      router.push(accessibleRoute);
    } else {
      router.push(accessibleRoute);
    }
  } catch (error) {
    console.error('Login failed:', error);
    const err = error as any;
    showSnackbar(err.response?.data?.message || 'Échec de la connexion', 'error');
  } finally {
    isLoading.value = false;
  }
};
const handleForgotPassword = async () => {
  try {
    if (!form.value.email || formErrors.value.email) return;

    isLoading.value = true;

    // Send password reset request
    await passwordChangeRequest(form.value.email);
    showSnackbar('Un email de réinitialisation a été envoyé à votre adresse e-mail.', 'success');

    // Reset form and go back to login
    setTimeout(() => {
      isForgotPassword.value = false;
    }, 2000);
  } catch (error) {
    console.error('Password reset failed:', error);
    const err = error as any;
    showSnackbar(err.response?.data?.message || 'Échec de la demande de réinitialisation', 'error');
  } finally {
    isLoading.value = false;
  }
};

const togglePasswordVisibility = () => {
  isPasswordVisible.value = !isPasswordVisible.value;
};

const toggleForgotPassword = () => {
  isForgotPassword.value = !isForgotPassword.value;
  formErrors.value = {};
};
</script>

<template>
  <VRow no-gutters class="auth-wrapper bg-surface">
    <!-- Left side image panel (hidden on mobile) -->
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 h-100">
        <VImg :src="authThemeImg" class="auth-illustration-cover" cover />
      </div>
    </VCol>

    <!-- Login form panel -->
    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center">
      <VCard flat :max-width="500" class="form-card mt-12 mt-sm-0 pa-4">
        <VCardText>
          <!-- Centered Logo Container -->
          <div class="app-logo mb-12 text-center">
            <VNodeRenderer style="color: white !important; align-items: center; justify-content: center; display: flex;"
              :nodes="layoutConfig.app.logo" />
          </div>

          <!-- Welcome Message -->
          <h4 class="text-h4 mb-1 text-white text-center">

            Bienvenue à <span class="text-capitalize">{{ themeConfig.app.title }}</span> ! 360 Print
          </h4>
          <p class="mb-0 text-center">
            {{ isForgotPassword
              ? 'Entrez votre email pour récupérer votre mot de passe'
              : 'Entrez votre email et votre mot de passe pour vous connecter'
            }}
          </p>
        </VCardText>

        <VCardText>
          <VForm @submit.prevent="() => isForgotPassword ? handleForgotPassword() : handleLogin()">
            <VRow>
              <!-- Email field -->
              <VCol cols="12">

                <VTextField v-model="form.email" autofocus type="email" autocomplete="email"
                  placeholder="contact@360print.ma" color="white" class="password-input"
                  :error-messages="formErrors.email" @update:model-value="formErrors.email = ''" variant="outlined"
                  label="Email" prepend-inner-icon="tabler-mail" />
              </VCol>

              <!-- Password field (only shown on login screen) -->
              <VCol v-if="!isForgotPassword" cols="12">
                <VTextField v-model="form.password" placeholder="············"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'" color="white"
                  class="password-input" @click:append-inner="togglePasswordVisibility" autocomplete="current-password"
                  :type="isPasswordVisible ? 'text' : 'password'" :error-messages="formErrors.password"
                  @update:model-value="formErrors.password = ''" label="Mot de passe" variant="outlined"
                  prepend-inner-icon="tabler-lock" />
              </VCol>

              <!-- Action buttons and links -->
              <VCol cols="12">
                <VBtn block type="submit" variant="outlined" class="custom-button mt-6 mb-4" color="white"
                  :loading="isLoading" :disabled="isLoading">
                  {{ isForgotPassword ? 'Envoyer la demande' : 'Se connecter' }}
                </VBtn>

                <div class="d-flex align-center flex-wrap justify-center mt-2 mb-4">
                  <a v-if="!isForgotPassword" class="text-white ms-2 mb-1" href="#"
                    @click.prevent="toggleForgotPassword">
                    Mot de passe oublié?
                  </a>

                  <IconBtn v-if="isForgotPassword" class="text-white ms-2 mb-1" @click="toggleForgotPassword">
                    <VIcon icon="tabler-arrow-left" />
                  </IconBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <!-- Footer -->
        <div class="footer-text">
          Designed by <a href="#" class="footer-link">icone</a> developed by <a href="#"
            class="footer-link">0101Agency</a>
        </div>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">
/* stylelint-disable-next-line scss/load-partial-extension */
@use "@core/scss/template/pages/page-auth.scss";

.app-logo {
  display: flex;
  justify-content: center;
  align-items: center;
  color: white;
  line-height: 10px;

  /* Override SVG colors to white */
  ::v-deep(svg .st0),
  ::v-deep(svg circle),
  ::v-deep(svg path) {
    fill: white !important;

  }

  ::v-deep(svg) {
    height: 120px;
    width: 120px;
  }

  /* Alternative: Use CSS filter to make entire SVG white */
}




.auth-illustration-cover {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  position: absolute;
  top: 0;
  left: 0;
}

.auth-card-v2 .form-card,
.auth-card-v2 {
  background-color: #e10a17;
  color: white;
}

.password-input ::v-deep(.v-field__input)::placeholder {
  color: rgba(255, 255, 255, 0.7) !important;
  opacity: 1;
}

.password-input ::v-deep(.v-field__input) {
  color: white !important;
  caret-color: white !important;
  background-color: #e10a17 !important;
  box-shadow: 0 0 0 1000px #e10a17 inset !important;
  -webkit-text-fill-color: white !important;
}

.password-input ::v-deep(.v-field--error) {
  color: #ffd9d9 !important;
}

.password-input ::v-deep(.v-input__control) {
  color: white !important;
}

.password-input ::v-deep(.v-input__append-inner .v-icon),
.password-input ::v-deep(.tabler-eye),
.password-input ::v-deep(.tabler-eye-off) {
  color: white !important;
}

.custom-button {
  transition: all 0.3s ease;

  &:hover {
    background-color: rgba(255, 255, 255, 0.1);
  }
}

.footer-text {
  text-align: center;
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
  margin-top: 1rem;
}

.footer-link {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

.v-input .v-input__details .v-messages .v-messages__message {
  color: #ffd9d9 !important;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
:deep(.v-input__details) {
  color: white !important;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
:deep(.v-messages__message) {
  color: white !important;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
:deep(.v-field--error) {
  --v-field-error-color: white !important;
}

::v-deep(.v-input__control .v-field__field .v-label) {
  color: white !important;
}

::v-deep(.v-input__control .v-field .v-field__prepend-inner .tabler-mail) {
  color: white !important;
}

::v-deep(.v-input__control .v-field .v-field__prepend-inner .tabler-lock) {
  color: white !important;
}
</style>
