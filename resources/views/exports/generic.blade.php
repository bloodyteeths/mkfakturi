<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst($type) }} Export</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        .header p {
            margin: 3px 0 0;
            color: #666;
            font-size: 9px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            table-layout: auto;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 4px 5px;
            text-align: left;
            font-size: 7px;
            word-wrap: break-word;
            overflow: hidden;
        }
        th {
            background-color: #4a5568;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 6px;
            white-space: nowrap;
        }
        tr:nth-child(even) {
            background-color: #f7fafc;
        }
        tr:hover {
            background-color: #edf2f7;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #718096;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ ucfirst(str_replace('_', ' ', $type)) }} Export</h1>
        @if($company)
            <p>{{ $company->name }}</p>
        @endif
        <p>Generated: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    @php
        // Columns to exclude from export (internal/system fields)
        $excludeColumns = [
            // Auth/security fields
            'password', 'remember_token', 'email_verified_at',
            'facebook_id', 'google_id', 'github_id', 'stripe_id',
            'two_factor_secret', 'two_factor_recovery_codes',
            'token', 'unique_hash',
            // Foreign key IDs (users see names, not IDs)
            'company_id', 'user_id', 'creator_id', 'customer_id', 'supplier_id',
            'unit_id', 'currency_id', 'category_id', 'warehouse_id',
            'invoice_id', 'bill_id', 'expense_id', 'payment_id',
            'estimate_id', 'proforma_invoice_id', 'recurring_invoice_id',
            'parent_id', 'address_id', 'tax_type_id',
            // Timestamps (redundant with formatted versions)
            'created_at', 'updated_at', 'deleted_at',
            // Internal/system fields
            'pivot', 'media', 'settings', 'meta',
            // Computed/appended attributes (often duplicates or internal)
            'formattedCreatedAt', 'unit_name', 'formatted_created_at',
            'tax_per_item', 'discount_per_item',
        ];

        // Get headers from first row, filtering out excluded columns
        $headers = [];
        $filteredData = [];

        if (count($data) > 0) {
            $allKeys = array_keys($data[0]);
            foreach ($allKeys as $key) {
                // Skip excluded columns
                if (in_array($key, $excludeColumns)) continue;

                // Skip any column ending with _id (likely foreign keys)
                if (preg_match('/_id$/', $key) && $key !== 'id') continue;

                // Check if column has any non-empty values
                $hasValue = false;
                foreach ($data as $row) {
                    $val = $row[$key] ?? null;
                    if ($val !== null && $val !== '' && $val !== [] && $val !== '[]') {
                        $hasValue = true;
                        break;
                    }
                }
                if ($hasValue) {
                    $headers[] = $key;
                }
            }

            // Build filtered data
            foreach ($data as $row) {
                $filteredRow = [];
                foreach ($headers as $header) {
                    $filteredRow[$header] = $row[$header] ?? '';
                }
                $filteredData[] = $filteredRow;
            }
        }
    @endphp

    @if(count($filteredData) > 0)
        <table>
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ str_replace('_', ' ', $header) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($filteredData as $row)
                    <tr>
                        @foreach($row as $value)
                            <td>
                                @if(is_array($value))
                                    {{ implode(', ', array_filter($value)) }}
                                @elseif(is_bool($value))
                                    {{ $value ? 'Yes' : 'No' }}
                                @elseif(strlen($value) > 50)
                                    {{ substr($value, 0, 47) }}...
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <p>No data to export</p>
        </div>
    @endif

    <div class="footer">
        <p>Total records: {{ count($data) }}</p>
    </div>
</body>
</html>
