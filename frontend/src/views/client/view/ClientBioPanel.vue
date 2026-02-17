<script setup lang="ts">
import DialogCloseBtn from '@/@core/components/DialogCloseBtn.vue';
import ModalForm from '@/components/ModalForm.vue';
import ClientForm from '@/components/clients/ClientForm.vue';
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import { router } from '@/plugins/1.router';
import { useClientStore } from '@/stores/client';
import { deleteClient } from '@services/api/client';
import type { Client } from '@services/models';
import { useArchiveStoreStore } from '@/stores/archive';
import { ref } from 'vue';
import { VForm } from 'vuetify/components/VForm';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

const apiUrl = import.meta.env.VITE_API_BASE_URL
const baseUrl = apiUrl.replace(/\/api\/?$/, '')

// 👉 Store call
const store = useClientStore();
const archiveStore = useArchiveStoreStore();


//👉 Props and ref declarations
const props = defineProps<{ clientData: any }>();
const localClientData = ref<Client>({ ...props.clientData });
const refFormEdit = ref<VForm | null>(null);
const isEditclientDrawerVisible = ref(false)
const originalClientData = ref<Client | null>(null);
const emit = defineEmits(['client-updated'])

const isDeleteDialogVisible = ref(false)

const avatarText = (client: string | null) => {
  if (!client) return '';
  const words = client.trim().split(' ');
  if (words.length === 1) {
    return words[0][0].toUpperCase(); // Single-word name
  }
  return (words[0][0] + words[words.length - 1][0]).toUpperCase(); // First and last initials
};


// 👉 Opens the drawer for editing a client.
const openEditClientDrawer = () => {
  store.setEditMode({ ...props.clientData });
  isEditclientDrawerVisible.value = true;
};

//👉 Closes the edit client drawer and resets the form validation.
const closeEditclientDrawer = () => {
  isEditclientDrawerVisible.value = false
  if (refFormEdit.value) {
    refFormEdit.value.resetValidation();

    if (originalClientData.value) {
      localClientData.value = { ...originalClientData.value };
    }
  }
};

// 👉 Function to handle form submission for adding a new client
const onSubmit = async () => {
  try {
    closeEditclientDrawer();
    emit('client-updated');
    showSnackbar('le client', 'success');
  } catch (error) {
    console.error('Error adding client:', error);
    closeEditclientDrawer();
    showSnackbar('le client', 'error');
  }
};

// 👉 State for dialog visibility Function to open the delete confirmation dialog
const openDeleteDialog = () => {
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleteclient = async () => {
  const clientId = props.clientData.id;

  if (!clientId) {
    console.error('No valid client ID found for deletion.');
    return;
  }

  try {
    await deleteClient(clientId);
    isDeleteDialogVisible.value = false;
    showSnackbar('le client', 'success');
    await router.push('/clients');
  } catch (error) {
    console.error('Error deleting client:', error);
    cancelDelete();
    showSnackbar('le client', 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
}
</script>

<template>
  <VRow>
    <!-- SECTION client Details -->
    <VCol cols="12">
      <VCard v-if="props.clientData">
        <VCardText class="text-center pt-12">
          <VAvatar :size="100" :color="!props.clientData.logo ? 'primary' : undefined">
            <VImg v-if="props.clientData.logo" :src="`${baseUrl}/${props.clientData.logo}`" cover />

            <span v-else class="text-5xl font-weight-medium text-uppercase">
              {{ avatarText(props.clientData.legalName ?? '') }}
            </span>
          </VAvatar>
          <!-- <VAvatar  :size="100"
            :color="!props.clientData.logo ? 'primary' : 'primary'"
            >
            <span  class="text-5xl font-weight-medium text-uppercase">
              {{ avatarText(props.clientData.legalName ?? '') }}
            </span>
          </VAvatar> -->

          <!-- 👉 Client fullName -->
          <h5 class="text-h5 mt-4">
            {{ props.clientData?.legalName }}
          </h5>
          <div class="text-body-1">
          {{ props.clientData?.code }}
          </div>

          <div class="d-flex justify-space-evenly gap-x-5 mt-6">
          </div>

        </VCardText>

        <!-- 👉 Client Details -->
        <VCardText>
          <h5 class="text-h5">
            Informations
          </h5>

          <VDivider class="my-4" />

          <VList class="card-list mt-2">
                <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Statut :
                </h6>
                <VChip v-if="props.clientData?.status" label
                  :color="resolveClientStatusVariant(props.clientData?.status)" size="small">
                  {{ props.clientData?.status }}
                </VChip>
                <span v-else></span>
              </div>
            </VListItem>
            <VListItem>
              <h6 class="text-h6">
                Téléphone :
                <span v-if="props.clientData?.phoneNumber" class="text-body-1 d-inline-block">
                  {{ props.clientData?.phoneNumber }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>
            <VListItem>
              <h6 class="text-h6">
                Email :
                <span v-if="props.clientData?.email" class="text-body-1 d-inline-block">
                  {{ props.clientData?.email }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>
            <VListItem>
              <h6 class="text-h6">
                Ville :
                <span v-if="props.clientData?.city" class="text-body-1 d-inline-block">
                  {{ props.clientData?.city }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>
            <VListItem>
              <h6 class="text-h6">
                Adresse :
                <span v-if="props.clientData?.address" class="text-body-1 d-inline-block">
                  {{ props.clientData?.address }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>
            <VListItem>
              <h6 class="text-h6">
                Secteur d’activité :
                <span v-if="props.clientData?.businessSector" class="text-body-1 d-inline-block">
                  {{ props.clientData?.businessSector }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>
        
            <VListItem>
              <h6 class="text-h6">
                Type :
                <span v-if="props.clientData?.type" class="text-body-1 d-inline-block">
                  {{ props.clientData?.type }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>

            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Gamme :
                </h6>
                <VChip v-if="props.clientData?.gamut" label :color="resolveClientGamutVariant(props.clientData?.gamut)"
                  size="small">
                  {{ props.clientData?.gamut }}
                </VChip>
                <span v-else></span>
              </div>
            </VListItem>
          </VList>
        </VCardText>

        <VCardText v-if="!archiveStore.isArchive" class="text-center">
          <VBtn :disabled="props.clientData?.legalName === 'Client de passage'" block @click="openEditClientDrawer()">
            Modifier les informations
          </VBtn>
          <VBtn class="mt-3" block variant="tonal" color="error" @click="openDeleteDialog">
            Archiver
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->
  </VRow>

  <!-- Delete Confirmation Dialog -->
  <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce client ?"
    deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
    :onDeleteClick="confirmDeleteclient" :onCancelClick="cancelDelete"
    @update:isVisible="isDeleteDialogVisible = $event" />

  <!-- 👉 Edit user drawer -->
  <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isEditclientDrawerVisible"
    @update:model-value="(val: boolean) => isEditclientDrawerVisible = val" persistent>
    <!-- Dialog close button slot -->
    <template #close-btn>
      <DialogCloseBtn @click="closeEditclientDrawer" />

    </template>
    <!-- Title and Subtitle slot -->
    <template #title>
      <h4 class="text-h4 text-center mb-2">
        Modifier les informations du client
      </h4>
    </template>

    <!-- Form slot -->
    <template #form>
      <ClientForm :mode="store.mode" :selected-client="store.selectedClient" @close="closeEditclientDrawer"
        @submit="onSubmit" />

    </template>
  </ModalForm>


</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.current-plan {
  background: linear-gradient(45deg, rgb(var(--v-theme-primary)) 0%, #9e95f5 100%);
  color: #fff;
}

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
