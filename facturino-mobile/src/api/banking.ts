import apiClient from './client';
import { BankAccount, BankTransaction } from '../types/api';

export const getBankAccounts = async (): Promise<BankAccount[]> => {
  const response = await apiClient.get('/banking/accounts');
  return response.data.data || [];
};

export const getBankTransactions = async (accountId: number, filters?: any) => {
  const response = await apiClient.get('/banking/transactions', {
    params: { account_id: accountId, ...filters },
  });
  return response.data.data || [];
};
