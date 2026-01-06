<?php

namespace AlexandruFlorea\LaravelSourceObfuscator\Console\Commands;

use Illuminate\Console\Command;
use AlexandruFlorea\LaravelSourceObfuscator\Utilities\PathResolver;

class ObfuscateStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obfuscate:status
                            {--report : Display the last obfuscation report if available}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display obfuscation status and information';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $config = config('obfuscator');
        $pathResolver = new PathResolver(base_path());

        $this->info('Obfuscation Status');
        $this->newLine();

        // Check output directory
        $this->checkOutputDirectory($pathResolver, $config);
        $this->newLine();

        // Check backup directory
        $this->checkBackupDirectory($pathResolver, $config);
        $this->newLine();

        // Display report if requested
        if ($this->option('report')) {
            $this->displayReport($pathResolver, $config);
        }

        return self::SUCCESS;
    }

    /**
     * Check output directory status.
     *
     * @param PathResolver $pathResolver
     * @param array $config
     * @return void
     */
    protected function checkOutputDirectory(PathResolver $pathResolver, array $config): void
    {
        $outputDir = $pathResolver->resolve($config['output_dir']);

        $this->info('Output Directory:');
        $this->line("  Path: {$outputDir}");

        if (is_dir($outputDir)) {
            $fileCount = $this->countFiles($outputDir);
            $size = $this->getDirectorySize($outputDir);

            $this->info('  Status: ✓ Exists');
            $this->line("  Files: {$fileCount}");
            $this->line("  Size: {$this->formatBytes($size)}");

            // Get last modified time
            $lastModified = $this->getLastModifiedTime($outputDir);
            if ($lastModified) {
                $this->line("  Last Modified: {$lastModified}");
            }
        } else {
            $this->comment('  Status: Directory does not exist');
        }
    }

    /**
     * Check backup directory status.
     *
     * @param PathResolver $pathResolver
     * @param array $config
     * @return void
     */
    protected function checkBackupDirectory(PathResolver $pathResolver, array $config): void
    {
        $backupPath = $pathResolver->resolve($config['backup']['path']);

        $this->info('Backup Directory:');
        $this->line("  Path: {$backupPath}");

        if (is_dir($backupPath)) {
            $backups = array_diff(scandir($backupPath), ['.', '..']);
            $backupCount = count($backups);

            $this->info('  Status: ✓ Exists');
            $this->line("  Backups: {$backupCount}");

            if ($backupCount > 0) {
                rsort($backups);
                $this->newLine();
                $this->line('  Recent Backups:');
                foreach (array_slice($backups, 0, 5) as $backup) {
                    $this->line("    • {$backup}");
                }
                if ($backupCount > 5) {
                    $this->line("    ... and " . ($backupCount - 5) . " more");
                }
            }
        } else {
            $this->comment('  Status: Directory does not exist');
        }
    }

    /**
     * Display the last obfuscation report.
     *
     * @param PathResolver $pathResolver
     * @param array $config
     * @return void
     */
    protected function displayReport(PathResolver $pathResolver, array $config): void
    {
        $reportPath = $pathResolver->resolve($config['ci_mode']['report_path']);

        $this->info('Obfuscation Report:');

        if (!file_exists($reportPath)) {
            $this->comment('  No report available');
            return;
        }

        $report = json_decode(file_get_contents($reportPath), true);

        if (!$report) {
            $this->error('  Failed to read report');
            return;
        }

        $this->newLine();
        $this->line("  Timestamp: {$report['timestamp']}");

        $this->newLine();
        $this->line('  Statistics:');
        foreach ($report['stats'] as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $this->line("    {$label}: {$value}");
        }

        if (!empty($report['errors'])) {
            $this->newLine();
            $this->error('  Errors: ' . count($report['errors']));
            foreach (array_slice($report['errors'], 0, 5) as $error) {
                $this->line("    • {$error['file']}: {$error['error']}");
            }
            if (count($report['errors']) > 5) {
                $this->line("    ... and " . (count($report['errors']) - 5) . " more errors");
            }
        }
    }

    /**
     * Count files in a directory recursively.
     *
     * @param string $directory
     * @return int
     */
    protected function countFiles(string $directory): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get directory size.
     *
     * @param string $directory
     * @return int
     */
    protected function getDirectorySize(string $directory): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Get last modified time of directory.
     *
     * @param string $directory
     * @return string|null
     */
    protected function getLastModifiedTime(string $directory): ?string
    {
        $lastModified = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $mtime = $file->getMTime();
                if ($mtime > $lastModified) {
                    $lastModified = $mtime;
                }
            }
        }

        return $lastModified > 0 ? date('Y-m-d H:i:s', $lastModified) : null;
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

