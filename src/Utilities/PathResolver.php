<?php

namespace AlexandruFlorea\LaravelSourceObfuscator\Utilities;

class PathResolver
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    /**
     * Resolve a path relative to the base path.
     *
     * @param string $path
     * @return string
     */
    public function resolve(string $path): string
    {
        // If already absolute, return as is
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        // Resolve relative to base path
        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Check if a path is absolute.
     *
     * @param string $path
     * @return bool
     */
    protected function isAbsolutePath(string $path): bool
    {
        // Unix-style absolute path
        if (strpos($path, '/') === 0) {
            return true;
        }

        // Windows-style absolute path
        if (preg_match('/^[a-zA-Z]:[\\/]/', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Get relative path from base path.
     *
     * @param string $path
     * @return string
     */
    public function getRelativePath(string $path): string
    {
        if (strpos($path, $this->basePath) === 0) {
            return ltrim(substr($path, strlen($this->basePath)), DIRECTORY_SEPARATOR);
        }

        return $path;
    }

    /**
     * Normalize a path.
     *
     * @param string $path
     * @return string
     */
    public function normalize(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];

        foreach ($parts as $part) {
            if ('.' === $part) {
                continue;
            }
            if ('..' === $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        $result = implode(DIRECTORY_SEPARATOR, $absolutes);

        // Preserve leading slash for absolute paths
        if ($this->isAbsolutePath($path)) {
            $result = DIRECTORY_SEPARATOR . $result;
        }

        return $result;
    }

    /**
     * Get base path.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Join path segments.
     *
     * @param string ...$segments
     * @return string
     */
    public function join(string ...$segments): string
    {
        return implode(DIRECTORY_SEPARATOR, $segments);
    }
}

