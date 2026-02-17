import { defineStore } from 'pinia';
import { useStorage } from '@vueuse/core';
import type { DocumentCore } from '@services/models';
import { set } from 'lodash';

export const useDocumentCoreStore = defineStore('DocumentCoreStore', {
  state: () => ({
    DocumentCore: [] as DocumentCore[],
    selectedDocumentCore: JSON.parse(
      useStorage('selectedDocumentCore', '[]').value
    ) as any | null , // Persist the selected document in local storage
    mode: useStorage('documentMode', 'add').value, // Persist the mode in local storage
    generate: useStorage('generate', false).value,
    quoteId: useStorage<string | null>('quoteId', null).value,
    orderNoteId : useStorage<string | null>('orderNoteId', null).value,
    clientId : useStorage<string | null>('clientId', null).value,
    docType : useStorage<string | null>('docType', null).value,
    status : useStorage<string | null>('status', null).value,
    shouldVerify : useStorage<boolean>('shouldVerify', false).value,
    newProductionNote : useStorage<boolean>('newProductionNote', false).value,


    
  }),
  actions: {
    setAddMode() {
      this.mode = 'add';
      this.selectedDocumentCore = null;
      localStorage.removeItem('selectedDocumentCore'); // Clear document when switching to add mode
      localStorage.setItem('documentMode', this.mode); // Persist mode
    },

    setEditMode(DocumentCore: DocumentCore) {
      this.mode = 'edit';
      this.selectedDocumentCore = DocumentCore;
      localStorage.setItem(
        'selectedDocumentCore',
        JSON.stringify(DocumentCore)
      ); // Persist selected document
      localStorage.setItem('documentMode', this.mode); // Persist mode
    },
    setPreviewMode() {
      this.mode = 'preview';
      this.generate = false;
      localStorage.removeItem('generate'); // Clear generate value
      localStorage.setItem('documentMode', this.mode); // Persist mode
    },

    closeDrawer() {
      this.selectedDocumentCore = null;
      this.mode = 'add';
      this.generate = false;
      localStorage.removeItem('selectedDocumentCore'); // Clear stored document
      localStorage.setItem('documentMode', this.mode); // Persist mode
    },
    setGenerateMode(value: boolean) {
      this.generate = value; // Explicitly set the generate value
      localStorage.setItem('generate', JSON.stringify(this.generate)); // Persist generate
      
    },

    setQuoteId(quoteId: string | null) {
      this.quoteId = quoteId;
      localStorage.setItem('quoteId', JSON.stringify(this.quoteId)); // Persist quoteId
    },

    setOrderNoteId(orderNoteId: string | null) {
      this.orderNoteId = orderNoteId;
      localStorage.setItem('orderNoteId', JSON.stringify(this.orderNoteId)); // Persist orderNoteId
    },
     
    setClientId(clientId: string | null) {
      this.clientId = clientId;
      localStorage.setItem('clientId', JSON.stringify(this.clientId)); // Persist clientId
    },

    setDocType(docType: string | null) {
      this.docType = docType;
      localStorage.setItem('docType', JSON.stringify(this.docType)); // Persist docType
    },
    setStatus(status: string | null) {
      this.status = status;
      localStorage.setItem('status', JSON.stringify(this.status)); 
    },

    setShouldVerify(shouldVerify: boolean) {
      this.shouldVerify = shouldVerify;
      localStorage.setItem('shouldVerify', JSON.stringify(this.shouldVerify)); 
    },
    setNewProductionNote(newProductionNote: boolean) {
      this.newProductionNote = newProductionNote;
      localStorage.setItem('newProductionNote', JSON.stringify(this.newProductionNote)); 
    }


  },
});
