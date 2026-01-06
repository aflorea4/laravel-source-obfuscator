<?php

namespace Aflorea4\LaravelSourceObfuscator\Tests\Unit;

use Aflorea4\LaravelSourceObfuscator\Tests\TestCase;
use Aflorea4\LaravelSourceObfuscator\Utilities\PathResolver;

class PathResolverTest extends TestCase
{
    protected PathResolver $pathResolver;
    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = '/var/www/html';
        $this->pathResolver = new PathResolver($this->basePath);
    }

    /** @test */
    public function it_resolves_relative_paths(): void
    {
        $result = $this->pathResolver->resolve('app/Models');
        $this->assertEquals('/var/www/html/app/Models', $result);
    }

    /** @test */
    public function it_returns_absolute_paths_as_is(): void
    {
        $absolutePath = '/absolute/path/to/file';
        $result = $this->pathResolver->resolve($absolutePath);
        $this->assertEquals($absolutePath, $result);
    }

    /** @test */
    public function it_gets_relative_path(): void
    {
        $absolutePath = '/var/www/html/app/Models/User.php';
        $result = $this->pathResolver->getRelativePath($absolutePath);
        $this->assertEquals('app/Models/User.php', $result);
    }

    /** @test */
    public function it_normalizes_paths(): void
    {
        $path = 'app/../config/./database.php';
        $result = $this->pathResolver->normalize($path);
        $this->assertEquals('config/database.php', $result);
    }

    /** @test */
    public function it_joins_path_segments(): void
    {
        $result = $this->pathResolver->join('app', 'Models', 'User.php');
        $this->assertEquals('app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'User.php', $result);
    }

    /** @test */
    public function it_returns_base_path(): void
    {
        $this->assertEquals($this->basePath, $this->pathResolver->getBasePath());
    }
}

