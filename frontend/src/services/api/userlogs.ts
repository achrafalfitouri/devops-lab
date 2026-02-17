import { axiosInstance } from '@/plugins/axios';


// 👉 Fetch UserLogss
export const fetchUserLogss = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`user/userlogs?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching userlogss:", error);
    throw error;
  }
};

// 👉 Get UserLogs by ID
export const fetchUserLogsById = async (userlogsId: number | string) => {
  try {
    const response = await axiosInstance.get(`user/userlogs/${userlogsId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching userlogs by ID:", error);
    throw error;
  }
};


