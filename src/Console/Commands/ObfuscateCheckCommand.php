<?php

namespace AlexandruFlorea\LaravelSourceObfuscator\Console\Commands;

use Illuminate\Console\Command;
use AlexandruFlorea\LaravelSourceObfuscator\Services\ObfuscationService;
use AlexandruFlorea\LaravelSourceObfuscator\Utilities\FileScanner;
use AlexandruFlorea\LaravelSourceObfuscator\Utilities\PathResolver;

class ObfuscateCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obfuscate:check
                            {--show-files : Display list of files that will be obfuscated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check PHPBolt configuration and preview files to be obfuscated';

    protected ObfuscationService $obfuscationService;

    /**
     * Create a new command instance.
     *
     * @param ObfuscationService $obfuscationService
     * @return void
     */
    public function __construct(ObfuscationService $obfuscationService)
    {
        parent::__construct();
        $this->obfuscationService = $obfuscationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('PHPBolt Configuration Check');
        $this->newLine();

        // Check PHPBolt installation
        $this->checkPhpBolt();
        $this->newLine();

        // Display configuration
        $this->displayConfiguration();
        $this->newLine();

        // Scan and display file statistics
        $this->displayFileStatistics();
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Check PHPBolt installation.
     *
     * @return void
     */
    protected function checkPhpBolt(): void
    {
        $config = config('obfuscator');

        $this->info('PHPBolt Extension Check:');
        $this->line('  Extension Name: ' . $config['extension_name']);
        $this->line('  Extension Loaded: ' . (extension_loaded($config['extension_name']) ? 'Yes' : 'No'));
        $this->line('  bolt_encrypt() Available: ' . (function_exists('bolt_encrypt') ? 'Yes' : 'No'));

        if ($this->obfuscationService->validatePhpBolt()) {
            $this->info('  Status: ✓ PHPBolt extension is properly loaded');
        } else {
            $this->error('  Status: ✗ PHPBolt extension is not loaded');
            $this->warn('  Please install bolt.so extension and enable it in php.ini');
            $this->warn('  Check: php -m | grep bolt');
        }
    }

    /**
     * Display configuration summary.
     *
     * @return void
     */
    protected function displayConfiguration(): void
    {
        $config = config('obfuscator');

        $this->info('Configuration Summary:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Extension Name', $config['extension_name']],
                ['Encryption Key', !empty($config['encryption_key']) ? 'Configured' : 'Will be generated'],
                ['Key Length', $config['key_length']],
                ['Output Directory', $config['output_dir']],
                ['Backup Enabled', $config['backup']['enabled'] ? 'Yes' : 'No'],
                ['Backup Path', $config['backup']['path']],
                ['Keep Last Backups', $config['backup']['keep_last']],
                ['Copy Non-PHP Files', $config['copy_non_php_files'] ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();
        $this->info('Include Paths:');
        foreach ($config['include_paths'] as $path) {
            $this->line("  • {$path}");
        }

        $this->newLine();
        $this->info('Exclude Paths:');
        foreach (array_slice($config['exclude_paths'], 0, 10) as $path) {
            $this->line("  • {$path}");
        }
        if (count($config['exclude_paths']) > 10) {
            $this->line("  ... and " . (count($config['exclude_paths']) - 10) . " more");
        }

        $this->newLine();
        $this->info('Obfuscation Options:');
        $options = $config['obfuscation'];
        foreach ($options as $key => $value) {
            $status = $value ? '✓' : '✗';
            $label = ucwords(str_replace('_', ' ', $key));
            $this->line("  {$status} {$label}");
        }
    }

    /**
     * Display file statistics.
     *
     * @return void
     */
    protected function displayFileStatistics(): void
    {
        $config = config('obfuscator');
        $pathResolver = new PathResolver(base_path());
        $fileScanner = new FileScanner($config);

        $includePaths = array_map(
            fn($path) => $pathResolver->resolve($path),
            $config['include_paths']
        );

        $stats = $fileScanner->getStatistics($includePaths);

        $this->info('File Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Files', $stats['total_files']],
                ['PHP Files', $stats['php_files']],
                ['Other Files', $stats['other_files']],
                ['Total Size', $this->formatBytes($stats['total_size'])],
            ]
        );

        if ($this->option('show-files')) {
            $this->newLine();
            $this->info('Files to be obfuscated:');
            $files = $fileScanner->scan($includePaths);
            foreach ($files as $file) {
                $relativePath = $pathResolver->getRelativePath($file);
                $this->line("  • {$relativePath}");
            }
        } else {
            $this->newLine();
            $this->comment('Use --show-files to see the complete list of files.');
        }
    }

    /**
     * Format bytes to human-readable format.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

