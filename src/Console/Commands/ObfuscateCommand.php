<?php

namespace Aflorea4\LaravelSourceObfuscator\Console\Commands;

use Illuminate\Console\Command;
use Aflorea4\LaravelSourceObfuscator\Services\ObfuscationService;

class ObfuscateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obfuscate:run 
                            {--source=* : Override source paths to obfuscate (comma-separated or multiple --source options)}
                            {--destination= : Override output directory}
                            {--dry-run : Run without actually obfuscating files}
                            {--skip-backup : Skip creating a backup}
                            {--force : Force obfuscation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obfuscate Laravel source code using PHPBolt';

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
        $this->info('Laravel Source Code Obfuscator');
        $this->newLine();

        // Check PHPBolt installation
        if (!$this->obfuscationService->validatePhpBolt()) {
            $this->error('PHPBolt is not properly installed or configured.');
            $this->error('Please check your configuration in config/obfuscator.php');
            return self::FAILURE;
        }

        // Show configuration summary
        $this->displayConfiguration();

        // Confirm before proceeding (unless --force is used)
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('Do you want to proceed with obfuscation?', true)) {
                $this->info('Obfuscation cancelled.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('Starting obfuscation process...');
        $this->newLine();

        // Run obfuscation
        try {
            $progressBar = $this->output->createProgressBar();
            $progressBar->start();

            // Build options with command-line overrides
            $options = [
                'dry_run' => $this->option('dry-run') ?? false,
                'skip_backup' => $this->option('skip-backup') ?? false,
            ];

            // Override source paths if provided
            if ($this->option('source')) {
                $options['source_override'] = $this->option('source');
            }

            // Override destination if provided
            if ($this->option('destination')) {
                $options['destination_override'] = $this->option('destination');
            }

            $result = $this->obfuscationService->obfuscate($options);

            $progressBar->finish();
            $this->newLine(2);

        // Display results
        $this->displayResults($result);

        // Display encryption key
        if ($result['success'] && isset($result['encryption_key'])) {
            $this->newLine();
            $this->warn('IMPORTANT: Save your encryption key!');
            $this->info('Encryption Key: ' . $result['encryption_key']);
            $this->comment('You will need this key to decrypt the files at runtime.');
            $this->comment('Set PHP_BOLT_KEY constant in your production environment.');
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
        } catch (\Exception $e) {
            $this->error('Obfuscation failed: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            return self::FAILURE;
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

        $this->info('Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Output Directory', $config['output_dir']],
                ['Include Paths', implode(', ', $config['include_paths'])],
                ['Backup Enabled', $config['backup']['enabled'] ? 'Yes' : 'No'],
                ['Strip Comments', $config['obfuscation']['strip_comments'] ? 'Yes' : 'No'],
                ['Strip Whitespace', $config['obfuscation']['strip_whitespace'] ? 'Yes' : 'No'],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE: No files will be modified');
        }

        $this->newLine();
    }

    /**
     * Display obfuscation results.
     *
     * @param array $result
     * @return void
     */
    protected function displayResults(array $result): void
    {
        $stats = $result['stats'];

        if ($result['success']) {
            $this->info('✓ Obfuscation completed successfully!');
        } else {
            $this->error('✗ Obfuscation completed with errors.');
        }

        $this->newLine();
        $this->info('Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Files', $stats['total_files']],
                ['Processed', $stats['processed']],
                ['Failed', $stats['failed']],
                ['Duration', $stats['duration'] . ' seconds'],
            ]
        );

        if (!empty($result['errors']) && $this->option('verbose')) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($result['errors'] as $error) {
                $this->line("  - {$error['file']}: {$error['error']}");
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('This was a dry run. No files were actually modified.');
        }
    }
}

