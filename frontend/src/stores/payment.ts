import { defineStore } from 'pinia';
import { useStorage } from '@vueuse/core';
import type { Payment } from '@services/models';
import { getDefaultPayment } from '@services/defaults';

export const usePaymentStore = defineStore('paymentStore', {
  state: () => ({
    payments: [] as Payment[],
    selectedPayment: useStorage<Payment | null>('selectedPayment', null, localStorage, {
      serializer: {
        read: (value) => (value ? JSON.parse(value) : null),
        write: JSON.stringify,
      },
    }),

    mode: 'add',
    type: 'null',
  }),

  actions: {
    setAddMode() {
      this.mode = 'add';
      this.selectedPayment = getDefaultPayment();
    },

    setEditMode(payment: Payment) {
      this.mode = 'edit';
      this.selectedPayment = payment;
    },

     setRecoveryType() {
      this.type = 'recovery';
    },
    setPaymentType() {
      this.type = 'payment';
    },

    closeDrawer() {
      this.selectedPayment = null;
      this.mode = 'add';
    },
  },
});
