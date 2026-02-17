
type NullableString = string | null;
type NullableArray = [] | null;
type Nullableboolean = boolean | 0 | 1 | null;
type NullableDate = Date | null;
type NullableInteger = number | null;


export type Role = {
  name: string;
  description: NullableString;
};
export type CashRegi = {
  name: string;
};

export type entity = {
  id: string;
  createdAt: NullableString;
  updatedAt: NullableString;
}

// 👉 User Model 
export type User = entity & {
  password: NullableString;
  photo: NullableString;
  lastName: NullableString;
  firstName: NullableString;
  cin: NullableString;
  phone: NullableString;
  email: NullableString;
  birthdate: NullableDate;
  title: NullableString;
  titleId: NullableString;
  permissions: NullableString;
  status: Nullableboolean;
  code: NullableString;
  gender: NullableString;
  roles: Role[] | null
  cashregisters: CashRegi[] | null,
  fullName: NullableString;
  passwordRequestCheck: Nullableboolean;
  number: NullableString;
};

// 👉 User Filter Params Model
export type UserFilterParms = {
  searchQuery: NullableString;
  selectedTitle: NullableString;
  selectedRole: NullableString;
  selectedStatus: NullableString;
};

// 👉 Client Model 
export type Client = entity & {
  logo: NullableString;
  legalName: NullableString;
  tradeName: NullableString;
  code: NullableString;
  phoneNumber: NullableString;
  email: NullableString;
  city: NullableString;
  address: NullableString;
  ice: NullableString;
  if: NullableString;
  tp: NullableString;
  type: NullableString;
  gamut: { name: string } | NullableString;
  status: { name: string } | NullableString;
  businessSector: { name: string } | NullableString;
  businessSectorId: NullableString;
  statusId: NullableString;
  gamutId: NullableString;
  clientTypeId: NullableString;
  cityId: NullableString;
  rc: NullableString;
  balance?: NullableString;
};

// 👉 Client Filter Params Model
export type ClientFilterParms = {
  searchQuery: NullableString;
  selectedType: NullableString;
  selectedGamut: NullableString;
  selectedStatus: NullableString;
  selectedCity: NullableString;
  selectedBusinessSector: NullableString;
};

// 👉 CashRegister  Model
export type CashRegister = entity & {
  name: NullableString;
  code: NullableString;
  users: User[] | null;
  status: Nullableboolean;
  managedBy: Array<{ id: string; fullName: string }>;

};

export type CashRegisterFilterParms = {
  selectedCashRegister: NullableString;
  selectedDate: NullableString;

};
// 👉 Transaction  Model
export type Transaction = entity & {
  name: NullableString;
  amount: NullableInteger;
  comment: NullableString;
  date: NullableDate | string;
  cashRegister: NullableString;
  cashTransactionType: NullableString;
  cashRegisterId: NullableString;
  cashTransactionTypeId: NullableString;
  client: NullableString;
  clientId: NullableString;
  targetUserId: NullableString;
  targetUser: NullableString;
  targetCashRegisterId: NullableString;
  targetCashRegister: NullableString;
  refundNote: NullableString;
  refundNoteId: NullableString;
  balanceReset: Nullableboolean;
  seller: NullableString;
  bank: NullableString;

};

export type TransactionFilterParms = {
  searchQuery: NullableString;
  selectedTransactionType: NullableString;
  selectedCashRegister: NullableString;
  selectedUser: NullableString;
  range: NullableString

};



// 👉  Document Model for document types
export type DocumentCore = entity & {
  code: NullableString;
  amount: NullableString;
  isTaxable: Nullableboolean;
  taxAmount: NullableString;
  finalAmount: NullableString;
  totalPhrase: NullableString;
  totalPhraseHt: NullableString;
  status: NullableString;
  documentsItems: DocumentItems[];
  items: DocumentItems[];
  clientId: NullableString;
  quoteComment: NullableString;
  orderComment: NullableString;
  invoiceComment: NullableString;
  returnComment: NullableString;
  outputComment: NullableString;
  deliveryComment: NullableString;
  productionComment: NullableString;
  receiptComment: NullableString;
  creditComment: NullableString;
  refundComment: NullableString;
  quoterequestComment: NullableString;
  clientSelect: NullableString,
  ice: NullableString,
  client: {
    ice: NullableString,
    id: ''
    legalName: NullableString,
    balance: NullableInteger
  }
  payedAmount: NullableInteger;
  totalToPay: NullableInteger;

}


// 👉 QuoteRequests Model
export type QuoteRequests = DocumentCore;

export type QuoteRequestsFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedDate: NullableString;
  selectedStatus: NullableString;
};
// 👉 Quotes  Model
export type Quotes = DocumentCore & {
  validityDate: NullableString;

};

export type QuotesFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedDate: NullableString;
  selectedStatus: NullableString;
};

// 👉 OrderNotes  Model
export type OrderNotes = DocumentCore & {
  productionNote: NullableString;
  orderUser: NullableString;
  quote: NullableString;
  orderUserId: NullableString;
  quoteId: NullableString;
};

export type OrderNotesFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedStatus: NullableString;
};

// 👉 ProductionNotes  Model
export type ProductionNotes = DocumentCore & {
  productionNote: NullableString;
  productionUser: NullableString;
  order: NullableString;
  productionUserId: NullableString;
  orderId: NullableString;

};


export type ProductionNotesFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedStatus: NullableString;

};

// 👉 OutputNotes  Model
export type OutputNotes = DocumentCore & {
  outputNote: NullableString;
  productionNote: NullableString;
  outputUser: NullableString;
  productionNoteId: NullableString;
  outputUserId: NullableString;
  outputDate: NullableString;

};

export type OutputNotesFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedDate: NullableString;
  selectedStatus: NullableString;
};

// 👉 DeliveryNotes  Model
export type DeliveryNotes = DocumentCore & {
  deliveryNote: NullableString;
  outputNote: NullableString;
  deliveryUser: NullableString;
  outputNoteId: NullableString;
  deliveryUserId: NullableString;

};

export type DeliveryNotesFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedStatus: NullableString;
  selectedTax: NullableString;

};

// 👉 ReturnNotes  Model
export type ReturnNotes = DocumentCore & {
  note: NullableString;
  deliveryNote: NullableString;
  returnUser: NullableString;
  deliveryNoteId: NullableString;
  returnUserId: NullableString;
};

export type ReturnNotesFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedStatus: NullableString;
};

// 👉 Invoices  Model
export type Invoices = DocumentCore & {
  dueDate: NullableString;
  discountedAmount: NullableInteger;
  clientBalance: NullableInteger;
};

export type InvoicesFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedDate: NullableString;
  selectedStatus: NullableString;

};

// 👉 Document Items  Model
export type DocumentItems = entity & {
  description: string | number | symbol | undefined | null;
  characteristics: string | number | symbol | undefined | null;
  price: NullableInteger;
  quantity: NullableInteger;
  undiscountedAmount: NullableInteger;
  discount: NullableInteger;
  amount: NullableInteger;
  productionNoteId: NullableString;
  outputNoteId: NullableString;
  deliveryNoteId: NullableString;
  returnNoteId: NullableString;
  invoiceId: NullableString;
  order: NullableInteger,
  index: NullableInteger;

};

// 👉 Contacts  Model
export type Contacts = entity & {
  firstName: NullableString;
  lastName: NullableString;
  fullName: NullableString;
  title: NullableString;
  phone: NullableString;
  email: NullableString;
  clientId: NullableString;
  client: NullableString;

};


// 👉 OrderReciept  Model
export type OrderReceipt = DocumentCore & {
  dueDate: NullableString;
  discountedAmount: NullableInteger;
};

export type OrderReceiptFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedDate: NullableString;
  selectedStatus: NullableString;

};
// 👉 InvoiceCredits  Model
export type InvoiceCredits = DocumentCore & {
  dueDate: NullableString;
  discountedAmount: NullableInteger;
};

export type InvoiceCreditsFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedDate: NullableString;
  selectedStatus: NullableString;

};

// 👉 Refunds  Mode
export type Refunds = DocumentCore & {
  type: NullableString;
  paymentType: {};
  paymentTypeId: NullableString;
};

export type RefundsFilterParms = {
  searchQuery: NullableString;
  selectedClient: NullableString;
  selectedUser: NullableString;
  selectedDate: NullableString;
  selectedStatus: NullableString;

};

// 👉 Email Model
export type EmailFolder = 'inbox' | 'sent' | 'draft' | 'spam'
export type EmailFilter = EmailFolder | 'trashed' | 'starred'
export type EmailLabel = 'personal' | 'company' | 'important' | 'private'

export interface EmailTo {
  email: NullableString
  name: NullableString
}

export interface EmailFrom {
  email: NullableString
  name: NullableString
  avatar: any
}

export interface EmailAttachment {
  fileName: NullableString
  thumbnail: any
  url: NullableString
  size: NullableString
}

export type Email = entity & {

  to: EmailTo[]
  from: EmailFrom
  subject: NullableString
  cc: NullableString[]
  bcc: NullableString[]
  message: NullableString
  attachments: EmailAttachment[]
  time: NullableString
  replies: Email[]

  labels: EmailLabel[]

  folder: EmailFolder
  client: Client,
  contact: Contacts,
  contentHtml: NullableString
  content: NullableString
  html: NullableString
  // Flags 🚩
  isRead: Nullableboolean
  isStarred: Nullableboolean
  isDeleted: Nullableboolean
}

export interface FetchEmailsPayload {
  q?: NullableString
  filter?: EmailFilter
  label?: EmailLabel
}

export type EmailFilterParms = {
  searchQuery: NullableString;
};

// 👉 Payment Model

export type Payment = entity & {
  code: NullableString;
  date: NullableDate
  amount: NullableInteger
  comment: NullableString
  invoiceId: NullableString
  orderReceiptId: NullableString
  checkNumber: NullableString
  checkDate: NullableDate
  wireTransferNumber: NullableString
  effectDate: NullableDate
  effectNumber: NullableString
  paymentType: NullableString,
  paymentTypeId: NullableString
  clientBalance: NullableInteger,
  client: NullableString;
  clientId: NullableString;
  recovery: NullableString;
  recoveryId: NullableString;

};

export type PaymentFilterParms = {
  searchQuery: NullableString;
  selectedPaymentType: NullableString;
  selectedDate: NullableString;
  selectedInvoice: NullableString;
  selectedClient: NullableString;
  selectedYear: NullableString;

};


// 👉 user logs Model

export type Logs = entity & {
  action: NullableString;
  userId: NullableString;
  entityId: NullableString;
  oldValue: NullableString;
  newValue: NullableString;

};
export type LogsFilterParms = {
  searchQuery: NullableString;
  selectedAction: NullableString;
}
