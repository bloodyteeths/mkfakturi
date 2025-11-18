import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Company } from '../types/api';

const TOKEN_KEY = 'facturino_token';
const COMPANY_KEY = 'facturino_company';

// Token storage (encrypted)
export const saveToken = async (token: string): Promise<void> => {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
};

export const getToken = async (): Promise<string | null> => {
  return await SecureStore.getItemAsync(TOKEN_KEY);
};

export const deleteToken = async (): Promise<void> => {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
};

// Company storage (non-sensitive)
export const saveCompany = async (company: Company): Promise<void> => {
  await AsyncStorage.setItem(COMPANY_KEY, JSON.stringify(company));
};

export const getCompany = async (): Promise<Company | null> => {
  const data = await AsyncStorage.getItem(COMPANY_KEY);
  return data ? JSON.parse(data) : null;
};

export const deleteCompany = async (): Promise<void> => {
  await AsyncStorage.removeItem(COMPANY_KEY);
};

// Clear all storage
export const clearStorage = async (): Promise<void> => {
  await deleteToken();
  await deleteCompany();
};
