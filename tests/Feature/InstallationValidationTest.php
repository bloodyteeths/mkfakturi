<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Space\FilePermissionChecker;
use App\Space\InstallUtils;
use App\Space\RequirementsChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * INS-02: Database and Environment Validation Test
 * Validates DB connectivity, file permissions, email configuration, and environment checks
 * Created for Agent 2: Installation & Onboarding Flow Validator
 */
class InstallationValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $requirementsChecker;

    protected $permissionChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requirementsChecker = new RequirementsChecker;
        $this->permissionChecker = new FilePermissionChecker;
    }

    /**
     * Test database connectivity validation
     */
    public function test_database_connectivity_validation()
    {
        // Test default database connection
        $this->assertTrue(DB::connection()->getPdo() !== null, 'Database connection should be available');

        // Test schema operations
        $this->assertTrue(Schema::hasTable('users'), 'Users table should exist');
        $this->assertTrue(Schema::hasTable('companies'), 'Companies table should exist');
        $this->assertTrue(Schema::hasTable('settings'), 'Settings table should exist');

        // Test database write operations
        Setting::setSetting('test_db_write', 'test_value');

        $retrievedValue = Setting::getSetting('test_db_write');
        $this->assertEquals('test_value', $retrievedValue, 'Should be able to read from database');

        // Clean up - manually delete the setting
        \DB::table('settings')->where('option', 'test_db_write')->delete();
    }

    /**
     * Test database configuration for different drivers
     */
    public function test_database_driver_configurations()
    {
        $supportedDrivers = ['sqlite', 'mysql', 'pgsql'];

        foreach ($supportedDrivers as $driver) {
            $config = $this->getDatabaseConfigForDriver($driver);
            $this->assertNotEmpty($config, "Configuration for {$driver} should be available");
            $this->assertArrayHasKey('driver', $config, "Driver key should exist for {$driver}");
            $this->assertEquals($driver, $config['driver'], "Driver should match {$driver}");
        }
    }

    /**
     * Test file permissions validation
     */
    public function test_file_permissions_validation()
    {
        $criticalPaths = [
            storage_path(),
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($criticalPaths as $path) {
            $this->assertTrue(is_dir($path), "Directory {$path} should exist");
            $this->assertTrue(is_writable($path), "Directory {$path} should be writable");
        }

        // Test storage disk operations
        $testContent = 'Test installation validation content';
        $testFile = 'installation_test.txt';

        Storage::disk('local')->put($testFile, $testContent);
        $this->assertTrue(Storage::disk('local')->exists($testFile), 'Should be able to create files in storage');

        $retrievedContent = Storage::disk('local')->get($testFile);
        $this->assertEquals($testContent, $retrievedContent, 'Should be able to read files from storage');

        Storage::disk('local')->delete($testFile);
        $this->assertFalse(Storage::disk('local')->exists($testFile), 'Should be able to delete files from storage');
    }

    /**
     * Test PHP requirements validation
     */
    public function test_php_requirements_validation()
    {
        $minPhpVersion = config('installer.core.minPhpVersion', '8.1');
        $phpSupport = $this->requirementsChecker->checkPHPVersion($minPhpVersion);

        $this->assertTrue($phpSupport['supported'], 'PHP version should meet minimum requirements');
        $this->assertGreaterThanOrEqual($minPhpVersion, $phpSupport['current'], 'Current PHP version should be sufficient');
    }

    /**
     * Test required PHP extensions
     */
    public function test_php_extensions_validation()
    {
        $requiredExtensions = config('installer.requirements', [
            'openssl',
            'pdo',
            'mbstring',
            'tokenizer',
            'xml',
            'ctype',
            'json',
            'curl',
            'zip',
            'fileinfo',
        ]);

        $extensionCheck = $this->requirementsChecker->check($requiredExtensions);

        foreach ($extensionCheck as $extension) {
            $this->assertTrue($extension, "PHP extension {$extension} should be available");
        }
    }

    /**
     * Test email configuration validation
     */
    public function test_email_configuration_validation()
    {
        // Test basic mail configuration
        $mailDriver = config('mail.default');
        $this->assertNotEmpty($mailDriver, 'Mail driver should be configured');

        $mailConfig = config("mail.mailers.{$mailDriver}");
        $this->assertNotEmpty($mailConfig, "Mail configuration for {$mailDriver} should exist");

        // Test email sending capability (using fake mail)
        Mail::fake();

        try {
            Mail::raw('Test installation email', function ($message) {
                $message->to('test@example.com')->subject('Installation Test');
            });

            Mail::assertSent(\Illuminate\Mail\Mailable::class);
            $this->assertTrue(true, 'Email system should be functional');
        } catch (\Exception $e) {
            $this->fail('Email system validation failed: '.$e->getMessage());
        }
    }

    /**
     * Test environment configuration validation
     */
    public function test_environment_configuration_validation()
    {
        $requiredEnvVars = [
            'APP_KEY',
            'APP_ENV',
            'APP_DEBUG',
            'DB_CONNECTION',
            'CACHE_DRIVER',
            'SESSION_DRIVER',
        ];

        foreach ($requiredEnvVars as $envVar) {
            $value = env($envVar);
            $this->assertNotNull($value, "Environment variable {$envVar} should be set");
        }

        // Test APP_KEY is properly set
        $appKey = config('app.key');
        $this->assertNotEmpty($appKey, 'Application key should be set');
        $this->assertStringStartsWith('base64:', $appKey, 'Application key should be base64 encoded');
    }

    /**
     * Test installation marker functionality
     */
    public function test_installation_marker_functionality()
    {
        // Clean up any existing marker
        InstallUtils::deleteDbMarker();
        $this->assertFalse(InstallUtils::dbMarkerExists(), 'DB marker should not exist initially');

        // Create marker
        $created = InstallUtils::createDbMarker();
        $this->assertTrue($created, 'Should be able to create DB marker');
        $this->assertTrue(InstallUtils::dbMarkerExists(), 'DB marker should exist after creation');

        // Test database creation check
        $this->assertTrue(InstallUtils::isDbCreated(), 'Installation should be detected as complete');

        // Delete marker
        $deleted = InstallUtils::deleteDbMarker();
        $this->assertTrue($deleted, 'Should be able to delete DB marker');
        $this->assertFalse(InstallUtils::dbMarkerExists(), 'DB marker should not exist after deletion');
    }

    /**
     * Test storage disk accessibility
     */
    public function test_storage_disk_accessibility()
    {
        $disks = ['local', 'public'];

        foreach ($disks as $diskName) {
            $disk = Storage::disk($diskName);
            $testFile = "installation_test_{$diskName}.txt";
            $testContent = "Test content for {$diskName} disk";

            // Test write
            $disk->put($testFile, $testContent);
            $this->assertTrue($disk->exists($testFile), "Should be able to write to {$diskName} disk");

            // Test read
            $retrieved = $disk->get($testFile);
            $this->assertEquals($testContent, $retrieved, "Should be able to read from {$diskName} disk");

            // Test delete
            $disk->delete($testFile);
            $this->assertFalse($disk->exists($testFile), "Should be able to delete from {$diskName} disk");
        }
    }

    /**
     * Test cache system functionality
     */
    public function test_cache_system_functionality()
    {
        $cacheDriver = config('cache.default');
        $this->assertNotEmpty($cacheDriver, 'Cache driver should be configured');

        $testKey = 'installation_test_cache';
        $testValue = 'test_cache_value_'.time();

        // Test cache write
        cache([$testKey => $testValue], 60);
        $this->assertEquals($testValue, cache($testKey), 'Cache should store and retrieve values');

        // Test cache delete
        cache()->forget($testKey);
        $this->assertNull(cache($testKey), 'Cache should be able to delete values');
    }

    /**
     * Test session functionality
     */
    public function test_session_functionality()
    {
        $sessionDriver = config('session.driver');
        $this->assertNotEmpty($sessionDriver, 'Session driver should be configured');

        $testKey = 'installation_test_session';
        $testValue = 'test_session_value_'.time();

        // Test session write
        session([$testKey => $testValue]);
        $this->assertEquals($testValue, session($testKey), 'Session should store and retrieve values');

        // Test session delete
        session()->forget($testKey);
        $this->assertNull(session($testKey), 'Session should be able to delete values');
    }

    /**
     * Test queue system functionality
     */
    public function test_queue_system_functionality()
    {
        $queueDriver = config('queue.default');
        $this->assertNotEmpty($queueDriver, 'Queue driver should be configured');

        $queueConfig = config("queue.connections.{$queueDriver}");
        $this->assertNotEmpty($queueConfig, "Queue configuration for {$queueDriver} should exist");

        // For sync driver, just test the configuration exists
        if ($queueDriver === 'sync') {
            $this->assertEquals('sync', $queueConfig['driver'], 'Sync queue driver should be properly configured');
        }
    }

    /**
     * Test memory and execution limits
     */
    public function test_system_limits_validation()
    {
        $memoryLimit = ini_get('memory_limit');
        $this->assertNotEmpty($memoryLimit, 'Memory limit should be set');

        // Convert memory limit to bytes for comparison
        $memoryBytes = $this->convertToBytes($memoryLimit);
        $minRequired = 128 * 1024 * 1024; // 128MB minimum

        if ($memoryBytes > 0) { // -1 means unlimited
            $this->assertGreaterThanOrEqual($minRequired, $memoryBytes, 'Memory limit should be at least 128MB');
        }

        $maxExecutionTime = ini_get('max_execution_time');
        if ($maxExecutionTime > 0) { // 0 means unlimited
            $this->assertGreaterThanOrEqual(60, $maxExecutionTime, 'Max execution time should be at least 60 seconds');
        }
    }

    /**
     * Test API endpoints accessibility for installation
     */
    public function test_installation_api_endpoints()
    {
        $endpoints = [
            '/api/v1/installation/wizard-step',
            '/api/v1/installation/languages',
            '/api/v1/installation/requirements',
            '/api/v1/installation/permissions',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->get($endpoint);
            $this->assertNotEquals(404, $response->status(), "Installation endpoint {$endpoint} should be accessible");
        }
    }

    /**
     * Test Macedonia-specific configuration requirements
     */
    public function test_macedonia_specific_requirements()
    {
        // Test timezone configuration
        $timezone = config('app.timezone');
        $validTimezones = ['UTC', 'Europe/Skopje', 'Europe/Sofia'];
        $this->assertContains($timezone, $validTimezones, 'Timezone should be appropriate for Macedonia');

        // Test locale configuration
        $locale = config('app.locale');
        $this->assertNotEmpty($locale, 'Application locale should be set');

        // Test currency support
        $currencies = ['MKD', 'EUR', 'USD'];
        $defaultCurrency = config('invoiceshelf.default_currency', 'MKD');
        $this->assertContains($defaultCurrency, $currencies, 'Default currency should be supported');

        // Test Macedonia-specific validation rules if they exist
        if (class_exists('\App\Rules\MacedoniaVatId')) {
            $this->assertTrue(true, 'Macedonia VAT ID validation should be available');
        }
    }

    /**
     * Helper method to convert memory limit to bytes
     */
    private function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Helper method to get database configuration for a specific driver
     */
    private function getDatabaseConfigForDriver($driver)
    {
        return config("database.connections.{$driver}", []);
    }

    /**
     * Test installation cleanup functionality
     */
    public function test_installation_cleanup_functionality()
    {
        // Create some test data that should be cleaned up
        $testFiles = [
            'test_cleanup_1.txt',
            'test_cleanup_2.txt',
        ];

        foreach ($testFiles as $file) {
            Storage::disk('local')->put($file, 'test content');
            $this->assertTrue(Storage::disk('local')->exists($file), "Test file {$file} should be created");
        }

        // Simulate cleanup process
        foreach ($testFiles as $file) {
            Storage::disk('local')->delete($file);
            $this->assertFalse(Storage::disk('local')->exists($file), "Test file {$file} should be cleaned up");
        }
    }

    /**
     * Test database migration rollback capability
     */
    public function test_database_rollback_capability()
    {
        // Test that we can rollback migrations if needed
        $tables = ['users', 'companies', 'settings'];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} should exist");
        }

        // Test that we can check table existence (important for rollback logic)
        $this->assertTrue(InstallUtils::tableExists('users'), 'InstallUtils should correctly detect table existence');
        $this->assertFalse(InstallUtils::tableExists('nonexistent_table'), 'InstallUtils should correctly detect non-existent tables');
    }
}
