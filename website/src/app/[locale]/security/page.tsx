import Link from 'next/link'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import PageHero from '@/components/PageHero'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/security', {
    title: {
      mk: 'Безбедност — Facturino',
      sq: 'Siguria — Facturino',
      tr: 'Guvenlik — Facturino',
      en: 'Security — Facturino',
    },
    description: {
      mk: 'ЕУ хостинг, AES-256 енкрипција, GDPR усогласеност и аудит логови. Вашите финансиски податоци се безбедни со Facturino.',
      sq: 'Strehim ne BE, enkriptim AES-256, perputhje GDPR dhe gjurme auditimi. Te dhenat tuaja financiare jane te sigurta me Facturino.',
      tr: 'AB barindirma, AES-256 sifreleme, GDPR uyumlulugu ve denetim kayitlari. Finansal verileriniz Facturino ile guvende.',
      en: 'EU hosting, AES-256 encryption, GDPR compliance, and audit logs. Your financial data is safe with Facturino.',
    },
  })
}

const copy = {
  mk: {
    heroTitle: 'Вашите податоци се безбедни кај нас',
    heroSub: 'Безбедност на ниво на претпријатие за македонски бизниси. Facturino ги штити вашите финансиски податоци со најсовремена енкрипција, строга контрола на пристап и целосна транспарентност.',
    // Section 1: Data Protection
    dataProtectionTitle: 'Заштита на податоци',
    dataProtectionSub: 'Вашите финансиски информации се шифрирани во секој момент.',
    dataProtectionItems: [
      ['AES-256 енкрипција во мирување', 'Сите податоци складирани на нашите сервери се заштитени со AES-256 енкрипција — индустрискиот стандард за заштита на чувствителни информации.'],
      ['TLS 1.3 енкрипција при пренос', 'Секоја комуникација меѓу вашиот уред и нашите сервери е шифрирана со TLS 1.3, најновиот и најбезбеден протокол за пренос.'],
      ['Шифрирани резервни копии', 'Резервните копии од базата на податоци се целосно шифрирани, обезбедувајќи заштита дури и во случај на физички пристап до серверите.'],
      ['Автоматски дневни бекапи', 'Автоматско дневно правење резервни копии со задржување од 30 дена, за да ги вратите вашите податоци во секој момент.'],
    ],
    // Section 2: Infrastructure
    infraTitle: 'Инфраструктура',
    infraSub: 'Доверлива и усогласена инфраструктура во Европската Унија.',
    infraItems: [
      ['Хостирање во ЕУ регион', 'Нашата инфраструктура е хостирана на Railway во ЕУ регион, обезбедувајќи дека вашите податоци остануваат во Европа.'],
      ['GDPR усогласеност', 'Процесирањето на податоците е целосно усогласено со GDPR регулативата на Европската Унија.'],
      ['Редовни безбедносни ажурирања', 'Системот се ажурира редовно со најновите безбедносни закрпи и подобрувања.'],
      ['99,9% време на работа', 'Таргетираме 99,9% достапност со мониторинг 24/7 и автоматско скалирање на ресурсите.'],
    ],
    // Section 3: Access Control
    accessTitle: 'Контрола на пристап',
    accessSub: 'Прецизна контрола кој што може да гледа и менува.',
    accessItems: [
      ['Улоги: Сопственик, Сметководител, Вработен', 'Рол-базиран пристап со три нивоа — секоја улога има точно дефинирани овластувања за максимална безбедност.'],
      ['Овластувања по компанија', 'Секоја компанија има сопствени поставки за пристап, целосно изолирани од другите компании.'],
      ['Двофакторска автентикација (2FA)', 'Поддршка за двофакторска автентикација за дополнителен слој на заштита при најавување.'],
      ['Управување со сесии', 'Автоматско истекување на сесии по неактивност и можност за прекин на сите активни сесии.'],
    ],
    // Section 4: Audit & Compliance
    auditTitle: 'Ревизија и усогласеност',
    auditSub: 'Целосна трага за секоја активност во системот.',
    auditItems: [
      ['Ревизорска трага за сите дејства', 'Секоја промена во системот се бележи — кој, кога и што променил — за целосна транспарентност.'],
      ['Историја на уредување на фактури', 'Секоја измена на фактура се следи со целосна историја на верзии, овозможувајќи преглед на сите промени.'],
      ['Евидентирање на најави', 'Секоја најава и обид за најава се евидентира, вклучувајќи IP адреса и време на пристап.'],
      ['Извоз на податоци', 'Целосен извоз на вашите податоци во секој момент — во согласност со GDPR правото на преносливост.'],
    ],
    // Section 5: Data Privacy
    privacyTitle: 'Приватност на податоци',
    privacySub: 'Вашите податоци ви припаѓаат вам — ние само ги чуваме безбедно.',
    privacyItems: [
      ['Не ги продаваме вашите податоци', 'Вашите финансиски информации никогаш нема да бидат продадени, споделени или користени за рекламирање.'],
      ['Минимално собирање податоци', 'Собираме само податоци неопходни за функционирање на услугата — ништо повеќе.'],
      ['Политика за приватност', 'Детална и транспарентна политика за приватност достапна на секој јазик.'],
      ['GDPR права', 'Целосна поддршка за вашите GDPR права: пристап, исправка, бришење и преносливост на податоците.'],
    ],
    privacyPolicyLink: 'Прочитајте ја Политиката за приватност',
    // Section 6: Open Source
    openSourceTitle: 'Транспарентност со отворен код',
    openSourceSub: 'Безбедноста преку транспарентност — нашиот код е отворен за инспекција.',
    openSourceItems: [
      ['Базирано на InvoiceShelf (AGPL-3.0)', 'Изградено врз InvoiceShelf, реномирана платформа за фактурирање со отворен код и AGPL-3.0 лиценца.'],
      ['Транспарентност на кодот', 'Нашиот код е достапен за преглед — секој може да провери како ги обработуваме вашите податоци.'],
      ['Ревидирано од заедницата', 'Отворениот код овозможува независна ревизија од глобалната развивачка заедница.'],
    ],
    // Bottom CTA
    ctaTitle: 'Прашања за безбедност?',
    ctaSub: 'Нашиот тим е подготвен да одговори на сите ваши прашања за безбедноста и приватноста на вашите податоци.',
    ctaContact: 'Контактирајте не',
    ctaSignup: 'Започнете бесплатно',
  },
  sq: {
    heroTitle: 'Të dhënat tuaja janë të sigurta me ne',
    heroSub: 'Siguri e nivelit të ndërmarrjes për bizneset maqedonase. Facturino mbron të dhënat tuaja financiare me enkriptim modern, kontroll të rreptë të qasjes dhe transparencë të plotë.',
    dataProtectionTitle: 'Mbrojtja e të dhënave',
    dataProtectionSub: 'Informacionet tuaja financiare janë të enkriptuara në çdo moment.',
    dataProtectionItems: [
      ['Enkriptim AES-256 në pushim', 'Të gjitha të dhënat e ruajtura në serverët tanë mbrohen me enkriptim AES-256 — standardi i industrisë për mbrojtjen e informacioneve të ndjeshme.'],
      ['Enkriptim TLS 1.3 në transit', 'Çdo komunikim mes pajisjes tuaj dhe serverëve tanë enkriptohet me TLS 1.3, protokolli më i ri dhe më i sigurt i transferimit.'],
      ['Kopje rezervë të enkriptuara', 'Kopjet rezervë të bazës së të dhënave janë plotësisht të enkriptuara, duke siguruar mbrojtje edhe në rast të qasjes fizike në serverë.'],
      ['Kopje rezervë automatike ditore', 'Kopje rezervë automatike ditore me mbajtje 30-ditore, për të rikthyer të dhënat tuaja në çdo moment.'],
    ],
    infraTitle: 'Infrastruktura',
    infraSub: 'Infrastrukturë e besueshme dhe e përputhshme në Bashkimin Evropian.',
    infraItems: [
      ['Strehim në rajonin e BE-së', 'Infrastruktura jonë strehohet në Railway në rajonin e BE-së, duke siguruar që të dhënat tuaja qëndrojnë në Evropë.'],
      ['Përputhshmëri me GDPR', 'Përpunimi i të dhënave është plotësisht i përputhshëm me rregulloren GDPR të Bashkimit Evropian.'],
      ['Përditësime të rregullta sigurie', 'Sistemi përditësohet rregullisht me arnimet më të fundit të sigurisë dhe përmirësimet.'],
      ['99.9% kohë pune', 'Synojmë 99.9% disponueshmëri me monitorim 24/7 dhe shkallëzim automatik të burimeve.'],
    ],
    accessTitle: 'Kontrolli i qasjes',
    accessSub: 'Kontroll i saktë se kush çfarë mund të shikojë dhe ndryshojë.',
    accessItems: [
      ['Role: Pronar, Kontabilist, Staf', 'Qasje e bazuar në role me tre nivele — çdo rol ka autorizime të përcaktuara saktësisht për siguri maksimale.'],
      ['Autorizime për kompani', 'Çdo kompani ka cilësimet e veta të qasjes, plotësisht të izoluara nga kompanitë e tjera.'],
      ['Autentikim dy-faktorësh (2FA)', 'Mbështetje për autentikim dy-faktorësh për një shtresë shtesë mbrojtjeje gjatë hyrjes.'],
      ['Menaxhimi i seancave', 'Skadim automatik i seancave pas joaktivitetit dhe mundësi për përfundimin e të gjitha seancave aktive.'],
    ],
    auditTitle: 'Auditi dhe përputhshmëria',
    auditSub: 'Gjurmë e plotë për çdo aktivitet në sistem.',
    auditItems: [
      ['Gjurmë auditi për të gjitha veprimet', 'Çdo ndryshim në sistem regjistrohet — kush, kur dhe çfarë ndryshoi — për transparencë të plotë.'],
      ['Histori e redaktimit të faturave', 'Çdo ndryshim i faturës ndiqet me histori të plotë versionesh, duke mundësuar shikimin e të gjitha ndryshimeve.'],
      ['Regjistrim i hyrjeve', 'Çdo hyrje dhe tentativë hyrjeje regjistrohet, përfshirë adresën IP dhe kohën e qasjes.'],
      ['Eksport i të dhënave', 'Eksport i plotë i të dhënave tuaja në çdo moment — në përputhje me të drejtën e portabilitetit GDPR.'],
    ],
    privacyTitle: 'Privatësia e të dhënave',
    privacySub: 'Të dhënat tuaja ju përkasin juve — ne vetëm i ruajmë në mënyrë të sigurt.',
    privacyItems: [
      ['Nuk shesim të dhënat tuaja', 'Informacionet tuaja financiare kurrë nuk do të shiten, ndahen ose përdoren për reklamim.'],
      ['Mbledhje minimale e të dhënave', 'Mbledhim vetëm të dhënat e nevojshme për funksionimin e shërbimit — asgjë më shumë.'],
      ['Politika e privatësisë', 'Politikë e detajuar dhe transparente e privatësisë e disponueshme në çdo gjuhë.'],
      ['Të drejtat GDPR', 'Mbështetje e plotë për të drejtat tuaja GDPR: qasje, korrigjim, fshirje dhe portabilitet të të dhënave.'],
    ],
    privacyPolicyLink: 'Lexoni Politikën e privatësisë',
    openSourceTitle: 'Transparencë me kod të hapur',
    openSourceSub: 'Siguri përmes transparencës — kodi ynë është i hapur për inspektim.',
    openSourceItems: [
      ['Bazuar në InvoiceShelf (AGPL-3.0)', 'Ndërtuar mbi InvoiceShelf, platformë e njohur e faturimit me kod të hapur dhe licencë AGPL-3.0.'],
      ['Transparencë e kodit', 'Kodi ynë është i disponueshëm për shqyrtim — kushdo mund të verifikojë si i përpunojmë të dhënat tuaja.'],
      ['Audituar nga komuniteti', 'Kodi i hapur mundëson auditim të pavarur nga komuniteti global i zhvilluesve.'],
    ],
    ctaTitle: 'Pyetje për sigurinë?',
    ctaSub: 'Ekipi ynë është gati të përgjigjet për të gjitha pyetjet tuaja rreth sigurisë dhe privatësisë së të dhënave tuaja.',
    ctaContact: 'Na kontaktoni',
    ctaSignup: 'Filloni falas',
  },
  tr: {
    heroTitle: 'Verileriniz bizimle güvende',
    heroSub: 'Makedon isletmeleri icin kurumsal duzeyde guvenlik. Facturino, finansal verilerinizi en modern sifreleme, siki erisim kontrolu ve tam seffaflikla korur.',
    dataProtectionTitle: 'Veri koruma',
    dataProtectionSub: 'Finansal bilgileriniz her an sifrelenmis durumdadir.',
    dataProtectionItems: [
      ['Depoda AES-256 sifreleme', 'Sunucularimizda saklanan tum veriler AES-256 sifreleme ile korunur — hassas bilgilerin korunmasi icin endustri standardi.'],
      ['Aktarimda TLS 1.3 sifreleme', 'Cihaziniz ile sunucularimiz arasindaki her iletisim en yeni ve en guvenli aktarim protokolu olan TLS 1.3 ile sifrelenir.'],
      ['Sifreli yedekler', 'Veritabani yedekleri tamamen sifrelenmistir ve sunuculara fiziksel erisim durumunda bile koruma saglar.'],
      ['Otomatik gunluk yedekleme', '30 gunluk saklama suresiyle otomatik gunluk yedekleme — verilerinizi her an geri yukleyebilirsiniz.'],
    ],
    infraTitle: 'Altyapi',
    infraSub: 'Avrupa Birligi\'nde guvenilir ve uyumlu altyapi.',
    infraItems: [
      ['AB bolgesinde barindirma', 'Altyapimiz Railway uzerinde AB bolgesinde barindiriliyor ve verilerinizin Avrupa\'da kalmasi saglaniyor.'],
      ['GDPR uyumlulugu', 'Veri isleme, Avrupa Birligi\'nin GDPR duzenlemesine tam olarak uygundur.'],
      ['Duzenli guvenlik guncellemeleri', 'Sistem, en son guvenlik yamalari ve iyilestirmelerle duzenli olarak guncellenir.'],
      ['%99,9 calisma suresi', '7/24 izleme ve otomatik kaynak olcekleme ile %99,9 kullanilabilirlik hedefliyoruz.'],
    ],
    accessTitle: 'Erisim kontrolu',
    accessSub: 'Kimin neyi gorebilecegi ve degistirebilecegi uzerinde hassas kontrol.',
    accessItems: [
      ['Roller: Sahip, Muhasebeci, Personel', 'Uc seviyeli role dayali erisim — her rol, maksimum guvenlik icin tam olarak tanimlanmis yetkilere sahiptir.'],
      ['Sirket bazinda yetkiler', 'Her sirketin kendi erisim ayarlari vardir ve diger sirketlerden tamamen izole edilmistir.'],
      ['Iki faktorlu dogrulama (2FA)', 'Giris sirasinda ek bir koruma katmani icin iki faktorlu dogrulama destegi.'],
      ['Oturum yonetimi', 'Etkisizlik sonrasi otomatik oturum sonlandirma ve tum aktif oturumlari sonlandirma olanagi.'],
    ],
    auditTitle: 'Denetim ve uyumluluk',
    auditSub: 'Sistemdeki her aktivite icin eksiksiz iz.',
    auditItems: [
      ['Tum islemler icin denetim izi', 'Sistemdeki her degisiklik kaydedilir — kim, ne zaman ve neyi degistirdi — tam seffaflik icin.'],
      ['Fatura duzenleme gecmisi', 'Her fatura degisikligi, tum degisikliklerin incelenmesini saglayan tam surum gecmisiyle izlenir.'],
      ['Giris kayitlari', 'IP adresi ve erisim zamani dahil olmak uzere her giris ve giris denemesi kaydedilir.'],
      ['Veri disari aktarimi', 'Verilerinizi her an tam olarak disari aktarin — GDPR tasinabilirlik hakkina uygun olarak.'],
    ],
    privacyTitle: 'Veri gizliligi',
    privacySub: 'Verileriniz size aittir — biz sadece onlari guvenle saklariz.',
    privacyItems: [
      ['Verilerinizi satmiyoruz', 'Finansal bilgileriniz asla satilmayacak, paylasilmayacak veya reklamcilik icin kullanilmayacaktir.'],
      ['Minimum veri toplama', 'Yalnizca hizmetin calismasi icin gerekli verileri topluyoruz — fazlasi degil.'],
      ['Gizlilik politikasi', 'Her dilde mevcut ayrintili ve seffaf gizlilik politikasi.'],
      ['GDPR haklari', 'GDPR haklariniz icin tam destek: erisim, duzeltme, silme ve veri tasinabilirligi.'],
    ],
    privacyPolicyLink: 'Gizlilik Politikasini okuyun',
    openSourceTitle: 'Acik kaynakla seffaflik',
    openSourceSub: 'Seffaflik yoluyla guvenlik — kodumuz incelemeye aciktir.',
    openSourceItems: [
      ['InvoiceShelf uzerine kurulu (AGPL-3.0)', 'Acik kaynakli ve AGPL-3.0 lisansli taninmis bir faturalama platformu olan InvoiceShelf uzerine insa edilmistir.'],
      ['Kod seffafligi', 'Kodumuz incelemeye aciktir — herkes verilerinizi nasil isledigimizi dogrulayabilir.'],
      ['Topluluk tarafindan denetlenmis', 'Acik kaynak, kuresel gelistirici toplulugu tarafindan bagimsiz denetime olanak tanir.'],
    ],
    ctaTitle: 'Guvenlik sorulariniz mi var?',
    ctaSub: 'Ekibimiz, verilerinizin guvenligi ve gizliligi hakkindaki tum sorularinizi yanit lamaya hazir.',
    ctaContact: 'Bize ulasin',
    ctaSignup: 'Ucretsiz baslatin',
  },
  en: {
    heroTitle: 'Your data is safe with us',
    heroSub: 'Enterprise-grade security for Macedonian businesses. Facturino protects your financial data with state-of-the-art encryption, strict access controls, and full transparency.',
    dataProtectionTitle: 'Data Protection',
    dataProtectionSub: 'Your financial information is encrypted at every moment.',
    dataProtectionItems: [
      ['AES-256 encryption at rest', 'All data stored on our servers is protected with AES-256 encryption — the industry standard for safeguarding sensitive information.'],
      ['TLS 1.3 encryption in transit', 'Every communication between your device and our servers is encrypted with TLS 1.3, the latest and most secure transfer protocol.'],
      ['Encrypted database backups', 'Database backups are fully encrypted, ensuring protection even in the event of physical server access.'],
      ['Automatic daily backups', 'Automatic daily backups with 30-day retention, so you can restore your data at any moment.'],
    ],
    infraTitle: 'Infrastructure',
    infraSub: 'Reliable and compliant infrastructure in the European Union.',
    infraItems: [
      ['EU region hosting', 'Our infrastructure is hosted on Railway in the EU region, ensuring your data stays in Europe.'],
      ['GDPR compliance', 'Data processing is fully compliant with the European Union\'s GDPR regulation.'],
      ['Regular security updates', 'The system is regularly updated with the latest security patches and improvements.'],
      ['99.9% uptime target', 'We target 99.9% availability with 24/7 monitoring and automatic resource scaling.'],
    ],
    accessTitle: 'Access Control',
    accessSub: 'Precise control over who can view and modify what.',
    accessItems: [
      ['Roles: Owner, Accountant, Staff', 'Role-based access with three levels — each role has precisely defined permissions for maximum security.'],
      ['Per-company permissions', 'Each company has its own access settings, fully isolated from other companies.'],
      ['Two-factor authentication (2FA)', 'Support for two-factor authentication for an additional layer of protection during login.'],
      ['Session management', 'Automatic session expiration after inactivity and the ability to terminate all active sessions.'],
    ],
    auditTitle: 'Audit & Compliance',
    auditSub: 'Complete trail for every activity in the system.',
    auditItems: [
      ['Audit trail for all actions', 'Every change in the system is logged — who, when, and what changed — for complete transparency.'],
      ['Invoice edit history', 'Every invoice modification is tracked with full version history, enabling review of all changes.'],
      ['Login and access logging', 'Every login and login attempt is recorded, including IP address and access time.'],
      ['Data export', 'Full export of your data at any time — in compliance with the GDPR right to data portability.'],
    ],
    privacyTitle: 'Data Privacy',
    privacySub: 'Your data belongs to you — we just keep it safe.',
    privacyItems: [
      ['We never sell your data', 'Your financial information will never be sold, shared, or used for advertising.'],
      ['Minimal data collection', 'We collect only the data necessary for the service to function — nothing more.'],
      ['Privacy Policy', 'Detailed and transparent privacy policy available in every language.'],
      ['GDPR rights', 'Full support for your GDPR rights: access, rectification, erasure, and data portability.'],
    ],
    privacyPolicyLink: 'Read the Privacy Policy',
    openSourceTitle: 'Open Source Transparency',
    openSourceSub: 'Security through transparency — our code is open for inspection.',
    openSourceItems: [
      ['Built on InvoiceShelf (AGPL-3.0)', 'Built on InvoiceShelf, a reputable open-source invoicing platform with an AGPL-3.0 license.'],
      ['Code transparency', 'Our code is available for review — anyone can verify how we process your data.'],
      ['Community audited', 'Open source enables independent auditing by the global developer community.'],
    ],
    ctaTitle: 'Questions about security?',
    ctaSub: 'Our team is ready to answer all your questions about the security and privacy of your data.',
    ctaContact: 'Contact us',
    ctaSignup: 'Start for free',
  },
} as const

const sectionIcons = [
  // Data Protection - shield
  <svg key="dp" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}><path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>,
  // Infrastructure - server
  <svg key="inf" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}><path strokeLinecap="round" strokeLinejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" /></svg>,
  // Access Control - key
  <svg key="ac" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}><path strokeLinecap="round" strokeLinejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>,
  // Audit - clipboard
  <svg key="au" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}><path strokeLinecap="round" strokeLinejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>,
  // Privacy - eye-slash
  <svg key="pr" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}><path strokeLinecap="round" strokeLinejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>,
  // Open Source - code
  <svg key="os" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}><path strokeLinecap="round" strokeLinejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>,
]

const sectionBgs = [
  'bg-white',
  'bg-slate-50',
  'bg-white',
  'bg-slate-50',
  'bg-white',
  'bg-slate-50',
]

const sectionAccents = [
  'from-blue-600 to-indigo-600',
  'from-emerald-600 to-teal-600',
  'from-amber-600 to-orange-600',
  'from-purple-600 to-violet-600',
  'from-rose-600 to-pink-600',
  'from-cyan-600 to-blue-600',
]

export default async function SecurityPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const sections = [
    { title: t.dataProtectionTitle, sub: t.dataProtectionSub, items: t.dataProtectionItems },
    { title: t.infraTitle, sub: t.infraSub, items: t.infraItems },
    { title: t.accessTitle, sub: t.accessSub, items: t.accessItems },
    { title: t.auditTitle, sub: t.auditSub, items: t.auditItems },
    { title: t.privacyTitle, sub: t.privacySub, items: t.privacyItems },
    { title: t.openSourceTitle, sub: t.openSourceSub, items: t.openSourceItems },
  ]

  return (
    <main id="main-content" className="min-h-screen">
      {/* Hero Section */}
      <PageHero
        image="/assets/images/hero_security.png"
        alt="Modern secure server room with blue LED lighting"
        title={t.heroTitle}
        subtitle={t.heroSub}
      />

      {/* Content Sections */}
      {sections.map((section, sectionIdx) => (
        <section key={sectionIdx} className={`py-8 md:py-20 ${sectionBgs[sectionIdx]}`}>
          <div className="container max-w-6xl mx-auto px-4">
            {/* Section header */}
            <div className="flex flex-col items-center text-center mb-6 md:mb-14">
              <div className={`mb-3 md:mb-5 flex h-10 w-10 md:h-14 md:w-14 items-center justify-center rounded-xl bg-gradient-to-br ${sectionAccents[sectionIdx]} text-white shadow-lg`}>
                {sectionIcons[sectionIdx]}
              </div>
              <h2 className="text-xl md:text-3xl font-bold text-gray-900 mb-2 md:mb-3">
                {section.title}
              </h2>
              <p className="text-lg text-gray-500 max-w-2xl">
                {section.sub}
              </p>
            </div>

            {/* Items grid */}
            <div className={`grid grid-cols-1 gap-6 ${section.items.length === 3 ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
              {section.items.map(([title, body], itemIdx) => (
                <div
                  key={itemIdx}
                  className="group relative rounded-xl md:rounded-2xl border border-gray-200 bg-white p-4 md:p-7 shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
                >
                  <div className="flex items-start gap-4">
                    <div className={`mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br ${sectionAccents[sectionIdx]} text-white`}>
                      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                      </svg>
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>
                      <p className="text-sm leading-relaxed text-gray-600">{body}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Privacy Policy link — only in Privacy section */}
            {sectionIdx === 4 && (
              <div className="mt-8 text-center">
                <Link
                  href={`/${locale}/privacy`}
                  className="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
                >
                  {t.privacyPolicyLink}
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                  </svg>
                </Link>
              </div>
            )}
          </div>
        </section>
      ))}

      {/* Bottom CTA */}
      <section className="relative overflow-hidden py-12 md:py-24 bg-slate-900 text-white">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-900 to-slate-900"></div>
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/5 rounded-full blur-3xl"></div>
        </div>

        <div className="container relative z-10 text-center max-w-3xl mx-auto px-4">
          <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20">
            <svg className="w-8 h-8 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
            </svg>
          </div>

          <h2 className="text-3xl md:text-4xl font-bold mb-4">
            {t.ctaTitle}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-2xl mx-auto">
            {t.ctaSub}
          </p>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link
              href={`/${locale}/contact`}
              className="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm px-8 py-4 font-bold text-white transition-all hover:bg-white/20 hover:border-white/50"
            >
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
              </svg>
              {t.ctaContact}
            </Link>
            <Link
              href="https://app.facturino.mk"
              className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 px-8 py-4 font-bold text-white shadow-lg transition-all hover:shadow-indigo-500/30 hover:scale-105"
            >
              {t.ctaSignup}
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </Link>
          </div>
        </div>
      </section>
    </main>
  )
}
