import { logoutUser } from '@/services/api/user';
import { useAuthStore } from '@/stores/auth';
import { useUserStore } from '@/stores/user';
import { router } from '@/plugins/1.router';

export const handleLogout = async () => {
  const authStore = useAuthStore();
  const userStore = useUserStore(); 
  
  authStore.setIsLogout(true);

  try {
    await logoutUser();
    authStore.logout(); 

    userStore.closeDrawer(); 
    userStore.users = []; 
    localStorage.removeItem('selectedUser'); 

    localStorage.setItem('isAuthenticated', 'false');
    localStorage.setItem('isLogout', 'true');

    setTimeout(() => {
      router.replace('/login');
    }, 100); 
  } catch (error) {
    console.error('Logout failed:', error);
  }
};

