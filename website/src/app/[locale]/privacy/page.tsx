import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/privacy', {
    title: {
      mk: 'Политика за приватност — Facturino',
      sq: 'Politika e privatësisë — Facturino',
      tr: 'Gizlilik Politikasi — Facturino',
      en: 'Privacy Policy — Facturino',
    },
    description: {
      mk: 'Како Facturino ги собира, чува и штити вашите лични податоци. GDPR усогласеност и транспарентност за вашата приватност.',
      sq: 'Si Facturino mbledh, ruan dhe mbron te dhenat tuaja personale. Perputhje GDPR dhe transparence per privatësine tuaj.',
      tr: 'Facturino kisisel verilerinizi nasil toplar, saklar ve korur. GDPR uyumlulugu ve gizliliginiz icin seffaflik.',
      en: 'How Facturino collects, stores, and protects your personal data. GDPR compliance and transparency for your privacy.',
    },
  })
}

const copy = {
  mk: {
    h1: 'Политика за приватност',
    lastUpdated: 'Последно ажурирано: 1 јануари 2025',
    sections: [
      {
        title: '1. Вовед',
        content: 'Facturino DOOEL, Скопје, Северна Македонија ("Facturino", "ние", "нас") ја цени вашата приватност. Оваа Политика за приватност објаснува како ги собираме, користиме, чуваме и заштитуваме вашите лични податоци кога ја користите нашата платформа за сметководство достапна на app.facturino.mk и поврзаните услуги.'
      },
      {
        title: '2. Податоци што ги собираме',
        content: 'Ги собираме следните категории на податоци:',
        list: [
          'Информации за идентификација: име, презиме, е-пошта, телефонски број, назив на компанија, даночен број (ЕДБ)',
          'Финансиски податоци: фактури, трансакции, банкарски изводи, сметководствени записи',
          'Податоци за користење: IP адреса, тип на прелистувач, времетраење на сесија, страни што ги посетувате',
          'Податоци за плаќање: информации за наплата обработени преку Paddle (не ги чуваме податоците за кредитни картички директно)',
          'Комуникациски податоци: пораки испратени преку контакт формуларот, е-пошта кореспонденција'
        ]
      },
      {
        title: '3. Како ги користиме вашите податоци',
        content: 'Вашите податоци ги користиме за:',
        list: [
          'Обезбедување и одржување на нашата сметководствена платформа',
          'Обработка на фактури, банкарски трансакции и финансиски извештаи',
          'AI-базирани финансиски анализи и предлози (вашите податоци се обработуваат безбедно и не се споделуваат со трети страни за обука на AI модели)',
          'Обработка на плаќања и управување со претплати',
          'Испраќање на системски известувања и ажурирања за услугата',
          'Подобрување на нашите услуги и корисничко искуство',
          'Исполнување на законски обврски и даночни прописи'
        ]
      },
      {
        title: '4. Правна основа за обработка (GDPR)',
        content: 'Ги обработуваме вашите лични податоци врз основа на:',
        list: [
          'Извршување на договор: обработка неопходна за да ви ги обезбедиме нашите услуги',
          'Легитимен интерес: подобрување на услугите, безбедност, спречување на измами',
          'Согласност: за маркетинг комуникации и колачиња',
          'Законска обврска: исполнување на даночни и сметководствени прописи'
        ]
      },
      {
        title: '5. Колачиња (Cookies)',
        content: 'Нашата веб-страница користи колачиња за:',
        list: [
          'Неопходни колачиња: за функционирање на платформата (автентикација, безбедност)',
          'Аналитички колачиња: за разбирање како ја користите платформата (анонимизирани)',
          'Функционални колачиња: за зачувување на вашите преференции (јазик, поставки)'
        ],
        extra: 'Можете да ги контролирате колачињата преку поставките на вашиот прелистувач. Деактивирањето на неопходните колачиња може да влијае на функционалноста на платформата.'
      },
      {
        title: '6. Трети страни и под-обработувачи',
        content: 'Соработуваме со следните трети страни за обезбедување на нашите услуги:',
        list: [
          'Paddle: обработка на плаќања и управување со претплати (Paddle.com Market Limited, Велика Британија)',
          'Railway: хостинг на инфраструктура во ЕУ регион',
          'PSD2 банкарски провајдери: поврзување со банкарски сметки за увоз на изводи',
          'Е-пошта провајдери: за системски известувања'
        ],
        extra: 'Секој под-обработувач е обврзан со договор за обработка на податоци (DPA) и ги обработува вашите податоци исклучиво според нашите инструкции.'
      },
      {
        title: '7. Пренос на податоци',
        content: 'Вашите податоци примарно се чуваат во ЕУ регион. Кога е неопходен пренос надвор од ЕУ/ЕЕА (на пример, за Paddle), обезбедуваме соодветни заштитни мерки вклучувајќи стандардни договорни клаузули (SCC) одобрени од Европската комисија.'
      },
      {
        title: '8. Безбедност на податоци',
        content: 'Применуваме технички и организациски мерки за заштита на вашите податоци:',
        list: [
          'Енкрипција на податоците при пренос (TLS/SSL) и во мирување',
          'Редовни безбедносни ревизии и резервни копии',
          'Рол-базиран пристап со минимални привилегии',
          'Аудит траги за следење на пристап до чувствителни податоци',
          'Хостинг во безбедни ЕУ дата центри'
        ]
      },
      {
        title: '9. Вашите права',
        content: 'Согласно GDPR и локалното законодавство, имате право на:',
        list: [
          'Пристап: да побарате копија од вашите лични податоци',
          'Исправка: да побарате корекција на неточни податоци',
          'Бришење: да побарате бришење на вашите податоци (со исклучоци предвидени со закон)',
          'Ограничување: да побарате ограничување на обработката',
          'Преносливост: да ги добиете вашите податоци во структуриран формат',
          'Приговор: да приговарате на обработката за директен маркетинг',
          'Повлекување на согласност: во секое време без да влијае на законитоста на претходната обработка'
        ],
        extra: 'За остварување на вашите права, контактирајте не на info@facturino.mk. Ќе одговориме во рок од 30 дена.'
      },
      {
        title: '10. Задржување на податоци',
        content: 'Вашите лични податоци ги чуваме додека е активен вашиот акаунт и дополнително онолку колку што е потребно за исполнување на законски обврски (вклучувајќи даночни прописи кои бараат чување на финансиски записи до 10 години). По бришење на акаунтот, податоците се анонимизираат или бришат во рок од 90 дена, освен ако законот не бара поинаку.'
      },
      {
        title: '11. Малолетни лица',
        content: 'Нашите услуги не се наменети за лица под 18 години. Свесно не собираме лични податоци од малолетни лица.'
      },
      {
        title: '12. Промени на политиката',
        content: 'Ја задржуваме можноста да ја ажурираме оваа Политика за приватност. За значајни промени ќе ве известиме преку е-пошта или известување во платформата. Продолженото користење на услугата по промените претставува прифаќање на новата политика.'
      },
      {
        title: '13. Контакт',
        content: 'За прашања поврзани со приватноста, контактирајте не на:',
        list: [
          'Е-пошта: info@facturino.mk',
          'Компанија: Facturino DOOEL',
          'Адреса: Скопје, Северна Македонија'
        ]
      }
    ]
  },
  sq: {
    h1: 'Politika e privatësisë',
    lastUpdated: 'Përditësuar së fundmi: 1 janar 2025',
    sections: [
      {
        title: '1. Hyrje',
        content: 'Facturino DOOEL, Shkup, Maqedonia e Veriut ("Facturino", "ne", "neve") e vlerëson privatësinë tuaj. Kjo Politikë e privatësisë shpjegon si i mbledhim, përdorim, ruajmë dhe mbrojmë të dhënat tuaja personale kur përdorni platformën tonë të kontabilitetit të disponueshme në app.facturino.mk dhe shërbimet e lidhura.'
      },
      {
        title: '2. Të dhënat që mbledhim',
        content: 'Mbledhim kategoritë e mëposhtme të të dhënave:',
        list: [
          'Informacione identifikimi: emër, mbiemër, email, numër telefoni, emër kompanie, numër tatimor (NIPT)',
          'Të dhëna financiare: fatura, transaksione, ekstrakte bankare, regjistrime kontabël',
          'Të dhëna përdorimi: adresë IP, lloj shfletuesi, kohëzgjatje sesioni, faqe të vizituara',
          'Të dhëna pagese: informacion faturimi i përpunuar përmes Paddle (nuk i ruajmë të dhënat e kartave të kreditit drejtpërdrejt)',
          'Të dhëna komunikimi: mesazhe të dërguara përmes formularit të kontaktit, korrespondencë email'
        ]
      },
      {
        title: '3. Si i përdorim të dhënat tuaja',
        content: 'I përdorim të dhënat tuaja për:',
        list: [
          'Ofrimin dhe mirëmbajtjen e platformës sonë të kontabilitetit',
          'Përpunimin e faturave, transaksioneve bankare dhe raporteve financiare',
          'Analiza financiare të bazuara në AI dhe sugjerime',
          'Përpunimin e pagesave dhe menaxhimin e abonimeve',
          'Dërgimin e njoftimeve të sistemit dhe përditësimeve të shërbimit',
          'Përmirësimin e shërbimeve dhe përvojës së përdoruesit',
          'Përmbushjen e detyrimeve ligjore dhe rregulloreve tatimore'
        ]
      },
      {
        title: '4. Baza ligjore për përpunim (GDPR)',
        content: 'I përpunojmë të dhënat tuaja personale në bazë të:',
        list: [
          'Ekzekutimi i kontratës: përpunim i nevojshëm për t\'ju ofruar shërbimet tona',
          'Interesi legjitim: përmirësim i shërbimeve, siguri, parandalim i mashtrimit',
          'Pëlqimi: për komunikime marketingu dhe biskota',
          'Detyrimi ligjor: përmbushje e rregulloreve tatimore dhe kontabël'
        ]
      },
      {
        title: '5. Biskota (Cookies)',
        content: 'Faqja jonë e internetit përdor biskota për:',
        list: [
          'Biskota të domosdoshme: për funksionimin e platformës (autentikim, siguri)',
          'Biskota analitike: për të kuptuar si e përdorni platformën (anonimizuara)',
          'Biskota funksionale: për ruajtjen e preferencave tuaja (gjuhë, cilësime)'
        ]
      },
      {
        title: '6. Palë të treta dhe nën-përpunues',
        content: 'Bashkëpunojmë me palët e treta të mëposhtme:',
        list: [
          'Paddle: përpunim i pagesave dhe menaxhim i abonimeve',
          'Railway: strehim i infrastrukturës në rajonin e BE-së',
          'Ofrues bankarë PSD2: lidhje me llogari bankare',
          'Ofrues emaili: për njoftime të sistemit'
        ]
      },
      {
        title: '7. Transferimi i të dhënave',
        content: 'Të dhënat tuaja ruhen kryesisht në rajonin e BE-së. Kur nevojitet transfer jashtë BE-së/ZEE-së, sigurojmë masa mbrojtëse përkatëse duke përfshirë klauzola standarde kontraktuale (SCC).'
      },
      {
        title: '8. Siguria e të dhënave',
        content: 'Zbatojmë masa teknike dhe organizative për mbrojtjen e të dhënave tuaja:',
        list: [
          'Enkriptim i të dhënave në transit (TLS/SSL) dhe në pushim',
          'Auditime të rregullta sigurie dhe kopje rezervë',
          'Qasje e bazuar në role me privilegje minimale',
          'Gjurmë auditimi për ndjekjen e qasjes në të dhëna të ndjeshme',
          'Strehim në qendrat e sigurta të të dhënave në BE'
        ]
      },
      {
        title: '9. Të drejtat tuaja',
        content: 'Sipas GDPR dhe legjislacionit lokal, keni të drejtë të:',
        list: [
          'Qasjes: të kërkoni kopje të të dhënave tuaja personale',
          'Korrigjimit: të kërkoni korrigjim të të dhënave të pasakta',
          'Fshirjes: të kërkoni fshirjen e të dhënave tuaja',
          'Kufizimit: të kërkoni kufizim të përpunimit',
          'Transportueshmërisë: të merrni të dhënat tuaja në format të strukturuar',
          'Kundërshtimit: të kundërshtoni përpunimin për marketing direkt',
          'Tërheqjes së pëlqimit: në çdo kohë'
        ],
        extra: 'Për ushtrimin e të drejtave tuaja, na kontaktoni në info@facturino.mk.'
      },
      {
        title: '10. Ruajtja e të dhënave',
        content: 'Të dhënat tuaja personale i ruajmë derisa llogaria juaj është aktive dhe shtesë aq sa kërkohet nga detyrimet ligjore.'
      },
      {
        title: '11. Të mitur',
        content: 'Shërbimet tona nuk janë të destinuara për persona nën 18 vjeç.'
      },
      {
        title: '12. Ndryshime të politikës',
        content: 'E rezervojmë mundësinë të përditësojmë këtë Politikë të privatësisë. Për ndryshime të rëndësishme do t\'ju njoftojmë përmes emailit.'
      },
      {
        title: '13. Kontakti',
        content: 'Për pyetje lidhur me privatësinë, na kontaktoni:',
        list: [
          'Email: info@facturino.mk',
          'Kompania: Facturino DOOEL',
          'Adresa: Shkup, Maqedonia e Veriut'
        ]
      }
    ]
  },
  tr: {
    h1: 'Gizlilik Politikasi',
    lastUpdated: 'Son güncelleme: 1 Ocak 2025',
    sections: [
      {
        title: '1. Giris',
        content: 'Facturino DOOEL, Üsküp, Kuzey Makedonya ("Facturino", "biz") gizliliginize deger verir. Bu Gizlilik Politikasi, app.facturino.mk adresindeki muhasebe platformumuzu ve ilgili hizmetleri kullanirken kisisel verilerinizi nasil topladigimizi, kullandigimizi, sakladigimizi ve korudigimizi aciklar.'
      },
      {
        title: '2. Topladigimiz veriler',
        content: 'Asagidaki veri kategorilerini topluyoruz:',
        list: [
          'Kimlik bilgileri: ad, soyad, e-posta, telefon, sirket adi, vergi numarasi',
          'Finansal veriler: faturalar, islemler, banka ekstreleri, muhasebe kayitlari',
          'Kullanim verileri: IP adresi, tarayici türü, oturum süresi, ziyaret edilen sayfalar',
          'Ödeme verileri: Paddle üzerinden islenen fatura bilgileri',
          'Iletisim verileri: iletisim formu mesajlari, e-posta yazismalari'
        ]
      },
      {
        title: '3. Verilerinizi nasil kullaniyoruz',
        content: 'Verilerinizi su amaclarla kullaniyoruz:',
        list: [
          'Muhasebe platformumuzun saglanmasi ve bakimi',
          'Fatura, banka islemleri ve finansal raporlarin islenmesi',
          'AI tabanli finansal analizler ve öneriler',
          'Ödeme isleme ve abonelik yönetimi',
          'Sistem bildirimleri ve hizmet güncellemeleri',
          'Hizmet ve kullanici deneyiminin iyilestirilmesi',
          'Yasal yükümlülüklerin ve vergi düzenlemelerinin yerine getirilmesi'
        ]
      },
      {
        title: '4. Isleme icin yasal dayanak (GDPR)',
        content: 'Kisisel verilerinizi su temellere dayanarak isliyoruz:',
        list: [
          'Sözlesmenin ifasi: hizmetlerimizi sunmak icin gerekli isleme',
          'Mesru menfaat: hizmet iyilestirme, güvenlik, dolandiricilik önleme',
          'Rizaniz: pazarlama iletisimleri ve cerezler icin',
          'Yasal yükümlülük: vergi ve muhasebe düzenlemelerinin karsilanmasi'
        ]
      },
      {
        title: '5. Cerezler (Cookies)',
        content: 'Web sitemiz su amaclarla cerez kullanir:',
        list: [
          'Zorunlu cerezler: platform isleyisi icin (kimlik dogrulama, güvenlik)',
          'Analitik cerezler: platformu nasil kullandiginizi anlamak icin (anonimlestirilmis)',
          'Islevsel cerezler: tercihlerinizi kaydetmek icin (dil, ayarlar)'
        ]
      },
      {
        title: '6. Ücüncü taraflar ve alt islemciler',
        content: 'Asagidaki ücüncü taraflarla calisiyoruz:',
        list: [
          'Paddle: ödeme isleme ve abonelik yönetimi',
          'Railway: AB bölgesinde altyapi barindirma',
          'PSD2 bankacilik saglayicilari: banka hesabi baglantisi',
          'E-posta saglayicilari: sistem bildirimleri icin'
        ]
      },
      {
        title: '7. Veri transferi',
        content: 'Verileriniz öncelikle AB bölgesinde saklanir. AB/AEA disina transfer gerektiginde, Avrupa Komisyonu tarafindan onaylanan standart sözlesme maddeleri (SCC) dahil uygun güvenceler sagliyoruz.'
      },
      {
        title: '8. Veri güvenligi',
        content: 'Verilerinizi korumak icin teknik ve organizasyonel önlemler uyguluyoruz:',
        list: [
          'Aktarimda (TLS/SSL) ve depoda veri sifreleme',
          'Düzenli güvenlik denetimleri ve yedekler',
          'Minimum ayricalikla rol tabanli erisim',
          'Hassas verilere erisim icin audit izleri',
          'AB\'deki güvenli veri merkezlerinde barindirma'
        ]
      },
      {
        title: '9. Haklariniz',
        content: 'GDPR ve yerel mevzuat kapsaminda su haklara sahipsiniz:',
        list: [
          'Erisim: kisisel verilerinizin kopyasini talep etme',
          'Düzeltme: yanlis verilerin düzeltilmesini talep etme',
          'Silme: verilerinizin silinmesini talep etme',
          'Kisitlama: islemenin kisitlanmasini talep etme',
          'Tasinabilirlik: verilerinizi yapilandirilmis formatta alma',
          'Itiraz: dogrudan pazarlama icin islemeye itiraz etme',
          'Rizayi geri cekme: herhangi bir zamanda'
        ],
        extra: 'Haklarinizi kullanmak icin info@facturino.mk adresinden bize ulasin.'
      },
      {
        title: '10. Veri saklama',
        content: 'Kisisel verilerinizi hesabiniz aktif oldugu sürece ve yasal yükümlülüklerin gerektirdigi ek süre boyunca sakliyoruz.'
      },
      {
        title: '11. Reşit olmayanlar',
        content: 'Hizmetlerimiz 18 yasindan küçük kisiler icin tasarlanmamistir.'
      },
      {
        title: '12. Politika degisiklikleri',
        content: 'Bu Gizlilik Politikasini güncelleme hakkimizi sakli tutariz. Önemli degisiklikler icin sizi e-posta ile bilgilendiririz.'
      },
      {
        title: '13. Iletisim',
        content: 'Gizlilikle ilgili sorular icin bize ulasin:',
        list: [
          'E-posta: info@facturino.mk',
          'Sirket: Facturino DOOEL',
          'Adres: Üsküp, Kuzey Makedonya'
        ]
      }
    ]
  },
  en: {
    h1: 'Privacy Policy',
    lastUpdated: 'Last updated: January 1, 2025',
    sections: [
      {
        title: '1. Introduction',
        content: 'Facturino DOOEL, Skopje, North Macedonia ("Facturino", "we", "us") values your privacy. This Privacy Policy explains how we collect, use, store, and protect your personal data when you use our accounting platform available at app.facturino.mk and related services.'
      },
      {
        title: '2. Data We Collect',
        content: 'We collect the following categories of data:',
        list: [
          'Identification information: name, surname, email, phone number, company name, tax number',
          'Financial data: invoices, transactions, bank statements, accounting records',
          'Usage data: IP address, browser type, session duration, pages visited',
          'Payment data: billing information processed through Paddle (we do not store credit card data directly)',
          'Communication data: messages sent through the contact form, email correspondence'
        ]
      },
      {
        title: '3. How We Use Your Data',
        content: 'We use your data to:',
        list: [
          'Provide and maintain our accounting platform',
          'Process invoices, bank transactions, and financial reports',
          'AI-based financial analysis and suggestions (your data is processed securely and not shared with third parties for AI model training)',
          'Process payments and manage subscriptions',
          'Send system notifications and service updates',
          'Improve our services and user experience',
          'Fulfill legal obligations and tax regulations'
        ]
      },
      {
        title: '4. Legal Basis for Processing (GDPR)',
        content: 'We process your personal data based on:',
        list: [
          'Contract performance: processing necessary to provide our services',
          'Legitimate interest: service improvement, security, fraud prevention',
          'Consent: for marketing communications and cookies',
          'Legal obligation: fulfilling tax and accounting regulations'
        ]
      },
      {
        title: '5. Cookies',
        content: 'Our website uses cookies for:',
        list: [
          'Essential cookies: for platform functionality (authentication, security)',
          'Analytics cookies: to understand how you use the platform (anonymized)',
          'Functional cookies: to save your preferences (language, settings)'
        ],
        extra: 'You can control cookies through your browser settings. Disabling essential cookies may affect platform functionality.'
      },
      {
        title: '6. Third Parties and Sub-processors',
        content: 'We work with the following third parties to provide our services:',
        list: [
          'Paddle: payment processing and subscription management (Paddle.com Market Limited, UK)',
          'Railway: infrastructure hosting in the EU region',
          'PSD2 banking providers: connecting to bank accounts for statement import',
          'Email providers: for system notifications'
        ],
        extra: 'Each sub-processor is bound by a data processing agreement (DPA) and processes your data exclusively according to our instructions.'
      },
      {
        title: '7. Data Transfers',
        content: 'Your data is primarily stored in the EU region. When transfers outside the EU/EEA are necessary (e.g., for Paddle), we ensure appropriate safeguards including Standard Contractual Clauses (SCC) approved by the European Commission.'
      },
      {
        title: '8. Data Security',
        content: 'We implement technical and organizational measures to protect your data:',
        list: [
          'Data encryption in transit (TLS/SSL) and at rest',
          'Regular security audits and backups',
          'Role-based access with minimal privileges',
          'Audit trails for tracking access to sensitive data',
          'Hosting in secure EU data centers'
        ]
      },
      {
        title: '9. Your Rights',
        content: 'Under GDPR and local legislation, you have the right to:',
        list: [
          'Access: request a copy of your personal data',
          'Rectification: request correction of inaccurate data',
          'Erasure: request deletion of your data (with exceptions provided by law)',
          'Restriction: request restriction of processing',
          'Portability: receive your data in a structured format',
          'Objection: object to processing for direct marketing',
          'Withdrawal of consent: at any time without affecting the lawfulness of prior processing'
        ],
        extra: 'To exercise your rights, contact us at info@facturino.mk. We will respond within 30 days.'
      },
      {
        title: '10. Data Retention',
        content: 'We keep your personal data while your account is active and additionally as long as required to fulfill legal obligations (including tax regulations requiring financial records to be kept for up to 10 years). After account deletion, data is anonymized or deleted within 90 days, unless the law requires otherwise.'
      },
      {
        title: '11. Minors',
        content: 'Our services are not intended for persons under 18 years of age. We do not knowingly collect personal data from minors.'
      },
      {
        title: '12. Policy Changes',
        content: 'We reserve the right to update this Privacy Policy. For significant changes, we will notify you via email or a notification in the platform. Continued use of the service after changes constitutes acceptance of the new policy.'
      },
      {
        title: '13. Contact',
        content: 'For privacy-related questions, contact us at:',
        list: [
          'Email: info@facturino.mk',
          'Company: Facturino DOOEL',
          'Address: Skopje, North Macedonia'
        ]
      }
    ]
  }
} as const

type Section = {
  title: string
  content: string
  list?: readonly string[]
  extra?: string
}

export default async function PrivacyPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content" className="section">
      <div className="container max-w-3xl">
        <h1 className="mb-2 text-3xl font-bold" style={{ color: 'var(--color-primary)' }}>
          {t.h1}
        </h1>
        <p className="mb-8 text-sm text-gray-500">{t.lastUpdated}</p>

        <div className="space-y-8">
          {t.sections.map((section: Section, i: number) => (
            <section key={i}>
              <h2 className="mb-2 text-xl font-semibold text-gray-900">{section.title}</h2>
              <p className="text-sm text-gray-700 leading-relaxed">{section.content}</p>
              {section.list && (
                <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-700">
                  {section.list.map((item: string, idx: number) => (
                    <li key={idx}>{item}</li>
                  ))}
                </ul>
              )}
              {section.extra && (
                <p className="mt-2 text-sm text-gray-600 italic">{section.extra}</p>
              )}
            </section>
          ))}
        </div>
      </div>
    </main>
  )
}
// CLAUDE-CHECKPOINT
