import { axiosInstance } from '@/plugins/axios';
import { Invoices } from '@services/models';

// 👉 Fetch invoices 
export const fetchInvoices = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/invoices/all?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching invoices:", error);
    throw error;
  }
};
// 👉 Fetch archived invoices 
export const fetchArchivedInvoices = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/invoices/all/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching invoices:", error);
    throw error;
  }
};

// 👉 Get invoices by ID
export const fetchInvoicesById = async (invoicesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/invoices/${invoicesId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching invoices by ID:", error);
    throw error;
  }
};
// 👉 Get archived invoices by ID
export const fetchArchivedInvoicesById = async (invoicesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/invoices/${invoicesId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching invoices by ID:", error);
    throw error;
  }
};

// 👉 Add invoices
export const addInvoices = async (invoicesData: Invoices) => {
  try {
    const response = await axiosInstance.post('/invoices/create', invoicesData);
    return response.data; // Return the invoices ID
  } catch (error) {
    console.error('Error in addinvoices function:', error);
    throw error;
  }
};

// 👉 Update invoices
export const updateInvoices = async (invoicesId: string, invoicesData: Invoices) => {
  try {
    const response = await axiosInstance.put(`/invoices/${invoicesId}`, invoicesData);
    return response.data;
  } catch (error) {
    console.error('Error updating invoices:', error);
    throw error;
  }
};

// 👉 Update status Invoices
export const updateStatusInvoices = async (invoiceId: string | string[] | undefined, invoicesData: Invoices) => {
  try {
    const response = await axiosInstance.put(`/invoices/status/${invoiceId}`, invoicesData);
    return response.data;
  } catch (error) {
    console.error('Error updating invoices status:', error);
    throw error;
  }
};


// 👉 Delete invoices
export const deleteInvoices = async (invoicesId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/invoices/${invoicesId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting invoices:', error);
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


// 👉 Export invoices 
export const exportInvoices = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/invoice?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting invoices:", error);
    throw error;
  }
};

// 👉 Get invoice by ID
export const GenerateInvoiceById = async (param: { ids: any[], id?: string | number } | string | number) => {
  try {
    let payload;
    let invoiceId;

    if (typeof param === "object" && "ids" in param) {
      payload = { ids: param.ids };
      invoiceId = param.id;
    } else {
      payload = { ids: [param] };
      invoiceId = param;
    }

    const response = await axiosInstance.post(`/invoices/${invoiceId}/generate-document`, payload);
    return response.data;
  } catch (error) {
    console.error("Error generating invoice by ID:", error);
    throw error;
  }
};



// 👉 Generate pdf Invoice by ID
export const GenerateInvoicePdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/invoice/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating invoice pdf by ID:", error);
    throw error;
  }
};
