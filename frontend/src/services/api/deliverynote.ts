import { axiosInstance } from '@/plugins/axios';
import { DeliveryNotes } from '@services/models';

// 👉 Fetch deliverynotes 
export const fetchDeliveryNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/deliverynotes?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching deliverynotes:", error);
    throw error;
  }
};
// 👉 Fetch archived deliverynotes 
export const fetchArchivedDeliveryNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/deliverynotes/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching deliverynotes:", error);
    throw error;
  }
};

// 👉 Get deliverynotes by ID
export const fetchDeliveryNotesById = async (deliverynotesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/deliverynotes/${deliverynotesId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching deliverynotes by ID:", error);
    throw error;
  }
};
// 👉 Get archived deliverynotes by ID
export const fetchArchivedDeliveryNotesById = async (deliverynotesId: number | string,filters: any = {},) => {
  try {
    const response = await axiosInstance.get(`/deliverynotes/${deliverynotesId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching deliverynotes by ID:", error);
    throw error;
  }
};

// 👉 Add deliverynotes
export const addDeliveryNotes = async (deliverynotesData: DeliveryNotes) => {
  try {
    const response = await axiosInstance.post('/deliverynotes/create', deliverynotesData);
    return response.data; // Return the deliverynotes ID
  } catch (error) {
    console.error('Error in adddeliverynotes function:', error);
    throw error;
  }
};

// 👉 Update deliverynotes
export const updateDeliveryNotes = async (deliverynotesId: string, deliverynotesData: DeliveryNotes) => {
  try {
    const response = await axiosInstance.put(`/deliverynotes/${deliverynotesId}`, deliverynotesData);
    return response.data;
  } catch (error) {
    console.error('Error updating deliverynotes:', error);
    throw error;
  }
};

// 👉 Update status deliverynote
export const updateStatusDeliveryNotes= async (quotesId: string | string[] | undefined, deliveryNoteData: DeliveryNotes) => {
  try {
    const response = await axiosInstance.put(`/deliverynotes/status/${quotesId}`, deliveryNoteData);
    return response.data;
  } catch (error) {
    console.error('Error updating deliverynote:', error);
    throw error;
  }
};

// 👉 Update status deliverynote
export const updateStatusDeliveryNotesItems= async (deliveryId: any,itemId : string, deliveryNoteDataItems: DeliveryNotes) => {
  try {
    const response = await axiosInstance.put(`/deliverynotes/statusitem/${deliveryId}`,
      {
        item : {       
          id : itemId,
          status :deliveryNoteDataItems}

      }
    );
    return response.data;
  } catch (error) {
    console.error('Error updating deliverynote:', error);
    throw error;
  }
};

// 👉 Delete deliverynotes
export const deleteDeliveryNotes = async (deliverynotesId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/deliverynotes/${deliverynotesId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting deliverynotes:', error);
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

// 👉 Export deliverynotes 
export const exportDeliveryNotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/deliverynote?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting deliverynotes:", error);
    throw error;
  }
};

// 👉 Get deliverynote by ID
export const GenerateDeliveryNoteById = async (param: { ids: any[] } | string | number) => {
  try {
    const payload = typeof param === "object" ? param : { ids: [param] };    
    const response = await axiosInstance.post(`/deliverynotes/generate-document`, payload);
    return response.data;
  } catch (error) {
    console.error("Error generating delivery note by ID:", error);
    throw error;
  }
};

// 👉 Get deliverynote by ID
export const GenerateWithMultipleDeliveryId = async (param: { ids: any[] } | string | number) => {
  try {
    const payload = typeof param === "object" ? param : { ids: [param] };    
    const response = await axiosInstance.post(`/deliverynotes/generate-document-different-delivery`, payload);
    return response.data;
  } catch (error) {
    console.error("Error generating delivery note by ID:", error);
    throw error;
  }
};

// 👉 Generate pdf deliverynote by ID
export const GenerateDeliveryNotePdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/delivery/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating delivery note pdf by ID:", error);
    throw error;
  }
};
