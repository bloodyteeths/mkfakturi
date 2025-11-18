import React, { createContext, useState, useContext, useEffect } from 'react';
import { AuthState, User, Company } from '../types/api';
import * as authApi from '../api/auth';
import * as storage from '../utils/storage';

interface AuthContextType extends AuthState {
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  switchCompany: (company: Company) => Promise<void>;
  loading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [state, setState] = useState<AuthState>({
    user: null,
    token: null,
    selectedCompany: null,
    companies: [],
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const token = await storage.getToken();
      const company = await storage.getCompany();

      if (token) {
        const bootstrap = await authApi.getBootstrap();
        setState({
          user: bootstrap.current_user,
          token,
          selectedCompany: company || bootstrap.current_company,
          companies: bootstrap.companies,
        });
      }
    } catch (error) {
      await storage.clearStorage();
    } finally {
      setLoading(false);
    }
  };

  const login = async (email: string, password: string) => {
    const response = await authApi.login(email, password);
    await storage.saveToken(response.token);

    const bootstrap = await authApi.getBootstrap();
    await storage.saveCompany(bootstrap.current_company);

    setState({
      user: bootstrap.current_user,
      token: response.token,
      selectedCompany: bootstrap.current_company,
      companies: bootstrap.companies,
    });
  };

  const logout = async () => {
    try {
      await authApi.logout();
    } finally {
      await storage.clearStorage();
      setState({
        user: null,
        token: null,
        selectedCompany: null,
        companies: [],
      });
    }
  };

  const switchCompany = async (company: Company) => {
    await storage.saveCompany(company);
    setState((prev) => ({ ...prev, selectedCompany: company }));
  };

  return (
    <AuthContext.Provider value={{ ...state, login, logout, switchCompany, loading }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within AuthProvider');
  return context;
};
