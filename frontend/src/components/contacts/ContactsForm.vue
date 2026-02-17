<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { VForm } from 'vuetify/components/VForm';
import { getDefaultContacts } from '@services/defaults';
import type { Contacts } from '@services/models';
import { useContactsStore } from '@stores/contact';
import { addContacts, fetchStaticData, updateContacts } from '@/services/api/contact';

const route = useRoute('clients-id')
const store = useContactsStore();
const emit = defineEmits(['close', 'submit']);


// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Loading state
const loading = ref(false);

const refForm = ref<VForm | null>(null);
const isFormValid = ref(false);
const contactsFormDefaults = ref<Contacts>(store.selectedContacts || getDefaultContacts());

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
}

// 👉 Explicitly define the type for filterItems
const filterItems = ref<{
  clients: FilterItem[];
}>({
  clients: []
});

const requiredValidator = (v: any) => (v !== null && v !== undefined) || 'Ce champ est obligatoire';
const emailValidator = (v: string) => /.[^\n\r@\u2028\u2029]*@.+\..+/.test(v) || "L'email doit être valide";

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
        const id = route.params.id
        const payload = {
          ...contactsFormDefaults.value,
          clientId: id,

        };

        if (store.mode === 'add') {
          const response = await addContacts(payload);
          const contactsId = response.id;
          store.contacts.push({ ...payload, id: contactsId });
        } else if (store.mode === 'edit') {
          const contactsId = payload.id;
          await updateContacts(contactsId, payload);

          const index = store.contacts.findIndex((c) => c.id === contactsId);
          if (index !== -1) {
            store.contacts[index] = { ...payload, id: contactsId };
          }
        }

        closeDrawer();
        emit('submit');
      } catch (error) {
        console.error('Error preparing contacts data:', error);
        closeDrawer();
        const err = error as any; 
        showSnackbar(`${err.response?.data.message}`, 'error');      } finally {
        loading.value = false;
      }
    }
  });
};

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.clients = mapStaticData(staticData.data.clients);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

watch(() => store.selectedContacts, (newContacts) => {
  if (store.mode === 'edit' && newContacts) {
    contactsFormDefaults.value = { ...newContacts };
  }
}, { immediate: true });

onMounted(async () => {
  if (store.mode === 'add') {
    contactsFormDefaults.value = getDefaultContacts();
  } else if (store.mode === 'edit' && store.selectedContacts) {
    contactsFormDefaults.value = { ...store.selectedContacts };

  }
  loadStaticData();
});

</script>

<template>
  <VForm class="mt-6" ref="refForm" v-model="isFormValid" @submit.prevent="handleFormSubmit">
    <VRow class="mt-6">

      <!-- Form fields -->
      <VCol cols="12" md="6">
        <VTextField v-model="contactsFormDefaults.firstName" :rules="store.mode === 'add' ? [requiredValidator] : []"
          label="Prénom *" placeholder="Prénom" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="contactsFormDefaults.lastName" 
          label="Nom" placeholder="Nom" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="contactsFormDefaults.email"
          :rules="store.mode === 'add' ? [requiredValidator, emailValidator] : []" label="Email *"
          placeholder="contact@email.com" variant="outlined" />
      </VCol>
      <VCol cols="12" md="6">
        <VTextField v-model="contactsFormDefaults.title" 
          label="Titre" placeholder="Titre" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="contactsFormDefaults.phone" :rules="store.mode === 'add' ? [requiredValidator] : []"
          label="Téléphone *" placeholder="+1-541-754-3010"variant="outlined"  />
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
