# Facturino Documentation Style Guide
**Version:** 1.0
**Date:** November 2025
**For:** All documentation writers and contributors

---

## Table of Contents

1. [General Principles](#general-principles)
2. [Tone and Voice](#tone-and-voice)
3. [Formatting Standards](#formatting-standards)
4. [Terminology](#terminology)
5. [Screenshots and Images](#screenshots-and-images)
6. [Code Examples](#code-examples)
7. [UI Element References](#ui-element-references)
8. [Language and Localization](#language-and-localization)
9. [Document Structure](#document-structure)
10. [Review Checklist](#review-checklist)

---

## General Principles

### Write for Your Audience

**User Manual (End Users):**
- Assume basic computer literacy
- Explain accounting concepts simply
- Use business terminology, not technical jargon
- Provide step-by-step instructions
- Include real-world examples
- Focus on "how to" accomplish tasks

**Administrator Manual (IT/Admins):**
- Assume technical competence
- Use precise technical terminology
- Provide command-line examples
- Include troubleshooting steps
- Reference configuration files and code
- Focus on "how to configure and maintain"

### Be Clear and Concise

✅ **Good:** "Click the Save button to save your changes."
❌ **Bad:** "In order to ensure that your modifications are persisted to the database, you should click on the button labeled Save."

✅ **Good:** "The invoice total is calculated automatically."
❌ **Bad:** "The system will perform an automatic calculation of the total amount for the invoice."

### Be Action-Oriented

Start procedures with verbs:
- ✅ "Create a new customer"
- ❌ "Customer creation"

### Be Accurate

- Test every procedure before documenting
- Use actual UI text (button labels, menu items)
- Verify technical details (commands, configurations)
- Keep screenshots up-to-date with current UI

---

## Tone and Voice

### Professional but Friendly

✅ **Good:** "Let's create your first invoice together."
❌ **Bad:** "You must now proceed to create an invoice."
❌ **Bad:** "Hey buddy! Let's make an invoice! 🎉"

### Encouraging

✅ **Good:** "Don't worry if this seems complex at first. Once you've done it a few times, it becomes second nature."
❌ **Bad:** "This is complicated and you might make mistakes."

### Respectful

✅ **Good:** "You may want to consult with your accountant about VAT rates."
❌ **Bad:** "Obviously, you should know your VAT rates."

### Conversational but Professional

Use contractions naturally:
- ✅ "You're ready to send the invoice"
- ✅ "Don't forget to save"
- ✅ "It's recommended to back up daily"

Avoid:
- Slang or colloquialisms
- Humor that might not translate across cultures
- Cultural references that may not be universal
- Sarcasm or negative tone

---

## Formatting Standards

### Headings

Use hierarchical heading structure:

```markdown
# Part/Chapter Title (H1)
## Section Title (H2)
### Subsection Title (H3)
#### Sub-subsection Title (H4)
```

**Heading Capitalization:**
Use title case for H1 and H2:
- ✅ "Creating Your First Invoice"
- ❌ "Creating your first invoice"

Use sentence case for H3 and below:
- ✅ "Understanding invoice statuses"
- ❌ "Understanding Invoice Statuses"

### Text Formatting

**Bold** - UI elements, buttons, menu items, important terms
- Example: "Click the **Save** button"
- Example: "Navigate to **Settings** → **Company Profile**"

*Italic* - Emphasis, field names, placeholder text
- Example: "Enter your *company name*"
- Example: "This field is *optional*"

`Code format` - Technical values, file paths, commands, code
- Example: "Set `QUEUE_CONNECTION=redis` in your `.env` file"
- Example: "Run `php artisan migrate`"

### Lists

**Numbered lists** - Sequential steps or ordered items:
```markdown
1. Open the invoice form
2. Select a customer
3. Add line items
4. Click Save
```

**Bulleted lists** - Non-sequential items or options:
```markdown
- Invoices
- Estimates
- Credit notes
- Payments
```

**Nested lists** - Use 4 spaces for indentation:
```markdown
- Customer management
    - Adding customers
    - Editing customers
    - Customer statements
- Invoice management
    - Creating invoices
    - Sending invoices
```

### Tables

Use tables for structured data:

```markdown
| Feature | Free Plan | Pro Plan | Enterprise |
|---------|-----------|----------|------------|
| Invoices | 50/month | Unlimited | Unlimited |
| Users | 1 | 5 | Unlimited |
| Support | Email | Email + Chat | Priority |
```

**Table Guidelines:**
- Keep tables simple (max 5-6 columns)
- Use headers for every column
- Left-align text, right-align numbers
- Use consistent column widths

### Callout Boxes

Use icons to indicate different types of information:

**Tips:**
```markdown
💡 **Tip:** Use keyboard shortcuts to work faster.
```

**Warnings:**
```markdown
⚠️ **Warning:** Deleting a customer cannot be undone.
```

**Success/Good:**
```markdown
✓ **Success:** Your invoice was sent successfully!
```

**Errors/Bad:**
```markdown
❌ **Error:** Invalid email address format.
```

**Notes:**
```markdown
📝 **Note:** This feature is available in Pro plans only.
```

**Security:**
```markdown
🔒 **Security:** Never share your certificate password.
```

**Money/Pricing:**
```markdown
💰 **Paid Feature:** This requires a Pro subscription.
```

### Links

**Internal links** (within documentation):
```markdown
See [Customer Management](chapter-02-customers.md) for details.
```

**External links:**
```markdown
For more information, visit the [Macedonian Tax Authority](https://ujp.gov.mk).
```

**Anchor links** (same page):
```markdown
See [Common Errors](#common-errors) below.
```

### Code Blocks

Use triple backticks with language identifier:

````markdown
```bash
php artisan migrate
```

```php
$invoice->status = 'paid';
$invoice->save();
```

```sql
SELECT * FROM invoices WHERE status = 'overdue';
```
````

**Guidelines:**
- Always specify the language for syntax highlighting
- Include comments for complex code
- Keep examples short and focused
- Use realistic, relevant examples

---

## Terminology

### Standard Terms

Use consistent terminology throughout all documentation:

| ✅ Use This | ❌ Not This | Notes |
|-------------|-------------|-------|
| **Invoice** | Bill, Receipt | Customer-facing document |
| **Bill** | Vendor invoice, Purchase invoice | Supplier-facing document |
| **Customer** | Client, Buyer | Person/company you sell to |
| **Supplier** | Vendor | Person/company you buy from |
| **Item** | Product, Service | What you sell |
| **Line item** | Invoice line | Individual row on invoice |
| **Tax ID** | VAT number, ЕДБ | Macedonian tax registration |
| **E-Faktura** | E-invoice, Electronic invoice | Macedonian e-invoicing |
| **Certificate** | QES cert, Digital signature | For signing e-invoices |
| **Dashboard** | Home, Main page | Landing page after login |
| **Click** | Press, Hit, Tap | Mouse action |
| **Select** | Choose, Pick | Dropdown or checkbox |
| **Enter** | Type, Input | Keyboard input |

### Macedonian Terms

When referencing Macedonian-specific concepts:

| English | Macedonian Cyrillic | Abbreviation | Usage |
|---------|---------------------|--------------|-------|
| Tax ID | Единствен даночен број | ЕДБ | Company tax ID |
| Personal ID | Единствен матичен број | ЕМБГ | Individual tax ID |
| VAT | Данок на додадена вредност | ДДВ | Sales tax |
| E-Faktura | Електронска фактура | E-Faktura | Electronic invoice |
| Qualified Electronic Signature | Квалификуван електронски потпис | КЕП | Digital signature |

**When to use:**
- First mention: "Tax ID (ЕДБ, Единствен даночен број)"
- Subsequent mentions: "Tax ID (ЕДБ)" or just "Tax ID" if clear from context

### UI Element Names

Always use the exact text from the interface:

✅ **Good:** "Click the **+ New Invoice** button"
❌ **Bad:** "Click the 'Create Invoice' button"

**Capitalization:**
Match the UI exactly:
- If button says "Save & Send", write **Save & Send** (not "Save and Send")
- If menu says "E-Faktura", write **E-Faktura** (not "E-faktura" or "eFaktura")

---

## Screenshots and Images

### When to Include Screenshots

**Always include screenshots for:**
- Main feature pages (dashboard, invoice list, customer list)
- Complex forms (invoice creation, settings pages)
- Multi-step processes (setup wizards, import processes)
- Error messages or validation feedback
- Confirmation dialogs
- Anything that's hard to describe in words

**Don't include screenshots for:**
- Simple text fields
- Standard buttons (Save, Cancel)
- Obvious UI elements
- Frequently changing content

### Screenshot Guidelines

**Quality:**
- Minimum resolution: 1920x1080 for desktop, 375x667 for mobile
- PNG format (lossless compression)
- File size: Optimize to <500KB per image
- No blurry or pixelated images

**Content:**
- Use realistic sample data (not "Test User" or "asdf")
- Use Macedonian language for Macedonian features
- Use professional company/customer names
- Hide sensitive information (real tax IDs, emails, etc.)
- Use consistent branding (same logo throughout)

**Annotations:**
- Use red arrows to point to specific elements
- Use red boxes to highlight important areas
- Use numbered circles (❶ ❷ ❸) for step sequences
- Use yellow highlighting sparingly
- Keep annotations clear and not overlapping

**Consistency:**
- Use the same user account throughout
- Use the same sample company
- Use consistent color scheme for annotations
- Use same screenshot dimensions for similar views

### Image File Naming

Format: `module-feature-view.png`

Examples:
- `invoice-create-form.png`
- `customer-list-view.png`
- `settings-company-profile.png`
- `einvoice-certificate-upload.png`
- `dashboard-overview.png`

**Guidelines:**
- All lowercase
- Hyphens, not underscores or spaces
- Descriptive names
- Include view type (list, form, detail, dialog)

### Image Captions

Always include captions:

```markdown
![Invoice creation form](screenshots/invoice-create-form.png)
*Figure 4.1: Invoice creation form with customer and line items*
```

---

## Code Examples

### Example Quality

**Good example:**
```php
// Create a new invoice
$invoice = Invoice::create([
    'customer_id' => $customer->id,
    'invoice_number' => 'FAK-2025-0001',
    'invoice_date' => now(),
    'due_date' => now()->addDays(30),
    'subtotal' => 10000.00,
    'tax' => 1800.00,
    'total' => 11800.00,
]);
```

**Bad example:**
```php
$i = new Invoice();
$i->cust = 1;
$i->num = 1;
// ...
$i->save();
```

### Code Guidelines

- Use meaningful variable names
- Include comments for complex logic
- Show complete, working examples
- Use realistic data
- Follow PSR-12 PHP standards
- Indent with 4 spaces (not tabs)

### Command-Line Examples

Show both the command and expected output:

```bash
$ php artisan migrate

Migrating: 2025_11_01_000001_create_invoices_table
Migrated:  2025_11_01_000001_create_invoices_table (123.45ms)
```

Use `$` to indicate command prompt (don't show full path like `user@server:~/path$`)

---

## UI Element References

### Navigation Paths

Use **→** to show navigation:
```markdown
Navigate to **Settings** → **Company Profile** → **Tax Settings**
```

### Buttons

```markdown
Click the **Save** button
Click **Save & Send**
Click the **+ New Invoice** button
```

### Menu Items

```markdown
Select **Invoices** from the sidebar
Go to **Customers** → **Add Customer**
```

### Form Fields

```markdown
Enter your company name in the *Company Name* field
Select a customer from the **Customer** dropdown
Check the **Send copy to me** checkbox
```

### Status Indicators

```markdown
Status: **SENT** (green indicator)
Status: **OVERDUE** (red indicator)
Status: **DRAFT** (gray indicator)
```

---

## Language and Localization

### English Writing

- Use American English spelling (color, not colour; customize, not customise)
- Use serial comma (Oxford comma): "invoices, bills, and estimates"
- Write in active voice: "The system sends an email" (not "An email is sent by the system")
- Use present tense: "Click Save to save changes" (not "Clicking Save will save changes")

### Macedonian Content

When documenting Macedonian-specific features:

1. **Provide both languages:**
   ```markdown
   Tax ID (ЕДБ, Единствен даночен број)
   ```

2. **Use Cyrillic for official terms:**
   - ✅ "ЕДБ" (not "EDB")
   - ✅ "ДДВ" (not "DDV")

3. **Explain local regulations:**
   ```markdown
   In Macedonia, the standard VAT rate (ДДВ) is 18%, with reduced rates of 5% for specific goods and 0% for exports.
   ```

### Accessibility

- Use descriptive link text (not "click here")
  - ✅ "See the [Invoice Creation Guide](link)"
  - ❌ "Click [here](link) for the guide"

- Provide alt text for all images:
  ```markdown
  ![Invoice list showing filters and search](invoice-list.png)
  ```

- Use heading hierarchy properly (don't skip levels)

---

## Document Structure

### Front Matter

Every document should start with:

```markdown
# Document Title
**Version:** 1.0
**Date:** November 2025
**Audience:** End Users / Administrators
**Last Reviewed:** November 17, 2025

---
```

### Table of Contents

For documents >10 pages:

```markdown
## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Main Features](#main-features)
...
```

### Section Structure

Each major section should have:

1. **Introduction** - What this section covers
2. **Prerequisites** - What user needs before starting
3. **Step-by-step instructions** - Main content
4. **Tips and best practices** - Helpful hints
5. **Common errors** - Troubleshooting
6. **Next steps** - Where to go next

Example:

```markdown
## 4.1 Creating Invoices

### Introduction
This section explains how to create invoices in Facturino...

### Before You Begin
Make sure you have:
- ✓ At least one customer
- ✓ At least one item
...

### Step-by-Step Guide
1. Navigate to Invoices
2. Click + New Invoice
...

### Tips
💡 Use keyboard shortcuts...

### Common Mistakes
❌ Wrong VAT rate...

### Next Steps
Now that you've created an invoice:
- Learn about recurring invoices (Section 4.5)
- Set up payment gateways (Chapter 6)
```

---

## Review Checklist

Before finalizing any documentation:

### Content Review

- [ ] Information is accurate and tested
- [ ] Steps are in logical order
- [ ] All steps are numbered
- [ ] Prerequisites are listed
- [ ] Examples are realistic and helpful
- [ ] Technical terms are explained on first use
- [ ] Links are working and relevant
- [ ] No outdated information

### Style Review

- [ ] Tone is appropriate for audience
- [ ] Terminology is consistent
- [ ] UI element names match the actual interface
- [ ] Formatting is consistent
- [ ] Headings use proper hierarchy
- [ ] Code examples follow standards
- [ ] Screenshots are clear and annotated
- [ ] Callout boxes are used appropriately

### Grammar and Spelling

- [ ] Spell-checked (American English)
- [ ] Grammar-checked
- [ ] No typos
- [ ] Proper capitalization
- [ ] Punctuation is correct

### Accessibility

- [ ] Headings are hierarchical
- [ ] Alt text for all images
- [ ] Links are descriptive
- [ ] Tables have headers
- [ ] Lists are properly formatted

### Completeness

- [ ] All sections outlined are written
- [ ] All screenshots are included
- [ ] All code examples are tested
- [ ] All links are included
- [ ] Version and date are current
- [ ] Table of contents is complete

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Nov 2025 | Documentation Team | Initial style guide created |

---

**Questions about this style guide?**
Contact the documentation team at docs@facturino.mk

---

**End of Style Guide**
