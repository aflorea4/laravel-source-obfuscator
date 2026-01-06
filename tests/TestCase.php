<?php

namespace AlexandruFlorea\LaravelSourceObfuscator\Tests;

use AlexandruFlorea\LaravelSourceObfuscator\ObfuscatorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ObfuscatorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup test environment
        config()->set('obfuscator.phpbolt_binary', '/usr/bin/phpbolt');
        config()->set('obfuscator.output_dir', 'tests/output');
        config()->set('obfuscator.backup.path', 'tests/backups');
    }
}

