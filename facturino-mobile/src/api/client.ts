import axios from 'axios';
import { getToken, getCompany } from '../utils/storage';

const API_BASE_URL = 'https://your-api-domain.com/api/v1';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor - add auth token and company header
apiClient.interceptors.request.use(
  async (config) => {
    const token = await getToken();
    const company = await getCompany();

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    if (company) {
      config.headers.company = company.id.toString();
    }

    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - handle 401 unauthorized
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Token expired or invalid - trigger logout
      // Will be handled by AuthContext
    }
    return Promise.reject(error);
  }
);

export default apiClient;
