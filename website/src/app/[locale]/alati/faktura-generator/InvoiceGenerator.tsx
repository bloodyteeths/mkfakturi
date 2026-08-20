'use client'

import { useMemo, useState } from 'react'
import { type Locale } from '@/i18n/locales'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'
const VAT_RATES = [18, 10, 5, 0]

type Item = { desc: string; qty: number; price: number; vat: number }

const UI: Record<Locale, {
  heroTitle: string; heroSub: string
  seller: string; buyer: string; company: string; address: string; edb: string; embs: string; bank: string; account: string
  invoice: string; number: string; date: string; due: string; place: string
  items: string; itemDesc: string; qty: string; price: string; vatRate: string; lineTotal: string; addItem: string; remove: string
  notes: string; notesPh: string
  subtotal: string; vat: string; total: string
  print: string; reset: string
  ctaTitle: string; ctaSub: string; ctaBtn: string
  previewTitle: string
}> = {
  mk: {
    heroTitle: 'Бесплатен генератор на фактури', heroSub: 'Направете професионална фактура за неколку минути — со ДДВ пресметка според МК стапки. Испечатете или зачувајте како PDF. Без регистрација.',
    seller: 'Продавач (издавач)', buyer: 'Купувач', company: 'Име на компанија', address: 'Адреса', edb: 'ЕДБ (даночен број)', embs: 'ЕМБС', bank: 'Банка', account: 'Трансакциска сметка',
    invoice: 'Фактура', number: 'Број на фактура', date: 'Датум', due: 'Рок на плаќање', place: 'Место',
    items: 'Ставки', itemDesc: 'Опис', qty: 'Кол.', price: 'Цена', vatRate: 'ДДВ %', lineTotal: 'Износ', addItem: '+ Додади ставка', remove: 'Отстрани',
    notes: 'Забелешка', notesPh: 'пр. Плаќање во рок од 15 дена на горенаведената сметка.',
    subtotal: 'Основица', vat: 'ДДВ', total: 'Вкупно за плаќање',
    print: '🖨 Печати / Зачувај PDF', reset: 'Ресетирај',
    ctaTitle: 'Испраќајте фактури автоматски со Facturino', ctaSub: 'Зачувувајте клиенти, следете плаќања и издавајте е-фактури — сè на едно место.', ctaBtn: 'Започни бесплатно →',
    previewTitle: 'Преглед на фактура',
  },
  sq: {
    heroTitle: 'Gjenerator falas i faturave', heroSub: 'Krijoni një faturë profesionale për pak minuta — me llogaritje TVSH sipas normave MK. Printoni ose ruani si PDF. Pa regjistrim.',
    seller: 'Shitësi (lëshuesi)', buyer: 'Blerësi', company: 'Emri i kompanisë', address: 'Adresa', edb: 'EDB (numri tatimor)', embs: 'EMBS', bank: 'Banka', account: 'Llogaria transaksionale',
    invoice: 'Faturë', number: 'Numri i faturës', date: 'Data', due: 'Afati i pagesës', place: 'Vendi',
    items: 'Artikuj', itemDesc: 'Përshkrimi', qty: 'Sasia', price: 'Çmimi', vatRate: 'TVSH %', lineTotal: 'Vlera', addItem: '+ Shto artikull', remove: 'Hiq',
    notes: 'Shënim', notesPh: 'p.sh. Pagesa brenda 15 ditësh në llogarinë e mësipërme.',
    subtotal: 'Baza', vat: 'TVSH', total: 'Gjithsej për pagesë',
    print: '🖨 Printo / Ruaj PDF', reset: 'Rivendos',
    ctaTitle: 'Dërgoni fatura automatikisht me Facturino', ctaSub: 'Ruani klientë, ndiqni pagesat dhe lëshoni e-fatura — të gjitha në një vend.', ctaBtn: 'Fillo falas →',
    previewTitle: 'Pamja e faturës',
  },
  tr: {
    heroTitle: 'Ücretsiz fatura oluşturucu', heroSub: 'Birkaç dakikada profesyonel bir fatura oluşturun — MK oranlarına göre KDV hesabıyla. Yazdırın veya PDF olarak kaydedin. Kayıt gerekmez.',
    seller: 'Satıcı (düzenleyen)', buyer: 'Alıcı', company: 'Şirket adı', address: 'Adres', edb: 'EDB (vergi no)', embs: 'EMBS', bank: 'Banka', account: 'İşlem hesabı',
    invoice: 'Fatura', number: 'Fatura no', date: 'Tarih', due: 'Vade', place: 'Yer',
    items: 'Kalemler', itemDesc: 'Açıklama', qty: 'Adet', price: 'Fiyat', vatRate: 'KDV %', lineTotal: 'Tutar', addItem: '+ Kalem ekle', remove: 'Kaldır',
    notes: 'Not', notesPh: 'örn. Ödeme 15 gün içinde yukarıdaki hesaba.',
    subtotal: 'Matrah', vat: 'KDV', total: 'Ödenecek toplam',
    print: '🖨 Yazdır / PDF kaydet', reset: 'Sıfırla',
    ctaTitle: 'Facturino ile faturaları otomatik gönderin', ctaSub: 'Müşterileri kaydedin, ödemeleri takip edin ve e-fatura düzenleyin — hepsi tek yerde.', ctaBtn: 'Ücretsiz başla →',
    previewTitle: 'Fatura önizleme',
  },
  en: {
    heroTitle: 'Free invoice generator', heroSub: 'Create a professional invoice in minutes — with VAT calculated at Macedonian rates. Print or save as PDF. No registration.',
    seller: 'Seller (issuer)', buyer: 'Buyer', company: 'Company name', address: 'Address', edb: 'EDB (tax number)', embs: 'EMBS', bank: 'Bank', account: 'Bank account',
    invoice: 'Invoice', number: 'Invoice number', date: 'Date', due: 'Due date', place: 'Place',
    items: 'Line items', itemDesc: 'Description', qty: 'Qty', price: 'Price', vatRate: 'VAT %', lineTotal: 'Amount', addItem: '+ Add item', remove: 'Remove',
    notes: 'Note', notesPh: 'e.g. Payment within 15 days to the account above.',
    subtotal: 'Net', vat: 'VAT', total: 'Total due',
    print: '🖨 Print / Save PDF', reset: 'Reset',
    ctaTitle: 'Send invoices automatically with Facturino', ctaSub: 'Save customers, track payments and issue e-invoices — all in one place.', ctaBtn: 'Start free →',
    previewTitle: 'Invoice preview',
  },
}

function fmt(n: number): string {
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0)
}

type Party = { company: string; address: string; edb: string; embs: string; bank: string; account: string }

export default function InvoiceGenerator({ locale }: { locale: Locale }) {
  const t = UI[locale]
  const [seller, setSeller] = useState<Party>({ company: '', address: '', edb: '', embs: '', bank: '', account: '' })
  const [buyer, setBuyer] = useState<Party>({ company: '', address: '', edb: '', embs: '', bank: '', account: '' })
  const [meta, setMeta] = useState({ number: '', date: '', due: '', place: 'Скопје' })
  const [items, setItems] = useState<Item[]>([{ desc: '', qty: 1, price: 0, vat: 18 }])
  const [notes, setNotes] = useState('')

  const totals = useMemo(() => {
    let net = 0
    const vatByRate: Record<number, number> = {}
    for (const it of items) {
      const lineNet = (it.qty || 0) * (it.price || 0)
      net += lineNet
      vatByRate[it.vat] = (vatByRate[it.vat] || 0) + lineNet * (it.vat / 100)
    }
    const vatTotal = Object.values(vatByRate).reduce((a, b) => a + b, 0)
    return { net, vatByRate, vatTotal, grand: net + vatTotal }
  }, [items])

  const setItem = (i: number, patch: Partial<Item>) =>
    setItems((prev) => prev.map((it, idx) => (idx === i ? { ...it, ...patch } : it)))

  const input = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 outline-none'
  const label = 'block text-xs font-medium text-gray-500 mb-1'

  return (
    <div className="grid lg:grid-cols-2 gap-8">
      {/* ─── FORM (hidden on print) ─── */}
      <div className="no-print space-y-6">
        <div className="card">
          <h3 className="font-bold text-gray-900 mb-3">{t.seller}</h3>
          <div className="grid grid-cols-2 gap-3">
            <div className="col-span-2"><label className={label}>{t.company}</label><input className={input} value={seller.company} onChange={(e) => setSeller({ ...seller, company: e.target.value })} /></div>
            <div className="col-span-2"><label className={label}>{t.address}</label><input className={input} value={seller.address} onChange={(e) => setSeller({ ...seller, address: e.target.value })} /></div>
            <div><label className={label}>{t.edb}</label><input className={input} value={seller.edb} onChange={(e) => setSeller({ ...seller, edb: e.target.value })} /></div>
            <div><label className={label}>{t.embs}</label><input className={input} value={seller.embs} onChange={(e) => setSeller({ ...seller, embs: e.target.value })} /></div>
            <div><label className={label}>{t.bank}</label><input className={input} value={seller.bank} onChange={(e) => setSeller({ ...seller, bank: e.target.value })} /></div>
            <div><label className={label}>{t.account}</label><input className={input} value={seller.account} onChange={(e) => setSeller({ ...seller, account: e.target.value })} /></div>
          </div>
        </div>

        <div className="card">
          <h3 className="font-bold text-gray-900 mb-3">{t.buyer}</h3>
          <div className="grid grid-cols-2 gap-3">
            <div className="col-span-2"><label className={label}>{t.company}</label><input className={input} value={buyer.company} onChange={(e) => setBuyer({ ...buyer, company: e.target.value })} /></div>
            <div className="col-span-2"><label className={label}>{t.address}</label><input className={input} value={buyer.address} onChange={(e) => setBuyer({ ...buyer, address: e.target.value })} /></div>
            <div><label className={label}>{t.edb}</label><input className={input} value={buyer.edb} onChange={(e) => setBuyer({ ...buyer, edb: e.target.value })} /></div>
            <div><label className={label}>{t.embs}</label><input className={input} value={buyer.embs} onChange={(e) => setBuyer({ ...buyer, embs: e.target.value })} /></div>
          </div>
        </div>

        <div className="card">
          <h3 className="font-bold text-gray-900 mb-3">{t.invoice}</h3>
          <div className="grid grid-cols-2 gap-3">
            <div><label className={label}>{t.number}</label><input className={input} value={meta.number} onChange={(e) => setMeta({ ...meta, number: e.target.value })} /></div>
            <div><label className={label}>{t.place}</label><input className={input} value={meta.place} onChange={(e) => setMeta({ ...meta, place: e.target.value })} /></div>
            <div><label className={label}>{t.date}</label><input type="date" className={input} value={meta.date} onChange={(e) => setMeta({ ...meta, date: e.target.value })} /></div>
            <div><label className={label}>{t.due}</label><input type="date" className={input} value={meta.due} onChange={(e) => setMeta({ ...meta, due: e.target.value })} /></div>
          </div>
        </div>

        <div className="card">
          <h3 className="font-bold text-gray-900 mb-3">{t.items}</h3>
          <div className="space-y-3">
            {items.map((it, i) => (
              <div key={i} className="grid grid-cols-12 gap-2 items-end">
                <div className="col-span-12 sm:col-span-5"><label className={label}>{t.itemDesc}</label><input className={input} value={it.desc} onChange={(e) => setItem(i, { desc: e.target.value })} /></div>
                <div className="col-span-3 sm:col-span-2"><label className={label}>{t.qty}</label><input type="number" min="0" step="0.01" className={input} value={it.qty} onChange={(e) => setItem(i, { qty: parseFloat(e.target.value) || 0 })} /></div>
                <div className="col-span-4 sm:col-span-2"><label className={label}>{t.price}</label><input type="number" min="0" step="0.01" className={input} value={it.price} onChange={(e) => setItem(i, { price: parseFloat(e.target.value) || 0 })} /></div>
                <div className="col-span-3 sm:col-span-2"><label className={label}>{t.vatRate}</label>
                  <select className={input} value={it.vat} onChange={(e) => setItem(i, { vat: parseInt(e.target.value) })}>
                    {VAT_RATES.map((r) => <option key={r} value={r}>{r}%</option>)}
                  </select>
                </div>
                <div className="col-span-2 sm:col-span-1">
                  <button onClick={() => setItems(items.filter((_, idx) => idx !== i))} className="text-rose-500 hover:text-rose-700 text-sm py-2" title={t.remove} aria-label={t.remove}>✕</button>
                </div>
              </div>
            ))}
          </div>
          <button onClick={() => setItems([...items, { desc: '', qty: 1, price: 0, vat: 18 }])} className="mt-3 text-indigo-600 hover:text-indigo-800 text-sm font-medium">{t.addItem}</button>
        </div>

        <div className="card">
          <label className={label}>{t.notes}</label>
          <textarea className={input} rows={2} placeholder={t.notesPh} value={notes} onChange={(e) => setNotes(e.target.value)} />
        </div>

        <div className="flex gap-3">
          <button onClick={() => window.print()} className="px-6 py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition-colors">{t.print}</button>
        </div>
      </div>

      {/* ─── PREVIEW (printable) ─── */}
      <div>
        <p className="no-print text-sm text-gray-400 mb-2">{t.previewTitle}</p>
        <div id="invoice-print" className="bg-white border border-gray-200 rounded-xl p-8 shadow-sm text-sm text-gray-800">
          <div className="flex justify-between items-start mb-8">
            <div>
              <div className="text-xs uppercase tracking-wider text-gray-400 mb-1">{t.seller}</div>
              <div className="font-bold text-gray-900">{seller.company || '—'}</div>
              <div className="text-gray-600 whitespace-pre-line">{seller.address}</div>
              {seller.edb && <div className="text-gray-600">{t.edb}: {seller.edb}</div>}
              {seller.embs && <div className="text-gray-600">{t.embs}: {seller.embs}</div>}
              {seller.account && <div className="text-gray-600">{seller.bank} {seller.account}</div>}
            </div>
            <div className="text-right">
              <div className="text-2xl font-extrabold text-indigo-600">{t.invoice}</div>
              {meta.number && <div className="font-semibold text-gray-900">№ {meta.number}</div>}
              {meta.date && <div className="text-gray-600">{t.date}: {meta.date}</div>}
              {meta.due && <div className="text-gray-600">{t.due}: {meta.due}</div>}
              {meta.place && <div className="text-gray-600">{meta.place}</div>}
            </div>
          </div>

          <div className="mb-6">
            <div className="text-xs uppercase tracking-wider text-gray-400 mb-1">{t.buyer}</div>
            <div className="font-bold text-gray-900">{buyer.company || '—'}</div>
            <div className="text-gray-600 whitespace-pre-line">{buyer.address}</div>
            {buyer.edb && <div className="text-gray-600">{t.edb}: {buyer.edb}</div>}
          </div>

          <table className="w-full mb-6">
            <thead>
              <tr className="border-b-2 border-gray-200 text-left text-xs uppercase text-gray-400">
                <th className="py-2">{t.itemDesc}</th>
                <th className="py-2 text-right">{t.qty}</th>
                <th className="py-2 text-right">{t.price}</th>
                <th className="py-2 text-right">{t.vatRate}</th>
                <th className="py-2 text-right">{t.lineTotal}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((it, i) => (
                <tr key={i} className="border-b border-gray-100">
                  <td className="py-2">{it.desc || '—'}</td>
                  <td className="py-2 text-right">{fmt(it.qty)}</td>
                  <td className="py-2 text-right">{fmt(it.price)}</td>
                  <td className="py-2 text-right">{it.vat}%</td>
                  <td className="py-2 text-right">{fmt(it.qty * it.price)}</td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="flex justify-end">
            <div className="w-64 space-y-1">
              <div className="flex justify-between text-gray-600"><span>{t.subtotal}</span><span>{fmt(totals.net)} ден</span></div>
              {Object.entries(totals.vatByRate).filter(([, v]) => v > 0).map(([rate, v]) => (
                <div key={rate} className="flex justify-between text-gray-600"><span>{t.vat} {rate}%</span><span>{fmt(v)} ден</span></div>
              ))}
              <div className="flex justify-between font-extrabold text-gray-900 text-base border-t border-gray-200 pt-2 mt-2"><span>{t.total}</span><span>{fmt(totals.grand)} ден</span></div>
            </div>
          </div>

          {notes && <div className="mt-8 pt-4 border-t border-gray-100 text-gray-500 text-xs whitespace-pre-line">{notes}</div>}
        </div>

        {/* Conversion CTA */}
        <div className="no-print mt-6 rounded-2xl bg-gradient-to-br from-indigo-600 to-cyan-500 p-6 text-center text-white">
          <h3 className="text-xl font-bold mb-1">{t.ctaTitle}</h3>
          <p className="text-indigo-100 text-sm mb-4">{t.ctaSub}</p>
          <a href={`${APP_URL}/signup`} className="inline-block bg-white text-indigo-700 font-bold px-6 py-3 rounded-xl hover:bg-indigo-50 transition-colors">{t.ctaBtn}</a>
        </div>
      </div>

      <style jsx global>{`
        @media print {
          .no-print { display: none !important; }
          #invoice-print { border: none !important; box-shadow: none !important; padding: 0 !important; }
          body { background: white !important; }
        }
      `}</style>
    </div>
  )
}
