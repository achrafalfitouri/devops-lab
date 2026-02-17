<script setup lang="ts">
import { DocumentItems } from '@/services/models';
import { useDocumentCoreStore } from '@/stores/documents';
import { ref } from 'vue';

const documentsStore = useDocumentCoreStore();

// 👉 Emits Definitions
interface Emit {
  (e: 'removeProduct', value: any): void;
  (e: 'totalAmount', value: number): void;
  (e: 'updateProductData', payload: { index: number | null, order: number | null; description: string | null; price: number | null; discount: number | null; quantity: number | null, characteristics? : string | null }): void;

}

// 👉 Props Interface 
interface Props {
  id: any;
  index: any
  count: any
  data: DocumentItems;
  order: number | null
  validPrice: boolean | null
  validQuantity: boolean | null
  validDiscount: boolean | null
  validDescription: boolean | null
  documentType : any 
}

// 👉 Definitions 
const props = defineProps<Props>()
const { width: windowWidth } = useWindowSize()
const emit = defineEmits<Emit>();
const localProductData = ref(structuredClone(toRaw(props.data)));

// 👉 Validators
const priceValidator = (v: number) => v > 0 || 'Price must be greater > 0.';
const discountValidator = (v: number) => v > 0 || 'Discount must be > 0.';
const quantityValidator = (v: number) => v > 0 || 'Quantity must be at least 1.';
const requiredValidator = (v: any) => (v !== null && v !== undefined && v !== '') || 'Ce champ est obligatoire';

// 👉 Function to remove item
const removeProduct = () => {
  emit('removeProduct', documentsStore.mode === 'add' ? props.index : props.id);
};

// 👉 Function to calculate items total price 
const totalPrice = computed(() => {
  const discountAmount = localProductData.value.discount || 0;
  const total = (localProductData.value.price || 0) * (localProductData.value.quantity || 0) - discountAmount;
  return total.toFixed(2);
});

// 👉 Computed to set a discount max 
const maxDiscount = computed(() => {
  const price = Math.max(localProductData.value.price || 0, 0);
  const quantity = Math.max(localProductData.value.quantity || 1, 0);
  return price * quantity;
});

// 👉 Computed to set a quantity min
const minQuantity = computed(() => {
  const price = localProductData.value.price || 0;
  const quantity = localProductData.value.quantity || 1;
  const discount = localProductData.value.discount || 0;
  const total = price * quantity;

  return discount >= total ? quantity : 1;
});

// 👉 Computed to set a price min
const minPrice = computed(() => {
  const price = localProductData.value.price || 0;
  const quantity = localProductData.value.quantity || 1;
  const discount = localProductData.value.discount || 0;
  const total = price * quantity;
  return discount >= total ? price : 0;
});

// 👉 Function to validate discount
const validateDiscount = (event: InputEvent) => {
  const inputElement = event.target as HTMLInputElement;
  let discount = inputElement.value;

  discount = discount.replace(/[^0-9.]/g, '');

  const [integer, decimal] = discount.split('.');

  if (decimal && decimal.length > 2) {
    discount = `${integer}.${decimal.substring(0, 2)}`;
  }

  let parsedDiscount = parseFloat(discount);

  if (isNaN(parsedDiscount)) {
    parsedDiscount = 0;
  }

  if (parsedDiscount > maxDiscount.value) {
    parsedDiscount = maxDiscount.value;
  } else if (parsedDiscount < 0) {
    parsedDiscount = 0;
  }

  localProductData.value.discount = parsedDiscount;

  inputElement.value = parsedDiscount.toString();
};


// 👉 Watch that listen to items total price in order to emit it to the parent 
watch(totalPrice, () => {
  emit('totalAmount', parseFloat(totalPrice.value));
}, { immediate: true });

// 👉 Watch to emit items data to the parent
watch(
  () => ({
    id: props.id,
    index: props.index,
    order: props.order !== null ? props.order : localProductData.value.order,
    description: localProductData.value.description as string,
    characteristics : localProductData.value.characteristics as string ,
    price: localProductData.value.price as number,
    quantity: localProductData.value.quantity as number,
    discount: localProductData.value.discount as number,
  }),
  (newData) => {
    emit('updateProductData', newData);

  },
  { deep: true }
);

// 👉 watch to validate discount entries
watch(
  () => localProductData.value.discount,
  (newDiscount) => {
    if (newDiscount !== null && newDiscount > maxDiscount.value) {
      localProductData.value.discount = maxDiscount.value;
    }
  }
);

const handleInput = (event: any) => {
  let value = event.target.value;
  const isPriceField = event.target.placeholder === 'Prix';
  const isQuantityField = event.target.placeholder === 'Quantité';

  if (isPriceField) {
    value = value.replace(/[^0-9.]/g, '');
    const [integer, decimal] = value.split('.');

    if (decimal && decimal.length > 2) {
      value = `${integer}.${decimal.substring(0, 2)}`;
    }
  }

  if (isQuantityField) {
    value = value.replace(/[^0-9]/g, '');
  }

  if (isPriceField) {
    localProductData.value.price = value;
  } else if (isQuantityField) {
    localProductData.value.quantity = value;
  }
};


</script>

<template>
  <div v-if="props.count === 0" class="add-products-header mb-2 d-none d-md-flex mb-4">
    <VRow class="me-10">
      <!-- Désignation Column -->
      <VCol 
        cols="12" 
        :md="props?.documentType === 'Bon de production' || props?.documentType === 'Demande de devis' ? 3 : 6" 
      >
        <h6 class="text-h6">Désignation</h6>
      </VCol>
      
      <!-- Caractéristique Column (only for Bon de production and Demande de devis) -->
      <VCol 
        v-if="props?.documentType === 'Demande de devis' || props?.documentType === 'Bon de production'"
        cols="12" 
        md="3"
      >
        <h6 class="text-h6">Caractéristique</h6>
      </VCol>
   
      <!-- Input Fields Column -->
      <VCol 
        cols="12" 
        md="6" 
        class="d-flex justify-space-between"
      >
        <h6 class="text-h6 ps-2">PU</h6>
        <h6 class="text-h6 ps-2">Qte</h6>
        <h6 class="text-h6 ps-2">Remise</h6>
        <h6 v-if="props?.documentType !== 'Demande de devis'" class="text-h6 pe-1" style="white-space: nowrap;">Montant HT</h6>
      </VCol>
    </VRow>
  </div>

  <VCard flat border class="d-flex flex-sm-row flex-column-reverse">
    <div class="pa-4 flex-grow-1">
      <VRow>
        <!-- 👉 Description -->
        <VCol 
          cols="12" 
          :md="props?.documentType === 'Bon de production' || props?.documentType === 'Demande de devis' ? 3 : 6" 
          class="pe-2"
        >
          <AppTextarea 
            :label="windowWidth < 960 ? 'Désignation' : ''" 
            v-model="localProductData.description" 
            rows="2"
            placeholder="Description de l'article" 
            persistent-placeholder
            :min="minPrice" 
          />
        </VCol>

        <!-- 👉 Characteristics (only for Bon de production and Demande de devis) -->
        <VCol 
          v-if="props?.documentType === 'Demande de devis' || props?.documentType === 'Bon de production'" 
          cols="12" 
          md="3" 
          class="pe-2"
        >
          <AppTextarea 
            :label="windowWidth < 960 ? 'Caractéristique' : ''" 
            v-model="localProductData.characteristics" 
            rows="2"
            placeholder="Caractéristique de l'article" 
            persistent-placeholder
            :min="minPrice" 
          />
        </VCol>

        <!-- 👉 Inputs and Total -->
        <VCol cols="12" md="6" class="d-flex align-center">
          <AppTextField 
            :label="windowWidth < 960 ? 'Prix' : ''" 
            v-model="localProductData.price" 
            placeholder="Prix"
            class="fieldinput me-1" 
            style="width: 75px"
            @input="handleInput" 
            @paste.prevent 
          />
          <AppTextField 
            :label="windowWidth < 960 ? 'Quantité' : ''" 
            v-model="localProductData.quantity"
            :min="minQuantity" 
            placeholder="Quantité" 
            class="fieldinput me-1" 
            style="width: 75px"
            @input="handleInput"
            @paste.prevent 
          />
          <AppTextField 
            :label="windowWidth < 960 ? 'Remise' : ''" 
            v-model="localProductData.discount" 
            min="0"
            :max="maxDiscount" 
            placeholder="Remise" 
            class="fieldinput me-1" 
            style="width: 75px"
            @input="validateDiscount" 
            @paste.prevent 
          />

          <!-- 👉 Total Label -->
          <div 
            v-if="props?.documentType !== 'Demande de devis'" 
            class="total d-flex flex-column align-items-center my-2 ps-1"
          >
            <p v-if="windowWidth < 960" style="margin: 0; width: 75px;" class="text-high-emphasis text-center mb-1">
              Totale
            </p>
            <p class="text-high-emphasis text-center" style="width: 75px; margin: 0;">
              {{ totalPrice }}
            </p>
          </div>
        </VCol>
      </VRow>
      
      <!-- Validation Message Area -->
      <div 
        v-if="(!localProductData.description || !localProductData.price || !localProductData.quantity) && documentsStore.shouldVerify && props.documentType!=='Demande de devis'"
        class="text-error mt-2 ps-2 validation-summary"
      >
        <span v-if="!localProductData.description" class="d-block">
          Description est obligatoire
        </span>
        <span v-if="!localProductData.price" class="d-block">
          Prix est obligatoire
        </span>
        <span v-if="!localProductData.quantity" class="d-block">
          Quantité est obligatoire
        </span>
      </div>
      <div 
        v-if="(!localProductData.description || !localProductData.price || !localProductData.quantity) && documentsStore.shouldVerify && props.documentType ==='Demande de devis'"
        class="text-error mt-2 ps-2 validation-summary"
      >
        <span v-if="!localProductData.description" class="d-block">
          Description est obligatoire
        </span>
      </div>
    </div>

    <!-- 👉 Action Buttons -->
    <div class="d-flex flex-column align-end item-actions" :class="$vuetify.display.smAndUp ? 'border-s' : 'border-b'">
      <IconBtn size="36" @click="removeProduct">
        <VIcon :size="24" icon="tabler-x" />
      </IconBtn>
    </div>
  </VCard>

</template>

<style scoped>
@media (min-width: 786px) {
  .responsive-label .v-input__label {
    display: none !important;
  }
}
</style>
<style scoped>
.add-products-header {
  margin-bottom: 1rem;
}

.pa-4 {
  padding: 1rem;
}

.v-col > * {
  margin-bottom: 0.5rem;
}

.v-col > .app-text-field {
  margin: 0 0.5rem;
}

.flex-sm-row .v-col:first-child {
  margin-right: 1rem;
}

@media (min-width: 1280px) {
  .text-h6 {
    padding-inline-end: 1.8rem;
  }

  .totaltitle {
    margin-left: 25px;
  }

}
</style>
<style>
body .app-text-field .v-input.v-input--density-comfortable .v-field .v-field__input,
body .app-select .v-input.v-input--density-comfortable .v-field .v-field__input,
body .app-autocomplete .v-input.v-input--density-comfortable .v-field .v-field__input,
body .app-combobox .v-input.v-input--density-comfortable .v-field .v-field__input,
body .app-textarea .v-input.v-input--density-comfortable .v-field .v-field__input,
body .app-picker-field .v-input.v-input--density-comfortable .v-field .v-field__input {
  --v-field-padding-start: 10px;
  --v-field-padding-end: 10px;
}
</style>
