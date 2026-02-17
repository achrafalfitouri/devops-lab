import { axiosInstance } from '@/plugins/axios';
import { Payment } from '@services/models';

// 👉 Fetch Payments
export const fetchPayments = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/payments?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching payments:", error);
    throw error;
  }
};

// 👉 Fetch Payments
export const fetchRecoveries = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/payments/recoveries?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching recoveries:", error);
    throw error;
  }
};
// 👉 Fetch archived Payments
export const fetchArchivedPayments = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/payments/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching payments:", error);
    throw error;
  }
};
// 👉 Fetch archived Recoveries
export const fetchArchivedRecoveries = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/payments/recoveries/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching recoveries:", error);
    throw error;
  }
};

// 👉 Get Payment by ID
export const fetchPaymentById = async (paymentId: number | string) => {
  try {
    const response = await axiosInstance.get(`/payments/${paymentId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching payment by ID:", error);
    throw error;
  }
};
// 👉 Get Payment by ID
export const fetchRecoveryById = async (paymentId: number | string) => {
  try {
    const response = await axiosInstance.get(`/payments/recoveries/${paymentId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching recovery by ID:", error);
    throw error;
  }
};
// 👉 Get archived Payment by ID
export const fetchArchivedPaymentById = async (paymentId: number | string) => {
  try {
    const response = await axiosInstance.get(`/payments/${paymentId}/archive`);
    return response.data;
  } catch (error) {
    console.error("Error fetching payment by ID:", error);
    throw error;
  }
};
// 👉 Get archived Payment by ID
export const fetchArchivedRecoveryById = async (paymentId: number | string) => {
  try {
    const response = await axiosInstance.get(`/payments/recoveries/${paymentId}/archive`);
    return response.data;
  } catch (error) {
    console.error("Error fetching recovery by ID:", error);
    throw error;
  }
};

// 👉 Add Payment
export const addPayment = async (paymentData: Payment) => {
  try {
    const response = await axiosInstance.post('/payments/create', paymentData);
    return response.data;
  } catch (error) {
    console.error('Error in addPayments function:', error);
    throw error;
  }
};
// 👉 Add Recovery
export const addRecovery = async (recoveryData: Payment) => {
  try {
    const response = await axiosInstance.post('/payments/recoveries/create', recoveryData);
    return response.data;
  } catch (error) {
    console.error('Error in addRecovery function:', error);
    throw error;
  }
};

// 👉 Update Payment
export const updatePayment = async (paymentId: number | string, paymentData: any) => {
  try {
    const response = await axiosInstance.put(`/payments/${paymentId}`, paymentData);
    return response.data;
  } catch (error) {
    console.error('Error updating payment:', error);
    throw error;
  }
};

// 👉 Update Payment
export const updateRecovery = async (paymentId: number | string, paymentData: any) => {
  try {
    const response = await axiosInstance.put(`/payments/recoveries/${paymentId}`, paymentData);
    return response.data;
  } catch (error) {
    console.error('Error updating recovery:', error);
    throw error;
  }
};

// 👉 Delete Payment
export const deletePayment = async (paymentId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/payments/${paymentId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting payment:', error);
    throw error;
  }
};


// 👉 Delete Recovery
export const deleteRecovery = async (paymentId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/payments/recoveries/${paymentId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting recovery:', error);
    throw error;
  }
};


// 👉 Fetch Static data
export const fetchStaticData = async () => {
  try {
    const response = await axiosInstance.get('/staticdatapayement');
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};

// 👉 Fetch Static data
// 👉 Fetch Static data
export const fetchRecoveriesStaticData = async (paymentTypeId? : any, clientId? : any, recoveryId? : any) => {
  try {
    const response = await axiosInstance.get('/staticdatarecoveries', {
      params: {
        payment_type_id: paymentTypeId,
        client_id: clientId,
        recovery_id: recoveryId
      }
    });
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};

// 👉 Export Payments
export const exportPayments = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/payement?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting payments:", error);
    throw error;
  }
};

// 👉 Generate pdf payment by ID
export const GeneratePaymentPdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/payment/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating payment pdf by ID:", error);
    throw error;
  }
};

// 👉 Stats
export const TotalTurnover= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats/total-turnover`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching total turnover:", error);
    throw error;
  }
};
export const InvoiceTurnover= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats/invoice-turnover`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching invoice turnover:", error);
    throw error;
  }
};
export const OrderReceiptTurnover= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats/order-receipt-turnover`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching order receipt turnover:", error);
    throw error;
  }
};



export const RealTurnover= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats/real-turnover`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching real turnover:", error);
    throw error;
  }
};

export const Recovery= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats/recovery`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching recovery:", error);
    throw error;
  }
};

export const TopSixCities= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats/top-cities`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching top six cities:", error);
    throw error;
  }
};
export const TopSixClients= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats/top-clients`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching top six clients:", error);
    throw error;
  }
};

export const TopSixActivitySectors= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats/top-activity-sectors`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching top six activity sectors:", error);
    throw error;
  }
};

export const TotalTurnoverByClientType= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats/total-turnover-by-client-type`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching total turnover by client type:", error);
    throw error;
  }
};

export const PaymentStats= async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/payments/stats`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching payment stats:", error);
    throw error;
  }
};


