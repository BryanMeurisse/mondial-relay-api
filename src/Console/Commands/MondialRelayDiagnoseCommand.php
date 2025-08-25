<?php

namespace Bmwsly\MondialRelayApi\Console\Commands;

use Bmwsly\MondialRelayApi\Clients\MondialRelayHybridClient;
use Bmwsly\MondialRelayApi\Config\MondialRelayConfig;
use Illuminate\Console\Command;

class MondialRelayDiagnoseCommand extends Command
{
    protected $signature = 'mondialrelay:diagnose 
                           {--test-api : Test API connectivity}
                           {--show-config : Show configuration details}
                           {--validate-only : Only validate configuration}';

    protected $description = 'Diagnose Mondial Relay API configuration and connectivity';

    public function handle()
    {
        $this->info('🔍 Mondial Relay API Diagnostics');
        $this->line('');

        // Load configuration
        $config = MondialRelayConfig::fromLaravelConfig(config('mondialrelay'));

        // Always show basic info
        $this->showBasicInfo($config);

        if ($this->option('show-config')) {
            $this->showDetailedConfig($config);
        }

        if ($this->option('validate-only')) {
            return $this->validateConfiguration($config);
        }

        // Validate configuration
        if (!$this->validateConfiguration($config)) {
            return 1;
        }

        if ($this->option('test-api')) {
            return $this->testApiConnectivity();
        }

        $this->info('✅ Basic diagnostics completed successfully');
        $this->line('');
        $this->comment('Use --test-api to test API connectivity');
        $this->comment('Use --show-config to see detailed configuration');

        return 0;
    }

    private function showBasicInfo(MondialRelayConfig $config): void
    {
        $summary = $config->getSummary();

        $this->table(['Setting', 'Value'], [
            ['Enseigne', $summary['enseigne']],
            ['Private Key', $summary['private_key']],
            ['Test Mode', $summary['test_mode']],
            ['API V2 Enabled', $summary['api_v2_enabled']],
            ['Debug Enabled', $summary['debug_enabled']],
            ['Configuration Valid', $summary['is_valid']],
            ['Validation Errors', $summary['validation_errors']],
        ]);

        $this->line('');
    }

    private function showDetailedConfig(MondialRelayConfig $config): void
    {
        $this->info('📋 Detailed Configuration');
        $this->line('');

        // Environment config
        $envConfig = $config->getEnvironmentConfig();
        $this->comment('Environment Configuration:');
        foreach ($envConfig as $key => $value) {
            $this->line("  {$key}: ".(is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }
        $this->line('');

        // Security config
        $securityConfig = $config->getSecurityConfig();
        $this->comment('Security Configuration:');
        foreach ($securityConfig as $key => $value) {
            if (is_array($value)) {
                $value = empty($value) ? 'none' : implode(', ', $value);
            }
            $this->line("  {$key}: ".(is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }
        $this->line('');

        // Debug config
        $debugConfig = $config->getDebugConfig();
        $this->comment('Debug Configuration:');
        foreach ($debugConfig as $key => $value) {
            $this->line("  {$key}: ".(is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }
        $this->line('');
    }

    private function validateConfiguration(MondialRelayConfig $config): bool
    {
        $this->info('🔧 Validating Configuration');
        $this->line('');

        if ($config->isValid()) {
            $this->info('✅ Configuration is valid');

            return true;
        }

        $this->error('❌ Configuration validation failed:');
        foreach ($config->getValidationErrors() as $error) {
            $this->line("  • {$error}");
        }
        $this->line('');

        $this->comment('Please check your .env file and configuration.');

        return false;
    }

    private function testApiConnectivity(): int
    {
        $this->info('🌐 Testing API Connectivity');
        $this->line('');

        try {
            $client = app(MondialRelayHybridClient::class);
            $results = $client->testConnectivity();

            // SOAP API test
            $this->comment('SOAP API (V1):');
            if ($results['soap']['available']) {
                $status = $results['soap']['status'] ?? 'UNKNOWN';
                if ($status === 'OK') {
                    $this->info('  ✅ SOAP API is working');
                } else {
                    $this->error('  ❌ SOAP API failed: '.($results['soap']['error'] ?? 'Unknown error'));
                }
            } else {
                $this->warn('  ⚠️  SOAP API not available');
            }

            // REST API test
            $this->comment('REST API (V2):');
            if ($results['rest']['available']) {
                $status = $results['rest']['status'] ?? 'UNKNOWN';
                if ($status === 'OK') {
                    $this->info('  ✅ REST API is working');
                } else {
                    $this->error('  ❌ REST API failed: '.($results['rest']['error'] ?? 'Unknown error'));
                }
            } else {
                $this->warn('  ⚠️  REST API not configured or not available');
            }

            $this->line('');

            // API status
            $apiStatus = $client->getApiStatus();
            $this->comment('API Status:');
            foreach ($apiStatus as $key => $value) {
                $this->line("  {$key}: ".(is_bool($value) ? ($value ? 'true' : 'false') : $value));
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to test API connectivity: '.$e->getMessage());

            return 1;
        }
    }
}
