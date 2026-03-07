<?php

/**
 * Образец 36 — Биланс на состојба (Balance Sheet)
 *
 * Official AOP codes 001-112 per Службен Весник.
 * Used for годишна сметка submission to Central Register (e-submit.crm.com.mk).
 *
 * Structure matches the official form exactly:
 * - АКТИВА: AOP 001-064
 * - ПАСИВА: AOP 065-112
 *
 * Each row has: aop, label, indent, and either:
 * - is_total + sum_of (subtotal row)
 * - ifrs_types (leaf row mapped from IFRS data)
 * - input-only (manual entry, no auto-population)
 */

return [

    'form_id' => 17,
    'sluzhben_vesnik' => 'Правилник за годишна сметка (Сл. весник на РМ бр. 52/11, 174/11, 9/12, 13/12, 60/14)',

    /*
    |--------------------------------------------------------------------------
    | АКТИВА (Assets) — AOP 001-064
    |--------------------------------------------------------------------------
    */
    'aktiva' => [
        // A. НЕТЕКОВНИ СРЕДСТВА
        ['aop' => '001', 'label' => 'А. НЕТЕКОВНИ СРЕДСТВА', 'is_total' => true, 'sum_of' => ['002', '009', '020', '021', '031'], 'indent' => 0],

        // Нематеријални средства
        ['aop' => '002', 'label' => 'I. Нематеријални средства', 'is_total' => true, 'sum_of' => ['003', '004', '005', '006', '007', '008'], 'indent' => 1],
        ['aop' => '003', 'label' => 'Издатоци за развој', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'development', 'indent' => 2],
        ['aop' => '004', 'label' => 'Концесии, патенти, лиценци, заштитни знаци и слични права', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'patents', 'indent' => 2],
        ['aop' => '005', 'label' => 'Гудвил', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'goodwill', 'indent' => 2],
        ['aop' => '006', 'label' => 'Аванси за набавка на нематеријални средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'intangible_advances', 'indent' => 2],
        ['aop' => '007', 'label' => 'Нематеријални средства во подготовка', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'intangible_wip', 'indent' => 2],
        ['aop' => '008', 'label' => 'Останати нематеријални средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'intangible_other', 'indent' => 2],

        // Материјални средства
        ['aop' => '009', 'label' => 'II. Материјални средства', 'is_total' => true, 'sum_of' => ['010', '013', '014', '015', '016', '017', '018', '019'], 'indent' => 1],
        ['aop' => '010', 'label' => 'Земјиште и градежни објекти', 'is_total' => true, 'sum_of' => ['011', '012'], 'indent' => 2],
        ['aop' => '011', 'label' => 'Земјиште', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'land', 'indent' => 3],
        ['aop' => '012', 'label' => 'Градежни објекти', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'buildings', 'indent' => 3],
        ['aop' => '013', 'label' => 'Постројки и опрема', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'equipment', 'indent' => 2],
        ['aop' => '014', 'label' => 'Транспортни средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'vehicles', 'indent' => 2],
        ['aop' => '015', 'label' => 'Алат, погонски и канцелариски инвентар, мебел', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'tools', 'indent' => 2],
        ['aop' => '016', 'label' => 'Биолошки средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'biological', 'indent' => 2],
        ['aop' => '017', 'label' => 'Аванси за набавка на материјални средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'tangible_advances', 'indent' => 2],
        ['aop' => '018', 'label' => 'Материјални средства во подготовка', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'tangible_wip', 'indent' => 2],
        ['aop' => '019', 'label' => 'Останати материјални средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'tangible_other', 'indent' => 2],

        // Вложувања во недвижности
        ['aop' => '020', 'label' => 'III. Вложувања во недвижности', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'investment_property', 'indent' => 1],

        // Долгорочни финансиски средства
        ['aop' => '021', 'label' => 'IV. Долгорочни финансиски средства', 'is_total' => true, 'sum_of' => ['022', '023', '024', '025', '026', '030'], 'indent' => 1],
        ['aop' => '022', 'label' => 'Вложувања во подружници', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'subsidiaries', 'indent' => 2],
        ['aop' => '023', 'label' => 'Вложувања во придружени друштва и учества во заеднички вложувања', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'associates', 'indent' => 2],
        ['aop' => '024', 'label' => 'Побарувања по дадени долгорочни заеми на поврзани друштва', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'lt_loans_related', 'indent' => 2],
        ['aop' => '025', 'label' => 'Побарувања по дадени долгорочни заеми', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'lt_loans', 'indent' => 2],
        ['aop' => '026', 'label' => 'Вложувања во хартии од вредност', 'is_total' => true, 'sum_of' => ['027', '028', '029'], 'indent' => 2],
        ['aop' => '027', 'label' => 'Вложувања кои се чуваат до доспевање', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'htm', 'indent' => 3],
        ['aop' => '028', 'label' => 'Вложувања расположиви за продажба', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'afs', 'indent' => 3],
        ['aop' => '029', 'label' => 'Вложувања по објективна вредност преку добивка/загуба', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'fvtpl', 'indent' => 3],
        ['aop' => '030', 'label' => 'Останати долгорочни финансиски средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'lt_financial_other', 'indent' => 2],

        // Долгорочни побарувања
        ['aop' => '031', 'label' => 'V. Долгорочни побарувања', 'is_total' => true, 'sum_of' => ['032', '033', '034'], 'indent' => 1],
        ['aop' => '032', 'label' => 'Побарувања од поврзани друштва', 'ifrs_types' => ['RECEIVABLE'], 'filter' => 'lt_related', 'indent' => 2],
        ['aop' => '033', 'label' => 'Побарувања од купувачи (долгорочни)', 'ifrs_types' => ['RECEIVABLE'], 'filter' => 'lt_trade', 'indent' => 2],
        ['aop' => '034', 'label' => 'Останати долгорочни побарувања', 'ifrs_types' => ['RECEIVABLE'], 'filter' => 'lt_other', 'indent' => 2],

        // Одложени даночни средства (standalone)
        ['aop' => '035', 'label' => 'Одложени даночни средства', 'ifrs_types' => ['NON_CURRENT_ASSET'], 'filter' => 'deferred_tax_asset', 'indent' => 0],

        // Б. ТЕКОВНИ СРЕДСТВА
        ['aop' => '036', 'label' => 'Б. ТЕКОВНИ СРЕДСТВА', 'is_total' => true, 'sum_of' => ['037', '045', '052', '059'], 'indent' => 0],

        // Залихи
        ['aop' => '037', 'label' => 'I. Залихи', 'is_total' => true, 'sum_of' => ['038', '039', '040', '041', '042', '043'], 'indent' => 1],
        ['aop' => '038', 'label' => 'Залихи на суровини и материјали', 'ifrs_types' => ['INVENTORY'], 'filter' => 'raw_materials', 'indent' => 2],
        ['aop' => '039', 'label' => 'Залихи на резервни делови, ситен инвентар, амбалажа', 'ifrs_types' => ['INVENTORY'], 'filter' => 'spare_parts', 'indent' => 2],
        ['aop' => '040', 'label' => 'Залихи на недовршени производи и полупроизводи', 'ifrs_types' => ['INVENTORY'], 'filter' => 'wip', 'indent' => 2],
        ['aop' => '041', 'label' => 'Залихи на готови производи', 'ifrs_types' => ['INVENTORY'], 'filter' => 'finished_goods', 'indent' => 2],
        ['aop' => '042', 'label' => 'Залихи на трговски стоки', 'ifrs_types' => ['INVENTORY'], 'filter' => 'merchandise', 'indent' => 2],
        ['aop' => '043', 'label' => 'Залихи на биолошки средства', 'ifrs_types' => ['INVENTORY'], 'filter' => 'biological_inventory', 'indent' => 2],

        // Средства за продажба (standalone)
        ['aop' => '044', 'label' => 'Средства наменети за продажба и прекинати работења', 'ifrs_types' => ['CURRENT_ASSET'], 'filter' => 'held_for_sale', 'indent' => 0],

        // Краткорочни побарувања
        ['aop' => '045', 'label' => 'II. Краткорочни побарувања', 'is_total' => true, 'sum_of' => ['046', '047', '048', '049', '050', '051'], 'indent' => 1],
        ['aop' => '046', 'label' => 'Побарувања од поврзани друштва', 'ifrs_types' => ['RECEIVABLE'], 'filter' => 'related', 'indent' => 2],
        ['aop' => '047', 'label' => 'Побарувања од купувачите', 'ifrs_types' => ['RECEIVABLE'], 'filter' => 'trade', 'indent' => 2],
        ['aop' => '048', 'label' => 'Побарувања за дадени аванси на добавувачи', 'ifrs_types' => ['RECEIVABLE'], 'filter' => 'advances', 'indent' => 2],
        ['aop' => '049', 'label' => 'Побарувања од државата (даноци, придонеси, царини)', 'ifrs_types' => ['RECEIVABLE', 'CONTROL'], 'filter' => 'tax_receivable', 'indent' => 2],
        ['aop' => '050', 'label' => 'Побарувања од вработените', 'ifrs_types' => ['RECEIVABLE'], 'filter' => 'employee', 'indent' => 2],
        ['aop' => '051', 'label' => 'Останати краткорочни побарувања', 'ifrs_types' => ['RECEIVABLE', 'CURRENT_ASSET'], 'filter' => 'other', 'indent' => 2],

        // Краткорочни финансиски средства
        ['aop' => '052', 'label' => 'III. Краткорочни финансиски средства', 'is_total' => true, 'sum_of' => ['053', '056', '057', '058'], 'indent' => 1],
        ['aop' => '053', 'label' => 'Вложувања во хартии од вредност', 'is_total' => true, 'sum_of' => ['054', '055'], 'indent' => 2],
        ['aop' => '054', 'label' => 'Вложувања кои се чуваат до доспевање (краткорочни)', 'ifrs_types' => ['CURRENT_ASSET'], 'filter' => 'htm_st', 'indent' => 3],
        ['aop' => '055', 'label' => 'Вложувања по објективна вредност преку добивка/загуба (краткорочни)', 'ifrs_types' => ['CURRENT_ASSET'], 'filter' => 'fvtpl_st', 'indent' => 3],
        ['aop' => '056', 'label' => 'Побарувања по дадени заеми на поврзани друштва', 'ifrs_types' => ['CURRENT_ASSET'], 'filter' => 'st_loans_related', 'indent' => 2],
        ['aop' => '057', 'label' => 'Побарувања по дадени заеми', 'ifrs_types' => ['CURRENT_ASSET'], 'filter' => 'st_loans', 'indent' => 2],
        ['aop' => '058', 'label' => 'Останати краткорочни финансиски средства', 'ifrs_types' => ['CURRENT_ASSET'], 'filter' => 'st_financial_other', 'indent' => 2],

        // Парични средства
        ['aop' => '059', 'label' => 'IV. Парични средства и парични еквиваленти', 'is_total' => true, 'sum_of' => ['060', '061'], 'indent' => 1],
        ['aop' => '060', 'label' => 'Парични средства', 'ifrs_types' => ['BANK'], 'indent' => 2],
        ['aop' => '061', 'label' => 'Парични еквиваленти', 'ifrs_types' => ['BANK'], 'filter' => 'equivalents', 'indent' => 2],

        // АВР (standalone)
        ['aop' => '062', 'label' => 'Платени трошоци за идни периоди и пресметани приходи (АВР)', 'ifrs_types' => ['CURRENT_ASSET'], 'filter' => 'prepaid', 'indent' => 0],

        // ВКУПНА АКТИВА
        ['aop' => '063', 'label' => 'ВКУПНА АКТИВА', 'is_total' => true, 'sum_of' => ['001', '035', '036', '044', '062'], 'indent' => 0, 'is_grand_total' => true],

        // Вонбилансна
        ['aop' => '064', 'label' => 'Вонбилансна евиденција — Актива', 'ifrs_types' => [], 'indent' => 0, 'is_offbalance' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | ПАСИВА (Equity & Liabilities) — AOP 065-112
    |--------------------------------------------------------------------------
    */
    'pasiva' => [
        // А. ГЛАВНИНА И РЕЗЕРВИ
        ['aop' => '065', 'label' => 'А. ГЛАВНИНА И РЕЗЕРВИ', 'is_total' => true, 'sum_of' => ['066', '067', '-068', '-069', '070', '071', '075', '-076', '077', '-078'], 'indent' => 0],
        ['aop' => '066', 'label' => 'Основна главнина', 'ifrs_types' => ['EQUITY'], 'filter' => 'share_capital', 'indent' => 1],
        ['aop' => '067', 'label' => 'Премии на емитирани акции', 'ifrs_types' => ['EQUITY'], 'filter' => 'share_premium', 'indent' => 1],
        ['aop' => '068', 'label' => 'Сопствени акции (одбивка)', 'ifrs_types' => ['EQUITY'], 'filter' => 'treasury_shares', 'indent' => 1],
        ['aop' => '069', 'label' => 'Запишан, а неуплатен капитал (одбивка)', 'ifrs_types' => ['EQUITY'], 'filter' => 'unpaid_capital', 'indent' => 1],
        ['aop' => '070', 'label' => 'Ревалоризациона резерва и разлики од вреднување', 'ifrs_types' => ['EQUITY'], 'filter' => 'revaluation', 'indent' => 1],
        ['aop' => '071', 'label' => 'Резерви', 'is_total' => true, 'sum_of' => ['072', '073', '074'], 'indent' => 1],
        ['aop' => '072', 'label' => 'Законски резерви', 'ifrs_types' => ['EQUITY'], 'filter' => 'legal_reserves', 'indent' => 2],
        ['aop' => '073', 'label' => 'Статутарни резерви', 'ifrs_types' => ['EQUITY'], 'filter' => 'statutory_reserves', 'indent' => 2],
        ['aop' => '074', 'label' => 'Останати резерви', 'ifrs_types' => ['EQUITY'], 'filter' => 'other_reserves', 'indent' => 2],
        ['aop' => '075', 'label' => 'Акумулирана добивка', 'ifrs_types' => ['EQUITY'], 'filter' => 'retained_earnings', 'indent' => 1],
        ['aop' => '076', 'label' => 'Пренесена загуба (одбивка)', 'ifrs_types' => ['EQUITY'], 'filter' => 'accumulated_losses', 'indent' => 1],
        ['aop' => '077', 'label' => 'Добивка за деловната година', 'ifrs_types' => ['EQUITY'], 'filter' => 'profit_current', 'indent' => 1],
        ['aop' => '078', 'label' => 'Загуба за деловната година (одбивка)', 'ifrs_types' => ['EQUITY'], 'filter' => 'loss_current', 'indent' => 1],
        ['aop' => '079', 'label' => 'Главнина на сопствениците на матично друштво', 'ifrs_types' => [], 'indent' => 1, 'consolidated_only' => true],
        ['aop' => '080', 'label' => 'Неконтролирано учество', 'ifrs_types' => [], 'indent' => 1, 'consolidated_only' => true],

        // Б. ОБВРСКИ
        ['aop' => '081', 'label' => 'Б. ОБВРСКИ', 'is_total' => true, 'sum_of' => ['082', '085'], 'indent' => 0],

        // Долгорочни резервирања
        ['aop' => '082', 'label' => 'I. Долгорочни резервирања за ризици и трошоци', 'is_total' => true, 'sum_of' => ['083', '084'], 'indent' => 1],
        ['aop' => '083', 'label' => 'Резервирања за пензии и отпремнини', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'pension_provisions', 'indent' => 2],
        ['aop' => '084', 'label' => 'Останати долгорочни резервирања', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'other_provisions', 'indent' => 2],

        // Долгорочни обврски
        ['aop' => '085', 'label' => 'II. Долгорочни обврски', 'is_total' => true, 'sum_of' => ['086', '087', '088', '089', '090', '091', '092', '093'], 'indent' => 1],
        ['aop' => '086', 'label' => 'Обврски спрема поврзани друштва', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'related_lt', 'indent' => 2],
        ['aop' => '087', 'label' => 'Обврски спрема добавувачи (долгорочни)', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'trade_lt', 'indent' => 2],
        ['aop' => '088', 'label' => 'Обврски за аванси, депозити и кауции', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'deposits_lt', 'indent' => 2],
        ['aop' => '089', 'label' => 'Обврски по заеми и кредити спрема поврзани друштва', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'loans_related_lt', 'indent' => 2],
        ['aop' => '090', 'label' => 'Обврски по заеми и кредити', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'loans_lt', 'indent' => 2],
        ['aop' => '091', 'label' => 'Обврски по хартии од вредност', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'securities_lt', 'indent' => 2],
        ['aop' => '092', 'label' => 'Останати финансиски обврски', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'financial_lt', 'indent' => 2],
        ['aop' => '093', 'label' => 'Останати долгорочни обврски', 'ifrs_types' => ['NON_CURRENT_LIABILITY', 'RECONCILIATION'], 'indent' => 2],

        // Одложени даночни обврски (standalone)
        ['aop' => '094', 'label' => 'Одложени даночни обврски', 'ifrs_types' => ['NON_CURRENT_LIABILITY'], 'filter' => 'deferred_tax_liability', 'indent' => 0],

        // Краткорочни обврски
        ['aop' => '095', 'label' => 'В. КРАТКОРОЧНИ ОБВРСКИ', 'is_total' => true, 'sum_of' => ['096', '097', '098', '099', '100', '101', '102', '103', '104', '105', '106', '107', '108'], 'indent' => 0],
        ['aop' => '096', 'label' => 'Обврски спрема поврзани друштва', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'related_st', 'indent' => 1],
        ['aop' => '097', 'label' => 'Обврски спрема добавувачи', 'ifrs_types' => ['PAYABLE'], 'indent' => 1],
        ['aop' => '098', 'label' => 'Обврски за аванси, депозити и кауции', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'deposits_st', 'indent' => 1],
        ['aop' => '099', 'label' => 'Обврски за даноци и придонеси на плата', 'ifrs_types' => ['CONTROL'], 'filter' => 'salary_tax', 'indent' => 1],
        ['aop' => '100', 'label' => 'Обврски кон вработените', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'employees', 'indent' => 1],
        ['aop' => '101', 'label' => 'Тековни даночни обврски', 'ifrs_types' => ['CONTROL'], 'indent' => 1],
        ['aop' => '102', 'label' => 'Краткорочни резервирања за ризици и трошоци', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'provisions_st', 'indent' => 1],
        ['aop' => '103', 'label' => 'Обврски по заеми и кредити спрема поврзани друштва', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'loans_related_st', 'indent' => 1],
        ['aop' => '104', 'label' => 'Обврски по заеми и кредити', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'loans_st', 'indent' => 1],
        ['aop' => '105', 'label' => 'Обврски по хартии од вредност', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'securities_st', 'indent' => 1],
        ['aop' => '106', 'label' => 'Обврски по основ на учество во резултатот', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'dividends', 'indent' => 1],
        ['aop' => '107', 'label' => 'Останати финансиски обврски', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'financial_st', 'indent' => 1],
        ['aop' => '108', 'label' => 'Останати краткорочни обврски', 'ifrs_types' => ['CURRENT_LIABILITY'], 'indent' => 1],

        // ПВР (standalone)
        ['aop' => '109', 'label' => 'Одложено плаќање на трошоци и приходи во идни периоди (ПВР)', 'ifrs_types' => ['CURRENT_LIABILITY'], 'filter' => 'accrued', 'indent' => 0],

        // Disposal groups (standalone)
        ['aop' => '110', 'label' => 'Обврски на средства наменети за продажба и прекинати работења', 'ifrs_types' => [], 'indent' => 0],

        // ВКУПНА ПАСИВА
        ['aop' => '111', 'label' => 'ВКУПНА ПАСИВА', 'is_total' => true, 'sum_of' => ['065', '081', '094', '095', '109', '110'], 'indent' => 0, 'is_grand_total' => true],

        // Вонбилансна
        ['aop' => '112', 'label' => 'Вонбилансна евиденција — Пасива', 'ifrs_types' => [], 'indent' => 0, 'is_offbalance' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFRS Type → AOP Fallback Mapping (for official Образец 36)
    |--------------------------------------------------------------------------
    | Default leaf node for each IFRS account type.
    | Since we can't disaggregate by sub-type, all balance for a type
    | flows to the most common leaf node for MK SMEs.
    */
    'ifrs_to_aop_fallback' => [
        // Assets
        'NON_CURRENT_ASSET' => '013',  // Plant and equipment (most common for SMEs)
        'CONTRA_ASSET'      => '013',  // Subtracted from tangible assets
        'INVENTORY'         => '042',  // Trade goods/merchandise
        'BANK'              => '060',  // Cash
        'CURRENT_ASSET'     => '051',  // Other ST receivables
        'RECEIVABLE'        => '047',  // Trade receivables - ST
        // Liabilities
        'NON_CURRENT_LIABILITY' => '093', // Other LT liabilities
        'CONTROL'               => '101', // Current tax liabilities
        'CURRENT_LIABILITY'     => '108', // Other ST liabilities
        'PAYABLE'               => '097', // Trade payables - ST
        'RECONCILIATION'        => '093', // Other LT liabilities
        // Equity
        'EQUITY'                => '066', // Share capital (default)
    ],
];

// CLAUDE-CHECKPOINT
