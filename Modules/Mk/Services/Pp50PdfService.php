<?php

namespace Modules\Mk\Services;

use App\Models\BankAccount;
use App\Models\Company;
use Modules\Mk\Models\PaymentBatch;
use PDF;

/**
 * PP50 PDF Service
 *
 * Generates printable PP50 payment slip PDFs for budget/public revenue payments.
 * PP50 extends PP30 with revenue code (шифра на приход) and municipality fields.
 */
class Pp50PdfService
{
    /**
     * Macedonian revenue codes — common budget payment classifications.
     */
    public const REVENUE_CODES = [
        '711111' => 'Данок на добивка',
        '711211' => 'Данок на добивка - аконтација',
        '713111' => 'Персонален данок на доход',
        '713121' => 'Данок на доход од самостојна дејност',
        '713131' => 'Данок на доход од имот и имотни права',
        '714111' => 'Данок на имот',
        '714121' => 'Данок на наследство и подарок',
        '714131' => 'Данок на промет на недвижности',
        '716111' => 'Комунална такса',
        '717111' => 'Административна такса',
        '717211' => 'Судска такса',
        '722315' => 'Придонес за ПИО',
        '722313' => 'Придонес за здравство',
        '722316' => 'Придонес за вработување',
        '722317' => 'Додатен придонес за здравство за повреда на работа',
        '723119' => 'Глоба за прекршок',
        '724149' => 'Останати неданочни приходи',
    ];

    /**
     * Macedonian municipalities (top ones used in budget payments).
     */
    public const MUNICIPALITIES = [
        '80' => 'Град Скопје',
        '01' => 'Аеродром',
        '02' => 'Арачиново',
        '03' => 'Берово',
        '04' => 'Битола',
        '05' => 'Богданци',
        '06' => 'Боговиње',
        '07' => 'Босилово',
        '08' => 'Брвеница',
        '09' => 'Бутел',
        '10' => 'Валандово',
        '11' => 'Василево',
        '12' => 'Вевчани',
        '13' => 'Велес',
        '14' => 'Виница',
        '15' => 'Врапчиште',
        '16' => 'Гази Баба',
        '17' => 'Гевгелија',
        '18' => 'Гостивар',
        '19' => 'Градско',
        '20' => 'Дебар',
        '21' => 'Дебрца',
        '22' => 'Делчево',
        '23' => 'Демир Капија',
        '24' => 'Демир Хисар',
        '25' => 'Дојран',
        '26' => 'Долнени',
        '27' => 'Ѓорче Петров',
        '28' => 'Желино',
        '29' => 'Зајас',
        '30' => 'Зелениково',
        '31' => 'Зрновци',
        '32' => 'Илинден',
        '33' => 'Јегуновце',
        '34' => 'Кавадарци',
        '35' => 'Карбинци',
        '36' => 'Карпош',
        '37' => 'Кисела Вода',
        '38' => 'Кичево',
        '39' => 'Конче',
        '40' => 'Кочани',
        '41' => 'Кратово',
        '42' => 'Крива Паланка',
        '43' => 'Кривогаштани',
        '44' => 'Крушево',
        '45' => 'Куманово',
        '46' => 'Липково',
        '47' => 'Лозово',
        '48' => 'Маврово и Ростуше',
        '49' => 'Македонска Каменица',
        '50' => 'Македонски Брод',
        '51' => 'Могила',
        '52' => 'Неготино',
        '53' => 'Новаци',
        '54' => 'Ново Село',
        '55' => 'Охрид',
        '56' => 'Пехчево',
        '57' => 'Петровец',
        '58' => 'Пласница',
        '59' => 'Прилеп',
        '60' => 'Пробиштип',
        '61' => 'Радовиш',
        '62' => 'Ранковце',
        '63' => 'Ресен',
        '64' => 'Росоман',
        '65' => 'Сарај',
        '66' => 'Свети Николе',
        '67' => 'Сопиште',
        '68' => 'Старо Нагоричане',
        '69' => 'Струга',
        '70' => 'Струмица',
        '71' => 'Студеничани',
        '72' => 'Теарце',
        '73' => 'Тетово',
        '74' => 'Центар',
        '75' => 'Центар Жупа',
        '76' => 'Чаир',
        '77' => 'Чашка',
        '78' => 'Чешиново-Облешево',
        '79' => 'Чучер-Сандево',
        '81' => 'Штип',
        '82' => 'Шуто Оризари',
    ];

    private Pp30PdfService $pp30Service;

    public function __construct(Pp30PdfService $pp30Service)
    {
        $this->pp30Service = $pp30Service;
    }

    /**
     * Generate PP50 PDF from raw data array.
     *
     * @param array $data
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generate(array $data)
    {
        $slip = $this->buildSlip(
            debtorName: $data['debtor_name'] ?? '',
            debtorIban: $data['debtor_iban'] ?? '',
            debtorBank: $data['debtor_bank'] ?? '',
            creditorName: $data['creditor_name'] ?? '',
            creditorIban: $data['creditor_iban'] ?? '',
            creditorBank: $data['creditor_bank'] ?? '',
            amountCents: (int) ($data['amount'] ?? 0),
            currencyCode: $data['currency_code'] ?? 'MKD',
            revenueCode: $data['revenue_code'] ?? '',
            municipalityCode: $data['municipality_code'] ?? '',
            paymentReference: $data['payment_reference'] ?? '',
            description: $data['description'] ?? '',
            date: $data['date'] ?? now()->format('d.m.Y'),
            billNumber: $data['bill_number'] ?? ''
        );

        view()->share(['slips' => [$slip]]);

        return PDF::loadView('app.pdf.reports.pp50');
    }

    /**
     * Generate PP50 PDF for an entire payment batch (one slip per item).
     *
     * @param PaymentBatch $batch
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateForBatch(PaymentBatch $batch)
    {
        $batch->load(['items', 'company', 'bankAccount']);

        $company = $batch->company;
        $bankAccount = $batch->bankAccount ?? $this->getDefaultBankAccount($company);

        $slips = [];

        foreach ($batch->items as $item) {
            $slips[] = $this->buildSlip(
                debtorName: $company->name ?? '',
                debtorIban: $bankAccount?->iban ?? $bankAccount?->account_number ?? '',
                debtorBank: $bankAccount?->bank_name ?? '',
                creditorName: $item->creditor_name,
                creditorIban: $item->creditor_iban ?? '',
                creditorBank: $item->creditor_bank_name ?? '',
                amountCents: (int) $item->amount,
                currencyCode: $item->currency_code ?? 'MKD',
                revenueCode: $item->revenue_code ?? '',
                municipalityCode: $item->municipality_code ?? '',
                paymentReference: $item->payment_reference ?? '',
                description: $item->description ?? 'Буџетско плаќање',
                date: $batch->batch_date
                    ? $batch->batch_date->format('d.m.Y')
                    : now()->format('d.m.Y'),
                billNumber: $item->bill?->bill_number ?? ''
            );
        }

        if (empty($slips)) {
            throw new \Exception('No items in payment batch.');
        }

        view()->share(['slips' => $slips]);

        return PDF::loadView('app.pdf.reports.pp50');
    }

    /**
     * Build a single PP50 slip data array.
     */
    public function buildSlip(
        string $debtorName,
        string $debtorIban,
        string $debtorBank,
        string $creditorName,
        string $creditorIban,
        string $creditorBank,
        int $amountCents,
        string $currencyCode,
        string $revenueCode,
        string $municipalityCode,
        string $paymentReference,
        string $description,
        string $date,
        string $billNumber
    ): array {
        // Auto-detect bank name from IBAN if not provided
        if (empty($debtorBank) && ! empty($debtorIban)) {
            $debtorBank = $this->pp30Service->getBankNameFromIban($debtorIban);
        }
        if (empty($creditorBank) && ! empty($creditorIban)) {
            $creditorBank = $this->pp30Service->getBankNameFromIban($creditorIban);
        }

        // Resolve revenue and municipality names
        $revenueName = self::REVENUE_CODES[$revenueCode] ?? '';
        $municipalityName = self::MUNICIPALITIES[$municipalityCode] ?? '';

        return [
            'debtor_name' => $debtorName,
            'debtor_iban' => $this->pp30Service->formatIban($debtorIban),
            'debtor_bank' => $debtorBank,
            'creditor_name' => $creditorName,
            'creditor_iban' => $this->pp30Service->formatIban($creditorIban),
            'creditor_bank' => $creditorBank,
            'amount' => $amountCents,
            'amount_formatted' => number_format($amountCents / 100, 2, ',', '.'),
            'amount_words' => $this->pp30Service->amountToWords($amountCents),
            'currency_code' => $currencyCode,
            'revenue_code' => $revenueCode,
            'revenue_name' => $revenueName,
            'municipality_code' => $municipalityCode,
            'municipality_name' => $municipalityName,
            'payment_reference' => $paymentReference,
            'description' => $description,
            'date' => $date,
            'bill_number' => $billNumber,
        ];
    }

    /**
     * Get the company's primary/default bank account.
     */
    protected function getDefaultBankAccount(Company $company): ?BankAccount
    {
        return BankAccount::where('company_id', $company->id)
            ->orderBy('id')
            ->first();
    }

    /**
     * Get all revenue codes as key-value array.
     */
    public static function getRevenueCodes(): array
    {
        return self::REVENUE_CODES;
    }

    /**
     * Get all municipalities as key-value array.
     */
    public static function getMunicipalities(): array
    {
        return self::MUNICIPALITIES;
    }
}

// CLAUDE-CHECKPOINT
