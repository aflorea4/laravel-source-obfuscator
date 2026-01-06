<?php

namespace Aflorea4\LaravelSourceObfuscator\Utilities;

class FileScanner
{
    protected array $config;
    protected array $excludePatterns;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->excludePatterns = $config['exclude_patterns'] ?? [];
    }

    /**
     * Scan directories and return list of files to obfuscate.
     *
     * @param array $paths
     * @return array
     */
    public function scan(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                if ($this->shouldIncludeFile($path)) {
                    $files[] = $path;
                }
            } elseif (is_dir($path)) {
                $files = array_merge($files, $this->scanDirectory($path));
            }
        }

        return array_unique($files);
    }

    /**
     * Recursively scan a directory.
     *
     * @param string $directory
     * @return array
     */
    protected function scanDirectory(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldIncludeFile($file->getPathname())) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Determine if a file should be included.
     *
     * @param string $filePath
     * @return bool
     */
    protected function shouldIncludeFile(string $filePath): bool
    {
        // Check exclude paths
        foreach ($this->config['exclude_paths'] as $excludePath) {
            if ($this->matchesPattern($filePath, $excludePath)) {
                return false;
            }
        }

        // Check exclude patterns (regex)
        foreach ($this->excludePatterns as $pattern) {
            if (preg_match($pattern, $filePath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a file path matches a pattern.
     *
     * @param string $filePath
     * @param string $pattern
     * @return bool
     */
    protected function matchesPattern(string $filePath, string $pattern): bool
    {
        // Handle wildcard patterns
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace(['*', '?'], ['.*', '.'], $pattern);
            $pattern = '#' . $pattern . '#';
            return preg_match($pattern, $filePath) === 1;
        }

        // Handle directory/file matching
        return strpos($filePath, $pattern) !== false;
    }

    /**
     * Get file count for given paths.
     *
     * @param array $paths
     * @return int
     */
    public function getFileCount(array $paths): int
    {
        return count($this->scan($paths));
    }

    /**
     * Get statistics about files to be processed.
     *
     * @param array $paths
     * @return array
     */
    public function getStatistics(array $paths): array
    {
        $files = $this->scan($paths);
        $stats = [
            'total_files' => count($files),
            'php_files' => 0,
            'other_files' => 0,
            'total_size' => 0,
        ];

        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $stats['total_size'] += filesize($file);

            if ($extension === 'php') {
                $stats['php_files']++;
            } else {
                $stats['other_files']++;
            }
        }

        return $stats;
    }
}

