import { defineStore } from 'pinia';
import type { Contacts } from '@services/models';

export const useContactsStore = defineStore('contactsStore', {
  state: () => ({
    contacts: [] as Contacts[],
    selectedContacts: null as Contacts | null,
    mode: 'add', 
  }),

  actions: {
    setAddMode() {
        this.mode = 'add';
        this.selectedContacts= null;
    },

    setEditMode(contacts: Contacts) {
        this.mode = 'edit';
        this.selectedContacts= contacts;
    },

    closeDrawer() {
        this.selectedContacts= null;
        this.mode= 'add';
    },
  },
});
