<?php

/**
 * UJP AOP Code Mapping for Macedonian Financial Reports
 *
 * @deprecated The obrazec_36 and obrazec_37 sections are DEPRECATED.
 *             Use config/ujp_forms/obrazec_36.php and config/ujp_forms/obrazec_37.php instead.
 *             Those configs have the full 112+44 AOP codes with account_code_to_aop mappings.
 *             AopReportService now reads from ujp_forms configs as of 2026-03-08.
 *
 * Only obrazec_38 (Cash Flow) remains active in this file.
 *
 * Each row: aop code, Macedonian label, IFRS types that contribute, hierarchy.
 * Subtotal rows have 'sum_of' pointing to child AOP codes.
 * Leaf rows have 'ifrs_types' listing which IFRS account types feed into them.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Образец 36 — Биланс на состојба (Balance Sheet)
    |--------------------------------------------------------------------------
    */
    'obrazec_36' => [
        'aktiva' => [
            ['aop' => '001', 'label' => 'ВКУПНА АКТИВА', 'is_total' => true, 'sum_of' => ['002', '030'], 'indent' => 0],
            // А. Нетековни средства
            ['aop' => '002', 'label' => 'А. Нетековни средства', 'is_total' => true, 'sum_of' => ['003', '010', '020'], 'indent' => 1],
            ['aop' => '003', 'label' => 'Нематеријални средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'intangible', 'indent' => 2],
            ['aop' => '010', 'label' => 'Материјални средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'tangible', 'indent' => 2],
            ['aop' => '020', 'label' => 'Долгорочни финансиски вложувања', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'financial', 'indent' => 2],
            // Б. Тековни средства
            ['aop' => '030', 'label' => 'Б. Тековни средства', 'is_total' => true, 'sum_of' => ['031', '040', '050'], 'indent' => 1],
            ['aop' => '031', 'label' => 'Залихи', 'ifrs_types' => ['INVENTORY'], 'indent' => 2],
            ['aop' => '040', 'label' => 'Краткорочни побарувања', 'ifrs_types' => ['RECEIVABLE', 'CURRENT_ASSET'], 'indent' => 2],
            ['aop' => '050', 'label' => 'Парични средства', 'ifrs_types' => ['BANK'], 'indent' => 2],
        ],
        'pasiva' => [
            ['aop' => '060', 'label' => 'ВКУПНА ПАСИВА', 'is_total' => true, 'sum_of' => ['061', '075', '085'], 'indent' => 0],
            // А. Главнина и резерви
            ['aop' => '061', 'label' => 'А. Капитал и резерви', 'is_total' => true, 'sum_of' => ['062', '070'], 'indent' => 1],
            ['aop' => '062', 'label' => 'Основен капитал', 'ifrs_types' => ['EQUITY'], 'filter' => 'share_capital', 'indent' => 2],
            ['aop' => '070', 'label' => 'Задржана добивка / загуба', 'ifrs_types' => ['EQUITY'], 'filter' => 'retained', 'indent' => 2],
            // Б. Долгорочни обврски
            ['aop' => '075', 'label' => 'Б. Долгорочни обврски', 'ifrs_types' => ['NON_CURRENT_LIABILITY', 'RECONCILIATION'], 'indent' => 1],
            // В. Краткорочни обврски
            ['aop' => '085', 'label' => 'В. Краткорочни обврски', 'is_total' => true, 'sum_of' => ['090', '100', '086'], 'indent' => 1],
            ['aop' => '090', 'label' => 'Обврски кон добавувачи', 'ifrs_types' => ['PAYABLE'], 'indent' => 2],
            ['aop' => '100', 'label' => 'Обврски за даноци и придонеси', 'ifrs_types' => ['CONTROL'], 'indent' => 2],
            ['aop' => '086', 'label' => 'Останати краткорочни обврски', 'ifrs_types' => ['CURRENT_LIABILITY'], 'indent' => 2],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Образец 37 — Биланс на успех (Income Statement)
    |--------------------------------------------------------------------------
    */
    'obrazec_37' => [
        'prihodi' => [
            ['aop' => '201', 'label' => 'Приходи од продажба', 'ifrs_types' => ['OPERATING_REVENUE'], 'indent' => 1],
            ['aop' => '210', 'label' => 'Останати оперативни приходи', 'ifrs_types' => ['NON_OPERATING_REVENUE'], 'filter' => 'other', 'indent' => 1],
            ['aop' => '220', 'label' => 'Финансиски приходи', 'ifrs_types' => ['NON_OPERATING_REVENUE'], 'filter' => 'financial', 'indent' => 1],
            ['aop' => '246', 'label' => 'ВКУПНИ ПРИХОДИ', 'is_total' => true, 'sum_of' => ['201', '210', '220'], 'indent' => 0],
        ],
        'rashodi' => [
            ['aop' => '251', 'label' => 'Набавна вредност на продадени стоки', 'ifrs_types' => ['DIRECT_EXPENSE'], 'indent' => 1],
            ['aop' => '260', 'label' => 'Трошоци за вработени', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'employee', 'indent' => 1],
            ['aop' => '270', 'label' => 'Амортизација', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'depreciation', 'indent' => 1],
            ['aop' => '275', 'label' => 'Останати расходи од работењето', 'ifrs_types' => ['OPERATING_EXPENSE', 'OVERHEAD_EXPENSE', 'OTHER_EXPENSE'], 'filter' => 'other', 'indent' => 1],
            ['aop' => '280', 'label' => 'Финансиски расходи', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'financial', 'indent' => 1],
            ['aop' => '293', 'label' => 'ВКУПНИ РАСХОДИ', 'is_total' => true, 'sum_of' => ['251', '260', '270', '275', '280'], 'indent' => 0],
        ],
        'rezultat' => [
            ['aop' => '244', 'label' => 'Добивка од работењето', 'formula' => 'profit', 'condition' => 'positive', 'indent' => 0],
            ['aop' => '245', 'label' => 'Загуба од работењето', 'formula' => 'loss', 'condition' => 'negative', 'indent' => 0],
            ['aop' => '248', 'label' => 'Добивка пред оданочување', 'formula' => 'profit_before_tax', 'condition' => 'positive', 'indent' => 0],
            ['aop' => '249', 'label' => 'Загуба пред оданочување', 'formula' => 'loss_before_tax', 'condition' => 'negative', 'indent' => 0],
            ['aop' => '250', 'label' => 'Данок на добивка (10%)', 'formula' => 'tax', 'indent' => 0],
            ['aop' => '255', 'label' => 'Нето добивка', 'formula' => 'net_profit', 'condition' => 'positive', 'indent' => 0],
            ['aop' => '256', 'label' => 'Нето загуба', 'formula' => 'net_loss', 'condition' => 'negative', 'indent' => 0],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Образец 38 — Извештај за парични текови (Cash Flow Statement)
    |--------------------------------------------------------------------------
    */
    'obrazec_38' => [
        'operating' => [
            ['aop' => '301', 'label' => 'I. ПАРИЧНИ ТЕКОВИ ОД ОПЕРАТИВНИ АКТИВНОСТИ', 'is_total' => false, 'indent' => 0],
            ['aop' => '302', 'label' => 'Нето добивка / загуба', 'data_key' => 'operating.net_income', 'indent' => 1],
            ['aop' => '303', 'label' => 'Амортизација', 'data_key' => 'operating.depreciation', 'indent' => 1],
            ['aop' => '304', 'label' => 'Промени во побарувања', 'data_key' => 'operating.receivables_change', 'indent' => 1],
            ['aop' => '305', 'label' => 'Промени во залихи', 'data_key' => 'operating.inventory_change', 'indent' => 1],
            ['aop' => '306', 'label' => 'Промени во обврски кон добавувачи', 'data_key' => 'operating.payables_change', 'indent' => 1],
            ['aop' => '307', 'label' => 'Промени во даночни обврски', 'data_key' => 'operating.tax_payable_change', 'indent' => 1],
            ['aop' => '308', 'label' => 'Останати оперативни промени', 'data_key' => 'operating.other_current_liabilities_change', 'indent' => 1],
            ['aop' => '310', 'label' => 'Нето парични средства од оперативни активности', 'data_key' => 'operating.total', 'is_total' => true, 'indent' => 0],
        ],
        'investing' => [
            ['aop' => '320', 'label' => 'II. ПАРИЧНИ ТЕКОВИ ОД ИНВЕСТИЦИСКИ АКТИВНОСТИ', 'is_total' => false, 'indent' => 0],
            ['aop' => '321', 'label' => 'Набавка/продажба на основни средства', 'data_key' => 'investing.fixed_assets', 'indent' => 1],
            ['aop' => '322', 'label' => 'Набавка/продажба на нематеријални средства', 'data_key' => 'investing.intangible_assets', 'indent' => 1],
            ['aop' => '323', 'label' => 'Долгорочни финансиски вложувања', 'data_key' => 'investing.investments', 'indent' => 1],
            ['aop' => '330', 'label' => 'Нето парични средства од инвестициски активности', 'data_key' => 'investing.total', 'is_total' => true, 'indent' => 0],
        ],
        'financing' => [
            ['aop' => '340', 'label' => 'III. ПАРИЧНИ ТЕКОВИ ОД ФИНАНСИСКИ АКТИВНОСТИ', 'is_total' => false, 'indent' => 0],
            ['aop' => '341', 'label' => 'Промени во долгорочни обврски', 'data_key' => 'financing.long_term_debt', 'indent' => 1],
            ['aop' => '342', 'label' => 'Промени во краткорочни обврски', 'data_key' => 'financing.short_term_debt', 'indent' => 1],
            ['aop' => '343', 'label' => 'Промени во капитал', 'data_key' => 'financing.equity_changes', 'indent' => 1],
            ['aop' => '350', 'label' => 'Нето парични средства од финансиски активности', 'data_key' => 'financing.total', 'is_total' => true, 'indent' => 0],
        ],
        'summary' => [
            ['aop' => '360', 'label' => 'Парични средства на почеток на периодот', 'data_key' => 'summary.cash_start', 'indent' => 0],
            ['aop' => '370', 'label' => 'Нето промена на парични средства', 'data_key' => 'summary.net_change', 'indent' => 0],
            ['aop' => '380', 'label' => 'Парични средства на крај на периодот', 'data_key' => 'summary.cash_end', 'is_total' => true, 'indent' => 0],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFRS Account Type → AOP fallback mapping
    |--------------------------------------------------------------------------
    | When an IFRS type has no 'filter' sub-classification, the entire balance
    | goes to the primary AOP code listed here.
    */
    'ifrs_to_aop_fallback' => [
        // Balance Sheet — Assets
        'NON_CURRENT_ASSET' => '010',  // Default to Материјални средства (leaf node)
        'CONTRA_ASSET'      => '010',  // Subtracted from non-current assets
        'INVENTORY'         => '031',
        'BANK'              => '050',
        'CURRENT_ASSET'     => '040',
        'RECEIVABLE'        => '040',
        // Balance Sheet — Liabilities & Equity
        'NON_CURRENT_LIABILITY' => '075',
        'CONTROL'               => '100',
        'CURRENT_LIABILITY'     => '086',
        'PAYABLE'               => '090',
        'RECONCILIATION'        => '075',
        'EQUITY'                => '062',  // Default to Основен капитал (leaf node)
        // Income Statement — Revenue
        'OPERATING_REVENUE'     => '201',
        'NON_OPERATING_REVENUE' => '210',
        // Income Statement — Expenses
        'DIRECT_EXPENSE'   => '251',
        'OPERATING_EXPENSE' => '275',  // Catch-all for unclassified operating expenses
        'OVERHEAD_EXPENSE'  => '275',
        'OTHER_EXPENSE'     => '275',
    ],
];

