import type { DocType, DocumentType } from './config';

export interface PrintableItem {
  description: string;
  characteristics?: string;
  unitPrice: number;
  quantity: number;
  discount?: number;
  total: number;
}

export interface PrintableDocument {
  code: string;
  createdAt: string;
  validityDate?: string;
  dueDate?: string;
  client: {
    name: string;
    ice: string;
  };
  items: PrintableItem[];
  isTaxable: boolean;
  totalHT: number;
  totalTVA: number;
  totalTTC: number;
  totalInWords: string;
  comment?: string;
  refundType?: string;
}

function formatDate(date: string | Date | undefined): string {
  if (!date) return '';
  const d = new Date(date);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

// Add helper to safely parse numbers and round to 2 decimals
function parsePrice(value: any): number {
  const num = Number(value) || 0;
  return Math.round(num * 100) / 100; // Rounds to 2 decimal places
}

export function adaptDocumentData(
  documentType: DocumentType | DocType,
  data: any,
  isTaxable: boolean
): PrintableDocument {

  // Map items with characteristics
  const items: PrintableItem[] = (data.items || []).map((item: any) => ({
    description: item.description || item.name || '',
    characteristics: item.characteristics || undefined,
    unitPrice: parsePrice(item.price || item.unitPrice),
    quantity: parsePrice(item.quantity),
    discount: item.discount ? parsePrice(item.discount) : undefined,
    total: parsePrice(item.amount || item.total || (Number(item.price) * Number(item.quantity))),
  }));

  // Client - use legalName
  const clientName = data.client?.legalName || data.client?.name || data.clientName || '';
  const clientIce = data.client?.ice || data.clientIce || '';

  // Totals - use your field names
  const totalHT = parsePrice(data.amount || data.totalHT);
  const totalTVA = parsePrice(data.taxAmount || data.totalTVA);
  const totalTTC = parsePrice(data.finalAmount || data.totalTTC);

  // Total in words - use totalPhrase
  const totalInWords = data.totalPhrase || data.totalInWords || '';

  // Comment field mapping
  const commentFields: Record<string, string> = {
    'Demande de devis': 'quoterequestComment',
    'Devis': 'quoteComment',
    'Bon de production': 'productionComment',
    'Bon de commande': 'orderComment',
    'Bon de livraison': 'deliveryComment',
    'Bon de sortie': 'outputComment',
    'Bon de retour': 'returnComment',
    'Facture': 'invoiceComment',
    'Reçu de commande': 'receiptComment',
    'Facture avoir': 'creditComment',
    'Bon de remboursement': 'refundComment',
  };

  const result: PrintableDocument = {
    code: data.code || '',
    createdAt: formatDate(data.createdAt),
    validityDate: data.validityDate ? formatDate(data.validityDate) : undefined,
    dueDate: data.dueDate ? formatDate(data.dueDate) : undefined,
    client: {
      name: clientName,
      ice: clientIce,
    },
    items,
    isTaxable: Boolean(data.isTaxable) || isTaxable,
    totalHT,
    totalTVA,
    totalTTC,
    totalInWords,
    comment: data[commentFields[documentType]] || data.comment || undefined,
    refundType: data.refundType || undefined,
  };

  return result;
}