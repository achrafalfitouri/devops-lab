import { axiosInstance } from '@/plugins/axios';


// 👉 Fetch TransactionLogss
export const fetchTransactionLogss = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`transactions/transactionlogs?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching transactionlogss:", error);
    throw error;
  }
};

// 👉 Get TransactionLogs by ID
export const fetchTransactionLogsById = async (transactionlogsId: number | string) => {
  try {
    const response = await axiosInstance.get(`transactions/transactionlogs/${transactionlogsId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching transactionlogs by ID:", error);
    throw error;
  }
};



