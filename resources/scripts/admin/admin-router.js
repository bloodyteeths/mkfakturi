import abilities from '@/scripts/admin/stub/abilities'

const LayoutInstallation = () =>
  import('@/scripts/admin/layouts/LayoutInstallation.vue')

const Login = () => import('@/scripts/admin/views/auth/Login.vue')
const LayoutBasic = () => import('@/scripts/admin/layouts/LayoutBasic.vue')
const LayoutLogin = () => import('@/scripts/admin/layouts/LayoutLogin.vue')
const LayoutPOS = () => import('@/scripts/admin/layouts/LayoutPOS.vue')
const ResetPassword = () =>
  import('@/scripts/admin/views/auth/ResetPassword.vue')
const ForgotPassword = () =>
  import('@/scripts/admin/views/auth/ForgotPassword.vue')
const Signup = () => import('@/scripts/public/views/signup/Signup.vue')
const SignupLayout = () => import('@/scripts/public/views/signup/SignupLayout.vue')

// Dashboard
const Dashboard = () => import('@/scripts/admin/views/dashboard/Dashboard.vue')

// Customers
const CustomerIndex = () => import('@/scripts/admin/views/customers/Index.vue')
const CustomerCreate = () =>
  import('@/scripts/admin/views/customers/Create.vue')
const CustomerView = () => import('@/scripts/admin/views/customers/View.vue')

//Settings
const SettingsIndex = () =>
  import('@/scripts/admin/views/settings/SettingsIndex.vue')
const AccountSetting = () =>
  import('@/scripts/admin/views/settings/AccountSetting.vue')
const PrivacyDataSetting = () =>
  import('@/scripts/admin/views/settings/PrivacyDataSetting.vue')
const CompanyInfo = () =>
  import('@/scripts/admin/views/settings/CompanyInfoSettings.vue')
const Preferences = () =>
  import('@/scripts/admin/views/settings/PreferencesSetting.vue')
const Customization = () =>
  import(
    '@/scripts/admin/views/settings/customization/CustomizationSetting.vue'
  )
const Notifications = () =>
  import('@/scripts/admin/views/settings/NotificationsSetting.vue')
const TaxTypes = () =>
  import('@/scripts/admin/views/settings/TaxTypesSetting.vue')
const VatReturn = () =>
  import('@/js/pages/tax/VatReturn.vue')
const PaymentMode = () =>
  import('@/scripts/admin/views/settings/PaymentsModeSetting.vue')
const CustomFieldsIndex = () =>
  import('@/scripts/admin/views/settings/CustomFieldsSetting.vue')
const NotesSetting = () =>
  import('@/scripts/admin/views/settings/NotesSetting.vue')
const ExpenseCategory = () =>
  import('@/scripts/admin/views/settings/ExpenseCategorySetting.vue')
// ExchangeRateSetting removed - using free Frankfurter API (no config needed)
// MailConfig removed - using centralized Postmark setup
const FileDisk = () =>
  import('@/scripts/admin/views/settings/FileDiskSetting.vue')
const Backup = () => import('@/scripts/admin/views/settings/BackupSetting.vue')
const UpdateApp = () =>
  import('@/scripts/admin/views/settings/UpdateAppSetting.vue')
const RolesSettings = () =>
  import('@/scripts/admin/views/settings/RolesSettings.vue')
const PDFGenerationSettings = () =>
  import('@/scripts/admin/views/settings/PDFGenerationSetting.vue')
const AiInsightsSetting = () =>
  import('@/scripts/admin/views/settings/AiInsightsSetting.vue')
const FeatureFlagsSettings = () =>
  import('@/scripts/admin/views/settings/FeatureFlagsSettings.vue')
const PartnerSettings = () =>
  import('@/scripts/admin/views/settings/InvitePartnerSection.vue')
const InviteCompanySettings = () =>
  import('@/scripts/admin/views/settings/InviteCompanyToCompanySection.vue')
const DailyClosingSetting = () =>
  import('@/scripts/admin/views/settings/DailyClosingSetting.vue')
const PeriodLockSetting = () =>
  import('@/scripts/admin/views/settings/PeriodLockSetting.vue')
const ChartOfAccountsSetting = () =>
  import('@/scripts/admin/views/settings/ChartOfAccountsSetting.vue')
const JournalExportSetting = () =>
  import('@/scripts/admin/views/settings/JournalExportSetting.vue')
const AccountReviewSetting = () =>
  import('@/scripts/admin/views/settings/AccountReviewSetting.vue')
const FiscalDevicesSetting = () =>
  import('@/scripts/admin/views/settings/FiscalDevicesSetting.vue')
const POSSetting = () =>
  import('@/scripts/admin/views/settings/POSSetting.vue')
const OnlinePaymentsSetting = () =>
  import('@/scripts/admin/views/settings/OnlinePaymentsSetting.vue')
const ViberNotificationsSetting = () =>
  import('@/scripts/admin/views/settings/ViberNotificationsSetting.vue')
const WooCommerceSetting = () =>
  import('@/scripts/admin/views/settings/WooCommerceSetting.vue')
const EFakturaSetting = () =>
  import('@/scripts/admin/views/settings/EFakturaSetting.vue')

// Items
const ItemsIndex = () => import('@/scripts/admin/views/items/Index.vue')
const ItemCreate = () => import('@/scripts/admin/views/items/Create.vue')

// Expenses
const ExpensesIndex = () => import('@/scripts/admin/views/expenses/Index.vue')
const ExpenseCreate = () => import('@/scripts/admin/views/expenses/Create.vue')

// Users
const UserIndex = () => import('@/scripts/admin/views/users/Index.vue')
const UserCreate = () => import('@/scripts/admin/views/users/Create.vue')

// Estimates
const EstimateIndex = () => import('@/scripts/admin/views/estimates/Index.vue')
const EstimateCreate = () =>
  import('@/scripts/admin/views/estimates/create/EstimateCreate.vue')
const EstimateView = () => import('@/scripts/admin/views/estimates/View.vue')

// Suppliers (Accounts Payable)
const SuppliersIndex = () =>
  import('@/scripts/admin/views/suppliers/Index.vue')
const SupplierCreate = () =>
  import('@/scripts/admin/views/suppliers/Create.vue')
const SupplierView = () => import('@/scripts/admin/views/suppliers/View.vue')

// Projects
const ProjectsIndex = () => import('@/scripts/admin/views/projects/Index.vue')
const ProjectCreate = () => import('@/scripts/admin/views/projects/Create.vue')
const ProjectView = () => import('@/scripts/admin/views/projects/View.vue')

// Stock Module (Phase 2)
const StockInventory = () => import('@/scripts/admin/views/stock/Inventory.vue')
const StockItemCard = () => import('@/scripts/admin/views/stock/ItemCard.vue')
const StockWarehouseInventory = () => import('@/scripts/admin/views/stock/WarehouseInventory.vue')
const StockInventoryValuation = () => import('@/scripts/admin/views/stock/InventoryValuation.vue')
const StockLowStock = () => import('@/scripts/admin/views/stock/LowStock.vue')
const StockAdjustments = () => import('@/scripts/admin/views/stock/Adjustments.vue')
const WacAudit = () => import('@/scripts/admin/views/stock/WacAudit.vue')
const WacAuditDetail = () => import('@/scripts/admin/views/stock/WacAuditDetail.vue')

// Warehouses
const WarehousesIndex = () => import('@/scripts/admin/views/stock/warehouses/Index.vue')
const WarehouseCreate = () => import('@/scripts/admin/views/stock/warehouses/Create.vue')

// Inventory Documents (приемница/издатница/преносница)
const InventoryDocumentsIndex = () => import('@/scripts/admin/views/stock/documents/Index.vue')
const InventoryDocumentCreate = () => import('@/scripts/admin/views/stock/documents/Create.vue')
const InventoryDocumentView = () => import('@/scripts/admin/views/stock/documents/View.vue')

// Client Documents (P8-01) + AI Document Hub
const ClientDocuments = () => import('@/scripts/admin/views/documents/ClientDocuments.vue')
const DocumentReview = () => import('@/scripts/admin/views/documents/DocumentReview.vue')

// Bills (Accounts Payable)
const BillsIndex = () => import('@/scripts/admin/views/bills/Index.vue')
const BillsCreate = () => import('@/scripts/admin/views/bills/Create.vue')
const BillsView = () => import('@/scripts/admin/views/bills/View.vue')
const BillsPayments = () =>
  import('@/scripts/admin/views/bills/Payments.vue')
const BillsInbox = () => import('@/scripts/admin/views/bills/Inbox.vue')

// Invoice Scanner (Accounts Payable)
const ReceiptScan = () => import('@/scripts/admin/views/receipts/Scan.vue')

// Payments
const PaymentsIndex = () => import('@/scripts/admin/views/payments/Index.vue')
const PaymentCreate = () => import('@/scripts/admin/views/payments/Create.vue')
const PaymentView = () => import('@/scripts/admin/views/payments/View.vue')

const NotFoundPage = () => import('@/scripts/admin/views/errors/404.vue')

// Invoice
const InvoiceIndex = () => import('@/scripts/admin/views/invoices/Index.vue')
const InvoiceCreate = () =>
  import('@/scripts/admin/views/invoices/create/InvoiceCreate.vue')
const InvoiceView = () => import('@/scripts/admin/views/invoices/View.vue')

// Recurring Invoice
const RecurringInvoiceIndex = () =>
  import('@/scripts/admin/views/recurring-invoices/Index.vue')
const RecurringInvoiceCreate = () =>
  import(
    '@/scripts/admin/views/recurring-invoices/create/RecurringInvoiceCreate.vue'
  )
const RecurringInvoiceView = () =>
  import('@/scripts/admin/views/recurring-invoices/View.vue')

// Proforma Invoices
const ProformaInvoiceIndex = () =>
  import('@/scripts/admin/views/proforma-invoices/Index.vue')
const ProformaInvoiceCreate = () =>
  import('@/scripts/admin/views/proforma-invoices/create/ProformaInvoiceCreate.vue')
const ProformaInvoiceView = () =>
  import('@/scripts/admin/views/proforma-invoices/View.vue')

// Reports
const ReportsIndex = () =>
  import('@/scripts/admin/views/reports/layout/Index.vue')
const GeneralLedger = () =>
  import('@/scripts/admin/views/reports/GeneralLedger.vue')
const JournalEntries = () =>
  import('@/scripts/admin/views/reports/JournalEntries.vue')

// Installation
const Installation = () =>
  import('@/scripts/admin/views/installation/Installation.vue')

// Modules
const ModuleIndex = () => import('@/scripts/admin/views/modules/Index.vue')

const ModuleView = () => import('@/scripts/admin/views/modules/View.vue')

// Imports
const ImportWizard = () => import('@/scripts/admin/views/imports/ImportWizard.vue')
const InvoicePublicPage = () =>
  import('@/scripts/components/InvoicePublicPage.vue')

// Console
const ConsoleHome = () => import('@/js/pages/console/ConsoleHome.vue')
const ConsoleCommissions = () => import('@/js/pages/console/Commissions.vue')
const InviteCompany = () => import('@/js/pages/console/InviteCompany.vue')
const InvitePartner = () => import('@/js/pages/console/InvitePartner.vue')
const PartnerInvitations = () => import('@/js/pages/console/PartnerInvitations.vue')

// Partners (Super Admin - AC-08, AC-17)
const PartnerIndex = () => import('@/scripts/admin/views/partners/Index.vue')
const PartnerCreate = () => import('@/scripts/admin/views/partners/Create.vue')
const PartnerView = () => import('@/scripts/admin/views/partners/View.vue')
const NetworkGraph = () => import('@/scripts/admin/views/partners/NetworkGraph.vue')

// Payouts (Super Admin)
const PayoutIndex = () => import('@/scripts/admin/views/payouts/Index.vue')
const PayoutView = () => import('@/scripts/admin/views/payouts/View.vue')

// Company Deadlines
const CompanyDeadlines = () => import('@/scripts/admin/views/deadlines/CompanyDeadlines.vue')

// Banking
const BankingDashboard = () => import('@/scripts/admin/views/banking/BankingDashboard.vue')
const InvoiceReconciliation = () => import('@/scripts/admin/views/banking/InvoiceReconciliation.vue')
const ImportStatement = () => import('@/scripts/admin/views/banking/ImportStatement.vue')
const ImportHistory = () => import('@/scripts/admin/views/banking/ImportHistory.vue')
const ReconciliationAnalytics = () => import('@/scripts/admin/views/banking/ReconciliationAnalytics.vue')
const MatchingRules = () => import('@/scripts/admin/views/banking/MatchingRules.vue')

// Billing & Subscription
const PricingPage = () => import('@/js/pages/pricing/Companies.vue')
const BillingIndex = () => import('@/js/pages/billing/Index.vue')
const BillingSuccess = () => import('@/js/pages/billing/Success.vue')

// Support Contact
const SupportIndex = () => import('@/scripts/admin/views/support/Index.vue')
const TicketAdminIndex = () => import('@/scripts/admin/views/support/AdminIndex.vue')

// Payroll Module
const PayrollIndex = () => import('@/scripts/admin/views/payroll/Index.vue')
const PayrollEmployeesIndex = () => import('@/scripts/admin/views/payroll/employees/Index.vue')
const PayrollEmployeeCreate = () => import('@/scripts/admin/views/payroll/employees/Create.vue')
const PayrollRunsIndex = () => import('@/scripts/admin/views/payroll/runs/Index.vue')
const PayrollRunCreate = () => import('@/scripts/admin/views/payroll/runs/Create.vue')
const PayrollRunShow = () => import('@/scripts/admin/views/payroll/runs/Show.vue')
const PayslipView = () => import('@/scripts/admin/views/payroll/payslips/View.vue')
const TaxSummary = () => import('@/scripts/admin/views/payroll/payslips/TaxSummary.vue')
const LeaveIndex = () => import('@/scripts/admin/views/payroll/leave/Index.vue')
const LeaveCreate = () => import('@/scripts/admin/views/payroll/leave/Create.vue')

export default [
  {
    path: '/installation',
    component: LayoutInstallation,
    meta: { requiresAuth: false },
    children: [
      {
        path: '/installation',
        component: Installation,
        name: 'installation',
      },
    ],
  },

  {
    path: '/customer/invoices/view/:hash',
    component: InvoicePublicPage,
    name: 'invoice.public',
  },

  {
    path: '/signup',
    component: SignupLayout,
    meta: { requiresAuth: false, isPublic: true },
    children: [
      {
        path: '',
        name: 'signup',
        component: Signup,
        meta: { requiresAuth: false, isPublic: true },
      },
    ],
  },

  {
    path: '/partner/signup',
    component: SignupLayout,
    meta: { requiresAuth: false, isPublic: true },
    children: [
      {
        path: '',
        name: 'partner-signup',
        component: () => import('@/scripts/public/views/partner-signup/PartnerSignup.vue'),
        meta: { requiresAuth: false, isPublic: true },
      },
    ],
  },

  {
    path: '/',
    component: LayoutLogin,
    meta: { requiresAuth: false, redirectIfAuthenticated: true },
    children: [
      {
        path: '',
        component: Login,
      },
      {
        path: 'login',
        name: 'login',
        component: Login,
      },
      {
        path: 'forgot-password',
        component: ForgotPassword,
        name: 'forgot-password',
      },
      {
        path: '/reset-password/:token',
        component: ResetPassword,
        name: 'reset-password',
      },
    ],
  },
  // Public pricing page (accessible without login)
  {
    path: '/pricing',
    name: 'public-pricing',
    component: PricingPage,
    meta: { requiresAuth: false, isPublic: true },
  },
  // POS — Full-screen layout (no sidebar)
  {
    path: '/admin/pos',
    component: LayoutPOS,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'pos',
        component: () => import('@/scripts/admin/views/pos/Index.vue'),
      },
      {
        path: 'kitchen',
        name: 'pos-kitchen',
        component: () => import('@/scripts/admin/views/pos/KitchenDisplay.vue'),
      },
    ],
  },
  {
    path: '/admin',
    component: LayoutBasic,
    meta: { requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        meta: { ability: abilities.DASHBOARD },
        component: Dashboard,
      },

      // Customers
      {
        path: 'customers',
        meta: { ability: abilities.VIEW_CUSTOMER },
        component: CustomerIndex,
      },
      {
        path: 'customers/create',
        name: 'customers.create',
        meta: { ability: abilities.CREATE_CUSTOMER },
        component: CustomerCreate,
      },
      {
        path: 'customers/:id/edit',
        name: 'customers.edit',
        meta: { ability: abilities.EDIT_CUSTOMER },
        component: CustomerCreate,
      },
      {
        path: 'customers/:id/view',
        name: 'customers.view',
        meta: { ability: abilities.VIEW_CUSTOMER },
        component: CustomerView,
      },

      // Suppliers
      {
        path: 'suppliers',
        name: 'suppliers.index',
        meta: { ability: abilities.VIEW_SUPPLIER },
        component: SuppliersIndex,
      },
      {
        path: 'suppliers/create',
        name: 'suppliers.create',
        meta: { ability: abilities.CREATE_SUPPLIER },
        component: SupplierCreate,
      },
      {
        path: 'suppliers/:id/edit',
        name: 'suppliers.edit',
        meta: { ability: abilities.EDIT_SUPPLIER },
        component: SupplierCreate,
      },
      {
        path: 'suppliers/:id/view',
        name: 'suppliers.view',
        meta: { ability: abilities.VIEW_SUPPLIER },
        component: SupplierView,
      },

      // Projects
      {
        path: 'projects',
        name: 'projects.index',
        meta: { ability: abilities.VIEW_PROJECT },
        component: ProjectsIndex,
      },
      {
        path: 'projects/create',
        name: 'projects.create',
        meta: { ability: abilities.CREATE_PROJECT },
        component: ProjectCreate,
      },
      {
        path: 'projects/:id/edit',
        name: 'projects.edit',
        meta: { ability: abilities.EDIT_PROJECT },
        component: ProjectCreate,
      },
      {
        path: 'projects/:id/view',
        name: 'projects.view',
        meta: { ability: abilities.VIEW_PROJECT },
        component: ProjectView,
      },

      // Stock Module (Phase 2)
      // Note: These routes are only functional when FACTURINO_STOCK_V1_ENABLED=true
      {
        path: 'stock',
        name: 'stock.index',
        meta: { ability: abilities.VIEW_ITEM },
        component: StockInventory,
      },
      {
        path: 'stock/item-card',
        name: 'stock.item-card',
        meta: { ability: abilities.VIEW_ITEM },
        component: StockItemCard,
      },
      {
        path: 'stock/item-card/:id',
        name: 'stock.item-card.view',
        meta: { ability: abilities.VIEW_ITEM },
        component: StockItemCard,
      },
      {
        path: 'stock/warehouse-inventory',
        name: 'stock.warehouse-inventory',
        meta: { ability: abilities.VIEW_ITEM },
        component: StockWarehouseInventory,
      },
      {
        path: 'stock/inventory-valuation',
        name: 'stock.inventory-valuation',
        meta: { ability: abilities.VIEW_ITEM },
        component: StockInventoryValuation,
      },
      {
        path: 'stock/low-stock',
        name: 'stock.low-stock',
        meta: { ability: abilities.VIEW_ITEM },
        component: StockLowStock,
      },
      {
        path: 'stock/adjustments',
        name: 'stock.adjustments',
        meta: { ability: abilities.VIEW_ITEM },
        component: StockAdjustments,
      },

      // WAC Audit
      {
        path: 'stock/wac-audit',
        name: 'stock.wac-audit',
        meta: { ability: abilities.VIEW_ITEM },
        component: WacAudit,
      },
      {
        path: 'stock/wac-audit/:id',
        name: 'stock.wac-audit.detail',
        meta: { ability: abilities.VIEW_ITEM },
        component: WacAuditDetail,
      },

      // Warehouses
      {
        path: 'stock/warehouses',
        name: 'warehouses.index',
        meta: { ability: abilities.VIEW_WAREHOUSE },
        component: WarehousesIndex,
      },
      {
        path: 'stock/warehouses/create',
        name: 'warehouses.create',
        meta: { ability: abilities.CREATE_WAREHOUSE },
        component: WarehouseCreate,
      },
      {
        path: 'stock/warehouses/:id/edit',
        name: 'warehouses.edit',
        meta: { ability: abilities.EDIT_WAREHOUSE },
        component: WarehouseCreate,
      },

      // Inventory Documents (приемница/издатница/преносница)
      {
        path: 'stock/documents',
        name: 'stock.documents',
        meta: { requiresAuth: true, ability: abilities.VIEW_ITEM },
        component: InventoryDocumentsIndex,
      },
      {
        path: 'stock/documents/create',
        name: 'stock.documents.create',
        meta: { requiresAuth: true, ability: abilities.CREATE_ITEM },
        component: InventoryDocumentCreate,
      },
      {
        path: 'stock/documents/:id',
        name: 'stock.documents.view',
        meta: { requiresAuth: true, ability: abilities.VIEW_ITEM },
        component: InventoryDocumentView,
      },
      {
        path: 'stock/documents/:id/edit',
        name: 'stock.documents.edit',
        meta: { requiresAuth: true, ability: abilities.EDIT_ITEM },
        component: InventoryDocumentCreate,
      },

      // Client Documents (P8-01) + AI Document Hub
      {
        path: 'documents',
        name: 'client-documents',
        meta: { requiresAuth: true },
        component: ClientDocuments,
      },
      {
        path: 'documents/:id/review',
        name: 'documents.review',
        meta: { requiresAuth: true },
        component: DocumentReview,
      },

      // Company Deadlines (P8-02)
      {
        path: 'deadlines',
        name: 'deadlines',
        component: CompanyDeadlines,
      },

      // Payments
      {
        path: 'payments',
        meta: { ability: abilities.VIEW_PAYMENT },
        component: PaymentsIndex,
      },
      {
        path: 'payments/create',
        name: 'payments.create',
        meta: { ability: abilities.CREATE_PAYMENT },
        component: PaymentCreate,
      },
      {
        path: 'payments/:id/create',
        name: 'invoice.payments.create',
        meta: { ability: abilities.CREATE_PAYMENT },
        component: PaymentCreate,
      },
      {
        path: 'payments/:id/edit',
        name: 'payments.edit',
        meta: { ability: abilities.EDIT_PAYMENT },
        component: PaymentCreate,
      },
      {
        path: 'payments/:id/view',
        name: 'payments.view',
        meta: { ability: abilities.VIEW_PAYMENT },
        component: PaymentView,
      },

      // Bills
      {
        path: 'bills',
        name: 'bills.index',
        meta: { ability: abilities.VIEW_BILL },
        component: BillsIndex,
      },
      {
        path: 'bills/create',
        name: 'bills.create',
        meta: { ability: abilities.CREATE_BILL },
        component: BillsCreate,
      },
      {
        path: 'bills/:id/edit',
        name: 'bills.edit',
        meta: { ability: abilities.EDIT_BILL },
        component: BillsCreate,
      },
      {
        path: 'bills/:id/view',
        name: 'bills.view',
        meta: { ability: abilities.VIEW_BILL },
        component: BillsView,
      },
      {
        path: 'bills/:id/payments',
        name: 'bills.payments',
        meta: { ability: abilities.EDIT_BILL },
        component: BillsPayments,
      },
      {
        path: 'bills/inbox',
        name: 'bills.inbox',
        meta: { ability: abilities.VIEW_BILL },
        component: BillsInbox,
      },

      // Invoice Scanner
      {
        path: 'receipts/scan',
        name: 'receipts.scan',
        meta: { ability: abilities.CREATE_BILL },
        component: ReceiptScan,
      },

      //settings
      {
        path: 'settings',
        name: 'settings',
        component: SettingsIndex,
        children: [
          {
            path: 'account-settings',
            name: 'account.settings',
            component: AccountSetting,
          },
          {
            path: 'privacy-data',
            name: 'privacy.data',
            component: PrivacyDataSetting,
          },
          {
            path: 'company-info',
            name: 'company.info',
            component: CompanyInfo,
          },
          {
            path: 'preferences',
            name: 'preferences',
            component: Preferences,
          },
          // Removed: AI Insights - no backend implementation
          // {
          //   path: 'ai-insights',
          //   name: 'ai.insights',
          //   component: AiInsightsSetting,
          // },
          {
            path: 'customization',
            name: 'customization',
            meta: { isOwner: true },
            component: Customization,
          },
          {
            path: 'notifications',
            name: 'notifications',
            component: Notifications,
          },
          {
            path: 'roles-settings',
            name: 'roles.settings',
            component: RolesSettings,
          },
          // Exchange rate provider settings removed - using free Frankfurter API
          {
            path: 'tax-types',
            name: 'tax.types',
            meta: { ability: abilities.VIEW_TAX_TYPE },
            component: TaxTypes,
          },
          // Removed: VAT Return - no backend generation logic
          // {
          //   path: 'vat-return',
          //   name: 'vat.return',
          //   meta: { ability: abilities.VIEW_TAX_TYPE },
          //   component: VatReturn,
          // },
          {
            path: 'notes',
            name: 'notes',
            meta: { ability: abilities.VIEW_ALL_NOTES },
            component: NotesSetting,
          },
          {
            path: 'payment-mode',
            name: 'payment.mode',
            component: PaymentMode,
          },
          {
            path: 'custom-fields',
            name: 'custom.fields',
            meta: { ability: abilities.VIEW_CUSTOM_FIELDS },
            component: CustomFieldsIndex,
          },
          {
            path: 'expense-category',
            name: 'expense.category',
            meta: { ability: abilities.VIEW_EXPENSE },
            component: ExpenseCategory,
          },

          // Removed: Mail Configuration - using centralized Postmark setup
          {
            path: 'file-disk',
            name: 'file-disk',
            meta: { isOwner: true },
            component: FileDisk,
          },
          {
            path: 'backup',
            name: 'backup',
            meta: { isOwner: true },
            component: Backup,
          },
          // Removed: Update App - external service offline
          // {
          //   path: 'update-app',
          //   name: 'updateapp',
          //   meta: { isOwner: true },
          //   component: UpdateApp,
          // },
          {
            path: 'pdf-generation',
            name: 'pdf.generation',
            meta: { isOwner: true, requiresSuperAdmin: true },
            component: PDFGenerationSettings,
          },
          {
            path: 'partner-settings',
            name: 'partner.settings',
            meta: { isOwner: true },
            component: PartnerSettings,
          },
          {
            path: 'invite-company',
            name: 'settings.invite-company',
            component: InviteCompanySettings,
          },
          {
            path: 'feature-flags',
            name: 'settings.feature-flags',
            meta: { requiresSuperAdmin: true },
            component: FeatureFlagsSettings,
          },
          // Certificates route removed - no UI component available
          // {
          //   path: 'certificates',
          //   name: 'settings.certificates',
          //   meta: { isOwner: true },
          //   component: () => import('@/js/pages/settings/CertUpload.vue'),
          // },
          {
            path: 'daily-closing',
            name: 'settings.daily-closing',
            meta: { ability: abilities.MANAGE_CLOSINGS },
            component: DailyClosingSetting,
          },
          {
            path: 'period-lock',
            name: 'settings.period-lock',
            meta: { ability: abilities.MANAGE_CLOSINGS },
            component: PeriodLockSetting,
          },
          {
            path: 'chart-of-accounts',
            name: 'settings.chart-of-accounts',
            meta: { ability: abilities.MANAGE_CLOSINGS },
            component: ChartOfAccountsSetting,
          },
          {
            path: 'journal-export',
            name: 'settings.journal-export',
            meta: { ability: abilities.MANAGE_CLOSINGS },
            component: JournalExportSetting,
          },
          {
            path: 'account-review',
            name: 'settings.account-review',
            meta: { ability: abilities.MANAGE_CLOSINGS },
            component: AccountReviewSetting,
          },
          {
            path: 'billing',
            name: 'settings.billing',
            meta: { isOwner: true },
            component: PricingPage,
          },
          {
            path: 'fiscal-devices',
            name: 'settings.fiscal-devices',
            meta: { isOwner: true },
            component: FiscalDevicesSetting,
          },
          {
            path: 'pos',
            name: 'settings.pos',
            meta: { isOwner: true },
            component: POSSetting,
          },
          {
            path: 'online-payments',
            name: 'settings.online-payments',
            meta: { isOwner: true },
            component: OnlinePaymentsSetting,
          },
          {
            path: 'viber-notifications',
            name: 'settings.viber-notifications',
            meta: { isOwner: true, requiresSuperAdmin: true },
            component: ViberNotificationsSetting,
          },
          {
            path: 'woocommerce',
            name: 'settings.woocommerce',
            meta: { isOwner: true },
            component: WooCommerceSetting,
          },
          {
            path: 'efaktura',
            name: 'settings.efaktura',
            meta: { isOwner: true },
            component: EFakturaSetting,
          },
        ],
      },

      // Items
      {
        path: 'items',
        meta: { ability: abilities.VIEW_ITEM },
        component: ItemsIndex,
      },
      {
        path: 'items/create',
        name: 'items.create',
        meta: { ability: abilities.CREATE_ITEM },
        component: ItemCreate,
      },
      {
        path: 'items/:id/edit',
        name: 'items.edit',
        meta: { ability: abilities.EDIT_ITEM },
        component: ItemCreate,
      },

      // Expenses
      {
        path: 'expenses',
        meta: { ability: abilities.VIEW_EXPENSE },
        component: ExpensesIndex,
      },
      {
        path: 'expenses/create',
        name: 'expenses.create',
        meta: { ability: abilities.CREATE_EXPENSE },
        component: ExpenseCreate,
      },
      {
        path: 'expenses/:id/edit',
        name: 'expenses.edit',
        meta: { ability: abilities.EDIT_EXPENSE },
        component: ExpenseCreate,
      },

      // Users
      {
        path: 'users',
        name: 'users.index',
        meta: { isOwner: true },
        component: UserIndex,
      },
      {
        path: 'users/create',
        meta: { isOwner: true },
        name: 'users.create',
        component: UserCreate,
      },
      {
        path: 'users/:id/edit',
        name: 'users.edit',
        meta: { isOwner: true },
        component: UserCreate,
      },

      // Estimates
      {
        path: 'estimates',
        name: 'estimates.index',
        meta: { ability: abilities.VIEW_ESTIMATE },
        component: EstimateIndex,
      },
      {
        path: 'estimates/create',
        name: 'estimates.create',
        meta: { ability: abilities.CREATE_ESTIMATE },
        component: EstimateCreate,
      },
      {
        path: 'estimates/:id/view',
        name: 'estimates.view',
        meta: { ability: abilities.VIEW_ESTIMATE },
        component: EstimateView,
      },
      {
        path: 'estimates/:id/edit',
        name: 'estimates.edit',
        meta: { ability: abilities.EDIT_ESTIMATE },
        component: EstimateCreate,
      },

      // Invoices
      {
        path: 'invoices',
        name: 'invoices.index',
        meta: { ability: abilities.VIEW_INVOICE },
        component: InvoiceIndex,
      },
      {
        path: 'invoices/create',
        name: 'invoices.create',
        meta: { ability: abilities.CREATE_INVOICE },
        component: InvoiceCreate,
      },
      {
        path: 'invoices/scan',
        name: 'invoices.scan',
        meta: { ability: abilities.CREATE_INVOICE },
        component: () => import('@/scripts/admin/views/invoices/Scan.vue'),
      },
      {
        path: 'invoices/:id/view',
        name: 'invoices.view',
        meta: { ability: abilities.VIEW_INVOICE },
        component: InvoiceView,
      },
      {
        path: 'invoices/:id/edit',
        name: 'invoices.edit',
        meta: { ability: abilities.EDIT_INVOICE },
        component: InvoiceCreate,
      },

      // Recurring Invoices
      {
        path: 'recurring-invoices',
        name: 'recurring-invoices.index',
        meta: { ability: abilities.VIEW_RECURRING_INVOICE },
        component: RecurringInvoiceIndex,
      },
      {
        path: 'recurring-invoices/create',
        name: 'recurring-invoices.create',
        meta: { ability: abilities.CREATE_RECURRING_INVOICE },
        component: RecurringInvoiceCreate,
      },
      {
        path: 'recurring-invoices/:id/view',
        name: 'recurring-invoices.view',
        meta: { ability: abilities.VIEW_RECURRING_INVOICE },
        component: RecurringInvoiceView,
      },
      {
        path: 'recurring-invoices/:id/edit',
        name: 'recurring-invoices.edit',
        meta: { ability: abilities.EDIT_RECURRING_INVOICE },
        component: RecurringInvoiceCreate,
      },

      // Proforma Invoices
      {
        path: 'proforma-invoices',
        name: 'proforma-invoices.index',
        meta: { ability: abilities.VIEW_ESTIMATE },
        component: ProformaInvoiceIndex,
      },
      {
        path: 'proforma-invoices/create',
        name: 'proforma-invoices.create',
        meta: { ability: abilities.CREATE_ESTIMATE },
        component: ProformaInvoiceCreate,
      },
      {
        path: 'proforma-invoices/:id/view',
        name: 'proforma-invoices.view',
        meta: { ability: abilities.VIEW_ESTIMATE },
        component: ProformaInvoiceView,
      },
      {
        path: 'proforma-invoices/:id/edit',
        name: 'proforma-invoices.edit',
        meta: { ability: abilities.EDIT_ESTIMATE },
        component: ProformaInvoiceCreate,
      },

      // Modules
      {
        path: 'modules',
        name: 'modules.index',
        meta: { isOwner: true },
        component: ModuleIndex,
      },

      {
        path: 'modules/:slug',
        name: 'modules.view',
        meta: { isOwner: true },
        component: ModuleView,
      },

      // Imports
      {
        path: 'imports',
        name: 'imports',
        component: () => import('@/scripts/admin/views/imports/MigrationHub.vue'),
      },
      {
        path: 'imports/wizard',
        name: 'imports.wizard',
        meta: { ability: abilities.CREATE_CUSTOMER },
        component: ImportWizard,
      },

      // Onboarding Wizard
      {
        path: 'onboarding',
        name: 'onboarding.wizard',
        component: () => import('@/scripts/admin/views/onboarding/OnboardingWizard.vue'),
      },

      // Console - Partner routes
      {
        path: 'console',
        name: 'console.home',
        meta: { isPartner: true },
        component: ConsoleHome,
      },
      {
        path: 'console/commissions',
        name: 'console.commissions',
        meta: { isPartner: true },
        component: ConsoleCommissions,
      },
      {
        path: 'console/invite-company',
        name: 'console.invite-company',
        meta: { isPartner: true },
        component: InviteCompany,
      },
      {
        path: 'console/invite-partner',
        name: 'console.invite-partner',
        meta: { isPartner: true },
        component: InvitePartner,
      },
      {
        path: 'console/invitations',
        name: 'console.invitations',
        meta: { isPartner: true },
        component: PartnerInvitations,
      },

      // Partners (Super Admin - AC-08)
      {
        path: 'partners',
        name: 'partners.index',
        meta: { requiresSuperAdmin: true },
        component: PartnerIndex,
      },
      {
        path: 'partners/create',
        name: 'partners.create',
        meta: { requiresSuperAdmin: true },
        component: PartnerCreate,
      },
      {
        path: 'partners/:id/edit',
        name: 'partners.edit',
        meta: { requiresSuperAdmin: true },
        component: PartnerCreate,
      },
      {
        path: 'partners/:id/view',
        name: 'partners.view',
        meta: { requiresSuperAdmin: true },
        component: PartnerView,
      },
      {
        path: 'partners/network',
        name: 'partners.network',
        meta: { requiresSuperAdmin: true },
        component: NetworkGraph,
      },

      // Payouts (Super Admin)
      {
        path: 'payouts',
        name: 'payouts.index',
        meta: { requiresSuperAdmin: true },
        component: PayoutIndex,
      },
      {
        path: 'payouts/:id/view',
        name: 'payouts.view',
        meta: { requiresSuperAdmin: true },
        component: PayoutView,
      },

      // Partner Accounting (PAF-04, PAF-05, PAF-09)
      {
        path: 'partner/accounting/chart-of-accounts',
        name: 'partner.accounting.chart-of-accounts',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/ChartOfAccounts.vue'),
      },
      {
        path: 'partner/accounting/review',
        name: 'partner.accounting.review',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/JournalReview.vue'),
      },
      {
        path: 'partner/accounting/export',
        name: 'partner.accounting.export',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/JournalExport.vue'),
      },
      {
        path: 'partner/accounting/journal-import',
        name: 'partner.accounting.journal-import',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/JournalImport.vue'),
      },
      {
        path: 'partner/accounting/journal-entries',
        name: 'partner.accounting.journal-entries',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/JournalEntries.vue'),
      },
      {
        path: 'partner/accounting/period-lock',
        name: 'partner.accounting.period-lock',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/PeriodLock.vue'),
      },
      {
        path: 'partner/accounting/daily-closing',
        name: 'partner.accounting.daily-closing',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/DailyClosing.vue'),
      },
      {
        path: 'partner/accounting/general-ledger',
        name: 'partner.accounting.general-ledger',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/GeneralLedger.vue'),
      },
      {
        path: 'partner/accounting/sub-ledger',
        name: 'partner.accounting.sub-ledger',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/SubLedger.vue'),
      },
      {
        path: 'partner/accounting/trial-balance',
        name: 'partner.accounting.trial-balance',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/TrialBalance.vue'),
      },
      {
        path: 'partner/accounting/fixed-assets',
        name: 'partner.accounting.fixed-assets',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/FixedAssets.vue'),
      },
      {
        path: 'partner/accounting/cash-flow',
        name: 'partner.accounting.cash-flow',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/CashFlow.vue'),
      },
      {
        path: 'partner/accounting/equity-changes',
        name: 'partner.accounting.equity-changes',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/EquityChanges.vue'),
      },
      {
        path: 'partner/accounting/year-end',
        name: 'partner.accounting.year-end',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/YearEndWizard.vue'),
      },
      {
        path: 'partner/accounting/vat-returns',
        name: 'partner.accounting.vat-returns',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/VatReturns.vue'),
      },
      {
        path: 'partner/accounting/ujp-forms',
        name: 'partner.accounting.ujp-forms',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/UjpForms.vue'),
      },
      {
        path: 'partner/accounting/payroll-reports',
        name: 'partner.accounting.payroll-reports',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/PayrollReports.vue'),
      },
      {
        path: 'partner/accounting/inventory',
        name: 'partner.accounting.inventory',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/PartnerInventory.vue'),
      },
      {
        path: 'partner/accounting/cash-book',
        name: 'partner.accounting.cash-book',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/CashBook.vue'),
      },
      {
        path: 'partner/accounting/vat-books',
        name: 'partner.accounting.vat-books',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/VatBooks.vue'),
      },
      {
        path: 'partner/accounting/trade-book',
        name: 'partner.accounting.trade-book',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/TradeBook.vue'),
      },
      {
        path: 'partner/accounting/ios-statement',
        name: 'partner.accounting.ios-statement',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/IOSStatement.vue'),
      },

      // Hub Pages
      {
        path: 'operations',
        name: 'operations.hub',
        component: () => import('@/scripts/admin/views/operations/Hub.vue'),
      },
      {
        path: 'finance',
        name: 'finance.hub',
        component: () => import('@/scripts/admin/views/finance/Hub.vue'),
      },

      // F1: Compensations
      {
        path: 'compensations',
        name: 'compensations.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/compensations/Index.vue'),
      },
      {
        path: 'compensations/create',
        name: 'compensations.create',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/compensations/Create.vue'),
      },
      {
        path: 'compensations/:id',
        name: 'compensations.view',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/compensations/View.vue'),
      },

      // F2: Payment Orders
      {
        path: 'payment-orders',
        name: 'payment-orders.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/payment-orders/Index.vue'),
      },
      {
        path: 'payment-orders/create',
        name: 'payment-orders.create',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/payment-orders/Create.vue'),
      },
      {
        path: 'payment-orders/:id',
        name: 'payment-orders.view',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/payment-orders/View.vue'),
      },

      // F3: Cost Centers
      {
        path: 'cost-centers',
        name: 'cost-centers.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/cost-centers/Index.vue'),
      },
      {
        path: 'cost-centers/rules',
        name: 'cost-centers.rules',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/cost-centers/Rules.vue'),
      },
      {
        path: 'cost-centers/summary',
        name: 'cost-centers.summary',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/cost-centers/Summary.vue'),
      },

      // F4: Late Interest
      {
        path: 'interest',
        name: 'interest.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/interest/Index.vue'),
      },
      {
        path: 'interest/summary',
        name: 'interest.summary',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/interest/Summary.vue'),
      },

      // F5: Collections & Reminders
      {
        path: 'collections',
        name: 'collections.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/collections/Index.vue'),
      },

      // Fiscal Receipts — Фискални Сметки
      {
        path: 'fiscal-receipts',
        name: 'fiscal-receipts.index',
        meta: { isOwner: true, ability: abilities.VIEW_FISCAL_MONITOR },
        component: () => import('@/scripts/admin/views/fiscal-receipts/Index.vue'),
      },

      // Fiscal Monitor — Cash Register Fraud Detection
      {
        path: 'fiscal-monitor',
        name: 'fiscal-monitor.index',
        meta: { isOwner: true, ability: abilities.VIEW_FISCAL_MONITOR },
        component: () => import('@/scripts/admin/views/fiscal-monitor/Index.vue'),
      },
      {
        path: 'fiscal-monitor/audit',
        name: 'fiscal-monitor.audit',
        meta: { isOwner: true, ability: abilities.VIEW_FISCAL_MONITOR },
        component: () => import('@/scripts/admin/views/fiscal-monitor/AuditReport.vue'),
      },
      {
        path: 'fiscal-monitor/device/:id',
        name: 'fiscal-monitor.device',
        meta: { isOwner: true, ability: abilities.VIEW_FISCAL_MONITOR },
        component: () => import('@/scripts/admin/views/fiscal-monitor/DeviceDetail.vue'),
      },

      // Manufacturing Module
      {
        path: 'manufacturing',
        name: 'manufacturing.index',
        meta: { ability: abilities.VIEW_ITEM },
        component: () => import('@/scripts/admin/views/manufacturing/Index.vue'),
      },
      {
        path: 'manufacturing/boms',
        name: 'manufacturing.bom.index',
        meta: { ability: abilities.VIEW_ITEM },
        component: () => import('@/scripts/admin/views/manufacturing/bom/Index.vue'),
      },
      {
        path: 'manufacturing/boms/create',
        name: 'manufacturing.bom.create',
        meta: { ability: abilities.CREATE_ITEM },
        component: () => import('@/scripts/admin/views/manufacturing/bom/Create.vue'),
      },
      {
        path: 'manufacturing/boms/:id',
        name: 'manufacturing.bom.view',
        meta: { ability: abilities.VIEW_ITEM },
        component: () => import('@/scripts/admin/views/manufacturing/bom/View.vue'),
      },
      {
        path: 'manufacturing/orders',
        name: 'manufacturing.order.index',
        meta: { ability: abilities.VIEW_ITEM },
        component: () => import('@/scripts/admin/views/manufacturing/orders/Index.vue'),
      },
      {
        path: 'manufacturing/orders/create',
        name: 'manufacturing.order.create',
        meta: { ability: abilities.CREATE_ITEM },
        component: () => import('@/scripts/admin/views/manufacturing/orders/Create.vue'),
      },
      {
        path: 'manufacturing/orders/:id',
        name: 'manufacturing.order.view',
        meta: { ability: abilities.VIEW_ITEM },
        component: () => import('@/scripts/admin/views/manufacturing/orders/View.vue'),
      },
      {
        path: 'manufacturing/orders/:id/complete',
        name: 'manufacturing.order.complete',
        meta: { ability: abilities.CREATE_ITEM },
        component: () => import('@/scripts/admin/views/manufacturing/orders/Complete.vue'),
      },
      {
        path: 'manufacturing/reports/cost-analysis',
        name: 'manufacturing.report.cost-analysis',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/manufacturing/reports/CostAnalysis.vue'),
      },
      {
        path: 'manufacturing/reports/variance',
        name: 'manufacturing.report.variance',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/manufacturing/reports/VarianceReport.vue'),
      },
      {
        path: 'manufacturing/reports/wastage',
        name: 'manufacturing.report.wastage',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/manufacturing/reports/WastageReport.vue'),
      },

      // Shop Floor (mobile operator view)
      {
        path: 'manufacturing/shop-floor',
        name: 'manufacturing.shop-floor',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/manufacturing/ShopFloor.vue'),
      },

      // Gantt Scheduler
      {
        path: 'manufacturing/gantt',
        name: 'manufacturing.gantt',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/manufacturing/Gantt.vue'),
      },

      // TV Dashboard (factory floor display)
      {
        path: 'manufacturing/tv',
        name: 'manufacturing.tv',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/manufacturing/TvDashboard.vue'),
      },

      // Work Centers
      {
        path: 'manufacturing/work-centers',
        name: 'manufacturing.work-center.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/manufacturing/work-centers/Index.vue'),
      },
      {
        path: 'manufacturing/work-centers/create',
        name: 'manufacturing.work-center.create',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/manufacturing/work-centers/Create.vue'),
      },
      {
        path: 'manufacturing/work-centers/:id',
        name: 'manufacturing.work-center.view',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/manufacturing/work-centers/View.vue'),
      },

      // F6: Purchase Orders
      {
        path: 'purchase-orders',
        name: 'purchase-orders.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/purchase-orders/Index.vue'),
      },
      {
        path: 'purchase-orders/create',
        name: 'purchase-orders.create',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/purchase-orders/Create.vue'),
      },
      {
        path: 'purchase-orders/:id',
        name: 'purchase-orders.view',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/purchase-orders/View.vue'),
      },
      {
        path: 'purchase-orders/:id/edit',
        name: 'purchase-orders.edit',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/purchase-orders/Edit.vue'),
      },
      {
        path: 'purchase-orders/:id/receive',
        name: 'purchase-orders.receive',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/purchase-orders/ReceiveGoods.vue'),
      },

      // F7: Budgets
      {
        path: 'budgets',
        name: 'budgets.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/budgets/Index.vue'),
      },
      {
        path: 'budgets/create',
        name: 'budgets.create',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/budgets/Create.vue'),
      },
      {
        path: 'budgets/:id',
        name: 'budgets.view',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/budgets/View.vue'),
      },

      // F8: Travel Orders
      {
        path: 'travel-orders',
        name: 'travel-orders.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/travel-orders/Index.vue'),
      },
      {
        path: 'travel-orders/create',
        name: 'travel-orders.create',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/travel-orders/Create.vue'),
      },
      {
        path: 'travel-orders/:id',
        name: 'travel-orders.view',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/travel-orders/View.vue'),
      },

      // F9: BI Dashboard (single page)
      {
        path: 'bi-dashboard',
        name: 'bi-dashboard.index',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('@/scripts/admin/views/bi-dashboard/Index.vue'),
      },

      // F11: Custom Reports — Partner-only (removed from company side)

      // Partner: F1 Compensations, F2 Payment Orders, F3 Cost Centers
      {
        path: 'partner/accounting/compensations',
        name: 'partner.accounting.compensations',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/Compensations.vue'),
      },
      {
        path: 'partner/accounting/payment-orders',
        name: 'partner.accounting.payment-orders',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/PaymentOrders.vue'),
      },
      {
        path: 'partner/accounting/cost-centers',
        name: 'partner.accounting.cost-centers',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/CostCenters.vue'),
      },

      // Partner: F4 Interest, F5 Collections, F6 Purchase Orders, F7 Budgets
      {
        path: 'partner/accounting/interest',
        name: 'partner.accounting.interest',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/Interest.vue'),
      },
      {
        path: 'partner/accounting/collections',
        name: 'partner.accounting.collections',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/Collections.vue'),
      },
      {
        path: 'partner/accounting/purchase-orders',
        name: 'partner.accounting.purchase-orders',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/PurchaseOrders.vue'),
      },
      {
        path: 'partner/accounting/budgets',
        name: 'partner.accounting.budgets',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/Budgets.vue'),
      },

      // F8: Travel Orders (Partner)
      {
        path: 'partner/accounting/travel-orders',
        name: 'partner.accounting.travel-orders',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/TravelOrders.vue'),
      },

      // F9: BI Dashboards (Partner)
      {
        path: 'partner/accounting/bi-dashboard',
        name: 'partner.accounting.bi-dashboard',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/BiDashboard.vue'),
      },

      // F10: Batch Operations (Partner)
      {
        path: 'partner/accounting/batch-operations',
        name: 'partner.accounting.batch-operations',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/BatchOperations.vue'),
      },

      // F11: Custom Reports (Partner)
      {
        path: 'partner/accounting/custom-reports',
        name: 'partner.accounting.custom-reports',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/CustomReports.vue'),
      },

      // F12: Financial Consolidation (Partner)
      {
        path: 'partner/accounting/consolidation',
        name: 'partner.accounting.consolidation',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/Consolidation.vue'),
      },

      // Activity Log (Partner)
      {
        path: 'partner/accounting/activity-log',
        name: 'partner.accounting.activity-log',
        meta: { isPartner: true },
        component: () => import('@/scripts/admin/views/partner/accounting/ActivityLog.vue'),
      },

      // Banking
      {
        path: 'banking',
        name: 'banking.dashboard',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT }, // Reuse financial report ability
        component: BankingDashboard,
      },
      {
        path: 'banking/reconciliation',
        name: 'banking.reconciliation',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: InvoiceReconciliation,
      },
      {
        path: 'banking/import',
        name: 'banking.import',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: ImportStatement,
      },
      {
        path: 'banking/import-history',
        name: 'banking.import-history',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: ImportHistory,
      },
      {
        path: 'banking/analytics',
        name: 'banking.analytics',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: ReconciliationAnalytics,
      },
      {
        path: 'banking/matching-rules',
        name: 'banking.matching-rules',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: MatchingRules,
      },

      // Billing & Subscription
      {
        path: 'pricing',
        name: 'billing.pricing',
        meta: { requiresAuth: true },
        component: PricingPage,
      },
      {
        path: 'billing',
        name: 'billing.index',
        meta: { requiresAuth: true },
        component: BillingIndex,
      },
      {
        path: 'billing/success',
        name: 'billing.success',
        meta: { requiresAuth: true },
        component: BillingSuccess,
      },

      // Reports
      {
        path: 'reports',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: ReportsIndex,
      },
      {
        path: 'reports/general-ledger/:id?',
        name: 'reports.general-ledger',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: GeneralLedger,
      },
      {
        path: 'reports/journal-entries',
        name: 'reports.journal-entries',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: JournalEntries,
      },
      {
        path: 'reports/cash-flow',
        name: 'reports.cash-flow',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('./views/reports/CashFlow.vue'),
      },
      {
        path: 'reports/equity-changes',
        name: 'reports.equity-changes',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('./views/reports/EquityChanges.vue'),
      },
      {
        path: 'accounting/fixed-assets',
        name: 'accounting.fixed-assets',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT },
        component: () => import('./views/accounting/FixedAssets.vue'),
      },

      // Support Contact
      {
        path: 'support',
        name: 'support.index',
        meta: { requiresAuth: true },
        component: SupportIndex,
      },

      // Super Admin Support Tickets (Cross-Tenant View)
      {
        path: 'support-admin',
        name: 'support.admin',
        meta: { requiresAuth: true, requiresSuperAdmin: true },
        component: TicketAdminIndex,
      },

      // Payroll Module (Business+ tier)
      {
        path: 'payroll',
        name: 'payroll.index',
        meta: { requiresAuth: true },
        component: PayrollIndex,
      },
      {
        path: 'payroll/employees',
        name: 'payroll.employees.index',
        meta: { requiresAuth: true },
        component: PayrollEmployeesIndex,
      },
      {
        path: 'payroll/employees/create',
        name: 'payroll.employees.create',
        meta: { requiresAuth: true },
        component: PayrollEmployeeCreate,
      },
      {
        path: 'payroll/employees/:id/edit',
        name: 'payroll.employees.edit',
        meta: { requiresAuth: true },
        component: PayrollEmployeeCreate,
      },
      {
        path: 'payroll/runs',
        name: 'payroll.runs.index',
        meta: { requiresAuth: true },
        component: PayrollRunsIndex,
      },
      {
        path: 'payroll/runs/create',
        name: 'payroll.runs.create',
        meta: { requiresAuth: true },
        component: PayrollRunCreate,
      },
      {
        path: 'payroll/runs/:id',
        name: 'payroll.runs.show',
        meta: { requiresAuth: true },
        component: PayrollRunShow,
      },
      {
        path: 'payroll/payslips/:id',
        name: 'payroll.payslips.view',
        meta: { requiresAuth: true },
        component: PayslipView,
      },
      {
        path: 'payroll/leave',
        name: 'payroll.leave',
        meta: { requiresAuth: true },
        component: LeaveIndex,
      },
      {
        path: 'payroll/leave/create',
        name: 'payroll.leave.create',
        meta: { requiresAuth: true },
        component: LeaveCreate,
      },
      {
        path: 'payroll/reports/tax-summary',
        name: 'payroll.reports.tax-summary',
        meta: { requiresAuth: true },
        component: TaxSummary,
      },
    ],
  },
  { path: '/:catchAll(.*)', component: NotFoundPage },
]

// CLAUDE-CHECKPOINT: Added GeneralLedger and JournalEntries report routes
// CLAUDE-CHECKPOINT: Removed certificates route - no UI component available
// LLM-CHECKPOINT
