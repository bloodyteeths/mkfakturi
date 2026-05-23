import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/faktura-primer-mk', {
    title: {
      mk: 'Фактура пример: Како изгледа правилна фактура во Македонија',
      en: 'Invoice Example North Macedonia: What a Proper Invoice Looks Like',
      sq: 'Shembull fature: Si duket një faturë e saktë në Maqedoni',
      tr: 'Fatura örneği: Makedonya\'da doğru fatura nasıl görünür',
    },
    description: {
      mk: 'Комплетен пример на фактура со сите 15 задолжителни полиња по македонски закон. ЕДБ, ЕМБС, ДДВ, ставки, рекапитулација — сe на едно место.',
      en: 'Complete invoice example with all 15 mandatory fields under Macedonian law. EDB, EMBS, VAT, line items, recapitulation — everything in one place.',
      sq: 'Shembull i plotë i faturës me të gjitha 15 fushat e detyrueshme sipas ligjit maqedonas. EDB, EMBS, TVSH, zëra, rekapitulim — gjithçka në një vend.',
      tr: 'Makedon yasalarına göre 15 zorunlu alanın tamamını içeren fatura örneği. EDB, EMBS, KDV, kalemler, rekapitülasyon — hepsi bir arada.',
    },
    datePublished: '2026-05-22',
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Фактура пример: Како изгледа правилна фактура во Македонија',
    publishDate: '22 мај 2026',
    readTime: '6 мин читање',
    intro:
      'Дали некогаш сте се запрашале дали вашата фактура ги содржи сите задолжителни елементи? Во Македонија, Законот за ДДВ (член 53) точно пропишува кои полиња мора да ги има секоја фактура. Во оваа статија ви покажуваме комплетен пример на правилна фактура со сите 15 задолжителни елементи, објаснуваме ја разликата со профактурата и ги наведуваме најчестите грешки.',
    sections: [
      {
        title: 'Задолжителни елементи на фактура',
        content:
          'Според Законот за данокот на додадена вредност и подзаконските акти, секоја фактура издадена во Македонија мора да ги содржи следните 15 елементи:',
        items: [
          'Назив на фирмата (издавач)',
          'Адреса на седиште на издавачот',
          'ЕДБ (единствен даночен број) на издавачот',
          'ЕМБС (единствен матичен број на субјект) на издавачот',
          'Банкарска сметка на издавачот',
          'Реден број на фактурата (секвенцијален, без прескокнување)',
          'Датум на издавање на фактурата',
          'Датум на промет (испорака на стоки или извршување на услуги)',
          'Назив и адреса на купувачот',
          'ЕДБ на купувачот (за правни лица)',
          'Опис на секоја ставка (производ или услуга)',
          'Количина и единица мерка за секоја ставка',
          'Единечна цена без ДДВ',
          'Износ на ДДВ и стапка (18%, 5% или 0%)',
          'Вкупен износ со ДДВ',
        ],
      },
      {
        title: 'Пример на комплетна фактура',
        content:
          'Издавач: Маркови Услуги ДООЕЛ, ЕДБ: 4030026123456, ЕМБС: 7654321, ул. Партизанска 12, Скопје, Жиро сметка: 300000001234567\nКупувач: АБЦ Трговија ДООЕЛ, ЕДБ: 4002026789012, ЕМБС: 1234567, бул. ВМРО 45, Битола\nФактура бр. 2026-0042 | Датум: 15.05.2026 | Датум на промет: 14.05.2026\n\nСтавка 1: Веб дизајн — корпоративна страна × 1 усл. × 45.000 МКД = 45.000 МКД\nСтавка 2: Хостинг — годишен пакет × 1 пар. × 12.000 МКД = 12.000 МКД\nСтавка 3: Техничка поддршка × 10 часа × 1.500 МКД = 15.000 МКД\n\nОсновица (без ДДВ): 72.000 МКД\nДДВ 18%: 12.960 МКД\nВКУПНО ЗА ПЛАЌАЊЕ: 84.960 МКД',
        items: null,
      },
      {
        title: 'Фактура vs профактура',
        content:
          'Профактурата (предфактура) НЕ е даночен документ — таа е само понуда за плаќање. Вистинската фактура мора да се издаде по извршениот промет. За повеќе детали прочитајте ја нашата статија за фактура vs профактура.',
        items: [
          'Фактурата е правно обврзувачка — профактурата не е',
          'Фактурата влегува во ДДВ евиденцијата — профактурата не',
          'Фактурата создава обврска за плаќање — профактурата е само понуда',
          'Фактурата мора да има секвенцијален број — профактурата може да има слободна нумерација',
          'Фактурата е доказ пред суд — профактурата не е',
        ],
      },
      {
        title: 'Чести грешки при фактурирање',
        content: 'Овие грешки може да ви создадат проблеми при даночна инспекција:',
        items: [
          'Отсутен ЕДБ — без ЕДБ, фактурата не е даночно валидна и купувачот не може да одбие влезен ДДВ',
          'Погрешна пресметка на ДДВ — 18% од основицата, НЕ од вкупниот износ. Пример: 1.000 МКД основица = 180 МКД ДДВ, НЕ 1.180 × 18%',
          'Прескокнат реден број — фактурите мора да бидат последователно нумерирани (001, 002, 003...) без празнини',
          'Отсутен датум на промет — ако датумот на промет се разликува од датумот на издавање, мора да бидат наведени и двата',
          'Отсутна банкарска сметка — фактурата мора да содржи жиро сметка на издавачот за уплата',
        ],
      },
      {
        title: 'Како Facturino помага',
        content:
          'Facturino автоматски генерира фактури со сите 15 задолжителни полиња. Не треба да паметите кои полиња се потребни — системот ги валидира пред да ви дозволи да ја испратите фактурата.',
        items: [
          'Автоматско пополнување на ЕДБ, ЕМБС и банкарска сметка',
          'Секвенцијално нумерирање без можност за прескокнување',
          'Автоматска ДДВ рекапитулација по стапки (18%, 5%, 0%)',
          'Валидација на сите задолжителни полиња пред испраќање',
          'Генерирање на PDF и е-фактура (UBL XML) истовремено',
          'Испраќање директно по е-пошта од платформата',
        ],
      },
    ],
    relatedTitle: 'Поврзани статии',
    relatedArticles: [
      { slug: 'kako-da-napravite-faktura', title: 'Како да направите фактура: Чекор-по-чекор водич' },
      { slug: 'faktura-vs-proforma', title: 'Фактура vs профактура: Клучни разлики' },
      { slug: 'ddv-vodich-mk', title: 'ДДВ водич за Македонија' },
    ],
    bottomCta: {
      title: 'Креирајте фактура за 30 секунди',
      subtitle: 'Сите задолжителни елементи автоматски пополнети.',
      cta: 'Креирај фактура →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Invoice Example North Macedonia: What a Proper Invoice Looks Like',
    publishDate: 'May 22, 2026',
    readTime: '6 min read',
    intro:
      'Have you ever wondered whether your invoice contains all the mandatory elements? In North Macedonia, the VAT Law (Article 53) prescribes exactly which fields every invoice must have. In this article, we show you a complete example of a proper invoice with all 15 mandatory elements, explain the difference from a proforma, and list the most common mistakes.',
    sections: [
      {
        title: 'Mandatory invoice elements',
        content:
          'According to the Value Added Tax Law and bylaws, every invoice issued in North Macedonia must contain the following 15 elements:',
        items: [
          'Company name (issuer)',
          'Registered address of the issuer',
          'EDB (unique tax number) of the issuer',
          'EMBS (unique entity registration number) of the issuer',
          'Bank account of the issuer',
          'Sequential invoice number (no gaps allowed)',
          'Date of issue',
          'Date of supply (delivery of goods or performance of services)',
          'Buyer name and address',
          'Buyer EDB (for legal entities)',
          'Description of each item (product or service)',
          'Quantity and unit of measure for each item',
          'Unit price excluding VAT',
          'VAT amount and rate (18%, 5%, or 0%)',
          'Total amount including VAT',
        ],
      },
      {
        title: 'Complete invoice example',
        content:
          'Issuer: Markovi Uslugi DOOEL, EDB: 4030026123456, EMBS: 7654321, ul. Partizanska 12, Skopje, Bank account: 300000001234567\nBuyer: ABC Trgovija DOOEL, EDB: 4002026789012, EMBS: 1234567, bul. VMRO 45, Bitola\nInvoice No. 2026-0042 | Date: 15.05.2026 | Supply date: 14.05.2026\n\nItem 1: Web design — corporate website × 1 svc × 45,000 MKD = 45,000 MKD\nItem 2: Hosting — annual package × 1 pcs × 12,000 MKD = 12,000 MKD\nItem 3: Technical support × 10 hrs × 1,500 MKD = 15,000 MKD\n\nSubtotal (excl. VAT): 72,000 MKD\nVAT 18%: 12,960 MKD\nTOTAL FOR PAYMENT: 84,960 MKD',
        items: null,
      },
      {
        title: 'Invoice vs proforma invoice',
        content:
          'A proforma invoice is NOT a tax document — it is only a payment offer. A real invoice must be issued after the supply has taken place. For more details, read our article on invoice vs proforma.',
        items: [
          'An invoice is legally binding — a proforma is not',
          'An invoice enters the VAT records — a proforma does not',
          'An invoice creates a payment obligation — a proforma is just an offer',
          'An invoice requires a sequential number — a proforma can have flexible numbering',
          'An invoice is evidence in court — a proforma is not',
        ],
      },
      {
        title: 'Common invoicing mistakes',
        content: 'These mistakes can cause problems during a tax inspection:',
        items: [
          'Missing EDB — without an EDB, the invoice is not valid for tax and the buyer cannot deduct input VAT',
          'Wrong VAT calculation — 18% of the base amount, NOT of the total. Example: 1,000 MKD base = 180 MKD VAT, NOT 1,180 × 18%',
          'Skipped sequential number — invoices must be numbered consecutively (001, 002, 003...) with no gaps',
          'Missing supply date — if the supply date differs from the issue date, both must be stated',
          'Missing bank account — the invoice must contain the issuer\'s bank account for payment',
        ],
      },
      {
        title: 'How Facturino helps',
        content:
          'Facturino automatically generates invoices with all 15 mandatory fields. You do not need to remember which fields are required — the system validates them before allowing you to send the invoice.',
        items: [
          'Automatic EDB, EMBS, and bank account population',
          'Sequential numbering with no gaps possible',
          'Automatic VAT recapitulation by rate (18%, 5%, 0%)',
          'Validation of all mandatory fields before sending',
          'Simultaneous PDF and e-invoice (UBL XML) generation',
          'Send directly by email from the platform',
        ],
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'kako-da-napravite-faktura', title: 'How to Create an Invoice: Step-by-Step Guide' },
      { slug: 'faktura-vs-proforma', title: 'Invoice vs Proforma: Key Differences' },
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for North Macedonia' },
    ],
    bottomCta: {
      title: 'Create an Invoice in 30 Seconds',
      subtitle: 'All mandatory fields auto-filled.',
      cta: 'Create Invoice →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Shembull fature: Si duket një faturë e saktë në Maqedoni',
    publishDate: '22 maj 2026',
    readTime: '6 min lexim',
    intro:
      'A jeni pyetur ndonjëherë nëse fatura juaj i përmban të gjitha elementet e detyrueshme? Në Maqedoni, Ligji për TVSH-në (neni 53) përcakton saktësisht cilat fusha duhet të ketë çdo faturë. Në këtë artikull ju tregojmë një shembull të plotë të faturës së saktë me të gjitha 15 elementet e detyrueshme, shpjegojmë dallimin nga profatura dhe listojmë gabimet më të zakonshme.',
    sections: [
      {
        title: 'Elementet e detyrueshme të faturës',
        content:
          'Sipas Ligjit për tatimin mbi vlerën e shtuar dhe akteve nënligjore, çdo faturë e lëshuar në Maqedoni duhet të përmbajë 15 elementet e mëposhtme:',
        items: [
          'Emri i kompanisë (lëshuesi)',
          'Adresa e regjistruar e lëshuesit',
          'EDB (numri unik tatimor) i lëshuesit',
          'EMBS (numri unik i regjistrimit) i lëshuesit',
          'Llogaria bankare e lëshuesit',
          'Numri rendor i faturës (sekuencial, pa boshllëqe)',
          'Data e lëshimit',
          'Data e furnizimit (dorëzimi i mallrave ose kryerja e shërbimeve)',
          'Emri dhe adresa e blerësit',
          'EDB e blerësit (për persona juridikë)',
          'Përshkrimi i çdo zëri (produkt ose shërbim)',
          'Sasia dhe njësia e matjes për çdo zë',
          'Çmimi për njësi pa TVSH',
          'Shuma e TVSH-së dhe norma (18%, 5% ose 0%)',
          'Shuma totale me TVSH',
        ],
      },
      {
        title: 'Shembull i faturës së plotë',
        content:
          'Lëshuesi: Markovi Uslugi DOOEL, EDB: 4030026123456, EMBS: 7654321, ul. Partizanska 12, Shkup, Llogaria bankare: 300000001234567\nBlerësi: ABC Trgovija DOOEL, EDB: 4002026789012, EMBS: 1234567, bul. VMRO 45, Manastir\nFatura nr. 2026-0042 | Data: 15.05.2026 | Data e furnizimit: 14.05.2026\n\nZëri 1: Dizajn uebi — faqe korporative × 1 shërb. × 45.000 MKD = 45.000 MKD\nZëri 2: Hosting — paketë vjetore × 1 copë × 12.000 MKD = 12.000 MKD\nZëri 3: Mbështetje teknike × 10 orë × 1.500 MKD = 15.000 MKD\n\nBazë (pa TVSH): 72.000 MKD\nTVSH 18%: 12.960 MKD\nTOTALI PËR PAGESË: 84.960 MKD',
        items: null,
      },
      {
        title: 'Fatura vs profatura',
        content:
          'Profatura NUK është dokument tatimor — ajo është vetëm ofertë pagese. Fatura e vërtetë duhet të lëshohet pasi të ketë ndodhur furnizimi. Për më shumë detaje, lexoni artikullin tonë për fatura vs profatura.',
        items: [
          'Fatura është ligjërisht detyruese — profatura nuk është',
          'Fatura hyn në evidencën e TVSH-së — profatura jo',
          'Fatura krijon detyrim pagese — profatura është vetëm ofertë',
          'Fatura kërkon numër sekuencial — profatura mund të ketë numërim fleksibël',
          'Fatura është provë në gjykatë — profatura jo',
        ],
      },
      {
        title: 'Gabime të zakonshme në faturim',
        content: 'Këto gabime mund t\'ju shkaktojnë probleme gjatë inspektimit tatimor:',
        items: [
          'EDB mungon — pa EDB, fatura nuk është e vlefshme tatimore dhe blerësi nuk mund të zbresë TVSH-në hyrëse',
          'Llogaritje e gabuar e TVSH-së — 18% e bazës, JO e totalit. Shembull: 1.000 MKD bazë = 180 MKD TVSH, JO 1.180 × 18%',
          'Numër rendor i kapërcyer — faturat duhet numërohen njëra pas tjetrës (001, 002, 003...) pa boshllëqe',
          'Datë furnizimi mungon — nëse data e furnizimit ndryshon nga data e lëshimit, duhet të tregohen të dyja',
          'Llogari bankare mungon — fatura duhet të përmbajë llogarinë bankare të lëshuesit për pagesë',
        ],
      },
      {
        title: 'Si ndihmon Facturino',
        content:
          'Facturino gjeneron automatikisht fatura me të gjitha 15 fushat e detyrueshme. Nuk keni nevojë të mbani mend cilat fusha nevojiten — sistemi i validon para se t\'ju lejojë ta dërgoni faturën.',
        items: [
          'Plotësim automatik i EDB, EMBS dhe llogarisë bankare',
          'Numërim sekuencial pa mundësi kapërcimi',
          'Rekapitulim automatik i TVSH-së sipas normës (18%, 5%, 0%)',
          'Validim i të gjitha fushave të detyrueshme para dërgimit',
          'Gjenerim i njëkohshëm i PDF dhe e-faturës (UBL XML)',
          'Dërgim direkt me email nga platforma',
        ],
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    relatedArticles: [
      { slug: 'kako-da-napravite-faktura', title: 'Si të krijoni faturë: Udhëzues hap pas hapi' },
      { slug: 'faktura-vs-proforma', title: 'Fatura vs profatura: Dallimet kryesore' },
      { slug: 'ddv-vodich-mk', title: 'Udhëzues për TVSH-në në Maqedoni' },
    ],
    bottomCta: {
      title: 'Krijoni faturë për 30 sekonda',
      subtitle: 'Të gjitha fushat e detyrueshme plotësohen automatikisht.',
      cta: 'Krijo faturë →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Fatura örneği: Makedonya\'da doğru fatura nasıl görünür',
    publishDate: '22 Mayıs 2026',
    readTime: '6 dk okuma',
    intro:
      'Faturanızın tüm zorunlu unsurları içerip içermediğini hiç merak ettiniz mi? Kuzey Makedonya\'da KDV Yasası (Madde 53) her faturanın hangi alanları içermesi gerektiğini tam olarak belirler. Bu makalede, 15 zorunlu unsurun tamamını içeren eksiksiz bir fatura örneği gösteriyor, proforma farkını açıklıyor ve en yaygın hataları sıralıyoruz.',
    sections: [
      {
        title: 'Zorunlu fatura unsurları',
        content:
          'Katma Değer Vergisi Yasası ve alt mevzuata göre, Kuzey Makedonya\'da düzenlenen her fatura aşağıdaki 15 unsuru içermelidir:',
        items: [
          'Şirket adı (düzenleyen)',
          'Düzenleyenin kayıtlı adresi',
          'Düzenleyenin EDB\'si (benzersiz vergi numarası)',
          'Düzenleyenin EMBS\'si (benzersiz kuruluş sicil numarası)',
          'Düzenleyenin banka hesabı',
          'Sıralı fatura numarası (boşluksuz)',
          'Düzenleme tarihi',
          'Teslim tarihi (mal teslimi veya hizmet ifası)',
          'Alıcı adı ve adresi',
          'Alıcının EDB\'si (tüzel kişiler için)',
          'Her kalemin açıklaması (ürün veya hizmet)',
          'Her kalem için miktar ve ölçü birimi',
          'KDV hariç birim fiyat',
          'KDV tutarı ve oranı (%18, %5 veya %0)',
          'KDV dahil toplam tutar',
        ],
      },
      {
        title: 'Eksiksiz fatura örneği',
        content:
          'Düzenleyen: Markovi Uslugi DOOEL, EDB: 4030026123456, EMBS: 7654321, ul. Partizanska 12, Üsküp, Banka hesabı: 300000001234567\nAlıcı: ABC Trgovija DOOEL, EDB: 4002026789012, EMBS: 1234567, bul. VMRO 45, Manastır\nFatura No. 2026-0042 | Tarih: 15.05.2026 | Teslim tarihi: 14.05.2026\n\nKalem 1: Web tasarım — kurumsal site × 1 hizm. × 45.000 MKD = 45.000 MKD\nKalem 2: Hosting — yıllık paket × 1 adet × 12.000 MKD = 12.000 MKD\nKalem 3: Teknik destek × 10 saat × 1.500 MKD = 15.000 MKD\n\nMatrah (KDV hariç): 72.000 MKD\nKDV %18: 12.960 MKD\nÖDENECEK TOPLAM: 84.960 MKD',
        items: null,
      },
      {
        title: 'Fatura vs proforma fatura',
        content:
          'Proforma fatura bir vergi belgesi DEĞİLDİR — yalnızca bir ödeme teklifidir. Gerçek fatura, teslim gerçekleştikten sonra düzenlenmelidir. Daha fazla ayrıntı için fatura vs proforma makalemizi okuyun.',
        items: [
          'Fatura yasal olarak bağlayıcıdır — proforma değildir',
          'Fatura KDV kayıtlarına girer — proforma girmez',
          'Fatura ödeme yükümlülüğü oluşturur — proforma sadece bir tekliftir',
          'Fatura zorunlu sıralı numara gerektirir — proforma esnek numaralamaya sahip olabilir',
          'Fatura mahkemede delildir — proforma değildir',
        ],
      },
      {
        title: 'Yaygın faturalama hataları',
        content: 'Bu hatalar vergi denetiminde sorunlara yol açabilir:',
        items: [
          'Eksik EDB — EDB olmadan fatura vergi açısından geçerli değildir ve alıcı giriş KDV\'sini indiremez',
          'Yanlış KDV hesaplaması — matrahın %18\'i, toplam tutarın DEĞİL. Örnek: 1.000 MKD matrah = 180 MKD KDV, 1.180 × %18 DEĞİL',
          'Atlanan sıralı numara — faturalar boşluk olmadan ardışık numaralandırılmalıdır (001, 002, 003...)',
          'Eksik teslim tarihi — teslim tarihi düzenleme tarihinden farklıysa her ikisi de belirtilmelidir',
          'Eksik banka hesabı — fatura, ödeme için düzenleyenin banka hesabını içermelidir',
        ],
      },
      {
        title: 'Facturino nasıl yardımcı olur',
        content:
          'Facturino, 15 zorunlu alanın tamamını içeren faturalar otomatik oluşturur. Hangi alanların gerekli olduğunu hatırlamanıza gerek yok — sistem, faturayı göndermenize izin vermeden önce hepsini doğrular.',
        items: [
          'EDB, EMBS ve banka hesabının otomatik doldurulması',
          'Boşluk bırakmayan sıralı numaralama',
          'Orana göre otomatik KDV rekapitülasyonu (%18, %5, %0)',
          'Göndermeden önce tüm zorunlu alanların doğrulanması',
          'Eş zamanlı PDF ve e-fatura (UBL XML) oluşturma',
          'Platformdan doğrudan e-posta ile gönderme',
        ],
      },
    ],
    relatedTitle: 'İlgili makaleler',
    relatedArticles: [
      { slug: 'kako-da-napravite-faktura', title: 'Fatura Nasıl Oluşturulur: Adım Adım Rehber' },
      { slug: 'faktura-vs-proforma', title: 'Fatura vs Proforma: Temel Farklar' },
      { slug: 'ddv-vodich-mk', title: 'Kuzey Makedonya KDV Rehberi' },
    ],
    bottomCta: {
      title: '30 Saniyede Fatura Oluşturun',
      subtitle: 'Tüm zorunlu alanlar otomatik doldurulur.',
      cta: 'Fatura Oluştur →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function FakturaPrimerPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const blogLabel = locale === 'mk' ? 'Блог' : locale === 'sq' ? 'Blog' : locale === 'tr' ? 'Blog' : 'Blog'
  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'

  const articleLd = articleJsonLd({
    locale,
    slug: 'faktura-primer-mk',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-22',
    tags: ['faktura', 'primer', 'invoice', 'macedonia'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/faktura-primer-mk` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/blog`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">{t.backLink}</Link>
          <article>
            <header className="mb-10">
              <span className="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full mb-4">{t.tag}</span>
              <h1 className="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-3 leading-tight">{t.title}</h1>
              <p className="text-sm text-gray-500">{t.publishDate} &middot; {t.readTime}</p>
            </header>
            <div className="prose prose-lg max-w-none">
              <p className="text-lg text-gray-700 leading-relaxed mb-8">{t.intro}</p>
              {t.sections.map((s, i) => (
                <section key={i} className="mb-10">
                  <h2 className="text-2xl font-bold text-gray-900 mb-4">{s.title}</h2>
                  {s.content && (
                    <p className="text-gray-700 leading-relaxed mb-4 whitespace-pre-line">{s.content}</p>
                  )}
                  {s.items && (
                    <ul className="space-y-2 mb-4">
                      {s.items.map((item, j) => (
                        <li key={j} className="flex items-start gap-2">
                          <span className="text-blue-500 mt-1.5 text-xs">{'●'}</span>
                          <span className="text-gray-700">{item}</span>
                        </li>
                      ))}
                    </ul>
                  )}
                </section>
              ))}
            </div>
            {/* Related articles */}
            <aside className="mt-12 pt-8 border-t border-gray-200">
              <h3 className="text-lg font-bold text-gray-900 mb-4">{t.relatedTitle}</h3>
              <div className="grid gap-3">
                {t.relatedArticles.map((ra, i) => (
                  <Link key={i} href={`/${locale}/blog/${ra.slug}`} className="text-blue-600 hover:text-blue-800 hover:underline">{ra.title}</Link>
                ))}
              </div>
            </aside>
          </article>
          {/* Bottom CTA */}
          <div className="mt-16 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-2xl p-8 sm:p-12 text-center text-white">
            <h2 className="text-2xl sm:text-3xl font-extrabold mb-3">{t.bottomCta.title}</h2>
            <p className="text-blue-100 mb-6 text-lg">{t.bottomCta.subtitle}</p>
            <a href={t.bottomCta.href} className="inline-block bg-white text-blue-700 font-bold px-8 py-3.5 rounded-xl hover:bg-blue-50 transition-colors text-lg shadow-lg">{t.bottomCta.cta}</a>
          </div>
        </div>
      </div>
    </main>
  )
}
// CLAUDE-CHECKPOINT
