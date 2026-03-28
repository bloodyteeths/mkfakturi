<?php

namespace Modules\Mk\Services;

use App\Mail\PaymentReminder;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Models\ReminderHistory;
use Modules\Mk\Models\ReminderTemplate;

class CollectionService
{
    /**
     * Default Macedonian statutory interest rate:
     * NBRM reference rate (5.25%) + 8% penalty = 13.25%
     * Per Закон за облигациони односи
     */
    private const DEFAULT_ANNUAL_RATE = 13.25;

    /**
     * Get all overdue invoices for a company, enriched with escalation level and interest.
     */
    public function getOverdueInvoices(int $companyId, array $filters = []): array
    {
        $query = Invoice::where('company_id', $companyId)
            ->where('due_amount', '>', 0)
            ->where('due_date', '<', Carbon::today()->format('Y-m-d'))
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID])
            ->with('customer:id,name,email,phone');

        // Apply filters
        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['escalation_level'])) {
            $level = $filters['escalation_level'];
            $today = Carbon::today();

            switch ($level) {
                case 'friendly':
                    $query->where('due_date', '>', $today->copy()->subDays(7)->format('Y-m-d'));
                    break;
                case 'firm':
                    $query->where('due_date', '<=', $today->copy()->subDays(7)->format('Y-m-d'))
                        ->where('due_date', '>', $today->copy()->subDays(30)->format('Y-m-d'));
                    break;
                case 'final':
                    $query->where('due_date', '<=', $today->copy()->subDays(30)->format('Y-m-d'))
                        ->where('due_date', '>', $today->copy()->subDays(60)->format('Y-m-d'));
                    break;
                case 'legal':
                    $query->where('due_date', '<=', $today->copy()->subDays(60)->format('Y-m-d'));
                    break;
            }
        }

        if (! empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', '%' . $search . '%');
                    });
            });
        }

        // Pagination
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 50)));

        $allInvoices = $query->orderBy('due_date', 'asc')->get();
        $total = $allInvoices->count();
        $invoices = $allInvoices->forPage($page, $perPage);

        $today = Carbon::today();
        $annualRate = $this->getInterestRate($companyId);

        // Prefetch reminder history to avoid N+1 queries
        $invoiceIds = $allInvoices->pluck('id')->toArray();
        $reminderCounts = [];
        $lastReminders = [];

        if (! empty($invoiceIds)) {
            $reminderCounts = ReminderHistory::forCompany($companyId)
                ->whereIn('invoice_id', $invoiceIds)
                ->select('invoice_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('invoice_id')
                ->pluck('cnt', 'invoice_id')
                ->toArray();

            $latestIds = ReminderHistory::forCompany($companyId)
                ->whereIn('invoice_id', $invoiceIds)
                ->select('invoice_id', DB::raw('MAX(id) as max_id'))
                ->groupBy('invoice_id')
                ->pluck('max_id')
                ->toArray();

            if (! empty($latestIds)) {
                $lastReminders = ReminderHistory::whereIn('id', $latestIds)
                    ->get()
                    ->keyBy('invoice_id');
            }
        }

        // Build aging buckets from ALL invoices (not just current page)
        $aging = ['0_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];
        $totalInterest = 0;

        $allMapped = $allInvoices->map(function ($invoice) use ($today, $annualRate, &$aging, &$totalInterest) {
            $dueDate = $invoice->due_date instanceof \DateTimeInterface
                ? Carbon::parse($invoice->due_date)
                : Carbon::parse((string) $invoice->due_date);
            $daysOverdue = $dueDate->diffInDays($today);
            $dueAmount = (int) $invoice->due_amount;

            // Aging buckets
            if ($daysOverdue <= 30) {
                $aging['0_30'] += $dueAmount;
            } elseif ($daysOverdue <= 60) {
                $aging['31_60'] += $dueAmount;
            } elseif ($daysOverdue <= 90) {
                $aging['61_90'] += $dueAmount;
            } else {
                $aging['90_plus'] += $dueAmount;
            }

            // Interest: principal * (rate/100/365) * days
            $interest = (int) round($dueAmount * ($annualRate / 100 / 365) * $daysOverdue);
            $totalInterest += $interest;

            return null; // Only for side-effects on aging/interest
        });

        $data = $invoices->map(function ($invoice) use ($today, $annualRate, $reminderCounts, $lastReminders) {
            $dueDate = $invoice->due_date instanceof \DateTimeInterface
                ? Carbon::parse($invoice->due_date)
                : Carbon::parse((string) $invoice->due_date);

            $daysOverdue = $dueDate->diffInDays($today);

            // Determine escalation level
            if ($daysOverdue > 60) {
                $escalation = 'legal';
            } elseif ($daysOverdue > 30) {
                $escalation = 'final';
            } elseif ($daysOverdue >= 7) {
                $escalation = 'firm';
            } else {
                $escalation = 'friendly';
            }

            $lastReminder = $lastReminders[$invoice->id] ?? null;

            // Interest calculation
            $dueAmount = (int) $invoice->due_amount;
            $interest = (int) round($dueAmount * ($annualRate / 100 / 365) * $daysOverdue);

            // Cooldown: can send if no reminder in last 24h
            $canSend = true;
            if ($lastReminder && $lastReminder->sent_at) {
                $canSend = $lastReminder->sent_at->lt(now()->subHours(24));
            }

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date instanceof \DateTimeInterface
                    ? $invoice->invoice_date->format('Y-m-d')
                    : (string) $invoice->invoice_date,
                'due_date' => $dueDate->format('Y-m-d'),
                'total' => (int) $invoice->total,
                'due_amount' => $dueAmount,
                'interest' => $interest,
                'total_with_interest' => $dueAmount + $interest,
                'days_overdue' => $daysOverdue,
                'escalation_level' => $escalation,
                'customer_id' => $invoice->customer_id,
                'customer_name' => $invoice->customer?->name,
                'customer_email' => $invoice->customer?->email,
                'last_reminder_at' => $lastReminder?->sent_at?->format('Y-m-d H:i'),
                'last_reminder_level' => $lastReminder?->escalation_level,
                'reminder_count' => $reminderCounts[$invoice->id] ?? 0,
                'can_send' => $canSend,
            ];
        })->values()->toArray();

        return [
            'invoices' => $data,
            'aging' => $aging,
            'interest_rate' => $annualRate,
            'total_interest' => $totalInterest,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Send a payment reminder for a specific invoice.
     */
    public function sendReminder(int $companyId, int $invoiceId, string $level, ?string $email = null, bool $attachOpomena = false): array
    {
        // Rate limit: max 1 reminder per invoice per 24 hours
        $recentReminder = ReminderHistory::forCompany($companyId)
            ->forInvoice($invoiceId)
            ->where('sent_at', '>=', now()->subHours(24))
            ->first();

        if ($recentReminder) {
            throw new \InvalidArgumentException('A reminder was already sent for this invoice in the last 24 hours.');
        }

        $invoice = Invoice::where('company_id', $companyId)
            ->where('id', $invoiceId)
            ->with('customer', 'company')
            ->first();

        if (! $invoice) {
            throw new \InvalidArgumentException('Invoice not found.');
        }

        // Use provided email or fall back to customer email
        $recipientEmail = $email ?: ($invoice->customer?->email);
        if (! $recipientEmail) {
            throw new \InvalidArgumentException('No email address provided.');
        }

        // Get template for this level
        $template = ReminderTemplate::forCompany($companyId)
            ->active()
            ->where('escalation_level', $level)
            ->first();

        if (! $template) {
            // Seed defaults and try again
            $this->seedDefaults($companyId);
            $template = ReminderTemplate::forCompany($companyId)
                ->active()
                ->where('escalation_level', $level)
                ->first();
        }

        if (! $template) {
            throw new \InvalidArgumentException('No active template found for level: ' . $level);
        }

        // Determine locale from company settings
        $locale = CompanySetting::getSetting('language', $companyId) ?: 'mk';
        // Normalize locale to our 4 supported ones
        if (! in_array($locale, ['mk', 'en', 'tr', 'sq'])) {
            $locale = 'mk';
        }

        // Generate Опомена PDF if requested
        $opomenaPdfContent = null;
        if ($attachOpomena) {
            $opomenaData = $this->getOpomenaData($companyId, $invoiceId);
            $opomenaPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('app.pdf.reports.opomena', $opomenaData);
            $opomenaPdf->setPaper('A4', 'portrait');
            $opomenaPdfContent = $opomenaPdf->output();
        }

        // Send the email
        Mail::to($recipientEmail)
            ->send(new PaymentReminder($invoice, $invoice->customer, $template, $level, $locale, $opomenaPdfContent));

        // Record in history
        $history = ReminderHistory::create([
            'company_id' => $companyId,
            'invoice_id' => $invoiceId,
            'customer_id' => $invoice->customer_id,
            'template_id' => $template->id,
            'escalation_level' => $level,
            'sent_at' => now(),
            'sent_via' => 'email',
            'amount_due' => (int) $invoice->due_amount,
        ]);

        return [
            'success' => true,
            'history_id' => $history->id,
            'sent_to' => $recipientEmail,
            'level' => $level,
            'invoice_number' => $invoice->invoice_number,
        ];
    }

    /**
     * Get reminder templates for a company.
     * Seeds defaults if none exist.
     */
    public function getTemplates(int $companyId): array
    {
        $templates = ReminderTemplate::forCompany($companyId)
            ->orderBy('days_after_due', 'asc')
            ->get();

        if ($templates->isEmpty()) {
            $this->seedDefaults($companyId);
            $templates = ReminderTemplate::forCompany($companyId)
                ->orderBy('days_after_due', 'asc')
                ->get();
        }

        return $templates->toArray();
    }

    /**
     * Seed default reminder templates for a company.
     * Creates 4 escalation levels with default texts.
     */
    public function seedDefaults(int $companyId): void
    {
        $defaults = [
            [
                'escalation_level' => 'friendly',
                'days_after_due' => 3,
                'subject_mk' => 'Потсетник за фактура {INVOICE_NUMBER}',
                'subject_en' => 'Reminder for invoice {INVOICE_NUMBER}',
                'subject_tr' => 'Fatura {INVOICE_NUMBER} icin hatirlatma',
                'subject_sq' => 'Kujtese per faturen {INVOICE_NUMBER}',
                'body_mk' => '<p>Почитувани,</p><p>Ве потсетуваме дека фактурата <strong>{INVOICE_NUMBER}</strong> со износ од <strong>{AMOUNT_DUE}</strong> беше достасана на <strong>{DUE_DATE}</strong>.</p><p>Доколку веќе сте ја извршиле уплатата, ве молиме занемарете го ова известување.</p><p>Со почит</p>',
                'body_en' => '<p>Dear Customer,</p><p>This is a friendly reminder that invoice <strong>{INVOICE_NUMBER}</strong> for <strong>{AMOUNT_DUE}</strong> was due on <strong>{DUE_DATE}</strong>.</p><p>If you have already made the payment, please disregard this notice.</p><p>Best regards</p>',
                'body_tr' => '<p>Sayın Musterimiz,</p><p><strong>{INVOICE_NUMBER}</strong> numarali <strong>{AMOUNT_DUE}</strong> tutarindaki faturanin vadesi <strong>{DUE_DATE}</strong> tarihinde dolmustur.</p><p>Odemeyi zaten yaptıysanız, lutfen bu bildirimi dikkate almayin.</p><p>Saygilarimizla</p>',
                'body_sq' => '<p>I nderuar,</p><p>Ju kujtojme se fatura <strong>{INVOICE_NUMBER}</strong> me shumen <strong>{AMOUNT_DUE}</strong> ka skaduar me <strong>{DUE_DATE}</strong>.</p><p>Nese keni bere pagesen, ju lutem injoroni kete njoftim.</p><p>Me respekt</p>',
            ],
            [
                'escalation_level' => 'firm',
                'days_after_due' => 14,
                'subject_mk' => 'Второ известување за фактура {INVOICE_NUMBER}',
                'subject_en' => 'Second notice for invoice {INVOICE_NUMBER}',
                'subject_tr' => 'Fatura {INVOICE_NUMBER} icin ikinci bildirim',
                'subject_sq' => 'Njoftim i dyte per faturen {INVOICE_NUMBER}',
                'body_mk' => '<p>Почитувани,</p><p>Ова е второ известување за неплатената фактура <strong>{INVOICE_NUMBER}</strong> во износ од <strong>{AMOUNT_DUE}</strong>, достасана на <strong>{DUE_DATE}</strong> (<strong>{DAYS_OVERDUE}</strong> дена задоцнување).</p><p>Ве молиме извршете ја уплатата во најкраток можен рок.</p><p>Со почит</p>',
                'body_en' => '<p>Dear Customer,</p><p>This is a second notice regarding the unpaid invoice <strong>{INVOICE_NUMBER}</strong> for <strong>{AMOUNT_DUE}</strong>, which was due on <strong>{DUE_DATE}</strong> (<strong>{DAYS_OVERDUE}</strong> days overdue).</p><p>Please arrange payment at your earliest convenience.</p><p>Best regards</p>',
                'body_tr' => '<p>Sayın Musterimiz,</p><p>Bu, <strong>{DUE_DATE}</strong> tarihinde vadesi gelen <strong>{AMOUNT_DUE}</strong> tutarindaki <strong>{INVOICE_NUMBER}</strong> numarali odenmemis fatura icin ikinci bildirimdir (<strong>{DAYS_OVERDUE}</strong> gun gecikme).</p><p>Lutfen en kısa surede odeme yapin.</p><p>Saygilarimizla</p>',
                'body_sq' => '<p>I nderuar,</p><p>Ky eshte njoftim i dyte per faturen e papaguar <strong>{INVOICE_NUMBER}</strong> me shumen <strong>{AMOUNT_DUE}</strong>, e cila ka skaduar me <strong>{DUE_DATE}</strong> (<strong>{DAYS_OVERDUE}</strong> dite vonese).</p><p>Ju lutem beni pagesen sa me shpejt.</p><p>Me respekt</p>',
            ],
            [
                'escalation_level' => 'final',
                'days_after_due' => 30,
                'subject_mk' => 'ПОСЛЕДНО ПРЕДУПРЕДУВАЊЕ - Фактура {INVOICE_NUMBER}',
                'subject_en' => 'FINAL NOTICE - Invoice {INVOICE_NUMBER}',
                'subject_tr' => 'SON UYARI - Fatura {INVOICE_NUMBER}',
                'subject_sq' => 'NJOFTIM I FUNDIT - Fatura {INVOICE_NUMBER}',
                'body_mk' => '<p>Почитувани,</p><p>Ова е <strong>последно предупредување</strong> за неплатената фактура <strong>{INVOICE_NUMBER}</strong> во износ од <strong>{AMOUNT_DUE}</strong>, достасана на <strong>{DUE_DATE}</strong> (<strong>{DAYS_OVERDUE}</strong> дена задоцнување).</p><p>Доколку уплатата не биде извршена во рок од 7 дена, ќе бидеме принудени да преземеме дополнителни мерки за наплата, вклучувајќи пресметка на законска камата.</p><p>Со почит</p>',
                'body_en' => '<p>Dear Customer,</p><p>This is a <strong>final notice</strong> regarding the unpaid invoice <strong>{INVOICE_NUMBER}</strong> for <strong>{AMOUNT_DUE}</strong>, which was due on <strong>{DUE_DATE}</strong> (<strong>{DAYS_OVERDUE}</strong> days overdue).</p><p>If payment is not received within 7 days, we will be forced to take further collection actions, including statutory interest charges.</p><p>Best regards</p>',
                'body_tr' => '<p>Sayın Musterimiz,</p><p>Bu, <strong>{AMOUNT_DUE}</strong> tutarindaki <strong>{INVOICE_NUMBER}</strong> numarali odenmemis fatura icin <strong>son uyaridir</strong>. Vade tarihi: <strong>{DUE_DATE}</strong> (<strong>{DAYS_OVERDUE}</strong> gun gecikme).</p><p>7 gun icinde odeme yapilmazsa, yasal faiz dahil ek tahsilat islemleri baslatilacaktir.</p><p>Saygilarimizla</p>',
                'body_sq' => '<p>I nderuar,</p><p>Ky eshte <strong>njoftim i fundit</strong> per faturen e papaguar <strong>{INVOICE_NUMBER}</strong> me shumen <strong>{AMOUNT_DUE}</strong>, e skaduar me <strong>{DUE_DATE}</strong> (<strong>{DAYS_OVERDUE}</strong> dite vonese).</p><p>Nese pagesa nuk merret brenda 7 diteve, do te jemi te detyruar te ndemarrim veprime te metejshme te arketimit, duke perfshire interesin ligjor.</p><p>Me respekt</p>',
            ],
            [
                'escalation_level' => 'legal',
                'days_after_due' => 60,
                'subject_mk' => 'ПРАВНА ПОСТАПКА - Фактура {INVOICE_NUMBER}',
                'subject_en' => 'LEGAL ACTION - Invoice {INVOICE_NUMBER}',
                'subject_tr' => 'HUKUKI ISLEM - Fatura {INVOICE_NUMBER}',
                'subject_sq' => 'VEPRIM LIGJOR - Fatura {INVOICE_NUMBER}',
                'body_mk' => '<p>Почитувани,</p><p>И покрај повеќекратните обиди за наплата, фактурата <strong>{INVOICE_NUMBER}</strong> во износ од <strong>{AMOUNT_DUE}</strong> останува неплатена (<strong>{DAYS_OVERDUE}</strong> дена задоцнување).</p><p>Ве известуваме дека ќе покренеме правна постапка за наплата на побарувањето, заедно со законска камата и трошоци за наплата.</p><p>Крајниот рок за уплата е 7 дена од приемот на ова известување.</p><p>Со почит</p>',
                'body_en' => '<p>Dear Customer,</p><p>Despite multiple attempts to collect, invoice <strong>{INVOICE_NUMBER}</strong> for <strong>{AMOUNT_DUE}</strong> remains unpaid (<strong>{DAYS_OVERDUE}</strong> days overdue).</p><p>We hereby notify you that we will initiate legal proceedings to recover the outstanding amount, together with statutory interest and collection costs.</p><p>The deadline for payment is 7 days from receipt of this notice.</p><p>Best regards</p>',
                'body_tr' => '<p>Sayın Musterimiz,</p><p>Birden fazla tahsilat girisimine ragmen, <strong>{AMOUNT_DUE}</strong> tutarindaki <strong>{INVOICE_NUMBER}</strong> numarali fatura hala odenmemistir (<strong>{DAYS_OVERDUE}</strong> gun gecikme).</p><p>Odenmemis tutari yasal faiz ve tahsilat masraflariyla birlikte tahsil etmek icin yasal islem baslatacagimizi bildiririz.</p><p>Odeme icin son tarih bu bildirimin alinmasindan itibaren 7 gundur.</p><p>Saygilarimizla</p>',
                'body_sq' => '<p>I nderuar,</p><p>Pavaresisht perpjekjeve te shumta per arketim, fatura <strong>{INVOICE_NUMBER}</strong> me shumen <strong>{AMOUNT_DUE}</strong> mbetet e papaguar (<strong>{DAYS_OVERDUE}</strong> dite vonese).</p><p>Ju njoftojme se do te fillojme procedura ligjore per te rikuperuar shumen e mbetur, se bashku me interesin ligjor dhe kostot e arketimit.</p><p>Afati i fundit per pagese eshte 7 dite nga marrja e ketij njoftimi.</p><p>Me respekt</p>',
            ],
        ];

        foreach ($defaults as $tpl) {
            $existing = ReminderTemplate::forCompany($companyId)
                ->where('escalation_level', $tpl['escalation_level'])
                ->first();

            if (! $existing) {
                ReminderTemplate::create(array_merge($tpl, ['company_id' => $companyId]));
            }
        }
    }

    /**
     * Get reminder history with optional filters.
     */
    public function getHistory(int $companyId, array $filters = []): array
    {
        $query = ReminderHistory::forCompany($companyId)
            ->with([
                'invoice:id,invoice_number,total,due_amount',
                'customer:id,name,email',
                'template:id,escalation_level,days_after_due',
            ])
            ->orderBy('sent_at', 'desc');

        if (! empty($filters['customer_id'])) {
            $query->forCustomer((int) $filters['customer_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('sent_at', '>=', $filters['from_date'] . ' 00:00:00');
        }

        if (! empty($filters['to_date'])) {
            $query->where('sent_at', '<=', $filters['to_date'] . ' 23:59:59');
        }

        // Pagination
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 50)));
        $total = (clone $query)->count();

        $data = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->toArray();

        return [
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Get effectiveness analytics: % paid after each level, avg days to pay.
     */
    public function getEffectiveness(int $companyId): array
    {
        $levels = ['friendly', 'firm', 'final', 'legal'];
        $result = [];

        foreach ($levels as $level) {
            $total = ReminderHistory::forCompany($companyId)
                ->where('escalation_level', $level)
                ->count();

            $paid = ReminderHistory::forCompany($companyId)
                ->where('escalation_level', $level)
                ->whereNotNull('paid_at')
                ->count();

            $driver = DB::getDriverName();
            $avgExpr = $driver === 'sqlite'
                ? "AVG(CAST((julianday(paid_at) - julianday(sent_at)) AS REAL))"
                : "AVG(TIMESTAMPDIFF(DAY, sent_at, paid_at))";

            $avgDays = ReminderHistory::forCompany($companyId)
                ->where('escalation_level', $level)
                ->whereNotNull('paid_at')
                ->select(DB::raw("$avgExpr as avg_days"))
                ->value('avg_days');

            $result[$level] = [
                'total_sent' => $total,
                'total_paid' => $paid,
                'paid_percentage' => $total > 0 ? round(($paid / $total) * 100, 1) : 0,
                'avg_days_to_pay' => $avgDays !== null ? round((float) $avgDays, 1) : null,
            ];
        }

        // Overall stats
        $totalOverdue = Invoice::where('company_id', $companyId)
            ->where('due_amount', '>', 0)
            ->where('due_date', '<', Carbon::today()->format('Y-m-d'))
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID])
            ->count();

        $totalOverdueAmount = Invoice::where('company_id', $companyId)
            ->where('due_amount', '>', 0)
            ->where('due_date', '<', Carbon::today()->format('Y-m-d'))
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID])
            ->sum('due_amount');

        $uniqueCustomers = Invoice::where('company_id', $companyId)
            ->where('due_amount', '>', 0)
            ->where('due_date', '<', Carbon::today()->format('Y-m-d'))
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID])
            ->distinct('customer_id')
            ->count('customer_id');

        return [
            'by_level' => $result,
            'overview' => [
                'total_overdue_invoices' => $totalOverdue,
                'total_overdue_amount' => (int) $totalOverdueAmount,
                'unique_customers' => $uniqueCustomers,
            ],
        ];
    }

    /**
     * Get data for Опомена (formal dunning letter) PDF.
     */
    public function getOpomenaData(int $companyId, int $invoiceId): array
    {
        $invoice = Invoice::where('company_id', $companyId)
            ->where('id', $invoiceId)
            ->with(['customer', 'company', 'currency'])
            ->first();

        if (! $invoice) {
            throw new \InvalidArgumentException('Invoice not found.');
        }

        $dueDate = $invoice->due_date instanceof \DateTimeInterface
            ? Carbon::parse($invoice->due_date)
            : Carbon::parse((string) $invoice->due_date);

        $today = Carbon::today();
        $daysOverdue = $dueDate->diffInDays($today);
        $dueAmount = (int) $invoice->due_amount;
        $annualRate = $this->getInterestRate($companyId);
        $interest = (int) round($dueAmount * ($annualRate / 100 / 365) * $daysOverdue);

        // Count previous reminders
        $reminderCount = ReminderHistory::forCompany($companyId)
            ->forInvoice($invoiceId)
            ->count();

        // Get logo directly from media record (accessors fail on cloud storage)
        $company = $invoice->company;
        $logoMedia = $company->getMedia('logo')->first();
        $logo = $logoMedia ? $logoMedia->getFullUrl() : null;

        return [
            'invoice' => $invoice,
            'company' => $company,
            'customer' => $invoice->customer,
            'logo' => $logo,
            'currency_symbol' => $invoice->currency->symbol ?? 'ден.',
            'due_date' => $dueDate->format('d.m.Y'),
            'days_overdue' => $daysOverdue,
            'due_amount' => $dueAmount,
            'interest_rate' => $annualRate,
            'interest_amount' => $interest,
            'total_with_interest' => $dueAmount + $interest,
            'today' => $today->format('d.m.Y'),
            'reminder_count' => $reminderCount,
        ];
    }

    /**
     * Get open items grouped by customer for ИОС.
     */
    public function getOpenItemsByCustomer(int $companyId, array $filters = []): array
    {
        $query = Invoice::where('company_id', $companyId)
            ->where('due_amount', '>', 0)
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID])
            ->with('customer:id,name,email,phone', 'currency:id,symbol');

        if (empty($filters['include_current'])) {
            $query->where('due_date', '<', Carbon::today()->format('Y-m-d'));
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%');
            });
        }

        $invoices = $query->orderBy('customer_id')->orderBy('due_date', 'asc')->get();
        $today = Carbon::today();
        $annualRate = $this->getInterestRate($companyId);

        $grouped = $invoices->groupBy('customer_id');

        $grandTotalDue = 0;
        $grandTotalInterest = 0;
        $invoiceCount = 0;

        $customers = $grouped->map(function ($items, $customerId) use ($today, $annualRate, &$grandTotalDue, &$grandTotalInterest, &$invoiceCount) {
            $customer = $items->first()->customer;
            $subtotalDue = 0;
            $subtotalInterest = 0;

            $mappedItems = $items->map(function ($invoice) use ($today, $annualRate, &$subtotalDue, &$subtotalInterest, &$invoiceCount) {
                $dueDate = Carbon::parse($invoice->due_date instanceof \DateTimeInterface ? $invoice->due_date : (string) $invoice->due_date);
                $dueAmount = (int) $invoice->due_amount;
                $daysOverdue = $dueDate->lt($today) ? $dueDate->diffInDays($today) : 0;
                $interest = $daysOverdue > 0 ? (int) round($dueAmount * ($annualRate / 100 / 365) * $daysOverdue) : 0;

                $subtotalDue += $dueAmount;
                $subtotalInterest += $interest;
                $invoiceCount++;

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date instanceof \DateTimeInterface ? $invoice->invoice_date->format('Y-m-d') : (string) $invoice->invoice_date,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'total' => (int) $invoice->total,
                    'due_amount' => $dueAmount,
                    'days_overdue' => $daysOverdue,
                    'interest' => $interest,
                ];
            })->values()->toArray();

            $grandTotalDue += $subtotalDue;
            $grandTotalInterest += $subtotalInterest;

            return [
                'customer_id' => (int) $customerId,
                'customer_name' => $customer?->name,
                'customer_email' => $customer?->email,
                'items' => $mappedItems,
                'subtotal_due' => $subtotalDue,
                'subtotal_interest' => $subtotalInterest,
                'subtotal_total_with_interest' => $subtotalDue + $subtotalInterest,
            ];
        })->values()->toArray();

        return [
            'customers' => $customers,
            'grand_total_due' => $grandTotalDue,
            'grand_total_interest' => $grandTotalInterest,
            'grand_total_with_interest' => $grandTotalDue + $grandTotalInterest,
            'customer_count' => count($customers),
            'invoice_count' => $invoiceCount,
        ];
    }

    /**
     * Get data for ИОС PDF for a specific customer.
     */
    public function getIosData(int $companyId, int $customerId): array
    {
        $customer = Customer::where('company_id', $companyId)->where('id', $customerId)->first();
        if (! $customer) {
            throw new \InvalidArgumentException('Customer not found.');
        }

        $invoices = Invoice::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('due_amount', '>', 0)
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID])
            ->with('currency:id,symbol')
            ->orderBy('due_date', 'asc')
            ->get();

        $company = \App\Models\Company::find($companyId);
        $logoMedia = $company->getMedia('logo')->first();
        $logo = $logoMedia ? $logoMedia->getFullUrl() : null;

        $today = Carbon::today();
        $annualRate = $this->getInterestRate($companyId);
        $currencySymbol = $invoices->first()?->currency?->symbol ?? 'ден.';

        $totalDue = 0;
        $totalInterest = 0;

        $items = $invoices->map(function ($invoice) use ($today, $annualRate, &$totalDue, &$totalInterest) {
            $dueDate = Carbon::parse($invoice->due_date instanceof \DateTimeInterface ? $invoice->due_date : (string) $invoice->due_date);
            $dueAmount = (int) $invoice->due_amount;
            $daysOverdue = $dueDate->lt($today) ? $dueDate->diffInDays($today) : 0;
            $interest = $daysOverdue > 0 ? (int) round($dueAmount * ($annualRate / 100 / 365) * $daysOverdue) : 0;

            $totalDue += $dueAmount;
            $totalInterest += $interest;

            return (object) [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date instanceof \DateTimeInterface ? $invoice->invoice_date->format('d.m.Y') : Carbon::parse((string) $invoice->invoice_date)->format('d.m.Y'),
                'due_date' => $dueDate->format('d.m.Y'),
                'total' => (int) $invoice->total,
                'paid' => (int) $invoice->total - $dueAmount,
                'due_amount' => $dueAmount,
                'days_overdue' => $daysOverdue,
                'interest' => $interest,
            ];
        });

        return [
            'company' => $company,
            'customer' => $customer,
            'logo' => $logo,
            'currency_symbol' => $currencySymbol,
            'items' => $items,
            'total_due' => $totalDue,
            'total_interest' => $totalInterest,
            'total_with_interest' => $totalDue + $totalInterest,
            'interest_rate' => $annualRate,
            'today' => $today->format('d.m.Y'),
        ];
    }

    /**
     * Bulk send ИОС PDFs to customers.
     */
    public function sendIosBulk(int $companyId, array $customerIds = []): array
    {
        $query = Invoice::where('company_id', $companyId)
            ->where('due_amount', '>', 0)
            ->where('due_date', '<', Carbon::today()->format('Y-m-d'))
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]);

        if (! empty($customerIds)) {
            $query->whereIn('customer_id', $customerIds);
        }

        $targetCustomerIds = $query->distinct()->pluck('customer_id')->toArray();

        $sent = 0;
        $failed = 0;

        foreach ($targetCustomerIds as $custId) {
            try {
                $iosData = $this->getIosData($companyId, $custId);
                $customer = $iosData['customer'];

                if (! $customer->email) {
                    $failed++;
                    continue;
                }

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('app.pdf.reports.ios', $iosData);
                $pdf->setPaper('A4', 'portrait');
                $pdfContent = $pdf->output();

                $company = $iosData['company'];

                \Illuminate\Support\Facades\Mail::to($customer->email)
                    ->send(new \App\Mail\IosMail($company, $customer, $pdfContent));

                $sent++;
            } catch (\Exception $e) {
                $failed++;
                \Log::warning("IOS bulk send failed for customer {$custId}: " . $e->getMessage());
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Get data for Каматна нота PDF for a specific customer.
     */
    public function getInterestNoteData(int $companyId, int $customerId): array
    {
        $customer = Customer::where('company_id', $companyId)->where('id', $customerId)->first();
        if (! $customer) {
            throw new \InvalidArgumentException('Customer not found.');
        }

        $invoices = Invoice::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('due_amount', '>', 0)
            ->where('due_date', '<', Carbon::today()->format('Y-m-d'))
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID])
            ->with('currency:id,symbol')
            ->orderBy('due_date', 'asc')
            ->get();

        if ($invoices->isEmpty()) {
            throw new \InvalidArgumentException('No overdue invoices found for this customer.');
        }

        $company = \App\Models\Company::find($companyId);
        $logoMedia = $company->getMedia('logo')->first();
        $logo = $logoMedia ? $logoMedia->getFullUrl() : null;

        $today = Carbon::today();
        $annualRate = $this->getInterestRate($companyId);
        $currencySymbol = $invoices->first()?->currency?->symbol ?? 'ден.';

        $totalPrincipal = 0;
        $totalInterest = 0;

        $items = $invoices->map(function ($invoice) use ($today, $annualRate, &$totalPrincipal, &$totalInterest) {
            $dueDate = Carbon::parse($invoice->due_date instanceof \DateTimeInterface ? $invoice->due_date : (string) $invoice->due_date);
            $dueAmount = (int) $invoice->due_amount;
            $daysOverdue = $dueDate->diffInDays($today);
            $interest = (int) round($dueAmount * ($annualRate / 100 / 365) * $daysOverdue);

            $totalPrincipal += $dueAmount;
            $totalInterest += $interest;

            return (object) [
                'invoice_number' => $invoice->invoice_number,
                'due_date' => $dueDate->format('d.m.Y'),
                'due_amount' => $dueAmount,
                'days_overdue' => $daysOverdue,
                'interest_rate' => $annualRate,
                'interest' => $interest,
            ];
        });

        return [
            'company' => $company,
            'customer' => $customer,
            'logo' => $logo,
            'currency_symbol' => $currencySymbol,
            'items' => $items,
            'total_principal' => $totalPrincipal,
            'total_interest' => $totalInterest,
            'total_with_interest' => $totalPrincipal + $totalInterest,
            'interest_rate' => $annualRate,
            'today' => $today->format('d.m.Y'),
        ];
    }

    /**
     * Get interest rate for a company.
     */
    protected function getInterestRate(?int $companyId = null): float
    {
        if ($companyId) {
            $customRate = CompanySetting::getSetting('interest_annual_rate', $companyId);
            if ($customRate !== null && $customRate !== '') {
                return (float) $customRate;
            }
        }

        return self::DEFAULT_ANNUAL_RATE;
    }
}

// CLAUDE-CHECKPOINT
