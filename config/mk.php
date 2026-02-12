<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Macedonian XML Signing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for digital signing of UBL XML documents for
    | Macedonian tax authority compliance
    |
    */

    'xml_signing' => [

        /*
        |--------------------------------------------------------------------------
        | Private Key Path
        |--------------------------------------------------------------------------
        |
        | Path to the private key file used for XML digital signatures.
        | This should be a RSA private key in PEM format.
        | Keep this file secure and never commit to version control.
        |
        */
        'private_key_path' => env('MK_XML_PRIVATE_KEY_PATH', storage_path('certificates/private.key')),

        /*
        |--------------------------------------------------------------------------
        | Certificate Path
        |--------------------------------------------------------------------------
        |
        | Path to the X.509 certificate file in PEM format.
        | This certificate will be embedded in the XML signature.
        |
        */
        'certificate_path' => env('MK_XML_CERTIFICATE_PATH', storage_path('certificates/certificate.pem')),

        /*
        |--------------------------------------------------------------------------
        | Private Key Passphrase
        |--------------------------------------------------------------------------
        |
        | Passphrase for the private key if it's password protected.
        | Leave empty if the private key has no passphrase.
        |
        */
        'passphrase' => env('MK_XML_PASSPHRASE', ''),

        /*
        |--------------------------------------------------------------------------
        | Signature Algorithm
        |--------------------------------------------------------------------------
        |
        | The signature algorithm to use for signing XML documents.
        | Supported: 'RSA_SHA256', 'RSA_SHA1'
        | SHA256 is recommended for better security.
        |
        */
        'algorithm' => env('MK_XML_SIGNATURE_ALGORITHM', 'RSA_SHA256'),

        /*
        |--------------------------------------------------------------------------
        | Canonicalization Method
        |--------------------------------------------------------------------------
        |
        | The canonicalization method for XML signatures.
        | This is usually EXC_C14N for most standards.
        |
        */
        'canonicalization_method' => 'EXC_C14N',

    ],

    /*
    |--------------------------------------------------------------------------
    | Macedonian Tax Authority Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to Macedonian tax authority requirements
    |
    */

    'tax_authority' => [

        /*
        |--------------------------------------------------------------------------
        | Standard VAT Rate
        |--------------------------------------------------------------------------
        |
        | The standard VAT rate in Macedonia (18%)
        |
        */
        'standard_vat_rate' => 18,

        /*
        |--------------------------------------------------------------------------
        | Reduced VAT Rate
        |--------------------------------------------------------------------------
        |
        | The reduced VAT rate in Macedonia (5%)
        |
        */
        'reduced_vat_rate' => 5,

        /*
        |--------------------------------------------------------------------------
        | Restaurant VAT Rate
        |--------------------------------------------------------------------------
        |
        | The preferential VAT rate for restaurant/hospitality services
        | in Macedonia (10%) — Закон за данокот на додадена вредност
        |
        */
        'restaurant_vat_rate' => 10,

        /*
        |--------------------------------------------------------------------------
        | Tax Scheme
        |--------------------------------------------------------------------------
        |
        | The tax scheme identifier for Macedonia
        |
        */
        'tax_scheme' => 'VAT',

        /*
        |--------------------------------------------------------------------------
        | Tax Scheme Name (Macedonian)
        |--------------------------------------------------------------------------
        |
        | The tax scheme name in Macedonian language
        |
        */
        'tax_scheme_name' => 'ДДВ',

        /*
        |--------------------------------------------------------------------------
        | Country Code
        |--------------------------------------------------------------------------
        |
        | ISO 3166-1 alpha-2 country code for Macedonia
        |
        */
        'country_code' => 'MK',

        /*
        |--------------------------------------------------------------------------
        | Currency Code
        |--------------------------------------------------------------------------
        |
        | ISO 4217 currency code for Macedonian Denar
        |
        */
        'currency_code' => 'MKD',

    ],

    /*
    |--------------------------------------------------------------------------
    | UBL Document Settings
    |--------------------------------------------------------------------------
    |
    | Settings for UBL document generation
    |
    */

    'ubl' => [

        /*
        |--------------------------------------------------------------------------
        | UBL Version
        |--------------------------------------------------------------------------
        |
        | The UBL version to use for document generation
        |
        */
        'version' => '2.1',

        /*
        |--------------------------------------------------------------------------
        | Customization ID
        |--------------------------------------------------------------------------
        |
        | The customization identifier for Macedonian UBL documents
        |
        */
        'customization_id' => 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0',

        /*
        |--------------------------------------------------------------------------
        | Profile ID
        |--------------------------------------------------------------------------
        |
        | The profile identifier for Macedonian business processes
        |
        */
        'profile_id' => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',

        /*
        |--------------------------------------------------------------------------
        | XSD Schema Path
        |--------------------------------------------------------------------------
        |
        | Path to UBL XSD schema files for validation
        |
        */
        'schema_path' => storage_path('schemas'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for bank PSD2 integrations
    |
    */

    'banks' => [

        /*
        |--------------------------------------------------------------------------
        | Supported Banks
        |--------------------------------------------------------------------------
        |
        | List of supported banks for PSD2 integration
        |
        */
        'supported' => ['stopanska', 'nlb', 'komercijalna'],

        /*
        |--------------------------------------------------------------------------
        | Rate Limits
        |--------------------------------------------------------------------------
        |
        | API rate limits for different banks (requests per minute)
        |
        */
        'rate_limits' => [
            'stopanska' => 15,     // 15 requests per minute
            'nlb' => 15,           // 15 requests per minute
            'komercijalna' => 15,  // 15 requests per minute
        ],

        /*
        |--------------------------------------------------------------------------
        | Default Sync Settings
        |--------------------------------------------------------------------------
        |
        | Default settings for bank transaction synchronization
        |
        */
        'sync_defaults' => [
            'days_back' => 30,      // Days to look back for transactions
            'max_transactions' => 100, // Maximum transactions per sync
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Stopanska Banka PSD2 Settings
    |--------------------------------------------------------------------------
    |
    | OAuth2 configuration for Stopanska Banka PSD2 API
    |
    */
    'stopanska' => [
        'client_id' => env('STOPANSKA_CLIENT_ID', ''),
        'client_secret' => env('STOPANSKA_CLIENT_SECRET', ''),
        'environment' => env('STOPANSKA_ENVIRONMENT', 'sandbox'),
        'sandbox_base_url' => env('STOPANSKA_SANDBOX_BASE_URL', 'https://sandbox-api.ob.stb.kibs.mk/xs2a/v1'),
        'production_base_url' => env('STOPANSKA_PRODUCTION_BASE_URL', 'https://api.ob.stb.kibs.mk/xs2a/v1'),
        'rate_limit_enabled' => env('STOPANSKA_RATE_LIMIT_ENABLED', true),
        'max_transactions_per_request' => env('STOPANSKA_MAX_TRANSACTIONS_PER_REQUEST', 200),
        'redirect_uri' => env('STOPANSKA_REDIRECT_URI', null), // Must be registered in Stopanska developer portal

        // mTLS Certificate Configuration (may be required for PSD2 API access)
        'mtls_cert_path' => env('STOPANSKA_MTLS_CERT_PATH', null),
        'mtls_key_path' => env('STOPANSKA_MTLS_KEY_PATH', null),
        'mtls_key_password' => env('STOPANSKA_MTLS_KEY_PASSWORD', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | NLB Banka PSD2 Settings
    |--------------------------------------------------------------------------
    |
    | OAuth2 configuration for NLB Banka PSD2 API
    |
    */
    'nlb' => [
        'client_id' => env('NLB_CLIENT_ID', ''),
        'client_secret' => env('NLB_CLIENT_SECRET', ''),
        'environment' => env('NLB_ENVIRONMENT', 'sandbox'),
        'sandbox_base_url' => env('NLB_SANDBOX_BASE_URL', 'https://developer-ob.nlb.mk/apis/xs2a/v1'),
        'production_base_url' => env('NLB_PRODUCTION_BASE_URL', 'https://developer-ob.nlb.mk/apis/xs2a/v1'),
        'auth_sandbox_base_url' => env('NLB_AUTH_SANDBOX_BASE_URL', 'https://auth.sandbox.mk.open-bank.io/v1/authentication/tenants/nlb'),
        'auth_production_base_url' => env('NLB_AUTH_PRODUCTION_BASE_URL', 'https://auth.mk.open-bank.io/v1/authentication/tenants/nlb'),
        'redirect_uri' => env('NLB_REDIRECT_URI', null), // Must be registered in NLB developer portal
        'scopes' => env('NLB_SCOPES', 'openid'), // OAuth scopes - NLB auto-grants PSD2 scopes

        // mTLS Certificate Configuration (required for PSD2 API access)
        // Certificates must be obtained from NLB developer portal
        // Paths can be absolute or relative to storage/certificates/ directory
        'mtls_cert_path' => env('NLB_MTLS_CERT_PATH', null), // Path to client certificate (.pem or .crt)
        'mtls_key_path' => env('NLB_MTLS_KEY_PATH', null),   // Path to private key (.key)
        'mtls_key_password' => env('NLB_MTLS_KEY_PASSWORD', null), // Password for encrypted key (optional)
    ],

    /*
    |--------------------------------------------------------------------------
    | Komercijalna Banka PSD2 Settings
    |--------------------------------------------------------------------------
    |
    | OAuth2 configuration for Komercijalna Banka PSD2 API
    | BIC/SWIFT: KOBSMK2X
    |
    */
    'komercijalna' => [
        'client_id' => env('MK_KOMER_CLIENT_ID', ''),
        'client_secret' => env('MK_KOMER_CLIENT_SECRET', ''),
        'environment' => env('MK_KOMER_ENVIRONMENT', 'sandbox'),
        'sandbox_base_url' => env('MK_KOMER_SANDBOX_URL', 'https://api-sandbox.kb.com.mk'),
        'production_base_url' => env('MK_KOMER_API_URL', 'https://api.kb.com.mk'),
        'auth_base_url' => env('MK_KOMER_AUTH_URL', 'https://auth.kb.com.mk'),
        'redirect_uri' => env('MK_KOMER_REDIRECT_URI', null), // Must be registered in Komercijalna developer portal
        'scopes' => env('MK_KOMER_SCOPES', 'accounts transactions'), // OAuth scopes for PSD2 access

        // mTLS Certificate Configuration (required for PSD2 API access)
        // Certificates must be obtained from Komercijalna Banka developer portal
        // Paths can be absolute or relative to storage/certificates/ directory
        'mtls_cert_path' => env('MK_KOMER_MTLS_CERT', null), // Path to client certificate (.pem or .crt)
        'mtls_key_path' => env('MK_KOMER_MTLS_KEY', null),   // Path to private key (.key)
        'mtls_key_password' => env('MK_KOMER_MTLS_KEY_PASSWORD', null), // Password for encrypted key (optional)
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Settings
    |--------------------------------------------------------------------------
    |
    | Settings for Macedonian payment gateways
    |
    */

    'payment_gateways' => [

        /*
        |--------------------------------------------------------------------------
        | CPay Settings
        |--------------------------------------------------------------------------
        |
        | Settings for CPay payment gateway (CASYS)
        |
        */
        'cpay' => [
            'merchant_id' => env('CPAY_MERCHANT_ID', ''),
            'secret_key' => env('CPAY_SECRET_KEY', ''),
            'payment_url' => env('CPAY_PAYMENT_URL', 'https://cpay.com.mk/payment'),
            'success_url' => env('CPAY_SUCCESS_URL', ''),
            'error_url' => env('CPAY_ERROR_URL', ''),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | E-Faktura Portal Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Macedonian e-Faktura tax authority portal
    |
    */

    'efaktura' => [

        /*
        |--------------------------------------------------------------------------
        | Portal URL
        |--------------------------------------------------------------------------
        |
        | The base URL for the Macedonian e-Faktura portal
        |
        */
        'portal_url' => env('MK_EFAKTURA_PORTAL_URL', 'https://e-ujp.ujp.gov.mk'),

        /*
        |--------------------------------------------------------------------------
        | Authentication Credentials
        |--------------------------------------------------------------------------
        |
        | Username and password for e-Faktura portal authentication
        |
        */
        'username' => env('MK_EFAKTURA_USERNAME'),
        'password' => env('MK_EFAKTURA_PASSWORD'),

        /*
        |--------------------------------------------------------------------------
        | Submission Mode
        |--------------------------------------------------------------------------
        |
        | Submission mode for e-invoices:
        | - 'portal': Submit via web portal (requires username/password)
        | - 'api': Submit via API (requires API credentials)
        |
        */
        'mode' => env('MK_EFAKTURA_MODE', 'portal'),

        /*
        |--------------------------------------------------------------------------
        | API Credentials (for API mode)
        |--------------------------------------------------------------------------
        |
        | API key and secret for programmatic submission
        |
        */
        'api_key' => env('MK_EFAKTURA_API_KEY'),
        'api_secret' => env('MK_EFAKTURA_API_SECRET'),

        /*
        |--------------------------------------------------------------------------
        | Timeout Settings
        |--------------------------------------------------------------------------
        |
        | HTTP timeout for portal/API requests (in seconds)
        |
        */
        'timeout' => env('MK_EFAKTURA_TIMEOUT', 30),

        /*
        |--------------------------------------------------------------------------
        | Retry Settings
        |--------------------------------------------------------------------------
        |
        | Number of times to retry failed submissions
        |
        */
        'max_retries' => env('MK_EFAKTURA_MAX_RETRIES', 3),

        /*
        |--------------------------------------------------------------------------
        | Environment
        |--------------------------------------------------------------------------
        |
        | Environment for e-Faktura submission:
        | - 'production': Live submissions
        | - 'sandbox': Test submissions
        |
        */
        'environment' => env('MK_EFAKTURA_ENVIRONMENT', 'production'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags for Macedonian-specific features
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Payroll Settings
    |--------------------------------------------------------------------------
    |
    | Macedonian payroll compliance settings
    |
    */

    'payroll' => [
        // Overtime multipliers (Закон за работни односи)
        'overtime_regular_multiplier' => 1.35,   // Regular overtime: 135%
        'overtime_holiday_multiplier' => 1.50,   // Holiday/night overtime: 150%

        // Contribution base limits (2024 rates)
        // Minimum: 50% of national average salary (MKD 63,154 / 2 = 31,577)
        // Maximum: 16x national average salary
        'min_contribution_base' => 3157700,      // MKD 31,577 in cents
        'max_contribution_base' => 101046400,    // MKD 1,010,464 in cents
        'national_avg_salary' => 6315400,        // MKD 63,154 in cents
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for exchange rate providers
    |
    */

    'exchange_rates' => [
        'provider' => env('EXCHANGE_RATE_PROVIDER', 'nbrm'),
        'nbrm' => [
            'base_url' => env('NBRM_API_URL', 'https://www.nbrm.mk/KLServiceNOV'),
            'cache_ttl' => env('NBRM_CACHE_TTL', 86400), // 24 hours
        ],
        'frankfurter' => [
            'base_url' => 'https://api.frankfurter.dev/v1',
            'cache_ttl' => 14400, // 4 hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Central Registry (crm.com.mk)
    |--------------------------------------------------------------------------
    |
    | Public company lookup service. No API key needed.
    |
    */

    'central_registry' => [
        'base_url' => env('CRM_BASE_URL', 'https://www.crm.com.mk'),
        'cache_ttl' => env('CRM_CACHE_TTL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Viber Business Notifications
    |--------------------------------------------------------------------------
    |
    | Registration at partners.viber.com (free, self-service).
    |
    */

    'viber' => [
        'enabled' => env('VIBER_ENABLED', false),
        'auth_token' => env('VIBER_AUTH_TOKEN'),
        'sender_name' => env('VIBER_SENDER_NAME', 'Facturino'),
        'sender_avatar' => env('VIBER_SENDER_AVATAR'),
        'notifications' => [
            'invoice_sent' => env('VIBER_NOTIFY_INVOICE_SENT', true),
            'payment_received' => env('VIBER_NOTIFY_PAYMENT_RECEIVED', true),
            'overdue_reminder' => env('VIBER_NOTIFY_OVERDUE_REMINDER', false),
            'overdue_days' => env('VIBER_OVERDUE_DAYS', 7),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WooCommerce Integration
    |--------------------------------------------------------------------------
    |
    | Per-company settings stored in company_settings table.
    | These are global defaults only.
    |
    */

    'woocommerce' => [
        'default_sync_frequency' => env('WOOCOMMERCE_SYNC_FREQUENCY', 60), // minutes
        'max_orders_per_sync' => env('WOOCOMMERCE_MAX_ORDERS', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fiscal Devices — ErpNet.FP Sidecar
    |--------------------------------------------------------------------------
    |
    | ErpNet.FP is an open-source .NET fiscal printer server that handles
    | vendor-specific protocols (Daisy, David, Expert, etc.) and exposes
    | a unified REST API. Runs as a Docker sidecar service.
    |
    | GitHub: https://github.com/erpnet/ErpNet.FP
    |
    */
    'fiscal_devices' => [
        'erpnet_fp' => [
            'base_url' => env('ERPNET_FP_BASE_URL', 'http://erpnet-fp:8001'),
            'timeout' => env('ERPNET_FP_TIMEOUT', 15),
            'connect_timeout' => env('ERPNET_FP_CONNECT_TIMEOUT', 5),
        ],
    ],

    'features' => [
        'advanced_payments' => env('FEATURE_ADVANCED_PAYMENTS', false),
        'psd2_banking' => env('FEATURE_PSD2_BANKING', false),
        'e_invoicing' => env('FEATURE_E_INVOICING', false),
        'central_registry_lookup' => env('FEATURE_CENTRAL_REGISTRY', true),
        'viber_notifications' => env('FEATURE_VIBER_NOTIFICATIONS', false),
        'woocommerce_sync' => env('FEATURE_WOOCOMMERCE_SYNC', false),
        'fiscal_devices' => env('FEATURE_FISCAL_DEVICES', false),
    ],

];
// CLAUDE-CHECKPOINT
