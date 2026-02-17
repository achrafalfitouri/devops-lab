import { defineStore } from 'pinia';
import { useStorage } from '@vueuse/core';
import { User } from '@/services/models';

interface AuthState {
  isAuthenticated: boolean;
  roles: Array<string>;
  permissions: Array<string>;
  cashregisters: Array<string>;
  user: User | any | null ;
  isLogout: boolean; 
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    isAuthenticated: useStorage('isAuthenticated', false).value,
    roles: [],  // Parse the stored JSON string to an array
    permissions: JSON.parse(useStorage('userRoles', '[]').value), 
    cashregisters: JSON.parse(useStorage('userCashRegisters', '[]').value), 
    user: JSON.parse(useStorage('user', '[]').value),
    isLogout: useStorage('isLogout', false).value
  }),
  actions: {
    login() {
      this.isAuthenticated = true;
      this.isLogout = false;
    },
    permissionCheck(roles: Array<string>, permissions: Array<string>) {
      this.roles = roles;
      this.permissions = permissions;
      // Store the updated roles array as a JSON string
      localStorage.setItem('userRoles', JSON.stringify(permissions));
    },
    CashCheck(cashregisters: Array<string>) {
      this.cashregisters = cashregisters;
  
      // Store the updated roles array as a JSON string
      localStorage.setItem('userCashRegisters', JSON.stringify(cashregisters));
    },
    UserCheck(user: User) {
      this.user = user;
  
      // Store the updated roles array as a JSON string
      localStorage.setItem('user', JSON.stringify(user));
    },
    logout() {
      this.isAuthenticated = false;
      this.roles = [];
      this.permissions = [];
      this.cashregisters = [];
      this.user = null;
      this.isLogout = true;
      // Clear the storage
      localStorage.removeItem('userRoles');
      localStorage.removeItem('userCashRegisters');
      localStorage.removeItem('user');
      localStorage.removeItem('isAuthenticated');
      localStorage.removeItem('isLogout');
    },
    setIsLogout(value: boolean) {
      this.isLogout = value; 
    }
  },
});
