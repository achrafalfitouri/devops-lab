import { RouteKey, RouteKeyId } from "./DocumentUtil";

export const tabs = [
  { title: 'Demande de devis', icon: 'tabler-file-description' },
  { title: 'Devis', icon: 'tabler-file-description' },
  { title: 'Bon de commande', icon: 'tabler-file-description' },
  { title: 'Bon de production', icon: 'tabler-file-description' },
  { title: 'Bon de sortie', icon: 'tabler-file-description' },
  { title: 'Bon de livraison', icon: 'tabler-file-description' },
  { title: 'Bon de retour', icon: 'tabler-file-description' },
  { title: 'Facture', icon: 'tabler-file-description' },
  { title: 'Reçu de commande', icon: 'tabler-file-description' },
  { title: 'Facture avoir', icon: 'tabler-file-description' },
  { title: 'Bon de remboursement', icon: 'tabler-file-description' },
]

// Mapping of document types to keys
export const documentTypeMapping: Record<any, string> = {
  'Demande de devis': 'quoterequest',
  'Devis': 'quote',
  'Bon de production': 'production',
  'Bon de commande': 'order',
  'Bon de livraison': 'delivery',
  'Bon de sortie': 'output',
  'Bon de retour': 'return',
  'Facture': 'invoice',
  'Reçu de commande': 'receipt',
  'Facture avoir': 'credit',
  'Bon de remboursement': 'refund',
};

export const routeMappings: Record<keyof typeof documentTypeMapping, RouteKeyId> = {
  'Demande de devis': 'documents-quoterequests-id',
  'Devis': 'documents-quotes-id',
  'Bon de production': 'documents-productionnotes-id',
  'Bon de commande': 'documents-ordernotes-id',
  'Bon de sortie': 'documents-outputnotes-id',
  'Bon de livraison': 'documents-deliverynotes-id',
  'Bon de retour': 'documents-returnnotes-id',
  'Facture': 'documents-invoices-id',
  'Reçu de commande': 'documents-orderreceipt-id',
  'Facture avoir': 'documents-invoicecredits-id',
  'Bon de remboursement': 'documents-refunds-id',
};
export const routeFormMappings: Record<keyof typeof documentTypeMapping, RouteKey> = {
  'Demande de devis': 'documents-quoterequests-form',
  'Devis': 'documents-quotes-form',
  'Bon de production': 'documents-productionnotes-form',
  'Bon de commande': 'documents-ordernotes-form',
  'Bon de sortie': 'documents-outputnotes-form',
  'Bon de livraison': 'documents-deliverynotes-form',
  'Bon de retour': 'documents-returnnotes-form',
  'Facture': 'documents-invoices-form',
  'Reçu de commande': 'documents-orderreceipt-form',
  'Facture avoir': 'documents-invoicecredits-form',
};

export const routeDocumentMappings: Record<keyof typeof documentTypeMapping, any> = {
  'Demande de devis': 'documents-quoterequests',
  'Devis': 'documents-quotes',
  'Bon de production': 'documents-productionnotes',
  'Bon de commande': 'documents-ordernotes',
  'Bon de sortie': 'documents-outputnotes',
  'Bon de livraison': 'documents-deliverynotes',
  'Bon de retour': 'documents-returnnotes',
  'Facture': 'documents-invoices',
  'Reçu de commande': 'documents-orderreceipt',
  'Facture avoir': 'documents-invoicecredits',
};

export const status = ['Brouillon', 'En attente', 'Rejeté', 'Validé', 'Terminé',]
export const productionNoteStatus = ['Brouillon', 'En cours', 'Annulé', 'Retourné', 'Perte', 'Validé', 'Terminé']
export const invoiceStatus = ['Brouillon', 'Payé', 'Non payé', 'Payé partiellement', 'Terminé']
export const orderReceiptStatus = ['Brouillon', 'Payé', 'Non payé', 'Payé partiellement', 'Terminé']
export const invoiceCreditStatus = ['Brouillon', 'Soldé']
export const refundStatus = ['Brouillon', 'Validé']
export const outputNoteStatus = ['Brouillon', 'En attente', 'Rejeté', 'Validé', 'Terminé']
export const delliveryNoteStatus = ['Brouillon', 'En attente', 'Rejeté', 'Retourné', 'Validé', 'Terminé']
export const statusItems = ['Validé', 'Retourné', 'A refaire'];

