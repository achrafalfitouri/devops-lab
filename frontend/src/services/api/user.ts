import { axiosInstance } from '@/plugins/axios';
import { User } from '@services/models';

// 👉 Fetch Users
export const fetchUsers = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`/user?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching users:", error);
    throw error;
  }
};
// 👉 Fetch archived Users
export const fetchArchivedUsers = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`user/archive?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error fetching users:", error);
    throw error;
  }
};

// 👉 Get User by ID
export const fetchUserById = async (userId: number | string,filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/user/${userId}`, {params: filters}
    );
    return response.data;
  } catch (error) {
    console.error("Error fetching user by ID:", error);
    throw error;
  }
};
// 👉 Get User by ID
export const fetchUserProfilById = async (userId: number | string,filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/user/profil/${userId}`, {params: filters}
    );
    return response.data;
  } catch (error) {
    console.error("Error fetching user by ID:", error);
    throw error;
  }
};
// 👉 Get archived user by ID
export const fetchArchivedUserById = async (userId: number | string,filters: any = {}) => {
  try {
    const response = await axiosInstance.get(`/user/${userId}/archive`, {params: filters}
    );
    return response.data;
  } catch (error) {
    console.error("Error fetching user by ID:", error);
    throw error;
  }
};

// 👉 Add User
export const addUser = async (userData: User) => {
  try {
    const response = await axiosInstance.post('/user/create', userData);
    return response.data;
  } catch (error) {
    console.error('Error in addUsers function:', error);
    throw error;
  }
};

// 👉 Update User
export const updateUser = async (userId: number | string, userData: any) => {
  try {
    const response = await axiosInstance.put(`/user/${userId}`, userData);
    return response.data;
  } catch (error) {
    console.error('Error updating user:', error);
    throw error;
  }
};

// 👉 Delete User
export const deleteUser = async (userId: number | string) => {
  try {
    const response = await axiosInstance.delete(`/user/${userId}`);
    return response.data;
  } catch (error) {
    console.error('Error deleting user:', error);
    throw error;
  }
};

// 👉 Add User Image
export const addUserImage = async (userId: string, userData: FormData) => {
  try {
    const response = await axiosInstance.post(`/user/${userId}/upload_photo`, userData);
    return response.data;
  } catch (error) {
    console.error('Error in addUserImage function:', error);
    throw error;
  }
};
// 👉 Add User Image
export const updateUserImage = async (userId: string, userData: FormData) => {
  try {
    const response = await axiosInstance.post(`/user/${userId}/update_photo`, userData);
    return response.data;
  } catch (error) {
    console.error('Error in addUserImage function:', error);
    throw error;
  }
};

// 👉 Login User
export const loginUser = async (credientials: any) => {
  try {
    const response = await axiosInstance.post('/user/login', credientials);
    return response.data;
  } catch (error) {
    console.error("Error login user:", error);
    throw error;
  }
};
// 👉 Password change User
export const passwordChangeRequest = async (credientials: any) => {
  try {
    const response = await axiosInstance.post('/password/request-reset', { email : credientials});
    return response.data;
  } catch (error) {
    console.error("Error password change :", error);
    throw error;
  }
};

// 👉 Logout User
export const logoutUser = async () => {
  try {
    const response = await axiosInstance.post('/user/logout');
    return response.data;
  } catch (error) {
    console.error("Error logout user:", error);
    throw error;
  }
};

// 👉 Check Auth User
export const checkAuthUser = async () => {
  try {
    const response = await axiosInstance.get('/user/check/auth');
    return response.data;
  } catch (error) {
    console.error("Error check auth user:", error);
    throw error;
  }
};

// 👉 Fetch Static data
export const fetchStaticData = async () => {
  try {
    const response = await axiosInstance.get('/staticdatauser');
    return response.data;
  } catch (error) {
    console.error("Error fetching static data:", error);
    throw error;
  }
};

// 👉 Export users
export const exportUsers = async (filters: any = {}, per_page: number, page: number) => {
  try {
    const response = await axiosInstance.post(`export/user?per_page=${per_page}&page=${page}`, filters);
    return response.data;
  } catch (error) {
    console.error("Error exporting users:", error);
    throw error;
  }
};

// 👉 Assign user role
export const assignUserRole = async (userId: number | string, rolesToAssign: any , rolesToRevoke : any) => {
  try {
  
    if (!Array.isArray(rolesToAssign) || !Array.isArray(rolesToRevoke)) {
      throw new Error('Both assign and revoke must be arrays');
    }

    const response = await axiosInstance.post(`/user/${userId}/assign-role`, {
      assign: rolesToAssign,
      revoke: rolesToRevoke
    });
    return response.data;
  } catch (error) {
    console.error('Error assigning or revoking roles:', error);
    throw error;
  }
};

// 👉 Assign user cashregister and roles
export const assignUserCashRegister = async (userId : any, cashRegistersToAssign : any) => {
  try {
    if (!Array.isArray(cashRegistersToAssign)) {
      throw new Error('Both assign and revoke must be arrays');
    }

    const response = await axiosInstance.post(`/user/${userId}/assign`, {
      assign: cashRegistersToAssign,
    });
    return response.data;
  } catch (error) {
    console.error('Error assigning or revoking cashregisters:', error);
    throw error;
  }
};

