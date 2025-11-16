import abilities from '@/scripts/admin/stub/abilities'

const LayoutInstallation = () =>
  import('@/scripts/admin/layouts/LayoutInstallation.vue')

const Login = () => import('@/scripts/admin/views/auth/Login.vue')
const LayoutBasic = () => import('@/scripts/admin/layouts/LayoutBasic.vue')
const LayoutLogin = () => import('@/scripts/admin/layouts/LayoutLogin.vue')
const ResetPassword = () =>
  import('@/scripts/admin/views/auth/ResetPassword.vue')
const ForgotPassword = () =>
  import('@/scripts/admin/views/auth/ForgotPassword.vue')

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
const ExchangeRateSetting = () =>
  import('@/scripts/admin/views/settings/ExchangeRateProviderSetting.vue')
const MailConfig = () =>
  import('@/scripts/admin/views/settings/MailConfigSetting.vue')
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
const TwoFactorSetting = () =>
  import('@/scripts/admin/views/settings/TwoFactorSetting.vue')

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

// Bills (Accounts Payable)
const BillsIndex = () => import('@/scripts/admin/views/bills/Index.vue')
const BillsCreate = () => import('@/scripts/admin/views/bills/Create.vue')
const BillsView = () => import('@/scripts/admin/views/bills/View.vue')
const BillsPayments = () =>
  import('@/scripts/admin/views/bills/Payments.vue')
const BillsInbox = () => import('@/scripts/admin/views/bills/Inbox.vue')

// Receipt Scanner (Accounts Payable)
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

// Reports
const ReportsIndex = () =>
  import('@/scripts/admin/views/reports/layout/Index.vue')

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

// Banking
const BankingDashboard = () => import('@/scripts/admin/views/banking/BankingDashboard.vue')

// Billing & Subscription
const PricingPage = () => import('@/js/pages/pricing/Companies.vue')
const BillingIndex = () => import('@/js/pages/billing/Index.vue')
const BillingSuccess = () => import('@/js/pages/billing/Success.vue')

// Support Ticketing
const TicketIndex = () => import('@/scripts/admin/views/support/Index.vue')
const TicketCreate = () => import('@/scripts/admin/views/support/Create.vue')
const TicketView = () => import('@/scripts/admin/views/support/View.vue')

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

      // Receipt Scanner
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
            path: 'two-factor',
            name: 'two.factor',
            component: TwoFactorSetting,
          },
          {
            path: 'company-info',
            name: 'company.info',
            meta: { isOwner: true },
            component: CompanyInfo,
          },
          {
            path: 'preferences',
            name: 'preferences',
            meta: { isOwner: true },
            component: Preferences,
          },
          {
            path: 'ai-insights',
            name: 'ai.insights',
            component: AiInsightsSetting,
          },
          {
            path: 'customization',
            name: 'customization',
            meta: { isOwner: true },
            component: Customization,
          },
          {
            path: 'notifications',
            name: 'notifications',
            meta: { isOwner: true },
            component: Notifications,
          },
          {
            path: 'roles-settings',
            name: 'roles.settings',
            meta: { isOwner: true },
            component: RolesSettings,
          },
          {
            path: 'exchange-rate-provider',
            name: 'exchange.rate.provider',
            meta: { ability: abilities.VIEW_EXCHANGE_RATE },
            component: ExchangeRateSetting,
          },
          {
            path: 'tax-types',
            name: 'tax.types',
            meta: { ability: abilities.VIEW_TAX_TYPE },
            component: TaxTypes,
          },
          {
            path: 'vat-return',
            name: 'vat.return',
            meta: { ability: abilities.VIEW_TAX_TYPE },
            component: VatReturn,
          },
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

          {
            path: 'mail-configuration',
            name: 'mailconfig',
            meta: { isOwner: true },
            component: MailConfig,
          },
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
          {
            path: 'update-app',
            name: 'updateapp',
            meta: { isOwner: true },
            component: UpdateApp,
          },
          {
            path: 'pdf-generation',
            name: 'pdf.generation',
            meta: { isOwner: true },
            component: PDFGenerationSettings,
          },
          {
            path: 'feature-flags',
            name: 'settings.feature-flags',
            meta: { isOwner: true },
            component: FeatureFlagsSettings,
          },
          {
            path: 'certificates',
            name: 'settings.certificates',
            meta: { isOwner: true },
            component: () => import('@/js/pages/settings/CertUpload.vue'),
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
        path: 'imports/wizard',
        name: 'imports.wizard',
        meta: { ability: abilities.CREATE_CUSTOMER }, // Use appropriate ability
        component: ImportWizard,
      },

      // Console
      {
        path: 'console',
        name: 'console.home',
        meta: { isOwner: true }, // Partner/accountant console requires owner access
        component: ConsoleHome,
      },

      // Banking
      {
        path: 'banking',
        name: 'banking.dashboard',
        meta: { ability: abilities.VIEW_FINANCIAL_REPORT }, // Reuse financial report ability
        component: BankingDashboard,
      },

      // Billing & Subscription
      {
        path: 'pricing',
        name: 'pricing',
        meta: { requiresAuth: false },
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

      // Support Tickets
      {
        path: 'support',
        name: 'support.index',
        meta: { requiresAuth: true },
        component: TicketIndex,
      },
      {
        path: 'support/create',
        name: 'support.create',
        meta: { requiresAuth: true },
        component: TicketCreate,
      },
      {
        path: 'support/:id',
        name: 'support.view',
        meta: { requiresAuth: true },
        component: TicketView,
      },
    ],
  },
  { path: '/:catchAll(.*)', component: NotFoundPage },
]

// LLM-CHECKPOINT
// CLAUDE-CHECKPOINT
