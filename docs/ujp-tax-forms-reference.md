# UJP Tax Form Templates - Complete Field Reference

> Source: Управа за јавни приходи на Република Северна Македонија (ujp.gov.mk)
> Extracted: 2026-03-07
> Purpose: Build matching XML generators in Facturino

---

## 1. ДБ - Даночен биланс за оданочување на добивка (Tax Balance for Profit Taxation)

**Службен весник на РСМ бр. 6/2020, valid from 11.01.2020**
**PDF**: http://www.ujp.gov.mk/files/attachment/0000/0121/DB_Bilans_6-2020_11.01.2020_web.pdf
**Local**: `docs/ujp-forms/DB_Bilans_2020.pdf`
**Submitted**: By Feb 28/29 (or Mar 15 if electronic filing via Central Register)
**Method**: Electronic via http://etax.ujp.gov.mk

### Header Fields
| Field | Description |
|-------|-------------|
| Посебен даночен статус | Special tax status checkboxes: ТИРЗ, Заштитни друштва, Казнено поправни домови |
| Единствен даночен број | Unique Tax ID (EDB) |
| Скратен назив | Short name |
| Адреса на вистинско седиште за контакт | Registered address |
| Даночен период (од - до) | Tax period (from - to) |
| Телефон | Phone |
| е-пошта | Email |
| Исправка на ДБ / Број | Correction flag / Archive number |

### УТВРДУВАЊЕ НА ДАНОК НА ДОБИВКА (Tax Calculation)

| AOP | Row | Field Label |
|-----|-----|-------------|
| 01 | I | Финансиски резултат во Биланс на успех (Financial result from Income Statement, + for profit, - for loss) |
| 02 | II | Непризнаени расходи и помалку искажани приходи за даночни цели (Sum of AOP 03-39) |
| 03 | 1 | Расходи кои не се поврзани со вршење на дејноста |
| 04 | 2 | Надоместоци на трошоци и други лични примања од работен однос над утврдениот износ |
| 05 | 3 | Надоместоци на трошоци на вработените што не се утврдени со член 9 став (1) точка 2) од ЗДД |
| 06 | 4 | Трошоци за организирана исхрана и превоз над износите утврдени со закон |
| 07 | 5 | Трошоци за хотелско сместување над 6.000 денари дневно и недокументиран превоз |
| 08 | 6 | Трошоци за исхрана на вработените кои работат ноќно време, над износите утврдени со закон |
| 09 | 7 | Трошоци по основ на месечни надоместоци на членови на органи на управување над пропишаниот износ |
| 10 | 8 | Трошоци по основ на уплатени доброволни придонеси во доброволен пензиски фонд над пропишаниот износ |
| 11 | 9 | Трошоци по основ на уплатени премии за осигурување на живот над пропишаниот износ |
| 12 | 10 | Надоместоци за лица волонтери и за лица ангажирани за вршење на јавни работи над износите |
| 13 | 11 | Скриени исплати на добивки |
| 14 | 12 | Кусоци кои не се предизвикани од вонредни настани |
| 15 | 13 | Трошоци за репрезентација (90% disallowed) |
| 16 | 14 | Трошоци за донации над 5% од вкупниот приход |
| 17 | 15 | Трошоци за спонзорства над 3% од вкупниот приход |
| 18 | 16 | Трошоци за донации во спортот согласно чл. 30-а од ЗДД |
| 19 | 17 | Трошоци по основ на камата по кредити кои не се користат за вршење на дејноста |
| 20 | 18 | Осигурителни премии за членови на органите на управување и вработени |
| 21 | 19 | Даноци по задршка исплатени во име на трети лица |
| 22 | 20 | Парични казни, пенали и казнени камати за ненавремена уплата на јавни давачки |
| 23 | 21 | Трошоци за стипендии |
| 24 | 22 | Трошоци на кало, растур, крш и расипување |
| 25 | 23 | Траен отпис на ненаплатени побарувања |
| 26 | 24 | Трошоци за нето износот на примањата по основ на деловна успешност над придонеси |
| 27 | 25 | Трошоци за практикантска работа над износите пропишани со закон |
| 28 | 26 | Трошоци за практична обука на ученици и практична настава на студенти над 8.000 денари месечно |
| 29 | 27 | Трошоци за амортизација на ревалоризираната вредност |
| 30 | 28 | Трошоци за амортизација над пропишаните стапки (Номенклатура) |
| 31 | 29 | Преостаната сегашна вредност на основни средства неискористени, без согласност од УЈП |
| 32 | 30 | Трошоци за исправка на вредноста на ненаплатени побарувања |
| 33 | 31 | Износ на ненаплатени побарувања од заем |
| 34 | 32 | Позитивна разлика трансферна цена vs пазарна цена (расходи) - поврзани лица |
| 35 | 33 | Позитивна разлика arm's length приходи vs трансферна цена (приходи) - поврзани лица |
| 36 | 34 | Дел од камати по заеми од поврзани лица над arm's length |
| 37 | 35 | Затезни камати од поврзани лица |
| 38 | 36 | Камати на заеми од содружници/акционери нерезиденти со >=20% учество |
| 39 | 37 | Други усогласувања на расходи |
| 40 | III | Даночна основа (I+II) |
| 41 | IV | Намалување на даночна основа (sum of AOP 42-48) |
| 42 | 38 | Наплатени побарувања за кои претходно е зголемена основата |
| 43 | 39 | Вратен дел од заем за кој претходно е зголемена основата |
| 44 | 40 | Трошоци за амортизација над пропишаните стапки, претходно зголемена основа |
| 45 | 41 | Неисплатени надоместоци за кои претходно е зголемена основата, ако се искажани како приход |
| 46 | 42 | Дивиденди остварени со учество во капиталот на друг обврзник (оданочени кај исплатувачот) |
| 47 | 43 | Дел од загуба намалена за непризнаени расходи, пренесена од претходни години |
| 48 | 44 | Износ на реинвестирана добивка |
| 49 | V | Даночна основа по намалување (III - IV) |
| 50 | VI | Пресметан данок на добивка (V x 10%) |
| 51 | VII | Намалување на пресметаниот данок (sum of AOP 52-55) |
| 52 | 45 | Намалување за фискални системи на опрема (до 10 уреди) |
| 53 | 46 | Данок содржан во оданочени приходи/добивки во странство (withholding tax) |
| 54 | 47 | Данок платен од подружница во странство |
| 55 | 48 | Даночно олеснување за донација согласно чл. 30-а од ЗДД |
| 56 | VIII | Пресметан данок по намалување (VI - VII) |
| 57 | 49 | Платени аконтации на данокот за даночниот период |
| 58 | 50 | Повеќе платен данок пренесен од претходни периоди |
| 59 | 51 | Износ за доплата / повеќе платен износ (AOP 56-57-58) |

### IX. ПОСЕБНИ ПОДАТОЦИ

| AOP | Row | Field Label |
|-----|-----|-------------|
| 60 | 52 | Вкупен износ на реинвестирана добивка |
| 61 | 53 | Загуби од претходни години (право на покритие до 3 години) |
| 62 | 54 | Остварена загуба намалена за непризнаени расходи (пренос во наредни 3 години) |
| 63 | 55 | Пренесен неискористен дел на намалување по чл. 30 од ЗДД |
| 64 | 56 | Пренесен неискористен дел на намалување на данок платен во странство |
| 65 | 57 | Остварен вкупен приход во годината |
| 66 | 58 | Вкупни трошоци за донации (со право на намалување) |
| 67 | 59 | Вкупни трошоци за донации (без право на намалување) |
| 68 | 60 | Вкупни трошоци за спонзорства (со право на намалување) |
| 69 | 61 | Вкупни трошоци за спонзорства (без право на намалување) |
| 70 | 62 | Вкупни трошоци за донација во спорт по чл. 30-а од ЗДД |

### Footer: Податоци за составувачот / потписникот
- Назив / Име и презиме
- ЕДБ / ЕМБГ
- Датум на пополнување
- Својство
- Потпис

---

## 2. ДБ-ВП - Даночен биланс на вкупен приход (Tax Balance on Total Income)

**Службен весник на Р.М. бр. 174/14, valid from 01.01.2015**
**PDF**: http://www.ujp.gov.mk/files/attachment/0000/0127/DB-VP_Bilans_174-14_01.01.2015.pdf
**Local**: `docs/ujp-forms/DB-VP.pdf`
**Applies to**: Small/micro traders, total income 3,000,001 - 6,000,000 MKD
**Rate**: As per Article 34 of ЗДД (1%)
**Note**: Once chosen, cannot change for 3 years

### Header Fields
- Единствен даночен број, Скратен назив, Адреса, Даночен период (од/до), Телефон, е-пошта
- Исправка на ДБ-ВП / Број

### Податоци за дејноста
- Опис на дејност, Шифра НАЦЕ, Назив НАЦЕ
- Друштво за вработување на инвалидизирани лица (checkbox)
- Правна форма: ДООЕЛ / ДОО / Акционерско друштво / Јавно трговско друштво / Командитно друштво / Командитно друштво со акции / Друго
- Друштвото се определило да плаќа ГДВП од: (Година)

### УТВРДУВАЊЕ НА ГОДИШЕН ДАНОК НА ВКУПЕН ПРИХОД

| AOP | Row | Field Label |
|-----|-----|-------------|
| 01 | 1 | Вкупен приход утврден во Билансот на успех (sum of 1.1+1.2+1.3) |
| 02 | 1.1 | Приходи од работењето (from AOP 201 of БУ) |
| 03 | 1.2 | Финансиски приходи (from AOP 223 of БУ) |
| 04 | 1.3 | Удел во добивката на придружените друштва (from AOP 244 of БУ) |
| 05 | 2 | Пропишана стапка (Article 34 ЗДД) |
| 06 | 3 | Годишен данок на вкупен приход (row 1 x row 2 / 100) |
| 07 | 4 | Износ на платени аконтации на данокот на добивка |

---

## 3. ДД-И - Извештај за уплатениот данок по задршка (Withholding Tax Report)

**UJP page**: http://www.ujp.gov.mk/mk/obrasci/opis/76
**Submitted**: By February 15 of the following year
**Legal basis**: Articles 21 and 24 of ЗДД
**Applies to**: Domestic/foreign legal entities making payments to foreign entities (withholding tax)

*Note: The opis page only shows description. The actual form PDF (292 KB) is downloadable from the UJP page but the direct link was not resolved. Form fields to be confirmed from the PDF.*

---

## 4. ДД-01 - Барање за пренесување на искажана загуба (Request for Loss Carry-Forward)

**Службен весник на Р.М. бр. 174/14, valid from 01.01.2015**
**PDF**: DD-01_Baranje_174-14_01.01.2015.pdf (270 KB on UJP)
**UJP page**: http://www.ujp.gov.mk/mk/obrasci/opis/158
**Submitted**: By March 31 of the following year
**Loss carry-forward period**: 3 years after the year the loss was reported

*Form fields need to be extracted from the PDF. General structure: header (EDB, name, period), loss details by year, signature blocks.*

---

## 5. ДДД/П - Пријава за дополнителен данок на добивка / Top-up Tax Return

**UJP page**: http://www.ujp.gov.mk/mk/obrasci/opis/258
**Legal basis**: Article 48 of ЗМГДД (Law on Global Minimum Tax on Profit)
**Submitted**: Electronically, per deadlines in Article 48
**PDF**: 71 KB on UJP

*Note: This is a new form for the global minimum tax (Pillar 2). Field structure to be confirmed from PDF.*

---

## 6. ДД-ДО - Пресметка на данок на добивка при престанување на условите за даночно ослободување

**UJP page**: http://www.ujp.gov.mk/mk/obrasci/opis/205
**PDF**: ДД-ДО_03.01.2022.pdf (88 KB)
**Legal basis**: Article 28 (3)(4) and Article 30-a (9) of ЗДД
**Submitted**: Within 30 days from cessation of exemption conditions

*Applies when reinvested profit exemptions or sports donation exemptions cease.*

---

## 7. ДБ-ДС - Даночен биланс за оданочување со данок за солидарност (Solidarity Tax Balance)

**Службен весник на РСМ бр. 200/2023, valid from 25.09.2023**
**PDF**: http://www.ujp.gov.mk/files/attachment/0000/1508/DB-DS_25.09.2023.pdf
**Local**: `docs/ujp-forms/DB-DS.pdf`
**Applies to**: Entities with total revenue > 615,000,000 MKD in 2022
**Rate**: 30%
**One-time filing**: by September 25, 2023

### Header Fields
- Единствен даночен број, Скратен назив, Адреса, Телефон, е-пошта
- Статусна промена (checkbox)
- Рок за поднесување, Исправка на ДБ-ДС / Број
- Износ на остварен вкупен приход во 2022 година

### A. ПРЕСМЕТКА ПО ОБРАЗЕЦ ДБ ЗА 2022

| AOP | Row | Field Label |
|-----|-----|-------------|
| 01 | 1 | Даночна основа по намалување, образец ДБ за 2018 (AOP 37) |
| 02 | 1 | Даночна основа по намалување, образец ДБ за 2019 (AOP 49) |
| 03 | 1 | Даночна основа по намалување, образец ДБ за 2021 (AOP 49) |
| 04 | 1 | Даночна основа по намалување, образец ДБ за 2022 (AOP 49) |
| 05 | 2 | Просечен износ за претходен период: (01+02+03) / број на периоди |
| 06 | 3 | Даночна основа = AOP 04 - (AOP 05 x 120%) [or x 130% for status changes] |
| 07 | 4 | Даночна стапка: 30% |
| 08 | 5 | Пресметан данок = AOP 06 x AOP 07 |

### Б. ПРЕСМЕТКА ЗА 2021 И 2022

| AOP | Row | Field Label |
|-----|-----|-------------|
| 09-13 | 1 | Даночна основа по намалување за 2017-2022 |
| 14 | 2 | Просечен износ за претходен период: (09+10+11) / број на периоди |
| 15 | 3 | Просечен износ за 2021 и 2022: (12+13) / 2 |
| 16 | 4 | Даночна основа = AOP 15 - (AOP 14 x 120%) [or x 130%] |
| 17 | 5 | Даночна стапка: 30% |
| 18 | 6 | Пресметан данок = AOP 16 x AOP 17 |

### В. УТВРДУВАЊЕ НА ДАНОК ЗА ПЛАЌАЊЕ

| AOP | Row | Field Label |
|-----|-----|-------------|
| 19 | 1 | Износ на данок за солидарност за плаќање (AOP 08 or AOP 18) |

---

## 8. ДБ-НП/ВП - Даночен биланс на непрофитни организации (Non-Profit Tax Balance)

**Службен весник на РСМ бр. 216/19, valid from 19.10.2019**
**PDF**: http://www.ujp.gov.mk/files/attachment/0000/1197/DB-NP-VP_216-19_19.10.2019_web.pdf
**Local**: `docs/ujp-forms/DB-NP-VP.pdf`
**Applies to**: Associations, foundations, unions with business income > 1,000,000 MKD
**Rate**: 1%

### Header Fields
- Единствен даночен број, Скратен назив, Адреса, Даночен период (од/до), Телефон, е-пошта
- Исправка на ДБ-НП/ВП / Број

### Податоци за дејноста
- Опис на дејност, Шифра НАЦЕ, Назив НАЦЕ
- Организационен облик (Шифра, Назив)

### A. УТВРДУВАЊЕ НА ГОДИШЕН ДАНОК

| AOP | Section/Row | Field Label |
|-----|-------------|-------------|
| 01 | I.1 | Приходи од продажба на производи и стоки (from AOP 241 of income statement) |
| 02 | I.2 | Приходи од услуги (from AOP 242) |
| 03 | I.3 | Приходи од кирии и закупнини (from AOP 245) |
| 04 | I.4 | Сопствени приходи (from AOP 246) |
| 05 | II | Вкупни приходи (sum AOP 01-04) |
| 06 | III | Намалување за 1,000,000 денари |
| 07 | IV | Вкупни приходи по намалување (II - III) |
| 08 | V | Годишен данок на вкупен приход (IV x 1%) |

---

## 9. ДДВ-04 - Даночна пријава на данокот на додадена вредност (VAT Return)

**Службен весник на РСМ бр. 79/22, valid from 30.03.2022**
**PDF**: http://www.ujp.gov.mk/files/attachment/0000/0967/sl.79_DDV-04_30.03.2022.pdf
**Local**: `docs/ujp-forms/DDV-04.pdf`
**Submitted**: Within 25 days after end of tax period (monthly/quarterly)
**Method**: Electronic via http://etax.ujp.gov.mk

### Header Fields
| Field | Description |
|-------|-------------|
| Даночен идентификациски број | Tax ID (first 2 chars = MK for registered VAT payers) |
| Скратен назив и адреса на вистинско седиште за контакт | Short name and address |
| Даночен период (од - до) | Tax period |
| Телефон | Phone |
| е-пошта | Email |
| Рок за поднесување | Filing deadline |
| Исправка на ДДВ-04 / Број | Correction flag / Number |

### ПРОМЕТ НА ДОБРА И УСЛУГИ (Supply of Goods and Services)

| Field | Description | Columns |
|-------|-------------|---------|
| 01 | Оданочив промет по општа даночна стапка | Даночна основа без ДДВ |
| 02 | (same) | ДДВ |
| 03 | Оданочив промет по повластена стапка од 10% | Даночна основа без ДДВ |
| 04 | (same) | ДДВ |
| 05 | Оданочив промет по повластена стапка од 5% | Даночна основа без ДДВ |
| 06 | (same) | ДДВ |
| 07 | Извоз (Exports) | Даночна основа без ДДВ |
| 08 | Промет ослободен со право на одбивка (exempt with input deduction) | Даночна основа без ДДВ |
| 09 | Промет ослободен без право на одбивка (exempt without input deduction) | Даночна основа без ДДВ |
| 10 | Промет кон нерезиденти, не предмет на оданочување | Даночна основа без ДДВ |
| 11 | Промет во земјата - reverse charge по чл. 32-а (supplier fills) | Даночна основа без ДДВ |
| 12 | Примен промет од нерезиденти по општа стапка (чл.32 т.4,5) | Даночна основа без ДДВ |
| 13 | (same) | ДДВ |
| 14 | Примен промет од нерезиденти по повластена стапка (чл.32 т.4,5) | Даночна основа без ДДВ |
| 15 | (same) | ДДВ |
| 16 | Примен промет reverse charge по општа стапка (чл.32-а) | Даночна основа без ДДВ |
| 17 | (same) | ДДВ |
| 18 | Примен промет reverse charge по повластена стапка (чл.32-а) | Даночна основа без ДДВ |
| 19 | (same) | ДДВ |
| 20 | **Вкупен ДДВ** (02+04+06+13+15+17+19) | ДДВ |

### ВЛЕЗНИ ИСПОЛНУВАЊА СО ПРАВО НА ОДБИВКА (Input VAT with Right to Deduct)

| Field | Description | Columns |
|-------|-------------|---------|
| 21 | Влезен промет (domestic purchases) | Даночна основа без ДДВ |
| 22 | (same) | ДДВ |
| 23 | Влезен промет reverse charge чл.32 т.4,5 | Даночна основа без ДДВ |
| 24 | (same) | ДДВ |
| 25 | Влезен промет reverse charge чл.32-а | Даночна основа без ДДВ |
| 26 | (same) | ДДВ |
| 27 | Увоз (imports) | Даночна основа без ДДВ |
| 28 | (same) | ДДВ |
| 29 | **Претходни даноци за одбивање** (22+24+26+28) | ДДВ |
| 30 | Останати даноци, претходни даноци и износи за одбивање (Article 55(1)(2), Article 37) | Amount |
| 31 | **Даночен долг / побарување** (20-29+/-30). Mark "x" before field 31 to request refund | Amount |
| 32 | Отстапување на побарување. Mark "x" to assign claim to another taxpayer's debt | Amount |

### ДДВ СОДРЖАН ВО ЗАЛИХИ (Pre-registration VAT on inventory) - Page 2 Table

| Column | Description |
|--------|-------------|
| Реден број | Sequential number |
| Име на испорачателот на добрата | Supplier name |
| Износ на фактура | Invoice amount |
| Износ на ДДВ | VAT amount |
| Дата на плаќање на фактура | Invoice payment date |
| Вкупно | Total |

### ПРИЛОЗИ (Attachments)
- Извештај за извршени промети (for Article 32-а supplies) - checkbox
- Извештај за примени промети (for Article 32-а receipts) - checkbox

### Footer
- Податоци за составувачот: Назив/Име и презиме, ЕДБ/ЕМБГ, Датум, Својство, Потпис
- Податоци за потписникот: Име и презиме, ЕМБГ, Датум, Својство, Потпис

---

## 10. ДДВ-ЕПФ - Евиденција на примени фактури (Received Invoices Registry)

**Службен весник на Р.М. бр. 98/14, valid from 01.07.2014**
**PDF**: http://www.ujp.gov.mk/files/attachment/0000/0653/DDV-EPF_Evidencija_98-14_01.07.2014.pdf
**Local**: `docs/ujp-forms/DDV-EPF.pdf`
**Submitted**: By 25th of month after each calendar quarter
**Used by**: Project implementors (recipients of supplies)

### Header Fields
- Скратен назив и вистинско седиште на имплементатор (примател на промет)
- Телефон, е-пошта
- Период (од - до)
- Рок за поднесување
- Исправка на ДДВ-ЕПФ / Број

### Податоци за проектот
- ЕДБ на проект
- Назив и адреса на проект
- Број на потврда на проект
- Времетраење на проект

### Податоци за извршениот промет (Table)

| Column | # | Description |
|--------|---|-------------|
| Ред. бр. | 1 | Sequential number of supplier |
| ЕДБ / Идентификационен број на вршителот на промет | 2 | Tax ID of supplier |
| Назив и адреса на вршителот на промет | 3 | Supplier name and address |
| Број на фактура | 4 | Invoice number |
| Датум на издавање на фактура | 5 | Invoice issue date |
| Вкупен износ на надоместокот за извршен промет (без ДДВ) | 6 | Total consideration excluding VAT |

Per-supplier subtotals ("вкупно за реден број 1/2...") and grand total ("вкупно").

---

## 11. МПИН - Месечна пресметка за интегрирана наплата (Monthly Payroll Calculation)

**UJP page**: http://www.ujp.gov.mk/mk/obrasci/opis/81
**Guide PDF**: http://www.ujp.gov.mk/files/attachment/0000/0526/ISBN_978-608-4592-70-9_Mesecna_presmetka_za_integrirana_naplata_11.03.2016.pdf
**Local**: `docs/ujp-forms/MPIN-guide.pdf`
**Submitted**: By 10th of current month for previous month
**Method**: Email to celaplata@ujp.gov.mk or electronic media, using designated payroll software

### ДЕЛ 1 - Месец/Година/Вид

| Field | Description |
|-------|-------------|
| 1.1 Месец | Month (01-12) |
| 1.2 Година | Year (4 digits) |
| 1.3 Вид на обврска | Obligation type code: 100 (partial salary), 333 (final), 101 (salary), 102 (bonus), 103 (correction), 109 (court order), 123 (additional employment), 222 (volunteers), 700 (temp agencies), 555 (temp agency aggregate), 553 (temp agency correction), 820 (board member), 821 (board member correction) |
| 1.4 Вид на обврзник | Payer type: 110 (legal entity), 111 (self-employed intellectual), 112 (self-employed economic), 116 (foreign/intl org), 122 (bankruptcy), 123 (liquidation), 180 (board member payer) |
| 1.5 Број на вработени | Number of employees (auto-calculated) |
| 1.6 Плата во вкупен износ | Total salary (auto-calculated from contributions) |

### ДЕЛ 2 - Идентификација на обврзникот за пресметка

| Field | Description |
|-------|-------------|
| 2.1 ЕДБ на обврзникот | Tax ID of payer |
| 2.16 Единствен матичен број | Registry number (7 digits, or 0000000 for individuals) |
| 2.2 Назив на обврзник | Payer name |
| 2.3 Телефонски повикувачки број | Area code |
| 2.4 Телефон | Phone |
| 2.5 Факс | Fax |
| 2.6 Адреса, име на улица | Street address |
| 2.7 Број | Street number |
| 2.8 Населено место | Settlement |
| 2.9 Град | City |
| 2.10 Ознака на општина | Municipality code |
| 2.11 Поштенски код | Postal code (4 digits) |
| 2.12 Контакт лице | Contact person |
| 2.13 Телефон | Contact phone |
| 2.14 Е-маил | Email |

### ДЕЛ 3 - Employee Data (per employee, repeating rows)

| Field | Description |
|-------|-------------|
| 3.0 Реден број | Sequential number |
| 3.1 ЕМБГ | Personal ID number |
| 3.2 Презиме | Surname |
| 3.3 Име | First name |
| 3.4 Број на деловна единица | Business unit number |
| 3.4б Ознака на подрачна единица ФЗОМ | Health fund regional code |
| 3.4ц Ознака на општината | Municipality code of employee |
| 3.5 Траење на стаж во денови | Insurance days in month (1-31) |
| 3.6 Ефективна работа часови | Effective work hours |
| 3.6б Прекувремени часови | Overtime hours |
| 3.6ц Неплатени часови | Unpaid hours (max 24/month) |
| 3.7 Износ за ефективна работа | Amount for effective work |
| 3.8 Часови за надоместок | Compensation hours |
| 3.9 Вид на надоместок | Compensation type code: 124 (undocumented), 125-128 (sick leave 70-100%), 130-131 (reduced hours 100/85%), 134-137 (disability 100/80/70/50%) |
| 3.10 Износ на надоместок | Compensation amount |
| 3.12 Година од која е определен паричен надоместок | Year of unemployment benefit (Employment Agency only) |
| 3.14 Плата во вкупен износ | Gross salary (base for contributions) |
| 3.15 Придонес за ПИО | Pension/disability contribution (18%) |
| 3.16 Доплата на придонес до најниска основица (ПИО) | Top-up to minimum base (pension) |
| 3.40 Придонес за здравствено осигурување | Health contribution (7.3%) |
| 3.41 Доплата до најниска основица (здравство) | Top-up to minimum base (health) |
| 3.42 Дополнителен придонес за здравствено (повреда на работа) | Additional health for work injury (0.5%) |
| 3.43 Доплата до најниска основица (дополнително здравство) | Top-up to minimum base (additional health) |
| 3.44 Придонес за невработеност | Unemployment contribution (1.2%) |
| 3.45 Доплата до најниска основица (невработеност) | Top-up to minimum base (unemployment) |
| 3.46 Персонален данок на доход од плата | Personal income tax = (Gross - contributions - personal allowance) x 10% |
| 3.17 Шифра за вид на стаж | Insurance type code: 0050 (full-time), 0041 (overtime), 0047 (part-time), 0048 (reduced=full), etc. |
| 3.17б Шифра за ослободување | Exemption code: 001 (self-employed), 015 (posted workers), 050, 060 |
| 3.18 Степен на зголемување | Accelerated service degree codes with rates (9%, 7.5%, 6%, 4.5%, 3%, 1.5%) |
| 3.19а Од ден | Start date of accelerated period |
| 3.19б До ден | End date of accelerated period |
| 3.20 Вкупно денови | Total accelerated days |
| 3.21 Основица за бенефициран стаж | Base for accelerated service |
| 3.22 Износ на придонес за бенефициран стаж | Contribution for accelerated service |
| 3.22б Доплата до најниска основица (бенефициран стаж) | Top-up for accelerated service |
| 3.23 Ознака на надлежен орган | Government body code: 128-140 (sick leave via FZOM), 244-247 (disabled workers), 245-246 (tax exemptions), 240 (youth under 27), 301-307 (unemployed categories), 351 (termination on non-work day), 400 (additional reporting) |
| 3.24 Часови за кои плаќа надлежен орган | Hours paid by government body |
| 3.25 Основица за пресметка од надлежен орган | Base for government-paid contribution |
| 3.26 Даночен број каде е пријавен обврзникот | Tax ID of primary employer (FZOM only) |
| 3.27 Дата на засновање на работен однос | Employment start date (day of month) |
| 3.28 Дата на престанок на работен однос | Employment end date (day of month) |
| 3.29 Шифра за промена | Change code: 1 (start), 2 (end), 3 (death), 4 (disability), 5 (piece-rate) |
| 3.30 Број на пријава/одјава | Contract/registration number |
| 3.13 Нето плата | Net salary = Gross - contributions - tax |
| 3.31 Ефективна нето плата | Effective net salary = Net - deductions |
| 3.32 Број на трансакциска сметка | Employee bank account |

### ДЕЛ 4 - Збирни вредности (auto-calculated, not filled manually)

Total sums of all contributions and tax for payment.

---

## 12. е-ППД - Електронска пресметка за приход и данок (Electronic Income/Tax Calculation)

**System**: https://e-pdd.ujp.gov.mk (since 01.01.2018)
**Guide PDF**: http://www.ujp.gov.mk/files/attachment/0000/1040/08-1112-1_Presmetuvanje_i_plakanje_na_e-Personalen_danok_05.02.2018.pdf
**Local**: `docs/ujp-forms/e-PPD-guide.pdf`
**Legal basis**: Закон за изменување на ЗПДД, Службен весник бр. 190/2017
**Submitted**: Before each income payment (except salary via MPIN)
**Replaces**: ПДД-ГИ, ПДД-ГИ/ОЗП, ПДД-ГИ/ОЦО, ПДД-МИ/ИС, ПДД-АДП/ПИ, ПДД-АДП/КД, ПДД-АДП/ДП

### Income Categories (Вид/Подвид приход)

| Code | Description |
|------|-------------|
| 1 | Плати, други лични примања по основ на работен однос и пензии |
| 1.4 | Пензиски надоместок по основ на доброволно пензиско осигурување |
| 2.1 | Регрес за годишен одмор (taxable) |
| 2.2 | Други примања на вработени лица (taxable) |
| 2.3 | Приходи кои не подлежат на оданочување со ПДД |
| 2.4 | Други лични примања на невработени лица |
| 3.1 | Примања на членови на органи на управување и надзор |
| 4.1 | Примања на функционери, пратеници, советници |
| 5.1 | Надоместоци за судии поротници, вешти лица, стечајни управници |
| 6.1 | Надоместоци на членови на МАНУ |
| 7.1 | Извршени консултантски услуги |
| 7.2 | Извршени интелектуални услуги |
| 7.3 | Извршени други повремени или привремени услуги |
| 8.1 | Приходи од имот и имотни права (закупнини) |
| 8.2 | Приходи од издавање на опремени станбени и деловни простории |
| 9.1-9.7 | Приходи од авторски права (various subcategories with different deduction rates) |
| 9.8 | Права на индустриска сопственост (actual expenses) |
| 10.1 | Дивиденди и други приходи од учество во добивката |
| 11.1 | Камати за заеми, обврзници и хартии од вредност |
| 12.1 | Добивки од посебни игри на среќа (except casino) |
| 12.2 | Добивки од игри на среќа во обложувалница |
| 12.3 | Добивки од општи игри на среќа |
| 12.4 | Добивки од посебни игри на неидентификувани нерезиденти |
| 13.1 | Приходи од откуп на корисен цврст отпад |
| 13.2 | Други приходи |
| 14.1 | Приходи од лековити билки и шумски плодови |
| 14.2 | Приходи од сопствени земјоделски производи |

### Form Fields (per recipient)
- ЕМБГ на примател / Tax ID of recipient
- Име и презиме на примател
- Вид на приход (category code)
- Подвид на приход (subcategory code)
- Бруто приход (gross income)
- Нормирани трошоци / Стварни трошоци (standard/actual deductions)
- Даночна основа (tax base)
- Данок на доход (income tax amount)
- Нето приход (net income)
- Трансакциска сметка на примател (recipient bank account)

---

## 13. ДЛД-ДБ - Годишен даночен биланс за утврдување на данокот на доход од самостојна дејност

**Службен весник на РСМ бр. 271/19, valid from 27.12.2019**
**PDF**: http://www.ujp.gov.mk/files/attachment/0000/1221/DLD-DB_271_19_27.12.2019.pdf
**Local**: `docs/ujp-forms/DLD-DB.pdf`
**Submitted**: By March 15 of next year, electronically via e-pdd.ujp.gov.mk
**Accompanies**: Образец "Б" (Income/Expense Balance)
**Applies to**: Sole traders, freelancers, agricultural/craft activities

### Header Fields
- Единствен даночен број, Име и презиме, ЕМБГ
- Скратен назив и адреса
- Даночен период (од/до), Телефон, е-пошта
- Исправка на ДЛД-ДБ / Број
- Дејност (activity description)

### A. УТВРДУВАЊЕ НА ОСНОВАТА

| AOP | Row | Field Label |
|-----|-----|-------------|
| 01 | I | Нето-доход пред оданочување (from AOP 23 of form "Б") |
| 02 | II | Загуба пред оданочување (from AOP 24 of form "Б") |
| 03 | III | Непризнаени расходи за даночни цели (sum of AOP 04-21) |
| 04 | 1 | Разлика помеѓу пазарна и трансферна цена кај поврзани лица |
| 05 | 2 | Амортизација над пропишаните стапки |
| 06 | 3 | Преостаната вредност на неискористени основни средства |
| 07 | 4 | Дел од амортизација по функционална метода над 10% |
| 08 | 5 | Камати од поврзани лица над пазарни стапки |
| 09 | 6 | Премија за животно осигурување над пропишан износ |
| 10 | 7 | Премии за неживотно осигурување |
| 11 | 8 | Придонеси во доброволен пензиски фонд над пропишан износ |
| 12 | 9 | Организирана исхрана и превоз и други надоместоци над пропишан износ |
| 13 | 10 | Регрес за годишен одмор над пропишан износ (ОКД) |
| 14 | 11 | Камати за набавка на патнички автомобили |
| 15 | 12 | Надоместок за користење на сопствен автомобил над пропишан износ |
| 16 | 13 | Парични казни, пенали, казнени камати за јавни давачки |
| 17 | 14 | Трошоци за репрезентација |
| 18 | 15 | Трошоци за автомобили, рент-а-кар (50% disallowed) |
| 19 | 16 | Стипендии |
| 20 | 17 | Надоместок за закуп и трошоци за закуп на имот на вработено лице |
| 21 | 18 | Други усогласувања на расходи |
| 22 | IV | Даночна основа (I+III or III-II, if negative prefix with "-") |

### Б. ДАНОЧНИ НАМАЛУВАЊА И ОСЛОБОДУВАЊА

| AOP | Row | Field Label |
|-----|-----|-------------|
| 23 | V | Намалување на даночната основа (sum of rows 19-22) |
| 24 | 19 | Вложувања во материјални/нематеријални средства (30% of investment, max 50% of base) |
| 25 | 20 | Вложувања во патнички автомобили за рент-а-кар/такси/обука (up to base) |
| 26 | 21 | Даночно намалување (personal allowance x months of activity) |
| 27 | 22 | Намалувања по други закони |
| 28 | VI | Основа за пресметување на данокот (IV - V) |
| 29 | 23 | Даночна основа за стапка 10% |
| 30 | 24 | Даночна основа за стапка 18% |
| 31 | VII | Пресметан данок |
| 32 | 25 | Со стапка 10% |
| 33 | 26 | Со стапка 18% |
| 34 | VIII | Намалување на пресметаниот данок (sum of 27+28) |
| 35 | 27 | Намалување за фискални апарати (up to 10) |
| 36 | 28 | Намалување по други основи |

### В. ДАНОК ЗА ПЛАЌАЊЕ/ВРАЌАЊЕ

| AOP | Row | Field Label |
|-----|-----|-------------|
| 37 | IX | Пресметан годишен данок за плаќање (VII - VIII) |
| 38 | X | Платени аконтации на данокот за даночниот период |
| 39 | XI | Данок за доплата / повеќе платен данок (IX - X, prefix "-" if overpaid) |

---

## 14. Б - Биланс на приходи и расходи (Income/Expense Balance for Self-Employed)

**Службен весник на РСМ бр. 21/2020, valid from 31.01.2020**
**PDF**: http://www.ujp.gov.mk/files/attachment/0000/1231/B_Bilans_21-10_31.01.2020.pdf
**Local**: `docs/ujp-forms/B_Bilans.pdf`
**Submitted**: With ДЛД-ДБ by March 15 via e-pdd.ujp.gov.mk
**Applies to**: Sole traders, agricultural/craft/freelance activities

### Header Fields
- Единствен даночен број, Име и презиме, ЕМБГ
- Скратен назив, Адреса, Даночен период (од/до), Телефон, е-пошта

### A. ПРИХОДИ И РАСХОДИ ОД САМОСТОЈНА ДЕЈНОСТ

| AOP | Row | Field Label |
|-----|-----|-------------|
| 01 | I | Вкупни приходи (sum of rows 1-4) |
| 02 | 1 | Приходи од продажба на готови производи |
| 03 | 2 | Приходи од продажба на суровини, материјали и трговски стоки |
| 04 | 3 | Приходи од извршени услуги |
| 05 | 4 | Други неспомнати приходи |
| 06 | II | Вкупни расходи (sum of rows 5-20) |
| 07 | 5 | Вредност на потрошени суровини и материјали |
| 08 | 6 | Набавна вредност на продадени суровини/стоки |
| 09 | 7 | Вредност на потрошена енергија |
| 10 | 8 | Закупнина на работни простории |
| 11 | 9 | Трошоци за транспортни и други услуги |
| 12 | 10 | Пресметана амортизација |
| 13 | 11 | Трошоци по основ на камата по кредити |
| 14 | 12 | Исплатени бруто-плати на вработените |
| 15 | 13 | Платени придонеси и данок на доход за исплати на физички лица |
| 16 | 14 | Платен ДДВ (поле 17 од ДДВ-04) |
| 17 | 15 | Платени царини, акцизи, даноци на имот, комунални такси |
| 18 | 16 | Платени премии за осигурување |
| 19 | 17 | Канцелариски трошоци и стручна литература |
| 20 | 18 | Дневници, патни трошоци, теренски додатоци |
| 21 | 19 | Трошоци по одредени прописи |
| 22 | 20 | Други расходи |

### Б. ФИНАНСИСКИ РЕЗУЛТАТ

| AOP | Row | Field Label |
|-----|-----|-------------|
| 23 | III | Нето-приход пред оданочување (I - II) |
| 24 | IV | Загуба пред оданочување (II - I) |

### В. ПОСЕБНИ ПОДАТОЦИ

| AOP | Row | Field Label |
|-----|-----|-------------|
| 25 | V | Вредност на средства за работа (sum of 21-25) |
| 26 | 21 | Земјиште и згради |
| 27 | 22 | Градежни објекти |
| 28 | 23 | Постројки и опрема |
| 29 | 24 | Алат, погонски и канцелариски инвентар, мебел, транспортни средства |
| 30 | 25 | Други неспомнати материјални средства |
| 31 | VI | Вредност на залихи (sum of 26-28) |
| 32 | 26 | Суровини и други материјали |
| 33 | 27 | Производство во тек |
| 34 | 28 | Трговски стоки и готови производи |
| 35 | 29 | Број на работници |
| 36 | 30 | Број на месеци на вршење на дејноста |

---

## 15. ДД-БС - Барање согласност за признавање на расход во даночен биланс

**UJP page**: http://www.ujp.gov.mk/mk/obrasci/opis/212
**PDF**: ДД-БС_2025.pdf (95 KB)
**Legal basis**: Article 9-а of ЗДД
**Submitted**: By January 31 of the following year
**Purpose**: Request consent to recognize expense for assets with no remaining use value

---

## 16. ДД-07 (без ГПРС) / ДД-07 (со ГПРС) - Барање за даночно ослободување за фискален систем

**UJP pages**: http://www.ujp.gov.mk/mk/obrasci/opis/63 and http://www.ujp.gov.mk/mk/obrasci/opis/156
**Submitted**: By January 20 of the following year, before filing ДБ
**Purpose**: Tax exemption for introducing fiscal cash register equipment (up to 10 devices)

---

## Forms Not Found on UJP (Require Other Sources)

### ДДВ-ИПДО
Not found via UJP search. May be an internal designation or renamed. The term "ИПДО" likely refers to "Извештај за примени/дадени промети на добра и услуги" - which are the attachments to ДДВ-04 (the reverse charge reports referenced in fields 11/25).

### И-ПД (opis/72) and П-ТЦ (opis/73)
These UJP page IDs actually resolve to double-taxation treaty forms (ЗД-В/ДИ = claim for dividend tax refund, and ЗД-В/АП = claim for royalty tax refund). These are NOT the forms you listed. The actual И-ПД and П-ТЦ forms may use different identifiers on UJP.

### МДБ (opis/71)
This UJP page resolves to ЗД-В/ДИ (Барање за враќање на данок на дивиденда), not МДБ. The actual МДБ form may need to be located separately.

### СПД and ДЕ
Not found on ujp.gov.mk. These may be from crm.com.mk (Central Registry) rather than the tax authority:
- **СПД** (Структура на приходи по дејности) - filed with Central Registry as part of annual financial statements
- **ДЕ** (Дополнителни податоци за државна евиденција) - statistical supplement filed with Central Registry

### ДД-ПЗ (opis/74)
UJP page 74 resolves to ЗД-В/ДП (Барање за враќање на данок на други приходи) - a double-taxation treaty form. The actual ДД-ПЗ form was not located.

### ДД-ИД (opis/68) and ДД-И (opis/69)
These UJP IDs resolve to double-taxation treaty forms ЗД-О/КА and ЗД-О/АП. The actual ДД-И (Извештај за уплатениот данок по задршка) is at http://www.ujp.gov.mk/mk/obrasci/opis/76.

---

## Complete UJP Form Index (from ujp.gov.mk/mk/obrasci)

| Code | Full Title | UJP opis ID |
|------|-----------|-------------|
| ДБ | Даночен биланс за оданочување на добивка | 60 |
| ДБ-ВП | Даночен биланс на вкупен приход | 65 |
| ДБ-ДС | Даночен биланс за оданочување со данок за солидарност | 251 |
| ДБ-НП/ВП | Даночен биланс на непрофитни организации | 203 |
| ДД-01 | Барање за пренесување на искажана загуба | 158 |
| ДД-07 (без ГПРС) | Барање за даночно ослободување за фискален систем (без ГПРС) | 63 |
| ДД-07 (со ГПРС) | Барање за одобрение на даночно ослободување за фискален систем | 156 |
| ДД-БС | Барање согласност за признавање на расход во даночен биланс | 212 |
| ДД-ДО | Пресметка на данок при престанување на даночно ослободување | 205 |
| ДДД/П | Пријава за дополнителен данок на добивка / Top-up tax return | 258 |
| ДД-И | Извештај за уплатениот данок по задршка | 76 |
| ДДВ-04 | Даночна пријава на данокот на додадена вредност | 228 (pogledni) |
| ДДВ-ЕПФ | Евиденција на примени фактури | 199 (pogledni) |
| МПИН | Месечна пресметка за интегрирана наплата | 81 |
| ДЛД-ДБ | Годишен даночен биланс за данок на доход од самостојна дејност | 246 (pogledni) |
| Б | Биланс на приходи и расходи | (filed with ДЛД-ДБ) |
| БДО-ДС | Барање за даночно ослободување за донации на спортски субјекти | 201 |
| Б-ПДР/ПЛ | Барање за потврда за даночен резидент (правно лице) | 216 |
| Б-ППД/ПЛ | Барање за потврда за платен данок (правно лице нерезидент) | 245 |

---

## Downloaded PDFs Summary

| File | Form | Size |
|------|------|------|
| DB_Bilans_2020.pdf | ДБ (complete with instructions) | 292 KB |
| DB-VP.pdf | ДБ-ВП (complete with instructions) | 297 KB |
| DB-DS.pdf | ДБ-ДС (complete with instructions) | 111 KB |
| DB-NP-VP.pdf | ДБ-НП/ВП (complete with instructions) | 200 KB |
| DDV-04.pdf | ДДВ-04 (complete with instructions) | 346 KB |
| DDV-EPF.pdf | ДДВ-ЕПФ (complete with instructions) | 170 KB |
| DLD-DB.pdf | ДЛД-ДБ (complete with instructions) | 105 KB |
| B_Bilans.pdf | Б (complete with instructions) | 97 KB |
| MPIN-guide.pdf | МПИН guide (48 pages, all fields documented) | 669 KB |
| e-PPD-guide.pdf | е-ППД guide (all income categories documented) | 516 KB |
| GodisnaPresmetka_2024.pdf | ДЛД-ГДП guide (annual declaration process) | 278 KB |
| Zakon_PDD.pdf | Закон за данокот на личен доход (full law text) | 9.3 MB |
| DD-I_obrazec.pdf | ДД-И (withholding tax report, 5 pages) | 626 KB |
| DD-ID.pdf | ДД-ИД (dividend tax calculation, 4 pages) | 321 KB |
| I-PD.pdf | И-ПД (statement of paid dividend tax, 1 page) | 247 KB |
| DD-01.pdf | ДД-01 (loss carry-forward request, 2 pages) | 277 KB |

---

## Implementation Plan (Phase 0 + Phase 1)

> Added: 2026-03-07
> Status: PLANNED — ready for implementation
> Full roadmap: `docs/plans/ujp-forms-roadmap.md`

### Existing Infrastructure (reuse, don't recreate)

| Component | Status | Location |
|-----------|--------|----------|
| TaxReturn model | EXISTS | `app/Models/TaxReturn.php` — statuses: DRAFT/FILED/ACCEPTED/REJECTED/AMENDED |
| TaxReportPeriod model | EXISTS | `app/Models/TaxReportPeriod.php` — MONTHLY/QUARTERLY/ANNUAL |
| tax_returns table | EXISTS | Has return_data JSON, xml_path, receipt_number, status tracking |
| tax_report_periods table | EXISTS | Unique: company_id + period_type + year + month + quarter |
| VatXmlService | EXISTS | `app/Services/VatXmlService.php` — DDV-04 XML generation |
| CitXmlService | EXISTS | `app/Services/CitXmlService.php` — DB-VP XML generation |
| AopReportService | EXISTS | `app/Services/AopReportService.php` — AOP code mapping for Образец 36/37 |
| VAT/CIT routes | EXISTS | `routes/api.php` lines 698-716 (admin), partner routes also exist |
| PdfPreviewModal.vue | EXISTS | Shared blob → Object URL → modal pattern |

### Architecture Pattern

```
Controller → TaxFormService (abstract) → Config (AOP codes) → XML + PDF → Vue Preview
     ↓              ↓                         ↓
TaxReturn     collect() → validate()     toXml() + toPdf()
(DB record)   (auto + manual fields)     (same data, two outputs)
```

All forms follow this pattern:
1. **collect()** — Auto-populates fields from IFRS data, accepts manual overrides
2. **validate()** — Arithmetic checks (subtotals match, rates correct)
3. **toXml()** — DOMDocument with UJP namespace, XSD-compatible
4. **toPdf()** — DomPDF with official Службен Весник layout
5. **file()** — Saves TaxReturn record with return_data JSON

### Phase 0: Shared Infrastructure (5 new files)

#### 1. TaxFormService — Abstract Base Class
**File**: `app/Services/Tax/TaxFormService.php`

```php
abstract class TaxFormService
{
    abstract public function formCode(): string;      // 'ДДВ-04', 'ДБ', 'Образец 36'
    abstract public function formTitle(): string;      // Full Macedonian title
    abstract public function periodType(): string;     // 'monthly', 'quarterly', 'annual'
    abstract public function collect(Company $company, int $year, ?int $month, ?int $quarter): array;
    abstract public function validate(array $data): array;  // ['errors' => [], 'warnings' => []]
    abstract public function toXml(Company $company, array $data): string;
    abstract public function toPdf(Company $company, array $data): Response;

    // Shared: saves TaxReturn with return_data JSON
    public function file(Company $company, array $data, int $periodId): TaxReturn;
    // Shared: collect + validate in one call
    public function preview(Company $company, int $year, ?int $month, ?int $quarter): array;
}
```

#### 2. UJP PDF Header Partial
**File**: `resources/views/app/pdf/reports/_ujp-form-header.blade.php`

Official UJP form header layout:
- Dark navy bar: "РЕПУБЛИКА СЕВЕРНА МАКЕДОНИЈА / УПРАВА ЗА ЈАВНИ ПРИХОДИ"
- Form code badge (e.g. "Образец ДДВ-04")
- Company info: ЕДБ, назив, адреса, телефон, е-пошта
- Tax period: од — до
- Службен весник reference

#### 3. UJP PDF Footer Partial
**File**: `resources/views/app/pdf/reports/_ujp-form-footer.blade.php`

Signature block: Составил / Одговорно лице / М.П. (печат), date, archive number.

#### 4. Admin Controller
**File**: `app/Http/Controllers/V1/Admin/Tax/UjpFormController.php`

Resolves form service by `{formCode}` URL parameter. 5 endpoints:
- `GET  ujp-forms/{formCode}/preview` — collect + validate
- `POST ujp-forms/{formCode}/xml` — generate XML download
- `POST ujp-forms/{formCode}/pdf` — generate PDF download
- `POST ujp-forms/{formCode}/file` — save TaxReturn record
- `GET  ujp-forms/list` — list available forms with status per company

#### 5. Partner Controller
**File**: `app/Http/Controllers/V1/Partner/PartnerUjpFormController.php`

Same endpoints with partner access checks. Reuses `getPartnerFromRequest`/`hasCompanyAccess` from `PartnerTaxController`.

#### Routes Addition (`routes/api.php`)
```
// Inside existing tax group:
Route::prefix('ujp-forms')->group(function () {
    Route::get('{formCode}/preview', [UjpFormController::class, 'preview']);
    Route::post('{formCode}/xml', [UjpFormController::class, 'generateXml']);
    Route::post('{formCode}/pdf', [UjpFormController::class, 'generatePdf']);
    Route::post('{formCode}/file', [UjpFormController::class, 'file']);
    Route::get('list', [UjpFormController::class, 'list']);
});
```

---

### Phase 1A: ДДВ-04 PDF + Preview (3 new files)

#### 6. DDV04FormService
**File**: `app/Services/Tax/DDV04FormService.php`

- **collect()**: Reuses `VatXmlService` data (make `calculateVatForPeriod()`/`calculateInputVatForPeriod()` public)
- **validate()**: Field 20 = 10-19, Field 29 = 20-28, Field 31 = 29-30
- **toXml()**: Delegates to existing `VatXmlService::generate()`
- **toPdf()**: Official DDV-04 layout with all 32 fields

**Requires MODIFY**: `app/Services/VatXmlService.php` — make data collection methods public

#### 7. DDV-04 PDF Template
**File**: `resources/views/app/pdf/reports/ujp/ddv-04.blade.php`

32 fields in 3 sections:
- Даночна обврска (Output VAT) — fields 1-10
- Претходен данок (Input VAT) — fields 11-25
- Пресметка (Calculation) — fields 26-32

#### 8. DDV-04 Vue Preview
**File**: `resources/scripts/admin/views/partner/accounting/components/DDV04Preview.vue`

Read-only preview with: PDF modal, XML download, File button.

---

### Phase 1B: ДБ Form — 70 AOP Fields (3 new files)

#### 9. DB Form Config
**File**: `config/ujp_forms/db.php`

All 70 AOP fields organized in 9 sections (I-IX):

| Section | AOP Range | Content | Source |
|---------|-----------|---------|--------|
| I | 01 | Financial result from income statement | Auto from IFRS |
| II | 02-39 | Non-deductible expenses (37 categories) | Mostly manual |
| III | 40 | Tax base (I+II) | Auto: AOP 01 + AOP 02 |
| IV | 41-48 | Tax base reductions | Manual |
| V | 49 | Tax base after reductions (III-IV) | Auto: max(0, 40-41) |
| VI | 50 | Calculated tax (V × 10%) | Auto: AOP 49 × 0.10 |
| VII | 51-55 | Tax credits | Manual |
| VIII | 56-59 | Final tax calculation | Auto formulas |
| IX | 60-70 | Special data | Manual |

Auto-calculated fields: AOP 01, 15 (representation 90%), 40, 44 (depreciation), 49, 50, 51, 56, 57, 59.
Manual fields: AOP 03-14, 16-39, 42-48, 52-55, 58, 60-70.

#### 10. DbFormService
**File**: `app/Services/Tax/DbFormService.php`

Extends `TaxFormService`. Key logic:
- AOP 01: `IfrsAdapter` → income statement total
- AOP 15: GL account 551x representation > 90% of total → excess
- AOP 44: Tax depreciation - accounting depreciation
- AOP 57: Prior year advance payments from CIT return
- Validation: arithmetic chain AOP 40→49→50→56→59

#### 11. DB PDF Template
**File**: `resources/views/app/pdf/reports/ujp/db.blade.php`

Official layout matching `docs/ujp-forms/DB_Bilans_2020.pdf`:
- Sections I-IX with Roman numeral headers
- AOP codes right column, labels left, values in bordered cells
- Uses `_ujp-form-header` + `_ujp-form-footer` partials

---

### Phase 1C: Upgraded Образец 36/37 (6 files)

#### 12. Expand AOP Codes
**MODIFY**: `config/ujp_aop.php`

| Form | Current AOP | Official AOP | Gap |
|------|------------|--------------|-----|
| Образец 36 (Balance Sheet) | ~15 codes | 112 codes | +97 |
| Образец 37 (Income Statement) | ~15 codes | 44 codes | +29 |

Expansion approach:
- Use IFRS account code prefix (first 2-3 digits) to classify into sub-AOP codes
- Fallback: if sub-classification fails, aggregate to parent AOP code
- Backward compatible — existing reports still work

#### 13-14. Form Services
- **NEW**: `app/Services/Tax/Obrazec36FormService.php` — delegates to `AopReportService::getBalanceSheetAop()`
- **NEW**: `app/Services/Tax/Obrazec37FormService.php` — delegates to `AopReportService::getIncomeStatementAop()`

#### 15-16. PDF Templates
- **NEW**: `resources/views/app/pdf/reports/ujp/obrazec-36.blade.php` — Official balance sheet
- **NEW**: `resources/views/app/pdf/reports/ujp/obrazec-37.blade.php` — Official income statement

---

### Vue Hub Page (3 new files + 1 modified)

#### 17. UJP Forms Hub
**File**: `resources/scripts/admin/views/partner/accounting/UjpForms.vue`

Grid of form cards showing: form code, title, period selector, status badge, preview/generate actions.

#### 18. UjpFormCard Component
**File**: `resources/scripts/admin/views/partner/accounting/components/UjpFormCard.vue`

Reusable card: icon, title, description, period, status, action buttons.

#### 19. DB Form Interactive Preview
**File**: `resources/scripts/admin/views/partner/accounting/components/DbFormPreview.vue`

- Auto-calc fields: green background, read-only
- Manual fields: editable `BaseInput type="number"`
- Collapsible sections, inline validation warnings
- Actions: Preview PDF, Generate XML, File

#### 20. Router
**MODIFY**: `resources/scripts/admin/admin-router.js` — add `partner.accounting.ujp-forms` route

---

### Complete File Summary

| # | File | Action | Phase |
|---|------|--------|-------|
| 1 | `app/Services/Tax/TaxFormService.php` | NEW | 0 |
| 2 | `resources/views/app/pdf/reports/_ujp-form-header.blade.php` | NEW | 0 |
| 3 | `resources/views/app/pdf/reports/_ujp-form-footer.blade.php` | NEW | 0 |
| 4 | `app/Http/Controllers/V1/Admin/Tax/UjpFormController.php` | NEW | 0 |
| 5 | `app/Http/Controllers/V1/Partner/PartnerUjpFormController.php` | NEW | 0 |
| 6 | `app/Services/Tax/DDV04FormService.php` | NEW | 1A |
| 7 | `resources/views/app/pdf/reports/ujp/ddv-04.blade.php` | NEW | 1A |
| 8 | `resources/scripts/admin/views/partner/accounting/components/DDV04Preview.vue` | NEW | 1A |
| 9 | `config/ujp_forms/db.php` | NEW | 1B |
| 10 | `app/Services/Tax/DbFormService.php` | NEW | 1B |
| 11 | `resources/views/app/pdf/reports/ujp/db.blade.php` | NEW | 1B |
| 12 | `app/Services/Tax/Obrazec36FormService.php` | NEW | 1C |
| 13 | `app/Services/Tax/Obrazec37FormService.php` | NEW | 1C |
| 14 | `resources/views/app/pdf/reports/ujp/obrazec-36.blade.php` | NEW | 1C |
| 15 | `resources/views/app/pdf/reports/ujp/obrazec-37.blade.php` | NEW | 1C |
| 16 | `resources/scripts/admin/views/partner/accounting/UjpForms.vue` | NEW | Vue |
| 17 | `resources/scripts/admin/views/partner/accounting/components/UjpFormCard.vue` | NEW | Vue |
| 18 | `resources/scripts/admin/views/partner/accounting/components/DbFormPreview.vue` | NEW | Vue |
| 19 | `routes/api.php` | MODIFY | 0 |
| 20 | `resources/scripts/admin/admin-router.js` | MODIFY | Vue |
| 21 | `config/ujp_aop.php` | MODIFY | 1C |
| 22 | `app/Services/VatXmlService.php` | MODIFY | 1A |

### Implementation Order
1. Phase 0: Steps 1-4 (infrastructure)
2. Phase 1A: Steps 5-7 (DDV-04)
3. Phase 1B: Steps 8-10 (DB form)
4. Vue: Steps 14-15, 17-20 (hub + components + router)
5. Phase 1C: Steps 11-13, 15-16 (Образец 36/37 expansion)

### Verification
1. Unit tests: Each FormService collect/validate/toXml
2. Feature tests: Controller endpoints (preview JSON, XML/PDF downloads, TaxReturn records)
3. Visual: Compare PDFs with official forms in `docs/ujp-forms/`
4. Integration: Existing VAT/CIT flows unbroken
5. Print: A4 margins, Cyrillic rendering in DejaVu Sans
