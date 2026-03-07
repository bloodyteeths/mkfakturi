<?php

/**
 * Образец 37 — Биланс на успех (Income Statement)
 *
 * Official AOP codes 201-244 per Службен Весник.
 * Used for годишна сметка submission to Central Register (e-submit.crm.com.mk).
 *
 * Structure matches the official form exactly:
 * - I. ПРИХОДИ ОД РАБОТЕЊЕТО (AOP 201-206)
 * - II. РАСХОДИ ОД РАБОТЕЊЕТО (AOP 207-222)
 * - III. ФИНАНСИСКИ ПРИХОДИ/РАСХОДИ (AOP 223-224)
 * - IV. РЕЗУЛТАТ (AOP 225-244)
 */

return [

    'form_id' => 18,
    'sluzhben_vesnik' => 'Правилник за годишна сметка (Сл. весник на РМ бр. 52/11, 174/11, 9/12, 13/12, 60/14)',

    /*
    |--------------------------------------------------------------------------
    | I. ПРИХОДИ ОД РАБОТЕЊЕТО (Operating Revenue) — AOP 201-206
    |--------------------------------------------------------------------------
    */
    'prihodi' => [
        ['aop' => '201', 'label' => 'I. ПРИХОДИ ОД РАБОТЕЊЕТО', 'is_total' => true, 'sum_of' => ['202', '203', '206'], 'indent' => 0],
        ['aop' => '202', 'label' => 'Приходи од продажба', 'ifrs_types' => ['OPERATING_REVENUE'], 'indent' => 1],
        ['aop' => '203', 'label' => 'Останати приходи од работењето', 'ifrs_types' => ['NON_OPERATING_REVENUE'], 'filter' => 'operating_other', 'indent' => 1],
        ['aop' => '204', 'label' => 'Залихи на готови производи на почеток на годината', 'ifrs_types' => [], 'indent' => 1, 'note' => 'inventory_beg'],
        ['aop' => '205', 'label' => 'Залихи на готови производи на крај на годината', 'ifrs_types' => [], 'indent' => 1, 'note' => 'inventory_end'],
        ['aop' => '206', 'label' => 'Капитализирано сопствено производство и услуги', 'ifrs_types' => ['NON_OPERATING_REVENUE'], 'filter' => 'capitalized', 'indent' => 1],
    ],

    /*
    |--------------------------------------------------------------------------
    | II. РАСХОДИ ОД РАБОТЕЊЕТО (Operating Expenses) — AOP 207-222
    |--------------------------------------------------------------------------
    */
    'rashodi' => [
        ['aop' => '207', 'label' => 'II. РАСХОДИ ОД РАБОТЕЊЕТО', 'is_total' => true, 'sum_of' => ['208', '209', '210', '211', '212', '213', '218', '219', '220', '221', '222'], 'indent' => 0],
        ['aop' => '208', 'label' => 'Трошоци за суровини и други материјали', 'ifrs_types' => ['DIRECT_EXPENSE'], 'filter' => 'materials', 'indent' => 1],
        ['aop' => '209', 'label' => 'Набавна вредност на продадени стоки', 'ifrs_types' => ['DIRECT_EXPENSE'], 'indent' => 1],
        ['aop' => '210', 'label' => 'Набавна вредност на продадени материјали, резервни делови', 'ifrs_types' => ['DIRECT_EXPENSE'], 'filter' => 'materials_sold', 'indent' => 1],
        ['aop' => '211', 'label' => 'Услуги со карактер на материјални трошоци', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'material_services', 'indent' => 1],
        ['aop' => '212', 'label' => 'Останати трошоци од работењето', 'ifrs_types' => ['OPERATING_EXPENSE', 'OVERHEAD_EXPENSE'], 'filter' => 'other_operating', 'indent' => 1],
        ['aop' => '213', 'label' => 'Трошоци за вработени', 'is_total' => true, 'sum_of' => ['214', '215', '216', '217'], 'indent' => 1],
        ['aop' => '214', 'label' => 'Плати и надоместоци на плата — нето', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'salaries_net', 'indent' => 2],
        ['aop' => '215', 'label' => 'Трошоци за даноци на плати и надоместоци', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'salary_taxes', 'indent' => 2],
        ['aop' => '216', 'label' => 'Придонеси за задолжително социјално осигурување', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'social_insurance', 'indent' => 2],
        ['aop' => '217', 'label' => 'Останати трошоци за вработените', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'other_employee', 'indent' => 2],
        ['aop' => '218', 'label' => 'Амортизација на материјални и нематеријални средства', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'depreciation', 'indent' => 1],
        ['aop' => '219', 'label' => 'Вредносно усогласување на нетековни средства', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'impairment_noncurrent', 'indent' => 1],
        ['aop' => '220', 'label' => 'Вредносно усогласување на тековни средства', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'impairment_current', 'indent' => 1],
        ['aop' => '221', 'label' => 'Резервирања за трошоци и ризици', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'provisions', 'indent' => 1],
        ['aop' => '222', 'label' => 'Останати расходи', 'ifrs_types' => ['OPERATING_EXPENSE', 'OTHER_EXPENSE'], 'indent' => 1],
    ],

    /*
    |--------------------------------------------------------------------------
    | III-IV. ФИНАНСИСКИ + РЕЗУЛТАТ — AOP 223-244
    |--------------------------------------------------------------------------
    | Result rows use 'formula' key for conditional calculations.
    */
    'rezultat' => [
        ['aop' => '223', 'label' => 'III. ФИНАНСИСКИ ПРИХОДИ', 'ifrs_types' => ['NON_OPERATING_REVENUE'], 'filter' => 'financial', 'indent' => 0],
        ['aop' => '224', 'label' => 'IV. ФИНАНСИСКИ РАСХОДИ', 'ifrs_types' => ['OPERATING_EXPENSE'], 'filter' => 'financial_expense', 'indent' => 0],
        ['aop' => '225', 'label' => 'ДОБИВКА ОД РЕДОВНОТО РАБОТЕЊЕ', 'formula' => 'operating_profit', 'indent' => 0],
        ['aop' => '226', 'label' => 'ЗАГУБА ОД РЕДОВНОТО РАБОТЕЊЕ', 'formula' => 'operating_loss', 'indent' => 0],
        ['aop' => '227', 'label' => 'Удел во добивката на придружени друштва', 'ifrs_types' => [], 'indent' => 1],
        ['aop' => '228', 'label' => 'Удел во загубата на придружени друштва', 'ifrs_types' => [], 'indent' => 1],
        ['aop' => '229', 'label' => 'ДОБИВКА ПРЕД ОДАНОЧУВАЊЕ', 'formula' => 'profit_before_tax', 'indent' => 0],
        ['aop' => '230', 'label' => 'ЗАГУБА ПРЕД ОДАНОЧУВАЊЕ', 'formula' => 'loss_before_tax', 'indent' => 0],
        ['aop' => '231', 'label' => 'Данок на добивка', 'formula' => 'tax', 'indent' => 1],
        ['aop' => '232', 'label' => 'Одложени даноци за периодот', 'ifrs_types' => [], 'indent' => 1],
        ['aop' => '233', 'label' => 'НЕТО ДОБИВКА ЗА ДЕЛОВНАТА ГОДИНА', 'formula' => 'net_profit', 'indent' => 0],
        ['aop' => '234', 'label' => 'НЕТО ЗАГУБА ЗА ДЕЛОВНАТА ГОДИНА', 'formula' => 'net_loss', 'indent' => 0],
        ['aop' => '235', 'label' => 'Нето добивка на сопствениците на матично друштво', 'ifrs_types' => [], 'indent' => 1, 'consolidated_only' => true],
        ['aop' => '236', 'label' => 'Нето добивка на неконтролирано учество', 'ifrs_types' => [], 'indent' => 1, 'consolidated_only' => true],
        ['aop' => '237', 'label' => 'Нето загуба на сопствениците на матично друштво', 'ifrs_types' => [], 'indent' => 1, 'consolidated_only' => true],
        ['aop' => '238', 'label' => 'Нето загуба на неконтролирано учество', 'ifrs_types' => [], 'indent' => 1, 'consolidated_only' => true],
        ['aop' => '239', 'label' => 'Останата сеопфатна добивка/загуба пред оданочување', 'ifrs_types' => [], 'indent' => 1],
        ['aop' => '240', 'label' => 'Данок на добивка на останатата сеопфатна добивка', 'ifrs_types' => [], 'indent' => 1],
        ['aop' => '241', 'label' => 'НЕТО ОСТАНАТА СЕОПФАТНА ДОБИВКА/ЗАГУБА', 'formula' => 'net_oci', 'indent' => 0],
        ['aop' => '242', 'label' => 'ВКУПНА СЕОПФАТНА ДОБИВКА/ЗАГУБА', 'formula' => 'total_comprehensive', 'indent' => 0],
        ['aop' => '243', 'label' => 'ВКУПНИ ПРИХОДИ', 'formula' => 'total_revenue', 'indent' => 0],
        ['aop' => '244', 'label' => 'ВКУПНИ РАСХОДИ', 'formula' => 'total_expenses', 'indent' => 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFRS Type → AOP Fallback Mapping (for official Образец 37)
    |--------------------------------------------------------------------------
    */
    'ifrs_to_aop_fallback' => [
        'OPERATING_REVENUE'     => '202', // Sales revenue
        'NON_OPERATING_REVENUE' => '203', // Other operating revenue (default)
        'DIRECT_EXPENSE'        => '209', // COGS
        'OPERATING_EXPENSE'     => '222', // Other expenses (catch-all)
        'OVERHEAD_EXPENSE'      => '212', // Other operating expenses
        'OTHER_EXPENSE'         => '222', // Other expenses
    ],
];

// CLAUDE-CHECKPOINT
