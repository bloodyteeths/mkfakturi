import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/terms', {
    title: {
      mk: 'Услови за користење — Facturino',
      sq: 'Kushtet e shërbimit — Facturino',
      tr: 'Kullanim Kosullari — Facturino',
      en: 'Terms of Service — Facturino',
    },
    description: {
      mk: 'Правни услови за користење на Facturino платформата. Кориснички договор, плаќања, партнерска програма и одговорност.',
      sq: 'Kushtet ligjore te perdorimit te platformes Facturino. Marreveshja e perdoruesit, pagesat, programi i partneritetit dhe pergjegjesise.',
      tr: 'Facturino platformunu kullanim kosullari. Kullanici sozlesmesi, odemeler, ortaklik programi ve sorumluluk.',
      en: 'Legal terms for using the Facturino platform. User agreement, payments, partner program, and liability terms.',
    },
  })
}

const copy = {
  mk: {
    h1: 'Услови за користење',
    lastUpdated: 'Последно ажурирано: 1 јануари 2025',
    sections: [
      {
        title: '1. Општи одредби',
        content: 'Овие Услови за користење ("Услови") го регулираат вашиот пристап и користење на платформата Facturino, управувана од Facturino DOOEL, Скопје, Северна Македонија ("Facturino", "ние", "нас"). Со користење на нашата платформа, се согласувате со овие Услови. Доколку не се согласувате, ве молиме не ја користете платформата.'
      },
      {
        title: '2. Опис на услугата',
        content: 'Facturino е cloud-базирана сметководствена платформа која обезбедува:',
        list: [
          'Креирање и управување со фактури со поддршка за е-Фактура',
          'AI-базирани финансиски анализи и предлози',
          'PSD2 банкарска интеграција за увоз на изводи',
          'Мулти-клиент управување за сметководствени канцеларии',
          'Финансиски извештаи усогласени со IFRS стандарди',
          'Автоматизирано порамнување на побарувања'
        ]
      },
      {
        title: '3. Регистрација и корисничка сметка',
        content: 'За користење на платформата потребно е да креирате корисничка сметка. Вие се обврзувате:',
        list: [
          'Да обезбедите точни и ажурни информации при регистрација',
          'Да ја чувате тајноста на вашата лозинка и да не ја споделувате со трети лица',
          'Да не известите веднаш за секоја неовластена употреба на вашата сметка',
          'Да ги користите услугите во согласност со важечките закони на Република Северна Македонија',
          'Да не користите платформата за незаконски цели или измами'
        ]
      },
      {
        title: '4. Обврски на корисникот',
        content: 'Како корисник на Facturino, се обврзувате:',
        list: [
          'Да ги внесувате податоците точно и навремено',
          'Да ги чувате резервни копии од вашите критични податоци',
          'Да не се обидувате да пристапите до системот или податоци на други корисници без овластување',
          'Да не вршите обратен инженеринг, декомпилирање или обид за откривање на изворниот код (освен ако не е дозволено со AGPL-3.0 лиценцата)',
          'Да не го оптоварувате непотребно системот со автоматизирани барања',
          'Да ги почитувате правата на интелектуална сопственост'
        ]
      },
      {
        title: '5. Пакети и плаќање',
        content: 'Facturino нуди различни пакети (Free, Starter, Standard, Business, Max) со различни функционалности и лимити. Во врска со плаќањата:',
        list: [
          'Плаќањата се обработуваат преку Paddle (Paddle.com Market Limited) како наш трговец на запис (Merchant of Record)',
          'Претплатите се автоматски обновуваат на месечна база освен ако не ги откажете',
          'Цените може да се променат со претходно известување од 30 дена',
          'Бесплатниот пробен период трае 14 дена и не бара кредитна картичка',
          'Откажувањето на претплатата стапува на сила на крајот на тековниот период на наплата',
          'Повраток на средства не е можен за веќе искористени периоди',
          'ДДВ се пресметува според важечките даночни прописи'
        ]
      },
      {
        title: '6. Партнерска програма',
        content: 'Facturino нуди партнерска програма за сметководители и сметководствени канцеларии. Условите на партнерската програма:',
        list: [
          'Партнерите добиваат рекурентна провизија (20% за Partner, 22% за Partner Plus) за секој активен клиент што го препорачале',
          'Провизијата се пресметува од месечната претплата на препорачаниот клиент',
          'Исплатата на провизијата се врши месечно',
          'Facturino го задржува правото да ја измени провизиската структура со претходно известување од 60 дена',
          'Злоупотреба на партнерската програма може да доведе до прекинување на партнерството'
        ]
      },
      {
        title: '7. Интелектуална сопственост',
        content: 'Facturino е базиран на InvoiceShelf, open-source платформа лиценцирана под AGPL-3.0. Во согласност со тоа:',
        list: [
          'Изворниот код на InvoiceShelf е достапен под AGPL-3.0 лиценцата',
          'Модификациите и додатоците специфични за Facturino се предмет на AGPL-3.0 условите',
          'Брендот "Facturino", логоата и дизајнот се заштитени и се во сопственост на Facturino DOOEL',
          'Корисничкиот содржај (податоци, фактури, извештаи) останува во сопственост на корисникот'
        ]
      },
      {
        title: '8. Достапност на услугата',
        content: 'Се стремиме да обезбедиме 99.9% достапност на платформата. Сепак:',
        list: [
          'Не гарантираме непрекината или безгрешна работа',
          'Планираното одржување ќе биде објавено однапред кога е можно',
          'Не сме одговорни за прекини поради виша сила, напади, или проблеми со трети страни',
          'Го задржуваме правото да ги суспендираме услугите за одржување или безбедносни причини'
        ]
      },
      {
        title: '9. Ограничување на одговорност',
        content: 'Facturino е алатка за сметководствено управување и не претставува замена за професионален сметководствен или правен совет. Во врска со одговорноста:',
        list: [
          'Не сме одговорни за директна, индиректна, случајна или последична штета настаната од користење на платформата',
          'Не гарантираме за точноста на AI предлозите — корисникот е одговорен за верификација на сите сметководствени записи',
          'Нашата максимална одговорност е ограничена на износот на претплатата платена во последните 12 месеци',
          'Не сме одговорни за загуба на податоци поради корисничка грешка или неовластен пристап до вашата сметка',
          'Корисникот е одговорен за усогласеност со даночните и сметководствени прописи'
        ]
      },
      {
        title: '10. Суспензија и прекинување',
        content: 'Го задржуваме правото да ја суспендираме или прекинеме вашата сметка доколку:',
        list: [
          'Прекршите некој од овие Услови',
          'Го користите сервисот за незаконски цели',
          'Не ги платите доспеаните претплати',
          'Вашата активност претставува безбедносен ризик за платформата или другите корисници',
          'Добиеме законски налог или барање од надлежен орган'
        ],
        extra: 'Во случај на прекинување, ќе ви обезбедиме можност да ги извезете вашите податоци во рок од 30 дена, освен ако прекинувањето не е поради законска причина.'
      },
      {
        title: '11. Заштита на податоци',
        content: 'Обработката на лични податоци е регулирана со нашата Политика за приватност. Со прифаќање на овие Услови, потврдувате дека сте ја прочитале и разбрале нашата Политика за приватност.'
      },
      {
        title: '12. Измени на условите',
        content: 'Го задржуваме правото да ги измениме овие Услови. За значајни промени, ќе ве известиме најмалку 30 дена однапред преку е-пошта или известување во платформата. Продолженото користење на платформата по стапување на сила на промените претставува прифаќање на новите Услови.'
      },
      {
        title: '13. Меродавно право и надлежност',
        content: 'Овие Услови се регулирани со законите на Република Северна Македонија. За сите спорови кои произлегуваат од овие Услови, надлежен е Основниот суд Скопје 2, Скопје.'
      },
      {
        title: '14. Контакт',
        content: 'За прашања поврзани со овие Услови, контактирајте не:',
        list: [
          'Е-пошта: info@facturino.mk',
          'Компанија: Facturino DOOEL',
          'Адреса: Скопје, Северна Македонија'
        ]
      }
    ]
  },
  sq: {
    h1: 'Kushtet e shërbimit',
    lastUpdated: 'Përditësuar së fundmi: 1 janar 2025',
    sections: [
      {
        title: '1. Dispozita të përgjithshme',
        content: 'Këto Kushte të shërbimit ("Kushtet") rregullojnë qasjen dhe përdorimin tuaj të platformës Facturino, e menaxhuar nga Facturino DOOEL, Shkup, Maqedonia e Veriut ("Facturino", "ne"). Duke përdorur platformën tonë, pranoni këto Kushte.'
      },
      {
        title: '2. Përshkrimi i shërbimit',
        content: 'Facturino është një platformë kontabiliteti e bazuar në cloud që ofron:',
        list: [
          'Krijim dhe menaxhim faturash me mbështetje për e-Faturë',
          'Analiza financiare dhe sugjerime të bazuara në AI',
          'Integrim bankar PSD2 për importin e ekstrakteve',
          'Menaxhim shumë-klientësh për zyrat e kontabilitetit',
          'Raporte financiare në përputhje me standardet IFRS',
          'Përputhje automatike e borxheve'
        ]
      },
      {
        title: '3. Regjistrimi dhe llogaria e përdoruesit',
        content: 'Për të përdorur platformën, duhet të krijoni një llogari. Ju zotoheni:',
        list: [
          'Të jepni informacione të sakta dhe aktuale gjatë regjistrimit',
          'Të ruani fshehtësinë e fjalëkalimit tuaj',
          'Të na njoftoni menjëherë për çdo përdorim të paautorizuar të llogarisë suaj',
          'Të përdorni shërbimet në përputhje me ligjet e zbatueshme',
          'Të mos e përdorni platformën për qëllime të paligjshme'
        ]
      },
      {
        title: '4. Detyrimet e përdoruesit',
        content: 'Si përdorues i Facturino, zotoheni:',
        list: [
          'Të futni të dhëna të sakta dhe në kohë',
          'Të ruani kopje rezervë të të dhënave tuaja kritike',
          'Të mos tentoni të qaseni në sistemin ose të dhënat e përdoruesve të tjerë pa autorizim',
          'Të mos bëni inxhinieri të kundërt ose dekompilim të kodit burimor (përveç nëse lejohet nga licenca AGPL-3.0)',
          'Të mos e ngarkoni sistemin panevojshërisht me kërkesa të automatizuara',
          'Të respektoni të drejtat e pronësisë intelektuale'
        ]
      },
      {
        title: '5. Paketat dhe pagesa',
        content: 'Facturino ofron paketa të ndryshme (Free, Starter, Standard, Business, Max). Lidhur me pagesat:',
        list: [
          'Pagesat përpunohen përmes Paddle (Paddle.com Market Limited) si tregtari ynë i regjistruar',
          'Abonimet rinovohen automatikisht çdo muaj',
          'Çmimet mund të ndryshojnë me njoftim 30-ditor paraprak',
          'Periudha prove falas zgjat 14 ditë pa nevojë për kartë krediti',
          'Anulimi hyn në fuqi në fund të periudhës aktuale të faturimit',
          'Rimbursim nuk ofrohet për periudha tashmë të përdorura',
          'TVSH llogaritet sipas rregulloreve tatimore të zbatueshme'
        ]
      },
      {
        title: '6. Programi i partneritetit',
        content: 'Facturino ofron program partneriteti për kontabilistë. Kushtet:',
        list: [
          'Partnerët marrin komision rekurent (20% Partner, 22% Partner Plus)',
          'Komisioni llogaritet mbi abonimin mujor të klientit të rekomanduar',
          'Pagesa e komisionit bëhet çdo muaj',
          'Facturino ruan të drejtën të ndryshojë strukturën e komisionit me njoftim 60-ditor',
          'Abuzimi mund të sjellë përfundim të partneritetit'
        ]
      },
      {
        title: '7. Pronësia intelektuale',
        content: 'Facturino bazohet në InvoiceShelf, platformë open-source e licencuar nën AGPL-3.0:',
        list: [
          'Kodi burimor i InvoiceShelf është i disponueshëm nën licencën AGPL-3.0',
          'Modifikimet specifike për Facturino janë subjekt i kushteve AGPL-3.0',
          'Marka "Facturino", logot dhe dizajni janë në pronësi të Facturino DOOEL',
          'Përmbajtja e përdoruesit (të dhëna, fatura, raporte) mbetet pronë e përdoruesit'
        ]
      },
      {
        title: '8. Disponueshmëria e shërbimit',
        content: 'Synojmë të sigurojmë 99.9% disponueshmëri. Megjithatë:',
        list: [
          'Nuk garantojmë punë të pandërprerë ose pa gabime',
          'Mirëmbajtja e planifikuar do të njoftohet paraprakisht kur është e mundur',
          'Nuk jemi përgjegjës për ndërprerje nga forca madhore ose palë të treta',
          'Ruajmë të drejtën të pezullojmë shërbimet për mirëmbajtje ose siguri'
        ]
      },
      {
        title: '9. Kufizimi i përgjegjësisë',
        content: 'Facturino është mjet menaxhimi kontabël dhe nuk zëvendëson këshillën profesionale. Lidhur me përgjegjësinë:',
        list: [
          'Nuk jemi përgjegjës për dëme direkte, indirekte ose pasojore',
          'Nuk garantojmë saktësinë e sugjerimeve AI — përdoruesi është përgjegjës për verifikimin',
          'Përgjegjësia jonë maksimale kufizohet në shumën e abonimit të 12 muajve të fundit',
          'Nuk jemi përgjegjës për humbje të dhënash nga gabimi i përdoruesit',
          'Përdoruesi është përgjegjës për përputhjen me rregulloret tatimore'
        ]
      },
      {
        title: '10. Pezullimi dhe përfundimi',
        content: 'Ruajmë të drejtën të pezullojmë ose përfundojmë llogarinë tuaj nëse:',
        list: [
          'Shkelni ndonjë nga këto Kushte',
          'E përdorni shërbimin për qëllime të paligjshme',
          'Nuk i paguani abonimet e duhura',
          'Aktiviteti juaj paraqet rrezik sigurie',
          'Marrim urdhër ligjor'
        ],
        extra: 'Në rast përfundimi, do t\'ju ofrojmë mundësi të eksportoni të dhënat brenda 30 ditëve.'
      },
      {
        title: '11. Mbrojtja e të dhënave',
        content: 'Përpunimi i të dhënave personale rregullohet me Politikën tonë të privatësisë.'
      },
      {
        title: '12. Ndryshimet e kushteve',
        content: 'Ruajmë të drejtën t\'i ndryshojmë këto Kushte. Për ndryshime të rëndësishme, do t\'ju njoftojmë 30 ditë përpara.'
      },
      {
        title: '13. Ligji i zbatueshëm',
        content: 'Këto Kushte rregullohen nga ligjet e Republikës së Maqedonisë së Veriut. Gjykata kompetente është Gjykata Themelore Shkup 2, Shkup.'
      },
      {
        title: '14. Kontakti',
        content: 'Për pyetje lidhur me këto Kushte:',
        list: [
          'Email: info@facturino.mk',
          'Kompania: Facturino DOOEL',
          'Adresa: Shkup, Maqedonia e Veriut'
        ]
      }
    ]
  },
  tr: {
    h1: 'Kullanim Kosullari',
    lastUpdated: 'Son güncelleme: 1 Ocak 2025',
    sections: [
      {
        title: '1. Genel hükümler',
        content: 'Bu Kullanim Kosullari ("Kosullar"), Facturino DOOEL, Üsküp, Kuzey Makedonya ("Facturino", "biz") tarafindan yönetilen Facturino platformuna erisinizi ve kullaniminizi düzenler. Platformumuzu kullanarak bu Kosullari kabul edersiniz.'
      },
      {
        title: '2. Hizmet tanimi',
        content: 'Facturino, asagidakileri sunan bulut tabanli bir muhasebe platformudur:',
        list: [
          'e-Fatura destegi ile fatura olusturma ve yönetim',
          'AI tabanli finansal analizler ve öneriler',
          'Ekstre ithalati icin PSD2 banka entegrasyonu',
          'Muhasebe bürölari icin coklu müsteri yönetimi',
          'IFRS standartlarina uyumlu finansal raporlar',
          'Otomatik alacak mutabakati'
        ]
      },
      {
        title: '3. Kayit ve kullanici hesabi',
        content: 'Platformu kullanmak icin bir hesap olusturmaniz gerekmektedir. Taahhüt edersiniz ki:',
        list: [
          'Kayit sirasinda dogru ve güncel bilgiler saglayacaksiniz',
          'Sifrenizi gizli tutacak ve ücüncü kisilerle paylasmayacaksiniz',
          'Hesabinizin yetkisiz kullanimini derhal bize bildireceksiniz',
          'Hizmetleri yürürlükteki yasalara uygun kullanacaksiniz',
          'Platformu yasadisi amaclarla kullanmayacaksiniz'
        ]
      },
      {
        title: '4. Kullanici yükümlülükleri',
        content: 'Facturino kullanicisi olarak taahhüt edersiniz ki:',
        list: [
          'Verileri dogru ve zamaninda gireceksiniz',
          'Kritik verilerinizin yedegini tutacaksiniz',
          'Diger kullanicilarin sisteme veya verilerine yetkisiz erisim denemeyeceksiniz',
          'Tersine mühendislik veya kaynak kodu ortaya cikarma girisiminde bulunmayacaksiniz (AGPL-3.0 lisansi tarafindan izin verilen durumlar haric)',
          'Sistemi otomatik isteklerle gereksiz yere yüklemeyeceksiniz',
          'Fikri mülkiyet haklarina saygi göstereceksiniz'
        ]
      },
      {
        title: '5. Paketler ve ödeme',
        content: 'Facturino farkli paketler (Free, Starter, Standard, Business, Max) sunmaktadir. Ödemeler hakkinda:',
        list: [
          'Ödemeler, kayitli tüccarimiz olarak Paddle (Paddle.com Market Limited) araciligiyla islenir',
          'Abonelikler, iptal etmediginiz sürece aylik olarak otomatik yenilenir',
          'Fiyatlar 30 gün önceden bildirimle degisebilir',
          'Ücretsiz deneme süresi 14 gündür ve kredi karti gerektirmez',
          'Abonelik iptali mevcut fatura döneminin sonunda yürürlüge girer',
          'Kullanilmis dönemler icin geri ödeme yapilmaz',
          'KDV yürürlükteki vergi düzenlemelerine göre hesaplanir'
        ]
      },
      {
        title: '6. Ortaklik programi',
        content: 'Facturino, muhasebeciler icin ortaklik programi sunmaktadir:',
        list: [
          'Ortaklar yinelenen komisyon alir (%20 Partner, %22 Partner Plus)',
          'Komisyon, yönlendirilen müsterinin aylik aboneligi üzerinden hesaplanir',
          'Komisyon ödemesi aylik yapilir',
          'Facturino, 60 gün önceden bildirimle komisyon yapisini degistirme hakkini sakli tutar',
          'Kötüye kullanim ortakligin sonlandirilmasina yol acabilir'
        ]
      },
      {
        title: '7. Fikri mülkiyet',
        content: 'Facturino, AGPL-3.0 altinda lisanslanan acik kaynakli InvoiceShelf üzerine kuruludur:',
        list: [
          'InvoiceShelf kaynak kodu AGPL-3.0 lisansi altinda mevcuttur',
          'Facturino\'ya özgü degisiklikler AGPL-3.0 kosullarina tabidir',
          '"Facturino" markasi, logolar ve tasarim Facturino DOOEL\'in mülkiyetindedir',
          'Kullanici icerigi (veriler, faturalar, raporlar) kullanicinin mülkiyetinde kalir'
        ]
      },
      {
        title: '8. Hizmet kullanilabilirligi',
        content: '%99.9 kullanilabilirlik saglamayi hedefliyoruz. Ancak:',
        list: [
          'Kesintisiz veya hatasiz calisma garanti etmiyoruz',
          'Planli bakim mümkün oldugunda önceden duyurulacaktir',
          'Mücbir sebepler veya ücüncü taraf sorunlarindan kaynaklanan kesintilerden sorumlu degiliz',
          'Bakim veya güvenlik nedenleriyle hizmetleri askiya alma hakkimizi sakli tutariz'
        ]
      },
      {
        title: '9. Sorumluluk sinirlamasi',
        content: 'Facturino bir muhasebe yönetim aracidir ve profesyonel muhasebe veya hukuki danismanligin yerini tutmaz:',
        list: [
          'Platformun kullanimindan kaynaklanan dogrudan, dolayli veya arizi zararlardan sorumlu degiliz',
          'AI önerilerinin dogrulugunu garanti etmiyoruz — kullanici tüm muhassebe kayitlarini dogrulamakla yükümlüdür',
          'Maksimum sorumlulugumuz son 12 ayda ödenen abonelik tutari ile sinirlidir',
          'Kullanici hatasindan kaynaklanan veri kayiplarindan sorumlu degiliz',
          'Kullanici vergi ve muhasebe düzenlemelerine uyumdan sorumludur'
        ]
      },
      {
        title: '10. Askiya alma ve fesih',
        content: 'Asagidaki durumlarda hesabinizi askiya alma veya feshetme hakkimizi sakli tutariz:',
        list: [
          'Bu Kosullardan herhangi birini ihlal ederseniz',
          'Hizmeti yasadisi amaclarla kullanirseniz',
          'Vadesi gelen abonelikleri ödemezseniz',
          'Aktiviteniz güvenlik riski olusturuyorsa',
          'Yasal emir veya yetkili makam talebi alirsak'
        ],
        extra: 'Fesih durumunda, yasal nedenle olmadigi sürece verilerinizi 30 gün icinde disari aktarma imkani saglariz.'
      },
      {
        title: '11. Veri koruma',
        content: 'Kisisel verilerin islenmesi Gizlilik Politikamiz ile düzenlenmektedir.'
      },
      {
        title: '12. Kosul degisiklikleri',
        content: 'Bu Kosullari degistirme hakkimizi sakli tutariz. Önemli degisiklikler icin en az 30 gün önce e-posta ile bilgilendirileceksiniz.'
      },
      {
        title: '13. Uygulanacak hukuk',
        content: 'Bu Kosullar Kuzey Makedonya Cumhuriyeti yasalarina tabidir. Yetkili mahkeme Üsküp 2 Temel Mahkemesidir.'
      },
      {
        title: '14. Iletisim',
        content: 'Bu Kosullarla ilgili sorular icin:',
        list: [
          'E-posta: info@facturino.mk',
          'Sirket: Facturino DOOEL',
          'Adres: Üsküp, Kuzey Makedonya'
        ]
      }
    ]
  },
  en: {
    h1: 'Terms of Service',
    lastUpdated: 'Last updated: January 1, 2025',
    sections: [
      {
        title: '1. General Provisions',
        content: 'These Terms of Service ("Terms") govern your access and use of the Facturino platform, operated by Facturino DOOEL, Skopje, North Macedonia ("Facturino", "we", "us"). By using our platform, you agree to these Terms. If you do not agree, please do not use the platform.'
      },
      {
        title: '2. Service Description',
        content: 'Facturino is a cloud-based accounting platform that provides:',
        list: [
          'Invoice creation and management with e-Invoice support',
          'AI-based financial analysis and suggestions',
          'PSD2 bank integration for statement import',
          'Multi-client management for accounting offices',
          'Financial reports compliant with IFRS standards',
          'Automated receivables reconciliation'
        ]
      },
      {
        title: '3. Registration and User Account',
        content: 'To use the platform, you need to create a user account. You commit to:',
        list: [
          'Providing accurate and up-to-date information during registration',
          'Keeping your password confidential and not sharing it with third parties',
          'Notifying us immediately of any unauthorized use of your account',
          'Using the services in accordance with applicable laws of the Republic of North Macedonia',
          'Not using the platform for illegal purposes or fraud'
        ]
      },
      {
        title: '4. User Obligations',
        content: 'As a Facturino user, you commit to:',
        list: [
          'Entering data accurately and in a timely manner',
          'Keeping backup copies of your critical data',
          'Not attempting to access the system or data of other users without authorization',
          'Not reverse engineering, decompiling, or attempting to discover the source code (unless permitted by the AGPL-3.0 license)',
          'Not unnecessarily burdening the system with automated requests',
          'Respecting intellectual property rights'
        ]
      },
      {
        title: '5. Plans and Payment',
        content: 'Facturino offers various plans (Free, Starter, Standard, Business, Max) with different features and limits. Regarding payments:',
        list: [
          'Payments are processed through Paddle (Paddle.com Market Limited) as our Merchant of Record',
          'Subscriptions automatically renew on a monthly basis unless cancelled',
          'Prices may change with 30 days prior notice',
          'The free trial period lasts 14 days and does not require a credit card',
          'Subscription cancellation takes effect at the end of the current billing period',
          'Refunds are not available for already used periods',
          'VAT is calculated according to applicable tax regulations'
        ]
      },
      {
        title: '6. Partner Program',
        content: 'Facturino offers a partner program for accountants and accounting offices. Partner program terms:',
        list: [
          'Partners receive recurring commission (20% for Partner, 22% for Partner Plus) for each active referred client',
          'Commission is calculated on the referred client\'s monthly subscription',
          'Commission payment is made monthly',
          'Facturino reserves the right to modify the commission structure with 60 days prior notice',
          'Abuse of the partner program may lead to partnership termination'
        ]
      },
      {
        title: '7. Intellectual Property',
        content: 'Facturino is based on InvoiceShelf, an open-source platform licensed under AGPL-3.0. Accordingly:',
        list: [
          'InvoiceShelf source code is available under the AGPL-3.0 license',
          'Modifications and additions specific to Facturino are subject to AGPL-3.0 terms',
          'The "Facturino" brand, logos, and design are protected and owned by Facturino DOOEL',
          'User content (data, invoices, reports) remains the property of the user'
        ]
      },
      {
        title: '8. Service Availability',
        content: 'We strive to provide 99.9% platform availability. However:',
        list: [
          'We do not guarantee uninterrupted or error-free operation',
          'Scheduled maintenance will be announced in advance when possible',
          'We are not liable for interruptions due to force majeure, attacks, or third-party issues',
          'We reserve the right to suspend services for maintenance or security reasons'
        ]
      },
      {
        title: '9. Limitation of Liability',
        content: 'Facturino is an accounting management tool and does not substitute for professional accounting or legal advice. Regarding liability:',
        list: [
          'We are not liable for direct, indirect, incidental, or consequential damages arising from use of the platform',
          'We do not guarantee the accuracy of AI suggestions -- the user is responsible for verifying all accounting records',
          'Our maximum liability is limited to the subscription amount paid in the last 12 months',
          'We are not liable for data loss due to user error or unauthorized access to your account',
          'The user is responsible for compliance with tax and accounting regulations'
        ]
      },
      {
        title: '10. Suspension and Termination',
        content: 'We reserve the right to suspend or terminate your account if:',
        list: [
          'You violate any of these Terms',
          'You use the service for illegal purposes',
          'You fail to pay due subscriptions',
          'Your activity poses a security risk to the platform or other users',
          'We receive a legal order or request from a competent authority'
        ],
        extra: 'In case of termination, we will provide you the opportunity to export your data within 30 days, unless termination is due to legal reasons.'
      },
      {
        title: '11. Data Protection',
        content: 'Personal data processing is governed by our Privacy Policy. By accepting these Terms, you confirm that you have read and understood our Privacy Policy.'
      },
      {
        title: '12. Changes to Terms',
        content: 'We reserve the right to modify these Terms. For significant changes, we will notify you at least 30 days in advance via email or a notification in the platform. Continued use of the platform after changes take effect constitutes acceptance of the new Terms.'
      },
      {
        title: '13. Governing Law and Jurisdiction',
        content: 'These Terms are governed by the laws of the Republic of North Macedonia. The competent court for all disputes arising from these Terms is the Basic Court Skopje 2, Skopje.'
      },
      {
        title: '14. Contact',
        content: 'For questions related to these Terms, contact us:',
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

export default async function TermsPage({ params }: { params: Promise<{ locale: string }> }) {
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
