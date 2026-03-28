import Image from 'next/image'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/pos-softver-makedonija', {
    title: {
      mk: 'Најдобар POS софтвер за малопродажба во Македонија 2026 \u2014 споредба | Facturino',
      sq: 'Softueri me i mire POS per shitje me pakice ne Maqedoni 2026 \u2014 krahasim | Facturino',
      tr: 'Makedonya\'da en iyi perakende POS yazilimi 2026 \u2014 karsilastirma | Facturino',
      en: 'Best POS Software for Retail in Macedonia 2026 \u2014 Comparison | Facturino',
    },
    description: {
      mk: 'Споредба на POS софтвер за Македонија: Vector, Accent, Jongis, PANTHEON и Facturino. Цени, фискален печатач, залиха, книжење и е-Фактура \u2014 кој е најдобар за малопродажба?',
      sq: 'Krahasim i softuerit POS per Maqedoni: Vector, Accent, Jongis, PANTHEON dhe Facturino. Cmimet, printer fiskal, stok, regjistrim dhe e-Fature.',
      tr: 'Makedonya POS yazilimi karsilastirmasi: Vector, Accent, Jongis, PANTHEON ve Facturino. Fiyat, fiskal yazici, stok, muhasebe ve e-Fatura.',
      en: 'POS software comparison for Macedonia: Vector, Accent, Jongis, PANTHEON and Facturino. Pricing, fiscal printer, inventory, accounting and e-Invoice.',
    },
  })
}

const copy = {
  mk: {
    backLink: '\u2190 \u041d\u0430\u0437\u0430\u0434 \u043a\u043e\u043d \u0431\u043b\u043e\u0433',
    tag: '\u0421\u043f\u043e\u0440\u0435\u0434\u0431\u0430',
    title: '\u041d\u0430\u0458\u0434\u043e\u0431\u0430\u0440 POS \u0441\u043e\u0444\u0442\u0432\u0435\u0440 \u0437\u0430 \u043c\u0430\u043b\u043e\u043f\u0440\u043e\u0434\u0430\u0436\u0431\u0430 \u0432\u043e \u041c\u0430\u043a\u0435\u0434\u043e\u043d\u0438\u0458\u0430 2026',
    publishDate: '28 \u043c\u0430\u0440\u0442 2026',
    readTime: '10 \u043c\u0438\u043d \u0447\u0438\u0442\u0430\u045a\u0435',
    intro:
      '\u0410\u043a\u043e \u0438\u043c\u0430\u0442\u0435 \u043f\u0440\u043e\u0434\u0430\u0432\u043d\u0438\u0446\u0430, \u043a\u0430\u0444\u0443\u043b\u0435, \u043a\u0438\u043e\u0441\u043a \u0438\u043b\u0438 \u043c\u0430\u043b \u0431\u0438\u0437\u043d\u0438\u0441 \u0432\u043e \u041c\u0430\u043a\u0435\u0434\u043e\u043d\u0438\u0458\u0430, \u0437\u043d\u0430\u0435\u0442\u0435 \u043a\u043e\u043b\u043a\u0443 \u0435 \u0442\u0435\u0448\u043a\u043e \u0434\u0430 \u043d\u0430\u0458\u0434\u0435\u0442\u0435 POS \u0441\u043e\u0444\u0442\u0432\u0435\u0440 \u0448\u0442\u043e \u0440\u0430\u0431\u043e\u0442\u0438 \u0441\u043e \u043c\u0430\u043a\u0435\u0434\u043e\u043d\u0441\u043a\u0438 \u0444\u0438\u0441\u043a\u0430\u043b\u0435\u043d \u043f\u0435\u0447\u0430\u0442\u0430\u0447, \u0433\u0438 \u0432\u043e\u0434\u0438 \u0437\u0430\u043b\u0438\u0445\u0438\u0442\u0435 \u0438 \u043a\u043d\u0438\u0436\u0438 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u0438. \u0412\u043e \u043e\u0432\u043e\u0458 \u0432\u043e\u0434\u0438\u0447 \u0433\u0438 \u0441\u043f\u043e\u0440\u0435\u0434\u0443\u0432\u0430\u043c\u0435 \u0441\u0438\u0442\u0435 POS \u0440\u0435\u0448\u0435\u043d\u0438\u0458\u0430 \u0434\u043e\u0441\u0442\u0430\u043f\u043d\u0438 \u0432\u043e \u041c\u0430\u043a\u0435\u0434\u043e\u043d\u0438\u0458\u0430 \u2014 \u0446\u0435\u043d\u0438, \u0444\u0443\u043d\u043a\u0446\u0438\u0438, \u043f\u0440\u0435\u0434\u043d\u043e\u0441\u0442\u0438 \u0438 \u043d\u0435\u0434\u043e\u0441\u0442\u0430\u0442\u043e\u0446\u0438.',
    sections: [
      {
        title: '\u0417\u043e\u0448\u0442\u043e \u0432\u0438 \u0442\u0440\u0435\u0431\u0430 POS \u0441\u043e\u0444\u0442\u0432\u0435\u0440 \u0432\u043e 2026?',
        content:
          '\u0412\u043e \u041c\u0430\u043a\u0435\u0434\u043e\u043d\u0438\u0458\u0430, 80% \u043e\u0434 \u043c\u0430\u043b\u0438\u0442\u0435 \u0431\u0438\u0437\u043d\u0438\u0441\u0438 \u043d\u0435\u043c\u0430\u0430\u0442 \u043f\u043e\u0432\u0440\u0437\u0430\u043d\u043e\u0441\u0442 \u043c\u0435\u0453\u0443 \u043a\u0430\u0441\u0430\u0442\u0430, \u0437\u0430\u043b\u0438\u0445\u0430\u0442\u0430 \u0438 \u043a\u043d\u0438\u0433\u043e\u0432\u043e\u0434\u0441\u0442\u0432\u043e\u0442\u043e. \u041a\u0430\u0441\u0438\u0435\u0440\u043e\u0442 \u043f\u0440\u043e\u0434\u0430\u0432\u0430 \u043d\u0430 \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u0430\u0442\u0430 \u043a\u0430\u0441\u0430, \u0430 \u0441\u043c\u0435\u0442\u043a\u043e\u0432\u043e\u0434\u0438\u0442\u0435\u043b\u043e\u0442 \u0433\u0438 \u043f\u0440\u0435\u043f\u0438\u0448\u0443\u0432\u0430 Z-\u0438\u0437\u0432\u0435\u0448\u0442\u0430\u0438\u0442\u0435 \u0440\u0430\u0447\u043d\u043e. \u0421\u043e \u0435-\u0424\u0430\u043a\u0442\u0443\u0440\u0430 \u0437\u0430\u0434\u043e\u043b\u0436\u0438\u0442\u0435\u043b\u043d\u0430 \u043e\u0434 \u041e\u043a\u0442\u043e\u043c\u0432\u0440\u0438 2026, \u0441\u0435\u043a\u043e\u0458 \u0431\u0438\u0437\u043d\u0438\u0441 \u043c\u043e\u0440\u0430 \u0434\u0430 \u0441\u0435 \u0434\u0438\u0433\u0438\u0442\u0430\u043b\u0438\u0437\u0438\u0440\u0430. \u041f\u0440\u0430\u0448\u0430\u045a\u0435\u0442\u043e \u043d\u0435 \u0435 \u0434\u0430\u043b\u0438, \u0442\u0443\u043a\u0443 \u043a\u043e\u0458 POS \u0434\u0430 \u0433\u043e \u0438\u0437\u0431\u0435\u0440\u0435\u0442\u0435.',
        items: null,
        steps: null,
      },
      {
        title: '1. Duna Vector \u2014 \u0434\u0435\u0441\u043a\u0442\u043e\u043f \u043a\u043b\u0430\u0441\u0438\u043a\u0430 (300\u20AC)',
        content:
          'Vector \u0435 \u043d\u0430\u0458\u0440\u0430\u0441\u043f\u0440\u043e\u0441\u0442\u0440\u0430\u043d\u0435\u0442\u0438\u043e\u0442 POS \u0441\u043e\u0444\u0442\u0432\u0435\u0440 \u0432\u043e \u041c\u0430\u043a\u0435\u0434\u043e\u043d\u0438\u0458\u0430, \u043a\u043e\u0440\u0438\u0441\u0442\u0435\u043d \u043e\u0434 \u0438\u043b\u0458\u0430\u0434\u043d\u0438\u0446\u0438 \u043f\u0440\u043e\u0434\u0430\u0432\u043d\u0438\u0446\u0438. \u0427\u0438\u043d\u0438 \u043e\u043a\u043e\u043b\u0443 300\u20AC \u0435\u0434\u043d\u043e\u043a\u0440\u0430\u0442\u043d\u043e \u043f\u043b\u0443\u0441 50-100\u20AC \u0437\u0430 \u0438\u043d\u0441\u0442\u0430\u043b\u0430\u0446\u0438\u0458\u0430 \u043e\u0434 \u0442\u0435\u0445\u043d\u0438\u0447\u0430\u0440.',
        items: [
          '\u2705 \u041f\u043e\u0434\u0434\u0440\u0436\u0443\u0432\u0430 \u043c\u0430\u043a\u0435\u0434\u043e\u043d\u0441\u043a\u0438 \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u0438 \u043f\u0435\u0447\u0430\u0442\u0430\u0447\u0438 \u043f\u0440\u0435\u043a\u0443 COM \u043f\u043e\u0440\u0442',
          '\u2705 \u041e\u0441\u043d\u043e\u0432\u043d\u0430 \u0437\u0430\u043b\u0438\u0445\u0430 \u0438 \u043a\u0430\u0442\u0435\u0433\u043e\u0440\u0438\u0438 \u043d\u0430 \u0430\u0440\u0442\u0438\u043a\u043b\u0438',
          '\u274C \u0421\u0430\u043c\u043e Windows \u2014 \u043d\u0435 \u0440\u0430\u0431\u043e\u0442\u0438 \u043d\u0430 \u0442\u0430\u0431\u043b\u0435\u0442',
          '\u274C \u041d\u0435\u043c\u0430 cloud \u043f\u0440\u0438\u0441\u0442\u0430\u043f \u2014 \u043f\u043e\u0434\u0430\u0442\u043e\u0446\u0438\u0442\u0435 \u0441\u0435 \u043d\u0430 \u0435\u0434\u0435\u043d \u043a\u043e\u043c\u043f\u0458\u0443\u0442\u0435\u0440',
          '\u274C \u041d\u0435\u043c\u0430 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u043e \u043a\u043d\u0438\u0436\u0435\u045a\u0435 \u2014 \u0441\u043c\u0435\u0442\u043a\u043e\u0432\u043e\u0434\u0438\u0442\u0435\u043b\u043e\u0442 \u043f\u0440\u0435\u043f\u0438\u0448\u0443\u0432\u0430 \u0440\u0430\u0447\u043d\u043e',
          '\u274C \u041d\u0435 \u0435 \u043f\u043e\u0434\u0433\u043e\u0442\u0432\u0435\u043d \u0437\u0430 \u0435-\u0424\u0430\u043a\u0442\u0443\u0440\u0430',
        ],
        steps: null,
      },
      {
        title: '2. Accent \u2014 \u0441\u043b\u0438\u0447\u043d\u0430 \u043f\u0440\u0438\u043a\u0430\u0437\u043a\u0430 (~200\u20AC)',
        content:
          'Accent \u0435 \u0434\u0440\u0443\u0433 \u043f\u043e\u043f\u0443\u043b\u0430\u0440\u0435\u043d \u0434\u0435\u0441\u043a\u0442\u043e\u043f POS, \u0441\u043b\u0438\u0447\u0435\u043d \u043d\u0430 Vector \u043f\u043e \u043a\u043e\u043d\u0446\u0435\u043f\u0442. \u041f\u043e\u043d\u0443\u0434\u0443\u0432\u0430 \u0437\u0430\u043b\u0438\u0445\u0430 \u0438 \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u043e \u043f\u0435\u0447\u0430\u0442\u0435\u045a\u0435, \u043d\u043e \u0441\u043e \u0438\u0441\u0442\u0438\u0442\u0435 \u043e\u0433\u0440\u0430\u043d\u0438\u0447\u0443\u0432\u0430\u045a\u0430.',
        items: [
          '\u2705 \u0414\u0435\u043b\u0443\u043c\u043d\u0430 \u0437\u0430\u043b\u0438\u0445\u0430 \u0438 \u043a\u0430\u0442\u0435\u0433\u043e\u0440\u0438\u0438',
          '\u2705 \u0424\u0438\u0441\u043a\u0430\u043b\u043d\u043e \u043f\u0435\u0447\u0430\u0442\u0435\u045a\u0435 \u043f\u0440\u0435\u043a\u0443 COM \u043f\u043e\u0440\u0442',
          '\u274C Windows-only, \u043d\u0435\u043c\u0430 cloud, \u043d\u0435\u043c\u0430 \u0442\u0430\u0431\u043b\u0435\u0442',
          '\u274C \u041d\u0435\u043c\u0430 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u043e \u043a\u043d\u0438\u0436\u0435\u045a\u0435',
          '\u274C \u041d\u0435 \u0435 \u043f\u043e\u0434\u0433\u043e\u0442\u0432\u0435\u043d \u0437\u0430 \u0435-\u0424\u0430\u043a\u0442\u0443\u0440\u0430',
        ],
        steps: null,
      },
      {
        title: '3. PANTHEON \u2014 \u043f\u043e\u043b\u043d ERP (500-1,200\u20AC/\u0433\u043e\u0434)',
        content:
          'PANTHEON \u043e\u0434 Datalab \u0435 \u0446\u0435\u043b\u043e\u0441\u0435\u043d ERP \u0441\u0438\u0441\u0442\u0435\u043c \u043a\u043e\u0458 \u0432\u043a\u043b\u0443\u0447\u0443\u0432\u0430 POS, \u043a\u043d\u0438\u0433\u043e\u0432\u043e\u0434\u0441\u0442\u0432\u043e \u0438 \u0437\u0430\u043b\u0438\u0445\u0430. \u041d\u043e \u0435 \u0434\u0438\u0437\u0430\u0458\u043d\u0438\u0440\u0430\u043d \u0437\u0430 \u043f\u043e\u0433\u043e\u043b\u0435\u043c\u0438 \u043a\u043e\u043c\u043f\u0430\u043d\u0438\u0438.',
        items: [
          '\u2705 \u0418\u043d\u0442\u0435\u0433\u0440\u0438\u0440\u0430\u043d\u043e \u043a\u043d\u0438\u0433\u043e\u0432\u043e\u0434\u0441\u0442\u0432\u043e \u0438 \u0437\u0430\u043b\u0438\u0445\u0430',
          '\u2705 \u041e\u043f\u0446\u0438\u043e\u043d\u0430\u043b\u043d\u043e cloud \u0440\u0435\u0448\u0435\u043d\u0438\u0435',
          '\u274C \u0421\u043a\u0430\u043f\u043e \u2014 500-1,200\u20AC/\u0433\u043e\u0434\u0438\u043d\u0430',
          '\u274C \u0422\u0435\u0445\u043d\u0438\u0447\u0430\u0440 \u0437\u0430 \u0438\u043d\u0441\u0442\u0430\u043b\u0430\u0446\u0438\u0458\u0430 \u0438 \u043e\u0434\u0440\u0436\u0443\u0432\u0430\u045a\u0435',
          '\u274C \u041a\u043e\u043c\u043f\u043b\u0435\u043a\u0441\u0435\u043d \u0437\u0430 \u043c\u0430\u043b\u0438 \u0431\u0438\u0437\u043d\u0438\u0441\u0438',
          '\u274C \u041d\u0435 \u0435 \u043f\u043e\u0434\u0433\u043e\u0442\u0432\u0435\u043d \u0437\u0430 \u0435-\u0424\u0430\u043a\u0442\u0443\u0440\u0430',
        ],
        steps: null,
      },
      {
        title: '4. Facturino POS \u2014 \u0431\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e, cloud, \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u043e',
        content:
          'Facturino POS \u0435 \u043d\u043e\u0432\u043e \u0440\u0435\u0448\u0435\u043d\u0438\u0435 \u0448\u0442\u043e \u0433\u043e \u043c\u0435\u043d\u0443\u0432\u0430 \u043f\u0440\u0430\u0432\u0438\u043b\u043e\u0442\u043e \u043d\u0430 \u0438\u0433\u0440\u0430\u0442\u0430. \u0420\u0430\u0431\u043e\u0442\u0438 \u0432\u043e Chrome \u043d\u0430 \u0431\u0438\u043b\u043e \u043a\u043e\u0458 \u0443\u0440\u0435\u0434, \u0431\u0435\u0437 \u0438\u043d\u0441\u0442\u0430\u043b\u0430\u0446\u0438\u0458\u0430. \u0421\u0435\u043a\u043e\u0458\u0430 \u043f\u0440\u043e\u0434\u0430\u0436\u0431\u0430 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u0438 \u0441\u0435 \u043a\u043d\u0438\u0436\u0438 \u0432\u043e IFRS, \u0433\u043e \u043e\u0434\u0437\u0435\u043c\u0430 \u0430\u0440\u0442\u0438\u043a\u043b\u043e\u0442 \u043e\u0434 \u0437\u0430\u043b\u0438\u0445\u0430 \u0438 \u043f\u0435\u0447\u0430\u0442\u0438 \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u0430 \u0441\u043c\u0435\u0442\u043a\u0430 \u043f\u0440\u0435\u043a\u0443 WebSerial.',
        items: [
          '\u2705 \u0411\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e \u0434\u043e 30 \u043f\u0440\u043e\u0434\u0430\u0436\u0431\u0438/\u043c\u0435\u0441\u0435\u0446. Starter \u043e\u0434 12\u20AC',
          '\u2705 \u0420\u0430\u0431\u043e\u0442\u0438 \u043d\u0430 \u0442\u0430\u0431\u043b\u0435\u0442, \u043a\u043e\u043c\u043f\u0458\u0443\u0442\u0435\u0440, \u0442\u0435\u043b\u0435\u0444\u043e\u043d \u2014 \u0441\u0430\u043c\u043e Chrome',
          '\u2705 Cloud \u043f\u0440\u0438\u0441\u0442\u0430\u043f \u043e\u0434 \u0441\u0435\u043a\u0430\u0434\u0435',
          '\u2705 \u0410\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u043e IFRS \u043a\u043d\u0438\u0436\u0435\u045a\u0435 \u2014 \u043f\u0440\u0438\u0445\u043e\u0434, \u0414\u0414\u0412, \u043d\u0430\u043f\u043b\u0430\u0442\u0430',
          '\u2705 \u0417\u0430\u043b\u0438\u0445\u0430 \u0441\u0435 \u0430\u0436\u0443\u0440\u0438\u0440\u0430 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u0438 \u043d\u0430 \u0441\u0435\u043a\u043e\u0458\u0430 \u043f\u0440\u043e\u0434\u0430\u0436\u0431\u0430',
          '\u2705 \u0424\u0438\u0441\u043a\u0430\u043b\u0435\u043d \u043f\u0435\u0447\u0430\u0442\u0430\u0447 \u043f\u0440\u0435\u043a\u0443 WebSerial \u2014 \u0431\u0435\u0437 \u0434\u0440\u0430\u0458\u0432\u0435\u0440\u0438',
          '\u2705 \u041f\u043e\u0434\u0433\u043e\u0442\u0432\u0435\u043d \u0437\u0430 \u0435-\u0424\u0430\u043a\u0442\u0443\u0440\u0430 (\u041e\u043a\u0442\u043e\u043c\u0432\u0440\u0438 2026)',
          '\u2705 AI \u0441\u043e\u0432\u0435\u0442\u043d\u0438\u043a \u0438 \u043c\u043e\u043d\u0438\u0442\u043e\u0440\u0438\u043d\u0433 \u043d\u0430 \u0438\u0437\u043c\u0430\u043c\u0438',
        ],
        steps: null,
      },
      {
        title: '\u041a\u043e\u0458 \u0434\u0430 \u0433\u043e \u0438\u0437\u0431\u0435\u0440\u0435\u0442\u0435?',
        content: null,
        items: null,
        steps: [
          {
            step: '\u0418\u043c\u0430\u0442\u0435 \u0435\u0434\u043d\u0430 \u043f\u0440\u043e\u0434\u0430\u0432\u043d\u0438\u0446\u0430 \u0438 \u0441\u0430\u043a\u0430\u0442\u0435 \u0434\u0430 \u0437\u0430\u0448\u0442\u0435\u0434\u0438\u0442\u0435',
            desc: 'Facturino POS \u2014 \u0431\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e \u0437\u0430 30 \u043f\u0440\u043e\u0434\u0430\u0436\u0431\u0438/\u043c\u0435\u0441\u0435\u0446, \u0431\u0435\u0437 \u0438\u043d\u0441\u0442\u0430\u043b\u0430\u0446\u0438\u0458\u0430, \u0431\u0435\u0437 \u0442\u0435\u0445\u043d\u0438\u0447\u0430\u0440.',
          },
          {
            step: '\u0421\u0430\u043a\u0430\u0442\u0435 POS + \u043a\u043d\u0438\u0433\u043e\u0432\u043e\u0434\u0441\u0442\u0432\u043e \u043d\u0430 \u0435\u0434\u043d\u043e \u043c\u0435\u0441\u0442\u043e',
            desc: 'Facturino POS \u2014 \u0435\u0434\u0438\u043d\u0441\u0442\u0432\u0435\u043d\u043e\u0442 \u0440\u0435\u0448\u0435\u043d\u0438\u0435 \u043a\u0430\u0434\u0435 \u0441\u0435\u043a\u043e\u0458\u0430 \u043f\u0440\u043e\u0434\u0430\u0436\u0431\u0430 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u0438 \u0441\u0435 \u043a\u043d\u0438\u0436\u0438.',
          },
          {
            step: '\u0418\u043c\u0430\u0442\u0435 \u0433\u043e\u043b\u0435\u043c\u0430 \u043a\u043e\u043c\u043f\u0430\u043d\u0438\u0458\u0430 \u0438 \u0431\u0443\u045f\u0435\u0442 \u0437\u0430 ERP',
            desc: 'PANTHEON \u0435 \u0434\u043e\u0431\u0430\u0440 \u0438\u0437\u0431\u043e\u0440, \u043d\u043e \u043f\u043e\u0434\u0433\u043e\u0442\u0432\u0435\u0442\u0435 \u0441\u0435 \u0437\u0430 500-1,200\u20AC/\u0433\u043e\u0434\u0438\u043d\u0430.',
          },
          {
            step: '\u0412\u0435\u045c\u0435 \u043a\u043e\u0440\u0438\u0441\u0442\u0438\u0442\u0435 Vector \u0438 \u0441\u0442\u0435 \u0437\u0430\u0434\u043e\u0432\u043e\u043b\u043d\u0438',
            desc: '\u041f\u0440\u043e\u0434\u043e\u043b\u0436\u0435\u0442\u0435, \u043d\u043e \u043f\u043b\u0430\u043d\u0438\u0440\u0430\u0458\u0442\u0435 \u043c\u0438\u0433\u0440\u0430\u0446\u0438\u0458\u0430 \u043f\u0440\u0435\u0434 \u041e\u043a\u0442\u043e\u043c\u0432\u0440\u0438 2026 \u043a\u043e\u0433\u0430 \u0435-\u0424\u0430\u043a\u0442\u0443\u0440\u0430 \u0441\u0442\u0430\u043d\u0443\u0432\u0430 \u0437\u0430\u0434\u043e\u043b\u0436\u0438\u0442\u0435\u043b\u043d\u0430.',
          },
        ],
      },
    ],
    relatedTitle: '\u041f\u043e\u0432\u0440\u0437\u0430\u043d\u0438 \u0441\u0442\u0430\u0442\u0438\u0438',
    related: [
      { slug: 'fiskalen-pecatac-chrome', title: '\u041a\u0430\u043a\u043e \u0434\u0430 \u043f\u043e\u0432\u0440\u0437\u0435\u0442\u0435 \u0444\u0438\u0441\u043a\u0430\u043b\u0435\u043d \u043f\u0435\u0447\u0430\u0442\u0430\u0447 \u0432\u043e Chrome \u0431\u0435\u0437 \u0434\u0440\u0430\u0458\u0432\u0435\u0440\u0438' },
      { slug: 'vector-alternativa-pos', title: '\u041f\u0440\u0435\u043c\u0438\u043d \u043e\u0434 Vector \u043d\u0430 Facturino POS: \u0432\u043e\u0434\u0438\u0447 \u0447\u0435\u043a\u043e\u0440 \u043f\u043e \u0447\u0435\u043a\u043e\u0440' },
      { slug: 'sto-e-e-faktura', title: '\u0428\u0442\u043e \u0435 \u0435-\u0424\u0430\u043a\u0442\u0443\u0440\u0430 \u0438 \u0437\u043e\u0448\u0442\u043e \u0435 \u0437\u0430\u0434\u043e\u043b\u0436\u0438\u0442\u0435\u043b\u043d\u0430 \u043e\u0434 2026?' },
    ],
    cta: {
      title: '\u041f\u0440\u043e\u0431\u0430\u0458\u0442\u0435 \u0433\u043e Facturino POS \u0431\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e',
      desc: '30 \u043f\u0440\u043e\u0434\u0430\u0436\u0431\u0438 \u043c\u0435\u0441\u0435\u0447\u043d\u043e, \u0431\u0435\u0437 \u043a\u0440\u0435\u0434\u0438\u0442\u043d\u0430 \u043a\u0430\u0440\u0442\u0438\u0447\u043a\u0430, \u0431\u0435\u0437 \u0434\u043e\u0433\u043e\u0432\u043e\u0440. \u041e\u0442\u0432\u043e\u0440\u0435\u0442\u0435 Chrome \u0438 \u0437\u0430\u043f\u043e\u0447\u043d\u0435\u0442\u0435.',
      button: '\u0417\u0430\u043f\u043e\u0447\u043d\u0438 \u0431\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Comparison',
    title: 'Best POS Software for Retail in Macedonia 2026',
    publishDate: '28 March 2026',
    readTime: '10 min read',
    intro:
      'If you have a shop, cafe, kiosk or small business in Macedonia, you know how hard it is to find POS software that works with Macedonian fiscal printers, tracks inventory and auto-posts to accounting. In this guide we compare all POS solutions available in Macedonia \u2014 prices, features, pros and cons.',
    sections: [
      {
        title: 'Why you need POS software in 2026',
        content:
          'In Macedonia, 80% of small businesses have no connection between their register, inventory and accounting. The cashier sells on the fiscal register, and the accountant re-types Z-reports manually. With e-Invoice mandatory from October 2026, every business must digitize. The question is not whether, but which POS to choose.',
        items: null,
        steps: null,
      },
      {
        title: '1. Duna Vector \u2014 desktop classic (\u20AC300)',
        content:
          'Vector is the most common POS software in Macedonia, used by thousands of shops. Costs around \u20AC300 one-time plus \u20AC50-100 for technician installation.',
        items: [
          '\u2705 Supports Macedonian fiscal printers via COM port',
          '\u2705 Basic inventory and item categories',
          '\u274C Windows only \u2014 no tablet support',
          '\u274C No cloud access \u2014 data stuck on one computer',
          '\u274C No automatic accounting \u2014 accountant re-types manually',
          '\u274C Not ready for e-Invoice',
        ],
        steps: null,
      },
      {
        title: '2. Accent \u2014 similar story (~\u20AC200)',
        content:
          'Accent is another popular desktop POS, similar to Vector in concept. Offers inventory and fiscal printing, but with the same limitations.',
        items: [
          '\u2705 Partial inventory and categories',
          '\u2705 Fiscal printing via COM port',
          '\u274C Windows-only, no cloud, no tablet',
          '\u274C No automatic accounting',
          '\u274C Not ready for e-Invoice',
        ],
        steps: null,
      },
      {
        title: '3. PANTHEON \u2014 full ERP (\u20AC500-1,200/year)',
        content:
          'PANTHEON by Datalab is a complete ERP system that includes POS, accounting and inventory. But it is designed for larger companies.',
        items: [
          '\u2705 Integrated accounting and inventory',
          '\u2705 Optional cloud solution',
          '\u274C Expensive \u2014 \u20AC500-1,200/year',
          '\u274C Technician for installation and maintenance',
          '\u274C Complex for small businesses',
          '\u274C Not ready for e-Invoice',
        ],
        steps: null,
      },
      {
        title: '4. Facturino POS \u2014 free, cloud, automatic',
        content:
          'Facturino POS is a new solution that changes the game. Runs in Chrome on any device, no installation. Every sale automatically posts to IFRS accounting, deducts stock and prints a fiscal receipt via WebSerial.',
        items: [
          '\u2705 Free up to 30 sales/month. Starter from \u20AC12',
          '\u2705 Works on tablet, computer, phone \u2014 just Chrome',
          '\u2705 Cloud access from anywhere',
          '\u2705 Automatic IFRS posting \u2014 revenue, VAT, payment',
          '\u2705 Inventory updates automatically on every sale',
          '\u2705 Fiscal printer via WebSerial \u2014 no drivers',
          '\u2705 Ready for e-Invoice (October 2026)',
          '\u2705 AI advisor and fraud monitoring',
        ],
        steps: null,
      },
      {
        title: 'Which one should you choose?',
        content: null,
        items: null,
        steps: [
          {
            step: 'You have one shop and want to save money',
            desc: 'Facturino POS \u2014 free for 30 sales/month, no installation, no technician.',
          },
          {
            step: 'You want POS + accounting in one place',
            desc: 'Facturino POS \u2014 the only solution where every sale auto-posts to accounting.',
          },
          {
            step: 'You have a large company with ERP budget',
            desc: 'PANTHEON is a good choice, but prepare for \u20AC500-1,200/year.',
          },
          {
            step: 'You already use Vector and are satisfied',
            desc: 'Continue, but plan migration before October 2026 when e-Invoice becomes mandatory.',
          },
        ],
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'fiskalen-pecatac-chrome', title: 'How to connect a fiscal printer in Chrome without drivers' },
      { slug: 'vector-alternativa-pos', title: 'Switching from Vector to Facturino POS: step by step guide' },
      { slug: 'sto-e-e-faktura', title: 'What is e-Invoice and why is it mandatory from 2026?' },
    ],
    cta: {
      title: 'Try Facturino POS for free',
      desc: '30 sales per month, no credit card, no contract. Open Chrome and start.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Krahasim',
    title: 'Softueri me i mire POS per shitje me pakice ne Maqedoni 2026',
    publishDate: '28 mars 2026',
    readTime: '10 min lexim',
    intro:
      'Nese keni dyqan, kafe, kiosk ose biznes te vogel ne Maqedoni, e dini sa e veshtire te gjeni softuer POS qe punon me printer fiskal maqedonas, ndjek stokun dhe regjistron automatikisht. Ne kete udhezues krahasojme te gjitha zgjidhjet POS ne Maqedoni.',
    sections: [
      { title: 'Pse ju nevojitet softuer POS ne 2026?', content: 'Ne Maqedoni, 80% e bizneseve te vogla nuk kane lidhje midis arkes, stokut dhe kontabilitetit. Arketari shet ne arken fiskale, kontabilisti kopjon Z-raportet me dore. Me e-Fature te detyrueshme nga Tetori 2026, cdo biznes duhet te digjitalizohet.', items: null, steps: null },
      { title: '1. Duna Vector \u2014 klasik desktop (300\u20AC)', content: 'Vector eshte softueri POS me i perhapur ne Maqedoni. Kushton rreth 300\u20AC plus 50-100\u20AC per instalim nga tekniku.', items: ['\u2705 Mbeshtet printera fiskal maqedonas me COM port', '\u2705 Stok bazik dhe kategori', '\u274C Vetem Windows \u2014 pa tablet', '\u274C Pa cloud \u2014 te dhenat ne nje kompjuter', '\u274C Pa regjistrim automatik', '\u274C Pa e-Fature'], steps: null },
      { title: '2. Accent \u2014 histori e ngjashme (~200\u20AC)', content: 'Accent eshte nje POS tjeter desktop i popullarizuar, i ngjashem me Vector.', items: ['\u2705 Stok i pjesshem dhe kategori', '\u2705 Printim fiskal me COM port', '\u274C Vetem Windows, pa cloud, pa tablet', '\u274C Pa regjistrim automatik', '\u274C Pa e-Fature'], steps: null },
      { title: '3. PANTHEON \u2014 ERP i plote (500-1,200\u20AC/vit)', content: 'PANTHEON nga Datalab eshte sistem ERP i kompletuar qe perfshine POS, kontabilitet dhe stok.', items: ['\u2705 Kontabilitet dhe stok i integruar', '\u2705 Zgjidhje cloud opsionale', '\u274C Shtrejte \u2014 500-1,200\u20AC/vit', '\u274C Teknik per instalim', '\u274C Kompleks per biznese te vogla', '\u274C Pa e-Fature'], steps: null },
      { title: '4. Facturino POS \u2014 falas, cloud, automatik', content: 'Facturino POS eshte zgjidhje e re qe ndryshon lojen. Punon ne Chrome ne cdo pajisje, pa instalim.', items: ['\u2705 Falas deri 30 shitje/muaj. Starter nga 12\u20AC', '\u2705 Punon ne tablet, kompjuter, telefon', '\u2705 Qasje cloud nga kudo', '\u2705 Regjistrim automatik IFRS', '\u2705 Stoku azhurnohet automatikisht', '\u2705 Printer fiskal me WebSerial \u2014 pa driver', '\u2705 Gati per e-Fature (Tetor 2026)', '\u2705 AI keshilltar dhe monitorim mashtrimi'], steps: null },
      { title: 'Cilin te zgjidhni?', content: null, items: null, steps: [{ step: 'Keni nje dyqan dhe doni te kurseni', desc: 'Facturino POS \u2014 falas per 30 shitje/muaj, pa instalim.' }, { step: 'Doni POS + kontabilitet ne nje vend', desc: 'Facturino POS \u2014 i vetmi ku cdo shitje regjistrohet automatikisht.' }, { step: 'Keni kompani te madhe me buxhet ERP', desc: 'PANTHEON eshte zgjidhje e mire, por pergatituni per 500-1,200\u20AC/vit.' }, { step: 'Tashme perdorni Vector', desc: 'Vazhdoni, por planifikoni migrimin para Tetorit 2026.' }] },
    ],
    relatedTitle: 'Artikuj te lidhur',
    related: [
      { slug: 'fiskalen-pecatac-chrome', title: 'Si te lidhni printer fiskal ne Chrome pa driver' },
      { slug: 'vector-alternativa-pos', title: 'Kalimi nga Vector ne Facturino POS: hap pas hapi' },
      { slug: 'sto-e-e-faktura', title: 'Cfare eshte e-Fatura dhe pse eshte e detyrueshme nga 2026?' },
    ],
    cta: { title: 'Provoni Facturino POS falas', desc: '30 shitje ne muaj, pa kartele krediti, pa kontrate.', button: 'Fillo falas' },
  },
  tr: {
    backLink: '\u2190 Bloga don',
    tag: 'Karsilastirma',
    title: 'Makedonya\'da en iyi perakende POS yazilimi 2026',
    publishDate: '28 Mart 2026',
    readTime: '10 dk okuma',
    intro:
      'Makedonya\'da magazaniz, kafeniz veya kucuk isletmeniz varsa, Makedonya fiskal yazicilarla calisan, stok takibi yapan ve otomatik muhasebe kaydeden bir POS yazilimi bulmanin ne kadar zor oldugunu bilirsiniz.',
    sections: [
      { title: 'Neden 2026\'da POS yazilimina ihtiyaciniz var?', content: 'Makedonya\'da kucuk isletmelerin %80\'inin kasa, stok ve muhasebe arasinda baglanti yok. Ekim 2026\'dan itibaren e-Fatura zorunlu. Her isletme dijitallesmeli.', items: null, steps: null },
      { title: '1. Duna Vector \u2014 masaustu klasigi (300\u20AC)', content: 'Vector, Makedonya\'daki en yaygin POS yazilimi. Yaklasik 300\u20AC + teknisyen kurulumu.', items: ['\u2705 COM port ile Makedonya fiskal yazicilari destekler', '\u2705 Temel stok ve kategoriler', '\u274C Sadece Windows \u2014 tablet destegi yok', '\u274C Cloud erisim yok', '\u274C Otomatik muhasebe yok', '\u274C e-Fatura icin hazir degil'], steps: null },
      { title: '2. Accent \u2014 benzer hikaye (~200\u20AC)', content: 'Accent, Vector\'e benzer baska bir masaustu POS.', items: ['\u2705 Kismi stok ve kategoriler', '\u2705 COM port ile fiskal baski', '\u274C Sadece Windows, cloud yok', '\u274C Otomatik muhasebe yok', '\u274C e-Fatura yok'], steps: null },
      { title: '3. PANTHEON \u2014 tam ERP (500-1,200\u20AC/yil)', content: 'Datalab PANTHEON, POS, muhasebe ve stoku iceren eksiksiz ERP sistemi.', items: ['\u2705 Entegre muhasebe ve stok', '\u2705 Opsiyonel cloud', '\u274C Pahali \u2014 500-1,200\u20AC/yil', '\u274C Kurulum icin teknisyen', '\u274C Kucuk isletmeler icin karmasik', '\u274C e-Fatura yok'], steps: null },
      { title: '4. Facturino POS \u2014 ucretsiz, cloud, otomatik', content: 'Facturino POS, kurallari degistiren yeni bir cozum. Herhangi bir cihazda Chrome\'da calisir.', items: ['\u2705 Ayda 30 satisa kadar ucretsiz. Starter 12\u20AC', '\u2705 Tablet, bilgisayar, telefonda calisir', '\u2705 Her yerden cloud erisim', '\u2705 Otomatik IFRS kaydi', '\u2705 Stok otomatik guncellenir', '\u2705 WebSerial ile fiskal yazici \u2014 surucusuz', '\u2705 e-Fatura hazir (Ekim 2026)', '\u2705 AI danisma ve dolandiricilik izleme'], steps: null },
      { title: 'Hangisini secmelisiniz?', content: null, items: null, steps: [{ step: 'Bir magazaniz var ve tasarruf etmek istiyorsunuz', desc: 'Facturino POS \u2014 ayda 30 satis ucretsiz, kurulum yok.' }, { step: 'POS + muhasebe tek yerde istiyorsunuz', desc: 'Facturino POS \u2014 her satisin otomatik kaydedildigi tek cozum.' }, { step: 'Buyuk sirket ve ERP butceniz var', desc: 'PANTHEON iyi bir secim, ama 500-1,200\u20AC/yil hazirligi.' }, { step: 'Zaten Vector kullaniyorsunuz', desc: 'Devam edin, ama Ekim 2026 oncesi goc planlayin.' }] },
    ],
    relatedTitle: 'Ilgili yazilar',
    related: [
      { slug: 'fiskalen-pecatac-chrome', title: 'Chrome\'da surucusuz fiskal yazici nasil baglanir' },
      { slug: 'vector-alternativa-pos', title: 'Vector\'den Facturino POS\'a gecis: adim adim rehber' },
      { slug: 'sto-e-e-faktura', title: 'e-Fatura nedir ve neden 2026\'dan itibaren zorunlu?' },
    ],
    cta: { title: 'Facturino POS\'u ucretsiz deneyin', desc: 'Ayda 30 satis, kredi karti yok, sozlesme yok.', button: 'Ucretsiz basla' },
  },
} as const

export default async function PosSoftverMakedonija({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
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

      <section className="pb-8">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <div className="rounded-2xl overflow-hidden shadow-xl border border-gray-100">
            <Image src="/images/pos/hero-shop-owner.png" alt="Macedonian shop owner using POS software on tablet" width={800} height={500} className="w-full h-auto" />
          </div>
        </div>
      </section>

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

      <section className="py-12 md:py-16 bg-gray-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl font-bold text-gray-900 mb-6">{t.relatedTitle}</h2>
          <div className="grid gap-4">
            {t.related.map((r) => (
              <Link key={r.slug} href={`/${locale}/blog/${r.slug}`} className="group flex items-center justify-between bg-white rounded-xl border border-gray-100 px-6 py-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                <span className="text-gray-900 font-medium group-hover:text-indigo-600 transition-colors">{r.title}</span>
                <svg className="w-5 h-5 text-gray-400 group-hover:text-indigo-600 flex-shrink-0 ml-4 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" /></svg>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className="section relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
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
