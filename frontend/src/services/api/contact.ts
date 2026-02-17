import { axiosInstance } from '@/plugins/axios';
import { Contacts } from '@services/models';

// 👉 Fetch contactss 
export const fetchContacts = async (clientId : any) => {
  try {
    const response = await axiosInstance.post(`/contacts`,
      {
        clientId: clientId
      }
    );
    return response.data;
  } catch (error) {
    console.error("Error fetching contacts:", error);
    throw error;
  }
};

// 👉 Get contacts by ID
export const fetchContactsById = async (contactsId: number | string) => {
  try {
    const response = await axiosInstance.get(`/contacts/${contactsId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching contacts by ID:", error);
    throw error;
  }
};

// 👉 Add contacts
export const addContacts = async (contactsData: Contacts) => {
  try {
    const response = await axiosInstance.post('/contacts/create/contact', contactsData);
    return response.data; // Return the contacts ID
  } catch (error) {
    console.error('Error in addcontacts function:', error);
    throw error;
  }
};

// 👉 Update contacts
export const updateContacts = async (contactsId: string, contactsData: Contacts) => {
  try {
    const response = await axiosInstance.put(`/contacts/${contactsId}`, contactsData);
    return response.data;
  } catch (error) {
    console.error('Error updating contacts:', error);
    throw error;
  }
};

// 👉 Delete contacts
export const deleteContacts = async (contactsId: any) => {
  try {
    const response = await axiosInstance.delete(`/contacts/${contactsId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting contacts:', error);
    throw error;
  }
};

// 👉 Fetch Static data
export const fetchStaticData = async () => {
  try {
    const response = await axiosInstance.get('/staticdatacontact');
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};
