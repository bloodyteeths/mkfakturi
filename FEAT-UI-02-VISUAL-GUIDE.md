# Partner Console UI - Visual Design Guide

## Layout Overview

```
┌─────────────────────────────────────────────────────────────────┐
│  Partner Console                                    [Switch]     │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  📊 Companies I Manage                               🔵 5        │
│  ─────────────────────────────────────────────────────────────  │
│                                                                   │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐                │
│  │ 🏢         │  │ 🏢         │  │ 🏢         │                │
│  │ Company A  │  │ Company B  │  │ Company C  │                │
│  │ 15% comm.  │  │ 12% comm.  │  │ 20% comm.  │                │
│  │            │  │            │  │            │                │
│  │ [Primary]  │  │            │  │            │                │
│  │            │  │            │  │            │                │
│  │ 8 perms    │  │ 5 perms    │  │ 12 perms   │                │
│  │ [Manage]   │  │ [Manage]   │  │ [Manage]   │                │
│  └────────────┘  └────────────┘  └────────────┘                │
│                                                                   │
│  📈 Companies I Referred                             🟠 12       │
│  ─────────────────────────────────────────────────────────────  │
│                                                                   │
│  ┌────────────┐  ┌────────────┐                                 │
│  │ 🏢         │  │ 🏢         │                                 │
│  │ Company X  │  │ Company Y  │                                 │
│  │ Referral   │  │ Referral   │                                 │
│  │            │  │            │                                 │
│  │ $500 comm. │  │ $350 comm. │                                 │
│  │ [Active]   │  │ [Active]   │                                 │
│  │            │  │            │                                 │
│  │[View Comm.]│  │[View Comm.]│                                 │
│  └────────────┘  └────────────┘                                 │
│                                                                   │
│  ⏰ Pending Invitations                              🔴 2        │
│  ─────────────────────────────────────────────────────────────  │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ 🏢 Company Z                                             │    │
│  │    Invited by John Doe                                   │    │
│  │                                                           │    │
│  │    Invited: Nov 15, 2025                                 │    │
│  │    Expires: Nov 22, 2025  ⚠️ EXPIRING SOON!             │    │
│  │                                                           │    │
│  │    Permissions: [view_invoices] [create_estimates]       │    │
│  │                                                           │    │
│  │                                           [Accept]        │    │
│  │                                           [Decline]       │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Color Scheme

### Section 1: Companies I Manage (Blue/Green)
```
Primary Color:    #3B82F6 (blue-500)
Border Left:      4px solid blue-500
Gradient:         blue-400 to blue-600
Badge:            Green for "Primary"
Button:           Primary variant (blue)
Hover:            Shadow-lg, slight lift
```

### Section 2: Companies I Referred (Orange/Purple)
```
Primary Color:    #F97316 (orange-500)
Border Left:      4px solid orange-500
Gradient:         orange-400 to orange-600
Badge:            Warning variant (orange/yellow)
Button:           Warning-outline variant
Hover:            Shadow-md
```

### Section 3: Pending Invitations (Yellow/Red)
```
Primary Color:    #EAB308 (yellow-500)
Border Left:      4px solid yellow-500
Gradient:         yellow-400 to yellow-600
Badge:            Danger variant (red) for urgency
Buttons:          Primary (Accept), Danger-outline (Decline)
Urgency Text:     Red-600 for expiring soon
```

## Card Anatomy

### Managed Company Card
```
┌───────────────────────────────────┐
│ ║ [Logo/Icon]  Company Name     ▣ │  ← Header with badge
│ ║              15% commission     │  ← Commission rate (blue)
│ ║                                 │
│ ║ Skopje, Macedonia               │  ← Address (if available)
│ ║                                 │
│ ║ ─────────────────────────────   │  ← Divider
│ ║ 8 permissions      [Manage]     │  ← Footer with action
└───────────────────────────────────┘
 ║← 4px blue border
```

### Referred Company Card
```
┌───────────────────────────────────┐
│ ║ [Logo/Icon]  Company Name       │
│ ║              REFERRAL ONLY      │  ← Label
│ ║                                 │
│ ║ Total Commissions:   $500.00    │  ← Orange text
│ ║ Status:              [Active]   │  ← Green badge
│ ║                                 │
│ ║ ─────────────────────────────   │
│ ║      [View Commissions]         │  ← Full width button
└───────────────────────────────────┘
 ║← 4px orange border
```

### Pending Invitation Card
```
┌─────────────────────────────────────────────────────┐
│ ║ [Icon] Company Name                               │
│ ║        Invited by John Doe                        │
│ ║                                                    │
│ ║ Invited: Nov 15, 2025                             │
│ ║ Expires: Nov 22, 2025  ⚠️ EXPIRING SOON!         │
│ ║                                                    │
│ ║ Permissions Offered:                              │
│ ║ [view_invoices] [create_estimates] [view_reports] │
│ ║                                                    │
│ ║                               [Accept]  [Decline] │
└─────────────────────────────────────────────────────┘
 ║← 4px yellow border
```

## Empty States

### Managed Companies Empty State
```
┌─────────────────────────────────────────────┐
│                                             │
│              🏢 (gray icon)                 │
│                                             │
│         No companies assigned               │
│                                             │
│    You don't have management access         │
│        to any companies yet.                │
│                                             │
└─────────────────────────────────────────────┘
   Gray dashed border, light gray background
```

### Referred Companies Empty State
```
┌─────────────────────────────────────────────┐
│                                             │
│          🏢 (orange icon)                   │
│                                             │
│        No referral tracking yet             │
│                                             │
│   Companies you refer will appear here      │
│      for commission tracking.               │
│                                             │
└─────────────────────────────────────────────┘
   Orange dashed border, orange-50 background
```

### Pending Invitations Empty State
```
┌─────────────────────────────────────────────┐
│                                             │
│          🏢 (yellow icon)                   │
│                                             │
│       No pending invitations                │
│                                             │
│  You don't have any pending company         │
│      invitations at this time.              │
│                                             │
└─────────────────────────────────────────────┘
   Yellow dashed border, yellow-50 background
```

### Global Empty State (All Empty)
```
┌─────────────────────────────────────────────┐
│                                             │
│                                             │
│          🏢 (larger gray icon)              │
│                                             │
│    Welcome to the Partner Console           │
│                                             │
│  You don't have any companies assigned      │
│  yet. Contact your administrator to get     │
│  access to company accounts or wait for     │
│  company invitations.                       │
│                                             │
│                                             │
└─────────────────────────────────────────────┘
```

## Responsive Breakpoints

### Mobile (< 768px)
```
┌────────────────┐
│  Company A     │
└────────────────┘
┌────────────────┐
│  Company B     │
└────────────────┘
┌────────────────┐
│  Company C     │
└────────────────┘

Single column, full width
```

### Tablet (768px - 1279px)
```
┌────────────────┐  ┌────────────────┐
│  Company A     │  │  Company B     │
└────────────────┘  └────────────────┘
┌────────────────┐  ┌────────────────┐
│  Company C     │  │  Company D     │
└────────────────┘  └────────────────┘

Two columns, equal width
```

### Desktop (≥ 1280px)
```
┌──────────┐  ┌──────────┐  ┌──────────┐
│ Company A│  │ Company B│  │ Company C│
└──────────┘  └──────────┘  └──────────┘
┌──────────┐  ┌──────────┐  ┌──────────┐
│ Company D│  │ Company E│  │ Company F│
└──────────┘  └──────────┘  └──────────┘

Three columns, equal width
```

## Interactive States

### Card Hover (Managed Companies)
```
Before Hover:
  - shadow: base
  - transform: none
  - cursor: default

On Hover:
  - shadow: lg
  - transform: translateY(-2px)
  - cursor: pointer
  - transition: all 200ms
```

### Button States
```
Accept Button:
  - Default: Primary blue
  - Hover: Darker blue
  - Loading: Spinner + disabled
  - Disabled: Gray, not clickable

Decline Button:
  - Default: Outlined red
  - Hover: Filled red
  - Loading: Spinner + disabled
  - Disabled: Gray outline, not clickable

Manage Button:
  - Default: Primary blue (small)
  - Hover: Darker blue
  - Active: Pressed state
```

### Urgency Indicators
```
Normal Invitation:
  - Expires: Gray text
  - No special styling

Expiring Soon (≤ 3 days):
  - Expires: RED-600 text, bold
  - Label: "EXPIRING SOON!" (red, uppercase)
  - Icon: ⚠️ warning symbol
```

## Badge Variants

```
Primary (Blue):     Background: blue-100, Text: blue-800
Success (Green):    Background: green-100, Text: green-800
Warning (Orange):   Background: orange-100, Text: orange-800
Danger (Red):       Background: red-100, Text: red-800
Default (Gray):     Background: gray-100, Text: gray-800

Sizes:
  - sm: px-2 py-1, text-xs
  - md: px-3 py-1, text-sm (default)
```

## Typography

```
Section Headers:
  - Font: text-xl (20px)
  - Weight: font-semibold (600)
  - Color: gray-900

Card Titles:
  - Font: text-base (16px)
  - Weight: font-semibold (600)
  - Color: gray-900

Metadata:
  - Font: text-sm (14px)
  - Weight: font-normal (400)
  - Color: gray-600

Labels:
  - Font: text-xs (12px)
  - Weight: font-medium (500)
  - Color: gray-700
  - Transform: uppercase (for "REFERRAL ONLY")
```

## Spacing

```
Section Spacing:
  - Between sections: space-y-8 (2rem)

Card Grid Gap:
  - gap-6 (1.5rem)

Card Padding:
  - p-6 (1.5rem)

Element Spacing:
  - Header to Content: mb-4 (1rem)
  - Content to Footer: pt-3 (0.75rem)
  - Between info items: space-y-2 (0.5rem)

Badge Spacing:
  - Horizontal: space-x-3 (0.75rem)
  - Permission badges: gap-1 (0.25rem)
```

## Animations

```
Card Hover:
  - Duration: 200ms
  - Easing: ease-in-out
  - Properties: shadow, transform

Button Click:
  - Duration: 150ms
  - Easing: ease-in
  - Properties: background, border

Loading Spinner:
  - Animation: spin
  - Duration: 1s
  - Easing: linear
  - Repeat: infinite
```

## Accessibility Features

```
Color Contrast:
  - Text on white: ≥ 4.5:1 ratio (WCAG AA)
  - Badges: ≥ 4.5:1 ratio

Focus States:
  - Buttons: 2px blue outline
  - Cards: 2px blue outline (keyboard navigation)

Screen Reader:
  - Semantic HTML (section, h2)
  - Meaningful labels
  - Hidden count in badges

Keyboard Navigation:
  - Tab order: logical flow
  - Enter/Space: activate buttons
  - Escape: close modals (if any)
```

## Icon Usage

```
Building Icon (Heroicons):
  - Used for all company placeholders
  - Used in empty states
  - Sizes: h-6 w-6 (cards), h-12 w-12 (empty states)

Badge Counts:
  - Positioned next to section headers
  - Right-aligned
  - Color-coded by section
```

## Summary

This visual guide provides a complete reference for the Partner Console UI design. The three-section layout uses distinct color coding (blue for managed, orange for referred, yellow for invitations) to create clear visual separation while maintaining a cohesive design system.

Key design principles:
- **Color-coded sections** for instant recognition
- **Consistent card patterns** with section-specific variations
- **Responsive grid** that adapts to screen size
- **Clear visual hierarchy** from headers to actions
- **Accessible design** meeting WCAG AA standards
- **Smooth interactions** with hover and loading states
