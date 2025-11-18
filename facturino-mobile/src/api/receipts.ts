import apiClient from './client';
import { ReceiptScanResult } from '../types/api';

export const scanReceipt = async (imageUri: string): Promise<ReceiptScanResult> => {
  const formData = new FormData();
  formData.append('image', {
    uri: imageUri,
    type: 'image/jpeg',
    name: 'receipt.jpg',
  } as any);

  const response = await apiClient.post('/receipts/scan', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data;
};

export const createExpense = async (data: any) => {
  const response = await apiClient.post('/expenses', data);
  return response.data;
};
