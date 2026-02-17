import { useCookie } from '@/@core/composable/useCookie';
import { router } from '@/plugins/1.router';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';
import { camelCase, snakeCase } from 'lodash';

// 👉 Create Axios instance
const axiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  withCredentials: true
});

type MappingFunction = (key: string) => string;

// 👉 Request Interceptor
axiosInstance.interceptors.request.use(
  async config => {
    // 👉  Get the CSRF token from the cookie when making non-GET requests
    if ((config.method as string).toLowerCase() !== 'get') {
      await axiosInstance.get('/csrf-cookie')
      const csrfCookie = useCookie('XSRF-TOKEN');
      const csrfToken = csrfCookie.value ?? null;
      config.headers['X-XSRF-TOKEN'] = csrfToken
    }

    // Skip transformation for FormData
    if (!(config.data instanceof FormData)) {
      if (config.data) {
        config.data = mapKeysRecursive(config.data, snakeCase);
      }
    }
    return config;
  },
  error => Promise.reject(error)
);

// 👉 Response Interceptor
// Response Interceptor
axiosInstance.interceptors.response.use(
  response => {
    if (response.data) {
      response.data = mapKeysRecursive(response.data, camelCase);
    }
    return response;
  },
  error => {
    const authStore = useAuthStore();
    if (error.response && error.response.status === 401) {
      authStore.logout();
      router.push({ name: 'login' });
    }
    return Promise.reject(error);
  }
);



function mapKeysRecursive(obj: any, mappingFn: MappingFunction): any {
  if (Array.isArray(obj)) {
    return obj.map(item => mapKeysRecursive(item, mappingFn));
  } else if (obj !== null && typeof obj === 'object') {
    return Object.keys(obj).reduce((acc, key) => {
      const mappedKey = mappingFn(key);
      acc[mappedKey] = mapKeysRecursive(obj[key], mappingFn);
      return acc;
    }, {} as any);
  }
  return obj;
}

export { axiosInstance };
