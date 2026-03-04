#!/usr/bin/env python3
"""
Generate 2-page partner business model PDF in Macedonian.
Page 1: How the portfolio program works (3 steps, credit wallet, math example)
Page 2: Facturino vs Pantheon vs Zonel feature comparison

Usage:
    pip install reportlab
    python docs/create_partner_business_model_pdf.py

Output: docs/FACTURINO_Partner_Biznis_Model.pdf
"""

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, Image
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
import os

# ── Brand Colors ──────────────────────────────────────────────
BRAND_BLUE = colors.HexColor('#1a56db')
BRAND_DARK = colors.HexColor('#1e293b')
BRAND_LIGHT_BG = colors.HexColor('#f0f7ff')
BRAND_GREEN = colors.HexColor('#059669')
BRAND_GREEN_LIGHT = colors.HexColor('#ecfdf5')
BRAND_GOLD = colors.HexColor('#d97706')
BRAND_GOLD_LIGHT = colors.HexColor('#fffbeb')
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
    for path in font_paths:
        reg = os.path.join(path, 'DejaVuSans.ttf')
        bold = os.path.join(path, 'DejaVuSans-Bold.ttf')
        if os.path.exists(reg) and os.path.exists(bold):
            pdfmetrics.registerFont(TTFont('DejaVu', reg))
            pdfmetrics.registerFont(TTFont('DejaVu-Bold', bold))
            return 'DejaVu', 'DejaVu-Bold'
    print("WARNING: DejaVu fonts not found. Cyrillic may not render.")
    return 'Helvetica', 'Helvetica-Bold'


def create_styles(font_regular, font_bold):
    """Create paragraph styles — compact for 2-page fit."""
    styles = getSampleStyleSheet()
    s = {}
    s['title'] = ParagraphStyle(
        'Title2', parent=styles['Heading1'],
        fontSize=18, spaceAfter=0, spaceBefore=0,
        alignment=TA_CENTER, textColor=WHITE,
        fontName=font_bold, leading=23,
        backColor=BRAND_BLUE, borderPadding=(8, 10, 8, 10),
    )
    s['section'] = ParagraphStyle(
        'Section2', parent=styles['Heading2'],
        fontSize=11, spaceBefore=6, spaceAfter=3,
        textColor=BRAND_BLUE, fontName=font_bold, leading=14,
    )
    s['body'] = ParagraphStyle(
        'Body2', parent=styles['Normal'],
        fontSize=9, spaceAfter=2,
        fontName=font_regular, leading=12,
    )
    s['bullet'] = ParagraphStyle(
        'Bullet2', parent=styles['Normal'],
        fontSize=9, spaceAfter=1,
        fontName=font_regular, leading=12,
        leftIndent=12, bulletIndent=0,
    )
    s['bullet_sm'] = ParagraphStyle(
        'BulletSm', parent=styles['Normal'],
        fontSize=8, spaceAfter=1,
        fontName=font_regular, leading=11,
        leftIndent=12, bulletIndent=0,
    )
    s['highlight'] = ParagraphStyle(
        'Highlight2', parent=styles['Normal'],
        fontSize=9, spaceAfter=3, spaceBefore=3,
        fontName=font_bold, leading=13,
        alignment=TA_CENTER, textColor=BRAND_GREEN,
        backColor=BRAND_GREEN_LIGHT,
        borderPadding=(6, 8, 6, 8),
    )
    s['gold_box'] = ParagraphStyle(
        'GoldBox2', parent=styles['Normal'],
        fontSize=9, spaceAfter=3, spaceBefore=3,
        fontName=font_bold, leading=12,
        alignment=TA_CENTER, textColor=BRAND_GOLD,
        backColor=BRAND_GOLD_LIGHT,
        borderPadding=(5, 7, 5, 7),
    )
    s['cta'] = ParagraphStyle(
        'CTA2', parent=styles['Normal'],
        fontSize=11, spaceAfter=2, spaceBefore=4,
        fontName=font_bold, leading=14,
        alignment=TA_CENTER, textColor=WHITE,
        backColor=BRAND_BLUE,
        borderPadding=(8, 10, 8, 10),
    )
    s['footer'] = ParagraphStyle(
        'Footer2', parent=styles['Normal'],
        fontSize=7, alignment=TA_CENTER,
        textColor=BRAND_GRAY, fontName=font_regular,
    )
    s['small'] = ParagraphStyle(
        'Small2', parent=styles['Normal'],
        fontSize=8, spaceAfter=1,
        fontName=font_regular, leading=10,
        textColor=BRAND_GRAY, alignment=TA_CENTER,
    )
    return s


def make_table(data, col_widths, header_bg=None, header_fg=WHITE,
               font_regular='DejaVu', font_bold='DejaVu-Bold', font_size=8):
    """Create a compact styled table."""
    if header_bg is None:
        header_bg = BRAND_BLUE
    t = Table(data, colWidths=col_widths, repeatRows=1)
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), header_bg),
        ('TEXTCOLOR', (0, 0), (-1, 0), header_fg),
        ('FONTNAME', (0, 0), (-1, 0), font_bold),
        ('FONTSIZE', (0, 0), (-1, 0), font_size),
        ('FONTNAME', (0, 1), (-1, -1), font_regular),
        ('FONTSIZE', (0, 1), (-1, -1), font_size),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('GRID', (0, 0), (-1, -1), 0.4, colors.HexColor('#cbd5e1')),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [WHITE, BRAND_LIGHT_GRAY]),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ('LEFTPADDING', (0, 0), (-1, -1), 4),
        ('RIGHTPADDING', (0, 0), (-1, -1), 4),
    ]))
    return t


def build_step_box(number, title, desc, bg_color, font_bold, font_regular):
    """Build a compact step cell."""
    data = [
        [Paragraph(str(number), ParagraphStyle(
            f'SN{number}', fontSize=16, fontName=font_bold,
            textColor=WHITE, alignment=TA_CENTER, leading=20,
        ))],
        [Paragraph(title, ParagraphStyle(
            f'ST{number}', fontSize=8, fontName=font_bold,
            textColor=BRAND_DARK, alignment=TA_CENTER, leading=11,
        ))],
        [Paragraph(desc, ParagraphStyle(
            f'SD{number}', fontSize=7, fontName=font_regular,
            textColor=BRAND_GRAY, alignment=TA_CENTER, leading=10,
        ))],
    ]
    t = Table(data, colWidths=[None])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), bg_color),
        ('BACKGROUND', (0, 1), (0, -1), BRAND_LIGHT_BG),
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (0, 0), 6),
        ('BOTTOMPADDING', (0, 0), (0, 0), 6),
        ('TOPPADDING', (0, 1), (-1, -1), 4),
        ('BOTTOMPADDING', (0, -1), (0, -1), 6),
        ('LEFTPADDING', (0, 0), (-1, -1), 4),
        ('RIGHTPADDING', (0, 0), (-1, -1), 4),
        ('BOX', (0, 0), (-1, -1), 0.5, colors.HexColor('#e2e8f0')),
    ]))
    return t


def build_pdf():
    """Build the 2-page partner business model PDF."""
    font_regular, font_bold = register_fonts()
    s = create_styles(font_regular, font_bold)

    output_path = os.path.join(os.path.dirname(__file__),
                               'FACTURINO_Partner_Biznis_Model.pdf')
    doc = SimpleDocTemplate(
        output_path,
        pagesize=A4,
        rightMargin=1.0 * cm,
        leftMargin=1.0 * cm,
        topMargin=0.8 * cm,
        bottomMargin=0.8 * cm,
    )
    W = PAGE_WIDTH - 2.0 * cm
    story = []

    logo_path = os.path.join(os.path.dirname(__file__), '..', 'logo', 'facturino_logo_transparent.png')

    # ══════════════════════════════════════════════════════════
    # PAGE 1: Business Model
    # ══════════════════════════════════════════════════════════

    # Logo (small)
    if os.path.exists(logo_path):
        logo = Image(logo_path, width=1.8 * cm, height=1.8 * cm)
        logo.hAlign = 'CENTER'
        story.append(logo)
        story.append(Spacer(1, 2))

    story.append(Paragraph("ПАРТНЕРСКА ПРОГРАМА ЗА СМЕТКОВОДИТЕЛИ", s['title']))
    story.append(Spacer(1, 6))

    # ── 3 Steps ───────────────────────────────────────────────
    story.append(Paragraph("Како работи?", s['section']))

    step_w = W / 3 - 3
    steps_table = Table(
        [[
            build_step_box(1, "Регистрирајте се",
                           "Бесплатно, без обврска",
                           BRAND_BLUE, font_bold, font_regular),
            build_step_box(2, "Додадете фирми",
                           "Сите добиваат Standard\n45 дена бесплатно",
                           BRAND_GREEN, font_bold, font_regular),
            build_step_box(3, "Заработувајте",
                           "20% провизија од секоја\nфирма што плаќа",
                           BRAND_GOLD, font_bold, font_regular),
        ]],
        colWidths=[step_w, step_w, step_w],
    )
    steps_table.setStyle(TableStyle([
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('LEFTPADDING', (0, 0), (-1, -1), 2),
        ('RIGHTPADDING', (0, 0), (-1, -1), 2),
    ]))
    story.append(steps_table)
    story.append(Spacer(1, 6))

    # ── After 45 Days ─────────────────────────────────────────
    story.append(Paragraph("По 45 дена — Sliding Scale + Кредитен Паричник", s['section']))

    after_items = [
        "<b>1:1 покривање</b> — секоја фирма што плаќа покрива 1 што не плаќа (Standard план)",
        "<b>Кредитен паричник</b> — провизијата (20%) прво покрива непокриени фирми. Остатокот = ИСПЛАТА",
        "<b>Непокриени фирми</b> — можат да ГЛЕДААТ сè, но не и да уредуваат (view-only)",
    ]
    for item in after_items:
        story.append(Paragraph(f"•  {item}", s['bullet']))

    story.append(Spacer(1, 4))

    # ── Math Example ──────────────────────────────────────────
    story.append(Paragraph("Пример со 10 фирми", s['section']))

    example_data = [
        ['Фирми', 'Плаќаат?', 'Детал'],
        ['Фирма 1-5', 'ДА', 'Standard (€39) × 5 = €195/мес приход'],
        ['Фирма 6-10', 'НЕ', 'Покриени 1:1 од платените 5 фирми'],
    ]
    story.append(make_table(example_data, [W * 0.18, W * 0.14, W * 0.68],
                            font_regular=font_regular, font_bold=font_bold))
    story.append(Spacer(1, 2))

    story.append(Paragraph(
        "Провизија: 20% × €195 = <b>€39/мес</b>  |  Сите 10 покриени  |  ВАША ИСПЛАТА: <b>€39/мес (€468/год)</b>",
        s['highlight']
    ))
    story.append(Paragraph(
        "Со 20 фирми (10 платени): ~€78/мес = €936/год  •  "
        "Со 60 фирми (30 платени): ~€234/мес = €2,808/год",
        s['gold_box']
    ))
    story.append(Spacer(1, 4))

    # ── What companies get ────────────────────────────────────
    story.append(Paragraph("Што добиваат вашите клиенти (фирмите)?", s['section']))

    features = [
        "Е-фактура UBL 2.1 + QES дигитален потпис — подготвени за задолжителна е-фактура",
        "PSD2 банкарска врска — NLB, Стопанска, Комерцијална, Шпаркасе",
        "Плати по мак. закон — МПИН, ПДД, придонеси, платни листи",
        "Основни средства — амортизација, регистар, GL книжење",
        "Завршна сметка — 6-чекор волшебник со сите законски обрасци",
        "AI помошник — прогнози, анализи, ризици (на македонски)",
        "Cloud — работете од секаде, без инсталација, безбедно",
    ]
    for f in features:
        story.append(Paragraph(f"•  {f}", s['bullet_sm']))

    story.append(Spacer(1, 6))

    # CTA + Footer
    story.append(Paragraph(
        "Регистрирајте се бесплатно:  app.facturino.mk/partner/signup",
        s['cta']
    ))
    story.append(Spacer(1, 4))
    story.append(Paragraph(
        "Facturino ДООЕЛ Велес  |  facturino.mk  |  info@facturino.mk  |  Март 2026",
        s['footer']
    ))

    # ══════════════════════════════════════════════════════════
    # PAGE 2: Feature Comparison
    # ══════════════════════════════════════════════════════════
    story.append(PageBreak())

    if os.path.exists(logo_path):
        logo2 = Image(logo_path, width=1.5 * cm, height=1.5 * cm)
        logo2.hAlign = 'CENTER'
        story.append(logo2)
        story.append(Spacer(1, 1))

    story.append(Paragraph("СПОРЕДБА: FACTURINO vs КОНКУРЕНЦИЈАТА", s['title']))
    story.append(Spacer(1, 5))

    # ── Main comparison table ─────────────────────────────────
    cmp_data = [
        ['Функција', 'Facturino', 'Pantheon', 'Zonel HELIX'],
        ['Тип', 'Cloud (web)', 'Desktop ERP', 'Desktop'],
        ['Цена за биро', 'Бесплатно', '~€70/мес', 'Не е јавна'],
        ['Цена за фирми', 'Од €12/мес', 'Вкл. во лиценца', 'Вкл. во лиценца'],
        ['Мак. ДДВ', 'Целосно', 'Да', 'Да'],
        ['Е-фактура UBL', 'Да + QES потпис', 'Подготвено', 'Нема'],
        ['PSD2 банки', '4 банки директно', 'Нема PSD2', 'Нема'],
        ['Плати + МПИН', 'Целосно', 'Да', 'Да'],
        ['Залихи/магацин', 'Да', 'Да', 'Да'],
        ['Основни средства', 'Да (амортизација)', 'Да', 'Да'],
        ['Завршна сметка', 'Да (6-чекори)', 'Да', 'Да'],
        ['AI помошник', 'Да (МК јазик)', 'Нема', 'Нема'],
        ['Мобилен пристап', 'PWA', 'Нема', 'Ограничено'],
        ['Мулти-компанија', '1 логин, неогр.', 'Скапо', 'Ограничено'],
        ['4 јазици', 'MK, SQ, TR, EN', 'Само MK', 'Само MK'],
        ['Миграција', 'Автоматска', 'Рачна', 'Рачна'],
        ['Провизија за биро', '20% месечно', 'Нема', 'Нема'],
    ]
    cw = W
    story.append(make_table(cmp_data,
                            [cw * 0.22, cw * 0.26, cw * 0.26, cw * 0.26],
                            font_regular=font_regular, font_bold=font_bold,
                            font_size=7.5))
    story.append(Spacer(1, 6))

    # ── Pricing comparison ────────────────────────────────────
    story.append(Paragraph("Ценовна споредба", s['section']))

    price_data = [
        ['', 'Facturino', 'Pantheon', 'Zonel HELIX'],
        ['За вас (биро)', 'БЕСПЛАТНО', '~€70/мес лиценца', 'Непозната цена'],
        ['За фирми', '€12-149/мес', 'Вкл. во лиценца', 'Вкл. во лиценца'],
        ['Провизија', '20% месечно', 'Нема', 'Нема'],
        ['Пристап', 'Cloud (од секаде)', 'Desktop', 'Desktop'],
    ]
    price_table = make_table(price_data,
                             [cw * 0.2, cw * 0.27, cw * 0.27, cw * 0.26],
                             header_bg=BRAND_DARK,
                             font_regular=font_regular, font_bold=font_bold,
                             font_size=7.5)
    price_table.setStyle(TableStyle([
        ('BACKGROUND', (1, 1), (1, -1), BRAND_GREEN_LIGHT),
        ('TEXTCOLOR', (1, 1), (1, 1), BRAND_GREEN),
        ('FONTNAME', (1, 1), (1, 1), font_bold),
    ]))
    story.append(price_table)
    story.append(Spacer(1, 6))

    # ── 5 Reasons ─────────────────────────────────────────────
    story.append(Paragraph("5 причини зошто Facturino", s['section']))

    reasons = [
        "БЕСПЛАТНО за сметководители + 20% провизија доживотно",
        "Cloud — работете од секаде, без инсталација, без одржување",
        "Е-фактура UBL 2.1 + QES — подготвени за задолжителна е-фактура",
        "PSD2 банкарска врска — автоматски увоз на трансакции од 4 банки",
        "AI помошник — побрзо затворање на месецот, прогнози, анализи",
    ]
    for i, r in enumerate(reasons, 1):
        story.append(Paragraph(f"<b>{i}.</b>  {r}", s['bullet_sm']))

    story.append(Spacer(1, 6))

    # ── Pricing table for companies ───────────────────────────
    story.append(Paragraph("Ценовник за фирми (вашите клиенти)", s['section']))

    pricing_data = [
        ['План', 'Цена', 'Ден', 'Фактури', 'Клучни функции'],
        ['Бесплатен', '€0', '0', '3/мес', 'Основно фактурирање'],
        ['Стартер', '€12', '740', '30/мес', 'Рекурентни, е-фактура (5)'],
        ['Стандарден', '€39', '2,400', '60/мес', 'QES, неогр. е-фактура'],
        ['Бизнис', '€59', '3,630', '150/мес', 'Банки, плати, API'],
        ['Макс', '€149', '9,170', 'Неогр.', 'Сè + МСФИ, неогр.'],
    ]
    story.append(make_table(pricing_data,
                            [cw * 0.15, cw * 0.1, cw * 0.1, cw * 0.12, cw * 0.53],
                            header_bg=BRAND_GREEN,
                            font_regular=font_regular, font_bold=font_bold,
                            font_size=7.5))
    story.append(Spacer(1, 2))
    story.append(Paragraph(
        "Секој нов клиент добива 14 дена бесплатен Standard план — без кредитна картичка",
        s['small']
    ))

    story.append(Spacer(1, 8))

    # CTA + Footer
    story.append(Paragraph(
        "Станете партнер денес:  app.facturino.mk/partner/signup",
        s['cta']
    ))
    story.append(Spacer(1, 4))
    story.append(Paragraph(
        "Facturino ДООЕЛ Велес  |  facturino.mk  |  info@facturino.mk  |  Март 2026",
        s['footer']
    ))

    # Build
    doc.build(story)
    print(f"PDF created: {output_path}")
    return output_path


if __name__ == "__main__":
    build_pdf()
