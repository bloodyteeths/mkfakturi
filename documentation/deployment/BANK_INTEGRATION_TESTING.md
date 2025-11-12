# Bank Integration Testing Guide

## Purpose of Bank Integration

Your bank integration is designed to:
1. **Automatically fetch bank transactions** from NLB Bank via PSD2 API
2. **Match transactions to invoices/payments** for automatic reconciliation
3. **Reduce manual data entry** for bookkeeping
4. **Ensure accurate cash flow tracking** in real-time
5. **Automate invoice payment confirmation** when money arrives in your account

---

## Step 1: Verify Connection Success

### After Connecting NLB Bank:

1. **Check Banking Dashboard**
   ```
   https://www.facturino.mk/admin/banking
   ```

   You should see:
   - ✅ Bank card showing "NLB Banka AD Skopje"
   - ✅ Account number or IBAN
   - ✅ Current balance (may be 0 in sandbox)
   - ✅ Last sync time
   - ✅ "Connected" status

2. **Check Debug Endpoints** (temporary):
   ```
   https://www.facturino.mk/debug/bank-tokens
   ```
   Should show:
   ```json
   {
     "count": 1,
     "tokens": [{
       "bank_code": "nlb",
       "has_access_token": true,
       "has_refresh_token": true,
       "expires_at": "..."
     }]
   }
   ```

   ```
   https://www.facturino.mk/debug/bank-accounts
   ```
   Should show:
   ```json
   {
     "count": 1,
     "accounts": [{
       "bank_code": "nlb",
       "bank_name": "NLB Banka AD Skopje",
       "account_number": "...",
       "iban": "MK...",
       "is_active": true
     }]
   }
   ```

---

## Step 2: Test Transaction Fetching

### Check if Sandbox Has Test Data:

The NLB sandbox may or may not have pre-populated test transactions. To check:

1. **Go to Banking Dashboard**
2. **Look at the Transactions section**
3. **Check if any transactions appear**

### If No Transactions Appear:

This is **normal** for sandbox environments. Most PSD2 sandboxes have:
- ✅ Test accounts (which you connected)
- ❌ Empty transaction history

**What this means**: The integration is working, but sandbox has no data to display.

### To Test with Real Data (Sandbox Limitations):

You have two options:

**Option A: Contact NLB Support**
- Email: developer support at NLB
- Ask for: "Sandbox test accounts with sample transactions"
- Request: Instructions to create test transactions in sandbox

**Option B: Use Production (After Testing)**
- Once confident in sandbox
- Switch to production environment
- Connect real bank account
- See real transactions

---

## Step 3: Test Transaction Sync

### Manual Sync:

In the banking dashboard, each account should have a "Sync" button. Test it:

1. Click the **"Sync Now"** button on your NLB account
2. Wait 5-10 seconds
3. Refresh the page
4. Check if:
   - Last sync time updated
   - Any new transactions appeared (if sandbox has data)

### Auto-Sync (Scheduled):

The system is designed to auto-sync transactions periodically. To verify:

1. **Check Laravel Logs** (via debug endpoint):
   ```
   https://www.facturino.mk/debug/logs
   ```
   Look for: `Manual bank sync triggered`

2. **Check if Sync Job Runs** (currently commented out in code):
   - Line 329: `\App\Jobs\SyncBankTransactions::dispatch($account)`
   - This job needs to be implemented for auto-sync

---

## Step 4: Test Transaction Matching (Invoice Reconciliation)

### The Goal:

When a customer pays an invoice, the bank transaction should automatically match to that invoice.

### How It Works:

1. You send invoice #1234 to customer for 5,000 MKD
2. Customer pays via bank transfer
3. Transaction appears in your NLB account
4. System matches transaction to invoice #1234 based on:
   - Amount (5,000 MKD)
   - Reference number (invoice number in transaction description)
   - Date range

### To Test (When Transactions Available):

1. **Create a test invoice** in Facturino
2. **Look for the transaction** in Banking → Transactions
3. **Check matching fields**:
   - `matched_invoice_id`: Should show invoice ID if matched
   - `match_confidence`: Percentage of match certainty
   - Status should show "Matched" badge

4. **Manual Matching**:
   - If not auto-matched, click "Match to Invoice" button
   - Select the invoice from list
   - Confirm match

---

## Step 5: Verify Data Flow

### Database Check (via debug endpoint):

```
https://www.facturino.mk/debug/bank-accounts
```

Should show:
```json
{
  "count": 1,
  "accounts": [{
    "id": 1,
    "bank_code": "nlb",
    "bank_name": "NLB Banka AD Skopje",
    "company_id": 2,
    "company_name": "Your Company Name",
    "account_number": "XXXXX",
    "iban": "MKXXXXXXXXXXXX",
    "current_balance": 0,
    "is_active": true,
    "created_at": "2025-11-09..."
  }]
}
```

### Check Transaction Storage:

After transactions sync (if any exist in sandbox):

```sql
-- Via database or debug endpoint
SELECT * FROM bank_transactions
WHERE bank_account_id = 1
ORDER BY transaction_date DESC
LIMIT 10;
```

Should show:
- Transaction date
- Amount
- Description/remittance info
- Counterparty name
- Booking status
- Processing status (unprocessed/processed/matched)

---

## Step 6: Test API Endpoints Directly

### Using Browser/Postman:

While logged in to Facturino:

**Get Accounts:**
```
GET https://www.facturino.mk/api/v1/banking/accounts
```
Expected response:
```json
{
  "data": [{
    "id": 1,
    "bank_name": "NLB Banka AD Skopje",
    "account_number": "...",
    "iban": "...",
    "current_balance": 0,
    "currency": "MKD",
    "sync_status": "connected",
    "last_sync_at": "2025-11-09T21:00:00Z"
  }]
}
```

**Get Transactions:**
```
GET https://www.facturino.mk/api/v1/banking/transactions
```
Expected response:
```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "total": 0
  }
}
```
(Empty array is normal if sandbox has no transactions)

**Trigger Manual Sync:**
```
POST https://www.facturino.mk/api/v1/banking/accounts/{account_id}/sync
```
Expected response:
```json
{
  "message": "Sync started successfully",
  "account_id": 1
}
```

---

## Step 7: Test Error Handling

### Test Expired Token:

1. **Wait for token to expire** (usually 1 hour)
2. **Try to sync transactions**
3. **Should see**: Token auto-refreshes using refresh_token
4. **Check logs** for: "PSD2 token refreshed"

### Test Disconnection:

1. **Revoke bank connection** (if available in UI)
2. **Try to fetch transactions**
3. **Should see**: Error message about disconnected bank
4. **Reconnect** should work again

---

## Step 8: Production Readiness Checklist

Before using with real bank account:

- [ ] Sandbox connection successful
- [ ] Accounts fetched correctly
- [ ] Token refresh working
- [ ] Error handling graceful
- [ ] Remove debug routes (`/debug/*`)
- [ ] Set environment variables:
  ```bash
  NLB_ENVIRONMENT=production
  NLB_CLIENT_ID=your_production_client_id
  NLB_CLIENT_SECRET=your_production_client_secret
  ```
- [ ] Register production redirect URI in NLB portal:
  ```
  https://www.facturino.mk/api/v1/banking/oauth/callback/nlb
  ```
- [ ] Test with small amount first
- [ ] Verify transaction matching accuracy

---

## Common Issues & Solutions

### Issue: No transactions showing

**Cause**: Sandbox has no test data
**Solution**:
1. Contact NLB to request test data
2. Or switch to production after thorough testing

### Issue: Sync button doesn't work

**Cause**: Sync job not implemented
**Solution**: Check line 329 in BankingController.php is uncommented

### Issue: Transactions not matching invoices

**Cause**: Matching algorithm needs tuning
**Solution**: Check transaction description contains invoice number

### Issue: Token expired errors

**Cause**: Refresh token not working
**Solution**: Check refresh token is saved and valid

---

## Advanced Testing: API Logs

### Check Laravel Logs for PSD2 Calls:

```
https://www.facturino.mk/debug/logs
```

Look for these entries:

**Successful Account Fetch:**
```
[production.INFO] Fetching accounts from PSD2 API
[production.INFO] PSD2 API response received (status: 200)
[production.INFO] Accounts extracted from response (count: 1)
```

**Failed API Call:**
```
[production.ERROR] PSD2 API returned error (status: 401)
[production.ERROR] Failed to fetch accounts
```

**Token Refresh:**
```
[production.INFO] PSD2 token refreshed
```

---

## Real-World Usage Scenario

### Typical Day with Bank Integration:

**Morning:**
1. System auto-syncs overnight transactions (via cron job)
2. You login to Facturino
3. Banking dashboard shows new transactions
4. Matched transactions are highlighted in green
5. Unmatched transactions need manual review

**During Day:**
1. Customer calls: "Did you receive my payment?"
2. You check Banking → Transactions
3. Search for customer name or amount
4. Confirm payment received
5. System has already marked invoice as paid

**Month End:**
1. Reconcile bank statement with Facturino
2. All matched transactions are already done
3. Only handle edge cases/unmatched items
4. Export reconciliation report

---

## Next Steps After Successful Test

1. **Implement Transaction Sync Job** (currently TODO)
2. **Implement Matching Algorithm** (basic structure exists)
3. **Add Webhook Support** (for real-time transaction notifications)
4. **Add Bank Reconciliation Report** (compare Facturino vs Bank)
5. **Add Multi-Currency Support** (if needed)
6. **Add Transaction Categories** (for expense tracking)

---

## Support Contacts

**NLB Bank Developer Support:**
- Developer Portal: https://developer-ob.nlb.mk/
- Registration required for support access

**Facturino Support:**
- Check Laravel logs: `/debug/logs`
- Check database: `/debug/bank-accounts` and `/debug/bank-tokens`
- Review this guide for troubleshooting

---

## Security Notes

- ✅ Access tokens stored encrypted in database
- ✅ OAuth flow uses PKCE for security
- ✅ Redirect URI validated by bank
- ✅ Tokens auto-refresh before expiry
- ⚠️ Remove `/debug/*` routes in production
- ⚠️ Never commit credentials to git
- ⚠️ Use environment variables for secrets

---

## Success Criteria

You'll know the integration is working when:

1. ✅ Bank account appears in dashboard
2. ✅ Balance displays correctly (or 0 in sandbox)
3. ✅ Sync button triggers API calls (check logs)
4. ✅ Transactions appear after sync (if sandbox has data)
5. ✅ No errors in Laravel logs
6. ✅ Token refresh works automatically
7. ✅ Can disconnect and reconnect bank

**If all above are ✅, your bank integration is working correctly!**

The lack of transactions in sandbox is normal - the integration itself is functional.
