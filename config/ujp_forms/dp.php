<?php

/**
 * ДП — Даночна пријава за данок на добивка (Annual Corporate Tax Return)
 *
 * This is the actual tax return form filed with UJP, distinct from the ДБ (Tax Balance)
 * which is the calculation worksheet. The ДП summarizes:
 *   - Revenue and expenses from the income statement
 *   - Tax base adjustments (non-deductible expenses, reductions)
 *   - Tax calculation at 10% flat rate
 *   - Advance payments and balance due/refund
 *
 * Based on Закон за данокот на добивка (Law on Corporate Income Tax)
 * Службен весник на РСМ бр. 112/2014, amendments through 2024
 *
 * Filing deadline: March 15 of the following year (electronic via e-Tax)
 *
 * Source types:
 *   'auto'           — Auto-calculated from IFRS accounting data
 *   'manual'         — User must enter manually
 *   'auto_or_manual' — Auto-calculated with manual override option
 *   'formula'        — Calculated from other fields (not editable)
 */
return [
    'form_code' => 'ДП',
    'form_title' => 'ДАНОЧНА ПРИЈАВА',
    'form_subtitle' => 'за данок на добивка',
    'sluzhben_vesnik' => 'Службен весник на РСМ бр. 112/2014',
    'tax_rate' => 0.10, // 10% corporate tax

    'sections' => [
        'I' => [
            'title' => 'ПРИХОДИ',
            'fields' => [
                ['aop' => '01', 'row' => '1', 'label' => 'Приходи од продажба на производи и услуги', 'source' => 'auto', 'auto_calc' => 'revenue_sales'],
                ['aop' => '02', 'row' => '2', 'label' => 'Финансиски приходи (камати, дивиденди, курсни разлики)', 'source' => 'auto', 'auto_calc' => 'revenue_financial'],
                ['aop' => '03', 'row' => '3', 'label' => 'Останати приходи', 'source' => 'auto', 'auto_calc' => 'revenue_other'],
                ['aop' => '04', 'row' => 'I', 'label' => 'ВКУПНИ ПРИХОДИ (01+02+03)', 'source' => 'formula', 'formula' => 'sum:01-03'],
            ],
        ],
        'II' => [
            'title' => 'РАСХОДИ',
            'fields' => [
                ['aop' => '05', 'row' => '4', 'label' => 'Трошоци за суровини и материјали', 'source' => 'auto', 'auto_calc' => 'expense_materials'],
                ['aop' => '06', 'row' => '5', 'label' => 'Трошоци за плати и надоместоци на вработени', 'source' => 'auto', 'auto_calc' => 'expense_salaries'],
                ['aop' => '07', 'row' => '6', 'label' => 'Трошоци за амортизација', 'source' => 'auto', 'auto_calc' => 'expense_depreciation'],
                ['aop' => '08', 'row' => '7', 'label' => 'Финансиски расходи (камати, курсни разлики)', 'source' => 'auto', 'auto_calc' => 'expense_financial'],
                ['aop' => '09', 'row' => '8', 'label' => 'Останати расходи', 'source' => 'auto', 'auto_calc' => 'expense_other'],
                ['aop' => '10', 'row' => 'II', 'label' => 'ВКУПНИ РАСХОДИ (05+06+07+08+09)', 'source' => 'formula', 'formula' => 'sum:05-09'],
            ],
        ],
        'III' => [
            'title' => 'ДОБИВКА / ЗАГУБА ПРЕД ОДАНОЧУВАЊЕ',
            'fields' => [
                ['aop' => '11', 'row' => 'III', 'label' => 'Добивка / загуба пред оданочување (I - II)', 'source' => 'formula', 'formula' => 'aop04-aop10', 'signed' => true],
            ],
        ],
        'IV' => [
            'title' => 'ДАНОЧНО НЕПРИЗНАЕНИ РАСХОДИ',
            'fields' => [
                ['aop' => '12', 'row' => '9', 'label' => 'Трошоци за репрезентација (90% непризнаени)', 'source' => 'auto_or_manual', 'auto_calc' => 'representation_excess_90pct'],
                ['aop' => '13', 'row' => '10', 'label' => 'Парични казни, пенали и казнени камати', 'source' => 'manual'],
                ['aop' => '14', 'row' => '11', 'label' => 'Донации над 5% од вкупниот приход', 'source' => 'manual'],
                ['aop' => '15', 'row' => '12', 'label' => 'Спонзорства над 3% од вкупниот приход', 'source' => 'manual'],
                ['aop' => '16', 'row' => '13', 'label' => 'Амортизација над пропишаните стапки', 'source' => 'auto_or_manual', 'auto_calc' => 'depreciation_excess'],
                ['aop' => '17', 'row' => '14', 'label' => 'Скриени исплати на добивки', 'source' => 'manual'],
                ['aop' => '18', 'row' => '15', 'label' => 'Расходи неповрзани со дејноста', 'source' => 'manual'],
                ['aop' => '19', 'row' => '16', 'label' => 'Останати непризнаени расходи', 'source' => 'manual'],
                ['aop' => '20', 'row' => 'IV', 'label' => 'ВКУПНО НЕПРИЗНАЕНИ РАСХОДИ (збир 12-19)', 'source' => 'formula', 'formula' => 'sum:12-19'],
            ],
        ],
        'V' => [
            'title' => 'ДАНОЧНА ОСНОВИЦА',
            'fields' => [
                ['aop' => '21', 'row' => '17', 'label' => 'Даночна основица пред намалување (III + IV)', 'source' => 'formula', 'formula' => 'aop11+aop20'],
                ['aop' => '22', 'row' => '18', 'label' => 'Намалување: реинвестирана добивка', 'source' => 'manual'],
                ['aop' => '23', 'row' => '19', 'label' => 'Намалување: пренесена загуба (до 3 години)', 'source' => 'manual'],
                ['aop' => '24', 'row' => '20', 'label' => 'Намалување: наплатени побарувања (претходно зголемена основа)', 'source' => 'manual'],
                ['aop' => '25', 'row' => '21', 'label' => 'Останати намалувања', 'source' => 'manual'],
                ['aop' => '26', 'row' => 'V', 'label' => 'ДАНОЧНА ОСНОВИЦА (21 - 22 - 23 - 24 - 25)', 'source' => 'formula', 'formula' => 'max(0, aop21-aop22-aop23-aop24-aop25)'],
            ],
        ],
        'VI' => [
            'title' => 'ДАНОК НА ДОБИВКА',
            'fields' => [
                ['aop' => '27', 'row' => '22', 'label' => 'Пресметан данок (V x 10%)', 'source' => 'formula', 'formula' => 'aop26*0.10'],
                ['aop' => '28', 'row' => '23', 'label' => 'Даночно ослободување за фискални апарати (до 10 уреди)', 'source' => 'manual'],
                ['aop' => '29', 'row' => '24', 'label' => 'Данок платен во странство (withholding tax credit)', 'source' => 'manual'],
                ['aop' => '30', 'row' => '25', 'label' => 'Даночно олеснување за донации во спорт (чл. 30-а ЗДД)', 'source' => 'manual'],
                ['aop' => '31', 'row' => 'VI', 'label' => 'ДАНОК НА ДОБИВКА ЗА ПЛАЌАЊЕ (27 - 28 - 29 - 30)', 'source' => 'formula', 'formula' => 'max(0, aop27-aop28-aop29-aop30)'],
            ],
        ],
        'VII' => [
            'title' => 'АКОНТАЦИИ',
            'fields' => [
                ['aop' => '32', 'row' => '26', 'label' => 'Платени аконтации на данок на добивка во текот на годината', 'source' => 'auto_or_manual', 'auto_calc' => 'advance_payments'],
                ['aop' => '33', 'row' => '27', 'label' => 'Повеќе платен данок пренесен од претходни периоди', 'source' => 'manual'],
                ['aop' => '34', 'row' => 'VII', 'label' => 'ВКУПНО АКОНТАЦИИ (32 + 33)', 'source' => 'formula', 'formula' => 'sum:32-33'],
            ],
        ],
        'VIII' => [
            'title' => 'РАЗЛИКА ЗА ДОПЛАТА / ПОВРАТ',
            'fields' => [
                ['aop' => '35', 'row' => 'VIII', 'label' => 'Разлика за доплата / поврат (VI - VII)', 'source' => 'formula', 'formula' => 'aop31-aop34', 'signed' => true],
            ],
        ],
    ],
];

// CLAUDE-CHECKPOINT
