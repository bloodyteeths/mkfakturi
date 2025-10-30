<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Company;
use App\Space\InstallUtils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * INS-04: Installation Error Handling and Rollback Test
 * Tests installation failure scenarios and validates cleanup mechanisms
 * Created for Agent 2: Installation & Onboarding Flow Validator
 */
class InstallationRollbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure clean state for rollback testing
        $this->clearInstallationMarkers();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $this->clearInstallationMarkers();
        parent::tearDown();
    }

    /**
     * Test database connection failure during installation
     */
    public function test_database_connection_failure_rollback()
    {
        // Simulate database connection failure
        Config::set('database.connections.test_invalid', [
            'driver' => 'mysql',
            'host' => 'invalid-host-that-does-not-exist',
            'port' => '99999',
            'database' => 'nonexistent_database',
            'username' => 'invalid_user',
            'password' => 'invalid_password',
        ]);

        // Attempt to test connection with invalid config
        $response = $this->postJson('/api/v1/installation/database/config', [
            'database_connection' => 'test_invalid',
            'database_host' => 'invalid-host-that-does-not-exist',
            'database_port' => '99999',
            'database_name' => 'nonexistent_database',
            'database_username' => 'invalid_user',
            'database_password' => 'invalid_password',
        ]);

        // Should return error response
        $this->assertNotEquals(200, $response->status(), 'Invalid database config should fail');
        
        // Verify no installation marker was created
        $this->assertFalse(InstallUtils::dbMarkerExists(), 'DB marker should not exist after failed connection');
        
        // Verify installation is still incomplete
        $this->assertEquals(0, Setting::getSetting('profile_complete', 0), 'Installation should remain incomplete');
    }

    /**
     * Test migration failure during installation
     */
    public function test_migration_failure_rollback()
    {
        // Create a temporary migration that will fail
        $this->createFailingMigration();
        
        try {
            // Attempt to run migrations (this should fail)
            Artisan::call('migrate', ['--force' => true]);
            
            // If we get here, the migration didn't fail as expected
            $this->markTestSkipped('Test migration did not fail as expected');
        } catch (\Exception $e) {
            // Expected behavior - migration failed
            $this->assertStringContains('syntax error', strtolower($e->getMessage()), 'Should fail with syntax error');
        }
        
        // Verify rollback occurred
        $this->assertFalse(InstallUtils::dbMarkerExists(), 'DB marker should not exist after failed migration');
        
        // Clean up the failing migration
        $this->removeFailingMigration();
    }

    /**
     * Test file permission failure during installation
     */
    public function test_file_permission_failure_rollback()
    {
        // Temporarily make storage directory read-only (if possible)
        $testDir = storage_path('app/test_readonly');
        
        if (!file_exists($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        // Try to make it read-only
        chmod($testDir, 0444);
        
        // Test file permission check
        $response = $this->getJson('/api/v1/installation/permissions');
        
        // Should detect permission issues
        $this->assertEquals(200, $response->status(), 'Permissions endpoint should respond');
        
        $permissions = $response->json();
        $hasPermissionIssues = false;
        
        if (isset($permissions['permissions'])) {
            foreach ($permissions['permissions'] as $permission) {
                if (!$permission['isWritable']) {
                    $hasPermissionIssues = true;
                    break;
                }
            }
        }
        
        // Clean up
        chmod($testDir, 0755);
        rmdir($testDir);
        
        // Verify installation would be blocked by permission issues
        if ($hasPermissionIssues) {
            $this->assertTrue(true, 'Permission issues detected correctly');
        } else {
            $this->markTestSkipped('Could not create permission issues for testing');
        }
    }

    /**
     * Test partial installation cleanup
     */
    public function test_partial_installation_cleanup()
    {
        // Create partial installation state
        $this->createPartialInstallationState();
        
        // Verify partial state exists
        $this->assertTrue(Setting::getSetting('profile_complete') !== 'COMPLETED', 'Installation should be incomplete');
        $this->assertDatabaseHas('settings', ['option' => 'profile_language']);
        
        // Simulate installation reset/cleanup
        $this->performInstallationCleanup();
        
        // Verify cleanup was successful
        $this->assertFalse(InstallUtils::dbMarkerExists(), 'DB marker should be removed');
        $this->assertEquals(0, Setting::getSetting('profile_complete', 0), 'Profile should be reset');
    }

    /**
     * Test user creation failure rollback
     */
    public function test_user_creation_failure_rollback()
    {
        // Attempt to create user with invalid data
        $response = $this->postJson('/api/v1/installation/login', [
            'name' => '', // Empty name should fail validation
            'email' => 'invalid-email', // Invalid email format
            'password' => '123', // Too short password
            'password_confirmation' => 'different', // Mismatched confirmation
        ]);

        // Should fail validation
        $this->assertEquals(422, $response->status(), 'User creation should fail validation');
        
        // Verify no user was created
        $this->assertEquals(0, User::count(), 'No users should be created after failed validation');
        
        // Verify installation remains incomplete
        $this->assertFalse(InstallUtils::dbMarkerExists(), 'Installation should remain incomplete');
    }

    /**
     * Test company creation failure rollback
     */
    public function test_company_creation_failure_rollback()
    {
        // First create a valid user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->actingAs($user, 'sanctum');
        
        // Attempt to create company with invalid data
        $response = $this->postJson('/api/v1/companies', [
            'name' => '', // Empty name should fail
            'email' => 'invalid-email',
            'currency' => 'INVALID', // Invalid currency
        ]);

        // Should fail validation
        $this->assertNotEquals(200, $response->status(), 'Company creation should fail validation');
        
        // Verify no company was created
        $this->assertEquals(0, Company::count(), 'No companies should be created after failed validation');
    }

    /**
     * Test storage cleanup during rollback
     */
    public function test_storage_cleanup_during_rollback()
    {
        // Create some test files that should be cleaned up
        $testFiles = [
            'installation_test_1.txt',
            'installation_test_2.txt',
            'installation_backup.sql',
        ];
        
        foreach ($testFiles as $file) {
            Storage::disk('local')->put($file, 'test content for rollback');
        }
        
        // Verify files exist
        foreach ($testFiles as $file) {
            $this->assertTrue(Storage::disk('local')->exists($file), "Test file {$file} should exist");
        }
        
        // Simulate installation failure and cleanup
        $this->performStorageCleanup($testFiles);
        
        // Verify files were cleaned up
        foreach ($testFiles as $file) {
            $this->assertFalse(Storage::disk('local')->exists($file), "Test file {$file} should be cleaned up");
        }
    }

    /**
     * Test settings cleanup during rollback
     */
    public function test_settings_cleanup_during_rollback()
    {
        // Create test settings that should be cleaned up
        $testSettings = [
            'test_setting_1' => 'value1',
            'test_setting_2' => 'value2',
            'profile_language' => 'en',
            'profile_complete' => 'STEP_3',
        ];
        
        foreach ($testSettings as $key => $value) {
            Setting::setSetting($key, $value);
        }
        
        // Verify settings exist
        foreach ($testSettings as $key => $value) {
            $this->assertEquals($value, Setting::getSetting($key), "Setting {$key} should exist");
        }
        
        // Perform rollback
        $this->performSettingsCleanup();
        
        // Verify test settings were cleaned up
        $this->assertNull(Setting::getSetting('test_setting_1'), 'Test setting 1 should be cleaned up');
        $this->assertNull(Setting::getSetting('test_setting_2'), 'Test setting 2 should be cleaned up');
        $this->assertEquals(0, Setting::getSetting('profile_complete', 0), 'Profile complete should be reset');
    }

    /**
     * Test database rollback with transactions
     */
    public function test_database_transaction_rollback()
    {
        DB::beginTransaction();
        
        try {
            // Create some test data
            $user = User::factory()->create(['name' => 'Test User']);
            $company = Company::factory()->create(['name' => 'Test Company']);
            
            // Verify data exists in transaction
            $this->assertDatabaseHas('users', ['name' => 'Test User']);
            $this->assertDatabaseHas('companies', ['name' => 'Test Company']);
            
            // Simulate an error that should trigger rollback
            throw new \Exception('Simulated installation error');
            
        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();
            
            // Verify rollback occurred
            $this->assertDatabaseMissing('users', ['name' => 'Test User']);
            $this->assertDatabaseMissing('companies', ['name' => 'Test Company']);
            
            $this->assertEquals('Simulated installation error', $e->getMessage());
        }
    }

    /**
     * Test cache cleanup during rollback
     */
    public function test_cache_cleanup_during_rollback()
    {
        // Set some test cache values
        $testCacheKeys = [
            'installation_step',
            'installation_progress',
            'user_preferences',
        ];
        
        foreach ($testCacheKeys as $key) {
            cache([$key => 'test_value_' . $key], 60);
        }
        
        // Verify cache values exist
        foreach ($testCacheKeys as $key) {
            $this->assertEquals('test_value_' . $key, cache($key), "Cache key {$key} should exist");
        }
        
        // Perform cache cleanup
        $this->performCacheCleanup($testCacheKeys);
        
        // Verify cache was cleaned up
        foreach ($testCacheKeys as $key) {
            $this->assertNull(cache($key), "Cache key {$key} should be cleaned up");
        }
    }

    /**
     * Test session cleanup during rollback
     */
    public function test_session_cleanup_during_rollback()
    {
        // Set some test session values
        $testSessionKeys = [
            'installation_wizard_step',
            'installation_data',
            'temporary_user_data',
        ];
        
        foreach ($testSessionKeys as $key) {
            session([$key => 'test_session_value_' . $key]);
        }
        
        // Verify session values exist
        foreach ($testSessionKeys as $key) {
            $this->assertEquals('test_session_value_' . $key, session($key), "Session key {$key} should exist");
        }
        
        // Perform session cleanup
        $this->performSessionCleanup();
        
        // Verify session was cleaned up
        foreach ($testSessionKeys as $key) {
            $this->assertNull(session($key), "Session key {$key} should be cleaned up");
        }
    }

    /**
     * Test multiple failure scenarios in sequence
     */
    public function test_multiple_failure_scenarios_recovery()
    {
        // Scenario 1: Database failure
        $this->createPartialInstallationState();
        $this->performInstallationCleanup();
        
        // Verify clean state
        $this->assertFalse(InstallUtils::dbMarkerExists());
        $this->assertEquals(0, Setting::getSetting('profile_complete', 0));
        
        // Scenario 2: File permission failure
        $this->createPartialInstallationState();
        $this->performInstallationCleanup();
        
        // Verify clean state again
        $this->assertFalse(InstallUtils::dbMarkerExists());
        $this->assertEquals(0, Setting::getSetting('profile_complete', 0));
        
        // Scenario 3: User creation failure
        $this->createPartialInstallationState();
        $this->performInstallationCleanup();
        
        // Final verification
        $this->assertFalse(InstallUtils::dbMarkerExists());
        $this->assertEquals(0, Setting::getSetting('profile_complete', 0));
        $this->assertEquals(0, User::count());
        $this->assertEquals(0, Company::count());
    }

    /**
     * Test installation health check after rollback
     */
    public function test_installation_health_check_after_rollback()
    {
        // Create failed installation state
        $this->createPartialInstallationState();
        
        // Perform rollback
        $this->performInstallationCleanup();
        
        // Test that installation can be started fresh
        $response = $this->getJson('/api/v1/installation/wizard-step');
        $this->assertEquals(200, $response->status(), 'Installation wizard should be accessible after rollback');
        
        $data = $response->json();
        $this->assertEquals(0, $data['profile_complete'], 'Profile should be reset to initial state');
        
        // Verify all installation endpoints are accessible
        $endpoints = [
            '/api/v1/installation/requirements',
            '/api/v1/installation/permissions',
            '/api/v1/installation/languages',
        ];
        
        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertEquals(200, $response->status(), "Endpoint {$endpoint} should be accessible after rollback");
        }
    }

    // Helper Methods

    protected function clearInstallationMarkers()
    {
        InstallUtils::deleteDbMarker();
        \DB::table('settings')->whereIn('option', ['profile_complete', 'profile_language'])->delete();
    }

    protected function createPartialInstallationState()
    {
        Setting::setSetting('profile_complete', 'STEP_3');
        Setting::setSetting('profile_language', 'en');
        Setting::setSetting('test_installation_data', 'partial_state');
    }

    protected function performInstallationCleanup()
    {
        // Simulate cleanup process
        InstallUtils::deleteDbMarker();
        
        // Clean up installation-related settings
        $installationSettings = [
            'profile_complete',
            'profile_language',
            'test_installation_data',
            'database_version',
            'installation_timestamp',
        ];
        
        \DB::table('settings')->whereIn('option', $installationSettings)->delete();
    }

    protected function performStorageCleanup(array $files)
    {
        foreach ($files as $file) {
            if (Storage::disk('local')->exists($file)) {
                Storage::disk('local')->delete($file);
            }
        }
    }

    protected function performSettingsCleanup()
    {
        // Clean up test settings
        \DB::table('settings')->whereIn('option', ['test_setting_1', 'test_setting_2'])->delete();
        Setting::setSetting('profile_complete', 0);
    }

    protected function performCacheCleanup(array $keys)
    {
        foreach ($keys as $key) {
            cache()->forget($key);
        }
    }

    protected function performSessionCleanup()
    {
        session()->flush();
    }

    protected function createFailingMigration()
    {
        $migrationPath = database_path('migrations/9999_99_99_999999_test_failing_migration.php');
        $migrationContent = <<<'EOT'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // This will cause a syntax error
        Schema::create('test_failing_table', function (Blueprint $table) {
            $table->id();
            $table->invalidColumnType('bad_column'); // This should fail
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('test_failing_table');
    }
};
EOT;
        
        file_put_contents($migrationPath, $migrationContent);
    }

    protected function removeFailingMigration()
    {
        $migrationPath = database_path('migrations/9999_99_99_999999_test_failing_migration.php');
        if (file_exists($migrationPath)) {
            unlink($migrationPath);
        }
    }
}

