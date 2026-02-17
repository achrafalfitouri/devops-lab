import { defineStore } from 'pinia';
import type { Client } from '@services/models';

export const useClientStore = defineStore('clientStore', {
  state: () => ({
    clients: [] as Client[],
    selectedClient: null as Client | null,
    mode: 'add', 
  }),

  actions: {
    setAddMode() {
        this.mode = 'add';
        this.selectedClient= null;
    },

    setEditMode(client: Client) {
        this.mode = 'edit';
        this.selectedClient= client;
    },

    closeDrawer() {
        this.selectedClient= null;
        this.mode= 'add';
    },
  },
});
