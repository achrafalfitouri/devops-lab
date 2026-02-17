<script setup lang="ts">
import { addTransaction, fetchStaticData, updateTransaction, } from '@/services/api/transaction';
import { useAuthStore } from '@/stores/auth';
import { getDefaultTransaction } from '@services/defaults';
import type { Transaction } from '@services/models';
import { useTransactionStore } from '@stores/transaction';
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue';
import { VForm } from 'vuetify/components/VForm';

const store = useTransactionStore();
const emit = defineEmits(['close', 'submit', 'date-picker-state']);



// 👉 Loading state
const loading = ref(false);

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

const refForm = ref<VForm | null>(null);
const isFormValid = ref(false);
const TransactionFormDefaults = ref<Transaction>(store.selectedTransaction || getDefaultTransaction());
const cashRegisterBalance = ref<number | null>(null);

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
  balance?: number;

}

// 👉 State variables for the filter options (initialized as empty arrays)
const filterItems = ref<{
  types: FilterItem[];
  cashregisters: FilterItem[];
  cashregistersTarget: FilterItem[];
  clients: FilterItem[];
  users: FilterItem[];
  refundCodes: FilterItem[];
}>({
  types: [],
  cashregisters: [],
  cashregistersTarget: [],
  clients: [],
  users: [],
  refundCodes: [],

});

const errorMessage = ref('');

const isTargetCashRegister = ref(false);
const isCashRegister = ref(false);
const isDatePickerOpen = ref(false);
const cashRegisterId = ref<string | null>(null);
const cashRegister = ref<string | null>(null);
const targetCashRegisterId = ref<string | null>(null);
const targetCashRegister = ref<string | null>(null);


const hasRequiredPermissions = (permissions: string[]): boolean => {
  const authStore = useAuthStore();
  return permissions.every((permission) => authStore.permissions.includes(permission));
};

const requiredPermissionsCr = ['cashregister_manager'];

const hasCashRegisterManagerPermission = hasRequiredPermissions(requiredPermissionsCr);


const requiredValidator = (v: any) => (v !== null && v !== undefined) || 'Ce champ est obligatoire';

const formTitle = computed(() => 'Enregistrer');

const closeDrawer = () => {
  store.closeDrawer();
  refForm.value?.reset();
  refForm.value?.resetValidation();
  emit('close');
};

const handleFormSubmit = () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loading.value = true;
      try {
        const payload = {
          ...TransactionFormDefaults.value,
          refund_note_id: store.mode === 'add' ? TransactionFormDefaults.value.refundNoteId : store.selectedTransaction?.refundNoteId,
          target_cash_register_id: TransactionFormDefaults.value.targetCashRegisterId,
          target_user_id: TransactionFormDefaults.value.targetUser,
          // cash_register_id: cashRegisterId.value,
          client_id: store.mode === 'add' ? TransactionFormDefaults.value.client : store.selectedTransaction?.clientId,
          cash_transaction_type_id: store.mode === 'add' ? TransactionFormDefaults.value.cashTransactionType : store.selectedTransaction?.cashTransactionTypeId,

        };

        if (store.mode === 'add') {
          const response = await addTransaction(payload);
          const transactionId = response.id;
          store.transactions.push({ ...payload, id: transactionId });
        } else if (store.mode === 'edit') {
          const transactionId = payload.id;
          await updateTransaction(transactionId, payload);

          const index = store.transactions.findIndex((c) => c.id === transactionId);
          if (index !== -1) {
            store.transactions[index] = { ...payload, id: transactionId };
          }
        }
        closeDrawer();
        emit('submit');
      } catch (error) {
        console.error('Error preparing user data:', error);
        closeDrawer();
        showSnackbar('le transaction', 'error');
        const err = error as any;
        showSnackbar(`${err.response?.data.message}`, 'error');

      } finally {
        loading.value = false;
      }
    }
  });
};

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();

    filterItems.value.types = mapStaticData(staticData.data.transactionType);
    filterItems.value.cashregisters = staticData.data.cashRegisterFilter.map((item: any) => ({
      text: item.name,
      value: item.id,
      balance: item.balance,
    }));

    filterItems.value.cashregistersTarget = mapStaticData(staticData.data.cashRegister);
    filterItems.value.clients = mapStaticData(staticData.data.client);
    filterItems.value.users = mapStaticData(staticData.data.user);
    filterItems.value.refundCodes = mapStaticData(staticData.data.refund);

  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

watch(
  [() => TransactionFormDefaults.value.cashRegisterId, () => TransactionFormDefaults.value.balanceReset],
  ([newCashRegisterId, resetFlag]) => {

    if (newCashRegisterId) {
      const selectedCashRegister = filterItems.value.cashregisters.find(
        (item) => item.value === newCashRegisterId
      );
      cashRegisterBalance.value = selectedCashRegister?.balance || null;

      if (resetFlag) {

        TransactionFormDefaults.value.amount = cashRegisterBalance.value;
        TransactionFormDefaults.value.cashTransactionTypeId = filterItems.value.types.find(
          (type) => type.text === 'out'
        )?.value || null;
      } else {

        cashRegisterBalance.value = null;
        TransactionFormDefaults.value.amount = null;

      }
    } else {

      cashRegisterBalance.value = null;
      TransactionFormDefaults.value.amount = null;

    }
  }
);


// 👉 Store form logic and call loadStaticData on component mount  
watch(() => store.selectedTransaction, (newTransaction) => {
  if (store.mode === 'edit' && newTransaction) {
    TransactionFormDefaults.value = { ...newTransaction };
  }
}, { immediate: true });

onMounted(async () => {
  if (store.mode === 'add') {
    TransactionFormDefaults.value = getDefaultTransaction();
    if (!TransactionFormDefaults.value.date) {
      TransactionFormDefaults.value.date = new Date();
    }
  } else if (store.mode === 'edit' && store.selectedTransaction) {
    TransactionFormDefaults.value = { ...store.selectedTransaction };
    if (store.selectedTransaction.date) {
      TransactionFormDefaults.value.date = dayjs(store.selectedTransaction.date, ['DD/MM/YYYY', 'YYYY-MM-DD', 'MM/DD/YYYY']).toDate();
    }
  }

  loadStaticData();
});


const selectedCheckbox = ref<'client' | 'user' | 'cashregister' | 'seller' | 'bank'>('client');

// Watch selectedCheckbox to reset the input fields
watch(selectedCheckbox, (newValue) => {
  if (newValue === 'client') {
    TransactionFormDefaults.value.name = '';
  } else if (newValue === 'user') {
    TransactionFormDefaults.value.clientId = null;
  } else if (newValue === 'cashregister') { 
    TransactionFormDefaults.value.targetCashRegisterId = null;
    TransactionFormDefaults.value.targetCashRegister = null;
  } 
  else if (newValue === 'bank') {
    TransactionFormDefaults.value.bank = '';
  }
  else if (newValue === 'seller') {
    TransactionFormDefaults.value.seller = '';
  }
});


// 👉 Utility function to resolve text from UUID
const resolveTransactionTypeText = (uuid: any): string | null => {
  const foundItem = filterItems.value.types.find((item) => item.value === uuid);

  const textValue = foundItem ? foundItem.text : null;

  if (textValue) {
    const reversedMapping: { [key: string]: string } = {
      'Sortie': 'sortie',
      'Entrée': 'entree',
    };

    return reversedMapping[textValue] || null;
  }

  return null;
};

const FlatPickrParent = ref(null)
const pickerOptions = ref({
  appendTo: FlatPickrParent.value ? (FlatPickrParent.value as HTMLElement) : undefined,
})
const isParentRefSet = ref(false)
watch(FlatPickrParent, (newValue, oldValue) => {
  if (newValue) {
    pickerOptions.value = {
      appendTo: newValue as HTMLElement,
    }
    isParentRefSet.value = true
  }
});



const handleTargetCashRegister = (value: any) => {

  TransactionFormDefaults.value.targetCashRegisterId = value?.value ?? null;
  TransactionFormDefaults.value.targetCashRegister = value?.text ?? null;


  if (value) {
    isTargetCashRegister.value = true;
    errorMessage.value = `veuillez vous assurer que la transaction est ajoutée à la caisse ${value.text}.`;
  } else {
    TransactionFormDefaults.value.cashRegisterId = null;
    isTargetCashRegister.value = false;
    errorMessage.value = '';

  }
};

const handleCashRegister = (value: any) => {
  TransactionFormDefaults.value.cashRegister = value?.text ?? null;
  TransactionFormDefaults.value.cashRegisterId = value?.value ?? null;

  if (value) {
    isCashRegister.value = true;
  } else {
    TransactionFormDefaults.value.targetCashRegisterId = null;
    isCashRegister.value = false;
  }
};


const normalizedCashRegisterId = computed(() => {
  const id = TransactionFormDefaults.value.cashRegisterId;

  if (typeof id === 'string') {
    return { value: id };
  }

  return id;
});

const normalizedTargetCashRegisterId = computed(() => {
  const id = TransactionFormDefaults.value.targetCashRegisterId;

  if (typeof id === 'string') {
    return { value: id };
  }

  return id;
});

const availableCashRegisters = computed(() => {
  if (!normalizedTargetCashRegisterId.value) return filterItems.value.cashregisters
  return filterItems.value.cashregisters.filter(
    item => item.value !== normalizedTargetCashRegisterId.value?.value
  )
});

const availableTargetCashRegisters = computed(() => {
  if (!normalizedCashRegisterId.value) return filterItems.value.cashregistersTarget
  return filterItems.value.cashregistersTarget.filter(
    item => item.value !== normalizedCashRegisterId.value?.value
  )
});

const setSelectedCheckboxBasedOnTransaction = () => {
  const transaction = TransactionFormDefaults.value

  if (transaction.targetCashRegisterId) {
    selectedCheckbox.value = 'cashregister'
  } else if (transaction.targetUserId) {
    selectedCheckbox.value = 'user'
  } else if (transaction.clientId) {
    selectedCheckbox.value = 'client'
  }
  else if (transaction.bank) {
    selectedCheckbox.value = 'bank'
  }
  else if (transaction.seller) {
    selectedCheckbox.value = 'seller'
  } 
}

watch(() => store.selectedTransaction, (newTransaction) => {
  if (store.mode === 'edit' && newTransaction) {
    TransactionFormDefaults.value = { ...newTransaction }
    setSelectedCheckboxBasedOnTransaction()
  }
}, { immediate: true })

const handleTransactionType = (value: any) => {
  TransactionFormDefaults.value.cashTransactionTypeId = value;

};

const handleDatePickerState = (isOpen: boolean) => {
  isDatePickerOpen.value = isOpen;
  emit('date-picker-state', isOpen);
};
</script>

<template>
  <VForm class="mt-6" ref="refForm" v-model="isFormValid" @submit.prevent="handleFormSubmit">
    <v-alert v-if="selectedCheckbox === 'cashregister' && isTargetCashRegister && isCashRegister" type="warning"
      class="mt-2">{{ errorMessage }}</v-alert>

    <VRow class="mt-6">

      <VCol cols="12" md="6">
        <vAutocomplete v-model="TransactionFormDefaults.cashRegister" label="Caisse *" placeholder="Caisse"
          :rules="store.mode === 'add' ? [requiredValidator] : []" :items="availableCashRegisters" item-title="text"
          clearable clear-icon="tabler-x" variant="outlined" return-object
          @update:modelValue="handleCashRegister($event)" />
      </VCol>
      <!-- Type -->
      <VCol cols="12" md="6">
        <vAutocomplete v-model="TransactionFormDefaults.cashTransactionType" label="Type de transaction *"
          placeholder="Type de transaction" :rules="store.mode === 'add' ? [requiredValidator] : []"
          :items="filterItems.types" item-title="text" item-value="value" clearable clear-icon="tabler-x"
          variant="outlined" :disabled="!!TransactionFormDefaults.balanceReset"
          @update:modelValue="handleTransactionType($event)" />
      </VCol>
      <!-- Balance Reset Switch -->
      <!-- <VCol cols="12" md="6">
        <VSwitch v-model="TransactionFormDefaults.balanceReset" :disabled="!!TransactionFormDefaults.cashRegisterId"
          label="Réinitialiser le solde" @change="(value: any) => console.log('Switch toggled:', value)" />
      </VCol> -->

      <!-- Bénéficiaire -->
      <VCol cols="12" md="6">
        <vAutocomplete v-model="selectedCheckbox"
          :placeholder="resolveTransactionTypeText(TransactionFormDefaults.cashTransactionTypeId) === 'sortie' ? 'Type de Récepteur' : ' Type de Emetteur'"
          :items="[{ title: 'Client', value: 'client' },
          { title: 'Utilisateur', value: 'user' }, { title: 'Caisse', value: 'cashregister' },
          { title: 'Fournisseur', value: 'seller' }, { title: 'Banque', value: 'bank' }]"
          :label="resolveTransactionTypeText(TransactionFormDefaults.cashTransactionTypeId) === 'sortie' ? 'Type de Récepteur' : ' Type de Emetteur'"
          clearable clear-icon="tabler-x" />
      </VCol>

      <VCol v-if="selectedCheckbox === 'client'" cols="12" md="6">
        <VAutocomplete v-model="TransactionFormDefaults.client"
          :label="resolveTransactionTypeText(TransactionFormDefaults.cashTransactionTypeId) === 'sortie' ? 'Récepteur *' : 'Emetteur *'"
          :placeholder="resolveTransactionTypeText(TransactionFormDefaults.cashTransactionTypeId) === 'sortie' ? 'Récepteur' : 'Emetteur'"
          :items="filterItems.clients" item-title="text" item-value="value" clearable clear-icon="tabler-x"
          variant="outlined"
          :rules="store.mode === 'add' && selectedCheckbox === 'client' ? [requiredValidator] : []" />
      </VCol>
      <VCol v-if="selectedCheckbox === 'user'" cols="12" md="6">
        <VAutocomplete v-model="TransactionFormDefaults.targetUser"
          :label="resolveTransactionTypeText(TransactionFormDefaults.cashTransactionTypeId) === 'sortie' ? 'Récepteur *' : 'Emetteur *'"
          :placeholder="resolveTransactionTypeText(TransactionFormDefaults.cashTransactionTypeId) === 'sortie' ? 'Récepteur' : 'Emetteur'"
          :items="filterItems.users" item-title="text" item-value="value" clearable clear-icon="tabler-x"
          variant="outlined" :rules="store.mode === 'add' && selectedCheckbox === 'user' ? [requiredValidator] : []" />
      </VCol>
      <VCol v-if="selectedCheckbox === 'cashregister'" cols="12" md="6">
        <VAutocomplete v-model="TransactionFormDefaults.targetCashRegister"
          :label="resolveTransactionTypeText(TransactionFormDefaults.cashTransactionTypeId) === 'sortie' ? 'Récepteur *' : 'Emetteur *'"
          :placeholder="resolveTransactionTypeText(TransactionFormDefaults.cashTransactionTypeId) === 'sortie' ? 'Récepteur' : 'Emetteur'"
          :items="availableTargetCashRegisters" item-title="text" item-value="value" clearable clear-icon="tabler-x"
          variant="outlined" return-object @update:modelValue="handleTargetCashRegister($event)"
          :rules="store.mode === 'add' && selectedCheckbox === 'cashregister' ? [requiredValidator] : []" />
      </VCol>
      <VCol v-if="selectedCheckbox === 'seller'" cols="12" md="6">
        <VTextField v-model="TransactionFormDefaults.seller" label="Fournisseur *" placeholder="Fournisseur"
          :rules="store.mode === 'add' && selectedCheckbox === 'seller' ? [requiredValidator] : []" />
      </VCol>

       <VCol v-if="selectedCheckbox === 'bank'" cols="12" md="6">
        <VTextField v-model="TransactionFormDefaults.bank" label="Banque *" placeholder="Banque"
          :rules="store.mode === 'add' && selectedCheckbox === 'bank' ? [requiredValidator] : []" />
      </VCol>

      <!-- Last Name -->
      <VCol cols="12" md="6">
        <VTextField v-model="TransactionFormDefaults.amount" :rules="store.mode === 'add' ? [requiredValidator] : []"
          label="Montant *" placeholder="Montant" type='number' :disabled="!!TransactionFormDefaults.balanceReset" />
      </VCol>
      <!-- Date -->
      <VCol cols="12" md="6">
        <!-- <VTextField v-model="TransactionFormDefaults.date" :rules="store.mode === 'add' ? [requiredValidator] : []"
          :disabled="!hasCashRegisterManagerPermission" type="date" label="Date" placeholder="date" /> -->
        <div></div>

        <div ref="FlatPickrParent" class="flat-pickr-parent"></div>
        <AppDateTimePicker v-if="isParentRefSet" :config="{
          position: 'above',
          dateFormat: 'd/m/Y',
          

          ...pickerOptions

        }" v-model="TransactionFormDefaults.date" placeholder="Date" :disabled="!hasCashRegisterManagerPermission"
          :FlatPickrParent="FlatPickrParent"
           @picker-open="handleDatePickerState(true)"
      @picker-close="handleDatePickerState(false)"
          />
      </VCol>

      <!-- Commentaire -->
      <VCol cols="12" md="6">
        <VTextField v-model="TransactionFormDefaults.comment" label="Commentaire" placeholder="Commentaire" />
      </VCol>

      <!-- Refund codes-->
      <VCol cols="12" md="6">
        <vAutocomplete
          v-if="resolveTransactionTypeText(TransactionFormDefaults.cashTransactionTypeId) === 'sortie' && selectedCheckbox === 'client'"
          v-model="TransactionFormDefaults.refundNoteId" label="Bon de remboursement *"
          placeholder="Bon de remboursement" :rules="store.mode === 'add' ? [requiredValidator] : []"
          :items="filterItems.refundCodes" item-title="text" item-value="value" clearable clear-icon="tabler-x"
          variant="outlined" />
      </VCol>
      <!-- Action buttons -->
      <VCol cols="12" class="d-flex flex-wrap justify-center gap-4 mt-4">
        <VBtn :disabled="loading" @click="closeDrawer" type="reset" variant="tonal" color="error">Annuler</VBtn>
        <VBtn :loading="loading" class="me-3" type="submit">{{ formTitle }}</VBtn>
      </VCol>
    </VRow>

  </VForm>
</template>
<style scoped lang="scss">
.rounded-circle {
  /* stylelint-disable-next-line liberty/use-logical-spec */
  top: 0;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  left: 0;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  width: 100%;
  /* stylelint-disable-next-line liberty/use-logical-spec */
  height: 100%;
  background-color: white;
  /* stylelint-disable-next-line order/properties-order */
  border: 2px dashed #ddd;
  border-radius: 50%;
}
</style>
<style lang="scss">
.flat-pickr-parent {
  position: relative;

  .flatpickr-calendar {
    top: -326px !important;
    left: 0 !important;
  }
}
</style>
