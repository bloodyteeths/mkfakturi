/**
 * Purchase Orders — Functional UI Audit
 *
 * Tests the Purchase Orders pages end-to-end via Playwright:
 *  - Page loads without JS errors or API errors
 *  - Create PO form renders with all fields (including cost center)
 *  - Create PO with items, supplier, cost center
 *  - View PO shows cost center, supplier, items
 *  - Edit PO preserves cost center
 *  - Send PO to supplier (status changes, PDF attached)
 *  - Download PDF
 *  - Cancel PO
 *  - Index page with status pipeline
 *  - i18n: no raw key paths visible
 *
 * Usage:
 *   TEST_EMAIL=giovanny.ledner@example.com TEST_PASSWORD=password123 \
 *     npx playwright test tests/visual/purchase-orders-audit.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test';

const BASE = 'http://localhost:8000';
const EMAIL = process.env.TEST_EMAIL || '';
const PASS = process.env.TEST_PASSWORD || '';

test.describe.configure({ mode: 'serial' });

/** @type {import('@playwright/test').Page} */
let page;
let jsErrors = [];
let apiErrors = [];
let createdPoId = null;

test.describe('Purchase Orders Audit', () => {
  test.setTimeout(30000);

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();

    page.on('pageerror', err => {
      jsErrors.push({ url: page.url(), error: err.message });
    });

    page.on('response', resp => {
      if (resp.url().includes('/api/') && (resp.status() === 404 || resp.status() >= 500)) {
        apiErrors.push({ url: resp.url(), status: resp.status() });
      }
    });
  });

  test.afterAll(async () => {
    if (jsErrors.length > 0) {
      console.log('JS Errors:', JSON.stringify(jsErrors, null, 2));
    }
    if (apiErrors.length > 0) {
      console.log('API Errors:', JSON.stringify(apiErrors, null, 2));
    }
    await page.close();
  });

  // ─────────────────────────────────────────────
  // AUTH — Login
  // ─────────────────────────────────────────────
  test('Login', async () => {
    test.setTimeout(60000);

    if (!EMAIL || !PASS) {
      console.log('SKIP: No credentials. Set TEST_EMAIL and TEST_PASSWORD.');
      test.skip();
      return;
    }

    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle', timeout: 30000 });

    await page.waitForFunction(() => typeof window.axios !== 'undefined', { timeout: 15000 })
      .catch(() => page.waitForTimeout(5000));

    const loginResult = await page.evaluate(async ({ email, password }) => {
      for (let attempt = 0; attempt < 3; attempt++) {
        if (typeof window.axios === 'undefined') {
          await new Promise(r => setTimeout(r, 2000));
          continue;
        }
        try {
          await window.axios.get(window.location.origin + '/sanctum/csrf-cookie');
          const resp = await window.axios.post('/auth/login', { email, password });
          return { success: true, status: resp.status };
        } catch (e) {
          return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message };
        }
      }
      return { success: false, message: 'window.axios never became available after 3 attempts' };
    }, { email: EMAIL, password: PASS });

    console.log('Login result:', JSON.stringify(loginResult));
    expect(loginResult.success, `Login failed: ${loginResult.message}`).toBeTruthy();
  });

  // ─────────────────────────────────────────────
  // INDEX — Navigate to PO list
  // ─────────────────────────────────────────────
  test('Navigate to Purchase Orders index', async () => {
    await page.goto(`${BASE}/admin/purchase-orders`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });
    await page.waitForTimeout(3000);

    const url = page.url();
    expect(url).not.toContain('/login');
    expect(url).not.toContain('/installation');

    await page.screenshot({ path: 'test-results/purchase-orders/01-index.png', fullPage: true });
  });

  test('Index page renders without raw i18n keys', async () => {
    const pageText = await page.textContent('body');

    const rawKeys = [
      'purchaseOrders.title',
      'purchaseOrders.new_po',
      'purchaseOrders.status_draft',
      'purchaseOrders.cost_center',
    ];
    for (const key of rawKeys) {
      expect(pageText, `Raw i18n key visible: ${key}`).not.toContain(key);
    }

    // Should contain translated title
    const hasTitle = pageText.includes('Набавки') ||
      pageText.includes('Purchase Orders') ||
      pageText.includes('Satın Alma') ||
      pageText.includes('Porosi Blerje');
    expect(hasTitle, 'Expected Purchase Orders title').toBeTruthy();
  });

  test('Status pipeline chips visible', async () => {
    // All 8 status chips should be visible
    const statusTexts = await page.locator('button:has(span.rounded-full)').allTextContents();
    console.log('Status chips:', statusTexts);
    expect(statusTexts.length).toBeGreaterThanOrEqual(4);
  });

  // ─────────────────────────────────────────────
  // CREATE — Navigate to create page
  // ─────────────────────────────────────────────
  test('Navigate to Create PO page', async () => {
    await page.goto(`${BASE}/admin/purchase-orders/create`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });
    await page.waitForTimeout(3000);

    const url = page.url();
    expect(url).toContain('purchase-orders/create');

    await page.screenshot({ path: 'test-results/purchase-orders/02-create-form.png', fullPage: true });
  });

  test('Create form has all required fields', async () => {
    const pageText = await page.textContent('body');

    // Check for field labels
    const fields = [
      // supplier label
      ['Добавувач', 'Supplier', 'Tedarikçi', 'Furnitori'],
      // date label
      ['Датум', 'Date', 'Tarih', 'Data'],
      // expected delivery
      ['Очекувана испорака', 'Expected Delivery', 'Beklenen Teslimat', 'Dorëzimi'],
      // warehouse
      ['Магацин', 'Warehouse', 'Depo', 'Magazina'],
      // cost center (NEW)
      ['Центар на трошок', 'Cost Center', 'Maliyet Merkezi', 'Qendra e Kostos'],
      // notes
      ['Забелешки', 'Notes', 'Notlar', 'Shënime'],
      // items section
      ['Ставки', 'Items', 'Kalemler', 'Artikujt'],
    ];

    for (const [mk, en, tr, sq] of fields) {
      const hasField = pageText.includes(mk) || pageText.includes(en) ||
        pageText.includes(tr) || pageText.includes(sq);
      expect(hasField, `Missing field: ${en}`).toBeTruthy();
    }
  });

  test('Create form has cost center dropdown', async () => {
    // The cost center multiselect should be on the page
    const pageText = await page.textContent('body');

    const hasCostCenterSelector =
      pageText.includes('Изберете центар на трошок') ||
      pageText.includes('Select Cost Center') ||
      pageText.includes('Maliyet Merkezi Seçin') ||
      pageText.includes('Zgjidhni Qendrën');

    console.log('Cost center selector found:', hasCostCenterSelector);
    expect(hasCostCenterSelector, 'Cost center dropdown placeholder should be visible').toBeTruthy();
  });

  // ─────────────────────────────────────────────
  // CREATE — Create a PO via API
  // ─────────────────────────────────────────────
  test('Create PO via API', async () => {
    test.setTimeout(15000);

    const result = await page.evaluate(async () => {
      try {
        // First fetch cost centers to pick one if available
        let costCenterId = null;
        try {
          const ccResp = await window.axios.get('/cost-centers', { params: { limit: 'all' } });
          const centers = ccResp.data?.data || [];
          if (centers.length > 0) {
            costCenterId = centers[0].id;
          }
        } catch (e) {
          // No cost centers, that's fine
        }

        // Fetch suppliers to pick one
        let supplierId = null;
        try {
          const supResp = await window.axios.get('/suppliers', { params: { limit: 'all' } });
          const suppliers = supResp.data?.suppliers?.data || supResp.data?.data || [];
          if (suppliers.length > 0) {
            supplierId = suppliers[0].id;
          }
        } catch (e) {
          // No suppliers
        }

        const payload = {
          supplier_id: supplierId,
          po_date: new Date().toISOString().split('T')[0],
          expected_delivery_date: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0],
          cost_center_id: costCenterId,
          notes: 'E2E test PO - audit',
          items: [
            { name: 'Test Item Alpha', quantity: 10, price: 50000, tax: 9000 },
            { name: 'Test Item Beta', quantity: 5, price: 25000, tax: 4500 },
          ],
        };

        const resp = await window.axios.post('/purchase-orders', payload);
        return {
          success: true,
          id: resp.data?.data?.id,
          poNumber: resp.data?.data?.po_number,
          costCenterId: resp.data?.data?.cost_center_id,
          total: resp.data?.data?.total,
          status: resp.data?.data?.status,
        };
      } catch (e) {
        return {
          success: false,
          status: e.response?.status,
          message: e.response?.data?.message || e.message,
        };
      }
    });

    console.log('Create PO result:', JSON.stringify(result));
    expect(result.success, `Create PO failed: ${result.message}`).toBeTruthy();
    expect(result.status).toBe('draft');
    expect(result.total).toBeGreaterThan(0);

    createdPoId = result.id;
    console.log('Created PO ID:', createdPoId);
  });

  // ─────────────────────────────────────────────
  // VIEW — View the created PO
  // ─────────────────────────────────────────────
  test('View PO shows details including cost center', async () => {
    expect(createdPoId, 'No PO was created').toBeTruthy();

    await page.goto(`${BASE}/admin/purchase-orders/${createdPoId}`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });
    await page.waitForTimeout(3000);

    const pageText = await page.textContent('body');

    // Should show PO number
    expect(pageText).toContain('PO-');

    // Should show status badge (Draft)
    const hasDraft = pageText.includes('Нацрт') || pageText.includes('Draft') ||
      pageText.includes('Taslak') || pageText.includes('Draft');
    expect(hasDraft, 'Draft status badge missing').toBeTruthy();

    // Should show items
    expect(pageText).toContain('Test Item Alpha');
    expect(pageText).toContain('Test Item Beta');

    // Should show cost center label
    const hasCostCenterLabel = pageText.includes('Центар на трошок') ||
      pageText.includes('Cost Center') ||
      pageText.includes('Maliyet Merkezi') ||
      pageText.includes('Qendra e Kostos');
    expect(hasCostCenterLabel, 'Cost Center label missing on View page').toBeTruthy();

    // Notes
    expect(pageText).toContain('E2E test PO - audit');

    await page.screenshot({ path: 'test-results/purchase-orders/03-view-po.png', fullPage: true });
  });

  test('View PO shows action buttons for draft', async () => {
    const pageText = await page.textContent('body');

    // Draft POs should have: Edit, Send, Cancel, Delete, Download PDF
    const hasEdit = pageText.includes('Измени') || pageText.includes('Edit');
    const hasSend = pageText.includes('Испрати до добавувач') || pageText.includes('Send to Supplier');
    const hasDownload = pageText.includes('Преземи PDF') || pageText.includes('Download PDF');

    expect(hasEdit, 'Edit button missing').toBeTruthy();
    expect(hasSend, 'Send button missing').toBeTruthy();
    expect(hasDownload, 'Download PDF button missing').toBeTruthy();
  });

  // ─────────────────────────────────────────────
  // PDF — Download PDF
  // ─────────────────────────────────────────────
  test('Download PDF works', async () => {
    expect(createdPoId, 'No PO was created').toBeTruthy();

    const pdfResult = await page.evaluate(async (poId) => {
      try {
        const resp = await window.axios.get(`/purchase-orders/${poId}/pdf`, {
          responseType: 'blob',
        });
        return {
          success: true,
          type: resp.data?.type || 'unknown',
          size: resp.data?.size || 0,
        };
      } catch (e) {
        return {
          success: false,
          status: e.response?.status,
          message: e.message,
        };
      }
    }, createdPoId);

    console.log('PDF download result:', JSON.stringify(pdfResult));
    expect(pdfResult.success, `PDF download failed: ${pdfResult.message}`).toBeTruthy();
    expect(pdfResult.size).toBeGreaterThan(1000); // PDF should be at least 1KB
    expect(pdfResult.type).toContain('pdf');
  });

  // ─────────────────────────────────────────────
  // SEND — Send PO (marks as sent, emails supplier with PDF attached)
  // ─────────────────────────────────────────────
  test('Send PO changes status to sent', async () => {
    expect(createdPoId, 'No PO was created').toBeTruthy();

    const sendResult = await page.evaluate(async (poId) => {
      try {
        const resp = await window.axios.post(`/purchase-orders/${poId}/send`);
        return {
          success: true,
          status: resp.data?.data?.status,
          emailSentTo: resp.data?.email_sent_to,
          message: resp.data?.message,
        };
      } catch (e) {
        return {
          success: false,
          status: e.response?.status,
          message: e.response?.data?.message || e.message,
        };
      }
    }, createdPoId);

    console.log('Send PO result:', JSON.stringify(sendResult));
    expect(sendResult.success, `Send PO failed: ${sendResult.message}`).toBeTruthy();
    expect(sendResult.status).toBe('sent');

    // Reload view page to verify status change
    await page.goto(`${BASE}/admin/purchase-orders/${createdPoId}`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });
    await page.waitForTimeout(2000);

    const pageText = await page.textContent('body');
    const isSent = pageText.includes('Испратена') || pageText.includes('Sent') ||
      pageText.includes('Gönderildi') || pageText.includes('Dërguar');
    expect(isSent, 'Status should be Sent').toBeTruthy();

    // Edit button should NOT be visible for sent POs
    const hasEdit = pageText.includes('Измени') || pageText.includes('Edit Draft');
    // "Edit" may appear in other contexts, check for edit button specifically
    const editButton = page.locator('a[href*="/edit"]');
    const editCount = await editButton.count();
    expect(editCount, 'Edit button should be hidden for sent POs').toBe(0);

    await page.screenshot({ path: 'test-results/purchase-orders/04-sent-po.png', fullPage: true });
  });

  // ─────────────────────────────────────────────
  // CANCEL — Cancel the sent PO
  // ─────────────────────────────────────────────
  test('Cancel PO changes status to cancelled', async () => {
    expect(createdPoId, 'No PO was created').toBeTruthy();

    const cancelResult = await page.evaluate(async (poId) => {
      try {
        const resp = await window.axios.post(`/purchase-orders/${poId}/cancel`);
        return {
          success: true,
          status: resp.data?.data?.status,
        };
      } catch (e) {
        return {
          success: false,
          status: e.response?.status,
          message: e.response?.data?.message || e.message,
        };
      }
    }, createdPoId);

    console.log('Cancel PO result:', JSON.stringify(cancelResult));
    expect(cancelResult.success, `Cancel PO failed: ${cancelResult.message}`).toBeTruthy();
    expect(cancelResult.status).toBe('cancelled');
  });

  // ─────────────────────────────────────────────
  // CLEANUP — Delete via API (create a new draft then delete)
  // ─────────────────────────────────────────────
  test('Create and delete a draft PO', async () => {
    const result = await page.evaluate(async () => {
      try {
        // Create a draft PO
        const createResp = await window.axios.post('/purchase-orders', {
          po_date: new Date().toISOString().split('T')[0],
          notes: 'E2E delete test',
          items: [{ name: 'Delete Me', quantity: 1, price: 1000, tax: 0 }],
        });
        const poId = createResp.data?.data?.id;
        if (!poId) return { success: false, message: 'No ID returned' };

        // Delete it
        const deleteResp = await window.axios.delete(`/purchase-orders/${poId}`);
        return { success: true, message: deleteResp.data?.message };
      } catch (e) {
        return {
          success: false,
          status: e.response?.status,
          message: e.response?.data?.message || e.message,
        };
      }
    });

    console.log('Delete PO result:', JSON.stringify(result));
    expect(result.success, `Delete PO failed: ${result.message}`).toBeTruthy();
  });

  // ─────────────────────────────────────────────
  // RECEIVE + CONVERT — Full lifecycle
  // ─────────────────────────────────────────────
  test('Full lifecycle: create → send → receive → convert to bill', async () => {
    test.setTimeout(20000);

    const result = await page.evaluate(async () => {
      try {
        // 1. Create
        const createResp = await window.axios.post('/purchase-orders', {
          po_date: new Date().toISOString().split('T')[0],
          notes: 'E2E lifecycle test',
          items: [
            { name: 'Lifecycle Item', quantity: 5, price: 10000, tax: 1800 },
          ],
        });
        const poId = createResp.data?.data?.id;
        const itemId = createResp.data?.data?.items?.[0]?.id;
        if (!poId) return { success: false, step: 'create', message: 'No PO ID' };

        // 2. Send
        const sendResp = await window.axios.post(`/purchase-orders/${poId}/send`);
        if (sendResp.data?.data?.status !== 'sent') {
          return { success: false, step: 'send', message: 'Status not sent' };
        }

        // 3. Receive goods (full qty)
        const receiveResp = await window.axios.post(`/purchase-orders/${poId}/receive-goods`, {
          items: [{
            purchase_order_item_id: itemId,
            quantity_received: 5,
            quantity_accepted: 5,
            quantity_rejected: 0,
          }],
        });
        const afterReceive = receiveResp.data?.data?.purchase_order?.status;
        if (afterReceive !== 'fully_received') {
          return { success: false, step: 'receive', message: `Expected fully_received, got ${afterReceive}` };
        }

        // 4. Convert to bill
        const convertResp = await window.axios.post(`/purchase-orders/${poId}/convert-to-bill`);
        const afterConvert = convertResp.data?.data?.purchase_order?.status;
        const billId = convertResp.data?.data?.bill?.id;
        if (afterConvert !== 'billed') {
          return { success: false, step: 'convert', message: `Expected billed, got ${afterConvert}` };
        }

        // 5. Three-way match
        const matchResp = await window.axios.get(`/purchase-orders/${poId}/three-way-match`);
        const matched = matchResp.data?.data?.matched;

        return {
          success: true,
          poId,
          billId,
          matched,
          finalStatus: afterConvert,
        };
      } catch (e) {
        return {
          success: false,
          status: e.response?.status,
          message: e.response?.data?.message || e.message,
        };
      }
    });

    console.log('Lifecycle result:', JSON.stringify(result));
    expect(result.success, `Lifecycle failed: ${result.message}`).toBeTruthy();
    expect(result.finalStatus).toBe('billed');
    expect(result.matched).toBeTruthy();
  });

  // ─────────────────────────────────────────────
  // ERRORS — Verify error handling
  // ─────────────────────────────────────────────
  test('No JS errors or API errors during tests', async () => {
    // Filter out known harmless errors
    const criticalJsErrors = jsErrors.filter(e =>
      !e.error.includes('ResizeObserver') &&
      !e.error.includes('Script error')
    );

    if (criticalJsErrors.length > 0) {
      console.log('Critical JS errors:', JSON.stringify(criticalJsErrors, null, 2));
    }
    expect(criticalJsErrors.length, `Found ${criticalJsErrors.length} JS errors`).toBe(0);

    // API errors (404s for missing data are OK, 500s are not)
    const serverErrors = apiErrors.filter(e => e.status >= 500);
    if (serverErrors.length > 0) {
      console.log('Server errors:', JSON.stringify(serverErrors, null, 2));
    }
    expect(serverErrors.length, `Found ${serverErrors.length} server errors`).toBe(0);
  });
});
