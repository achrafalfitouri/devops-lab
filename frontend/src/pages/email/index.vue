<script setup lang="ts">
import { fetchEmails } from '@/services/api/email'
import { getDefaultEmailFilterParams } from '@/services/defaults'
import ComposeDialog from '@/views/email/ComposeDialog.vue'
import EmailLeftSidebarContent from '@/views/email/EmailLeftSidebarContent.vue'
import EmailView from '@/views/email/EmailView.vue'
import { Email, EmailFilterParms } from '@services/models'
import { debounce } from 'lodash'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'


definePage({
  meta: {
    layoutWrapperClasses: 'layout-content-height-fixed',
  },
})

const { isLeftSidebarOpen } = useResponsiveLeftSidebar()

// Compose dialog
const isComposeDialogVisible = ref(false)

const emails = ref<Email[]>([])
const emailsMeta = computed(() => ({
  total: emails.value.length,
  unread: emails.value.filter(email => !email.isRead).length,
  inbox: emails.value.filter(email => email.folder === 'inbox').length,
  draft: emails.value.filter(email => email.folder === 'draft').length,
  spam: emails.value.filter(email => email.folder === 'spam').length,
  star: emails.value.filter(email => email.isStarred).length,
}))


// Email View
const openedEmail = ref<Email | null>(null)

const emailViewMeta = computed(() => {
  const returnValue = {
    hasNextEmail: false,
    hasPreviousEmail: false,
  }

  if (openedEmail.value) {
    const openedEmailIndex = emailsData.value.emails.findIndex(
      e => e.id === openedEmail.value?.id,
    )

    returnValue.hasNextEmail = !!emailsData.value.emails[openedEmailIndex + 1]
    returnValue.hasPreviousEmail = !!emailsData.value.emails[openedEmailIndex - 1]
  }

  return returnValue
})

// Email view
const changeOpenedEmail = (dir: 'previous' | 'next') => {
  if (!openedEmail.value)
    return

  const openedEmailIndex = emailsData.value.emails.findIndex(
    e => e.id === openedEmail.value?.id,
  )

  const newEmailIndex = dir === 'previous' ? openedEmailIndex - 1 : openedEmailIndex + 1

  openedEmail.value = emailsData.value.emails[newEmailIndex]
}

const openEmail = (email: Email) => {
  openedEmail.value = email
  email.isRead = true 
}

const fetchedEmailData = ref<any>(null)

const handleFetchEmailData = (data: any) => {
  fetchedEmailData.value = data
}

// 👉 Filters and search
const filterParams = ref<EmailFilterParms>(getDefaultEmailFilterParams())
// 👉 Data table options
const queryParams = ref({
  itemsPerPage: 10,
  page: 1,
})

// 👉 State variables for form validation and loading status
const isLoading = ref(true)


// 👉 State variable for storing payments data
const emailsData = ref<{
  emails: Email[]
  totalEmails: number
}>({
  emails: [],
  totalEmails: 0,
})

// 👉 Function to fetch emails from the API
const loadEmails = async () => {
  isLoading.value = true;
  try {
    const filters: any = {};

    if (filterParams.value.searchQuery) {
      filters.search = filterParams.value.searchQuery;

    }
    const data = await fetchEmails(filters, queryParams.value.itemsPerPage, queryParams.value.page);
    emailsData.value.emails = data.emails || [];
    emailsData.value.totalEmails = data.totalEmails || 0;


  } catch (error) {
    console.error('Error fetching clients:', error);
  } finally {
    isLoading.value = false;
  }
};

// 👉 Call loadStaticData on component mount
onMounted(() => {
  loadEmails()
});

// Replace throttledLoadEmails with debouncedLoadEmails
const debouncedLoadEmails = debounce(async () => {
  await loadEmails();
}, 800);

// 👉 Watchers to reload emails on filter, search, pagination, or sorting change
watch(filterParams, () => {
  debouncedLoadEmails();
}, { deep: true });

</script>

<template>
  <VLayout style="min-block-size: 100%;" class="email-app-layout">
    <VNavigationDrawer v-model="isLeftSidebarOpen" absolute touchless location="start"
      :temporary="$vuetify.display.mdAndDown">
      <EmailLeftSidebarContent :emails-meta="emailsMeta"
        @toggle-compose-dialog-visibility="isComposeDialogVisible = !isComposeDialogVisible"
        @fetch-email-data="handleFetchEmailData" />
    </VNavigationDrawer>
    <EmailView :email="openedEmail" :email-meta="emailViewMeta" @navigated="changeOpenedEmail"
      @close="openedEmail = null" />
    <VMain>
      <VCard flat class="email-content-list h-100 d-flex flex-column">
        <div class="d-flex align-center">
          <IconBtn class="d-lg-none ms-3" @click="isLeftSidebarOpen = true">
            <VIcon icon="tabler-menu-2" />
          </IconBtn>

          <!-- 👉 Search -->
          <VTextField v-model="filterParams.searchQuery" density="default" class="email-search px-sm-2 flex-grow-1 py-1"
            placeholder="Rechercher le courrier">
            <template #prepend-inner>
              <VIcon icon="tabler-search" size="24" class="me-1 text-medium-emphasis" />
            </template>
          </VTextField>
        </div>
        <VDivider />
        <!-- 👉 Action bar -->

        <VDivider />
        <!-- 👉 Emails list -->
        <PerfectScrollbar tag="ul" :options="{ wheelPropagation: false }" class="email-list">
          <!-- Loading Indicator -->
          <div v-if="isLoading" class="d-flex justify-center align-center flex-grow-1 loading-container"
            style="min-height: 450px;">
            <VProgressCircular indeterminate color="primary" size="40" />
          </div>

          <!-- Email Items -->
          <template v-else>
            <li v-for="email in emailsData.emails" :key="email.id"
              class="email-item d-flex align-center pa-4 gap-2 cursor-pointer" :class="{ 'email-read': email.isRead }"
              @click="openEmail(email)">
              <VAvatar size="32">
                <VImg :src="'default-avatar.png'"
                  :alt="email.client?.legalName || email.contact?.fullName || 'Unknown Sender'" />
              </VAvatar>
              <h6 class="text-h6">
                {{ email.client?.legalName || email.contact?.fullName || 'Unknown Sender' }}
              </h6>

              <span class="text-body-2 truncate">{{ email.subject }}</span>

              <VSpacer />
            </li>

            <!-- No emails found -->
            <div v-if="emailsData.emails.length === 0" class="d-flex justify-center align-center flex-grow-1"
              style="min-height: 450px;">
              <span>Aucun e-mail trouvé</span>
            </div>
          </template>
        </PerfectScrollbar>

      </VCard>
      <ComposeDialog v-show="isComposeDialogVisible" @close="isComposeDialogVisible = false"
        :emailData="fetchedEmailData" @fetchEmails=loadEmails />
    </VMain>
  </VLayout>
</template>

<style lang="scss">
@use "@styles/variables/vuetify";
@use "@core/scss/base/mixins";

// ℹ️ Remove border. Using variant plain cause UI issue, caret isn't align in center
.email-search {
  .v-field__outline {
    display: none;
  }

  .v-field__field {
    .v-field__input {
      font-size: 0.9375rem !important;
      line-height: 1.375rem !important;
    }
  }
}

.email-app-layout {
  border-radius: vuetify.$card-border-radius;

  @include mixins.elevation(vuetify.$card-elevation);

  $sel-email-app-layout: &;

  @at-root {
    .skin--bordered {
      @include mixins.bordered-skin($sel-email-app-layout);
    }
  }
}

.email-content-list {
  border-end-start-radius: 0;
  border-start-start-radius: 0;
}

.email-list {
  white-space: nowrap;

  .email-item {
    block-size: 4.375rem;
    transition: all 0.2s ease-in-out;
    will-change: transform, box-shadow;

    &.email-read {
      background-color: rgba(var(--v-theme-on-surface), var(--v-hover-opacity));
    }

    & + .email-item {
      border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    }
  }

  .email-item .email-meta {
    display: flex;
  }

  .email-item:hover {
    transform: translateY(-2px);

    @include mixins.elevation(4);

    // ℹ️ Don't show actions on hover on mobile & tablet devices
    @media screen and (min-width: 1280px) {
      .email-actions {
        display: block !important;
      }

      .email-meta {
        display: none;
      }
    }

    +.email-item {
      border-color: transparent;
    }

    @media screen and (max-width: 600px) {
      .email-actions {
        display: none !important;
      }
    }
  }
}

.email-compose-dialog {
  position: absolute;
  inset-block-end: 0;
  inset-inline-end: 0;
  min-inline-size: 100%;

  @media only screen and (min-width: 800px) {
    min-inline-size: 712px;
  }
}
</style>
