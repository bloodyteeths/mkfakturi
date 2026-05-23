import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/neisplatena-plata-prijavuvanje', {
    title: {
      mk: 'Неисплатена плата: Како да пријавите до Инспекторат за труд',
      en: 'Unpaid Wages North Macedonia: How to Report to Labor Inspectorate',
      sq: 'Paga e papaguar: Si të raportoni tek Inspektorati i Punës',
      tr: 'Ödenmemiş Maaş: Kuzey Makedonya Çalışma Müfettişliğine Nasıl Şikayet Edilir',
    },
    description: {
      mk: 'Чекор-по-чекор водич за пријавување неисплатена плата до Државен инспекторат за труд. Законски рокови (чл. 106 ЗРО), потребни документи, казни за работодавачот (EUR 2.000–5.000) и права на вработениот.',
      en: 'Step-by-step guide to reporting unpaid wages to the North Macedonia State Labor Inspectorate. Legal deadlines (Art. 106 Labor Law), required documents, employer penalties (EUR 2,000–5,000) and employee rights.',
      sq: 'Udhëzues hap-pas-hapi për raportimin e pagës së papaguar tek Inspektorati Shtetëror i Punës. Afatet ligjore (neni 106 i Ligjit të Punës), dokumentet e nevojshme, gjobat për punëdhënësin (EUR 2.000–5.000) dhe të drejtat e punonjësit.',
      tr: 'Ödenmemiş maaşı Kuzey Makedonya Devlet Çalışma Müfettişliğine bildirmek için adım adım rehber. Yasal süreler (md. 106 İş Kanunu), gerekli belgeler, işveren cezaları (EUR 2.000–5.000) ve çalışan hakları.',
    },
    datePublished: '2026-05-23',
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Неисплатена плата: Како да пријавите до Инспекторат за труд',
    publishDate: '23 мај 2026',
    readTime: '7 мин читање',
    intro: 'Доколку работодавачот не ви ја исплати платата навреме, имате законско право да поднесете пријава до Државниот инспекторат за труд. Овој водич детално ги покрива законските рокови за исплата на плата (чл. 106 ЗРО), постапката за пријавување, потребните документи, казните за работодавачот и правата кои ги имате како вработен во Северна Македонија.',
    sections: [
      {
        title: 'Рок за исплата на плата',
        content: 'Според член 106 од Законот за работни односи (ЗРО), работодавачот е должен да ја исплати платата најдоцна до 15-ти во наредниот месец за претходниот работен месец. Ова значи дека платата за април мора да биде исплатена најдоцна до 15 мај. Доколку работодавачот го пропушти овој рок, тоа претставува прекршок за кој Државниот инспекторат за труд може да преземе мерки. Рокот важи и за исплата на придонесите од задолжително социјално осигурување (пензиско, здравствено, за вработување), кои мора да бидат уплатени пред или истовремено со исплатата на нето платата.',
        items: null,
        steps: null,
      },
      {
        title: 'Кога може да пријавите',
        content: 'Законот предвидува неколку ситуации во кои вработениот има право да поднесе пријава до Државниот инспекторат за труд поради неисплатена плата или поврзани прекршоци:',
        items: [
          'Платата не е исплатена 1 или повеќе месеци по законскиот рок (15-ти во наредниот месец)',
          'Придонесите од задолжително социјално осигурување не се уплатени (можете да проверите на moj.ujp.gov.mk)',
          'Исплатената плата е пониска од минималната плата (20.175 МКД нето за 2026 година)',
          'Платата е исплатена но без платна листа (пејслип) со детална разбивка',
          'Работодавачот исплатил нето плата без претходно да ги уплати придонесите',
          'Работодавачот одбива да издаде потврда за плата или М1/М2 образец',
        ],
        steps: null,
      },
      {
        title: 'Чекор-по-чекор пријава',
        content: 'Постапката за пријавување на неисплатена плата е бесплатна и може да се поднесе онлајн или лично. Еве ги чекорите:',
        items: null,
        steps: [
          {
            step: 'Соберете докази',
            desc: 'Подгответе ги сите релевантни документи: договор за вработување, платни листи (пејслипи) за претходните месеци, извод од банка кој покажува дека нема уплата на плата, и секоја писмена комуникација со работодавачот за платата.',
          },
          {
            step: 'Испратете писмено барање до работодавачот',
            desc: 'Согласно чл. 181 од ЗРО, пред да поднесете пријава, испратете писмено барање до работодавачот со рок од 8 дена да ја исплати заостанатата плата. Барањето испратете го препорачано по пошта или преку е-пошта со потврда за прием. Ова е важен чекор бидејќи го документира вашиот обид за мирно решавање.',
          },
          {
            step: 'Поднесете пријава до Државен инспекторат за труд',
            desc: 'Доколку работодавачот не одговори или не ја исплати платата во рокот од 8 дена, поднесете пријава до Државниот инспекторат за труд. Пријавата може да се поднесе онлајн преку веб-страницата dit.gov.mk, по е-пошта или лично во регионалната канцеларија на инспекторатот.',
          },
          {
            step: 'Инспекторот врши контрола кај работодавачот',
            desc: 'По приемот на пријавата, инспекторот за труд е должен да изврши контрола кај работодавачот во рок од 15 дена. Инспекторот ја проверува евиденцијата за плати, МПИН образците и банкарските извештаи на работодавачот.',
          },
          {
            step: 'Инспекторот издава наредба за усогласување',
            desc: 'Доколку се утврди прекршок, инспекторот издава наредба (решение) со која му наложува на работодавачот да ја исплати заостанатата плата и придонесите во определен рок. Работодавачот може да поднесе жалба, но наредбата е извршна.',
          },
          {
            step: 'Тужба до Основен суд (ако е потребно)',
            desc: 'Доколку работодавачот и по наредбата на инспекторатот не ја исплати платата, имате право да поднесете тужба до надлежниот Основен суд. Судскиот спор за неисплатена плата е ослободен од судски такси. Рокот за тужба е 3 години од денот кога платата требало да биде исплатена.',
          },
        ],
      },
      {
        title: 'Потребни документи',
        content: 'За поднесување пријава до Инспекторатот за труд, подгответе ги следните документи:',
        items: [
          'Договор за вработување (оригинал или копија)',
          'Платни листи (пејслипи) за месеците за кои не е исплатена плата, или писмено барање до работодавачот за нивно издавање',
          'Извод од банкарска сметка кој покажува дека нема уплата на плата во соодветниот период',
          'Копија од писменото барање испратено до работодавачот (со потврда за прием)',
          'Секоја друга писмена комуникација со работодавачот (е-пошта, пораки, записници)',
          'Лична карта или пасош (за идентификација)',
        ],
        steps: null,
      },
      {
        title: 'Казни за работодавачот',
        content: 'Законот за работни односи предвидува строги казни за работодавачи кои не ја исплатуваат платата навреме:',
        items: [
          'Глоба од EUR 2.000 до 5.000 (во денарска противвредност) за правно лице кое не ја исплатило платата во законскиот рок',
          'Глоба од EUR 500 до 1.000 за одговорното лице во правното лице (директор, управител)',
          'Кривична одговорност по чл. 353 од Кривичниот законик доколку неисплатата е систематска или опфаќа поголем број вработени',
          'Законска затезна камата на неисплатениот износ од денот на доспевање до денот на исплата',
          'Можност за привремена забрана за вршење дејност при повторен прекршок',
          'Задолжителна исплата на сите заостанати придонеси со камата до ПИОМ, ФЗО и АВРМ',
        ],
        steps: null,
      },
      {
        title: 'Права на вработениот',
        content: 'Покрај правото на пријава, вработениот има дополнителни законски права при неисплатена плата:',
        items: [
          'Право на раскинување на договорот за вработување без отказен рок доколку платата не е исплатена 2 или повеќе месеци (чл. 100, ст. 1, т. 5 од ЗРО)',
          'Право на паричен надоместок за невработеност доколку работниот однос е прекинат поради инсолвентност или стечај на работодавачот',
          'Право на ретроактивна уплата на сите придонеси од задолжително социјално осигурување за целиот период на неисплата',
          'Право на законска затезна камата на целиот износ на заостанатата плата',
          'Право на надомест на штета доколку поради неисплатата претрпите финансиска загуба (на пр. кредитна рата)',
          'Право на бесплатна правна помош преку Министерството за правда доколку немате средства за адвокат',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'trudovo-pravo-osnovi', title: 'Трудово право: 10 работи што секој работодавач мора да ги знае' },
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата во Македонија: Придонеси и даноци' },
      { slug: 'mpin-obrazec', title: 'МПИН образец: Водич за месечна пресметка' },
    ],
    cta: {
      title: 'Платите навреме, без стрес',
      desc: 'Facturino автоматски пресметува плати и генерира МПИН — усогласено со македонските прописи.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Unpaid Wages North Macedonia: How to Report to Labor Inspectorate',
    publishDate: 'May 23, 2026',
    readTime: '7 min read',
    intro: 'If your employer fails to pay your salary on time, you have the legal right to file a complaint with the State Labor Inspectorate. This guide covers the legal deadlines for salary payment (Art. 106 of the Labor Relations Law), the step-by-step complaint procedure, required documents, employer penalties, and the rights you have as an employee in North Macedonia.',
    sections: [
      {
        title: 'Salary Payment Deadline',
        content: 'Under Article 106 of the Labor Relations Law (ZRO), the employer is obligated to pay salaries no later than the 15th of the following month for the previous working month. This means that the April salary must be paid by May 15 at the latest. If the employer misses this deadline, it constitutes a violation for which the State Labor Inspectorate can take action. The deadline also applies to mandatory social insurance contributions (pension, health, employment), which must be remitted before or simultaneously with the net salary payment.',
        items: null,
        steps: null,
      },
      {
        title: 'When You Can Report',
        content: 'The law provides several situations in which an employee has the right to file a complaint with the State Labor Inspectorate for unpaid wages or related violations:',
        items: [
          'Salary has not been paid for 1 or more months past the legal deadline (15th of the following month)',
          'Mandatory social insurance contributions have not been paid (you can check at moj.ujp.gov.mk)',
          'The paid salary is below the minimum wage (20,175 MKD net for 2026)',
          'Salary was paid but without a payslip with a detailed breakdown',
          'Employer paid net salary without first remitting the contributions',
          'Employer refuses to issue salary confirmation or M1/M2 form',
        ],
        steps: null,
      },
      {
        title: 'Step-by-Step Complaint Process',
        content: 'The procedure for reporting unpaid wages is free of charge and can be submitted online or in person. Here are the steps:',
        items: null,
        steps: [
          {
            step: 'Collect evidence',
            desc: 'Prepare all relevant documents: employment contract, payslips for previous months, bank statement showing no salary deposit, and any written communication with the employer regarding salary.',
          },
          {
            step: 'Send a written request to the employer',
            desc: 'Under Art. 181 of the ZRO, before filing a complaint, send a written request to the employer with an 8-day deadline to pay the outstanding salary. Send the request via registered mail or email with delivery confirmation. This is an important step as it documents your attempt at amicable resolution.',
          },
          {
            step: 'File a complaint with the State Labor Inspectorate',
            desc: 'If the employer does not respond or does not pay within the 8-day period, file a complaint with the State Labor Inspectorate. The complaint can be submitted online via dit.gov.mk, by email, or in person at the regional inspectorate office.',
          },
          {
            step: 'Inspector conducts an inspection',
            desc: 'Upon receiving the complaint, the labor inspector must conduct an inspection at the employer within 15 days. The inspector reviews the payroll records, MPIN forms, and the employer\'s banking statements.',
          },
          {
            step: 'Inspector issues a compliance order',
            desc: 'If a violation is found, the inspector issues an order directing the employer to pay the outstanding salary and contributions within a specified period. The employer can appeal, but the order is enforceable.',
          },
          {
            step: 'Lawsuit at Basic Court (if necessary)',
            desc: 'If the employer still does not pay after the inspectorate\'s order, you have the right to file a lawsuit at the competent Basic Court. Lawsuits for unpaid wages are exempt from court fees. The statute of limitations is 3 years from the day the salary was due.',
          },
        ],
      },
      {
        title: 'Required Documents',
        content: 'To file a complaint with the Labor Inspectorate, prepare the following documents:',
        items: [
          'Employment contract (original or copy)',
          'Payslips for the months when salary was not paid, or a written request to the employer for their issuance',
          'Bank account statement showing no salary deposit during the relevant period',
          'Copy of the written request sent to the employer (with delivery confirmation)',
          'Any other written communication with the employer (emails, messages, minutes)',
          'ID card or passport (for identification)',
        ],
        steps: null,
      },
      {
        title: 'Penalties for the Employer',
        content: 'The Labor Relations Law prescribes strict penalties for employers who fail to pay salaries on time:',
        items: [
          'Fine of EUR 2,000 to 5,000 (in denar equivalent) for a legal entity that has not paid salary within the legal deadline',
          'Fine of EUR 500 to 1,000 for the responsible person in the legal entity (director, manager)',
          'Criminal liability under Art. 353 of the Criminal Code if non-payment is systematic or affects a larger number of employees',
          'Statutory default interest on the unpaid amount from the due date until the date of payment',
          'Possible temporary ban on conducting business activity for repeat offenses',
          'Mandatory payment of all outstanding contributions with interest to PIOM, FZO, and AVRM',
        ],
        steps: null,
      },
      {
        title: 'Employee Rights',
        content: 'In addition to the right to file a complaint, employees have additional legal rights when wages are unpaid:',
        items: [
          'Right to terminate the employment contract without notice if salary has not been paid for 2 or more months (Art. 100, para. 1, item 5 of ZRO)',
          'Right to unemployment benefits if employment was terminated due to employer insolvency or bankruptcy',
          'Right to retroactive payment of all mandatory social insurance contributions for the entire period of non-payment',
          'Right to statutory default interest on the full amount of outstanding salary',
          'Right to compensation for damages if you suffered financial loss due to non-payment (e.g., loan installment)',
          'Right to free legal aid through the Ministry of Justice if you lack funds for a lawyer',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'trudovo-pravo-osnovi', title: 'Labor Law: 10 Things Every Employer Must Know' },
      { slug: 'presmetka-na-plata-mk', title: 'Payroll Calculation in Macedonia: Contributions and Taxes' },
      { slug: 'mpin-obrazec', title: 'MPIN Form: Monthly Payroll Filing Guide' },
    ],
    cta: {
      title: 'Pay salaries on time, stress-free',
      desc: 'Facturino automatically calculates payroll and generates MPIN — compliant with Macedonian regulations.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Paga e papaguar: Si të raportoni tek Inspektorati i Punës',
    publishDate: '23 maj 2026',
    readTime: '7 min lexim',
    intro: 'Nëse punëdhënësi juaj nuk jua paguan pagën në kohë, keni të drejtën ligjore të paraqisni ankesë tek Inspektorati Shtetëror i Punës. Ky udhëzues mbulon afatet ligjore për pagesën e pagës (neni 106 i Ligjit të Marrëdhënieve të Punës), procedurën hap-pas-hapi të ankesës, dokumentet e nevojshme, gjobat për punëdhënësin dhe të drejtat tuaja si punonjës në Maqedoninë e Veriut.',
    sections: [
      {
        title: 'Afati për pagesën e pagës',
        content: 'Sipas nenit 106 të Ligjit të Marrëdhënieve të Punës (ZRO), punëdhënësi është i detyruar ta paguajë pagën jo më vonë se data 15 e muajit pasues për muajin e kaluar të punës. Kjo do të thotë që paga e prillit duhet të paguhet deri më 15 maj më së voni. Nëse punëdhënësi e kalon këtë afat, kjo përbën shkelje për të cilën Inspektorati Shtetëror i Punës mund të ndërmarrë veprime. Afati vlen edhe për kontributet e detyrueshme të sigurimit social (pensional, shëndetësor, punësimi), të cilat duhet të dërgohen para ose njëkohësisht me pagesën e pagës neto.',
        items: null,
        steps: null,
      },
      {
        title: 'Kur mund të raportoni',
        content: 'Ligji parashikon disa situata në të cilat punonjësi ka të drejtë të paraqesë ankesë tek Inspektorati Shtetëror i Punës për pagë të papaguar ose shkelje të ngjashme:',
        items: [
          'Paga nuk është paguar 1 ose më shumë muaj pas afatit ligjor (data 15 e muajit pasues)',
          'Kontributet e detyrueshme të sigurimit social nuk janë paguar (mund ta kontrolloni në moj.ujp.gov.mk)',
          'Paga e paguar është nën pagën minimale (20.175 MKD neto për 2026)',
          'Paga u pagua por pa fletëpagesë me ndarje të detajuar',
          'Punëdhënësi pagoi pagën neto pa dërguar më parë kontributet',
          'Punëdhënësi refuzon të lëshojë konfirmim page ose formular M1/M2',
        ],
        steps: null,
      },
      {
        title: 'Procedura hap-pas-hapi',
        content: 'Procedura për raportimin e pagës së papaguar është falas dhe mund të dorëzohet online ose personalisht. Ja hapat:',
        items: null,
        steps: [
          {
            step: 'Mblidhni dëshmi',
            desc: 'Përgatitni të gjitha dokumentet relevante: kontratën e punës, fletëpagesat për muajt e kaluar, ekstrakt bankar që tregon se nuk ka depozitim page, dhe çdo komunikim të shkruar me punëdhënësin lidhur me pagën.',
          },
          {
            step: 'Dërgoni kërkesë të shkruar punëdhënësit',
            desc: 'Sipas nenit 181 të ZRO, para paraqitjes së ankesës, dërgoni kërkesë të shkruar punëdhënësit me afat 8-ditor për ta paguar pagën e vonuar. Dërgojeni kërkesën me postë të rekomanduar ose email me konfirmim pranimi. Ky është hap i rëndësishëm pasi dokumenton përpjekjen tuaj për zgjidhje paqësore.',
          },
          {
            step: 'Paraqitni ankesë tek Inspektorati Shtetëror i Punës',
            desc: 'Nëse punëdhënësi nuk përgjigjet ose nuk paguan brenda periudhës 8-ditore, paraqitni ankesë tek Inspektorati Shtetëror i Punës. Ankesa mund të dorëzohet online përmes dit.gov.mk, me email ose personalisht në zyrën rajonale të inspektoratit.',
          },
          {
            step: 'Inspektori kryen kontroll',
            desc: 'Pas pranimit të ankesës, inspektori i punës duhet të kryejë kontroll te punëdhënësi brenda 15 ditësh. Inspektori shqyrton evidencat e pagave, formularët MPIN dhe pasqyrat bankare të punëdhënësit.',
          },
          {
            step: 'Inspektori lëshon urdhër përputhshmërie',
            desc: 'Nëse konstatohet shkelje, inspektori lëshon urdhër (vendim) me të cilin i urdhëron punëdhënësit ta paguajë pagën e vonuar dhe kontributet brenda afatit të caktuar. Punëdhënësi mund të ankohet, por urdhëri është i ekzekutueshëm.',
          },
          {
            step: 'Padi në Gjykatën Themelore (nëse nevojitet)',
            desc: 'Nëse punëdhënësi ende nuk paguan pas urdhërit të inspektoratit, keni të drejtë të paraqisni padi në Gjykatën Themelore kompetente. Paditë për pagë të papaguar janë të liruara nga taksat gjyqësore. Afati i parashkrimit është 3 vjet nga dita kur paga ka qenë e detyrueshme.',
          },
        ],
      },
      {
        title: 'Dokumentet e nevojshme',
        content: 'Për paraqitjen e ankesës tek Inspektorati i Punës, përgatitni dokumentet vijuese:',
        items: [
          'Kontrata e punës (origjinali ose kopja)',
          'Fletëpagesat për muajt kur paga nuk u pagua, ose kërkesë e shkruar punëdhënësit për lëshimin e tyre',
          'Ekstrakt i llogarisë bankare që tregon se nuk ka depozitim page gjatë periudhës përkatëse',
          'Kopje e kërkesës së shkruar dërguar punëdhënësit (me konfirmim pranimi)',
          'Çdo komunikim tjetër i shkruar me punëdhënësin (email, mesazhe, procesverbale)',
          'Letërnjoftim ose pasaportë (për identifikim)',
        ],
        steps: null,
      },
      {
        title: 'Gjobat për punëdhënësin',
        content: 'Ligji i Marrëdhënieve të Punës parashikon gjoba të rrepta për punëdhënësit që nuk paguajnë pagën në kohë:',
        items: [
          'Gjobë EUR 2.000 deri 5.000 (ekuivalent në denarë) për personin juridik që nuk ka paguar pagën brenda afatit ligjor',
          'Gjobë EUR 500 deri 1.000 për personin përgjegjës në personin juridik (drejtor, menaxher)',
          'Përgjegjësi penale sipas nenit 353 të Kodit Penal nëse mospagesa është sistematike ose prek numër më të madh punonjësish',
          'Kamatë ligjore vonesë mbi shumën e papaguar nga data e maturimit deri në datën e pagesës',
          'Ndalim i mundshëm i përkohshëm i aktivitetit tregtar për shkelje të përsëritura',
          'Pagesë e detyrueshme e të gjitha kontributeve të vonuara me kamatë ndaj PIOM, FZO dhe AVRM',
        ],
        steps: null,
      },
      {
        title: 'Të drejtat e punonjësit',
        content: 'Përveç të drejtës për ankesë, punonjësi ka të drejta shtesë ligjore kur paga nuk paguhet:',
        items: [
          'E drejtë për ndërprerje të kontratës së punës pa njoftim nëse paga nuk është paguar 2 ose më shumë muaj (neni 100, par. 1, pika 5 e ZRO)',
          'E drejtë për kompensim papunësie nëse punësimi u ndërpre për shkak të falimentimit të punëdhënësit',
          'E drejtë për pagesë retroaktive të të gjitha kontributeve të detyrueshme për gjithë periudhën e mospagesës',
          'E drejtë për kamatë ligjore vonesë mbi shumën e plotë të pagës së vonuar',
          'E drejtë për kompensim dëmi nëse keni pësuar humbje financiare nga mospagesa (p.sh. këst kredie)',
          'E drejtë për ndihmë juridike falas përmes Ministrisë së Drejtësisë nëse nuk keni mjete për avokat',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'trudovo-pravo-osnovi', title: "E drejta e punës: 10 gjëra që çdo punëdhënës duhet t'i dijë" },
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pagës në Maqedoni: Kontributet dhe tatimet' },
      { slug: 'mpin-obrazec', title: 'Formulari MPIN: Udhëzues për llogaritjen mujore' },
    ],
    cta: {
      title: 'Paguani pagat në kohë, pa stres',
      desc: 'Facturino llogarit automatikisht pagat dhe gjeneron MPIN — në pajtim me rregulloret maqedonase.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Ödenmemiş Maaş: Kuzey Makedonya Çalışma Müfettişliğine Nasıl Şikayet Edilir',
    publishDate: '23 Mayıs 2026',
    readTime: '7 dk okuma',
    intro: 'İşvereniniz maaşınızı zamanında ödemezse, Devlet Çalışma Müfettişliğine şikayette bulunma yasal hakkınız vardır. Bu rehber, maaş ödemesi için yasal süreleri (İş İlişkileri Kanunu md. 106), adım adım şikayet prosedürünü, gerekli belgeleri, işveren cezalarını ve Kuzey Makedonya\'da bir çalışan olarak sahip olduğunuz hakları kapsamaktadır.',
    sections: [
      {
        title: 'Maaş Ödeme Süresi',
        content: 'İş İlişkileri Kanunu\'nun (ZRO) 106. maddesine göre, işveren maaşı önceki çalışma ayı için takip eden ayın en geç 15\'ine kadar ödemekle yükümlüdür. Bu, Nisan maaşının en geç 15 Mayıs\'a kadar ödenmesi gerektiği anlamına gelir. İşveren bu süreyi kaçırırsa, Devlet Çalışma Müfettişliği\'nin işlem yapabileceği bir ihlal oluşturur. Süre, zorunlu sosyal sigorta primleri (emeklilik, sağlık, istihdam) için de geçerlidir; bunlar net maaş ödemesinden önce veya eş zamanlı olarak ödenmelidir.',
        items: null,
        steps: null,
      },
      {
        title: 'Ne Zaman Şikayette Bulunabilirsiniz',
        content: 'Kanun, çalışanın ödenmemiş ücretler veya ilgili ihlaller nedeniyle Devlet Çalışma Müfettişliğine şikayette bulunma hakkına sahip olduğu çeşitli durumları öngörmektedir:',
        items: [
          'Maaş yasal süreden (takip eden ayın 15\'i) 1 veya daha fazla ay sonra hala ödenmemiş',
          'Zorunlu sosyal sigorta primleri ödenmemiş (moj.ujp.gov.mk üzerinden kontrol edilebilir)',
          'Ödenen maaş asgari ücretin altında (2026 için net 20.175 MKD)',
          'Maaş ödendi ancak ayrıntılı dökümü içeren bordro belgesi verilmedi',
          'İşveren önce primleri ödemeden net maaşı ödedi',
          'İşveren maaş onay belgesi veya M1/M2 formu vermeyi reddediyor',
        ],
        steps: null,
      },
      {
        title: 'Adım Adım Şikayet Süreci',
        content: 'Ödenmemiş maaşları bildirme prosedürü ücretsizdir ve çevrimiçi veya şahsen yapılabilir. İşte adımlar:',
        items: null,
        steps: [
          {
            step: 'Kanıt toplayın',
            desc: 'Tüm ilgili belgeleri hazırlayın: iş sözleşmesi, önceki aylara ait bordro belgeleri, maaş yatırılmadığını gösteren banka hesap özeti ve maaşla ilgili işverenle yapılan tüm yazışmalar.',
          },
          {
            step: 'İşverene yazılı talep gönderin',
            desc: 'ZRO\'nun 181. maddesine göre, şikayette bulunmadan önce işverene ödenmemiş maaşı ödemesi için 8 günlük süre tanıyan yazılı bir talep gönderin. Talebi taahhütlü posta veya teslim onaylı e-posta ile gönderin. Bu, dostane çözüm girişiminizi belgelediği için önemli bir adımdır.',
          },
          {
            step: 'Devlet Çalışma Müfettişliğine şikayette bulunun',
            desc: 'İşveren yanıt vermezse veya 8 günlük süre içinde ödemezse, Devlet Çalışma Müfettişliğine şikayette bulunun. Şikayet dit.gov.mk web sitesi üzerinden çevrimiçi, e-posta ile veya bölgesel müfettişlik ofisinde şahsen yapılabilir.',
          },
          {
            step: 'Müfettiş denetim yapar',
            desc: 'Şikayeti aldıktan sonra, çalışma müfettişi 15 gün içinde işverende denetim yapmak zorundadır. Müfettiş, bordro kayıtlarını, MPIN formlarını ve işverenin banka hesap özetlerini inceler.',
          },
          {
            step: 'Müfettiş uyum emri verir',
            desc: 'İhlal tespit edilirse müfettiş, işverene ödenmemiş maaş ve primleri belirtilen süre içinde ödemesini emreden bir emir (karar) verir. İşveren itiraz edebilir, ancak emir uygulanabilirdir.',
          },
          {
            step: 'Temel Mahkemede dava (gerekirse)',
            desc: 'Müfettişlik emrinden sonra işveren hala ödemezse, yetkili Temel Mahkemede dava açma hakkınız vardır. Ödenmemiş maaş davaları mahkeme harçlarından muaftır. Zamanaşımı süresi, maaşın ödenmesi gereken günden itibaren 3 yıldır.',
          },
        ],
      },
      {
        title: 'Gerekli Belgeler',
        content: 'Çalışma Müfettişliğine şikayette bulunmak için aşağıdaki belgeleri hazırlayın:',
        items: [
          'İş sözleşmesi (orijinal veya kopya)',
          'Maaşın ödenmediği aylara ait bordro belgeleri veya işverene düzenlenmesi için yazılı talep',
          'İlgili dönemde maaş yatırılmadığını gösteren banka hesap özeti',
          'İşverene gönderilen yazılı talebin kopyası (teslim onayı ile)',
          'İşverenle yapılan diğer tüm yazışmalar (e-postalar, mesajlar, tutanaklar)',
          'Kimlik kartı veya pasaport (kimlik tespiti için)',
        ],
        steps: null,
      },
      {
        title: 'İşveren İçin Cezalar',
        content: 'İş İlişkileri Kanunu, maaşları zamanında ödemeyen işverenler için katı cezalar öngörmektedir:',
        items: [
          'Maaşı yasal sürede ödemeyen tüzel kişi için EUR 2.000 ila 5.000 (denar karşılığı) para cezası',
          'Tüzel kişideki sorumlu kişi (müdür, yönetici) için EUR 500 ila 1.000 para cezası',
          'Ödeme yapılmaması sistematik ise veya çok sayıda çalışanı etkiliyorsa Ceza Kanunu md. 353 kapsamında cezai sorumluluk',
          'Vadeden ödeme gününe kadar ödenmemiş tutar üzerinden yasal temerrüt faizi',
          'Tekrarlayan ihlallerde ticari faaliyette geçici yasak olasılığı',
          'PIOM, FZO ve AVRM\'ye faizi ile birlikte tüm gecikmiş primlerin zorunlu ödemesi',
        ],
        steps: null,
      },
      {
        title: 'Çalışan Hakları',
        content: 'Şikayet hakkının yanı sıra, çalışanın maaş ödenmediğinde ek yasal hakları vardır:',
        items: [
          'Maaş 2 veya daha fazla ay ödenmemişse ihbar süresiz iş sözleşmesini feshetme hakkı (ZRO md. 100, f. 1, b. 5)',
          'İşverenin iflası nedeniyle iş akdi sona ermişse işsizlik ödeneği hakkı',
          'Ödeme yapılmayan tüm dönem için zorunlu sosyal sigorta primlerinin geriye dönük ödenmesini talep hakkı',
          'Gecikmiş maaşın tamamı üzerinden yasal temerrüt faizi hakkı',
          'Ödeme yapılmaması nedeniyle mali kayıp yaşandıysa (örn. kredi taksidi) tazminat hakkı',
          'Avukat tutacak imkanınız yoksa Adalet Bakanlığı aracılığıyla ücretsiz hukuki yardım hakkı',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'trudovo-pravo-osnovi', title: 'İş hukuku: Her işverenin bilmesi gereken 10 şey' },
      { slug: 'presmetka-na-plata-mk', title: "Makedonya'da bordro hesaplama: Katkılar ve vergiler" },
      { slug: 'mpin-obrazec', title: 'MPIN formu: Aylık hesaplama rehberi' },
    ],
    cta: {
      title: 'Maaşları zamanında, stressiz ödeyin',
      desc: 'Facturino bordroyu otomatik hesaplar ve MPIN oluşturur — Makedon düzenlemelerine uygun.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function NeisplatenaPlataPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const blogLabel = { mk: 'Блог', en: 'Blog', sq: 'Blog', tr: 'Blog' }[locale]
  const homeLabel = { mk: 'Почетна', en: 'Home', sq: 'Kryefaqja', tr: 'Ana Sayfa' }[locale]

  const articleLd = articleJsonLd({
    locale,
    slug: 'neisplatena-plata-prijavuvanje',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['неисплатена плата', 'инспекторат за труд', 'unpaid wages', 'labor inspectorate'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/neisplatena-plata-prijavuvanje` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
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
