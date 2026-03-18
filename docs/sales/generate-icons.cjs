#!/usr/bin/env node
/**
 * Generate professional flat-design icons via Gemini Nano Banana
 * for the accountant booklet, replacing emoji icons.
 */

const https = require('https');
const fs = require('fs');
const path = require('path');

const API_KEY = process.env.GEMINI_API_KEY || '';
const MODEL = 'gemini-2.5-flash-image';
const ICONS_DIR = path.join(__dirname, 'icons');

const STYLE = 'Simple minimal flat vector icon, indigo/purple color (#4F46E5), white background, no text, no shadows, no gradients, clean geometric lines, suitable for professional print booklet. 128x128 pixels.';

const icons = [
  { name: 'building', prompt: 'A modern office building with windows. ' + STYLE },
  { name: 'bank', prompt: 'A bank building with columns and a dollar/currency symbol. ' + STYLE },
  { name: 'document', prompt: 'A document or invoice page with lines of text and a checkmark. ' + STYLE },
  { name: 'camera', prompt: 'A camera or document scanner icon, representing scanning/OCR. ' + STYLE },
  { name: 'chat', prompt: 'A chat bubble or AI assistant icon with a small sparkle/star. ' + STYLE },
  { name: 'box', prompt: 'A shipping/inventory box or package icon. ' + STYLE },
  { name: 'chart', prompt: 'A bar chart or line graph representing financial reports/analytics. ' + STYLE },
  { name: 'payroll', prompt: 'A wallet or money/coins icon representing payroll/salary. ' + STYLE },
  { name: 'search', prompt: 'A magnifying glass icon representing search or data extraction. ' + STYLE },
  { name: 'clipboard', prompt: 'A clipboard with a checklist, representing bookkeeping/posting. ' + STYLE },
  { name: 'users', prompt: 'Two or three people/users silhouette icon representing a team or accountants. ' + STYLE },
  { name: 'shield', prompt: 'A shield with a checkmark representing security and compliance. ' + STYLE },
  { name: 'lightning', prompt: 'A lightning bolt icon representing speed and efficiency. ' + STYLE },
  { name: 'percent', prompt: 'A percentage symbol (%) icon representing commission or discount. ' + STYLE },
  { name: 'globe', prompt: 'A globe or cloud icon representing cloud computing and web access. ' + STYLE },
  { name: 'sparkle', prompt: 'A magic wand or sparkle/star icon representing AI and innovation. ' + STYLE },
];

function generateIcon(icon) {
  return new Promise((resolve, reject) => {
    const data = JSON.stringify({
      contents: [{ parts: [{ text: icon.prompt }] }],
      generationConfig: { responseModalities: ['TEXT', 'IMAGE'] },
    });

    const url = `https://generativelanguage.googleapis.com/v1beta/models/${MODEL}:generateContent?key=${API_KEY}`;
    const urlObj = new URL(url);

    const options = {
      hostname: urlObj.hostname,
      path: urlObj.pathname + urlObj.search,
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(data) },
    };

    const req = https.request(options, (res) => {
      let body = '';
      res.on('data', (chunk) => body += chunk);
      res.on('end', () => {
        try {
          const json = JSON.parse(body);
          if (json.error) {
            console.error(`FAIL ${icon.name}: ${json.error.message}`);
            resolve(false);
            return;
          }
          const parts = json.candidates?.[0]?.content?.parts || [];
          for (const p of parts) {
            if (p.inlineData) {
              const buf = Buffer.from(p.inlineData.data, 'base64');
              const outPath = path.join(ICONS_DIR, `${icon.name}.png`);
              fs.writeFileSync(outPath, buf);
              console.log(`OK ${icon.name}: ${buf.length} bytes`);
              resolve(true);
              return;
            }
          }
          console.error(`FAIL ${icon.name}: no image in response`);
          resolve(false);
        } catch (e) {
          console.error(`FAIL ${icon.name}: ${e.message}`);
          resolve(false);
        }
      });
    });

    req.on('error', (e) => { console.error(`FAIL ${icon.name}: ${e.message}`); resolve(false); });
    req.write(data);
    req.end();
  });
}

async function main() {
  if (!fs.existsSync(ICONS_DIR)) fs.mkdirSync(ICONS_DIR, { recursive: true });

  // Generate in batches of 4 to avoid rate limits
  for (let i = 0; i < icons.length; i += 4) {
    const batch = icons.slice(i, i + 4);
    console.log(`\nBatch ${Math.floor(i/4) + 1}/${Math.ceil(icons.length/4)}: ${batch.map(b => b.name).join(', ')}`);
    await Promise.all(batch.map(generateIcon));
    if (i + 4 < icons.length) {
      console.log('Waiting 2s...');
      await new Promise(r => setTimeout(r, 2000));
    }
  }

  console.log('\nDone! Icons in:', ICONS_DIR);
}

main();
