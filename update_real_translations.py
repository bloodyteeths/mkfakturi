#!/usr/bin/env python3
import json
import os

# Real translations
translations = {
    "mk": {  # Macedonian
        "landing": {
            "hero": {
                "badge": "Ново: AI-засновано следење на трошоци",
                "title": "Сметководство кое размислува за вас.",
                "subtitle": "Факторино комбинира моќно фактурирање со напреден AI за да го автоматизира вашето книговодство, следи трошоци и предвидува ваша финансиска иднина.",
                "cta_primary": "Започнете бесплатна проба",
                "cta_secondary": "Видете како функционира",
                "dashboard_alt": "Факторино контролна табла"
            },
            "partners": {
                "trusted_by": "Доверба од иновативни компании"
            },
            "features": {
                "ai_insights": {
                    "title": "AI-засновани финансиски анализи",
                    "description": "Добивајте предлози во реално време за тоа како да заштедите пари и да го оптимизирате вашиот готовински тек."
                },
                "invoicing": {
                    "title": "Убави фактури",
                    "description": "Креирајте професионални фактури за секунди кои ќе ве платат побрзо."
                },
                "expenses": {
                    "title": "Паметно следење на трошоци",
                    "description": "Сликајте ја вашата сметка и дозволете нашиот AI автоматски да ги извлече податоците."
                },
                "reporting": {
                    "title": "Сеопфатни извештаи",
                    "description": "Визуализирајте го вашето финансиско здравје со интерактивни графикони и извештаи."
                }
            },
            "how_it_works": {
                "title": "Како функционира Факторино",
                "subtitle": "Започнете за минути, не денови.",
                "step1": {
                    "title": "Креирајте сметка",
                    "description": "Регистрирајте се бесплатно и поставете го профилот на вашата компанија."
                },
                "step2": {
                    "title": "Поврзете податоци",
                    "description": "Увезете ги вашите клиенти и поврзете ги вашите банкарски сметки."
                },
                "step3": {
                    "title": "Добијте плаќање",
                    "description": "Испраќајте фактури и следете плаќања автоматски."
                }
            },
            "testimonials": {
                "title": "Сакано од бизниси како вашиот",
                "review1": "Факторино целосно го трансформираше начинот на кој ги управуваме нашите финансии. AI анализите се револуционерни.",
                "author1": "Елена М.",
                "role1": "Извршен директор во TechStart",
                "review2": "Најдобриот софтвер за фактурирање што го користев. Едноставен, брз и убав.",
                "author2": "Марко П.",
                "role2": "Фриленс дизајнер",
                "review3": "Заштедувам часови секоја недела на книговодство благодарение на автоматското следење на трошоци.",
                "author3": "Сара Ј.",
                "role3": "Сопственик на Cafe Bliss"
            },
            "faq": {
                "title": "Често поставувани прашања",
                "q1": "Дали има бесплатна проба?",
                "a1": "Да, нудиме 14-дневна бесплатна проба на сите платени планови.",
                "q2": "Можам ли да откажам во секое време?",
                "a2": "Апсолутно. Нема долгорочни договори и можете да откажете кога сакате.",
                "q3": "Дали моите податоци се безбедни?",
                "a3": "Користиме енкрипција на банкарско ниво за да обезбедиме дека вашите податоци се секогаш безбедни.",
                "q4": "Дали поддржувате повеќе валути?",
                "a4": "Да, Факторино поддржува преку 100 валути со живи курсеви."
            },
            "cta_footer": {
                "title": "Подготвени да го поедноставите вашето сметководство?",
                "subtitle": "Придружете се на илјадници бизниси кои му веруваат на Факторино.",
                "button": "Започнете сега"
            },
            "footer": {
                "product": "Производ",
                "features": "Функции",
                "pricing": "Цени",
                "company": "Компанија",
                "about": "За нас",
                "blog": "Блог",
                "careers": "Кариери",
                "legal": "Правно",
                "privacy": "Политика за приватност",
                "terms": "Услови за користење",
                "rights": "Сите права задржани."
            }
        },
        "pricing_public": {
            "title": "Едноставни, транспарентни цени",
            "subtitle": "Изберете го планот што одговара на потребите на вашиот бизнис.",
            "monthly": "Месечно",
            "yearly": "Годишно",
            "save_text": "Заштедете 20%",
            "most_popular": "Најпопуларно",
            "per_month": "/месец",
            "per_year": "/година",
            "start_trial": "Започнете бесплатна проба",
            "get_started": "Започнете",
            "contact_sales": "Контактирајте продажба",
            "features_label": "Сè од {prev} плус:",
            "custom_solution": {
                "title": "Потребно ви е приспособено решение?",
                "description": "Контактирајте не за корпоративни цени и приспособени интеграции.",
                "button": "Контактирајте продажба"
            },
            "plans": {
                "free": {
                    "name": "Бесплатно",
                    "description": "Совршено за фрилансери кои само започнуваат."
                },
                "starter": {
                    "name": "Почетник",
                    "description": "Одлично за мали бизниси кои брзо растат."
                },
                "standard": {
                    "name": "Стандард",
                    "description": "За воспоставени бизниси со тимови."
                },
                "business": {
                    "name": "Бизнис",
                    "description": "Напредни функции за компании во раст."
                }
            }
        }
    },
    "sq": {  # Albanian
        "landing": {
            "hero": {
                "badge": "E re: Gjurmimi i shpenzimeve të fuqizuara nga AI",
                "title": "Kontabiliteti që mendon për ju.",
                "subtitle": "Facturino kombinon faturimin e fuqishëm me AI të avancuar për të automatizuar kontabilitetin tuaj, gjurmuar shpenzimet dhe parashikuar të ardhmen tuaj financiare.",
                "cta_primary": "Filloni provën falas",
                "cta_secondary": "Shihni si funksionon",
                "dashboard_alt": "Paneli i kontrollit Facturino"
            },
            "partners": {
                "trusted_by": "Besuar nga kompani me mendim përpara"
            },
            "features": {
                "ai_insights": {
                    "title": "Njohuri financiare të fuqizuara nga AI",
                    "description": "Merrni sugjerime në kohë reale se si të kurseni para dhe të optimizoni rrjedhën tuaj të parasë."
                },
                "invoicing": {
                    "title": "Fatura të bukura",
                    "description": "Krijoni fatura profesionale në sekonda që ju paguajnë më shpejt."
                },
                "expenses": {
                    "title": "Gjurmimi i mençur i shpenzimeve",
                    "description": "Bëni një fotografi të faturës suaj dhe lini AI-në tonë të nxjerrë të dhënat automatikisht."
                },
                "reporting": {
                    "title": "Raportim gjithëpërfshirës",
                    "description": "Vizualizoni shëndetin tuaj financiar me grafikë dhe raporte interaktive."
                }
            },
            "how_it_works": {
                "title": "Si funksionon Facturino",
                "subtitle": "Filloni për minuta, jo ditë.",
                "step1": {
                    "title": "Krijoni llogari",
                    "description": "Regjistrohuni falas dhe vendosni profilin e kompanisë suaj."
                },
                "step2": {
                    "title": "Lidhni të dhënat",
                    "description": "Importoni klientët tuaj dhe lidhni llogaritë tuaja bankare."
                },
                "step3": {
                    "title": "Merrni pagesën",
                    "description": "Dërgoni fatura dhe gjurmoni pagesat automatikisht."
                }
            },
            "testimonials": {
                "title": "E dashur nga biznese si juaja",
                "review1": "Facturino ka transformuar plotësisht mënyrën se si menaxhojmë financat tona. Njohuritë e AI janë revolucionare.",
                "author1": "Elena M.",
                "role1": "CEO në TechStart",
                "review2": "Softueri më i mirë i faturimit që kam përdorur. I thjeshtë, i shpejtë dhe i bukur.",
                "author2": "Marko P.",
                "role2": "Dizajner i lirë",
                "review3": "Kursej orë çdo javë në kontabilitet falë gjurmimit automatik të shpenzimeve.",
                "author3": "Sarah J.",
                "role3": "Pronare në Cafe Bliss"
            },
            "faq": {
                "title": "Pyetje të shpeshta",
                "q1": "A ka provë falas?",
                "a1": "Po, ofrojmë një provë falas 14-ditore për të gjitha planet e paguara.",
                "q2": "A mund ta anuloj në çdo kohë?",
                "a2": "Absolutisht. Nuk ka kontrata afatgjata dhe mund ta anuloni kur të dëshironi.",
                "q3": "A janë të dhënat e mia të sigurta?",
                "a3": "Përdorim enkriptim në nivel bankar për të siguruar që të dhënat tuaja janë gjithmonë të sigurta.",
                "q4": "A mbështetni valuta të shumta?",
                "a4": "Po, Facturino mbështet mbi 100 valuta me kurse këmbimi të drejtpërdrejta."
            },
            "cta_footer": {
                "title": "Gati për të thjeshtuar kontabilitetin tuaj?",
                "subtitle": "Bashkohuni me mijëra biznese që i besojnë Facturino.",
                "button": "Filloni tani"
            },
            "footer": {
                "product": "Produkti",
                "features": "Veçoritë",
                "pricing": "Çmimet",
                "company": "Kompania",
                "about": "Rreth nesh",
                "blog": "Blogu",
                "careers": "Karriera",
                "legal": "Ligjore",
                "privacy": "Politika e privatësisë",
                "terms": "Kushtet e shërbimit",
                "rights": "Të gjitha të drejtat e rezervuara."
            }
        },
        "pricing_public": {
            "title": "Çmime të thjeshta dhe transparente",
            "subtitle": "Zgjidhni planin që i përshtatet nevojave të biznesit tuaj.",
            "monthly": "Mujore",
            "yearly": "Vjetore",
            "save_text": "Kurseni 20%",
            "most_popular": "Më popullore",
            "per_month": "/muaj",
            "per_year": "/vit",
            "start_trial": "Filloni provën falas",
            "get_started": "Filloni",
            "contact_sales": "Kontaktoni shitjet",
            "features_label": "Gjithçka në {prev} plus:",
            "custom_solution": {
                "title": "Keni nevojë për një zgjidhje të personalizuar?",
                "description": "Na kontaktoni për çmime korporative dhe integrime të personalizuara.",
                "button": "Kontaktoni shitjet"
            },
            "plans": {
                "free": {
                    "name": "Falas",
                    "description": "Perfekte për punëtorë të lirë që sapo fillojnë."
                },
                "starter": {
                    "name": "Fillestar",
                    "description": "E shkëlqyer për biznese të vogla që rriten shpejt."
                },
                "standard": {
                    "name": "Standard",
                    "description": "Për biznese të vendosura me ekipe."
                },
                "business": {
                    "name": "Biznes",
                    "description": "Veçori të avancuara për kompani në rritje."
                }
            }
        }
    },
    "tr": {  # Turkish
        "landing": {
            "hero": {
                "badge": "Yeni: Yapay Zeka Destekli Gider Takibi",
                "title": "Sizin için düşünen muhasebe.",
                "subtitle": "Facturino, defter tutmanızı otomatikleştirmek, giderleri takip etmek ve finansal geleceğinizi tahmin etmek için gelişmiş yapay zeka ile güçlü faturalamayı birleştirir.",
                "cta_primary": "Ücretsiz denemeye başlayın",
                "cta_secondary": "Nasıl çalıştığını görün",
                "dashboard_alt": "Facturino kontrol paneli"
            },
            "partners": {
                "trusted_by": "İleriye dönük düşünen şirketler tarafından güveniliyor"
            },
            "features": {
                "ai_insights": {
                    "title": "Yapay Zeka Destekli Finansal İçgörüler",
                    "description": "Nasıl para tasarrufu yapacağınız ve nakit akışınızı optimize edeceğiniz konusunda gerçek zamanlı öneriler alın."
                },
                "invoicing": {
                    "title": "Güzel faturalar",
                    "description": "Saniyeler içinde sizi daha hızlı ödeten profesyonel faturalar oluşturun."
                },
                "expenses": {
                    "title": "Akıllı gider takibi",
                    "description": "Fişinizin fotoğrafını çekin ve yapay zekamızın verileri otomatik olarak çıkarmasına izin verin."
                },
                "reporting": {
                    "title": "Kapsamlı raporlama",
                    "description": "Etkileşimli grafikler ve raporlarla finansal sağlığınızı görselleştirin."
                }
            },
            "how_it_works": {
                "title": "Facturino nasıl çalışır",
                "subtitle": "Günler değil, dakikalar içinde başlayın.",
                "step1": {
                    "title": "Hesap oluşturun",
                    "description": "Ücretsiz kaydolun ve şirket profilinizi ayarlayın."
                },
                "step2": {
                    "title": "Verileri bağlayın",
                    "description": "Müşterilerinizi içe aktarın ve banka hesaplarınızı bağlayın."
                },
                "step3": {
                    "title": "Ödeme alın",
                    "description": "Fatura gönderin ve ödemeleri otomatik olarak takip edin."
                }
            },
            "testimonials": {
                "title": "Sizinki gibi işletmeler tarafından seviliyor",
                "review1": "Facturino, finanslarımızı yönetme şeklimizi tamamen değiştirdi. Yapay zeka içgörüleri oyunun kurallarını değiştiriyor.",
                "author1": "Elena M.",
                "role1": "TechStart CEO'su",
                "review2": "Kullandığım en iyi faturalama yazılımı. Basit, hızlı ve güzel.",
                "author2": "Marko P.",
                "role2": "Serbest tasarımcı",
                "review3": "Otomatik gider takibi sayesinde her hafta defter tutmada saatler kazanıyorum.",
                "author3": "Sarah J.",
                "role3": "Cafe Bliss sahibi"
            },
            "faq": {
                "title": "Sıkça sorulan sorular",
                "q1": "Ücretsiz deneme var mı?",
                "a1": "Evet, tüm ücretli planlar için 14 günlük ücretsiz deneme sunuyoruz.",
                "q2": "İstediğim zaman iptal edebilir miyim?",
                "a2": "Kesinlikle. Uzun vadeli sözleşme yok ve istediğiniz zaman iptal edebilirsiniz.",
                "q3": "Verilerim güvende mi?",
                "a3": "Verilerinizin her zaman güvende olmasını sağlamak için banka düzeyinde şifreleme kullanıyoruz.",
                "q4": "Birden fazla para birimini destekliyor musunuz?",
                "a4": "Evet, Facturino canlı döviz kurlarıyla 100'den fazla para birimini destekler."
            },
            "cta_footer": {
                "title": "Muhasebenizi kolaylaştırmaya hazır mısınız?",
                "subtitle": "Facturino'ya güvenen binlerce işletmeye katılın.",
                "button": "Şimdi başlayın"
            },
            "footer": {
                "product": "Ürün",
                "features": "Özellikler",
                "pricing": "Fiyatlandırma",
                "company": "Şirket",
                "about": "Hakkımızda",
                "blog": "Blog",
                "careers": "Kariyer",
                "legal": "Yasal",
                "privacy": "Gizlilik politikası",
                "terms": "Hizmet şartları",
                "rights": "Tüm hakları saklıdır."
            }
        },
        "pricing_public": {
            "title": "Basit, şeffaf fiyatlandırma",
            "subtitle": "İşletmenizin ihtiyaçlarına uygun planı seçin.",
            "monthly": "Aylık",
            "yearly": "Yıllık",
            "save_text": "%20 tasarruf edin",
            "most_popular": "En popüler",
            "per_month": "/ay",
            "per_year": "/yıl",
            "start_trial": "Ücretsiz denemeye başlayın",
            "get_started": "Başlayın",
            "contact_sales": "Satışla iletişime geçin",
            "features_label": "{prev} içindeki her şey artı:",
            "custom_solution": {
                "title": "Özel bir çözüme mi ihtiyacınız var?",
                "description": "Kurumsal fiyatlandırma ve özel entegrasyonlar için bizimle iletişime geçin.",
                "button": "Satışla iletişime geçin"
            },
            "plans": {
                "free": {
                    "name": "Ücretsiz",
                    "description": "Yeni başlayan serbest çalışanlar için mükemmel."
                },
                "starter": {
                    "name": "Başlangıç",
                    "description": "Hızla büyüyen küçük işletmeler için harika."
                },
                "standard": {
                    "name": "Standart",
                    "description": "Ekipleri olan yerleşik işletmeler için."
                },
                "business": {
                    "name": "İşletme",
                    "description": "Ölçeklenen şirketler için gelişmiş özellikler."
                }
            }
        }
    }
}

def update_json_file(file_path, lang_code):
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        # Update with real translations
        if lang_code in translations:
            data.update(translations[lang_code])
        
        with open(file_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print(f"✅ Updated {file_path} with real {lang_code.upper()} translations")
    except Exception as e:
        print(f"❌ Error updating {file_path}: {e}")

# Update translation files
base_path = "/Users/tamsar/Downloads/mkaccounting/lang"
update_json_file(os.path.join(base_path, "mk.json"), "mk")
update_json_file(os.path.join(base_path, "sq.json"), "sq")
update_json_file(os.path.join(base_path, "tr.json"), "tr")

print("\n✅ All translations updated with real content!")
print("\nNext steps:")
print("1. Run: npm run build")
print("2. Commit: git add -A && git commit -m 'fix: Replace placeholder translations with real Macedonian, Albanian, and Turkish'")
print("3. Push: git push")
print("4. Deploy to production (Railway will auto-deploy on push)")
