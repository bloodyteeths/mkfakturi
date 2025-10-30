#!/bin/bash

# Branding Scan Script for InvoiceShelf -> Facturino Rebranding
# Created: 2025-07-26
# Purpose: Search for all "InvoiceShelf" occurrences and generate CSV report

# Output CSV file
CSV_OUTPUT="branding_report.csv"

# Remove existing CSV if it exists
if [ -f "$CSV_OUTPUT" ]; then
    rm "$CSV_OUTPUT"
fi

# Create CSV header
echo "file_path,line_number,match_context" > "$CSV_OUTPUT"

# Define directories to exclude
EXCLUDE_DIRS="--exclude-dir=node_modules --exclude-dir=vendor --exclude-dir=.git --exclude-dir=storage/logs --exclude-dir=storage/framework/cache --exclude-dir=storage/framework/sessions --exclude-dir=storage/framework/views --exclude-dir=public/build"

# Define file types to include (text files only)
INCLUDE_FILES="--include=*.php --include=*.vue --include=*.js --include=*.blade.php --include=*.md --include=*.json --include=*.yml --include=*.yaml --include=*.config.js --include=*.txt --include=*.scss --include=*.css --include=*.ts --include=*.sh --include=*.sql"

echo "Starting branding scan for 'InvoiceShelf' occurrences..."
echo "Excluding directories: node_modules, vendor, .git, storage/logs, storage/framework/*"
echo "Including file types: .php, .vue, .js, .blade.php, .md, .json, .yml, .yaml, .config.js, .txt, .scss, .css, .ts, .sh, .sql"
echo

# Find all occurrences of "InvoiceShelf" and format for CSV
grep -r -n $EXCLUDE_DIRS $INCLUDE_FILES "InvoiceShelf" . 2>/dev/null | while IFS=: read -r file line_num match_context; do
    # Clean up file path (remove leading ./)
    clean_file=$(echo "$file" | sed 's|^\./||')
    
    # Escape quotes in match_context for CSV safety
    escaped_context=$(echo "$match_context" | sed 's/"/\\""/g' | sed 's/,/\\,/g')
    
    # Write to CSV
    echo "\"$clean_file\",$line_num,\"$escaped_context\"" >> "$CSV_OUTPUT"
done

# Count total occurrences and files
if [ -f "$CSV_OUTPUT" ]; then
    # Count lines (subtract 1 for header)
    total_occurrences=$(($(wc -l < "$CSV_OUTPUT") - 1))
    
    # Count unique files
    unique_files=$(tail -n +2 "$CSV_OUTPUT" | cut -d',' -f1 | sort -u | wc -l)
    
    echo "Scan completed!"
    echo "Total 'InvoiceShelf' occurrences found: $total_occurrences"
    echo "Files containing 'InvoiceShelf': $unique_files"
    echo "Report saved to: $CSV_OUTPUT"
    echo
    
    # Show first few entries as preview
    echo "Preview of findings:"
    echo "==================="
    head -6 "$CSV_OUTPUT" | column -t -s ','
    
    if [ $total_occurrences -gt 5 ]; then
        echo "... (and $((total_occurrences - 5)) more occurrences)"
    fi
else
    echo "Error: No CSV file generated. No occurrences found or scan failed."
    exit 1
fi

