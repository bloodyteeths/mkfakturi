// Authentication & User Types
export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
}

export interface Company {
  id: number;
  name: string;
  logo: string | null;
  currency_id: number;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  selectedCompany: Company | null;
  companies: Company[];
}

export interface LoginResponse {
  type: string;
  token: string;
}

export interface BootstrapResponse {
  current_user: User;
  companies: Company[];
  current_company: Company;
}

// Dashboard Types
export interface DashboardStats {
  total_unpaid: number;
  total_overdue: number;
  amount_collected: number;
  recent_invoices: Invoice[];
}

// Invoice Types
export interface Invoice {
  id: number;
  invoice_number: string;
  invoice_date: string;
  due_date: string;
  customer_id: number;
  customer_name: string;
  status: 'DRAFT' | 'SENT' | 'VIEWED' | 'OVERDUE' | 'PAID';
  sub_total: number;
  tax: number;
  total: number;
  due_amount: number;
  currency: Currency;
}

export interface InvoiceItem {
  id?: number;
  item_id: number | null;
  name: string;
  description: string;
  quantity: number;
  price: number;
  tax: number;
  total: number;
}

export interface CreateInvoicePayload {
  customer_id: number;
  invoice_date: string;
  due_date: string;
  items: InvoiceItem[];
  notes?: string;
}

// Customer Types
export interface Customer {
  id: number;
  name: string;
  email: string;
  phone: string;
  currency_id: number;
}

export interface CreateCustomerPayload {
  name: string;
  email: string;
  phone?: string;
  address_street_1?: string;
  city?: string;
}

// Banking Types
export interface BankAccount {
  id: number;
  bank_name: string;
  account_number: string;
  current_balance: number;
  currency: string;
  last_sync_at: string;
}

export interface BankTransaction {
  id: number;
  transaction_date: string;
  amount: number;
  description: string;
  counterparty_name: string;
}

// Receipt Types
export interface ReceiptScanResult {
  vendor_name: string;
  amount: number;
  date: string;
  tax: number;
}

// Notification Types
export interface Notification {
  id: number;
  title: string;
  message: string;
  type: string;
  created_at: string;
  read_at: string | null;
}

// Lookup Types
export interface Currency {
  id: number;
  code: string;
  symbol: string;
}

export interface Tax {
  id: number;
  name: string;
  percent: number;
}

export interface Item {
  id: number;
  name: string;
  price: number;
}
