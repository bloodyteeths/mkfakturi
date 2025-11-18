import apiClient from './client';
import { Customer, CreateCustomerPayload } from '../types/api';

export const getCustomers = async (search?: string) => {
  const response = await apiClient.get('/customers', { params: { search } });
  return response.data;
};

export const getCustomer = async (id: number): Promise<Customer> => {
  const response = await apiClient.get(`/customers/${id}`);
  return response.data.data;
};

export const createCustomer = async (payload: CreateCustomerPayload): Promise<Customer> => {
  const response = await apiClient.post('/customers', payload);
  return response.data.data;
};
