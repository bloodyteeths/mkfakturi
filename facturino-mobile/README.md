# Facturino Companion Mobile App

Lightweight companion app for Facturino accounting system.

## Features

- ✅ Login with Sanctum authentication
- ✅ Dashboard with stats (unpaid, overdue, collected)
- ✅ Invoice list/detail/create/send
- ✅ Customer list/add
- ✅ Bank accounts (read-only)
- ✅ Receipt scanner (OCR)
- ✅ Settings

## NOT Included (Web-Only)

- ❌ Subscriptions/payments
- ❌ Partner dashboards
- ❌ QES signing
- ❌ E-invoice XML
- ❌ IFRS reports
- ❌ VAT reports

## Setup

```bash
cd facturino-mobile
npm install
```

## Run

```bash
npm start      # Start Expo dev server
npm run ios    # Run on iOS simulator
npm run android # Run on Android emulator
```

## Build

```bash
eas build --profile preview --platform all
```

## Environment

Update API base URL in `src/api/client.ts`:
```typescript
const API_BASE_URL = 'https://your-api-domain.com/api/v1';
```

## Architecture

- Expo SDK 50
- TypeScript
- React Navigation 6
- Context API (no Redux)
- Axios for API
- SecureStore for tokens

## Structure

```
src/
├── api/          # API clients
├── screens/      # App screens (12 total)
├── contexts/     # AuthContext
├── navigation/   # Navigation config
├── types/        # TypeScript types
└── utils/        # Utilities (storage, formatters)
```

## Screens (12)

1. LoginScreen
2. DashboardScreen
3. InvoiceListScreen
4. InvoiceDetailScreen
5. CreateInvoiceScreen
6. CustomerListScreen
7. AddCustomerScreen
8. BankAccountsScreen
9. ReceiptScanScreen
10. NotificationsScreen
11. SettingsScreen
12. MoreScreen

## API Endpoints (27)

See MOBILE_ROADMAP.md for full list.
