import { addDeliveryNotes, updateDeliveryNotes, updateStatusDeliveryNotes } from '@/services/api/deliverynote';
import { addInvoices, updateInvoices, updateStatusInvoices } from '@/services/api/invoice';
import { addInvoiceCredits, updateInvoiceCredits, updateStatusInvoiceCredits } from '@/services/api/invoicecredits';
import { addOrderNotes, updateOrderNotes, updateStatusOrderNotes } from '@/services/api/ordernote';
import { addOrderReceipt, updateOrderReceipt, updateStatusOrderReceipt } from '@/services/api/orderrec';
import { addOutputNotes, updateOutputNotes, updateStatusOutputNotes } from '@/services/api/outputnote';
import { addProductionNotes, updateProductionNotes, updateStatusProductionNotes } from '@/services/api/productionnote';
import { addQuotes, updateQuotes, updateStatusQuotes } from '@/services/api/quote';
import { addQuoteRequests, updateQuoteRequests, updateStatusQuoteRequests } from '@/services/api/quoterequest';
import { addRefunds, updateRefunds, updateStatusRefunds } from '@/services/api/refund';
import { addReturnNotes, updateReturnNotes, updateStatusReturnNotes } from '@/services/api/returnnote';
import { Console } from 'console';
// 👉 Snackbar
const { showSnackbar } = useSnackbar();

export type RouteKey =
  | 'documents-quoterequests-form'
  | 'documents-quotes-form'
  | 'documents-ordernotes-form'
  | 'documents-productionnotes-form'
  | 'documents-outputnotes-form'
  | 'documents-deliverynotes-form'
  | 'documents-returnnotes-form'
  | 'documents-invoices-form'
  | 'documents-orderreceipt-form'
  | 'documents-invoicecredits-form'
  | 'documents-refunds-form';

  export type RouteKeyId =
  | 'documents-quoterequests-id'
  | 'documents-quotes-id'
  | 'documents-ordernotes-id'
  | 'documents-productionnotes-id'
  | 'documents-outputnotes-id'
  | 'documents-deliverynotes-id'
  | 'documents-returnnotes-id'
  | 'documents-invoices-id'
  | 'documents-orderreceipt-id'
  | 'documents-invoicecredits-id'
  | 'documents-refunds-id';

  export type RouteType = 'form' | 'id';

export const createFormMappings = (defaults: any, type: RouteType) => {
  const mappings = {   
    'documents-quoterequests': {
      defaults,
      addAction: addQuoteRequests,
      updateAction: updateQuoteRequests,
      updateStatus: updateStatusQuoteRequests,
      responseKey: 'quoterequest',
    },
    'documents-quotes': {
      defaults,
      addAction: addQuotes,
      updateAction: updateQuotes,
      updateStatus: updateStatusQuotes,
      responseKey: 'quote',
    },
    'documents-ordernotes': {
      defaults,
      addAction: addOrderNotes,
      updateAction: updateOrderNotes,
      updateStatus: updateStatusOrderNotes,
      responseKey: 'orderNote',
    },
    'documents-productionnotes': {
      defaults,
      addAction: addProductionNotes,
      updateAction: updateProductionNotes,
      updateStatus: updateStatusProductionNotes,
      responseKey: 'productionNote',
    },
    'documents-outputnotes': {
      defaults,
      addAction: addOutputNotes,
      updateAction: updateOutputNotes,
      updateStatus: updateStatusOutputNotes,
      responseKey: 'outputNote',
    },
    'documents-deliverynotes': {
      defaults,
      addAction: addDeliveryNotes,
      updateAction: updateDeliveryNotes,
      updateStatus: updateStatusDeliveryNotes,
      responseKey: 'deliveryNote',
    },
    'documents-returnnotes': {
      defaults,
      addAction: addReturnNotes,
      updateAction: updateReturnNotes,
      updateStatus: updateStatusReturnNotes,
      responseKey: 'returnNote',
    },
    'documents-invoices': {
      defaults,
      addAction: addInvoices,
      updateAction: updateInvoices,
      updateStatus: updateStatusInvoices,
      responseKey: 'invoice',
    },
    'documents-orderreceipt': {
      defaults,
      addAction: addOrderReceipt,
      updateAction: updateOrderReceipt,
      updateStatus: updateStatusOrderReceipt,
      responseKey: 'orderReceipt',
    },
    'documents-invoicecredits': {
      defaults,
      addAction: addInvoiceCredits,
      updateAction: updateInvoiceCredits,
      updateStatus: updateStatusInvoiceCredits,
      responseKey: 'invoiceCredit',
    },
    'documents-refunds': {
      defaults,
      addAction: addRefunds,
      updateAction: updateRefunds,
      updateStatus: updateStatusRefunds,
      responseKey: 'refund',
    },
  };

  type MappingType = typeof mappings[keyof typeof mappings];
  const result: Record<string, MappingType> = {};

  Object.entries(mappings).forEach(([key, value]) => {
    result[`${key}-${type}`] = value;
  });

  return result;
};

  const baseDocumentOrder = [
    { name: 'Demande de devis', baseRoute: 'documents-quoterequests', order: 1 },
    { name: 'Devis', baseRoute: 'documents-quotes', order: 2 },
    { name: 'Bon de commande', baseRoute: 'documents-ordernotes', order: 3 },
    { name: 'Bon de production', baseRoute: 'documents-productionnotes', order: 4 },
    { name: 'Bon de sortie', baseRoute: 'documents-outputnotes', order: 5 },
    { name: 'Bon de livraison', baseRoute: 'documents-deliverynotes', order: 6 },
    { name: 'Bon de retour', baseRoute: 'documents-returnnotes', order: 7 },
    { name: 'Facture', baseRoute: 'documents-invoices', order: 8 },
    { name: 'Reçu de commande', baseRoute: 'documents-orderreceipt', order: 9 },
    { name: 'Facture avoir', baseRoute: 'documents-invoicecredits', order: 10 },
    { name: 'Bon de remboursement', baseRoute: 'documents-refunds', order: 11 },
  ];
  
  export const documentOrder = baseDocumentOrder.map(doc => ({
    name: doc.name,
    route: `${doc.baseRoute}-form`,
    order: doc.order,
  }));
  
  export const documentOrderId = baseDocumentOrder.map(doc => ({
    name: doc.name,
    route: `${doc.baseRoute}-id`,
    order: doc.order,
  }));
  
 export const updateStatusId = async (
  newStatus: string,
  documentsFormDefaults: any,
  documentsStore: any,
  routeName: RouteKeyId,
  ids?: string[],
  id?: string,
  docType?: string
) => {
  try {
    // Better validation check
    const multiSelectDocTypes = ['Bon de livraison', 'Bon de production', 'Bon de sortie'];
    const singleSelectDocTypes = [ 'Demande de devis','Bon de commande', 'Devis', 'Facture', 'Facture avoir', 'Bon de remboursement', 'Reçu de commande'];
    
    if (multiSelectDocTypes.includes(docType || '')) {
      if (!ids || ids.length === 0) {
        showSnackbar('erreur de status, selectioner au moins un document', 'error');
        return;
      }
    } else if (singleSelectDocTypes.includes(docType || '')) {
      if (!id) {
        showSnackbar('erreur de status, selectioner au moins un document', 'error');
        return;
      }
    }

    const formMappings = createFormMappings(documentsFormDefaults, 'id');
    const currentMapping = formMappings[routeName];
    if (!currentMapping) {
      console.error('Unsupported route:', routeName);
      return;
    }

    const payload: DocumentCore = {
      ids,
      status: newStatus,
    };

    const response = await currentMapping.updateStatus(
      singleSelectDocTypes.includes(docType || '') ? id : ids, 
      payload
    );

    const updatedDocuments = response[currentMapping.responseKey];

    if (Array.isArray(updatedDocuments)) {
      updatedDocuments.forEach((updatedDocument: any) => {
        const index = documentsStore.DocumentCore.findIndex((doc: { id: any }) => doc.id === updatedDocument.id);
        if (index !== -1) {
          documentsStore.DocumentCore[index] = {
            ...documentsStore.DocumentCore[index],
            status: updatedDocument.status,
          };
        }
      });
    }

    showSnackbar(`Statut mis à jour vers ${newStatus}`, 'success');
  } catch (error) {
    console.error('Error updating status:', error);
    const err = error as any;
    showSnackbar(`${err.response?.data.message || "Erreur lors de la mise à jour du statut"}`, 'error');
  }
};
  
  
  export const navigateToNextDocument = async (
    docType: string,
    routeName: RouteKeyId,
    routeParamsId: string | number,
    GenerateDocument: (param: { ids: any[], id?: string | number  } | string | number) => Promise<any>, 
    selectedIds: any,
    router: ReturnType<typeof useRouter>,
    documentsStore: any,
    selectedStatus?: string,
    isTaxable?: boolean,
    selectedItemsIds?: any,
  ) => {

       if ((!selectedItemsIds || selectedItemsIds.length === 0) && (docType === 'Facture' ||docType === 'Reçu de commande'   )) {

        showSnackbar('error de géneration, selectioner au moins un article', 'error');
        return
      }

    const currentDocument = documentOrderId.find((doc) => doc.route === routeName);
    if (!currentDocument) return;
    
    let nextDocument;
  
    if (currentDocument.name === 'Bon de livraison') {
      if (selectedStatus === 'Validé') {
        nextDocument = documentOrderId.find((doc) =>
          isTaxable ? doc.name === 'Facture' : doc.name === 'Reçu de commande'
        );
      } else if (selectedStatus === 'Rejeté') {
        nextDocument = documentOrderId.find((doc) => doc.name === 'Bon de retour');
      }
    } else if (currentDocument.name === 'Facture' ) {
      nextDocument = documentOrderId.find((doc) => doc.name === 'Facture avoir');
    }
     else if (currentDocument.name === 'Facture avoir' || currentDocument.name === 'Reçu de commande') {
      nextDocument = documentOrderId.find((doc) => doc.name === 'Bon de remboursement');
    }
    else {
      nextDocument = documentOrderId.find((doc) => doc.order === currentDocument.order + 1);
    }
  
    if (!nextDocument) return;
  
    const documentTypeMapping: Record<string, string> = {
      'Bon de retour': 'returnNote',
      'Facture': 'invoice',
      'Reçu de commande': 'orderReceipt',
    };
    const documentKey = documentTypeMapping[nextDocument.name];
 
  
    try {
      const params = router.currentRoute.value.params;
      if (!('id' in params)) {
        console.error('ID not found in route params');
        return;
      }
  const data = await GenerateDocument(
    currentDocument.name === 'Devis' || currentDocument.name === 'Bon de commande' || currentDocument.name === 'Facture avoir' || currentDocument.name === 'Demande de devis'
      ? params.id
      : currentDocument.name === 'Facture' || currentDocument.name === 'Reçu de commande'
      ? { ids: selectedItemsIds, id: params.id }
      : { ids: selectedIds } 
  );
      let idNextDoc;
  
      switch (docType) {
        case 'Demande de devis':
          idNextDoc = data?.quote?.id;
          break;
        case 'Devis':
          idNextDoc = data?.orderNote?.id;
          break;
        case 'Bon de commande':
          idNextDoc = data?.productionNote?.id;
          break;
        case 'Bon de production':
          idNextDoc = data?.outputNote?.id;
          break;
        case 'Bon de sortie':
          idNextDoc = data?.deliveryNote?.id;
          break;
        case 'Bon de livraison':
          idNextDoc = data?.[documentKey]?.id;
          break;
        case 'Facture':
          idNextDoc =  data?.invoiceCredit?.id;
          break;
        case 'Reçu de commande':
          idNextDoc =  data?.refund?.id;
          break;
        case 'Facture avoir':
          idNextDoc =  data?.refund?.id;
          break;
      }
  
      if (idNextDoc) {
        router.push({ name: nextDocument.route as RouteKeyId, params: { id: idNextDoc } });
        documentsStore.setGenerateMode(true);
      } else {
        console.error(`Failed to get ID for document type: ${docType}`);
      }
    } catch (error) {
      const err = error as any; 
      showSnackbar(`${err.response?.data.message}`, 'error');    }
  };

  export const navigateToNextDocumentMultipleDeliveryNoteProcess = async (
    docType: string,
    routeName: RouteKeyId,
    routeParamsId: string | number | null,
    GenerateDocument: (param: { ids: any[], id?: string | number } | string | number) => Promise<any>,
    selectedIds: any,
    router: ReturnType<typeof useRouter>,
    documentsStore: any,
    selectedStatus?: string, // Now expects a single unified status: 'Validé' or 'Rejeté'
    isTaxable?: boolean,
    selectedItemsIds?: any,
  ) => {
  
    // Validation for required items
    if ((!selectedItemsIds || selectedItemsIds.length === 0) && (docType === 'Facture' || docType === 'Reçu de commande')) {
      showSnackbar('error de géneration, selectioner au moins un article', 'error');
      return;
    }
  
    const currentDocument = documentOrderId.find((doc) => doc.route === routeName);
    if (!currentDocument) return;
  
    let nextDocument;
  
   
  
    // Use the unified status for routing logic
    if (currentDocument.name === 'Bon de livraison') {
      if (selectedStatus === 'Validé') {
        nextDocument = documentOrderId.find((doc) =>
          isTaxable ? doc.name === 'Facture' : doc.name === 'Reçu de commande'
        );
      } else if (selectedStatus === 'Rejeté') {
        nextDocument = documentOrderId.find((doc) => doc.name === 'Bon de retour');
      }
    } else if (currentDocument.name === 'Facture') {
      nextDocument = documentOrderId.find((doc) => doc.name === 'Facture avoir');
    } else if (currentDocument.name === 'Facture avoir' || currentDocument.name === 'Reçu de commande') {
      nextDocument = documentOrderId.find((doc) => doc.name === 'Bon de remboursement');
    } else {
      nextDocument = documentOrderId.find((doc) => doc.order === currentDocument.order + 1);
    }
  
    if (!nextDocument) {
      console.warn('No next document found for current status and configuration');
      return;
    }
  
    const documentTypeMapping: Record<string, string> = {
      'Bon de retour': 'returnNote',
      'Facture': 'invoice',
      'Reçu de commande': 'orderReceipt',
    };
    const documentKey = documentTypeMapping[nextDocument.name];
  
    try {
      const params = router.currentRoute.value.params as any;
      const singleId = ('id' in params ? params.id : null) ?? null;
  
      const data = await GenerateDocument(
        currentDocument.name === 'Devis' || currentDocument.name === 'Bon de commande' || currentDocument.name === 'Facture avoir' || currentDocument.name === 'Demande de devis'
          ? singleId
          : currentDocument.name === 'Facture' || currentDocument.name === 'Reçu de commande'
          ? { ids: selectedItemsIds, id: singleId }
          : { ids: selectedIds }
      );
  
      let idNextDoc;
  
      switch (docType) {
        case 'Demande de devis':
          idNextDoc = data?.quote?.id;
          break;
        case 'Devis':
          idNextDoc = data?.orderNote?.id;
          break;
        case 'Bon de commande':
          idNextDoc = data?.productionNote?.id;
          break;
        case 'Bon de production':
          idNextDoc = data?.outputNote?.id;
          break;
        case 'Bon de sortie':
          idNextDoc = data?.deliveryNote?.id;
          break;
        case 'Bon de livraison':
          idNextDoc = data?.[documentKey]?.id;
          break;
        case 'Facture':
          idNextDoc = data?.invoiceCredit?.id;
          break;
        case 'Reçu de commande':
          idNextDoc = data?.refund?.id;
          break;
        case 'Facture avoir':
          idNextDoc = data?.refund?.id;
          break;
      }
  
      if (idNextDoc) {
        router.push({ name: nextDocument.route as RouteKeyId, params: { id: idNextDoc } });
        documentsStore.setGenerateMode(true);
      } else {
        console.error(`Failed to get ID for document type: ${docType}`);
      }
    } catch (error) {
      const err = error as any;
      showSnackbar(`${err.response?.data.message}`, 'error');
    }
  };
  
  export const updateNextDocumentButtonVisibility = (
    routeName: RouteKeyId,
    selectedStatus: string,
    nextDocumentName: Ref<string | null | undefined>,
    isNextDocumentButtonVisible: Ref<boolean>,
    isTaxable?: any,
    allValidated?: any,
    atLeastOneReturnedOrRedo?: any,
    allReturned?: any,
    allRedo?: any,
    redoAndReturned? : any
  ) => {
    const currentDocument = documentOrderId.find((doc) => doc.route === routeName);
    if (!currentDocument) return;
  
    const nextDocument = documentOrderId.find((doc) => doc.order === currentDocument.order + 1);
 
    // Default values
    nextDocumentName.value = null;
    isNextDocumentButtonVisible.value = false;
    switch (currentDocument.name) {
      case 'Bon de livraison':
        nextDocumentName.value = 'Facture';

        if (allValidated) {
          nextDocumentName.value = isTaxable ? 'Facture' : 'Reçu de commande';
        } else if (allReturned || allRedo  || redoAndReturned) {
          nextDocumentName.value = 'Bon de retour';
        }
          else if (atLeastOneReturnedOrRedo) {
            nextDocumentName.value = isTaxable
              ? 'Facture et bon de retour'
              : 'Reçu de commande et bon de retour';
          }
        break;
  
      case 'Facture':
          nextDocumentName.value = 'Facture avoir';
       
        break;
  
      case 'Facture avoir':
        nextDocumentName.value = 'Bon de remboursement';
        break;
  
      case 'Reçu de commande':
          nextDocumentName.value = 'Bon de remboursement';
       
        break;
  
      default:
        nextDocumentName.value = nextDocument?.name || null;
        break;
    }
  
    isNextDocumentButtonVisible.value = !!nextDocumentName.value;
  };
  
