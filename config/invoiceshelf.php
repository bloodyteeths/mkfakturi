<?php

use App\Models\Bill;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Estimate;
use App\Models\ExchangeRateProvider;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Note;
use App\Models\Payment;
use App\Models\ProformaInvoice;
use App\Models\Project;
use App\Models\RecurringInvoice;
use App\Models\Supplier;
use App\Models\TaxType;

return [

    /*
    * Minimum php version.
    */
    'min_php_version' => '8.2.0',

    /*
    * Minimum mysql version.
    */

    'min_mysql_version' => '5.7.7',

    /*
    * Minimum mariadb version.
    */

    'min_mariadb_version' => '10.2.7',

    /*
    * Minimum pgsql version.
    */

    'min_pgsql_version' => '9.2.0',

    /*
    * Minimum sqlite version.
    */

    'min_sqlite_version' => '3.35.0',

    /*
    * Marketplace url.
    */
    'base_url' => 'https://invoiceshelf.com',

    /*
    * List of languages supported by Facturino.
    */
    'languages' => [
        ['code' => 'ar', 'name' => 'Arabic'],
        ['code' => 'nl', 'name' => 'Dutch'],
        ['code' => 'en', 'name' => 'English'],
        ['code' => 'fr', 'name' => 'French'],
        ['code' => 'de', 'name' => 'German'],
        ['code' => 'ja', 'name' => 'Japanese'],
        ['code' => 'it', 'name' => 'Italian'],
        ['code' => 'lv', 'name' => 'Latvian'],
        ['code' => 'pl', 'name' => 'Polish'],
        ['code' => 'pt_BR', 'name' => 'Portuguese (Brazilian)'],
        ['code' => 'sr', 'name' => 'Serbian Latin'],
        ['code' => 'ko', 'name' => 'Korean'],
        ['code' => 'es', 'name' => 'Spanish'],
        ['code' => 'sv', 'name' => 'Svenska'],
        ['code' => 'sk', 'name' => 'Slovak'],
        ['code' => 'vi', 'name' => 'Tiếng Việt'],
        ['code' => 'cs', 'name' => 'Czech'],
        ['code' => 'el', 'name' => 'Greek'],
        ['code' => 'hr', 'name' => 'Crotian'],
        ['code' => 'mk', 'name' => 'Macedonian'],
        ['code' => 'th', 'name' => 'ไทย'],
    ],

    /*
    * List of Fiscal Years
    */
    'fiscal_years' => [
        ['key' => 'settings.preferences.fiscal_years.january_december', 'value' => '1-12'],
        ['key' => 'settings.preferences.fiscal_years.february_january', 'value' => '2-1'],
        ['key' => 'settings.preferences.fiscal_years.march_february', 'value' => '3-2'],
        ['key' => 'settings.preferences.fiscal_years.april_march', 'value' => '4-3'],
        ['key' => 'settings.preferences.fiscal_years.may_april', 'value' => '5-4'],
        ['key' => 'settings.preferences.fiscal_years.june_may', 'value' => '6-5'],
        ['key' => 'settings.preferences.fiscal_years.july_june', 'value' => '7-6'],
        ['key' => 'settings.preferences.fiscal_years.august_july', 'value' => '8-7'],
        ['key' => 'settings.preferences.fiscal_years.september_august', 'value' => '9-8'],
        ['key' => 'settings.preferences.fiscal_years.october_september', 'value' => '10-9'],
        ['key' => 'settings.preferences.fiscal_years.november_october', 'value' => '11-10'],
        ['key' => 'settings.preferences.fiscal_years.december_november', 'value' => '12-11'],
    ],

    /*
    * List of convert estimate options
    */
    'convert_estimate_options' => [
        ['key' => 'settings.preferences.no_action', 'value' => 'no_action'],
        ['key' => 'settings.preferences.delete_estimate', 'value' => 'delete_estimate'],
        ['key' => 'settings.preferences.mark_estimate_as_accepted', 'value' => 'mark_estimate_as_accepted'],
    ],

    /*
    * List of retrospective edits
    */
    'retrospective_edits' => [
        ['key' => 'settings.preferences.allow', 'value' => 'allow'],
        ['key' => 'settings.preferences.disable_on_invoice_partial_paid', 'value' => 'disable_on_invoice_partial_paid'],
        ['key' => 'settings.preferences.disable_on_invoice_paid', 'value' => 'disable_on_invoice_paid'],
        ['key' => 'settings.preferences.disable_on_invoice_sent', 'value' => 'disable_on_invoice_sent'],
    ],

    /*
    * List of setting menu
    */
    'setting_menu' => [
        [
            'title' => 'settings.menu_title.account_settings',
            'group' => '',
            'name' => 'Account Settings',
            'link' => '/admin/settings/account-settings',
            'icon' => 'UserIcon',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.company_information',
            'group' => '',
            'name' => 'Company information',
            'link' => '/admin/settings/company-info',
            'icon' => 'BuildingOfficeIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.preferences',
            'group' => '',
            'name' => 'Preferences',
            'link' => '/admin/settings/preferences',
            'icon' => 'CogIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.ai_insights',
            'group' => '',
            'name' => 'AI Insights',
            'link' => '/admin/settings/ai-insights',
            'icon' => 'LightBulbIcon',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.customization',
            'group' => '',
            'name' => 'Customization',
            'link' => '/admin/settings/customization',
            'icon' => 'PencilSquareIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.pdf_generation',
            'group' => '',
            'name' => 'PDF Generation',
            'link' => '/admin/settings/pdf-generation',
            'icon' => 'DocumentIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.roles.title',
            'group' => '',
            'name' => 'Roles',
            'link' => '/admin/settings/roles-settings',
            'icon' => 'UserGroupIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.partner_settings',
            'group' => '',
            'name' => 'Partner Management',
            'link' => '/admin/settings/partner-settings',
            'icon' => 'UsersIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.exchange_rate',
            'group' => '',
            'name' => 'Exchange Rate Provider',
            'link' => '/admin/settings/exchange-rate-provider',
            'icon' => 'BanknotesIcon',
            'owner_only' => false,
            'ability' => 'view-exchange-rate-provider',
            'model' => ExchangeRateProvider::class,
        ],
        [
            'title' => 'settings.menu_title.notifications',
            'group' => '',
            'name' => 'Notifications',
            'link' => '/admin/settings/notifications',
            'icon' => 'BellIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.tax_types',
            'group' => '',
            'name' => 'Tax types',
            'link' => '/admin/settings/tax-types',
            'icon' => 'CheckCircleIcon',
            'owner_only' => false,
            'ability' => 'view-tax-type',
            'model' => TaxType::class,
        ],
        [
            'title' => 'settings.menu_title.payment_modes',
            'group' => '',
            'name' => 'Payment modes',
            'link' => '/admin/settings/payment-mode',
            'icon' => 'CreditCardIcon',
            'owner_only' => false,
            'ability' => 'view-payment',
            'model' => Payment::class,
        ],
        [
            'title' => 'settings.menu_title.custom_fields',
            'group' => '',
            'name' => 'Custom fields',
            'link' => '/admin/settings/custom-fields',
            'icon' => 'CubeIcon',
            'owner_only' => false,
            'ability' => 'view-custom-field',
            'model' => CustomField::class,
        ],
        [
            'title' => 'settings.menu_title.notes',
            'group' => '',
            'name' => 'Notes',
            'link' => '/admin/settings/notes',
            'icon' => 'ClipboardDocumentCheckIcon',
            'owner_only' => false,
            'ability' => 'view-all-notes',
            'model' => Note::class,
        ],
        [
            'title' => 'settings.menu_title.expense_category',
            'group' => '',
            'name' => 'Expense Category',
            'link' => '/admin/settings/expense-category',
            'icon' => 'ClipboardDocumentListIcon',
            'owner_only' => false,
            'ability' => 'view-expense',
            'model' => Expense::class,
        ],
        [
            'title' => 'settings.mail.mail_config',
            'group' => '',
            'name' => 'Mail Configuration',
            'link' => '/admin/settings/mail-configuration',
            'icon' => 'EnvelopeIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.file_disk',
            'group' => '',
            'name' => 'File Disk',
            'link' => '/admin/settings/file-disk',
            'icon' => 'FolderIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.backup',
            'group' => '',
            'name' => 'Backup',
            'link' => '/admin/settings/backup',
            'icon' => 'CircleStackIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.update_app',
            'group' => '',
            'name' => 'Update App',
            'link' => '/admin/settings/update-app',
            'icon' => 'ArrowPathIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.feature_flags.title',
            'group' => '',
            'name' => 'Feature Flags',
            'link' => '/admin/settings/feature-flags',
            'icon' => 'FlagIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.certificates',
            'group' => '',
            'name' => 'Certificates',
            'link' => '/admin/settings/certificates',
            'icon' => 'ShieldCheckIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.daily_closing',
            'group' => '',
            'name' => 'Daily Closing',
            'link' => '/admin/settings/daily-closing',
            'icon' => 'CalendarDaysIcon',
            'owner_only' => false,
            'ability' => 'manage-closings',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.period_lock',
            'group' => '',
            'name' => 'Period Lock',
            'link' => '/admin/settings/period-lock',
            'icon' => 'LockClosedIcon',
            'owner_only' => false,
            'ability' => 'manage-closings',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.chart_of_accounts',
            'group' => '',
            'name' => 'Chart of Accounts',
            'link' => '/admin/settings/chart-of-accounts',
            'icon' => 'TableCellsIcon',
            'owner_only' => false,
            'ability' => 'manage-closings',
            'model' => '',
        ],
        [
            'title' => 'settings.menu_title.journal_export',
            'group' => '',
            'name' => 'Journal Export',
            'link' => '/admin/settings/journal-export',
            'icon' => 'ArrowDownTrayIcon',
            'owner_only' => false,
            'ability' => 'manage-closings',
            'model' => '',
        ],
    ],

    /*
    * List of main menu
    */
    'main_menu' => [
        [
            'title' => 'navigation.dashboard',
            'group' => 1,
            'link' => '/admin/dashboard',
            'icon' => 'HomeIcon',
            'name' => 'Dashboard',
            'owner_only' => false,
            'ability' => 'dashboard',
            'model' => '',
        ],
        [
            'title' => 'navigation.customers',
            'group' => 1,
            'link' => '/admin/customers',
            'icon' => 'UserIcon',
            'name' => 'Customers',
            'owner_only' => false,
            'ability' => 'view-customer',
            'model' => Customer::class,
        ],
        [
            'title' => 'navigation.items',
            'group' => 1,
            'link' => '/admin/items',
            'icon' => 'StarIcon',
            'name' => 'Items',
            'owner_only' => false,
            'ability' => 'view-item',
            'model' => Item::class,
        ],
        [
            'title' => 'navigation.estimates',
            'group' => 2,
            'link' => '/admin/estimates',
            'icon' => 'DocumentIcon',
            'name' => 'Estimates',
            'owner_only' => false,
            'ability' => 'view-estimate',
            'model' => Estimate::class,
        ],
        [
            'title' => 'navigation.proforma_invoices',
            'group' => 2,
            'link' => '/admin/proforma-invoices',
            'icon' => 'DocumentDuplicateIcon',
            'name' => 'Proforma Invoices',
            'owner_only' => false,
            'ability' => 'view-estimate',
            'model' => ProformaInvoice::class,
        ],
        [
            'title' => 'navigation.invoices',
            'group' => 2,
            'link' => '/admin/invoices',
            'icon' => 'DocumentTextIcon',
            'name' => 'Invoices',
            'owner_only' => false,
            'ability' => 'view-invoice',
            'model' => Invoice::class,
        ],
        [
            'title' => 'navigation.recurring-invoices',
            'group' => 2,
            'link' => '/admin/recurring-invoices',
            'icon' => 'DocumentTextIcon',
            'name' => 'Recurring Invoices',
            'owner_only' => false,
            'ability' => 'view-recurring-invoice',
            'model' => RecurringInvoice::class,
        ],
        [
            'title' => 'navigation.payments',
            'group' => 2,
            'link' => '/admin/payments',
            'icon' => 'CreditCardIcon',
            'name' => 'Payments',
            'owner_only' => false,
            'ability' => 'view-payment',
            'model' => Payment::class,
        ],
        [
            'title' => 'navigation.expenses',
            'group' => 2,
            'link' => '/admin/expenses',
            'icon' => 'CalculatorIcon',
            'name' => 'Expenses',
            'owner_only' => false,
            'ability' => 'view-expense',
            'model' => Expense::class,
        ],
        [
            'title' => 'navigation.suppliers',
            'group' => 2,
            'link' => '/admin/suppliers',
            'icon' => 'BuildingStorefrontIcon',
            'name' => 'Suppliers',
            'owner_only' => false,
            'ability' => 'view-supplier',
            'model' => Supplier::class,
        ],
        [
            'title' => 'navigation.bills',
            'group' => 2,
            'link' => '/admin/bills',
            'icon' => 'ClipboardDocumentListIcon',
            'name' => 'Bills',
            'owner_only' => false,
            'ability' => 'view-bill',
            'model' => Bill::class,
        ],
        [
            'title' => 'navigation.projects',
            'group' => 2,
            'link' => '/admin/projects',
            'icon' => 'FolderOpenIcon',
            'name' => 'Projects',
            'owner_only' => false,
            'ability' => 'view-project',
            'model' => Project::class,
        ],
        [
            'title' => 'navigation.stock',
            'group' => 2,
            'link' => '/admin/stock',
            'icon' => 'CubeIcon',
            'name' => 'Stock',
            'owner_only' => false,
            'ability' => 'view-item',
            'model' => Item::class,
            'feature_flag' => 'stock',
        ],
        [
            'title' => 'navigation.bills_inbox',
            'group' => 2,
            'link' => '/admin/bills/inbox',
            'icon' => 'InboxStackIcon',
            'name' => 'Bills Inbox',
            'owner_only' => false,
            'ability' => 'view-bill',
            'model' => Bill::class,
        ],
        [
            'title' => 'navigation.receipts_scan',
            'group' => 2,
            'link' => '/admin/receipts/scan',
            'icon' => 'QrCodeIcon',
            'name' => 'Receipt Scanner',
            'owner_only' => false,
            'ability' => 'create-expense',
            'model' => Expense::class,
        ],
        [
            'title' => 'navigation.banking',
            'group' => 2,
            'link' => '/admin/banking',
            'icon' => 'BanknotesIcon',
            'name' => 'Banking',
            'owner_only' => false,
            'ability' => 'view-report',
            'model' => '',
        ],
        [
            'title' => 'navigation.support',
            'group' => 2,
            'link' => '/admin/support',
            'icon' => 'LifebuoyIcon',
            'name' => 'Support',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'navigation.billing',
            'group' => 3,
            'link' => '/admin/billing',
            'icon' => 'CreditCardIcon',
            'name' => 'Billing',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'navigation.modules',
            'group' => 3,
            'link' => '/admin/modules',
            'icon' => 'PuzzlePieceIcon',
            'name' => 'Modules',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'navigation.users',
            'group' => 3,
            'link' => '/admin/users',
            'icon' => 'UsersIcon',
            'name' => 'Users',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'navigation.reports',
            'group' => 3,
            'link' => '/admin/reports',
            'icon' => 'ChartBarIcon',
            'name' => 'Reports',
            'owner_only' => false,
            'ability' => 'view-financial-reports',
            'model' => '',
        ],
        [
            'title' => 'vat.generate_return',
            'group' => 3,
            'link' => '/admin/settings/vat-return',
            'icon' => 'DocumentTextIcon',
            'name' => 'VAT Return',
            'owner_only' => false,
            'ability' => 'view-tax-type',
            'model' => TaxType::class,
        ],
        [
            'title' => 'navigation.migration_wizard',
            'group' => 3,
            'link' => '/admin/imports/wizard',
            'icon' => 'ArrowDownTrayIcon',
            'name' => 'Migration Wizard',
            'owner_only' => false,
            'ability' => 'create-customer',
            'model' => Customer::class,
        ],
        [
            'title' => 'navigation.settings',
            'group' => 3,
            'link' => '/admin/settings',
            'icon' => 'CogIcon',
            'name' => 'Settings',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'navigation.support_tickets',
            'group' => 3,
            'link' => '/admin/support',
            'icon' => 'LifebuoyIcon',
            'name' => 'Support Tickets',
            'owner_only' => false,
            'ability' => 'viewAny',
            'model' => \Coderflex\LaravelTicket\Models\Ticket::class,
        ],
        [
            'title' => 'Chart of Accounts',
            'group' => 'partner.accounting',
            'link' => '/admin/partner/accounting/chart-of-accounts',
            'icon' => 'CalculatorIcon',
            'name' => 'partner.accounting.chart-of-accounts',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'Account Mappings',
            'group' => 'partner.accounting',
            'link' => '/admin/partner/accounting/mappings',
            'icon' => 'LinkIcon',
            'name' => 'partner.accounting.mappings',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'Journal Entries',
            'group' => 'partner.accounting',
            'link' => '/admin/partner/accounting/journal-entries',
            'icon' => 'DocumentTextIcon',
            'name' => 'partner.accounting.journal-entries',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'Journal Review',
            'group' => 'partner.accounting',
            'link' => '/admin/partner/accounting/review',
            'icon' => 'ClipboardDocumentCheckIcon',
            'name' => 'partner.accounting.review',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
        [
            'title' => 'Export Journal',
            'group' => 'partner.accounting',
            'link' => '/admin/partner/accounting/export',
            'icon' => 'ArrowDownTrayIcon',
            'name' => 'partner.accounting.export',
            'owner_only' => false,
            'ability' => '',
            'model' => '',
        ],
    ],

    /*
    * List of customer portal menu
    */
    'customer_menu' => [
        [
            'title' => 'navigation.dashboard',
            'link' => '/customer/dashboard',
            'icon' => '',
            'name' => '',
            'ability' => '',
            'owner_only' => false,
            'group' => '',
            'model' => '',
        ],
        [
            'title' => 'navigation.invoices',
            'link' => '/customer/invoices',
            'icon' => '',
            'name' => '',
            'ability' => '',
            'owner_only' => false,
            'group' => '',
            'model' => '',
        ],
        [
            'title' => 'navigation.estimates',
            'link' => '/customer/estimates',
            'icon' => '',
            'name' => '',
            'owner_only' => false,
            'ability' => '',
            'group' => '',
            'model' => '',
        ],
        [
            'title' => 'navigation.payments',
            'link' => '/customer/payments',
            'icon' => '',
            'name' => '',
            'owner_only' => false,
            'ability' => '',
            'group' => '',
            'model' => '',
        ],
        [
            'title' => 'navigation.settings',
            'link' => '/customer/settings',
            'icon' => '',
            'name' => '',
            'owner_only' => false,
            'ability' => '',
            'group' => '',
            'model' => '',
        ],
    ],

    /*
    * List of recurring invoice status
    */
    'recurring_invoice_status' => [
        'create_status' => [
            ['key' => 'settings.preferences.active', 'value' => 'ACTIVE'],
            ['key' => 'settings.preferences.on_hold', 'value' => 'ON_HOLD'],
        ],
        'update_status' => [
            ['key' => 'settings.preferences.active', 'value' => 'ACTIVE'],
            ['key' => 'settings.preferences.on_hold', 'value' => 'ON_HOLD'],
            ['key' => 'settings.preferences.completed', 'value' => 'COMPLETED'],
        ],
    ],

    /*
    * List of exchange rate provider (currency converter server's)
    */
    'currency_converter_servers' => [
        ['key' => 'settings.preferences.premium', 'value' => 'PREMIUM'],
        ['key' => 'settings.preferences.prepaid', 'value' => 'PREPAID'],
        ['key' => 'settings.preferences.free', 'value' => 'FREE'],
        ['key' => 'settings.preferences.dedicated', 'value' => 'DEDICATED'],
    ],

    /*
    * List of exchange rate drivers
    */
    'exchange_rate_drivers' => [
        ['key' => 'settings.exchange_rate.currency_converter', 'value' => 'currency_converter'],
        ['key' => 'settings.exchange_rate.currency_freak', 'value' => 'currency_freak'],
        ['key' => 'settings.exchange_rate.currency_layer', 'value' => 'currency_layer'],
        ['key' => 'settings.exchange_rate.open_exchange_rate', 'value' => 'open_exchange_rate'],
    ],

    /*
    * List of Custom field supported models
    */
    'custom_field_models' => [
        'Customer',
        'Estimate',
        'Invoice',
        'Payment',
        'Expense',
    ],
];

// LLM-CHECKPOINT
// CLAUDE-CHECKPOINT

// LLM-CHECKPOINT
// CLAUDE-CHECKPOINT PAF-10: Added partner accounting navigation menu
