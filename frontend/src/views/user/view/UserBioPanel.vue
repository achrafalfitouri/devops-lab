<script setup lang="ts">
import UserCashRegister from '@/components/users/UserCashRegister.vue';
import UserForm from '@/components/users/UserForm.vue';
import UserRole from '@/components/users/UserRole.vue';
import { router } from '@/plugins/1.router';
import { useAuthStore } from '@/stores/auth';
import { useUserStore } from '@/stores/user';
import { deleteUser } from '@services/api/user';
import type { User } from '@services/models';
import { useArchiveStoreStore } from '@/stores/archive';
import { ref } from 'vue';
import { VForm } from 'vuetify/components/VForm';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

const apiUrl = import.meta.env.VITE_API_BASE_URL
const baseUrl = apiUrl.replace(/\/api\/?$/, '')
// 👉 Store call
const store = useUserStore();
const authStore = useAuthStore();
const archiveStore = useArchiveStoreStore();


//👉 Props and ref declarations
const props = defineProps<{ userData: any }>();
const localUserData = ref<User>({ ...props.userData });
const refFormEdit = ref<VForm | null>(null);
const isEditUserDrawerVisible = ref(false);
const isEditUserRoleDrawerVisible = ref(false);
const isEditUserCashRegisterDrawerVisible = ref(false);
const originalUserData = ref<User | null>(null);

const isDeleteDialogVisible = ref(false)

const emit = defineEmits(['user-updated'])

const avatarText = (user: string | null) => {
  if (!user) return '';
  const words = user.trim().split(' ');
  if (words.length === 1) {
    return words[0][0].toUpperCase();
  }
  return (words[0][0] + words[words.length - 1][0]).toUpperCase();
};


// 👉 computed property to conditionally display the buttons.
const route = useRoute();

const isProfileRoute = computed(() => route.name === 'profil-id');

// 👉 Opens the drawer for editing a user.
const openEditUserDrawer = () => {
  store.setEditMode({ ...props.userData });
  isEditUserDrawerVisible.value = true;

};

// 👉 Opens the drawer for editing a user role.
const openEditRoleUserDrawer = () => {
  store.setEditMode({ ...props.userData });
  isEditUserRoleDrawerVisible.value = true;

};

//👉 Closes the edit user drawer and resets the form validation.
const closeEditUserDrawer = () => {
  isEditUserDrawerVisible.value = false;
  if (refFormEdit.value) {
    refFormEdit.value.resetValidation();

    if (originalUserData.value) {
      localUserData.value = { ...originalUserData.value };
    }
  }
};

//👉 Closes the edit user role drawer and resets the form validation.
const closeEditRoleUserDrawer = () => {
  isEditUserRoleDrawerVisible.value = false;
  if (refFormEdit.value) {
    refFormEdit.value.resetValidation();

    if (originalUserData.value) {
      localUserData.value = { ...originalUserData.value };
    }
  }
};
const closeEditCashRegisterUserDrawer = () => {
  isEditUserCashRegisterDrawerVisible.value = false;
  if (refFormEdit.value) {
    refFormEdit.value.resetValidation();

    if (originalUserData.value) {
      localUserData.value = { ...originalUserData.value };
    }
  }
};

// 👉 Updates the user information after validating the form.
const onSubmit = async () => {
  try {
    closeEditUserDrawer();
    emit('user-updated');
    // Show success snackbar
    showSnackbar("l'utilisateur", 'success');
  } catch (error) {
    console.error('Error updating user:', error);
    // Show error snackbar
    closeEditUserDrawer();
    showSnackbar("l'utilisateur", 'error');
  }
}

// 👉 State for dialog visibility Function to open the delete confirmation dialog
const openDeleteDialog = () => {
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleteUser = async () => {
  const userId = props.userData?.id;

  if (!userId) {
    console.error('No valid user ID found for deletion.');
    return;
  }

  try {
    await deleteUser(userId);
    isDeleteDialogVisible.value = false;
    // Show success snackbar
    showSnackbar("l'utilisateur", 'success');
    await router.push('/users');
  } catch (error) {
    console.error('Error deleting user:', error);
    // Show error snackbar
    cancelDelete();
    showSnackbar("l'utilisateur", 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
}
</script>

<template>
  <VRow>
    <!-- SECTION Customer Details -->
    <VCol cols="12">
      <VCard v-if="props.userData">
        <VCardText class="text-center pt-12">
          <!-- 👉 Avatar -->
          <VAvatar :size="100" :color="!props.userData.photo ? 'primary' : undefined">
            <VImg v-if="props.userData.photo" :src="`${baseUrl}/${props.userData.photo}`" cover />

            <span v-else class="text-5xl font-weight-medium text-uppercase">
              {{ avatarText(props.userData.fullName ?? '') }}
            </span>
          </VAvatar>

          <!-- 👉 User fullName -->
          <h5 class="text-h5 mt-4">
            {{ props.userData?.fullName }}
          </h5>
          <div class="text-body-1">
            <!-- {{ props.userData?.code }} -->
            {{ props.userData?.number }}
          </div>

        </VCardText>

        <!-- 👉 Customer Details -->
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
                <VChip v-if="props.userData?.status !== null" label
                  :color="resolveUserStatusVariant(props.userData?.status)" size="small">
                  {{ getStatusTitle(props.userData?.status) }}
                </VChip>
                <span v-else></span>
              </div>
            </VListItem>
            
            <VListItem>
              <h6 class="text-h6">
                Nom :
                <span v-if="props.userData?.lastName" class="text-body-1 d-inline-block">
                  {{ props.userData?.lastName }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>

                        <VListItem>
              <h6 class="text-h6">
                Prénom :
                <span v-if="props.userData?.firstName" class="text-body-1 d-inline-block">
                  {{ props.userData?.firstName }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>
               <VListItem>
              <h6 class="text-h6">
                Titre :
                <span v-if="props.userData?.title" class="text-body-1 d-inline-block">
                  {{ props.userData?.title }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>
                  <VListItem>
              <h6 class="text-h6">
                Téléphone :
                <span v-if="props.userData?.phone" class="text-body-1 d-inline-block">
                  {{ props.userData?.phone }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>

            
            <VListItem>
              <h6 class="text-h6">
                Email :
                <span v-if="props.userData?.email" class="text-body-1 d-inline-block">
                  {{ props.userData?.email }}
                </span>
                <span v-else></span>
              </h6>
            </VListItem>
            <VListItem>
              <h6 class="text-h6">
                Genre :
                <span v-if="props.userData?.gender" class="text-body-1 d-inline-block">
                  {{ props.userData?.gender }}
                </span>
              </h6>
            </VListItem>

          </VList>
        </VCardText>

        <VCardText v-if="!archiveStore.isArchive" class="text-center">
          <VBtn v-if="!authStore.isLogout && !isProfileRoute" block @click="openEditUserDrawer()">
            Modifier les informations
          </VBtn>
          <VBtn v-if="!authStore.isLogout && !isProfileRoute" class="mt-3" block @click="openEditRoleUserDrawer()">
            Modifier les permissions
          </VBtn>

          <VBtn v-if="!authStore.isLogout && !isProfileRoute" class="mt-3" block variant="tonal" color="error"
            @click="openDeleteDialog">
            Archiver
          </VBtn>

          <!-- Show "Se déconnecter" button only if viewing own profile -->
          <VBtn v-if="authStore.user?.id === props.userData.id && isProfileRoute" class="mt-3" block variant="tonal"
            color="error" @click="handleLogout">
            Se déconnecter
          </VBtn>

        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- Delete Confirmation Dialog -->
  <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver cet utilisateur ?"
    deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
    :onDeleteClick="confirmDeleteUser" :onCancelClick="cancelDelete"
    @update:isVisible="isDeleteDialogVisible = $event" />

  <!-- 👉 Edit user drawer -->
  <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isEditUserDrawerVisible"
    @update:model-value="(val: boolean) => isEditUserDrawerVisible = val" persistent>
    <!-- Dialog close button slot -->
    <template #close-btn>
      <DialogCloseBtn @click="closeEditUserDrawer" />

    </template>
    <!-- Title and Subtitle slot -->
    <template #title>
      <h4 class="text-h4 text-center mb-2">
        Modifier les informations de l’utilisateur

      </h4>
    </template>

    <!-- Form slot -->
    <template #form>
      <UserForm :mode="store.mode" :selected-client="store.selectedUser" @close="closeEditUserDrawer"
        @submit="onSubmit" />
    </template>
  </ModalForm>
  <!-- 👉 Edit role user drawer -->
  <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isEditUserRoleDrawerVisible"
    @update:model-value="(val: boolean) => isEditUserRoleDrawerVisible = val" persistent>
    <!-- Dialog close button slot -->
    <template #close-btn>
      <DialogCloseBtn @click="closeEditRoleUserDrawer" />
    </template>
    <!-- Title and Subtitle slot -->
    <template #title>
      <h4 class="text-h4 text-center mb-2">
        Modifier les permissions de l’utilisateur

      </h4>
    </template>

    <!-- Form slot -->
    <template #form>
      <UserRole :mode="store.mode" :selected-user="store.selectedUser" @close="closeEditRoleUserDrawer"
        @submit="onSubmit" />
    </template>
  </ModalForm>
  <!-- 👉 Edit CashRegister user drawer -->
  <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isEditUserCashRegisterDrawerVisible"
    @update:model-value="(val: boolean) => isEditUserCashRegisterDrawerVisible = val" persistent>
    <!-- Dialog close button slot -->
    <template #close-btn>
      <DialogCloseBtn @click="closeEditCashRegisterUserDrawer" />
    </template>
    <!-- Title and Subtitle slot -->
    <template #title>
      <h4 class="text-h4 text-center mb-2">
        Modifier les caisse des utilisateurs
      </h4>
    </template>

    <!-- Form slot -->
    <template #form>
      <UserCashRegister :mode="store.mode" :selected-user="store.selectedUser" @close="closeEditCashRegisterUserDrawer"
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
