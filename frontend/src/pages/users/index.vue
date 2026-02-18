<script setup lang="ts">
import AlertDialog from '@/components/dialogs/AlertDialog.vue';
import ModalForm from '@/components/ModalForm.vue';
import UserForm from '@/components/users/UserForm.vue';
import { useArchiveStoreStore } from '@/stores/archive';
import { useUserStore } from '@/stores/user';
import { deleteUser, exportUsers, fetchArchivedUsers, fetchStaticData, fetchUserById, fetchUsers } from '@services/api/user';
import { getDefaultUser, getDefaultUserFilterParams } from '@services/defaults';
import type { User, UserFilterParms } from '@services/models';
import { debounce } from 'lodash';
import { computed, ref } from 'vue';
import { VForm } from 'vuetify/components/VForm';

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

const apiUrl = (window as any).__ENV__?.API_BASE_URL
const baseUrl = apiUrl.replace(/\/api\/?$/, '')

// 👉 Store call
const store = useUserStore();
const archiveStore = useArchiveStoreStore();

// 👉 State variables for controlling drawer visibility
const isAddNewUserDrawerVisible = ref(false)

// 👉 State variables for form validation and loading status
const isLoading = ref(true)

// 👉 References to the forms
const refFormAdd = ref<VForm>()

// 👉 State variable for the selected user in edit mode
const selectedUser = ref<User>(getDefaultUser())

// 👉 State variables for image preview and file
const photo = ref<string>('')
const imageFile = ref<File | null>(null)

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the user to delete
const userToDelete = ref<User | null>(null)

// 👉 Filters and search
const filterParams = ref<UserFilterParms>(getDefaultUserFilterParams())

// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1,
})

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string | boolean;
}

// 👉 State variables for the filter options (initialized with proper structure)
const filterItems = ref<{
  titles: FilterItem[];
  roles: FilterItem[];
  status: FilterItem[];
}>({
  titles: [],
  roles: [],
  status: [],
});


// 👉 State variable for storing users data
const usersData = ref<{
  users: User[]
  totalUsers: number
}>({
  users: [],
  totalUsers: 0,
})

// 👉 Modal title logic
const title1 = computed(() => store.mode === 'add' ? 'Créer un utilisateur' : "Modifier les informations de l'utilisateur");

// 👉 Handle drawer visibility update
const handleDrawerModelValueUpdate = (val: boolean) => {
  isAddNewUserDrawerVisible.value = val
}

// 👉 Function to fetch users from the API
const loadUsers = async () => {
  isLoading.value = true;
  try {
    let data;
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = {
        search: filterParams.value.searchQuery,
      };
    } else {
      if (filterParams.value.selectedTitle) filters.title = filterParams.value.selectedTitle;
      if (filterParams.value.selectedRole) filters.role = filterParams.value.selectedRole;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;

    }
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
      data = await fetchArchivedUsers(filters, queryParams.value.itemsPerPage, queryParams.value.page)
    }
    else {
      data = await fetchUsers(filters, queryParams.value.itemsPerPage, queryParams.value.page)
    }


    usersData.value.users = data.users || [];

    usersData.value.totalUsers = data.totalUsers || 0

    if (usersData.value.users.length === 0 && queryParams.value.page > 1) {

      queryParams.value.page--;
      await loadUsers();
    }
  }
  catch (error) {
    console.error('Error fetching users:', error)
  }
  finally {
    isLoading.value = false
  }
}

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.titles = mapStaticData(staticData.data.titleFilter);
    filterItems.value.roles = mapStaticData(staticData.data.roleFilter);
    filterItems.value.status = mapStaticData(staticData.data.statusFilter);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

// 👉 Function to export user 
const exportUser = async () => {

  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;
    } else {
      if (filterParams.value.selectedTitle) filters.title = filterParams.value.selectedTitle;
      if (filterParams.value.selectedRole) filters.role = filterParams.value.selectedRole;
      if (filterParams.value.selectedStatus) filters.status = filterParams.value.selectedStatus;
    }
    const response = await exportUsers(filters, queryParams.value.itemsPerPage, queryParams.value.page);

    if (response.status === 200 && response.downloadUrl) {
      const downloadUrl = response.downloadUrl;
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.setAttribute('download', 'users.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      console.error('Failed to export users: Invalid response format');
    }
  } catch (error) {
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadUsers()
  loadStaticData();
});

const debouncedLoadUsers = debounce(async () => {
  await loadUsers();
}, 800);

// 👉 Watchers to reload users on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadUsers();
}, { deep: true });

// 👉 Function to handle page change
const onPageChange = (newPage: number) => {
  queryParams.value.page = newPage;
  loadUsers();
};

// 👉 Function to handle items per page change
const onItemsPerPageChange = (newItemsPerPage: number) => {
  queryParams.value.itemsPerPage = newItemsPerPage;
  loadUsers();
};

// 👉 clear filters
const onSearchInput = (newSearch: any) => {
  if (newSearch) {
    filterParams.value.selectedTitle = null;
    filterParams.value.selectedRole = null;
  }
};

// 👉 clear search
const onFilterChange = () => {
  {
    filterParams.value.searchQuery = null;
  }
};

// 👉 Table headers definition
const headers = [
  { title: 'Utilisateur', key: 'user' },
  { title: 'Titre', key: 'title' },
  { title: 'Permissions', key: 'roles' },
  { title: 'Statut', key: 'status' },
  ...((!archiveStore.isArchive)
    ? [{ title: 'Gestion', key: 'actions', sortable: false }]
    : []),]

// 👉 Function to open the drawer for adding a new user
const openAddNewUserDrawer = () => {
  selectedUser.value = getDefaultUser()
  isAddNewUserDrawerVisible.value = true
  store.setAddMode();

}

// 👉 Function to close the drawer and reset the form for adding a new user
const closeNavigationDrawer = () => {
  isAddNewUserDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()
  photo.value = ''
  imageFile.value = null
}

// 👉 Function to handle form submission for adding a new user
const onSubmit = async () => {
  try {
    closeNavigationDrawer();
    await loadUsers();
    showSnackbar("l'utilisateur", 'success');
  } catch (error) {
    console.error('Error adding user:', error);
    closeNavigationDrawer();
    showSnackbar("l'utilisateur", 'error');
  }
};

const openEditUserDrawer = async (user: User) => {
  photo.value = user.photo || ''
  const id = user.id
  const data = await fetchUserById(id)
  store.setEditMode(data);
  isAddNewUserDrawerVisible.value = true
}
const openView = (user: User) => {
  store.setEditMode(user);
}

// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (user: User) => {
  userToDelete.value = user
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleteUser = async () => {
  const userId = userToDelete.value?.id
  if (!userId) {
    console.error('User ID is undefined. Cannot delete user.')
    return
  }
  try {
    await deleteUser(userId)
    isDeleteDialogVisible.value = false
    await loadUsers()
    showSnackbar("l'utilisateur", 'success');
  } catch (error) {
    console.error('Error deleting user:', error)
    isDeleteDialogVisible.value = false
    const err = error as any;
    showSnackbar(`${err.response?.data.message}`, 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  userToDelete.value = null
}

const isClearableTitle = computed(() => {
  return filterParams.value.selectedTitle !== null;
});
const isClearableRole = computed(() => {
  return filterParams.value.selectedRole !== null;
});
const isClearableStatus = computed(() => {
  return filterParams.value.selectedStatus !== null;
});


const isDatePickerOpen = ref(false);

const handleDatePickerState = (isOpen: any) => {
  isDatePickerOpen.value = isOpen;
};
</script>

<template>
  <section>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Utilisateurs
        </h4>
      </div>
      <div>
        <!-- Add Button -->
        <VBtn v-if="!archiveStore.isArchive" prepend-icon="tabler-plus" @click="openAddNewUserDrawer">
          Ajouter un utilisateur
        </VBtn>
      </div>
    </div>
    <!-- 👉 Widgets -->
    <VCard class="mb-6">

      <!-- First Row: Items per page, Export, and Add buttons -->
      <VCardText>
        <VRow align="center" class="justify-end">
          <VCol>
            <VCardTitle class="pa-0">Filtres et recherche</VCardTitle>
          </VCol>
          <VCol class="d-flex align-center gap-2 justify-end">
            <!-- Items per Page Select -->
            <AppSelect :model-value="queryParams.itemsPerPage" :items="[
              { value: 10, title: '10' },
              { value: 50, title: '50' },
              { value: 100, title: '100' }
            ]" style="inline-size: 6.25rem;" @update:model-value="onItemsPerPageChange" />

            <!-- Export Button -->
            <VBtn v-if="!archiveStore.isArchive" variant="tonal" color="secondary" prepend-icon="tabler-upload"
              @click="exportUser">
              Exporter
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <!-- Second Row: Search, Filter 1, and Filter 2 -->
      <VCardText>
        <VRow>
          <!-- Search Field -->
          <VCol cols="12" sm="4">
            <VTextField v-model="filterParams.searchQuery" @input="onSearchInput" placeholder="Recherche"
              variant="outlined" label="Recherche" />
          </VCol>

          <VCol cols="12" sm="4">
            <VAutocomplete v-model="filterParams.selectedTitle" placeholder="Titre" label="Titres"
              :items="filterItems.titles" item-title="text" item-value="value" @update:modelValue="onFilterChange"
              :clearable="isClearableTitle" clear-icon="tabler-x" variant="outlined" />
          </VCol>
          <VCol cols="12" sm="4">
            <VAutocomplete v-model="filterParams.selectedRole" placeholder="Permission" label="Permissions"
              :items="filterItems.roles" item-title="text" item-value="value" @update:modelValue="onFilterChange"
              :clearable="isClearableRole" clear-icon="tabler-x" variant="outlined" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 Data Table -->
    <VCard>
      <VDataTable v-model:items-per-page="queryParams.itemsPerPage" :items="usersData.users" :headers="headers"
        item-value="id" class="text-no-wrap" :loading="isLoading" locale="fr">
        <template #loading>
          <div class="d-flex justify-center align-center loading-container">
            <VProgressCircular indeterminate color="primary" />
          </div>
        </template>
        <template #item.user="{ item }">
          <div class="d-flex align-center gap-4">
            <VAvatar size="38" class="text-body-1 text-uppercase" :color="!item.photo ? 'primary' : undefined">
              <VImg v-if="item.photo" :src="`${baseUrl}/${item.photo}`" cover />
              <span v-else>
                {{ avatarText(item.fullName ?? '') }}
              </span>
            </VAvatar>
            <div class="d-flex flex-column">
              <div class="d-flex align-center">
                <span class="text-body-1 text-truncate" @click="openView(item)">
                  <RouterLink
                    :to="{ name: archiveStore.isArchive ? 'archives-users-id' : 'users-id', params: { id: item.id } }"
                    class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
                    {{ item.fullName }}
                  </RouterLink>
                </span>

              </div>
              <small class="text-body-2 text-truncate">
                {{ item.email }}
              </small>
            </div>
          </div>
        </template>

        <template #item.roles="{ item }">
          <div class="managed-by-container d-flex flex-wrap align-items-center">
            <div v-for="(role, index) in (item.roles || []).slice(0, 2)" :key="index" class="managed-by-item">
              <VChip color="secondary" text-color="white" size="x-small" outlined dense class="ma-1">
                {{ role.name }}
              </VChip>
            </div>

            <RouterLink v-if="item.roles && item.roles.length > 2" :to="{ name: 'users-id', params: { id: item.id } }"
              style="font-size: 13px; color: #808390;" class="ma-1" @click.prevent="openView(item)">
              Afficher plus
            </RouterLink>
          </div>
        </template>






        <template #item.birthdate="{ item }">
          {{ item.birthdate ? formatDateToddmmYYYY(String(item.birthdate)) : '-' }}
        </template>

        <template #item.status="{ item }">
          <VChip v-if="item.status !== null" :color="resolveUserStatusVariant(item.status, item.passwordRequestCheck)"
            size="small" class="text-body-2 text-capitalize">
            {{ getStatusTitle(item.status, item.passwordRequestCheck) }}
          </VChip>
          <span v-else></span>
        </template>
        <template #item.title="{ item }">
          <span>{{ typeof (item as any).title === 'object' ? (item as any).title?.name : item.title }}</span>
        </template>

        <template #item.actions="{ item }">
          <div style="display: flex; gap: 8px;">
            <RouterLink
              :to="{ name: archiveStore.isArchive ? 'archives-users-id' : 'users-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              <IconBtn @click="openView(item)">

                <VIcon icon="tabler-eye" />
              </IconBtn>
            </RouterLink>

            <IconBtn v-if="!archiveStore.isArchive" @click="openEditUserDrawer(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <IconBtn v-if="!archiveStore.isArchive" @click="openDeleteDialog(item)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination :page="queryParams.page" :items-per-page="queryParams.itemsPerPage"
            :totalItems="usersData.totalUsers" @update:page="onPageChange" locale="fr" />
        </template>
      </VDataTable>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver cet utilisateur ?"
      deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
      :onDeleteClick="confirmDeleteUser" :onCancelClick="cancelDelete"
      @update:isVisible="isDeleteDialogVisible = $event" />

    <!-- 👉  user drawer -->
    <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isAddNewUserDrawerVisible"
      @update:model-value="handleDrawerModelValueUpdate" persistent :is-date-picker-open="isDatePickerOpen">

      <!-- Dialog close button slot -->
      <template #close-btn>
        <DialogCloseBtn @click="closeNavigationDrawer" />
      </template>

      <!-- Title and Subtitle slot -->
      <template #title>
        <h4 class="text-h4 text-center mb-2">
          {{ title1 }}
        </h4>
      </template>
      <!-- Form slot -->
      <template #form>
        <UserForm :mode="store.mode" :selected-user="store.selectedUser" @close="closeNavigationDrawer"
          @submit="onSubmit" @date-picker-state="handleDatePickerState" />
      </template>
    </ModalForm>
  </section>
</template>
<style>
/* Global styles */
.v-data-table-progress {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  height: 0;
  visibility: hidden;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  /* stylelint-disable-next-line order/properties-order */
  overflow: hidden;
}

/* Add more global styles as needed */
</style>
