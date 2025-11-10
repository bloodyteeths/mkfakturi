<?php

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Bill;
use App\Models\Certificate;
use App\Models\Commission;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\EInvoice;
use App\Models\Estimate;
use App\Models\ExchangeRateProvider;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ImportJob;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Note;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ProformaInvoice;
use App\Models\RecurringInvoice;
use App\Models\Supplier;
use App\Models\TaxReturn;
use App\Models\TaxType;
use App\Models\Unit;
use App\Models\User;

return [
    'abilities' => [

        // Customer
        [
            'name' => 'view customer',
            'ability' => 'view-customer',
            'model' => Customer::class,
        ],
        [
            'name' => 'create customer',
            'ability' => 'create-customer',
            'model' => Customer::class,
            'depends_on' => [
                'view-customer',
                'view-custom-field',
            ],
        ],
        [
            'name' => 'edit customer',
            'ability' => 'edit-customer',
            'model' => Customer::class,
            'depends_on' => [
                'view-customer',
                'view-custom-field',
            ],
        ],
        [
            'name' => 'delete customer',
            'ability' => 'delete-customer',
            'model' => Customer::class,
            'depends_on' => [
                'view-customer',
            ],
        ],

        // Item
        [
            'name' => 'view item',
            'ability' => 'view-item',
            'model' => Item::class,
        ],
        [
            'name' => 'create item',
            'ability' => 'create-item',
            'model' => Item::class,
            'depends_on' => [
                'view-item',
                'view-tax-type',
            ],
        ],
        [
            'name' => 'edit item',
            'ability' => 'edit-item',
            'model' => Item::class,
            'depends_on' => [
                'view-item',
            ],
        ],
        [
            'name' => 'delete item',
            'ability' => 'delete-item',
            'model' => Item::class,
            'depends_on' => [
                'view-item',
            ],
        ],

        // Tax Type
        [
            'name' => 'view tax type',
            'ability' => 'view-tax-type',
            'model' => TaxType::class,
        ],
        [
            'name' => 'create tax type',
            'ability' => 'create-tax-type',
            'model' => TaxType::class,
            'depends_on' => [
                'view-tax-type',
            ],
        ],
        [
            'name' => 'edit tax type',
            'ability' => 'edit-tax-type',
            'model' => TaxType::class,
            'depends_on' => [
                'view-tax-type',
            ],
        ],
        [
            'name' => 'delete tax type',
            'ability' => 'delete-tax-type',
            'model' => TaxType::class,
            'depends_on' => [
                'view-tax-type',
            ],
        ],

        // Estimate
        [
            'name' => 'view estimate',
            'ability' => 'view-estimate',
            'model' => Estimate::class,
        ],
        [
            'name' => 'create estimate',
            'ability' => 'create-estimate',
            'model' => Estimate::class,
            'depends_on' => [
                'view-estimate',
                'view-item',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'edit estimate',
            'ability' => 'edit-estimate',
            'model' => Estimate::class,
            'depends_on' => [
                'view-item',
                'view-estimate',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'delete estimate',
            'ability' => 'delete-estimate',
            'model' => Estimate::class,
            'depends_on' => [
                'view-estimate',
            ],
        ],
        [
            'name' => 'send estimate',
            'ability' => 'send-estimate',
            'model' => Estimate::class,
        ],

        // Invoice
        [
            'name' => 'view invoice',
            'ability' => 'view-invoice',
            'model' => Invoice::class,
        ],
        [
            'name' => 'create invoice',
            'ability' => 'create-invoice',
            'model' => Invoice::class,
            'owner_only' => false,
            'depends_on' => [
                'view-item',
                'view-invoice',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'edit invoice',
            'ability' => 'edit-invoice',
            'model' => Invoice::class,
            'depends_on' => [
                'view-item',
                'view-invoice',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'delete invoice',
            'ability' => 'delete-invoice',
            'model' => Invoice::class,
            'depends_on' => [
                'view-invoice',
            ],
        ],
        [
            'name' => 'send invoice',
            'ability' => 'send-invoice',
            'model' => Invoice::class,
        ],

        // Credit Note
        [
            'name' => 'view credit note',
            'ability' => 'view-credit-note',
            'model' => CreditNote::class,
        ],
        [
            'name' => 'create credit note',
            'ability' => 'create-credit-note',
            'model' => CreditNote::class,
            'depends_on' => [
                'view-credit-note',
                'view-item',
                'view-invoice',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'edit credit note',
            'ability' => 'edit-credit-note',
            'model' => CreditNote::class,
            'depends_on' => [
                'view-credit-note',
                'view-item',
                'view-invoice',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'delete credit note',
            'ability' => 'delete-credit-note',
            'model' => CreditNote::class,
            'depends_on' => [
                'view-credit-note',
            ],
        ],
        [
            'name' => 'send credit note',
            'ability' => 'send-credit-note',
            'model' => CreditNote::class,
        ],

        // Recurring Invoice
        [
            'name' => 'view recurring invoice',
            'ability' => 'view-recurring-invoice',
            'model' => RecurringInvoice::class,
        ],
        [
            'name' => 'create recurring invoice',
            'ability' => 'create-recurring-invoice',
            'model' => RecurringInvoice::class,
            'depends_on' => [
                'view-item',
                'view-recurring-invoice',
                'view-tax-type',
                'view-customer',
                'view-all-notes',
                'send-invoice',
            ],
        ],
        [
            'name' => 'edit recurring invoice',
            'ability' => 'edit-recurring-invoice',
            'model' => RecurringInvoice::class,
            'depends_on' => [
                'view-item',
                'view-recurring-invoice',
                'view-tax-type',
                'view-customer',
                'view-all-notes',
                'send-invoice',
            ],
        ],
        [
            'name' => 'delete recurring invoice',
            'ability' => 'delete-recurring-invoice',
            'model' => RecurringInvoice::class,
            'depends_on' => [
                'view-recurring-invoice',
            ],
        ],

        // Payment
        [
            'name' => 'view payment',
            'ability' => 'view-payment',
            'model' => Payment::class,
        ],
        [
            'name' => 'create payment',
            'ability' => 'create-payment',
            'model' => Payment::class,
            'depends_on' => [
                'view-customer',
                'view-payment',
                'view-invoice',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'edit payment',
            'ability' => 'edit-payment',
            'model' => Payment::class,
            'depends_on' => [
                'view-customer',
                'view-payment',
                'view-invoice',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'delete payment',
            'ability' => 'delete-payment',
            'model' => Payment::class,
            'depends_on' => [
                'view-payment',
            ],
        ],
        [
            'name' => 'send payment',
            'ability' => 'send-payment',
            'model' => Payment::class,
        ],

        // Expense
        [
            'name' => 'view expense',
            'ability' => 'view-expense',
            'model' => Expense::class,
        ],
        [
            'name' => 'create expense',
            'ability' => 'create-expense',
            'model' => Expense::class,
            'depends_on' => [
                'view-customer',
                'view-expense',
                'view-custom-field',
            ],
        ],
        [
            'name' => 'edit expense',
            'ability' => 'edit-expense',
            'model' => Expense::class,
            'depends_on' => [
                'view-customer',
                'view-expense',
                'view-custom-field',
            ],
        ],
        [
            'name' => 'delete expense',
            'ability' => 'delete-expense',
            'model' => Expense::class,
            'depends_on' => [
                'view-expense',
            ],
        ],

        // Custom Field
        [
            'name' => 'view custom field',
            'ability' => 'view-custom-field',
            'model' => CustomField::class,
        ],
        [
            'name' => 'create custom field',
            'ability' => 'create-custom-field',
            'model' => CustomField::class,
            'depends_on' => [
                'view-custom-field',
            ],
        ],
        [
            'name' => 'edit custom field',
            'ability' => 'edit-custom-field',
            'model' => CustomField::class,
            'depends_on' => [
                'view-custom-field',
            ],
        ],
        [
            'name' => 'delete custom field',
            'ability' => 'delete-custom-field',
            'model' => CustomField::class,
            'depends_on' => [
                'view-custom-field',
            ],
        ],

        // Financial Reports
        [
            'name' => 'view financial reports',
            'ability' => 'view-financial-reports',
            'model' => null,
        ],

        // Exchange Rate Provider
        [
            'name' => 'view exchange rate provider',
            'ability' => 'view-exchange-rate-provider',
            'model' => ExchangeRateProvider::class,
            'owner_only' => false,
        ],
        [
            'name' => 'create exchange rate provider',
            'ability' => 'create-exchange-rate-provider',
            'model' => ExchangeRateProvider::class,
            'owner_only' => false,
            'depends_on' => [
                'view-exchange-rate-provider',
            ],
        ],
        [
            'name' => 'edit exchange rate provider',
            'ability' => 'edit-exchange-rate-provider',
            'model' => ExchangeRateProvider::class,
            'owner_only' => false,
            'depends_on' => [
                'view-exchange-rate-provider',
            ],
        ],
        [
            'name' => 'delete exchange rate provider',
            'ability' => 'delete-exchange-rate-provider',
            'model' => ExchangeRateProvider::class,
            'owner_only' => false,
            'depends_on' => [
                'view-exchange-rate-provider',
            ],
        ],

        // Settings
        [
            'name' => 'view company dashboard',
            'ability' => 'dashboard',
            'model' => null,
        ],
        [
            'name' => 'view all notes',
            'ability' => 'view-all-notes',
            'model' => Note::class,
        ],
        [
            'name' => 'manage notes',
            'ability' => 'manage-all-notes',
            'model' => Note::class,
            'depends_on' => [
                'view-all-notes',
            ],
        ],

        // User Management
        [
            'name' => 'view user',
            'ability' => 'view-user',
            'model' => User::class,
        ],
        [
            'name' => 'create user',
            'ability' => 'create-user',
            'model' => User::class,
            'depends_on' => [
                'view-user',
            ],
        ],
        [
            'name' => 'edit user',
            'ability' => 'edit-user',
            'model' => User::class,
            'depends_on' => [
                'view-user',
            ],
        ],
        [
            'name' => 'delete user',
            'ability' => 'delete-user',
            'model' => User::class,
            'depends_on' => [
                'view-user',
            ],
        ],

        // Bank Accounts
        [
            'name' => 'view bank account',
            'ability' => 'view-bank-account',
            'model' => BankAccount::class,
        ],
        [
            'name' => 'create bank account',
            'ability' => 'create-bank-account',
            'model' => BankAccount::class,
            'depends_on' => [
                'view-bank-account',
            ],
        ],
        [
            'name' => 'edit bank account',
            'ability' => 'edit-bank-account',
            'model' => BankAccount::class,
            'depends_on' => [
                'view-bank-account',
            ],
        ],
        [
            'name' => 'delete bank account',
            'ability' => 'delete-bank-account',
            'model' => BankAccount::class,
            'depends_on' => [
                'view-bank-account',
            ],
        ],

        // Bank Transactions
        [
            'name' => 'view bank transaction',
            'ability' => 'view-bank-transaction',
            'model' => BankTransaction::class,
        ],
        [
            'name' => 'sync bank transactions',
            'ability' => 'sync-bank-transaction',
            'model' => BankTransaction::class,
            'depends_on' => [
                'view-bank-transaction',
                'view-bank-account',
            ],
        ],
        [
            'name' => 'match bank transaction',
            'ability' => 'match-bank-transaction',
            'model' => BankTransaction::class,
            'depends_on' => [
                'view-bank-transaction',
                'view-invoice',
                'view-expense',
            ],
        ],

        // Partners (Affiliates)
        [
            'name' => 'view partner',
            'ability' => 'view-partner',
            'model' => Partner::class,
        ],
        [
            'name' => 'create partner',
            'ability' => 'create-partner',
            'model' => Partner::class,
            'depends_on' => [
                'view-partner',
            ],
        ],
        [
            'name' => 'edit partner',
            'ability' => 'edit-partner',
            'model' => Partner::class,
            'depends_on' => [
                'view-partner',
            ],
        ],
        [
            'name' => 'delete partner',
            'ability' => 'delete-partner',
            'model' => Partner::class,
            'depends_on' => [
                'view-partner',
            ],
        ],

        // Commissions
        [
            'name' => 'view commission',
            'ability' => 'view-commission',
            'model' => Commission::class,
        ],
        [
            'name' => 'create commission',
            'ability' => 'create-commission',
            'model' => Commission::class,
            'depends_on' => [
                'view-commission',
                'view-partner',
            ],
        ],
        [
            'name' => 'approve commission',
            'ability' => 'approve-commission',
            'model' => Commission::class,
            'depends_on' => [
                'view-commission',
            ],
        ],
        [
            'name' => 'pay commission',
            'ability' => 'pay-commission',
            'model' => Commission::class,
            'depends_on' => [
                'view-commission',
                'approve-commission',
            ],
        ],

        // Expense Categories
        [
            'name' => 'view expense category',
            'ability' => 'view-expense-category',
            'model' => ExpenseCategory::class,
        ],
        [
            'name' => 'create expense category',
            'ability' => 'create-expense-category',
            'model' => ExpenseCategory::class,
            'depends_on' => [
                'view-expense-category',
            ],
        ],
        [
            'name' => 'edit expense category',
            'ability' => 'edit-expense-category',
            'model' => ExpenseCategory::class,
            'depends_on' => [
                'view-expense-category',
            ],
        ],
        [
            'name' => 'delete expense category',
            'ability' => 'delete-expense-category',
            'model' => ExpenseCategory::class,
            'depends_on' => [
                'view-expense-category',
            ],
        ],

        // Payment Methods
        [
            'name' => 'view payment method',
            'ability' => 'view-payment-method',
            'model' => PaymentMethod::class,
        ],
        [
            'name' => 'create payment method',
            'ability' => 'create-payment-method',
            'model' => PaymentMethod::class,
            'depends_on' => [
                'view-payment-method',
            ],
        ],
        [
            'name' => 'edit payment method',
            'ability' => 'edit-payment-method',
            'model' => PaymentMethod::class,
            'depends_on' => [
                'view-payment-method',
            ],
        ],
        [
            'name' => 'delete payment method',
            'ability' => 'delete-payment-method',
            'model' => PaymentMethod::class,
            'depends_on' => [
                'view-payment-method',
            ],
        ],

        // Units
        [
            'name' => 'view unit',
            'ability' => 'view-unit',
            'model' => Unit::class,
        ],
        [
            'name' => 'create unit',
            'ability' => 'create-unit',
            'model' => Unit::class,
            'depends_on' => [
                'view-unit',
            ],
        ],
        [
            'name' => 'edit unit',
            'ability' => 'edit-unit',
            'model' => Unit::class,
            'depends_on' => [
                'view-unit',
            ],
        ],
        [
            'name' => 'delete unit',
            'ability' => 'delete-unit',
            'model' => Unit::class,
            'depends_on' => [
                'view-unit',
            ],
        ],

        // Import/Migration
        [
            'name' => 'view import',
            'ability' => 'view-import',
            'model' => ImportJob::class,
        ],
        [
            'name' => 'create import',
            'ability' => 'create-import',
            'model' => ImportJob::class,
            'depends_on' => [
                'view-import',
            ],
        ],
        [
            'name' => 'delete import',
            'ability' => 'delete-import',
            'model' => ImportJob::class,
            'depends_on' => [
                'view-import',
            ],
        ],

        // E-Invoice
        [
            'name' => 'view e-invoice',
            'ability' => 'view-e-invoice',
            'model' => EInvoice::class,
        ],
        [
            'name' => 'create e-invoice',
            'ability' => 'create-e-invoice',
            'model' => EInvoice::class,
            'depends_on' => [
                'view-e-invoice',
                'view-invoice',
            ],
        ],
        [
            'name' => 'submit e-invoice',
            'ability' => 'submit-e-invoice',
            'model' => EInvoice::class,
            'depends_on' => [
                'view-e-invoice',
                'sign-e-invoice',
            ],
        ],
        [
            'name' => 'sign e-invoice',
            'ability' => 'sign-e-invoice',
            'model' => EInvoice::class,
            'depends_on' => [
                'view-e-invoice',
                'manage-certificates',
            ],
        ],

        // Tax Return
        [
            'name' => 'view tax return',
            'ability' => 'view-tax-return',
            'model' => TaxReturn::class,
        ],
        [
            'name' => 'file tax return',
            'ability' => 'file-tax-return',
            'model' => TaxReturn::class,
            'depends_on' => [
                'view-tax-return',
                'view-invoice',
                'view-expense',
            ],
        ],
        [
            'name' => 'manage tax periods',
            'ability' => 'manage-tax-periods',
            'model' => TaxReturn::class,
            'depends_on' => [
                'view-tax-return',
            ],
        ],

        // Certificate (QES for e-invoice signing)
        [
            'name' => 'upload certificate',
            'ability' => 'upload-certificate',
            'model' => Certificate::class,
        ],
        [
            'name' => 'manage certificates',
            'ability' => 'manage-certificates',
            'model' => Certificate::class,
            'depends_on' => [
                'upload-certificate',
            ],
        ],

        // Supplier (Accounts Payable)
        [
            'name' => 'view supplier',
            'ability' => 'view-supplier',
            'model' => Supplier::class,
        ],
        [
            'name' => 'create supplier',
            'ability' => 'create-supplier',
            'model' => Supplier::class,
            'depends_on' => [
                'view-supplier',
                'view-all-notes',
                'view-custom-field',
            ],
        ],
        [
            'name' => 'edit supplier',
            'ability' => 'edit-supplier',
            'model' => Supplier::class,
            'depends_on' => [
                'view-supplier',
                'view-all-notes',
                'view-custom-field',
            ],
        ],
        [
            'name' => 'delete supplier',
            'ability' => 'delete-supplier',
            'model' => Supplier::class,
            'depends_on' => [
                'view-supplier',
            ],
        ],

        // Bill (Accounts Payable)
        [
            'name' => 'view bill',
            'ability' => 'view-bill',
            'model' => Bill::class,
        ],
        [
            'name' => 'create bill',
            'ability' => 'create-bill',
            'model' => Bill::class,
            'depends_on' => [
                'view-bill',
                'view-supplier',
                'view-item',
                'view-tax-type',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'edit bill',
            'ability' => 'edit-bill',
            'model' => Bill::class,
            'depends_on' => [
                'view-bill',
                'view-supplier',
                'view-item',
                'view-tax-type',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'delete bill',
            'ability' => 'delete-bill',
            'model' => Bill::class,
            'depends_on' => [
                'view-bill',
            ],
        ],
        [
            'name' => 'send bill',
            'ability' => 'send-bill',
            'model' => Bill::class,
        ],

        // Proforma Invoice
        [
            'name' => 'view proforma invoice',
            'ability' => 'view-proforma-invoice',
            'model' => ProformaInvoice::class,
        ],
        [
            'name' => 'create proforma invoice',
            'ability' => 'create-proforma-invoice',
            'model' => ProformaInvoice::class,
            'depends_on' => [
                'view-proforma-invoice',
                'view-customer',
                'view-item',
                'view-tax-type',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'edit proforma invoice',
            'ability' => 'edit-proforma-invoice',
            'model' => ProformaInvoice::class,
            'depends_on' => [
                'view-proforma-invoice',
                'view-customer',
                'view-item',
                'view-tax-type',
                'view-custom-field',
                'view-all-notes',
            ],
        ],
        [
            'name' => 'delete proforma invoice',
            'ability' => 'delete-proforma-invoice',
            'model' => ProformaInvoice::class,
            'depends_on' => [
                'view-proforma-invoice',
            ],
        ],
        [
            'name' => 'send proforma invoice',
            'ability' => 'send-proforma-invoice',
            'model' => ProformaInvoice::class,
        ],
        [
            'name' => 'convert proforma invoice',
            'ability' => 'convert-proforma-invoice',
            'model' => ProformaInvoice::class,
            'depends_on' => [
                'view-proforma-invoice',
                'create-invoice',
            ],
        ],

        // Audit Logs
        [
            'name' => 'view audit logs',
            'ability' => 'view-audit-logs',
            'model' => AuditLog::class,
        ],

        // Phase 3: Bank Connections
        [
            'name' => 'connect bank',
            'ability' => 'connect-bank',
            'model' => \App\Models\BankConnection::class,
        ],
        [
            'name' => 'view bank transactions',
            'ability' => 'view-bank-transactions',
            'model' => \App\Models\BankConnection::class,
            'depends_on' => [
                'connect-bank',
            ],
        ],

        // Phase 3: Reconciliation
        [
            'name' => 'view reconciliation',
            'ability' => 'view-reconciliation',
            'model' => null,
            'depends_on' => [
                'view-invoice',
                'view-bank-transactions',
            ],
        ],
        [
            'name' => 'approve reconciliation',
            'ability' => 'approve-reconciliation',
            'model' => null,
            'depends_on' => [
                'view-reconciliation',
            ],
        ],

        // Phase 4: Approvals
        [
            'name' => 'request approval',
            'ability' => 'request-approval',
            'model' => \App\Models\ApprovalRequest::class,
        ],
        [
            'name' => 'approve document',
            'ability' => 'approve-document',
            'model' => \App\Models\ApprovalRequest::class,
        ],
        [
            'name' => 'view all approvals',
            'ability' => 'view-all-approvals',
            'model' => \App\Models\ApprovalRequest::class,
        ],

        // Phase 4: Exports
        [
            'name' => 'create export',
            'ability' => 'create-export',
            'model' => \App\Models\ExportJob::class,
        ],

        // Phase 4: Recurring Expenses
        [
            'name' => 'view recurring expense',
            'ability' => 'view-recurring-expense',
            'model' => \App\Models\RecurringExpense::class,
        ],
        [
            'name' => 'create recurring expense',
            'ability' => 'create-recurring-expense',
            'model' => \App\Models\RecurringExpense::class,
            'depends_on' => [
                'view-recurring-expense',
                'view-expense-category',
            ],
        ],
        [
            'name' => 'edit recurring expense',
            'ability' => 'edit-recurring-expense',
            'model' => \App\Models\RecurringExpense::class,
            'depends_on' => [
                'view-recurring-expense',
            ],
        ],
        [
            'name' => 'delete recurring expense',
            'ability' => 'delete-recurring-expense',
            'model' => \App\Models\RecurringExpense::class,
            'depends_on' => [
                'view-recurring-expense',
            ],
        ],
    ],
];
// CLAUDE-CHECKPOINT
