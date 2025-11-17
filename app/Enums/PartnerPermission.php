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

    // Customers
    case VIEW_CUSTOMERS = 'view_customers';
    case CREATE_CUSTOMERS = 'create_customers';
    case EDIT_CUSTOMERS = 'edit_customers';
    case DELETE_CUSTOMERS = 'delete_customers';

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
            'customers' => [
                self::VIEW_CUSTOMERS,
                self::CREATE_CUSTOMERS,
                self::EDIT_CUSTOMERS,
                self::DELETE_CUSTOMERS,
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
        return array_map(fn($case) => $case->value, self::cases());
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
            fn($value) => $value !== self::FULL_ACCESS->value
        );
    }

    /**
     * Get human-readable label for permission
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            // Invoices
            self::VIEW_INVOICES => 'View Invoices',
            self::CREATE_INVOICES => 'Create Invoices',
            self::EDIT_INVOICES => 'Edit Invoices',
            self::DELETE_INVOICES => 'Delete Invoices',
            self::SEND_INVOICES => 'Send Invoices',

            // Customers
            self::VIEW_CUSTOMERS => 'View Customers',
            self::CREATE_CUSTOMERS => 'Create Customers',
            self::EDIT_CUSTOMERS => 'Edit Customers',
            self::DELETE_CUSTOMERS => 'Delete Customers',

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
     *
     * @param string $category
     * @return string
     */
    public static function getCategoryLabel(string $category): string
    {
        return match($category) {
            'invoices' => 'Invoices',
            'customers' => 'Customers',
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
     *
     * @return array
     */
    public static function getGroupedForApi(): array
    {
        $grouped = [];

        foreach (self::getGrouped() as $category => $permissions) {
            $grouped[$category] = [
                'label' => self::getCategoryLabel($category),
                'permissions' => array_map(function($permission) {
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
