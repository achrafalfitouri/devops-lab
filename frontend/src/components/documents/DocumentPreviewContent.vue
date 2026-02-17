<script lang="ts" setup>
import DocumentsFooter from '@/components/DocumentsFooter.vue';
import { VNodeRenderer } from '@layouts/components/VNodeRenderer';
import { computed } from 'vue';

// You need to define props for the component
const props = defineProps<{
  documentData: any
  documentType: string
  documentKey: string
  themeConfig: any
  formatDateToddmmYYYY: any
}>()

// 👉 Show discount column only if at least one item has non-null, non-zero discount
const showDiscountColumn = computed(() => {
  const items = props.documentData?.items || []
  return Array.isArray(items) && items.some((item: any) => item?.discount != null && Number(item.discount) !== 0)
})
</script>

<template>
  <VCard id="printable-content" class="quote-preview-wrapper pa-5 pa-sm-8">
    <!-- SECTION Header -->
    <div
      class="quote-header-preview d-flex flex-wrap justify-space-between flex-column flex-sm-row print-row bg-var-theme-background gap-5 rounded pa-5 mb-5">
      <!-- 👉 Left Content -->
      <div>
        <div class="d-flex align-center app-logo mb-3">
          <VNodeRenderer :nodes="props.themeConfig.app.logo" />
          <h6 class="app-logo-title" style="font-size: 1.05rem;">
            {{ props.themeConfig.app.title }}
          </h6>
        </div>

        <!-- 👉 Address -->
        <p class="mb-1" style="font-size: 0.875rem;">
          <span style="font-weight: 600;">Client :</span> {{ props.documentData.client?.legalName }}
        </p>
        <p class="mb-0" style="font-size: 0.875rem;">
          <span style="font-weight: 600;">ICE :</span> {{ props.documentData.client?.ice }}
        </p>
      </div>

      <!-- 👉 Right Content -->
      <div>
        <h6 class="font-weight-medium" style="font-size: 1.2rem;">
          {{ props.documentType }}
        </h6>
        <p class="font-weight-regular mb-3" style="font-size: 1rem;">
          {{ props.documentData?.code }}
        </p>
        <p class="mb-1" style="font-size: 0.875rem;">
          <span>Date de création : </span>
          <span>{{ props.formatDateToddmmYYYY(props.documentData?.createdAt) }}</span>
        </p>

        <p v-if="props.documentType === 'Devis'" class="mb-0" style="font-size: 0.875rem;">
          <span>Date d'expiration : </span>
          <span>{{ props.formatDateToddmmYYYY(props.documentData?.validityDate) }}</span>
        </p>
      </div>
    </div>
    <!-- !SECTION -->
       <div style="height: 8px;"></div>

    <!-- 👉 quote Table -->
    <VTable class="return-preview-table border text-high-emphasis overflow-hidden mb-5">
      <thead>
        <tr>
          <th scope="col" style="font-size: 0.875rem;" 
              :style="props?.documentType === 'Bon de production' ? 'width: 25%' : props?.documentType === 'Demande de devis' ? 'width: 35%' : 'width: 70%'">
            Designation
          </th>
          <th v-if="props?.documentType === 'Demande de devis' || props?.documentType === 'Bon de production'" 
              scope="col" style="font-size: 0.875rem;" 
              :style="props?.documentType === 'Bon de production' ? 'width: 25%' : 'width: 35%'">
            Caractéristique
          </th>
          <th scope="col" class="text-center" style="font-size: 0.875rem; width: 15%;">PU</th>
          <th scope="col" class="text-center" style="font-size: 0.875rem; width: 15%;">Qte</th>
          <th v-if="showDiscountColumn" scope="col" class="text-center" style="font-size: 0.875rem; width: 15%;">Remise</th>
          <th v-if="props?.documentType !== 'Demande de devis'" scope="col" class="text-center" 
              style="font-size: 0.875rem;" 
              :style="props?.documentType === 'Bon de production' ? 'width: 20%' : 'width: 35%'">
            Montant HT
          </th>
        </tr>
      </thead>

      <tbody style="font-size: 0.875rem;">
        <tr v-for="item in props.documentData?.items" :key="item.id">
          <td :style="props?.documentType === 'Bon de production' ? 'width: 25%' : props?.documentType === 'Demande de devis' ? 'width: 35%' : 'width: 70%'">
            <div
              style="white-space: normal; overflow-wrap: break-word; word-break: break-word; padding: 12px 12px 12px 0;">
              {{ item.description }}
            </div>
          </td>
          <td v-if="props?.documentType === 'Demande de devis' || props?.documentType === 'Bon de production'" 
              :style="props?.documentType === 'Bon de production' ? 'width: 25%' : 'width: 35%'">
            <div
              style="white-space: normal; overflow-wrap: break-word; word-break: break-word; padding: 12px 12px 12px 0;">
              {{ item.characteristics }}
            </div>
          </td>
          <td class="text-center">{{ item.price }}</td>
          <td class="text-center">{{ item.quantity }}</td>

          <td v-if="showDiscountColumn" class="text-center">
            <span v-if="item?.discount != null && Number(item.discount) !== 0">{{ item.discount }}</span>
            <span v-else>-</span>
          </td>

          <td v-if="props?.documentType !== 'Demande de devis'" class="text-center">
            <div :style="props?.documentType === 'Bon de production' ? 'width: 100px;' : 'width: 130px;'">
              {{ item.amount }}
            </div>
          </td>
        </tr>
      </tbody>
    </VTable>

    <!-- 👉 Total -->
    <div v-if="props.documentType !== 'Bon de production'"
      class="d-flex justify-space-between flex-column flex-sm-row print-row mr-4">
      <div class="mb-2" style="flex: 1; max-width: 50%;">
        <div class="mb-2">
          <p v-if="props.documentData?.isTaxable" class="mb-1" style="font-size: 0.875rem; font-weight: 600;">Total TTC en lettres
          </p>
          <p v-else class="mb-1" style="font-size: 0.875rem; font-weight: 600;">Total HT en lettres:</p>
          <span class="d-block" style="font-size: 0.8125rem; max-width: 100%;">{{ props.documentData?.totalPhrase }}</span>
        </div>

        <div v-if="props.documentData[`${props.documentKey}Comment`]">
          <p class="mb-1" style="font-size: 0.875rem; font-weight: 600;">Commentaire </p>
          <span class="d-block" style="font-size: 0.8125rem; max-width: 100%;">{{
            props.documentData[`${props.documentKey}Comment`] }}</span>
        </div>
      </div>

      <div style="margin-right: -16px !important;">
        <table class="w-100">
          <tbody>
            <tr>
              <td class="pe-14" style="font-size: 0.875rem;">Total HT</td>
              <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                <span class="font-weight-medium" style="font-size: 0.875rem;">
                  &nbsp;&nbsp;&nbsp;
                  {{ props.documentData?.amount }} DH
                </span>
              </td>
            </tr>

            <tr v-if="props.documentData?.isTaxable">
              <td class="pe-14" style="font-size: 0.875rem;">Total TVA</td>
              <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                <span class="font-weight-medium" style="font-size: 0.875rem;">
                  {{ props.documentData?.taxAmount }} DH
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <VDivider v-if="props.documentData?.isTaxable" class="my-2" />

        <table class="w-100">
          <tbody v-if="props.documentData?.isTaxable">
            <tr>
              <td class="pe-14" style="font-size: 0.875rem;">Total TTC</td>
              <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                <span class="font-weight-medium" style="font-size: 0.875rem;">
                  {{ props.documentData?.finalAmount }} DH
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="spacer mb-5"></div>
    <div style="height: 8px;"></div>

    <div class="footer-wrapper">
      <VDivider class="border-dashed mb-2" />
      <DocumentsFooter class="document-footer" />
    </div>
  </VCard>
</template>

<style scoped>
.footer-wrapper {
  position: absolute;
  bottom: 16px;
  left: 32px;
  right: 32px;
}

.footer-wrapper .document-footer {
  position: relative;
  bottom: auto;
  left: auto;
}
</style>
