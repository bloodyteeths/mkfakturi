'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import { type Locale } from '@/i18n/locales'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

/* ─── Deadline types ─── */
type DeadlineType = 'vat' | 'payroll' | 'corporate' | 'annual'

interface Deadline {
  day: number
  month: number // 1-based
  type: DeadlineType
  recurring: 'monthly' | 'quarterly' | 'annual'
  titleKey: string
  descKey: string
  fileKey: string
  penaltyKey: string
}

/* ─── All 2026 deadlines ─── */
const DEADLINES: Deadline[] = [
  // Monthly — МПИН (payroll contributions)
  ...Array.from({ length: 12 }, (_, i) => ({
    day: 15,
    month: i + 1,
    type: 'payroll' as DeadlineType,
    recurring: 'monthly' as const,
    titleKey: 'mpinTitle',
    descKey: 'mpinDesc',
    fileKey: 'mpinFile',
    penaltyKey: 'mpinPenalty',
  })),
  // Monthly — Salary payment
  ...Array.from({ length: 12 }, (_, i) => ({
    day: 15,
    month: i + 1,
    type: 'payroll' as DeadlineType,
    recurring: 'monthly' as const,
    titleKey: 'salaryTitle',
    descKey: 'salaryDesc',
    fileKey: 'salaryFile',
    penaltyKey: 'salaryPenalty',
  })),
  // Monthly — Corporate tax advance
  ...Array.from({ length: 12 }, (_, i) => ({
    day: 15,
    month: i + 1,
    type: 'corporate' as DeadlineType,
    recurring: 'monthly' as const,
    titleKey: 'corpAdvanceTitle',
    descKey: 'corpAdvanceDesc',
    fileKey: 'corpAdvanceFile',
    penaltyKey: 'corpAdvancePenalty',
  })),
  // Quarterly — ДДВ-04
  { day: 25, month: 1, type: 'vat', recurring: 'quarterly', titleKey: 'ddv04Title', descKey: 'ddv04Desc', fileKey: 'ddv04File', penaltyKey: 'ddv04Penalty' },
  { day: 25, month: 4, type: 'vat', recurring: 'quarterly', titleKey: 'ddv04Title', descKey: 'ddv04Desc', fileKey: 'ddv04File', penaltyKey: 'ddv04Penalty' },
  { day: 25, month: 7, type: 'vat', recurring: 'quarterly', titleKey: 'ddv04Title', descKey: 'ddv04Desc', fileKey: 'ddv04File', penaltyKey: 'ddv04Penalty' },
  { day: 25, month: 10, type: 'vat', recurring: 'quarterly', titleKey: 'ddv04Title', descKey: 'ddv04Desc', fileKey: 'ddv04File', penaltyKey: 'ddv04Penalty' },
  // Annual — Feb 28
  { day: 28, month: 2, type: 'annual', recurring: 'annual', titleKey: 'annualAccountsTitle', descKey: 'annualAccountsDesc', fileKey: 'annualAccountsFile', penaltyKey: 'annualAccountsPenalty' },
  // Annual — Mar 15
  { day: 15, month: 3, type: 'corporate', recurring: 'annual', titleKey: 'dbvpTitle', descKey: 'dbvpDesc', fileKey: 'dbvpFile', penaltyKey: 'dbvpPenalty' },
  { day: 15, month: 3, type: 'corporate', recurring: 'annual', titleKey: 'corpAnnualTitle', descKey: 'corpAnnualDesc', fileKey: 'corpAnnualFile', penaltyKey: 'corpAnnualPenalty' },
  // Annual — Mar 31
  { day: 31, month: 3, type: 'vat', recurring: 'annual', titleKey: 'ddvAnnualTitle', descKey: 'ddvAnnualDesc', fileKey: 'ddvAnnualFile', penaltyKey: 'ddvAnnualPenalty' },
]

/* ─── Copy: all 4 locales ─── */
const copy = {
  mk: {
    backLink: '← Сите алатки',
    badge: 'Бесплатно',
    h1: 'Даночен календар 2026',
    subtitle: 'Сите даночни рокови за Северна Македонија на едно место. МПИН, ДДВ, данок на добивка и годишна сметка — никогаш не пропуштајте рок.',
    monthNames: ['Јануари', 'Февруари', 'Март', 'Април', 'Мај', 'Јуни', 'Јули', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември'],
    filterAll: 'Сите',
    filterVat: 'ДДВ',
    filterPayroll: 'МПИН / Плата',
    filterCorporate: 'Данок на добивка',
    filterAnnual: 'Годишни',
    typeBadge: { vat: 'ДДВ', payroll: 'МПИН', corporate: 'Добивка', annual: 'Годишно' },
    recurring: { monthly: 'Месечно', quarterly: 'Квартално', annual: 'Еднаш годишно' },
    dateLabel: 'Датум',
    whatToFile: 'Што се поднесува',
    penalty: 'Казна за пропуштање',
    todayLabel: 'Денес',
    upcomingLabel: 'Претстои',
    passedLabel: 'Поминато',
    noDeadlines: 'Нема рокови за овој месец со избраниот филтер.',
    // Deadline details
    mpinTitle: 'МПИН поднесување',
    mpinDesc: 'Месечно поднесување на Месечна пресметка за интегрирана наплата (МПИН) до УЈП за придонеси од плата.',
    mpinFile: 'Образец МПИН преку е-Даноци',
    mpinPenalty: '1.000–5.000 EUR глоба + камата за секој ден задоцнување',
    salaryTitle: 'Исплата на плата',
    salaryDesc: 'Краен рок за исплата на плата на вработените за претходниот месец.',
    salaryFile: 'Вирман за плата преку банка',
    salaryPenalty: '500–3.000 EUR глоба по Законот за работни односи',
    corpAdvanceTitle: 'Аконтација данок на добивка',
    corpAdvanceDesc: 'Месечна аконтација (1/12 од годишниот данок на добивка) до УЈП.',
    corpAdvanceFile: 'Образец ДП преку е-Даноци',
    corpAdvancePenalty: 'Камата 0,03% дневно + 1.000–3.000 EUR глоба',
    ddv04Title: 'ДДВ-04 квартална пријава',
    ddv04Desc: 'Квартална пријава за данок на додадена вредност за тримесечни обврзници.',
    ddv04File: 'Образец ДДВ-04 преку е-Даноци',
    ddv04Penalty: '2.500–5.000 EUR глоба + камата + загуба на право на одбивка',
    annualAccountsTitle: 'Годишна сметка (ЦРСМ)',
    annualAccountsDesc: 'Поднесување годишна сметка и финансиски извештаи до Централен регистар на РСМ.',
    annualAccountsFile: 'Биланс на состојба + Биланс на успех преку ЦРРСМ портал',
    annualAccountsPenalty: '2.000–5.000 EUR глоба + бришење од регистар',
    dbvpTitle: 'ДБ-ВП (Даночен биланс)',
    dbvpDesc: 'Годишен даночен биланс за данок на добивка поднесен до УЈП.',
    dbvpFile: 'Образец ДБ-ВП преку е-Даноци',
    dbvpPenalty: '2.500–5.000 EUR глоба + процена на данок од УЈП',
    corpAnnualTitle: 'Данок на добивка — годишна уплата',
    corpAnnualDesc: 'Конечна уплата на данок на добивка за претходната фискална година (разлика меѓу аконтации и конечен износ).',
    corpAnnualFile: 'Уплата преку ПП50 налог',
    corpAnnualPenalty: 'Камата 0,03% дневно + 2.500–5.000 EUR глоба',
    ddvAnnualTitle: 'ДДВ годишна рекапитулација',
    ddvAnnualDesc: 'Годишна рекапитулација на ДДВ за усогласување на квартални/месечни пријави.',
    ddvAnnualFile: 'Образец ДДВ-годишна преку е-Даноци',
    ddvAnnualPenalty: '2.500–5.000 EUR глоба',
    // Educational section
    eduTitle: 'Што треба да знаете за даночните рокови',
    eduMissTitle: 'Што се случува ако пропуштите рок?',
    eduMissText: 'УЈП автоматски пресметува камата од 0,03% дневно на неплатениот данок. Дополнително, може да ви биде изречена глоба од 1.000 до 5.000 EUR. За повторни прекршоци, казните се зголемуваат. При непоробно на годишна сметка, ЦРРСМ може да поведе постапка за бришење на фирмата.',
    eduRemindTitle: 'Како да поставите потсетници?',
    eduRemindText: 'Facturino има вграден систем за потсетници кој автоматски ве известува 7, 3 и 1 ден пред секој даночен рок. Не треба рачно да следите — системот работи автоматски.',
    eduCommonTitle: 'Најчесто пропуштени рокови',
    eduCommonText: 'Според нашето искуство, најчесто се пропуштаат: (1) ДДВ-04 кварталната пријава на 25-ти, (2) МПИН на 15-ти кај мали фирми без сметководител, и (3) годишната сметка на 28 февруари. Ризикот е најголем во јануари-март кога се преклопуваат годишни и месечни обврски.',
    // FAQ
    faqTitle: 'Најчесто поставувани прашања',
    faq: [
      {
        q: 'Кога е рокот за МПИН секој месец?',
        a: 'МПИН (Месечна пресметка за интегрирана наплата) се поднесува до 15-ти во тековниот месец за претходниот месец. Се поднесува електронски преку порталот е-Даноци на УЈП. Пријавата ги опфаќа сите придонеси: пензиско (18,8%), здравствено (7,5%), невработеност (1,2%) и дополнително (0,5%).',
      },
      {
        q: 'Кога се поднесува кварталната ДДВ пријава?',
        a: 'Кварталната ДДВ пријава (Образец ДДВ-04) се поднесува до 25-ти во месецот по завршување на кварталот: 25 јануари, 25 април, 25 јули и 25 октомври. Месечните обврзници поднесуваат до 25-ти секој месец.',
      },
      {
        q: 'Кој е рокот за годишна сметка?',
        a: 'Годишната сметка се поднесува до 28 февруари (или 29 во престапна година) до Централниот регистар на РСМ. Ова ги вклучува Билансот на состојба, Билансот на успех и другите финансиски извештаи пропишани со Законот за трговски друштва.',
      },
      {
        q: 'Кои се казните за задоцнето поднесување?',
        a: 'Казните варираат зависно од обврската: глоба од 1.000 до 5.000 EUR за правно лице, плус камата од 0,03% дневно на неплатен данок. За непоробно на годишна сметка, ЦРРСМ може да поведе постапка за бришење. За МПИН задоцнување, вработените губат здравствено осигурување.',
      },
    ],
    // CTA
    ctaInline: 'Facturino ве потсетува автоматски за сите даночни рокови.',
    ctaButton: 'Пробај бесплатно',
    ctaTitle: 'Никогаш не пропуштајте даночен рок',
    ctaSub: 'Facturino има вграден даночен календар со автоматски потсетници. Поврзете го сметководството со роковите — на едно место.',
    ctaMainButton: 'Започни бесплатно — 14 дена',
    ctaSecondary: 'Закажи демо',
    legend: 'Легенда',
  },
  sq: {
    backLink: '← Të gjitha mjetet',
    badge: 'Falas',
    h1: 'Kalendari Tatimor 2026',
    subtitle: 'Të gjitha afatet tatimore për Maqedoninë e Veriut në një vend. MPIN, TVSH, tatimi mbi fitimin dhe llogaritë vjetore — mos humbisni asnjë afat.',
    monthNames: ['Janar', 'Shkurt', 'Mars', 'Prill', 'Maj', 'Qershor', 'Korrik', 'Gusht', 'Shtator', 'Tetor', 'Nëntor', 'Dhjetor'],
    filterAll: 'Të gjitha',
    filterVat: 'TVSH',
    filterPayroll: 'MPIN / Paga',
    filterCorporate: 'Tatimi mbi fitimin',
    filterAnnual: 'Vjetore',
    typeBadge: { vat: 'TVSH', payroll: 'MPIN', corporate: 'Fitimi', annual: 'Vjetore' },
    recurring: { monthly: 'Mujore', quarterly: 'Tremujore', annual: 'Vjetore' },
    dateLabel: 'Data',
    whatToFile: 'Cfarë dorëzohet',
    penalty: 'Gjoba për humbje',
    todayLabel: 'Sot',
    upcomingLabel: 'Së shpejti',
    passedLabel: 'Ka kaluar',
    noDeadlines: 'Nuk ka afate për këtë muaj me filtrin e zgjedhur.',
    mpinTitle: 'Dorëzimi i MPIN',
    mpinDesc: 'Dorëzimi mujor i Llogaritjes Mujore për Arkëtim të Integruar (MPIN) pranë DAP për kontributet nga paga.',
    mpinFile: 'Formulari MPIN përmes e-Tatimit',
    mpinPenalty: '1.000–5.000 EUR gjobë + kamatë për çdo ditë vonesë',
    salaryTitle: 'Pagesa e pagës',
    salaryDesc: 'Afati i fundit për pagesën e pagës së punonjësve për muajin e kaluar.',
    salaryFile: 'Urdhër pagese përmes bankës',
    salaryPenalty: '500–3.000 EUR gjobë sipas Ligjit të Marrëdhënieve të Punës',
    corpAdvanceTitle: 'Akontuesi i tatimit mbi fitimin',
    corpAdvanceDesc: 'Akontacion mujor (1/12 e tatimit vjetor mbi fitimin) pranë DAP.',
    corpAdvanceFile: 'Formulari DP përmes e-Tatimit',
    corpAdvancePenalty: 'Kamatë 0,03% ditore + 1.000–3.000 EUR gjobë',
    ddv04Title: 'TVSH-04 deklarata tremujore',
    ddv04Desc: 'Deklarata tremujore e tatimit mbi vlerën e shtuar për obliguesit tremujorë.',
    ddv04File: 'Formulari DDV-04 përmes e-Tatimit',
    ddv04Penalty: '2.500–5.000 EUR gjobë + kamatë + humbje e të drejtës së zbritjes',
    annualAccountsTitle: 'Llogaritë vjetore (QRRM)',
    annualAccountsDesc: 'Dorëzimi i llogarive vjetore dhe raporteve financiare pranë Regjistrit Qendror të RMV.',
    annualAccountsFile: 'Bilanci + Pasqyra e suksesit përmes portalit QRRM',
    annualAccountsPenalty: '2.000–5.000 EUR gjobë + fshirje nga regjistri',
    dbvpTitle: 'DB-VP (Bilanci tatimor)',
    dbvpDesc: 'Bilanci vjetor tatimor mbi fitimin i dorëzuar pranë DAP.',
    dbvpFile: 'Formulari DB-VP përmes e-Tatimit',
    dbvpPenalty: '2.500–5.000 EUR gjobë + vlerësim tatimor nga DAP',
    corpAnnualTitle: 'Tatimi mbi fitimin — pagesa vjetore',
    corpAnnualDesc: 'Pagesa përfundimtare e tatimit mbi fitimin për vitin fiskal të kaluar (diferenca midis akontacioneve dhe shumës përfundimtare).',
    corpAnnualFile: 'Pagesë përmes urdhrit PP50',
    corpAnnualPenalty: 'Kamatë 0,03% ditore + 2.500–5.000 EUR gjobë',
    ddvAnnualTitle: 'TVSH rekapitullimi vjetor',
    ddvAnnualDesc: 'Rekapitullimi vjetor i TVSH-së për harmonizimin e deklaratave tremujore/mujore.',
    ddvAnnualFile: 'Formulari DDV-vjetore përmes e-Tatimit',
    ddvAnnualPenalty: '2.500–5.000 EUR gjobë',
    eduTitle: 'Cfarë duhet të dini për afatet tatimore',
    eduMissTitle: 'Cfarë ndodh nëse humbisni një afat?',
    eduMissText: 'DAP automatikisht llogarit kamatë 0,03% ditore mbi tatimin e papaguar. Gjithashtu, mund t\'ju vendoset gjobë nga 1.000 deri 5.000 EUR. Për shkelje të përsëritura, gjobat rriten. Për mos-dorëzim të llogarive vjetore, QRRM mund të nisë procedurë fshirjeje.',
    eduRemindTitle: 'Si të vendosni përkujtues?',
    eduRemindText: 'Facturino ka sistem të integruar përkujtuesish që ju njofton automatikisht 7, 3 dhe 1 ditë para çdo afati tatimor. Nuk keni nevojë të ndiqni manualisht — sistemi punon automatikisht.',
    eduCommonTitle: 'Afatet më shpesh të humbura',
    eduCommonText: 'Sipas përvojës sonë, më shpesh humbasin: (1) DDV-04 deklarata tremujore më 25, (2) MPIN më 15 tek bizneset e vogla pa kontabilist, dhe (3) llogaritë vjetore më 28 shkurt. Risku është më i madh janar-mars kur përputhën obligimet vjetore dhe mujore.',
    faqTitle: 'Pyetjet më të shpeshta',
    faq: [
      {
        q: 'Kur është afati i MPIN çdo muaj?',
        a: 'MPIN (Llogaritja Mujore për Arkëtim të Integruar) dorëzohet deri më 15 të muajit aktual për muajin e kaluar. Dorëzohet elektronikisht përmes portalit e-Tatimi të DAP. Deklarata mbulon të gjitha kontributet: pensioni (18,8%), shëndetësore (7,5%), papunësia (1,2%) dhe shtesë (0,5%).',
      },
      {
        q: 'Kur dorëzohet deklarata tremujore e TVSH-së?',
        a: 'Deklarata tremujore e TVSH-së (Formulari DDV-04) dorëzohet deri më 25 të muajit pas përfundimit të tremujorit: 25 janar, 25 prill, 25 korrik dhe 25 tetor. Obliguesit mujorë dorëzojnë deri më 25 çdo muaj.',
      },
      {
        q: 'Cili është afati për llogaritë vjetore?',
        a: 'Llogaritë vjetore dorëzohen deri më 28 shkurt (ose 29 në vit brishtë) pranë Regjistrit Qendror të RMV. Kjo përfshin Bilancin, Pasqyrën e suksesit dhe raporte të tjera financiare të përcaktuara me Ligjin e Shoqërive Tregtare.',
      },
      {
        q: 'Cilat janë gjobat për dorëzim me vonesë?',
        a: 'Gjobat variojnë sipas obligimit: gjobë nga 1.000 deri 5.000 EUR për personin juridik, plus kamatë 0,03% ditore mbi tatimin e papaguar. Për mos-dorëzim të llogarive vjetore, QRRM mund të nisë procedurë fshirjeje. Për vonesë MPIN, punonjësit humbasin siguriminё shëndetësor.',
      },
    ],
    ctaInline: 'Facturino ju përkujton automatikisht për të gjitha afatet tatimore.',
    ctaButton: 'Provo falas',
    ctaTitle: 'Mos humbisni asnjë afat tatimor',
    ctaSub: 'Facturino ka kalendar tatimor të integruar me përkujtues automatikë. Lidhni kontabilitetin me afatet — në një vend.',
    ctaMainButton: 'Fillo falas — 14 ditë',
    ctaSecondary: 'Cakto demo',
    legend: 'Legjenda',
  },
  tr: {
    backLink: '← Tüm araçlar',
    badge: 'Ücretsiz',
    h1: 'Vergi Takvimi 2026',
    subtitle: 'Kuzey Makedonya için tüm vergi son tarihleri tek bir yerde. MPIN, KDV, kurumlar vergisi ve yıllık hesaplar — hiçbir tarihi kaçırmayın.',
    monthNames: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
    filterAll: 'Tümü',
    filterVat: 'KDV',
    filterPayroll: 'MPIN / Maaş',
    filterCorporate: 'Kurumlar Vergisi',
    filterAnnual: 'Yıllık',
    typeBadge: { vat: 'KDV', payroll: 'MPIN', corporate: 'Kurumlar', annual: 'Yıllık' },
    recurring: { monthly: 'Aylık', quarterly: 'Üç aylık', annual: 'Yılda bir' },
    dateLabel: 'Tarih',
    whatToFile: 'Ne bildirilir',
    penalty: 'Geçirme cezası',
    todayLabel: 'Bugün',
    upcomingLabel: 'Yaklaşan',
    passedLabel: 'Geçmiş',
    noDeadlines: 'Seçilen filtre ile bu ay için son tarih yok.',
    mpinTitle: 'MPIN bildirimi',
    mpinDesc: 'Maaş katkıları için Entegre Tahsilat Aylık Hesaplama (MPIN) belgesinin GGİ\'ye aylık bildirimi.',
    mpinFile: 'MPIN Formu e-Vergi üzerinden',
    mpinPenalty: 'Her gecikme günü için 1.000–5.000 EUR ceza + faiz',
    salaryTitle: 'Maaş ödemesi',
    salaryDesc: 'Önceki ay için çalışanlara maaş ödemesi son tarihi.',
    salaryFile: 'Banka havalesi ile maaş ödemesi',
    salaryPenalty: 'İş Kanunu\'na göre 500–3.000 EUR ceza',
    corpAdvanceTitle: 'Kurumlar vergisi avansı',
    corpAdvanceDesc: 'GGİ\'ye aylık avans (yıllık kurumlar vergisinin 1/12\'si).',
    corpAdvanceFile: 'DP Formu e-Vergi üzerinden',
    corpAdvancePenalty: 'Günlük %0,03 faiz + 1.000–3.000 EUR ceza',
    ddv04Title: 'KDV-04 üç aylık beyanname',
    ddv04Desc: 'Üç aylık mükellefler için katma değer vergisi üç aylık beyannamesi.',
    ddv04File: 'DDV-04 Formu e-Vergi üzerinden',
    ddv04Penalty: '2.500–5.000 EUR ceza + faiz + indirim hakkı kaybı',
    annualAccountsTitle: 'Yıllık hesaplar (MSKT)',
    annualAccountsDesc: 'KMC Merkez Sicil\'e yıllık hesap ve mali tabloların sunulması.',
    annualAccountsFile: 'Bilanço + Gelir tablosu MSKT portalı üzerinden',
    annualAccountsPenalty: '2.000–5.000 EUR ceza + sicilden silinme',
    dbvpTitle: 'DB-VP (Vergi bilançosu)',
    dbvpDesc: 'GGİ\'ye sunulan yıllık kurumlar vergisi bilançosu.',
    dbvpFile: 'DB-VP Formu e-Vergi üzerinden',
    dbvpPenalty: '2.500–5.000 EUR ceza + GGİ tarafından vergi takdiri',
    corpAnnualTitle: 'Kurumlar vergisi — yıllık ödeme',
    corpAnnualDesc: 'Önceki mali yıl için kurumlar vergisinin nihai ödemesi (avanslar ile nihai tutar arasındaki fark).',
    corpAnnualFile: 'PP50 ödeme emri ile ödeme',
    corpAnnualPenalty: 'Günlük %0,03 faiz + 2.500–5.000 EUR ceza',
    ddvAnnualTitle: 'KDV yıllık mutabakat',
    ddvAnnualDesc: 'Üç aylık/aylık beyannamelerin mutabakatı için yıllık KDV rekapitülasyonu.',
    ddvAnnualFile: 'KDV-yıllık Formu e-Vergi üzerinden',
    ddvAnnualPenalty: '2.500–5.000 EUR ceza',
    eduTitle: 'Vergi son tarihleri hakkında bilmeniz gerekenler',
    eduMissTitle: 'Bir son tarihi kaçırırsanız ne olur?',
    eduMissText: 'GGİ ödenmemiş vergi üzerinden günlük %0,03 faiz otomatik hesaplar. Ayrıca 1.000 ila 5.000 EUR arası ceza verilebilir. Tekrarlanan ihlallerde cezalar artar. Yıllık hesapların sunulmaması durumunda MSKT silme işlemi başlatabilir.',
    eduRemindTitle: 'Hatırlatıcılar nasıl ayarlanır?',
    eduRemindText: 'Facturino\'nun yerleşik hatırlatma sistemi her vergi son tarihinden 7, 3 ve 1 gün önce sizi otomatik bilgilendirir. Manuel takip gerekmez — sistem otomatik çalışır.',
    eduCommonTitle: 'En sık kaçırılan son tarihler',
    eduCommonText: 'Deneyimlerimize göre en çok kaçırılanlar: (1) 25\'inde KDV-04 üç aylık beyanname, (2) muhasebecisi olmayan küçük işletmelerde 15\'inde MPIN ve (3) 28 Şubat\'ta yıllık hesaplar. Risk Ocak-Mart arasında en yüksektir çünkü yıllık ve aylık yükümlülükler örtüşür.',
    faqTitle: 'Sık sorulan sorular',
    faq: [
      {
        q: 'MPIN son tarihi her ay ne zaman?',
        a: 'MPIN (Entegre Tahsilat Aylık Hesaplama) önceki ay için cari ayın 15\'ine kadar bildirilir. GGİ\'nin e-Vergi portalı üzerinden elektronik bildirilir. Beyan tüm katkıları kapsar: emeklilik (%18,8), sağlık (%7,5), işsizlik (%1,2) ve ek (%0,5).',
      },
      {
        q: 'Üç aylık KDV beyannamesi ne zaman verilir?',
        a: 'Üç aylık KDV beyannamesi (Form DDV-04) çeyreğin bitiminden sonraki ayın 25\'ine kadar verilir: 25 Ocak, 25 Nisan, 25 Temmuz ve 25 Ekim. Aylık mükellefler her ayın 25\'ine kadar verir.',
      },
      {
        q: 'Yıllık hesaplar için son tarih nedir?',
        a: 'Yıllık hesaplar 28 Şubat\'a (veya artık yılda 29) kadar KMC Merkez Sicil\'e sunulur. Bilanço, Gelir tablosu ve Ticaret Şirketleri Kanunu ile belirlenen diğer mali tabloları içerir.',
      },
      {
        q: 'Geç bildirim cezaları nelerdir?',
        a: 'Cezalar yükümlülüğe göre değişir: tüzel kişi için 1.000 ila 5.000 EUR ceza, artı ödenmemiş vergi üzerinden günlük %0,03 faiz. Yıllık hesapların sunulmaması durumunda MSKT silme işlemi başlatabilir. MPIN gecikmesinde çalışanlar sağlık sigortasını kaybeder.',
      },
    ],
    ctaInline: 'Facturino tüm vergi son tarihleri için sizi otomatik hatırlatır.',
    ctaButton: 'Ücretsiz dene',
    ctaTitle: 'Hiçbir vergi son tarihini kaçırmayın',
    ctaSub: 'Facturino\'nun otomatik hatırlatıcılı yerleşik vergi takvimi var. Muhasebeyi son tarihlerle birleştirin — tek bir yerde.',
    ctaMainButton: 'Ücretsiz başla — 14 gün',
    ctaSecondary: 'Demo planla',
    legend: 'Lejand',
  },
  en: {
    backLink: '← All tools',
    badge: 'Free',
    h1: 'Tax Calendar 2026',
    subtitle: 'All tax deadlines for North Macedonia in one place. MPIN, VAT, corporate tax, and annual accounts — never miss a deadline.',
    monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    filterAll: 'All',
    filterVat: 'VAT',
    filterPayroll: 'MPIN / Salary',
    filterCorporate: 'Corporate Tax',
    filterAnnual: 'Annual',
    typeBadge: { vat: 'VAT', payroll: 'MPIN', corporate: 'Corporate', annual: 'Annual' },
    recurring: { monthly: 'Monthly', quarterly: 'Quarterly', annual: 'Once a year' },
    dateLabel: 'Date',
    whatToFile: 'What to file',
    penalty: 'Penalty for missing',
    todayLabel: 'Today',
    upcomingLabel: 'Upcoming',
    passedLabel: 'Passed',
    noDeadlines: 'No deadlines for this month with the selected filter.',
    mpinTitle: 'MPIN filing',
    mpinDesc: 'Monthly filing of the Monthly Calculation for Integrated Collection (MPIN) to UJP for salary contributions.',
    mpinFile: 'MPIN form via e-Tax portal',
    mpinPenalty: '1,000-5,000 EUR fine + daily interest for each day of delay',
    salaryTitle: 'Salary payment',
    salaryDesc: 'Deadline for paying employee salaries for the previous month.',
    salaryFile: 'Bank transfer salary payment',
    salaryPenalty: '500-3,000 EUR fine per Labour Relations Law',
    corpAdvanceTitle: 'Corporate tax advance',
    corpAdvanceDesc: 'Monthly advance payment (1/12 of annual corporate tax) to UJP.',
    corpAdvanceFile: 'DP form via e-Tax portal',
    corpAdvancePenalty: '0.03% daily interest + 1,000-3,000 EUR fine',
    ddv04Title: 'DDV-04 quarterly VAT return',
    ddv04Desc: 'Quarterly value-added tax return for quarterly filers.',
    ddv04File: 'DDV-04 form via e-Tax portal',
    ddv04Penalty: '2,500-5,000 EUR fine + interest + loss of input VAT deduction right',
    annualAccountsTitle: 'Annual accounts (CRSM)',
    annualAccountsDesc: 'Filing annual accounts and financial statements to the Central Registry of North Macedonia.',
    annualAccountsFile: 'Balance sheet + Income statement via CRSM portal',
    annualAccountsPenalty: '2,000-5,000 EUR fine + registry deletion proceedings',
    dbvpTitle: 'DB-VP (Tax balance)',
    dbvpDesc: 'Annual corporate tax balance filed with UJP.',
    dbvpFile: 'DB-VP form via e-Tax portal',
    dbvpPenalty: '2,500-5,000 EUR fine + tax assessment by UJP',
    corpAnnualTitle: 'Corporate tax — annual payment',
    corpAnnualDesc: 'Final corporate tax payment for the previous fiscal year (difference between advances and final amount).',
    corpAnnualFile: 'Payment via PP50 order',
    corpAnnualPenalty: '0.03% daily interest + 2,500-5,000 EUR fine',
    ddvAnnualTitle: 'VAT annual reconciliation',
    ddvAnnualDesc: 'Annual VAT recapitulation for reconciling quarterly/monthly returns.',
    ddvAnnualFile: 'DDV-annual form via e-Tax portal',
    ddvAnnualPenalty: '2,500-5,000 EUR fine',
    eduTitle: 'What you need to know about tax deadlines',
    eduMissTitle: 'What happens if you miss a deadline?',
    eduMissText: 'UJP automatically calculates 0.03% daily interest on unpaid tax. Additionally, a fine of 1,000 to 5,000 EUR may be imposed. For repeat offenses, penalties increase. For failure to submit annual accounts, CRSM may initiate company deletion proceedings.',
    eduRemindTitle: 'How to set up reminders?',
    eduRemindText: 'Facturino has a built-in reminder system that automatically notifies you 7, 3, and 1 day before every tax deadline. No manual tracking needed — the system works automatically.',
    eduCommonTitle: 'Most commonly missed deadlines',
    eduCommonText: 'Based on our experience, the most commonly missed are: (1) DDV-04 quarterly return on the 25th, (2) MPIN on the 15th at small businesses without an accountant, and (3) annual accounts on February 28. Risk is highest in January-March when annual and monthly obligations overlap.',
    faqTitle: 'Frequently Asked Questions',
    faq: [
      {
        q: 'When is the MPIN deadline each month?',
        a: 'MPIN (Monthly Calculation for Integrated Collection) is filed by the 15th of the current month for the previous month. Filed electronically via UJP\'s e-Tax portal. The declaration covers all contributions: pension (18.8%), health (7.5%), unemployment (1.2%), and supplementary (0.5%).',
      },
      {
        q: 'When is the quarterly VAT return filed?',
        a: 'The quarterly VAT return (Form DDV-04) is filed by the 25th of the month after the quarter ends: January 25, April 25, July 25, and October 25. Monthly filers submit by the 25th every month.',
      },
      {
        q: 'What is the deadline for annual accounts?',
        a: 'Annual accounts are filed by February 28 (or 29 in a leap year) with the Central Registry of North Macedonia. This includes the Balance sheet, Income statement, and other financial statements prescribed by the Trade Companies Law.',
      },
      {
        q: 'What are the penalties for late filing?',
        a: 'Penalties vary by obligation: 1,000 to 5,000 EUR fine for legal entities, plus 0.03% daily interest on unpaid tax. For failure to submit annual accounts, CRSM may initiate deletion proceedings. For MPIN delays, employees lose health insurance coverage.',
      },
    ],
    ctaInline: 'Facturino automatically reminds you of all tax deadlines.',
    ctaButton: 'Try for free',
    ctaTitle: 'Never miss a tax deadline',
    ctaSub: 'Facturino has a built-in tax calendar with automatic reminders. Connect accounting with deadlines — in one place.',
    ctaMainButton: 'Start free — 14 days',
    ctaSecondary: 'Schedule demo',
    legend: 'Legend',
  },
} as const

/* ─── Type color map ─── */
const TYPE_COLORS: Record<DeadlineType, { bg: string; text: string; border: string; dot: string }> = {
  vat: { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-500' },
  payroll: { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
  corporate: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' },
  annual: { bg: 'bg-purple-50', text: 'text-purple-700', border: 'border-purple-200', dot: 'bg-purple-500' },
}

export default function TaxCalendar({ locale }: { locale: Locale }) {
  const t = useMemo(() => copy[locale], [locale])

  const today = useMemo(() => new Date(), [])
  const currentMonth = today.getMonth() // 0-based
  const currentDay = today.getDate()
  const currentYear = today.getFullYear()

  const [selectedMonth, setSelectedMonth] = useState(currentMonth) // 0-based for UI
  const [filter, setFilter] = useState<'all' | DeadlineType>('all')

  // Filter deadlines for the selected month (month in data is 1-based)
  const monthDeadlines = useMemo(() => {
    const month1 = selectedMonth + 1
    return DEADLINES
      .filter((d) => d.month === month1)
      .filter((d) => filter === 'all' || d.type === filter)
      .sort((a, b) => a.day - b.day)
  }, [selectedMonth, filter])

  // Deduplicate deadlines by grouping same day + same titleKey
  const groupedDeadlines = useMemo(() => {
    const seen = new Set<string>()
    return monthDeadlines.filter((d) => {
      const key = `${d.day}-${d.titleKey}`
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })
  }, [monthDeadlines])

  function getDeadlineStatus(day: number, month0: number): 'today' | 'upcoming' | 'passed' {
    if (currentYear === 2026 && month0 === currentMonth && day === currentDay) return 'today'
    const deadlineDate = new Date(2026, month0, day)
    const todayDate = new Date(currentYear, currentMonth, currentDay)
    return deadlineDate >= todayDate ? 'upcoming' : 'passed'
  }

  const filters: { key: 'all' | DeadlineType; label: string }[] = [
    { key: 'all', label: t.filterAll },
    { key: 'vat', label: t.filterVat },
    { key: 'payroll', label: t.filterPayroll },
    { key: 'corporate', label: t.filterCorporate },
    { key: 'annual', label: t.filterAnnual },
  ]

  const faqLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: t.faq.map((item) => ({
      '@type': 'Question',
      name: item.q,
      acceptedAnswer: { '@type': 'Answer', text: item.a },
    })),
  }

  const webAppLd = {
    '@context': 'https://schema.org',
    '@type': 'WebApplication',
    name: t.h1,
    description: t.subtitle,
    url: `https://www.facturino.mk/${locale}/alati/danocen-kalendar`,
    applicationCategory: 'FinanceApplication',
    operatingSystem: 'All',
    offers: { '@type': 'Offer', price: '0', priceCurrency: 'EUR' },
    author: { '@type': 'Organization', name: 'Facturino', url: 'https://www.facturino.mk' },
  }

  const breadcrumbLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Facturino', item: `https://www.facturino.mk/${locale}` },
      { '@type': 'ListItem', position: 2, name: locale === 'mk' ? 'Алатки' : locale === 'sq' ? 'Mjete' : locale === 'tr' ? 'Araçlar' : 'Tools', item: `https://www.facturino.mk/${locale}/alati` },
      { '@type': 'ListItem', position: 3, name: t.h1, item: `https://www.facturino.mk/${locale}/alati/danocen-kalendar` },
    ],
  }

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(webAppLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />

      {/* Hero */}
      <section className="relative overflow-hidden pt-24 md:pt-32 pb-12 md:pb-16">
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-10 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite]" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite_2s]" />
        </div>
        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          <Link href={`/${locale}/alati`} className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors">
            {t.backLink}
          </Link>
          <span className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600 mb-4">
            {t.badge}
          </span>
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-4">
            {t.h1}
          </h1>
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">
            {t.subtitle}
          </p>
        </div>
      </section>

      {/* Month Selector */}
      <section className="pb-6">
        <div className="container max-w-4xl mx-auto px-4 sm:px-6">
          <div className="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-12 gap-2">
            {t.monthNames.map((name, i) => (
              <button
                key={i}
                onClick={() => setSelectedMonth(i)}
                className={`px-2 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all ${
                  selectedMonth === i
                    ? 'bg-indigo-600 text-white shadow-md'
                    : i === currentMonth
                    ? 'bg-indigo-50 text-indigo-700 border-2 border-indigo-300 hover:bg-indigo-100'
                    : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'
                }`}
              >
                {name.slice(0, 3)}
              </button>
            ))}
          </div>
        </div>
      </section>

      {/* Filter Buttons */}
      <section className="pb-6">
        <div className="container max-w-4xl mx-auto px-4 sm:px-6">
          <div className="flex flex-wrap gap-2">
            {filters.map((f) => (
              <button
                key={f.key}
                onClick={() => setFilter(f.key)}
                className={`px-4 py-2 rounded-full text-sm font-medium transition-all ${
                  filter === f.key
                    ? 'bg-indigo-600 text-white shadow-md'
                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'
                }`}
              >
                {f.key !== 'all' && (
                  <span className={`inline-block w-2.5 h-2.5 rounded-full mr-2 ${TYPE_COLORS[f.key as DeadlineType].dot}`} />
                )}
                {f.label}
              </button>
            ))}
          </div>
        </div>
      </section>

      {/* Legend */}
      <section className="pb-4">
        <div className="container max-w-4xl mx-auto px-4 sm:px-6">
          <div className="flex flex-wrap items-center gap-4 text-sm text-gray-500">
            <span className="font-medium text-gray-700">{t.legend}:</span>
            {(['vat', 'payroll', 'corporate', 'annual'] as DeadlineType[]).map((type) => (
              <span key={type} className="flex items-center gap-1.5">
                <span className={`inline-block w-3 h-3 rounded-full ${TYPE_COLORS[type].dot}`} />
                {t.typeBadge[type]}
              </span>
            ))}
          </div>
        </div>
      </section>

      {/* Deadline List */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-4xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl font-bold text-gray-900 mb-6">
            {t.monthNames[selectedMonth]} 2026
          </h2>

          {groupedDeadlines.length === 0 ? (
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
              {t.noDeadlines}
            </div>
          ) : (
            <div className="space-y-4">
              {groupedDeadlines.map((deadline, i) => {
                const status = getDeadlineStatus(deadline.day, selectedMonth)
                const colors = TYPE_COLORS[deadline.type]
                const titleText = t[deadline.titleKey as keyof typeof t] as string
                const descText = t[deadline.descKey as keyof typeof t] as string
                const fileText = t[deadline.fileKey as keyof typeof t] as string
                const penaltyText = t[deadline.penaltyKey as keyof typeof t] as string

                return (
                  <details
                    key={`${deadline.day}-${deadline.titleKey}-${i}`}
                    className={`group bg-white rounded-2xl shadow-sm border overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 ${
                      status === 'today'
                        ? 'border-indigo-300 ring-2 ring-indigo-200'
                        : status === 'upcoming'
                        ? 'border-gray-100'
                        : 'border-gray-100 opacity-75'
                    }`}
                  >
                    <summary className="flex items-center gap-4 p-5 cursor-pointer list-none">
                      {/* Date circle */}
                      <div className={`flex-shrink-0 w-14 h-14 rounded-xl flex flex-col items-center justify-center ${
                        status === 'today' ? 'bg-indigo-600 text-white' : 'bg-gray-50 text-gray-700'
                      }`}>
                        <span className="text-xl font-bold leading-none">{deadline.day}</span>
                        <span className="text-[10px] uppercase tracking-wider mt-0.5">
                          {t.monthNames[selectedMonth].slice(0, 3)}
                        </span>
                      </div>

                      {/* Content */}
                      <div className="flex-1 min-w-0">
                        <div className="flex flex-wrap items-center gap-2 mb-1">
                          <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${colors.bg} ${colors.text}`}>
                            {t.typeBadge[deadline.type]}
                          </span>
                          <span className="text-xs text-gray-400">
                            {t.recurring[deadline.recurring]}
                          </span>
                          {status === 'today' && (
                            <span className="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-700 animate-pulse">
                              {t.todayLabel}
                            </span>
                          )}
                          {status === 'upcoming' && selectedMonth === currentMonth && (
                            <span className="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
                              {t.upcomingLabel}
                            </span>
                          )}
                          {status === 'passed' && (
                            <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">
                              {t.passedLabel}
                            </span>
                          )}
                        </div>
                        <h3 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                          {titleText}
                        </h3>
                      </div>

                      {/* Expand icon */}
                      <span className="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      </span>
                    </summary>

                    <div className="px-5 pb-5 space-y-3 border-t border-gray-50">
                      <p className="text-gray-600 leading-relaxed text-sm pt-3">
                        {descText}
                      </p>
                      <div className="grid sm:grid-cols-2 gap-3">
                        <div className="rounded-lg bg-gray-50 p-3">
                          <p className="text-xs font-medium text-gray-500 mb-1">{t.whatToFile}</p>
                          <p className="text-sm font-semibold text-gray-800">{fileText}</p>
                        </div>
                        <div className="rounded-lg bg-red-50 p-3">
                          <p className="text-xs font-medium text-red-500 mb-1">{t.penalty}</p>
                          <p className="text-sm font-semibold text-red-700">{penaltyText}</p>
                        </div>
                      </div>
                    </div>
                  </details>
                )
              })}
            </div>
          )}

          {/* Inline CTA */}
          <div className="mt-6 flex items-center justify-between rounded-lg bg-indigo-600/5 border border-indigo-100 px-4 py-3">
            <p className="text-sm text-gray-700">{t.ctaInline}</p>
            <a
              href={`${APP_URL}/signup`}
              className="ml-3 flex-shrink-0 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors whitespace-nowrap"
            >
              {t.ctaButton} &rarr;
            </a>
          </div>
        </div>
      </section>

      {/* Educational Section */}
      <section className="py-12 md:py-16 bg-slate-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">{t.eduTitle}</h2>
          <div className="space-y-4">
            <details className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100" open>
              <summary className="flex items-center justify-between p-5 cursor-pointer list-none">
                <div className="flex items-center gap-3">
                  <span className="flex-shrink-0 w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                  </span>
                  <h3 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">{t.eduMissTitle}</h3>
                </div>
                <span className="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
                </span>
              </summary>
              <div className="px-5 pb-5 text-gray-600 leading-relaxed text-sm">{t.eduMissText}</div>
            </details>

            <details className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
              <summary className="flex items-center justify-between p-5 cursor-pointer list-none">
                <div className="flex items-center gap-3">
                  <span className="flex-shrink-0 w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                  </span>
                  <h3 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">{t.eduRemindTitle}</h3>
                </div>
                <span className="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
                </span>
              </summary>
              <div className="px-5 pb-5 text-gray-600 leading-relaxed text-sm">{t.eduRemindText}</div>
            </details>

            <details className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
              <summary className="flex items-center justify-between p-5 cursor-pointer list-none">
                <div className="flex items-center gap-3">
                  <span className="flex-shrink-0 w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                  </span>
                  <h3 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">{t.eduCommonTitle}</h3>
                </div>
                <span className="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
                </span>
              </summary>
              <div className="px-5 pb-5 text-gray-600 leading-relaxed text-sm">{t.eduCommonText}</div>
            </details>
          </div>
        </div>
      </section>

      {/* FAQ Section */}
      <section className="section bg-white">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">{t.faqTitle}</h2>
          <div className="space-y-4">
            {t.faq.map((item, i) => (
              <details key={i} className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
                <summary className="flex items-center justify-between p-6 cursor-pointer list-none">
                  <h3 className="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors pr-8">
                    {item.q}
                  </h3>
                  <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                    </svg>
                  </span>
                </summary>
                <div className="px-6 pb-6 text-gray-600 leading-relaxed">
                  {item.a}
                </div>
              </details>
            ))}
          </div>
        </div>
      </section>

      {/* Bottom CTA */}
      <section className="py-12 lg:py-24 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600" />
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl" />
        </div>
        <div className="container relative z-10 text-center text-white max-w-2xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-extrabold mb-4 tracking-tight">{t.ctaTitle}</h2>
          <p className="text-xl text-indigo-100 mb-8">{t.ctaSub}</p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href={`${APP_URL}/signup`} className="px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
              {t.ctaMainButton}
            </a>
            <Link href={`/${locale}/contact`} className="px-8 py-4 bg-indigo-700/50 text-white border border-white/20 rounded-xl font-bold text-lg hover:bg-indigo-700/70 transition-all duration-300 backdrop-blur-sm">
              {t.ctaSecondary}
            </Link>
          </div>
        </div>
      </section>
    </main>
  )
}
