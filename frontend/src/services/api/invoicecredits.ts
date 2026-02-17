import { axiosInstance } from '@/plugins/axios';
import { InvoiceCredits } from '@services/models';

// 👉 Fetch invoicecredits 
export const fetchInvoiceCredits = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/invoicecredits?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching invoicecredits:", error);
    throw error;
  }
};
// 👉 Fetch archived invoicecredits 
export const fetchArchivedInvoiceCredits = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/invoicecredits/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching invoicecredits:", error);
    throw error;
  }
};

// 👉 Get invoicecredits by ID
export const fetchInvoiceCreditsById = async (invoicecreditsId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/invoicecredits/${invoicecreditsId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching invoicecredits by ID:", error);
    throw error;
  }
};

// 👉 Get archived invoicecredits by ID
export const fetchArchivedInvoiceCreditsById = async (invoicecreditsId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/invoicecredits/${invoicecreditsId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching invoicecredits by ID:", error);
    throw error;
  }
};

// 👉 Add invoicecredits
export const addInvoiceCredits = async (invoicecreditsData: InvoiceCredits) => {
  try {
    const response = await axiosInstance.post('/invoicecredits/create', invoicecreditsData);
    return response.data; // Return the invoicecredits ID
  } catch (error) {
    console.error('Error in addinvoicecredits function:', error);
    throw error;
  }
};

// 👉 Update invoicecredits
export const updateInvoiceCredits = async (invoicecreditsId: string, invoicecreditsData: InvoiceCredits) => {
  try {
    const response = await axiosInstance.put(`/invoicecredits/${invoicecreditsId}`, invoicecreditsData);
    return response.data;
  } catch (error) {
    console.error('Error updating invoicecredits:', error);
    throw error;
  }
};

// 👉 Update status Order Notes
export const updateStatusInvoiceCredits = async (invoicecreditId: string | string[] | undefined, invoicecreditData: InvoiceCredits) => {
  try {
    const response = await axiosInstance.put(`/invoicecredits/status/${invoicecreditId}`, invoicecreditData);
    return response.data;
  } catch (error) {
    console.error('Error updating Order Notes status:', error);
    throw error;
  }
};


// 👉 Delete invoicecredits
export const deleteInvoiceCredits = async (invoicecreditsId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/invoicecredits/${invoicecreditsId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting invoicecredits:', error);
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

// 👉 Export invoicecredits 
export const exportInvoiceCredits = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/invoicecredit?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting invoicecredits:", error);
    throw error;
  }
};

// 👉 Get invoicecredits by ID
export const GenerateInvoicecreditById = async (param: { ids: any[] } | string | number) => {
  try {
    const response = await axiosInstance.post(`/invoicecredits/${param}/generate-document`);
    return response.data;
  } catch (error) {
    console.error("Error generating order note by ID:", error);
    throw error;
  }
};

// 👉 Generate pdf Invoice credits by ID
export const GenerateInvoiceCreditsPdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/invoicecredit/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating invoice credits  pdf by ID:", error);
    throw error;
  }
};
