import { axiosInstance } from '@/plugins/axios';
import { Client } from '@services/models';

// 👉 Fetch clients 
export const fetchClients = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/client?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching clients:", error);
    throw error;
  }
};
// 👉 Fetch archived clients 
export const fetchArchivedClients = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/client/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching clients:", error);
    throw error;
  }
};

// 👉 Get client by ID
export const fetchClientById = async (clientId: number | string, filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/client/${clientId}` ,{params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching client by ID:", error);
    throw error;
  }
};
// 👉 Get archived client by ID
export const fetchArchivedClientById = async (clientId: number | string, filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/client/${clientId}/archive` ,{params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching client by ID:", error);
    throw error;
  }
};

// 👉 Add client
export const addClient = async (clientData: Client) => {
  try {
    const response = await axiosInstance.post('/client/create', clientData);
    return response.data; // Return the client ID
  } catch (error) {
    console.error('Error in addclient function:', error);
    throw error;
  }
};

// 👉 Update client
export const updateClient = async (clientId: string, clientData: Client) => {
  try {
    const response = await axiosInstance.put(`/client/${clientId}`, clientData);
    return response.data;
  } catch (error) {
    console.error('Error updating client:', error);
    throw error;
  }
};

// 👉 Delete client
export const deleteClient = async (clientId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/client/${clientId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting client:', error);
    throw error;
  }
};

// 👉 Add Client Image
export const addClientImage = async (clientId: string,clientData: FormData) => {
  try {
    const response = await axiosInstance.post(`/client/${clientId}/upload_logo`, clientData);
    return response.data;
  } catch (error) {
    console.error('Error in addClientImage function:', error);
    throw error;
  }
};
// 👉 Add Client Image
export const updateClientImage = async (clientId: string,clientData: FormData) => {
  try {
    const response = await axiosInstance.post(`/client/${clientId}/update_logo`, clientData);
    return response.data;
  } catch (error) {
    console.error('Error in updateClientImage function:', error);
    throw error;
  }
};

// 👉 Fetch Static data
export const fetchStaticData = async () => {
  try {
    const response = await axiosInstance.get('/staticdata');
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};


// 👉 Export clients 
export const exportClients = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/client?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting clients:", error);
    throw error;
  }
};

// 👉 Stats
export const NumberOfClientsByActivitySector = async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/clients/bybusiness`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching nb client by activity sector:", error);
    throw error;
  }
};

export const NumberOfClientsByType = async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/clients/bytype`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching nb client by client type:", error);
    throw error;
  }
};

export const NumberOfClientsByGamut = async (filters: any = {}) => {
  try {
    const response = await axiosInstance.post(`/clients/stats/gamut`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching nb client by gamut:", error);
    throw error;
  }
};
