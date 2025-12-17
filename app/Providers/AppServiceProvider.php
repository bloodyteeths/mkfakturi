<?php

namespace App\Providers;

use App\Bouncer\Scopes\DefaultScope;
use App\Policies\CompanyPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\EstimatePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ItemPolicy;
use App\Policies\ModulesPolicy;
use App\Policies\NotePolicy;
use App\Policies\OwnerPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\RecurringInvoicePolicy;
use App\Policies\ReportPolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingsPolicy;
use App\Policies\UserPolicy;
use App\Services\AiInsightsService;
use App\Services\McpClient;
use App\Services\McpDataProvider;
use App\Space\InstallUtils;
use Gate;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Silber\Bouncer\Database\Models as BouncerModels;
use Silber\Bouncer\Database\Role;
use View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/admin/dashboard';

    /**
     * The path to the "customer home" route for your application.
     *
     * This is used by Laravel authentication to redirect customers after login.
     *
     * @var string
     */
    public const CUSTOMER_HOME = '/customer/dashboard';

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->isHealthCheckRequest() || $this->app->runningInConsole()) {
            return;
        }

        // Ensure cache directory exists (fix for ephemeral environments)
        if (! file_exists(storage_path('framework/cache/data'))) {
            @mkdir(storage_path('framework/cache/data'), 0775, true);
        }

        if (InstallUtils::isDbCreated()) {
            $this->addMenus();
        }

        Gate::policy(Role::class, RolePolicy::class);

        // Phase 2 Policies
        Gate::policy(\App\Models\Supplier::class, \App\Policies\SupplierPolicy::class);
        Gate::policy(\App\Models\Bill::class, \App\Policies\BillPolicy::class);
        Gate::policy(\App\Models\ProformaInvoice::class, \App\Policies\ProformaInvoicePolicy::class);
        Gate::policy(\App\Models\AuditLog::class, \App\Policies\AuditLogPolicy::class);

        // Phase 3-4 Policies
        // Gate::policy(\App\Models\BankConnection::class, \App\Policies\BankConnectionPolicy::class);
        Gate::policy(\App\Models\ApprovalRequest::class, \App\Policies\ApprovalPolicy::class);
        // Gate::policy(\App\Models\ExportJob::class, \App\Policies\ExportJobPolicy::class);
        // Gate::policy(\App\Models\RecurringExpense::class, \App\Policies\RecurringExpensePolicy::class);

        // E-Invoice Policy
        Gate::policy(\App\Models\EInvoice::class, \App\Policies\EInvoicePolicy::class);

        // Import/Migration Policy
        Gate::policy(\App\Models\ImportJob::class, \App\Policies\ImportJobPolicy::class);

        // Support Ticketing Policy (PHASE 2 - TRACK 3: MILESTONE 3.1)
        Gate::policy(\Coderflex\LaravelTicket\Models\Ticket::class, \App\Policies\TicketPolicy::class);
        // CLAUDE-CHECKPOINT

        View::addNamespace('pdf_templates', storage_path('app/templates/pdf'));

        $this->bootAuth();
        $this->bootBroadcast();
        $this->bootObservers();

        // In demo mode, prevent all outgoing emails and notifications
        if (config('app.env') === 'demo') {
            \Illuminate\Support\Facades\Mail::fake();
            \Illuminate\Support\Facades\Notification::fake();
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        BouncerModels::scope(new DefaultScope);

        // Register AI services
        $this->app->singleton(McpDataProvider::class, function ($app) {
            return new McpDataProvider;
        });

        $this->app->singleton(AiInsightsService::class, function ($app) {
            return new AiInsightsService(
                $app->make(McpDataProvider::class)
            );
        });

        // Keep McpClient for external integrations if needed
        $this->app->singleton(McpClient::class, function ($app) {
            return new McpClient;
        });
    }

    public function addMenus()
    {
        // main menu
        \Menu::make('main_menu', function ($menu) {
            foreach (config('invoiceshelf.main_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });

        // setting menu
        \Menu::make('setting_menu', function ($menu) {
            foreach (config('invoiceshelf.setting_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });

        \Menu::make('customer_portal_menu', function ($menu) {
            foreach (config('invoiceshelf.customer_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });
    }

    public function generateMenu($menu, $data)
    {
        $menu->add($data['title'], $data['link'])
            ->data('icon', $data['icon'])
            ->data('name', $data['name'])
            ->data('owner_only', $data['owner_only'])
            ->data('ability', $data['ability'])
            ->data('model', $data['model'])
            ->data('group', $data['group']);
    }

    public function bootAuth()
    {

        Gate::define('create company', [CompanyPolicy::class, 'create']);
        Gate::define('transfer company ownership', [CompanyPolicy::class, 'transferOwnership']);
        Gate::define('delete company', [CompanyPolicy::class, 'delete']);

        Gate::define('manage modules', [ModulesPolicy::class, 'manageModules']);

        Gate::define('manage settings', [SettingsPolicy::class, 'manageSettings']);
        Gate::define('manage company', [SettingsPolicy::class, 'manageCompany']);
        Gate::define('manage backups', [SettingsPolicy::class, 'manageBackups']);
        Gate::define('manage file disk', [SettingsPolicy::class, 'manageFileDisk']);
        Gate::define('manage email config', [SettingsPolicy::class, 'manageEmailConfig']);
        Gate::define('manage pdf config', [SettingsPolicy::class, 'managePDFConfig']);
        Gate::define('manage notes', [NotePolicy::class, 'manageNotes']);
        Gate::define('view notes', [NotePolicy::class, 'viewNotes']);

        Gate::define('send invoice', [InvoicePolicy::class, 'send']);
        Gate::define('send estimate', [EstimatePolicy::class, 'send']);
        Gate::define('send payment', [PaymentPolicy::class, 'send']);

        Gate::define('delete multiple items', [ItemPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple customers', [CustomerPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple users', [UserPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple invoices', [InvoicePolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple estimates', [EstimatePolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple expenses', [ExpensePolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple payments', [PaymentPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple recurring invoices', [RecurringInvoicePolicy::class, 'deleteMultiple']);

        Gate::define('view dashboard', [DashboardPolicy::class, 'view']);

        Gate::define('view report', [ReportPolicy::class, 'viewReport']);

        Gate::define('owner only', [OwnerPolicy::class, 'managedByOwner']);
    }

    public function bootBroadcast()
    {
        Broadcast::routes(['middleware' => 'api.auth']);
    }

    /**
     * Register model observers for accounting backbone
     *
     * IMPORTANT: We always register observers if the env var is set.
     * The Pennant feature check is done at runtime in the observers/adapter
     * because at boot time there's no authenticated user for Pennant to check.
     */
    public function bootObservers(): void
    {
        // Register IFRS observers if global feature flag is enabled via config or env
        // Note: We only check config/env here, NOT Pennant, because Pennant needs
        // an authenticated user which doesn't exist at boot time.
        // The IfrsAdapter will do the per-company feature check at runtime.
        $isEnabled = config('ifrs.enabled', false) || env('FEATURE_ACCOUNTING_BACKBONE', false);

        if ($isEnabled) {
            \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
            \App\Models\Payment::observe(\App\Observers\PaymentObserver::class);
            \App\Models\CreditNote::observe(\App\Observers\CreditNoteObserver::class);
            \App\Models\Expense::observe(\App\Observers\ExpenseObserver::class);

            // Phase 2: Accounts Payable observers
            \App\Models\Bill::observe(\App\Observers\BillObserver::class);
            \App\Models\BillPayment::observe(\App\Observers\BillPaymentObserver::class);

            // Phase 2: Proforma Invoice observer
            \App\Models\ProformaInvoice::observe(\App\Observers\ProformaInvoiceObserver::class);

            \Illuminate\Support\Facades\Log::debug('IFRS observers registered');
        }

        // Audit trail observers (always enabled)
        \App\Models\Supplier::observe(\App\Observers\AuditObserver::class);
        \App\Models\Bill::observe(\App\Observers\AuditObserver::class);
        \App\Models\BillItem::observe(\App\Observers\AuditObserver::class);
        \App\Models\BillPayment::observe(\App\Observers\AuditObserver::class);
        \App\Models\ProformaInvoice::observe(\App\Observers\AuditObserver::class);
        \App\Models\ProformaInvoiceItem::observe(\App\Observers\AuditObserver::class);

        // Phase 3-4 Observers (audit trail)
        \App\Models\BankConnection::observe(\App\Observers\AuditObserver::class);
        \App\Models\ApprovalRequest::observe(\App\Observers\AuditObserver::class);
        \App\Models\RecurringExpense::observe(\App\Observers\AuditObserver::class);
        \App\Models\ExportJob::observe(\App\Observers\AuditObserver::class);
        \App\Models\GatewayWebhookEvent::observe(\App\Observers\AuditObserver::class);

        // Stock module observers (behind FACTURINO_STOCK_V1_ENABLED feature flag)
        // These observers automatically process stock movements when invoice/bill items are created
        if (\App\Services\StockService::isEnabled()) {
            \App\Models\InvoiceItem::observe(\App\Observers\StockInvoiceItemObserver::class);
            \App\Models\BillItem::observe(\App\Observers\StockBillItemObserver::class);
        }

        // Company observer - seeds chart of accounts for new companies (Partner Accounting Phase 4)
        \App\Models\Company::observe(\App\Observers\CompanyObserver::class);
    }

    /**
     * Check if current request is a health check endpoint
     */
    protected function isHealthCheckRequest(): bool
    {
        if (! $this->app->bound('request')) {
            return false;
        }

        $request = $this->app->make('request');
        $healthCheckPaths = ['/health', '/up', '/ping', '/ready'];

        return in_array($request->path(), $healthCheckPaths);
    }
}

// CLAUDE-CHECKPOINT
