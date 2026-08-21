import { defaultLocale, isLocale, type Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

const BASE_URL = 'https://www.facturino.mk'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/recnik', {
    title: {
      mk: 'Речник на сметководствени и деловни поими (Македонија)',
      en: 'Macedonian Accounting & Business Glossary',
      sq: 'Fjalor i termave kontabël dhe të biznesit (Maqedoni)',
      tr: 'Makedonya Muhasebe ve İş Terimleri Sözlüğü',
    },
    description: {
      mk: 'Бесплатен речник на македонски сметководствени, даночни и деловни поими — ДДВ, фактура, е-фактура, биланс, придонеси, ДООЕЛ и повеќе, со јасни дефиниции.',
      en: 'Free dictionary of Macedonian accounting, tax and business terms — VAT, invoice, e-invoice, balance sheet, contributions, DOOEL and more, with clear definitions.',
      sq: 'Fjalor falas i termave kontabël, tatimorë dhe të biznesit maqedonas — TVSH, faturë, e-faturë, bilanc, kontribute, DOOEL e më shumë, me përkufizime të qarta.',
      tr: 'Ücretsiz Makedonya muhasebe, vergi ve iş terimleri sözlüğü — KDV, fatura, e-fatura, bilanço, primler, DOOEL ve daha fazlası, net tanımlarla.',
    },
  })
}

const copy = {
  mk: {
    hero: {
      badge: 'Речник',
      title: 'Речник на сметководствени и деловни поими',
      sub: 'Бесплатен речник на најчестите македонски сметководствени, даночни и деловни поими — со јасни и точни дефиниции. Идеален за претприемачи, сметководители и сопственици на мали фирми.',
    },
    categories: [
      {
        heading: 'ДДВ и даноци',
        terms: [
          { term: 'ДДВ', def: 'Данок на додадена вредност е индиректен потрошувачки данок што се пресметува на прометот на добра и услуги. Во Северна Македонија се применуваат три стапки: општа од 18%, повластена од 5% и повластена од 10%. Данокот го плаќа крајниот потрошувач, а фирмите го наплаќаат и уплаќаат во буџетот.' },
          { term: 'ДДВ обврзник', def: 'Правно или физичко лице кое е регистрирано за целите на ДДВ и има обврска да пресметува, наплаќа и уплаќа ДДВ. Регистрацијата е задолжителна кога годишниот промет надмине 2.000.000 МКД, а можна е и доброволна. ДДВ обврзникот има право на одбивка на претходниот данок.' },
          { term: 'ЕДБ', def: 'Единствен даночен број е идентификациски број што Управата за јавни приходи (УЈП) го доделува на секој даночен обврзник. Се користи во сите даночни постапки, пријави и на фактурите. Секоја фирма и самостоен вршител на дејност добива ЕДБ при регистрација.' },
          { term: 'Персонален данок на доход', def: 'Данок што се плаќа на приходите на физичките лица — плати, хонорари, закупнини и слично. Во Северна Македонија стапката е рамна и изнесува 10%. Данокот најчесто го задржува и уплаќа исплатувачот на приходот.' },
          { term: 'Данок на добивка', def: 'Данок што се плаќа на добивката (профитот) на правните лица. Стапката во Северна Македонија е 10% од оданочивата основа. Основата се утврдува од сметководствената добивка, коригирана за даночно непризнаени расходи.' },
          { term: 'Аконтација', def: 'Аванс на данок што се плаќа во текот на годината врз основа на очекуваната или претходната обврска, пред конечната пресметка. Најчесто се однесува на месечни аконтации на данокот на добивка или персоналниот данок. По истекот на периодот, аконтациите се пребиваат со конечно утврдениот данок.' },
          { term: 'Даночна основа', def: 'Износот врз кој се пресметува данокот, пред применување на даночната стапка. Кај ДДВ тоа е вредноста на прометот без данокот, а кај данокот на добивка — оданочивата добивка. Точното утврдување на основата е клучно за правилна даночна пресметка.' },
          { term: 'Паушален данок', def: 'Поедноставен начин на оданочување кај кој данокот се утврдува во фиксен (паушален) износ, наместо според реалните приходи и расходи. Наменет е за помали самостојни вршители на дејност што исполнуваат законски услови. Паушалците водат поедноставена евиденција.' },
        ],
      },
      {
        heading: 'Фактурирање',
        terms: [
          { term: 'Фактура', def: 'Сметководствен документ што продавачот го издава на купувачот како доказ за извршен промет на добра или услуги. Содржи податоци за страните, опис на прометот, износ, ДДВ и датуми. Фактурата е основа за наплата и за книжење во деловните книги.' },
          { term: 'Профактура (про-фактура)', def: 'Понудбен документ што се издава пред самата испорака или наплата и служи како понуда или основа за плаќање однапред. За разлика од фактурата, профактурата не создава даночна обврска и не се книжи како приход. Често се користи за авансни плаќања.' },
          { term: 'Е-фактура', def: 'Електронска фактура издадена, испратена и примена во структуриран електронски формат. Во Северна Македонија електронското фактурирање преку системот на УЈП станува задолжително. Погледнете го нашиот водич за е-фактура за деталите.' },
          { term: 'Сторно фактура', def: 'Документ со кој се поништува или коригира претходно издадена фактура, најчесто со негативен (сторниран) износ. Се користи при грешки, откажан промет или враќање на добра. Сторно фактурата овозможува исправка на ДДВ и приходите без бришење на оригиналниот запис.' },
          { term: 'Авансна фактура', def: 'Фактура што се издава при примен аванс (претплата), пред испораката на добрата или услугите. Кај ДДВ обврзниците, авансот создава даночна обврска во моментот на наплатата. При конечната испорака се издава конечна фактура во која се одбива веќе фактурираниот аванс.' },
          { term: 'ЕМБС', def: 'Единствен матичен број на субјект е идентификациски број што Централниот регистар го доделува на секоја регистрирана фирма. Служи за еднозначна идентификација на правниот субјект во сите регистри и документи. Се наведува на фактурите и во деловната кореспонденција.' },
          { term: 'QES (квалификуван електронски потпис)', def: 'Квалификуван електронски потпис е највисокото ниво на електронски потпис што правно е изедначен со своерачниот потпис. Се издава од овластен доверлив давател преку квалификуван сертификат. Се користи за потпишување на е-фактури и електронски даночни документи.' },
        ],
      },
      {
        heading: 'Сметководство',
        terms: [
          { term: 'Биланс на состојба', def: 'Финансиски извештај што ја прикажува состојбата на средствата, обврските и капиталот на одреден датум. Секогаш важи равенката: средства = обврски + капитал. Билансот на состојба дава слика за имотната и финансиската положба на фирмата.' },
          { term: 'Биланс на успех', def: 'Финансиски извештај што ги прикажува приходите, расходите и резултатот (добивка или загуба) за одреден период. За разлика од билансот на состојба, се однесува на текот на средствата низ времето, а не на состојбата на еден датум. Го покажува деловниот успех на фирмата.' },
          { term: 'Главна книга', def: 'Централна сметководствена евиденција во која се книжат сите деловни промени по сметки од контниот план. Од главната книга се изведуваат финансиските извештаи. Секоја трансакција се внесува двострано — на должна и на побарувачка страна.' },
          { term: 'Контен план', def: 'Систематизиран список на сите сметки што фирмата ги користи за книжење, распоредени по класи. Во Северна Македонија се применува контниот план според Правилник 174/2011. Погледнете го целосниот контен план на нашата страница.' },
          { term: 'Амортизација', def: 'Постепено распределување на набавната вредност на основните средства на трошок во текот на нивниот корисен век. Одразува трошење и застарување на средствата како што се машини, опрема и возила. Амортизацијата ги намалува добивката и книговодствената вредност на средството.' },
          { term: 'Обртни средства', def: 'Средства што се очекува да се претворат во пари или потрошат во рок од една година — залихи, побарувања, парични средства. За разлика од основните средства, тие постојано циркулираат во работењето. Нивото на обртни средства е важно за секојдневната ликвидност.' },
          { term: 'Основни средства', def: 'Долгорочни материјални и нематеријални средства што се користат подолго од една година — згради, опрема, возила, софтвер. Не се наменети за препродажба, туку за долготрајна употреба во работењето. Нивната вредност се распределува на трошок преку амортизација.' },
          { term: 'Побарувања', def: 'Износи што други лица ѝ должат на фирмата, најчесто од продажба на добра или услуги на одложено плаќање. Претставуваат право на идна наплата и се книжат како средство. Наплатата на побарувањата директно влијае на ликвидноста.' },
          { term: 'Обврски', def: 'Износи што фирмата им должи на други — добавувачи, банки, вработени, држава. Претставуваат идни одливи на средства и се книжат на страната на изворите. Навременото подмирување на обврските е клучно за деловната репутација.' },
          { term: 'Ликвидност', def: 'Способност на фирмата навремено да ги подмирува своите краткорочни обврски со расположливи парични средства. Висока ликвидност значи дека фирмата лесно ги плаќа тековните обврски. Проблемите со ликвидноста може да доведат до неможност за плаќање, дури и кога фирмата е профитабилна.' },
        ],
      },
      {
        heading: 'Плати и работни односи',
        terms: [
          { term: 'Бруто плата', def: 'Вкупната плата на вработениот пред одбивање на придонесите и персоналниот данок. Од бруто платата се пресметуваат социјалните придонеси и данокот. Бруто платата е основа за пресметка на трошокот на работодавачот.' },
          { term: 'Нето плата', def: 'Износот што вработениот реално го добива „на рака“ по одбивање на придонесите и персоналниот данок од бруто платата. Тоа е сумата што се исплаќа на сметката на вработениот. Разликата меѓу бруто и нето платата ги сочинува придонесите и данокот.' },
          { term: 'Придонеси', def: 'Задолжителни социјални придонеси што се одбиваат од бруто платата за пензиско, здравствено и осигурување во случај на невработеност. Во Северна Македонија вкупните придонеси изнесуваат 28% од бруто платата. Придонесите обезбедуваат социјална и здравствена заштита на вработените.' },
          { term: 'МПИН', def: 'Месечна пресметка за интегрирана наплата — електронска пријава преку која се пресметуваат и уплаќаат придонесите и персоналниот данок од платите. Се доставува до УЈП за секоја исплата на плата. МПИН обезбедува интегрирана наплата на сите обврски од платите.' },
          { term: 'Минимална плата', def: 'Законски утврден најнизок износ на плата што работодавачот мора да го исплати за полно работно време. Се утврдува и усогласува во бруто и нето износ на национално ниво. Ниту еден работодавач не смее да исплати плата под минималната.' },
          { term: 'Регрес (К-15)', def: 'Регрес за годишен одмор е паричен надоместок што работодавачот му го исплаќа на вработениот за користење на годишниот одмор. Познат е и како К-15. Условите и износот се уредуваат со закон и колективни договори.' },
        ],
      },
      {
        heading: 'Правни форми',
        terms: [
          { term: 'ДООЕЛ', def: 'Друштво со ограничена одговорност со еден основач — најчеста форма за мали фирми со само еден сопственик. Основачот одговара за обврските само до висината на вложениот капитал. Погоден е за самостојни претприемачи што сакаат ограничена лична одговорност.' },
          { term: 'ДОО', def: 'Друштво со ограничена одговорност со двајца или повеќе основачи (содружници). Одговорноста на содружниците е ограничена до висината на нивните влогови. Управувањето и распределбата на добивката се уредуваат со договор меѓу содружниците.' },
          { term: 'Трговец поединец (ТП)', def: 'Физичко лице што самостојно врши трговска дејност регистрирана во Централниот регистар. За разлика од ДООЕЛ, трговецот поединец одговара за обврските со целиот свој личен имот. Оваа форма е поедноставна за основање, но носи неограничена лична одговорност.' },
        ],
      },
    ],
    ctaTitle: 'Води книги без стрес со Facturino',
    ctaBtn: 'Пробај бесплатно →',
    home: 'Почетна',
  },
  sq: {
    hero: {
      badge: 'Fjalor',
      title: 'Fjalor i termave kontabël dhe të biznesit',
      sub: 'Fjalor falas i termave më të shpeshta kontabël, tatimorë dhe të biznesit në Maqedoni — me përkufizime të qarta dhe të sakta. Ideal për sipërmarrës, kontabilistë dhe pronarë të bizneseve të vogla.',
    },
    categories: [
      {
        heading: 'TVSH dhe tatimet',
        terms: [
          { term: 'TVSH', def: 'Tatimi mbi vlerën e shtuar është një tatim indirekt mbi konsumin që llogaritet mbi qarkullimin e mallrave dhe shërbimeve. Në Maqedoninë e Veriut zbatohen tre norma: e përgjithshme 18%, e reduktuar 5% dhe e reduktuar 10%. Tatimin e paguan konsumatori final, ndërsa firmat e mbledhin dhe e derdhin në buxhet.' },
          { term: 'Detyrues i TVSH-së', def: 'Person juridik ose fizik i regjistruar për qëllime të TVSH-së, i detyruar të llogarisë, mbledhë dhe derdhë TVSH-në. Regjistrimi është i detyrueshëm kur qarkullimi vjetor kalon 2.000.000 MKD, por është i mundur edhe vullnetarisht. Detyruesi i TVSH-së ka të drejtë të zbresë tatimin e paraprakut.' },
          { term: 'EDB', def: 'Numri unik tatimor është numri identifikues që Drejtoria e të Hyrave Publike (UJP) ia jep çdo tatimpaguesi. Përdoret në të gjitha procedurat tatimore, deklaratat dhe në fatura. Çdo firmë dhe kryerës i pavarur i veprimtarisë e merr EDB-në gjatë regjistrimit.' },
          { term: 'Tatimi personal mbi të ardhurat', def: 'Tatim që paguhet mbi të ardhurat e personave fizikë — paga, honorare, qira e të ngjashme. Në Maqedoninë e Veriut norma është e sheshtë dhe është 10%. Tatimin zakonisht e mban dhe e derdh paguesi i të ardhurës.' },
          { term: 'Tatimi mbi fitimin', def: 'Tatim që paguhet mbi fitimin e personave juridikë. Norma në Maqedoninë e Veriut është 10% e bazës së tatueshme. Baza përcaktohet nga fitimi kontabël, i korrigjuar për shpenzimet e panjohura tatimisht.' },
          { term: 'Kësti paraprak (akontacion)', def: 'Paradhënie e tatimit që paguhet gjatë vitit mbi bazën e detyrimit të pritur ose të mëparshëm, para llogaritjes përfundimtare. Zakonisht lidhet me këstet mujore të tatimit mbi fitimin ose tatimit personal. Pas skadimit të periudhës, këstet balancohen me tatimin e përcaktuar përfundimtar.' },
          { term: 'Baza tatimore', def: 'Shuma mbi të cilën llogaritet tatimi, para zbatimit të normës tatimore. Te TVSH-ja është vlera e qarkullimit pa tatim, kurse te tatimi mbi fitimin — fitimi i tatueshëm. Përcaktimi i saktë i bazës është thelbësor për një llogaritje tatimore korrekte.' },
          { term: 'Tatimi i palës (paushall)', def: 'Mënyrë e thjeshtuar tatimimi ku tatimi përcaktohet në një shumë fikse (paushall), në vend të të ardhurave dhe shpenzimeve reale. Është menduar për kryerës të vegjël të pavarur të veprimtarisë që plotësojnë kushtet ligjore. Ata mbajnë një evidencë të thjeshtuar.' },
        ],
      },
      {
        heading: 'Faturimi',
        terms: [
          { term: 'Faturë', def: 'Dokument kontabël që shitësi ia lëshon blerësit si dëshmi për qarkullimin e kryer të mallrave ose shërbimeve. Përmban të dhëna për palët, përshkrimin e qarkullimit, shumën, TVSH-në dhe datat. Fatura është baza për arkëtim dhe për kontabilizim në librat afaristë.' },
          { term: 'Profaturë (pro-faturë)', def: 'Dokument oferte që lëshohet para vetë dorëzimit ose arkëtimit dhe shërben si ofertë ose bazë për pagesë paraprake. Ndryshe nga fatura, profatura nuk krijon detyrim tatimor dhe nuk kontabilizohet si e ardhur. Përdoret shpesh për pagesa paradhënie.' },
          { term: 'E-faturë', def: 'Faturë elektronike e lëshuar, dërguar dhe pranuar në format elektronik të strukturuar. Në Maqedoninë e Veriut faturimi elektronik përmes sistemit të UJP-së po bëhet i detyrueshëm. Shihni udhëzuesin tonë për e-faturën për detajet.' },
          { term: 'Faturë storno', def: 'Dokument me të cilin anulohet ose korrigjohet një faturë e lëshuar më parë, zakonisht me shumë negative (storno). Përdoret në raste gabimesh, qarkullimi të anuluar ose kthimi mallrash. Fatura storno mundëson korrigjimin e TVSH-së dhe të ardhurave pa fshirë regjistrimin origjinal.' },
          { term: 'Faturë avansi', def: 'Faturë që lëshohet me rastin e një avansi (parapagese), para dorëzimit të mallrave ose shërbimeve. Te detyruesit e TVSH-së, avansi krijon detyrim tatimor në momentin e arkëtimit. Në dorëzimin përfundimtar lëshohet fatura përfundimtare në të cilën zbritet avansi tashmë i faturuar.' },
          { term: 'EMBS', def: 'Numri unik amë i subjektit është numri identifikues që Regjistri Qendror ia jep çdo firme të regjistruar. Shërben për identifikim të qartë të subjektit juridik në të gjitha regjistrat dhe dokumentet. Shënohet në fatura dhe në korrespondencën afariste.' },
          { term: 'QES (nënshkrim elektronik i kualifikuar)', def: 'Nënshkrimi elektronik i kualifikuar është niveli më i lartë i nënshkrimit elektronik që ligjërisht barazohet me nënshkrimin me dorë. Lëshohet nga një ofrues i besuar i autorizuar përmes një certifikate të kualifikuar. Përdoret për nënshkrimin e e-faturave dhe dokumenteve tatimore elektronike.' },
        ],
      },
      {
        heading: 'Kontabiliteti',
        terms: [
          { term: 'Bilanci i gjendjes', def: 'Raport financiar që tregon gjendjen e mjeteve, detyrimeve dhe kapitalit në një datë të caktuar. Gjithmonë vlen ekuacioni: mjete = detyrime + kapital. Bilanci i gjendjes jep pamjen e pozitës pasurore dhe financiare të firmës.' },
          { term: 'Bilanci i suksesit', def: 'Raport financiar që tregon të ardhurat, shpenzimet dhe rezultatin (fitim ose humbje) për një periudhë të caktuar. Ndryshe nga bilanci i gjendjes, lidhet me rrjedhën e mjeteve gjatë kohës, jo me gjendjen në një datë. Tregon suksesin afarist të firmës.' },
          { term: 'Libri i madh', def: 'Evidenca qendrore kontabël në të cilën kontabilizohen të gjitha ndryshimet afariste sipas llogarive të planit kontabël. Nga libri i madh nxirren raportet financiare. Çdo transaksion futet dyanësisht — në anën debitore dhe kreditore.' },
          { term: 'Plani kontabël', def: 'Listë e sistemuar e të gjitha llogarive që firma i përdor për kontabilizim, të renditura sipas klasave. Në Maqedoninë e Veriut zbatohet plani kontabël sipas Rregullores 174/2011. Shihni planin e plotë kontabël në faqen tonë.' },
          { term: 'Amortizimi', def: 'Shpërndarja graduale e vlerës së blerjes së mjeteve themelore në shpenzim gjatë jetës së tyre të dobishme. Pasqyron konsumimin dhe vjetërsimin e mjeteve si makineritë, pajisjet dhe automjetet. Amortizimi zvogëlon fitimin dhe vlerën kontabël të mjetit.' },
          { term: 'Mjete qarkulluese', def: 'Mjete që pritet të kthehen në para ose të konsumohen brenda një viti — inventari, arkëtueshmet, mjetet monetare. Ndryshe nga mjetet themelore, ato qarkullojnë vazhdimisht në veprimtari. Niveli i mjeteve qarkulluese është i rëndësishëm për likuiditetin e përditshëm.' },
          { term: 'Mjete themelore', def: 'Mjete afatgjata materiale dhe jomateriale që përdoren më gjatë se një vit — ndërtesa, pajisje, automjete, softuer. Nuk janë të destinuara për rishitje, por për përdorim afatgjatë në veprimtari. Vlera e tyre shpërndahet në shpenzim përmes amortizimit.' },
          { term: 'Arkëtueshme', def: 'Shuma që të tjerët ia detyrohen firmës, zakonisht nga shitja e mallrave ose shërbimeve me pagesë të shtyrë. Përfaqësojnë të drejtën për arkëtim të ardhshëm dhe kontabilizohen si mjet. Arkëtimi i tyre ndikon drejtpërdrejt në likuiditet.' },
          { term: 'Detyrime', def: 'Shuma që firma u detyrohet të tjerëve — furnitorëve, bankave, punonjësve, shtetit. Përfaqësojnë dalje të ardhshme mjetesh dhe kontabilizohen në anën e burimeve. Shlyerja në kohë e detyrimeve është thelbësore për reputacionin afarist.' },
          { term: 'Likuiditeti', def: 'Aftësia e firmës për të shlyer në kohë detyrimet e saj afatshkurtra me mjetet monetare në dispozicion. Likuiditet i lartë do të thotë se firma i paguan lehtë detyrimet rrjedhëse. Problemet me likuiditetin mund të çojnë në pamundësi pagese, edhe kur firma është fitimprurëse.' },
        ],
      },
      {
        heading: 'Pagat dhe marrëdhëniet e punës',
        terms: [
          { term: 'Paga bruto', def: 'Paga e plotë e punonjësit para zbritjes së kontributeve dhe tatimit personal. Nga paga bruto llogariten kontributet sociale dhe tatimi. Paga bruto është baza për llogaritjen e kostos së punëdhënësit.' },
          { term: 'Paga neto', def: 'Shuma që punonjësi realisht e merr “në dorë” pas zbritjes së kontributeve dhe tatimit personal nga paga bruto. Kjo është shuma që derdhet në llogarinë e punonjësit. Ndryshimi mes pagës bruto dhe neto përbëhet nga kontributet dhe tatimi.' },
          { term: 'Kontributet', def: 'Kontribute të detyrueshme sociale që zbriten nga paga bruto për sigurimin pensional, shëndetësor dhe atë të papunësisë. Në Maqedoninë e Veriut kontributet e përgjithshme janë 28% të pagës bruto. Kontributet sigurojnë mbrojtjen sociale dhe shëndetësore të punonjësve.' },
          { term: 'MPIN', def: 'Llogaria mujore për arkëtim të integruar — deklaratë elektronike përmes së cilës llogariten dhe derdhen kontributet dhe tatimi personal nga pagat. I dorëzohet UJP-së për çdo pagesë page. MPIN siguron arkëtimin e integruar të të gjitha detyrimeve nga pagat.' },
          { term: 'Paga minimale', def: 'Shuma më e ulët e pagës e përcaktuar me ligj që punëdhënësi duhet ta paguajë për orar të plotë pune. Përcaktohet dhe harmonizohet në shumë bruto dhe neto në nivel kombëtar. Asnjë punëdhënës nuk guxon të paguajë pagë nën minimalen.' },
          { term: 'Regres (K-15)', def: 'Regresi për pushimin vjetor është një kompensim monetar që punëdhënësi ia paguan punonjësit për shfrytëzimin e pushimit vjetor. Njihet edhe si K-15. Kushtet dhe shuma rregullohen me ligj dhe kontrata kolektive.' },
        ],
      },
      {
        heading: 'Format juridike',
        terms: [
          { term: 'DOOEL', def: 'Shoqëri me përgjegjësi të kufizuar me një themelues — forma më e shpeshtë për firmat e vogla me vetëm një pronar. Themeluesi përgjigjet për detyrimet vetëm deri në lartësinë e kapitalit të investuar. E përshtatshme për sipërmarrës të pavarur që duan përgjegjësi personale të kufizuar.' },
          { term: 'DOO', def: 'Shoqëri me përgjegjësi të kufizuar me dy ose më shumë themelues (ortakë). Përgjegjësia e ortakëve është e kufizuar deri në lartësinë e kontributeve të tyre. Menaxhimi dhe shpërndarja e fitimit rregullohen me marrëveshje mes ortakëve.' },
          { term: 'Tregtar individual (TP)', def: 'Person fizik që kryen në mënyrë të pavarur veprimtari tregtare të regjistruar në Regjistrin Qendror. Ndryshe nga DOOEL, tregtari individual përgjigjet për detyrimet me tërë pasurinë e tij personale. Kjo formë është më e thjeshtë për t’u themeluar, por sjell përgjegjësi personale të pakufizuar.' },
        ],
      },
    ],
    ctaTitle: 'Mbaj librat pa stres me Facturino',
    ctaBtn: 'Provoni falas →',
    home: 'Ballina',
  },
  tr: {
    hero: {
      badge: 'Sözlük',
      title: 'Muhasebe ve iş terimleri sözlüğü',
      sub: 'Makedonya’daki en yaygın muhasebe, vergi ve iş terimlerinin ücretsiz sözlüğü — net ve doğru tanımlarla. Girişimciler, muhasebeciler ve küçük işletme sahipleri için idealdir.',
    },
    categories: [
      {
        heading: 'KDV ve vergiler',
        terms: [
          { term: 'KDV', def: 'Katma değer vergisi, mal ve hizmet cirosu üzerinden hesaplanan dolaylı bir tüketim vergisidir. Kuzey Makedonya’da üç oran uygulanır: genel %18, indirimli %5 ve indirimli %10. Vergiyi nihai tüketici öder, firmalar ise tahsil edip bütçeye aktarır.' },
          { term: 'KDV mükellefi', def: 'KDV amaçları için kayıtlı olan ve KDV’yi hesaplama, tahsil etme ve ödeme yükümlülüğü bulunan tüzel veya gerçek kişi. Yıllık ciro 2.000.000 MKD’yi aştığında kayıt zorunludur, ancak gönüllü olarak da mümkündür. KDV mükellefinin indirim (önceki vergi) hakkı vardır.' },
          { term: 'EDB', def: 'Tek vergi numarası, Kamu Gelirleri İdaresi’nin (UJP) her vergi mükellefine verdiği kimlik numarasıdır. Tüm vergi işlemlerinde, beyannamelerde ve faturalarda kullanılır. Her firma ve bağımsız faaliyet yürüten kişi kayıt sırasında EDB alır.' },
          { term: 'Kişisel gelir vergisi', def: 'Gerçek kişilerin gelirleri üzerinden ödenen vergi — maaşlar, telif ücretleri, kiralar ve benzerleri. Kuzey Makedonya’da oran sabittir ve %10’dur. Vergiyi genellikle gelirin ödeyicisi keser ve aktarır.' },
          { term: 'Kurumlar vergisi', def: 'Tüzel kişilerin kârı üzerinden ödenen vergi. Kuzey Makedonya’daki oran, vergilendirilebilir matrahın %10’udur. Matrah, vergisel olarak kabul edilmeyen giderler için düzeltilen muhasebe kârından belirlenir.' },
          { term: 'Peşin ödeme (avans vergi)', def: 'Nihai hesaplamadan önce, beklenen veya önceki yükümlülüğe göre yıl içinde ödenen vergi avansıdır. Genellikle kurumlar vergisi veya kişisel verginin aylık taksitlerini ifade eder. Dönem sonunda avanslar, nihai olarak belirlenen vergiyle mahsuplaşır.' },
          { term: 'Vergi matrahı', def: 'Vergi oranı uygulanmadan önce, verginin üzerinden hesaplandığı tutardır. KDV’de bu, vergisiz ciro değeridir; kurumlar vergisinde ise vergilendirilebilir kârdır. Matrahın doğru belirlenmesi, doğru vergi hesaplaması için çok önemlidir.' },
          { term: 'Götürü vergi (paushall)', def: 'Verginin gerçek gelir ve giderler yerine sabit (götürü) bir tutarda belirlendiği basitleştirilmiş bir vergilendirme yöntemidir. Yasal koşulları sağlayan küçük bağımsız faaliyet sahipleri içindir. Götürü mükellefler basitleştirilmiş kayıt tutar.' },
        ],
      },
      {
        heading: 'Faturalama',
        terms: [
          { term: 'Fatura', def: 'Satıcının, gerçekleştirilen mal veya hizmet cirosunun kanıtı olarak alıcıya düzenlediği muhasebe belgesidir. Tarafların bilgilerini, cironun tanımını, tutarı, KDV’yi ve tarihleri içerir. Fatura, tahsilat ve ticari defterlere kayıt için temeldir.' },
          { term: 'Proforma fatura', def: 'Teslimat veya tahsilattan önce düzenlenen ve teklif ya da peşin ödeme temeli olarak kullanılan bir teklif belgesidir. Faturadan farklı olarak proforma fatura vergi yükümlülüğü doğurmaz ve gelir olarak kaydedilmez. Sıklıkla avans ödemeleri için kullanılır.' },
          { term: 'E-fatura', def: 'Yapılandırılmış elektronik biçimde düzenlenen, gönderilen ve alınan elektronik faturadır. Kuzey Makedonya’da UJP sistemi üzerinden elektronik faturalama zorunlu hale gelmektedir. Ayrıntılar için e-fatura rehberimize bakın.' },
          { term: 'Storno (iptal) faturası', def: 'Daha önce düzenlenmiş bir faturayı iptal eden veya düzelten, genellikle negatif (storno) tutarlı bir belgedir. Hatalarda, iptal edilen ciroda veya mal iadelerinde kullanılır. Storno faturası, orijinal kaydı silmeden KDV ve gelirlerin düzeltilmesini sağlar.' },
          { term: 'Avans faturası', def: 'Malların veya hizmetlerin teslimatından önce, alınan bir avans (peşinat) için düzenlenen faturadır. KDV mükelleflerinde avans, tahsilat anında vergi yükümlülüğü doğurur. Nihai teslimatta, önceden faturalanan avansın düşüldüğü nihai fatura düzenlenir.' },
          { term: 'EMBS', def: 'Tek özne kayıt numarası, Merkezi Sicil’in kayıtlı her firmaya verdiği kimlik numarasıdır. Tüzel kişinin tüm sicillerde ve belgelerde net biçimde tanımlanmasını sağlar. Faturalarda ve ticari yazışmalarda belirtilir.' },
          { term: 'QES (nitelikli elektronik imza)', def: 'Nitelikli elektronik imza, hukuken el yazısı imzaya eşdeğer sayılan en yüksek düzeydeki elektronik imzadır. Yetkili güven hizmeti sağlayıcısı tarafından nitelikli bir sertifika aracılığıyla verilir. E-faturaların ve elektronik vergi belgelerinin imzalanmasında kullanılır.' },
        ],
      },
      {
        heading: 'Muhasebe',
        terms: [
          { term: 'Bilanço (durum bilançosu)', def: 'Belirli bir tarihte varlıkların, borçların ve sermayenin durumunu gösteren finansal rapordur. Her zaman şu denklem geçerlidir: varlıklar = borçlar + sermaye. Bilanço, firmanın varlık ve mali durumunun bir resmini verir.' },
          { term: 'Gelir tablosu', def: 'Belirli bir dönem için gelirleri, giderleri ve sonucu (kâr veya zarar) gösteren finansal rapordur. Bilançodan farklı olarak, bir tarihteki duruma değil, zaman içindeki akışa ilişkindir. Firmanın işletme başarısını gösterir.' },
          { term: 'Büyük defter', def: 'Tüm ticari işlemlerin hesap planındaki hesaplara göre kaydedildiği merkezi muhasebe kaydıdır. Finansal raporlar büyük defterden türetilir. Her işlem çift taraflı olarak — borç ve alacak tarafına — girilir.' },
          { term: 'Hesap planı', def: 'Firmanın kayıt için kullandığı, sınıflara göre düzenlenmiş tüm hesapların sistematik listesidir. Kuzey Makedonya’da 174/2011 sayılı Yönetmelik’e göre hesap planı uygulanır. Tam hesap planını sayfamızda görebilirsiniz.' },
          { term: 'Amortisman', def: 'Duran varlıkların alım değerinin, faydalı ömürleri boyunca kademeli olarak gidere dağıtılmasıdır. Makine, ekipman ve araçlar gibi varlıkların yıpranmasını ve eskimesini yansıtır. Amortisman, kârı ve varlığın defter değerini azaltır.' },
          { term: 'Dönen varlıklar', def: 'Bir yıl içinde nakde çevrilmesi veya tüketilmesi beklenen varlıklardır — stoklar, alacaklar, nakit. Duran varlıkların aksine, faaliyette sürekli olarak döner. Dönen varlık düzeyi, günlük likidite için önemlidir.' },
          { term: 'Duran varlıklar', def: 'Bir yıldan uzun süre kullanılan uzun vadeli maddi ve maddi olmayan varlıklardır — binalar, ekipman, araçlar, yazılım. Yeniden satış için değil, faaliyette uzun süreli kullanım içindir. Değerleri, amortisman yoluyla gidere dağıtılır.' },
          { term: 'Alacaklar', def: 'Başkalarının firmaya olan borç tutarlarıdır, genellikle vadeli mal veya hizmet satışından kaynaklanır. Gelecekteki bir tahsilat hakkını temsil eder ve varlık olarak kaydedilir. Alacakların tahsili likiditeyi doğrudan etkiler.' },
          { term: 'Borçlar', def: 'Firmanın başkalarına olan borç tutarlarıdır — tedarikçiler, bankalar, çalışanlar, devlet. Gelecekteki varlık çıkışlarını temsil eder ve kaynaklar tarafında kaydedilir. Borçların zamanında ödenmesi, ticari itibar için çok önemlidir.' },
          { term: 'Likidite', def: 'Firmanın kısa vadeli yükümlülüklerini eldeki nakitle zamanında ödeyebilme yeteneğidir. Yüksek likidite, firmanın cari borçlarını kolayca ödediği anlamına gelir. Likidite sorunları, firma kârlı olsa bile ödeme yapamamaya yol açabilir.' },
        ],
      },
      {
        heading: 'Maaşlar ve iş ilişkileri',
        terms: [
          { term: 'Brüt maaş', def: 'Çalışanın primler ve kişisel vergi kesilmeden önceki tam maaşıdır. Sosyal primler ve vergi, brüt maaştan hesaplanır. Brüt maaş, işverenin maliyetinin hesaplanmasına temel oluşturur.' },
          { term: 'Net maaş', def: 'Çalışanın, brüt maaştan primler ve kişisel vergi kesildikten sonra “ele” gerçekten aldığı tutardır. Bu, çalışanın hesabına yatırılan tutardır. Brüt ve net maaş arasındaki fark primler ve vergiden oluşur.' },
          { term: 'Primler', def: 'Emeklilik, sağlık ve işsizlik sigortası için brüt maaştan kesilen zorunlu sosyal primlerdir. Kuzey Makedonya’da toplam primler brüt maaşın %28’idir. Primler, çalışanların sosyal ve sağlık korumasını sağlar.' },
          { term: 'MPIN', def: 'Entegre tahsilat için aylık hesaplama — maaşlardan primlerin ve kişisel verginin hesaplanıp ödendiği elektronik beyannamedir. Her maaş ödemesi için UJP’ye sunulur. MPIN, maaşlardan doğan tüm yükümlülüklerin entegre tahsilatını sağlar.' },
          { term: 'Asgari ücret', def: 'İşverenin tam çalışma süresi için ödemesi gereken, yasayla belirlenen en düşük maaş tutarıdır. Ulusal düzeyde brüt ve net tutar olarak belirlenir ve uyarlanır. Hiçbir işveren asgari ücretin altında maaş ödeyemez.' },
          { term: 'İkramiye (K-15)', def: 'Yıllık izin ikramiyesi, işverenin çalışana yıllık iznini kullanması için ödediği bir para tazminatıdır. K-15 olarak da bilinir. Koşulları ve tutarı yasa ile toplu iş sözleşmeleriyle düzenlenir.' },
        ],
      },
      {
        heading: 'Hukuki biçimler',
        terms: [
          { term: 'DOOEL', def: 'Tek kurucusu olan limited şirket — yalnızca tek sahibi olan küçük firmalar için en yaygın biçimdir. Kurucu, borçlardan yalnızca yatırdığı sermaye tutarına kadar sorumludur. Sınırlı kişisel sorumluluk isteyen bağımsız girişimciler için uygundur.' },
          { term: 'DOO', def: 'İki veya daha fazla kurucusu (ortağı) olan limited şirkettir. Ortakların sorumluluğu, koydukları sermaye payı tutarıyla sınırlıdır. Yönetim ve kâr dağıtımı, ortaklar arasındaki sözleşmeyle düzenlenir.' },
          { term: 'Bireysel tacir (TP)', def: 'Merkezi Sicil’de kayıtlı ticari faaliyeti bağımsız olarak yürüten gerçek kişidir. DOOEL’den farklı olarak, bireysel tacir borçlardan tüm kişisel malvarlığıyla sorumludur. Bu biçim kurulması daha basittir, ancak sınırsız kişisel sorumluluk getirir.' },
        ],
      },
    ],
    ctaTitle: 'Facturino ile defterlerinizi stressiz tutun',
    ctaBtn: 'Ücretsiz deneyin →',
    home: 'Ana Sayfa',
  },
  en: {
    hero: {
      badge: 'Glossary',
      title: 'Accounting & business glossary',
      sub: 'A free dictionary of the most common Macedonian accounting, tax and business terms — with clear, accurate definitions. Ideal for entrepreneurs, accountants and small-business owners.',
    },
    categories: [
      {
        heading: 'VAT & taxes',
        terms: [
          { term: 'VAT (ДДВ)', def: 'Value added tax is an indirect consumption tax charged on the turnover of goods and services. North Macedonia applies three rates: a standard 18%, and reduced rates of 5% and 10%. The final consumer bears the tax, while businesses collect it and remit it to the budget.' },
          { term: 'VAT payer', def: 'A legal or natural person registered for VAT and obliged to calculate, charge and remit VAT. Registration is mandatory once annual turnover exceeds 2,000,000 MKD, but it can also be voluntary. A VAT payer is entitled to deduct input VAT.' },
          { term: 'EDB (tax number)', def: 'The unique tax number is an identifier the Public Revenue Office (UJP) assigns to every taxpayer. It is used in all tax procedures, returns and on invoices. Every company and self-employed person receives an EDB upon registration.' },
          { term: 'Personal income tax', def: 'A tax paid on the income of natural persons — salaries, fees, rents and the like. In North Macedonia the rate is flat at 10%. The tax is usually withheld and remitted by the payer of the income.' },
          { term: 'Corporate profit tax', def: 'A tax paid on the profit of legal entities. The rate in North Macedonia is 10% of the taxable base. The base is derived from accounting profit, adjusted for tax-non-deductible expenses.' },
          { term: 'Advance payment (акontacija)', def: 'A prepayment of tax made during the year based on the expected or prior liability, before the final assessment. It usually refers to monthly instalments of profit tax or personal income tax. At period end, the advances are offset against the finally assessed tax.' },
          { term: 'Tax base', def: 'The amount on which tax is calculated before the tax rate is applied. For VAT this is the value of turnover excluding tax; for profit tax it is the taxable profit. Determining the base correctly is essential for an accurate tax calculation.' },
          { term: 'Lump-sum tax (paušal)', def: 'A simplified taxation method where the tax is set as a fixed (lump-sum) amount rather than on actual income and expenses. It is intended for smaller self-employed persons who meet the legal conditions. Lump-sum taxpayers keep simplified records.' },
        ],
      },
      {
        heading: 'Invoicing',
        terms: [
          { term: 'Invoice', def: 'An accounting document the seller issues to the buyer as proof of a completed supply of goods or services. It contains details of the parties, a description of the supply, the amount, VAT and dates. The invoice is the basis for collection and for posting in the books.' },
          { term: 'Proforma invoice', def: 'A quotation document issued before the actual delivery or collection, serving as an offer or a basis for advance payment. Unlike an invoice, a proforma does not create a tax liability and is not booked as income. It is often used for advance payments.' },
          { term: 'E-invoice', def: 'An electronic invoice issued, sent and received in a structured electronic format. In North Macedonia electronic invoicing via the UJP system is becoming mandatory. See our e-invoice guide for the details.' },
          { term: 'Credit note (сторно)', def: 'A document that cancels or corrects a previously issued invoice, usually with a negative (reversed) amount. It is used for errors, cancelled supplies or returned goods. A credit note allows correction of VAT and income without deleting the original record.' },
          { term: 'Advance invoice', def: 'An invoice issued for a received advance (prepayment), before the delivery of goods or services. For VAT payers, the advance creates a tax liability at the moment of collection. On final delivery, a final invoice is issued in which the already-invoiced advance is deducted.' },
          { term: 'EMBS', def: 'The unique entity registration number is an identifier the Central Registry assigns to every registered company. It uniquely identifies the legal entity across all registers and documents. It is stated on invoices and in business correspondence.' },
          { term: 'QES (qualified electronic signature)', def: 'A qualified electronic signature is the highest level of electronic signature, legally equivalent to a handwritten one. It is issued by an authorised trust service provider via a qualified certificate. It is used to sign e-invoices and electronic tax documents.' },
        ],
      },
      {
        heading: 'Accounting',
        terms: [
          { term: 'Balance sheet', def: 'A financial statement showing the position of assets, liabilities and equity on a specific date. The equation always holds: assets = liabilities + equity. The balance sheet gives a picture of the company’s asset and financial position.' },
          { term: 'Income statement', def: 'A financial statement showing income, expenses and the result (profit or loss) over a given period. Unlike the balance sheet, it relates to the flow of resources over time rather than the position on a single date. It shows the company’s operating performance.' },
          { term: 'General ledger', def: 'The central accounting record in which all business transactions are posted to accounts from the chart of accounts. Financial statements are derived from the general ledger. Each transaction is entered on both sides — debit and credit.' },
          { term: 'Chart of accounts', def: 'A systematised list of all accounts a company uses for posting, arranged by class. In North Macedonia the chart of accounts under Rulebook 174/2011 applies. See the full chart of accounts on our page.' },
          { term: 'Depreciation', def: 'The gradual allocation of the purchase value of fixed assets to expense over their useful life. It reflects the wear and obsolescence of assets such as machinery, equipment and vehicles. Depreciation reduces profit and the asset’s book value.' },
          { term: 'Current assets', def: 'Assets expected to be converted into cash or consumed within one year — inventory, receivables, cash. Unlike fixed assets, they circulate continuously in operations. The level of current assets is important for day-to-day liquidity.' },
          { term: 'Fixed assets', def: 'Long-term tangible and intangible assets used for more than one year — buildings, equipment, vehicles, software. They are not intended for resale but for long-term use in operations. Their value is allocated to expense through depreciation.' },
          { term: 'Receivables', def: 'Amounts others owe the company, usually from selling goods or services on deferred payment. They represent a right to future collection and are booked as an asset. Collecting receivables directly affects liquidity.' },
          { term: 'Liabilities', def: 'Amounts the company owes to others — suppliers, banks, employees, the state. They represent future outflows of resources and are booked on the sources side. Settling liabilities on time is essential for business reputation.' },
          { term: 'Liquidity', def: 'The company’s ability to settle its short-term obligations on time with available cash. High liquidity means the company easily pays its current obligations. Liquidity problems can lead to an inability to pay, even when the company is profitable.' },
        ],
      },
      {
        heading: 'Payroll & employment',
        terms: [
          { term: 'Gross salary', def: 'The employee’s full salary before deducting contributions and personal income tax. Social contributions and tax are calculated from the gross salary. The gross salary is the basis for calculating the employer’s cost.' },
          { term: 'Net salary', def: 'The amount the employee actually receives “in hand” after contributions and personal income tax are deducted from the gross salary. This is the amount paid to the employee’s account. The difference between gross and net consists of contributions and tax.' },
          { term: 'Contributions', def: 'Mandatory social contributions deducted from the gross salary for pension, health and unemployment insurance. In North Macedonia total contributions amount to 28% of the gross salary. Contributions provide social and health protection for employees.' },
          { term: 'MPIN', def: 'The monthly calculation for integrated collection — an electronic filing through which contributions and personal income tax on salaries are calculated and paid. It is submitted to the UJP for each salary payment. MPIN ensures integrated collection of all salary-related obligations.' },
          { term: 'Minimum wage', def: 'The lowest legally set salary amount an employer must pay for full working time. It is set and adjusted in gross and net amounts at the national level. No employer may pay a salary below the minimum.' },
          { term: 'Holiday allowance (K-15)', def: 'The annual leave allowance is a cash benefit the employer pays the employee for taking annual leave. It is also known as K-15. Its conditions and amount are regulated by law and collective agreements.' },
        ],
      },
      {
        heading: 'Legal forms',
        terms: [
          { term: 'DOOEL', def: 'A limited liability company with a single founder — the most common form for small firms with just one owner. The founder is liable for obligations only up to the amount of invested capital. It suits independent entrepreneurs who want limited personal liability.' },
          { term: 'DOO', def: 'A limited liability company with two or more founders (partners). The partners’ liability is limited to the amount of their contributions. Management and profit distribution are governed by an agreement among the partners.' },
          { term: 'Sole trader (TP)', def: 'A natural person who independently carries out a commercial activity registered in the Central Registry. Unlike a DOOEL, a sole trader is liable for obligations with all of their personal property. This form is simpler to set up but carries unlimited personal liability.' },
        ],
      },
    ],
    ctaTitle: 'Keep your books stress-free with Facturino',
    ctaBtn: 'Try it free →',
    home: 'Home',
  },
} as const

function slugify(input: string) {
  return input
    .toLowerCase()
    .replace(/\([^)]*\)/g, '')
    .trim()
    .replace(/[^a-z0-9Ѐ-ӿà-ɏ]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

const CROSS_LINKS: Record<string, string> = {
  'ДДВ': '/alati/ddv-kalkulator',
  'VAT (ДДВ)': '/alati/ddv-kalkulator',
  'TVSH': '/alati/ddv-kalkulator',
  'KDV': '/alati/ddv-kalkulator',
  'Е-фактура': '/e-faktura/vodic',
  'E-faturë': '/e-faktura/vodic',
  'E-fatura': '/e-faktura/vodic',
  'E-invoice': '/e-faktura/vodic',
  'Контен план': '/kontni-plan',
  'Plani kontabël': '/kontni-plan',
  'Hesap planı': '/kontni-plan',
  'Chart of accounts': '/kontni-plan',
}

const FAQ_MK = [
  {
    question: 'Што е ДДВ во Северна Македонија?',
    answer: 'ДДВ е данок на додадена вредност — индиректен потрошувачки данок на прометот на добра и услуги, со стапки од 18%, 5% и 10%. Го наплаќаат ДДВ обврзниците и го уплаќаат во буџетот.',
  },
  {
    question: 'Кога фирмата мора да се регистрира за ДДВ?',
    answer: 'Регистрацијата за ДДВ е задолжителна кога годишниот промет ќе надмине 2.000.000 МКД. Можна е и доброволна регистрација пред да се достигне овој праг.',
  },
  {
    question: 'Која е разликата меѓу бруто и нето плата?',
    answer: 'Бруто платата е вкупната плата пред одбивање на придонесите и персоналниот данок, а нето платата е износот што вработениот реално го добива на рака. Придонесите изнесуваат 28% од бруто платата, а персоналниот данок 10%.',
  },
  {
    question: 'Што значи ДООЕЛ?',
    answer: 'ДООЕЛ е друштво со ограничена одговорност со еден основач. Основачот одговара за обврските на фирмата само до висината на вложениот капитал, што ја ограничува неговата лична одговорност.',
  },
]

export default async function RecnikPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const breadcrumbLd = breadcrumbJsonLd([
    { name: t.home, href: `/${locale}` },
    { name: t.hero.title, href: `/${locale}/recnik` },
  ])

  const allTerms: { term: string; def: string }[] = t.categories.flatMap((c) => c.terms.map((tm) => ({ term: tm.term, def: tm.def })))
  const definedTermSetLd = {
    '@context': 'https://schema.org',
    '@type': 'DefinedTermSet',
    name: t.hero.title,
    url: `${BASE_URL}/${locale}/recnik`,
    inLanguage: locale,
    hasDefinedTerm: allTerms.map((tm) => ({
      '@type': 'DefinedTerm',
      name: tm.term,
      description: tm.def,
    })),
  }

  const faqLd = faqJsonLd(FAQ_MK)

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(definedTermSetLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqLd) }} />

      <section className="bg-gradient-to-b from-indigo-50 to-white">
        <div className="container max-w-4xl mx-auto px-4 sm:px-6 py-12 md:py-16">
          <span className="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-4">{t.hero.badge}</span>
          <h1 className="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">{t.hero.title}</h1>
          <p className="text-lg text-gray-600 max-w-2xl">{t.hero.sub}</p>
        </div>
      </section>

      <section className="container max-w-4xl mx-auto px-4 sm:px-6 py-8">
        <nav className="flex flex-wrap gap-2 mb-10" aria-label={t.hero.title}>
          {t.categories.map((c) => (
            <a
              key={c.heading}
              href={`#${slugify(c.heading)}`}
              className="text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors px-3 py-1.5 rounded-full"
            >
              {c.heading}
            </a>
          ))}
        </nav>

        {t.categories.map((c) => (
          <section key={c.heading} id={slugify(c.heading)} className="mb-12 scroll-mt-20">
            <h2 className="text-xl md:text-2xl font-bold text-gray-900 mb-5 pb-2 border-b border-gray-200">{c.heading}</h2>
            <dl className="space-y-6">
              {c.terms.map((tm) => {
                const link = CROSS_LINKS[tm.term]
                return (
                  <div key={tm.term} id={slugify(tm.term)} className="scroll-mt-24">
                    <dt className="text-base font-bold text-gray-900 mb-1">
                      {link ? (
                        <Link href={`/${locale}${link}`} className="text-indigo-700 hover:text-indigo-800 hover:underline">
                          {tm.term}
                        </Link>
                      ) : (
                        tm.term
                      )}
                    </dt>
                    <dd className="text-gray-600 leading-relaxed">{tm.def}</dd>
                  </div>
                )
              })}
            </dl>
          </section>
        ))}
      </section>

      <section className="bg-gradient-to-br from-indigo-600 to-cyan-500">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6 py-12 text-center text-white">
          <h2 className="text-2xl md:text-3xl font-extrabold mb-6">{t.ctaTitle}</h2>
          <a href="https://app.facturino.mk/signup" className="inline-block bg-white text-indigo-700 font-bold px-8 py-3.5 rounded-xl hover:bg-indigo-50 transition-colors text-lg shadow-lg">{t.ctaBtn}</a>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
