import { defineStore } from 'pinia';
import { useStorage } from '@vueuse/core';
import type { Logs } from '@services/models';

export const useClientLogsStore = defineStore('clientlogsStore', {
  state: () => ({
    clientlogss: [] as Logs[],
    selectedClientLogs: useStorage<Logs | null>('selectedClientLogs', null, localStorage, {
      serializer: {
        read: (value) => (value ? JSON.parse(value) : null),
        write: JSON.stringify,
      },
    }),

    mode: 'add',
  }),

  actions: {
    setAddMode() {
      this.mode = 'add';
      this.selectedClientLogs = null;
    },

    setEditMode(clientlogs: Logs) {
      this.mode = 'edit';
      this.selectedClientLogs = clientlogs;
    },

    closeDrawer() {
      this.selectedClientLogs = null;
      this.mode = 'add';
    },
  },
});
