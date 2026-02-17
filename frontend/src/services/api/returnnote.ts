import { axiosInstance } from '@/plugins/axios';
import { ReturnNotes } from '@services/models';

// 👉 Fetch returnnotes 
export const fetchReturnNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/returnnotes?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching returnnotes:", error);
    throw error;
  }
};

// 👉 Fetch archive returnnotes 
export const fetchArchivedReturnNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/returnnotes/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching returnnotes:", error);
    throw error;
  }
};

// 👉 Get returnnotes by ID
export const fetchReturnNotesById = async (returnnotesId: number | string,filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/returnnotes/${returnnotesId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching returnnotes by ID:", error);
    throw error;
  }
};
// 👉 Get returnnotes by ID
export const fetchArchivedReturnNotesById = async (returnnotesId: number | string,filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/returnnotes/${returnnotesId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching returnnotes by ID:", error);
    throw error;
  }
};

// 👉 Add returnnotes
export const addReturnNotes = async (returnnotesData: ReturnNotes) => {
  try {
    const response = await axiosInstance.post('/returnnotes/create', returnnotesData);
    return response.data; // Return the returnnotes ID
  } catch (error) {
    console.error('Error in addreturnnotes function:', error);
    throw error;
  }
};

// 👉 Update returnnotes
export const updateReturnNotes = async (returnnotesId: string, returnnotesData: ReturnNotes) => {
  try {
    const response = await axiosInstance.put(`/returnnotes/${returnnotesId}`, returnnotesData);
    return response.data;
  } catch (error) {
    console.error('Error updating returnnotes:', error);
    throw error;
  }
};

// 👉 Update status ReturnNotes
export const updateStatusReturnNotes = async (returnnotesId: string | string[] | undefined, ReturnNotesData: ReturnNotes) => {
  try {
    const response = await axiosInstance.put(`/returnnotes/status/${returnnotesId}`, ReturnNotesData);
    return response.data;
  } catch (error) {
    console.error('Error updating ReturnNotes Data status:', error);
    throw error;
  }
};


// 👉 Delete returnnotes
export const deleteReturnNotes = async (returnnotesId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/returnnotes/${returnnotesId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting returnnotes:', error);
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

// 👉 Export returnnotes 
export const exportReturnNotes= async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/returnenote?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting returnnotes:", error);
    throw error;
  }
};

// 👉 Generate pdf Return Note by ID
export const GenerateReturnNotePdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/return/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating return note pdf by ID:", error);
    throw error;
  }
};
