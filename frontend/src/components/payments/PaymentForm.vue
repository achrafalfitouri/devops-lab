<script setup lang="ts">
import { addPayment, addRecovery, fetchRecoveriesStaticData, fetchStaticData, updatePayment, updateRecovery } from '@/services/api/payment';
import { useDocumentCoreStore } from '@/stores/documents';
import { getDefaultPayment } from '@services/defaults';
import type { Payment } from '@services/models';
import { usePaymentStore } from '@stores/payment';
import { computed, ref, watch } from 'vue';
import { VForm } from 'vuetify/components/VForm';

const store = usePaymentStore();
const documentsStore = useDocumentCoreStore();
const emit = defineEmits(['close', 'submit', 'update:title2', 'date-picker-state', 'update:recoveryBalance']);

// 👉 Loading state
const loading = ref(false);

const refForm = ref<VForm | null>(null);
const isFormValid = ref(false);
const isDatePickerOpen = ref(false);
const PaymentFormDefaults = ref<Payment>(store.selectedPayment || getDefaultPayment());
const recoveryBalance = ref<number | null>(null);

// 👉 State variables for the filter options (initialized as empty arrays)
interface FilterItem {
  text: string;
  value: string;
}

// 👉 State variables for the filter options (initialized as empty arrays)
const filterItems = ref<{
  paymentTypes: FilterItem[];
  clients: FilterItem[];
  recoveryTypes: FilterItem[];
  recoveries: FilterItem[];

}>({
  paymentTypes: []
  , clients: []
  , recoveryTypes: []
  , recoveries: []
});

// 👉 Snackbar
const { showSnackbar } = useSnackbar();

const requiredValidator = (v: any) => (v !== null && v !== undefined) || 'Ce champ est obligatoire';

const formTitle = computed(() => 'Enregistrer');

interface Props {
  clientBalanceProps?: number | null
}

const props = defineProps<Props>()

// 👉 Options for search filters
const closeDrawer = () => {
  store.closeDrawer();
  emit('close');
};

const handleFormSubmit = () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loading.value = true;
      try {
        const payload = {
          ...PaymentFormDefaults.value,
          payment_type_id: PaymentFormDefaults.value.paymentType === store.selectedPayment?.paymentType ? store.selectedPayment?.paymentTypeId : PaymentFormDefaults.value.paymentTypeId,
          invoice_id: documentsStore?.docType === 'invoice' ? documentsStore.selectedDocumentCore?.id : null,
          order_receipt_id: documentsStore?.docType === 'orderreceipt' ? documentsStore.selectedDocumentCore?.id : null,

        };

        if (store.mode === 'add') {
          // Add new payment logic
          const response = store.type === 'recovery' ? await addRecovery(payload) : await addPayment(payload);
          const paymentId = response.id;
          store.payments.push({ ...payload, id: paymentId });
        } else if (store.mode === 'edit') {
          const paymentId = payload.id;
          if (store.type === 'recovery') {
            await updateRecovery(paymentId, payload);
          } else {
            await updatePayment(paymentId, payload);
          }

          const index = store.payments.findIndex((c) => c.id === paymentId);
          if (index !== -1) {
            store.payments[index] = { ...payload, id: paymentId };
          }
        }
        closeDrawer();
        emit('submit');
      } catch (error) {
        console.error('Error preparing payment data:', error);

        closeDrawer();
        const err = error as any;
        showSnackbar(`${err.response?.data.message}`, 'error');
      } finally {
        loading.value = false;
      }
    }
  });
};

// 👉 Utility function to resolve text from UUID
const resolvePaymentTypeText = (uuid: any): string | null => {
  const foundItem = filterItems.value.paymentTypes.find((item) => item.value === uuid);

  const textValue = foundItem ? foundItem.text : null;

  if (textValue) {
    const reversedMapping: { [key: string]: string } = {
      'Espèce': 'ESPECE',
      'Virement': 'VIREMENT',
      'Chèque': 'CHEQUE',
      'Effet': 'EFFET',
    };

    return reversedMapping[textValue] || null;
  }

  return null;
};

// 👉 Utility function to resolve text from UUID
const resolvePaymentTypeTextByText = (text: any): string | null => {
  if (text) {
    const reversedMapping: { [key: string]: string } = {
      'Espèce': 'ESPECE',
      'Virement': 'VIREMENT',
      'Chèque': 'CHEQUE',
      'Effet': 'EFFET',
    };

    return reversedMapping[text] || null;
  }

  return null;
};


const resolvePaymentTypeUuid = (text: any): string | null => {
  const foundItem = filterItems.value.paymentTypes.find((item) => item.text === text);
  return foundItem ? foundItem.value : null;
};

// 👉 Fetch static data and populate filter options
const loadStaticData = async () => {
  try {
    const staticData = await fetchStaticData();
    filterItems.value.paymentTypes = mapStaticData(staticData.data.paymentType);
    filterItems.value.clients = mapStaticData(staticData.data.client);
    filterItems.value.recoveryTypes = mapStaticData(staticData.data.recoveryType);
  } catch (error) {
    console.error("Error fetching static data:", error);
  }
};

// 👉 Function to check if the selected payment type is "Espèce"
const isEspecePaymentType = computed(() => {
  const isEspecePaymentTypeNewValue = resolvePaymentTypeText(PaymentFormDefaults.value.paymentType) === 'ESPECE' ||
    (store.mode === 'edit' && resolvePaymentTypeUuid(store.selectedPayment?.paymentType) === resolvePaymentTypeUuid(PaymentFormDefaults.value.paymentType))

  return isEspecePaymentTypeNewValue;
});


// 👉 Store form logic and call loadStaticData on component mount  
watch(() => store.selectedPayment, (newPayment) => {
  if (store.mode === 'edit' && newPayment) {
    PaymentFormDefaults.value = { ...newPayment };

    const paymentTypeText = resolvePaymentTypeTextByText(newPayment.paymentType);
    emit('update:title2', paymentTypeText === 'ESPECE' ? 'Espèce' : '');

  }
}, { immediate: true });

onMounted(async () => {
  if (store.mode === 'add') {
    PaymentFormDefaults.value = store.selectedPayment ? { ...store.selectedPayment } : getDefaultPayment();
  } else if (store.mode === 'edit' && store.selectedPayment) {
    PaymentFormDefaults.value = { ...store.selectedPayment };

    // If editing and has a recoveryId, fetch recoveries to populate the dropdown
    if (store.selectedPayment.recoveryId) {
      try {
        const paymentTypeId = PaymentFormDefaults.value.paymentType === store.selectedPayment?.paymentType ? store.selectedPayment?.paymentTypeId : PaymentFormDefaults.value.paymentTypeId;
        const clientId = PaymentFormDefaults.value.client === store.selectedPayment?.client ? store.selectedPayment?.clientId : PaymentFormDefaults.value.clientId;

        const recoveriesStaticData = await fetchRecoveriesStaticData(paymentTypeId, clientId);
        filterItems.value.recoveries = mapStaticData(recoveriesStaticData.data.recoveries);

        // Set the recovery balance from the fetched data
        const selectedRecovery = recoveriesStaticData.data.recoveries.find((r: any) => r.id === store.selectedPayment?.recoveryId);
        PaymentFormDefaults.value.recoveryId = selectedRecovery ? selectedRecovery.id : null;
        PaymentFormDefaults.value.recovery = selectedRecovery ? selectedRecovery.display : null;
        if (selectedRecovery) {
          recoveryBalance.value = selectedRecovery.recoveryBalance || null;
          emit('update:recoveryBalance', recoveryBalance.value);
        }
      } catch (error) {
        console.error("Error fetching recovery for edit mode:", error);
      }
    }
  }
  loadStaticData();
});

watch(
  () => PaymentFormDefaults.value.paymentType,
  (newPaymentType, oldPaymentType) => {
    if (newPaymentType !== oldPaymentType) {
      if (newPaymentType !== store.selectedPayment?.paymentType) {
        PaymentFormDefaults.value.paymentType = newPaymentType;
      }
    }
  }
);

// 👉 Watch for payment type changes to reload recoveries
watch(
  () => [PaymentFormDefaults.value.paymentTypeId, PaymentFormDefaults.value.clientId],
  async ([newPaymentTypeId, newClientId]) => {
    // Clear recovery field whenever payment type changes
    PaymentFormDefaults.value.recoveryId = null;
    PaymentFormDefaults.value.recovery = null;
    recoveryBalance.value = null;
    emit('update:recoveryBalance', null);

    if (store.type === 'payment' && newPaymentTypeId) {
      const paymentTypeText = resolvePaymentTypeText(newPaymentTypeId);

      // Only load recoveries if payment type is not ESPECE
      if (paymentTypeText && paymentTypeText !== 'ESPECE') {
        try {
          const paymentTypeId = PaymentFormDefaults.value.paymentType === store.selectedPayment?.paymentType ? store.selectedPayment?.paymentTypeId : PaymentFormDefaults.value.paymentTypeId;
          const clientId = PaymentFormDefaults.value.client === store.selectedPayment?.client ? store.selectedPayment?.clientId : PaymentFormDefaults.value.clientId;

          if (paymentTypeId && clientId) {
            const recoveriesStaticData = await fetchRecoveriesStaticData(paymentTypeId, clientId);
            filterItems.value.recoveries = mapStaticData(recoveriesStaticData.data.recoveries);
          }
        } catch (error) {
          console.error("Error fetching recoveries static data:", error);
        }
      } else {
        // Clear recoveries if payment type is ESPECE or null
        filterItems.value.recoveries = [];
      }
    }
  },
  { deep: true }
);


const clientBalance = computed(() => props.clientBalanceProps || 0);

const isAmountExceedingBalance = computed(() => {
  const paymentType = resolvePaymentTypeTextByText(PaymentFormDefaults.value.paymentType);

  // Check if amount exceeds client balance for ESPECE
  if (isEspecePaymentType.value && PaymentFormDefaults.value.amount !== null && PaymentFormDefaults.value.amount > clientBalance.value) {
    return true;
  }

  // Check if amount exceeds recovery balance for VIREMENT, CHEQUE, or EFFET
  if ((paymentType === 'VIREMENT' || paymentType === 'CHEQUE' || paymentType === 'EFFET') &&
    PaymentFormDefaults.value.amount !== null &&
    recoveryBalance.value !== null &&
    PaymentFormDefaults.value.amount > recoveryBalance.value) {
    return true;
  }

  return false;
});

const handlePaymentTypeUpdate = (event: any) => {

  if (event === null) {
    // Handle clearing the selection
    PaymentFormDefaults.value.paymentTypeId = null;
    PaymentFormDefaults.value.paymentType = null;
    emit('update:title2', '');
    return;
  }

  PaymentFormDefaults.value.paymentTypeId = event.value;
  PaymentFormDefaults.value.paymentType = event.text;
  emit('update:title2', resolvePaymentTypeText(event.value) === 'ESPECE' ? 'Espèce' : '');
};

const handleClientsUpdate = (event: any) => {

  if (event === null) {
    // Handle clearing the selection
    PaymentFormDefaults.value.clientId = null;
    PaymentFormDefaults.value.client = null;
    return;
  }

  PaymentFormDefaults.value.clientId = event.value;
  PaymentFormDefaults.value.client = event.text;
};
const handleRecoveriesUpdate = async (event: any) => {

  if (event === null) {
    // Handle clearing the selection
    PaymentFormDefaults.value.recoveryId = null;
    PaymentFormDefaults.value.recovery = null;
    recoveryBalance.value = null;
    emit('update:recoveryBalance', null);
    return;
  }
  PaymentFormDefaults.value.recoveryId = event.value;
  PaymentFormDefaults.value.recovery = event.text;

  // Fetch and emit the selected recovery's balance
  try {
    const paymentTypeId = PaymentFormDefaults.value.paymentType === store.selectedPayment?.paymentType ? store.selectedPayment?.paymentTypeId : PaymentFormDefaults.value.paymentTypeId;
    const clientId = PaymentFormDefaults.value.client === store.selectedPayment?.client ? store.selectedPayment?.clientId : PaymentFormDefaults.value.clientId;
    const recoveryId = event.value;

    if (paymentTypeId && clientId && recoveryId) {
      const recoveriesStaticData = await fetchRecoveriesStaticData(paymentTypeId, clientId);
      const selectedRecovery = recoveriesStaticData.data.recoveries.find((r: any) => r.id === recoveryId);
      const selectedRecoveryType = resolvePaymentTypeTextByText(selectedRecovery.paymentType);
      switch (selectedRecoveryType) {
        case 'VIREMENT':
          PaymentFormDefaults.value.wireTransferNumber = selectedRecovery.wireTransferNumber;
          break;
        case 'CHEQUE':
          PaymentFormDefaults.value.checkNumber = selectedRecovery.checkNumber;
          break;
        case 'EFFET':
          PaymentFormDefaults.value.effectNumber = selectedRecovery.effectNumber;
          break;
        default:
          break;
      }
      recoveryBalance.value = selectedRecovery?.recoveryBalance || null;
      emit('update:recoveryBalance', recoveryBalance.value);
    }
  } catch (error) {
    console.error("Error fetching recovery balance:", error);
  }
};

const handleDatePickerState = (isOpen: boolean) => {
  isDatePickerOpen.value = isOpen;
  emit('date-picker-state', isOpen);
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

</script>

<template>
  <VForm class="mt-6" ref="refForm" v-model="isFormValid" @submit.prevent="handleFormSubmit">
    <VRow class="mt-6">

      <VCol cols="12" md="6">
        <VAutocomplete :model-value="PaymentFormDefaults.paymentType"
          @update:modelValue="handlePaymentTypeUpdate($event)" placeholder="Type" label="Type *"
          :rules="store.mode === 'add' ? [requiredValidator] : []"
          :items="store.type === 'recovery' ? filterItems.recoveryTypes : filterItems.paymentTypes" item-title="text"
          return-object clearable clear-icon="tabler-x" variant="outlined" />
      </VCol>

      <VCol cols="12" md="6">
        <VAutocomplete :model-value="PaymentFormDefaults.client" label="Client *" placeholder="Client"
          @update:modelValue="handleClientsUpdate($event)" :items="filterItems.clients" item-title="text"
          item-value="value" return-object clearable clear-icon="tabler-x" variant="outlined"
          :disabled="store.type === 'payment'" :rules="store.mode === 'add' ? [requiredValidator] : []" />
      </VCol>

      <VCol cols="12" md="6">
        <VAutocomplete :model-value="PaymentFormDefaults.recovery"
          :label="resolvePaymentTypeTextByText(PaymentFormDefaults.paymentType) !== 'ESPECE' && store.type === 'payment' ? 'Recouvrement *' : 'Recouvrement'"
          :placeholder="resolvePaymentTypeTextByText(PaymentFormDefaults.paymentType) !== 'ESPECE' && store.type === 'payment' ? 'Recouvrement *' : 'Recouvrement'"
          @update:modelValue="handleRecoveriesUpdate($event)" :items="filterItems.recoveries" item-title="text"
          item-value="value" return-object clearable clear-icon="tabler-x" variant="outlined"
          :disabled="store.type === 'recovery' || !PaymentFormDefaults.paymentTypeId || resolvePaymentTypeTextByText(PaymentFormDefaults.paymentType) === 'ESPECE'"
          :rules="(store.mode === 'add' && resolvePaymentTypeTextByText(PaymentFormDefaults.paymentType) !== 'ESPECE') && store.type === 'payment' ? [requiredValidator] : []" />
      </VCol>

      <!-- Date de paiment -->
      <VCol cols="12" md="6">

        <div></div>

        <div ref="FlatPickrParent" class="flat-pickr-parent"></div>
        <AppDateTimePicker v-if="isParentRefSet" label="Date de paiement *" v-model="PaymentFormDefaults.date"
          placeholder="Date de paiment" :config="{
            position: 'above',
            dateFormat: 'd/m/Y',
            ...pickerOptions
          }" :FlatPickrParent="FlatPickrParent" :rules="[requiredValidator]" @picker-open="handleDatePickerState(true)"
          @picker-close="handleDatePickerState(false)" />

      </VCol>

      <!-- Montant -->
      <VCol cols="12" md="6">
        <VTextField v-model="PaymentFormDefaults.amount" type="number" :rules="[
          ...(store.mode === 'add' ? [requiredValidator] : []),
          (value: any) => value > 0 || 'Montant doit être supérieur à 0',
          (value: any) =>
            resolvePaymentTypeTextByText(PaymentFormDefaults.paymentType) == 'ESPECE'
              ? value <= clientBalance || `Le montant ne peut pas dépasser ${clientBalance}`
              : true
        ]" placeholder="Montant" label="Montant *" variant="outlined" :max="clientBalance" min="1" step="0.01" />


      </VCol>


      <!-- Note -->
      <VCol cols="12 " md="6">
        <VTextField v-model="PaymentFormDefaults.comment" placeholder="Commentaire" label="Commentaire"
          variant="outlined" />
      </VCol>


      <!-- Conditional Fields -->

      <VCol cols="12" md="6" v-if="resolvePaymentTypeTextByText(PaymentFormDefaults.paymentType) === 'CHEQUE'">
        <VTextField v-model="PaymentFormDefaults.checkNumber" :rules="store.mode === 'add' ? [requiredValidator] : []"
          placeholder="Numéro de série de chèque" label="Numéro de série de chèque *" variant="outlined"
          :disabled="store.type === 'payment'" />
      </VCol>

      <VCol cols="12" md="6" v-if="resolvePaymentTypeTextByText(PaymentFormDefaults.paymentType) === 'VIREMENT'">
        <VTextField v-model="PaymentFormDefaults.wireTransferNumber"
          :rules="store.mode === 'add' ? [requiredValidator] : []" placeholder="Référence de virement"
          variant="outlined" label="Référence de virement *" :disabled="store.type === 'payment'" />
      </VCol>

      <VCol cols="12" md="6" v-if="resolvePaymentTypeTextByText(PaymentFormDefaults.paymentType) === 'EFFET'">
        <VTextField v-model="PaymentFormDefaults.effectNumber" :rules="store.mode === 'add' ? [requiredValidator] : []"
          label="Numéro de série d’effet *" placeholder="Numéro de série d’effet" variant="outlined"
          :disabled="store.type === 'payment'" />
      </VCol>

      <!-- Action buttons -->
      <VCol cols="12" class="d-flex flex-wrap justify-center gap-4 mt-4">

        <VBtn :disabled="loading" @click="closeDrawer" type="reset" variant="tonal" color="error">Annuler</VBtn>

        <VBtn :disabled="loading" :loading="loading" class="me-3" type="submit">
          {{ formTitle }}
        </VBtn>
      </VCol>
    </VRow>

  </VForm>
</template>
<style scoped>
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
