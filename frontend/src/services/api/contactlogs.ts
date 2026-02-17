import { axiosInstance } from '@/plugins/axios';


// 👉 Fetch ContactLogss
export const fetchContactLogss = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`contacts/contactlogs/all?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching contactlogss:", error);
    throw error;
  }
};

// 👉 Get ContactLogs by ID
export const fetchContactLogsById = async (contactlogsId: number | string) => {
  try {
    const response = await axiosInstance.get(`contacts/contactlogs/${contactlogsId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching contactlogs by ID:", error);
    throw error;
  }
};






