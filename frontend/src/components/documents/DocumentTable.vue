<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  documentType: {
    type: String,
    required: true
  },

  TableData: {
    type: Object,
    default: () => ({ data: [] })
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  documentData: {
    type: Object,
    default: () => ({})
  },
  hasDocumentManagerPermission: {
    type: Boolean,
    default: false
  },
  routeMappings: {
    type: Object,
    required: true
  },
  selectedIds: {
    type: Array,
    default: () => []
  },
  selectedItemsIds: {
    type: Array,
    default: () => []
  },
  statusItems: {
    type: Array,
    default: () => []
  },
  invoiceStatus: {
    type: Array,
    default: () => []
  },
  productionNoteStatus: {
    type: Array,
    default: () => []
  },
  outputNoteStatus: {
    type: Array,
    default: () => []
  },
  delliveryNoteStatus: {
    type: Array,
    default: () => []
  },
  invoiceCreditStatus: {
    type: Array,
    default: () => []
  },
  refundStatus: {
    type: Array,
    default: () => []
  },
  status: {
    type: Array,
    default: () => []
  },
  nextDocumentName: {
    type: String,
    default: null
  },
  canGenerate: {
    type: Boolean,
    default: false
  },
  archiveStore: {
    type: Object,
    default: () => ({ isArchive: false })
  },
  isStatusChanged: {
    type: Boolean,
    default: false
  },
})

const emit = defineEmits([
  'update:selectedDocumentTableItems',
  'handleStatusChange',
  'openEditDrawer',
  'openDeleteDialog',
  'DuplicateDocument',
  'handleNavigateToNextDocument',
  'openAddNewwProductionNote',
  'update:selectedStatus',
  'update:selectedSubItems'
])

const route = useRoute()
const routeId = computed(() => (route.params as { id?: string }).id)

const selectedDocumentTableItems = ref<any[]>([])
const selectedSubItems = ref<Record<string, boolean>>({})
const selectedStatusItems = ref<Record<string, boolean>>({})
const selectedStatus = ref(null)

// Watch for changes in selected table items and emit update
watch(selectedDocumentTableItems, (newValue) => {
  emit('update:selectedDocumentTableItems', newValue)
})

// Watch for changes in selected sub items and emit update
watch(
  selectedSubItems,
  (newValue) => {
    emit('update:selectedSubItems', newValue)
  },
  { deep: true }
)

function updateSelectedSubItem(id: string, value: boolean) {
  selectedSubItems.value[id] = value
}

// Reset all selections when status changes
watch(selectedStatus, (newValue) => {
  emit('update:selectedStatus', newValue)
})


const handleStatusChange = (status: any, id: any) => {
  emit('handleStatusChange', status, id)
}

const openEditDrawer = (itemId: any) => {
  emit('openEditDrawer', itemId)
}

const openDeleteDialog = (item: any) => {
  emit('openDeleteDialog', item)
}

const DuplicateDocument = (item: any) => {
  emit('DuplicateDocument', item)
}

onMounted(() => {
  if (props.documentData?.items) {
    const statusObj = props.documentData.items.reduce((acc: any, item: any) => {
      acc[item.id] = item.status
      return acc
    }, {})
    selectedStatusItems.value = statusObj
  }
})


// Local ref for selection
watch(
  [() => props.isStatusChanged, () => props.TableData],
  ([isStatusChanged], [prevIsStatusChanged, prevTableData]) => {
    if (isStatusChanged && props.TableData?.data?.length) {
      selectedDocumentTableItems.value = props.TableData.data.filter((item: any) =>
        props.selectedItemsIds.includes(item.id)
      ) || [];
      emit('update:selectedDocumentTableItems', selectedDocumentTableItems.value);
    }
  },
  { deep: true }
);

function handleSelectionChange(newSelection: any[]) {
  selectedDocumentTableItems.value = newSelection.map((item: any) => item.id)
  emit('update:selectedDocumentTableItems', selectedDocumentTableItems.value)
}

const baseHeaders = [
  { title: 'Numéro', key: 'code' },
  { title: 'Client', key: 'client' },
  {
    title: props.documentData?.isTaxable ? 'Total TTC' : 'Total HT',
    key: props.documentData?.isTaxable ? 'finalAmount' : 'amount',
    width: '140px'
  },
  { title: 'Status', key: 'status', width: '140px' },
  { title: 'Gestion', key: 'actions', sortable: false, width: '180px' },
];

const headers = computed(() => {
  const currentHeaders = [...baseHeaders];
  if (props.documentType === 'Facture' || props.documentType === 'Reçu de commande') {
    currentHeaders.splice(2, 0, {
      title: 'Total payé',
      key: 'payedAmount',
      width: '140px'
    });
  }
  if (props.documentType === 'Bon de production') {
    const index = currentHeaders.findIndex(header =>
      header.key === 'finalAmount' ||
      header.key === 'amount' ||
      header.title === 'Total TTC' ||
      header.title === 'Total HT'
    );
    if (index !== -1) {
      currentHeaders.splice(index, 1);
    }
  }

  return currentHeaders;
});

// Helper function to cast item to any
const asAny = (item: unknown) => item as any

</script>

<template>
  <div v-if="documentType !== 'Devis' && documentType !== 'Bon de commande' && documentType !== 'Demande de devis'"
    class="d-flex justify-space-between align-center flex-wrap gap-y-4">
    <VRow class="mb-3">
      <VCol cols="12" md="9">
        <VCard>
          <VDataTable @update:model-value="handleSelectionChange" item-value="id"
            :expand-on-click="['Bon de livraison', 'Facture', 'Facture avoir', 'Reçu de commande'].includes(documentType)"
            v-model="selectedDocumentTableItems" return-object :headers="headers" :items="TableData?.data"
            class="elevation-1" :loading="isLoading"
            :show-select="!(documentType === 'Facture avoir' || documentType === 'Bon de remboursement')"
            color="primary"
            :item-selectable="(item: any) =>
              documentType !== 'Facture' && documentType !== 'Reçu de commande' && !['Terminé', 'Annulé', 'Retourné', 'Perte'].includes(item.status)">

            <template #loading>
              <div class="d-flex justify-center align-center loading-container">
                <VProgressCircular indeterminate color="primary" />
              </div>
            </template>

            <template v-if="documentType === 'Facture' || documentType === 'Reçu de commande'"
              #item.payedAmount="{ item }">
              <span>
                <!-- @ts-ignore -->
                {{ asAny(item).payedAmount || '0.00' }}
              </span>
            </template>

            <template #expanded-row="slotProps">
              <!-- @ts-ignore -->
              <tr v-for="subItem in asAny(slotProps.item).items" :key="subItem.id" class="v-data-table__tr">

                <td v-if="documentType === 'Facture' || documentType === 'Reçu de commande'">
                  <VCheckbox :model-value="selectedSubItems[subItem.id]"
                    @update:model-value="(value: any) => updateSelectedSubItem(subItem.id, value)" />
                </td>

                <td v-if="documentType === 'Bon de livraison'">&nbsp;</td>
                <td>{{ subItem.description || 'N/A' }}</td>

                <td>{{ subItem.amount ?? '0.00' }}</td>
                <td v-if="documentType === 'Facture' || documentType === 'Reçu de commande'">&nbsp;</td>

                <td v-if="documentType === 'Bon de livraison'">
                  <VSelect :disabled="['Terminé'].includes(documentData?.status)"
                    @update:modelValue="handleStatusChange($event, subItem.id)"
                    v-model="selectedStatusItems[subItem.id]" :items="statusItems" placeholder="Statut" />
                </td>
                <td v-if="documentType === 'Bon de livraison'">&nbsp;</td>
                <td v-if="documentType === 'Bon de livraison'">&nbsp;</td>
                <td v-if="documentType === 'Facture'">&nbsp;</td>
                <td v-if="documentType === 'Facture'">&nbsp;</td>
                <td v-if="documentType === 'Facture'">&nbsp;</td>
                <td v-if="documentType === 'Facture avoir'">&nbsp;</td>
                <td v-if="documentType === 'Facture avoir'">&nbsp;</td>
                <td v-if="documentType === 'Reçu de commande'">&nbsp;</td>
                <td v-if="documentType === 'Reçu de commande'">&nbsp;</td>
              </tr>
            </template>

            <template #item.code="{ item }">
              <!-- @ts-ignore -->
              <RouterLink :to="{ name: routeMappings[documentType], params: { id: (item as any).id } }"
                class="text-link font-weight-medium d-inline-block"
                :class="{ 'active-link': routeId == (item as any).id }" style="line-height: 1.375rem;">
                {{ (item as any).code }}
              </RouterLink>
            </template>

            <template #item.client="{ item }">
              <!-- @ts-ignore -->
              <span>{{ (item as any).client?.legalName || 'N/A' }}</span>
            </template>

            <template #item.isTaxable="{ item }">
              <!-- @ts-ignore -->
              <span v-if="asAny(item).isTaxable">
                {{ asAny(item).taxAmount || '0.00' }}
              </span>
              <span v-else>-</span>
            </template>

            <template #item.finalAmount="{ item }">
              <!-- @ts-ignore -->
              <span v-if="asAny(item).isTaxable">
                {{ asAny(item).finalAmount || '0.00' }}
              </span>
              <span v-else>-</span>
            </template>

            <template #item.actions="{ item }">
              <!-- @ts-ignore -->
              <div style="display: flex; gap: 8px;">
                <RouterLink :to="{ name: routeMappings[documentType], params: { id: (item as any).id } }"
                  :class="{ 'active-link': routeId == (item as any).id }"
                  class="text-link font-weight-medium d-inline-block"
                  style="line-height: 1.375rem; text-decoration: none;">
                  <IconBtn>
                    <VIcon icon="tabler-eye" />
                  </IconBtn>
                </RouterLink>
                <IconBtn v-if="documentType === 'Bon de production' && hasDocumentManagerPermission"
                  :disabled="archiveStore.isArchive" @click="DuplicateDocument(item)">
                  <VIcon icon="tabler-copy" />
                </IconBtn>
                <IconBtn v-if="hasDocumentManagerPermission" @click="openEditDrawer((item as any).id)"
                  :disabled="(!(['Facture avoir', 'Bon de retour'].includes(documentType)) && (item as any).status ? ['Validé', 'Annulé', 'Terminé'].includes((item as any).status) : false) || archiveStore.isArchive">
                  <VIcon icon="tabler-pencil" />
                </IconBtn>
                <IconBtn :disabled="archiveStore.isArchive" v-if="hasDocumentManagerPermission"
                  @click="openDeleteDialog(item)">
                  <VIcon icon="tabler-trash" />
                </IconBtn>
              </div>
            </template>

            <template #bottom></template>
          </VDataTable>
        </VCard>
      </VCol>

      <slot name="generatecard"></slot>
    </VRow>
  </div>
</template>
