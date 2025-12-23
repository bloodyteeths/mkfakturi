<?php

namespace App\Enums;

enum PartnerPermission: string
{
    // Invoices
    case VIEW_INVOICES = 'view_invoices';
    case CREATE_INVOICES = 'create_invoices';
    case EDIT_INVOICES = 'edit_invoices';
    case DELETE_INVOICES = 'delete_invoices';
    case SEND_INVOICES = 'send_invoices';

    // Estimates
    case VIEW_ESTIMATES = 'view_estimates';
    case CREATE_ESTIMATES = 'create_estimates';
    case EDIT_ESTIMATES = 'edit_estimates';
    case DELETE_ESTIMATES = 'delete_estimates';
    case SEND_ESTIMATES = 'send_estimates';

    // Customers
    case VIEW_CUSTOMERS = 'view_customers';
    case CREATE_CUSTOMERS = 'create_customers';
    case EDIT_CUSTOMERS = 'edit_customers';
    case DELETE_CUSTOMERS = 'delete_customers';

    // Expenses
    case VIEW_EXPENSES = 'view_expenses';
    case CREATE_EXPENSES = 'create_expenses';
    case EDIT_EXPENSES = 'edit_expenses';
    case DELETE_EXPENSES = 'delete_expenses';

    // Bills (Supplier invoices)
    case VIEW_BILLS = 'view_bills';
    case CREATE_BILLS = 'create_bills';
    case EDIT_BILLS = 'edit_bills';
    case DELETE_BILLS = 'delete_bills';

    // Suppliers
    case VIEW_SUPPLIERS = 'view_suppliers';
    case CREATE_SUPPLIERS = 'create_suppliers';
    case EDIT_SUPPLIERS = 'edit_suppliers';
    case DELETE_SUPPLIERS = 'delete_suppliers';

    // Items/Products
    case VIEW_ITEMS = 'view_items';
    case CREATE_ITEMS = 'create_items';
    case EDIT_ITEMS = 'edit_items';
    case DELETE_ITEMS = 'delete_items';

    // Payments
    case VIEW_PAYMENTS = 'view_payments';
    case CREATE_PAYMENTS = 'create_payments';
    case EDIT_PAYMENTS = 'edit_payments';
    case DELETE_PAYMENTS = 'delete_payments';

    // Banking
    case VIEW_BANK_ACCOUNTS = 'view_bank_accounts';
    case MANAGE_BANK_ACCOUNTS = 'manage_bank_accounts';
    case VIEW_BANK_TRANSACTIONS = 'view_bank_transactions';
    case RECONCILE_TRANSACTIONS = 'reconcile_transactions';

    // Reports
    case VIEW_REPORTS = 'view_reports';
    case EXPORT_REPORTS = 'export_reports';
    case VIEW_PROFIT_LOSS = 'view_profit_loss';
    case VIEW_BALANCE_SHEET = 'view_balance_sheet';

    // Payroll
    case VIEW_SALARIES = 'view_salaries';
    case MANAGE_SALARIES = 'manage_salaries';

    // Settings
    case MANAGE_COMPANY_SETTINGS = 'manage_company_settings';
    case MANAGE_USERS = 'manage_users';

    // Master permission
    case FULL_ACCESS = 'full_access';

    /**
     * Get permissions grouped by category
     * Used for UI rendering in Permission Editor
     *
     * @return array<string, array<PartnerPermission>>
     */
    public static function getGrouped(): array
    {
        return [
            'invoices' => [
                self::VIEW_INVOICES,
                self::CREATE_INVOICES,
                self::EDIT_INVOICES,
                self::DELETE_INVOICES,
                self::SEND_INVOICES,
            ],
            'estimates' => [
                self::VIEW_ESTIMATES,
                self::CREATE_ESTIMATES,
                self::EDIT_ESTIMATES,
                self::DELETE_ESTIMATES,
                self::SEND_ESTIMATES,
            ],
            'customers' => [
                self::VIEW_CUSTOMERS,
                self::CREATE_CUSTOMERS,
                self::EDIT_CUSTOMERS,
                self::DELETE_CUSTOMERS,
            ],
            'expenses' => [
                self::VIEW_EXPENSES,
                self::CREATE_EXPENSES,
                self::EDIT_EXPENSES,
                self::DELETE_EXPENSES,
            ],
            'bills' => [
                self::VIEW_BILLS,
                self::CREATE_BILLS,
                self::EDIT_BILLS,
                self::DELETE_BILLS,
            ],
            'suppliers' => [
                self::VIEW_SUPPLIERS,
                self::CREATE_SUPPLIERS,
                self::EDIT_SUPPLIERS,
                self::DELETE_SUPPLIERS,
            ],
            'items' => [
                self::VIEW_ITEMS,
                self::CREATE_ITEMS,
                self::EDIT_ITEMS,
                self::DELETE_ITEMS,
            ],
            'payments' => [
                self::VIEW_PAYMENTS,
                self::CREATE_PAYMENTS,
                self::EDIT_PAYMENTS,
                self::DELETE_PAYMENTS,
            ],
            'banking' => [
                self::VIEW_BANK_ACCOUNTS,
                self::MANAGE_BANK_ACCOUNTS,
                self::VIEW_BANK_TRANSACTIONS,
                self::RECONCILE_TRANSACTIONS,
            ],
            'reports' => [
                self::VIEW_REPORTS,
                self::EXPORT_REPORTS,
                self::VIEW_PROFIT_LOSS,
                self::VIEW_BALANCE_SHEET,
            ],
            'payroll' => [
                self::VIEW_SALARIES,
                self::MANAGE_SALARIES,
            ],
            'settings' => [
                self::MANAGE_COMPANY_SETTINGS,
                self::MANAGE_USERS,
            ],
        ];
    }

    /**
     * Get all permissions as array of strings
     *
     * @return array<string>
     */
    public static function getAllValues(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    /**
     * Get all permissions except FULL_ACCESS
     *
     * @return array<string>
     */
    public static function getAllExceptFullAccess(): array
    {
        return array_filter(
            self::getAllValues(),
            fn ($value) => $value !== self::FULL_ACCESS->value
        );
    }

    /**
     * Get human-readable label for permission
     */
    public function label(): string
    {
        return match ($this) {
            // Invoices
            self::VIEW_INVOICES => 'View Invoices',
            self::CREATE_INVOICES => 'Create Invoices',
            self::EDIT_INVOICES => 'Edit Invoices',
            self::DELETE_INVOICES => 'Delete Invoices',
            self::SEND_INVOICES => 'Send Invoices',

            // Estimates
            self::VIEW_ESTIMATES => 'View Estimates',
            self::CREATE_ESTIMATES => 'Create Estimates',
            self::EDIT_ESTIMATES => 'Edit Estimates',
            self::DELETE_ESTIMATES => 'Delete Estimates',
            self::SEND_ESTIMATES => 'Send Estimates',

            // Customers
            self::VIEW_CUSTOMERS => 'View Customers',
            self::CREATE_CUSTOMERS => 'Create Customers',
            self::EDIT_CUSTOMERS => 'Edit Customers',
            self::DELETE_CUSTOMERS => 'Delete Customers',

            // Expenses
            self::VIEW_EXPENSES => 'View Expenses',
            self::CREATE_EXPENSES => 'Create Expenses',
            self::EDIT_EXPENSES => 'Edit Expenses',
            self::DELETE_EXPENSES => 'Delete Expenses',

            // Bills
            self::VIEW_BILLS => 'View Bills',
            self::CREATE_BILLS => 'Create Bills',
            self::EDIT_BILLS => 'Edit Bills',
            self::DELETE_BILLS => 'Delete Bills',

            // Suppliers
            self::VIEW_SUPPLIERS => 'View Suppliers',
            self::CREATE_SUPPLIERS => 'Create Suppliers',
            self::EDIT_SUPPLIERS => 'Edit Suppliers',
            self::DELETE_SUPPLIERS => 'Delete Suppliers',

            // Items
            self::VIEW_ITEMS => 'View Items',
            self::CREATE_ITEMS => 'Create Items',
            self::EDIT_ITEMS => 'Edit Items',
            self::DELETE_ITEMS => 'Delete Items',

            // Payments
            self::VIEW_PAYMENTS => 'View Payments',
            self::CREATE_PAYMENTS => 'Create Payments',
            self::EDIT_PAYMENTS => 'Edit Payments',
            self::DELETE_PAYMENTS => 'Delete Payments',

            // Banking
            self::VIEW_BANK_ACCOUNTS => 'View Bank Accounts',
            self::MANAGE_BANK_ACCOUNTS => 'Manage Bank Accounts',
            self::VIEW_BANK_TRANSACTIONS => 'View Transactions',
            self::RECONCILE_TRANSACTIONS => 'Reconcile Transactions',

            // Reports
            self::VIEW_REPORTS => 'View Reports',
            self::EXPORT_REPORTS => 'Export Reports',
            self::VIEW_PROFIT_LOSS => 'View Profit & Loss',
            self::VIEW_BALANCE_SHEET => 'View Balance Sheet',

            // Payroll
            self::VIEW_SALARIES => 'View Salaries',
            self::MANAGE_SALARIES => 'Manage Salaries',

            // Settings
            self::MANAGE_COMPANY_SETTINGS => 'Manage Company Settings',
            self::MANAGE_USERS => 'Manage Users',

            // Master
            self::FULL_ACCESS => 'Full Access',
        };
    }

    /**
     * Get category label
     */
    public static function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'invoices' => 'Invoices',
            'estimates' => 'Estimates',
            'customers' => 'Customers',
            'expenses' => 'Expenses',
            'bills' => 'Bills',
            'suppliers' => 'Suppliers',
            'items' => 'Items/Products',
            'payments' => 'Payments',
            'banking' => 'Banking',
            'reports' => 'Reports',
            'payroll' => 'Payroll',
            'settings' => 'Settings',
            default => ucfirst($category),
        };
    }

    /**
     * Convert grouped permissions to API format
     * Returns array with permission objects including labels
     */
    public static function getGroupedForApi(): array
    {
        $grouped = [];

        foreach (self::getGrouped() as $category => $permissions) {
            $grouped[$category] = [
                'label' => self::getCategoryLabel($category),
                'permissions' => array_map(function ($permission) {
                    return [
                        'value' => $permission->value,
                        'label' => $permission->label(),
                    ];
                }, $permissions),
            ];
        }

        return $grouped;
    }
}

// CLAUDE-CHECKPOINT
