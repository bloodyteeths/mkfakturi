import Link from 'next/link'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import FeatureAccordion from '@/components/FeatureAccordion'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/pregled', {
    title: {
      mk: 'Преглед на функции — Facturino',
      sq: 'Permbledhje e vecorive — Facturino',
      tr: 'Ozellik Onizleme — Facturino',
      en: 'Feature Preview — Facturino',
    },
    description: {
      mk: 'Видете го Facturino во акција пред да се регистрирате. Контролна табла, фактури, банкарство, AI советник и повеќе — со слики од апликацијата.',
      sq: 'Shikoni Facturino ne veprim para se te regjistroheni. Paneli, faturat, bankat, keshilltari AI dhe me shume — me pamje nga aplikacioni.',
      tr: 'Kayit olmadan once Facturino\'yu gorun. Kontrol paneli, faturalar, bankacilik, AI danisman ve dahasi — uygulamadan ekran goruntuleri.',
      en: 'See Facturino in action before you sign up. Dashboard, invoices, banking, AI advisor and more — with real app screenshots.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – mk full, other locales have translated content       */
/* ------------------------------------------------------------------ */

const copy = {
  mk: {
    hero: {
      title: 'Видете го Facturino во акција',
      subtitle: 'Разгледајте ги сите функции со слики од апликацијата — пред да се регистрирате.',
      cta: 'Започни бесплатно',
    },
    groups: [
      {
        title: 'Продажби и фактурирање',
        icon: '\u{1F4B0}',
        features: [
          {
            icon: '\u{1F4CA}',
            title: 'Контролна табла',
            description: 'Централен преглед на вашиот бизнис — приходи, трошоци, неплатени фактури и графикони на еден екран.',
            bullets: [
              'Преглед на приходи и трошоци по месеци',
              'Брз пристап до неплатени фактури',
              'Графикони за финансиски тренд',
              'Последни активности и известувања',
            ],
            screenshots: [{ src: '/assets/screenshots/dashboard.png', alt: 'Контролна табла' }],
            defaultOpen: true,
          },
          {
            icon: '\u{1F465}',
            title: 'Клиенти',
            description: 'Управувајте со вашата база на клиенти — контакт информации, историја на фактури и салдо на еден клик.',
            bullets: [
              'Листа и пребарување на клиенти',
              'Детален профил со историја на плаќања',
              'Увоз/извоз на клиенти',
              'Автоматско пополнување од ЦРРСМ',
            ],
            screenshots: [{ src: '/assets/screenshots/customers.png', alt: 'Листа на клиенти' }],
          },
          {
            icon: '\u{1F4E6}',
            title: 'Ставки (Производи и услуги)',
            description: 'Каталог на сите ваши производи и услуги со цени, ДДВ стапки и единици за мерење.',
            bullets: [
              'Централен каталог на ставки',
              'ДДВ стапки и единици за мерење',
              'Брзо додавање при креирање фактура',
            ],
            screenshots: [{ src: '/assets/screenshots/items.png', alt: 'Ставки' }],
          },
          {
            icon: '\u{1F4DD}',
            title: 'Проценки',
            description: 'Испратете професионални понуди на клиентите и конвертирајте ги во фактури со еден клик.',
            bullets: [
              'Професионален PDF дизајн',
              'Конвертирај во фактура со еден клик',
              'Статуси: испратена, прифатена, одбиена',
            ],
            screenshots: [{ src: '/assets/screenshots/estimates.png', alt: 'Проценки' }],
          },
          {
            icon: '\u{1F4C4}',
            title: 'Профактури',
            description: 'Креирајте профактури за авансни плаќања со автоматско поврзување со финалната фактура.',
            bullets: [
              'Автоматско нумерирање',
              'Поврзување со финална фактура',
              'PDF извоз и испраќање по е-маил',
            ],
            screenshots: [{ src: '/assets/screenshots/proformas.png', alt: 'Профактури' }],
          },
          {
            icon: '\u{1F9FE}',
            title: 'Фактури',
            description: 'Целосно фактурирање — од креирање до испраќање и следење на плаќања. Подготвено за е-Фактура.',
            bullets: [
              'Професионален македонски дизајн',
              'Автоматско пресметување на ДДВ',
              'Испраќање по е-маил директно од системот',
              'Подготвено за е-Фактура (UJP)',
              'QR код за плаќање',
            ],
            screenshots: [
              { src: '/assets/screenshots/invoices.png', alt: 'Листа на фактури' },
              { src: '/assets/screenshots/create-invoice.png', alt: 'Креирање нова фактура' },
            ],
          },
          {
            icon: '\u{1F501}',
            title: 'Повторливи фактури',
            description: 'Автоматизирајте месечни, квартални или годишни фактури — системот ги генерира и испраќа за вас.',
            bullets: [
              'Дефинирај фреквенција и датум',
              'Автоматско генерирање и испраќање',
              'Преглед на претстојни фактури',
            ],
            screenshots: [{ src: '/assets/screenshots/recurring.png', alt: 'Повторливи фактури' }],
          },
          {
            icon: '\u{1F4B3}',
            title: 'Плаќања',
            description: 'Евидентирајте ги сите примени плаќања и следете кои фактури се платени, делумно платени или неплатени.',
            bullets: [
              'Рачно или автоматско евидентирање',
              'Поврзување со фактура',
              'Преглед по датум, клиент или статус',
            ],
            screenshots: [{ src: '/assets/screenshots/payments.png', alt: 'Плаќања' }],
          },
        ],
      },
      {
        title: 'Набавки и трошоци',
        icon: '\u{1F9FE}',
        features: [
          {
            icon: '\u{1F4B8}',
            title: 'Трошоци',
            description: 'Евидентирајте ги сите бизнис трошоци по категории, добавувачи и датуми.',
            bullets: [
              'Категории на трошоци',
              'Прикачување на документи (сметки)',
              'Извештаи по период и категорија',
            ],
            screenshots: [
              { src: '/assets/screenshots/expenses.png', alt: 'Листа на трошоци' },
              { src: '/assets/screenshots/create-expense.png', alt: 'Нов трошок' },
            ],
          },
          {
            icon: '\u{1F3ED}',
            title: 'Добавувачи',
            description: 'Управувајте со добавувачите — контакт информации, историја на набавки и заостанати обврски.',
            bullets: [
              'База на добавувачи со контакт информации',
              'Историја на сметки и плаќања',
              'Увоз од ЦРРСМ',
            ],
            screenshots: [{ src: '/assets/screenshots/suppliers.png', alt: 'Добавувачи' }],
          },
          {
            icon: '\u{1F4C3}',
            title: 'Влезни сметки',
            description: 'Евидентирајте влезни фактури од добавувачи и следете ги роковите за плаќање.',
            bullets: [
              'Евиденција на влезни сметки',
              'Рокови за плаќање и статуси',
              'Поврзување со трошоци',
            ],
            screenshots: [{ src: '/assets/screenshots/bills.png', alt: 'Влезни сметки' }],
          },
          {
            icon: '\u{1F4F7}',
            title: 'Скенирање фискални сметки',
            description: 'Фотографирајте фискална сметка и системот автоматски ги извлекува податоците со OCR технологија.',
            bullets: [
              'OCR препознавање на фискални сметки',
              'Автоматско извлекување на ставки и износи',
              'Директно креирање трошок од скен',
            ],
            screenshots: [{ src: '/assets/screenshots/scanner.png', alt: 'Скенирање' }],
          },
        ],
      },
      {
        title: 'Проекти и магацин',
        icon: '\u{1F4E6}',
        features: [
          {
            icon: '\u{1F4CB}',
            title: 'Проекти',
            description: 'Организирајте ги фактурите и трошоците по проекти за подобро следење на профитабилноста.',
            bullets: [
              'Групирање на приходи и трошоци по проект',
              'Преглед на профитабилност по проект',
              'Временска линија на активности',
            ],
            screenshots: [{ src: '/assets/screenshots/projects.png', alt: 'Проекти' }],
          },
          {
            icon: '\u{1F4E6}',
            title: 'Залиха (Магацин)',
            description: 'Целосно магацинско работење со приемници, издатници, WAC вреднување и известувања за ниски залихи.',
            bullets: [
              'Приемница, издатница, преносница',
              'WAC (пондерирана просечна цена)',
              'Известувања за ниски залихи',
              'Баркод и SKU следење',
            ],
            screenshots: [{ src: '/assets/screenshots/stock.png', alt: 'Залиха' }],
          },
        ],
      },
      {
        title: 'Банкарство, плати и рокови',
        icon: '\u{1F3E6}',
        features: [
          {
            icon: '\u{1F3E6}',
            title: 'Банкарство (PSD2)',
            description: 'Поврзете ги вашите банкарски сметки за автоматски увоз на изводи и полуавтоматско порамнување со фактури.',
            bullets: [
              'PSD2 поврзување со локални банки',
              'Автоматски увоз на трансакции',
              'Полуавтоматско порамнување',
              'CSV/MT940 увоз како алтернатива',
            ],
            screenshots: [{ src: '/assets/screenshots/banking.png', alt: 'Банкарство' }],
          },
          {
            icon: '\u{1F4B0}',
            title: 'Плати',
            description: 'Управувајте со платите на вашите вработени — пресметки, исплати и извештаи.',
            bullets: [
              'Месечни пресметки на плати',
              'Автоматско пресметување на даноци и придонеси',
              'Извештаи по вработен и период',
            ],
            screenshots: [{ src: '/assets/screenshots/payroll.png', alt: 'Плати' }],
          },
          {
            icon: '\u{23F0}',
            title: 'Рокови',
            description: 'Следете ги сите важни даночни и деловни рокови на едно место — без пропуштање.',
            bullets: [
              'Календар на даночни рокови',
              'Автоматски потсетници',
              'Преглед по статус и приоритет',
            ],
            screenshots: [{ src: '/assets/screenshots/deadlines.png', alt: 'Рокови' }],
          },
        ],
      },
      {
        title: 'Вештачка интелигенција',
        icon: '\u{1F916}',
        features: [
          {
            icon: '\u{1F4A1}',
            title: 'AI Инсајти',
            description: 'Автоматска финансиска анализа на вашиот бизнис — ризици, трендови и препораки генерирани од AI.',
            bullets: [
              'Автоматска анализа на приходи и трошоци',
              'Рано предупредување за финансиски ризици',
              '90-дневна прогноза на готовински тек',
              'Анализа на старост на побарувања (AR Aging)',
              'Препораки за оптимизација на профит',
            ],
            screenshots: [{ src: '/assets/screenshots/ai-insights.png', alt: 'AI Инсајти' }],
          },
          {
            icon: '\u{1F4AC}',
            title: 'AI Помошник',
            description: 'Прашајте го AI помошникот било што за вашиот бизнис на македонски — и добијте конкретни одговори базирани на вашите податоци.',
            bullets: [
              '"Кој клиент ми должи најмногу?"',
              '"Дали сум профитабилен овој месец?"',
              '"Што ако го изгубам најголемиот клиент?"',
              '"Како да го зголемам профитот?"',
              'Одговори на македонски јазик',
            ],
            screenshots: [{ src: '/assets/screenshots/ai-chat.png', alt: 'AI Помошник' }],
          },
        ],
      },
      {
        title: 'Извештаи и подесувања',
        icon: '\u{1F4C8}',
        features: [
          {
            icon: '\u{1F4CA}',
            title: 'Извештаи',
            description: 'Професионални финансиски извештаи — биланс, добивка/загуба, готовински тек и повеќе.',
            bullets: [
              'Биланс на состојба (IFRS)',
              'Извештај за добивка и загуба',
              'Извештај за готовински тек',
              'Извоз во Excel и PDF',
            ],
            screenshots: [{ src: '/assets/screenshots/reports.png', alt: 'Извештаи' }],
          },
          {
            icon: '\u{2699}\u{FE0F}',
            title: 'Подесувања',
            description: 'Прилагодете го системот на вашите потреби — компаниски информации, ДДВ, шаблони и интеграции.',
            bullets: [
              'Компаниски информации и лого',
              'ДДВ стапки и даночни правила',
              'Прилагодени PDF шаблони',
              'Кориснички улоги и овластувања',
            ],
            screenshots: [{ src: '/assets/screenshots/settings.png', alt: 'Подесувања' }],
          },
          {
            icon: '\u{1F4DC}',
            title: 'Е-Фактура',
            description: 'Подготвени за е-Фактура — структурирани податоци за UJP, QES потпис и автоматско испраќање.',
            bullets: [
              'Структурирани податоци за UJP',
              'QES дигитален потпис',
              'Автоматско испраќање и следење',
              'Подготвено за продукциски API',
            ],
            screenshots: [{ src: '/assets/screenshots/efaktura.png', alt: 'Е-Фактура' }],
          },
        ],
      },
    ],
    bottomCta: {
      title: 'Сакате да го пробате?',
      sub: 'Без кредитна картичка \u2022 14 дена бесплатно \u2022 Откажете во секое време',
      button: 'Започни бесплатно',
    },
  },
  sq: {
    hero: {
      title: 'Shikoni Facturino ne veprim',
      subtitle: 'Eksploroni te gjitha vecorite me pamje nga aplikacioni — para se te regjistroheni.',
      cta: 'Fillo falas',
    },
    groups: [
      { title: 'Shitjet dhe faturimi', icon: '\u{1F4B0}' },
      { title: 'Blerjet dhe shpenzimet', icon: '\u{1F9FE}' },
      { title: 'Projektet dhe magazina', icon: '\u{1F4E6}' },
      { title: 'Bankat, pagat dhe afatet', icon: '\u{1F3E6}' },
      { title: 'Inteligjenca artificiale', icon: '\u{1F916}' },
      { title: 'Raportet dhe rregullimet', icon: '\u{1F4C8}' },
    ],
    bottomCta: {
      title: 'Deshironi ta provoni?',
      sub: 'Pa karte krediti \u2022 14 dite falas \u2022 Anuloni ne cdo kohe',
      button: 'Fillo falas',
    },
  },
  tr: {
    hero: {
      title: 'Facturino\'yu calısırken gorun',
      subtitle: 'Kayıt olmadan once tum ozellikleri uygulama ekran goruntuleriyle keşfedin.',
      cta: 'Ucretsiz basla',
    },
    groups: [
      { title: 'Satıs ve faturalama', icon: '\u{1F4B0}' },
      { title: 'Satın alma ve giderler', icon: '\u{1F9FE}' },
      { title: 'Projeler ve depo', icon: '\u{1F4E6}' },
      { title: 'Bankacılık, maaslar ve sureler', icon: '\u{1F3E6}' },
      { title: 'Yapay zeka', icon: '\u{1F916}' },
      { title: 'Raporlar ve ayarlar', icon: '\u{1F4C8}' },
    ],
    bottomCta: {
      title: 'Denemek ister misiniz?',
      sub: 'Kredi kartı gerekmez \u2022 14 gun ucretsiz \u2022 Istediginiz zaman iptal edin',
      button: 'Ucretsiz basla',
    },
  },
  en: {
    hero: {
      title: 'See Facturino in action',
      subtitle: 'Explore all features with real app screenshots — before you sign up.',
      cta: 'Start Free',
    },
    groups: [
      { title: 'Sales & Invoicing', icon: '\u{1F4B0}' },
      { title: 'Purchases & Expenses', icon: '\u{1F9FE}' },
      { title: 'Projects & Inventory', icon: '\u{1F4E6}' },
      { title: 'Banking, Payroll & Deadlines', icon: '\u{1F3E6}' },
      { title: 'Artificial Intelligence', icon: '\u{1F916}' },
      { title: 'Reports & Settings', icon: '\u{1F4C8}' },
    ],
    bottomCta: {
      title: 'Want to try it?',
      sub: 'No credit card \u2022 14 days free \u2022 Cancel anytime',
      button: 'Start Free',
    },
  },
} as const

// Use mk data as the canonical feature source for all locales
const mkData = copy.mk

/* ------------------------------------------------------------------ */
/*  Page Component                                                     */
/* ------------------------------------------------------------------ */

export default async function PregledPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]
  const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

  return (
    <main id="main-content">
      {/* ============================================================ */}
      {/*  HERO                                                        */}
      {/* ============================================================ */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[var(--color-primary)] to-[#1a2e5a] py-20 text-white md:py-28">
        <div className="pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-white/5" />
        <div className="pointer-events-none absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-white/5" />

        <div className="container relative mx-auto max-w-4xl px-4 text-center">
          <h1 className="mb-6 text-4xl font-extrabold leading-tight md:text-5xl lg:text-6xl">
            {t.hero.title}
          </h1>
          <p className="mx-auto mb-10 max-w-2xl text-lg text-white/80 md:text-xl">
            {t.hero.subtitle}
          </p>
          <a
            href={`${APP_URL}/signup`}
            className="inline-block rounded-lg bg-white px-8 py-4 text-lg font-bold text-[var(--color-primary)] shadow-lg transition hover:scale-105 hover:shadow-xl"
          >
            {t.hero.cta}
          </a>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  FEATURE GROUPS                                               */}
      {/* ============================================================ */}
      <div className="container mx-auto max-w-4xl px-4 py-16 md:py-20">
        {mkData.groups.map((group, gi) => {
          const groupHeader = t.groups[gi] || { title: group.title, icon: group.icon }

          return (
            <section key={gi} className="mb-16 last:mb-0">
              {/* Group header */}
              <div className="mb-8 flex items-center gap-3">
                <span className="text-3xl">{groupHeader.icon}</span>
                <h2 className="text-2xl font-extrabold text-gray-900 md:text-3xl">
                  {groupHeader.title}
                </h2>
              </div>

              {/* Accordion list */}
              <div className="space-y-4">
                {group.features.map((feature, fi) => (
                  <FeatureAccordion
                    key={fi}
                    icon={feature.icon}
                    title={feature.title}
                    description={feature.description}
                    bullets={feature.bullets}
                    screenshots={feature.screenshots}
                    defaultOpen={'defaultOpen' in feature ? feature.defaultOpen : undefined}
                    ctaText={t.hero.cta}
                    ctaHref={`${APP_URL}/signup`}
                  />
                ))}
              </div>
            </section>
          )
        })}
      </div>

      {/* ============================================================ */}
      {/*  BOTTOM CTA                                                  */}
      {/* ============================================================ */}
      <section className="bg-gray-50 py-16 md:py-20">
        <div className="container mx-auto max-w-3xl px-4 text-center">
          <h2 className="mb-4 text-3xl font-extrabold text-gray-900 md:text-4xl">
            {t.bottomCta.title}
          </h2>
          <p className="mb-8 text-gray-500">
            {t.bottomCta.sub}
          </p>
          <a
            href={`${APP_URL}/signup`}
            className="btn-primary inline-block px-10 py-4 text-lg font-bold"
          >
            {t.bottomCta.button}
          </a>
        </div>
      </section>
    </main>
  )
}
