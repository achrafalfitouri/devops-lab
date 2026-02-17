<script lang="ts" setup>
import TiptapEditor from '@/@core/components/TiptapEditor.vue';
import { sendEmail } from '@/services/api/email';


// 👉 Accept cash register as prop
const props = withDefaults(defineProps<{
  emailData?: {
    to: string
    subject: string
    content: string
    rawHtml: string
    clientId: string
    contactId: string
  } | null
}>(), {

});

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'fetchEmails'): void
}>();

watch(() => props.emailData, emailData => {
  if (!emailData)
    return
  to.value = emailData.to
  subject.value = emailData.subject
  content.value = emailData.content
}, { immediate: true, deep: true })

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

const content = ref('')

const to = ref('')
const subject = ref('')
const message = ref('')


const resetValues = () => {
  to.value = subject.value = message.value = ''
}

// Send Email Function
const sendEmailHandler = async () => {
  try {
    console!.log("Sending email...", props.emailData);
    // Prepare the email data object
    const emailData = {
      to: to.value,
      subject: subject.value,
      message: content.value,
      clientId: props.emailData?.clientId,
      contactId: props.emailData?.contactId,
      content: props.emailData?.content,

    };
    const response = await sendEmail(emailData);

    if (response.status === "success") {
      resetValues(); 
      showSnackbar("L'email", 'success');
      emit('close'); 
      emit('fetchEmails');
    }
  } catch (error) {
    console.error("Failed to send email:", error);
    emit('close');
    showSnackbar("L'email", 'error');
  }
};
</script>

<template>
  <VCard class="email-compose-dialog" elevation="10" max-width="30vw">
    <VCardItem class="py-3 px-6">
      <div class="d-flex align-center">
        <h5 class="text-h5">
          Composez le courrier        
        </h5>
        <VSpacer />

        <div class="d-flex align-center gap-x-2">
          <IconBtn size="small" icon="tabler-minus" @click="$emit('close')" />
          <IconBtn size="small" icon="tabler-x" @click="$emit('close'); resetValues()" />
        </div>
      </div>
    </VCardItem>

    <div class="px-1 pe-6 py-1">
      <VTextField v-model="to" density="compact">
        <template #prepend-inner>
          <div class="text-base font-weight-medium text-disabled">
            À:         
         </div>
        </template>
      </VTextField>
    </div>

    <VDivider />
    <div class="px-1 pe-6 py-1">
      <VTextField v-model="subject" density="compact">
        <template #prepend-inner>
          <div class="text-base font-weight-medium text-disabled">
            Sujet:
          </div>
        </template>
      </VTextField>
    </div>

    <VDivider />

    <TiptapEditor v-model="content" placeholder="Message" />

    <VDivider />

    <div class="d-flex align-center px-6 py-4">
      <VBtn color="primary" class="me-4" append-icon="tabler-send" @click="sendEmailHandler">
        envoyer

      </VBtn>
      <VSpacer />

    </div>
  </VCard>
</template>

<style lang="scss">
@use "@core/scss/base/mixins";

.v-card.email-compose-dialog {
  z-index: 910 !important;

  @include mixins.elevation(18);

  .v-field--prepended {
    padding-inline-start: 20px;
  }

  .v-field__prepend-inner {
    align-items: center;
    padding: 0;
  }

  .v-card-item {
    background-color: rgba(var(--v-theme-on-surface), var(--v-hover-opacity));
  }

  .v-textarea .v-field {
    --v-field-padding-start: 20px;
  }

  .v-field__outline {
    display: none;
  }

  .v-input {
    .v-field__prepend-inner {
      display: flex;
      align-items: center;
      padding-block-start: 0;
    }
  }

  .app-text-field {
    .v-field__input {
      padding-block-start: 6px;
    }

    .v-field--focused {
      box-shadow: none !important;
    }
  }
}

.email-compose-dialog {
  .ProseMirror {
    p {
      margin-block-end: 0;
    }

    padding: 1.5rem;
    block-size: 100px;
    overflow-y: auto;
    padding-block: 0.5rem;

    &:focus-visible {
      outline: none;
    }

    p.is-editor-empty:first-child::before {
      block-size: 0;
      color: #adb5bd;
      content: attr(data-placeholder);
      float: inline-start;
      pointer-events: none;
    }

    ul,
    ol {
      padding-inline: 1.125rem;
    }

    &-focused {
      outline: none;
    }
  }
}

.scrollable-editor {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #ccc;
  padding: 8px;
  border-radius: 6px;
  background-color: #fff;
}
</style>
