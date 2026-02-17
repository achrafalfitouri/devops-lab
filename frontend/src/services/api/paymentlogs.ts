import { axiosInstance } from '@/plugins/axios';


// 👉 Fetch PaymentLogss
export const fetchPaymentLogss = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`payments/paymentlogs?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching paymentlogss:", error);
    throw error;
  }
};

// 👉 Get PaymentLogs by ID
export const fetchPaymentLogsById = async (paymentlogsId: number | string) => {
  const response = await axiosInstance.get(`payments/paymentlogs/${paymentlogsId}`);
  return response.data;
};

// 👉 Fetch RecoveryLogs
export const fetchRecoveryLogs = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`payments/recoverylogs?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching recoverylogs:", error);
    throw error;
  }
};

// 👉 Get RecoveryLogs by ID
export const fetchRecoveryLogsById = async (recoverylogsId: number | string) => {
  const response = await axiosInstance.get(`payments/recoverylogs/${recoverylogsId}`);
  return response.data;
};



