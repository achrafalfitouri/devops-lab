import { defineStore } from 'pinia';
import type { Transaction } from '@services/models';

export const useTransactionStore = defineStore('transactionStore', {
  state: () => ({
    transactions: [] as Transaction[],
    selectedTransaction: null as Transaction | null,
    mode: 'add', 
  }),

  actions: {
    setAddMode() {
        this.mode = 'add';
        this.selectedTransaction= null;
    },

    setEditMode(transaction: Transaction) {
        this.mode = 'edit';
        this.selectedTransaction= transaction;
    },

    closeDrawer() {
        this.selectedTransaction= null;
        this.mode= 'add';
    },
  },
});
