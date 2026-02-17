import type {
  CashRegister, CashRegisterFilterParms, Client, ClientFilterParms, Contacts, DeliveryNotes, DeliveryNotesFilterParms, DocumentItems, Email,
  EmailFilterParms,
  InvoiceCredits, InvoiceCreditsFilterParms, Invoices, InvoicesFilterParms,
  Logs, LogsFilterParms,
  OrderNotes, OrderNotesFilterParms, OrderReceipt, OrderReceiptFilterParms, OutputNotes, OutputNotesFilterParms, Payment, PaymentFilterParms, ProductionNotes, ProductionNotesFilterParms,
  QuoteRequests,
  Quotes, QuotesFilterParms, Refunds, RefundsFilterParms, ReturnNotes, ReturnNotesFilterParms, Transaction, TransactionFilterParms, User, UserFilterParms
} from '@services/models';

// 👉 Function to get default User values
export const getDefaultUser = (): User => ({
  id: '',
  password: null,
  lastName: null,
  firstName: null,
  cin: null,
  phone: null,
  email: null,
  birthdate: null,
  title: null,
  titleId: null,
  permissions: null,
  status: 1,
  photo: null,
  code: null,
  gender: null,
  roles: null,
  cashregisters: null,
  createdAt: null,
  updatedAt: null,
  fullName: null,
  passwordRequestCheck: null,
  number: null,
})

// 👉 Function to get default User filter params
export const getDefaultUserFilterParams = (): UserFilterParms => ({
  searchQuery: null,
  selectedTitle: null,
  selectedRole: null,
  selectedStatus: null,
}
);

// 👉 Function to get default Client values
export const getDefaultClient = (): Client => ({
  id: '',
  logo: null,
  legalName: null,
  tradeName: null,
  code: null,
  phoneNumber: null,
  email: null,
  city: null,
  address: null,
  ice: null,
  if: null,
  tp: null,
  type: null,
  gamut: null,
  status: null,
  businessSector: null,
  createdAt: null,
  updatedAt: null,
  businessSectorId: null,
  statusId: null,
  gamutId: null,
  clientTypeId: null,
  cityId: null,
  rc: null
});

// 👉 Function to get default Client filter params
export const getDefaultClientFilterParams = (): ClientFilterParms => ({
  searchQuery: null,
  selectedType: null,
  selectedGamut: null,
  selectedStatus: null,
  selectedCity: null,
  selectedBusinessSector: null,
});


// 👉 CashRegister  Model
export const getDefaultCashRegister = (): CashRegister => ({
  id: '',
  name: null,
  code: null,
  users: null,
  status: null,
  managedBy: [],
  createdAt: null,
  updatedAt: null,
});
// 👉 Function to get default CashRegister filter params
export const getDefaultCashRegisterFilterParams = (): CashRegisterFilterParms => ({
  selectedCashRegister: null,
  selectedDate: null
});

// 👉 Transaction  Model
export const getDefaultTransaction = (): Transaction => ({
  id: '',
  name: null,
  amount: null,
  comment: null,
  date: null,
  cashTransactionType: null,
  cashRegisterId: null,
  cashRegister: null,
  cashTransactionTypeId: null,
  createdAt: null,
  updatedAt: null,
  client: null,
  clientId: null,
  targetUserId: null,
  targetUser: null,
  targetCashRegisterId: null,
  targetCashRegister: null,
  refundNote: null,
  refundNoteId: null,
  balanceReset: null,
  seller: null,
  bank: null,

});

// 👉 Function to get default Transaction filter params
export const getDefaultTransactionFilterParams = (): TransactionFilterParms => ({
  searchQuery: null,
  selectedTransactionType: null,
  selectedCashRegister: null,
  selectedUser: null,
  range: null

});

// 👉 Default structure for a documentCore
export const documentCoreDefaults = (): DocumentCore => ({
  id: "",
  code: null,
  amount: null,
  isTaxable: 1,
  taxAmount: null,
  finalAmount: null,
  totalPhrase: null,
  totalPhraseHt: null,
  status: null,
  documentsItems: [],
  items: [],
  clientId: null,
  createdAt: null,
  updatedAt: null,
  quoteComment: null,
  orderComment: null,
  invoiceComment: null,
  returnComment: null,
  outputComment: null,
  deliveryComment: null,
  productionComment: null,
  refundComment: null,
  quoterequestComment: null,
  clientSelect: null,
  ice: null,
  client: {
    ice: null,
    id: '',
    legalName: null,
    balance: null,
  }
}
);
// 👉 Quotes  default
export const getDefaultQuotes = (): Quotes => ({
  ...documentCoreDefaults(),
  validityDate: null,
});
// 👉 QuoteRequests  default
export const getDefaultQuoteRequests = (): QuoteRequests => ({
  ...documentCoreDefaults(),
});

// 👉  Provides the default structure for an OrderNotes object
export const getDefaultOrderNotes = (): OrderNotes => ({
  ...documentCoreDefaults(),
  productionNote: null,
  orderUser: null,
  orderUserId: null,
  quote: null,
  quoteId: null,
});

// 👉 Function to get default Transaction filter params
export const getDefaultQuotesFilterParams = (): QuotesFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedDate: null,
  selectedStatus: null,
});
// 👉 Function to get default Transaction filter params
export const getDefaultQuoteRequestsFilterParams = (): QuotesFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedDate: null,
  selectedStatus: null,
});

// 👉  Provides the default filter parameters for OrderNotes
export const getDefaultOrderNotesFilterParams = (): OrderNotesFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedStatus: null,
});

// 👉  Provides the default structure for a ProductionNotes object
export const getDefaultProductionNotes = (): ProductionNotes => ({
  ...documentCoreDefaults(),
  productionNote: null,
  productionUser: null,
  productionUserId: null,
  order: null,
  orderId: null,
});

// 👉  Provides the default filter parameters for ProductionNotes
export const getDefaultProductionNotesFilterParams = (): ProductionNotesFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedStatus: null,
});

// 👉  Provides the default structure for an OutputNotes object
export const getDefaultOutputNotes = (): OutputNotes => ({
  ...documentCoreDefaults(),
  outputDate: null,
  outputNote: null,
  productionNote: null,
  outputUser: null,
  productionNoteId: null,
  outputUserId: null,
});

// 👉  Provides the default filter parameters for OutputNotes
export const getDefaultOutputNotesFilterParams = (): OutputNotesFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedDate: null,
  selectedStatus: null,
});

// 👉  Provides the default structure for a DeliveryNotes object
export const getDefaultDeliveryNotes = (): DeliveryNotes => ({
  ...documentCoreDefaults(),
  deliveryNote: null,
  outputNote: null,
  deliveryUser: null,
  outputNoteId: null,
  deliveryUserId: null,
});

// 👉  Provides the default filter parameters for DeliveryNotes
export const getDefaultDeliveryNotesFilterParams = (): DeliveryNotesFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedStatus: null,
  selectedTax: null
});

// 👉  Provides the default structure for a ReturnNotes object
export const getDefaultReturnNotes = (): ReturnNotes => ({
  ...documentCoreDefaults(),
  note: null,
  deliveryNote: null,
  returnUser: null,
  deliveryNoteId: null,
  returnUserId: null,
});

// 👉  Provides the default filter parameters for ReturnNotes
export const getDefaultReturnNotesFilterParams = (): ReturnNotesFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedStatus: null,
});

// 👉  Provides the default structure for an Invoices object
export const getDefaultInvoices = (): Invoices => ({
  ...documentCoreDefaults(),
  dueDate: null,
  discountedAmount: null,
});

// 👉  Provides the default filter parameters for Invoices
export const getDefaultInvoicesFilterParams = (): InvoicesFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedDate: null,
  selectedStatus: null,

});

// 👉  Provides the default structure for an Invoices object
export const getDefaultRefunds = (): Refunds => ({
  ...documentCoreDefaults(),
  type: null,
  paymentType: {},
  paymentTypeId: null,
});

// 👉  Provides the default filter parameters for Invoices
export const getDefaultRefundsFilterParams = (): RefundsFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedDate: null,
  selectedStatus: null,
});


// 👉 Define DocumentItems default
export const getDefaultDocumentItem = (): DocumentItems => ({
  id: "",
  description: "",
  characteristics: null,
  price: null,
  quantity: null,
  undiscountedAmount: 0,
  discount: 0,
  amount: null,
  index: null,
  productionNoteId: null,
  outputNoteId: null,
  deliveryNoteId: null,
  returnNoteId: null,
  invoiceId: null,
  order: null,
  createdAt: null,
  updatedAt: null,


});

// 👉 Define Contacts default
export const getDefaultContacts = (): Contacts => ({
  id: "",
  firstName: null,
  lastName: null,
  fullName: null,
  title: null,
  phone: null,
  email: null,
  client: null,
  clientId: null,
  createdAt: null,
  updatedAt: null,
});


export const getDefaultEmail = (): Email => ({
  id: "",
  to: [],
  from: {
    email: null,
    name: null,
    avatar: null,
  },
  subject: null,
  cc: [],
  bcc: [],
  message: null,
  attachments: [],
  time: null,
  replies: [],
  labels: [],
  client: getDefaultClient(),
  contact: getDefaultContacts(),
  contentHtml: null,
  content: null,
  html: null,
  folder: 'inbox',
  isRead: null,
  isStarred: null,
  isDeleted: null,
  createdAt: null,
  updatedAt: null,
});

// 👉 Function to get default email filter params
export const getDefaultEmailFilterParams = (): EmailFilterParms => ({
  searchQuery: null,
});


// 👉 Function to get default payment params
export const getDefaultPayment = (): Payment => ({
  id: "",
  code: null,
  date: null,
  amount: null,
  comment: null,
  paymentTypeId: null,
  invoiceId: null,
  orderReceiptId: null,
  checkNumber: null,
  checkDate: null,
  wireTransferNumber: null,
  effectDate: null,
  effectNumber: null,
  paymentType: null,
  createdAt: null,
  updatedAt: null,
  clientBalance: null,
  client: null,
  clientId: null,
  recovery: null,
  recoveryId: null,
});

// 👉 Function to get default payment filter params
export const getDefaultPaymentFilterParams = (): PaymentFilterParms => ({
  searchQuery: null,
  selectedPaymentType: null,
  selectedDate: null,
  selectedInvoice: null,
  selectedClient: null,
  selectedYear: null,
});

// 👉 OrderReciept  Model
export const getDefaultOrderReceipt = (): OrderReceipt => ({
  ...documentCoreDefaults(),
  dueDate: null,
  discountedAmount: null,
});

// 👉 Function to get default OrderReceipt filter params
export const getDefaultOrderReceiptFilterParams = (): OrderReceiptFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedDate: null,
  selectedStatus: null,
});
// 👉 InvoiceCredits  Model
export const getDefaultInvoiceCredits = (): InvoiceCredits => ({
  ...documentCoreDefaults(),
  dueDate: null,
  discountedAmount: null,
  clientBalance: null

});

// 👉 Function to get default InvoiceCredits filter params
export const getDefaultInvoiceCreditsFilterParams = (): InvoiceCreditsFilterParms => ({
  searchQuery: null,
  selectedClient: null,
  selectedUser: null,
  selectedDate: null,
  selectedStatus: null,
});


// 👉 Function to get default User logs 

export const getDefaultLogs = (): Logs => ({
  id: '',
  userId: null,
  action: null,
  entityId: null,
  oldValue: null,
  newValue: null,
  createdAt: null,
  updatedAt: null,
});

// 👉 Function to get default user lo
export const getDefaultLogsFilterParams = (): LogsFilterParms => ({
  searchQuery: null,
  selectedAction: null,
});

