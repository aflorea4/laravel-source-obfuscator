<?php

namespace AlexandruFlorea\LaravelSourceObfuscator\Tests\Unit;

use AlexandruFlorea\LaravelSourceObfuscator\Tests\TestCase;
use AlexandruFlorea\LaravelSourceObfuscator\Utilities\FileScanner;

class FileScannerTest extends TestCase
{
    protected FileScanner $fileScanner;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'exclude_paths' => [
                'vendor',
                'tests',
                '*.blade.php',
                '*.md',
            ],
            'exclude_patterns' => [
                '/\.git/',
                '/node_modules/',
            ],
        ];
        
        $this->fileScanner = new FileScanner($this->config);
    }

    /** @test */
    public function it_initializes_with_config(): void
    {
        $this->assertInstanceOf(FileScanner::class, $this->fileScanner);
    }

    /** @test */
    public function it_excludes_vendor_directory(): void
    {
        $method = new \ReflectionMethod($this->fileScanner, 'shouldIncludeFile');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->fileScanner, '/path/to/vendor/package/file.php');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_excludes_blade_templates(): void
    {
        $method = new \ReflectionMethod($this->fileScanner, 'shouldIncludeFile');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->fileScanner, '/path/to/view.blade.php');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_includes_regular_php_files(): void
    {
        $method = new \ReflectionMethod($this->fileScanner, 'shouldIncludeFile');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->fileScanner, '/path/to/Controller.php');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_matches_wildcard_patterns(): void
    {
        $method = new \ReflectionMethod($this->fileScanner, 'matchesPattern');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->fileScanner, '/path/to/README.md', '*.md');
        $this->assertTrue($result);
    }
}

