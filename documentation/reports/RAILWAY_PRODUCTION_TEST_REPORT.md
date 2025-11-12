# Railway Production Testing Report - Migration Wizard UI
**Date:** 2025-11-12
**Project:** Facturino v1 - Migration Wizard UI Enhancements
**Test Environment:** https://www.facturino.mk/admin/imports/wizard

---

## Executive Summary

### Current Deployment Status: UNAVAILABLE (502 ERROR)

The Railway production deployment at `https://www.facturino.mk` is currently returning a **502 Bad Gateway** error, indicating that the application server is not responding. Testing of the Migration Wizard UI cannot proceed until the deployment issue is resolved.

---

## Deployment Status Check

### HTTP Status Verification
```bash
$ curl -I https://www.facturino.mk/admin/imports/wizard
HTTP/2 502
content-type: application/json
server: railway-edge
x-railway-edge: railway/europe-west4-drams3a
x-railway-fallback: true
x-railway-request-id: u3bxaaSPROqw5SUcss7a6g
date: Wed, 12 Nov 2025 20:26:13 GMT
```

### Error Analysis
- **Status Code:** 502 Bad Gateway
- **Server:** railway-edge (Railway's edge server is responding)
- **Issue:** Backend application is not responding to Railway's edge servers
- **Region:** europe-west4

### Possible Causes
1. Application container failed to start
2. Health check endpoint failing
3. Database connection issues (PostgreSQL)
4. Memory/resource limits exceeded
5. Build process failed
6. Environment variables misconfigured
7. Port binding issues

---

## Expected UI Features (Based on Code Review)

The following features have been implemented in the codebase and should be verified once deployment is restored:

### 1. Help Guide Panel Component
**File:** `/resources/scripts/admin/views/imports/components/HelpGuidePanel.vue`

**Features:**
- Toggle visibility from header button
- Step-specific contextual help (adapts to current wizard step)
- Visual walkthroughs with step-by-step instructions
- Common pitfalls warnings (yellow panels)
- Pro tips sections (green panels)
- Quick links to documentation
- Video tutorial links
- FAQ and support contact links

**Step-Specific Content:**
- **Step 1 (Upload):** Upload instructions, file format tips, encoding warnings
- **Step 2 (Mapping):** Field mapping examples, auto-detect guidance
- **Step 3 (Validation):** Validation error explanations, common issues
- **Step 4 (Commit):** Import summary review, final warnings

### 2. QuickStart Panel Component
**File:** `/resources/scripts/admin/views/imports/components/QuickStartPanel.vue`

**Features:**
- Appears automatically on first visit (localStorage check)
- Gradient header with rocket icon
- 4-step visual journey diagram with arrows
- Prerequisites checklist with checkmarks
- Time estimate (5-10 minutes)
- Two CTA buttons:
  - "Start Interactive Tour" (primary)
  - "Skip Tour and Get Started" (secondary)
- First-time user banner with tips
- Video tutorial link with play button
- Dismissible (stores in localStorage)

**Prerequisites Checklist:**
1. Prepare data file (CSV, Excel, or XML)
2. Backup existing data
3. Ensure proper formatting
4. Set aside 5-10 minutes

### 3. Interactive Tour Component
**File:** `/resources/scripts/admin/views/imports/components/InteractiveTour.vue`

**Features:**
- Full-screen overlay with spotlight effect
- Highlights specific UI elements with pulsing border
- Smart tooltip positioning (auto-adjusts to viewport)
- Animated transitions (fade in/out)
- Progress indicator dots
- Step counter (e.g., "Step 3 of 5")
- Navigation buttons:
  - Previous (if not on first step)
  - Next (if not on last step)
  - Finish (on last step)
- Close button (X) to exit tour
- Completion tracking (localStorage)
- Step-specific tour content per wizard step

**Tour Steps by Wizard Step:**

**Wizard Step 1 (Upload):**
1. Welcome to Migration Wizard
2. Upload area explanation
3. Template download section
4. Tips section overview
5. Progress sidebar guide

**Wizard Step 2 (Mapping):**
1. Field mapping table guide
2. Auto-detect button explanation

**Wizard Step 3 (Validation):**
1. Validation results panel
2. Error list details

**Wizard Step 4 (Commit):**
1. Commit summary review
2. Final import button

### 4. Help Tooltip Component
**File:** `/resources/scripts/admin/views/imports/components/HelpTooltip.vue`

**Features:**
- Question mark icon (customizable)
- Hover or click trigger modes
- Smart positioning (top/bottom/left/right with auto-adjust)
- Dark theme tooltip with arrow
- Optional title and content
- Optional external link with icon
- Optional close button
- Smooth fade animations
- Teleports to body (avoids z-index issues)
- Click-outside to close

**Customization Options:**
- Icon type (default: QuestionMarkCircleIcon)
- Icon size (sm/md/lg)
- Icon color class
- Placement (top/bottom/left/right)
- Trigger type (hover/click)

### 5. Import Wizard Main Component Updates
**File:** `/resources/scripts/admin/views/imports/ImportWizard.vue`

**Features Added:**
- Help Guide toggle button in header
- Start Tour button in header
- Conditional layout grid (2/10 or 3/9 split based on help panel visibility)
- QuickStart panel integration
- Interactive tour activation
- Tour completion tracking
- Help guide state management

---

## Translation Keys

All UI text is internationalized. The following translation keys have been added to support the new features:

**File:** `/resources/scripts/admin/lang/help-translations.json`

### Categories:
1. **Navigation & Controls** (15 keys)
   - help_guide, show_help, hide_help, start_tour, finish_tour, etc.

2. **QuickStart Panel** (18 keys)
   - quick_start_guide, quickstart_goal, prerequisites_checklist, etc.

3. **Help Guide Content** (40+ keys)
   - Step-specific help for all 4 wizard steps
   - Common pitfalls, pro tips, validation errors

4. **Interactive Tour** (25 keys)
   - Tour welcome, step titles, action prompts
   - Content for all tour steps

5. **General UI** (8 keys)
   - Video tutorials, quick links, learn more, etc.

**Total Translation Keys:** 150+ new keys added

---

## Testing Checklist

Once the Railway deployment is restored, perform the following tests:

### Phase 1: Deployment Verification
- [ ] Application loads without 502 error
- [ ] HTTPS certificate valid
- [ ] Static assets loading (CSS, JS)
- [ ] Database connectivity working
- [ ] Authentication system functional

### Phase 2: UI Component Visibility
- [ ] Navigate to `/admin/imports/wizard`
- [ ] Verify wizard page loads
- [ ] Check header buttons present:
  - [ ] "Show Help" / "Hide Help" button
  - [ ] "Start Tour" button
  - [ ] "Cancel" button (if import in progress)
- [ ] Verify progress sidebar visible
- [ ] Check main content area renders

### Phase 3: QuickStart Panel
- [ ] **First Visit Behavior:**
  - [ ] Clear localStorage: `localStorage.removeItem('migration-wizard-tour-completed')`
  - [ ] Clear localStorage: `localStorage.removeItem('migration-wizard-tour-skipped')`
  - [ ] Reload page
  - [ ] QuickStart panel should appear automatically
  - [ ] Panel has gradient header with rocket icon
  - [ ] 4-step visual journey displays correctly
  - [ ] Prerequisites checklist items visible
  - [ ] "Start Interactive Tour" button present
  - [ ] "Skip Tour and Get Started" button present
  - [ ] First-time banner displays with yellow background
  - [ ] Video tutorial link present

- [ ] **QuickStart Actions:**
  - [ ] Click "Start Interactive Tour" - tour should launch
  - [ ] Click "Skip Tour and Get Started" - panel should close
  - [ ] Verify `migration-wizard-tour-skipped` set in localStorage
  - [ ] Reload page - QuickStart should NOT appear

- [ ] **Close Behavior:**
  - [ ] QuickStart panel has X close button (if not first time)
  - [ ] Clicking X closes panel
  - [ ] Panel stays closed on page reload

### Phase 4: Help Guide Panel
- [ ] **Toggle Functionality:**
  - [ ] Click "Show Help" button in header
  - [ ] Help Guide panel appears on right side
  - [ ] Button text changes to "Hide Help"
  - [ ] Main content area narrows (2/10 split vs 3/9)
  - [ ] Click "Hide Help" - panel disappears
  - [ ] Main content area expands back

- [ ] **Help Content - Step 1 (Upload):**
  - [ ] Navigate to Step 1
  - [ ] Show Help Guide
  - [ ] Verify "Step 1: Upload Your File" title
  - [ ] Blue panel with upload icon visible
  - [ ] 3 step instructions listed
  - [ ] Yellow "Common Pitfalls" section present
  - [ ] Green "Pro Tips" section present
  - [ ] Check all translations render (no missing keys)

- [ ] **Help Content - Step 2 (Mapping):**
  - [ ] Navigate to Step 2 (upload a file first if needed)
  - [ ] Verify "Step 2: Map Your Fields" title
  - [ ] Mapping instructions visible
  - [ ] Example mappings displayed
  - [ ] Purple panel with examples

- [ ] **Help Content - Step 3 (Validation):**
  - [ ] Navigate to Step 3
  - [ ] Verify "Step 3: Validate Your Data" title
  - [ ] Validation instructions present
  - [ ] Red panel with common validation errors

- [ ] **Help Content - Step 4 (Commit):**
  - [ ] Navigate to Step 4
  - [ ] Verify "Step 4: Complete Import" title
  - [ ] Commit instructions visible
  - [ ] Yellow "Important" warning panel present

- [ ] **Quick Links:**
  - [ ] Video tutorial link present at bottom
  - [ ] Quick links section displays
  - [ ] "Full Documentation" link present
  - [ ] "FAQ" link present
  - [ ] "Contact Support" link present

### Phase 5: Interactive Tour
- [ ] **Launch Tour:**
  - [ ] Clear localStorage: `localStorage.removeItem('migration-wizard-tour-completed')`
  - [ ] Click "Start Tour" button in header
  - [ ] Full-screen overlay appears (dark background)
  - [ ] First tour step displays
  - [ ] Spotlight effect visible on target element

- [ ] **Tour Step 1 (Welcome):**
  - [ ] Welcome tooltip appears
  - [ ] Header highlights (.import-wizard-header)
  - [ ] Gradient header on tooltip (primary to purple)
  - [ ] Welcome icon (SparklesIcon) visible
  - [ ] Title: "Welcome to the Migration Wizard"
  - [ ] Content text renders correctly
  - [ ] Progress dots at bottom (1st dot expanded)
  - [ ] Step counter shows "Step 1 of 5"
  - [ ] Only "Next" button visible (no Previous)
  - [ ] X button to close tour present

- [ ] **Tour Navigation:**
  - [ ] Click "Next" - advances to step 2
  - [ ] Spotlight moves to new element (.upload-area)
  - [ ] Tooltip repositions automatically
  - [ ] Progress dots update (2nd dot expanded)
  - [ ] Step counter updates to "Step 2 of 5"
  - [ ] Both "Previous" and "Next" buttons visible
  - [ ] Click "Previous" - returns to step 1
  - [ ] Navigate through all 5 steps

- [ ] **Tour Steps Verification:**
  - [ ] Step 2: Upload area highlighted, action hint present
  - [ ] Step 3: Template download section highlighted
  - [ ] Step 4: Tips section highlighted
  - [ ] Step 5: Progress sidebar highlighted

- [ ] **Tour Completion:**
  - [ ] On last step, "Finish Tour" button replaces "Next"
  - [ ] Button has green background
  - [ ] Checkmark icon on button
  - [ ] Click "Finish Tour"
  - [ ] Overlay fades out
  - [ ] Tour closes
  - [ ] Verify `migration-wizard-tour-completed` set in localStorage
  - [ ] Reload page - QuickStart should NOT appear

- [ ] **Tour Early Exit:**
  - [ ] Start tour again
  - [ ] Click X button on any step
  - [ ] Tour closes immediately
  - [ ] Overlay removed
  - [ ] Page returns to normal state

- [ ] **Tour Element Highlighting:**
  - [ ] Each target element has visible border
  - [ ] Border is primary blue color (4px width)
  - [ ] Glow/shadow effect present
  - [ ] Element scrolls into view automatically
  - [ ] Spotlight box shadow creates focus effect

- [ ] **Tour Tooltip Positioning:**
  - [ ] Tooltips position to right of element when space available
  - [ ] Tooltips position to left when right edge would overflow
  - [ ] Tooltips position below when side positioning doesn't work
  - [ ] Arrow always points to highlighted element
  - [ ] No tooltips cut off by viewport edges

### Phase 6: Help Tooltips
- [ ] **Locate Tooltip Icons:**
  - [ ] Find help icon (?) next to upload area
  - [ ] Find help icons next to field labels
  - [ ] Find help icons in tips section

- [ ] **Hover Behavior:**
  - [ ] Hover over help icon
  - [ ] Tooltip appears after ~200ms delay
  - [ ] Dark gray/black tooltip with white text
  - [ ] Arrow points to icon
  - [ ] Tooltip has shadow effect
  - [ ] Move mouse away - tooltip disappears
  - [ ] Hover over tooltip itself - tooltip stays visible

- [ ] **Click Behavior (if any click-triggered tooltips):**
  - [ ] Click help icon
  - [ ] Tooltip appears immediately
  - [ ] Tooltip stays open
  - [ ] Click elsewhere - tooltip closes
  - [ ] Click tooltip content - tooltip stays open
  - [ ] Click X button - tooltip closes

- [ ] **Tooltip Content:**
  - [ ] Title displays (if present)
  - [ ] Content text readable
  - [ ] External links work (if present)
  - [ ] Link opens in new tab
  - [ ] Icon for external link present

- [ ] **Tooltip Positioning:**
  - [ ] Test tooltip near top edge - positions below
  - [ ] Test tooltip near bottom edge - positions above
  - [ ] Test tooltip near left edge - positions right
  - [ ] Test tooltip near right edge - positions left
  - [ ] All tooltips remain within viewport

### Phase 7: Upload Functionality
- [ ] **Drag and Drop:**
  - [ ] Drag CSV file over upload area
  - [ ] Upload area highlights/changes style
  - [ ] Drop file
  - [ ] Upload progress bar appears
  - [ ] File uploads successfully
  - [ ] Success message displays

- [ ] **File Picker:**
  - [ ] Click "Browse Files" button
  - [ ] File picker opens
  - [ ] Select CSV file
  - [ ] File uploads
  - [ ] Progress indicator visible

- [ ] **Template Downloads:**
  - [ ] Locate template download buttons
  - [ ] Click "Download Customers Template"
  - [ ] CSV file downloads
  - [ ] Test other templates (Items, Invoices, etc.)
  - [ ] All templates download successfully

### Phase 8: Translation Verification
- [ ] **Check for Missing Keys:**
  - [ ] Open browser console
  - [ ] Look for Vue i18n warnings about missing keys
  - [ ] Verify all text displays (not raw keys like `imports.tour_welcome_title`)

- [ ] **Spot Check Translations:**
  - [ ] QuickStart panel title renders
  - [ ] Help Guide step titles render
  - [ ] Tour tooltip content renders
  - [ ] Button labels render correctly
  - [ ] No placeholder text visible

### Phase 9: Responsive Design
- [ ] **Desktop (1920x1080):**
  - [ ] All panels visible
  - [ ] No horizontal scroll
  - [ ] Layout looks balanced
  - [ ] Help panel width appropriate

- [ ] **Laptop (1366x768):**
  - [ ] Components scale appropriately
  - [ ] Text remains readable
  - [ ] No overlapping elements

- [ ] **Tablet (iPad - 768x1024):**
  - [ ] QuickStart panel adapts
  - [ ] Help Guide may stack or hide
  - [ ] Tour tooltips position correctly
  - [ ] Touch interactions work

- [ ] **Mobile (375x667):**
  - [ ] Wizard accessible (or disabled)
  - [ ] Warning message if not mobile-optimized
  - [ ] Core functionality preserved

### Phase 10: Browser Compatibility
- [ ] **Chrome (latest):**
  - [ ] All features work
  - [ ] Animations smooth
  - [ ] No console errors

- [ ] **Firefox (latest):**
  - [ ] All features work
  - [ ] Spotlight effect renders
  - [ ] Tooltips position correctly

- [ ] **Safari (latest):**
  - [ ] All features work
  - [ ] Gradient backgrounds display
  - [ ] localStorage works

- [ ] **Edge (latest):**
  - [ ] All features work
  - [ ] No compatibility warnings

### Phase 11: Performance
- [ ] **Page Load:**
  - [ ] Initial load time < 3 seconds
  - [ ] Assets load progressively
  - [ ] No layout shift during load

- [ ] **Interactions:**
  - [ ] Help panel toggle instant (< 200ms)
  - [ ] Tour transitions smooth (60fps)
  - [ ] Tooltips appear without lag
  - [ ] No janky animations

- [ ] **Memory:**
  - [ ] No memory leaks on long sessions
  - [ ] Opening/closing panels doesn't accumulate DOM
  - [ ] Tour cleanup removes event listeners

### Phase 12: Error Handling
- [ ] **Missing Elements:**
  - [ ] If tour target element doesn't exist, warning in console
  - [ ] Tour skips missing step gracefully
  - [ ] No JavaScript errors break page

- [ ] **Network Issues:**
  - [ ] If help content fails to load, fallback message
  - [ ] User can still proceed with wizard

- [ ] **LocalStorage Issues:**
  - [ ] If localStorage unavailable, app still works
  - [ ] Tour/QuickStart may always show (degraded experience)

### Phase 13: Accessibility
- [ ] **Keyboard Navigation:**
  - [ ] Tab through help buttons
  - [ ] Enter/Space activate buttons
  - [ ] Tour can be navigated with keyboard
  - [ ] Help tooltips accessible via focus

- [ ] **Screen Readers:**
  - [ ] Help buttons have aria-labels
  - [ ] Tour steps announce changes
  - [ ] Tooltip content readable by screen readers

- [ ] **Color Contrast:**
  - [ ] Text meets WCAG AA standards
  - [ ] Icons visible to colorblind users
  - [ ] Focus indicators clearly visible

---

## Screenshots to Capture

Once deployment is working, capture the following screenshots:

### 1. QuickStart Panel
- **Filename:** `quickstart-panel-first-visit.png`
- **Description:** Full QuickStart panel on first visit with all sections visible
- **Browser:** Chrome at 1920x1080

### 2. Help Guide Panel - Step 1
- **Filename:** `help-guide-step1-upload.png`
- **Description:** Help Guide panel open on Step 1 showing upload instructions
- **Browser:** Chrome at 1920x1080

### 3. Interactive Tour - Welcome
- **Filename:** `interactive-tour-welcome.png`
- **Description:** First tour step with spotlight and tooltip
- **Browser:** Chrome at 1920x1080

### 4. Interactive Tour - Upload Area
- **Filename:** `interactive-tour-upload.png`
- **Description:** Tour highlighting upload area with action hint
- **Browser:** Chrome at 1920x1080

### 5. Help Tooltip Hover
- **Filename:** `help-tooltip-hover.png`
- **Description:** Help tooltip displayed on hover
- **Browser:** Chrome at 1920x1080

### 6. Upload Area with File
- **Filename:** `upload-area-file-selected.png`
- **Description:** Upload area after file selection
- **Browser:** Chrome at 1920x1080

### 7. Progress Sidebar
- **Filename:** `progress-sidebar-step2.png`
- **Description:** Progress sidebar showing Step 2 active
- **Browser:** Chrome at 1920x1080

### 8. Mobile View
- **Filename:** `mobile-view-quickstart.png`
- **Description:** QuickStart panel on mobile device
- **Browser:** Chrome DevTools mobile emulation (iPhone 12)

---

## Known Issues & Bugs to Watch For

Based on code review, these are potential issues to look for:

### 1. Tour Target Elements Not Found
**Symptom:** Console warning: "Tour target not found: .upload-area"
**Cause:** CSS class selector doesn't match actual DOM
**Test:** Check if all target selectors in InteractiveTour.vue match actual elements

### 2. Tooltip Positioning Edge Cases
**Symptom:** Tooltip appears off-screen or cut off
**Cause:** calculatePosition() logic doesn't account for small viewports
**Test:** Try tour on 1024x768 screen

### 3. LocalStorage Conflicts
**Symptom:** Tour/QuickStart behavior inconsistent
**Cause:** Multiple keys or competing state
**Keys to check:**
- `migration-wizard-tour-completed`
- `migration-wizard-tour-skipped`

### 4. Translation Keys Missing
**Symptom:** Raw key displayed like `imports.tour_welcome_title`
**Cause:** help-translations.json not merged into main lang files
**Test:** Check if lang/en.json includes imports.* keys

### 5. Help Guide Content Not Updating
**Symptom:** Help content doesn't change when wizard step changes
**Cause:** currentStep prop not reactive
**Test:** Navigate through wizard steps with Help Guide open

### 6. Tour Overlay Z-Index Issues
**Symptom:** Tour overlay behind other modals
**Cause:** z-50 not high enough
**Test:** Try tour with open dropdowns or modals

### 7. Video Tutorial Links Broken
**Symptom:** Video links go to example.com placeholder
**Cause:** Hardcoded placeholder URLs in components
**Code locations:**
- QuickStartPanel.vue line 231
- HelpGuidePanel.vue line 301

### 8. Help Panel Layout Shift
**Symptom:** Page jumps when toggling help panel
**Cause:** Grid column change causes reflow
**Test:** Toggle help rapidly, check for layout jump

---

## Performance Observations to Record

Monitor and document:

### Lighthouse Scores
- Performance: __/100
- Accessibility: __/100
- Best Practices: __/100
- SEO: __/100

### Load Times
- Initial page load: __ ms
- Time to interactive: __ ms
- First contentful paint: __ ms
- Largest contentful paint: __ ms

### Bundle Sizes
- New components total size: __ KB
- Help translations JSON: __ KB
- Increased main bundle by: __ %

### Animation Performance
- Tour spotlight transition FPS: __
- Help panel toggle time: __ ms
- Tooltip fade animation FPS: __

---

## Recommendations for Deployment Recovery

### Immediate Actions
1. **Check Railway Logs:**
   ```bash
   railway logs --project facturino --environment production
   ```

2. **Verify Build Status:**
   - Check Railway dashboard for build failures
   - Review build logs for npm/composer errors

3. **Health Check Endpoint:**
   - Ensure app has `/health` or `/api/health` endpoint
   - Configure Railway health check if not set

4. **Environment Variables:**
   - Verify all required ENV vars are set in Railway
   - Check database connection strings
   - Verify APP_URL matches domain

5. **Resource Limits:**
   - Check if container is hitting memory/CPU limits
   - Review Railway usage metrics
   - Scale up if necessary

6. **Database Migrations:**
   - Ensure migrations ran successfully
   - Check if migration blocked startup

### Post-Recovery Actions
1. Run through full testing checklist above
2. Capture all required screenshots
3. Document any bugs discovered
4. Create tickets for issues found
5. Verify analytics/monitoring integrated

---

## Testing Timeline

**Estimated Time:** 4-6 hours for complete testing

### Phase Breakdown:
- **Phase 1-2:** 30 minutes (Deployment & basic UI)
- **Phase 3:** 30 minutes (QuickStart panel)
- **Phase 4:** 45 minutes (Help Guide panel)
- **Phase 5:** 60 minutes (Interactive tour - most complex)
- **Phase 6:** 30 minutes (Help tooltips)
- **Phase 7:** 30 minutes (Upload functionality)
- **Phase 8:** 20 minutes (Translations)
- **Phase 9:** 30 minutes (Responsive design)
- **Phase 10:** 30 minutes (Browser compatibility)
- **Phase 11:** 20 minutes (Performance)
- **Phase 12:** 15 minutes (Error handling)
- **Phase 13:** 30 minutes (Accessibility)

**Total:** ~6 hours for thorough testing

---

## Test Results Summary

**Status:** NOT TESTED - Deployment unavailable

### Pass/Fail Summary
- [ ] QuickStart Panel: PENDING
- [ ] Help Guide Panel: PENDING
- [ ] Interactive Tour: PENDING
- [ ] Help Tooltips: PENDING
- [ ] Upload Functionality: PENDING
- [ ] Translation Keys: PENDING
- [ ] Responsive Design: PENDING
- [ ] Browser Compatibility: PENDING
- [ ] Performance: PENDING
- [ ] Accessibility: PENDING

### Bugs Found
(To be filled after testing)

### Performance Issues
(To be filled after testing)

### Accessibility Issues
(To be filled after testing)

---

## Next Steps

1. **Resolve 502 Error:**
   - Debug Railway deployment
   - Review application logs
   - Fix configuration issues
   - Verify database connectivity

2. **Once Deployed:**
   - Execute full testing checklist
   - Capture screenshots
   - Document all findings
   - Create bug tickets if needed

3. **Optimization (if needed):**
   - Fix broken video tutorial links
   - Adjust tour element selectors
   - Optimize bundle size
   - Improve accessibility

4. **Final Approval:**
   - Demo to stakeholders
   - Get sign-off on UI/UX
   - Deploy to production (if not already)
   - Monitor for errors

---

## Contact & Support

**Developer:** Claude (AI Assistant)
**Project Lead:** (To be filled)
**Railway Project:** facturino
**Repository:** (To be filled)

**For Deployment Issues:**
- Check Railway dashboard
- Review application logs
- Contact Railway support if infrastructure issue

**For UI/Feature Issues:**
- Open GitHub issue
- Reference this test report
- Include screenshots and browser info

---

## Appendix A: Component File Locations

```
/Users/tamsar/Downloads/mkaccounting/
├── resources/scripts/admin/views/imports/
│   ├── ImportWizard.vue (main wizard component)
│   └── components/
│       ├── QuickStartPanel.vue (first-time user panel)
│       ├── HelpGuidePanel.vue (step-specific help)
│       ├── InteractiveTour.vue (guided tour)
│       ├── HelpTooltip.vue (contextual tooltips)
│       ├── Step1Upload.vue (upload step)
│       ├── Step2Mapping.vue (field mapping)
│       ├── Step3Validation.vue (validation)
│       └── Step4Commit.vue (final import)
└── resources/scripts/admin/lang/
    └── help-translations.json (150+ translation keys)
```

## Appendix B: LocalStorage Keys

```javascript
// Tour completion tracking
localStorage.getItem('migration-wizard-tour-completed') // 'true' if completed
localStorage.getItem('migration-wizard-tour-skipped')   // 'true' if skipped

// To reset for testing:
localStorage.removeItem('migration-wizard-tour-completed')
localStorage.removeItem('migration-wizard-tour-skipped')
```

## Appendix C: Browser Console Commands for Testing

```javascript
// Force show QuickStart panel
localStorage.removeItem('migration-wizard-tour-completed');
localStorage.removeItem('migration-wizard-tour-skipped');
location.reload();

// Force hide QuickStart panel
localStorage.setItem('migration-wizard-tour-completed', 'true');
location.reload();

// Check current tour state
console.log('Tour completed:', localStorage.getItem('migration-wizard-tour-completed'));
console.log('Tour skipped:', localStorage.getItem('migration-wizard-tour-skipped'));
```

---

**End of Test Report**
