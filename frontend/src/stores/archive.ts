import { defineStore } from 'pinia';
import { useStorage } from '@vueuse/core';


export const useArchiveStoreStore = defineStore('ArchiveStore', {
  state: () => ({
    isArchive: useStorage('isArchive', false).value,
    
  }),

  actions: {
    setArchiveMode(value: boolean) {
      this.isArchive = value;  
      localStorage.setItem('isArchive', JSON.stringify(this.isArchive)); 
      },
  },
});
