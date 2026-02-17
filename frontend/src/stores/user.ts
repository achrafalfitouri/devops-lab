import { defineStore } from 'pinia';
import { useStorage } from '@vueuse/core';
import type { User } from '@services/models';

export const useUserStore = defineStore('userStore', {
  state: () => ({
    users: [] as User[],
    selectedUser: useStorage<User | null>('selectedUser', null, localStorage, {
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
      this.selectedUser = null;
    },

    setEditMode(user: User) {
      this.mode = 'edit';
      this.selectedUser = user;
    },

    closeDrawer() {
      this.selectedUser = null;
      this.mode = 'add';
    },
  },
});
