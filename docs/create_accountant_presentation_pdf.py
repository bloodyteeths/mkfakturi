#!/usr/bin/env python3
"""
Generate professional Macedonian-language accountant presentation PDF
for Facturino DOOEL Veles partner bureau outreach.

Usage:
    pip install reportlab
    python docs/create_accountant_presentation_pdf.py

Output: docs/FACTURINO_Prezentacija_Smetkovoditeli.pdf
"""

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, KeepTogether, Image
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch, cm
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY, TA_RIGHT
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
import os
import sys

# Colors
BRAND_BLUE = colors.HexColor('#1a56db')
BRAND_DARK = colors.HexColor('#1e293b')
BRAND_LIGHT_BG = colors.HexColor('#f0f7ff')
BRAND_GREEN = colors.HexColor('#059669')
BRAND_GREEN_LIGHT = colors.HexColor('#ecfdf5')
BRAND_GOLD = colors.HexColor('#d97706')
BRAND_GOLD_LIGHT = colors.HexColor('#fffbeb')
BRAND_RED = colors.HexColor('#dc2626')
BRAND_GRAY = colors.HexColor('#64748b')
BRAND_LIGHT_GRAY = colors.HexColor('#f1f5f9')
WHITE = colors.white

PAGE_WIDTH, PAGE_HEIGHT = A4


def register_fonts():
    """Register DejaVu fonts for Cyrillic support."""
    font_paths = [
        '/usr/share/fonts/truetype/dejavu/',
        '/usr/share/fonts/dejavu/',
        '/System/Library/Fonts/',
        '/Library/Fonts/',
        os.path.expanduser('~/Library/Fonts/'),
    ]

    dejavu_regular = None
    dejavu_bold = None

    for path in font_paths:
        reg = os.path.join(path, 'DejaVuSans.ttf')
        bold = os.path.join(path, 'DejaVuSans-Bold.ttf')
        if os.path.exists(reg) and os.path.exists(bold):
            dejavu_regular = reg
            dejavu_bold = bold
            break

    if dejavu_regular and dejavu_bold:
        pdfmetrics.registerFont(TTFont('DejaVu', dejavu_regular))
        pdfmetrics.registerFont(TTFont('DejaVu-Bold', dejavu_bold))
        return 'DejaVu', 'DejaVu-Bold'
    else:
        print("WARNING: DejaVu fonts not found. Cyrillic may not render correctly.")
        print("Install with: brew install font-dejavu (macOS) or apt install fonts-dejavu (Linux)")
        return 'Helvetica', 'Helvetica-Bold'


def create_styles(font_regular, font_bold):
    """Create all paragraph styles."""
    styles = getSampleStyleSheet()

    custom = {}

    custom['title'] = ParagraphStyle(
        'CustomTitle', parent=styles['Heading1'],
        fontSize=32, spaceAfter=4, spaceBefore=0,
        alignment=TA_CENTER, textColor=BRAND_BLUE,
        fontName=font_bold, leading=38
    )

    custom['subtitle'] = ParagraphStyle(
        'CustomSubtitle', parent=styles['Normal'],
        fontSize=14, spaceAfter=6, spaceBefore=2,
        alignment=TA_CENTER, textColor=BRAND_DARK,
        fontName=font_regular, leading=18
    )

    custom['company'] = ParagraphStyle(
        'Company', parent=styles['Normal'],
        fontSize=11, spaceAfter=2,
        alignment=TA_CENTER, textColor=BRAND_GRAY,
        fontName=font_regular
    )

    custom['slide_title'] = ParagraphStyle(
        'SlideTitle', parent=styles['Heading1'],
        fontSize=20, spaceBefore=0, spaceAfter=10,
        textColor=WHITE, fontName=font_bold,
        backColor=BRAND_BLUE, borderPadding=(10, 12, 10, 12),
        leading=26
    )

    custom['section'] = ParagraphStyle(
        'Section', parent=styles['Heading2'],
        fontSize=14, spaceBefore=14, spaceAfter=6,
        textColor=BRAND_BLUE, fontName=font_bold, leading=18
    )

    custom['body'] = ParagraphStyle(
        'Body', parent=styles['Normal'],
        fontSize=10, spaceAfter=4,
        fontName=font_regular, leading=14,
        alignment=TA_LEFT
    )

    custom['body_center'] = ParagraphStyle(
        'BodyCenter', parent=styles['Normal'],
        fontSize=10, spaceAfter=4,
        fontName=font_regular, leading=14,
        alignment=TA_CENTER
    )

    custom['bullet'] = ParagraphStyle(
        'Bullet', parent=styles['Normal'],
        fontSize=10, spaceAfter=3,
        fontName=font_regular, leading=14,
        leftIndent=15, bulletIndent=0
    )

    custom['highlight'] = ParagraphStyle(
        'Highlight', parent=styles['Normal'],
        fontSize=12, spaceAfter=6, spaceBefore=6,
        fontName=font_bold, leading=16,
        alignment=TA_CENTER, textColor=BRAND_GREEN,
        backColor=BRAND_GREEN_LIGHT,
        borderPadding=(8, 10, 8, 10)
    )

    custom['cta'] = ParagraphStyle(
        'CTA', parent=styles['Normal'],
        fontSize=13, spaceAfter=6, spaceBefore=6,
        fontName=font_bold, leading=18,
        alignment=TA_CENTER, textColor=WHITE,
        backColor=BRAND_BLUE,
        borderPadding=(12, 14, 12, 14)
    )

    custom['gold_box'] = ParagraphStyle(
        'GoldBox', parent=styles['Normal'],
        fontSize=11, spaceAfter=6, spaceBefore=6,
        fontName=font_bold, leading=16,
        alignment=TA_CENTER, textColor=BRAND_GOLD,
        backColor=BRAND_GOLD_LIGHT,
        borderPadding=(8, 10, 8, 10)
    )

    custom['footer'] = ParagraphStyle(
        'Footer', parent=styles['Normal'],
        fontSize=8, alignment=TA_CENTER,
        textColor=BRAND_GRAY, fontName=font_regular
    )

    custom['small'] = ParagraphStyle(
        'Small', parent=styles['Normal'],
        fontSize=9, spaceAfter=2,
        fontName=font_regular, leading=12,
        textColor=BRAND_GRAY
    )

    return custom


def make_table(data, col_widths, header_bg=BRAND_BLUE, header_fg=WHITE,
               font_regular='DejaVu', font_bold='DejaVu-Bold'):
    """Create a styled table."""
    t = Table(data, colWidths=col_widths, repeatRows=1)
    style_cmds = [
        ('BACKGROUND', (0, 0), (-1, 0), header_bg),
        ('TEXTCOLOR', (0, 0), (-1, 0), header_fg),
        ('FONTNAME', (0, 0), (-1, 0), font_bold),
        ('FONTSIZE', (0, 0), (-1, 0), 9),
        ('FONTNAME', (0, 1), (-1, -1), font_regular),
        ('FONTSIZE', (0, 1), (-1, -1), 9),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#cbd5e1')),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [WHITE, BRAND_LIGHT_GRAY]),
        ('TOPPADDING', (0, 0), (-1, -1), 5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 5),
        ('LEFTPADDING', (0, 0), (-1, -1), 6),
        ('RIGHTPADDING', (0, 0), (-1, -1), 6),
    ]
    t.setStyle(TableStyle(style_cmds))
    return t


def build_presentation():
    """Build the full presentation PDF."""
    font_regular, font_bold = register_fonts()
    s = create_styles(font_regular, font_bold)

    output_path = os.path.join(os.path.dirname(__file__),
                                'FACTURINO_Prezentacija_Smetkovoditeli.pdf')

    doc = SimpleDocTemplate(
        output_path,
        pagesize=A4,
        rightMargin=1.2 * cm,
        leftMargin=1.2 * cm,
        topMargin=1.2 * cm,
        bottomMargin=1.5 * cm,
    )

    usable_width = PAGE_WIDTH - 2.4 * cm
    story = []

    # ================================================================
    # PAGE 1: Title with Logo
    # ================================================================
    # Add logo
    logo_path = os.path.join(os.path.dirname(__file__), '..', 'logo', 'facturino_logo_transparent.png')
    if os.path.exists(logo_path):
        story.append(Spacer(1, 20))
        logo = Image(logo_path, width=4.5 * cm, height=4.5 * cm)
        logo.hAlign = 'CENTER'
        story.append(logo)
        story.append(Spacer(1, 10))
    else:
        story.append(Spacer(1, 60))
    story.append(Paragraph("FACTURINO", s['title']))
    story.append(Spacer(1, 8))
    story.append(Paragraph(
        "Единствената сметководствена платформа<br/>направена за Македонија",
        s['subtitle']
    ))
    story.append(Spacer(1, 20))
    story.append(Paragraph("Facturino ДООЕЛ Велес", s['company']))
    story.append(Spacer(1, 30))
    story.append(Paragraph(
        "Партнерска програма за сметководствени бироа",
        s['highlight']
    ))
    story.append(Spacer(1, 12))
    story.append(Paragraph(
        "20% месечна провизија  •  Бесплатен пристап  •  Приоритетна поддршка",
        s['gold_box']
    ))
    story.append(Spacer(1, 40))
    story.append(Paragraph(
        "info@facturino.mk  |  facturino.mk  |  app.facturino.mk",
        s['body_center']
    ))
    story.append(Spacer(1, 6))
    story.append(Paragraph("Февруари 2026", s['small']))

    story.append(PageBreak())

    # ================================================================
    # PAGE 2: What is Facturino + Partner Program
    # ================================================================
    story.append(Paragraph("ШТО Е FACTURINO?", s['slide_title']))
    story.append(Spacer(1, 8))

    story.append(Paragraph(
        "Facturino е модерна cloud платформа за фактурирање и сметководство "
        "направена специјално за Македонија.", s['body']
    ))
    story.append(Spacer(1, 4))

    features_intro = [
        "Македонски ДДВ стапки (18%, 5%, 0%) — вградени",
        "Поврзување со 4 македонски банки (NLB, Стопанска, Комерцијална, Шпаркасе)",
        "Е-фактура за УЈП — UBL 2.1 XML формат, спремни за кога ќе стане задолжително",
        "Плати по македонски закон — МПИН, ПДД, придонеси, платни листи",
        "4 јазици: Македонски, Албански, Турски, Англиски",
        "Македонски контен план (сметковен план) вграден",
    ]
    for f in features_intro:
        story.append(Paragraph(f"•  {f}", s['bullet']))

    story.append(Spacer(1, 12))
    story.append(Paragraph("ПАРТНЕРСКА ПРОГРАМА — 20% ПРОВИЗИЈА", s['slide_title']))
    story.append(Spacer(1, 8))

    partner_data = [
        ['Бенефит', 'Детали'],
        ['Бесплатен пристап', 'Сите функции бесплатно за вас'],
        ['20% провизија', 'Од секоја месечна претплата на вашите клиенти'],
        ['22% провизија (Plus)', 'Кога имате 10+ активни клиенти'],
        ['Партнерски портал', 'Dashboard со статистики и заработка'],
        ['Месечна исплата', 'Преку банкарска сметка, секој месец'],
        ['Персонализиран линк', 'За лесно споделување со клиенти'],
        ['Приоритетна поддршка', 'Директна линија за партнери'],
    ]
    story.append(make_table(partner_data, [usable_width * 0.35, usable_width * 0.65],
                            header_bg=BRAND_GREEN, font_regular=font_regular, font_bold=font_bold))

    story.append(Spacer(1, 10))
    story.append(Paragraph(
        "Провизијата е ДОЖИВОТНА — додека клиентот плаќа, вие добивате 20%",
        s['gold_box']
    ))

    story.append(PageBreak())

    # ================================================================
    # PAGE 3: Earnings Calculator + Pricing
    # ================================================================
    story.append(Paragraph("КОЛКУ МОЖЕТЕ ДА ЗАРАБОТИТЕ?", s['slide_title']))
    story.append(Spacer(1, 8))

    earnings_data = [
        ['Клиенти', 'План', 'Месечно (клиенти)', 'Ваша провизија', 'Годишно'],
        ['5', 'Стартер (€12)', '€60', '€12/мес (20%)', '€144'],
        ['10', 'Стандарден (€29)', '€290', '€58/мес (20%)', '€696'],
        ['20', 'Стандарден (€29)', '€580', '€127/мес (22%)', '€1,524'],
        ['30', 'Микс', '~€1,200', '€264/мес (22%)', '€3,168'],
        ['50', 'Микс', '~€2,000', '€440/мес (22%)', '€5,280'],
    ]
    w = usable_width
    story.append(make_table(earnings_data,
                            [w * 0.12, w * 0.25, w * 0.22, w * 0.22, w * 0.19],
                            font_regular=font_regular, font_bold=font_bold))

    story.append(Spacer(1, 8))
    story.append(Paragraph(
        "ПАСИВЕН ПРИХОД — еднаш ги ставате клиентите, секој месец добивате провизија",
        s['highlight']
    ))

    story.append(Spacer(1, 14))
    story.append(Paragraph("ЦЕНОВНИК ЗА КЛИЕНТИТЕ (ФИРМИТЕ)", s['slide_title']))
    story.append(Spacer(1, 8))

    pricing_data = [
        ['План', 'Цена/мес', 'Фактури', 'Корисници', 'Клучни функции'],
        ['Бесплатен', '€0', '5', '1', 'Основно фактурирање'],
        ['Стартер', '€12', '50', '1', 'Рекурентни, расходи, понуди'],
        ['Стандарден', '€29', '200', '3', 'Е-фактура, банки, QES'],
        ['Бизнис', '€59', '1,000', '5', 'Плати, API, повеќе валути'],
        ['Макс', '€149', 'Неогр.', 'Неогр.', 'Сe + 100 AI прашања'],
    ]
    story.append(make_table(pricing_data,
                            [w * 0.15, w * 0.12, w * 0.12, w * 0.14, w * 0.47],
                            font_regular=font_regular, font_bold=font_bold))

    story.append(Spacer(1, 6))
    story.append(Paragraph(
        "14 дена бесплатен Стандарден план за секој нов клиент — без кредитна картичка",
        s['small']
    ))

    story.append(PageBreak())

    # ================================================================
    # PAGE 4: All Features
    # ================================================================
    story.append(Paragraph("СИТЕ ФУНКЦИИ НА FACTURINO", s['slide_title']))
    story.append(Spacer(1, 6))

    feature_sections = [
        ("Фактурирање и продажба", [
            "Фактури, понуди, проформа, кредитни ноти",
            "PDF со QR код за плаќање",
            "Автоматско нумерирање",
            "Рекурентни фактури (автоматски секој месец)",
            "Статуси: Нацрт → Испратена → Видена → Платена",
        ]),
        ("Сметководство", [
            "Приходи и расходи со категории",
            "Книга на влезни фактури (bills)",
            "МСФИ извештаи (биланс на состојба/успех)",
            "Аналитички извештаи по клиент, период, категорија",
            "Македонски контен план",
        ]),
        ("Е-фактура (подготвени за УЈП)", [
            "UBL 2.1 XML генерирање",
            "QES дигитален потпис",
            "Прием и прифаќање/одбивање на е-фактури",
        ]),
        ("Банкарски поврзувања (PSD2)", [
            "NLB, Стопанска, Комерцијална, Шпаркасе — директна врска",
            "Автоматски увоз на трансакции",
            "Полу-автоматско порамнување на уплати со фактури",
            "CSV увоз за банки без PSD2",
        ]),
        ("Плати и вработени", [
            "Пресметка по македонски закон (ПДД, придонеси)",
            "Прекувремена, одмор, боледување, породилно",
            "МПИН XML извоз, платни листи PDF",
        ]),
        ("Дополнително", [
            "Материјално сметководство (залихи, магацини, WAC)",
            "Проекти (профитабилност по проект)",
            "AI помошник (прогнози, анализи, ризици)",
            "PWA мобилна апликација",
            "WooCommerce интеграција",
        ]),
    ]

    for title, items in feature_sections:
        story.append(Paragraph(f"<b>{title}</b>", s['section']))
        for item in items:
            story.append(Paragraph(f"•  {item}", s['bullet']))
        story.append(Spacer(1, 2))

    story.append(PageBreak())

    # ================================================================
    # PAGE 5: Multi-company + Competition
    # ================================================================
    story.append(Paragraph("МУЛТИ-КОМПАНИЈА — ЗА БИРОА", s['slide_title']))
    story.append(Spacer(1, 6))

    multi_features = [
        "Еден логин за пристап до сите клиенти (фирми)",
        "Dashboard со рокови за сите клиенти (ДДВ-04, МПИН, данок на добивка)",
        "Масовни извештаи за повеќе клиенти одеднаш",
        "Портал за клиенти — тие качуваат скенирани фактури и изводи",
        "Автоматски рокови — системот ве потсетува",
        "Безбедност — секој клиент ги гледа само своите податоци",
    ]
    for f in multi_features:
        story.append(Paragraph(f"•  {f}", s['bullet']))

    story.append(Spacer(1, 6))

    compare_before_after = [
        ['Без Facturino', 'Со Facturino'],
        ['Различен софтвер за секој клиент', 'Еден систем за сите'],
        ['Рачно следење на рокови', 'Автоматски dashboard со рокови'],
        ['Часови copy-paste на изводи', 'Автоматски увоз од банка'],
        ['Ризик од грешки', 'Автоматска валидација'],
        ['Без преглед на сите клиенти', 'Целосен преглед на едно место'],
    ]
    story.append(make_table(compare_before_after, [usable_width * 0.5, usable_width * 0.5],
                            header_bg=colors.HexColor('#991b1b'),
                            font_regular=font_regular, font_bold=font_bold))

    story.append(Spacer(1, 12))
    story.append(Paragraph("ЗОШТО FACTURINO НАМЕСТО КОНКУРЕНЦИЈАТА?", s['slide_title']))
    story.append(Spacer(1, 6))

    competition_data = [
        ['Функција', 'Facturino', 'Pantheon', 'MiniMax', 'Excel'],
        ['Мак. ДДВ', 'Целосно', 'Да', 'Нема', 'Рачно'],
        ['Е-фактура UBL', 'Автом.', 'Нема', 'Нема', 'Нема'],
        ['PSD2 банки', '4 банки', 'Огран.', 'Нема', 'Нема'],
        ['Плати МПИН', 'Автом.', 'Да', 'Нема', 'Рачно'],
        ['AI помошник', 'Вграден', 'Нема', 'Нема', 'Нема'],
        ['Мулти-комп.', 'Неогр.', 'Скапо', 'Огран.', 'Нема'],
        ['Мобилна', 'PWA', 'Нема', 'Нема', 'Нема'],
        ['Албански', 'Целосно', 'Нема', 'Нема', 'Нема'],
        ['Провизија', '20%', 'Нема', 'Нема', 'Нема'],
    ]
    cw = usable_width
    story.append(make_table(competition_data,
                            [cw * 0.2, cw * 0.2, cw * 0.2, cw * 0.2, cw * 0.2],
                            font_regular=font_regular, font_bold=font_bold))

    story.append(PageBreak())

    # ================================================================
    # PAGE 6: Migration + Security + How to start + Contact
    # ================================================================
    story.append(Paragraph("МИГРАЦИЈА — 10 МИНУТИ НАМЕСТО МЕСЕЦИ", s['slide_title']))
    story.append(Spacer(1, 6))

    story.append(Paragraph(
        "Вашите клиенти користат друг софтвер? Нема проблем — автоматска миграција:", s['body']
    ))
    migration_items = [
        "Onivo — автоматска миграција за 10 минути",
        "Megasoft — увоз преку CSV/Excel",
        "Pantheon — увоз преку извоз на податоци",
        "Excel — директен увоз на табели",
    ]
    for m in migration_items:
        story.append(Paragraph(f"•  {m}", s['bullet']))

    story.append(Paragraph(
        "Точност: 95%+ за македонски бизнис податоци. Миграцијата е бесплатна!",
        s['highlight']
    ))

    story.append(Spacer(1, 10))
    story.append(Paragraph("БЕЗБЕДНОСТ И СИГУРНОСТ", s['slide_title']))
    story.append(Spacer(1, 6))

    security_items = [
        "Европски сервери (Railway/AWS во ЕУ)",
        "HTTPS енкрипција — сите податоци шифрирани",
        "Автоматски бекап секои 6 часа на Cloudflare R2",
        "GDPR усогласеност за заштита на лични податоци",
        "Изолација — секој клиент ги гледа само своите податоци",
        "Двофакторска автентикација (2FA)",
        "7-годишно чување на податоци",
    ]
    for sec in security_items:
        story.append(Paragraph(f"•  {sec}", s['bullet']))

    story.append(Spacer(1, 10))
    story.append(Paragraph("КАКО ДА СТАНЕТЕ ПАРТНЕР? — 3 ЧЕКОРИ", s['slide_title']))
    story.append(Spacer(1, 6))

    steps = [
        ("Чекор 1: Регистрација (2 мин)",
         "Посетете app.facturino.mk/partner/signup — внесете го вашето биро и ЕДБ. "
         "Без кредитна картичка, без обврска."),
        ("Чекор 2: Додадете клиенти",
         "Додадете 1-2 клиенти за проба. 14 дена бесплатен Стандарден план. "
         "Тестирајте ги сите функции."),
        ("Чекор 3: Заработувајте",
         "Клиентите избираат план. Вие добивате 20% секој месец. "
         "Следете ја заработката во партнерскиот портал."),
    ]
    for title, desc in steps:
        story.append(Paragraph(f"<b>{title}</b>", s['section']))
        story.append(Paragraph(desc, s['body']))

    story.append(Spacer(1, 14))
    story.append(Paragraph(
        "Регистрирајте се денес:  app.facturino.mk/partner/signup",
        s['cta']
    ))

    story.append(Spacer(1, 14))

    # Contact info table
    contact_data = [
        ['', ''],
        ['Веб-сајт', 'facturino.mk'],
        ['Апликација', 'app.facturino.mk'],
        ['Партнерска регистрација', 'app.facturino.mk/partner/signup'],
        ['Е-пошта', 'info@facturino.mk'],
        ['За сметководители', 'facturino.mk/for-accountants'],
        ['Ценовник', 'facturino.mk/pricing'],
    ]
    contact_data[0] = ['Facturino ДООЕЛ Велес', 'Контакт информации']
    story.append(make_table(contact_data, [usable_width * 0.45, usable_width * 0.55],
                            header_bg=BRAND_DARK, font_regular=font_regular, font_bold=font_bold))

    story.append(Spacer(1, 16))
    story.append(Paragraph(
        "Facturino ДООЕЛ Велес — Единствената сметководствена платформа за Македонија",
        s['footer']
    ))
    story.append(Paragraph("Февруари 2026", s['footer']))

    # Build PDF
    doc.build(story)
    print(f"PDF created: {output_path}")
    return output_path


if __name__ == "__main__":
    build_presentation()
