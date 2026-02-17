<script setup lang="ts">
import { addClient, addClientImage, fetchStaticData, updateClient, updateClientImage } from '@/services/api/client';
import { getDefaultClient } from '@services/defaults';
import type { Client } from '@services/models';
import { useClientStore } from '@stores/client';
import { computed, onMounted, ref, watch } from 'vue';
import { VForm } from 'vuetify/components/VForm';
import * as yup from 'yup';

const store = useClientStore();
const apiUrl = import.meta.env.VITE_API_BASE_URL
const baseUrl = apiUrl.replace(/\/api\/?$/, '')
const emit = defineEmits(['close', 'submit']);

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Loading state
const loading = ref(false);

const refForm = ref<VForm | null>(null);
const isFormValid = ref(false);
const clientFormDefaults = ref<Client>(store.selectedClient || getDefaultClient());

const logo = ref<string>(store.selectedClient?.logo || '');
const imageFile = ref<File | null>(null);
const errorMessage = ref('');

// 👉 Field validation errors
const fieldErrors = ref<Record<string, string>>({});

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
}

// 👉 Explicitly define the type for filterItems
const filterItems = ref<{
  types: FilterItem[];
  gamuts: FilterItem[];
  status: FilterItem[];
  cities: FilterItem[];
  businessSector: FilterItem[];
}>({
  types: [],
  gamuts: [],
  status: [],
  cities: [],
  businessSector: [],
});

// 👉 Yup Validation Schema for email and phone only
const validationSchema = yup.object({
  email: yup.string()
    .nullable()
    .notRequired()
    .email('L\'email doit être valide'),

  phoneNumber: yup
    .string()
    .nullable()
    .notRequired()
    .transform((value) => {
      if (!value || typeof value !== 'string') return value;
      const trimmed = value.trim();
      if (trimmed === '') return trimmed;

      // Check for invalid "+" usage (multiple + or + not at start)
      const plusCount = (trimmed.match(/\+/g) || []).length;
      if (plusCount > 1 || (plusCount === 1 && !trimmed.startsWith('+'))) {
        return value; // Return unchanged to fail validation
      }

      // Keep leading + and remove all other non-digit characters
      const hasPlus = trimmed.startsWith('+');
      const cleaned = trimmed.replace(/\D/g, '');
      return hasPlus ? '+' + cleaned : cleaned;
    })
    .test('international-phone', 'Format de téléphone invalide', function (value) {
      if (!value || value === '') return true;
      const phoneRegex = /^\+?[0-9]{7,15}$/;
      return phoneRegex.test(value);
    }),
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
};

// 👉 Single field validation on blur
const validateSingleField = async (fieldName: string, value: any) => {
  const result = await validateField(fieldName, value);
  if (result === true) {
    delete fieldErrors.value[fieldName];
  } else {
    fieldErrors.value[fieldName] = result;
  }
};

// 👉 Validate email and phone fields for form submission
const validateEmailAndPhone = async () => {
  const fields = ['email', 'phoneNumber'];
  let isValid = true;

  for (const field of fields) {
    const value = clientFormDefaults.value[field as keyof Client];
    if (value) { // Only validate if field has a value
      const result = await validateField(field, value);
      if (result !== true) {
        fieldErrors.value[field] = result;
        isValid = false;
      } else {
        delete fieldErrors.value[field];
      }
    }
  }

  return isValid;
};

const requiredValidator = (v: any) => (v !== null && v !== undefined) || 'Ce champ est obligatoire';
const emailValidator = (v: string) => /.[^\n\r@\u2028\u2029]*@.+\..+/.test(v) || "Le courrier électronique doit être valide";

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
    logo.value = URL.createObjectURL(file);
    imageFile.value = file;
  }
};

const closeDrawer = () => {
  store.closeDrawer();
  refForm.value?.reset();
  refForm.value?.resetValidation();
  logo.value = '';
  imageFile.value = null;
  fieldErrors.value = {};
  emit('close');
};

const handleFormSubmit = async () => {
  const isEmailPhoneValid = await validateEmailAndPhone();

  if (isEmailPhoneValid) {
    refForm.value?.validate().then(async ({ valid }) => {
      if (valid) {
        loading.value = true;
        try {
          const clientData = new FormData();
          const payload = {
            ...clientFormDefaults.value,
            city_id: clientFormDefaults.value.city === store.selectedClient?.city ? store.selectedClient?.cityId : clientFormDefaults.value.city,
            client_type_id: clientFormDefaults.value.type === store.selectedClient?.type ? store.selectedClient?.clientTypeId : clientFormDefaults.value.type,
            gamut_id: clientFormDefaults.value.gamut === store.selectedClient?.gamut ? store.selectedClient?.gamutId : clientFormDefaults.value.gamut,
            status_id: clientFormDefaults.value.status === store.selectedClient?.status ? store.selectedClient?.statusId : clientFormDefaults.value.status,
            business_sector_id: clientFormDefaults.value.businessSector === store.selectedClient?.businessSector ? store.selectedClient?.businessSectorId : clientFormDefaults.value.businessSector,
          };

          if (imageFile.value) {
            clientData.append('logo', imageFile.value);
          }

          if (store.mode === 'add') {
            const response = await addClient(payload);
            const clientId = response.id;
            if (imageFile.value) {
              await addClientImage(clientId, clientData);
            }
            store.clients.push({ ...payload, id: clientId });
          } else if (store.mode === 'edit') {
            const clientId = payload.id;
            await updateClient(clientId, payload);
            if (imageFile.value) {
              await updateClientImage(clientId, clientData);
            }
            const index = store.clients.findIndex((c) => c.id === clientId);
            if (index !== -1) {
              store.clients[index] = { ...payload, id: clientId };
            }
          }

          closeDrawer();
          emit('submit');
        } catch (error) {
          console.error('Error preparing client data:', error);
          closeDrawer();
          const err = error as any;
          showSnackbar(`${err.response?.data.message}`, 'error');
        } finally {
          loading.value = false;
        }
      }
    });
  } else {
    showSnackbar('Veuillez corriger les erreurs dans le formulaire', 'error');
  }
};

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();

    filterItems.value.types = mapStaticData(staticData.data.clientTypes);
    filterItems.value.gamuts = mapStaticData(staticData.data.gamutes);
    filterItems.value.status = mapStaticData(staticData.data.statuses);
    filterItems.value.cities = mapStaticData(staticData.data.cities);
    filterItems.value.businessSector = mapStaticData(staticData.data.businessSectors);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

watch(() => store.selectedClient, (newClient) => {
  if (store.mode === 'edit' && newClient) {
    clientFormDefaults.value = { ...newClient };
    logo.value = newClient.logo || '';
  }
}, { immediate: true });

onMounted(async () => {
  if (store.mode === 'add') {
    clientFormDefaults.value = getDefaultClient();
  } else if (store.mode === 'edit' && store.selectedClient) {
    clientFormDefaults.value = { ...store.selectedClient };
    logo.value = store.selectedClient.logo || '';
  }

  await loadStaticData();

  if (store.mode === 'add') {
    const actifStatus = filterItems.value.status.find(status =>
      status.text === 'Actif'
    );
    if (actifStatus) {
      clientFormDefaults.value.status = actifStatus.value;
    }
  }
});

</script>

<template>
  <VForm class="mt-6" ref="refForm" v-model="isFormValid" @submit.prevent="handleFormSubmit">
    <v-alert v-if="errorMessage" type="error" class="mt-2">{{ errorMessage }}</v-alert>

    <VRow class="mt-6">
      <VCol cols="12" class="d-flex justify-center">
        <v-avatar @click="() => ($refs.fileInput as unknown as HTMLInputElement).click()" size="170"
          class="position-relative cursor-pointer">
          <v-img :src="imageFile ? logo : `${baseUrl}/${logo}` || '/path-to-default-avatar.jpg'" class="rounded-circle"
            cover></v-img>
          <VCardText v-if="!logo" icon class="position-absolute d-flex align-items-center justify-center">
            Photo
            <v-icon style="color: #ddd; font-size: 32px;">tabler-plus</v-icon>
          </VCardText>

          <!-- Hidden file input for image upload -->
          <input ref="fileInput" type="file" accept="image/*" class="d-none" @change="onFileChange" />
        </v-avatar>
      </VCol>

      <!-- Form fields -->
      <VCol cols="12" md="6">
        <VTextField v-model="clientFormDefaults.tradeName" :rules="store.mode === 'add' ? [requiredValidator] : []"
          label="Nom commercial *" placeholder="Nom commercial" variant="outlined" required />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="clientFormDefaults.legalName" :rules="store.mode === 'add' ? [requiredValidator] : []"
          label="Nom légal *" placeholder="Nom légal" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="clientFormDefaults.phoneNumber" label="Numéro de téléphone" placeholder="+2120606060606"
          variant="outlined" :error="!!fieldErrors.phoneNumber" :error-messages="fieldErrors.phoneNumber"
          @input="validateFieldRealtime('phoneNumber', clientFormDefaults.phoneNumber)"
          @blur="validateSingleField('phoneNumber', clientFormDefaults.phoneNumber)" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="clientFormDefaults.email" label="Email" placeholder="company@email.com" variant="outlined"
          :error="!!fieldErrors.email" :error-messages="fieldErrors.email"
          @input="validateFieldRealtime('email', clientFormDefaults.email)"
          @blur="validateSingleField('email', clientFormDefaults.email)" />
      </VCol>

      <VCol cols="12" md="6">
        <VAutocomplete v-model="clientFormDefaults.city" :items="filterItems.cities" item-title="text"
          item-value="value" label="Ville" placeholder="Ville" clearable clear-icon="tabler-x" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="clientFormDefaults.address" label="Adresse" placeholder="1234 Main St, Apt 101"
          variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="clientFormDefaults.ice" label="ICE" placeholder="ICE" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="clientFormDefaults.if" label="IF" placeholder="IF" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="clientFormDefaults.tp" label="TP" placeholder="TP" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="clientFormDefaults.rc" label="RC" placeholder="RC" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VAutocomplete v-model="clientFormDefaults.type" label="Type" placeholder="Type" :items="filterItems.types"
          item-title="text" item-value="value" clearable clear-icon="tabler-x" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VAutocomplete v-model="clientFormDefaults.gamut" :items="filterItems.gamuts" item-title="text"
          item-value="value" label="Gamme" placeholder="Gamme" clearable clear-icon="tabler-x" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VAutocomplete :disabled="store.mode === 'add'" v-model="clientFormDefaults.status" label="Statut *"
          placeholder="Select Statut" :rules="store.mode === 'add' ? [requiredValidator] : []"
          :items="filterItems.status" item-title="text" item-value="value" clearable clear-icon="tabler-x"
          variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VAutocomplete v-model="clientFormDefaults.businessSector" label="Secteur d'activité"
          placeholder="Secteur d'activité" :items="filterItems.businessSector" item-title="text" item-value="value"
          clearable clear-icon="tabler-x" variant="outlined" />
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
