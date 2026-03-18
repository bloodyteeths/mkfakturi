#!/usr/bin/env node
/**
 * Generate print-ready A3 landscape PDF from the booklet HTML
 * Usage: node docs/sales/generate-booklet-pdf.js
 * Output: docs/sales/booklet-accountants-a3.pdf
 */

const puppeteer = require('puppeteer');
const path = require('path');

(async () => {
  const htmlPath = path.resolve(__dirname, 'booklet-accountants-a3.html');
  const pdfPath = path.resolve(__dirname, 'booklet-accountants-a3.pdf');

  console.log('Launching browser...');
  const browser = await puppeteer.launch({ headless: true });
  const page = await browser.newPage();

  console.log('Loading booklet HTML...');
  await page.goto(`file://${htmlPath}`, { waitUntil: 'networkidle0', timeout: 30000 });

  // Wait for fonts to load
  await page.evaluateHandle('document.fonts.ready');

  console.log('Generating PDF (A3 landscape, 300 DPI)...');
  await page.pdf({
    path: pdfPath,
    width: '420mm',
    height: '297mm',
    printBackground: true,
    preferCSSPageSize: false,
    margin: { top: 0, right: 0, bottom: 0, left: 0 },
    scale: 1,
  });

  await browser.close();
  console.log(`PDF saved to: ${pdfPath}`);
})();
