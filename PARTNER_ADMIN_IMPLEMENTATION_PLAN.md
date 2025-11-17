# Partner Admin Implementation Plan (AC-08 to AC-13)

**Based on canonical ChatGPT model - This is the future-proof structure**

---

## Current Status

✅ **What Works:**
- Partner model with company relationships
- `partner_company_links` pivot table
- Partner console at `/admin/console`
- Company switching
- Partner commission tracking (separate portal)
- Partner seeder

❌ **What's Missing (Critical):**
- Super admin UI to manage partners
- UI to assign/unassign companies
- Permission editor
- Invitation flows
- Commission overview in console

---

## Implementation Tickets

### **AC-08: Super Admin Partner Management**

**Goal:** Super admin can view/manage all partners

**Backend:**
1. **Controller:** `app/Http/Controllers/V1/Admin/Partner/PartnerManagementController.php`
   - `index()` - List all partners with stats
   - `show($partnerId)` - Partner detail
   - `store()` - Create partner
   - `update($partnerId)` - Update partner
   - `destroy($partnerId)` - Deactivate partner

2. **Routes:** `routes/api.php`
   ```php
   Route::prefix('partners')->middleware(['super-admin'])->group(function () {
       Route::get('/', [PartnerManagementController::class, 'index']);
       Route::post('/', [PartnerManagementController::class, 'store']);
       Route::get('/{partner}', [PartnerManagementController::class, 'show']);
       Route::put('/{partner}', [PartnerManagementController::class, 'update']);
       Route::delete('/{partner}', [PartnerManagementController::class, 'destroy']);
   });
   ```

**Frontend:**
1. **List Page:** `resources/js/pages/admin/partners/Index.vue`
   - Table with: name, email, companies count, total earnings, status
   - Search/filter
   - Create button

2. **Detail Page:** `resources/js/pages/admin/partners/View.vue`
   - Tabs:
     - **Info:** name, email, phone, tax_id, bank account, commission_rate
     - **Companies:** assigned companies table
     - **Commissions:** earnings overview
     - **Payouts:** payout history

3. **Create/Edit:** `resources/js/pages/admin/partners/Create.vue`
   - Form fields: name, email, phone, company_name, tax_id, commission_rate
   - KYC status dropdown

4. **Routes:** `resources/scripts/admin/admin-router.js`
   ```js
   {
     path: 'partners',
     meta: { requiresSuperAdmin: true },
     children: [
       { path: '', name: 'partners.index', component: PartnersIndex },
       { path: 'create', name: 'partners.create', component: PartnerCreate },
       { path: ':id', name: 'partners.view', component: PartnerView },
     ]
   }
   ```

---

### **AC-09: Assign/Unassign Company UI**

**Goal:** Super admin can assign companies to partners with permissions

**Backend:**
1. **Controller Methods** (add to `PartnerManagementController`):
   - `assignCompany($partnerId)` - POST
   - `unassignCompany($partnerId, $companyId)` - DELETE
   - `updatePermissions($partnerId, $companyId)` - PUT

2. **Request Validation:**
   ```php
   $request->validate([
       'company_id' => 'required|exists:companies,id',
       'is_primary' => 'boolean',
       'override_commission_rate' => 'nullable|numeric|min:0|max:100',
       'permissions' => 'array',
       'permissions.*' => 'in:view_invoices,edit_invoices,view_bank,manage_bank,...'
   ]);
   ```

3. **Permission Enums:** Create `app/Enums/PartnerPermission.php`
   ```php
   enum PartnerPermission: string {
       case VIEW_INVOICES = 'view_invoices';
       case EDIT_INVOICES = 'edit_invoices';
       case VIEW_CUSTOMERS = 'view_customers';
       case EDIT_CUSTOMERS = 'edit_customers';
       case VIEW_BANK = 'view_bank';
       case MANAGE_BANK = 'manage_bank';
       case VIEW_REPORTS = 'view_reports';
       case VIEW_SALARIES = 'view_salaries';
       case FULL_ACCESS = 'full_access';
   }
   ```

**Frontend:**
1. **Assign Company Modal:** `resources/js/pages/admin/partners/components/AssignCompanyModal.vue`
   - Company dropdown (search/select)
   - Permission checkboxes (from enum)
   - Commission rate override (number input)
   - Primary company toggle
   - Save button

2. **Companies Tab** (in PartnerView):
   - Table showing assigned companies
   - Columns: company name, primary badge, commission rate, permissions count
   - Actions: Edit permissions, Unassign
   - "Assign Company" button (opens modal)

---

### **AC-10: Partner Commission Overview in Console**

**Goal:** Integrate commission data into `/admin/console`

**Backend:**
1. **Add to `AccountantConsoleController`:**
   - `commissions()` - Get commissions for current partner
   - Returns: total earnings, monthly, pending, per-company breakdown

**Frontend:**
1. **New Route:** `/admin/console/commissions`
2. **Component:** `resources/js/pages/console/Commissions.vue`
   - KPI cards: Total earnings, This month, Pending payout
   - Chart: Monthly trend
   - Table: Recent commissions by company
   - Filter: Date range, company

3. **Navigation:** Add "Commissions" to console sidebar
   - Update `ConsoleHome.vue` with sidebar navigation
   - Add commission summary to company cards

---

### **AC-11: Company → Invite Accountant Flow**

**Goal:** Company owner can invite a partner to manage their books

**Database Migration:**
```php
Schema::table('partner_company_links', function (Blueprint $table) {
    $table->string('invitation_status')->default('accepted'); // pending, accepted, declined
    $table->integer('created_by')->unsigned()->nullable();
    $table->timestamp('invited_at')->nullable();
    $table->timestamp('accepted_at')->nullable();

    $table->foreign('created_by')->references('id')->on('users');
});
```

**Backend:**
1. **Controller:** `app/Http/Controllers/V1/Admin/PartnerInvitationController.php`
   - `invitePartner()` - Company owner sends invite
   - `acceptInvite($token)` - Partner accepts
   - `declineInvite($token)` - Partner declines

2. **Notification:** `app/Notifications/PartnerInvited.php`
   - Email to partner with accept/decline links

**Frontend:**
1. **Company Settings:** Add "Invite Accountant" section
   - Form: partner email, permissions checkboxes
   - Send button

2. **Partner Invitations:** `resources/js/pages/console/Invitations.vue`
   - List pending invitations
   - Accept/Decline buttons

---

### **AC-12: Partner → Invite Company Flow**

**Goal:** Partner can invite company via QR/email (already partially exists)

**Backend:**
1. **Use existing:** `AffiliateLink` model
2. **Add endpoint:** Generate partner-specific signup link
3. **On company signup:** Auto-create `partner_company_links` entry

**Frontend:**
1. **Partner Console:** Add "Invite Company" button
2. **Modal:**
   - QR code (reuse existing QR component)
   - Email invitation form
   - Copy link button

---

### **AC-13: Permission Editor UI**

**Goal:** Visual permission management

**Frontend:**
1. **Component:** `resources/js/pages/admin/partners/components/PermissionEditor.vue`
   - Permission groups:
     - **Invoices:** view, edit, delete
     - **Customers:** view, edit, delete
     - **Banking:** view, manage
     - **Reports:** view
     - **Payroll:** view
     - **Full Access:** master toggle
   - Checkbox matrix
   - Save/Cancel buttons

2. **Integration:**
   - Used in AssignCompanyModal
   - Used in PartnerView companies tab

---

## Database Schema Updates

### Migration: Add invitation fields to `partner_company_links`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('partner_company_links', function (Blueprint $table) {
            $table->string('invitation_status')->default('accepted')->after('is_active');
            $table->integer('created_by')->unsigned()->nullable()->after('invitation_status');
            $table->timestamp('invited_at')->nullable()->after('created_by');
            $table->timestamp('accepted_at')->nullable()->after('invited_at');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('partner_company_links', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['invitation_status', 'created_by', 'invited_at', 'accepted_at']);
        });
    }
};
```

---

## Permission System Design

### Predefined Permissions (PartnerPermission Enum)

```php
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
}
```

### Permission Checking Helper (add to Partner model)

```php
public function hasPermission(int $companyId, PartnerPermission $permission): bool
{
    $link = $this->companies()
        ->where('companies.id', $companyId)
        ->first();

    if (!$link) {
        return false;
    }

    $permissions = $link->pivot->permissions ?? [];

    // Full access overrides everything
    if (in_array(PartnerPermission::FULL_ACCESS->value, $permissions)) {
        return true;
    }

    return in_array($permission->value, $permissions);
}
```

---

## UI/UX Flow Examples

### Flow 1: Super Admin Assigns Company to Partner

1. Admin goes to `/admin/partners`
2. Clicks on a partner
3. Goes to "Companies" tab
4. Clicks "Assign Company"
5. Modal opens:
   - Select company dropdown
   - Permission checkboxes (grouped)
   - Commission override field
   - Primary toggle
6. Clicks "Save"
7. Partner instantly sees company in their console

### Flow 2: Company Invites Partner

1. Company owner goes to `/admin/settings/partner`
2. Clicks "Invite Accountant"
3. Enters partner email
4. Selects permissions
5. Clicks "Send Invitation"
6. Partner receives email
7. Partner clicks "Accept"
8. Partner sees company in console

### Flow 3: Partner Views Commissions

1. Partner logs in
2. Goes to `/admin/console`
3. Sidebar shows "Commissions" link
4. Clicks it
5. Sees:
   - Total earnings: €5,234
   - This month: €432
   - Pending payout: €1,200
   - Chart showing monthly trend
   - Table with per-company breakdown

---

## Super Admin Bypass Logic

Add to middleware/controllers:

```php
// Super admin bypasses all partner restrictions
if (auth()->user()->isSuperAdmin()) {
    // Full access to everything
    return $next($request);
}

// Partner must check partner_company_links
$partner = Partner::where('user_id', auth()->id())->first();
if (!$partner) {
    abort(403, 'Not registered as partner');
}

// Check access to current company
$hasAccess = $partner->activeCompanies()
    ->where('companies.id', $companyId)
    ->exists();

if (!$hasAccess) {
    abort(403, 'No access to this company');
}
```

---

## Next Steps

**Choose implementation order:**

1. **AC-08** (Partner list/detail) - Foundation
2. **AC-09** (Assign companies) - Most critical
3. **AC-13** (Permission editor) - Needed for AC-09
4. **Migration** (Add invitation fields)
5. **AC-10** (Commission in console)
6. **AC-11** (Company invites partner)
7. **AC-12** (Partner invites company)

**OR implement in parallel if multiple devs available**

---

## Estimated Effort

- AC-08: 4 hours (backend 1h, frontend 3h)
- AC-09: 6 hours (backend 2h, frontend 4h)
- AC-10: 3 hours (backend 1h, frontend 2h)
- AC-11: 5 hours (backend 2h, frontend 2h, email 1h)
- AC-12: 3 hours (reuse existing, minor additions)
- AC-13: 4 hours (complex UI component)

**Total:** ~25 hours for complete implementation

---

**Should I proceed with AC-08 (Partner Management UI) first?**
