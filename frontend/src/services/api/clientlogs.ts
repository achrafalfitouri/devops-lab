import { axiosInstance } from '@/plugins/axios';


// 👉 Fetch ClientLogss
export const fetchClientLogss = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`client/clientlogs?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching clientlogss:", error);
    throw error;
  }
};

// 👉 Get ClientLogs by ID
export const fetchClientLogsById = async (clientlogsId: number | string) => {
  try {
    const response = await axiosInstance.get(`client/clientlogs/${clientlogsId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching clientlogs by ID:", error);
    throw error;
  }
};





