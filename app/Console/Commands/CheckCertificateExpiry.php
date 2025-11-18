<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Check Certificate Expiry Command
 *
 * Checks for expiring QES certificates and sends alerts to company owners
 * Runs daily via scheduler to warn about certificates expiring within 30 days
 * CLAUDE-CHECKPOINT
 */
class CheckCertificateExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring QES certificates and send alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expiring certificates...');

        // Check if certificates table exists
        if (! DB::getSchemaBuilder()->hasTable('certificates')) {
            $this->warn('Certificates table does not exist yet. Skipping check.');

            return 0;
        }

        // Find certificates expiring within 30 days
        $expiringCerts = DB::table('certificates')
            ->where('expires_at', '<=', Carbon::now()->addDays(30))
            ->where('expires_at', '>', Carbon::now())
            ->get();

        if ($expiringCerts->isEmpty()) {
            $this->info('No expiring certificates found.');

            return 0;
        }

        $this->warn("Found {$expiringCerts->count()} expiring certificate(s).");

        foreach ($expiringCerts as $cert) {
            $daysLeft = Carbon::now()->diffInDays(Carbon::parse($cert->expires_at));
            $certName = $cert->name ?? 'Unnamed';

            $this->line("Certificate ID {$cert->id}: {$certName} expires in {$daysLeft} days");

            // Try to find the company and owner
            try {
                $company = DB::table('companies')->where('id', $cert->company_id)->first();

                if (! $company) {
                    $this->error("  Company not found for certificate {$cert->id}");

                    continue;
                }

                // Find the company owner
                $owner = DB::table('users')->where('id', $company->owner_id)->first();

                if (! $owner) {
                    $this->error("  Owner not found for company {$company->id}");

                    continue;
                }

                // Send email notification
                $this->sendExpiryWarning($cert, $company, $owner, $daysLeft);

                $this->info("  ✓ Email sent to {$owner->email}");

            } catch (\Exception $e) {
                $this->error("  Failed to process certificate {$cert->id}: {$e->getMessage()}");
                \Log::error('Certificate expiry check failed', [
                    'certificate_id' => $cert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed {$expiringCerts->count()} expiring certificate(s).");

        return 0;
    }

    /**
     * Send certificate expiry warning email
     */
    protected function sendExpiryWarning($cert, $company, $owner, int $daysLeft): void
    {
        try {
            // Use a simple mail approach without requiring a Mailable class
            Mail::raw(
                $this->buildEmailBody($cert, $company, $daysLeft),
                function ($message) use ($owner, $daysLeft) {
                    $message->to($owner->email)
                        ->subject("⚠️ QES Certificate Expiring in {$daysLeft} Days - Action Required");
                }
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send certificate expiry email', [
                'certificate_id' => $cert->id,
                'owner_email' => $owner->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build email body text
     */
    protected function buildEmailBody($cert, $company, int $daysLeft): string
    {
        $urgency = $daysLeft <= 7 ? '🚨 URGENT' : '⚠️ WARNING';
        $expiryDate = Carbon::parse($cert->expires_at)->format('Y-m-d H:i:s');

        return <<<EMAIL
{$urgency}: QES Certificate Expiring Soon

Dear {$company->name},

This is an automated reminder that your Qualified Electronic Signature (QES) certificate is expiring soon.

Certificate Details:
- Certificate Name: {$cert->name}
- Certificate ID: {$cert->id}
- Expires On: {$expiryDate}
- Days Remaining: {$daysLeft} days

Action Required:
You need to renew your QES certificate before it expires to continue signing e-invoices and submitting them to the tax authority.

What to do:
1. Contact your certificate provider to renew your certificate
2. Once renewed, upload the new certificate in Facturino:
   - Go to Settings > E-Invoice > Certificates
   - Upload your new PFX/P12 certificate file
3. Verify the certificate is working by signing a test invoice

If your certificate expires without renewal:
- You will not be able to sign e-invoices
- Invoice submissions to the tax authority will fail
- Your business operations may be disrupted

Need Help?
- Email: support@facturino.mk
- Documentation: https://docs.facturino.mk/certificates

Best regards,
Facturino Monitoring System
EMAIL;
    }
}
// CLAUDE-CHECKPOINT
