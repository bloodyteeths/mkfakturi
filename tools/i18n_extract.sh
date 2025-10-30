#!/bin/bash

# i18n_extract.sh - Extract hard-coded English strings from Vue and Blade templates
# Finds user-facing strings that need internationalization
# Excludes technical/system strings and already internationalized content

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
OUTPUT_FILE="$PROJECT_ROOT/tools/i18n_strings.txt"
REPORT_FILE="$PROJECT_ROOT/tools/i18n_extraction_report.txt"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== i18n String Extraction Tool ===${NC}"
echo "Project root: $PROJECT_ROOT"
echo "Output file: $OUTPUT_FILE"
echo "Report file: $REPORT_FILE"
echo

# Initialize files
> "$OUTPUT_FILE"
> "$REPORT_FILE"

# Function to extract strings from Blade templates
extract_blade_strings() {
    echo -e "${YELLOW}Extracting strings from Blade templates...${NC}"
    
    local blade_files=$(find "$PROJECT_ROOT/resources/views" -name "*.blade.php" -not -path "*/vendor/*")
    local blade_count=0
    local string_count=0
    
    for file in $blade_files; do
        blade_count=$((blade_count + 1))
        echo "Processing: $file"
        
        # Extract quoted strings, excluding already internationalized ones
        # Look for strings in quotes but exclude @lang(), __(), and route() calls
        grep -n -o -E '"[^"]{3,}"' "$file" | \
        grep -v -E '(@lang\(|__\(|route\(|csrf_token|str_replace|config\(|url\(|asset\()' | \
        grep -v -E '(class=|id=|name=|type=|src=|href=|action=|method=|for=|value=|placeholder=)' | \
        grep -v -E '(http://|https://|mailto:|tel:|#|/)' | \
        grep -v -E '^[0-9]+:"[0-9.,\-\s]*"$' | \
        while IFS=':' read -r line_num content; do
            # Remove outer quotes
            clean_content=$(echo "$content" | sed 's/^"//; s/"$//')
            
            # Skip technical strings, variables, and CSS classes
            if [[ ! "$clean_content" =~ ^[a-z_\-]+$ ]] && \
               [[ ! "$clean_content" =~ ^[A-Z_]+$ ]] && \
               [[ ! "$clean_content" =~ ^\{\{ ]] && \
               [[ ! "$clean_content" =~ ^\$ ]] && \
               [[ ! "$clean_content" =~ ^(px|em|rem|vh|vw|%)$ ]] && \
               [[ ! "$clean_content" =~ ^[0-9\.\-\s]*$ ]] && \
               [[ ${#clean_content} -gt 2 ]] && \
               [[ "$clean_content" =~ [A-Za-z] ]]; then
                
                string_count=$((string_count + 1))
                echo "BLADE:$file:$line_num:$clean_content" >> "$OUTPUT_FILE"
            fi
        done
    done
    
    echo -e "${GREEN}Processed $blade_count Blade files, found $string_count potential strings${NC}"
    echo "Blade files processed: $blade_count" >> "$REPORT_FILE"
    echo "Blade strings found: $string_count" >> "$REPORT_FILE"
}

# Function to extract strings from Vue templates
extract_vue_strings() {
    echo -e "${YELLOW}Extracting strings from Vue templates...${NC}"
    
    local vue_files=$(find "$PROJECT_ROOT/resources/scripts" -name "*.vue" -o -name "*.js" -o -name "*.ts")
    local vue_count=0
    local string_count=0
    
    for file in $vue_files; do
        vue_count=$((vue_count + 1))
        echo "Processing: $file"
        
        # Extract template strings and JavaScript strings
        # Look for strings in quotes but exclude already internationalized ones
        grep -n -o -E '"[^"]{3,}"|\`[^\`]{3,}\`|'\''[^'\'']{3,}'\''' "$file" | \
        grep -v -E '(\$t\(|\$tc\(|t\(|tc\(|import |from |require\(|console\.)' | \
        grep -v -E '(class=|id=|name=|type=|src=|href=|to=|:to=|v-model=|@click=)' | \
        grep -v -E '(http://|https://|mailto:|tel:|#|/)' | \
        grep -v -E '^[0-9]+:["\`'\''"][0-9.,\-\s]*["\`'\'']$' | \
        while IFS=':' read -r line_num content; do
            # Remove outer quotes/backticks
            clean_content=$(echo "$content" | sed 's/^["'\''`]//; s/["'\''`]$//')
            
            # Skip technical strings, variables, and code
            if [[ ! "$clean_content" =~ ^[a-z_\-]+$ ]] && \
               [[ ! "$clean_content" =~ ^[A-Z_]+$ ]] && \
               [[ ! "$clean_content" =~ ^\{\{ ]] && \
               [[ ! "$clean_content" =~ ^\$ ]] && \
               [[ ! "$clean_content" =~ ^(px|em|rem|vh|vw|%)$ ]] && \
               [[ ! "$clean_content" =~ ^[0-9\.\-\s]*$ ]] && \
               [[ ${#clean_content} -gt 2 ]] && \
               [[ "$clean_content" =~ [A-Za-z] ]]; then
                
                string_count=$((string_count + 1))
                echo "VUE:$file:$line_num:$clean_content" >> "$OUTPUT_FILE"
            fi
        done
    done
    
    echo -e "${GREEN}Processed $vue_count Vue files, found $string_count potential strings${NC}"
    echo "Vue files processed: $vue_count" >> "$REPORT_FILE"
    echo "Vue strings found: $string_count" >> "$REPORT_FILE"
}

# Function to generate summary report
generate_report() {
    echo -e "${YELLOW}Generating extraction report...${NC}"
    
    local total_strings=$(wc -l < "$OUTPUT_FILE")
    local blade_strings=$(grep -c "^BLADE:" "$OUTPUT_FILE" || echo "0")
    local vue_strings=$(grep -c "^VUE:" "$OUTPUT_FILE" || echo "0")
    
    cat >> "$REPORT_FILE" << EOF

=== I18N EXTRACTION SUMMARY ===
Total strings found: $total_strings
Blade template strings: $blade_strings
Vue component strings: $vue_strings

Generated: $(date)

=== NEXT STEPS ===
1. Review $OUTPUT_FILE for strings to internationalize
2. Add relevant strings to lang/mk.json and lang/sq.json
3. Update templates to use @lang() or \$t() functions
4. Test with different locales

=== SAMPLE STRINGS ===
EOF
    
    # Add sample of found strings
    head -20 "$OUTPUT_FILE" >> "$REPORT_FILE"
    
    echo -e "${GREEN}Report generated: $REPORT_FILE${NC}"
    echo -e "${BLUE}Total strings found: $total_strings${NC}"
}

# Main execution
echo -e "${BLUE}Starting extraction process...${NC}"
echo "Extraction started: $(date)" > "$REPORT_FILE"

extract_blade_strings
extract_vue_strings
generate_report

echo
echo -e "${GREEN}Extraction complete!${NC}"
echo -e "${BLUE}Files generated:${NC}"
echo "  - Strings: $OUTPUT_FILE"
echo "  - Report:  $REPORT_FILE"
echo
echo -e "${YELLOW}Next: Review extracted strings and add translations to lang files${NC}"

