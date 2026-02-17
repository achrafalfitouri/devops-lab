<script setup lang="ts">
import { getDefaultContacts } from '@/services/defaults';
import { useContactsStore } from '@/stores/contact';
import { deleteContacts, fetchContacts } from '@services/api/contact';
import type { Contacts } from '@services/models';
import { computed, ref } from 'vue';
import { VForm } from 'vuetify/components/VForm';


const props = defineProps<{ clientId: any, data: any }>();

// 👉 State variable for storing contacts data
const contactsData = ref<{
  contacts: Contacts[]
  totalcontacts: number
}>({
  contacts: [],
  totalcontacts: 0,
})

const isLoading = ref(true)

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

// 👉 Store call
const store = useContactsStore();

// 👉 State variables for controlling drawer visibility
const isAddNewcontactDrawerVisible = ref(false)
// 👉 References to the forms
const refFormAdd = ref<VForm>()

// 👉 State variable for the selected contact in edit mode
const selectedcontact = ref<Contacts>(getDefaultContacts())

// 👉 State for dialog visibility
const isDeleteDialogVisible = ref(false)

// 👉 State for storing the contact to delete
const contactToDelete = ref<Contacts | null>(null)
// 👉 Modal title logic
const title1 = computed(() => store.mode === 'add' ? 'Ajouter les informatons du contact' : 'Modifier les informatons du contact');
const title2 = computed(() => store.mode === 'add' ? 'Ajout des informatons du contact' : 'Modification des informatons du contact');

// 👉 Data table options
const sortBy = ref<string | undefined>(undefined)
const orderBy = ref<string | undefined>(undefined)


const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// 👉 Table headers definition
const headers = [
  { title: 'Contact', key: 'contact' },
  { title: 'Titre', key: 'title' },
  { title: 'Téléphone', key: 'phone' },
  { title: 'Gestion', key: 'actions', sortable: false },
]

// 👉 Function to fetch contacts from the API
const loadcontacts = async () => {
  isLoading.value = true;
  try {
    const data = await fetchContacts(props.clientId);
    contactsData.value.contacts = data.contacts || [];
    contactsData.value.totalcontacts = data.totalContacts || 0;
  } catch (error) {
    console.error('Error fetching contacts:', error);
  } finally {
    isLoading.value = false;
  }
};

// 👉 Call loadContacts on component setup
onMounted(() => {
  loadcontacts()
});

// 👉 Handle drawer visibility update
const handleDrawerModelValueUpdate = (val: boolean) => {
  isAddNewcontactDrawerVisible.value = val
}
// 👉 Function to open the drawer for adding a new contact
const openAddNewcontactDrawer = () => {
  selectedcontact.value = getDefaultContacts()
  isAddNewcontactDrawerVisible.value = true
  store.setAddMode();
}

// 👉 Function to close the drawer and reset the form for adding a new contact
const closeNavigationDrawer = () => {
  isAddNewcontactDrawerVisible.value = false
  refFormAdd.value?.reset()
  refFormAdd.value?.resetValidation()
}
// 👉 Function to handle form submission for adding a new contact
const onSubmit = async () => {
  try {
    closeNavigationDrawer();
    await loadcontacts();
    showSnackbar('le contact', 'success');
  } catch (error) {
    console.error('Error adding contact:', error);
    closeNavigationDrawer();
    showSnackbar('le contact', 'error');
  }
};

// 👉 Opens the drawer for editing a contact.
const openEditcontactDrawer = (contact: Contacts) => {
  store.setEditMode(contact);
  isAddNewcontactDrawerVisible.value = true
}
// 👉 Function to open the delete confirmation dialog
const openDeleteDialog = (contact: Contacts) => {
  contactToDelete.value = contact
  isDeleteDialogVisible.value = true
}

// 👉 Function to handle delete action
const confirmDeleteContact = async () => {
  const contactId = contactToDelete.value
  if (!contactId) {
    console.error('contact ID is undefined. Cannot delete contact.')
    return
  }
  try {
    await deleteContacts(contactId)
    isDeleteDialogVisible.value = false
    await loadcontacts()
    showSnackbar('le contact', 'success');
  } catch (error) {
    console.error('Error deleting contact:', error)
    isDeleteDialogVisible.value = false
    showSnackbar('le contact', 'error');
  }
}

// 👉 Function to cancel the delete action
const cancelDelete = () => {
  isDeleteDialogVisible.value = false
  contactToDelete.value = null
}
</script>

<template>
  <VCard>
    <VCardText>
      <div class="d-flex justify-space-between flex-wrap align-center gap-4">
        <h5 class="text-h5">
          Contacts
        </h5>
        <div>
          <!-- 👉 Add client button -->
          <VBtn :disabled="props.data?.legalName === 'Client de passage'" prepend-icon="tabler-plus"
            @click="openAddNewcontactDrawer">
            Ajouter contact
          </VBtn>
        </div>
      </div>
    </VCardText>

    <VDivider />

    <VDataTable :headers="headers" :items="contactsData.contacts" item-value="id" class="text-no-wrap"
      @update:options="updateOptions" :loading="isLoading">
      <!-- Custom circular loading indicator -->
      <template #loading>

        <div class="d-flex justify-center align-center loading-container">
          <VProgressCircular indeterminate color="primary" />
        </div>
      </template>
      <template #item.contact="{ item }">
        <div class="d-flex align-center gap-4">
          <VAvatar size="38" :color="resolveUserRoleVariant(item.fullName).color" class="text-body-1 text-uppercase">
            {{ avatarText(item.fullName ?? '') }}
          </VAvatar>
          <div class="d-flex flex-column">
            <div class="d-flex align-center">
              <span class="text-body-1 text-truncate">
                {{ item.fullName }}
              </span>
            </div>
            <small class="text-body-2 text-truncate">
              {{ item.email }}
            </small>
          </div>
        </div>
      </template>



      <!-- Actions -->

      <template #item.actions="{ item }">
        <div style="display: flex; gap: 8px;">

          <IconBtn @click="openEditcontactDrawer(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn value="delete" @click="openDeleteDialog(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </div>
      </template>
      <template #bottom></template>


    </VDataTable>
  </VCard>
  <!-- Delete Confirmation Dialog -->
  <AlertDialog :isVisible="isDeleteDialogVisible" message="Êtes-vous sûr de vouloir archiver ce contact ?"
    deleteButtonText="Confirmer" cancelButtonText="Annuler" deleteButtonColor="error" cancelButtonColor="secondary"
    :onDeleteClick="confirmDeleteContact" :onCancelClick="cancelDelete"
    @update:isVisible="isDeleteDialogVisible = $event" />

  <!-- 👉  contact drawer -->
  <ModalForm :width="$vuetify.display.smAndDown ? 'auto' : 900" :model-value="isAddNewcontactDrawerVisible"
    @update:model-value="handleDrawerModelValueUpdate" persistent>
    <!-- Dialog close button slot -->
    <template #close-btn>
      <DialogCloseBtn @click="closeNavigationDrawer" />

    </template>
    <!-- Title and Subtitle slot -->
    <template #title>
      <h4 class="text-h4 text-center mb-2">
        {{ title1 }}
      </h4>
      <p class="text-body-1 text-center mb-6">
        {{ title2 }}
      </p>
    </template>
    <!-- Form slot -->
    <template #form>
      <ContactsForm :mode="store.mode" :selected-contact="store.selectedContacts" @close="closeNavigationDrawer"
        @submit="onSubmit" />
    </template>
  </ModalForm>
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
