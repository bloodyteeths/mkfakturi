<?php

use App\Http\Controllers\AppVersionController;
use App\Http\Controllers\CertUploadController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\V1\Admin\Accounting\AccountingReportsController;
use App\Http\Controllers\V1\Admin\AccountsPayable\CloneBillController;
use App\Http\Controllers\V1\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\V1\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\V1\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\V1\Admin\Backup\BackupsController;
use App\Http\Controllers\V1\Admin\Backup\DownloadBackupController;
use App\Http\Controllers\V1\Admin\Company\CompaniesController;
use App\Http\Controllers\V1\Admin\Company\CompanyController as AdminCompanyController;
use App\Http\Controllers\V1\Admin\CreditNotes\CreditNoteController;
use App\Http\Controllers\V1\Admin\Customer\CustomersController;
use App\Http\Controllers\V1\Admin\Customer\CustomerLedgerController;
use App\Http\Controllers\V1\Admin\Customer\CustomerLinkController;
use App\Http\Controllers\V1\Admin\Customer\CustomerMatchController;
use App\Http\Controllers\V1\Admin\Customer\CustomerStatsController;
use App\Http\Controllers\V1\Admin\CustomField\CustomFieldsController;
use App\Http\Controllers\V1\Admin\Dashboard\DashboardController;
use App\Http\Controllers\V1\Admin\EInvoice\EInvoiceController;
use App\Http\Controllers\V1\Admin\Estimate\ChangeEstimateStatusController;
use App\Http\Controllers\V1\Admin\Estimate\CloneEstimateController;
use App\Http\Controllers\V1\Admin\Estimate\ConvertEstimateController;
use App\Http\Controllers\V1\Admin\Estimate\EstimatesController;
use App\Http\Controllers\V1\Admin\Estimate\EstimateTemplatesController;
use App\Http\Controllers\V1\Admin\Estimate\SendEstimateController;
use App\Http\Controllers\V1\Admin\Estimate\SendEstimatePreviewController;
use App\Http\Controllers\V1\Admin\ExchangeRate\GetExchangeRateController;
use App\Http\Controllers\V1\Admin\Expense\ExpenseCategoriesController;
use App\Http\Controllers\V1\Admin\Expense\ExpensesController;
use App\Http\Controllers\V1\Admin\Expense\ShowReceiptController;
use App\Http\Controllers\V1\Admin\Expense\UploadReceiptController;
use App\Http\Controllers\V1\Admin\General\BootstrapController;
use App\Http\Controllers\V1\Admin\General\BulkExchangeRateController;
use App\Http\Controllers\V1\Admin\General\ConfigController;
use App\Http\Controllers\V1\Admin\General\CountriesController;
use App\Http\Controllers\V1\Admin\General\CurrenciesController;
use App\Http\Controllers\V1\Admin\General\DateFormatsController;
use App\Http\Controllers\V1\Admin\General\GetAllUsedCurrenciesController;
use App\Http\Controllers\V1\Admin\General\NextNumberController;
use App\Http\Controllers\V1\Admin\General\NotesController;
use App\Http\Controllers\V1\Admin\General\NumberPlaceholdersController;
use App\Http\Controllers\V1\Admin\General\SearchController;
use App\Http\Controllers\V1\Admin\General\SearchUsersController;
use App\Http\Controllers\V1\Admin\General\TimeFormatsController;
use App\Http\Controllers\V1\Admin\General\TimezonesController;
use App\Http\Controllers\V1\Admin\Invoice\ChangeInvoiceStatusController;
use App\Http\Controllers\V1\Admin\Invoice\CloneInvoiceController;
use App\Http\Controllers\V1\Admin\Invoice\InvoicesController;
use App\Http\Controllers\V1\Admin\Invoice\InvoiceTemplatesController;
use App\Http\Controllers\V1\Admin\Invoice\SendInvoiceController;
use App\Http\Controllers\V1\Admin\Invoice\SendInvoicePreviewController;
use App\Http\Controllers\V1\Admin\Item\ItemCategoriesController;
use App\Http\Controllers\V1\Admin\Item\ItemsController;
use App\Http\Controllers\V1\Admin\Item\UnitsController;
use App\Http\Controllers\V1\Admin\MigrationController;
use App\Http\Controllers\V1\Admin\Mobile\AuthController as MobileAuthController;
use App\Http\Controllers\V1\Admin\Modules\ApiTokenController;
use App\Http\Controllers\V1\Admin\Modules\CompleteModuleInstallationController;
use App\Http\Controllers\V1\Admin\Modules\CopyModuleController;
use App\Http\Controllers\V1\Admin\Modules\DisableModuleController;
use App\Http\Controllers\V1\Admin\Modules\DownloadModuleController;
use App\Http\Controllers\V1\Admin\Modules\EnableModuleController;
use App\Http\Controllers\V1\Admin\Modules\ModuleController;
use App\Http\Controllers\V1\Admin\Modules\ModulesController;
use App\Http\Controllers\V1\Admin\Modules\UnzipModuleController;
use App\Http\Controllers\V1\Admin\Modules\UploadModuleController;
use App\Http\Controllers\V1\Admin\Payment\PaymentMethodsController;
use App\Http\Controllers\V1\Admin\Payment\PaymentsController;
use App\Http\Controllers\V1\Admin\Payment\SendPaymentController;
use App\Http\Controllers\V1\Admin\Payment\SendPaymentPreviewController;
use App\Http\Controllers\V1\Admin\RecurringInvoice\RecurringInvoiceController;
use App\Http\Controllers\V1\Admin\RecurringInvoice\RecurringInvoiceFrequencyController;
use App\Http\Controllers\V1\Admin\Role\AbilitiesController;
use App\Http\Controllers\V1\Admin\Role\RolesController;
use App\Http\Controllers\V1\Admin\Settings\CompanyController;
use App\Http\Controllers\V1\Admin\Settings\CompanyCurrencyCheckTransactionsController;
use App\Http\Controllers\V1\Admin\Settings\DiskController;
use App\Http\Controllers\V1\Admin\Settings\FeatureFlagsController;
// Mail configuration controllers removed - using centralized Postmark setup
use App\Http\Controllers\V1\Admin\Settings\GetCompanySettingsController;
use App\Http\Controllers\V1\Admin\Settings\GetSettingsController;
use App\Http\Controllers\V1\Admin\Settings\GetUserSettingsController;
use App\Http\Controllers\V1\Admin\Settings\PDFConfigurationController;
use App\Http\Controllers\V1\Admin\Settings\TaxTypesController;
use App\Http\Controllers\V1\Admin\Settings\UpdateCompanySettingsController;
use App\Http\Controllers\V1\Admin\Settings\UpdateSettingsController;
use App\Http\Controllers\V1\Admin\Settings\UpdateUserSettingsController;
use App\Http\Controllers\V1\Admin\Update\CheckVersionController;
use App\Http\Controllers\V1\Admin\Update\CopyFilesController;
use App\Http\Controllers\V1\Admin\Update\DeleteFilesController;
use App\Http\Controllers\V1\Admin\Update\DownloadUpdateController;
use App\Http\Controllers\V1\Admin\Update\FinishUpdateController;
use App\Http\Controllers\V1\Admin\Update\MigrateUpdateController;
use App\Http\Controllers\V1\Admin\Update\UnzipUpdateController;
use App\Http\Controllers\V1\Admin\Users\UsersController;
use App\Http\Controllers\V1\Customer\Auth\ForgotPasswordController as AuthForgotPasswordController;
use App\Http\Controllers\V1\Partner\BulkReportController;
use App\Http\Controllers\V1\Partner\PartnerAccountController;
use App\Http\Controllers\V1\Partner\PartnerAccountMappingController;
use App\Http\Controllers\V1\Partner\PartnerJournalExportController;
use App\Http\Controllers\V1\Partner\PartnerJournalImportController;
use App\Http\Controllers\V1\Admin\Payroll\LeaveRequestController;
use App\Http\Controllers\V1\Admin\Payroll\LeaveTypeController;
use App\Http\Controllers\V1\Admin\Payroll\PayrollEmployeeController;
use App\Http\Controllers\V1\Admin\Payroll\PayrollReportController;
use App\Http\Controllers\V1\Admin\Payroll\PayrollRunController;
use App\Http\Controllers\V1\Admin\Payroll\PayslipController;
use App\Http\Controllers\V1\Admin\Payroll\SalaryStructureController;
use App\Http\Controllers\V1\Admin\FiscalDeviceController;
use App\Http\Controllers\V1\Customer\Auth\ResetPasswordController as AuthResetPasswordController;
use App\Http\Controllers\V1\Customer\Estimate\AcceptEstimateController as CustomerAcceptEstimateController;
use App\Http\Controllers\V1\Customer\Estimate\EstimatesController as CustomerEstimatesController;
use App\Http\Controllers\V1\Customer\Expense\ExpensesController as CustomerExpensesController;
use App\Http\Controllers\V1\Customer\General\BootstrapController as CustomerBootstrapController;
use App\Http\Controllers\V1\Customer\General\DashboardController as CustomerDashboardController;
use App\Http\Controllers\V1\Customer\General\ProfileController as CustomerProfileController;
use App\Http\Controllers\V1\Customer\Invoice\InvoicesController as CustomerInvoicesController;
use App\Http\Controllers\V1\Customer\Payment\PaymentMethodController;
use App\Http\Controllers\V1\Customer\Payment\PaymentsController as CustomerPaymentsController;
use App\Http\Controllers\V1\Installation\AppDomainController;
use App\Http\Controllers\V1\Installation\DatabaseConfigurationController;
use App\Http\Controllers\V1\Installation\FilePermissionsController;
use App\Http\Controllers\V1\Installation\FinishController;
use App\Http\Controllers\V1\Installation\LanguagesController;
use App\Http\Controllers\V1\Installation\LoginController;
use App\Http\Controllers\V1\Installation\OnboardingWizardController;
use App\Http\Controllers\V1\Installation\RequirementsController;
use App\Http\Controllers\V1\Webhook\CronJobController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ping
// ----------------------------------

Route::get('ping', function () {
    return response()->json([
        'success' => 'invoiceshelf-self-hosted',
    ]);
})->name('ping');

// QR Code Generation (Public - no auth required)
// ----------------------------------

Route::get('/qr', [QrCodeController::class, 'generate'])->middleware('throttle:public');

// Version 1 endpoints
// --------------------------------------
Route::prefix('/v1')->group(function () {

    // App version
    // ----------------------------------

    Route::get('/app/version', AppVersionController::class);

    // Authentication & Password Reset
    // ----------------------------------

    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        // Web/SPA login - uses session authentication
        Route::post('login', [AdminLoginController::class, 'login']);

        // Mobile logout - uses token authentication
        Route::post('logout', [MobileAuthController::class, 'logout'])->middleware('auth:sanctum');

        // Send reset password mail
        Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);

        // handle reset password form process
        Route::post('reset/password', [ResetPasswordController::class, 'reset']);
    });

    // Countries (public route)
    // ----------------------------------

    Route::get('/countries', CountriesController::class)->middleware('throttle:public');

    // Banking OAuth Callback (public route - no auth required)
    // ----------------------------------

    Route::get('/banking/oauth/callback/{provider}', [\App\Http\Controllers\V1\Admin\Banking\BankingOAuthController::class, 'callback']);
    Route::get('/bank/oauth/callback', [\App\Http\Controllers\V1\Admin\Banking\BankConnectionController::class, 'callback']);

    // Onboarding
    // ----------------------------------

    Route::middleware(['redirect-if-installed'])->prefix('installation')->group(function () {
        Route::get('/wizard-step', [OnboardingWizardController::class, 'getStep']);

        Route::post('/wizard-step', [OnboardingWizardController::class, 'updateStep']);

        Route::post('/wizard-language', [OnboardingWizardController::class, 'saveLanguage']);

        Route::get('/languages', [LanguagesController::class, 'languages']);

        Route::get('/requirements', [RequirementsController::class, 'requirements']);

        Route::get('/permissions', [FilePermissionsController::class, 'permissions']);

        Route::post('/database/config', [DatabaseConfigurationController::class, 'saveDatabaseEnvironment']);

        Route::get('/database/config', [DatabaseConfigurationController::class, 'getDatabaseEnvironment']);

        Route::put('/set-domain', AppDomainController::class);

        Route::post('/login', LoginController::class);

        Route::post('/finish', FinishController::class);
    });

    // Use auth:sanctum for SPA authentication - works with statefulApi() in bootstrap/app.php
    // Sanctum automatically handles sessions for same-domain requests (SANCTUM_STATEFUL_DOMAINS)
    Route::middleware(['auth:sanctum', 'company', 'view-only'])->group(function () {

        // TEMPORARY: Sync abilities for all companies (OUTSIDE bouncer middleware to avoid chicken-egg)
        // This endpoint bypasses bouncer since users need abilities to access anything
        // TODO: Remove after all tenants have abilities synced
        Route::get('/sync-abilities', function () {
            if (! auth()->user()->isOwner()) {
                abort(403, 'Only owners can sync abilities');
            }

            \Artisan::call('abilities:sync');
            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Abilities synced successfully for all companies!',
                'output' => $output,
                'note' => 'This route can be removed after confirming all tenants have abilities',
            ]);
        });

        Route::middleware(['bouncer'])->group(function () {

            // Bootstrap
            // ----------------------------------

            Route::get('/bootstrap', BootstrapController::class);

            // Currencies
            // ----------------------------------

            Route::prefix('/currencies')->group(function () {
                Route::get('/used', GetAllUsedCurrenciesController::class);

                Route::post('/bulk-update-exchange-rate', BulkExchangeRateController::class);
            });

            // Dashboard
            // ----------------------------------

            Route::get('/dashboard', DashboardController::class);

            // Company Deadlines (P8-02)
            // ----------------------------------

            Route::prefix('deadlines')->group(function () {
                Route::get('/', [\App\Http\Controllers\V1\Admin\DeadlineController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\V1\Admin\DeadlineController::class, 'store']);
                Route::post('/{id}/complete', [\App\Http\Controllers\V1\Admin\DeadlineController::class, 'complete']);
                Route::delete('/{id}', [\App\Http\Controllers\V1\Admin\DeadlineController::class, 'destroy']);
            });

            // Auth check
            // ----------------------------------

            Route::get('/auth/check', [MobileAuthController::class, 'check']);

            // Search users
            // ----------------------------------

            Route::get('/search', SearchController::class);

            Route::get('/search/user', SearchUsersController::class);

            // MISC
            // ----------------------------------

            Route::get('/config', ConfigController::class);

            Route::get('/currencies', CurrenciesController::class);

            Route::get('/timezones', TimezonesController::class);

            Route::get('/date/formats', DateFormatsController::class);

            Route::get('/time/formats', TimeFormatsController::class);

            Route::get('/next-number', NextNumberController::class);

            Route::get('/number-placeholders', NumberPlaceholdersController::class);

            Route::get('/current-company', AdminCompanyController::class);

            // Customers
            // ----------------------------------

            Route::post('/customers/delete', [CustomersController::class, 'delete'])->middleware('throttle:strict');

            Route::get('customers/{customer}/stats', CustomerStatsController::class);
            Route::post('customers/{customer}/link-supplier', [CustomerLinkController::class, 'link']);
            Route::delete('customers/{customer}/link-supplier', [CustomerLinkController::class, 'unlink']);
            Route::get('customers/{customer}/ledger', [CustomerLedgerController::class, 'forCustomer']);
            Route::get('customers/match-by-tax-id', [CustomerMatchController::class, 'matchSupplier']);

            Route::resource('customers', CustomersController::class);

            // Items
            // ----------------------------------

            Route::post('/items/delete', [ItemsController::class, 'delete'])->middleware('throttle:strict');
            Route::get('/items/lookup-barcode', [ItemsController::class, 'lookupByBarcode']);

            Route::resource('items', ItemsController::class);

            Route::resource('units', UnitsController::class);

            Route::resource('item-categories', ItemCategoriesController::class);

            // Invoices
            // -------------------------------------------------

            Route::get('/invoices/{invoice}/send/preview', SendInvoicePreviewController::class);

            Route::post('/invoices/{invoice}/send', SendInvoiceController::class);

            Route::post('/invoices/{invoice}/clone', CloneInvoiceController::class);

            Route::post('/invoices/{invoice}/status', ChangeInvoiceStatusController::class);

            // FG-01-10: E-Faktura (UBL XML export) — usage-limited via controller
            Route::post('/invoices/{invoice}/export-xml', [\App\Http\Controllers\V1\Admin\Invoice\ExportXmlController::class, 'export']);

            Route::post('/invoices/{invoice}/payment/cpay', [InvoicesController::class, 'initiateCpayPayment']);

            Route::post('/invoices/bulk-action', [InvoicesController::class, 'bulkAction']);

            Route::post('/invoices/delete', [InvoicesController::class, 'delete'])->middleware('throttle:strict');

            Route::get('/invoices/templates', InvoiceTemplatesController::class);

            // FG-01-00: Apply invoice limit middleware to creation only
            // Note: store() creates new invoices, update() modifies existing ones
            Route::post('/invoices', [InvoicesController::class, 'store'])
                ->middleware('invoice-limit');

            // Other invoice routes (without invoice-limit middleware)
            Route::get('/invoices', [InvoicesController::class, 'index']);
            Route::get('/invoices/{invoice}', [InvoicesController::class, 'show']);
            Route::put('/invoices/{invoice}', [InvoicesController::class, 'update']);
            Route::patch('/invoices/{invoice}', [InvoicesController::class, 'update']);

            // Credit Notes
            // -------------------------------------------------

            Route::post('/credit-notes/{creditNote}/send', [CreditNoteController::class, 'send']);

            Route::post('/credit-notes/{creditNote}/mark-as-viewed', [CreditNoteController::class, 'markAsViewed']);

            Route::post('/credit-notes/{creditNote}/mark-as-completed', [CreditNoteController::class, 'markAsCompleted']);

            Route::post('/credit-notes/delete', [CreditNoteController::class, 'delete']);

            Route::apiResource('credit-notes', CreditNoteController::class);

            // Recurring Invoice (available to all, usage limits apply on free tier)
            // -------------------------------------------------

            Route::get('/recurring-invoice-frequency', RecurringInvoiceFrequencyController::class);

            Route::post('/recurring-invoices/delete', [RecurringInvoiceController::class, 'delete']);

            Route::apiResource('recurring-invoices', RecurringInvoiceController::class);

            // Estimates (available to all, usage limits apply on free tier)
            // -------------------------------------------------

            Route::get('/estimates/{estimate}/send/preview', SendEstimatePreviewController::class);

            Route::post('/estimates/{estimate}/send', SendEstimateController::class);

            Route::post('/estimates/{estimate}/clone', CloneEstimateController::class);

            Route::post('/estimates/{estimate}/status', ChangeEstimateStatusController::class);

            Route::post('/estimates/{estimate}/convert-to-invoice', ConvertEstimateController::class);

            Route::get('/estimates/templates', EstimateTemplatesController::class);

            Route::post('/estimates/delete', [EstimatesController::class, 'delete'])->middleware('throttle:strict');

            Route::apiResource('estimates', EstimatesController::class);

            // Expenses (available to all, usage limits apply on free tier)
            // ----------------------------------

            Route::get('/expenses/{expense}/show/receipt', ShowReceiptController::class);

            Route::post('/expenses/{expense}/upload/receipts', UploadReceiptController::class);

            Route::post('/expenses/delete', [ExpensesController::class, 'delete'])->middleware('throttle:strict');

            Route::apiResource('expenses', ExpensesController::class);

            Route::apiResource('categories', ExpenseCategoriesController::class);

            // Projects
            // ----------------------------------
            // Phase 1.1 - Project Dimension feature for accountants

            Route::get('/projects/list', [\App\Http\Controllers\V1\Admin\Project\ProjectsController::class, 'list']);

            Route::get('/projects/{project}/summary', [\App\Http\Controllers\V1\Admin\Project\ProjectsController::class, 'summary']);

            Route::get('/projects/{project}/documents', [\App\Http\Controllers\V1\Admin\Project\ProjectsController::class, 'documents']);

            Route::post('/projects/delete', [\App\Http\Controllers\V1\Admin\Project\ProjectsController::class, 'delete'])->middleware('throttle:strict');

            Route::apiResource('projects', \App\Http\Controllers\V1\Admin\Project\ProjectsController::class);

            // Recurring Expenses (Phase 4)
            // ----------------------------------

            Route::post('/recurring-expenses/{recurringExpense}/process-now', [\App\Http\Controllers\V1\Admin\RecurringExpenseController::class, 'processNow']);

            Route::apiResource('recurring-expenses', \App\Http\Controllers\V1\Admin\RecurringExpenseController::class);

            // Payroll Module (MK Tax Compliance)
            // ----------------------------------
            // Usage-limited: free tier gets 2 employees, paid tiers get more (see config/subscriptions.php)
            Route::group([], function () {
                // Payroll Employees
                Route::get('/payroll-employees/departments', [PayrollEmployeeController::class, 'departments']);
                Route::post('/payroll-employees/{payrollEmployee}/terminate', [PayrollEmployeeController::class, 'terminate']);
                Route::post('/payroll-employees/{id}/restore', [PayrollEmployeeController::class, 'restore']);
                Route::apiResource('payroll-employees', PayrollEmployeeController::class);

                // Salary Structures
                Route::get('/salary-structures/history/{employeeId}', [SalaryStructureController::class, 'history']);
                Route::post('/salary-structures/{salaryStructure}/set-current', [SalaryStructureController::class, 'setCurrent']);
                Route::apiResource('salary-structures', SalaryStructureController::class);

                // Payroll Runs
                Route::post('/payroll-runs/{payrollRun}/calculate', [PayrollRunController::class, 'calculate']);
                Route::post('/payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve']);
                Route::post('/payroll-runs/{payrollRun}/post', [PayrollRunController::class, 'post']);
                Route::post('/payroll-runs/{payrollRun}/mark-paid', [PayrollRunController::class, 'markPaid']);
                Route::get('/payroll-runs/{payrollRun}/bank-file', [PayrollRunController::class, 'downloadBankFile']);
                Route::apiResource('payroll-runs', PayrollRunController::class);

                // Payslips
                Route::get('/payslips/{payrollRunLine}/download', [PayslipController::class, 'download']);
                Route::get('/payslips/{payrollRunLine}/preview', [PayslipController::class, 'preview']);
                Route::get('/payslips/bulk/{payrollRunId}', [PayslipController::class, 'bulkDownload']);
                Route::get('/payslips/download-zip/{token}', [PayslipController::class, 'downloadZip']);

                // Payroll Reports
                Route::get('/payroll-reports/tax-summary', [PayrollReportController::class, 'taxSummary']);
                Route::get('/payroll-reports/statistics', [PayrollReportController::class, 'statistics']);
                Route::get('/payroll-reports/employee-history/{employeeId}', [PayrollReportController::class, 'employeeHistory']);
                Route::get('/payroll-reports/monthly-comparison', [PayrollReportController::class, 'monthlyComparison']);
                Route::get('/payroll-reports/export-tax-summary', [PayrollReportController::class, 'exportTaxSummary']);
                Route::get('/payroll-reports/download-mpin-xml', [PayrollReportController::class, 'downloadMpinXml']);
                Route::get('/payroll-reports/download-ddv04-xml', [PayrollReportController::class, 'downloadDdv04Xml']);

                // Leave Management
                Route::prefix('leave-types')->group(function () {
                    Route::get('/', [LeaveTypeController::class, 'index']);
                });
                Route::prefix('leave-requests')->group(function () {
                    Route::get('/', [LeaveRequestController::class, 'index']);
                    Route::post('/', [LeaveRequestController::class, 'store']);
                    Route::get('/balance/{employee}', [LeaveRequestController::class, 'balance']);
                    Route::get('/{id}', [LeaveRequestController::class, 'show']);
                    Route::patch('/{id}', [LeaveRequestController::class, 'update']);
                    Route::delete('/{id}', [LeaveRequestController::class, 'destroy']);
                    Route::post('/{id}/approve', [LeaveRequestController::class, 'approve']);
                    Route::post('/{id}/reject', [LeaveRequestController::class, 'reject']);
                });
            });

            // Fiscal Devices (P10-02)
            // ----------------------------------

            Route::prefix('fiscal-devices')->group(function () {
                Route::get('/', [FiscalDeviceController::class, 'index']);
                Route::post('/', [FiscalDeviceController::class, 'store']);
                Route::get('/erpnet-status', [FiscalDeviceController::class, 'erpnetStatus']);
                Route::get('/{id}', [FiscalDeviceController::class, 'show']);
                Route::patch('/{id}', [FiscalDeviceController::class, 'update']);
                Route::delete('/{id}', [FiscalDeviceController::class, 'destroy']);
                Route::get('/{id}/status', [FiscalDeviceController::class, 'status']);
                Route::post('/{id}/send-invoice', [FiscalDeviceController::class, 'sendInvoice']);
                Route::get('/{id}/daily-report', [FiscalDeviceController::class, 'dailyReport']);
                Route::get('/{id}/receipts', [FiscalDeviceController::class, 'receipts']);
            });

            // Company Lookup (Central Registry)
            // ----------------------------------

            Route::get('/company-lookup', [\App\Http\Controllers\V1\Admin\CompanyLookupController::class, 'lookup']);

            // WooCommerce Integration
            // ----------------------------------

            Route::prefix('woocommerce')->middleware(['tier:business'])->group(function () {
                Route::get('/settings', [\App\Http\Controllers\V1\Admin\Integration\WooCommerceController::class, 'getSettings']);
                Route::post('/settings', [\App\Http\Controllers\V1\Admin\Integration\WooCommerceController::class, 'saveSettings']);
                Route::post('/test-connection', [\App\Http\Controllers\V1\Admin\Integration\WooCommerceController::class, 'testConnection']);
                Route::post('/sync', [\App\Http\Controllers\V1\Admin\Integration\WooCommerceController::class, 'syncNow']);
                Route::get('/sync-history', [\App\Http\Controllers\V1\Admin\Integration\WooCommerceController::class, 'syncHistory']);
            });
            // Viber Platform Settings (super-admin only)
            // ----------------------------------

            Route::middleware('super-admin')->prefix('admin/viber')->group(function () {
                Route::get('/settings', [\App\Http\Controllers\V1\Admin\ViberSettingsController::class, 'getSettings']);
                Route::post('/settings', [\App\Http\Controllers\V1\Admin\ViberSettingsController::class, 'saveSettings']);
                Route::post('/test-connection', [\App\Http\Controllers\V1\Admin\ViberSettingsController::class, 'testConnection']);
            });

            // Viber availability check (any authenticated user)
            Route::get('/viber/availability', [\App\Http\Controllers\V1\Admin\ViberSettingsController::class, 'checkAvailability']);


            // Exports (Phase 4)
            // ----------------------------------

            Route::prefix('companies/{company}')->group(function () {
                Route::get('/exports', [\App\Http\Controllers\V1\Admin\ExportController::class, 'index']);

                Route::post('/exports', [\App\Http\Controllers\V1\Admin\ExportController::class, 'store']);

                Route::get('/exports/{exportJob}/download', [\App\Http\Controllers\V1\Admin\ExportController::class, 'download'])
                    ->name('exports.download');

                Route::delete('/exports/{exportJob}', [\App\Http\Controllers\V1\Admin\ExportController::class, 'destroy']);
            });

            // User Data Exports (GDPR Compliance)
            // ----------------------------------

            Route::get('/user-data-exports', [\App\Http\Controllers\V1\Admin\Settings\DataExportController::class, 'index']);

            Route::get('/user-data-exports/latest', [\App\Http\Controllers\V1\Admin\Settings\DataExportController::class, 'latest']);

            Route::post('/user-data-exports', [\App\Http\Controllers\V1\Admin\Settings\DataExportController::class, 'store']);

            Route::get('/user-data-exports/{export}/download', [\App\Http\Controllers\V1\Admin\Settings\DataExportController::class, 'download'])
                ->name('user-data-export.download');

            Route::delete('/user-data-exports/{export}', [\App\Http\Controllers\V1\Admin\Settings\DataExportController::class, 'destroy']);

            // Payments
            // ----------------------------------

            Route::get('/payments/{payment}/send/preview', SendPaymentPreviewController::class);

            Route::post('/payments/{payment}/send', SendPaymentController::class);

            Route::post('/payments/delete', [PaymentsController::class, 'delete'])->middleware('throttle:strict');

            Route::apiResource('payments', PaymentsController::class);

            Route::apiResource('payment-methods', PaymentMethodsController::class);

            // Custom fields (available to all, usage limits apply on free tier)
            // ----------------------------------

            Route::resource('custom-fields', CustomFieldsController::class);

            // Backup & Disk
            // ----------------------------------

            Route::apiResource('backups', BackupsController::class);

            Route::apiResource('/disks', DiskController::class);

            Route::get('download-backup', DownloadBackupController::class);

            Route::get('/disk/drivers', [DiskController::class, 'getDiskDrivers']);

            // Exchange Rate & Currencies (available to all for display)
            // Multi-currency transactions require Business+ tier
            // ----------------------------------

            // Exchange rates now use free Frankfurter API (no provider config needed)
            Route::get('/currencies/{currency}/exchange-rate', GetExchangeRateController::class);

            // Settings
            // ----------------------------------

            Route::get('/me', [CompanyController::class, 'getUser']);

            Route::put('/me', [CompanyController::class, 'updateProfile']);

            Route::get('/me/settings', GetUserSettingsController::class);

            Route::put('/me/settings', UpdateUserSettingsController::class);

            Route::post('/me/upload-avatar', [CompanyController::class, 'uploadAvatar']);

            Route::put('/company', [CompanyController::class, 'updateCompany']);

            Route::post('/company/upload-logo', [CompanyController::class, 'uploadCompanyLogo']);

            Route::get('/company/settings', GetCompanySettingsController::class);

            Route::post('/company/settings', UpdateCompanySettingsController::class);

            Route::get('/settings', GetSettingsController::class);

            Route::post('/settings', UpdateSettingsController::class);

            // Feature flags - super admin only
            Route::middleware('super-admin')->group(function () {
                Route::get('/settings/feature-flags', [FeatureFlagsController::class, 'index']);
                Route::post('/settings/feature-flags/{flag}/toggle', [FeatureFlagsController::class, 'toggle']);
            });

            Route::get('/company/has-transactions', CompanyCurrencyCheckTransactionsController::class);

            // Certificates
            // ----------------------------------
            Route::get('/certificates/current', [CertUploadController::class, 'current']);
            Route::post('/certificates/upload', [CertUploadController::class, 'upload']);
            Route::post('/certificates/{id}/verify', [CertUploadController::class, 'verify']);
            Route::delete('/certificates/{id}', [CertUploadController::class, 'delete']);

            // Mails - using centralized Postmark setup
            // ----------------------------------
            // This endpoint returns mail from_name/from_mail for invoice sending UI
            Route::get('/company/mail/config', \App\Http\Controllers\V1\Admin\Settings\GetCompanyMailConfigurationController::class);

            // PDF Generation
            // ----------------------------------

            Route::get('/pdf/drivers', [PDFConfigurationController::class, 'getDrivers']);

            Route::get('/pdf/config', [PDFConfigurationController::class, 'getEnvironment']);

            Route::post('/pdf/config', [PDFConfigurationController::class, 'saveEnvironment']);

            Route::apiResource('notes', NotesController::class);

            // Tax Types
            // ----------------------------------

            Route::apiResource('tax-types', TaxTypesController::class);

            // E-Invoices
            // ----------------------------------

            // FG-01-11: E-Invoice operations (usage-limited, see UsageLimitService)
            Route::prefix('e-invoices')->middleware(['throttle:60,1'])->group(function () {
                // Static routes first (before wildcard {id} routes)
                Route::get('/', [EInvoiceController::class, 'index']);
                Route::get('/portal-status', [EInvoiceController::class, 'checkPortalStatus']);
                Route::get('/submission-queue', [EInvoiceController::class, 'getSubmissionQueue']);
                Route::get('/by-invoice/{invoiceId}', [EInvoiceController::class, 'showByInvoice']);
                Route::post('/generate/{invoiceId}', [EInvoiceController::class, 'generate']);

                // P7-02: Incoming e-invoice endpoints (must be before /{id} wildcard)
                Route::prefix('incoming')->group(function () {
                    Route::get('/', [EInvoiceController::class, 'listIncoming']);
                    Route::post('/poll', [EInvoiceController::class, 'pollPortalInbox']);
                    Route::get('/{id}', [EInvoiceController::class, 'showIncoming'])->where('id', '[0-9]+');
                    Route::post('/{id}/accept', [EInvoiceController::class, 'acceptIncoming'])->where('id', '[0-9]+');
                    Route::post('/{id}/reject', [EInvoiceController::class, 'rejectIncoming'])->where('id', '[0-9]+');
                });

                // Wildcard {id} routes last
                Route::get('/{id}', [EInvoiceController::class, 'show'])->where('id', '[0-9]+');
                Route::post('/{id}/sign', [EInvoiceController::class, 'sign'])->where('id', '[0-9]+');
                Route::post('/{id}/submit', [EInvoiceController::class, 'submit'])->where('id', '[0-9]+');
                Route::post('/{id}/simulate', [EInvoiceController::class, 'simulate'])->where('id', '[0-9]+');
                Route::get('/{id}/download-xml', [EInvoiceController::class, 'downloadXml'])->where('id', '[0-9]+');
                Route::post('/{submissionId}/resubmit', [EInvoiceController::class, 'resubmit'])->where('submissionId', '[0-9]+');
            });

            // VAT Returns
            // ----------------------------------

            Route::prefix('tax')->group(function () {
                Route::post('vat-return/preview', [App\Http\Controllers\V1\Admin\Tax\VatReturnController::class, 'preview']);
                Route::post('vat-return', [App\Http\Controllers\V1\Admin\Tax\VatReturnController::class, 'generate']);
                Route::post('vat-return/file', [App\Http\Controllers\V1\Admin\Tax\VatReturnController::class, 'file']);
                Route::get('vat-return/periods', [App\Http\Controllers\V1\Admin\Tax\VatReturnController::class, 'getPeriods']);
                Route::get('vat-return/periods/{periodId}/returns', [App\Http\Controllers\V1\Admin\Tax\VatReturnController::class, 'getReturns']);
                Route::post('vat-return/periods/{periodId}/close', [App\Http\Controllers\V1\Admin\Tax\VatReturnController::class, 'closePeriod']);
                Route::post('vat-return/periods/{periodId}/reopen', [App\Http\Controllers\V1\Admin\Tax\VatReturnController::class, 'reopenPeriod']);
                Route::get('vat-return/{id}/download-xml', [App\Http\Controllers\V1\Admin\Tax\VatReturnController::class, 'downloadXml']);
                Route::get('vat-status/{company}', [App\Http\Controllers\V1\Admin\Tax\VatReturnController::class, 'status']);

                // Corporate Income Tax (CIT / DB-VP) Returns
                Route::post('cit-return/preview', [App\Http\Controllers\V1\Admin\Tax\CitReturnController::class, 'preview']);
                Route::post('cit-return', [App\Http\Controllers\V1\Admin\Tax\CitReturnController::class, 'generate']);
                Route::post('cit-return/file', [App\Http\Controllers\V1\Admin\Tax\CitReturnController::class, 'file']);
                Route::get('cit-return/periods', [App\Http\Controllers\V1\Admin\Tax\CitReturnController::class, 'getPeriods']);
                Route::get('cit-return/periods/{periodId}/returns', [App\Http\Controllers\V1\Admin\Tax\CitReturnController::class, 'getReturns']);
                Route::get('cit-return/{id}/download-xml', [App\Http\Controllers\V1\Admin\Tax\CitReturnController::class, 'downloadXml']);

                // UJP Tax Forms (unified endpoint for all forms)
                Route::get('ujp-forms/list', [App\Http\Controllers\V1\Admin\Tax\UjpFormController::class, 'list']);
                Route::get('ujp-forms/{formCode}/preview', [App\Http\Controllers\V1\Admin\Tax\UjpFormController::class, 'preview']);
                Route::post('ujp-forms/{formCode}/xml', [App\Http\Controllers\V1\Admin\Tax\UjpFormController::class, 'generateXml']);
                Route::post('ujp-forms/{formCode}/pdf', [App\Http\Controllers\V1\Admin\Tax\UjpFormController::class, 'generatePdf']);
                Route::post('ujp-forms/{formCode}/file', [App\Http\Controllers\V1\Admin\Tax\UjpFormController::class, 'file']);
            });

            // Suppliers, Bills, Bill Payments (Accounts Payable)
            // ----------------------------------
            Route::post('/suppliers/delete', [\App\Http\Controllers\V1\Admin\AccountsPayable\SuppliersController::class, 'delete']);
            Route::get('suppliers/{supplier}/stats', \App\Http\Controllers\V1\Admin\AccountsPayable\SupplierStatsController::class);
            Route::get('suppliers/{supplier}/ledger', [CustomerLedgerController::class, 'forSupplier']);
            Route::get('suppliers/match-by-tax-id', [CustomerMatchController::class, 'matchCustomer']);
            Route::apiResource('suppliers', \App\Http\Controllers\V1\Admin\AccountsPayable\SuppliersController::class);

            Route::post('/bills/{bill}/send', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillsController::class, 'send']);
            Route::post('/bills/{bill}/clone', CloneBillController::class);
            Route::post('/bills/{bill}/mark-as-viewed', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillsController::class, 'markAsViewed']);
            Route::post('/bills/{bill}/mark-as-completed', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillsController::class, 'markAsCompleted']);
            Route::get('/bills/{bill}/download-pdf', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillsController::class, 'downloadPdf']);
            Route::post('/bills/delete', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillsController::class, 'delete']);
            Route::apiResource('bills', \App\Http\Controllers\V1\Admin\AccountsPayable\BillsController::class);

            Route::get('/bills/{bill}/payments', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillPaymentsController::class, 'index']);
            Route::post('/bills/{bill}/payments', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillPaymentsController::class, 'store']);
            Route::get('/bills/{bill}/payments/{payment}', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillPaymentsController::class, 'show']);
            Route::put('/bills/{bill}/payments/{payment}', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillPaymentsController::class, 'update']);
            Route::delete('/bills/{bill}/payments/{payment}', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillPaymentsController::class, 'destroy']);

            // Bills CSV/XLSX Import
            Route::post('/bills/import', [\App\Http\Controllers\V1\Admin\AccountsPayable\BillsImportController::class, 'import']);

            // Invoice Scanner (OCR → Bill)
            Route::post('/receipts/scan', [\App\Http\Controllers\V1\Admin\AccountsPayable\ReceiptScannerController::class, 'scan']);

            // Proforma Invoices
            // ----------------------------------

            Route::get('/proforma-invoices/{proformaInvoice}/send/preview', [\App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoicesController::class, 'sendPreview']);
            Route::post('/proforma-invoices/{proformaInvoice}/send', [\App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoicesController::class, 'send']);

            Route::post('/proforma-invoices/{proformaInvoice}/mark-as-viewed', [\App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoicesController::class, 'markAsViewed']);

            Route::post('/proforma-invoices/{proformaInvoice}/mark-as-expired', [\App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoicesController::class, 'markAsExpired']);

            Route::post('/proforma-invoices/{proformaInvoice}/mark-as-rejected', [\App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoicesController::class, 'markAsRejected']);

            Route::post('/proforma-invoices/{proformaInvoice}/convert-to-invoice', [\App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoicesController::class, 'convertToInvoice']);

            Route::get('/proforma-invoices/{proformaInvoice}/download-pdf', [\App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoicesController::class, 'downloadPdf']);

            Route::post('/proforma-invoices/delete', [\App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoicesController::class, 'delete']);

            Route::get('/proforma-invoices/templates', \App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoiceTemplatesController::class);

            Route::apiResource('proforma-invoices', \App\Http\Controllers\V1\Admin\ProformaInvoices\ProformaInvoicesController::class);

            // Audit Logs
            // ----------------------------------

            Route::get('/audit-logs/document/{type}/{id}', [\App\Http\Controllers\V1\Admin\AuditLogs\AuditLogController::class, 'forDocument']);

            Route::get('/audit-logs/user/{user}', [\App\Http\Controllers\V1\Admin\AuditLogs\AuditLogController::class, 'forUser']);

            Route::apiResource('audit-logs', \App\Http\Controllers\V1\Admin\AuditLogs\AuditLogController::class)->only(['index', 'show']);

            // Support Ticketing System
            // TRACK 3: MILESTONE 3.1 - Customer Ticket Portal
            // ----------------------------------

            Route::prefix('support')->group(function () {
                // Tickets (Customer View - Tenant Isolated)
                Route::get('/tickets', [\App\Http\Controllers\V1\Admin\Support\TicketController::class, 'index']);
                Route::post('/tickets', [\App\Http\Controllers\V1\Admin\Support\TicketController::class, 'store']);
                Route::get('/tickets/{ticket}', [\App\Http\Controllers\V1\Admin\Support\TicketController::class, 'show']);
                Route::put('/tickets/{ticket}', [\App\Http\Controllers\V1\Admin\Support\TicketController::class, 'update']);
                Route::delete('/tickets/{ticket}', [\App\Http\Controllers\V1\Admin\Support\TicketController::class, 'destroy']);

                // Ticket Messages (Replies)
                Route::get('/tickets/{ticket}/messages', [\App\Http\Controllers\V1\Admin\Support\TicketMessageController::class, 'index']);
                Route::post('/tickets/{ticket}/messages', [\App\Http\Controllers\V1\Admin\Support\TicketMessageController::class, 'store']);
                Route::put('/tickets/{ticket}/messages/{message}', [\App\Http\Controllers\V1\Admin\Support\TicketMessageController::class, 'update']);
                Route::delete('/tickets/{ticket}/messages/{message}', [\App\Http\Controllers\V1\Admin\Support\TicketMessageController::class, 'destroy']);

                // TRACK 3: MILESTONE 3.2 - Agent Dashboard (Admin/Support Only)
                // Admin Ticket Operations (Cross-Tenant - Admins/Support Only)
                Route::prefix('admin')->group(function () {
                    Route::get('/tickets', [\App\Http\Controllers\V1\Admin\Support\AdminTicketController::class, 'listAllTickets']);
                    Route::get('/statistics', [\App\Http\Controllers\V1\Admin\Support\AdminTicketController::class, 'getStatistics']);
                    Route::post('/tickets/{ticket}/assign', [\App\Http\Controllers\V1\Admin\Support\AdminTicketController::class, 'assignTicket']);
                    Route::post('/tickets/{ticket}/change-status', [\App\Http\Controllers\V1\Admin\Support\AdminTicketController::class, 'changeStatus']);
                    Route::post('/tickets/{ticket}/internal-notes', [\App\Http\Controllers\V1\Admin\Support\AdminTicketController::class, 'addInternalNote']);

                    // Super Admin Company Browser (Support Mode)
                    Route::get('/companies/search', [\App\Http\Controllers\V1\Admin\Support\AdminCompanyBrowserController::class, 'search']);
                    Route::post('/companies/{company}/enter-support-mode', [\App\Http\Controllers\V1\Admin\Support\AdminCompanyBrowserController::class, 'enterSupportMode']);
                    Route::post('/support-mode/exit', [\App\Http\Controllers\V1\Admin\Support\AdminCompanyBrowserController::class, 'exitSupportMode']);
                    Route::get('/support-mode/status', [\App\Http\Controllers\V1\Admin\Support\AdminCompanyBrowserController::class, 'status']);
                });

                // Canned Responses (Admin/Support Only)
                Route::prefix('canned-responses')->group(function () {
                    Route::get('/', [\App\Http\Controllers\V1\Admin\Support\CannedResponseController::class, 'index']);
                    Route::post('/', [\App\Http\Controllers\V1\Admin\Support\CannedResponseController::class, 'store']);
                    Route::get('/{cannedResponse}', [\App\Http\Controllers\V1\Admin\Support\CannedResponseController::class, 'show']);
                    Route::put('/{cannedResponse}', [\App\Http\Controllers\V1\Admin\Support\CannedResponseController::class, 'update']);
                    Route::delete('/{cannedResponse}', [\App\Http\Controllers\V1\Admin\Support\CannedResponseController::class, 'destroy']);
                    Route::post('/{cannedResponse}/use', [\App\Http\Controllers\V1\Admin\Support\CannedResponseController::class, 'use']);
                });
            });

            // Roles
            // ----------------------------------

            Route::get('abilities', AbilitiesController::class);

            Route::apiResource('roles', RolesController::class);

            // Import/Migration Wizard
            // ----------------------------------

            Route::prefix('imports')->group(function () {
                Route::get('/', [MigrationController::class, 'index']);
                Route::post('/', [MigrationController::class, 'store']);
                Route::get('/{import}', [MigrationController::class, 'show']);
                Route::post('/{import}/mapping', [MigrationController::class, 'mapping']);
                Route::post('/{import}/validate', [MigrationController::class, 'validateImport']);
                Route::post('/{import}/commit', [MigrationController::class, 'commit']);
                Route::delete('/{import}', [MigrationController::class, 'destroy']);
                Route::get('/{import}/progress', [MigrationController::class, 'progress']);
                Route::get('/{import}/logs', [MigrationController::class, 'logs']);
            });

            // Accounting Reports (IFRS) - available to all tiers
            // Advanced IFRS reports (trial balance, balance sheet, income statement)
            // are basic reports that help users understand the value of the platform
            // Feature flag: FEATURE_ACCOUNTING_BACKBONE
            // ----------------------------------

            Route::prefix('accounting')->group(function () {
                Route::get('/trial-balance', [AccountingReportsController::class, 'trialBalance']);
                Route::get('/balance-sheet', [AccountingReportsController::class, 'balanceSheet']);
                Route::get('/income-statement', [AccountingReportsController::class, 'incomeStatement']);
                Route::get('/general-ledger', [AccountingReportsController::class, 'generalLedger']);
                Route::get('/general-ledger/export', [AccountingReportsController::class, 'generalLedgerExport']);
                Route::get('/journal-entries', [AccountingReportsController::class, 'journalEntries']);
                Route::get('/journal-entries/export', [AccountingReportsController::class, 'journalEntriesExport']);
                Route::get('/cash-flow', [AccountingReportsController::class, 'cashFlow']);
                Route::get('/equity-changes', [AccountingReportsController::class, 'equityChanges']);
                Route::post('/backfill-invoices', [AccountingReportsController::class, 'backfillInvoices']);

                // Fixed Assets
                Route::prefix('fixed-assets')->group(function () {
                    Route::get('/', [\App\Http\Controllers\V1\Admin\Accounting\FixedAssetController::class, 'index']);
                    Route::post('/', [\App\Http\Controllers\V1\Admin\Accounting\FixedAssetController::class, 'store']);
                    Route::get('/register', [\App\Http\Controllers\V1\Admin\Accounting\FixedAssetController::class, 'register']);
                    Route::get('/{id}', [\App\Http\Controllers\V1\Admin\Accounting\FixedAssetController::class, 'show']);
                    Route::put('/{id}', [\App\Http\Controllers\V1\Admin\Accounting\FixedAssetController::class, 'update']);
                    Route::post('/{id}/dispose', [\App\Http\Controllers\V1\Admin\Accounting\FixedAssetController::class, 'dispose']);
                    Route::delete('/{id}', [\App\Http\Controllers\V1\Admin\Accounting\FixedAssetController::class, 'destroy']);
                });
            });

            // Project Reports - available to all tiers
            // Phase 1.1 - Project Dimension reporting
            // ----------------------------------

            Route::prefix('reports')->group(function () {
                Route::get('/projects', [\App\Http\Controllers\V1\Admin\Report\ProjectReportController::class, 'index']);

                // Financial Report Exports (CSV/PDF)
                Route::get('/export/balance-sheet/{hash}', [\App\Http\Controllers\V1\Admin\Report\ReportExportController::class, 'balanceSheet']);
                Route::get('/export/income-statement/{hash}', [\App\Http\Controllers\V1\Admin\Report\ReportExportController::class, 'incomeStatement']);
                Route::get('/export/trial-balance/{hash}', [\App\Http\Controllers\V1\Admin\Report\ReportExportController::class, 'trialBalance']);
                Route::get('/export/tax-summary/{hash}', [\App\Http\Controllers\V1\Admin\Report\ReportExportController::class, 'taxSummary']);
                Route::get('/export/expenses/{hash}', [\App\Http\Controllers\V1\Admin\Report\ReportExportController::class, 'expenses']);
                Route::get('/projects/{id}', [\App\Http\Controllers\V1\Admin\Report\ProjectReportController::class, 'show']);
            });

            // Migration Wizard (Laravel Excel)
            // Feature flag: FEATURE_MIGRATION_WIZARD
            // ----------------------------------

            Route::prefix('migration')->group(function () {
                Route::post('/upload', [MigrationController::class, 'upload']);
                Route::get('/{job}/preview', [MigrationController::class, 'preview']);
                Route::get('/presets', [MigrationController::class, 'availablePresets']);
                Route::get('/presets/{source}', [MigrationController::class, 'presets']);
                Route::post('/{job}/dry-run', [MigrationController::class, 'dryRun']);
                Route::post('/{job}/import', [MigrationController::class, 'import']);
                Route::get('/{job}/status', [MigrationController::class, 'status']);
                Route::get('/{job}/errors', [MigrationController::class, 'errors']);
                Route::get('/templates', [MigrationController::class, 'templates']);
                Route::get('/templates/{type}', [MigrationController::class, 'downloadTemplate'])->name('migration.download-template');
            });

            // Stock Management Reports
            // Feature flag: FACTURINO_STOCK_V1_ENABLED
            // ----------------------------------

            Route::prefix('stock')->group(function () {
                // Warehouse list for dropdowns
                Route::get('/warehouses', [\App\Http\Controllers\V1\Admin\Stock\StockReportsController::class, 'warehouses']);

                // Item Stock Card (movement history with running balance)
                Route::get('/items/{item}/card', [\App\Http\Controllers\V1\Admin\Stock\StockReportsController::class, 'itemCard']);

                // Warehouse Inventory (current stock by warehouse)
                Route::get('/warehouses/{warehouse}/inventory', [\App\Http\Controllers\V1\Admin\Stock\StockReportsController::class, 'warehouseInventory']);

                // Inventory Valuation (total stock value report)
                Route::get('/inventory-valuation', [\App\Http\Controllers\V1\Admin\Stock\StockReportsController::class, 'inventoryValuation']);

                // Inventory List (for physical counting)
                Route::get('/inventory-list', [\App\Http\Controllers\V1\Admin\Stock\StockReportsController::class, 'inventoryList']);
            });

            // Daily Closing & Period Lock (Phase 3)
            // ----------------------------------
            Route::prefix('accounting')->group(function () {
                // Daily Closings
                Route::get('/daily-closings', [\App\Http\Controllers\V1\Admin\Accounting\PeriodLockController::class, 'indexDailyClosings']);
                Route::post('/daily-closings', [\App\Http\Controllers\V1\Admin\Accounting\PeriodLockController::class, 'storeDailyClosing']);
                Route::delete('/daily-closings/{id}', [\App\Http\Controllers\V1\Admin\Accounting\PeriodLockController::class, 'destroyDailyClosing']);

                // Period Locks
                Route::get('/period-locks', [\App\Http\Controllers\V1\Admin\Accounting\PeriodLockController::class, 'indexPeriodLocks']);
                Route::post('/period-locks', [\App\Http\Controllers\V1\Admin\Accounting\PeriodLockController::class, 'storePeriodLock']);
                Route::delete('/period-locks/{id}', [\App\Http\Controllers\V1\Admin\Accounting\PeriodLockController::class, 'destroyPeriodLock']);

                // Check if date is locked
                Route::get('/check-date', [\App\Http\Controllers\V1\Admin\Accounting\PeriodLockController::class, 'checkDate']);

                // Chart of Accounts (Phase 4)
                Route::get('/accounts', [\App\Http\Controllers\V1\Admin\Accounting\AccountController::class, 'index']);
                Route::get('/accounts/tree', [\App\Http\Controllers\V1\Admin\Accounting\AccountController::class, 'tree']);
                Route::get('/accounts/{id}', [\App\Http\Controllers\V1\Admin\Accounting\AccountController::class, 'show']);
                Route::post('/accounts', [\App\Http\Controllers\V1\Admin\Accounting\AccountController::class, 'store']);
                Route::put('/accounts/{id}', [\App\Http\Controllers\V1\Admin\Accounting\AccountController::class, 'update']);
                Route::delete('/accounts/{id}', [\App\Http\Controllers\V1\Admin\Accounting\AccountController::class, 'destroy']);

                // Account Mappings
                Route::get('/account-mappings', [\App\Http\Controllers\V1\Admin\Accounting\AccountController::class, 'indexMappings']);
                Route::post('/account-mappings', [\App\Http\Controllers\V1\Admin\Accounting\AccountController::class, 'upsertMapping']);
                Route::delete('/account-mappings/{id}', [\App\Http\Controllers\V1\Admin\Accounting\AccountController::class, 'destroyMapping']);

                // Journal Export (Phase 4)
                Route::get('/journals', [\App\Http\Controllers\V1\Admin\Accounting\JournalExportController::class, 'index']);
                Route::get('/journals/export', [\App\Http\Controllers\V1\Admin\Accounting\JournalExportController::class, 'export']);
                Route::get('/journals/formats', [\App\Http\Controllers\V1\Admin\Accounting\JournalExportController::class, 'formats']);

                // AI Account Suggestions (Phase 4.2) - available to all, usage limits apply
                Route::prefix('suggestions')->group(function () {
                    Route::get('/{type}/{id}', [\App\Http\Controllers\V1\Admin\Accounting\AccountSuggestionController::class, 'suggest']);
                    Route::post('/confirm', [\App\Http\Controllers\V1\Admin\Accounting\AccountSuggestionController::class, 'confirm']);
                    Route::get('/pending', [\App\Http\Controllers\V1\Admin\Accounting\AccountSuggestionController::class, 'pending']);
                    Route::post('/bulk-confirm', [\App\Http\Controllers\V1\Admin\Accounting\AccountSuggestionController::class, 'bulkConfirm']);
                });
            });

            // Year-End Closing Wizard
            // ----------------------------------
            Route::prefix('year-end/{year}')->group(function () {
                Route::get('/preflight', [\App\Http\Controllers\V1\Admin\Accounting\YearEndClosingController::class, 'preflight']);
                Route::get('/summary', [\App\Http\Controllers\V1\Admin\Accounting\YearEndClosingController::class, 'summary']);
                Route::post('/closing', [\App\Http\Controllers\V1\Admin\Accounting\YearEndClosingController::class, 'closing']);
                Route::get('/reports/{type}', [\App\Http\Controllers\V1\Admin\Accounting\YearEndClosingController::class, 'reports']);
                Route::post('/finalize', [\App\Http\Controllers\V1\Admin\Accounting\YearEndClosingController::class, 'finalize']);
                Route::post('/undo', [\App\Http\Controllers\V1\Admin\Accounting\YearEndClosingController::class, 'undo']);
            });

            // Client Document Upload Portal (P8-01)
            // ----------------------------------
            Route::prefix('client-documents')->group(function () {
                Route::post('/upload', [\App\Http\Controllers\V1\Client\ClientDocumentController::class, 'upload']);
                Route::get('/', [\App\Http\Controllers\V1\Client\ClientDocumentController::class, 'index']);
                Route::get('/{id}/download', [\App\Http\Controllers\V1\Client\ClientDocumentController::class, 'download']);
                Route::get('/{id}', [\App\Http\Controllers\V1\Client\ClientDocumentController::class, 'show']);
                Route::delete('/{id}', [\App\Http\Controllers\V1\Client\ClientDocumentController::class, 'destroy']);
            });

            // F1: Compensations (Kompenzacija) - Standard+ tier
            // ----------------------------------
            Route::prefix('compensations')->middleware('tier:standard')->group(function () {
                Route::get('/', [\Modules\Mk\Http\Controllers\CompensationController::class, 'index']);
                Route::get('/opportunities', [\Modules\Mk\Http\Controllers\CompensationController::class, 'opportunities']);
                Route::get('/eligible-documents', [\Modules\Mk\Http\Controllers\CompensationController::class, 'eligibleDocuments']);
                Route::post('/', [\Modules\Mk\Http\Controllers\CompensationController::class, 'store']);
                Route::get('/{id}', [\Modules\Mk\Http\Controllers\CompensationController::class, 'show']);
                Route::post('/{id}/confirm', [\Modules\Mk\Http\Controllers\CompensationController::class, 'confirm']);
                Route::post('/{id}/cancel', [\Modules\Mk\Http\Controllers\CompensationController::class, 'cancel']);
                Route::get('/{id}/pdf', [\Modules\Mk\Http\Controllers\CompensationController::class, 'pdf']);
            });

            // F2: Payment Orders (Nalozi za Plakjanje) - Standard+ tier
            // ----------------------------------
            Route::prefix('payment-orders')->middleware('tier:standard')->group(function () {
                Route::get('/', [\Modules\Mk\Http\Controllers\PaymentOrderController::class, 'index']);
                Route::get('/payable-bills', [\Modules\Mk\Http\Controllers\PaymentOrderController::class, 'payableBills']);
                Route::get('/overdue-summary', [\Modules\Mk\Http\Controllers\PaymentOrderController::class, 'overdueSummary']);
                Route::post('/', [\Modules\Mk\Http\Controllers\PaymentOrderController::class, 'store']);
                Route::get('/{id}', [\Modules\Mk\Http\Controllers\PaymentOrderController::class, 'show']);
                Route::post('/{id}/approve', [\Modules\Mk\Http\Controllers\PaymentOrderController::class, 'approve']);
                Route::get('/{id}/export', [\Modules\Mk\Http\Controllers\PaymentOrderController::class, 'export']);
                Route::post('/{id}/confirm', [\Modules\Mk\Http\Controllers\PaymentOrderController::class, 'confirm']);
                Route::post('/{id}/cancel', [\Modules\Mk\Http\Controllers\PaymentOrderController::class, 'cancel']);
            });

            // F3: Cost Centers - Standard+ tier
            // ----------------------------------
            Route::prefix('cost-centers')->middleware('tier:standard')->group(function () {
                Route::get('/', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'index']);
                Route::post('/', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'store']);
                Route::get('/summary', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'summary']);
                Route::get('/rules', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'rules']);
                Route::post('/rules', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'storeRule']);
                Route::put('/rules/{ruleId}', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'updateRule']);
                Route::delete('/rules/{ruleId}', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'deleteRule']);
                Route::post('/suggest', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'suggest']);
                Route::get('/{id}', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'show']);
                Route::put('/{id}', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'update']);
                Route::delete('/{id}', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'destroy']);
                Route::get('/{id}/trial-balance', [\Modules\Mk\Http\Controllers\CostCenterController::class, 'trialBalance']);
            });

            // ----------------------------------
            // F4: Late Interest Calculations
            // ----------------------------------
            Route::prefix('interest')->middleware('tier:standard')->group(function () {
                Route::get('/', [\Modules\Mk\Http\Controllers\InterestController::class, 'index']);
                Route::post('/calculate', [\Modules\Mk\Http\Controllers\InterestController::class, 'calculate']);
                Route::get('/summary', [\Modules\Mk\Http\Controllers\InterestController::class, 'summary']);
                Route::get('/{id}', [\Modules\Mk\Http\Controllers\InterestController::class, 'show']);
                Route::post('/{id}/generate-note', [\Modules\Mk\Http\Controllers\InterestController::class, 'generateNote']);
                Route::post('/{id}/waive', [\Modules\Mk\Http\Controllers\InterestController::class, 'waive']);
            });

            // ----------------------------------
            // F5: Collections & Payment Reminders
            // ----------------------------------
            Route::prefix('collections')->middleware('tier:standard')->group(function () {
                Route::get('/overdue', [\Modules\Mk\Http\Controllers\CollectionController::class, 'overdueInvoices']);
                Route::post('/send-reminder', [\Modules\Mk\Http\Controllers\CollectionController::class, 'sendReminder']);
                Route::get('/templates', [\Modules\Mk\Http\Controllers\CollectionController::class, 'templates']);
                Route::post('/templates', [\Modules\Mk\Http\Controllers\CollectionController::class, 'storeTemplate']);
                Route::put('/templates/{id}', [\Modules\Mk\Http\Controllers\CollectionController::class, 'updateTemplate']);
                Route::delete('/templates/{id}', [\Modules\Mk\Http\Controllers\CollectionController::class, 'deleteTemplate']);
                Route::get('/history', [\Modules\Mk\Http\Controllers\CollectionController::class, 'history']);
                Route::get('/effectiveness', [\Modules\Mk\Http\Controllers\CollectionController::class, 'effectiveness']);
            });

            // ----------------------------------
            // F6: Purchase Orders
            // ----------------------------------
            Route::prefix('purchase-orders')->middleware('tier:standard')->group(function () {
                Route::get('/', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'index']);
                Route::post('/', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'store']);
                Route::get('/{id}', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'show']);
                Route::put('/{id}', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'update']);
                Route::post('/{id}/send', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'send']);
                Route::post('/{id}/receive-goods', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'receiveGoods']);
                Route::post('/{id}/convert-to-bill', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'convertToBill']);
                Route::get('/{id}/three-way-match', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'threeWayMatch']);
                Route::post('/{id}/cancel', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'cancel']);
                Route::delete('/{id}', [\Modules\Mk\Http\Controllers\PurchaseOrderController::class, 'destroy']);
            });

            // ----------------------------------
            // F7: Budgeting & Planning
            // ----------------------------------
            Route::prefix('budgets')->middleware('tier:standard')->group(function () {
                Route::get('/', [\Modules\Mk\Http\Controllers\BudgetController::class, 'index']);
                Route::post('/', [\Modules\Mk\Http\Controllers\BudgetController::class, 'store']);
                Route::post('/prefill-actuals', [\Modules\Mk\Http\Controllers\BudgetController::class, 'prefillFromActuals']);
                Route::get('/{id}', [\Modules\Mk\Http\Controllers\BudgetController::class, 'show']);
                Route::put('/{id}', [\Modules\Mk\Http\Controllers\BudgetController::class, 'update']);
                Route::post('/{id}/approve', [\Modules\Mk\Http\Controllers\BudgetController::class, 'approve']);
                Route::post('/{id}/lock', [\Modules\Mk\Http\Controllers\BudgetController::class, 'lock']);
                Route::get('/{id}/vs-actual', [\Modules\Mk\Http\Controllers\BudgetController::class, 'budgetVsActual']);
                Route::delete('/{id}', [\Modules\Mk\Http\Controllers\BudgetController::class, 'destroy']);
            });

            // ----------------------------------
            // F8: Travel Expense Management (Патни Налози)
            // ----------------------------------
            Route::prefix('travel-orders')->middleware('tier:standard')->group(function () {
                Route::get('/', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'index']);
                Route::get('/summary', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'summary']);
                Route::post('/', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'store']);
                Route::get('/{id}', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'show']);
                Route::put('/{id}', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'update']);
                Route::post('/{id}/approve', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'approve']);
                Route::post('/{id}/settle', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'settle']);
                Route::post('/{id}/reject', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'reject']);
                Route::get('/{id}/pdf', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'pdf']);
                Route::delete('/{id}', [\Modules\Mk\Http\Controllers\TravelOrderController::class, 'destroy']);
            });

            // ----------------------------------
            // F9: BI Dashboards
            // ----------------------------------
            Route::prefix('bi-dashboard')->middleware('tier:standard')->group(function () {
                Route::get('/ratios', [\Modules\Mk\Http\Controllers\BiDashboardController::class, 'ratios']);
                Route::get('/trends', [\Modules\Mk\Http\Controllers\BiDashboardController::class, 'trends']);
                Route::get('/summary', [\Modules\Mk\Http\Controllers\BiDashboardController::class, 'summary']);
                Route::post('/refresh', [\Modules\Mk\Http\Controllers\BiDashboardController::class, 'refresh']);
            });

            // F11: Custom Report Builder
            Route::prefix('custom-reports')->middleware('tier:standard')->group(function () {
                Route::get('/', [\Modules\Mk\Http\Controllers\CustomReportController::class, 'index']);
                Route::post('/', [\Modules\Mk\Http\Controllers\CustomReportController::class, 'store']);
                Route::post('/preview', [\Modules\Mk\Http\Controllers\CustomReportController::class, 'preview']);
                Route::get('/{id}', [\Modules\Mk\Http\Controllers\CustomReportController::class, 'show']);
                Route::put('/{id}', [\Modules\Mk\Http\Controllers\CustomReportController::class, 'update']);
                Route::get('/{id}/execute', [\Modules\Mk\Http\Controllers\CustomReportController::class, 'execute']);
                Route::get('/{id}/export-pdf', [\Modules\Mk\Http\Controllers\CustomReportController::class, 'exportPdf']);
                Route::delete('/{id}', [\Modules\Mk\Http\Controllers\CustomReportController::class, 'destroy']);
            });

            // PSD2 Banking Integration (OAuth + Transaction Management)
            // Feature flag: FEATURE_PSD2_BANKING
            // ----------------------------------

            // PSD2 Bank connections available in Business+ tier
            Route::prefix('banking')->middleware('tier:business')->group(function () {
                // Dashboard widget status endpoint
                Route::get('/status', [\App\Http\Controllers\V1\Admin\Banking\BankingController::class, 'status']);

                // Bank account management
                Route::get('/accounts', [\App\Http\Controllers\V1\Admin\Banking\BankingController::class, 'accounts']);
                Route::get('/transactions', [\App\Http\Controllers\V1\Admin\Banking\BankingController::class, 'transactions']);
                Route::post('/sync/{account}', [\App\Http\Controllers\V1\Admin\Banking\BankingController::class, 'syncAccount']);
                Route::patch('/transactions/{transaction}/categorize', [\App\Http\Controllers\V1\Admin\Banking\BankingController::class, 'categorize']);
                Route::post('/transactions/suggest-category', [\App\Http\Controllers\V1\Admin\Banking\BankingController::class, 'suggestCategory']);
                Route::post('/accounts', [\App\Http\Controllers\V1\Admin\Banking\BankingController::class, 'storeManualAccount']);
                Route::delete('/accounts/{account}', [\App\Http\Controllers\V1\Admin\Banking\BankingController::class, 'disconnect']);
                Route::post('/transactions/manual', [\App\Http\Controllers\V1\Admin\Banking\BankingController::class, 'storeManualTransaction']);

                // CSV Import
                Route::prefix('import')->group(function () {
                    Route::post('/preview', [\App\Http\Controllers\V1\Admin\Banking\BankImportController::class, 'preview']);
                    Route::post('/confirm', [\App\Http\Controllers\V1\Admin\Banking\BankImportController::class, 'confirm']);
                    Route::get('/banks', [\App\Http\Controllers\V1\Admin\Banking\BankImportController::class, 'supportedBanks']);
                    // P0-03: Import Logging & Analytics
                    Route::get('/history', [\App\Http\Controllers\V1\Admin\Banking\BankImportController::class, 'importHistory']);
                    Route::get('/stats', [\App\Http\Controllers\V1\Admin\Banking\BankImportController::class, 'importStats']);
                });

                // Invoice Reconciliation
                Route::prefix('reconciliation')->group(function () {
                    Route::get('/', [\Modules\Mk\Http\Controllers\ReconciliationController::class, 'index']);
                    Route::post('/auto-match', [\Modules\Mk\Http\Controllers\ReconciliationController::class, 'autoMatch']);
                    Route::post('/manual-match', [\Modules\Mk\Http\Controllers\ReconciliationController::class, 'manualMatch']);
                    Route::get('/stats', [\Modules\Mk\Http\Controllers\ReconciliationController::class, 'stats']);
                    Route::get('/unpaid-invoices', [\Modules\Mk\Http\Controllers\ReconciliationController::class, 'getUnpaidInvoices']);
                    Route::post('/confirm-match', [\Modules\Mk\Http\Controllers\ReconciliationController::class, 'confirmMatch']);
                    Route::get('/analytics', [\App\Http\Controllers\V1\Admin\Banking\ReconciliationAnalyticsController::class, 'index']);

                    // P0-14: Split payment endpoints
                    Route::post('/{id}/split', [\Modules\Mk\Http\Controllers\ReconciliationController::class, 'splitPayment']);
                    Route::get('/{id}/splits', [\Modules\Mk\Http\Controllers\ReconciliationController::class, 'getSplits']);
                });

                // Matching Rules (P0-09)
                Route::prefix('matching-rules')->group(function () {
                    Route::get('/', [\App\Http\Controllers\V1\Admin\Banking\MatchingRulesController::class, 'index']);
                    Route::post('/', [\App\Http\Controllers\V1\Admin\Banking\MatchingRulesController::class, 'store']);
                    Route::put('/{id}', [\App\Http\Controllers\V1\Admin\Banking\MatchingRulesController::class, 'update']);
                    Route::delete('/{id}', [\App\Http\Controllers\V1\Admin\Banking\MatchingRulesController::class, 'destroy']);
                    Route::post('/{id}/test', [\App\Http\Controllers\V1\Admin\Banking\MatchingRulesController::class, 'test']);
                });

                // OAuth routes (these need to be accessible without full auth for callback)
                Route::get('/oauth/start', [\App\Http\Controllers\V1\Admin\Banking\BankingOAuthController::class, 'start']);
            });

            // Bank Connections (Phase 3)
            // ----------------------------------

            // Bank connections available in Business+ tier
            Route::prefix('bank')->middleware('tier:business')->group(function () {
                // OAuth flow
                Route::post('/oauth/start', [\App\Http\Controllers\V1\Admin\Banking\BankConnectionController::class, 'start']);

                // Bank connections management
                Route::apiResource('connections', \App\Http\Controllers\V1\Admin\Banking\BankConnectionController::class);

                // Bank accounts & transactions
                Route::get('/accounts', [\App\Http\Controllers\V1\Admin\Banking\BankConnectionController::class, 'accounts']);
                Route::get('/accounts/{accountId}/transactions', [\App\Http\Controllers\V1\Admin\Banking\BankConnectionController::class, 'transactions']);
            });

            // Reconciliation (Phase 3)
            // ----------------------------------

            // Auto-reconciliation available in Business+ tier
            Route::prefix('reconciliation')->middleware('tier:business')->group(function () {
                Route::get('/auto-matched', [\App\Http\Controllers\ReconciliationController::class, 'autoMatched']);
                Route::get('/suggested', [\App\Http\Controllers\ReconciliationController::class, 'suggested']);
                Route::get('/manual', [\App\Http\Controllers\ReconciliationController::class, 'manual']);
                Route::post('/approve', [\App\Http\Controllers\ReconciliationController::class, 'approve']);
                Route::post('/reject', [\App\Http\Controllers\ReconciliationController::class, 'reject']);
            });

            // Approvals (Phase 4)
            // ----------------------------------

            Route::prefix('approvals')->group(function () {
                Route::get('/', [\App\Http\Controllers\ApprovalRequestController::class, 'index']);
                Route::get('/{id}', [\App\Http\Controllers\ApprovalRequestController::class, 'show']);
                Route::post('/{id}/approve', [\App\Http\Controllers\ApprovalRequestController::class, 'approve']);
                Route::post('/{id}/reject', [\App\Http\Controllers\ApprovalRequestController::class, 'reject']);
                Route::get('/document/{type}/{id}', [\App\Http\Controllers\ApprovalRequestController::class, 'forDocument']);
            });

            // AI Insights Integration
            // Feature flag: FEATURE_MCP_AI_TOOLS
            // ----------------------------------

            // AI available to all tiers with usage limits (free = 3/month preview)
            Route::prefix('ai')->middleware(['feature:mcp_ai_tools'])->group(function () {
                Route::get('/insights', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'index']);
                Route::post('/insights/generate', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'generate']);
                Route::post('/insights/refresh', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'refresh']);
                Route::post('/insights/chat', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'chat']);
                Route::delete('/insights/chat/{conversationId}', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'clearConversation']);
                Route::post('/insights/chat-stream', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'chatStream']);
                Route::get('/conversations', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'conversationHistory']);
                Route::get('/risks', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'risks']);
                Route::get('/settings', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'getSettings']);
                Route::post('/settings', [\App\Http\Controllers\V1\Admin\AiInsightsController::class, 'updateSettings']);

                // Document analysis endpoints (require pdf_analysis feature flag)
                Route::post('/analyze-document', [\App\Http\Controllers\V1\Admin\AiDocumentController::class, 'analyzeDocument']);
                Route::post('/analyze-receipt', [\App\Http\Controllers\V1\Admin\AiDocumentController::class, 'analyzeReceipt']);
                Route::post('/extract-invoice', [\App\Http\Controllers\V1\Admin\AiDocumentController::class, 'extractInvoice']);
                Route::get('/monthly-trends', [\App\Http\Controllers\V1\Admin\AiDocumentController::class, 'monthlyTrends']);

                // Debug endpoint to see raw data
                Route::get('/debug/data', function (\Illuminate\Http\Request $request) {
                    $company = \App\Models\Company::find($request->header('company'));
                    if (! $company) {
                        return response()->json(['error' => 'Company not found'], 404);
                    }

                    $dataProvider = app(\App\Services\McpDataProvider::class);

                    // Get all invoices with details
                    $allInvoices = \App\Models\Invoice::where('company_id', $company->id)
                        ->get()
                        ->map(function ($inv) {
                            return [
                                'id' => $inv->id,
                                'number' => $inv->invoice_number,
                                'status' => $inv->status,
                                'paid_status' => $inv->paid_status ?? null,
                                'total' => $inv->total,
                                'due_amount' => $inv->due_amount,
                                'date' => $inv->invoice_date,
                            ];
                        });

                    return response()->json([
                        'company' => [
                            'id' => $company->id,
                            'name' => $company->name,
                        ],
                        'raw_data' => [
                            'all_invoices' => $allInvoices,
                            'company_stats' => $dataProvider->getCompanyStats($company),
                            'trial_balance' => $dataProvider->getTrialBalance($company),
                        ],
                    ]);
                });
            });

            // Stock Management Module (Facturino)
            // Stock module is always enabled - no feature flag needed
            // ----------------------------------

            Route::prefix('stock')->group(function () {
                // Warehouses
                Route::apiResource('warehouses', \App\Http\Controllers\V1\Admin\Stock\WarehouseController::class);
                Route::post('/warehouses/{id}/set-default', [\App\Http\Controllers\V1\Admin\Stock\WarehouseController::class, 'setDefault']);

                // Stock Reports
                Route::get('/inventory', [\App\Http\Controllers\V1\Admin\Stock\StockController::class, 'inventory']);
                Route::get('/item-card/{item}', [\App\Http\Controllers\V1\Admin\Stock\StockController::class, 'itemCard']);
                Route::get('/warehouse/{warehouse}/inventory', [\App\Http\Controllers\V1\Admin\Stock\StockController::class, 'warehouseInventory']);
                Route::get('/valuation-report', [\App\Http\Controllers\V1\Admin\Stock\StockController::class, 'valuationReport']);
                Route::get('/low-stock', [\App\Http\Controllers\V1\Admin\Stock\StockController::class, 'lowStock']);

                // Stock Operations (Adjustments, Transfers, Initial Stock)
                Route::get('/adjustments', [\App\Http\Controllers\V1\Admin\Stock\StockAdjustmentController::class, 'index']);
                Route::post('/adjustments', [\App\Http\Controllers\V1\Admin\Stock\StockAdjustmentController::class, 'store']);
                Route::get('/adjustments/{id}', [\App\Http\Controllers\V1\Admin\Stock\StockAdjustmentController::class, 'show']);
                Route::delete('/adjustments/{id}', [\App\Http\Controllers\V1\Admin\Stock\StockAdjustmentController::class, 'destroy']);

                Route::get('/transfers', [\App\Http\Controllers\V1\Admin\Stock\StockAdjustmentController::class, 'transfers']);
                Route::post('/transfers', [\App\Http\Controllers\V1\Admin\Stock\StockAdjustmentController::class, 'transfer']);

                Route::post('/initial-stock', [\App\Http\Controllers\V1\Admin\Stock\StockAdjustmentController::class, 'initialStock']);

                // Item stock lookup (for UI validation)
                Route::get('/items/{item}/stock', [\App\Http\Controllers\V1\Admin\Stock\StockAdjustmentController::class, 'itemStock']);

                // Inventory Documents (приемница/издатница/преносница)
                Route::get('/documents', [\App\Http\Controllers\V1\Admin\Stock\InventoryDocumentController::class, 'index']);
                Route::post('/documents', [\App\Http\Controllers\V1\Admin\Stock\InventoryDocumentController::class, 'store']);
                Route::get('/documents/{id}', [\App\Http\Controllers\V1\Admin\Stock\InventoryDocumentController::class, 'show']);
                Route::put('/documents/{id}', [\App\Http\Controllers\V1\Admin\Stock\InventoryDocumentController::class, 'update']);
                Route::delete('/documents/{id}', [\App\Http\Controllers\V1\Admin\Stock\InventoryDocumentController::class, 'destroy']);
                Route::post('/documents/{id}/approve', [\App\Http\Controllers\V1\Admin\Stock\InventoryDocumentController::class, 'approve']);
                Route::post('/documents/{id}/void', [\App\Http\Controllers\V1\Admin\Stock\InventoryDocumentController::class, 'void']);

                // Dashboard Summary
                Route::get('/dashboard-summary', [\App\Http\Controllers\V1\Admin\Stock\StockController::class, 'dashboardSummary']);
            });
        });


        // Self Update
        // ----------------------------------

        Route::get('/check/update', CheckVersionController::class);

        Route::post('/update/download', DownloadUpdateController::class);

        Route::post('/update/unzip', UnzipUpdateController::class);

        Route::post('/update/copy', CopyFilesController::class);

        Route::post('/update/delete', DeleteFilesController::class);

        Route::post('/update/migrate', MigrateUpdateController::class);

        Route::post('/update/finish', FinishUpdateController::class);

        // Companies
        // -------------------------------------------------

        Route::post('companies', [CompaniesController::class, 'store']);

        Route::post('/transfer/ownership/{user}', [CompaniesController::class, 'transferOwnership']);

        Route::post('companies/delete', [CompaniesController::class, 'destroy']);

        Route::get('companies', [CompaniesController::class, 'getUserCompanies']);

        // Users
        // ----------------------------------

        Route::post('/users/delete', [UsersController::class, 'delete']);

        // FG-01-31: Apply user limit check to user creation
        // Note: apiResource creates routes for: index, show, store, update, destroy
        // We only want to gate 'store' (creation), so we'll split it
        Route::get('/users', [UsersController::class, 'index']);
        Route::get('/users/{user}', [UsersController::class, 'show']);
        Route::post('/users', [UsersController::class, 'store'])->middleware('user-limit'); // FG-01-31
        Route::put('/users/{user}', [UsersController::class, 'update']);
        Route::patch('/users/{user}', [UsersController::class, 'update']);
        Route::delete('/users/{user}', [UsersController::class, 'destroy']);

        // Modules
        // ----------------------------------

        Route::prefix('/modules')->group(function () {
            Route::get('/', ModulesController::class);

            Route::get('/check', ApiTokenController::class);

            Route::get('/{module}', ModuleController::class);

            Route::post('/{module}/enable', EnableModuleController::class);

            Route::post('/{module}/disable', DisableModuleController::class);

            Route::post('/download', DownloadModuleController::class);

            Route::post('/upload', UploadModuleController::class);

            Route::post('/unzip', UnzipModuleController::class);

            Route::post('/copy', CopyModuleController::class);

            Route::post('/complete', CompleteModuleInstallationController::class);
        });

        // Accountant Console Routes
        // ----------------------------------
        Route::prefix('/console')->middleware(['partner-scope'])->group(function () {
            Route::get('/', [\Modules\Mk\Http\Controllers\AccountantConsoleController::class, 'index']);
            Route::get('/companies', [\Modules\Mk\Http\Controllers\AccountantConsoleController::class, 'index']); // Updated to use categorized index() method
            Route::post('/switch', [\Modules\Mk\Http\Controllers\AccountantConsoleController::class, 'switchCompany']);
            Route::get('/commissions', [\Modules\Mk\Http\Controllers\AccountantConsoleController::class, 'commissions']); // AC-10
        });

        // Partner Management Routes (AC-08)
        // Super Admin only - manage partners, assign companies, set permissions
        // ----------------------------------
        Route::prefix('/partners')->middleware(['super-admin'])->group(function () {
            Route::get('/stats', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'stats']);
            Route::get('/permissions', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'permissions']); // AC-13
            Route::get('/', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'store']);
            Route::get('/{partner}', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'show']);
            Route::put('/{partner}', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'update']);
            Route::delete('/{partner}', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'destroy']);

            // AC-09: Company Assignment
            Route::get('/{partner}/available-companies', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'availableCompanies']);
            Route::post('/{partner}/companies', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'assignCompany']);
            Route::put('/{partner}/companies/{company}', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'updateCompanyAssignment']);
            Route::delete('/{partner}/companies/{company}', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'unassignCompany']);

            // AC-16: Reassignment Helper Routes
            Route::get('/{partner}/upline', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'getPartnerUpline']);
        });

        // ----------------------------------
        // Payout Management Routes
        // Super Admin only - view, approve, export partner payouts
        // ----------------------------------
        Route::prefix('/payouts')->middleware(['super-admin'])->group(function () {
            Route::get('/stats', [\App\Http\Controllers\V1\Admin\Payout\PayoutManagementController::class, 'stats']);
            Route::get('/export', [\App\Http\Controllers\V1\Admin\Payout\PayoutManagementController::class, 'export']);
            Route::get('/', [\App\Http\Controllers\V1\Admin\Payout\PayoutManagementController::class, 'index']);
            Route::get('/{payout}', [\App\Http\Controllers\V1\Admin\Payout\PayoutManagementController::class, 'show']);
            Route::post('/{payout}/complete', [\App\Http\Controllers\V1\Admin\Payout\PayoutManagementController::class, 'markCompleted']);
            Route::post('/{payout}/fail', [\App\Http\Controllers\V1\Admin\Payout\PayoutManagementController::class, 'markFailed']);
            Route::post('/{payout}/cancel', [\App\Http\Controllers\V1\Admin\Payout\PayoutManagementController::class, 'cancel']);
        });

        // AC-16: Company Helper Routes (outside partners prefix)
        Route::get('/companies/{company}/current-partner', [\App\Http\Controllers\V1\Admin\Partner\PartnerManagementController::class, 'getCompanyCurrentPartner'])->middleware('super-admin');

        // Partner Invitation Routes (AC-11, AC-12, AC-14, AC-15)
        // ----------------------------------
        Route::post('/invitations/company-to-partner', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'companyInvitesPartner']);
        Route::get('/invitations/pending-for-partner', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'getPendingForPartner']);
        Route::get('/invitations/pending', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'getPending']);
        Route::get('/invitations/pending-company', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'getPendingCompany']);
        Route::post('/invitations/{linkId}/respond', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'respondToInvitation']);
        Route::delete('/invitations/companies/{companyId}/unlink', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'unlinkFromCompany']);
        Route::post('/invitations/partner-to-company', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'partnerInvitesCompany']);
        Route::post('/invitations/company-to-company', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'companyInvitesCompany']);
        Route::post('/invitations/partner-to-partner', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'partnerInvitesPartner']);
        Route::post('/invitations/send-email', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'sendEmailInvite']);
        Route::post('/invitations/send-partner-email', [\App\Http\Controllers\V1\Admin\PartnerInvitationController::class, 'sendPartnerEmailInvite']);

        // Entity Reassignment Routes (AC-16)
        // ----------------------------------
        Route::post('/reassignments/company-partner', [\App\Http\Controllers\V1\Admin\EntityReassignmentController::class, 'reassignCompanyPartner'])->middleware('super-admin');
        Route::post('/reassignments/partner-upline', [\App\Http\Controllers\V1\Admin\EntityReassignmentController::class, 'reassignPartnerUpline'])->middleware('super-admin');
        Route::get('/reassignments/log', [\App\Http\Controllers\V1\Admin\EntityReassignmentController::class, 'getReassignmentLog'])->middleware('super-admin');

        // Referral Network Graph Routes (AC-17)
        // ----------------------------------
        Route::get('/referral-network/graph', [\App\Http\Controllers\V1\Admin\ReferralNetworkController::class, 'getNetworkGraph'])->middleware('super-admin');
    });
});

// Public image serving route for scanned supplier invoices (outside auth:sanctum to allow browser img tags with session cookies)
// This route is intentionally outside the auth middleware to support standard browser <img> tags
// Security: Path validation in controller ensures only scanned-receipts/ invoice images are served
Route::prefix('/v1')->group(function () {
    Route::get('/receipts/image/{path}', [\App\Http\Controllers\V1\Admin\AccountsPayable\ReceiptScannerController::class, 'getImage'])
        ->where('path', '.*')
        ->middleware(['web']); // Use web middleware for session cookie support
});

// Continue with v1 routes
Route::prefix('/v1')->group(function () {
    Route::prefix('/{company:slug}/customer')->group(function () {

        // Authentication & Password Reset
        // ----------------------------------

        Route::prefix('auth')->group(function () {

            // Send reset password mail
            Route::post('password/email', [AuthForgotPasswordController::class, 'sendResetLinkEmail']);

            // handle reset password form process
            Route::post('reset/password', [AuthResetPasswordController::class, 'reset'])->name('customer.password.reset');
        });

        // Invoices, Estimates, Payments and Expenses endpoints
        // -------------------------------------------------------

        Route::middleware(['auth:customer', 'customer-portal'])->group(function () {
            Route::get('/bootstrap', CustomerBootstrapController::class);

            Route::get('/dashboard', CustomerDashboardController::class);

            Route::get('invoices', [CustomerInvoicesController::class, 'index']);

            Route::get('invoices/{id}', [CustomerInvoicesController::class, 'show']);

            Route::post('/estimate/{estimate}/status', CustomerAcceptEstimateController::class);

            Route::get('estimates', [CustomerEstimatesController::class, 'index']);

            Route::get('estimates/{id}', [CustomerEstimatesController::class, 'show']);

            Route::get('payments', [CustomerPaymentsController::class, 'index']);

            Route::get('payments/{id}', [CustomerPaymentsController::class, 'show']);

            Route::get('/payment-method', PaymentMethodController::class);

            Route::get('expenses', [CustomerExpensesController::class, 'index']);

            Route::get('expenses/{id}', [CustomerExpensesController::class, 'show']);

            Route::post('/profile', [CustomerProfileController::class, 'updateProfile']);

            Route::get('/me', [CustomerProfileController::class, 'getUser']);

            Route::get('/countries', CountriesController::class);
        });
    });

    // CSV Template downloads (public within v1)
    Route::get('migration/templates/{type}', [\App\Http\Controllers\V1\Admin\Imports\ImportController::class, 'downloadTemplate']);
});

Route::get('/cron', CronJobController::class)->middleware('cron-job');

// Webhook Routes (Phase 4 - no auth required)
// ----------------------------------
Route::prefix('webhooks')->group(function () {
    Route::post('paddle', [\App\Http\Controllers\Webhooks\WebhookController::class, 'paddle']);
    Route::post('cpay', [\App\Http\Controllers\Webhooks\WebhookController::class, 'cpay']);
    Route::post('bank/nlb', [\App\Http\Controllers\Webhooks\WebhookController::class, 'nlbBank']);
    Route::post('bank/stopanska', [\App\Http\Controllers\Webhooks\WebhookController::class, 'stopanskaBank']);
    Route::post('stripe', [\App\Http\Controllers\Webhooks\WebhookController::class, 'stripe']);

    // Subscription webhooks
    Route::post('paddle/subscription', [\Modules\Mk\Billing\Controllers\PaddleWebhookController::class, 'handleWebhook']);
    Route::post('cpay/subscription', [\Modules\Mk\Billing\Controllers\CpayWebhookController::class, 'handleSubscriptionCallback']);
});

// Company Subscription Routes (B-31 series)
// ----------------------------------
Route::middleware(['auth:sanctum'])->prefix('v1/companies/{company}/subscription')->group(function () {
    Route::get('/', [\Modules\Mk\Billing\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/checkout', [\Modules\Mk\Billing\Controllers\SubscriptionController::class, 'checkout'])->name('subscription.checkout')->middleware('throttle:strict');
    Route::get('/success', [\Modules\Mk\Billing\Controllers\SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/manage', [\Modules\Mk\Billing\Controllers\SubscriptionController::class, 'manage'])->name('subscription.manage');
    Route::post('/swap', [\Modules\Mk\Billing\Controllers\SubscriptionController::class, 'swap'])->name('subscription.swap')->middleware('throttle:strict');
    Route::post('/cancel', [\Modules\Mk\Billing\Controllers\SubscriptionController::class, 'cancel'])->name('subscription.cancel')->middleware('throttle:strict');
    Route::post('/resume', [\Modules\Mk\Billing\Controllers\SubscriptionController::class, 'resume'])->name('subscription.resume')->middleware('throttle:strict');
});

// Partner Plus Subscription Routes (B-31-04)
// ----------------------------------
Route::middleware(['auth:sanctum'])->prefix('v1/partner/subscription')->group(function () {
    Route::get('/', [\Modules\Mk\Partner\Controllers\PartnerSubscriptionController::class, 'index'])->name('partner.subscription.index');
    Route::post('/checkout', [\Modules\Mk\Partner\Controllers\PartnerSubscriptionController::class, 'checkout'])->name('partner.subscription.checkout')->middleware('throttle:strict');
    Route::get('/success', [\Modules\Mk\Partner\Controllers\PartnerSubscriptionController::class, 'success'])->name('partner.subscription.success');
    Route::get('/manage', [\Modules\Mk\Partner\Controllers\PartnerSubscriptionController::class, 'manage'])->name('partner.subscription.manage');
    Route::post('/cancel', [\Modules\Mk\Partner\Controllers\PartnerSubscriptionController::class, 'cancel'])->name('partner.subscription.cancel')->middleware('throttle:strict');
    Route::post('/resume', [\Modules\Mk\Partner\Controllers\PartnerSubscriptionController::class, 'resume'])->name('partner.subscription.resume')->middleware('throttle:strict');
});

// Billing Route Aliases (for frontend convenience)
// Automatically uses current company from user session
// ----------------------------------
Route::middleware(['auth:sanctum'])->prefix('billing')->group(function () {
    Route::get('/subscription', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $companyId = $user->companies()->first()?->id ?? $user->company_id ?? 1;

        return app(\Modules\Mk\Billing\Controllers\SubscriptionController::class)->index($companyId);
    });

    Route::get('/invoices', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $companyId = $user->companies()->first()?->id ?? $user->company_id ?? 1;

        // Return billing invoices (from Paddle subscription)
        $company = \App\Models\Company::find($companyId);
        if (! $company || ! $company->subscription) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => []]);  // TODO: Implement Paddle invoice fetching
    });
});

// Partner Portal Routes
// ----------------------------------
Route::middleware(['auth:sanctum', 'partner-scope', 'throttle:api'])->prefix('v1/partner')->group(function () {
    Route::get('/dashboard', [\Modules\Mk\Partner\Controllers\PartnerDashboardController::class, 'index']);
    Route::get('/commissions', [\Modules\Mk\Partner\Controllers\PartnerDashboardController::class, 'commissions']);
    Route::get('/referrals', [\Modules\Mk\Partner\Controllers\PartnerReferralsController::class, 'index']);
    Route::post('/referrals', [\Modules\Mk\Partner\Controllers\PartnerReferralsController::class, 'store'])->middleware('throttle:strict');
    Route::get('/clients', [\Modules\Mk\Partner\Controllers\PartnerClientsController::class, 'index']);
    Route::get('/clients/{companyId}', [\Modules\Mk\Partner\Controllers\PartnerClientsController::class, 'show']);

    // Portfolio management
    Route::prefix('/portfolio')->group(function () {
        Route::post('/activate', [\Modules\Mk\Partner\Controllers\PortfolioController::class, 'activate'])->middleware('throttle:strict');
        Route::get('/stats', [\Modules\Mk\Partner\Controllers\PortfolioController::class, 'stats']);
    });
    Route::prefix('/portfolio-companies')->group(function () {
        Route::get('/', [\Modules\Mk\Partner\Controllers\PortfolioCompanyController::class, 'index']);
        Route::post('/', [\Modules\Mk\Partner\Controllers\PortfolioCompanyController::class, 'store'])->middleware('throttle:strict');
        Route::get('/template', [\Modules\Mk\Partner\Controllers\PortfolioCompanyController::class, 'template']);
        Route::post('/import-preview', [\Modules\Mk\Partner\Controllers\PortfolioCompanyController::class, 'importPreview'])->middleware('throttle:strict');
        Route::post('/import-confirm', [\Modules\Mk\Partner\Controllers\PortfolioCompanyController::class, 'importConfirm'])->middleware('throttle:strict');
        Route::get('/{companyId}', [\Modules\Mk\Partner\Controllers\PortfolioCompanyController::class, 'show']);
        Route::delete('/{companyId}', [\Modules\Mk\Partner\Controllers\PortfolioCompanyController::class, 'destroy'])->middleware('throttle:strict');
    });

    Route::get('/payouts', [\Modules\Mk\Partner\Controllers\PartnerPayoutsController::class, 'index']);
    Route::get('/bank-details', [\Modules\Mk\Partner\Controllers\PartnerPayoutsController::class, 'getBankDetails']);
    Route::post('/bank-details', [\Modules\Mk\Partner\Controllers\PartnerPayoutsController::class, 'updateBankDetails'])->middleware('throttle:strict');
    Route::get('/payouts/{payout}/receipt', [\Modules\Mk\Partner\Controllers\PartnerPayoutsController::class, 'downloadReceipt']);

    // Stripe Connect for partner payouts (Cross-border to Macedonia)
    Route::prefix('/stripe-connect')->group(function () {
        // Account management
        Route::post('/account', [\Modules\Mk\Partner\Controllers\StripeConnectController::class, 'createConnectedAccount'])->middleware('throttle:strict');
        Route::get('/status', [\Modules\Mk\Partner\Controllers\StripeConnectController::class, 'getAccountStatus']);
        Route::delete('/account', [\Modules\Mk\Partner\Controllers\StripeConnectController::class, 'deleteConnectedAccount'])->middleware('throttle:strict');

        // Onboarding - generates Stripe-hosted Account Link
        Route::post('/account-link', [\Modules\Mk\Partner\Controllers\StripeConnectController::class, 'createAccountLink'])->middleware('throttle:strict');

        // Express Dashboard - generates login link for partner
        Route::post('/dashboard-link', [\Modules\Mk\Partner\Controllers\StripeConnectController::class, 'createDashboardLink'])->middleware('throttle:strict');

        // Bank account management
        Route::put('/bank-account', [\Modules\Mk\Partner\Controllers\StripeConnectController::class, 'updateBankAccount'])->middleware('throttle:strict');
    });

    // Partner Accounting Routes (PAB-05)
    // Chart of Accounts
    Route::get('/companies/{company}/accounts', [PartnerAccountController::class, 'index']);
    Route::get('/companies/{company}/accounts/tree', [PartnerAccountController::class, 'tree']);
    Route::get('/companies/{company}/accounts/export', [PartnerAccountController::class, 'export']);
    Route::get('/companies/{company}/accounts/{account}', [PartnerAccountController::class, 'show']);
    Route::post('/companies/{company}/accounts', [PartnerAccountController::class, 'store']);
    Route::put('/companies/{company}/accounts/{account}', [PartnerAccountController::class, 'update']);
    Route::delete('/companies/{company}/accounts/{account}', [PartnerAccountController::class, 'destroy']);
    Route::post('/companies/{company}/accounts/import', [PartnerAccountController::class, 'import']);

    // Account Mappings
    Route::get('/companies/{company}/mappings', [PartnerAccountMappingController::class, 'index']);
    Route::get('/companies/{company}/mappings/{mapping}', [PartnerAccountMappingController::class, 'show']);
    Route::post('/companies/{company}/mappings', [PartnerAccountMappingController::class, 'store']);
    Route::put('/companies/{company}/mappings/{mapping}', [PartnerAccountMappingController::class, 'update']);
    Route::delete('/companies/{company}/mappings/{mapping}', [PartnerAccountMappingController::class, 'destroy']);
    Route::post('/companies/{company}/mappings/suggest', [PartnerAccountMappingController::class, 'suggest']);

    // Journal Export
    Route::get('/companies/{company}/journal-entries', [PartnerJournalExportController::class, 'entries']);
    Route::get('/companies/{company}/journal-entries/{entry}', [PartnerJournalExportController::class, 'show']);
    Route::put('/companies/{company}/journal-entries/{entry}', [PartnerJournalExportController::class, 'confirm']);
    Route::post('/companies/{company}/journal/export', [PartnerJournalExportController::class, 'export']);

    // Journal Learning System (AI Account Suggestions)
    Route::post('/companies/{company}/journal/learn', [PartnerJournalExportController::class, 'learn']);
    Route::post('/companies/{company}/journal/accept-all', [PartnerJournalExportController::class, 'acceptAll']);

    // Journal Import (Migration from Pantheon/Biznisoft)
    Route::post('/companies/{company}/journal/import/preview', [PartnerJournalImportController::class, 'preview']);
    Route::post('/companies/{company}/journal/import', [PartnerJournalImportController::class, 'import']);
    Route::get('/companies/{company}/journal/import/formats', [PartnerJournalImportController::class, 'formats']);

    // Batch AI Account Suggestions (QuickBooks-style)
    Route::post('/companies/{company}/journal/suggest', [PartnerAccountMappingController::class, 'batchSuggest']);

    // Partner Stock/Warehouse Management for Client Companies
    Route::prefix('/companies/{company}/stock')->group(function () {
        Route::get('/warehouses', [\App\Http\Controllers\V1\Admin\Stock\WarehouseController::class, 'index']);
        Route::post('/warehouses', [\App\Http\Controllers\V1\Admin\Stock\WarehouseController::class, 'store']);
        Route::get('/warehouses/{warehouse}', [\App\Http\Controllers\V1\Admin\Stock\WarehouseController::class, 'show']);
        Route::put('/warehouses/{warehouse}', [\App\Http\Controllers\V1\Admin\Stock\WarehouseController::class, 'update']);
        Route::delete('/warehouses/{warehouse}', [\App\Http\Controllers\V1\Admin\Stock\WarehouseController::class, 'destroy']);
        Route::post('/warehouses/{warehouse}/set-default', [\App\Http\Controllers\V1\Admin\Stock\WarehouseController::class, 'setDefault']);
    });

    // Partner Reports for Client Companies
    Route::prefix('/companies/{company}/reports')->group(function () {
        Route::get('/profit-loss', [\App\Http\Controllers\V1\Admin\Report\ProfitLossReportController::class, 'index']);
        Route::get('/expenses', [\App\Http\Controllers\V1\Admin\Report\ExpensesReportController::class, 'index']);
        Route::get('/tax-summary', [\App\Http\Controllers\V1\Admin\Report\TaxSummaryReportController::class, 'index']);
        Route::get('/customers', [\App\Http\Controllers\V1\Admin\Report\CustomerSalesReportController::class, 'index']);
        Route::get('/items', [\App\Http\Controllers\V1\Admin\Report\ItemSalesReportController::class, 'index']);
    });

    // Partner Stock Reports for Client Companies
    Route::prefix('/companies/{company}/stock-reports')->group(function () {
        Route::get('/inventory', [\App\Http\Controllers\V1\Admin\Stock\StockReportsController::class, 'inventoryList']);
        Route::get('/valuation', [\App\Http\Controllers\V1\Admin\Stock\StockReportsController::class, 'inventoryValuation']);
        Route::get('/item-card/{item}', [\App\Http\Controllers\V1\Admin\Stock\StockReportsController::class, 'itemCard']);
    });

    // Partner Period Lock Management for Client Companies
    Route::get('/companies/{company}/period-locks', [\App\Http\Controllers\V1\Partner\PartnerPeriodLockController::class, 'index']);
    Route::post('/companies/{company}/period-locks', [\App\Http\Controllers\V1\Partner\PartnerPeriodLockController::class, 'store']);
    Route::delete('/companies/{company}/period-locks/{periodLock}', [\App\Http\Controllers\V1\Partner\PartnerPeriodLockController::class, 'destroy']);

    // Partner Daily Closing Management for Client Companies
    Route::get('/companies/{company}/daily-closings', [\App\Http\Controllers\V1\Partner\PartnerDailyClosingController::class, 'index']);
    Route::post('/companies/{company}/daily-closings', [\App\Http\Controllers\V1\Partner\PartnerDailyClosingController::class, 'store']);
    Route::delete('/companies/{company}/daily-closings/{dailyClosing}', [\App\Http\Controllers\V1\Partner\PartnerDailyClosingController::class, 'destroy']);

    // Partner IFRS Accounting Reports for Client Companies
    Route::prefix('/companies/{company}/accounting')->group(function () {
        Route::get('/ifrs-status', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'ifrsStatus']);
        Route::post('/enable-ifrs', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'enableIfrs']);
        Route::get('/general-ledger', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'generalLedger']);
        Route::get('/sub-ledger', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'subLedger']);
        Route::get('/journal-entries', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'journalEntries']);
        Route::get('/trial-balance', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'trialBalance']);
        Route::get('/trial-balance/export', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'trialBalanceExport']);
        Route::get('/balance-sheet', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'balanceSheet']);
        Route::get('/income-statement', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'incomeStatement']);
        Route::get('/general-ledger/export', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'generalLedgerExport']);
        Route::get('/cash-book/export', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'cashBookExport']);
        Route::get('/cash-flow', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'cashFlow']);
        Route::get('/cash-flow/export', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'cashFlowExport']);
        Route::get('/equity-changes', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'equityChanges']);
        Route::get('/equity-changes/export', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'equityChangesExport']);
        Route::get('/vat-books', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'vatBooks']);
        Route::get('/vat-books/export', [\App\Http\Controllers\V1\Partner\PartnerAccountingReportsController::class, 'vatBooksExport']);

        // F1: Compensations (Partner)
        Route::prefix('compensations')->group(function () {
            Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerCompensationController::class, 'index']);
            Route::get('/opportunities', [\App\Http\Controllers\V1\Partner\PartnerCompensationController::class, 'opportunities']);
            Route::get('/eligible-documents', [\App\Http\Controllers\V1\Partner\PartnerCompensationController::class, 'eligibleDocuments']);
            Route::post('/', [\App\Http\Controllers\V1\Partner\PartnerCompensationController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerCompensationController::class, 'show']);
            Route::post('/{id}/confirm', [\App\Http\Controllers\V1\Partner\PartnerCompensationController::class, 'confirm']);
            Route::post('/{id}/cancel', [\App\Http\Controllers\V1\Partner\PartnerCompensationController::class, 'cancel']);
            Route::get('/{id}/pdf', [\App\Http\Controllers\V1\Partner\PartnerCompensationController::class, 'pdf']);
        });

        // F2: Payment Orders (Partner)
        Route::prefix('payment-orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerPaymentOrderController::class, 'index']);
            Route::get('/payable-bills', [\App\Http\Controllers\V1\Partner\PartnerPaymentOrderController::class, 'payableBills']);
            Route::get('/overdue-summary', [\App\Http\Controllers\V1\Partner\PartnerPaymentOrderController::class, 'overdueSummary']);
            Route::post('/', [\App\Http\Controllers\V1\Partner\PartnerPaymentOrderController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerPaymentOrderController::class, 'show']);
            Route::post('/{id}/approve', [\App\Http\Controllers\V1\Partner\PartnerPaymentOrderController::class, 'approve']);
            Route::get('/{id}/export', [\App\Http\Controllers\V1\Partner\PartnerPaymentOrderController::class, 'export']);
            Route::post('/{id}/confirm', [\App\Http\Controllers\V1\Partner\PartnerPaymentOrderController::class, 'confirm']);
            Route::post('/{id}/cancel', [\App\Http\Controllers\V1\Partner\PartnerPaymentOrderController::class, 'cancel']);
        });

        // F3: Cost Centers (Partner)
        Route::prefix('cost-centers')->group(function () {
            Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'store']);
            Route::get('/summary', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'summary']);
            Route::get('/rules', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'rules']);
            Route::post('/rules', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'storeRule']);
            Route::put('/rules/{ruleId}', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'updateRule']);
            Route::delete('/rules/{ruleId}', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'deleteRule']);
            Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'destroy']);
            Route::get('/{id}/trial-balance', [\App\Http\Controllers\V1\Partner\PartnerCostCenterController::class, 'trialBalance']);
        });

        // F4: Interest Calculations (Partner)
        Route::prefix('interest')->group(function () {
            Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerInterestController::class, 'index']);
            Route::post('/calculate', [\App\Http\Controllers\V1\Partner\PartnerInterestController::class, 'calculate']);
            Route::get('/summary', [\App\Http\Controllers\V1\Partner\PartnerInterestController::class, 'summary']);
            Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerInterestController::class, 'show']);
            Route::post('/{id}/generate-note', [\App\Http\Controllers\V1\Partner\PartnerInterestController::class, 'generateNote']);
            Route::post('/{id}/waive', [\App\Http\Controllers\V1\Partner\PartnerInterestController::class, 'waive']);
        });

        // F5: Collections (Partner)
        Route::prefix('collections')->group(function () {
            Route::get('/overdue', [\App\Http\Controllers\V1\Partner\PartnerCollectionController::class, 'overdueInvoices']);
            Route::post('/send-reminder', [\App\Http\Controllers\V1\Partner\PartnerCollectionController::class, 'sendReminder']);
            Route::get('/templates', [\App\Http\Controllers\V1\Partner\PartnerCollectionController::class, 'templates']);
            Route::get('/history', [\App\Http\Controllers\V1\Partner\PartnerCollectionController::class, 'history']);
            Route::get('/effectiveness', [\App\Http\Controllers\V1\Partner\PartnerCollectionController::class, 'effectiveness']);
        });

        // F6: Purchase Orders (Partner)
        Route::prefix('purchase-orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'show']);
            Route::post('/', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'update']);
            Route::post('/{id}/send', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'send']);
            Route::post('/{id}/receive-goods', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'receiveGoods']);
            Route::post('/{id}/convert-to-bill', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'convertToBill']);
            Route::get('/{id}/three-way-match', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'threeWayMatch']);
            Route::post('/{id}/cancel', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'cancel']);
            Route::delete('/{id}', [\App\Http\Controllers\V1\Partner\PartnerPurchaseOrderController::class, 'destroy']);
        });

        // F7: Budgets (Partner)
        Route::prefix('budgets')->group(function () {
            Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerBudgetController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerBudgetController::class, 'show']);
            Route::get('/{id}/vs-actual', [\App\Http\Controllers\V1\Partner\PartnerBudgetController::class, 'budgetVsActual']);
        });

        // F8: Travel Orders (Partner)
        Route::prefix('travel-orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerTravelOrderController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerTravelOrderController::class, 'show']);
            Route::post('/', [\App\Http\Controllers\V1\Partner\PartnerTravelOrderController::class, 'store']);
            Route::post('/{id}/approve', [\App\Http\Controllers\V1\Partner\PartnerTravelOrderController::class, 'approve']);
            Route::post('/{id}/settle', [\App\Http\Controllers\V1\Partner\PartnerTravelOrderController::class, 'settle']);
            Route::get('/{id}/pdf', [\App\Http\Controllers\V1\Partner\PartnerTravelOrderController::class, 'pdf']);
        });

        // F9: BI Dashboards (Partner)
        Route::prefix('bi-dashboard')->group(function () {
            Route::get('/ratios', [\App\Http\Controllers\V1\Partner\PartnerBiDashboardController::class, 'ratios']);
            Route::get('/trends', [\App\Http\Controllers\V1\Partner\PartnerBiDashboardController::class, 'trends']);
            Route::get('/summary', [\App\Http\Controllers\V1\Partner\PartnerBiDashboardController::class, 'summary']);
        });

        // F11: Custom Reports (Partner)
        Route::prefix('custom-reports')->group(function () {
            Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerCustomReportController::class, 'index']);
            Route::post('/preview', [\App\Http\Controllers\V1\Partner\PartnerCustomReportController::class, 'preview']);
            Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerCustomReportController::class, 'show']);
            Route::get('/{id}/execute', [\App\Http\Controllers\V1\Partner\PartnerCustomReportController::class, 'execute']);
        });

        // Fixed Assets (full CRUD for partners)
        Route::get('/fixed-assets', [\App\Http\Controllers\V1\Partner\PartnerFixedAssetController::class, 'index']);
        Route::post('/fixed-assets', [\App\Http\Controllers\V1\Partner\PartnerFixedAssetController::class, 'store']);
        Route::get('/fixed-assets/register', [\App\Http\Controllers\V1\Partner\PartnerFixedAssetController::class, 'register']);
        Route::get('/fixed-assets/register/export', [\App\Http\Controllers\V1\Partner\PartnerFixedAssetController::class, 'registerExport']);
        Route::get('/fixed-assets/{id}', [\App\Http\Controllers\V1\Partner\PartnerFixedAssetController::class, 'show']);
        Route::put('/fixed-assets/{id}', [\App\Http\Controllers\V1\Partner\PartnerFixedAssetController::class, 'update']);
        Route::post('/fixed-assets/{id}/dispose', [\App\Http\Controllers\V1\Partner\PartnerFixedAssetController::class, 'dispose']);
        Route::delete('/fixed-assets/{id}', [\App\Http\Controllers\V1\Partner\PartnerFixedAssetController::class, 'destroy']);
    });

    // Partner Tax Returns (VAT + CIT) for Client Companies
    Route::prefix('/companies/{company}/tax')->group(function () {
        // VAT (DDV-04)
        Route::post('/vat-return/preview', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'vatPreview']);
        Route::post('/vat-return', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'vatGenerate']);
        Route::post('/vat-return/file', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'vatFile']);
        Route::get('/vat-return/periods', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'vatPeriods']);
        Route::get('/vat-return/periods/{periodId}/returns', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'vatReturns']);
        Route::get('/vat-return/{id}/download-xml', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'vatDownloadXml']);
        Route::get('/vat-status', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'vatStatus']);
        // CIT (DB-VP)
        Route::post('/cit-return/preview', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'citPreview']);
        Route::post('/cit-return', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'citGenerate']);
        Route::post('/cit-return/file', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'citFile']);
        Route::get('/cit-return/{id}/download-xml', [\App\Http\Controllers\V1\Partner\PartnerTaxController::class, 'citDownloadXml']);
    });

    // Partner UJP Tax Forms for Client Companies
    Route::prefix('/companies/{company}/ujp-forms')->group(function () {
        Route::get('/list', [\App\Http\Controllers\V1\Partner\PartnerUjpFormController::class, 'list']);
        Route::get('/{formCode}/preview', [\App\Http\Controllers\V1\Partner\PartnerUjpFormController::class, 'preview']);
        Route::post('/{formCode}/xml', [\App\Http\Controllers\V1\Partner\PartnerUjpFormController::class, 'generateXml']);
        Route::post('/{formCode}/pdf', [\App\Http\Controllers\V1\Partner\PartnerUjpFormController::class, 'generatePdf']);
        Route::post('/{formCode}/file', [\App\Http\Controllers\V1\Partner\PartnerUjpFormController::class, 'file']);
    });

    // Partner Payroll Reports for Client Companies
    Route::prefix('/companies/{company}/payroll-reports')->group(function () {
        Route::get('/tax-summary', [\App\Http\Controllers\V1\Partner\PartnerPayrollReportController::class, 'taxSummary']);
        Route::get('/statistics', [\App\Http\Controllers\V1\Partner\PartnerPayrollReportController::class, 'statistics']);
        Route::get('/monthly-comparison', [\App\Http\Controllers\V1\Partner\PartnerPayrollReportController::class, 'monthlyComparison']);
        Route::get('/download-mpin-xml', [\App\Http\Controllers\V1\Partner\PartnerPayrollReportController::class, 'downloadMpinXml']);
        Route::get('/download-ddv04-xml', [\App\Http\Controllers\V1\Partner\PartnerPayrollReportController::class, 'downloadDdv04Xml']);
    });

    // Client Document Upload Portal - Partner Review (P8-01)
    // ----------------------------------
    Route::prefix('/companies/{company}/documents')->group(function () {
        Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerClientDocumentController::class, 'index']);
        Route::get('/download-all', [\App\Http\Controllers\V1\Partner\PartnerClientDocumentController::class, 'bulkDownload']);
        Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerClientDocumentController::class, 'show']);
        Route::post('/{id}/review', [\App\Http\Controllers\V1\Partner\PartnerClientDocumentController::class, 'markReviewed']);
        Route::post('/{id}/reject', [\App\Http\Controllers\V1\Partner\PartnerClientDocumentController::class, 'reject']);
        Route::get('/{id}/download', [\App\Http\Controllers\V1\Partner\PartnerClientDocumentController::class, 'download']);
    });

    // Partner Deadline Tracking (P8-02)
    Route::prefix('/deadlines')->group(function () {
        Route::get('/', [\App\Http\Controllers\V1\Partner\DeadlineController::class, 'index']);
        Route::get('/summary', [\App\Http\Controllers\V1\Partner\DeadlineController::class, 'summary']);
        Route::post('/', [\App\Http\Controllers\V1\Partner\DeadlineController::class, 'store'])->middleware('throttle:strict');
        Route::patch('/{id}', [\App\Http\Controllers\V1\Partner\DeadlineController::class, 'update']);
        Route::post('/{id}/complete', [\App\Http\Controllers\V1\Partner\DeadlineController::class, 'complete']);
        Route::delete('/{id}', [\App\Http\Controllers\V1\Partner\DeadlineController::class, 'destroy'])->middleware('throttle:strict');
    });

    // P8-03: Bulk Reporting Across Clients
    Route::prefix('/reports')->group(function () {
        Route::post('/multi-company', [BulkReportController::class, 'multiCompany']);
        Route::post('/consolidated', [BulkReportController::class, 'consolidated']);
        Route::post('/export', [BulkReportController::class, 'export']);
    });

    // F10: Batch Operations (Partner)
    Route::prefix('/batch-operations')->group(function () {
        Route::get('/', [\App\Http\Controllers\V1\Partner\PartnerBatchOperationController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\V1\Partner\PartnerBatchOperationController::class, 'store']);
        Route::get('/operations', [\App\Http\Controllers\V1\Partner\PartnerBatchOperationController::class, 'operations']);
        Route::get('/{id}', [\App\Http\Controllers\V1\Partner\PartnerBatchOperationController::class, 'show']);
        Route::post('/{id}/cancel', [\App\Http\Controllers\V1\Partner\PartnerBatchOperationController::class, 'cancel']);
        Route::get('/{id}/progress', [\App\Http\Controllers\V1\Partner\PartnerBatchOperationController::class, 'progress']);
    });

    // F12: Financial Consolidation (Partner)
    Route::prefix('/consolidation')->group(function () {
        Route::get('/groups', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'index']);
        Route::post('/groups', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'store']);
        Route::get('/groups/{id}', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'show']);
        Route::put('/groups/{id}', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'update']);
        Route::delete('/groups/{id}', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'destroy']);
        Route::get('/groups/{id}/intercompany', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'intercompany']);
        Route::post('/groups/{id}/eliminations', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'eliminations']);
        Route::get('/groups/{id}/trial-balance', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'trialBalance']);
        Route::get('/groups/{id}/income-statement', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'incomeStatement']);
        Route::get('/groups/{id}/balance-sheet', [\App\Http\Controllers\V1\Partner\PartnerConsolidationController::class, 'balanceSheet']);
    });
});

// AI Financial Assistant Routes (available to all, usage limits apply)
Route::middleware(['auth:sanctum'])->prefix('v1/ai')->group(function () {
    Route::get('/summary', [App\Http\Controllers\AiSummaryController::class, 'getSummary']);
    Route::get('/risk', [App\Http\Controllers\AiSummaryController::class, 'getRisk']);
});

// Public Signup Routes (No Auth Required)
// ----------------------------------
Route::prefix('v1/public/signup')->middleware(['throttle:public'])->group(function () {
    Route::post('/validate-referral', [\Modules\Mk\Public\Controllers\SignupController::class, 'validateReferral']);
    Route::post('/validate-company-referral', [\Modules\Mk\Public\Controllers\SignupController::class, 'validateCompanyReferral']);
    Route::get('/plans', [\Modules\Mk\Public\Controllers\SignupController::class, 'getPlans']);
    Route::get('/currencies', [\Modules\Mk\Public\Controllers\SignupController::class, 'getCurrencies']);
    Route::get('/languages', [\Modules\Mk\Public\Controllers\SignupController::class, 'getLanguages']);
    Route::post('/register', [\Modules\Mk\Public\Controllers\SignupController::class, 'register'])->middleware('throttle:strict');
});

// Public Partner Signup Routes (No Auth Required)
// ----------------------------------
Route::prefix('v1/public/partner-signup')->middleware(['throttle:public'])->group(function () {
    Route::post('/validate-referral', [\Modules\Mk\Public\Controllers\PartnerSignupController::class, 'validateReferral']);
    Route::post('/register', [\Modules\Mk\Public\Controllers\PartnerSignupController::class, 'register'])->middleware('throttle:strict');
});
// Clawd AI Assistant Monitoring Endpoint
// ----------------------------------
Route::middleware(['throttle:60,1', 'clawd.token'])
    ->prefix('v1/clawd')
    ->group(function () {
        Route::get('/status', [\App\Http\Controllers\Api\ClawdStatusController::class, '__invoke']);
        Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\Api\ClawdStatusController::class, 'replyToTicket']);
    });

// CLAUDE-CHECKPOINT: Public signup endpoints with referral tracking
