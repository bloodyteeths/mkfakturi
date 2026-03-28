import Image from 'next/image'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/fiskalen-pecatac-chrome', {
    title: {
      mk: '\u041a\u0430\u043a\u043e \u0434\u0430 \u043f\u043e\u0432\u0440\u0437\u0435\u0442\u0435 \u0444\u0438\u0441\u043a\u0430\u043b\u0435\u043d \u043f\u0435\u0447\u0430\u0442\u0430\u0447 \u0432\u043e Chrome \u0431\u0435\u0437 \u0434\u0440\u0430\u0458\u0432\u0435\u0440\u0438 (WebSerial) | Facturino',
      sq: 'Si te lidhni printer fiskal ne Chrome pa driver (WebSerial) | Facturino',
      tr: 'Chrome\'da surucusuz fiskal yazici nasil baglanir (WebSerial) | Facturino',
      en: 'How to Connect a Fiscal Printer in Chrome Without Drivers (WebSerial) | Facturino',
    },
    description: {
      mk: '\u0412\u043e\u0434\u0438\u0447: \u043f\u043e\u0432\u0440\u0437\u0435\u0442\u0435 Datecs, Tremol \u0438\u043b\u0438 Daisy \u0444\u0438\u0441\u043a\u0430\u043b\u0435\u043d \u043f\u0435\u0447\u0430\u0442\u0430\u0447 \u0432\u043e Chrome \u043f\u0440\u0435\u043a\u0443 WebSerial \u2014 \u0431\u0435\u0437 COM \u043f\u043e\u0440\u0442, \u0431\u0435\u0437 \u0434\u0440\u0430\u0458\u0432\u0435\u0440\u0438, \u0431\u0435\u0437 \u0442\u0435\u0445\u043d\u0438\u0447\u0430\u0440. ISL \u043f\u0440\u043e\u0442\u043e\u043a\u043e\u043b, \u0414\u0414\u0412 \u0433\u0440\u0443\u043f\u0438 \u0410/\u0411/\u0412/\u0413.',
      sq: 'Udhezues: lidhni printer fiskal Datecs, Tremol ose Daisy ne Chrome me WebSerial \u2014 pa COM port, pa driver, pa teknik.',
      tr: 'Rehber: Datecs, Tremol veya Daisy fiskal yaziciyi Chrome\'da WebSerial ile baglayin \u2014 COM port yok, surucu yok, teknisyen yok.',
      en: 'Guide: connect Datecs, Tremol or Daisy fiscal printer in Chrome via WebSerial \u2014 no COM port, no drivers, no technician. ISL protocol, VAT groups A/B/V/G.',
    },
  })
}

const copy = {
  mk: {
    backLink: '\u2190 \u041d\u0430\u0437\u0430\u0434 \u043a\u043e\u043d \u0431\u043b\u043e\u0433',
    tag: '\u0412\u043e\u0434\u0438\u0447',
    title: '\u041a\u0430\u043a\u043e \u0434\u0430 \u043f\u043e\u0432\u0440\u0437\u0435\u0442\u0435 \u0444\u0438\u0441\u043a\u0430\u043b\u0435\u043d \u043f\u0435\u0447\u0430\u0442\u0430\u0447 \u0432\u043e Chrome \u0431\u0435\u0437 \u0434\u0440\u0430\u0458\u0432\u0435\u0440\u0438',
    publishDate: '28 \u043c\u0430\u0440\u0442 2026',
    readTime: '6 \u043c\u0438\u043d \u0447\u0438\u0442\u0430\u045a\u0435',
    intro:
      '\u0414\u043e\u0441\u0435\u0433\u0430, \u043f\u043e\u0432\u0440\u0437\u0443\u0432\u0430\u045a\u0435 \u043d\u0430 \u0444\u0438\u0441\u043a\u0430\u043b\u0435\u043d \u043f\u0435\u0447\u0430\u0442\u0430\u0447 \u0431\u0430\u0440\u0430\u0448\u0435 \u0442\u0435\u0445\u043d\u0438\u0447\u0430\u0440, COM \u043f\u043e\u0440\u0442, Windows \u0434\u0440\u0430\u0458\u0432\u0435\u0440\u0438 \u0438 DLL \u0431\u0438\u0431\u043b\u0438\u043e\u0442\u0435\u043a\u0438. \u0421\u043e WebSerial, Chrome \u043a\u043e\u043c\u0443\u043d\u0438\u0446\u0438\u0440\u0430 \u0434\u0438\u0440\u0435\u043a\u0442\u043d\u043e \u0441\u043e \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u0438\u043e\u0442 \u043f\u0435\u0447\u0430\u0442\u0430\u0447 \u043f\u0440\u0435\u043a\u0443 USB \u2014 \u0431\u0435\u0437 \u0438\u043d\u0441\u0442\u0430\u043b\u0430\u0446\u0438\u0458\u0430. \u0415\u0432\u0435 \u043a\u0430\u043a\u043e.',
    sections: [
      {
        title: '\u0428\u0442\u043e \u0435 WebSerial?',
        content: 'WebSerial \u0435 API \u0432\u0433\u0440\u0430\u0434\u0435\u043d \u0432\u043e Chrome \u043a\u043e\u0458 \u043e\u0432\u043e\u0437\u043c\u043e\u0436\u0443\u0432\u0430 \u0432\u0435\u0431-\u0430\u043f\u043b\u0438\u043a\u0430\u0446\u0438\u0438 \u0434\u0430 \u043a\u043e\u043c\u0443\u043d\u0438\u0446\u0438\u0440\u0430\u0430\u0442 \u0441\u043e USB \u0443\u0440\u0435\u0434\u0438 \u0434\u0438\u0440\u0435\u043a\u0442\u043d\u043e. \u041d\u0435\u043c\u0430 \u043f\u043e\u0442\u0440\u0435\u0431\u0430 \u043e\u0434 \u0434\u0440\u0430\u0458\u0432\u0435\u0440\u0438, DLL \u0431\u0438\u0431\u043b\u0438\u043e\u0442\u0435\u043a\u0438 \u0438\u043b\u0438 \u0438\u043d\u0441\u0442\u0430\u043b\u0430\u0446\u0438\u0458\u0430 \u043d\u0430 \u0441\u043e\u0444\u0442\u0432\u0435\u0440. \u0420\u0430\u0431\u043e\u0442\u0438 \u043d\u0430 Windows, Mac \u0438 ChromeOS.',
        items: null,
        steps: null,
      },
      {
        title: '\u041f\u043e\u0434\u0434\u0440\u0436\u0430\u043d\u0438 \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u0438 \u043f\u0435\u0447\u0430\u0442\u0430\u0447\u0438',
        content: 'Facturino \u0433\u043e \u043a\u043e\u0440\u0438\u0441\u0442\u0438 ISL (\u0418\u043d\u0442\u0435\u0440\u043d\u0430\u0446\u0438\u043e\u043d\u0430\u043b\u0435\u043d \u0421\u0442\u0430\u043d\u0434\u0430\u0440\u0434\u0435\u043d \u041b\u0438\u043d\u043a) \u043f\u0440\u043e\u0442\u043e\u043a\u043e\u043b\u043e\u0442 \u2014 \u0441\u0442\u0430\u043d\u0434\u0430\u0440\u0434 \u0437\u0430 \u0431\u0430\u043b\u043a\u0430\u043d\u0441\u043a\u0438 \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u0438 \u043f\u0435\u0447\u0430\u0442\u0430\u0447\u0438.',
        items: [
          'Datecs FP-700, FP-700X, FP-2000, FP-800 \u0438 \u0434\u0440\u0443\u0433\u0438 \u043c\u043e\u0434\u0435\u043b\u0438',
          'Tremol FP01-KL, S25, M20 \u0438 \u0434\u0440\u0443\u0433\u0438',
          'Daisy Compact-S, Expert \u0438 \u043e\u0441\u0442\u0430\u043d\u0430\u0442\u0438',
          '\u0411\u0438\u043b\u043e \u043a\u043e\u0458 \u0434\u0440\u0443\u0433 ISL-\u043a\u043e\u043c\u043f\u0430\u0442\u0438\u0431\u0438\u043b\u0435\u043d \u043f\u0435\u0447\u0430\u0442\u0430\u0447',
        ],
        steps: null,
      },
      {
        title: '\u0427\u0435\u043a\u043e\u0440 \u043f\u043e \u0447\u0435\u043a\u043e\u0440: \u043f\u043e\u0432\u0440\u0437\u0443\u0432\u0430\u045a\u0435',
        content: null,
        items: null,
        steps: [
          {
            step: '\u041f\u0440\u0438\u043a\u043b\u0443\u0447\u0435\u0442\u0435 \u0433\u043e \u043f\u0435\u0447\u0430\u0442\u0430\u0447\u043e\u0442 \u043d\u0430 USB',
            desc: '\u041f\u043e\u0432\u0435\u045c\u0435\u0442\u043e \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u0438 \u043f\u0435\u0447\u0430\u0442\u0430\u0447\u0438 \u0438\u043c\u0430\u0430\u0442 USB \u043f\u043e\u0440\u0442 \u043f\u043e\u043a\u0440\u0430\u0458 COM. \u041f\u0440\u0438\u043a\u043b\u0443\u0447\u0435\u0442\u0435 \u0433\u043e USB \u043a\u0430\u0431\u0435\u043b\u043e\u0442 \u0432\u043e \u043a\u043e\u043c\u043f\u0458\u0443\u0442\u0435\u0440\u043e\u0442 \u0438\u043b\u0438 \u0442\u0430\u0431\u043b\u0435\u0442\u043e\u0442.',
          },
          {
            step: '\u041e\u0442\u0432\u043e\u0440\u0435\u0442\u0435 Facturino \u0432\u043e Chrome',
            desc: '\u041e\u0434\u0435\u0442\u0435 \u043d\u0430 app.facturino.mk \u0438 \u043d\u0430\u0432\u0438\u0433\u0438\u0440\u0430\u0458\u0442\u0435 \u0434\u043e \u041f\u043e\u0441\u0442\u0430\u0432\u043a\u0438 \u2192 \u0424\u0438\u0441\u043a\u0430\u043b\u043d\u0438 \u0443\u0440\u0435\u0434\u0438.',
          },
          {
            step: '\u041a\u043b\u0438\u043a\u043d\u0435\u0442\u0435 \u201C\u041f\u043e\u0432\u0440\u0437\u0438 \u0443\u0440\u0435\u0434\u201D',
            desc: 'Chrome \u045c\u0435 \u043f\u043e\u043a\u0430\u0436\u0435 \u043b\u0438\u0441\u0442\u0430 \u043d\u0430 USB \u0443\u0440\u0435\u0434\u0438. \u0418\u0437\u0431\u0435\u0440\u0435\u0442\u0435 \u0433\u043e \u0432\u0430\u0448\u0438\u043e\u0442 \u0444\u0438\u0441\u043a\u0430\u043b\u0435\u043d \u043f\u0435\u0447\u0430\u0442\u0430\u0447 (\u043e\u0431\u0438\u0447\u043d\u043e \u0441\u0435 \u043f\u043e\u043a\u0430\u0436\u0443\u0432\u0430 \u043a\u0430\u043a\u043e \u201CUSBxxxx\u201D \u0438\u043b\u0438 \u201CDatecs FP-700\u201D).',
          },
          {
            step: '\u0413\u043e\u0442\u043e\u0432\u043e!',
            desc: '\u041f\u0435\u0447\u0430\u0442\u0430\u0447\u043e\u0442 \u0435 \u043f\u043e\u0432\u0440\u0437\u0430\u043d. \u0417\u0435\u043b\u0435\u043d\u0430\u0442\u0430 \u0442\u043e\u0447\u043a\u0430 \u0432\u043e POS \u0438\u043d\u0442\u0435\u0440\u0444\u0435\u0458\u0441\u043e\u0442 \u043f\u043e\u043a\u0430\u0436\u0443\u0432\u0430 \u0434\u0435\u043a\u0430 \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u0438\u043e\u0442 \u043f\u0435\u0447\u0430\u0442\u0430\u0447 \u0435 \u0430\u043a\u0442\u0438\u0432\u0435\u043d. \u0421\u043b\u0435\u0434\u043d\u0438\u043e\u0442 \u043f\u0430\u0442 \u043a\u043e\u0433\u0430 \u045c\u0435 \u0433\u043e \u043e\u0442\u0432\u043e\u0440\u0438\u0442\u0435 Chrome, \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u0438 \u045c\u0435 \u0441\u0435 \u043f\u043e\u0432\u0440\u0437\u0435.',
          },
        ],
      },
      {
        title: '\u0414\u0414\u0412 \u0433\u0440\u0443\u043f\u0438 (\u0410/\u0411/\u0412/\u0413)',
        content: 'Facturino \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u0438 \u0433\u0438 \u043c\u0430\u043f\u0438\u0440\u0430 \u0430\u0440\u0442\u0438\u043a\u043b\u0438\u0442\u0435 \u0432\u043e \u0414\u0414\u0412 \u0433\u0440\u0443\u043f\u0438 \u0441\u043f\u043e\u0440\u0435\u0434 \u043c\u0430\u043a\u0435\u0434\u043e\u043d\u0441\u043a\u0438\u043e\u0442 \u0437\u0430\u043a\u043e\u043d:',
        items: [
          '\u0413\u0440\u0443\u043f\u0430 \u0410: 18% \u0414\u0414\u0412 (\u0441\u0442\u0430\u043d\u0434\u0430\u0440\u0434\u043d\u0430 \u0441\u0442\u0430\u043f\u043a\u0430)',
          '\u0413\u0440\u0443\u043f\u0430 \u0411: 5% \u0414\u0414\u0412 (\u043d\u0430\u043c\u0430\u043b\u0435\u043d\u0430 \u0441\u0442\u0430\u043f\u043a\u0430 \u2014 \u043b\u0435\u043a\u043e\u0432\u0438, \u043a\u043d\u0438\u0433\u0438, \u0443\u0447\u0438\u043b\u0438\u0448\u0435\u043d \u043f\u0440\u0438\u0431\u043e\u0440)',
          '\u0413\u0440\u0443\u043f\u0430 \u0412: 10% \u0414\u0414\u0412 (\u043d\u0430\u043c\u0430\u043b\u0435\u043d\u0430 \u0441\u0442\u0430\u043f\u043a\u0430 \u2014 \u0445\u0440\u0430\u043d\u0430, \u0443\u0433\u043e\u0441\u0442\u0438\u0442\u0435\u043b\u0441\u0442\u0432\u043e)',
          '\u0413\u0440\u0443\u043f\u0430 \u0413: 0% \u0414\u0414\u0412 (\u043e\u0441\u043b\u043e\u0431\u043e\u0434\u0435\u043d\u043e)',
        ],
        steps: null,
      },
      {
        title: '\u041f\u0440\u0435\u0434\u043d\u043e\u0441\u0442\u0438 \u043d\u0430 WebSerial vs. COM \u043f\u043e\u0440\u0442',
        content: null,
        items: [
          '\u0411\u0435\u0437 \u0434\u0440\u0430\u0458\u0432\u0435\u0440\u0438: \u043d\u0435\u043c\u0430 \u0438\u043d\u0441\u0442\u0430\u043b\u0430\u0446\u0438\u0458\u0430, \u043d\u0435\u043c\u0430 DLL, \u043d\u0435\u043c\u0430 Windows-\u0437\u0430\u0432\u0438\u0441\u043d\u043e\u0441\u0442',
          '\u0420\u0430\u0431\u043e\u0442\u0438 \u043d\u0430 \u0441\u0435\u043a\u043e\u0458 \u043e\u043f\u0435\u0440\u0430\u0442\u0438\u0432\u0435\u043d \u0441\u0438\u0441\u0442\u0435\u043c: Windows, Mac, ChromeOS',
          '\u0411\u0435\u0437 \u0442\u0435\u0445\u043d\u0438\u0447\u0430\u0440: \u043f\u0440\u0438\u043a\u043b\u0443\u0447\u0435\u0442\u0435 USB, \u0438\u0437\u0431\u0435\u0440\u0435\u0442\u0435 \u0443\u0440\u0435\u0434, \u0433\u043e\u0442\u043e\u0432\u043e',
          '\u0410\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u043e \u043f\u043e\u0432\u0440\u0437\u0443\u0432\u0430\u045a\u0435: Chrome \u0433\u043e \u043f\u0430\u043c\u0442\u0438 \u0443\u0440\u0435\u0434\u043e\u0442 \u0438 \u0441\u043b\u0435\u0434\u043d\u0438\u043e\u0442 \u043f\u0430\u0442 \u0441\u0435 \u043f\u043e\u0432\u0440\u0437\u0443\u0432\u0430 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u0438',
          '\u0411\u0435\u0437\u0431\u0435\u0434\u043d\u043e: \u043a\u043e\u0440\u0438\u0441\u043d\u0438\u043a\u043e\u0442 \u043c\u043e\u0440\u0430 \u0435\u043a\u0441\u043f\u043b\u0438\u0446\u0438\u0442\u043d\u043e \u0434\u0430 \u0433\u043e \u043e\u0434\u043e\u0431\u0440\u0438 \u043f\u0440\u0438\u0441\u0442\u0430\u043f\u043e\u0442 \u0434\u043e \u0443\u0440\u0435\u0434\u043e\u0442',
        ],
        steps: null,
      },
      {
        title: '\u041d\u0430\u0458\u0447\u0435\u0441\u0442\u0438 \u043f\u0440\u043e\u0431\u043b\u0435\u043c\u0438 \u0438 \u0440\u0435\u0448\u0435\u043d\u0438\u0458\u0430',
        content: null,
        items: null,
        steps: [
          {
            step: '\u041f\u0435\u0447\u0430\u0442\u0430\u0447\u043e\u0442 \u043d\u0435 \u0441\u0435 \u043f\u043e\u043a\u0430\u0436\u0443\u0432\u0430 \u0432\u043e \u043b\u0438\u0441\u0442\u0430\u0442\u0430',
            desc: '\u041f\u0440\u043e\u0432\u0435\u0440\u0435\u0442\u0435 \u0434\u0430\u043b\u0438 USB \u043a\u0430\u0431\u0435\u043b\u043e\u0442 \u0435 \u0434\u043e\u0431\u0440\u043e \u043f\u043e\u0432\u0440\u0437\u0430\u043d. \u041d\u0435\u043a\u043e\u0438 \u043f\u0435\u0447\u0430\u0442\u0430\u0447\u0438 \u0431\u0430\u0440\u0430\u0430\u0442 USB-B \u043a\u0430\u0431\u0435\u043b (\u043a\u0432\u0430\u0434\u0440\u0430\u0442\u0435\u043d \u043a\u043e\u043d\u0435\u043a\u0442\u043e\u0440). \u0420\u0435\u0441\u0442\u0430\u0440\u0442\u0438\u0440\u0430\u0458\u0442\u0435 \u0433\u043e \u043f\u0435\u0447\u0430\u0442\u0430\u0447\u043e\u0442 \u0438 \u043e\u0441\u0432\u0435\u0436\u0435\u0442\u0435 \u0433\u043e Chrome.',
          },
          {
            step: 'Chrome \u0431\u0430\u0440\u0430 \u0434\u043e\u0437\u0432\u043e\u043b\u0430 \u0441\u0435\u043a\u043e\u0458 \u043f\u0430\u0442',
            desc: '\u041f\u0440\u0432\u0438\u043e\u0442 \u043f\u0430\u0442 Chrome \u0431\u0430\u0440\u0430 \u0434\u043e\u0437\u0432\u043e\u043b\u0430 (user gesture). \u041f\u043e \u043e\u0434\u043e\u0431\u0440\u0443\u0432\u0430\u045a\u0435, Facturino \u0433\u043e \u043f\u0430\u043c\u0442\u0438 \u0438 \u0441\u043b\u0435\u0434\u043d\u0438\u043e\u0442 \u043f\u0430\u0442 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0441\u043a\u0438 \u0441\u0435 \u043f\u043e\u0432\u0440\u0437\u0443\u0432\u0430.',
          },
          {
            step: '\u041f\u0435\u0447\u0430\u0442\u0438 \u0447\u0443\u0434\u043d\u0438 \u043a\u0430\u0440\u0430\u043a\u0442\u0435\u0440\u0438',
            desc: 'Facturino \u043a\u043e\u0440\u0438\u0441\u0442\u0438 CP1251 \u043a\u043e\u0434\u0438\u0440\u0430\u045a\u0435 \u0437\u0430 \u045c\u0438\u0440\u0438\u043b\u0438\u0446\u0430. \u0410\u043a\u043e \u043f\u0435\u0447\u0430\u0442\u0430\u0447\u043e\u0442 \u043d\u0435 \u043f\u043e\u0434\u0434\u0440\u0436\u0443\u0432\u0430 \u045c\u0438\u0440\u0438\u043b\u0438\u0446\u0430, \u043a\u043e\u043d\u0442\u0430\u043a\u0442\u0438\u0440\u0430\u0458\u0442\u0435 \u043d\u0435 \u0437\u0430 \u043f\u043e\u043c\u043e\u0448.',
          },
        ],
      },
    ],
    relatedTitle: '\u041f\u043e\u0432\u0440\u0437\u0430\u043d\u0438 \u0441\u0442\u0430\u0442\u0438\u0438',
    related: [
      { slug: 'pos-softver-makedonija', title: '\u041d\u0430\u0458\u0434\u043e\u0431\u0430\u0440 POS \u0441\u043e\u0444\u0442\u0432\u0435\u0440 \u0437\u0430 \u041c\u0430\u043a\u0435\u0434\u043e\u043d\u0438\u0458\u0430 2026' },
      { slug: 'vector-alternativa-pos', title: '\u041f\u0440\u0435\u043c\u0438\u043d \u043e\u0434 Vector \u043d\u0430 Facturino POS' },
      { slug: 'ddv-vodich-mk', title: '\u0412\u043e\u0434\u0438\u0447 \u0437\u0430 \u0414\u0414\u0412 \u0432\u043e \u041c\u0430\u043a\u0435\u0434\u043e\u043d\u0438\u0458\u0430' },
    ],
    cta: {
      title: '\u041f\u043e\u0432\u0440\u0437\u0435\u0442\u0435 \u0433\u043e \u043f\u0435\u0447\u0430\u0442\u0430\u0447\u043e\u0442 \u0437\u0430 2 \u043c\u0438\u043d\u0443\u0442\u0438',
      desc: '\u0420\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u0430\u0458\u0442\u0435 \u0441\u0435 \u0431\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e, \u043f\u0440\u0438\u043a\u043b\u0443\u0447\u0435\u0442\u0435 USB \u0438 \u043f\u0435\u0447\u0430\u0442\u0435\u0442\u0435 \u0444\u0438\u0441\u043a\u0430\u043b\u043d\u0430 \u0441\u043c\u0435\u0442\u043a\u0430 \u043e\u0434 Chrome.',
      button: '\u0417\u0430\u043f\u043e\u0447\u043d\u0438 \u0431\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Guide',
    title: 'How to Connect a Fiscal Printer in Chrome Without Drivers',
    publishDate: '28 March 2026',
    readTime: '6 min read',
    intro:
      'Until now, connecting a fiscal printer required a technician, COM port, Windows drivers and DLL libraries. With WebSerial, Chrome communicates directly with the fiscal printer via USB \u2014 no software installation needed. Here\'s how.',
    sections: [
      { title: 'What is WebSerial?', content: 'WebSerial is an API built into Chrome that allows web applications to communicate with USB devices directly. No drivers, DLL libraries or software installation needed. Works on Windows, Mac and ChromeOS.', items: null, steps: null },
      { title: 'Supported fiscal printers', content: 'Facturino uses the ISL (International Standard Link) protocol \u2014 the standard for Balkan fiscal printers.', items: ['Datecs FP-700, FP-700X, FP-2000, FP-800 and other models', 'Tremol FP01-KL, S25, M20 and others', 'Daisy Compact-S, Expert and the rest', 'Any other ISL-compatible printer'], steps: null },
      { title: 'Step by step: connecting', content: null, items: null, steps: [{ step: 'Plug the printer into USB', desc: 'Most fiscal printers have a USB port alongside COM. Plug the USB cable into your computer or tablet.' }, { step: 'Open Facturino in Chrome', desc: 'Go to app.facturino.mk and navigate to Settings \u2192 Fiscal Devices.' }, { step: 'Click "Connect Device"', desc: 'Chrome will show a list of USB devices. Select your fiscal printer (usually shows as "USBxxxx" or "Datecs FP-700").' }, { step: 'Done!', desc: 'The printer is connected. The green dot in the POS interface shows the fiscal printer is active. Next time you open Chrome, it auto-connects.' }] },
      { title: 'VAT groups (A/B/V/G)', content: 'Facturino automatically maps items to VAT groups per Macedonian law:', items: ['Group A: 18% VAT (standard rate)', 'Group B: 5% VAT (reduced \u2014 medicines, books, school supplies)', 'Group V: 10% VAT (reduced \u2014 food, hospitality)', 'Group G: 0% VAT (exempt)'], steps: null },
      { title: 'WebSerial vs. COM port advantages', content: null, items: ['No drivers: no installation, no DLL, no Windows dependency', 'Works on every OS: Windows, Mac, ChromeOS', 'No technician: plug USB, select device, done', 'Auto-reconnect: Chrome remembers the device and reconnects automatically', 'Secure: user must explicitly approve device access'], steps: null },
      { title: 'Common issues and solutions', content: null, items: null, steps: [{ step: 'Printer not showing in list', desc: 'Check that the USB cable is properly connected. Some printers need a USB-B cable (square connector). Restart the printer and refresh Chrome.' }, { step: 'Chrome asks for permission every time', desc: 'The first time Chrome asks for permission (user gesture). After approval, Facturino remembers and auto-connects next time.' }, { step: 'Prints strange characters', desc: 'Facturino uses CP1251 encoding for Cyrillic. If your printer doesn\'t support Cyrillic, contact us for help.' }] },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'pos-softver-makedonija', title: 'Best POS Software for Macedonia 2026' },
      { slug: 'vector-alternativa-pos', title: 'Switching from Vector to Facturino POS' },
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for Macedonia' },
    ],
    cta: { title: 'Connect your printer in 2 minutes', desc: 'Sign up free, plug USB and print a fiscal receipt from Chrome.', button: 'Start free' },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Udhezues',
    title: 'Si te lidhni printer fiskal ne Chrome pa driver',
    publishDate: '28 mars 2026',
    readTime: '6 min lexim',
    intro: 'Deri tani, lidhja e printerit fiskal kerkonte teknik, COM port, driver Windows dhe biblioteka DLL. Me WebSerial, Chrome komunikon direkt me printerin fiskal me USB \u2014 pa instalim softueri.',
    sections: [
      { title: 'Cfare eshte WebSerial?', content: 'WebSerial eshte API i integruar ne Chrome qe lejon aplikacionet web te komunikojne me pajisje USB direkt. Pa driver, pa DLL, pa instalim. Punon ne Windows, Mac dhe ChromeOS.', items: null, steps: null },
      { title: 'Printera fiskal te mbeshtetur', content: 'Facturino perdor protokollin ISL \u2014 standardi per printera fiskal ballkanike.', items: ['Datecs FP-700, FP-700X, FP-2000, FP-800', 'Tremol FP01-KL, S25, M20', 'Daisy Compact-S, Expert', 'Cdo printer tjeter ISL-kompatibel'], steps: null },
      { title: 'Hap pas hapi: lidhja', content: null, items: null, steps: [{ step: 'Futni printerin ne USB', desc: 'Shumica e printerave fiskal kane port USB krahas COM. Lidhni kabllun USB ne kompjuter ose tablet.' }, { step: 'Hapni Facturino ne Chrome', desc: 'Shkoni ne app.facturino.mk dhe navigoni te Cilesimet \u2192 Pajisje Fiskale.' }, { step: 'Klikoni "Lidh Pajisjen"', desc: 'Chrome do te tregoje listen e pajisjeve USB. Zgjidhni printerin tuaj fiskal.' }, { step: 'Gati!', desc: 'Printeri eshte i lidhur. Pika jeshile ne POS tregon se printeri fiskal eshte aktiv. Heren tjeter Chrome lidhet automatikisht.' }] },
      { title: 'Grupet e TVSH (A/B/V/G)', content: 'Facturino i mapon automatikisht artikujt ne grupe TVSH sipas ligjit maqedonas:', items: ['Grupi A: 18% TVSH (norma standarde)', 'Grupi B: 5% TVSH (e reduktuar \u2014 ilace, libra)', 'Grupi V: 10% TVSH (e reduktuar \u2014 ushqim, hoteleri)', 'Grupi G: 0% TVSH (e perliruar)'], steps: null },
      { title: 'Perparsite e WebSerial vs. COM port', content: null, items: ['Pa driver: pa instalim, pa DLL, pa varesi nga Windows', 'Punon ne cdo OS: Windows, Mac, ChromeOS', 'Pa teknik: futni USB, zgjidhni pajisjen, gati', 'Rilidhje automatike: Chrome e mban mend pajisjen', 'I sigurt: perdoruesi duhet te miratoje eksplicitsisht qasjen'], steps: null },
      { title: 'Probleme te zakonshme dhe zgjidhje', content: null, items: null, steps: [{ step: 'Printeri nuk shfaqet ne liste', desc: 'Kontrolloni se kabllu USB eshte i lidhur mire. Disa printera kerkojne kabllo USB-B. Rinisni printerin dhe rifreskoni Chrome.' }, { step: 'Chrome kerkon leje cdo here', desc: 'Heren e pare Chrome kerkon leje. Pas miratimit, Facturino e mban mend dhe lidhet automatikisht.' }, { step: 'Printon karaktere te cuditshme', desc: 'Facturino perdor kodimin CP1251 per cirilike. Nese printeri juaj nuk mbeshtet cirilike, na kontaktoni.' }] },
    ],
    relatedTitle: 'Artikuj te lidhur',
    related: [
      { slug: 'pos-softver-makedonija', title: 'Softueri me i mire POS per Maqedoni 2026' },
      { slug: 'vector-alternativa-pos', title: 'Kalimi nga Vector ne Facturino POS' },
      { slug: 'ddv-vodich-mk', title: 'Udhezues per TVSH ne Maqedoni' },
    ],
    cta: { title: 'Lidhni printerin per 2 minuta', desc: 'Regjistrohuni falas, futni USB dhe printoni kupon fiskal nga Chrome.', button: 'Fillo falas' },
  },
  tr: {
    backLink: '\u2190 Bloga don',
    tag: 'Rehber',
    title: 'Chrome\'da surucusuz fiskal yazici nasil baglanir',
    publishDate: '28 Mart 2026',
    readTime: '6 dk okuma',
    intro: 'Simdi ye kadar fiskal yazici baglamak icin teknisyen, COM port, Windows suruculeri ve DLL kutuphaneleri gerekiyordu. WebSerial ile Chrome, fiskal yaziciyla USB uzerinden dogrudan iletisim kuruyor.',
    sections: [
      { title: 'WebSerial nedir?', content: 'WebSerial, Chrome\'a yerlesik bir API\'dir ve web uygulamalarinin USB cihazlarla dogrudan iletisim kurmasini saglar. Surucu, DLL veya kurulum gerekmez. Windows, Mac ve ChromeOS\'ta calisir.', items: null, steps: null },
      { title: 'Desteklenen fiskal yazicilar', content: 'Facturino, ISL (International Standard Link) protokolunu kullanir \u2014 Balkan fiskal yazicilarinin standardi.', items: ['Datecs FP-700, FP-700X, FP-2000, FP-800', 'Tremol FP01-KL, S25, M20', 'Daisy Compact-S, Expert', 'Diger ISL uyumlu yazicilar'], steps: null },
      { title: 'Adim adim: baglanti', content: null, items: null, steps: [{ step: 'Yaziciyi USB\'ye takin', desc: 'Cogu fiskal yazicida COM yaninda USB portu vardir. USB kablosunu bilgisayar veya tablete takin.' }, { step: 'Chrome\'da Facturino\'yu acin', desc: 'app.facturino.mk adresine gidin ve Ayarlar \u2192 Fiskal Cihazlar\'a gidin.' }, { step: '"Cihaz Bagla"ya tiklayin', desc: 'Chrome USB cihaz listesini gosterecek. Fiskal yazicininizi secin.' }, { step: 'Tamam!', desc: 'Yazici baglandi. POS arayuzundeki yesil nokta fiskal yazicinin aktif oldugunu gosterir. Chrome bir dahaki sefere otomatik baglanir.' }] },
      { title: 'KDV gruplari (A/B/V/G)', content: 'Facturino, urunleri Makedonya yasasina gore KDV gruplarina otomatik esler:', items: ['Grup A: %18 KDV (standart oran)', 'Grup B: %5 KDV (indirimli \u2014 ilaclar, kitaplar)', 'Grup V: %10 KDV (indirimli \u2014 gida, otelcilik)', 'Grup G: %0 KDV (muaf)'], steps: null },
      { title: 'WebSerial vs. COM port avantajlari', content: null, items: ['Surucusuz: kurulum yok, DLL yok, Windows bagimliligi yok', 'Her isletim sisteminde calisir: Windows, Mac, ChromeOS', 'Teknisyen gereksiz: USB takin, cihazi secin, tamam', 'Otomatik yeniden baglanti: Chrome cihazi hatirlayip otomatik baglanir', 'Guvenli: kullanici erisimi acikca onaylamalidir'], steps: null },
      { title: 'Yaygin sorunlar ve cozumler', content: null, items: null, steps: [{ step: 'Yazici listede gorunmuyor', desc: 'USB kablonun duzgun baglandigini kontrol edin. Bazi yazicilar USB-B kablosu gerektirir. Yaziciyi yeniden baslatin ve Chrome\'u yenileyin.' }, { step: 'Chrome her seferinde izin istiyor', desc: 'Ilk sefer Chrome izin ister. Onayladiktan sonra Facturino hatirlayip otomatik baglanir.' }, { step: 'Garip karakterler yazdiriyor', desc: 'Facturino Kiril icin CP1251 kodlamasi kullanir. Yazicininiz Kiril desteklemiyorsa bize ulasin.' }] },
    ],
    relatedTitle: 'Ilgili yazilar',
    related: [
      { slug: 'pos-softver-makedonija', title: 'Makedonya\'da en iyi POS yazilimi 2026' },
      { slug: 'vector-alternativa-pos', title: 'Vector\'den Facturino POS\'a gecis' },
      { slug: 'ddv-vodich-mk', title: 'Makedonya KDV rehberi' },
    ],
    cta: { title: 'Yaziciyi 2 dakikada baglayin', desc: 'Ucretsiz kaydolun, USB takin ve Chrome\'dan fiskal fis yazdirin.', button: 'Ucretsiz basla' },
  },
} as const

export default async function FiskalenPecatacChrome({
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
            <Image src="/images/pos/fiscal-printer-closeup.png" alt="Fiscal receipt printer connected via USB to Chrome browser" width={800} height={600} className="w-full h-auto" />
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
