#!/usr/bin/env python3
"""
Generate professional one-pager PDF for FACTURINO partner bureau outreach
"""

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, Image
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
from reportlab.pdfgen import canvas
import os

def create_onepager_pdf():
    """Create professional one-pager PDF for partner bureau outreach"""
    
    # Set up document
    doc = SimpleDocTemplate(
        "docs/FACTURINO_Partner_Bureau_OnePager.pdf",
        pagesize=A4,
        rightMargin=0.5*inch,
        leftMargin=0.5*inch,
        topMargin=0.5*inch,
        bottomMargin=0.5*inch
    )
    
    # Define styles
    styles = getSampleStyleSheet()
    
    # Custom styles
    title_style = ParagraphStyle(
        'CustomTitle',
        parent=styles['Heading1'],
        fontSize=28,
        spaceAfter=6,
        alignment=TA_CENTER,
        textColor=colors.HexColor('#1a365d'),
        fontName='Helvetica-Bold'
    )
    
    subtitle_style = ParagraphStyle(
        'CustomSubtitle',
        parent=styles['Heading2'],
        fontSize=14,
        spaceAfter=12,
        alignment=TA_CENTER,
        textColor=colors.HexColor('#2d3748'),
        fontName='Helvetica'
    )
    
    section_header_style = ParagraphStyle(
        'SectionHeader',
        parent=styles['Heading2'],
        fontSize=14,
        spaceBefore=16,
        spaceAfter=8,
        textColor=colors.HexColor('#2b6cb0'),
        fontName='Helvetica-Bold',
        backColor=colors.HexColor('#ebf8ff'),
        borderPadding=8
    )
    
    feature_style = ParagraphStyle(
        'Feature',
        parent=styles['Normal'],
        fontSize=10,
        spaceAfter=4,
        leftIndent=20,
        bulletIndent=0,
        fontName='Helvetica'
    )
    
    body_style = ParagraphStyle(
        'Body',
        parent=styles['Normal'],
        fontSize=10,
        spaceAfter=6,
        alignment=TA_JUSTIFY,
        fontName='Helvetica'
    )
    
    # Build content
    story = []
    
    # Header
    story.append(Paragraph("FACTURINO", title_style))
    story.append(Paragraph("Macedonia's Premier Accounting Platform for Partner Bureaus", subtitle_style))
    story.append(Spacer(1, 12))
    
    # Unique Competitive Advantages
    story.append(Paragraph("🏆 UNIQUE COMPETITIVE ADVANTAGES", section_header_style))
    
    # Universal Migration Wizard
    story.append(Paragraph("<b>Universal Migration Wizard - Market Exclusive</b>", feature_style))
    migration_features = [
        "• <b>ONLY platform</b> in Macedonia with automated competitor migration",
        "• <b>100% accuracy</b> for Macedonian field mapping (naziv→name, embs→tax_id)",  
        "• <b>Complete business migration</b> in under 10 minutes (vs months with competitors)",
        "• <b>Supports ALL formats</b>: Onivo, Megasoft, Pantheon, CSV, Excel, XML"
    ]
    for feature in migration_features:
        story.append(Paragraph(feature, feature_style))
    
    story.append(Spacer(1, 8))
    
    # Accountant Console
    story.append(Paragraph("<b>Professional Accountant Console - Market First</b>", feature_style))
    console_features = [
        "• <b>Multi-client management</b> from single dashboard",
        "• <b>Commission tracking</b> with per-client rate customization",
        "• <b>Enterprise-grade security</b> with partner scope validation",
        "• <b>Session-based company switching</b> with audit trail"
    ]
    for feature in console_features:
        story.append(Paragraph(feature, feature_style))
    
    story.append(Spacer(1, 8))
    
    # Macedonia Compliance
    story.append(Paragraph("<b>Complete Macedonia Compliance - Unmatched</b>", feature_style))
    compliance_features = [
        "• <b>ДДВ-04 VAT return automation</b> - \"huge switch lever\" competitors lack",
        "• <b>18% standard, 5% reduced VAT</b> with automatic calculations",
        "• <b>Digital signature support</b> with QES certificate management",
        "• <b>Macedonian Cyrillic</b> throughout interface and documents"
    ]
    for feature in compliance_features:
        story.append(Paragraph(feature, feature_style))
    
    story.append(Spacer(1, 12))
    
    # Macedonia-Specific Benefits
    story.append(Paragraph("🇲🇰 MACEDONIA-SPECIFIC BENEFITS", section_header_style))
    
    macedonia_benefits = [
        "<b>Banking Integration:</b> All 3 major banks (Stopanska, NLB, Komercijalna) with PSD2 support",
        "<b>Language Excellence:</b> 95% Macedonian interface with Albanian parity for both communities", 
        "<b>Tax Authority:</b> E-faktura portal upload with automated XML generation",
        "<b>Business Workflows:</b> Macedonia-specific processes built into every feature"
    ]
    for benefit in macedonia_benefits:
        story.append(Paragraph(f"• {benefit}", feature_style))
    
    story.append(Spacer(1, 12))
    
    # Business Value Proposition
    story.append(Paragraph("💼 BUSINESS VALUE PROPOSITION", section_header_style))
    
    # Two-column table for bureau vs clients
    value_data = [
        ['For Partner Bureaus', 'For Your Clients'],
        ['• Revenue Growth: Commission tracking', '• Painless Migration: Minutes not months'],
        ['• Client Acquisition: Universal migration', '• Modern Experience: Vue 3 interface'],
        ['• Competitive Edge: ONLY automated migration', '• Complete Automation: VAT, bank sync'],
        ['• Professional Image: Enterprise interface', '• Cost Efficiency: Superior functionality'],
        ['• Autonomy: Own QES certificates', '• Future-Proof: Regular enhancements']
    ]
    
    value_table = Table(value_data, colWidths=[3.5*inch, 3.5*inch])
    value_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#2b6cb0')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, 0), 11),
        ('FONTNAME', (0, 1), (-1, -1), 'Helvetica'),
        ('FONTSIZE', (0, 1), (-1, -1), 9),
        ('BOTTOMPADDING', (0, 0), (-1, 0), 8),
        ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#e2e8f0')),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(value_table)
    
    story.append(Spacer(1, 12))
    
    # Technical Superiority
    story.append(Paragraph("🚀 TECHNICAL SUPERIORITY", section_header_style))
    
    tech_features = [
        "<b>Enterprise Architecture:</b> Laravel 12 + Vue 3 + Docker production stack",
        "<b>Performance:</b> <300ms response times with comprehensive optimization",
        "<b>Security:</b> Multi-factor auth, zero vulnerabilities, enterprise-grade",
        "<b>Scalability:</b> Multi-tenant with queue processing and automated monitoring"
    ]
    for feature in tech_features:
        story.append(Paragraph(f"• {feature}", feature_style))
    
    story.append(Spacer(1, 12))
    
    # Why FACTURINO Wins
    story.append(Paragraph("💡 WHY FACTURINO WINS", section_header_style))
    
    # Comparison table
    comparison_data = [
        ['The Competition Cannot:', 'We Deliver:'],
        ['❌ Migrate data automatically', '✅ Minutes vs Months migration'],
        ['❌ Manage multiple clients', '✅ Professional vs Outdated interface'],
        ['❌ Generate ДДВ-04 automatically', '✅ Automated vs Manual processes'],
        ['❌ Support Macedonian + Albanian', '✅ Complete vs Partial compliance']
    ]
    
    comparison_table = Table(comparison_data, colWidths=[3.5*inch, 3.5*inch])
    comparison_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#742a2a')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, 0), 11),
        ('FONTNAME', (0, 1), (-1, -1), 'Helvetica'),
        ('FONTSIZE', (0, 1), (-1, -1), 9),
        ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#e2e8f0')),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(comparison_table)
    
    story.append(Spacer(1, 12))
    
    # Partnership Opportunity
    story.append(Paragraph("🤝 PARTNERSHIP OPPORTUNITY", section_header_style))
    
    contact_info = [
        "<b>Staging Demo:</b> staging.facturino.mk - Full feature access available",
        "<b>Documentation:</b> Comprehensive quick-start guide and 140+ validation steps",
        "<b>Support:</b> Direct technical support during pilot phase",
        "<b>Business Development:</b> Ready for immediate partner bureau engagement"
    ]
    for info in contact_info:
        story.append(Paragraph(f"• {info}", feature_style))
    
    story.append(Spacer(1, 16))
    
    # Call to action
    cta_style = ParagraphStyle(
        'CTA',
        parent=styles['Normal'],
        fontSize=12,
        alignment=TA_CENTER,
        textColor=colors.HexColor('#2b6cb0'),
        fontName='Helvetica-Bold',
        backColor=colors.HexColor('#ebf8ff'),
        borderPadding=12
    )
    
    story.append(Paragraph("Ready to eliminate switching friction and dominate the Macedonia accounting market?", cta_style))
    
    story.append(Spacer(1, 8))
    
    # Footer
    footer_style = ParagraphStyle(
        'Footer',
        parent=styles['Normal'],
        fontSize=10,
        alignment=TA_CENTER,
        textColor=colors.HexColor('#718096'),
        fontName='Helvetica-Oblique'
    )
    
    story.append(Paragraph("FACTURINO - Making accounting software migration effortless for Macedonia businesses", footer_style))
    
    # Build PDF
    doc.build(story)
    print("✅ Professional one-pager PDF created: docs/FACTURINO_Partner_Bureau_OnePager.pdf")

if __name__ == "__main__":
    create_onepager_pdf()

