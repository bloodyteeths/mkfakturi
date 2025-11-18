---

### **AC-14: Company → Invite Company Flow**

**Goal:** Companies can invite other companies to join Fakturino using QR/email/referral links.

**Backend:**
1. **Migration:** Create `company_referrals` table
   ```php
   Schema::create('company_referrals', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('inviter_company_id');
       $table->unsignedBigInteger('invited_company_id')->nullable();
       $table->string('invitation_token')->unique();
       $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
       $table->timestamps();

       $table->foreign('inviter_company_id')->references('id')->on('companies')->onDelete('cascade');
       $table->foreign('invited_company_id')->references('id')->on('companies')->onDelete('set null');
   });
   ```

2. **Controller:** `CompanyReferralController`
   - `createInvite()` - generates referral link + QR
   - `acceptInvite($token)` - accepts the referral
   - `declineInvite($token)`
   - Once accepted:
     - create new company if needed
     - attach referral relationship
     - trigger commission entry

3. **Routes:**
   ```php
   Route::prefix('company/referrals')->group(function () {
       Route::post('/invite', [CompanyReferralController::class, 'createInvite']);
       Route::get('/accept/{token}', [CompanyReferralController::class, 'acceptInvite']);
       Route::get('/decline/{token}', [CompanyReferralController::class, 'declineInvite']);
   });
   ```

**Frontend:**
- Add “Invite Company” card in Company Settings
- Modal with:
  - “Generate Invite Link”
  - QR code
  - Copy link
- New page `/company/referrals`:
  - List invited companies
  - Status indicator

---

### **AC-15: Partner → Invite Partner Flow**

**Goal:** Partners can invite other partners, forming a multi-level accountant network.

**Backend:**
1. **Migration:** Create `partner_referrals` table (similar to companies)
2. **Controller:** `PartnerReferralController`
3. **On acceptance:**
   - create child partner
   - link `parent_partner_id`
   - set default commission chain rules

**Frontend:**
- “Invite Partner” in Partner Console
- Modal for email invite + QR
- Partner tree view (basic list in this phase)

---

### **AC-16: Entity Reassignment (Switch Inviter)**

**Goal:** A company or partner can switch to a different inviter with admin approval.

**Backend:**
- Add `inviter_override_requests` table
- Controller:
  - `requestOverride()`
  - `approveOverride()`
  - `rejectOverride()`
- On approval:
  - update referral parent
  - recalculate commission linkage

**Frontend:**
- Request form inside settings
- Admin UI to approve/deny

---

### **AC-17: Network Graph (Company & Partner Trees)**

**Goal:** Visualize multi-level referral hierarchy.

**Frontend:**
- Use `vue-d3-tree` or lightweight SVG tree
- Pages:
  - `/admin/partners/:id/tree`
  - `/admin/companies/:id/tree`
- Node info:
  - entity name
  - role (partner/company)
  - commissions from subtree

**Backend:**
- Endpoint returning:
  - children list
  - referral depth
  - aggregated commissions

---

### **AC-18: Multi-Level Commission Engine**

**Goal:** Unified commission calculation for:

- company → company
- partner → partner
- partner → company
- mixed paths

**Backend:**
1. Create `commission_chains` config table:
   - level
   - entity_type
   - percentage

2. Extend existing `CommissionService`:
   - determine referral path
   - apply multi-level distribution
   - create commission records per level

3. Add admin UI:
   - `/admin/settings/commission-rules`
   - editable matrix for partner/company depth % rules

**Frontend:**
- Table editor UI with rows: levels 1–5
- Columns: company→company, partner→company, partner→partner
- Preview card showing expected payout distribution

---
