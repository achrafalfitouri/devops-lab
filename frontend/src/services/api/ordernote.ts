import { axiosInstance } from '@/plugins/axios';
import { OrderNotes } from '@services/models';

// 👉 Fetch ordernotes 
export const fetchOrderNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/ordernotes?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching ordernotes:", error);
    throw error;
  }
};
// 👉 Fetch archived ordernotes 
export const fetchArchivedOrderNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/ordernotes/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching ordernotes:", error);
    throw error;
  }
};

// 👉 Get ordernotes by ID
export const fetchOrderNotesById = async (ordernotesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/ordernotes/${ordernotesId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching ordernotes by ID:", error);
    throw error;
  }
};
// 👉 Get archived ordernotes by ID
export const fetchArchivedOrderNotesById = async (ordernotesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/ordernotes/${ordernotesId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching ordernotes by ID:", error);
    throw error;
  }
};

// 👉 Add ordernotes
export const addOrderNotes = async (ordernotesData: OrderNotes) => {
  try {
    const response = await axiosInstance.post('/ordernotes/create', ordernotesData);
    return response.data; // Return the ordernotes ID
  } catch (error) {
    console.error('Error in addordernotes function:', error);
    throw error;
  }
};

// 👉 Update ordernotes
export const updateOrderNotes = async (ordernotesId: string, ordernotesData: OrderNotes) => {
  try {
    const response = await axiosInstance.put(`/ordernotes/${ordernotesId}`, ordernotesData);
    return response.data;
  } catch (error) {
    console.error('Error updating ordernotes:', error);
    throw error;
  }
};

// 👉 Update status Order Notes
export const updateStatusOrderNotes = async (ordernoteId: string | string[] | undefined, orderNoteData: OrderNotes) => {
  try {
    const response = await axiosInstance.put(`/ordernotes/status/${ordernoteId}`, orderNoteData);
    return response.data;
  } catch (error) {
    console.error('Error updating Order Notes status:', error);
    throw error;
  }
};


// 👉 Delete ordernotes
export const deleteOrderNotes = async (ordernotesId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/ordernotes/${ordernotesId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting ordernotes:', error);
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

// 👉 Export ordernotes 
export const exportOrderNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/ordernote?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting ordernotes:", error);
    throw error;
  }
};

// 👉 Get ordernotes by ID
export const GenerateOrderNoteById = async (param: { ids: any[] } | string | number) => {
  try {
    const response = await axiosInstance.post(`/ordernotes/${param}/generate-document`);
    return response.data;
  } catch (error) {
    console.error("Error generating order note by ID:", error);
    throw error;
  }
};

// 👉 Generate pdf Ordernote by ID
export const GenerateOrdernotePdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/ordernote/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating ordernote pdf by ID:", error);
    throw error;
  }
};

