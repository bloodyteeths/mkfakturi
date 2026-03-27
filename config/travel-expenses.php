<?php

/**
 * Travel expense category → GL account mapping.
 * Per Правилник за контниот план 174/2011.
 */
return [
    'categories' => [
        'per_diem' => [
            'gl_code' => '440',
            'gl_name' => 'Дневници за службени патувања, ноќевања и патни трошоци',
            'vat_deductible' => false,
            'label_mk' => 'Дневници',
            'label_en' => 'Per Diem',
            'label_sq' => 'Dieta Ditore',
            'label_tr' => 'Harcırah',
        ],
        'fuel' => [
            'gl_code' => '403',
            'gl_name' => 'Трошоци за енергија (горива)',
            'vat_deductible' => true,
            'label_mk' => 'Гориво',
            'label_en' => 'Fuel',
            'label_sq' => 'Karburant',
            'label_tr' => 'Yakıt',
        ],
        'tolls' => [
            'gl_code' => '449',
            'gl_name' => 'Останати трошоци од работењето (патарини/паркинг)',
            'vat_deductible' => true, // domestic tolls only
            'label_mk' => 'Патарини/паркинг',
            'label_en' => 'Tolls/Parking',
            'label_sq' => 'Taksa rrugore/Parking',
            'label_tr' => 'Otoyol/Otopark',
        ],
        'forwarding' => [
            'gl_code' => '419',
            'gl_name' => 'Останати услуги (шпедитерски)',
            'vat_deductible' => true,
            'label_mk' => 'Шпедитерски услуги',
            'label_en' => 'Forwarding Services',
            'label_sq' => 'Shërbime spedicionere',
            'label_tr' => 'Nakliye Hizmetleri',
        ],
        'accommodation' => [
            'gl_code' => '440',
            'gl_name' => 'Дневници за службени патувања, ноќевања и патни трошоци',
            'vat_deductible' => true,
            'label_mk' => 'Смештај',
            'label_en' => 'Accommodation',
            'label_sq' => 'Akomodimi',
            'label_tr' => 'Konaklama',
        ],
        'transport' => [
            'gl_code' => '440',
            'gl_name' => 'Дневници за службени патувања, ноќевања и патни трошоци',
            'vat_deductible' => true,
            'label_mk' => 'Превоз (билети)',
            'label_en' => 'Transport (tickets)',
            'label_sq' => 'Transport (bileta)',
            'label_tr' => 'Ulaşım (bilet)',
        ],
        'vehicle_maintenance' => [
            'gl_code' => '410',
            'gl_name' => 'Транспортни услуги',
            'vat_deductible' => true,
            'label_mk' => 'Сервис на возило',
            'label_en' => 'Vehicle Maintenance',
            'label_sq' => 'Mirëmbajtje e automjetit',
            'label_tr' => 'Araç Bakım',
        ],
        'communication' => [
            'gl_code' => '419',
            'gl_name' => 'Останати услуги (комуникации)',
            'vat_deductible' => true,
            'label_mk' => 'Комуникации',
            'label_en' => 'Communication',
            'label_sq' => 'Komunikim',
            'label_tr' => 'İletişim',
        ],
        'meals' => [
            'gl_code' => '440',
            'gl_name' => 'Дневници за службени патувања, ноќевања и патни трошоци',
            'vat_deductible' => false, // Art. 34 DDV — employee meals not deductible
            'label_mk' => 'Оброци',
            'label_en' => 'Meals',
            'label_sq' => 'Vakte ushqimore',
            'label_tr' => 'Yemek',
        ],
        'other' => [
            'gl_code' => '449',
            'gl_name' => 'Останати трошоци од работењето',
            'vat_deductible' => false,
            'label_mk' => 'Останато',
            'label_en' => 'Other',
            'label_sq' => 'Tjetër',
            'label_tr' => 'Diğer',
        ],
    ],

    // GL accounts used in journal entry posting
    'gl_accounts' => [
        'advance' => '143',  // Побарувања за аконтации за служб. патувања
        'cash' => '102',     // Парични средства во благајна
        'bank' => '100',     // Парични средства на трансакциски сметки
        'vat_input' => '130', // Претходен ДДВ
    ],

    // Domestic per-diem defaults
    'domestic' => [
        'base_salary' => 42875, // Average net salary (MKD) — update when stat office publishes new data
        'rate' => 0.08,         // 8% of average salary
        'full_day_amount' => 3430, // MKD — for >12h or per 24h
        'half_day_amount' => 1715, // MKD — for 8-12h
    ],

    // Meal reduction percentages per government decree
    'meal_reductions' => [
        'breakfast' => 0.10,
        'lunch' => 0.30,
        'dinner' => 0.30,
    ],

    // Mileage rate for private vehicle
    'mileage' => [
        'rate_per_km' => 15, // MKD per km (30% of fuel price)
        'tax_free_monthly_cap' => 3500, // MKD
    ],

    // Default fuel norms (liters per 100km) by vehicle type
    'fuel_norms' => [
        'car' => 8.0,
        'van' => 12.0,
        'truck' => 35.0,
        'trailer' => 0.0, // trailers don't consume fuel
    ],
];

// CLAUDE-CHECKPOINT
