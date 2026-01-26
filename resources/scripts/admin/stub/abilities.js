export default {
  DASHBOARD: 'dashboard',

  // customers
  CREATE_CUSTOMER: 'create-customer',
  DELETE_CUSTOMER: 'delete-customer',
  EDIT_CUSTOMER: 'edit-customer',
  VIEW_CUSTOMER: 'view-customer',

  // Items
  CREATE_ITEM: 'create-item',
  DELETE_ITEM: 'delete-item',
  EDIT_ITEM: 'edit-item',
  VIEW_ITEM: 'view-item',

  // Tax Types
  CREATE_TAX_TYPE: 'create-tax-type',
  DELETE_TAX_TYPE: 'delete-tax-type',
  EDIT_TAX_TYPE: 'edit-tax-type',
  VIEW_TAX_TYPE: 'view-tax-type',

  // Estimates
  CREATE_ESTIMATE: 'create-estimate',
  DELETE_ESTIMATE: 'delete-estimate',
  EDIT_ESTIMATE: 'edit-estimate',
  VIEW_ESTIMATE: 'view-estimate',
  SEND_ESTIMATE: 'send-estimate',

  // Proforma Invoices
  CREATE_PROFORMA_INVOICE: 'create-proforma-invoice',
  DELETE_PROFORMA_INVOICE: 'delete-proforma-invoice',
  EDIT_PROFORMA_INVOICE: 'edit-proforma-invoice',
  VIEW_PROFORMA_INVOICE: 'view-proforma-invoice',
  SEND_PROFORMA_INVOICE: 'send-proforma-invoice',

  // Invoices
  CREATE_INVOICE: 'create-invoice',
  DELETE_INVOICE: 'delete-invoice',
  EDIT_INVOICE: 'edit-invoice',
  VIEW_INVOICE: 'view-invoice',
  SEND_INVOICE: 'send-invoice',

  // E-Invoices
  VIEW_EINVOICE: 'view-einvoice',
  GENERATE_EINVOICE: 'generate-einvoice',
  SUBMIT_EINVOICE: 'submit-einvoice',

  // Recurring Invoices
  CREATE_RECURRING_INVOICE: 'create-recurring-invoice',
  DELETE_RECURRING_INVOICE: 'delete-recurring-invoice',
  EDIT_RECURRING_INVOICE: 'edit-recurring-invoice',
  VIEW_RECURRING_INVOICE: 'view-recurring-invoice',

  // Payment
  CREATE_PAYMENT: 'create-payment',
  DELETE_PAYMENT: 'delete-payment',
  EDIT_PAYMENT: 'edit-payment',
  VIEW_PAYMENT: 'view-payment',
  SEND_PAYMENT: 'send-payment',

  // Payment
  CREATE_EXPENSE: 'create-expense',
  DELETE_EXPENSE: 'delete-expense',
  EDIT_EXPENSE: 'edit-expense',
  VIEW_EXPENSE: 'view-expense',

  // Custom fields
  CREATE_CUSTOM_FIELDS: 'create-custom-field',
  DELETE_CUSTOM_FIELDS: 'delete-custom-field',
  EDIT_CUSTOM_FIELDS: 'edit-custom-field',
  VIEW_CUSTOM_FIELDS: 'view-custom-field',

  // Roles
  CREATE_ROLE: 'create-role',
  DELETE_ROLE: 'delete-role',
  EDIT_ROLE: 'edit-role',
  VIEW_ROLE: 'view-role',

  // exchange rates
  VIEW_EXCHANGE_RATE: 'view-exchange-rate-provider',
  CREATE_EXCHANGE_RATE: 'create-exchange-rate-provider',
  EDIT_EXCHANGE_RATE: 'edit-exchange-rate-provider',
  DELETE_EXCHANGE_RATE: 'delete-exchange-rate-provider',

  // Reports
  VIEW_FINANCIAL_REPORT: 'view-financial-reports',

  // settings
  MANAGE_NOTE: 'manage-all-notes',
  VIEW_NOTE: 'view-all-notes',

  // Suppliers (Accounts Payable)
  VIEW_SUPPLIER: 'view-supplier',
  CREATE_SUPPLIER: 'create-supplier',
  EDIT_SUPPLIER: 'edit-supplier',
  DELETE_SUPPLIER: 'delete-supplier',

  // Bills (Accounts Payable)
  VIEW_BILL: 'view-bill',
  CREATE_BILL: 'create-bill',
  EDIT_BILL: 'edit-bill',
  DELETE_BILL: 'delete-bill',
  SEND_BILL: 'send-bill',

  // Projects
  VIEW_PROJECT: 'view-project',
  CREATE_PROJECT: 'create-project',
  EDIT_PROJECT: 'edit-project',
  DELETE_PROJECT: 'delete-project',

  // Warehouses (Stock Module)
  VIEW_WAREHOUSE: 'view-warehouse',
  CREATE_WAREHOUSE: 'create-warehouse',
  EDIT_WAREHOUSE: 'edit-warehouse',
  DELETE_WAREHOUSE: 'delete-warehouse',

  // Stock Reports (Phase 2: Stock Module)
  VIEW_STOCK_REPORTS: 'view-item', // Uses existing item permission - stock reports are read-only

  // Period Closings (Phase 3: Daily Closing & Period Lock)
  VIEW_CLOSINGS: 'view-financial-reports', // View closings uses financial reports permission
  MANAGE_CLOSINGS: 'manage-closings', // Create/delete daily closings and period locks
}
// CLAUDE-CHECKPOINT
