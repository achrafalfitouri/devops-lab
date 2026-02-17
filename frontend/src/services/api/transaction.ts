import { axiosInstance } from '@/plugins/axios';

// 👉 Create Transaction
export const addTransaction = async (transactionData: any) => {
  try {
    const response = await axiosInstance.post('/transactions/create', transactionData);
    return response.data;
  } catch (error) {
    console.error('Error creating transaction:', error);
    throw error;
  }
};

// 👉 Update Transaction by ID
export const updateTransaction = async (id: number | string, transactionData: any) => {
  try {
    const response = await axiosInstance.put(`/transactions/${id}`, transactionData);
    return response.data;
  } catch (error) {
    console.error('Error updating transaction:', error);
    throw error;
  }
};

// 👉 Soft Delete Transaction by ID
export const deleteTransaction = async (id: number | string) => {
  try {
    const response = await axiosInstance.delete(`/transactions/${id}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting transaction:', error);
    throw error;
  }
};

// 👉 Get Transactions
export const getAllTransactions = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/transactions?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error('Error fetching transactions:', error);
    throw error;
  }
};
// 👉 Get archived Transactions
export const getArchivedAllTransactions = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/transactions/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error('Error fetching transactions:', error);
    throw error;
  }
};

// 👉 Get transaction by ID
export const fetchTransactionById = async (Id: number | string,filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/transactions/${Id}`, {params: filters}
    );
    return response.data;
  } catch (error) {
    console.error("Error fetching transaction by ID:", error);
    throw error;
  }
};

// 👉 Fetch Static data
export const fetchStaticData = async () => {
  try {
    const response = await axiosInstance.get('/staticdatatransaction');
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};

// 👉 Export transactions 
export const exportTransactions = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/transaction?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting transactions:", error);
    throw error;
  }
};
