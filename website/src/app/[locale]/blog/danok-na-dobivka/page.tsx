import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/danok-na-dobivka', {
    title: {
      mk: 'Данок на добивка: Стапки, рокови и пресметка | Facturino',
      en: 'Corporate Income Tax: Rates, Deadlines and Calculation | Facturino',
      sq: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja | Facturino',
      tr: 'Kurumlar vergisi: Oranlar, son tarihler ve hesaplama | Facturino',
    },
    description: {
      mk: 'Водич за данокот на добивка во Македонија: стапка од 10%, даночна основа, признати трошоци, аконтации и годишна пријава ДБ-ВП до 15 март.',
      en: 'Guide to corporate income tax in Macedonia: 10% rate, tax base, deductible expenses, advance payments and annual DB-VP return due March 15.',
      sq: 'Udhëzues për tatimin mbi fitimin në Maqedoni: norma 10%, baza tatimore, shpenzimet e zbritshme, pagesat paraprake dhe deklarata vjetore DB-VP deri më 15 mars.',
      tr: 'Makedonya\'da kurumlar vergisi rehberi: %10 oran, vergi matrahı, indirilebilir giderler, avans ödemeler ve 15 Mart\'a kadar yıllık DB-VP beyannamesi.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Едукација',
    title: 'Данок на добивка: Стапки, рокови и пресметка',
    publishDate: '4 февруари 2026',
    readTime: '7 мин читање',
    intro: 'Данокот на добивка е директен данок кој го плаќаат сите правни лица во Македонија на остварената добивка. Со стапка од 10%, македонскиот корпоративен данок е меѓу најниските во Европа — но правилното пресметување на даночната основа и почитувањето на роковите е клучно за избегнување казни. Во овој водич детално ги покриваме стапките, признатите трошоци, аконтациите и годишната даночна пријава.',
    sections: [
      {
        title: 'Стапка и даночна основа',
        content: 'Стапката на данокот на добивка во Македонија е единствена и изнесува 10% на даночната основа. Даночната основа се пресметува како разлика меѓу вкупните приходи и вкупните признати расходи на компанијата во текот на фискалната година (1 јануари до 31 декември). Важно е да се напомене дека не сите трошоци се даночно признати — одредени ставки мора да се додадат назад на добивката при пресметување на даночната основа. За мали компании со годишен приход до 6.000.000 МКД постои поедноставена даночна постапка — тие можат да плаќаат 1% на вкупниот приход наместо стандардниот данок на добивка, што значително ја поедноставува администрацијата.',
        items: null,
        steps: null,
      },
      {
        title: 'Признати и непризнати трошоци',
        content: 'Правилната класификација на трошоците е клучна за точното пресметување на даночната основа.',
        items: [
          'Признати трошоци — Плати и придонеси за вработените, вклучувајќи бонуси и надоместоци.',
          'Признати трошоци — Набавка на суровини и материјали директно поврзани со производството.',
          'Признати трошоци — Закупнина на деловен простор, канцелариски материјали и комунални услуги.',
          'Признати трошоци — Амортизација на основни средства по пропишаните стапки.',
          'Непризнати трошоци — Казни, пенали и глоби наметнати од државни органи.',
          'Непризнати трошоци — Репрезентација над 1% од вкупниот приход.',
          'Непризнати трошоци — Донации над законски дозволениот лимит (5% од приходот).',
          'Непризнати трошоци — Трошоци кои не се документирани со валидна фактура.',
        ],
        steps: null,
      },
      {
        title: 'Аконтации на данок на добивка',
        content: 'Компаниите во Македонија плаќаат месечни аконтации (авансни плаќања) на данокот на добивка. Аконтацијата се пресметува врз основа на добивката од претходната година поделена на 12 месеци. Месечната аконтација се плаќа до 15-ти во месецот за претходниот месец. Доколку компанијата очекува значително помала добивка во тековната година, може да поднесе барање за намалување на аконтацијата до УЈП. По поднесувањето на годишната даночна пријава, се прави конечна пресметка — ако вкупно платените аконтации го надминуваат реалниот данок, разликата се враќа или се пренесува.',
        items: null,
        steps: null,
      },
      {
        title: 'Годишна пријава ДБ-ВП: Чекор по чекор',
        content: null,
        items: null,
        steps: [
          { step: 'Подгответе ги финансиските извештаи', desc: 'Завршете го годишниот биланс на состојба и билансот на успех. Сите приходи и расходи мора да бидат точно евидентирани и документирани со фактури.' },
          { step: 'Пресметајте ја даночната основа', desc: 'Почнете од сметководствената добивка, додадете ги непризнатите трошоци и одземете ги даночните олеснувања за да ја добиете конечната даночна основа.' },
          { step: 'Пополнете го образецот ДБ-ВП', desc: 'Внесете ги податоците во образецот ДБ-ВП преку системот е-Даноци на УЈП. Образецот ги вклучува сите ставки за пресметка на данокот.' },
          { step: 'Поднесете до 15 март', desc: 'Годишната даночна пријава мора да се поднесе електронски до УЈП најдоцна до 15 март во тековната година за претходната фискална година.' },
          { step: 'Платете ја евентуалната разлика', desc: 'Ако пресметаниот данок е поголем од платените аконтации, разликата мора да се плати до 30 април.' },
        ],
      },
      {
        title: 'Казни и последици',
        content: 'Непочитувањето на роковите и правилата за данокот на добивка повлекува сериозни последици.',
        items: [
          'Ненавремена пријава — Глоба од 500 до 5.000 евра за правно лице и 250 до 1.000 евра за одговорното лице.',
          'Неплаќање на аконтации — Камата од 0,03% дневно на неплатениот износ, плус можни дополнителни глоби.',
          'Неточни податоци — Доколку УЈП утврди намерно намалување на даночната основа, глобата може да достигне до 100% од затаениот данок.',
          'Недостаток на документација — Непризнавање на трошоци за кои нема валидна фактура или договор.',
          'Facturino помага — Автоматски извештаи за добивка и загуба, следење на признати трошоци и генерирање на податоци подготвени за ДБ-ВП пријавата.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'ddv-vodich-mk', title: 'ДДВ во Македонија: Целосен водич за 2026' },
      { slug: 'rokovi-ujp-2026', title: 'Даночен календар 2026: Сите рокови за УЈП' },
      { slug: 'godishna-smetka-2025', title: 'Годишна сметка 2025: Целосен водич за поднесување до ЦРСМ' },
    ],
    cta: {
      title: 'Пресметајте го данокот на добивка лесно',
      desc: 'Facturino автоматски ги следи приходите и расходите и генерира извештаи за даночна пријава.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Education',
    title: 'Corporate Income Tax: Rates, Deadlines and Calculation',
    publishDate: 'February 4, 2026',
    readTime: '7 min read',
    intro: 'Corporate income tax is a direct tax paid by all legal entities in Macedonia on their realized profit. At a rate of 10%, the Macedonian corporate tax is among the lowest in Europe — but correctly calculating the tax base and meeting deadlines is crucial to avoiding penalties. In this guide, we cover in detail the rates, deductible expenses, advance payments, and the annual tax return.',
    sections: [
      {
        title: 'Rate and tax base',
        content: 'The corporate income tax rate in Macedonia is a flat 10% on the tax base. The tax base is calculated as the difference between total revenues and total recognized expenses of the company during the fiscal year (January 1 to December 31). It is important to note that not all expenses are tax-deductible — certain items must be added back to profit when calculating the tax base. For small companies with annual revenue up to 6,000,000 MKD, there is a simplified tax procedure — they can pay 1% of total revenue instead of the standard corporate income tax, which significantly simplifies administration.',
        items: null,
        steps: null,
      },
      {
        title: 'Deductible and non-deductible expenses',
        content: 'Proper classification of expenses is crucial for accurately calculating the tax base.',
        items: [
          'Deductible — Salaries and contributions for employees, including bonuses and allowances.',
          'Deductible — Procurement of raw materials directly related to production.',
          'Deductible — Rent for business premises, office supplies, and utility costs.',
          'Deductible — Depreciation of fixed assets at prescribed rates.',
          'Non-deductible — Fines and penalties imposed by government authorities.',
          'Non-deductible — Representation costs exceeding 1% of total revenue.',
          'Non-deductible — Donations above the legally permitted limit (5% of revenue).',
          'Non-deductible — Expenses not documented with a valid invoice.',
        ],
        steps: null,
      },
      {
        title: 'Advance payments',
        content: 'Companies in Macedonia pay monthly advance payments on corporate income tax. The advance payment is calculated based on the previous year\'s profit divided by 12 months. The monthly advance is due by the 15th of the current month for the previous month. If the company expects significantly lower profit in the current year, it can submit a request to UJP to reduce the advance payment amount. After submitting the annual tax return, a final calculation is made — if total advance payments exceed the actual tax, the difference is refunded or carried forward.',
        items: null,
        steps: null,
      },
      {
        title: 'Annual DB-VP return: Step by step',
        content: null,
        items: null,
        steps: [
          { step: 'Prepare financial statements', desc: 'Complete the annual balance sheet and income statement. All revenues and expenses must be accurately recorded and documented with invoices.' },
          { step: 'Calculate the tax base', desc: 'Start from accounting profit, add back non-deductible expenses, and subtract tax reliefs to arrive at the final tax base.' },
          { step: 'Fill out the DB-VP form', desc: 'Enter the data into the DB-VP form through UJP\'s e-Tax system. The form includes all items for tax calculation.' },
          { step: 'Submit by March 15', desc: 'The annual tax return must be submitted electronically to UJP no later than March 15 of the current year for the previous fiscal year.' },
          { step: 'Pay any remaining difference', desc: 'If the calculated tax exceeds total advance payments, the difference must be paid by April 30.' },
        ],
      },
      {
        title: 'Penalties and consequences',
        content: 'Failure to comply with corporate income tax deadlines and rules carries serious consequences.',
        items: [
          'Late filing — Fine of EUR 500 to EUR 5,000 for the legal entity and EUR 250 to EUR 1,000 for the responsible person.',
          'Non-payment of advances — Interest of 0.03% per day on the unpaid amount, plus potential additional fines.',
          'Inaccurate data — If UJP determines intentional reduction of the tax base, the fine can reach up to 100% of the concealed tax.',
          'Lack of documentation — Non-recognition of expenses without a valid invoice or contract.',
          'Facturino helps — Automatic profit and loss reports, tracking of deductible expenses, and generation of data ready for the DB-VP return.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'ddv-vodich-mk', title: 'VAT in Macedonia: Complete Guide for 2026' },
      { slug: 'rokovi-ujp-2026', title: 'Tax Calendar 2026: All UJP Deadlines' },
      { slug: 'godishna-smetka-2025', title: 'Annual Accounts 2025: Complete Filing Guide for CRMS' },
    ],
    cta: {
      title: 'Calculate corporate tax with ease',
      desc: 'Facturino automatically tracks revenues and expenses and generates reports for your tax return.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Edukim',
    title: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja',
    publishDate: '4 shkurt 2026',
    readTime: '7 min lexim',
    intro: 'Tatimi mbi fitimin është një tatim i drejtpërdrejtë që paguhet nga të gjitha subjektet juridike në Maqedoni mbi fitimin e realizuar. Me normën 10%, tatimi korporativ maqedonas është ndër më të ulëtat në Europë — por llogaritja e saktë e bazës tatimore dhe respektimi i afateve është vendimtar për shmangien e gjobave. Në këtë udhëzues, mbulojmë në detaje normat, shpenzimet e zbritshme, pagesat paraprake dhe deklaratën vjetore tatimore.',
    sections: [
      {
        title: 'Norma dhe baza tatimore',
        content: 'Norma e tatimit mbi fitimin në Maqedoni është e sheshtë 10% mbi bazën tatimore. Baza tatimore llogaritet si diferenca midis të ardhurave totale dhe shpenzimeve totale të njohura të kompanisë gjatë vitit fiskal (1 janar deri më 31 dhjetor). Është e rëndësishme të theksohet se jo të gjitha shpenzimet janë të zbritshme tatimisht — zëra të caktuar duhet të shtohen përsëri te fitimi gjatë llogaritjes së bazës tatimore. Për kompani të vogla me të ardhura vjetore deri në 6.000.000 MKD, ekziston një procedurë e thjeshtuar tatimore — ato mund të paguajnë 1% të të ardhurave totale në vend të tatimit standard mbi fitimin, gjë që thjeshton ndjeshëm administrimin.',
        items: null,
        steps: null,
      },
      {
        title: 'Shpenzime të zbritshme dhe të pazbritshme',
        content: 'Klasifikimi i duhur i shpenzimeve është vendimtar për llogaritjen e saktë të bazës tatimore.',
        items: [
          'Të zbritshme — Pagat dhe kontributet për punonjësit, përfshirë bonuset dhe shtesat.',
          'Të zbritshme — Prokurimi i lëndëve të para të lidhura drejtpërdrejt me prodhimin.',
          'Të zbritshme — Qiraja për ambiente biznesi, furnizimet e zyrës dhe kostot e shërbimeve komunale.',
          'Të zbritshme — Amortizimi i aseteve fikse me norma të përcaktuara.',
          'Të pazbritshme — Gjobat dhe penalitetet e vendosura nga autoritetet qeveritare.',
          'Të pazbritshme — Kostot e përfaqësimit mbi 1% të të ardhurave totale.',
          'Të pazbritshme — Donacionet mbi limitin e lejuar ligjërisht (5% e të ardhurave).',
          'Të pazbritshme — Shpenzimet e padokumentuara me faturë të vlefshme.',
        ],
        steps: null,
      },
      {
        title: 'Pagesat paraprake',
        content: 'Kompanitë në Maqedoni paguajnë pagesa paraprake mujore për tatimin mbi fitimin. Pagesa paraprake llogaritet bazuar në fitimin e vitit të mëparshëm të ndarë me 12 muaj. Pagesa mujore duhet të bëhet deri në datën 15 të muajit aktual për muajin e mëparshëm. Nëse kompania pret fitim ndjeshëm më të ulët në vitin aktual, mund të paraqesë kërkesë pranë UJP për uljen e shumës së pagesës paraprake. Pas dorëzimit të deklaratës vjetore tatimore, bëhet llogaritja përfundimtare — nëse pagesat totale paraprake tejkalojnë tatimin aktual, diferenca rimbursohet ose transferohet.',
        items: null,
        steps: null,
      },
      {
        title: 'Deklarata vjetore DB-VP: Hap pas hapi',
        content: null,
        items: null,
        steps: [
          { step: 'Përgatitni pasqyrat financiare', desc: 'Përfundoni bilancin vjetor dhe pasqyrën e të ardhurave. Të gjitha të ardhurat dhe shpenzimet duhet të regjistrohen saktë dhe dokumentohen me fatura.' },
          { step: 'Llogaritni bazën tatimore', desc: 'Nisuni nga fitimi kontabël, shtoni shpenzimet e pazbritshme dhe zbritni lehtësirat tatimore për të arritur bazën tatimore përfundimtare.' },
          { step: 'Plotësoni formularin DB-VP', desc: 'Futni të dhënat në formularin DB-VP përmes sistemit e-Tatim të UJP-së. Formulari përfshin të gjithë zërat për llogaritjen e tatimit.' },
          { step: 'Dorëzoni deri më 15 mars', desc: 'Deklarata vjetore tatimore duhet të dorëzohet elektronikisht pranë UJP-së jo më vonë se 15 mars të vitit aktual për vitin e mëparshëm fiskal.' },
          { step: 'Paguani diferencën eventuale', desc: 'Nëse tatimi i llogarit tejkalon pagesat totale paraprake, diferenca duhet të paguhet deri më 30 prill.' },
        ],
      },
      {
        title: 'Gjobat dhe pasojat',
        content: 'Mosrespektimi i afateve dhe rregullave për tatimin mbi fitimin mbart pasoja serioze.',
        items: [
          'Deklarim i vonuar — Gjobë nga 500 deri 5.000 euro për subjektin juridik dhe 250 deri 1.000 euro për personin përgjegjës.',
          'Mospagesë e paraprake — Interes 0,03% në ditë mbi shumën e papaguar, plus gjoba shtesë të mundshme.',
          'Të dhëna të pasakta — Nëse UJP konstaton ulje të qëllimshme të bazës tatimore, gjoba mund të arrijë deri në 100% të tatimit të fshehur.',
          'Mungesë dokumentacioni — Mosnjohje e shpenzimeve pa faturë ose kontratë të vlefshme.',
          'Facturino ndihmon — Raporte automatike fitimi dhe humbjeje, ndjekje e shpenzimeve të zbritshme dhe gjenerim i të dhënave gati për deklaratën DB-VP.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'ddv-vodich-mk', title: 'TVSH në Maqedoni: Udhëzues i plotë për 2026' },
      { slug: 'rokovi-ujp-2026', title: 'Kalendari tatimor 2026: Të gjitha afatet për DAP' },
      { slug: 'godishna-smetka-2025', title: 'Llogaritë vjetore 2025: Udhëzues i plotë për dorëzim në QRMK' },
    ],
    cta: {
      title: 'Llogaritni tatimin mbi fitimin me lehtësi',
      desc: 'Facturino ndjek automatikisht të ardhurat dhe shpenzimet dhe gjeneron raporte për deklaratën tuaj tatimore.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Eğitim',
    title: 'Kurumlar vergisi: Oranlar, son tarihler ve hesaplama',
    publishDate: '4 Şubat 2026',
    readTime: '7 dk okuma',
    intro: 'Kurumlar vergisi, Makedonya\'daki tüm tüzel kişilerin elde ettikleri kar üzerinden ödediği doğrudan bir vergidir. %10\'luk oranıyla Makedonya kurumlar vergisi, Avrupa\'nın en düşükleri arasındadır — ancak vergi matrahının doğru hesaplanması ve son tarihlere uyulması cezalardan kaçınmak için hayati önem taşır. Bu rehberde oranları, indirilebilir giderleri, avans ödemeleri ve yıllık vergi beyannamesini ayrıntılı olarak ele alıyoruz.',
    sections: [
      {
        title: 'Oran ve vergi matrahı',
        content: 'Makedonya\'da kurumlar vergisi oranı, vergi matrahı üzerinden sabit %10\'dur. Vergi matrahı, mali yıl boyunca (1 Ocak - 31 Aralık) şirketin toplam gelirleri ile toplam kabul edilen giderleri arasındaki fark olarak hesaplanır. Tüm giderlerin vergiden düşülebilir olmadığını belirtmek önemlidir — vergi matrahı hesaplanırken belirli kalemlerin kara eklenmesi gerekir. Yıllık geliri 6.000.000 MKD\'ye kadar olan küçük şirketler için basitleştirilmiş bir vergi prosedürü mevcuttur — standart kurumlar vergisi yerine toplam gelirin %1\'ini ödeyebilirler, bu da yönetimi önemli ölçüde basitleştirir.',
        items: null,
        steps: null,
      },
      {
        title: 'İndirilebilir ve indirilemez giderler',
        content: 'Giderlerin doğru sınıflandırılması, vergi matrahının doğru hesaplanması için hayati önem taşır.',
        items: [
          'İndirilebilir — Primler ve ödenekler dahil çalışan maaşları ve katkı payları.',
          'İndirilebilir — Üretimle doğrudan ilgili hammadde temini.',
          'İndirilebilir — İş yeri kirası, ofis malzemeleri ve fatura giderleri.',
          'İndirilebilir — Belirlenmiş oranlarda sabit varlıkların amortismanı.',
          'İndirilemez — Devlet kurumları tarafından uygulanan para cezaları ve yaptırımlar.',
          'İndirilemez — Toplam gelirin %1\'ini aşan temsil giderleri.',
          'İndirilemez — Yasal olarak izin verilen limiti aşan bağışlar (gelirin %5\'i).',
          'İndirilemez — Geçerli bir fatura ile belgelenmeyen giderler.',
        ],
        steps: null,
      },
      {
        title: 'Avans ödemeler',
        content: 'Makedonya\'daki şirketler, kurumlar vergisi için aylık avans ödemeler yapar. Avans ödeme, önceki yılın karının 12 aya bölünmesiyle hesaplanır. Aylık avans, bir önceki ay için cari ayın 15\'ine kadar ödenir. Şirket cari yılda önemli ölçüde daha düşük kar bekliyorsa, avans ödeme tutarının düşürülmesi için UJP\'ye başvurabilir. Yıllık vergi beyannamesi verildikten sonra nihai hesaplama yapılır — toplam avans ödemeler gerçek vergiyi aşarsa, fark iade edilir veya devredilir.',
        items: null,
        steps: null,
      },
      {
        title: 'Yıllık DB-VP beyannamesi: Adım adım',
        content: null,
        items: null,
        steps: [
          { step: 'Mali tabloları hazırlayın', desc: 'Yıllık bilançoyu ve gelir tablosunu tamamlayın. Tüm gelir ve giderler doğru şekilde kaydedilmeli ve faturalarla belgelenmelidir.' },
          { step: 'Vergi matrahını hesaplayın', desc: 'Muhasebe karından başlayın, indirilemez giderleri ekleyin ve nihai vergi matrahına ulaşmak için vergi indirimlerini çıkarın.' },
          { step: 'DB-VP formunu doldurun', desc: 'UJP\'nin e-Vergi sistemi aracılığıyla DB-VP formuna verileri girin. Form, vergi hesaplaması için tüm kalemleri içerir.' },
          { step: '15 Mart\'a kadar gönderin', desc: 'Yıllık vergi beyannamesi, bir önceki mali yıl için cari yılın en geç 15 Mart\'ına kadar UJP\'ye elektronik olarak sunulmalıdır.' },
          { step: 'Varsa kalan farkı ödeyin', desc: 'Hesaplanan vergi toplam avans ödemeleri aşarsa, farkın 30 Nisan\'a kadar ödenmesi gerekir.' },
        ],
      },
      {
        title: 'Cezalar ve sonuçlar',
        content: 'Kurumlar vergisi son tarihlerine ve kurallarına uyulmaması ciddi sonuçlar doğurur.',
        items: [
          'Geç beyanname — Tüzel kişi için 500 ila 5.000 euro ve sorumlu kişi için 250 ila 1.000 euro para cezası.',
          'Avans ödememe — Ödenmemiş tutar üzerinden günlük %0,03 faiz ve olası ek cezalar.',
          'Yanlış veriler — UJP vergi matrahının kasıtlı olarak düşürüldüğünü tespit ederse, ceza gizlenen verginin %100\'üne kadar ulaşabilir.',
          'Belge eksikliği — Geçerli fatura veya sözleşme olmayan giderlerin tanınmaması.',
          'Facturino yardımcı olur — Otomatik kar-zarar raporları, indirilebilir gider takibi ve DB-VP beyannamesi için hazır veri üretimi.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'ddv-vodich-mk', title: "Makedonya'da KDV: 2026 için eksiksiz rehber" },
      { slug: 'rokovi-ujp-2026', title: 'Vergi takvimi 2026: Tüm UJP tarihleri' },
      { slug: 'godishna-smetka-2025', title: 'Yıllık hesaplar 2025: CRMS dosyalama rehberi' },
    ],
    cta: {
      title: 'Kurumlar vergisini kolayca hesaplayın',
      desc: 'Facturino gelir ve giderleri otomatik takip eder ve vergi beyannamesi için raporlar oluşturur.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function DanokNaDobivkaPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
      {/* ARTICLE HEADER */}
      <section className="section relative overflow-hidden pt-24 md:pt-32 pb-12 md:pb-16">
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-10 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob animation-delay-2000" />
        </div>
        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          <Link href={`/${locale}/blog`} className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors">{t.backLink}</Link>
          <div className="mb-4"><span className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600">{t.tag}</span></div>
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-6">{t.title}</h1>
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-8">
            <span className="flex items-center gap-1.5"><svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>{t.publishDate}</span>
            <span className="flex items-center gap-1.5"><svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{t.readTime}</span>
          </div>
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">{t.intro}</p>
        </div>
      </section>

      {/* ARTICLE BODY */}
      <section className="py-12 md:py-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <div className="space-y-12">
            {t.sections.map((section, i) => (
              <div key={i}>
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{section.title}</h2>
                {section.content && (<p className="text-gray-700 leading-relaxed text-lg">{section.content}</p>)}
                {section.items && (
                  <ul className="space-y-3 mt-2">
                    {section.items.map((item, j) => (
                      <li key={j} className="flex items-start gap-3">
                        <span className="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center"><svg className="w-3 h-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg></span>
                        <span className="text-gray-700 leading-relaxed">{item}</span>
                      </li>
                    ))}
                  </ul>
                )}
                {section.steps && (
                  <ol className="space-y-6 mt-4">
                    {section.steps.map((s, j) => (
                      <li key={j} className="flex items-start gap-4">
                        <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold flex items-center justify-center mt-0.5">{j + 1}</span>
                        <div><h3 className="font-semibold text-gray-900 text-lg">{s.step}</h3><p className="text-gray-600 leading-relaxed mt-1">{s.desc}</p></div>
                      </li>
                    ))}
                  </ol>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* RELATED ARTICLES */}
      <section className="py-12 md:py-16 bg-gray-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl font-bold text-gray-900 mb-6">{t.relatedTitle}</h2>
          <div className="grid gap-4">
            {t.related.map((r) => (
              <Link
                key={r.slug}
                href={`/${locale}/blog/${r.slug}`}
                className="group flex items-center justify-between bg-white rounded-xl border border-gray-100 px-6 py-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all"
              >
                <span className="text-gray-900 font-medium group-hover:text-indigo-600 transition-colors">{r.title}</span>
                <svg className="w-5 h-5 text-gray-400 group-hover:text-indigo-600 flex-shrink-0 ml-4 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* BOTTOM CTA */}
      <section className="section relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />
        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">{t.cta.title}</h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">{t.cta.desc}</p>
          <a href="https://app.facturino.mk/signup" className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all">
            {t.cta.button}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
          </a>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
