#!/bin/bash
# test_backup.sh
# Test script for Facturino backup system
# Tests backup creation, verification, and listing

set -e # Exit on error

echo "========================================="
echo "Facturino Backup System Test"
echo "========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored status
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ $2${NC}"
    else
        echo -e "${RED}✗ $2${NC}"
        exit 1
    fi
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "ℹ $1"
}

# Step 1: Check if backup config exists
echo "Step 1: Checking backup configuration..."
if [ -f config/backup.php ]; then
    print_status 0 "Backup configuration file exists"
else
    print_status 1 "Backup configuration file not found"
fi
echo ""

# Step 2: Check if Spatie Backup is installed
echo "Step 2: Checking Spatie Backup package..."
if grep -q "spatie/laravel-backup" composer.json; then
    print_status 0 "Spatie Backup package found in composer.json"
else
    print_status 1 "Spatie Backup package not found"
fi
echo ""

# Step 3: Check storage directory permissions
echo "Step 3: Checking storage directory permissions..."
if [ -w storage/app ]; then
    print_status 0 "Storage directory is writable"
else
    print_warning "Storage directory may not be writable"
fi
echo ""

# Step 4: Run database-only backup test
echo "Step 4: Running database-only backup test..."
print_info "This will create a test backup of the database only..."
php artisan backup:run --only-db
if [ $? -eq 0 ]; then
    print_status 0 "Database backup created successfully"
else
    print_status 1 "Database backup failed"
fi
echo ""

# Step 5: Verify backup was created
echo "Step 5: Verifying backup file was created..."

# Get the backup name from config
BACKUP_NAME=$(php artisan tinker --execute="echo config('backup.backup.name');" 2>/dev/null | tail -1)
if [ -z "$BACKUP_NAME" ]; then
    BACKUP_NAME="facturino"
fi

BACKUP_DIR="storage/app/${BACKUP_NAME}"

if [ -d "$BACKUP_DIR" ]; then
    LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/*.zip 2>/dev/null | head -1)

    if [ -z "$LATEST_BACKUP" ]; then
        print_status 1 "No backup file found in $BACKUP_DIR"
    else
        print_status 0 "Backup file created: $(basename $LATEST_BACKUP)"

        # Check backup size
        SIZE=$(du -h "$LATEST_BACKUP" | cut -f1)
        print_info "Backup size: $SIZE"

        # Check if backup is readable
        if [ -r "$LATEST_BACKUP" ]; then
            print_status 0 "Backup file is readable"
        else
            print_warning "Backup file may not be readable"
        fi
    fi
else
    print_status 1 "Backup directory not found: $BACKUP_DIR"
fi
echo ""

# Step 6: List all backups
echo "Step 6: Listing all available backups..."
php artisan backup:list
if [ $? -eq 0 ]; then
    print_status 0 "Backup list command executed successfully"
else
    print_warning "Backup list command failed"
fi
echo ""

# Step 7: Monitor backup health
echo "Step 7: Checking backup health..."
php artisan backup:monitor
if [ $? -eq 0 ]; then
    print_status 0 "Backup health check passed"
else
    print_warning "Backup health check reported issues"
fi
echo ""

# Step 8: Check scheduled tasks
echo "Step 8: Verifying scheduled backup tasks..."
if grep -q "backup:run" routes/console.php; then
    print_status 0 "Backup schedule found in console routes"
else
    print_warning "Backup schedule not found in console routes"
fi
echo ""

# Step 9: Summary
echo "========================================="
echo "Test Summary"
echo "========================================="

if [ -n "$LATEST_BACKUP" ]; then
    echo "Latest backup: $(basename $LATEST_BACKUP)"
    echo "Location: $LATEST_BACKUP"
    echo "Size: $SIZE"
    echo ""
fi

echo "Backup configuration: config/backup.php"
echo "Backup directory: $BACKUP_DIR"
echo "Backup command: php artisan backup:list"
echo ""

echo "========================================="
echo -e "${GREEN}Backup system test completed successfully!${NC}"
echo "========================================="
echo ""

echo "Next steps:"
echo "1. Review backup files in: $BACKUP_DIR"
echo "2. Test restore procedure (see documentation/BACKUP_RESTORE.md)"
echo "3. Configure remote storage (S3) for production"
echo "4. Set up email notifications in .env"
echo ""

exit 0
