import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import Link from 'next/link'

const copy = {
  mk: {
    h1: 'Цени',
    sub: '14‑дневен бесплатен пробен период. Без обврска.',
    sectionCompany: 'За компании',
    sectionPartner: 'За сметководители (партнери)',
    popularBadge: 'Популарно',
    recommendedBadge: 'Препорачано',
    companyPlans: [
      { name: 'Free', price: '€0', period: '/засекогаш', bullets: ['5 фактури/месец', '1 корисник', 'PDF извоз', 'Основни шаблони'] },
      { name: 'Starter', price: '€12', period: '/месец', bullets: ['50 фактури/месец', '1 корисник', 'Неограничено клиенти', 'Email фактури'] },
      { name: 'Standard', price: '€29', period: '/месец', bullets: ['200 фактури/месец', '3 корисници', 'Е‑Фактура испраќање', 'QES потпис', 'AI увоз'], popular: true },
      { name: 'Business', price: '€59', period: '/месец', bullets: ['1000 фактури/месец', '5 корисници', 'Банкарски изводи', 'Авто-категоризација', 'Full AI увоз'] },
      { name: 'Max', price: '€149', period: '/месец', bullets: ['Се неограничено', 'API пристап', 'Мулти-локации', 'IFRS извештаи', 'WhatsApp поддршка'] }
    ],
    partnerPlans: [
      { name: 'Partner', price: 'Бесплатно', period: '', bullets: ['Неограничено клиенти', 'Партнер портал', '20% рекурентна провизија', 'Следење на заработка'], popular: true },
      { name: 'Partner Plus', price: '€29', period: '/месец', bullets: ['Сè од Partner', 'Фактурирање за канцеларија', 'Напредни извештаи', '22% провизија', 'Приоритетна поддршка'] }
    ],
    cta: 'Започни сега',
    ctaPartner: 'Придружи се'
  },
  sq: {
    h1: 'Çmimet',
    sub: 'Provë falas 14 ditë. Pa detyrim.',
    sectionCompany: 'Për kompani',
    sectionPartner: 'Për kontabilistë (partnerë)',
    popularBadge: 'Popullor',
    recommendedBadge: 'I rekomanduar',
    companyPlans: [
      { name: 'Free', price: '€0', period: '/përgjithmonë', bullets: ['5 fatura/muaj', '1 përdorues', 'Eksport PDF', 'Shabllone bazë'] },
      { name: 'Starter', price: '€12', period: '/muaj', bullets: ['50 fatura/muaj', '1 përdorues', 'Klientë të pakufizuar', 'Email fatura'] },
      { name: 'Standard', price: '€29', period: '/muaj', bullets: ['200 fatura/muaj', '3 përdorues', 'Dërgim e‑Faturë', 'Nënshkrim QES', 'Import AI'], popular: true },
      { name: 'Business', price: '€59', period: '/muaj', bullets: ['1000 fatura/muaj', '5 përdorues', 'Ekstrakte bankare', 'Auto-kategorizim', 'Import AI i plotë'] },
      { name: 'Max', price: '€149', period: '/muaj', bullets: ['Çdo gjë e pakufizuar', 'Qasje API', 'Multi-lokacione', 'Raporte IFRS', 'Suport WhatsApp'] }
    ],
    partnerPlans: [
      { name: 'Partner', price: 'Falas', period: '', bullets: ['Klientë të pakufizuar', 'Portal partneri', 'Komision 20% rekurent', 'Ndjekje fitimesh'], popular: true },
      { name: 'Partner Plus', price: '€29', period: '/muaj', bullets: ['Gjithçka nga Partner', 'Faturim për zyrë', 'Raporte të avancuara', 'Komision 22%', 'Suport prioritar'] }
    ],
    cta: 'Fillo tani',
    ctaPartner: 'Bashkohu'
  },
  tr: {
    h1: 'Fiyatlar',
    sub: '14 gün ücretsiz deneme. Taahhüt yok.',
    sectionCompany: 'Şirketler için',
    sectionPartner: 'Muhasebeciler için (iş ortakları)',
    popularBadge: 'Popüler',
    recommendedBadge: 'Önerilen',
    companyPlans: [
      { name: 'Free', price: '€0', period: '/süresiz', bullets: ['5 fatura/ay', '1 kullanıcı', 'PDF dışa aktarma', 'Temel şablonlar'] },
      { name: 'Starter', price: '€12', period: '/ay', bullets: ['50 fatura/ay', '1 kullanıcı', 'Sınırsız müşteri', 'Email fatura'] },
      { name: 'Standard', price: '€29', period: '/ay', bullets: ['200 fatura/ay', '3 kullanıcı', 'e‑Fatura gönderim', 'QES imza', 'AI içe aktarma'], popular: true },
      { name: 'Business', price: '€59', period: '/ay', bullets: ['1000 fatura/ay', '5 kullanıcı', 'Banka ekstreleri', 'Otomatik kategorizasyon', 'Tam AI içe aktarma'] },
      { name: 'Max', price: '€149', period: '/ay', bullets: ['Her şey sınırsız', 'API erişimi', 'Çoklu lokasyon', 'IFRS raporları', 'WhatsApp destek'] }
    ],
    partnerPlans: [
      { name: 'Partner', price: 'Ücretsiz', period: '', bullets: ['Sınırsız müşteri', 'Partner portalı', '%20 tekrarlayan komisyon', 'Kazanç takibi'], popular: true },
      { name: 'Partner Plus', price: '€29', period: '/ay', bullets: ['Partner\'ın tümü', 'Ofis için faturalama', 'Gelişmiş raporlar', '%22 komisyon', 'Öncelikli destek'] }
    ],
    cta: 'Şimdi başla',
    ctaPartner: 'Katıl'
  }
} as const

export default function PricingPage({ params }: { params: { locale: string } }) {
  const locale: Locale = isLocale(params.locale) ? (params.locale as Locale) : defaultLocale
  const t = copy[locale]
  return (
    <main className="section">
      <div className="container">
        <h1 className="mb-2 text-3xl font-bold" style={{color:'var(--color-primary)'}}>{t.h1}</h1>
        <p className="mb-12 text-[color:var(--color-muted)]">{t.sub}</p>

        {/* Company Pricing */}
        <div className="mb-16">
          <h2 className="mb-6 text-2xl font-bold">{t.sectionCompany}</h2>
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            {t.companyPlans.map((p, i) => (
              <div key={i} className={`card relative flex flex-col ${p.popular ? 'ring-2 ring-blue-500' : ''}`}>
                {p.popular && (
                  <div className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-blue-500 px-3 py-1 text-xs font-semibold text-white whitespace-nowrap">
                    {t.popularBadge}
                  </div>
                )}
                <h3 className="mb-2 text-xl font-semibold">{p.name}</h3>
                <div className="mb-1 text-3xl font-bold">{p.price}</div>
                <div className="mb-4 text-sm text-gray-500 h-5">{p.period}</div>
                <ul className="mb-6 space-y-2 text-sm text-gray-700 flex-grow">
                  {p.bullets.map((b, j) => (
                    <li key={j} className="flex items-start">
                      <span className="mr-2 text-green-500 flex-shrink-0">✓</span>
                      <span>{b}</span>
                    </li>
                  ))}
                </ul>
                <Link href={`/${locale}/contact`} className="btn-primary w-full text-center">
                  {t.cta}
                </Link>
              </div>
            ))}
          </div>
        </div>

        {/* Partner Pricing */}
        <div>
          <h2 className="mb-6 text-2xl font-bold">{t.sectionPartner}</h2>
          <div className="grid grid-cols-1 gap-6 md:grid-cols-2 max-w-3xl mx-auto">
            {t.partnerPlans.map((p, i) => (
              <div key={i} className={`card relative flex flex-col ${p.popular ? 'ring-2 ring-green-500' : ''}`}>
                {p.popular && (
                  <div className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-green-500 px-3 py-1 text-xs font-semibold text-white whitespace-nowrap">
                    {t.recommendedBadge}
                  </div>
                )}
                <h3 className="mb-2 text-xl font-semibold">{p.name}</h3>
                <div className="mb-1 text-3xl font-bold">{p.price}</div>
                <div className="mb-4 text-sm text-gray-500 h-5">{p.period}</div>
                <ul className="mb-6 space-y-2 text-sm text-gray-700 flex-grow">
                  {p.bullets.map((b, j) => (
                    <li key={j} className="flex items-start">
                      <span className="mr-2 text-green-500 flex-shrink-0">✓</span>
                      <span>{b}</span>
                    </li>
                  ))}
                </ul>
                <Link href={`/${locale}/contact`} className="btn-primary w-full text-center">
                  {t.ctaPartner}
                </Link>
              </div>
            ))}
          </div>
        </div>
      </div>
    </main>
  )
}

