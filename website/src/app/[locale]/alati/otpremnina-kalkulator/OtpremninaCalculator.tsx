'use client'

import { useMemo, useState } from 'react'
import { type Locale } from '@/i18n/locales'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

// Severance (отпремнина) per the Macedonian Law on Labour Relations (ЗРО).
// Redundancy (технолошки вишок, Art. 97): net salary multiplier by years of
// service with the same employer. Retirement (Art. 101): 2 net salaries.
// The scale is shown transparently; amounts are indicative — a collective
// agreement or contract may provide more, so verify with an accountant.
function redundancyMultiplier(years: number): number {
  if (years < 5) return 1
  if (years < 10) return 2
  if (years < 15) return 3
  if (years < 20) return 4
  if (years < 25) return 5
  return 6
}

const RETIREMENT_MULTIPLIER = 2

function fmt(n: number): string {
  return new Intl.NumberFormat('mk-MK', { maximumFractionDigits: 0 }).format(Math.round(n || 0))
}

const UI: Record<Locale, {
  netSalary: string; netHint: string; years: string; reason: string
  redundancy: string; retirement: string
  result: string; multiplier: string; formula: (m: number, s: number) => string
  scaleTitle: string; scaleRows: [string, string][]; scaleYears: string; scaleAmount: string
  disclaimer: string
  ctaTitle: string; ctaSub: string; ctaBtn: string
}> = {
  mk: {
    netSalary: 'Просечна нето плата (последни 6 месеци)', netHint: 'во МКД',
    years: 'Години работен стаж кај истиот работодавач', reason: 'Основа',
    redundancy: 'Технолошки вишок (отказ)', retirement: 'Пензионирање',
    result: 'Отпремнина', multiplier: 'нето плати',
    formula: (m, s) => `${m} × ${fmt(s)} МКД`,
    scaleTitle: 'Скала при технолошки вишок (Чл. 97 ЗРО)',
    scaleYears: 'Работен стаж', scaleAmount: 'Отпремнина',
    scaleRows: [['до 5 години', '1 нето плата'], ['5 – 10 години', '2 нето плати'], ['10 – 15 години', '3 нето плати'], ['15 – 20 години', '4 нето плати'], ['20 – 25 години', '5 нето плати'], ['над 25 години', '6 нето плати']],
    disclaimer: 'Пресметката е информативна, според Законот за работни односи. При пензионирање отпремнината е 2 просечни нето плати. Колективен договор или договор за вработување може да предвиди поповолен износ. Проверете со сметководител.',
    ctaTitle: 'Автоматска пресметка на плати и отпремнини',
    ctaSub: 'Facturino ги пресметува платите, придонесите и надоместоците за целата фирма.',
    ctaBtn: 'Започни бесплатно →',
  },
  sq: {
    netSalary: 'Paga neto mesatare (6 muajt e fundit)', netHint: 'në MKD',
    years: 'Vite përvojë pune te i njëjti punëdhënës', reason: 'Baza',
    redundancy: 'Tepricë teknologjike (shkarkim)', retirement: 'Pensionim',
    result: 'Kompensimi (otpremnina)', multiplier: 'paga neto',
    formula: (m, s) => `${m} × ${fmt(s)} MKD`,
    scaleTitle: 'Shkalla për tepricë teknologjike (Neni 97 ЗРО)',
    scaleYears: 'Përvoja', scaleAmount: 'Kompensimi',
    scaleRows: [['deri 5 vjet', '1 pagë neto'], ['5 – 10 vjet', '2 paga neto'], ['10 – 15 vjet', '3 paga neto'], ['15 – 20 vjet', '4 paga neto'], ['20 – 25 vjet', '5 paga neto'], ['mbi 25 vjet', '6 paga neto']],
    disclaimer: 'Llogaritja është informative, sipas Ligjit të Marrëdhënieve të Punës. Në pensionim kompensimi është 2 paga neto mesatare. Një kontratë kolektive mund të parashikojë shumë më të favorshme. Verifikoni me kontabilist.',
    ctaTitle: 'Llogaritje automatike e pagave dhe kompensimeve',
    ctaSub: 'Facturino llogarit pagat, kontributet dhe kompensimet për të gjithë firmën.',
    ctaBtn: 'Fillo falas →',
  },
  tr: {
    netSalary: 'Ortalama net maaş (son 6 ay)', netHint: 'MKD olarak',
    years: 'Aynı işverende çalışma yılı', reason: 'Dayanak',
    redundancy: 'Teknolojik fazlalık (işten çıkarma)', retirement: 'Emeklilik',
    result: 'Kıdem tazminatı', multiplier: 'net maaş',
    formula: (m, s) => `${m} × ${fmt(s)} MKD`,
    scaleTitle: 'Teknolojik fazlalık cetveli (Madde 97 ЗРО)',
    scaleYears: 'Kıdem', scaleAmount: 'Tazminat',
    scaleRows: [['5 yıla kadar', '1 net maaş'], ['5 – 10 yıl', '2 net maaş'], ['10 – 15 yıl', '3 net maaş'], ['15 – 20 yıl', '4 net maaş'], ['20 – 25 yıl', '5 net maaş'], ['25 yıl üzeri', '6 net maaş']],
    disclaimer: 'Hesaplama bilgilendirme amaçlıdır, İş İlişkileri Kanununa göre. Emeklilikte tazminat 2 ortalama net maaştır. Toplu sözleşme daha uygun bir tutar öngörebilir. Bir muhasebeciyle doğrulayın.',
    ctaTitle: 'Maaş ve tazminatların otomatik hesabı',
    ctaSub: 'Facturino tüm şirket için maaşları, katkıları ve tazminatları hesaplar.',
    ctaBtn: 'Ücretsiz başla →',
  },
  en: {
    netSalary: 'Average net salary (last 6 months)', netHint: 'in MKD',
    years: 'Years of service with the same employer', reason: 'Basis',
    redundancy: 'Redundancy (technological surplus)', retirement: 'Retirement',
    result: 'Severance', multiplier: 'net salaries',
    formula: (m, s) => `${m} × ${fmt(s)} MKD`,
    scaleTitle: 'Redundancy scale (Art. 97 Labour Law)',
    scaleYears: 'Service', scaleAmount: 'Severance',
    scaleRows: [['up to 5 years', '1 net salary'], ['5 – 10 years', '2 net salaries'], ['10 – 15 years', '3 net salaries'], ['15 – 20 years', '4 net salaries'], ['20 – 25 years', '5 net salaries'], ['over 25 years', '6 net salaries']],
    disclaimer: 'This is an indicative calculation per the Law on Labour Relations. On retirement, severance is 2 average net salaries. A collective agreement or contract may provide a more favorable amount. Verify with an accountant.',
    ctaTitle: 'Automatic payroll & severance calculation',
    ctaSub: 'Facturino calculates salaries, contributions and allowances for your whole company.',
    ctaBtn: 'Start free →',
  },
}

export default function OtpremninaCalculator({ locale }: { locale: Locale }) {
  const t = UI[locale]
  const [net, setNet] = useState<number>(0)
  const [years, setYears] = useState<number>(0)
  const [reason, setReason] = useState<'redundancy' | 'retirement'>('redundancy')

  const { multiplier, total } = useMemo(() => {
    const m = reason === 'retirement' ? RETIREMENT_MULTIPLIER : redundancyMultiplier(years)
    return { multiplier: m, total: m * (net || 0) }
  }, [net, years, reason])

  const input = 'w-full rounded-lg border border-gray-300 px-3 py-2.5 text-base focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 outline-none'
  const label = 'block text-sm font-medium text-gray-700 mb-1.5'

  return (
    <div className="grid lg:grid-cols-2 gap-8">
      <div className="space-y-5">
        <div className="card">
          <label className={label}>{t.netSalary} <span className="text-gray-400 font-normal">({t.netHint})</span></label>
          <input type="number" min="0" step="1" className={input} value={net || ''} onChange={(e) => setNet(parseFloat(e.target.value) || 0)} />
        </div>
        <div className="card">
          <label className={label}>{t.years}</label>
          <input type="number" min="0" step="1" className={input} value={years || ''} onChange={(e) => setYears(parseInt(e.target.value) || 0)} />
        </div>
        <div className="card">
          <label className={label}>{t.reason}</label>
          <div className="flex flex-col gap-2">
            <label className="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="reason" checked={reason === 'redundancy'} onChange={() => setReason('redundancy')} />
              <span className="text-gray-700">{t.redundancy}</span>
            </label>
            <label className="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="reason" checked={reason === 'retirement'} onChange={() => setReason('retirement')} />
              <span className="text-gray-700">{t.retirement}</span>
            </label>
          </div>
        </div>
      </div>

      <div className="space-y-5">
        <div className="rounded-2xl bg-gradient-to-br from-indigo-600 to-cyan-500 p-8 text-white text-center">
          <p className="text-indigo-100 mb-1">{t.result}</p>
          <p className="text-4xl font-extrabold mb-2">{fmt(total)} <span className="text-2xl">МКД</span></p>
          <p className="text-indigo-100 text-sm">{t.formula(multiplier, net)} — {multiplier} {t.multiplier}</p>
        </div>

        <div className="card">
          <h3 className="font-bold text-gray-900 mb-3">{t.scaleTitle}</h3>
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-gray-400 border-b border-gray-100">
                <th className="py-1.5 font-semibold">{t.scaleYears}</th>
                <th className="py-1.5 font-semibold text-right">{t.scaleAmount}</th>
              </tr>
            </thead>
            <tbody>
              {t.scaleRows.map((row, i) => {
                const active = reason === 'redundancy' && redundancyMultiplier(years) === i + 1
                return (
                  <tr key={i} className={`border-b border-gray-50 ${active ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-gray-600'}`}>
                    <td className="py-1.5">{row[0]}</td>
                    <td className="py-1.5 text-right">{row[1]}</td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>

        <p className="text-xs text-gray-500 leading-relaxed">{t.disclaimer}</p>

        <div className="rounded-2xl bg-slate-50 border border-slate-100 p-6 text-center">
          <h3 className="font-bold text-gray-900 mb-1">{t.ctaTitle}</h3>
          <p className="text-gray-600 text-sm mb-4">{t.ctaSub}</p>
          <a href={`${APP_URL}/signup`} className="inline-block bg-indigo-600 text-white font-semibold px-6 py-3 rounded-xl hover:bg-indigo-700 transition-colors">{t.ctaBtn}</a>
        </div>
      </div>
    </div>
  )
}
