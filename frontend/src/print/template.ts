import jsPDF from 'jspdf';
import type { DocumentConfig } from './config';
import { getConfig, getDocumentType, type DocumentType } from './config';
import type { PrintableDocument, PrintableItem } from './adapter';
import { 
  LOGO_BASE64, 
  LOGO_WIDTH, 
  LOGO_HEIGHT, 
  LOGO_X, 
  LOGO_Y, 
  SHOW_DIVIDER,
  DIVIDER_X,
  DIVIDER_WIDTH,
  DIVIDER_MARGIN_TOP,
  DIVIDER_THICKNESS,
  SPACE_AFTER_DIVIDER 
} from './companyLogo';

// A4 dimensions in points
const PAGE_WIDTH = 595.28;
const PAGE_HEIGHT = 841.89;
const MARGIN = 40;

// Table takes full width from margin to margin
const TABLE_WIDTH = PAGE_WIDTH - (MARGIN * 2);

// Styling
const BORDER_COLOR = '#000000';
const FONT_NORMAL = 10;
const FONT_SMALL = 9;

interface Context {
  pdf: jsPDF;
  config: DocumentConfig;
  data: PrintableDocument;
  y: number;
  page: number;
  tableX: number;
  tableWidth: number;
}

function newPage(ctx: Context): void {
  ctx.pdf.addPage();
  ctx.page++;
  ctx.y = MARGIN;
}

function checkPageBreak(ctx: Context, neededHeight: number): void {
  if (ctx.y + neededHeight > PAGE_HEIGHT - MARGIN - 80) {
    newPage(ctx);
    drawTableHeader(ctx);
  }
}

// Format numbers with space as thousand separator - preserves decimals
function formatNumber(num: number): string {
  const formatted = num.toFixed(2);
  const [integer, decimal] = formatted.split('.');
  const integerWithSpaces = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  return decimal && decimal !== '00' ? `${integerWithSpaces}.${decimal}` : integerWithSpaces;
}

function drawHeader(ctx: Context): void {
  const { pdf, config, data } = ctx;

  // Logo
  if (LOGO_BASE64) {
    try {
      pdf.addImage(LOGO_BASE64, 'PNG', LOGO_X, LOGO_Y, LOGO_WIDTH, LOGO_HEIGHT);
    } catch (e) {
      console.error('Logo error:', e);
    }
  }

  // Divider (if you want to keep it)
  if (SHOW_DIVIDER) {
    pdf.setDrawColor(BORDER_COLOR);
    pdf.setLineWidth(DIVIDER_THICKNESS);
    const dividerY = LOGO_Y + LOGO_HEIGHT + DIVIDER_MARGIN_TOP;
    pdf.line(DIVIDER_X, dividerY, DIVIDER_X + DIVIDER_WIDTH, dividerY);
  }

  // Prepare lines and measure widths
  pdf.setFontSize(18);
  pdf.setFont('helvetica', 'bold');
  const title = config.title.toUpperCase();
  const titleWidth = pdf.getTextWidth(title);

  pdf.setFontSize(FONT_NORMAL);
  pdf.setFont('helvetica', 'normal');
  const code = `N° : ${data.code}`;
  const codeWidth = pdf.getTextWidth(code);
  const date = `Date : ${data.createdAt}`;
  const dateWidth = pdf.getTextWidth(date);

  // Find the max width
  const maxWidth = Math.max(titleWidth, codeWidth, dateWidth);

  // Set the left X so all lines start at the same place, flush right
  const rightX = PAGE_WIDTH - MARGIN;
  const leftX = rightX - maxWidth;

  // Draw title
  pdf.setFontSize(18);
  pdf.setFont('helvetica', 'bold');
  pdf.text(title, leftX, MARGIN + 30, { align: 'left' }); // was MARGIN + 10

  // Draw code
  pdf.setFontSize(FONT_NORMAL);
  pdf.setFont('helvetica', 'normal');
  pdf.text(code, leftX, MARGIN + 48, { align: 'left' }); // was MARGIN + 28

  // Draw date
  pdf.text(date, leftX, MARGIN + 64, { align: 'left' }); // was MARGIN + 44

  // Set ctx.y for next section
  ctx.y = Math.max(LOGO_Y + LOGO_HEIGHT + DIVIDER_MARGIN_TOP + SPACE_AFTER_DIVIDER, MARGIN + 74); // was MARGIN + 54
}

function drawClient(ctx: Context): void {
  const { pdf, data } = ctx;

  pdf.setFontSize(FONT_NORMAL);
  pdf.setFont('helvetica', 'normal');
  pdf.text(`CLIENT : ${data.client.name}`, MARGIN, ctx.y);

  ctx.y += 14;

  pdf.setFont('helvetica', 'normal');
  pdf.text(`ICE : ${data.client.ice}`, MARGIN, ctx.y);

  ctx.y += 24;
}

// Check if any item has discount > 0
function hasAnyDiscount(data: PrintableDocument): boolean {
  return data.items.some(item => item.discount && item.discount > 0);
}

// Check if any item has price > 0
function hasAnyPrice(data: PrintableDocument): boolean {
  return data.items.some(item => item.unitPrice && item.unitPrice > 0);
}

// Check if any item has quantity > 0
function hasAnyQuantity(data: PrintableDocument): boolean {
  return data.items.some(item => item.quantity && item.quantity > 0);
}

// Check if any item has total > 0
function hasAnyTotal(data: PrintableDocument): boolean {
  return data.items.some(item => item.total && item.total > 0);
}

// Fixed column widths for non-text columns
const COL_PRICE = 70;
const COL_QTY = 70;
const COL_REMISE = 70;
const COL_TOTAL = 90;
const COL_GAP = 4; 
const ROW_GAP = 0; 

function getColumns(data: PrintableDocument, config: DocumentConfig) {
  const isQuoteRequest = config.title === 'Demande de devis';
  const isProductionNote = config.title === 'Bon de production';
  const showRemise = hasAnyDiscount(data);
  const showPrice = hasAnyPrice(data);
  const showQty = hasAnyQuantity(data);
  const showTotal = hasAnyTotal(data);

  console.log('getColumns debug:', {
    isQuoteRequest,
    isProductionNote,
    showPrice,
    showQty,
    showTotal,
    showRemise,
  });

  const cols = [];

  if (isProductionNote) {
    // Production Note: Only Désignation + Caractéristiques + Quantité
    const usedWidth = COL_QTY;
    const remainingWidth = TABLE_WIDTH - usedWidth;
    const halfWidth = remainingWidth / 2;

    cols.push({ key: 'description', width: halfWidth });
    cols.push({ key: 'characteristics', width: halfWidth });
    cols.push({ key: 'quantity', width: COL_QTY });
  } else if (isQuoteRequest) {
    // Quote Request: Désignation + Caractéristiques, hide columns if 0
    let usedWidth = 0;
    if (showPrice) usedWidth += COL_PRICE;
    if (showQty) usedWidth += COL_QTY;
    if (showRemise) usedWidth += COL_REMISE;
    if (showTotal) usedWidth += COL_TOTAL;

    const remainingWidth = TABLE_WIDTH - usedWidth;
    const halfWidth = remainingWidth / 2;

    cols.push({ key: 'description', width: halfWidth });
    cols.push({ key: 'characteristics', width: halfWidth });

    if (showPrice) cols.push({ key: 'unitPrice', width: COL_PRICE });
    if (showQty) cols.push({ key: 'quantity', width: COL_QTY });
    if (showRemise) cols.push({ key: 'remise', width: COL_REMISE });
    if (showTotal) cols.push({ key: 'total', width: COL_TOTAL });
  } else {
    // Other documents: always show all columns, full width
    const usedWidth = COL_PRICE + COL_QTY + (showRemise ? COL_REMISE : 0) + COL_TOTAL;
    const descWidth = TABLE_WIDTH - usedWidth;

    cols.push({ key: 'description', width: descWidth });
    cols.push({ key: 'unitPrice', width: COL_PRICE });
    cols.push({ key: 'quantity', width: COL_QTY });
    if (showRemise) cols.push({ key: 'remise', width: COL_REMISE });
    cols.push({ key: 'total', width: COL_TOTAL });
  }

  console.log('columns:', cols.map(c => ({ key: c.key, width: c.width })));
  return cols;
}

function drawTableHeader(ctx: Context): void {
  const { pdf, data, config } = ctx;
  const cols = getColumns(data, config);
  const h = 25;
  const x = ctx.tableX;

  pdf.setDrawColor(BORDER_COLOR);
  pdf.setLineWidth(0.5);

  // Draw each column as separate box with gap
  let currentX = x;
  const labels: Record<string, string> = {
    description: 'Désignation',
    characteristics: 'Caractéristiques',
    unitPrice: 'Prix U',
    quantity: 'Qté',
    remise: 'Remise',
    total: 'Prix Total',
  };

  pdf.setFontSize(FONT_SMALL);
  pdf.setFont('helvetica', 'bold');

  cols.forEach((col, i) => {
    const colWidth = i === cols.length - 1 ? col.width : col.width - COL_GAP;
    pdf.rect(currentX, ctx.y, colWidth, h);
    pdf.text(labels[col.key], currentX + colWidth / 2, ctx.y + 16, { align: 'center' });
    currentX += col.width;
  });

  ctx.y += h;
}

// Fixed row height for consistent padding
const MIN_ROW_HEIGHT = 0;
const ROW_PADDING_TOP = 12;
const ROW_PADDING_BOTTOM = 12;

function drawTableRow(ctx: Context, item: PrintableItem, isLast: boolean): void {
  const { pdf, data, config } = ctx;
  const x = ctx.tableX;
  const cols = getColumns(data, config);
  const isQuoteRequest = config.title === 'Demande de devis';
  const isProductionNote = config.title === 'Bon de production';

  pdf.setFontSize(FONT_SMALL);
  pdf.setFont('helvetica', 'normal');

  // Calculate description lines
  const descCol = cols.find(c => c.key === 'description');
  const maxDescWidth = (descCol?.width || 200) - 16 - COL_GAP;
  const descLines = pdf.splitTextToSize(item.description, maxDescWidth);

  // Calculate characteristics lines (for quote request and production note)
  let charLines: string[] = [];
  if (isQuoteRequest || isProductionNote) {
    const charCol = cols.find(c => c.key === 'characteristics');
    if (charCol && item.characteristics) {
      const maxCharWidth = charCol.width - 16 - COL_GAP;
      pdf.setFont('helvetica', 'normal');
      pdf.setFontSize(FONT_SMALL);
      charLines = pdf.splitTextToSize(item.characteristics, maxCharWidth);
    }
  }

  const lineHeight = 11; 
  const textBaselineOffset = 9; 

  // Calculate actual visual content height
  // First line needs baseline offset, each additional line adds lineHeight
  const descContentHeight = descLines.length > 0 ? textBaselineOffset + (descLines.length - 1) * lineHeight : 0;
  const charContentHeight = charLines.length > 0 ? textBaselineOffset + (charLines.length - 1) * lineHeight : 0;
  const contentHeight = Math.max(descContentHeight, charContentHeight);

  // Row height: padding + content + padding
  const h = Math.max(MIN_ROW_HEIGHT, ROW_PADDING_TOP + contentHeight + ROW_PADDING_BOTTOM);

  checkPageBreak(ctx, h);

  pdf.setDrawColor(BORDER_COLOR);
  pdf.setLineWidth(0.5);

  // Draw each column as separate box with gap
  let currentX = x;
  cols.forEach((col, i) => {
    const colWidth = i === cols.length - 1 ? col.width : col.width - COL_GAP;
    pdf.rect(currentX, ctx.y, colWidth, h);
    currentX += col.width;
  });

  // Draw description - starts at exact padding distance from top
  pdf.setFontSize(FONT_SMALL);
  pdf.setFont('helvetica', 'normal');
  const descY = ctx.y + ROW_PADDING_TOP;
  
  // Draw each line - first line at baseline offset, then add lineHeight for each
  descLines.forEach((line: string, i: number) => {
    pdf.text(line, x + 8, descY + textBaselineOffset + (i * lineHeight));
  });

  // Draw characteristics (if quote request or production note) - same padding approach
  if (isQuoteRequest || isProductionNote) {
    const descColWidth = descCol?.width || 200;
    const charCol = cols.find(c => c.key === 'characteristics');
    if (charCol && charLines.length > 0) {
      pdf.setFont('helvetica', 'normal');
      pdf.setFontSize(FONT_SMALL);
      
      const charY = ctx.y + ROW_PADDING_TOP;
      
      // Draw each line
      charLines.forEach((line: string, i: number) => {
        pdf.text(line, x + descColWidth + 8, charY + textBaselineOffset + (i * lineHeight));
      });
    }
  }

  // Draw values - vertically centered
  pdf.setFontSize(FONT_SMALL);
  pdf.setFont('helvetica', 'normal');
  const centerY = ctx.y + h / 2 + 4;

  currentX = x;
  cols.forEach((col, i) => {
    const colWidth = i === cols.length - 1 ? col.width : col.width - COL_GAP;

    if (col.key === 'description' || col.key === 'characteristics') {
      currentX += col.width;
      return;
    }

    const cellCenterX = currentX + colWidth / 2;

    if (col.key === 'unitPrice') {
      pdf.text(formatNumber(item.unitPrice), cellCenterX, centerY, { align: 'center' });
    } else if (col.key === 'quantity') {
      pdf.text(formatNumber(item.quantity), cellCenterX, centerY, { align: 'center' });
    } else if (col.key === 'remise') {
      const remiseText = item.discount && item.discount > 0 ? formatNumber(item.discount) : '-';
      pdf.text(remiseText, cellCenterX, centerY, { align: 'center' });
    } else if (col.key === 'total') {
      pdf.text(formatNumber(item.total), cellCenterX, centerY, { align: 'center' });
    }

    currentX += col.width;
  });

  ctx.y += h + (isLast ? 0 : ROW_GAP);
}

function drawTotals(ctx: Context): void {
  const { pdf, data, config } = ctx;
  const x = ctx.tableX;
  const h = 25;
  const cols = getColumns(data, config);

  // Skip totals entirely for Bon de production
  if (config.title === 'Bon de production') {
    ctx.y += 20;
    return;
  }

  // Check if we have totals to show
  const showTotal = hasAnyTotal(data);
  if (!showTotal) {
    ctx.y += 20;
    return;
  }

  // Calculate totals position (last 2 columns)
  const lastCol = cols[cols.length - 1];
  const secondLastCol = cols[cols.length - 2];
  const lastColWidth = lastCol.width;
  const secondLastColWidth = secondLastCol.width - COL_GAP;
  
  const labelX = x + TABLE_WIDTH - lastCol.width - secondLastCol.width;
  const labelW = secondLastColWidth;
  const valueX = x + TABLE_WIDTH - lastCol.width;
  const valueW = lastColWidth;

  const totalsStartY = ctx.y;

  pdf.setDrawColor(BORDER_COLOR);
  pdf.setLineWidth(0.5);

  // TOTAL HT
  pdf.rect(labelX, ctx.y, labelW, h);
  pdf.rect(valueX, ctx.y, valueW, h);
  pdf.setFontSize(FONT_SMALL);
  pdf.setFont('helvetica', 'bold');
  pdf.text('TOTAL HT', labelX + labelW / 2, ctx.y + 16, { align: 'center' });
  pdf.setFont('helvetica', 'normal');
  pdf.text(formatNumber(data.totalHT), valueX + valueW / 2, ctx.y + 16, { align: 'center' });
  ctx.y += h; 

  // TVA
  if (data.isTaxable && data.totalTVA > 0) {
    pdf.rect(labelX, ctx.y, labelW, h);
    pdf.rect(valueX, ctx.y, valueW, h);
    pdf.setFont('helvetica', 'bold');
    pdf.text('TVA', labelX + labelW / 2, ctx.y + 16, { align: 'center' });
    pdf.setFont('helvetica', 'normal');
    pdf.text(formatNumber(data.totalTVA), valueX + valueW / 2, ctx.y + 16, { align: 'center' });
    ctx.y += h; 
  }

  // TOTAL TTC
  pdf.rect(labelX, ctx.y, labelW, h);
  pdf.rect(valueX, ctx.y, valueW, h);
  pdf.setFont('helvetica', 'bold');
  pdf.text('TOTAL TTC', labelX + labelW / 2, ctx.y + 16, { align: 'center' });
  pdf.setFont('helvetica', 'normal');
  pdf.text(formatNumber(data.totalTTC), valueX + valueW / 2, ctx.y + 16, { align: 'center' });
  ctx.y += h;

  // Total TTC en lettres
  if (data.totalInWords) {
    pdf.setFontSize(FONT_NORMAL);
    pdf.setFont('helvetica', 'bold');
    pdf.text('Total TTC en lettres', x + 10, totalsStartY + 30);

    pdf.setFont('helvetica', 'normal');
    pdf.text(data.totalInWords, x + 10, totalsStartY + 52);
  }

  ctx.y += 20;
}

function drawComment(ctx: Context): void {
  const { pdf, data } = ctx;

  if (!data.comment) return;

  checkPageBreak(ctx, 40);

  pdf.setFontSize(FONT_SMALL);
  pdf.setFont('helvetica', 'italic');
  const lines = pdf.splitTextToSize(data.comment, TABLE_WIDTH);
  pdf.text(lines, MARGIN, ctx.y);

  ctx.y += lines.length * 12 + 15;
}

function drawRefundType(ctx: Context): void {
  const { pdf, config, data } = ctx;

  if (!config.showRefundType || !data.refundType) return;

  checkPageBreak(ctx, 25);

  pdf.setFontSize(FONT_SMALL);
  pdf.setFont('helvetica', 'bold');
  pdf.text('Mode de remboursement : ', MARGIN, ctx.y);

  const labelW = pdf.getTextWidth('Mode de remboursement : ');
  pdf.setFont('helvetica', 'normal');
  pdf.text(data.refundType, MARGIN + labelW, ctx.y);

  ctx.y += 20;
}

function drawFooter(ctx: Context): void {
  const { pdf } = ctx;
  const footerY = PAGE_HEIGHT - 55;

  pdf.setFontSize(FONT_SMALL);
  pdf.setFont('helvetica', 'bold');
  pdf.setTextColor(0, 0, 0);

  pdf.text(
    '360PRINT SARL AU ( Digital printing, Packaging, Sewing & Embroidery, Consulting, Signage )',
    PAGE_WIDTH / 2,
    footerY,
    { align: 'center' }
  );

  pdf.setFont('helvetica', 'normal');
  pdf.setFontSize(8);

  const lines = [
    'Immeuble n°5, Rue Capitaine Audibere Camp El Ghoul Gueliz Marrakech.',
    'Tél : 08 08 68 03 80 - 06 70 03 60 40 - contact@360print.ma',
    'IF : 53579478 - ICE : 003206806000087 - RC : 132591 - CNSS : 4879852',
  ];

  lines.forEach((line, i) => {
    pdf.text(line, PAGE_WIDTH / 2, footerY + 11 + i * 10, { align: 'center' });
  });
}

export function renderDocument(documentType: DocumentType | string, data: PrintableDocument): jsPDF {
  const pdf = new jsPDF({ 
    orientation: 'portrait', 
    unit: 'pt', 
    format: 'a4',
    compress: true
  });
  
  const actualType = getDocumentType(documentType);
  const config = getConfig(actualType);

  const ctx: Context = {
    pdf,
    config,
    data,
    y: MARGIN,
    page: 1,
    tableX: MARGIN,
    tableWidth: TABLE_WIDTH,
  };

  drawHeader(ctx);
  drawClient(ctx);
  drawTableHeader(ctx);

  data.items.forEach((item, i) => {
    drawTableRow(ctx, item, i === data.items.length - 1);
  });

  drawTotals(ctx);
  drawComment(ctx);
  drawRefundType(ctx);

  // Footer on all pages
  for (let i = 1; i <= ctx.page; i++) {
    pdf.setPage(i);
    drawFooter(ctx);
  }

  return pdf;
}