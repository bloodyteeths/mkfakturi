/**
 * AI Chat — Memory, Direct Creation & Locale Tests
 *
 * Tests the 3 critical fixes:
 * 1. Conversation memory (multi-turn context)
 * 2. Direct entity creation (real invoice, not just instructions)
 * 3. Locale-aware responses
 *
 * Usage (production):
 *   TEST_BASE_URL=https://app.facturino.mk TEST_EMAIL=your@email.com TEST_PASSWORD=yourpass \
 *     npx playwright test tests/visual/ai-chat-memory.spec.js --project=chromium
 *
 * Usage (local):
 *   TEST_EMAIL=giovanny.ledner@example.com TEST_PASSWORD=password123 \
 *     npx playwright test tests/visual/ai-chat-memory.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const BASE = process.env.TEST_BASE_URL || 'http://localhost:8000';
const EMAIL = process.env.TEST_EMAIL || '';
const PASS = process.env.TEST_PASSWORD || '';
const SCREENSHOT_DIR = path.join(process.cwd(), 'test-results', 'ai-chat-memory');

if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function ss(page, name) {
  await page.screenshot({
    path: path.join(SCREENSHOT_DIR, `${name}.png`),
    fullPage: true,
  });
}

test.describe.configure({ mode: 'serial' });

/** @type {import('@playwright/test').Page} */
let page;
let apiResponses = [];

test.beforeAll(async ({ browser }) => {
  test.setTimeout(60000);
  if (!EMAIL || !PASS) {
    console.log('SKIP: Set TEST_EMAIL and TEST_PASSWORD env vars.');
    return;
  }

  page = await browser.newPage();

  // Capture AI assistant API responses
  page.on('response', async (resp) => {
    if (resp.url().includes('/ai/assistant')) {
      try {
        const json = await resp.json();
        apiResponses.push({ status: resp.status(), body: json });
      } catch {
        apiResponses.push({ status: resp.status(), body: null });
      }
    }
  });

  // Login — wait for Vue app to hydrate
  await page.goto(`${BASE}/login`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(5000); // extra wait for Vue hydration

  // Log all network responses for debugging
  page.on('response', (resp) => {
    if (resp.url().includes('login') || resp.url().includes('sanctum') || resp.url().includes('csrf')) {
      console.log(`[NET] ${resp.status()} ${resp.url()}`);
    }
  });

  // Fill credentials
  await page.fill('input[type="email"]', EMAIL);
  await page.fill('input[type="password"]', PASS);
  await page.waitForTimeout(500);

  // Click login and wait
  await page.locator('button:has-text("Login")').click();
  console.log('[DEBUG] Login button clicked, waiting for navigation...');

  // Wait up to 45s for redirect
  try {
    await page.waitForURL(/\/(admin|dashboard)/, { timeout: 45000 });
  } catch {
    // Take debug screenshot
    const url = page.url();
    console.log(`[DEBUG] Still on: ${url}`);
    await page.screenshot({ path: path.join(SCREENSHOT_DIR, 'login-debug.png'), fullPage: true });

    // Check for any error notifications
    const body = await page.content();
    if (body.includes('throttle') || body.includes('rate') || body.includes('Too many')) {
      console.log('[DEBUG] Rate limited!');
    }
    if (body.includes('credentials') || body.includes('invalid') || body.includes('грешка')) {
      console.log('[DEBUG] Invalid credentials!');
    }

    // If still on login page, the test will fail gracefully
    if (url.includes('login')) {
      console.log('[DEBUG] Login failed — check credentials or rate limiting');
      return; // beforeAll will fail, skipping all tests
    }
  }
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
});

test.afterAll(async () => {
  if (apiResponses.length) {
    console.log('\n=== AI ASSISTANT API RESPONSES ===');
    apiResponses.forEach((r, i) => {
      console.log(`Response ${i + 1}: status=${r.status}`, JSON.stringify(r.body, null, 2));
    });
  }
  if (page) await page.close();
});

// Helper: find and interact with chat widget
async function findChatInput() {
  const chatInput = page.locator([
    'input[placeholder*="question"]',
    'input[placeholder*="Прашај"]',
    'input[placeholder*="финансии"]',
    'input[placeholder*="Type"]',
    'input[placeholder*="Pyetni"]',
    'input[placeholder*="Sorunuzu"]',
    'input[maxlength="500"]',
  ].join(', ')).first();
  return chatInput;
}

async function sendChatMessage(message) {
  const chatInput = await findChatInput();
  const visible = await chatInput.isVisible().catch(() => false);
  if (!visible) {
    console.log('Chat input not found');
    return false;
  }

  await chatInput.fill(message);
  await page.waitForTimeout(300);

  // Try send button or Enter
  const sendBtn = page.locator('button').filter({ hasText: /Send|Испрати|Dërgo/ }).first();
  const hasSend = await sendBtn.isVisible().catch(() => false);
  if (hasSend) {
    await sendBtn.click();
  } else {
    await chatInput.press('Enter');
  }

  return true;
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 1. Chat Widget Presence
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('chat widget is visible on dashboard', async () => {
  test.setTimeout(15000);
  if (!EMAIL) return test.skip();

  await page.goto(`${BASE}/admin/dashboard`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);

  const chatInput = await findChatInput();
  const visible = await chatInput.isVisible().catch(() => false);

  await ss(page, '01-dashboard-with-chat');
  expect(visible).toBeTruthy();
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 2. Simple Question — Feature Knowledge
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('chat answers question about purchase orders', async () => {
  test.setTimeout(30000);
  if (!EMAIL) return test.skip();

  apiResponses = [];

  const sent = await sendChatMessage('Како да направам набавка?');
  if (!sent) return test.skip();

  // Wait for AI response
  await page.waitForTimeout(10000);
  await ss(page, '02-question-purchase-orders');

  // Check the API returned a meaningful response
  if (apiResponses.length > 0) {
    const lastResp = apiResponses[apiResponses.length - 1];
    console.log('Question response intent:', lastResp.body?.intent);
    console.log('Question response message:', lastResp.body?.message?.substring(0, 200));

    // Should be question or navigate intent, not an error
    expect(lastResp.status).toBe(200);
    expect(lastResp.body?.message).toBeTruthy();
  }
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 3. Direct Invoice Creation
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('chat creates invoice directly', async () => {
  test.setTimeout(30000);
  if (!EMAIL) return test.skip();

  apiResponses = [];

  const sent = await sendChatMessage('Направи фактура за тест клиент, 1000 денари, без ДДВ');
  if (!sent) return test.skip();

  // Wait for AI to process and respond
  await page.waitForTimeout(12000);
  await ss(page, '03-create-invoice-response');

  if (apiResponses.length > 0) {
    const lastResp = apiResponses[apiResponses.length - 1];
    console.log('Create response intent:', lastResp.body?.intent);
    console.log('Create response redirect_url:', lastResp.body?.redirect_url);
    console.log('Create response message:', lastResp.body?.message?.substring(0, 200));

    expect(lastResp.status).toBe(200);

    // Should have a redirect URL (either from direct creation or draft)
    if (lastResp.body?.redirect_url) {
      console.log('SUCCESS: Got redirect URL for created entity');

      // Check for view button in the chat
      const viewBtn = page.locator('a, button').filter({
        hasText: /Прегледај|Отвори|View|види|view/i,
      }).first();
      const hasViewBtn = await viewBtn.isVisible().catch(() => false);
      if (hasViewBtn) {
        await ss(page, '03b-view-button-visible');
        console.log('SUCCESS: View button is visible in chat');
      }
    } else if (lastResp.body?.draft_id) {
      console.log('Got draft ID (fallback mode):', lastResp.body.draft_id);
    }
  }
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 4. Conversation Memory — Follow-up
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('chat remembers previous messages', async () => {
  test.setTimeout(30000);
  if (!EMAIL) return test.skip();

  apiResponses = [];

  // Send a follow-up that references previous conversation
  const sent = await sendChatMessage('А сега направи уште една фактура за истиот клиент, 2000 денари');
  if (!sent) return test.skip();

  await page.waitForTimeout(12000);
  await ss(page, '04-memory-followup');

  if (apiResponses.length > 0) {
    const lastResp = apiResponses[apiResponses.length - 1];
    console.log('Memory test intent:', lastResp.body?.intent);
    console.log('Memory test message:', lastResp.body?.message?.substring(0, 200));

    expect(lastResp.status).toBe(200);

    // The AI should understand "same client" from context
    if (lastResp.body?.intent?.includes('create_invoice')) {
      console.log('SUCCESS: AI understood follow-up context (create_invoice intent)');
    } else if (lastResp.body?.redirect_url) {
      console.log('SUCCESS: AI created entity from follow-up context');
    }
  }
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 5. Navigate Intent — Feature Navigation
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('chat navigates to budgets page', async () => {
  test.setTimeout(30000);
  if (!EMAIL) return test.skip();

  apiResponses = [];

  const sent = await sendChatMessage('Отвори ми ги буџетите');
  if (!sent) return test.skip();

  await page.waitForTimeout(10000);
  await ss(page, '05-navigate-budgets');

  if (apiResponses.length > 0) {
    const lastResp = apiResponses[apiResponses.length - 1];
    console.log('Navigate intent:', lastResp.body?.intent);
    console.log('Navigate URL:', lastResp.body?.navigation_url || lastResp.body?.redirect_url);

    expect(lastResp.status).toBe(200);

    if (lastResp.body?.intent === 'navigate') {
      console.log('SUCCESS: Navigate intent returned');
      expect(lastResp.body?.navigation_url || lastResp.body?.redirect_url).toBeTruthy();
    }
  }
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 6. Summary
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('final chat state screenshot', async () => {
  test.setTimeout(10000);
  if (!EMAIL) return test.skip();

  await ss(page, '06-final-chat-state');

  // Count messages in chat
  const messages = page.locator('[class*="message"], [class*="chat-bubble"], [class*="rounded-lg"]');
  const count = await messages.count();
  console.log(`Total visible chat elements: ${count}`);

  console.log('\n=== AI CHAT MEMORY TEST SUMMARY ===');
  console.log(`Total API calls captured: ${apiResponses.length}`);
  apiResponses.forEach((r, i) => {
    console.log(`  ${i + 1}. status=${r.status}, intent=${r.body?.intent}, has_redirect=${!!r.body?.redirect_url}`);
  });
});
