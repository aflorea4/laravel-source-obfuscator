<?php

namespace Aflorea4\LaravelSourceObfuscator\Services;

use Aflorea4\LaravelSourceObfuscator\Utilities\FileScanner;
use Aflorea4\LaravelSourceObfuscator\Utilities\PathResolver;
use Illuminate\Support\Facades\Log;

class ObfuscationService
{
    protected array $config;
    protected string $basePath;
    protected FileScanner $fileScanner;
    protected PathResolver $pathResolver;
    protected array $processedFiles = [];
    protected array $errors = [];
    protected string $encryptionKey;

    public function __construct(array $config, string $basePath)
    {
        $this->config = $config;
        $this->basePath = $basePath;
        $this->fileScanner = new FileScanner($config);
        $this->pathResolver = new PathResolver($basePath);
        $this->encryptionKey = $this->generateEncryptionKey();
    }

    /**
     * Run the obfuscation process.
     *
     * @param array $options
     * @return array
     */
    public function obfuscate(array $options = []): array
    {
        $startTime = microtime(true);

        // Validate PHPBolt installation
        if (!$this->validatePhpBolt()) {
            throw new \RuntimeException('PHPBolt extension (bolt.so) is not loaded. Please install and enable the bolt extension.');
        }

        // Create backup if requested via --backup flag
        if ($options['enable_backup'] ?? false) {
            $this->createBackup();
        }

        // Scan files to obfuscate (with optional override)
        $files = $this->scanFiles($options['source_override'] ?? null);

        if (empty($files)) {
            return [
                'success' => true,
                'message' => 'No files found to obfuscate.',
                'stats' => [
                    'total_files' => 0,
                    'processed' => 0,
                    'failed' => 0,
                    'duration' => 0,
                ],
            ];
        }

        // Prepare output directory (with optional override)
        $outputDir = isset($options['destination_override']) 
            ? $this->pathResolver->resolve($options['destination_override'])
            : $this->pathResolver->resolve($this->config['output_dir']);

        // If production-ready mode, copy entire project first (before preparing output dir)
        if ($options['production_ready'] ?? false) {
            $this->createProductionBundle($outputDir, $options);
            // In production-ready mode, don't delete the output directory
            // Just ensure it exists (it should from the copy above)
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
        } else {
            $this->prepareOutputDirectory($outputDir);
        }

        // Process files
        $stats = $this->processFiles($files, $outputDir, $options);

        // Generate report if in CI mode
        if ($this->config['ci_mode']['generate_report']) {
            $this->generateReport($stats);
        }

        $duration = microtime(true) - $startTime;
        $stats['duration'] = round($duration, 2);

        return [
            'success' => $stats['failed'] === 0,
            'message' => sprintf(
                'Obfuscation completed: %d processed, %d failed',
                $stats['processed'],
                $stats['failed']
            ),
            'stats' => $stats,
            'errors' => $this->errors,
            'encryption_key' => $this->encryptionKey,
        ];
    }

    /**
     * Validate PHPBolt installation.
     *
     * @return bool
     */
    public function validatePhpBolt(): bool
    {
        $extensionName = $this->config['extension_name'];

        // Check if extension is loaded
        if (!extension_loaded($extensionName)) {
            $this->log('error', "PHPBolt extension '{$extensionName}' is not loaded");
            return false;
        }

        // Check if bolt_encrypt function exists
        if (!function_exists('bolt_encrypt')) {
            $this->log('error', 'bolt_encrypt() function is not available');
            return false;
        }

        $this->log('info', 'PHPBolt extension validated successfully');
        return true;
    }

    /**
     * Scan files to obfuscate.
     *
     * @param array|null $sourceOverride Optional source paths override
     * @return array
     */
    protected function scanFiles(?array $sourceOverride = null): array
    {
        // Use override paths if provided, otherwise use config
        $paths = $sourceOverride ?? $this->config['include_paths'];
        
        $includePaths = array_map(
            fn($path) => $this->pathResolver->resolve($path),
            $paths
        );

        return $this->fileScanner->scan($includePaths);
    }

    /**
     * Process files for obfuscation.
     *
     * @param array $files
     * @param string $outputDir
     * @param array $options
     * @return array
     */
    protected function processFiles(array $files, string $outputDir, array $options): array
    {
        $stats = [
            'total_files' => count($files),
            'processed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($files as $file) {
            try {
                $relativePath = $this->pathResolver->getRelativePath($file);
                $outputPath = $outputDir . DIRECTORY_SEPARATOR . $relativePath;

                // Create output directory
                $outputFileDir = dirname($outputPath);
                if (!is_dir($outputFileDir)) {
                    mkdir($outputFileDir, 0755, true);
                }

                // Obfuscate PHP files
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    if ($this->obfuscateFile($file, $outputPath, $options)) {
                        $stats['processed']++;
                        $this->processedFiles[] = $file;
                    } else {
                        $stats['failed']++;
                    }
                } else if ($this->config['copy_non_php_files']) {
                    // Copy non-PHP files
                    copy($file, $outputPath);
                    $stats['processed']++;
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                $this->errors[] = [
                    'file' => $file,
                    'error' => $e->getMessage(),
                ];
                $this->log('error', "Failed to process file {$file}: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Obfuscate a single file using PHPBolt extension.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param array $options
     * @return bool
     */
    protected function obfuscateFile(string $inputFile, string $outputFile, array $options): bool
    {
        // Check for dry run
        if ($options['dry_run'] ?? false) {
            $this->log('info', "Would obfuscate: {$inputFile} -> {$outputFile}");
            return true;
        }

        try {
            // Read source file
            $contents = file_get_contents($inputFile);
            
            if ($contents === false) {
                throw new \RuntimeException("Failed to read file: {$inputFile}");
            }

            // Apply obfuscation options
            if ($this->config['obfuscation']['strip_comments']) {
                $contents = $this->stripComments($contents);
            }

            if ($this->config['obfuscation']['strip_whitespace']) {
                $contents = $this->stripWhitespace($contents);
            }

            // Remove opening PHP tag for encryption
            $contents = preg_replace('/^\s*<\?php\s*/i', '', $contents);

            // Encrypt using PHPBolt extension
            $encrypted = bolt_encrypt($contents, $this->encryptionKey);

            if ($encrypted === false) {
                throw new \RuntimeException("bolt_encrypt() failed for: {$inputFile}");
            }

            // Prepare decrypt header
            $header = "<?php bolt_decrypt(__FILE__, PHP_BOLT_KEY); return 0;";
            $separator = "\n##!##\n";
            
            // Write encrypted file
            $result = file_put_contents($outputFile, $header . $separator . $encrypted);

            if ($result === false) {
                throw new \RuntimeException("Failed to write encrypted file: {$outputFile}");
            }

            $this->log('debug', "Successfully obfuscated: {$inputFile}");
            return true;

        } catch (\Exception $e) {
            $this->log('error', "Failed to obfuscate {$inputFile}: " . $e->getMessage());
            $this->errors[] = [
                'file' => $inputFile,
                'error' => $e->getMessage(),
            ];
            return false;
        }
    }

    /**
     * Strip comments from PHP code.
     *
     * @param string $content
     * @return string
     */
    protected function stripComments(string $content): string
    {
        if (!$this->config['obfuscation']['strip_comments']) {
            return $content;
        }

        $tokens = token_get_all($content);
        $output = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                // Remove comments but keep the code
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }
                $output .= $token[1];
            } else {
                $output .= $token;
            }
        }

        return $output;
    }

    /**
     * Strip unnecessary whitespace from PHP code.
     *
     * @param string $content
     * @return string
     */
    protected function stripWhitespace(string $content): string
    {
        if (!$this->config['obfuscation']['strip_whitespace']) {
            return $content;
        }

        $tokens = token_get_all($content);
        $output = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] === T_WHITESPACE) {
                    // Keep single space instead of multiple whitespaces
                    $output .= ' ';
                } else {
                    $output .= $token[1];
                }
            } else {
                $output .= $token;
            }
        }

        return $output;
    }

    /**
     * Generate encryption key.
     *
     * @return string
     */
    protected function generateEncryptionKey(): string
    {
        // Use configured key if provided
        if (!empty($this->config['encryption_key'])) {
            return $this->config['encryption_key'];
        }

        // Generate random key with configured length
        $length = $this->config['key_length'] ?? 6;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $key = '';
        
        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $key;
    }

    /**
     * Create a backup of the source code.
     *
     * @return void
     */
    protected function createBackup(): void
    {
        $backupPath = $this->pathResolver->resolve($this->config['backup']['path']);
        $timestamp = date('Y-m-d_His');
        $backupDir = $backupPath . DIRECTORY_SEPARATOR . $timestamp;

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Copy files to backup
        foreach ($this->config['include_paths'] as $path) {
            $sourcePath = $this->pathResolver->resolve($path);
            if (file_exists($sourcePath)) {
                $this->recursiveCopy($sourcePath, $backupDir . DIRECTORY_SEPARATOR . basename($path));
            }
        }

        $this->log('info', "Backup created at: {$backupDir}");

        // Clean old backups
        $this->cleanOldBackups($backupPath);
    }

    /**
     * Prepare the output directory.
     *
     * @param string $outputDir
     * @return void
     */
    protected function prepareOutputDirectory(string $outputDir): void
    {
        if (is_dir($outputDir)) {
            // Clean existing output directory
            $this->deleteDirectory($outputDir);
        }

        mkdir($outputDir, 0755, true);
    }

    /**
     * Generate obfuscation report.
     *
     * @param array $stats
     * @return void
     */
    protected function generateReport(array $stats): void
    {
        $reportPath = $this->pathResolver->resolve($this->config['ci_mode']['report_path']);
        $reportDir = dirname($reportPath);

        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'stats' => $stats,
            'encryption_key' => $this->encryptionKey,
            'config' => [
                'include_paths' => $this->config['include_paths'],
                'exclude_paths' => $this->config['exclude_paths'],
                'obfuscation_options' => $this->config['obfuscation'],
            ],
            'errors' => $this->errors,
            'processed_files' => $this->processedFiles,
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->log('info', "Report generated at: {$reportPath}");
    }

    /**
     * Recursively copy a directory.
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    protected function recursiveCopy(string $source, string $destination): void
    {
        if (is_file($source)) {
            copy($source, $destination);
            return;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file !== '.' && $file !== '..') {
                $srcPath = $source . DIRECTORY_SEPARATOR . $file;
                $dstPath = $destination . DIRECTORY_SEPARATOR . $file;

                if (is_dir($srcPath)) {
                    $this->recursiveCopy($srcPath, $dstPath);
                } else {
                    copy($srcPath, $dstPath);
                }
            }
        }
        closedir($dir);
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

    /**
     * Clean old backups.
     *
     * @param string $backupPath
     * @return void
     */
    protected function cleanOldBackups(string $backupPath): void
    {
        $keepLast = $this->config['backup']['keep_last'];

        if (!is_dir($backupPath)) {
            return;
        }

        $backups = array_diff(scandir($backupPath), ['.', '..']);
        rsort($backups);

        if (count($backups) > $keepLast) {
            $toDelete = array_slice($backups, $keepLast);
            foreach ($toDelete as $backup) {
                $this->deleteDirectory($backupPath . DIRECTORY_SEPARATOR . $backup);
            }
        }
    }

    /**
     * Log a message.
     *
     * @param string $level
     * @param string $message
     * @return void
     */
    protected function log(string $level, string $message): void
    {
        if (!$this->config['logging']['enabled']) {
            return;
        }

        $configLevel = $this->config['logging']['level'];
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];

        if ($levels[$level] >= $levels[$configLevel]) {
            Log::channel('single')->{$level}('[Obfuscator] ' . $message);
        }
    }

    /**
     * Get processed files.
     *
     * @return array
     */
    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }

    /**
     * Get errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get encryption key.
     *
     * @return string
     */
    public function getEncryptionKey(): string
    {
        return $this->encryptionKey;
    }

    /**
     * Create a production-ready bundle with the entire Laravel project.
     *
     * @param string $outputDir
     * @param array $options
     * @return void
     */
    protected function createProductionBundle(string $outputDir, array $options): void
    {
        $this->log('info', 'Creating production-ready bundle');

        if ($options['dry_run'] ?? false) {
            $this->log('info', 'Dry run: Skipping production bundle creation');
            return;
        }

        // Get exclude lists from config
        $excludeDirs = $this->config['production_bundle']['exclude_dirs'] ?? [];
        $excludeFiles = $this->config['production_bundle']['exclude_files'] ?? [];
        $alwaysInclude = $this->config['production_bundle']['always_include'] ?? [];

        // Copy entire project structure
        $this->copyDirectoryRecursive(
            $this->basePath,
            $outputDir,
            $outputDir, // Pass original output dir to prevent recursion
            $excludeDirs,
            $excludeFiles,
            $alwaysInclude
        );

        $this->log('info', 'Production bundle created successfully');
    }

    /**
     * Recursively copy directory with exclusions.
     *
     * @param string $source
     * @param string $destination
     * @param string $outputRoot Original output directory root (to prevent recursion)
     * @param array $excludeDirs
     * @param array $excludeFiles
     * @param array $alwaysInclude
     * @return void
     */
    protected function copyDirectoryRecursive(
        string $source,
        string $destination,
        string $outputRoot,
        array $excludeDirs = [],
        array $excludeFiles = [],
        array $alwaysInclude = []
    ): void {
        if (!is_dir($source)) {
            return;
        }

        // Create destination directory if it doesn't exist
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $items = scandir($source);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
            $destPath = $destination . DIRECTORY_SEPARATOR . $item;
            $relativePath = str_replace($this->basePath . DIRECTORY_SEPARATOR, '', $sourcePath);

            // Check if this is in always include list (takes priority)
            $shouldAlwaysInclude = false;
            foreach ($alwaysInclude as $alwaysPath) {
                if ($relativePath === $alwaysPath || str_starts_with($relativePath, $alwaysPath . DIRECTORY_SEPARATOR)) {
                    $shouldAlwaysInclude = true;
                    break;
                }
            }

            if ($shouldAlwaysInclude) {
                if (is_dir($sourcePath)) {
                    $this->copyDirectoryRecursive($sourcePath, $destPath, $outputRoot, $excludeDirs, $excludeFiles, $alwaysInclude);
                } else {
                    copy($sourcePath, $destPath);
                }
                continue;
            }

            // Skip excluded directories
            if (is_dir($sourcePath)) {
                $shouldExclude = false;
                foreach ($excludeDirs as $excludeDir) {
                    if ($item === $excludeDir || $relativePath === $excludeDir || str_starts_with($relativePath, $excludeDir . DIRECTORY_SEPARATOR)) {
                        $shouldExclude = true;
                        break;
                    }
                }

                // Skip the output directory itself to avoid recursion
                if ($sourcePath === $outputRoot || str_starts_with($sourcePath, $outputRoot . DIRECTORY_SEPARATOR)) {
                    $shouldExclude = true;
                }

                if (!$shouldExclude) {
                    $this->copyDirectoryRecursive($sourcePath, $destPath, $outputRoot, $excludeDirs, $excludeFiles, $alwaysInclude);
                }
            } else {
                // Skip excluded files
                $shouldExclude = false;
                foreach ($excludeFiles as $excludeFile) {
                    if ($item === $excludeFile || fnmatch($excludeFile, $item) || fnmatch($excludeFile, $relativePath)) {
                        $shouldExclude = true;
                        break;
                    }
                }

                if (!$shouldExclude) {
                    copy($sourcePath, $destPath);
                }
            }
        }
    }
}
