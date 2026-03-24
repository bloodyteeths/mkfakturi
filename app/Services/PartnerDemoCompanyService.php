<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillPayment;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanySubscription;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\TaxType;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Mk\Partner\Services\PortfolioTierService;
use Silber\Bouncer\BouncerFacade;
use Vinkla\Hashids\Facades\Hashids;

class PartnerDemoCompanyService
{
    /**
     * Seed a demo ДООЕЛ company for a newly registered partner.
     * Uses a savepoint so failure does not kill the signup transaction.
     */
    public function seedForPartner(Partner $partner, User $user): Company
    {
        return DB::transaction(function () use ($partner, $user) {
            $currency = Currency::firstOrCreate(
                ['code' => 'MKD'],
                ['name' => 'Macedonian Denar', 'symbol' => 'ден', 'precision' => 2, 'thousand_separator' => '.', 'decimal_separator' => ',', 'swap_currency_symbol' => true]
            );

            // ── Company ─────────────────────────────────────────
            $slug = 'demo-kompanija-dooel-' . $partner->id;
            $company = Company::create([
                'name' => 'Демо Компанија ДООЕЛ',
                'owner_id' => $user->id,
                'slug' => $slug,
                'phone' => '+38923112233',
                'vat_number' => 'MK4080012345678',
                'currency_id' => $currency->id,
                'is_portfolio_managed' => true,
                'managing_partner_id' => $partner->id,
            ]);
            $company->unique_hash = Hashids::connection(Company::class)->encode($company->id);
            $company->save();

            // Company address
            Address::create([
                'company_id' => $company->id,
                'name' => 'Демо Компанија ДООЕЛ',
                'address_street_1' => 'Бул. Илинден бр. 42',
                'city' => 'Скопје',
                'state' => 'Скопје',
                'country_id' => 129,
                'zip' => '1000',
                'phone' => '+38923112233',
                'type' => Address::BILLING_TYPE,
            ]);

            // Roles only (skip English payment methods & units)
            $company->setupRoles();
            $company->setupDefaultSettings();

            // Override MK-specific settings
            CompanySetting::setSettings([
                'currency' => $currency->id,
                'language' => 'mk',
                'time_zone' => 'Europe/Skopje',
                'tax_per_item' => 'YES',
                'discount_per_item' => 'NO',
                'invoice_number_format' => '{{SERIES:ФАК}}{{DELIMITER:-}}{{SEQUENCE:6}}',
                'estimate_number_format' => '{{SERIES:ПОН}}{{DELIMITER:-}}{{SEQUENCE:6}}',
                'payment_number_format' => '{{SERIES:ПЛА}}{{DELIMITER:-}}{{SEQUENCE:6}}',
                'proforma_invoice_number_format' => '{{SERIES:ПРО}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            ], $company->id);

            // Attach user to company
            $user->companies()->attach($company->id);
            BouncerFacade::scope()->to($company->id);
            $user->assign('super admin');

            // ── Reference Data ──────────────────────────────────
            $vat18 = TaxType::create(['name' => 'ДДВ 18%', 'percent' => 18.0, 'company_id' => $company->id, 'type' => 'GENERAL']);
            $vat05 = TaxType::create(['name' => 'ДДВ 5%', 'percent' => 5.0, 'company_id' => $company->id, 'type' => 'GENERAL']);

            $paymentMethods = collect([
                'Готовина' => PaymentMethod::GL_CASH,
                'Банкарски трансфер' => PaymentMethod::GL_BANK,
                'Кредитна картичка' => PaymentMethod::GL_BANK,
                'Чек' => PaymentMethod::GL_CASH,
                'Вирман' => PaymentMethod::GL_BANK,
            ])->map(fn ($gl, $name) => PaymentMethod::create(['name' => $name, 'company_id' => $company->id, 'account_code' => $gl]));

            $cashMethod = $paymentMethods->first();
            $bankMethod = $paymentMethods->skip(1)->first();

            $unitNames = ['парче', 'комад', 'килограм', 'литар', 'метар', 'кутија', 'пакет', 'час', 'ден', 'месец'];
            $units = collect($unitNames)->mapWithKeys(fn ($name) => [$name => Unit::create(['name' => $name, 'company_id' => $company->id])]);
            $pcUnit = $units['парче'];
            $kgUnit = $units['килограм'];
            $hourUnit = $units['час'];
            $boxUnit = $units['кутија'];

            $categories = collect([
                'Кирија' => 'Месечна закупнина за канцеларија',
                'Комуналии' => 'Струја, вода, греење',
                'Канцелариски материјали' => 'Хартија, тонер, канцелариски потрошни',
                'Превоз' => 'Гориво и транспортни трошоци',
                'Интернет и телекомуникации' => 'Интернет, телефон, мобилни',
            ])->map(fn ($desc, $name) => ExpenseCategory::create(['name' => $name, 'company_id' => $company->id, 'description' => $desc]));

            // ── Customers ───────────────────────────────────────
            $customerData = [
                ['name' => 'Техно Солушнс ДООЕЛ', 'contact_name' => 'Марко Стоилковски', 'email' => 'info@tehno-solutions.mk', 'phone' => '+38971234567', 'vat' => 'MK4080001111111', 'street' => 'ул. Мито Хаџивасилев-Јасмин бр. 12', 'city' => 'Скопје', 'zip' => '1000'],
                ['name' => 'Агро Маркет ДОО', 'contact_name' => 'Ана Петровска', 'email' => 'nabavki@agromarket.mk', 'phone' => '+38972345678', 'vat' => 'MK4080002222222', 'street' => 'ул. Никола Тесла бр. 8', 'city' => 'Битола', 'zip' => '7000'],
                ['name' => 'Медика Фарм ДООЕЛ', 'contact_name' => 'Дарко Илиевски', 'email' => 'office@medikafarm.mk', 'phone' => '+38973456789', 'vat' => 'MK4080003333333', 'street' => 'бул. Партизански одреди бр. 55', 'city' => 'Скопје', 'zip' => '1000'],
                ['name' => 'Градежен Инженеринг АД', 'contact_name' => 'Иван Николовски', 'email' => 'kontakt@gradezen-ing.mk', 'phone' => '+38974567890', 'vat' => 'MK4080004444444', 'street' => 'ул. 11 Октомври бр. 25', 'city' => 'Прилеп', 'zip' => '7500'],
                ['name' => 'Дигитал Маркетинг ДООЕЛ', 'contact_name' => 'Елена Стефановска', 'email' => 'hello@digitalmarketing.mk', 'phone' => '+38975678901', 'vat' => 'MK4080005555555', 'street' => 'ул. Водњанска бр. 3', 'city' => 'Скопје', 'zip' => '1000'],
            ];

            $customers = collect($customerData)->map(function ($c) use ($company, $currency, $user) {
                $customer = Customer::create([
                    'name' => $c['name'],
                    'contact_name' => $c['contact_name'],
                    'email' => $c['email'],
                    'phone' => $c['phone'],
                    'vat_number' => $c['vat'],
                    'company_id' => $company->id,
                    'currency_id' => $currency->id,
                    'creator_id' => $user->id,
                    'enable_portal' => false,
                ]);

                Address::create([
                    'customer_id' => $customer->id,
                    'name' => $c['name'],
                    'address_street_1' => $c['street'],
                    'city' => $c['city'],
                    'state' => 'Македонија',
                    'country_id' => 129,
                    'zip' => $c['zip'],
                    'phone' => $c['phone'],
                    'type' => Address::BILLING_TYPE,
                ]);

                return $customer;
            });

            // ── Suppliers ───────────────────────────────────────
            $supplierData = [
                ['name' => 'Канцелариски Центар ДОО', 'contact' => 'Горан Ристовски', 'email' => 'naracki@kancelariskicentar.mk', 'phone' => '+38976111222', 'street' => 'ул. Св. Кирил и Методиј бр. 30', 'city' => 'Скопје', 'zip' => '1000'],
                ['name' => 'ИТ Сервис Груп ДООЕЛ', 'contact' => 'Стефан Димитров', 'email' => 'support@itservis.mk', 'phone' => '+38977222333', 'street' => 'ул. Орце Николов бр. 15', 'city' => 'Скопје', 'zip' => '1000'],
                ['name' => 'Енерго Плус ДОО', 'contact' => 'Наташа Трајковска', 'email' => 'fakturi@energoplus.mk', 'phone' => '+38978333444', 'street' => 'ул. Македонија бр. 9', 'city' => 'Тетово', 'zip' => '1200'],
            ];

            $suppliers = collect($supplierData)->map(function ($s) use ($company, $currency, $user) {
                return Supplier::create([
                    'name' => $s['name'],
                    'contact_name' => $s['contact'],
                    'email' => $s['email'],
                    'phone' => $s['phone'],
                    'company_id' => $company->id,
                    'currency_id' => $currency->id,
                    'creator_id' => $user->id,
                ]);
            });

            // ── Items (services + products with stock) ──────────
            $itemDefs = [
                // Services (no stock)
                ['name' => 'Сметководствени услуги', 'desc' => 'Месечно водење на сметководство', 'price' => 1500000, 'unit' => $hourUnit, 'tax' => $vat18, 'stock' => false],
                ['name' => 'Консултации', 'desc' => 'Деловни и финансиски консултации', 'price' => 200000, 'unit' => $hourUnit, 'tax' => $vat18, 'stock' => false],
                ['name' => 'Ревизија', 'desc' => 'Годишна ревизија на финансиски извештаи', 'price' => 6000000, 'unit' => $pcUnit, 'tax' => $vat18, 'stock' => false],
                ['name' => 'Веб дизајн', 'desc' => 'Изработка на веб страница', 'price' => 4500000, 'unit' => $pcUnit, 'tax' => $vat18, 'stock' => false],
                ['name' => 'ИТ Поддршка', 'desc' => 'Месечна техничка поддршка', 'price' => 800000, 'unit' => $hourUnit, 'tax' => $vat18, 'stock' => false],
                // Products (with stock tracking)
                ['name' => 'Тонер за печатач', 'desc' => 'Компатибилен тонер HP LaserJet', 'price' => 180000, 'unit' => $pcUnit, 'tax' => $vat18, 'stock' => true, 'qty' => 25, 'cost' => 120000],
                ['name' => 'А4 Хартија (500 листа)', 'desc' => 'Хартија за печатење и копирање', 'price' => 25000, 'unit' => $boxUnit, 'tax' => $vat05, 'stock' => true, 'qty' => 100, 'cost' => 18000],
                ['name' => 'Канцелариски прибор сет', 'desc' => 'Пенкала, маркери, коректор, лепенка', 'price' => 45000, 'unit' => $pcUnit, 'tax' => $vat18, 'stock' => true, 'qty' => 50, 'cost' => 30000],
            ];

            $warehouse = Warehouse::getOrCreateDefault($company->id);

            $items = collect($itemDefs)->map(function ($d) use ($company, $currency, $user, $warehouse) {
                $item = Item::create([
                    'name' => $d['name'],
                    'description' => $d['desc'],
                    'price' => $d['price'],
                    'unit_id' => $d['unit']->id,
                    'company_id' => $company->id,
                    'currency_id' => $currency->id,
                    'creator_id' => $user->id,
                    'tax_per_item' => true,
                    'track_quantity' => $d['stock'],
                ]);

                // Attach default tax
                Tax::create([
                    'tax_type_id' => $d['tax']->id,
                    'item_id' => $item->id,
                    'name' => $d['tax']->name,
                    'percent' => $d['tax']->percent,
                    'amount' => (int) round($d['price'] * $d['tax']->percent / 100),
                    'company_id' => $company->id,
                    'currency_id' => $currency->id,
                ]);

                // Record initial stock for tracked items
                if ($d['stock'] && ! empty($d['qty'])) {
                    app(StockService::class)->recordInitialStock(
                        $company->id,
                        $warehouse->id,
                        $item->id,
                        (float) $d['qty'],
                        $d['cost'],
                        'Почетна залиха',
                        $user->id
                    );
                }

                return $item;
            });

            // ── Invoices ────────────────────────────────────────
            $now = now();
            $invoiceDefs = [
                ['customer' => 0, 'items' => [[0, 10], [1, 5]], 'status' => Invoice::STATUS_DRAFT, 'paid' => Invoice::STATUS_UNPAID, 'days_ago' => 2],
                ['customer' => 1, 'items' => [[2, 1]], 'status' => Invoice::STATUS_SENT, 'paid' => Invoice::STATUS_UNPAID, 'days_ago' => 15],
                ['customer' => 2, 'items' => [[0, 8], [4, 3]], 'status' => Invoice::STATUS_VIEWED, 'paid' => Invoice::STATUS_UNPAID, 'days_ago' => 10],
                ['customer' => 0, 'items' => [[3, 1]], 'status' => Invoice::STATUS_COMPLETED, 'paid' => Invoice::STATUS_PAID, 'days_ago' => 45],
                ['customer' => 3, 'items' => [[1, 20], [4, 10]], 'status' => Invoice::STATUS_SENT, 'paid' => Invoice::STATUS_PARTIALLY_PAID, 'days_ago' => 30],
                ['customer' => 4, 'items' => [[0, 5]], 'status' => Invoice::STATUS_SENT, 'paid' => Invoice::STATUS_UNPAID, 'days_ago' => 60],
                ['customer' => 1, 'items' => [[5, 3], [6, 10]], 'status' => Invoice::STATUS_COMPLETED, 'paid' => Invoice::STATUS_PAID, 'days_ago' => 20],
            ];

            $invoices = [];
            foreach ($invoiceDefs as $idx => $def) {
                $seq = $idx + 1;
                $invDate = $now->copy()->subDays($def['days_ago']);

                // Calculate totals
                $subTotal = 0;
                $taxTotal = 0;
                foreach ($def['items'] as [$itemIdx, $qty]) {
                    $price = $items[$itemIdx]->price;
                    $taxPercent = $itemDefs[$itemIdx]['tax']->percent;
                    $lineTotal = $price * $qty;
                    $lineTax = (int) round($lineTotal * $taxPercent / 100);
                    $subTotal += $lineTotal;
                    $taxTotal += $lineTax;
                }
                $total = $subTotal + $taxTotal;

                $invoice = Invoice::create([
                    'invoice_date' => $invDate,
                    'due_date' => $invDate->copy()->addDays(15),
                    'invoice_number' => 'ФАК-' . str_pad($seq, 6, '0', STR_PAD_LEFT),
                    'customer_id' => $customers[$def['customer']]->id,
                    'company_id' => $company->id,
                    'creator_id' => $user->id,
                    'currency_id' => $currency->id,
                    'exchange_rate' => 1.0,
                    'sub_total' => $subTotal,
                    'total' => $total,
                    'tax' => $taxTotal,
                    'due_amount' => $total,
                    'base_due_amount' => $total,
                    'base_sub_total' => $subTotal,
                    'base_total' => $total,
                    'base_tax' => $taxTotal,
                    'status' => $def['status'],
                    'paid_status' => $def['paid'],
                    'sequence_number' => $seq,
                    'customer_sequence_number' => $seq,
                    'tax_per_item' => 'YES',
                    'discount_per_item' => 'NO',
                    'template_name' => 'invoice1',
                    'sent' => in_array($def['status'], [Invoice::STATUS_SENT, Invoice::STATUS_VIEWED, Invoice::STATUS_COMPLETED]),
                ]);
                $invoice->unique_hash = Hashids::connection(Invoice::class)->encode($invoice->id);
                $invoice->save();

                // Create invoice items
                foreach ($def['items'] as [$itemIdx, $qty]) {
                    $item = $items[$itemIdx];
                    $taxPercent = $itemDefs[$itemIdx]['tax']->percent;
                    $lineTotal = $item->price * $qty;
                    $lineTax = (int) round($lineTotal * $taxPercent / 100);

                    $invItem = InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'quantity' => $qty,
                        'price' => $item->price,
                        'total' => $lineTotal,
                        'tax' => $lineTax,
                        'discount_type' => 'fixed',
                        'discount_val' => 0,
                        'discount' => 0,
                        'exchange_rate' => 1.0,
                        'base_price' => $item->price,
                        'base_discount_val' => 0,
                        'base_tax' => $lineTax,
                        'base_total' => $lineTotal,
                        'company_id' => $company->id,
                        'currency_id' => $currency->id,
                        'unit_name' => $itemDefs[$itemIdx]['unit']->name,
                    ]);

                    Tax::create([
                        'tax_type_id' => $itemDefs[$itemIdx]['tax']->id,
                        'invoice_item_id' => $invItem->id,
                        'name' => $itemDefs[$itemIdx]['tax']->name,
                        'percent' => $taxPercent,
                        'amount' => $lineTax,
                        'company_id' => $company->id,
                        'currency_id' => $currency->id,
                        'exchange_rate' => 1.0,
                        'base_amount' => $lineTax,
                    ]);
                }

                $invoices[$idx] = $invoice;
            }

            // ── Payments (withoutEvents to skip PDF) ────────────
            Payment::withoutEvents(function () use ($invoices, $bankMethod, $currency, $company, $user) {
                // Full payment for invoice 3 (COMPLETED/PAID)
                $this->createPayment($invoices[3], $invoices[3]->total, 1, $bankMethod, $currency, $company, $user);
                $invoices[3]->update(['due_amount' => 0, 'base_due_amount' => 0]);

                // Full payment for invoice 6 (COMPLETED/PAID)
                $this->createPayment($invoices[6], $invoices[6]->total, 2, $bankMethod, $currency, $company, $user);
                $invoices[6]->update(['due_amount' => 0, 'base_due_amount' => 0]);

                // Partial payment for invoice 4 (PARTIALLY_PAID)
                $partialAmount = (int) round($invoices[4]->total * 0.4);
                $this->createPayment($invoices[4], $partialAmount, 3, $bankMethod, $currency, $company, $user);
                $invoices[4]->update(['due_amount' => $invoices[4]->total - $partialAmount, 'base_due_amount' => $invoices[4]->total - $partialAmount]);
            });

            // ── Estimates ───────────────────────────────────────
            $estimateDefs = [
                ['customer' => 2, 'items' => [[3, 1], [4, 6]], 'status' => Estimate::STATUS_SENT, 'days_ago' => 5],
                ['customer' => 3, 'items' => [[0, 12]], 'status' => Estimate::STATUS_ACCEPTED, 'days_ago' => 25],
                ['customer' => 4, 'items' => [[1, 8], [2, 1]], 'status' => Estimate::STATUS_EXPIRED, 'days_ago' => 40],
            ];

            foreach ($estimateDefs as $idx => $def) {
                $seq = $idx + 1;
                $estDate = $now->copy()->subDays($def['days_ago']);

                $subTotal = 0;
                $taxTotal = 0;
                foreach ($def['items'] as [$itemIdx, $qty]) {
                    $lineTotal = $items[$itemIdx]->price * $qty;
                    $lineTax = (int) round($lineTotal * $itemDefs[$itemIdx]['tax']->percent / 100);
                    $subTotal += $lineTotal;
                    $taxTotal += $lineTax;
                }
                $total = $subTotal + $taxTotal;

                $estimate = Estimate::create([
                    'estimate_date' => $estDate,
                    'expiry_date' => $estDate->copy()->addDays(15),
                    'estimate_number' => 'ПОН-' . str_pad($seq, 6, '0', STR_PAD_LEFT),
                    'customer_id' => $customers[$def['customer']]->id,
                    'company_id' => $company->id,
                    'creator_id' => $user->id,
                    'currency_id' => $currency->id,
                    'exchange_rate' => 1.0,
                    'sub_total' => $subTotal,
                    'total' => $total,
                    'tax' => $taxTotal,
                    'base_sub_total' => $subTotal,
                    'base_total' => $total,
                    'base_tax' => $taxTotal,
                    'status' => $def['status'],
                    'sequence_number' => $seq,
                    'customer_sequence_number' => $seq,
                    'tax_per_item' => 'YES',
                    'discount_per_item' => 'NO',
                    'template_name' => 'estimate1',
                    'sent' => $def['status'] !== Estimate::STATUS_DRAFT,
                ]);
                $estimate->unique_hash = Hashids::connection(Estimate::class)->encode($estimate->id);
                $estimate->save();

                foreach ($def['items'] as [$itemIdx, $qty]) {
                    $item = $items[$itemIdx];
                    $lineTotal = $item->price * $qty;
                    $lineTax = (int) round($lineTotal * $itemDefs[$itemIdx]['tax']->percent / 100);

                    $estItem = EstimateItem::create([
                        'estimate_id' => $estimate->id,
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'quantity' => $qty,
                        'price' => $item->price,
                        'total' => $lineTotal,
                        'tax' => $lineTax,
                        'discount_type' => 'fixed',
                        'discount_val' => 0,
                        'discount' => 0,
                        'exchange_rate' => 1.0,
                        'base_price' => $item->price,
                        'base_discount_val' => 0,
                        'base_tax' => $lineTax,
                        'base_total' => $lineTotal,
                        'company_id' => $company->id,
                        'unit_name' => $itemDefs[$itemIdx]['unit']->name,
                    ]);

                    Tax::create([
                        'tax_type_id' => $itemDefs[$itemIdx]['tax']->id,
                        'estimate_item_id' => $estItem->id,
                        'name' => $itemDefs[$itemIdx]['tax']->name,
                        'percent' => $itemDefs[$itemIdx]['tax']->percent,
                        'amount' => $lineTax,
                        'company_id' => $company->id,
                        'currency_id' => $currency->id,
                        'exchange_rate' => 1.0,
                        'base_amount' => $lineTax,
                    ]);
                }
            }

            // ── Proforma Invoices ───────────────────────────────
            $proformaDefs = [
                ['customer' => 0, 'items' => [[0, 6], [1, 3]], 'days_ago' => 3],
                ['customer' => 4, 'items' => [[3, 1]], 'days_ago' => 8],
            ];

            foreach ($proformaDefs as $idx => $def) {
                $seq = $idx + 1;
                $proDate = $now->copy()->subDays($def['days_ago']);

                $subTotal = 0;
                $taxTotal = 0;
                foreach ($def['items'] as [$itemIdx, $qty]) {
                    $lineTotal = $items[$itemIdx]->price * $qty;
                    $lineTax = (int) round($lineTotal * $itemDefs[$itemIdx]['tax']->percent / 100);
                    $subTotal += $lineTotal;
                    $taxTotal += $lineTax;
                }
                $total = $subTotal + $taxTotal;

                $proforma = ProformaInvoice::create([
                    'proforma_invoice_date' => $proDate,
                    'expiry_date' => $proDate->copy()->addDays(15),
                    'proforma_invoice_number' => 'ПРО-' . str_pad($seq, 6, '0', STR_PAD_LEFT),
                    'customer_id' => $customers[$def['customer']]->id,
                    'company_id' => $company->id,
                    'created_by' => $user->id,
                    'currency_id' => $currency->id,
                    'exchange_rate' => 1.0,
                    'sub_total' => $subTotal,
                    'total' => $total,
                    'tax' => $taxTotal,
                    'base_sub_total' => $subTotal,
                    'base_total' => $total,
                    'base_tax' => $taxTotal,
                    'status' => ProformaInvoice::STATUS_SENT,
                    'sequence_number' => $seq,
                    'customer_sequence_number' => $seq,
                    'template_name' => 'proforma1',
                ]);
                $proforma->unique_hash = Hashids::connection(ProformaInvoice::class)->encode($proforma->id);
                $proforma->save();

                foreach ($def['items'] as [$itemIdx, $qty]) {
                    $item = $items[$itemIdx];
                    $lineTotal = $item->price * $qty;
                    $lineTax = (int) round($lineTotal * $itemDefs[$itemIdx]['tax']->percent / 100);

                    $proItem = ProformaInvoiceItem::create([
                        'proforma_invoice_id' => $proforma->id,
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'quantity' => $qty,
                        'price' => $item->price,
                        'total' => $lineTotal,
                        'tax' => $lineTax,
                        'discount_type' => 'fixed',
                        'discount_val' => 0,
                        'discount' => 0,
                        'exchange_rate' => 1.0,
                        'base_price' => $item->price,
                        'base_discount_val' => 0,
                        'base_tax' => $lineTax,
                        'base_total' => $lineTotal,
                        'company_id' => $company->id,
                        'unit_name' => $itemDefs[$itemIdx]['unit']->name,
                    ]);

                    Tax::create([
                        'tax_type_id' => $itemDefs[$itemIdx]['tax']->id,
                        'proforma_invoice_item_id' => $proItem->id,
                        'name' => $itemDefs[$itemIdx]['tax']->name,
                        'percent' => $itemDefs[$itemIdx]['tax']->percent,
                        'amount' => $lineTax,
                        'company_id' => $company->id,
                        'currency_id' => $currency->id,
                        'exchange_rate' => 1.0,
                        'base_amount' => $lineTax,
                    ]);
                }
            }

            // ── Bills ───────────────────────────────────────────
            $billDefs = [
                ['supplier' => 0, 'items' => [[5, 5], [6, 20]], 'status' => Bill::STATUS_DRAFT, 'paid' => Bill::PAID_STATUS_UNPAID, 'days_ago' => 3],
                ['supplier' => 1, 'items' => [[7, 10]], 'status' => Bill::STATUS_SENT, 'paid' => Bill::PAID_STATUS_UNPAID, 'days_ago' => 12],
                ['supplier' => 2, 'items' => [[5, 10], [7, 20]], 'status' => Bill::STATUS_COMPLETED, 'paid' => Bill::PAID_STATUS_PAID, 'days_ago' => 35],
            ];

            $bills = [];
            foreach ($billDefs as $idx => $def) {
                $seq = $idx + 1;
                $billDate = $now->copy()->subDays($def['days_ago']);

                $subTotal = 0;
                $taxTotal = 0;
                foreach ($def['items'] as [$itemIdx, $qty]) {
                    $lineTotal = $items[$itemIdx]->price * $qty;
                    $lineTax = (int) round($lineTotal * $itemDefs[$itemIdx]['tax']->percent / 100);
                    $subTotal += $lineTotal;
                    $taxTotal += $lineTax;
                }
                $total = $subTotal + $taxTotal;

                $bill = Bill::create([
                    'bill_date' => $billDate,
                    'due_date' => $billDate->copy()->addDays(30),
                    'bill_number' => 'СМТ-' . str_pad($seq, 6, '0', STR_PAD_LEFT),
                    'supplier_id' => $suppliers[$def['supplier']]->id,
                    'company_id' => $company->id,
                    'creator_id' => $user->id,
                    'currency_id' => $currency->id,
                    'exchange_rate' => 1.0,
                    'sub_total' => $subTotal,
                    'total' => $total,
                    'tax' => $taxTotal,
                    'due_amount' => $def['paid'] === Bill::PAID_STATUS_PAID ? 0 : $total,
                    'base_due_amount' => $def['paid'] === Bill::PAID_STATUS_PAID ? 0 : $total,
                    'base_sub_total' => $subTotal,
                    'base_total' => $total,
                    'base_tax' => $taxTotal,
                    'status' => $def['status'],
                    'paid_status' => $def['paid'],
                    'sequence_number' => $seq,
                    'tax_per_item' => 'YES',
                    'discount_per_item' => 'NO',
                    'template_name' => 'bill1',
                    'sent' => $def['status'] !== Bill::STATUS_DRAFT,
                ]);
                $bill->unique_hash = Hashids::connection(Bill::class)->encode($bill->id);
                $bill->save();

                foreach ($def['items'] as [$itemIdx, $qty]) {
                    $item = $items[$itemIdx];
                    $lineTotal = $item->price * $qty;
                    $lineTax = (int) round($lineTotal * $itemDefs[$itemIdx]['tax']->percent / 100);

                    $billItem = BillItem::create([
                        'bill_id' => $bill->id,
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'quantity' => $qty,
                        'price' => $item->price,
                        'total' => $lineTotal,
                        'tax' => $lineTax,
                        'discount_type' => 'fixed',
                        'discount_val' => 0,
                        'discount' => 0,
                        'exchange_rate' => 1.0,
                        'base_price' => $item->price,
                        'base_discount_val' => 0,
                        'base_tax' => $lineTax,
                        'base_total' => $lineTotal,
                        'company_id' => $company->id,
                        'unit_name' => $itemDefs[$itemIdx]['unit']->name,
                    ]);

                    Tax::create([
                        'tax_type_id' => $itemDefs[$itemIdx]['tax']->id,
                        'bill_item_id' => $billItem->id,
                        'name' => $itemDefs[$itemIdx]['tax']->name,
                        'percent' => $itemDefs[$itemIdx]['tax']->percent,
                        'amount' => $lineTax,
                        'company_id' => $company->id,
                        'currency_id' => $currency->id,
                        'exchange_rate' => 1.0,
                        'base_amount' => $lineTax,
                    ]);
                }

                $bills[$idx] = $bill;
            }

            // Bill payment for the PAID bill (#2)
            BillPayment::create([
                'bill_id' => $bills[2]->id,
                'amount' => $bills[2]->total,
                'payment_date' => $bills[2]->bill_date->copy()->addDays(10),
                'payment_method_id' => $bankMethod->id,
                'exchange_rate' => 1.0,
                'base_amount' => $bills[2]->total,
                'company_id' => $company->id,
                'creator_id' => $user->id,
                'notes' => 'Плаќање за сметка ' . $bills[2]->bill_number,
            ]);

            // ── Expenses ────────────────────────────────────────
            $expenseDefs = [
                ['cat' => 'Кирија', 'supplier' => 0, 'amount' => 2500000, 'days_ago' => 5, 'note' => 'Кирија за март 2026'],
                ['cat' => 'Комуналии', 'supplier' => null, 'amount' => 450000, 'days_ago' => 10, 'note' => 'ЕВН Македонија - струја'],
                ['cat' => 'Канцелариски материјали', 'supplier' => 0, 'amount' => 85000, 'days_ago' => 8, 'note' => 'Тонери и хартија'],
                ['cat' => 'Превоз', 'supplier' => null, 'amount' => 320000, 'days_ago' => 12, 'note' => 'Гориво за службено возило'],
                ['cat' => 'Интернет и телекомуникации', 'supplier' => 2, 'amount' => 180000, 'days_ago' => 7, 'note' => 'Месечна интернет претплата'],
            ];

            foreach ($expenseDefs as $def) {
                $cat = $categories->first(fn ($c) => $c->name === $def['cat']);
                Expense::create([
                    'expense_date' => $now->copy()->subDays($def['days_ago']),
                    'amount' => $def['amount'],
                    'base_amount' => $def['amount'],
                    'exchange_rate' => 1.0,
                    'notes' => $def['note'],
                    'expense_category_id' => $cat->id,
                    'company_id' => $company->id,
                    'supplier_id' => $def['supplier'] !== null ? $suppliers[$def['supplier']]->id : null,
                    'payment_method_id' => $cashMethod->id,
                    'currency_id' => $currency->id,
                    'creator_id' => $user->id,
                ]);
            }

            // ── Partner Linking ─────────────────────────────────
            $partner->companies()->attach($company->id, [
                'is_active' => true,
                'is_portfolio_managed' => true,
                'permissions' => json_encode([\App\Enums\PartnerPermission::FULL_ACCESS->value]),
                'invitation_status' => 'accepted',
                'accepted_at' => now(),
            ]);

            CompanySubscription::create([
                'company_id' => $company->id,
                'plan' => config('subscriptions.portfolio.company_trial_plan', 'standard'),
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(config('subscriptions.portfolio.company_trial_days', 14)),
                'started_at' => now(),
            ]);

            app(PortfolioTierService::class)->recalculate($partner->fresh());

            Log::info('Demo company seeded for partner', [
                'partner_id' => $partner->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
            ]);

            return $company;
        });
    }

    /**
     * Helper to create a payment for an invoice.
     */
    private function createPayment(Invoice $invoice, int $amount, int $seq, PaymentMethod $method, Currency $currency, Company $company, User $user): Payment
    {
        $payment = Payment::create([
            'payment_date' => $invoice->invoice_date->copy()->addDays(10),
            'payment_number' => 'ПЛА-' . str_pad($seq, 6, '0', STR_PAD_LEFT),
            'amount' => $amount,
            'notes' => 'Плаќање за фактура ' . $invoice->invoice_number,
            'customer_id' => $invoice->customer_id,
            'company_id' => $company->id,
            'creator_id' => $user->id,
            'currency_id' => $currency->id,
            'exchange_rate' => 1.0,
            'base_amount' => $amount,
            'payment_method_id' => $method->id,
            'invoice_id' => $invoice->id,
            'sequence_number' => $seq,
            'customer_sequence_number' => $seq,
        ]);
        $payment->unique_hash = Hashids::connection(Payment::class)->encode($payment->id);
        $payment->save();

        return $payment;
    }
} // CLAUDE-CHECKPOINT
