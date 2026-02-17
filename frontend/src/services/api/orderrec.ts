import { axiosInstance } from '@/plugins/axios';
import { OrderReceipt } from '@services/models';

// 👉 Fetch orderreceipt 
export const fetchOrderReceipt = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/orderreceipts?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching orderreceipt:", error);
    throw error;
  }
};
// 👉 Fetch archived orderreceipt 
export const fetchArchivedOrderReceipt = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/orderreceipts/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching orderreceipt:", error);
    throw error;
  }
};

// 👉 Get orderreceipt by ID
export const fetchOrderReceiptById = async (orderreceiptId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/orderreceipts/${orderreceiptId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching orderreceipt by ID:", error);
    throw error;
  }
};
// 👉 Get archived orderreceipt by ID
export const fetchArchivedOrderReceiptById = async (orderreceiptId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/orderreceipts/${orderreceiptId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching orderreceipt by ID:", error);
    throw error;
  }
};

// 👉 Add orderreceipt
export const addOrderReceipt = async (orderreceiptData: OrderReceipt) => {
  try {
    const response = await axiosInstance.post('/orderreceipts/create', orderreceiptData);
    return response.data; // Return the orderreceipt ID
  } catch (error) {
    console.error('Error in addorderreceipt function:', error);
    throw error;
  }
};

// 👉 Update orderreceipt
export const updateOrderReceipt = async (orderreceiptId: string, orderreceiptData: OrderReceipt) => {
  try {
    const response = await axiosInstance.put(`/orderreceipts/${orderreceiptId}`, orderreceiptData);
    return response.data;
  } catch (error) {
    console.error('Error updating orderreceipt:', error);
    throw error;
  }
};

// 👉 Update status Order Notes
export const updateStatusOrderReceipt = async (ordernoteId: string | string[] | undefined, orderNoteData: OrderReceipt) => {
  try {
    const response = await axiosInstance.put(`/orderreceipts/status/${ordernoteId}`, orderNoteData);
    return response.data;
  } catch (error) {
    console.error('Error updating Order Notes status:', error);
    throw error;
  }
};


// 👉 Delete orderreceipt
export const deleteOrderReceipt = async (orderreceiptId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/orderreceipts/${orderreceiptId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting orderreceipt:', error);
    throw error;
  }
};

// 👉 Fetch Static data
export const fetchStaticData = async () => {
  try {
    const response = await axiosInstance.get('/staticdatadocuments');
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};

// 👉 Export orderreceipt 
export const exportOrderReceipt = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/orderreceipt?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting orderreceipt:", error);
    throw error;
  }
};

// 👉 Get orderReceipt by ID
export const GenerateOrderReceiptById = async (param: { ids: any[], id?: string | number } | string | number) => {
  try {
    let payload;
    let orderReceiptId;

    if (typeof param === "object" && "ids" in param) {
      payload = { ids: param.ids };
      orderReceiptId = param.id;
    } else {
      payload = { ids: [param] };
      orderReceiptId = param;
    }

    const response = await axiosInstance.post(`/orderreceipts/${orderReceiptId}/generate-document`, payload);
    return response.data;
  } catch (error) {
    console.error("Error generating order receipt by ID:", error);
    throw error;
  }
};


// 👉 Generate pdf Orderr eciept by ID
export const GenerateOrderRecieptPdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/orderreciept/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating order reciept pdf by ID:", error);
    throw error;
  }
};
