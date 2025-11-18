import apiClient from './client';
import { Invoice, CreateInvoicePayload } from '../types/api';

export const getInvoices = async (filters?: { status?: string; search?: string; page?: number }) => {
  const response = await apiClient.get('/invoices', { params: filters });
  return response.data;
};

export const getInvoice = async (id: number): Promise<Invoice> => {
  const response = await apiClient.get(`/invoices/${id}`);
  return response.data.data;
};

export const createInvoice = async (payload: CreateInvoicePayload): Promise<Invoice> => {
  const response = await apiClient.post('/invoices', payload);
  return response.data.data;
};

export const sendInvoice = async (id: number): Promise<void> => {
  await apiClient.post(`/invoices/${id}/send`);
};

export const downloadInvoicePDF = async (id: number): Promise<string> => {
  const response = await apiClient.get(`/invoices/${id}/download-pdf`, {
    responseType: 'blob',
  });
  return response.data;
};
