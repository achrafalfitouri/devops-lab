import { axiosInstance } from "@/plugins/axios";
import { Email } from "../models";

interface EmailData {
  to: string;
  subject: string;
  message: string;
}
// 👉 send Email
export const sendEmail= async (emailData: EmailData) => {


  try {
    const response = await axiosInstance.post("/email/send",emailData);
    return response.data;
  } catch (error) {
    console.error("Error sending email:", error);
    throw error;
  }
};

// 👉 Fetch Static data client
export const fetchStaticDataClient = async () => {
  try {
    const response = await axiosInstance.get('/staticdataclientforemail');
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};
// 👉 Fetch Static data clientcontact
export const fetchStaticDataClientContact = async (ClientId : any) => {
  try {
    const response = await axiosInstance.get(`/staticdataclientcontactforemail/${ClientId}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};
// 👉 Fetch Static data 
export const fetchStaticDataClientDocumentCode   = async (ClientId : any,DocumentType : any) => {
  try {
    const response = await axiosInstance.get(`/staticdataclientdocumentcodesforemail/${ClientId}/${DocumentType}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};
// 👉 Fetch Static data template 
export const fetchStaticDataEmailTemplate   = async () => {
  try {
    const response = await axiosInstance.get(`/staticdatatemplate`);
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};


// 👉 Fetch email template by ID
export const fetchSelectedEmailTemplate = async (clientId: string | null, contactId: string | null,document : any,documentCode : string | null,template:string | null) => {
  try {
    const response = await axiosInstance.post("/email/return-template", {
      clientId,
      contactId,
      document,
       documentCode,
       template
    });

    return response.data;
  } catch (error) {
    console.error("Error fetching template:", error);
    throw error;
  }
};


// 👉 Fetch email 
export const fetchEmails = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/emails?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching emails:", error);
    throw error;
  }
};
