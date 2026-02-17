import { axiosInstance } from '@/plugins/axios';
import { Quotes } from '@services/models';

// 👉 Fetch quotes 
export const fetchQuotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/quotes?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching quotes:", error);
    throw error;
  }
};
// 👉 Fetch archived quotes 
export const fetchArchivedQuotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/quotes/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching quotes:", error);
    throw error;
  }
};

// 👉 Get quotes by ID
export const fetchQuotesById = async (quotesId: number | string,
  filters: any = {}
) => {
  try {
    const response = await axiosInstance.get(`/quotes/${quotesId}`, { params: filters });
    return response.data;
  } catch (error) {
    console.error("Error fetching quotes by ID:", error);
    throw error;
  }
};
// 👉 Get archived quotes by ID
export const fetchArchivedQuotesById = async (quotesId: number | string,
  filters: any = {}
) => {
  try {
    const response = await axiosInstance.get(`/quotes/${quotesId}/archive`, { params: filters });
    return response.data;
  } catch (error) {
    console.error("Error fetching quotes by ID:", error);
    throw error;
  }
};

// 👉 Add quotes
export const addQuotes = async (quotesData: Quotes) => {
  try {
    const response = await axiosInstance.post('/quotes/create', quotesData);
    return response.data; // Return the quotes ID
  } catch (error) {
    console.error('Error in addquotes function:', error);
    throw error;
  }
};

// 👉 Update quotes
export const updateQuotes = async (quotesId: string, quotesData: Quotes) => {
  try {
    const response = await axiosInstance.put(`/quotes/${quotesId}`, quotesData);
    return response.data;
  } catch (error) {
    console.error('Error updating quotes:', error);
    throw error;
  }
};
// 👉 Update status quotes
export const updateStatusQuotes = async (quotesId: string | string[] | undefined, quotesData: Quotes) => {
  try {
    const response = await axiosInstance.put(`/quotes/status/${quotesId}`, quotesData);
    return response.data;
  } catch (error) {
    console.error('Error updating quotes:', error);
    throw error;
  }
};

// 👉 Delete quotes
export const deleteQuotes = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/quotes/${quotesId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting quotes:', error);
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

// 👉 Export quotes 
export const exportQuotes = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/quote?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting quotes:", error);
    throw error;
  }
};


// 👉 Get quotes by ID
export const GenerateQuoteById = async (param: { ids: any[] } | string | number) => {
  try {
    const response = await axiosInstance.post(`/quotes/${param}/generate-document`);
    return response.data;
  } catch (error) {
    console.error("Error generating quotes by ID:", error);
    throw error;
  }
};


// 👉 Generate pdf quote by ID
export const GenerateQuotePdfById = async (quotesId: number | string) => {
  try {
    const response = await axiosInstance.get(`/quote/${quotesId}`);
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


export const generateDocumentNavigation = async (
  whereIamGoing: string,
  quoteId?: string,
  processGroupId?: string
) => {
  try {
    const response = await axiosInstance.post("/generate-document-navigation", {
      where_iam_going: whereIamGoing,
      quote_id: quoteId,
      process_group_id: processGroupId
    });

    return response.data.documentId;
  } catch (error) {
    console.error("Error generating document:", error);
    throw error;
  }
};

export const DuplicateQuote = async (quoteId?: string) => {
  try {
    const response = await axiosInstance.post("/quotes/duplicate", {
      quoteId
    });

    return response.data;
  } catch (error) {
    console.error("Error duplicating quote:", error);
    throw error;
  }
};

