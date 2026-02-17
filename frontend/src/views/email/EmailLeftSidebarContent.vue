<script setup lang="ts">
import { fetchSelectedEmailTemplate, fetchStaticDataClient, fetchStaticDataClientContact, fetchStaticDataClientDocumentCode, fetchStaticDataEmailTemplate } from '@/services/api/email';

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'toggleComposeDialogVisibility'): void
  (e: 'fetchEmailData', data: any): any
}>()

interface Props {
  emailsMeta: {
    inbox: number
    draft: number
    spam: number
    star: number
  }
}


const inboxEmails = ref(0)
const draftEmails = ref(0)
const spamEmails = ref(0)
const starredEmails = ref(0)

watch(() => props.emailsMeta, emailsMeta => {
  if (!emailsMeta)
    return

  inboxEmails.value = emailsMeta.inbox
  draftEmails.value = emailsMeta.draft
  spamEmails.value = emailsMeta.spam
  starredEmails.value = emailsMeta.star
}, { immediate: true, deep: true })


// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string | boolean;
}
const filterParams = ref({
  selectedClient: null,
  selectedContact: null,
  selectedDocumentCode: null,
  selectedDocument: null as { text: string; value: string } | null,
  selectedTemplate: null
})

const filterItems = ref<{
  contacts: FilterItem[];
  clients: FilterItem[];
  docCodes: FilterItem[];
  templates: FilterItem[];


}>({
  contacts: [],
  clients: [],
  docCodes: [],
  templates: [],

});

const documentItems = [
  { text: 'Devis', value: 'Devis' },
  { text: 'Bon de commande', value: 'Bon de commande' },
  { text: 'Bon de livraison', value: 'Bon de livraison' },
  { text: 'Facture', value: 'Facture' },
  { text: 'Reçu de commande', value: 'Reçu de commande' },
  { text: 'Facture avoir', value: 'Facture avoir' },

]

const clientId = ref<string | null>(null)

// 👉 Fetch static data and populate filter options
const loadStaticDataClient = async () => {
  try {
    const staticDataClient = await fetchStaticDataClient();


    filterItems.value.clients = mapStaticData(staticDataClient.data.clients);


  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadStaticDataClient();
});

watch(() => filterParams.value.selectedClient, async newClient => {
  clientId.value = newClient
  filterParams.value.selectedContact = null
  filterParams.value.selectedDocument = null
  filterParams.value.selectedDocumentCode = null
  filterParams.value.selectedTemplate = null
  filterItems.value.contacts = []
  filterItems.value.docCodes = []
  filterItems.value.templates = []
  if (newClient) {
    try {
      const { data } = await fetchStaticDataClientContact(newClient)
      filterItems.value.contacts = mapStaticData(data.contacts)
    } catch (error) {
      console.error("Failed to fetch contacts:", error)
    }
  }
})

watch(() => filterParams.value.selectedDocument, async newDoc => {
  filterParams.value.selectedDocumentCode = null
  filterItems.value.docCodes = []

  if (clientId.value && newDoc) {
    try {
      const { data } = await fetchStaticDataClientDocumentCode(clientId.value, newDoc?.value)
      filterItems.value.docCodes = mapStaticData(data.documentCodes)
    } catch (error) {
      console.error("Failed to fetch document codes:", error)
    }
  }
})

watch(() => filterParams.value.selectedDocumentCode, async newDoc => {
  filterParams.value.selectedTemplate = null
  filterItems.value.templates = []


  try {
    const { data } = await fetchStaticDataEmailTemplate()
    filterItems.value.templates = mapStaticData(data.emailTemplate)
  } catch (error) {
    console.error("Failed to fetch document codes:", error)
  }

})


const handleFetchSelectedEmailTemplate = async () => {
  try {

    const response = await fetchSelectedEmailTemplate(filterParams.value.selectedClient, filterParams.value.selectedContact, filterParams.value.selectedDocument?.value, filterParams.value.selectedDocumentCode, filterParams.value.selectedTemplate)
    emit('fetchEmailData', response)
    emit('toggleComposeDialogVisibility')
  } catch (error) {
    console.error("Failed to fetch document codes:", error)
  }
}

</script>

<template>
  <div class="d-flex flex-column h-100">
    <!-- 👉 Compose -->
    <div class="px-6 pb-5 pt-6">

      <VAutocomplete class="mb-4" v-model="filterParams.selectedClient" placeholder="Client" label="Clients"
        :items="filterItems.clients" item-title="text" item-value="value" clear-icon="tabler-x" variant="outlined"
        :rules="[(v: any) => !!v || 'Client is required']" clearable />

      <VAutocomplete class="mb-4" v-model="filterParams.selectedContact" placeholder="Contacts des clients" label="Contacts des clients"
        :items="filterItems.contacts" item-title="text" item-value="value" clear-icon="tabler-x" variant="outlined"
        :disabled="!filterParams.selectedClient" :rules="[(v: any) => !!v || 'Contact is required']" clearable />

      <VAutocomplete class="mb-4" v-model="filterParams.selectedDocument" return-object placeholder="Document"
        label="Document" :items="documentItems" item-title="text" item-value="value" clear-icon="tabler-x"
        variant="outlined" :disabled="!filterParams.selectedContact"
        :rules="[(v: any) => !!v || 'Document is required']" clearable />

      <VAutocomplete class="mb-4" v-model="filterParams.selectedDocumentCode" placeholder="Codes de document client"
        label="Codes de document client" :items="filterItems.docCodes" item-title="text" item-value="value"
        clear-icon="tabler-x" variant="outlined" :disabled="!filterParams.selectedDocument"
        :rules="[(v: any) => !!v || 'Document code is required']" clearable />

      <VAutocomplete class="mb-4" v-model="filterParams.selectedTemplate" placeholder="Email template" label="Email template"
        :items="filterItems.templates" item-title="text" item-value="value" clear-icon="tabler-x" variant="outlined"
        :disabled="!filterParams.selectedDocumentCode" :rules="[(v: any) => !!v || 'Email template is required']"
        clearable />



      <VBtn :disabled="!filterParams.selectedTemplate" block @click="handleFetchSelectedEmailTemplate()">
        Composer
      </VBtn>
    </div>


  </div>
</template>

<style lang="scss">
.email-filters,
.email-labels {

  .email-filter-active,
  .email-label-active {
    &::after {
      position: absolute;
      background: currentcolor;
      block-size: 100%;
      content: "";
      inline-size: 3px;
      inset-block-start: 0;
      inset-inline-start: 0;
    }
  }
}

.email-filters {
  >li {
    position: relative;
    margin-block-end: 4px;
    padding-block: 4px;
    padding-inline: 24px;
  }
}

.email-labels {
  >li {
    position: relative;
    margin-block-end: 0.75rem;
    padding-inline: 24px;
  }
}
</style>
