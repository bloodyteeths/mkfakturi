# OnivoFetcher - Onivo Accounting Data Extraction Tool

Playwright automation script for extracting data from Onivo accounting system for competitive data migration in the Macedonia market.

## Features

- **Automated Login**: Handles authentication with Onivo accounting system
- **Multi-Format Export**: Supports CSV and Excel downloads  
- **Complete Data Coverage**: Extracts customers, invoices, items, and payments
- **Macedonian Language Support**: Handles Cyrillic text and local formats
- **Robust Error Handling**: Retry logic and detailed logging
- **Stealth Automation**: User-agent rotation and anti-detection measures
- **Configurable Downloads**: Organized file naming and storage

## Installation

```bash
# Navigate to the onivo-fetcher directory
cd tools/onivo-fetcher

# Install dependencies
npm install

# Install Playwright browsers
npm run install-playwright
```

## Configuration

1. Copy the environment template:
```bash
cp .env.example .env
```

2. Edit `.env` with your credentials:
```env
ONIVO_EMAIL=your-email@example.com
ONIVO_PASS=your-password
ONIVO_URL=https://your-instance.onivo.mk
```

## Usage

### Basic Usage
```bash
# Run all exports (customers, invoices, items, payments)
npm run fetch

# Run in headed mode for debugging
npm run fetch-debug

# Run specific export types
node index.js customers invoices

# Test login only
npm test
```

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `ONIVO_EMAIL` | Login email (required) | - |
| `ONIVO_PASS` | Login password (required) | - |
| `ONIVO_URL` | Onivo instance URL | `https://demo.onivo.mk` |
| `HEADLESS` | Run browser in headless mode | `true` |
| `DEBUG` | Enable verbose logging | `false` |
| `DOWNLOAD_PATH` | Download directory | `./downloads` |

### Export Types

The tool supports extracting the following data types:

- **customers** (Кліенти) - Customer database with contact information
- **invoices** (Фактури) - Invoice records with line items  
- **items** (Ставки) - Product/service catalog
- **payments** (Плаќања) - Payment transactions and history

## Output

Downloaded files are saved to the `downloads/` directory with timestamped names:

```
downloads/
├── klienti_2025-01-26T10-30-00-000Z.csv
├── fakturi_2025-01-26T10-31-15-000Z.xlsx  
├── stavki_2025-01-26T10-32-30-000Z.csv
├── plateni_2025-01-26T10-33-45-000Z.csv
└── extraction-report-1706267625000.json
```

## Integration with Migration Wizard

The extracted files can be directly imported into the Facturino Universal Migration Wizard:

1. Run OnivoFetcher to extract data from competitor system
2. Upload the generated CSV/Excel files to `/admin/imports`
3. Use the 4-step migration wizard with Macedonian field mapping
4. Complete business migration in minutes

## Error Handling

- **Retry Logic**: Automatically retries failed exports up to 3 times
- **Screenshots**: Captures error screenshots when `DEBUG=true`
- **Detailed Logging**: Comprehensive logs saved to `onivo-fetcher.log`
- **Graceful Degradation**: Continues with other exports if one fails

## Troubleshooting

### Login Issues
```bash
# Test login credentials
npm test

# Run in headed mode to see the browser
HEADLESS=false npm run fetch-debug
```

### Export Failures
```bash
# Enable debug mode for detailed logs
DEBUG=true npm run fetch

# Check the error screenshots in the current directory
ls -la *.png
```

### Network Issues
```bash
# Increase timeout for slow connections
TIMEOUT=60000 npm run fetch
```

## Development

### Code Structure

```
index.js              # Main automation script
├── validateConfig()  # Configuration validation
├── createBrowser()   # Browser setup with Macedonia locale
├── login()          # Onivo authentication  
├── exportData()     # Data extraction per type
└── generateReport() # Results summary
```

### Adding New Export Types

To support additional data types, update the `EXPORT_TYPES` configuration:

```javascript
const EXPORT_TYPES = {
  expenses: {
    name: 'Трошоци', // Macedonian name
    path: '/expenses/export',
    filename: 'trosoci',
    selectors: {
      exportButton: '[data-export="expenses"]',
      formatSelect: 'select[name="format"]', 
      downloadButton: '.btn-download'
    }
  }
};
```

## Security Considerations

- **Credentials**: Never commit `.env` files with real credentials
- **Rate Limiting**: Built-in delays prevent overwhelming the server
- **User-Agent**: Rotates browser signatures to avoid detection
- **Clean Exit**: Properly closes browser instances

## Macedonia Market Context

This tool addresses the critical need for **painless data migration** from Macedonia's dominant accounting software (Onivo) to enable customer acquisition. The automation handles:

- **Cyrillic Text**: Proper encoding for Macedonian characters
- **Local Formats**: Date formats (dd.mm.yyyy) and currency (MKD/EUR)
- **Business Logic**: Tax rates (18% standard, 5% reduced VAT)
- **Compliance**: 7-year data retention requirements

## Performance

- **Parallel Processing**: Can handle multiple export types simultaneously
- **Memory Efficient**: Streams large files without loading into memory
- **Network Optimized**: Waits for network idle before proceeding
- **Fast Execution**: Complete business migration in under 10 minutes

## License

MIT License - See main project license for details.

## Support

For issues or questions:
1. Check the generated `onivo-fetcher.log` file
2. Run with `DEBUG=true` for verbose output
3. Capture screenshots by running in headed mode
4. Review the extraction report JSON for detailed results