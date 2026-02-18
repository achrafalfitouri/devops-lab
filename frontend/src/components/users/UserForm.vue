<script setup lang="ts">
import { addUser, addUserImage, checkAuthUser, fetchStaticData, updateUser, updateUserImage } from '@/services/api/user';
import { getDefaultUser } from '@services/defaults';
import type { User } from '@services/models';
import { useUserStore } from '@stores/user';
import { useAuthStore } from '@stores/auth';
import { computed, ref, watch, onMounted } from 'vue';
import { VForm } from 'vuetify/components/VForm';
import { router } from '@/plugins/1.router';
import * as yup from 'yup';
import {mapStaticData} from '@/composables/ClientUtil';


const store = useUserStore();
const authStore = useAuthStore();

const apiUrl = (window as any).__ENV__?.API_BASE_URL
const baseUrl = apiUrl.replace(/\/api\/?$/, '')

const emit = defineEmits(['close', 'submit', 'date-picker-state']);

// 👉 Loading state
const loading = ref(false);

const refForm = ref<VForm | null>(null);
const isFormValid = ref(false);
const UserFormDefaults = ref<User>(store.selectedUser || getDefaultUser());

const photo = ref<string>(store.selectedUser?.photo || '');
const imageFile = ref<File | null>(null);
const errorMessage = ref('');

// 👉 Field validation errors
const fieldErrors = ref<Record<string, string>>({});

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
}

// 👉 State variables for the filter options (initialized as empty arrays)
const filterItems = ref<{
  titles: FilterItem[];
}>({
  titles: []
});

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 State variable for password visibility
const isPasswordVisible = ref(false);
const isDatePickerOpen = ref(false);


// 👉 Enhanced Validation Rules
// 👉 Yup Validation Schema
const validationSchema = yup.object({
  firstName: yup.string()
    .required('Ce champ est obligatoire')
    .min(2, 'Le prénom doit contenir au moins 2 caractères')
    .max(50, 'Le prénom ne peut pas dépasser 50 caractères')
    .matches(/^[a-zA-ZÀ-ÿ\s'-]+$/, 'Le prénom ne peut contenir que des lettres, espaces, apostrophes et tirets'),

  lastName: yup.string()
    .required('Ce champ est obligatoire')
    .min(2, 'Le nom doit contenir au moins 2 caractères')
    .max(50, 'Le nom ne peut pas dépasser 50 caractères')
    .matches(/^[a-zA-ZÀ-ÿ\s'-]+$/, 'Le nom ne peut contenir que des lettres, espaces, apostrophes et tirets'),

  email: yup.string()
    .required('L\'email est obligatoire')
    .email('L\'email doit être valide'),

  phone: yup.string()
    .nullable()
    .notRequired()
    .test('moroccan-phone', 'Format de téléphone invalide. Utilisez le format marocain (ex: 0612345678, +212612345678)', function (value) {
      if (!value || value.trim() === '') return true;
      const cleanPhone = value.replace(/[\s-]/g, '');
      const phoneRegex = /^(\+212|0)(5|6|7)[0-9]{8}$|^(\+212[\s-]?)?(0?[567])[0-9]{2}[\s-]?[0-9]{2}[\s-]?[0-9]{2}[\s-]?[0-9]{2}$/;
      return phoneRegex.test(cleanPhone);
    }),

  cin: yup
    .string()
    .nullable()
    .notRequired()
    .test(
      'moroccan-cin',
      'Format CIN invalide. Exemple: AB123456 avec code région valide.',
      function (value) {
        if (!value || value.trim() === '') return true;
        const upper = value.toUpperCase();
        const regex = /^([A-Z]{2,3})(\d{5,6})$/;
        const match = upper.match(regex);
        return !!match;
      }
    ),

  password: yup.string()
    .required('Le mot de passe est obligatoire')
    .min(6, 'Le mot de passe doit contenir au moins 6 caractères')
    .matches(/[a-zA-Z]/, 'Le mot de passe doit contenir au moins une lettre')
    .matches(/(?=.*\d)/, 'Le mot de passe doit contenir au moins un chiffre')
.matches(
  /(?=.*[!@#$%^&*()_\-+={}[\]|\\:;"'<>,.?/~`])/,
  'Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&*()_+-=).'
)

});

// 👉 Validation helper function
const validateField = async (field: string, value: any) => {
  try {
    const schema = yup.reach(validationSchema, field);
    if (typeof (schema as any).validate === 'function') {
      await (schema as yup.AnySchema).validate(value);
      return true;
    } else {
      return true;
    }
  } catch (error) {
    return (error as yup.ValidationError).message;
  }
};

// 👉 Real-time field validation on input change
const validateFieldRealtime = async (fieldName: string, value: any) => {
  if (store.mode === 'add') {
    const result = await validateField(fieldName, value);
    if (result === true) {
      // Clear error immediately when field becomes valid
      delete fieldErrors.value[fieldName];
    } else {
      // Only show error if field has been touched (has some content or had error before)
      if (value || fieldErrors.value[fieldName]) {
        fieldErrors.value[fieldName] = result;
      }
    }
  }
};

// 👉 Single field validation on blur (for fields that haven't been validated yet)
const validateSingleField = async (fieldName: string, value: any) => {
  if (store.mode === 'add') {
    const result = await validateField(fieldName, value);
    if (result === true) {
      delete fieldErrors.value[fieldName];
    } else {
      fieldErrors.value[fieldName] = result;
    }
  }
};

// 👉 Validate all fields for form submission
const validateAllFields = async () => {
  if (store.mode !== 'add') return true;
  const fields = ['firstName', 'lastName', 'email', 'phone', 'cin', 'password'];
  let isValid = true;
  for (const field of fields) {
    const value = UserFormDefaults.value[field as keyof User];
    const result = await validateField(field, value);
    if (result !== true) {
      fieldErrors.value[field] = result;
      isValid = false;
    } else {
      delete fieldErrors.value[field];
    }
  }

  return isValid;
};

const formTitle = computed(() => 'Enregistrer');

const onFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] || null;

  if (file) {
    const maxSize = 12 * 1024 * 1024;
    if (file.size > maxSize) {
      errorMessage.value = 'La taille du fichier dépasse 12 Mo. Veuillez télécharger un fichier plus petit.';
      return;
    }

    errorMessage.value = '';
    photo.value = URL.createObjectURL(file);
    imageFile.value = file;
  }
};

const closeDrawer = () => {
  store.closeDrawer();
  refForm.value?.reset();
  refForm.value?.resetValidation();
  photo.value = '';
  imageFile.value = null;
  fieldErrors.value = {};
  emit('close');
};

const verifyAuthentication = async () => {
  try {
    const response = await checkAuthUser();
    if (response.authenticated) {
      authStore.login();
      localStorage.setItem('isAuthenticated', 'true');
    } else {
      authStore.logout();
      localStorage.setItem('isAuthenticated', 'false');
      router.replace({ name: 'login' });
    }
  } catch (error) {
    console.error('Auth verification failed:', error);
    authStore.logout();
    localStorage.setItem('isAuthenticated', 'false');
  }
};

const handleFormSubmit = async () => {
  const isValid = await validateAllFields();

  if (isValid) {
    loading.value = true;
    try {
      const userData = new FormData();
      const payload = {
        ...UserFormDefaults.value,
        title_id: UserFormDefaults.value.title === store.selectedUser?.title ? store.selectedUser?.titleId : UserFormDefaults.value.title,
      };

      if (imageFile.value) {
        userData.append('photo', imageFile.value);
      }

      if (store.mode === 'add') {
        const response = await addUser(payload);
        const userId = response.id;
        if (imageFile.value) {
          await addUserImage(userId, userData);
        }
        store.users.push({ ...payload, id: userId });
      } else if (store.mode === 'edit') {
        const userId = payload.id;
        await updateUser(userId, payload);

        if (imageFile.value) {
          await updateUserImage(userId, userData);
        }
        const index = store.users.findIndex((c) => c.id === userId);
        if (index !== -1) {
          store.users[index] = { ...payload, id: userId };
        }
      }
      await verifyAuthentication();
      closeDrawer();
      emit('submit');
    } catch (error) {
      console.error('Error preparing user data:', error);
      closeDrawer();
      const err = error as any;
      showSnackbar(`${err.response?.data.message}`, 'error');
    } finally {
      loading.value = false;
    }
  } else {
    showSnackbar('Veuillez corriger les erreurs dans le formulaire', 'error');
  }
};

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.titles = mapStaticData(staticData.data.title);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

// 👉 Store form logic and call loadStaticData on component mount  
watch(() => store.selectedUser, (newUser) => {
  if (store.mode === 'edit' && newUser) {
    UserFormDefaults.value = { ...newUser };
    photo.value = newUser.photo || '';
  }
}, { immediate: true });

onMounted(async () => {
  if (store.mode === 'add') {
    UserFormDefaults.value = getDefaultUser();
  } else if (store.mode === 'edit' && store.selectedUser) {
    UserFormDefaults.value = { ...store.selectedUser };
    photo.value = store.selectedUser.photo || '';
  }
  loadStaticData();
});

const handleDatePickerState = (isOpen: boolean) => {
  isDatePickerOpen.value = isOpen;
  emit('date-picker-state', isOpen);
};
</script>

<template>
  <VForm class="mt-6" ref="refForm" v-model="isFormValid" @submit.prevent="handleFormSubmit">
    <v-alert v-if="errorMessage" type="error" class="mt-2">{{ errorMessage }}</v-alert>

    <VRow class="mt-6">
      <VCol cols="12" class="d-flex justify-center">
        <v-avatar @click="() => ($refs.fileInput as unknown as HTMLInputElement).click()" size="170"
          class="position-relative cursor-pointer">
          <v-img :src="imageFile ? photo : `${baseUrl}/${photo}`" class="rounded-circle" cover></v-img>
          <VCardText v-if="!photo" icon class="position-absolute d-flex align-items-center justify-center">
            Photo
            <v-icon style="color: #ddd; font-size: 32px;">tabler-plus</v-icon>
          </VCardText>

          <!-- Hidden file input for image upload -->
          <input ref="fileInput" type="file" accept="image/*" class="d-none" @change="onFileChange" />
        </v-avatar>
      </VCol>

      <!-- Code number -->
      <VCol cols="12" md="6">
        <VTextField 
          v-model="UserFormDefaults.number" 
          label="Code" 
          placeholder="Code" 
          variant="outlined"
         />
      </VCol>
      <VCol cols="12" md="6">
        <VTextField 
          v-model="UserFormDefaults.firstName" 
          label="Prénom *" 
          placeholder="Prénom" 
          variant="outlined"
          :error="!!fieldErrors.firstName" 
          :error-messages="fieldErrors.firstName"
          @input="validateFieldRealtime('firstName', UserFormDefaults.firstName)"
          @blur="validateSingleField('firstName', UserFormDefaults.firstName)" />
      </VCol>

      <!-- Last Name -->
      <VCol cols="12" md="6">
        <VTextField 
          v-model="UserFormDefaults.lastName" 
          label="Nom *" 
          placeholder="Nom" 
          variant="outlined"
          :error="!!fieldErrors.lastName" 
          :error-messages="fieldErrors.lastName"
          @input="validateFieldRealtime('lastName', UserFormDefaults.lastName)"
          @blur="validateSingleField('lastName', UserFormDefaults.lastName)" />
      </VCol>

      <VCol cols="12" md="6">
        <AppDateTimePicker v-model="UserFormDefaults.birthdate" placeholder="Date de naissance"   @picker-open="handleDatePickerState(true)"
      @picker-close="handleDatePickerState(false)" />
      </VCol>

      <!-- Gender -->
      <VCol cols="12" md="6">
        <VAutocomplete v-model="UserFormDefaults.gender" label="Genre" placeholder="Sélectionnez le genre" :items="[
          { title: 'Homme', value: 'Homme' },
          { title: 'Femme', value: 'Femme' },
        ]" variant="outlined" />
      </VCol>

      <!-- CIN -->
      <VCol cols="12" md="6">
        <VTextField 
          v-model="UserFormDefaults.cin" 
          label="CIN" 
          placeholder="CIN" 
          variant="outlined"
          :error="!!fieldErrors.cin" 
          :error-messages="fieldErrors.cin"
          @input="validateFieldRealtime('cin', UserFormDefaults.cin)"
          @blur="validateSingleField('cin', UserFormDefaults.cin)" />
      </VCol>

      <!-- Phone -->
      <VCol cols="12" md="6">
        <VTextField 
          v-model="UserFormDefaults.phone" 
          label="Numéro de téléphone" 
          placeholder="+1-541-754-3010"
          variant="outlined" 
          :error="!!fieldErrors.phone" 
          :error-messages="fieldErrors.phone"
          @input="validateFieldRealtime('phone', UserFormDefaults.phone)"
          @blur="validateSingleField('phone', UserFormDefaults.phone)" />
      </VCol>

      <!-- Email -->
      <VCol cols="12" md="6">
        <VTextField 
          v-model="UserFormDefaults.email" 
          label="Email *" 
          placeholder="johndoe@email.com" 
          variant="outlined"
          :error="!!fieldErrors.email" 
          :error-messages="fieldErrors.email"
          @input="validateFieldRealtime('email', UserFormDefaults.email)"
          @blur="validateSingleField('email', UserFormDefaults.email)" />
      </VCol>

      <!-- Password -->
      <VCol cols="12" md="6">
        <VTextField 
          v-model="UserFormDefaults.password" 
          label="Mot de passe *" 
          placeholder="············"
          :type="isPasswordVisible ? 'text' : 'password'"
          :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
          @click:append-inner="isPasswordVisible = !isPasswordVisible" 
          variant="outlined"
          :error="!!fieldErrors.password" 
          :error-messages="fieldErrors.password"
          @input="validateFieldRealtime('password', UserFormDefaults.password)"
          @blur="validateSingleField('password', UserFormDefaults.password)" />
      </VCol>

      <!-- Title -->
      <VCol cols="12" md="6">
        <VAutocomplete v-model="UserFormDefaults.title" label="Titre" placeholder="Sélectionnez un titre"
          :items="filterItems.titles" item-title="text" item-value="value" clearable clear-icon="tabler-x"
          variant="outlined" />
      </VCol>

      <!-- Status -->
      <VCol cols="12" md="6">
        <VAutocomplete :disabled="store.mode === 'add'" v-model="UserFormDefaults.status" label="Statut" placeholder="Statut" :items="[
          { title: 'Activé', value: 1 },
          { title: 'Désactivé', value: 0 },
        ]" variant="outlined" :error="!!fieldErrors.status" :error-messages="fieldErrors.status" />
      </VCol>

      <!-- Action buttons -->
      <VCol cols="12" class="d-flex flex-wrap justify-center gap-4 mt-4">
        <VBtn :disabled="loading" @click="closeDrawer" type="reset" variant="tonal" color="error">
          Annuler
        </VBtn>
        <VBtn :loading="loading" class="me-3" type="submit">
          {{ formTitle }}
        </VBtn>
      </VCol>
    </VRow>
  </VForm>
</template>

<style scoped>
.rounded-circle {
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: white;
  border: 2px dashed #ddd;
  border-radius: 50%;
}
</style>
