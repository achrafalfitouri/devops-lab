import { defineStore } from 'pinia';
import { useStorage } from '@vueuse/core';
import type { Logs } from '@services/models';

export const useUserLogsStore = defineStore('userlogsStore', {
  state: () => ({
    userlogss: [] as Logs[],
    selectedUserLogs: useStorage<Logs | null>('selectedUserLogs', null, localStorage, {
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
      this.selectedUserLogs = null;
    },

    setEditMode(userlogs: Logs) {
      this.mode = 'edit';
      this.selectedUserLogs = userlogs;
    },

    closeDrawer() {
      this.selectedUserLogs = null;
      this.mode = 'add';
    },
  },
});
