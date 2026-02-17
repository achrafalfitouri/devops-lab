export type DocumentType =
    | 'quote-request'
    | 'quote'
    | 'production-note'
    | 'order-note'
    | 'delivery-note'
    | 'output-note'
    | 'return-note'
    | 'invoice'
    | 'order-receipt'
    | 'invoice-credit'
    | 'refund';

export type DocType = 'Demande de devis' | 'Devis' | 'Bon de production' | 'Bon de commande' | 'Bon de livraison' | 'Bon de sortie' | 'Bon de retour' | 'Facture' | 'Reçu de commande' | 'Facture avoir' | 'Bon de remboursement';
    
export interface DocumentConfig {
  title: string;
  showRefundType?: boolean;
}

const configs: Record<DocumentType, DocumentConfig> = {
  'quote-request': { title: 'Demande de devis' },
  'quote': { title: 'Devis' },
  'production-note': { title: 'Bon de production' },
  'order-note': { title: 'Bon de commande' },
  'delivery-note': { title: 'Bon de livraison' },
  'output-note': { title: 'Bon de sortie' },
  'return-note': { title: 'Bon de retour' },
  'invoice': { title: 'Facture' },
  'order-receipt': { title: 'Reçu de commande' },
  'invoice-credit': { title: 'Facture avoir' },
  'refund': { title: 'Bon de remboursement', showRefundType: true },
};

// Reverse lookup: title -> type
const titleToType: Record<string, DocumentType> = {
  'Demande de devis': 'quote-request',
  'Devis': 'quote',
  'Bon de production': 'production-note',
  'Bon de commande': 'order-note',
  'Bon de livraison': 'delivery-note',
  'Bon de sortie': 'output-note',
  'Bon de retour': 'return-note',
  'Facture': 'invoice',
  'Reçu de commande': 'order-receipt',
  'Facture avoir': 'invoice-credit',
  'Bon de remboursement': 'refund',
};

export function getConfig(type: DocumentType | string): DocumentConfig {
  // If it's a title, convert to type first
  const docType = titleToType[type] || type as DocumentType;
  return configs[docType];
}

export function getDocumentType(titleOrType: string): DocumentType {
  return titleToType[titleOrType] || titleOrType as DocumentType;
}
