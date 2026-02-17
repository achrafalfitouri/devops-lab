import { axiosInstance } from '@/plugins/axios';


// 👉 Fetch documentlogs
export const fetchDocumentLogss = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/documentlogs?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching documentlogs:", error);
    throw error;
  }
};

// 👉 Get documentlogs by ID
export const fetchDocumentLogsById = async (doclogsId: number | string) => {
  try {
    const response = await axiosInstance.get(`/documentlogs/${doclogsId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching documentlogs by ID:", error);
    throw error;
  }
};

// 👉 Fetch documentlogs
export const fetchDocumentItemLogss = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/itemlogs?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching item logs:", error);
    throw error;
  }
};

// 👉 Get documentlogs by ID
export const fetchDocumentItemLogsById = async (doclogsId: number | string) => {
  try {
    const response = await axiosInstance.get(`/itemlogs/${doclogsId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching item logs by ID:", error);
    throw error;
  }
};







// // 👉 Fetch RefundsLogss
// export const fetchRefundsLogss = async (filters: any = {}, per_page: number, page: number) => {
//   try {
//     const response = await axiosInstance.post(`/refundslogs?per_page=${per_page}&page=${page}`, filters);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching returnnoteslogs:", error);
//     throw error;
//   }
// };

// // 👉 Get RefundsLogs by ID
// export const fetchRefundsLogsById = async (doclogsId: number | string) => {
//   try {
//     const response = await axiosInstance.get(`/refundslogs/${doclogsId}`);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching refundslogs by ID:", error);
//     throw error;
//   }
// };

// // 👉 Fetch QuotesLogss
// export const fetchQuotesLogss = async (filters: any = {}, per_page: number, page: number) => {
//   try {
//     const response = await axiosInstance.post(`/quoteslogs?per_page=${per_page}&page=${page}`, filters);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching quoteslogs:", error);
//     throw error;
//   }
// };

// // 👉 Get QuotesLogs by ID
// export const fetchQuotesLogsById = async (doclogsId: number | string) => {
//   try {
//     const response = await axiosInstance.get(`/quoteslogs/${doclogsId}`);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching quoteslogs by ID:", error);
//     throw error;
//   }
// };

// // 👉 Fetch ProductionNotesLogss
// export const fetchProductionNotesLogss = async (filters: any = {}, per_page: number, page: number) => {
//   try {
//     const response = await axiosInstance.post(`/productionnoteslogs?per_page=${per_page}&page=${page}`, filters);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching productionnoteslogs:", error);
//     throw error;
//   }
// };

// // 👉 Get ProductionNotesLogs by ID
// export const fetchProductionNotesLogsById = async (doclogsId: number | string) => {
//   try {
//     const response = await axiosInstance.get(`/productionnoteslogs/${doclogsId}`);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching productionnoteslogs by ID:", error);
//     throw error;
//   }
// };

// // 👉 Fetch OutputNotesLogss
// export const fetchOutputNotesLogss = async (filters: any = {}, per_page: number, page: number) => {
//   try {
//     const response = await axiosInstance.post(`/outputnoteslogs?per_page=${per_page}&page=${page}`, filters);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching outputnoteslogs:", error);
//     throw error;
//   }
// };

// // 👉 Get OutputNotesLogs by ID
// export const fetchOutputNotesLogsById = async (doclogsId: number | string) => {
//   try {
//     const response = await axiosInstance.get(`/outputnoteslogs/${doclogsId}`);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching outputnoteslogs by ID:", error);
//     throw error;
//   }
// };

// // 👉 Fetch OrderReceiptLogss
// export const fetchOrderReceiptLogss = async (filters: any = {}, per_page: number, page: number) => {
//   try {
//     const response = await axiosInstance.post(`/orderreceiptslogs?per_page=${per_page}&page=${page}`, filters);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching orderreceiptslogs:", error);
//     throw error;
//   }
// };

// // 👉 Get OrderReceiptLogs by ID
// export const fetchOrderReceiptLogsById = async (doclogsId: number | string) => {
//   try {
//     const response = await axiosInstance.get(`/orderreceiptslogs/${doclogsId}`);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching orderreceiptslogs by ID:", error);
//     throw error;
//   }
// };

// // 👉 Fetch OrderNoteLogs
// export const fetchOrderNoteLogss = async (filters: any = {}, per_page: number, page: number) => {
//   try {
//     const response = await axiosInstance.post(`/ordernoteslogs?per_page=${per_page}&page=${page}`, filters);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching ordernoteslogs:", error);
//     throw error;
//   }
// };

// // 👉 Get OrderNoteLogss by ID
// export const fetchOrderNoteLogsById = async (doclogsId: number | string) => {
//   try {
//     const response = await axiosInstance.get(`/ordernoteslogs/${doclogsId}`);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching ordernoteslogs by ID:", error);
//     throw error;
//   }
// };

// // 👉 Fetch InvoiceLogs
// export const fetchInvoiceLogss = async (filters: any = {}, per_page: number, page: number) => {
//   try {
//     const response = await axiosInstance.post(`/invoicelogs?per_page=${per_page}&page=${page}`, filters);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching invoicelogs:", error);
//     throw error;
//   }
// };

// // 👉 Get InvoiceLogss by ID
// export const fetchInvoiceLogsById = async (doclogsId: number | string) => {
//   try {
//     const response = await axiosInstance.get(`/invoicelogs/${doclogsId}`);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching invoicelogs by ID:", error);
//     throw error;
//   }
// };

// // 👉 Fetch InvoiceCreditLogs
// export const fetchInvoiceCreditLogss = async (filters: any = {}, per_page: number, page: number) => {
//   try {
//     const response = await axiosInstance.post(`/invoicecreditlogs?per_page=${per_page}&page=${page}`, filters);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching invoicecreditlogs:", error);
//     throw error;
//   }
// };

// // 👉 Get InvoiceCreditLogss by ID
// export const fetchInvoiceCreditLogsById = async (doclogsId: number | string) => {
//   try {
//     const response = await axiosInstance.get(`/invoicecreditlogs/${doclogsId}`);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching invoicecreditlogs by ID:", error);
//     throw error;
//   }
// };

// // 👉 Fetch DeliveryNoteLogs
// export const fetchDeliveryNoteLogss = async (filters: any = {}, per_page: number, page: number) => {
//   try {
//     const response = await axiosInstance.post(`/deliverynotelogs?per_page=${per_page}&page=${page}`, filters);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching deliverynotelogs:", error);
//     throw error;
//   }
// };

// // 👉 Get DeliveryNoteLogss by ID
// export const fetchDeliveryNoteLogsById = async (doclogsId: number | string) => {
//   try {
//     const response = await axiosInstance.get(`/deliverynotelogs/${doclogsId}`);
//     return response.data;
//   } catch (error) {
//     console.error("Error fetching deliverynotelogs by ID:", error);
//     throw error;
//   }
// };
