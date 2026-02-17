import { axiosInstance } from '@/plugins/axios';
import { OutputNotes } from '@services/models';

// 👉 Fetch outputnotes 
export const fetchOutputNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/outputnotes?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching outputnotes:", error);
    throw error;
  }
};
// 👉 Fetch archived outputnotes 
export const fetchArchivedOutputNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/outputnotes/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching outputnotes:", error);
    throw error;
  }
};

// 👉 Get outputnotes by ID
export const fetchOutputNotesById = async (outputnotesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/outputnotes/${outputnotesId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching outputnotes by ID:", error);
    throw error;
  }
};
// 👉 Get archived outputnotes by ID
export const fetchArchivedOutputNotesById = async (outputnotesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/outputnotes/${outputnotesId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching outputnotes by ID:", error);
    throw error;
  }
};

// 👉 Add outputnotes
export const addOutputNotes = async (outputnotesData: OutputNotes) => {
  try {
    const response = await axiosInstance.post('/outputnotes/create', outputnotesData);
    return response.data; // Return the outputnotes ID
  } catch (error) {
    console.error('Error in addoutputnotes function:', error);
    throw error;
  }
};

// 👉 Update outputnotes
export const updateOutputNotes = async (outputnotesId: string, outputnotesData: OutputNotes) => {
  try {
    const response = await axiosInstance.put(`/outputnotes/${outputnotesId}`, outputnotesData);
    return response.data;
  } catch (error) {
    console.error('Error updating outputnotes:', error);
    throw error;
  }
};

// 👉 Delete outputnotes
export const deleteOutputNotes = async (outputnotesId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/outputnotes/${outputnotesId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting outputnotes:', error);
    throw error;
  }
};

// 👉 Update status OutputNotes
export const updateStatusOutputNotes = async (outputnotesId: string | string[] | undefined, outputnoteData: OutputNotes) => {
  try {
    const response = await axiosInstance.put(`/outputnotes/status/${outputnotesId}`, outputnoteData);
    return response.data;
  } catch (error) {
    console.error('Error updating Output Notes status:', error);
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

// 👉 Export outputnotes 
export const exportOutputNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/outputnote?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting outputnotes:", error);
    throw error;
  }
};


// 👉 Get output note note by ID
export const GenerateOutputNoteById = async (param: { ids: any[] } | string | number) => {
  try {
    const payload = typeof param === "object" ? param : { ids: [param] };    
    const response = await axiosInstance.post(`/outputnotes/generate-document`, payload);
    return response.data;
  } catch (error) {
    console.error("Error generating output note by ID:", error);
    throw error;
  }
};

// 👉 Generate pdf Output note by ID
export const GenerateOutputNotePdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/output/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating output note pdf by ID:", error);
    throw error;
  }
};
