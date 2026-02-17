import { adaptDocumentData } from './adapter';
import { type DocumentType,DocType } from './config';
import { renderDocument } from './template';

export type { DocumentType ,DocType };

export function printDocument(
    documentType: DocumentType | DocType,
    data: any,
    isTaxable: boolean
): void {
    const printableData = adaptDocumentData(documentType, data, isTaxable);
    const pdf = renderDocument(documentType, printableData);
    pdf.save(`${documentType.replace(/ /g, '-')}-${printableData.code}.pdf`);
}
