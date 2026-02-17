import { axiosInstance } from '@/plugins/axios';
import { CashRegister } from '../models';

// 👉 Create Cash Register
export const addCashRegister = async (cashRegisterData: any) => {
  try {
    const response = await axiosInstance.post('/cash-registers/create', cashRegisterData);
    return response.data;
  } catch (error) {
    console.error('Error creating cash register:', error);
    throw error;
  }
};

// 👉 Update Cash Register by ID
export const updateCashRegister = async (id: number | string, cashRegisterData: any) => {
  try {
    const response = await axiosInstance.put(`/cash-registers/${id}`, cashRegisterData);
    return response.data;
  } catch (error) {
    console.error('Error updating cash register:', error);
    throw error;
  }
};

// 👉 Update status cash-registers
export const updateStatusCashregister = async (cashregisterId: string, cashregisterData: { status: boolean }) => {
  try {
    // Make PUT request with the status payload
    const response = await axiosInstance.put(`/cash-registers/status/${cashregisterId}`, cashregisterData);
    return response.data;
  } catch (error) {
    console.error('Error updating cashregister:', error);
    throw error;
  }
};


// 👉 Soft Delete Cash Register by ID
export const deleteCashRegister = async (id: number | string) => {
  try {
    const response = await axiosInstance.delete(`/cash-registers/${id}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting cash register:', error);
    throw error;
  }
};

// 👉 Get Combined Cash Registers and Transactions
export const getAllCashRegistersAndTransactions = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/cash-registers?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error('Error fetching cash registers and transactions:', error);
    throw error;
  }
};

// 👉 Get archived Combined Cash Registers and Transactions
export const getArchivedAllCashRegistersAndTransactions = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/cash-registers/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error('Error fetching cash registers and transactions:', error);
    throw error;
  }
};
