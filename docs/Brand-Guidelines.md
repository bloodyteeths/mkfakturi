# Facturino Brand Guidelines

## Overview

Facturino is Macedonia's premier accounting platform designed for partner bureaus and accounting professionals. Our brand reflects professionalism, innovation, and deep local expertise in Macedonian business practices.

---

## Brand Identity

### Brand Name
- **Primary**: Facturino
- **Previous**: InvoiceShelf (legacy, being phased out)
- **Usage**: Always capitalize "Facturino" - never use lowercase or alternate spellings

### Brand Positioning
- Macedonia's Premier Accounting Platform for Partner Bureaus
- Professional-grade financial management with complete local compliance
- Universal migration wizard and multi-client management platform

---

## Logo Usage

### Logo Assets
- **Primary Logo**: `/resources/static/img/logo.png`
- **Light Theme**: `/resources/static/img/logo-white.png` 
- **Dark Theme**: `/resources/static/img/logo-gray.png`
- **SVG Component**: `MainLogo.vue` for web applications

### Logo Specifications
- **Viewbox**: `0 0 425 65`
- **Aspect Ratio**: Maintain original proportions
- **Minimum Size**: 120px width for digital use
- **Clear Space**: Minimum padding equal to the height of the "F" in Facturino

### Logo Usage Guidelines
- Always use official logo files - never recreate or modify
- Maintain proper contrast against backgrounds
- Use white logo on dark backgrounds, colored logo on light backgrounds
- Never stretch, skew, or rotate the logo
- Alt text should always be "Facturino Logo" in appropriate language

---

## Color Scheme

### Primary Colors
- **Primary Palette**: CSS variables `--color-primary-50` through `--color-primary-900`
- **Primary Active**: `rgba(var(--color-primary-500), var(--tw-text-opacity))`
- **Primary Light**: `rgba(var(--color-primary-400), var(--tw-text-opacity))`

### Supporting Colors
- **Black**: `#040405`
- **Gray Palette**: Tailwind Slate colors
- **Accent Colors**: 
  - Red: Tailwind red palette (errors, warnings)
  - Teal: Tailwind teal palette (success states)

### Color Usage
- Primary colors for navigation, CTAs, and brand elements
- Gray palette for text, borders, and neutral UI elements
- Maintain WCAG AA contrast ratios for accessibility

---

## Typography

### Primary Font Family
- **Font**: Poppins, sans-serif
- **Fallback**: System sans-serif stack
- **Usage**: All UI text, headings, and body content

### Typography Hierarchy
- **Headings**: Bold weights (600-700)
- **Body Text**: Regular weight (400)
- **UI Elements**: Medium weight (500)
- **Code**: Monospace fallback when needed

---

## Internationalization (i18n) Standards

### Supported Languages
1. **Macedonian (mk)** - Primary market language
2. **Albanian (sq)** - Secondary market language  
3. **English (en)** - Development and international reference

### Language Standards

#### Macedonian (mk)
- **Script**: Cyrillic
- **Encoding**: UTF-8
- **Keyboard**: Macedonian standard layout
- **Currency**: MKD (Macedonian Denar)
- **Date Format**: DD.MM.YYYY
- **Number Format**: 1.234,56 (period for thousands, comma for decimals)

#### Albanian (sq)
- **Script**: Latin
- **Encoding**: UTF-8  
- **Currency**: MKD (when operating in Macedonia)
- **Date Format**: DD/MM/YYYY
- **Number Format**: 1,234.56 (comma for thousands, period for decimals)

### Translation Guidelines
- Technical terms (API, JSON, XML) remain in English
- UI elements must be fully localized
- Error messages localized with technical details in English when helpful
- Navigation keys: Use semantic naming (e.g., `navigation.dashboard` not `nav_dashboard`)
- Maintain consistency in tone - professional but approachable

---

## New Feature Branding

### AI Financial Assistant
- **Icon**: Use existing Heroicons set (consistent with app)
- **Label**: "ИИ Финансиски Асистент" (mk), "AI Financial Assistant" (en)
- **Colors**: Primary color scheme
- **Positioning**: Enhanced productivity tool, not replacement for accountants

### Migration Wizard
- **Icon**: ArrowDownTrayIcon (import/download symbol)
- **Label**: "Мастер за миграција" (mk), "Migration Wizard" (en)
- **Colors**: Success green for completed steps, primary for active
- **Positioning**: Universal compatibility, speed, and accuracy

### VAT Return Generator
- **Icon**: DocumentTextIcon
- **Label**: "Генериранје ДДВ враќање" (mk), "Generate VAT Return" (en)
- **Colors**: Primary scheme with success indicators
- **Positioning**: Automated compliance, accuracy, time-saving

---

## Voice & Tone

### Brand Voice
- **Professional**: Expert knowledge, reliable solutions
- **Local**: Deep understanding of Macedonian business needs
- **Innovative**: Cutting-edge features, modern approach
- **Supportive**: Helpful guidance, clear documentation

### Tone Guidelines
- Use active voice when possible
- Be concise but thorough
- Avoid technical jargon in user-facing content
- Maintain respectful, professional tone in all languages
- Show confidence in capabilities without arrogance

---

## Brand Applications

### Email Templates
- Footer: "Powered by Facturino" 
- Macedonian: "Овозможено од Facturino"
- Maintain consistent header branding across all email types

### PDF Documents
- Use Facturino branding in headers/footers
- Include logo in appropriate size and placement
- Maintain professional document formatting

### Web Application
- Theme class: `theme-facturino` (updated from `theme-invoiceshelf`)
- Page titles include "Facturino" brand name
- Consistent icon usage from Heroicons library

---

## Compliance & Legal

### Macedonia-Specific Branding
- All VAT references use standard 18% and 5% rates
- Business registration terminology uses Macedonian standards (EMBS, ЕДБ)
- Currency formatting follows local conventions
- Date formats comply with local business practices

### Digital Signature Integration
- QES certificate branding maintains Facturino identity
- Compliance documentation includes proper attribution
- Technical integrations preserve brand consistency

---

## Implementation Checklist

### Completed Branding Updates ✅
- [x] Main Blade layouts (`app.blade.php`, PDF templates)
- [x] Vue logo components (`MainLogo.vue`, loaders, login pages)
- [x] Email templates (test, payment, invoice, estimate)
- [x] Language files (English, Macedonian notifications)
- [x] Navigation components and dropdowns
- [x] Dashboard settings and widgets

### Brand Consistency Requirements
- All new components must use `MainLogo.vue` component
- All new text must use i18n keys, never hard-coded strings  
- All new features must include mk/sq translations
- Color usage must reference CSS custom properties
- Typography must use Poppins font family

---

## Quality Assurance

### Brand Review Process
1. **Design Review**: Ensure visual consistency with brand guidelines
2. **Content Review**: Verify tone, voice, and messaging alignment
3. **Localization Review**: Confirm proper translation and cultural adaptation
4. **Technical Review**: Validate implementation follows brand standards

### Testing Requirements
- Cross-browser logo rendering validation
- Multi-language UI testing (mk/sq/en)
- Color contrast accessibility verification
- Typography rendering across devices
- Brand consistency in email previews

---

## Contact & Governance

### Brand Stewardship
- All brand modifications require documentation update
- Logo changes require assets update in multiple formats
- Color scheme changes require CSS variable updates
- New features require brand guidelines review

### Future Considerations
- Mobile app branding alignment
- Print material specifications
- Social media brand assets
- Partnership co-branding guidelines

---

**Document Version**: 1.0  
**Last Updated**: 2025-07-26  
**Next Review**: Quarterly or upon major releases

