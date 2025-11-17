# Facturino Documentation - Sample Sections
**Version:** 1.0
**Date:** November 2025

This document contains fully-written sample sections from both the User Manual and Administrator Manual to demonstrate the writing style, formatting, and level of detail.

---

# USER MANUAL SAMPLE SECTIONS

## Sample Section 1: Creating Your First Invoice (from Part 4)

### 4.1 Creating Invoices

Creating an invoice in Facturino is straightforward and takes less than 2 minutes once you're familiar with the process. This section will walk you through creating your first invoice step by step.

#### Before You Begin

Make sure you have:
- ✓ At least one customer in your system
- ✓ At least one item/service in your catalog
- ✓ Your company information configured
- ✓ Invoice number sequence set up (this happens automatically)

💡 **Tip:** If you haven't added customers or items yet, see Chapter 2 (Customer Management) and Chapter 3 (Items & Inventory) first.

---

#### Step 1: Navigate to Invoices

1. From the main dashboard, click **Invoices** in the left sidebar
2. Click the **+ New Invoice** button in the top right corner

The invoice creation form will appear.

**[SCREENSHOT: Invoice menu item highlighted + New Invoice button]**

---

#### Step 2: Select a Customer

At the top of the form, you'll see the **Customer** field.

1. Click the **Customer** dropdown
2. Start typing your customer's name or tax ID
3. Select the customer from the list
4. If the customer doesn't exist, click **+ Add New Customer** to create one quickly

When you select a customer, Facturino automatically fills in:
- Customer's billing address
- Email address (for sending the invoice)
- Default payment terms (if configured)
- Tax exemption status (if applicable)

**[SCREENSHOT: Customer dropdown with autocomplete, showing search results]**

💡 **Tip:** You can create customers on-the-fly without leaving the invoice form by clicking the **+ Add New Customer** link.

---

#### Step 3: Set Invoice Details

Configure the invoice header information:

**Invoice Number**
- Auto-generated based on your sequence (e.g., FAK-2025-0001)
- You can customize it if needed
- Must be unique within your company

**Invoice Date**
- Defaults to today's date
- Click the calendar icon to choose a different date
- Format: DD.MM.YYYY (Macedonian standard)

**Due Date**
- Automatically calculated based on payment terms (e.g., Net 30)
- You can override by clicking the calendar icon
- Facturino will highlight overdue invoices after this date

**Payment Terms**
- Select from predefined terms: Due on Receipt, Net 15, Net 30, Net 60
- Or create custom terms (e.g., "30% deposit, balance Net 30")

**Reference Number** (optional)
- Your internal reference (PO number, project code, etc.)
- Appears on the invoice and helps with tracking

**[SCREENSHOT: Invoice header fields with date pickers and dropdowns]**

---

#### Step 4: Add Line Items

This is where you specify what you're billing for. Each line item represents a product or service.

1. Click **+ Add Item** in the line items section
2. Select an item from your catalog, or type a custom description
3. Enter the quantity
4. The unit price is pre-filled from your catalog (you can override it)
5. The line total calculates automatically

**Line Item Fields:**

- **Item/Description:** What you're selling (e.g., "Консалтинг услуги")
- **Quantity:** How many units (e.g., 10)
- **Unit:** Unit of measure (e.g., "час" for hours, "парче" for pieces)
- **Price:** Unit price (e.g., 2500.00 MKD)
- **Discount:** Optional line-level discount (percentage or fixed amount)
- **Tax:** VAT rate (18%, 5%, or 0%)
- **Total:** Auto-calculated (Quantity × Price - Discount + Tax)

**[SCREENSHOT: Line items section with one item added, showing all fields]**

**Adding Multiple Items:**
- Click **+ Add Item** again to add more lines
- Reorder items by dragging the handle icon (⋮⋮)
- Delete items by clicking the trash icon

💡 **Tip:** Use the Tab key to quickly move between fields when entering multiple line items.

---

#### Step 5: Apply Discounts (Optional)

You can apply discounts at two levels:

**Line-Level Discount:**
- Applied to individual items
- Can be a percentage (e.g., 10%) or fixed amount (e.g., 100 MKD)
- Enter in the "Discount" column for each item

**Invoice-Level Discount:**
- Applied to the entire invoice subtotal
- Scroll to the bottom of the invoice
- Enter discount percentage or amount in the "Discount" field
- The discount applies after all line items are totaled

**Example:**
```
Line items subtotal: 10,000 MKD
Invoice discount (10%): -1,000 MKD
Subtotal after discount: 9,000 MKD
VAT (18%): +1,620 MKD
Total: 10,620 MKD
```

**[SCREENSHOT: Invoice totals section showing discount calculation]**

---

#### Step 6: Review Totals

The invoice automatically calculates:

- **Subtotal:** Sum of all line items (before tax and discount)
- **Discount:** Total discount applied
- **Tax:** VAT calculated at 18%, 5%, or 0% per item
- **Total:** Final amount due (Subtotal - Discount + Tax)

All amounts are in Macedonian Denars (MKD) by default.

⚠️ **Important:** Double-check the totals before sending. Common mistakes:
- Forgetting to apply the correct VAT rate
- Applying discount twice (both line-level and invoice-level)
- Using wrong unit price

**[SCREENSHOT: Invoice totals breakdown panel]**

---

#### Step 7: Add Notes and Terms (Optional)

At the bottom of the invoice, you can add:

**Notes (Public)**
- Visible to the customer on the invoice
- Examples: "Плаќање во рок од 30 дена" (Payment within 30 days)
- "Ви благодариме за вашиот бизнис!" (Thank you for your business!)

**Private Notes (Internal)**
- Only visible to you and your team
- Examples: "Customer requested 10% discount"
- "Rushed order - priority shipping"

**Terms & Conditions**
- Standard legal terms
- Pre-filled from Settings → Invoice Settings
- You can override per invoice if needed

**[SCREENSHOT: Notes and terms section at bottom of invoice]**

---

#### Step 8: Save as Draft or Send

You have three options:

**1. Save as Draft**
- Click **Save Draft**
- Invoice is saved but not sent to customer
- Status: DRAFT
- You can edit it anytime
- Invoice number is reserved

**2. Save and Send**
- Click **Save & Send**
- Invoice is saved and immediately emailed to customer
- Status: SENT
- A confirmation dialog appears
- Email preview is shown before sending

**3. Save and Download PDF**
- Click **Save & Download**
- Invoice is saved
- PDF automatically downloads to your computer
- You can send it manually via your email client

**[SCREENSHOT: Action buttons at bottom - Save Draft, Save & Send, Save & Download]**

---

#### Step 9: Send the Invoice

If you chose **Save & Send**, a dialog appears:

1. **Email Address**
   - Pre-filled with customer's email
   - You can add multiple recipients (comma-separated)
   - CC yourself or your accountant

2. **Email Subject**
   - Default: "Invoice FAK-2025-0001 from [Your Company]"
   - Customizable

3. **Email Message**
   - Pre-filled template
   - Editable
   - Supports Macedonian and English

4. **Attachments**
   - Invoice PDF is automatically attached
   - You can attach additional files (contracts, delivery notes)

5. Click **Send Email**

**[SCREENSHOT: Send invoice email dialog with preview]**

✓ **Success!** You'll see a confirmation: "Invoice sent successfully to customer@example.mk"

The invoice status changes to **SENT** and appears in your invoice list.

---

#### What Happens Next?

**Customer Receives Email:**
- They get a professional email with your branding
- PDF invoice is attached
- They can click a link to view the invoice online
- If enabled, they can pay directly via the payment link

**Invoice Tracking:**
- When the customer opens the email, status changes to **VIEWED**
- When they pay, status changes to **PAID** (if payment is recorded)
- You can see all activity in the invoice detail view

**Payment Reminders:**
- Facturino can automatically send reminders before/after the due date
- Configure in Settings → Invoice Settings → Reminders

**[SCREENSHOT: Invoice detail view showing activity timeline]**

---

#### Quick Tips for Invoice Creation

💡 **Use Keyboard Shortcuts:**
- `Ctrl + S` (or `Cmd + S` on Mac): Save draft
- `Ctrl + Enter`: Save and send
- `Tab`: Move to next field
- `Esc`: Cancel and return to invoice list

💡 **Clone Invoices:**
- To create similar invoices quickly, click the **Clone** button on any existing invoice
- All details are copied; just change the customer and date

💡 **Save Favorite Items:**
- Frequently used items can be marked as favorites for quick access
- Click the star icon next to item name

💡 **Create Invoice Templates:**
- For recurring customers with standard items, create invoice templates
- Go to Settings → Templates to set up

💡 **Set Default Payment Terms:**
- Go to Settings → Company Settings
- Set default payment terms (e.g., Net 30)
- New invoices will use this by default

---

#### Common Invoice Creation Mistakes

❌ **Wrong VAT Rate**
- **Problem:** Applying 18% VAT when service is exempt
- **Solution:** Check Macedonian tax regulations; some services are 0% VAT

❌ **Incorrect Due Date**
- **Problem:** Due date before invoice date
- **Solution:** Facturino will warn you; adjust the due date

❌ **Missing Customer Email**
- **Problem:** Can't send invoice because customer has no email
- **Solution:** Add email to customer record before sending

❌ **Duplicate Invoice Numbers**
- **Problem:** Manually entering a number that already exists
- **Solution:** Use auto-numbering; if manual is required, check existing invoices first

❌ **Decimal Errors**
- **Problem:** Entering 2500 instead of 25.00 for price
- **Solution:** Always use decimal point (.) for cents; Macedonian format: 2.500,00

---

#### Next Steps

Now that you've created your first invoice:
- Learn about **Recurring Invoices** (Section 4.5) for subscription billing
- Set up **Payment Gateways** (Chapter 6) to accept online payments
- Configure **E-Faktura** (Chapter 5) for electronic tax-compliant invoices
- Explore **Invoice Reports** (Chapter 10) to track your sales

---

## Sample Section 2: E-Faktura Certificate Setup (from Part 5)

### 5.2 Certificate Setup

To generate and sign electronic invoices (E-Faktura) in Macedonia, you need a Qualified Electronic Signature (QES) certificate. This section explains how to obtain and configure your certificate in Facturino.

#### What is a QES Certificate?

A QES (Qualified Electronic Signature) certificate is a digital credential that:
- Legally identifies you or your company
- Allows you to digitally sign documents
- Is recognized by the Macedonian government
- Meets EU eIDAS standards for electronic signatures

Think of it like a digital stamp or seal that proves the invoice came from you and hasn't been tampered with.

---

#### Obtaining a QES Certificate

**Step 1: Choose a Certificate Authority (CA)**

In Macedonia, QES certificates are issued by approved Certificate Authorities:

- **KIBS (Kibernetic Security)** - https://kibs.com.mk
- **Makedonski Telekom** - https://telekom.mk/ca
- **One** - https://one.com.mk/services/ca

💡 **Tip:** Prices range from €50-150 per year. Check with your accountant or IT provider for recommendations.

**Step 2: Prepare Required Documents**

You'll need:
- Company registration documents (ЕДБ number)
- Authorized signatory ID (passport or ID card)
- Proof of business address
- Company seal (печат)
- Bank account verification

**Step 3: Apply for the Certificate**

1. Visit the CA's website or office
2. Fill out the application form
3. Submit required documents
4. Pay the certificate fee
5. Complete identity verification (in-person or video call)

**Step 4: Receive Your Certificate**

The CA will provide:
- Certificate file (.pfx or .p12 format)
- Certificate password (keep this secure!)
- Installation instructions
- Certificate validity period (usually 1-3 years)

⚠️ **Security Warning:** Never share your certificate file or password. Treat it like your bank password.

---

#### Certificate File Formats

Facturino supports the following certificate formats:

| Format | Extension | Description |
|--------|-----------|-------------|
| **PKCS#12** | .pfx, .p12 | Most common; includes certificate + private key |
| **PEM** | .pem | Text-based format; may need separate key file |

Most Macedonian CAs provide certificates in .pfx or .p12 format, which is recommended.

---

#### Uploading Your Certificate to Facturino

**Step 1: Access Certificate Management**

1. Log in to Facturino as an administrator
2. Navigate to **Settings** → **E-Faktura** → **Certificates**
3. Click **+ Upload Certificate**

**[SCREENSHOT: Settings menu → E-Faktura → Certificates page]**

---

**Step 2: Upload Certificate File**

1. Click **Choose File**
2. Select your certificate file (.pfx or .p12)
3. Enter the certificate password in the **Password** field
4. Add a friendly name (e.g., "Company QES Cert 2025-2028")
5. Click **Upload & Validate**

**[SCREENSHOT: Certificate upload form with file picker and password field]**

---

**Step 3: Validation**

Facturino will validate your certificate:

✓ **Certificate is valid**
- File format is correct
- Password is correct
- Certificate is not expired
- Certificate includes private key
- Certificate is issued by a recognized CA

If validation fails, you'll see an error message:

❌ **Common Validation Errors:**

**"Invalid password"**
- Double-check the password provided by your CA
- Passwords are case-sensitive
- Ensure no extra spaces

**"Certificate expired"**
- Check the certificate validity dates
- You need to renew with your CA

**"Missing private key"**
- Certificate file doesn't include the private key
- Contact your CA for the correct file

**"Unsupported format"**
- Convert to .pfx or .p12 format
- Use OpenSSL tool if needed

---

**Step 4: Certificate Details**

After successful upload, you'll see certificate details:

- **Issued To:** Your company name (as registered)
- **Issued By:** Certificate Authority name
- **Valid From:** Start date
- **Valid Until:** Expiry date
- **Serial Number:** Unique certificate identifier
- **Signature Algorithm:** Usually RSA-SHA256 or ECDSA

**[SCREENSHOT: Certificate details view showing all metadata]**

✓ **Success!** Your certificate is now ready to sign invoices.

---

#### Setting the Default Certificate

If you have multiple certificates (e.g., different authorized signatories):

1. Go to **Settings** → **E-Faktura** → **Certificates**
2. Click the star icon (⭐) next to your preferred certificate
3. This certificate will be used by default for signing invoices

You can override the default when signing individual invoices.

---

#### Certificate Expiry Notifications

Facturino automatically monitors your certificate expiry:

- **90 days before expiry:** Warning notification
- **30 days before expiry:** Urgent notification (daily)
- **7 days before expiry:** Critical alert (email to admin)
- **On expiry date:** Certificate is deactivated

To renew:
1. Contact your CA 30-60 days before expiry
2. Obtain new certificate
3. Upload to Facturino
4. Old certificate remains archived for historical invoices

💡 **Tip:** Set a calendar reminder 60 days before expiry to start the renewal process.

---

#### Certificate Security Best Practices

🔒 **Storage:**
- Certificates are encrypted at rest in Facturino
- Only administrators can view certificate details
- Private keys never leave the server

🔒 **Access Control:**
- Limit administrator access to trusted staff
- Use two-factor authentication (2FA) for admin accounts
- Regularly review who has access to certificates

🔒 **Backup:**
- Keep a backup of your certificate file in a secure location
- Store password separately (password manager or safe)
- Test backup restoration annually

🔒 **Password Security:**
- Use a strong, unique password for your certificate
- Don't reuse passwords from other systems
- Change password if compromise is suspected

---

#### Troubleshooting Certificate Issues

**Problem: "Certificate validation failed"**

**Cause:** Multiple possible causes
**Solutions:**
1. Verify the password is correct
2. Ensure file is not corrupted (re-download from CA)
3. Check file format (.pfx or .p12)
4. Try uploading from a different browser
5. Contact Facturino support with the error message

---

**Problem: "Certificate expired" (but it shouldn't be)**

**Cause:** Server time may be incorrect
**Solutions:**
1. Check system time on your server
2. Verify timezone is set correctly
3. Sync with NTP server
4. Contact your system administrator

---

**Problem: Signed invoices rejected by tax authority**

**Cause:** Certificate not recognized or signature invalid
**Solutions:**
1. Verify certificate is from an approved Macedonian CA
2. Check certificate is still valid (not expired or revoked)
3. Ensure certificate includes company ЕДБ number
4. Test with sample invoice XML
5. Contact tax authority (Министерство за финансии)

---

#### Certificate Revocation

If your certificate is compromised (password leaked, unauthorized access):

1. **Immediately:**
   - Contact your CA to revoke the certificate
   - Disable the certificate in Facturino

2. **In Facturino:**
   - Go to Settings → E-Faktura → Certificates
   - Click the certificate
   - Click **Deactivate**
   - Select reason: "Security compromise"

3. **Obtain a new certificate:**
   - Apply for a new certificate from your CA
   - Upload to Facturino
   - Re-sign any invoices signed with the old certificate (if required)

4. **Notify stakeholders:**
   - Inform your tax authority if required
   - Notify customers if invoices are affected

---

#### Advanced: Converting Certificate Formats

If you have a certificate in a different format, you can convert it using OpenSSL:

**PEM to PFX:**
```bash
openssl pkcs12 -export -out certificate.pfx -inkey private.key -in certificate.crt
```

**PFX to PEM:**
```bash
openssl pkcs12 -in certificate.pfx -out certificate.pem -nodes
```

📝 **Note:** These commands require OpenSSL installed on your computer. If you're not comfortable with command-line tools, ask your IT administrator or contact Facturino support.

---

#### Next Steps

Now that your certificate is configured:
- Learn how to **Generate E-Invoices** (Section 5.3)
- Set up **Automatic E-Invoice Signing** (Section 5.4)
- Configure **Tax Authority Submission** (Section 5.5)
- Review **E-Faktura Compliance** (Section 5.6)

---

# ADMINISTRATOR MANUAL SAMPLE SECTIONS

## Sample Section 3: Queue & Background Jobs Configuration (from Part 8)

### 8.1 Queue Configuration

Facturino uses Laravel's queue system to process time-consuming tasks in the background, such as sending emails, generating PDFs, submitting e-invoices, and syncing bank transactions. Proper queue configuration is critical for application performance and reliability.

#### Understanding Queues

**What are queues?**
Queues allow the application to defer time-consuming tasks to be processed asynchronously. Instead of making the user wait, the task is added to a queue and processed by background workers.

**Benefits:**
- Faster response times for users
- Better resource utilization
- Fault tolerance (retry failed jobs)
- Scalability (add more workers as needed)

**When are queues used in Facturino?**
- Sending invoice emails
- Generating invoice PDFs
- Submitting e-invoices to tax authority
- Syncing bank transactions
- Generating reports
- Processing CSV imports
- Backing up data

---

#### Choosing a Queue Driver

Facturino supports multiple queue drivers. Choose based on your deployment size and requirements:

| Driver | Best For | Pros | Cons |
|--------|----------|------|------|
| **Database** | Small deployments (1-10 users) | Simple, no extra services | Slower, not scalable |
| **Redis** | Production (recommended) | Fast, reliable, scalable | Requires Redis server |
| **Amazon SQS** | AWS deployments | Managed, scalable | Costs, AWS lock-in |
| **Beanstalkd** | Legacy systems | Fast | Less common, maintenance |

**Recommendation:** Use **Redis** for production deployments with more than 5 concurrent users.

---

#### Database Queue Setup (Simple)

Best for: Development, testing, small deployments

**Step 1: Configure .env**

```bash
# .env
QUEUE_CONNECTION=database
```

**Step 2: Create queue tables**

```bash
php artisan queue:table
php artisan migrate
```

This creates two tables:
- `jobs` - Pending jobs
- `failed_jobs` - Failed jobs for retry

**Step 3: Start queue worker**

```bash
php artisan queue:work --queue=default,high,low --tries=3 --timeout=300
```

**Parameters:**
- `--queue=default,high,low` - Process queues in order of priority
- `--tries=3` - Retry failed jobs up to 3 times
- `--timeout=300` - Kill jobs that run longer than 5 minutes

⚠️ **Important:** The queue worker must run continuously. If it stops, jobs won't be processed.

---

#### Redis Queue Setup (Recommended)

Best for: Production deployments

**Prerequisites:**
- Redis server installed and running
- PHP Redis extension installed

**Step 1: Install Redis**

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

**Verify Redis is running:**
```bash
redis-cli ping
# Should return: PONG
```

**Step 2: Install PHP Redis Extension**

```bash
sudo apt install php-redis
sudo systemctl restart php8.1-fpm
```

**Verify:**
```bash
php -m | grep redis
# Should return: redis
```

**Step 3: Configure .env**

```bash
# .env
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

**For remote Redis:**
```bash
REDIS_HOST=your-redis-server.com
REDIS_PASSWORD=your-secure-password
REDIS_PORT=6379
```

**Step 4: Test Redis Connection**

```bash
php artisan tinker
> Redis::connection()->ping();
# Should return: "PONG"
```

**Step 5: Configure Queue Settings**

Edit `config/queue.php`:

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 300,
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

**Step 6: Start Queue Workers**

```bash
php artisan queue:work redis --queue=high,default,low --tries=3 --timeout=300 --sleep=3 --max-jobs=1000 --max-time=3600
```

**Parameters explained:**
- `redis` - Use Redis driver
- `--queue=high,default,low` - Process priority queues first
- `--tries=3` - Retry failed jobs 3 times
- `--timeout=300` - Kill jobs after 5 minutes
- `--sleep=3` - Wait 3 seconds when queue is empty
- `--max-jobs=1000` - Restart worker after 1000 jobs (prevents memory leaks)
- `--max-time=3600` - Restart worker after 1 hour

---

#### Queue Priority

Facturino uses three queue priorities:

1. **high** - Critical tasks
   - E-invoice submissions (time-sensitive)
   - Payment processing
   - Real-time notifications

2. **default** - Standard tasks
   - Sending emails
   - PDF generation
   - Bank transaction sync

3. **low** - Background tasks
   - Report generation
   - Data exports
   - Cleanup jobs

**Dispatching to specific queues:**

```php
// In application code
SendInvoiceEmail::dispatch($invoice)->onQueue('high');
GeneratePdfJob::dispatch($invoice)->onQueue('default');
CleanupLogsJob::dispatch()->onQueue('low');
```

---

#### Supervisor Configuration

To keep queue workers running continuously, use Supervisor (process manager).

**Step 1: Install Supervisor**

```bash
sudo apt install supervisor
```

**Step 2: Create Supervisor Configuration**

Create file: `/etc/supervisor/conf.d/facturino-worker.conf`

```ini
[program:facturino-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/facturino/artisan queue:work redis --queue=high,default,low --tries=3 --timeout=300 --sleep=3 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/facturino/storage/logs/worker.log
stopwaitsecs=3600
```

**Configuration explained:**
- `process_name` - Unique name per process
- `command` - Queue worker command
- `autostart=true` - Start on boot
- `autorestart=true` - Restart if crashed
- `user=www-data` - Run as web server user
- `numprocs=4` - Run 4 worker processes
- `stdout_logfile` - Log file location
- `stopwaitsecs=3600` - Wait 1 hour for graceful shutdown

**Step 3: Reload Supervisor**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start facturino-worker:*
```

**Step 4: Check Worker Status**

```bash
sudo supervisorctl status facturino-worker:*
```

Expected output:
```
facturino-worker:facturino-worker_00   RUNNING   pid 1234, uptime 0:05:23
facturino-worker:facturino-worker_01   RUNNING   pid 1235, uptime 0:05:23
facturino-worker:facturino-worker_02   RUNNING   pid 1236, uptime 0:05:23
facturino-worker:facturino-worker_03   RUNNING   pid 1237, uptime 0:05:23
```

---

#### Worker Scaling

**How many workers do you need?**

| Users | Jobs/Hour | Recommended Workers |
|-------|-----------|---------------------|
| 1-10 | <100 | 1-2 |
| 10-50 | 100-500 | 2-4 |
| 50-200 | 500-2000 | 4-8 |
| 200+ | 2000+ | 8-16 |

**Monitoring queue depth:**

```bash
# Check jobs in queue
php artisan queue:info

# Output:
# default: 45 jobs
# high: 2 jobs
# low: 103 jobs
```

If queue depth is consistently high, add more workers by increasing `numprocs` in Supervisor config.

---

#### Failed Jobs

Jobs fail for various reasons: network errors, invalid data, third-party service downtime.

**View failed jobs:**

```bash
php artisan queue:failed
```

Output:
```
+--------------------------------------+------------+---------------------+---------------------+
| ID                                   | Connection | Queue               | Failed At           |
+--------------------------------------+------------+---------------------+---------------------+
| 7c428b83-c07e-4e42-8d56-95d7e78d1ef4 | redis      | default             | 2025-11-17 14:23:10 |
| 9a3f2d1c-b2e1-4a7c-9e8d-6f5c4b3a2d1e | redis      | high                | 2025-11-17 15:10:45 |
+--------------------------------------+------------+---------------------+---------------------+
```

**Retry a specific failed job:**

```bash
php artisan queue:retry 7c428b83-c07e-4e42-8d56-95d7e78d1ef4
```

**Retry all failed jobs:**

```bash
php artisan queue:retry all
```

**Delete a failed job:**

```bash
php artisan queue:forget 7c428b83-c07e-4e42-8d56-95d7e78d1ef4
```

**Clear all failed jobs:**

```bash
php artisan queue:flush
```

---

#### Failed Job Notifications

Configure automatic alerts for failed jobs.

**Edit `config/queue.php`:**

```php
'failed' => [
    'driver' => 'database-uuids',
    'database' => env('DB_CONNECTION', 'mysql'),
    'table' => 'failed_jobs',
],
```

**Create event listener:**

In `app/Providers/EventServiceProvider.php`:

```php
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

public function boot()
{
    Queue::failing(function (JobFailed $event) {
        // Log the failure
        Log::error('Job failed', [
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'exception' => $event->exception->getMessage(),
        ]);

        // Send email alert (if critical queue)
        if ($event->job->getQueue() === 'high') {
            Mail::to('admin@example.com')->send(
                new JobFailedNotification($event)
            );
        }
    });
}
```

---

#### Monitoring Queue Health

**Key metrics to monitor:**

1. **Queue Depth** - Number of pending jobs
2. **Processing Rate** - Jobs processed per minute
3. **Failed Job Rate** - Percentage of failed jobs
4. **Average Job Duration** - Time to process jobs
5. **Worker Status** - Number of active workers

**Using Laravel Horizon (Redis only):**

Laravel Horizon provides a dashboard for monitoring Redis queues.

**Installation:**

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

**Configuration:**

Edit `config/horizon.php`:

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'processes' => 4,
            'tries' => 3,
            'timeout' => 300,
        ],
    ],
],
```

**Start Horizon:**

```bash
php artisan horizon
```

**Access Dashboard:**
Navigate to: `https://your-domain.com/horizon`

**Supervisor configuration for Horizon:**

```ini
[program:facturino-horizon]
process_name=%(program_name)s
command=php /var/www/facturino/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/facturino/storage/logs/horizon.log
stopwaitsecs=3600
```

---

#### Queue Performance Optimization

**1. Use Redis instead of database**
- 10-50x faster
- Better for high-volume workloads

**2. Increase worker count**
- Monitor queue depth
- Add workers if backlog grows

**3. Optimize job code**
- Avoid N+1 queries in jobs
- Use chunking for large datasets
- Cache frequently accessed data

**4. Use job batching**
- Group related jobs together
- Reduce overhead

Example:
```php
Bus::batch([
    new ProcessInvoice($invoice1),
    new ProcessInvoice($invoice2),
    new ProcessInvoice($invoice3),
])->dispatch();
```

**5. Set appropriate timeouts**
- Don't set timeout too low (jobs get killed prematurely)
- Don't set too high (hung jobs block workers)
- 300 seconds (5 min) is a good default

---

#### Troubleshooting

**Problem: Queue worker stops running**

**Cause:** Worker crashed, Supervisor not configured
**Solution:**
1. Check Supervisor status: `sudo supervisorctl status`
2. Restart workers: `sudo supervisorctl restart facturino-worker:*`
3. Check logs: `tail -f storage/logs/worker.log`

---

**Problem: Jobs not being processed**

**Cause:** Worker not running, wrong queue connection
**Solution:**
1. Verify worker is running: `ps aux | grep queue:work`
2. Check `.env` QUEUE_CONNECTION setting
3. Test Redis connection: `redis-cli ping`
4. Manually process a job: `php artisan queue:work --once`

---

**Problem: High memory usage**

**Cause:** Memory leaks, not restarting workers
**Solution:**
1. Use `--max-jobs=1000` to restart after 1000 jobs
2. Use `--max-time=3600` to restart after 1 hour
3. Monitor with: `ps aux --sort=-%mem | grep queue`
4. Increase server memory if needed

---

**Problem: Jobs timing out**

**Cause:** Timeout set too low, slow external API
**Solution:**
1. Increase `--timeout` parameter (e.g., 600 for 10 minutes)
2. Optimize job code to run faster
3. Split long jobs into smaller chunks
4. Use job batching for parallel processing

---

#### Next Steps

Now that queues are configured:
- Set up **Email Configuration** (Chapter 7) to send emails via queue
- Configure **E-Invoice Submission Queue** (Chapter 5.5)
- Implement **Job Monitoring** (Chapter 11.4)
- Review **Performance Optimization** (Chapter 11.8)

---

**End of Sample Sections**
