import apiClient from './client';
import { LoginResponse, BootstrapResponse } from '../types/api';

export const login = async (
  email: string,
  password: string,
  deviceName: string = 'mobile'
): Promise<LoginResponse> => {
  const response = await apiClient.post('/auth/login', {
    username: email,
    password,
    device_name: deviceName,
  });
  return response.data;
};

export const logout = async (): Promise<void> => {
  await apiClient.post('/auth/logout');
};

export const getBootstrap = async (): Promise<BootstrapResponse> => {
  const response = await apiClient.get('/bootstrap');
  return response.data;
};
