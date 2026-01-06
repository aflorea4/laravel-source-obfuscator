<?php

namespace Aflorea4\LaravelSourceObfuscator\Console\Commands;

use Illuminate\Console\Command;
use Aflorea4\LaravelSourceObfuscator\Utilities\PathResolver;

class ObfuscateClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obfuscate:clear
                            {--output : Clear only the output directory}
                            {--backups : Clear only the backup directories}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear obfuscated output and/or backup directories';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $config = config('obfuscator');
        $pathResolver = new PathResolver(base_path());

        $clearOutput = $this->option('output') || (!$this->option('output') && !$this->option('backups'));
        $clearBackups = $this->option('backups') || (!$this->option('output') && !$this->option('backups'));

        // Confirm action
        if (!$this->option('force')) {
            $message = 'This will delete ';
            if ($clearOutput && $clearBackups) {
                $message .= 'both output and backup directories';
            } elseif ($clearOutput) {
                $message .= 'the output directory';
            } else {
                $message .= 'the backup directories';
            }
            $message .= '. Continue?';

            if (!$this->confirm($message, false)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        $this->newLine();

        // Clear output directory
        if ($clearOutput) {
            $outputDir = $pathResolver->resolve($config['output_dir']);
            if (is_dir($outputDir)) {
                $this->info("Clearing output directory: {$outputDir}");
                $this->deleteDirectory($outputDir);
                $this->info('✓ Output directory cleared');
            } else {
                $this->comment('Output directory does not exist');
            }
        }

        // Clear backup directories
        if ($clearBackups) {
            $backupPath = $pathResolver->resolve($config['backup']['path']);
            if (is_dir($backupPath)) {
                $this->info("Clearing backup directories: {$backupPath}");
                $this->deleteDirectory($backupPath);
                $this->info('✓ Backup directories cleared');
            } else {
                $this->comment('Backup directory does not exist');
            }
        }

        $this->newLine();
        $this->info('Clear operation completed!');

        return self::SUCCESS;
    }

    /**
     * Delete a directory recursively.
     *
     * @param string $dir
     * @return void
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

