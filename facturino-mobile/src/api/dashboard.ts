import apiClient from './client';
import { DashboardStats } from '../types/api';

export const getDashboardStats = async (): Promise<DashboardStats> => {
  const response = await apiClient.get('/dashboard');
  return response.data;
};
