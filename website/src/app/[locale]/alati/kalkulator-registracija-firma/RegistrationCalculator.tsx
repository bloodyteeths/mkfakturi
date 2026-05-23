'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import { type Locale } from '@/i18n/locales'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

type CompanyType = 'dooel' | 'doo' | 'pausalec'

interface CostBreakdown {
  crsmFee: number
  notary: { min: number; max: number }
  seal: { min: number; max: number }
  bankAccount: { min: number; max: number }
  accountant: { min: number; max: number }
  totalMin: number
  totalMax: number
}

function calculateCosts(type: CompanyType, founders: number): CostBreakdown {
  let crsmFee: number
  let notary: { min: number; max: number }
  let seal: { min: number; max: number }
  let bankAccount: { min: number; max: number }
  let accountant: { min: number; max: number }

  switch (type) {
    case 'dooel':
      crsmFee = 2720
      notary = { min: 2000, max: 3000 }
      seal = { min: 500, max: 1000 }
      bankAccount = { min: 0, max: 300 }
      accountant = { min: 3000, max: 5000 }
      break
    case 'doo':
      crsmFee = 3400
      notary = { min: 3000, max: 4000 + (founders > 2 ? (founders - 2) * 500 : 0) }
      seal = { min: 500, max: 1500 }
      bankAccount = { min: 0, max: 500 }
      accountant = { min: 5000, max: 8000 }
      break
    case 'pausalec':
      crsmFee = 1360
      notary = { min: 2000, max: 2500 }
      seal = { min: 500, max: 800 }
      bankAccount = { min: 0, max: 300 }
      accountant = { min: 3000, max: 4000 }
      break
  }

  const totalMin = crsmFee + notary.min + seal.min + bankAccount.min + accountant.min
  const totalMax = crsmFee + notary.max + seal.max + bankAccount.max + accountant.max

  return { crsmFee, notary, seal, bankAccount, accountant, totalMin, totalMax }
}

function fmt(n: number): string {
  return Math.round(n).toLocaleString('mk-MK')
}

function fmtRange(range: { min: number; max: number }, currency: string): string {
  if (range.min === range.max) return `${fmt(range.min)} ${currency}`
  if (range.min === 0) return `0 - ${fmt(range.max)} ${currency}`
  return `${fmt(range.min)} - ${fmt(range.max)} ${currency}`
}

const copy = {
  mk: {
    backLink: '← Сите алатки',
    badge: 'Бесплатно',
    h1: 'Калкулатор за регистрација на фирма',
    subtitle: 'Пресметајте ги сите трошоци за отворање фирма во Македонија — ДООЕЛ, ДОО или Паушалец. Моментално, без регистрација.',
    companyTypeLabel: 'Тип на фирма',
    types: {
      dooel: 'ДООЕЛ',
      doo: 'ДОО',
      pausalec: 'Паушалец',
    },
    typeDescriptions: {
      dooel: 'Друштво со ограничена одговорност — еден основач',
      doo: 'Друштво со ограничена одговорност — повеќе основачи',
      pausalec: 'Паушално оданочување — поедноставен режим',
    },
    foundersLabel: 'Број на основачи',
    foundersHelp: 'За ДОО: минимум 2, максимум 50 основачи',
    capitalLabel: 'Основачки капитал',
    capitalHelp: 'Минимум 5.000 МКД за ДООЕЛ и ДОО',
    currency: 'МКД',
    resultsTitle: 'Пресметка на трошоци',
    crsmFee: 'Такса за ЦРСМ (Централен регистар)',
    notaryCost: 'Нотарски трошоци',
    sealCost: 'Печат на фирма',
    bankAccount: 'Отворање банкарска сметка',
    accountantFirst: 'Прв месец сметководство (проценка)',
    totalEstimate: 'Вкупно проценка',
    capitalNote: 'Основачкиот капитал не е трошок — тоа се средства на фирмата.',
    timelineTitle: 'Колку трае регистрацијата?',
    timelineItems: {
      dooel: { days: '1-2 работни дена', desc: 'ДООЕЛ се регистрира најбрзо — потребен е само еден основач и едноставна документација.' },
      doo: { days: '2-3 работни дена', desc: 'ДОО бара договор меѓу основачите и нотарска заверка, што одзема дополнително време.' },
      pausalec: { days: '1-2 работни дена', desc: 'Паушалец има поедноставена процедура слична на ДООЕЛ.' },
    },
    requirementsTitle: 'Што ви треба пред регистрација?',
    requirements: [
      'Важечка лична карта или пасош',
      'Адреса на седиште на фирмата (може и домашна адреса)',
      'Избрана дејност (НКД шифра на дејност)',
      'Уникатно име на фирмата (проверете на ЦРСМ)',
      'Основачки капитал (мин. 5.000 МКД за ДООЕЛ/ДОО)',
    ],
    stepsTitle: 'Први чекори по регистрација',
    steps: [
      { title: 'Регистрација во УЈП', desc: 'Пријавување кај Управа за јавни приходи за даночен број и ДДВ (ако е применливо).' },
      { title: 'МПИН регистрација', desc: 'Регистрација во системот за придонеси (МПИН) — задолжително за вработени.' },
      { title: 'Отворање банкарска сметка', desc: 'Денарска и/или девизна сметка во избрана банка.' },
      { title: 'Печат и фактурирање', desc: 'Набавка на печат и поставување систем за издавање фактури.' },
    ],
    ctaInline: 'Facturino ви помага да започнете со фактурирање веднаш по регистрацијата.',
    ctaButton: 'Пробај бесплатно',
    faqTitle: 'Најчесто поставувани прашања',
    faq: [
      {
        q: 'Колку чини регистрација на фирма во Македонија?',
        a: 'Вкупните трошоци за регистрација на фирма во Македонија се движат од 7.000 до 15.000 МКД, во зависност од типот на фирмата. За ДООЕЛ трошоците се пониски (ЦРСМ такса 2.720 МКД + нотар + печат), додека за ДОО трошоците се повисоки поради посложена документација. Основачкиот капитал (мин. 5.000 МКД) не е трошок — тоа се средства на фирмата.',
      },
      {
        q: 'Колку трае регистрацијата на фирма?',
        a: 'Регистрацијата на ДООЕЛ трае 1-2 работни дена, а на ДОО 2-3 работни дена. Централниот регистар (ЦРСМ) ја обработува пријавата во рок од 4 часа по поднесувањето, доколку документацијата е комплетна. Нотарската заверка може да се заврши истиот ден.',
      },
      {
        q: 'Дали ми треба адвокат за регистрација на фирма?',
        a: 'Не е задолжително, но е препорачливо. За ДООЕЛ процедурата е едноставна и можете сами да ја завршите преку едношалтерскиот систем на ЦРСМ. За ДОО со повеќе основачи, адвокат може да помогне со изготвување на договорот за основање. Нотарска заверка е задолжителна за сите типови.',
      },
      {
        q: 'Кој е минималниот основачки капитал?',
        a: 'Минималниот основачки капитал за ДООЕЛ и ДОО е 5.000 МКД (приближно 80 евра). За Паушалец не е потребен основачки капитал. Капиталот може да биде во парични средства (депонирани на банкарска сметка) или во непарични влогови (опрема, возила итн.).',
      },
    ],
    ctaTitle: 'Започнете со фактурирање веднаш',
    ctaSub: 'Facturino е подготвен за нови фирми — регистрирајте се и издадете ја првата фактура за 5 минути.',
    ctaMainButton: 'Започни бесплатно — 14 дена',
    ctaSecondary: 'Закажи демо',
  },
  sq: {
    backLink: '← Te gjitha mjetet',
    badge: 'Falas',
    h1: 'Llogaritesi per regjistrimin e firmes',
    subtitle: 'Llogaritni te gjitha kostot per hapjen e firmes ne Maqedoni — SHPKNJP, SHPK ose Paushalist. Menjehere, pa regjistrim.',
    companyTypeLabel: 'Lloji i firmes',
    types: {
      dooel: 'SHPKNJP',
      doo: 'SHPK',
      pausalec: 'Paushalist',
    },
    typeDescriptions: {
      dooel: 'Shoqeri me pergjegjesi te kufizuar — nje themelues',
      doo: 'Shoqeri me pergjegjesi te kufizuar — shume themelues',
      pausalec: 'Tatim i pergjithshem — regjim i thjeshtuar',
    },
    foundersLabel: 'Numri i themeluesve',
    foundersHelp: 'Per SHPK: minimumi 2, maksimumi 50 themelues',
    capitalLabel: 'Kapitali themeltar',
    capitalHelp: 'Minimumi 5.000 MKD per SHPKNJP dhe SHPK',
    currency: 'MKD',
    resultsTitle: 'Llogaritja e kostove',
    crsmFee: 'Taksa QRMV (Regjistri Qendror)',
    notaryCost: 'Kostot e noterit',
    sealCost: 'Vula e firmes',
    bankAccount: 'Hapja e llogarise bankare',
    accountantFirst: 'Muaji i pare kontabilitet (vleresim)',
    totalEstimate: 'Totali i vleresuar',
    capitalNote: 'Kapitali themeltar nuk eshte kosto — jane mjete te firmes.',
    timelineTitle: 'Sa zgjat regjistrimi?',
    timelineItems: {
      dooel: { days: '1-2 dite pune', desc: 'SHPKNJP regjistrohet me shpejt — nevojitet vetem nje themelues dhe dokumentacion i thjeshtuar.' },
      doo: { days: '2-3 dite pune', desc: 'SHPK kerkon marreveshje mes themeluesve dhe vertetim noterial, qe kerkon kohe shtese.' },
      pausalec: { days: '1-2 dite pune', desc: 'Paushalist ka procedure te thjeshtuar si SHPKNJP.' },
    },
    requirementsTitle: 'Cfare ju nevojitet para regjistrimit?',
    requirements: [
      'Leternjoftim ose pasaporte e vlefshme',
      'Adresa e selise se firmes (mund edhe adresa e shtepise)',
      'Veprimtaria e zgjedhur (kodi NKD)',
      'Emer unik i firmes (kontrolloni ne QRMV)',
      'Kapitali themeltar (min. 5.000 MKD per SHPKNJP/SHPK)',
    ],
    stepsTitle: 'Hapat e pare pas regjistrimit',
    steps: [
      { title: 'Regjistrimi ne DAP', desc: 'Paraqitja prane Drejtorise se te Ardhurave Publike per numer tatimor dhe TVSH (nese aplikohet).' },
      { title: 'Regjistrimi MPIN', desc: 'Regjistrimi ne sistemin e kontributeve (MPIN) — i detyrueshem per punonjes.' },
      { title: 'Hapja e llogarise bankare', desc: 'Llogari ne denar dhe/ose deviza ne banken e zgjedhur.' },
      { title: 'Vula dhe faturimi', desc: 'Prokurimi i vules dhe vendosja e sistemit per leshimin e faturave.' },
    ],
    ctaInline: 'Facturino ju ndihmon te filloni me faturimin menjehere pas regjistrimit.',
    ctaButton: 'Provo falas',
    faqTitle: 'Pyetjet me te shpeshta',
    faq: [
      {
        q: 'Sa kushton regjistrimi i firmes ne Maqedoni?',
        a: 'Kostot totale per regjistrimin e firmes ne Maqedoni variojne nga 7.000 deri ne 15.000 MKD, ne varesi te llojit te firmes. Per SHPKNJP kostot jane me te uleta (taksa QRMV 2.720 MKD + noter + vule), ndersa per SHPK jane me te larta. Kapitali themeltar (min. 5.000 MKD) nuk eshte kosto.',
      },
      {
        q: 'Sa zgjat regjistrimi i firmes?',
        a: 'Regjistrimi i SHPKNJP zgjat 1-2 dite pune, dhe i SHPK 2-3 dite pune. Regjistri Qendror (QRMV) e perpunon aplikimin brenda 4 oreve pas paraqitjes, nese dokumentacioni eshte komplet.',
      },
      {
        q: 'A me duhet avokat per regjistrimin e firmes?',
        a: 'Nuk eshte e detyrueshme, por rekomandohet. Per SHPKNJP procedura eshte e thjeshtuar dhe mundeni vete ta kryeni permes sistemit te QRMV. Per SHPK me shume themelues, avokati ndihmon me hartimin e marreveshjes. Vertetimi noterial eshte i detyrueshem per te gjitha llojet.',
      },
      {
        q: 'Cili eshte kapitali minimal themeltar?',
        a: 'Kapitali minimal themeltar per SHPKNJP dhe SHPK eshte 5.000 MKD (rreth 80 euro). Per Paushalist nuk nevojitet kapital themeltar. Kapitali mund te jete ne mjete monetare ose ne kontribute jo-monetare (pajisje, automjete etj.).',
      },
    ],
    ctaTitle: 'Filloni me faturimin menjehere',
    ctaSub: 'Facturino eshte gati per firma te reja — regjistrohuni dhe leshoni faturen e pare per 5 minuta.',
    ctaMainButton: 'Fillo falas — 14 dite',
    ctaSecondary: 'Cakto demo',
  },
  tr: {
    backLink: '← Tum araclar',
    badge: 'Ucretsiz',
    h1: 'Firma Kayit Maliyet Hesaplayici',
    subtitle: 'Makedonya\'da firma acma maliyetlerini hesaplayin — tek kisilik, cok ortakli veya goturu. Aninda, kayit gerekmez.',
    companyTypeLabel: 'Firma turu',
    types: {
      dooel: 'DOOEL (Tek kisilik)',
      doo: 'DOO (Cok ortakli)',
      pausalec: 'Goturu',
    },
    typeDescriptions: {
      dooel: 'Sinirli sorumlu sirket — tek kurucu',
      doo: 'Sinirli sorumlu sirket — birden fazla kurucu',
      pausalec: 'Goturu vergilendirme — basitlestirilmis rejim',
    },
    foundersLabel: 'Kurucu sayisi',
    foundersHelp: 'DOO icin: en az 2, en fazla 50 kurucu',
    capitalLabel: 'Kurucu sermaye',
    capitalHelp: 'DOOEL ve DOO icin minimum 5.000 MKD',
    currency: 'MKD',
    resultsTitle: 'Maliyet hesabi',
    crsmFee: 'CRSM harcı (Merkez Sicili)',
    notaryCost: 'Noter masraflari',
    sealCost: 'Firma muhuru',
    bankAccount: 'Banka hesabi acma',
    accountantFirst: 'Ilk ay muhasebe (tahmin)',
    totalEstimate: 'Tahmini toplam',
    capitalNote: 'Kurucu sermaye bir maliyet degildir — sirketin varligidir.',
    timelineTitle: 'Kayit ne kadar surer?',
    timelineItems: {
      dooel: { days: '1-2 is gunu', desc: 'DOOEL en hizli kayit edilir — sadece bir kurucu ve basit belgeler gerekir.' },
      doo: { days: '2-3 is gunu', desc: 'DOO kurucular arasi sozlesme ve noter tasdiki gerektirir, bu da ek zaman alir.' },
      pausalec: { days: '1-2 is gunu', desc: 'Goturu, DOOEL\'e benzer basitlestirilmis bir prosedure sahiptir.' },
    },
    requirementsTitle: 'Kayit oncesi neler gerekli?',
    requirements: [
      'Gecerli kimlik karti veya pasaport',
      'Firma merkez adresi (ev adresi olabilir)',
      'Secilen faaliyet (NKD faaliyet kodu)',
      'Benzersiz firma adi (CRSM\'de kontrol edin)',
      'Kurucu sermaye (DOOEL/DOO icin min. 5.000 MKD)',
    ],
    stepsTitle: 'Kayit sonrasi ilk adimlar',
    steps: [
      { title: 'UJP kaydı', desc: 'Kamu Gelir Idaresi\'ne vergi numarasi ve KDV (uygulanabilirse) icin basvuru.' },
      { title: 'MPIN kaydı', desc: 'Katki sistemi (MPIN) kaydı — calisanlar icin zorunlu.' },
      { title: 'Banka hesabi acma', desc: 'Secilen bankada dinar ve/veya doviz hesabi.' },
      { title: 'Muhur ve faturalama', desc: 'Muhur temini ve fatura duzenleme sistemi kurulumu.' },
    ],
    ctaInline: 'Facturino kayit sonrasi hemen faturalamaya baslamaniza yardimci olur.',
    ctaButton: 'Ucretsiz dene',
    faqTitle: 'Sik sorulan sorular',
    faq: [
      {
        q: 'Makedonya\'da firma kaydi ne kadara mal olur?',
        a: 'Makedonya\'da firma kayit maliyetleri firma turune gore 7.000 ile 15.000 MKD arasinda degisir. DOOEL icin maliyetler daha dusuktur (CRSM harci 2.720 MKD + noter + muhur), DOO icin daha yuksektir. Kurucu sermaye (min. 5.000 MKD) maliyet degildir — sirketin varligidir.',
      },
      {
        q: 'Firma kaydı ne kadar surer?',
        a: 'DOOEL kaydı 1-2 is gunu, DOO kaydı 2-3 is gunu surer. Merkez Sicili (CRSM), belgeler tam ise basvuruyu 4 saat icinde isler. Noter tasdiki ayni gun yaptırılabilir.',
      },
      {
        q: 'Firma kaydı icin avukat gerekli mi?',
        a: 'Zorunlu degildir, ancak tavsiye edilir. DOOEL icin prosedur basittir ve CRSM\'nin tek durak sistemi uzerinden kendiniz yapabilirsiniz. Birden fazla kuruculu DOO icin avukat kurulis sozlesmesinin hazirlanmasinda yardimci olabilir. Noter tasdiki tum turler icin zorunludur.',
      },
      {
        q: 'Minimum kurucu sermaye nedir?',
        a: 'DOOEL ve DOO icin minimum kurucu sermaye 5.000 MKD\'dir (yaklasik 80 euro). Goturu icin kurucu sermaye gerekmez. Sermaye nakit (banka hesabina yatirilan) veya ayni (ekipman, arac vb.) olabilir.',
      },
    ],
    ctaTitle: 'Hemen faturalamaya baslayin',
    ctaSub: 'Facturino yeni firmalar icin hazir — kayit olun ve ilk faturanizi 5 dakikada kesin.',
    ctaMainButton: 'Ucretsiz basla — 14 gun',
    ctaSecondary: 'Demo planla',
  },
  en: {
    backLink: '← All tools',
    badge: 'Free',
    h1: 'Company Registration Cost Calculator',
    subtitle: 'Calculate all costs for registering a company in North Macedonia — sole proprietorship, LLC, or lump-sum. Instantly, no registration required.',
    companyTypeLabel: 'Company type',
    types: {
      dooel: 'DOOEL (Single-member LLC)',
      doo: 'DOO (Multi-member LLC)',
      pausalec: 'Lump-sum (Simplified)',
    },
    typeDescriptions: {
      dooel: 'Limited liability company — single founder',
      doo: 'Limited liability company — multiple founders',
      pausalec: 'Lump-sum taxation — simplified regime',
    },
    foundersLabel: 'Number of founders',
    foundersHelp: 'For DOO: minimum 2, maximum 50 founders',
    capitalLabel: 'Initial capital',
    capitalHelp: 'Minimum 5,000 MKD for DOOEL and DOO',
    currency: 'MKD',
    resultsTitle: 'Cost breakdown',
    crsmFee: 'CRSM registration fee (Central Registry)',
    notaryCost: 'Notary costs',
    sealCost: 'Company seal',
    bankAccount: 'Bank account opening',
    accountantFirst: 'First month accountant (estimate)',
    totalEstimate: 'Total estimated cost',
    capitalNote: 'Initial capital is not a cost — it becomes company assets.',
    timelineTitle: 'How long does registration take?',
    timelineItems: {
      dooel: { days: '1-2 business days', desc: 'DOOEL registers fastest — only one founder needed with simple documentation.' },
      doo: { days: '2-3 business days', desc: 'DOO requires an agreement between founders and notary certification, which takes additional time.' },
      pausalec: { days: '1-2 business days', desc: 'Lump-sum has a simplified procedure similar to DOOEL.' },
    },
    requirementsTitle: 'What do you need before registration?',
    requirements: [
      'Valid personal ID card or passport',
      'Company headquarters address (home address is acceptable)',
      'Chosen activity (NKD activity code)',
      'Unique company name (check at CRSM)',
      'Initial capital (min. 5,000 MKD for DOOEL/DOO)',
    ],
    stepsTitle: 'First steps after registration',
    steps: [
      { title: 'UJP registration', desc: 'Register with the Public Revenue Office for a tax number and VAT (if applicable).' },
      { title: 'MPIN registration', desc: 'Register in the contribution system (MPIN) — mandatory for employees.' },
      { title: 'Open a bank account', desc: 'Denar and/or foreign currency account at your chosen bank.' },
      { title: 'Seal and invoicing', desc: 'Obtain a company seal and set up an invoicing system.' },
    ],
    ctaInline: 'Facturino helps you start invoicing immediately after registration.',
    ctaButton: 'Try for free',
    faqTitle: 'Frequently Asked Questions',
    faq: [
      {
        q: 'How much does it cost to register a company in Macedonia?',
        a: 'Total costs for registering a company in North Macedonia range from 7,000 to 15,000 MKD, depending on the company type. For DOOEL (single-member LLC) costs are lower (CRSM fee 2,720 MKD + notary + seal), while DOO (multi-member LLC) costs are higher due to more complex documentation. The initial capital (min. 5,000 MKD) is not a cost — it becomes company assets.',
      },
      {
        q: 'How long does company registration take?',
        a: 'DOOEL registration takes 1-2 business days, and DOO takes 2-3 business days. The Central Registry (CRSM) processes the application within 4 hours of submission, provided documentation is complete. Notary certification can be done the same day.',
      },
      {
        q: 'Do I need a lawyer to register a company?',
        a: 'It is not mandatory, but recommended. For DOOEL the procedure is simple and you can complete it yourself through CRSM\'s one-stop system. For DOO with multiple founders, a lawyer can help with drafting the founding agreement. Notary certification is mandatory for all types.',
      },
      {
        q: 'What is the minimum capital required?',
        a: 'The minimum initial capital for DOOEL and DOO is 5,000 MKD (approximately 80 EUR). Lump-sum does not require initial capital. Capital can be in cash (deposited into a bank account) or in non-cash contributions (equipment, vehicles, etc.).',
      },
    ],
    ctaTitle: 'Start invoicing right away',
    ctaSub: 'Facturino is ready for new companies — sign up and issue your first invoice in 5 minutes.',
    ctaMainButton: 'Start free — 14 days',
    ctaSecondary: 'Schedule demo',
  },
} as const

export default function RegistrationCalculator({ locale }: { locale: Locale }) {
  const t = useMemo(() => copy[locale], [locale])
  const [companyType, setCompanyType] = useState<CompanyType>('dooel')
  const [founders, setFounders] = useState(2)
  const [capital, setCapital] = useState('5000')

  const capitalNum = parseInt(capital.replace(/[^0-9]/g, '')) || 0
  const minCapital = companyType === 'pausalec' ? 0 : 5000
  const capitalValid = companyType === 'pausalec' || capitalNum >= minCapital

  const costs = useMemo(() => calculateCosts(companyType, founders), [companyType, founders])

  const timeline = t.timelineItems[companyType]

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
    url: `https://www.facturino.mk/${locale}/alati/kalkulator-registracija-firma`,
    applicationCategory: 'FinanceApplication',
    operatingSystem: 'All',
    offers: { '@type': 'Offer', price: '0', priceCurrency: 'EUR' },
    author: {
      '@type': 'Organization',
      name: 'Facturino',
      url: 'https://www.facturino.mk',
    },
  }

  const breadcrumbLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Facturino', item: `https://www.facturino.mk/${locale}` },
      { '@type': 'ListItem', position: 2, name: locale === 'mk' ? 'Алатки' : locale === 'sq' ? 'Mjete' : locale === 'tr' ? 'Araclar' : 'Tools', item: `https://www.facturino.mk/${locale}/alati` },
      { '@type': 'ListItem', position: 3, name: t.h1, item: `https://www.facturino.mk/${locale}/alati/kalkulator-registracija-firma` },
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
          <div className="absolute top-10 right-10 w-72 h-72 bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite_2s]" />
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

      {/* Calculator */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-xl mx-auto px-4 sm:px-6">
          <div className="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 md:p-8">
            {/* Company Type Selector */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">{t.companyTypeLabel}</label>
              <div className="space-y-2">
                {(['dooel', 'doo', 'pausalec'] as const).map((type) => (
                  <button
                    key={type}
                    onClick={() => {
                      setCompanyType(type)
                      if (type === 'pausalec') setCapital('0')
                      else if (capital === '0') setCapital('5000')
                    }}
                    className={`w-full text-left px-4 py-3 rounded-xl transition-all ${
                      companyType === type
                        ? 'bg-indigo-600 text-white shadow-md'
                        : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'
                    }`}
                  >
                    <span className="block text-sm font-semibold">{t.types[type]}</span>
                    <span className={`block text-xs mt-0.5 ${companyType === type ? 'text-indigo-100' : 'text-gray-500'}`}>
                      {t.typeDescriptions[type]}
                    </span>
                  </button>
                ))}
              </div>
            </div>

            {/* Founders (only for DOO) */}
            {companyType === 'doo' && (
              <div className="mb-6 animate-[fadeIn_0.3s_ease-out]">
                <label htmlFor="reg-founders" className="block text-sm font-medium text-gray-700 mb-2">{t.foundersLabel}</label>
                <input
                  id="reg-founders"
                  type="number"
                  min={2}
                  max={50}
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-lg font-semibold text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-colors"
                  value={founders}
                  onChange={(e) => {
                    const val = Math.min(50, Math.max(2, parseInt(e.target.value) || 2))
                    setFounders(val)
                  }}
                />
                <p className="mt-1.5 text-xs text-gray-500">{t.foundersHelp}</p>
              </div>
            )}

            {/* Initial Capital */}
            {companyType !== 'pausalec' && (
              <div className="mb-6 animate-[fadeIn_0.3s_ease-out]">
                <label htmlFor="reg-capital" className="block text-sm font-medium text-gray-700 mb-2">{t.capitalLabel}</label>
                <div className="relative">
                  <input
                    id="reg-capital"
                    type="text"
                    inputMode="numeric"
                    className={`w-full rounded-xl border bg-white px-4 py-3 pr-16 text-lg font-semibold text-gray-900 focus:ring-2 focus:outline-none transition-colors ${
                      !capitalValid
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                        : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-200'
                    }`}
                    value={capital}
                    onChange={(e) => {
                      const val = e.target.value.replace(/[^0-9]/g, '')
                      setCapital(val)
                    }}
                  />
                  <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">{t.currency}</span>
                </div>
                <p className={`mt-1.5 text-xs ${!capitalValid ? 'text-red-500 font-medium' : 'text-gray-500'}`}>
                  {t.capitalHelp}
                </p>
              </div>
            )}

            {/* Cost Breakdown */}
            <div className="rounded-xl bg-gradient-to-br from-indigo-50 to-emerald-50 p-5 space-y-3">
              <h2 className="text-base font-bold text-gray-900 mb-3">{t.resultsTitle}</h2>

              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">{t.crsmFee}</span>
                <span className="text-sm font-bold text-gray-900">{fmt(costs.crsmFee)} {t.currency}</span>
              </div>

              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">{t.notaryCost}</span>
                <span className="text-sm font-bold text-gray-900">{fmtRange(costs.notary, t.currency)}</span>
              </div>

              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">{t.sealCost}</span>
                <span className="text-sm font-bold text-gray-900">{fmtRange(costs.seal, t.currency)}</span>
              </div>

              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">{t.bankAccount}</span>
                <span className="text-sm font-bold text-gray-900">{fmtRange(costs.bankAccount, t.currency)}</span>
              </div>

              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">{t.accountantFirst}</span>
                <span className="text-sm font-bold text-gray-900">{fmtRange(costs.accountant, t.currency)}</span>
              </div>

              <div className="border-t border-indigo-200 pt-3 mt-3">
                <div className="flex justify-between items-center">
                  <span className="text-base font-bold text-indigo-700">{t.totalEstimate}</span>
                  <span className="text-xl font-extrabold text-indigo-700">
                    {fmt(costs.totalMin)} - {fmt(costs.totalMax)} {t.currency}
                  </span>
                </div>
              </div>

              {companyType !== 'pausalec' && (
                <p className="text-xs text-gray-500 mt-2 bg-white/60 rounded-lg px-3 py-2">
                  {t.capitalNote}
                </p>
              )}
            </div>

            {/* Inline CTA */}
            <div className="mt-4 flex items-center justify-between rounded-lg bg-indigo-600/5 border border-indigo-100 px-4 py-3">
              <p className="text-sm text-gray-700">{t.ctaInline}</p>
              <a
                href={`${APP_URL}/signup`}
                className="ml-3 flex-shrink-0 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors whitespace-nowrap"
              >
                {t.ctaButton} &rarr;
              </a>
            </div>
          </div>
        </div>
      </section>

      {/* Timeline */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">{t.timelineTitle}</h2>
          <div className="grid md:grid-cols-3 gap-4">
            {(['dooel', 'doo', 'pausalec'] as const).map((type) => (
              <div
                key={type}
                className={`rounded-2xl border p-5 transition-all ${
                  companyType === type
                    ? 'bg-indigo-50 border-indigo-200 shadow-md ring-1 ring-indigo-200'
                    : 'bg-white border-gray-100 shadow-sm'
                }`}
              >
                <div className="flex items-center gap-2 mb-2">
                  <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </span>
                  <span className="text-sm font-bold text-gray-900">{t.types[type]}</span>
                </div>
                <p className="text-lg font-extrabold text-indigo-600 mb-1">{t.timelineItems[type].days}</p>
                <p className="text-sm text-gray-600 leading-relaxed">{t.timelineItems[type].desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Requirements */}
      <section className="pb-12 md:pb-16 bg-slate-50 py-12 md:py-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">{t.requirementsTitle}</h2>
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
            <ul className="space-y-4">
              {t.requirements.map((req, i) => (
                <li key={i} className="flex items-start gap-3">
                  <span className="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold mt-0.5">
                    {i + 1}
                  </span>
                  <span className="text-gray-700 leading-relaxed">{req}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </section>

      {/* Steps After Registration */}
      <section className="pb-12 md:pb-16 py-12 md:py-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">{t.stepsTitle}</h2>
          <div className="grid sm:grid-cols-2 gap-4">
            {t.steps.map((step, i) => (
              <div key={i} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div className="flex items-center gap-3 mb-3">
                  <span className="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-cyan-500 text-white flex items-center justify-center text-sm font-bold">
                    {i + 1}
                  </span>
                  <h3 className="text-base font-bold text-gray-900">{step.title}</h3>
                </div>
                <p className="text-sm text-gray-600 leading-relaxed">{step.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* FAQ Section */}
      <section className="section bg-slate-50">
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
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-emerald-600" />
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
