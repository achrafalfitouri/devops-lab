import { axiosInstance } from '@/plugins/axios';
import { ProductionNotes } from '@services/models';

// 👉 Fetch productionnotes 
export const fetchProductionNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/productionnotes?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching productionnotes:", error);
    throw error;
  }
};
// 👉 Fetch archived  productionnotes 
export const fetchArchivedProductionNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/productionnotes/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching productionnotes:", error);
    throw error;
  }
};

// 👉 Get productionnotes by ID
export const fetchProductionNotesById = async (productionnotesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/productionnotes/${productionnotesId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching productionnotes by ID:", error);
    throw error;
  }
};
// 👉 Get archived productionnotes by ID
export const fetchArchivedProductionNotesById = async (productionnotesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/productionnotes/${productionnotesId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching productionnotes by ID:", error);
    throw error;
  }
};

// 👉 Add productionnotes
export const addProductionNotes = async (productionnotesData: ProductionNotes) => {
  try {
    const response = await axiosInstance.post('/productionnotes/create', productionnotesData);
    return response.data; // Return the productionnotes ID
  } catch (error) {
    console.error('Error in addproductionnotes function:', error);
    throw error;
  }
};

// 👉 Update productionnotes
export const updateProductionNotes = async (productionnotesId: string, productionnotesData: ProductionNotes) => {
  try {
    const response = await axiosInstance.put(`/productionnotes/${productionnotesId}`, productionnotesData);
    return response.data;
  } catch (error) {
    console.error('Error updating productionnotes:', error);
    throw error;
  }
};

// 👉 Update status Production notes
export const updateStatusProductionNotes = async (productionnoteId: string | string[] | undefined, productionnoteData: ProductionNotes) => {
  try {
    const response = await axiosInstance.put(`/productionnotes/status/${productionnoteId}`, productionnoteData);
    return response.data;
  } catch (error) {
    console.error('Error updating Production notes status:', error);
    throw error;
  }
};

// 👉 Delete productionnotes
export const deleteProductionNotes = async (productionnotesId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/productionnotes/${productionnotesId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting productionnotes:', error);
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

// 👉 Export productionnotes 
export const exportProductionNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/productionnote?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting productionnotes:", error);
    throw error;
  }
};

// 👉 Get production note by ID
export const GenerateProductionNoteById = async (param: { ids: any[] } | string | number) => {
  try {
    const payload = typeof param === "object" ? param : { ids: [param] };    
    const response = await axiosInstance.post(`/productionnotes/generate-document`, payload);
    return response.data;
  } catch (error) {
    console.error("Error generating production note by ID:", error);
    throw error;
  }
};



// 👉 Generate pdf production by ID
export const GenerateProductionNotePdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/production/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating production note pdf by ID:", error);
    throw error;
  }
};


export const DuplicateProductionNote = async (productionNoteId?: string) => {
  try {
    const response = await axiosInstance.post("/productionnotes/duplicate", {
     id : productionNoteId 
    });

    return response.data;
  } catch (error) {
    console.error("Error duplicating production note:", error);
    throw error;
  }
};

