<?php

namespace Aflorea4\LaravelSourceObfuscator\Tests\Feature;

use Aflorea4\LaravelSourceObfuscator\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ObfuscateCommandTest extends TestCase
{
    /** @test */
    public function it_registers_obfuscate_run_command(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('obfuscate:run', $commands);
    }

    /** @test */
    public function it_registers_obfuscate_check_command(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('obfuscate:check', $commands);
    }

    /** @test */
    public function it_registers_obfuscate_status_command(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('obfuscate:status', $commands);
    }

    /** @test */
    public function it_registers_obfuscate_clear_command(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('obfuscate:clear', $commands);
    }

    /** @test */
    public function obfuscate_check_command_runs_successfully(): void
    {
        $exitCode = Artisan::call('obfuscate:check');
        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function obfuscate_status_command_runs_successfully(): void
    {
        $exitCode = Artisan::call('obfuscate:status');
        $this->assertEquals(0, $exitCode);
    }
}

