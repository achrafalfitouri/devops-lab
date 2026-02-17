import { axiosInstance } from '@/plugins/axios';
import { QuoteRequests } from '@services/models';

// 👉 Fetch quoterequests 
export const fetchQuoteRequests = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/quoterequests?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching quoterequests:", error);
    throw error;
  }
};
// 👉 Fetch archived quoterequests 
export const fetchArchivedQuoteRequests = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/quoterequests/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching quoterequests:", error);
    throw error;
  }
};

// 👉 Get quoterequests by ID
export const fetchQuoteRequestsById = async (quoterequestsId: number | string,
  filters: any = {}
) => {
  try {
    const response = await axiosInstance.get(`/quoterequests/${quoterequestsId}`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching quoterequests by ID:", error);
    throw error;
  }
};
// 👉 Get archived quoterequests by ID
export const fetchArchivedQuoteRequestsById = async (quoterequestsId: number | string,
  filters: any = {}
) => {
  try {
    const response = await axiosInstance.get(`/quoterequests/${quoterequestsId}/archive`, {params: filters});
    return response.data;
  } catch (error) {
    console.error("Error fetching quoterequests by ID:", error);
    throw error;
  }
};

// 👉 Add quoterequests
export const addQuoteRequests = async (quoterequestsData: QuoteRequests ) => {
  try {
    const response = await axiosInstance.post('/quoterequests/create', quoterequestsData);
    return response.data; // Return the quoterequests ID
  } catch (error) {
    console.error('Error in addquoterequests function:', error);
    throw error;
  }
};

// 👉 Update quoterequests
export const updateQuoteRequests = async (quoterequestsId: string, quoterequestsData: QuoteRequests) => {
  try {
    const response = await axiosInstance.put(`/quoterequests/${quoterequestsId}`, quoterequestsData);
    return response.data;
  } catch (error) {
    console.error('Error updating quoterequests:', error);
    throw error;
  }
};
// 👉 Update status quoterequests
export const updateStatusQuoteRequests = async (quoterequestsId: string | string[] | undefined, quoterequestsData: QuoteRequests) => {
  try {
    const response = await axiosInstance.put(`/quoterequests/status/${quoterequestsId}`, quoterequestsData);
    return response.data;
  } catch (error) {
    console.error('Error updating quoterequests:', error);
    throw error;
  }
};

// 👉 Delete quoterequests
export const deleteQuoteRequests = async (quoterequestsId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/quoterequests/${quoterequestsId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting quoterequests:', error);
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

// 👉 Export quoterequests 
export const exportQuoteRequests = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/quoterequest?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting quoterequests:", error);
    throw error;
  }
};


// 👉 Get quoterequests by ID
export const GenerateQuoteById = async (param: { ids: any[] } | string | number) => {
  try {
    const response = await axiosInstance.post(`/quoterequests/${param}/generate-document`);
    return response.data;
  } catch (error) {
    console.error("Error generating quoterequests by ID:", error);
    throw error;
  }
};


// 👉 Generate pdf quote by ID
export const GenerateQuotePdfById = async (quoterequestsId: number | string) => {
  try {
    const response = await axiosInstance.get(`/quote/${quoterequestsId}`);
    return response.data;
  } catch (error) {
    console.error("Error generating quote  pdf by ID:", error);
    throw error;
  }
};

// 👉 Get details
export const details = async () => {
  try {
    const response = await axiosInstance.get(`/details`);
    return response.data;
  } catch (error) {
    console.error("Error getting details :", error);
    throw error;
  }
};


export const generateDocumentNavigation = async (whereIamGoing: string, quoteId?: string) => {
  try {
    const response = await axiosInstance.post("/generate-document-navigation", {
      whereIamGoing,
      quoteId 
    });

    return response.data.documentId;
  } catch (error) {
    console.error("Error generating document:", error);
    throw error;
  }
};

export const DuplicateQuoteRequest = async (quoteRequestId?: string) => {
  try {
    const response = await axiosInstance.post("/quoterequests/duplicate", {
      quoteRequestId 
    });

    return response.data;
  } catch (error) {
    console.error("Error duplicating quote:", error);
    throw error;
  }
};

