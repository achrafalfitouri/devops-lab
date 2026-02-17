<script setup lang="ts">
import { Email } from '@services/models';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';

interface Props {
  email: Email | null
  emailMeta: {
    hasPreviousEmail: boolean
    hasNextEmail: boolean
  }
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'navigated', direction: 'previous' | 'next'): void
  (e: 'close'): void
  (e: 'trash'): void
  (e: 'unread'): void
  (e: 'read'): void
  (e: 'star'): void
  (e: 'unstar'): void
}>()

</script>

<template>
  <!-- ℹ️ calc(100% - 256px) => 265px is left sidebar width -->
  <VNavigationDrawer temporary :model-value="!!props.email" location="right" :scrim="false" floating class="email-view">
    <template v-if="props.email">
      <!-- 👉 header -->

      <div class="email-view-header d-flex align-center px-5 py-3">
        <IconBtn class="me-2" @click="$emit('close')">
          <VIcon size="22" icon="tabler-chevron-left" class="flip-in-rtl" />
        </IconBtn>

        <div class="d-flex align-center flex-wrap flex-grow-1 overflow-hidden gap-2">
          <div class="text-body-1 text-high-emphasis text-truncate">
            {{ props.email.subject }}
          </div>

          <div class="d-flex flex-wrap gap-2">
          </div>
        </div>

        <div>
          <div class="d-flex align-center">
            <IconBtn :disabled="!props.emailMeta.hasPreviousEmail" @click="$emit('navigated', 'previous')">
              <VIcon icon="tabler-chevron-left" class="flip-in-rtl" />
            </IconBtn>

            <IconBtn :disabled="!props.emailMeta.hasNextEmail" @click="$emit('navigated', 'next')">
              <VIcon icon="tabler-chevron-right" class="flip-in-rtl" />
            </IconBtn>
          </div>
        </div>
      </div>

      <VDivider />


      <VDivider />

      <!-- 👉 Mail Content -->
      <PerfectScrollbar tag="div" class="mail-content-container flex-grow-1 pa-sm-12 pa-6"
        :options="{ wheelPropagation: false }">
        <VCard class="mb-4">
          <div class="d-flex align-start align-sm-center pa-6 gap-x-4">
            <VAvatar size="38">
              <VImg :src="'default-avatar.png'"
                :alt="props.email.client?.legalName || props.email.contact?.fullName || 'Unknown Sender'" />
            </VAvatar>

            <div class="d-flex flex-wrap flex-grow-1 overflow-hidden">
              <div class="text-truncate">
                <div class="text-body-1 text-high-emphasis text-truncate">
                  {{ props.email.contact !== null ? props.email.contact.fullName : 'Unknown Sender' }}
                </div>
                <div class="text-sm">
                  {{ props.email.contact !== null ? props.email.contact?.email : 'Unknown Sender' }}
                </div>
              </div>

              <VSpacer />


            </div>
          </div>

          <VDivider />

          <VCardText>
            <!-- eslint-disable vue/no-v-html -->
            <div class="text-base" v-html="props.email.html" />
            <!-- eslint-enable -->
          </VCardText>

        </VCard>
      </PerfectScrollbar>
    </template>
  </VNavigationDrawer>
</template>

<style lang="scss">
.email-view {
  inline-size: 100% !important;

  @media only screen and (min-width: 1280px) {
    inline-size: calc(100% - 256px) !important;
  }

  .v-navigation-drawer__content {
    display: flex;
    flex-direction: column;
  }

  .editor {
    padding-block-start: 0 !important;
    padding-inline: 0 !important;
  }

  .ProseMirror {
    padding: 0.5rem;
    block-size: 100px;
    overflow-y: auto;
    padding-block: 0.5rem;
  }
}

.email-view-action-bar {
  min-block-size: 56px;
}

.mail-content-container {
  background-color: rgb(var(--v-theme-on-surface), var(--v-hover-opacity));

  .mail-header {
    margin-block: 12px;
    margin-inline: 24px;
  }
}
</style>
