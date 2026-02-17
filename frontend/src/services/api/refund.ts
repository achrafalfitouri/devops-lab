import { axiosInstance } from '@/plugins/axios';
import { Refunds } from '@services/models';

// 👉 Fetch refunds 
export const fetchRefunds = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/refund-notes?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching refunds:", error);
    throw error;
  }
};
// 👉 Fetch archived refunds 
export const fetchArchivedRefunds = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/refund-notes/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching refunds:", error);
    throw error;
  }
};

// 👉 Get refunds by ID
export const fetchRefundsById = async (refundsId: number | string,filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/refund-notes/${refundsId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching refunds by ID:", error);
    throw error;
  }
};
// 👉 Get  archived refunds by ID
export const fetchArchivedRefundsById = async (refundsId: number | string,filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/refund-notes/${refundsId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching refunds by ID:", error);
    throw error;
  }
};

// 👉 Add refunds
export const addRefunds = async (refundsData: Refunds) => {
  try {
    const response = await axiosInstance.post('/refund-notes/create', refundsData);
    return response.data; // Return the refunds ID
  } catch (error) {
    console.error('Error in addrefunds function:', error);
    throw error;
  }
};

// 👉 Update refunds
export const updateRefunds = async (refundsId: string, refundsData: Refunds) => {
  try {
    const response = await axiosInstance.put(`/refund-notes/${refundsId}`, refundsData);
    return response.data;
  } catch (error) {
    console.error('Error updating refunds:', error);
    throw error;
  }
};

// 👉 Update status Order Notes
export const updateStatusRefunds = async (refundId: string | string[] | undefined, refundData: Refunds) => {
  try {
    const response = await axiosInstance.put(`/refund-notes/status/${refundId}`, refundData);
    return response.data;
  } catch (error) {
    console.error('Error updating Order Notes status:', error);
    throw error;
  }
};
// 👉 Update status Order Notes
export const updateTypeRefunds = async (refundId: string | string[] | undefined, refundData: Refunds) => {
  try {
    const response = await axiosInstance.put(`/refund-notes/type/${refundId}`, 
      
     { type : refundData});
    return response.data;
  } catch (error) {
    console.error('Error updating refund Notes type:', error);
    throw error;
  }
};


// 👉 Delete refunds
export const deleteRefunds = async (refundsId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/refund-notes/${refundsId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting refunds:', error);
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

// 👉 Export refunds 
export const exportRefunds = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/refund?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting refunds:", error);
    throw error;
  }
};

// 👉 Get refunds by ID
export const GenerateRefundById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.post(`/refunds/${quotesId}/generate-document`);
    return response.data;
  } catch (error) {
    console.error("Error generating order note by ID:", error);
    throw error;
  }
};

// 👉 Generate pdf Invoice credits by ID
export const GenerateRefundsPdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/refund/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating invoice credits  pdf by ID:", error);
    throw error;
  }
};
