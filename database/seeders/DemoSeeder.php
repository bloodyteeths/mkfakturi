<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\TaxType;
use App\Models\Unit;
use App\Models\User;
use App\Space\InstallUtils;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade;
use Vinkla\Hashids\Facades\Hashids;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds for staging environment with realistic Macedonia business data.
     * This seeder creates comprehensive demo data for partner bureau testing and evaluation.
     */
    public function run(): void
    {
        // Get Macedonia currency (MKD)
        $mkdCurrency = Currency::where('code', 'MKD')->first();
        if (!$mkdCurrency) {
            $mkdCurrency = Currency::create([
                'name' => 'Macedonian Denar',
                'code' => 'MKD',
                'symbol' => 'ден',
                'precision' => 2,
                'thousand_separator' => '.',
                'decimal_separator' => ',',
                'swap_currency_symbol' => true,
            ]);
        }

        // Create demo user - Macedonia business owner
        $user = User::create([
            'email' => 'marko.petrovski@megasoft.mk',
            'name' => 'Марко Петровски',
            'role' => 'super admin',
            'password' => bcrypt('demo123'),
            'phone' => '+38970123456',
            'currency_id' => $mkdCurrency->id,
        ]);

        // Create demo company - Macedonia LLC
        $company = Company::create([
            'name' => 'Македонска Софтвер ДОО Скопје',
            'owner_id' => $user->id,
            'slug' => 'makedonska-softver-doo',
            'website' => 'https://megasoft.mk',
            'phone' => '+38970123456',
            'vat_number' => 'MK4080009501878',
            'currency_id' => $mkdCurrency->id,
        ]);

        $company->unique_hash = Hashids::connection(Company::class)->encode($company->id);
        $company->save();

        // Create company address in Skopje
        Address::create([
            'company_id' => $company->id,
            'name' => 'Македонска Софтвер ДОО Скопје',
            'address_street_1' => 'Бул. Кузман Јосифовски Питу бр. 17',
            'address_street_2' => 'Локал 5',
            'city' => 'Скопје',
            'state' => 'Скопски регион',
            'country_id' => 1, // Macedonia
            'zip' => '1000',
            'phone' => '+38970123456',
            'type' => Address::BILLING_TYPE,
        ]);

        $company->setupDefaultData();
        $user->companies()->attach($company->id);
        BouncerFacade::scope()->to($company->id);
        $user->assign('super admin');

        // Set Macedonia-specific user settings
        $user->setSettings([
            'language' => 'mk',
            'timezone' => 'Europe/Skopje',
            'date_format' => 'DD.MM.YYYY',
            'currency_id' => $mkdCurrency->id,
        ]);

        // Set Macedonia-specific company settings
        CompanySetting::setSettings([
            'currency' => $mkdCurrency->id,
            'date_format' => 'DD.MM.YYYY',
            'language' => 'mk',
            'timezone' => 'Europe/Skopje',
            'fiscal_year' => '1-12',
            'tax_per_item' => 'YES',
            'discount_per_item' => 'NO',
            'invoice_prefix' => 'ФАК-',
            'estimate_prefix' => 'ПОН-',
            'payment_prefix' => 'ПЛА-',
            'carbon_date_format' => 'd.m.Y',
            'moment_date_format' => 'DD.MM.YYYY',
        ], $company->id);

        // Create Macedonia VAT tax types
        $vat18 = TaxType::create([
            'name' => 'ДДВ 18%',
            'percent' => 18.0,
            'description' => 'Стандардна стапка на данок на додадена вредност',
            'compound_tax' => false,
            'collective_tax' => 0,
            'company_id' => $company->id,
        ]);

        $vat5 = TaxType::create([
            'name' => 'ДДВ 5%',
            'percent' => 5.0,
            'description' => 'Намалена стапка на данок на додадена вредност',
            'compound_tax' => false,
            'collective_tax' => 0,
            'company_id' => $company->id,
        ]);

        // Create Macedonia payment methods
        $paymentMethods = [
            'Готовина',
            'Банкарски трансфер',
            'Кредитна картичка',
            'Чек',
            'Вирман',
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::create([
                'name' => $method,
                'company_id' => $company->id,
            ]);
        }

        // Create Macedonia units
        $units = [
            'парче', 'комад', 'килограм', 'литар', 'метар', 'кутија', 'пакет', 'час', 'ден', 'месец'
        ];

        foreach ($units as $unit) {
            Unit::create([
                'name' => $unit,
                'company_id' => $company->id,
            ]);
        }

        // Create realistic Macedonia customers
        $customers = [
            [
                'name' => 'Тетекс АД Тетово',
                'contact_name' => 'Ана Стојановска',
                'email' => 'ana.stojanovska@teteks.mk',
                'phone' => '+38944567890',
                'website' => 'https://teteks.mk',
                'vat_number' => 'MK4080012345678',
                'address' => 'Индустриска зона бб, Тетово 1200',
            ],
            [
                'name' => 'Алкалоид АД Скопје',
                'contact_name' => 'Димитар Николов',
                'email' => 'dimitri.nikolov@alkaloid.mk',
                'phone' => '+38970234567',
                'website' => 'https://alkaloid.mk',
                'vat_number' => 'MK4080009876543',
                'address' => 'Бул. Александар Македонски 12, Скопје 1000',
            ],
            [
                'name' => 'Охридска Банка АД Охрид',
                'contact_name' => 'Марија Тодоровска',
                'email' => 'marija.todorovska@ohridskabanka.mk',
                'phone' => '+38946345678',
                'website' => 'https://ohridskabanka.mk',
                'vat_number' => 'MK4080011223344',
                'address' => 'Кеј Маршал Тито 3, Охрид 6000',
            ],
            [
                'name' => 'Макпетрол АД Скопје',
                'contact_name' => 'Петар Јордановски',
                'email' => 'petar.jordanovski@makpetrol.mk',
                'phone' => '+38970456789',
                'website' => 'https://makpetrol.mk',
                'vat_number' => 'MK4080005566778',
                'address' => 'Бул. Партизански одреди 14, Скопје 1000',
            ],
            [
                'name' => 'Силс ДООЕЛ Струмица',
                'contact_name' => 'Бранко Митревски',
                'email' => 'branko.mitreski@sils.mk',
                'phone' => '+38934567891',
                'website' => 'https://sils.mk',
                'vat_number' => 'MK4080007788990',
                'address' => 'Индустриска зона 15, Струмица 2400',
            ],
        ];

        $createdCustomers = [];
        foreach ($customers as $customerData) {
            $customer = Customer::create([
                'name' => $customerData['name'],
                'contact_name' => $customerData['contact_name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'website' => $customerData['website'],
                'vat_number' => $customerData['vat_number'],
                'company_id' => $company->id,
                'currency_id' => $mkdCurrency->id,
                'creator_id' => $user->id,
                'enable_portal' => true,
            ]);

            // Create billing address for each customer
            Address::create([
                'customer_id' => $customer->id,
                'name' => $customerData['name'],
                'address_street_1' => $customerData['address'],
                'city' => explode(' ', explode(',', $customerData['address'])[1] ?? 'Скопје')[0],
                'state' => 'Македонија',
                'country_id' => 1,
                'zip' => explode(' ', $customerData['address'])[-1] ?? '1000',
                'phone' => $customerData['phone'],
                'type' => Address::BILLING_TYPE,
            ]);

            $createdCustomers[] = $customer;
        }

        // Create Macedonia business items/services
        $items = [
            [
                'name' => 'Софтверски развој',
                'description' => 'Развој на веб апликации и мобилни апликации',
                'price' => 150000, // 1500.00 MKD per hour
                'unit' => 'час',
                'tax' => $vat18->id,
            ],
            [
                'name' => 'IT Консултации',
                'description' => 'Професионални IT консултации и советување',
                'price' => 120000, // 1200.00 MKD per hour
                'unit' => 'час',
                'tax' => $vat18->id,
            ],
            [
                'name' => 'Хостинг услуги',
                'description' => 'Месечни хостинг услуги за веб страници',
                'price' => 300000, // 3000.00 MKD per month
                'unit' => 'месец',
                'tax' => $vat18->id,
            ],
            [
                'name' => 'Домен регистрација',
                'description' => 'Годишна регистрација на .mk домени',
                'price' => 150000, // 1500.00 MKD per year
                'unit' => 'парче',
                'tax' => $vat5->id,
            ],
            [
                'name' => 'Техничка поддршка',
                'description' => 'Техничка поддршка и одржување на системи',
                'price' => 100000, // 1000.00 MKD per hour
                'unit' => 'час',
                'tax' => $vat18->id,
            ],
        ];

        $createdItems = [];
        foreach ($items as $itemData) {
            $unit = Unit::where('name', $itemData['unit'])->where('company_id', $company->id)->first();
            
            $item = Item::create([
                'name' => $itemData['name'],
                'description' => $itemData['description'],
                'price' => $itemData['price'],
                'unit_id' => $unit->id,
                'company_id' => $company->id,
                'currency_id' => $mkdCurrency->id,
                'creator_id' => $user->id,
                'tax_per_item' => true,
            ]);

            // Add tax to item
            Tax::create([
                'tax_type_id' => $itemData['tax'],
                'item_id' => $item->id,
                'percent' => $itemData['tax'] == $vat18->id ? 18.0 : 5.0,
                'amount' => ($itemData['price'] * ($itemData['tax'] == $vat18->id ? 18.0 : 5.0)) / 100,
                'company_id' => $company->id,
                'currency_id' => $mkdCurrency->id,
            ]);

            $createdItems[] = $item;
        }

        // Create sample invoices
        $invoiceData = [
            [
                'customer' => $createdCustomers[0],
                'items' => [
                    ['item' => $createdItems[0], 'quantity' => 40, 'description' => 'Развој на веб платформа за е-трговија'],
                    ['item' => $createdItems[2], 'quantity' => 12, 'description' => 'Хостинг за 12 месеци'],
                ],
                'date' => Carbon::now()->subDays(30),
                'due_date' => Carbon::now()->subDays(16),
                'notes' => 'Ви благодариме за соработката!',
            ],
            [
                'customer' => $createdCustomers[1],
                'items' => [
                    ['item' => $createdItems[1], 'quantity' => 20, 'description' => 'IT консултации за дигитална трансформација'],
                    ['item' => $createdItems[4], 'quantity' => 10, 'description' => 'Техничка поддршка'],
                ],
                'date' => Carbon::now()->subDays(15),
                'due_date' => Carbon::now()->addDays(1),
                'notes' => 'Плаќање во рок од 14 дена.',
            ],
            [
                'customer' => $createdCustomers[2],
                'items' => [
                    ['item' => $createdItems[3], 'quantity' => 5, 'description' => 'Регистрација на домени za банката'],
                    ['item' => $createdItems[2], 'quantity' => 6, 'description' => 'Хостинг услуги'],
                ],
                'date' => Carbon::now()->subDays(7),
                'due_date' => Carbon::now()->addDays(7),
                'notes' => 'Месечно плаќање согласно договор.',
            ],
        ];

        $createdInvoices = [];
        foreach ($invoiceData as $index => $invData) {
            $subTotal = 0;
            $taxTotal = 0;

            // Calculate totals
            foreach ($invData['items'] as $invItem) {
                $lineTotal = $invItem['item']->price * $invItem['quantity'];
                $subTotal += $lineTotal;
                
                $tax = Tax::where('item_id', $invItem['item']->id)->first();
                if ($tax) {
                    $taxTotal += ($lineTotal * $tax->percent) / 100;
                }
            }

            $total = $subTotal + $taxTotal;

            $invoice = Invoice::create([
                'invoice_date' => $invData['date'],
                'due_date' => $invData['due_date'],
                'invoice_number' => 'ФАК-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'reference_number' => 'REF-' . ($index + 1),
                'customer_id' => $invData['customer']->id,
                'company_id' => $company->id,
                'creator_id' => $user->id,
                'currency_id' => $mkdCurrency->id,
                'exchange_rate' => 1.0,
                'sub_total' => $subTotal,
                'total' => $total,
                'tax' => $taxTotal,
                'due_amount' => $total,
                'base_due_amount' => $total,
                'base_sub_total' => $subTotal,
                'base_total' => $total,
                'base_tax' => $taxTotal,
                'status' => Invoice::STATUS_SENT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'sequence_number' => $index + 1,
                'customer_sequence_number' => $index + 1,
                'notes' => $invData['notes'],
                'tax_per_item' => 'YES',
                'discount_per_item' => 'NO',
                'template_name' => 'facturino',
            ]);

            $invoice->unique_hash = Hashids::connection(Invoice::class)->encode($invoice->id);
            $invoice->save();

            // Create invoice items
            foreach ($invData['items'] as $invItem) {
                $lineTotal = $invItem['item']->price * $invItem['quantity'];
                $tax = Tax::where('item_id', $invItem['item']->id)->first();
                $taxAmount = $tax ? ($lineTotal * $tax->percent) / 100 : 0;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_id' => $invItem['item']->id,
                    'name' => $invItem['item']->name,
                    'description' => $invItem['description'],
                    'quantity' => $invItem['quantity'],
                    'price' => $invItem['item']->price,
                    'total' => $lineTotal,
                    'tax' => $taxAmount,
                    'discount_type' => 'fixed',
                    'discount_val' => 0,
                    'discount' => 0,
                    'exchange_rate' => 1.0,
                    'base_price' => $invItem['item']->price,
                    'base_discount_val' => 0,
                    'base_tax' => $taxAmount,
                    'base_total' => $lineTotal,
                    'company_id' => $company->id,
                    'currency_id' => $mkdCurrency->id,
                ]);

                // Add tax to invoice item
                if ($tax) {
                    Tax::create([
                        'tax_type_id' => $tax->tax_type_id,
                        'invoice_item_id' => InvoiceItem::where('invoice_id', $invoice->id)
                            ->where('item_id', $invItem['item']->id)
                            ->first()->id,
                        'percent' => $tax->percent,
                        'amount' => $taxAmount,
                        'company_id' => $company->id,
                        'currency_id' => $mkdCurrency->id,
                        'exchange_rate' => 1.0,
                        'base_amount' => $taxAmount,
                    ]);
                }
            }

            $createdInvoices[] = $invoice;
        }

        // Create sample payments for some invoices (with events disabled to avoid PDF generation issues)
        $paymentMethod = PaymentMethod::where('name', 'Банкарски трансфер')
            ->where('company_id', $company->id)->first();

        // Temporarily disable events to avoid PDF generation during seeding
        Payment::withoutEvents(function () use ($createdInvoices, $paymentMethod, $mkdCurrency, $company, $user) {
            foreach ([$createdInvoices[0], $createdInvoices[2]] as $index => $invoice) {
                $payment = Payment::create([
                    'payment_date' => $invoice->invoice_date->addDays(10),
                    'payment_number' => 'ПЛА-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                    'amount' => $invoice->total,
                    'notes' => 'Плаќање за фактура ' . $invoice->invoice_number,
                    'customer_id' => $invoice->customer_id,
                    'company_id' => $company->id,
                    'creator_id' => $user->id,
                    'currency_id' => $mkdCurrency->id,
                    'exchange_rate' => 1.0,
                    'base_amount' => $invoice->total,
                    'payment_method_id' => $paymentMethod->id,
                    'invoice_id' => $invoice->id,
                    'sequence_number' => $index + 1,
                    'customer_sequence_number' => $index + 1,
                ]);

                $payment->unique_hash = Hashids::connection(Payment::class)->encode($payment->id);
                $payment->save();

                // Update invoice payment status
                $invoice->update([
                    'due_amount' => 0,
                    'base_due_amount' => 0,
                    'paid_status' => Invoice::STATUS_PAID,
                    'status' => Invoice::STATUS_COMPLETED,
                ]);
            }
        });

        // Mark profile setup as complete
        Setting::setSetting('profile_complete', 'COMPLETED');

        // Create installation marker
        InstallUtils::createDbMarker();
    }
}

