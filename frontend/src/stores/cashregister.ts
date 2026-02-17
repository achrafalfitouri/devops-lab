import { defineStore } from 'pinia';
import { useStorage } from '@vueuse/core';
import type { CashRegister } from '@services/models';

export const useCashRegisterStore = defineStore('cashRegisterStore', {
  state: () => ({
    cashregisters: [] as CashRegister[],
    selectedCashRegister: useStorage<CashRegister | null>('selectedCashregister', null, localStorage, {
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
        this.selectedCashRegister= null;
    },

    setEditMode(cashRegister: CashRegister) {
        this.mode = 'edit';
        this.selectedCashRegister= cashRegister;
    },

    closeDrawer() {
        this.selectedCashRegister= null;
        this.mode= 'add';
    },
  },
});
