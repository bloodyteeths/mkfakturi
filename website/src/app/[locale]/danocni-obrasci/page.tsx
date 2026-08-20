import { defaultLocale, isLocale, type Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

const BASE_URL = 'https://www.facturino.mk'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/danocni-obrasci', {
    title: {
      mk: 'Даночни и деловни обрасци во Македонија 2026 — комплетен список',
      en: 'Macedonian Tax & Business Forms 2026 — Complete List',
      sq: 'Formularët tatimorë dhe të biznesit në Maqedoni 2026',
      tr: 'Makedonya Vergi ve İş Formları 2026',
    },
    description: {
      mk: 'Референтен список на сите даночни и деловни обрасци во Северна Македонија — ДДВ-04, МПИН, ДБ, ПДД-ГДП, годишна сметка, упис во ЦРСМ. Кој ги поднесува, рокови и каде се поднесуваат (УЈП, ЦРСМ, Фонд ПИОМ).',
      en: 'Reference directory of all North Macedonia tax and business forms — DDV-04, MPIN, DB, PDD-GDP, annual accounts, CRSM registration. Who files them, deadlines and where they are submitted (UJP, CRSM, PIOM Fund).',
      sq: 'Direktori referues i të gjitha formularëve tatimorë dhe të biznesit në Maqedoninë e Veriut — DDV-04, MPIN, DB, PDD-GDP, llogaria vjetore, regjistrimi në QRMK. Kush i dorëzon, afatet dhe ku dorëzohen (UJP, QRMK, Fondi PIOM).',
      tr: 'Kuzey Makedonya tüm vergi ve iş formlarının referans dizini — DDV-04, MPIN, DB, PDD-GDP, yıllık hesaplar, CRSM kaydı. Kim doldurur, son tarihler ve nereye verilir (UJP, CRSM, PIOM Fonu).',
    },
  })
}

const copy = {
  mk: {
    hero: {
      badge: 'Референца',
      title: 'Даночни и деловни обрасци во Македонија',
      sub: 'Комплетен референтен список на официјалните даночни и деловни обрасци во Северна Македонија — што е секој образец, кој го поднесува, до кога и каде. Идеална почетна точка за прашањето „кој образец за …“.',
    },
    columns: {
      code: 'Код',
      name: 'Назив',
      purpose: 'Намена',
      who: 'Кој поднесува',
      deadline: 'Рок',
      to: 'Се поднесува до',
    },
    categories: [
      {
        heading: 'ДДВ (данок на додадена вредност)',
        forms: [
          {
            code: 'ДДВ-04',
            name: 'ДДВ пријава',
            purpose: 'Периодична пресметка на пресметан и одбитен ДДВ.',
            who: 'ДДВ обврзници',
            deadline: 'До 25-ти во наредниот месец',
            to: 'УЈП (е-Даноци)',
          },
          {
            code: 'ДДВ-01',
            name: 'Пријава за регистрација за ДДВ',
            purpose: 'Пријава за влез во системот на ДДВ.',
            who: 'Обврзник со промет над 2.000.000 МКД',
            deadline: 'До 15 јануари (или при надминување на прагот)',
            to: 'УЈП',
          },
        ],
      },
      {
        heading: 'Плати и придонеси',
        forms: [
          {
            code: 'МПИН',
            name: 'Месечна пресметка за интегрирана наплата',
            purpose: 'Пресметка за плата, персонален данок и социјални придонеси.',
            who: 'Работодавачи',
            deadline: 'Пред исплата на плата',
            to: 'УЈП',
          },
          {
            code: 'ПП30 / ПП50',
            name: 'Налози за плаќање јавни приходи',
            purpose: 'Платни налози за уплата на данок и придонеси на соодветните сметки.',
            who: 'Работодавачи и обврзници',
            deadline: 'Со исплатата на обврската',
            to: 'Носител на платен промет (банка)',
          },
          {
            code: 'М1 / М2',
            name: 'Пријава / одјава во социјално осигурување',
            purpose: 'Пријавување и одјавување на работник во задолжително социјално осигурување.',
            who: 'Работодавачи',
            deadline: 'Пред започнување / по престанок на работниот однос',
            to: 'Фонд ПИОМ / АВРСМ',
          },
        ],
      },
      {
        heading: 'Данок на добивка и годишни извештаи',
        forms: [
          {
            code: 'ДБ',
            name: 'Даночен биланс / годишна пријава за данок на добивка',
            purpose: 'Годишна пресметка на данокот на добивка за фискалната година.',
            who: 'Правни лица',
            deadline: 'До крајот на февруари',
            to: 'УЈП',
          },
          {
            code: 'Годишна сметка',
            name: 'Биланс на состојба и биланс на успех',
            purpose: 'Завршна годишна финансиска сметка на друштвото.',
            who: 'Правни лица и трговци поединци',
            deadline: 'До крајот на февруари',
            to: 'ЦРСМ',
          },
        ],
      },
      {
        heading: 'Персонален данок на доход',
        forms: [
          {
            code: 'ПДД-ГДП',
            name: 'Годишна даночна пријава за персонален данок',
            purpose: 'Годишно пријавување на сите приходи на физичко лице.',
            who: 'Физички лица и самостојни дејности',
            deadline: 'До крајот на март',
            to: 'УЈП',
          },
          {
            code: 'Е-ПДД',
            name: 'Електронска годишна пресметка',
            purpose: 'Пополнета е-пресметка од УЈП што обврзникот ја потврдува или коригира.',
            who: 'Физички лица',
            deadline: 'По потврда од УЈП (до крајот на април)',
            to: 'УЈП (е-Персонален данок)',
          },
        ],
      },
      {
        heading: 'Регистрација на фирма',
        forms: [
          {
            code: 'Пријава за упис',
            name: 'Пријава за упис во трговскиот регистар',
            purpose: 'Основање на ДООЕЛ/ДОО и упис на друштвото.',
            who: 'Основачи на друштво',
            deadline: 'При основање на друштвото',
            to: 'Централен регистар (ЦРСМ)',
          },
          {
            code: 'ЗП образец',
            name: 'Заверени потписи на застапник',
            purpose: 'Депонирани и заверени потписи на лицата овластени за застапување.',
            who: 'Управители / застапници',
            deadline: 'При упис или промена на застапник',
            to: 'ЦРСМ / нотар',
          },
        ],
      },
    ],
    ctaTitle: 'Автоматски пополнети обрасци со Facturino',
    ctaSub: 'Facturino генерира ДДВ, МПИН и годишни пресметки автоматски и ве потсетува пред секој рок.',
    ctaBtn: 'Пробај бесплатно →',
    home: 'Почетна',
    label: 'Даночни обрасци',
    faq: [
      {
        question: 'Кој образец се користи за ДДВ пријава?',
        answer:
          'ДДВ пријавата се поднесува на образецот ДДВ-04 преку системот е-Даноци на УЈП, најчесто до 25-ти во наредниот месец по даночниот период.',
      },
      {
        question: 'Кој образец за плата и придонеси?',
        answer:
          'За плата, персонален данок и социјални придонеси се користи МПИН пресметката, која работодавачот ја поднесува до УЈП пред исплата на платата.',
      },
      {
        question: 'Каде се поднесува годишната сметка?',
        answer:
          'Годишната сметка (биланс на состојба и биланс на успех) се поднесува до Централниот регистар на РСМ (ЦРСМ), по правило до крајот на февруари.',
      },
      {
        question: 'Кој образец за годишна пријава за персонален данок?',
        answer:
          'Годишната даночна пријава за персонален данок се поднесува на образецот ПДД-ГДП до УЈП, обично до крајот на март за претходната година.',
      },
    ],
  },
  en: {
    hero: {
      badge: 'Reference',
      title: 'Tax & Business Forms in Macedonia',
      sub: 'A complete reference directory of the official tax and business forms in North Macedonia — what each form is, who files it, by when and where. The place to start for "which form for …".',
    },
    columns: {
      code: 'Code',
      name: 'Name',
      purpose: 'Purpose',
      who: 'Who files',
      deadline: 'Deadline',
      to: 'Submitted to',
    },
    categories: [
      {
        heading: 'VAT (value-added tax)',
        forms: [
          {
            code: 'DDV-04',
            name: 'VAT return',
            purpose: 'Periodic calculation of output and input VAT.',
            who: 'VAT-registered taxpayers',
            deadline: 'By the 25th of the following month',
            to: 'UJP (e-Danoci)',
          },
          {
            code: 'DDV-01',
            name: 'VAT registration application',
            purpose: 'Application to enter the VAT system.',
            who: 'Taxpayers with turnover over MKD 2,000,000',
            deadline: 'By 15 January (or on exceeding the threshold)',
            to: 'UJP',
          },
        ],
      },
      {
        heading: 'Salaries and contributions',
        forms: [
          {
            code: 'MPIN',
            name: 'Monthly integrated collection calculation',
            purpose: 'Calculation of salary, personal income tax and social contributions.',
            who: 'Employers',
            deadline: 'Before salary payout',
            to: 'UJP',
          },
          {
            code: 'PP30 / PP50',
            name: 'Public-revenue payment orders',
            purpose: 'Payment orders for tax and contributions to the relevant accounts.',
            who: 'Employers and taxpayers',
            deadline: 'On settlement of the obligation',
            to: 'Payment provider (bank)',
          },
          {
            code: 'M1 / M2',
            name: 'Social-insurance registration / deregistration',
            purpose: 'Registering and deregistering an employee in mandatory social insurance.',
            who: 'Employers',
            deadline: 'Before start / after end of employment',
            to: 'PIOM Fund / ESARM',
          },
        ],
      },
      {
        heading: 'Corporate income tax and annual reports',
        forms: [
          {
            code: 'DB',
            name: 'Tax balance / annual corporate income tax return',
            purpose: 'Annual calculation of corporate income tax for the fiscal year.',
            who: 'Legal entities',
            deadline: 'By the end of February',
            to: 'UJP',
          },
          {
            code: 'Annual accounts',
            name: 'Balance sheet and income statement',
            purpose: 'Final annual financial accounts of the company.',
            who: 'Legal entities and sole traders',
            deadline: 'By the end of February',
            to: 'CRSM',
          },
        ],
      },
      {
        heading: 'Personal income tax',
        forms: [
          {
            code: 'PDD-GDP',
            name: 'Annual personal income tax return',
            purpose: 'Annual reporting of all income of an individual.',
            who: 'Individuals and self-employed',
            deadline: 'By the end of March',
            to: 'UJP',
          },
          {
            code: 'E-PDD',
            name: 'Electronic annual calculation',
            purpose: 'Pre-filled e-calculation from UJP that the taxpayer confirms or corrects.',
            who: 'Individuals',
            deadline: 'After UJP confirmation (by the end of April)',
            to: 'UJP (e-Personal tax)',
          },
        ],
      },
      {
        heading: 'Company registration',
        forms: [
          {
            code: 'Registration application',
            name: 'Application for entry in the trade register',
            purpose: 'Incorporation of a DOOEL/DOO and registration of the company.',
            who: 'Company founders',
            deadline: 'On incorporating the company',
            to: 'Central Registry (CRSM)',
          },
          {
            code: 'ZP form',
            name: 'Certified signatures of the representative',
            purpose: 'Deposited and certified signatures of persons authorised to represent.',
            who: 'Managers / representatives',
            deadline: 'On registration or change of representative',
            to: 'CRSM / notary',
          },
        ],
      },
    ],
    ctaTitle: 'Auto-filled forms with Facturino',
    ctaSub: 'Facturino generates VAT, MPIN and annual calculations automatically and reminds you before every deadline.',
    ctaBtn: 'Try it free →',
    home: 'Home',
    label: 'Tax forms',
    faq: [
      {
        question: 'Кој образец се користи за ДДВ пријава?',
        answer:
          'ДДВ пријавата се поднесува на образецот ДДВ-04 преку системот е-Даноци на УЈП, најчесто до 25-ти во наредниот месец по даночниот период.',
      },
      {
        question: 'Кој образец за плата и придонеси?',
        answer:
          'За плата, персонален данок и социјални придонеси се користи МПИН пресметката, која работодавачот ја поднесува до УЈП пред исплата на платата.',
      },
      {
        question: 'Каде се поднесува годишната сметка?',
        answer:
          'Годишната сметка (биланс на состојба и биланс на успех) се поднесува до Централниот регистар на РСМ (ЦРСМ), по правило до крајот на февруари.',
      },
      {
        question: 'Кој образец за годишна пријава за персонален данок?',
        answer:
          'Годишната даночна пријава за персонален данок се поднесува на образецот ПДД-ГДП до УЈП, обично до крајот на март за претходната година.',
      },
    ],
  },
  sq: {
    hero: {
      badge: 'Referencë',
      title: 'Formularët tatimorë dhe të biznesit në Maqedoni',
      sub: 'Një direktori e plotë referimi e formularëve zyrtarë tatimorë dhe të biznesit në Maqedoninë e Veriut — çfarë është secili formular, kush e dorëzon, deri kur dhe ku. Pika e nisjes për pyetjen “cili formular për …”.',
    },
    columns: {
      code: 'Kodi',
      name: 'Emri',
      purpose: 'Qëllimi',
      who: 'Kush dorëzon',
      deadline: 'Afati',
      to: 'Dorëzohet te',
    },
    categories: [
      {
        heading: 'TVSH (tatimi mbi vlerën e shtuar)',
        forms: [
          {
            code: 'DDV-04',
            name: 'Deklarata e TVSH-së',
            purpose: 'Llogaritja periodike e TVSH-së së llogaritur dhe të zbritur.',
            who: 'Tatimpagues të TVSH-së',
            deadline: 'Deri më 25 të muajit pasues',
            to: 'UJP (e-Danoci)',
          },
          {
            code: 'DDV-01',
            name: 'Kërkesë për regjistrim për TVSH',
            purpose: 'Kërkesë për hyrje në sistemin e TVSH-së.',
            who: 'Tatimpagues me qarkullim mbi 2.000.000 MKD',
            deadline: 'Deri më 15 janar (ose kur tejkalohet pragu)',
            to: 'UJP',
          },
        ],
      },
      {
        heading: 'Pagat dhe kontributet',
        forms: [
          {
            code: 'MPIN',
            name: 'Llogaritja mujore e arkëtimit të integruar',
            purpose: 'Llogaritja e pagës, tatimit personal dhe kontributeve sociale.',
            who: 'Punëdhënësit',
            deadline: 'Para pagesës së pagës',
            to: 'UJP',
          },
          {
            code: 'PP30 / PP50',
            name: 'Urdhra pagese për të ardhurat publike',
            purpose: 'Urdhra pagese për tatimin dhe kontributet në llogaritë përkatëse.',
            who: 'Punëdhënës dhe tatimpagues',
            deadline: 'Me shlyerjen e detyrimit',
            to: 'Ofruesi i pagesave (banka)',
          },
          {
            code: 'M1 / M2',
            name: 'Regjistrim / çregjistrim në sigurimin social',
            purpose: 'Regjistrimi dhe çregjistrimi i punonjësit në sigurimin social të detyrueshëm.',
            who: 'Punëdhënësit',
            deadline: 'Para fillimit / pas përfundimit të punësimit',
            to: 'Fondi PIOM / AVRSM',
          },
        ],
      },
      {
        heading: 'Tatimi mbi fitimin dhe raportet vjetore',
        forms: [
          {
            code: 'DB',
            name: 'Bilanci tatimor / deklarata vjetore e tatimit mbi fitimin',
            purpose: 'Llogaritja vjetore e tatimit mbi fitimin për vitin fiskal.',
            who: 'Subjekte juridike',
            deadline: 'Deri në fund të shkurtit',
            to: 'UJP',
          },
          {
            code: 'Llogaria vjetore',
            name: 'Bilanci i gjendjes dhe bilanci i suksesit',
            purpose: 'Llogaria financiare vjetore përfundimtare e shoqërisë.',
            who: 'Subjekte juridike dhe tregtarë individualë',
            deadline: 'Deri në fund të shkurtit',
            to: 'QRMK',
          },
        ],
      },
      {
        heading: 'Tatimi mbi të ardhurat personale',
        forms: [
          {
            code: 'PDD-GDP',
            name: 'Deklarata vjetore e tatimit mbi të ardhurat personale',
            purpose: 'Raportimi vjetor i të gjitha të ardhurave të një personi fizik.',
            who: 'Persona fizikë dhe të vetëpunësuar',
            deadline: 'Deri në fund të marsit',
            to: 'UJP',
          },
          {
            code: 'E-PDD',
            name: 'Llogaritja elektronike vjetore',
            purpose: 'E-llogaritje e parapërgatitur nga UJP që tatimpaguesi e konfirmon ose korrigjon.',
            who: 'Persona fizikë',
            deadline: 'Pas konfirmimit nga UJP (deri në fund të prillit)',
            to: 'UJP (e-Tatimi personal)',
          },
        ],
      },
      {
        heading: 'Regjistrimi i firmës',
        forms: [
          {
            code: 'Kërkesë për regjistrim',
            name: 'Kërkesë për regjistrim në regjistrin tregtar',
            purpose: 'Themelimi i një DOOEL/DOO dhe regjistrimi i shoqërisë.',
            who: 'Themeluesit e shoqërisë',
            deadline: 'Gjatë themelimit të shoqërisë',
            to: 'Regjistri Qendror (QRMK)',
          },
          {
            code: 'Formulari ZP',
            name: 'Nënshkrime të vërtetuara të përfaqësuesit',
            purpose: 'Nënshkrime të depozituara dhe të vërtetuara të personave të autorizuar për përfaqësim.',
            who: 'Menaxherë / përfaqësues',
            deadline: 'Gjatë regjistrimit ose ndryshimit të përfaqësuesit',
            to: 'QRMK / noter',
          },
        ],
      },
    ],
    ctaTitle: 'Formularë të mbushur automatikisht me Facturino',
    ctaSub: 'Facturino gjeneron automatikisht llogaritjet e TVSH-së, MPIN dhe ato vjetore dhe ju kujton para çdo afati.',
    ctaBtn: 'Provoni falas →',
    home: 'Ballina',
    label: 'Formularët tatimorë',
    faq: [
      {
        question: 'Кој образец се користи за ДДВ пријава?',
        answer:
          'ДДВ пријавата се поднесува на образецот ДДВ-04 преку системот е-Даноци на УЈП, најчесто до 25-ти во наредниот месец по даночниот период.',
      },
      {
        question: 'Кој образец за плата и придонеси?',
        answer:
          'За плата, персонален данок и социјални придонеси се користи МПИН пресметката, која работодавачот ја поднесува до УЈП пред исплата на платата.',
      },
      {
        question: 'Каде се поднесува годишната сметка?',
        answer:
          'Годишната сметка (биланс на состојба и биланс на успех) се поднесува до Централниот регистар на РСМ (ЦРСМ), по правило до крајот на февруари.',
      },
      {
        question: 'Кој образец за годишна пријава за персонален данок?',
        answer:
          'Годишната даночна пријава за персонален данок се поднесува на образецот ПДД-ГДП до УЈП, обично до крајот на март за претходната година.',
      },
    ],
  },
  tr: {
    hero: {
      badge: 'Referans',
      title: 'Makedonya\'da Vergi ve İş Formları',
      sub: 'Kuzey Makedonya\'daki resmi vergi ve iş formlarının eksiksiz bir referans dizini — her formun ne olduğu, kimin doldurduğu, ne zamana kadar ve nereye. "Hangi form için …" sorusunun başlangıç noktası.',
    },
    columns: {
      code: 'Kod',
      name: 'Adı',
      purpose: 'Amaç',
      who: 'Kim doldurur',
      deadline: 'Son tarih',
      to: 'Nereye verilir',
    },
    categories: [
      {
        heading: 'KDV (katma değer vergisi)',
        forms: [
          {
            code: 'DDV-04',
            name: 'KDV beyannamesi',
            purpose: 'Hesaplanan ve indirilen KDV\'nin dönemsel hesaplaması.',
            who: 'KDV mükellefleri',
            deadline: 'Ertesi ayın 25\'ine kadar',
            to: 'UJP (e-Danoci)',
          },
          {
            code: 'DDV-01',
            name: 'KDV kayıt başvurusu',
            purpose: 'KDV sistemine giriş başvurusu.',
            who: 'Cirosu 2.000.000 MKD üzeri mükellefler',
            deadline: '15 Ocak\'a kadar (veya eşik aşıldığında)',
            to: 'UJP',
          },
        ],
      },
      {
        heading: 'Maaşlar ve katkılar',
        forms: [
          {
            code: 'MPIN',
            name: 'Aylık entegre tahsilat hesabı',
            purpose: 'Maaş, kişisel gelir vergisi ve sosyal katkıların hesaplanması.',
            who: 'İşverenler',
            deadline: 'Maaş ödemesinden önce',
            to: 'UJP',
          },
          {
            code: 'PP30 / PP50',
            name: 'Kamu geliri ödeme emirleri',
            purpose: 'Vergi ve katkıların ilgili hesaplara ödeme emirleri.',
            who: 'İşverenler ve mükellefler',
            deadline: 'Yükümlülüğün ödenmesiyle',
            to: 'Ödeme sağlayıcı (banka)',
          },
          {
            code: 'M1 / M2',
            name: 'Sosyal sigorta kaydı / kayıt silme',
            purpose: 'Bir çalışanın zorunlu sosyal sigortaya kaydı ve kaydının silinmesi.',
            who: 'İşverenler',
            deadline: 'İşe başlamadan önce / işten çıktıktan sonra',
            to: 'PIOM Fonu / AVRSM',
          },
        ],
      },
      {
        heading: 'Kurumlar vergisi ve yıllık raporlar',
        forms: [
          {
            code: 'DB',
            name: 'Vergi bilançosu / yıllık kurumlar vergisi beyannamesi',
            purpose: 'Mali yıl için kurumlar vergisinin yıllık hesaplanması.',
            who: 'Tüzel kişiler',
            deadline: 'Şubat sonuna kadar',
            to: 'UJP',
          },
          {
            code: 'Yıllık hesaplar',
            name: 'Bilanço ve gelir tablosu',
            purpose: 'Şirketin nihai yıllık mali hesapları.',
            who: 'Tüzel kişiler ve şahıs tacirler',
            deadline: 'Şubat sonuna kadar',
            to: 'CRSM',
          },
        ],
      },
      {
        heading: 'Kişisel gelir vergisi',
        forms: [
          {
            code: 'PDD-GDP',
            name: 'Yıllık kişisel gelir vergisi beyannamesi',
            purpose: 'Bir bireyin tüm gelirlerinin yıllık bildirimi.',
            who: 'Bireyler ve serbest meslek sahipleri',
            deadline: 'Mart sonuna kadar',
            to: 'UJP',
          },
          {
            code: 'E-PDD',
            name: 'Elektronik yıllık hesaplama',
            purpose: 'UJP tarafından önceden doldurulan ve mükellefin onayladığı veya düzelttiği e-hesaplama.',
            who: 'Bireyler',
            deadline: 'UJP onayından sonra (Nisan sonuna kadar)',
            to: 'UJP (e-Kişisel vergi)',
          },
        ],
      },
      {
        heading: 'Şirket kaydı',
        forms: [
          {
            code: 'Kayıt başvurusu',
            name: 'Ticaret siciline kayıt başvurusu',
            purpose: 'Bir DOOEL/DOO kuruluşu ve şirketin tescili.',
            who: 'Şirket kurucuları',
            deadline: 'Şirketin kuruluşunda',
            to: 'Merkez Sicil (CRSM)',
          },
          {
            code: 'ZP formu',
            name: 'Temsilcinin onaylı imzaları',
            purpose: 'Temsile yetkili kişilerin depolanmış ve onaylı imzaları.',
            who: 'Müdürler / temsilciler',
            deadline: 'Kayıt veya temsilci değişikliğinde',
            to: 'CRSM / noter',
          },
        ],
      },
    ],
    ctaTitle: 'Facturino ile otomatik doldurulan formlar',
    ctaSub: 'Facturino KDV, MPIN ve yıllık hesaplamaları otomatik oluşturur ve her son tarihten önce sizi hatırlatır.',
    ctaBtn: 'Ücretsiz deneyin →',
    home: 'Ana Sayfa',
    label: 'Vergi formları',
    faq: [
      {
        question: 'Кој образец се користи за ДДВ пријава?',
        answer:
          'ДДВ пријавата се поднесува на образецот ДДВ-04 преку системот е-Даноци на УЈП, најчесто до 25-ти во наредниот месец по даночниот период.',
      },
      {
        question: 'Кој образец за плата и придонеси?',
        answer:
          'За плата, персонален данок и социјални придонеси се користи МПИН пресметката, која работодавачот ја поднесува до УЈП пред исплата на платата.',
      },
      {
        question: 'Каде се поднесува годишната сметка?',
        answer:
          'Годишната сметка (биланс на состојба и биланс на успех) се поднесува до Централниот регистар на РСМ (ЦРСМ), по правило до крајот на февруари.',
      },
      {
        question: 'Кој образец за годишна пријава за персонален данок?',
        answer:
          'Годишната даночна пријава за персонален данок се поднесува на образецот ПДД-ГДП до УЈП, обично до крајот на март за претходната година.',
      },
    ],
  },
} as const

export default async function DanocniObrasciPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const breadcrumbLd = breadcrumbJsonLd([
    { name: t.home, href: `/${locale}` },
    { name: t.label, href: `/${locale}/danocni-obrasci` },
  ])
  const collectionLd = {
    '@context': 'https://schema.org',
    '@type': 'CollectionPage',
    name: t.hero.title,
    url: `${BASE_URL}/${locale}/danocni-obrasci`,
    inLanguage: locale,
    isPartOf: { '@type': 'WebSite', name: 'Facturino', url: BASE_URL },
    about: 'Macedonian tax and business forms (UJP, CRSM)',
  }
  const faqLd = faqJsonLd(t.faq.map((f) => ({ question: f.question, answer: f.answer })))

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(collectionLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqLd) }} />

      {/* HERO */}
      <section className="bg-gradient-to-b from-indigo-50 to-white">
        <div className="container max-w-5xl mx-auto px-4 sm:px-6 py-12 md:py-16">
          <span className="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-4">{t.hero.badge}</span>
          <h1 className="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">{t.hero.title}</h1>
          <p className="text-lg text-gray-600 max-w-3xl">{t.hero.sub}</p>
        </div>
      </section>

      {/* CATEGORY TABLES */}
      <section className="container max-w-5xl mx-auto px-4 sm:px-6 py-4">
        <div className="space-y-12">
          {t.categories.map((category, i) => (
            <div key={i}>
              <h2 className="text-xl md:text-2xl font-bold text-gray-900 mb-4">{category.heading}</h2>
              <div className="overflow-x-auto rounded-xl border border-gray-100 shadow-sm">
                <table className="min-w-full divide-y divide-gray-100 text-sm">
                  <thead className="bg-gray-50">
                    <tr>
                      <th scope="col" className="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">{t.columns.code}</th>
                      <th scope="col" className="px-4 py-3 text-left font-semibold text-gray-700">{t.columns.name}</th>
                      <th scope="col" className="px-4 py-3 text-left font-semibold text-gray-700">{t.columns.purpose}</th>
                      <th scope="col" className="px-4 py-3 text-left font-semibold text-gray-700">{t.columns.who}</th>
                      <th scope="col" className="px-4 py-3 text-left font-semibold text-gray-700">{t.columns.deadline}</th>
                      <th scope="col" className="px-4 py-3 text-left font-semibold text-gray-700">{t.columns.to}</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100 bg-white">
                    {category.forms.map((form, j) => (
                      <tr key={j} className="hover:bg-indigo-50/40 transition-colors">
                        <td className="px-4 py-3 align-top font-semibold text-indigo-700 whitespace-nowrap">{form.code}</td>
                        <td className="px-4 py-3 align-top font-medium text-gray-900">{form.name}</td>
                        <td className="px-4 py-3 align-top text-gray-600">{form.purpose}</td>
                        <td className="px-4 py-3 align-top text-gray-600">{form.who}</td>
                        <td className="px-4 py-3 align-top text-gray-600">{form.deadline}</td>
                        <td className="px-4 py-3 align-top text-gray-600 whitespace-nowrap">{form.to}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* BOTTOM CTA */}
      <section className="bg-gradient-to-br from-indigo-600 to-cyan-500 mt-12">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6 py-12 text-center text-white">
          <h2 className="text-2xl md:text-3xl font-extrabold mb-3">{t.ctaTitle}</h2>
          <p className="text-indigo-100 mb-6 text-lg">{t.ctaSub}</p>
          <a href="https://app.facturino.mk/signup" className="inline-block bg-white text-indigo-700 font-bold px-8 py-3.5 rounded-xl hover:bg-indigo-50 transition-colors text-lg shadow-lg">{t.ctaBtn}</a>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
