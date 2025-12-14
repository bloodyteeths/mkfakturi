<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Laravel\Paddle\Billable;
use Silber\Bouncer\BouncerFacade;
use Silber\Bouncer\Database\Role;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class Company extends Model implements HasMedia
{
    use Billable;
    use HasFactory;
    use InteractsWithMedia; // CLAUDE-CHECKPOINT: Added Paddle Billable trait

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'name',
        'slug',
        'vat_id',
        'vat_number',
        'tax_id',
        'ifrs_entity_id',
        'paddle_id',
        'subscription_tier',
        'trial_ends_at',
    ]; // CLAUDE-CHECKPOINT: Added Paddle subscription fields

    public const COMPANY_LEVEL = 'company_level';

    public const CUSTOMER_LEVEL = 'customer_level';

    protected $appends = ['logo', 'logo_path'];

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'scope');
    }

    public function getLogoPathAttribute()
    {
        $logo = $this->getMedia('logo')->first();

        if (! $logo || ! $this->logoFileExists($logo)) {
            return null;
        }

        $fileDisk = FileDisk::whereSetAsDefault(true)->first();

        if ($fileDisk && $fileDisk->isSystem()) {
            return $logo->getPath();
        }

        return $logo->getFullUrl();
    }

    public function getLogoAttribute()
    {
        $logo = $this->getMedia('logo')->first();

        if ($logo && $this->logoFileExists($logo)) {
            return $logo->getFullUrl();
        }

        return null;
    }

    protected function logoFileExists(Media $logo): bool
    {
        $diskName = $logo->disk ?? config('filesystems.default');

        try {
            return Storage::disk($diskName)->exists($logo->getPathRelativeToRoot());
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class);
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function exchangeRateLogs(): HasMany
    {
        return $this->hasMany(ExchangeRateLog::class);
    }

    public function exchangeRateProviders(): HasMany
    {
        return $this->hasMany(ExchangeRateProvider::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function taxTypes(): HasMany
    {
        return $this->hasMany(TaxType::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_company', 'company_id', 'user_id');
    }

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'partner_company_links')
            ->using(PartnerCompany::class)
            ->withPivot([
                'id',
                'is_primary',
                'override_commission_rate',
                'permissions',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function activePartners(): BelongsToMany
    {
        return $this->partners()->wherePivot('is_active', true);
    }

    public function partnerLinks(): HasMany
    {
        return $this->hasMany(PartnerCompany::class);
    }

    public function miniMaxTokens(): HasMany
    {
        return $this->hasMany(MiniMaxToken::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function usageTracking(): HasMany
    {
        return $this->hasMany(UsageTracking::class);
    }

    /**
     * Company's IFRS Entity relationship
     */
    public function ifrsEntity(): BelongsTo
    {
        return $this->belongsTo(\IFRS\Models\Entity::class, 'ifrs_entity_id');
    } // CLAUDE-CHECKPOINT: Added ifrsEntity relationship

    /**
     * Company's subscription relationship
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class);
    }

    /**
     * Check if company is on a specific plan
     */
    public function isOnPlan(string $plan): bool
    {
        if (! $this->relationLoaded('subscription')) {
            $this->load('subscription');
        }

        return $this->subscription &&
            $this->subscription->plan === $plan &&
            in_array($this->subscription->status, ['trial', 'active']);
    }

    /**
     * Check if company can access a feature based on minimum required plan
     *
     * @param  string  $feature  Feature key from config
     */
    public function canAccessFeature(string $feature): bool
    {
        // Plan hierarchy
        $planHierarchy = ['free' => 0, 'starter' => 1, 'standard' => 2, 'business' => 3, 'max' => 4];

        // Feature to minimum plan mapping (can be moved to config later)
        $featureRequirements = [
            'basic_invoicing' => 'free',
            'estimates' => 'starter',
            'recurring_invoices' => 'starter',
            'expenses' => 'standard',
            'reports' => 'standard',
            'multi_currency' => 'business',
            'custom_fields' => 'business',
            'api_access' => 'max',
        ];

        $requiredPlan = $featureRequirements[$feature] ?? 'free';

        if (! $this->relationLoaded('subscription')) {
            $this->load('subscription');
        }

        if (! $this->subscription || ! in_array($this->subscription->status, ['trial', 'active'])) {
            return false;
        }

        $currentPlan = $this->subscription->plan;

        return $planHierarchy[$currentPlan] >= $planHierarchy[$requiredPlan];
    }

    /**
     * Check if upgrade is required to access a minimum plan level
     */
    public function upgradeRequired(string $minPlan): bool
    {
        $planHierarchy = ['free' => 0, 'starter' => 1, 'standard' => 2, 'business' => 3, 'max' => 4];

        if (! $this->relationLoaded('subscription')) {
            $this->load('subscription');
        }

        if (! $this->subscription) {
            return true; // No subscription = upgrade required
        }

        $currentPlan = $this->subscription->plan;

        return $planHierarchy[$currentPlan] < $planHierarchy[$minPlan];
    }

    /**
     * Get current plan name
     */
    public function getCurrentPlanAttribute(): string
    {
        if (! $this->relationLoaded('subscription')) {
            $this->load('subscription');
        }

        return $this->subscription ? $this->subscription->plan : 'free';
    }
    // CLAUDE-CHECKPOINT

    public function setupRoles()
    {
        BouncerFacade::scope()->to($this->id);

        $super_admin = BouncerFacade::role()->firstOrCreate([
            'name' => 'super admin',
            'title' => 'Super Admin',
            'scope' => $this->id,
        ]);

        foreach (config('abilities.abilities') as $ability) {
            BouncerFacade::allow($super_admin)->to($ability['ability'], $ability['model']);
        }

        // Create 'admin' role for partners/managers
        $admin = BouncerFacade::role()->firstOrCreate([
            'name' => 'admin',
            'title' => 'Admin',
            'scope' => $this->id,
        ]);

        // Grant same permissions to admin for now
        foreach (config('abilities.abilities') as $ability) {
            BouncerFacade::allow($admin)->to($ability['ability'], $ability['model']);
        }
    }

    public function setupDefaultPaymentMethods()
    {
        PaymentMethod::create(['name' => 'Cash', 'company_id' => $this->id]);
        PaymentMethod::create(['name' => 'Check', 'company_id' => $this->id]);
        PaymentMethod::create(['name' => 'Credit Card', 'company_id' => $this->id]);
        PaymentMethod::create(['name' => 'Bank Transfer', 'company_id' => $this->id]);
    }

    public function setupDefaultUnits()
    {
        Unit::create(['name' => 'box', 'company_id' => $this->id]);
        Unit::create(['name' => 'cm', 'company_id' => $this->id]);
        Unit::create(['name' => 'dz', 'company_id' => $this->id]);
        Unit::create(['name' => 'ft', 'company_id' => $this->id]);
        Unit::create(['name' => 'g', 'company_id' => $this->id]);
        Unit::create(['name' => 'in', 'company_id' => $this->id]);
        Unit::create(['name' => 'kg', 'company_id' => $this->id]);
        Unit::create(['name' => 'km', 'company_id' => $this->id]);
        Unit::create(['name' => 'lb', 'company_id' => $this->id]);
        Unit::create(['name' => 'mg', 'company_id' => $this->id]);
        Unit::create(['name' => 'pc', 'company_id' => $this->id]);
    }

    public function setupDefaultSettings()
    {
        $defaultInvoiceEmailBody = 'You have received a new invoice from <b>{COMPANY_NAME}</b>.</br> Please download using the button below:';
        $defaultEstimateEmailBody = 'You have received a new estimate from <b>{COMPANY_NAME}</b>.</br> Please download using the button below:';
        $defaultPaymentEmailBody = 'Thank you for the payment.</b></br> Please download your payment receipt using the button below:';
        $billingAddressFormat = '<h3>{BILLING_ADDRESS_NAME}</h3><p>{BILLING_ADDRESS_STREET_1}</p><p>{BILLING_ADDRESS_STREET_2}</p><p>{BILLING_CITY}  {BILLING_STATE}</p><p>{BILLING_COUNTRY}  {BILLING_ZIP_CODE}</p><p>{BILLING_PHONE}</p>';
        $shippingAddressFormat = '<h3>{SHIPPING_ADDRESS_NAME}</h3><p>{SHIPPING_ADDRESS_STREET_1}</p><p>{SHIPPING_ADDRESS_STREET_2}</p><p>{SHIPPING_CITY}  {SHIPPING_STATE}</p><p>{SHIPPING_COUNTRY}  {SHIPPING_ZIP_CODE}</p><p>{SHIPPING_PHONE}</p>';
        $companyAddressFormat = '<h3><strong>{COMPANY_NAME}</strong></h3><p>{COMPANY_ADDRESS_STREET_1}</p><p>{COMPANY_ADDRESS_STREET_2}</p><p>{COMPANY_CITY} {COMPANY_STATE}</p><p>{COMPANY_COUNTRY}  {COMPANY_ZIP_CODE}</p><p>{COMPANY_PHONE}</p>';
        $paymentFromCustomerAddress = '<h3>{BILLING_ADDRESS_NAME}</h3><p>{BILLING_ADDRESS_STREET_1}</p><p>{BILLING_ADDRESS_STREET_2}</p><p>{BILLING_CITY} {BILLING_STATE} {BILLING_ZIP_CODE}</p><p>{BILLING_COUNTRY}</p><p>{BILLING_PHONE}</p>';

        $settings = [
            'invoice_auto_generate' => 'YES',
            'payment_auto_generate' => 'YES',
            'estimate_auto_generate' => 'YES',
            'save_pdf_to_disk' => 'NO',
            'invoice_mail_body' => $defaultInvoiceEmailBody,
            'estimate_mail_body' => $defaultEstimateEmailBody,
            'payment_mail_body' => $defaultPaymentEmailBody,
            'invoice_company_address_format' => $companyAddressFormat,
            'invoice_shipping_address_format' => $shippingAddressFormat,
            'invoice_billing_address_format' => $billingAddressFormat,
            'estimate_company_address_format' => $companyAddressFormat,
            'estimate_shipping_address_format' => $shippingAddressFormat,
            'estimate_billing_address_format' => $billingAddressFormat,
            'payment_company_address_format' => $companyAddressFormat,
            'payment_from_customer_address_format' => $paymentFromCustomerAddress,
            'currency' => request()->currency ?? 13,
            'time_zone' => 'Asia/Kolkata',
            'language' => 'en',
            'fiscal_year' => '1-12',
            'carbon_date_format' => 'Y/m/d',
            'moment_date_format' => 'YYYY/MM/DD',
            'carbon_time_format' => 'H:i',
            'moment_time_format' => 'HH:mm',
            'invoice_use_time' => 'NO',
            'notification_email' => 'noreply@invoiceshelf.com',
            'notify_invoice_viewed' => 'NO',
            'notify_estimate_viewed' => 'NO',
            'tax_per_item' => 'NO',
            'discount_per_item' => 'NO',
            'invoice_email_attachment' => 'NO',
            'estimate_email_attachment' => 'NO',
            'payment_email_attachment' => 'NO',
            'retrospective_edits' => 'allow',
            'invoice_number_format' => '{{SERIES:INV}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'estimate_number_format' => '{{SERIES:EST}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'payment_number_format' => '{{SERIES:PAY}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'estimate_set_expiry_date_automatically' => 'YES',
            'estimate_expiry_date_days' => 7,
            'invoice_set_due_date_automatically' => 'YES',
            'invoice_due_date_days' => 7,
            'bulk_exchange_rate_configured' => 'YES',
            'estimate_convert_action' => 'no_action',
            'automatically_expire_public_links' => 'YES',
            'link_expiry_days' => 7,
        ];

        CompanySetting::setSettings($settings, $this->id);
    }

    public function setupDefaultData()
    {
        $this->setupRoles();
        $this->setupDefaultPaymentMethods();
        $this->setupDefaultUnits();
        $this->setupDefaultSettings();

        return true;
    }

    public function deleteCompany($user)
    {
        if ($this->exchangeRateLogs()->exists()) {
            $this->exchangeRateLogs()->delete();
        }

        if ($this->exchangeRateProviders()->exists()) {
            $this->exchangeRateProviders()->delete();
        }

        if ($this->expenses()->exists()) {
            $this->expenses()->delete();
        }

        if ($this->expenseCategories()->exists()) {
            $this->expenseCategories()->delete();
        }

        if ($this->payments()->exists()) {
            $this->payments()->delete();
        }

        if ($this->paymentMethods()->exists()) {
            $this->paymentMethods()->delete();
        }

        if ($this->customFieldValues()->exists()) {
            $this->customFieldValues()->delete();
        }

        if ($this->customFields()->exists()) {
            $this->customFields()->delete();
        }

        if ($this->invoices()->exists()) {
            $this->invoices->map(function ($invoice) {
                $this->checkModelData($invoice);

                if ($invoice->transactions()->exists()) {
                    $invoice->transactions()->delete();
                }
            });

            $this->invoices()->delete();
        }

        if ($this->recurringInvoices()->exists()) {
            $this->recurringInvoices->map(function ($recurringInvoice) {
                $this->checkModelData($recurringInvoice);
            });

            $this->recurringInvoices()->delete();
        }

        if ($this->estimates()->exists()) {
            $this->estimates->map(function ($estimate) {
                $this->checkModelData($estimate);
            });

            $this->estimates()->delete();
        }

        if ($this->items()->exists()) {
            $this->items()->delete();
        }

        if ($this->taxTypes()->exists()) {
            $this->taxTypes()->delete();
        }

        if ($this->customers()->exists()) {
            $this->customers->map(function ($customer) {
                if ($customer->addresses()->exists()) {
                    $customer->addresses()->delete();
                }

                $customer->delete();
            });
        }

        $roles = Role::when($this->id, function ($query) {
            return $query->where('scope', $this->id);
        })->get();

        if ($roles) {
            $roles->map(function ($role) {
                $role->delete();
            });
        }

        if ($this->users()->exists()) {
            $user->companies()->detach($this->id);
        }

        $this->settings()->delete();

        $this->address()->delete();

        $this->delete();

        return true;
    }

    public function checkModelData($model)
    {
        $model->items->map(function ($item) {
            if ($item->taxes()->exists()) {
                $item->taxes()->delete();
            }

            $item->delete();
        });

        if ($model->taxes()->exists()) {
            $model->taxes()->delete();
        }
    }

    public function hasTransactions()
    {
        if (
            $this->customers()->exists() ||
            $this->items()->exists() ||
            $this->invoices()->exists() ||
            $this->estimates()->exists() ||
            $this->expenses()->exists() ||
            $this->payments()->exists() ||
            $this->recurringInvoices()->exists()
        ) {
            return true;
        }

        return false;
    }
}
