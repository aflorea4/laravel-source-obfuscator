# Laravel Source Code Obfuscator

A comprehensive Laravel package for source code obfuscation using PHPBolt. Protect your Laravel application's source code with advanced obfuscation techniques, perfect for CI/CD pipelines and deployment workflows.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aflorea4/laravel-source-obfuscator.svg?style=flat-square)](https://packagist.org/packages/aflorea4/laravel-source-obfuscator)
[![Total Downloads](https://img.shields.io/packagist/dt/aflorea4/laravel-source-obfuscator.svg?style=flat-square)](https://packagist.org/packages/aflorea4/laravel-source-obfuscator)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D8.0-red.svg?style=flat-square)](https://laravel.com)

## Features

-   🔒 **PHPBolt Integration** - Uses PHPBolt engine for professional-grade obfuscation
-   ⚙️ **Highly Configurable** - Extensive configuration options for fine-tuned control
-   📁 **Smart File Selection** - Include/exclude patterns for precise file targeting
-   🔄 **Automatic Backups** - Create backups before obfuscation with rotation
-   🚀 **CI/CD Ready** - Designed for seamless integration into deployment pipelines
-   📊 **Detailed Reporting** - Generate comprehensive obfuscation reports
-   🎯 **Multiple Commands** - CLI commands for various obfuscation tasks
-   ⚡ **Performance Optimized** - Parallel processing support for large codebases
-   🛡️ **Safe Operation** - Dry-run mode and validation checks

## Requirements

-   PHP >= 7.4
-   Laravel >= 8.0 (8.x, 9.x, 10.x, 11.x, 12.x)
-   PHPBolt extension (`bolt.so`) - [Get it here](https://github.com/arshidkv12/phpBolt)

## Installation

### 1. Install via Composer

```bash
composer require aflorea4/laravel-source-obfuscator
```

### 2. Install PHPBolt Extension

PHPBolt is a free PHP extension for code obfuscation. Install the `bolt.so` extension:

```bash
# Download bolt.so for your PHP version from https://github.com/arshidkv12/phpBolt
# Or use the provided installation script

# Copy to PHP extensions directory
sudo cp bolt.so $(php-config --extension-dir)/

# Enable the extension
echo "extension=bolt.so" | sudo tee /etc/php/$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')/mods-available/bolt.ini
sudo phpenmod bolt

# Restart PHP-FPM
sudo systemctl restart php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm

# Verify installation
php -m | grep bolt
```

**Note:** The extension must be loaded both during obfuscation AND at runtime in production.

### 3. Publish Configuration

```bash
php artisan vendor:publish --provider="Aflorea4\LaravelSourceObfuscator\ObfuscatorServiceProvider" --tag="config"
```

This will create `config/obfuscator.php` in your Laravel application.

### 4. Configure Encryption Key

Edit `.env` file and add:

```env
# Encryption key (leave empty to auto-generate)
PHPBOLT_KEY=

# OR set a specific key (min 6 characters)
PHPBOLT_KEY=your-secret-key-here

# Output directories
OBFUSCATOR_OUTPUT_DIR=build/obfuscated
OBFUSCATOR_BACKUP_DIR=backups/pre-obfuscation
```

**Important:** The encryption key will be shown after obfuscation. You must set it as `PHP_BOLT_KEY` constant in production.

## Runtime Configuration (Production)

After obfuscating your code, you need to configure your production environment:

### 1. Install bolt.so Extension in Production

```bash
# The bolt.so extension MUST be installed in production
sudo cp bolt.so $(php-config --extension-dir)/
echo "extension=bolt.so" | sudo tee /etc/php/8.2/mods-available/bolt.ini
sudo phpenmod bolt
sudo systemctl restart php8.2-fpm
```

### 2. Define PHP_BOLT_KEY Constant

Add to your `public/index.php` or bootstrap file:

```php
<?php
// Define encryption key (use the key shown after obfuscation)
define('PHP_BOLT_KEY', 'your-encryption-key-here');

// Or load from environment
define('PHP_BOLT_KEY', env('PHPBOLT_KEY'));

// Rest of your Laravel bootstrap code...
```

**Without these steps, your obfuscated code will NOT run!**

## Configuration

The package configuration file (`config/obfuscator.php`) provides extensive options:

### PHPBolt Extension Configuration

```php
'extension_name' => 'bolt',  // Extension name to check
'encryption_key' => env('PHPBOLT_KEY', ''),  // Leave empty to auto-generate
'key_length' => env('PHPBOLT_KEY_LENGTH', 6),  // Key length if auto-generated
```

### Include/Exclude Paths

```php
'include_paths' => [
    'app',
    'routes',
    'config',
    'database/migrations',
    'database/seeders',
],

'exclude_paths' => [
    'tests',
    'vendor',
    'node_modules',
    'storage',
    '*.blade.php',  // Exclude Blade templates
    '*.md',
    '*.json',
],
```

### Obfuscation Options

```php
'obfuscation' => [
    'strip_comments' => true,        // Remove comments before encryption
    'strip_whitespace' => true,      // Remove unnecessary whitespace
    // Note: String encoding, variable scrambling, integrity checks,
    // and encryption are handled by bolt_encrypt() automatically
],
```

### Backup Configuration

```php
'backup' => [
    'enabled' => true,
    'path' => env('OBFUSCATOR_BACKUP_DIR', 'backups/pre-obfuscation'),
    'keep_last' => 5,  // Keep only the last 5 backups
],
```

### CI/CD Mode

```php
'ci_mode' => [
    'fail_on_error' => true,
    'generate_report' => true,
    'report_path' => 'build/obfuscation-report.json',
],
```

### Performance Settings

```php
'performance' => [
    'parallel_processes' => 0,      // 0 = auto-detect CPU cores
    'memory_limit' => '512M',
    'timeout' => 60,                // Timeout per file in seconds
],
```

## Usage

### Available Commands

The package provides four Artisan commands:

#### 1. Obfuscate Source Code

Run the main obfuscation process:

```bash
php artisan obfuscate:run
```

**Options:**

-   `--source=path` - Override source paths (can be used multiple times: `--source=app --source=routes`)
-   `--destination=path` - Override output directory
-   `--dry-run` - Simulate obfuscation without modifying files
-   `--skip-backup` - Skip creating a backup
-   `--force` - Skip confirmation prompt
-   `--verbose` - Display detailed output

**Examples:**

```bash
# Dry run to preview changes
php artisan obfuscate:run --dry-run

# Run without backup (not recommended)
php artisan obfuscate:run --skip-backup

# Run in CI/CD (non-interactive)
php artisan obfuscate:run --force

# Override source paths (obfuscate only specific directories)
php artisan obfuscate:run --source=app --source=routes

# Override output directory
php artisan obfuscate:run --destination=build/production

# Combine options
php artisan obfuscate:run --source=app --destination=dist --force
```

#### 2. Check Configuration

Verify PHPBolt installation and preview files to be obfuscated:

```bash
php artisan obfuscate:check
```

**Options:**

-   `--show-files` - Display complete list of files that will be obfuscated

**Example:**

```bash
php artisan obfuscate:check --show-files
```

#### 3. View Status

Display obfuscation status and information:

```bash
php artisan obfuscate:status
```

**Options:**

-   `--report` - Display the last obfuscation report

**Example:**

```bash
php artisan obfuscate:status --report
```

#### 4. Clear Output/Backups

Clean obfuscated output and backup directories:

```bash
php artisan obfuscate:clear
```

**Options:**

-   `--output` - Clear only the output directory
-   `--backups` - Clear only backup directories
-   `--force` - Skip confirmation prompt

**Examples:**

```bash
# Clear everything (with confirmation)
php artisan obfuscate:clear

# Clear only output directory
php artisan obfuscate:clear --output --force

# Clear only backups
php artisan obfuscate:clear --backups --force
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Deploy with Obfuscation

on:
    push:
        branches: [main]

jobs:
    deploy:
        runs-on: ubuntu-latest

        steps:
            - uses: actions/checkout@v3

            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: "8.2"

            - name: Install Dependencies
              run: composer install --no-dev --optimize-autoloader

            - name: Install PHPBolt
              run: |
                  # Install PHPBolt (adjust based on your setup)
                  wget https://your-phpbolt-url/phpbolt-installer.sh
                  bash phpbolt-installer.sh

            - name: Check Obfuscation Setup
              run: php artisan obfuscate:check

            - name: Obfuscate Source Code
              run: php artisan obfuscate:run --force

            - name: Upload Obfuscated Code
              uses: actions/upload-artifact@v3
              with:
                  name: obfuscated-application
                  path: build/obfuscated/

            - name: Upload Report
              uses: actions/upload-artifact@v3
              with:
                  name: obfuscation-report
                  path: build/obfuscation-report.json
```

### GitLab CI Example

```yaml
stages:
    - build
    - obfuscate
    - deploy

obfuscate:
    stage: obfuscate
    image: php:8.2
    before_script:
        - composer install --no-dev --optimize-autoloader
        - bash install-phpbolt.sh
    script:
        - php artisan obfuscate:check
        - php artisan obfuscate:run --force
    artifacts:
        paths:
            - build/obfuscated/
            - build/obfuscation-report.json
        expire_in: 1 week
```

### Jenkins Pipeline Example

```groovy
pipeline {
    agent any

    stages {
        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-dev --optimize-autoloader'
            }
        }

        stage('Setup PHPBolt') {
            steps {
                sh 'bash install-phpbolt.sh'
            }
        }

        stage('Verify Configuration') {
            steps {
                sh 'php artisan obfuscate:check'
            }
        }

        stage('Obfuscate') {
            steps {
                sh 'php artisan obfuscate:run --force'
            }
        }

        stage('Archive Artifacts') {
            steps {
                archiveArtifacts artifacts: 'build/obfuscated/**/*', fingerprint: true
                archiveArtifacts artifacts: 'build/obfuscation-report.json', fingerprint: true
            }
        }
    }
}
```

## Best Practices

### 1. Test Before Production

Always test obfuscated code in a staging environment:

```bash
# Use dry-run first
php artisan obfuscate:run --dry-run

# Check what will be obfuscated
php artisan obfuscate:check --show-files

# Run actual obfuscation
php artisan obfuscate:run
```

### 2. Exclude Blade Templates

Blade templates should generally not be obfuscated as they need to be parsed by Laravel:

```php
'exclude_paths' => [
    '*.blade.php',
],
```

### 3. Be Careful with Function/Class Scrambling

Scrambling function and class names can break:

-   Reflection-based code
-   Dynamic method calls
-   External integrations

Start with these options disabled:

```php
'scramble_functions' => false,
'scramble_classes' => false,
```

### 4. Maintain Backups

Always keep backups enabled:

```php
'backup' => [
    'enabled' => true,
    'keep_last' => 5,
],
```

### 5. Version Control

Add to `.gitignore`:

```
/build/obfuscated/
/backups/
build/obfuscation-report.json
```

### 6. Monitor Performance

For large codebases, adjust performance settings:

```php
'performance' => [
    'parallel_processes' => 4,  // Adjust based on available CPU
    'memory_limit' => '1G',
    'timeout' => 120,
],
```

## Troubleshooting

### PHPBolt Extension Not Loaded

```
Error: PHPBolt extension (bolt.so) is not loaded
```

**Solution:** Verify bolt.so extension is loaded:

```bash
# Check if extension is loaded
php -m | grep bolt

# Check extension directory
php -i | grep extension_dir

# Verify bolt.so exists
ls -l $(php-config --extension-dir)/bolt.so

# Check if enabled
php -r "var_dump(extension_loaded('bolt'));"

# If not loaded, enable it
echo "extension=bolt.so" | sudo tee /etc/php/8.2/mods-available/bolt.ini
sudo phpenmod bolt
sudo systemctl restart php8.2-fpm
```

### Permission Denied

```
Error: Permission denied when creating output directory
```

**Solution:** Ensure Laravel has write permissions:

```bash
chmod -R 775 build/
chmod -R 775 backups/
```

### Memory Limit Exceeded

```
Error: Allowed memory size exhausted
```

**Solution:** Increase memory limit in configuration:

```php
'performance' => [
    'memory_limit' => '1G',  // Increase as needed
],
```

### Files Not Being Obfuscated

**Solution:** Check your include/exclude patterns:

```bash
php artisan obfuscate:check --show-files
```

## Security Considerations

1. **Source Code Protection** - Keep your original source code secure and separate from obfuscated versions
2. **License Keys** - Store PHPBolt license keys securely
3. **Backup Security** - Ensure backup directories are not publicly accessible
4. **Deploy Only Obfuscated Code** - Never deploy unobfuscated code to production
5. **Access Control** - Limit access to obfuscation commands in production environments

## Performance Tips

1. **Exclude Unnecessary Files** - Only obfuscate what needs protection
2. **Use Parallel Processing** - Enable parallel processing for faster obfuscation
3. **Optimize File Scanning** - Use specific include paths rather than entire directories
4. **CI/CD Caching** - Cache PHPBolt installation in CI/CD pipelines

## Support & Contributing

### Issues

If you encounter any issues, please check:

1. PHPBolt installation and configuration
2. File permissions
3. Configuration file settings

### Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

-   **Alexandru Florea** - Package Author
-   **PHPBolt** - Obfuscation Engine
-   Inspired by [jaydeepukani/laravel-source-obfuscator](https://github.com/jaydeepukani/Laravel-Source-Obfuscator) for command-line path override ideas

## Changelog

### Version 1.0.0 (Initial Release)

-   PHPBolt integration
-   Configurable include/exclude paths
-   Four CLI commands (run, check, status, clear)
-   Automatic backup system
-   CI/CD support
-   Performance optimization options
-   Comprehensive reporting

## Roadmap

-   [ ] Support for additional obfuscation engines
-   [ ] Web-based configuration UI
-   [ ] Real-time obfuscation monitoring
-   [ ] Integration with Laravel Forge
-   [ ] Docker support
-   [ ] Advanced scheduling options

---

**Note:** PHPBolt is a commercial product. You need a valid PHPBolt license to use this package. This package is not affiliated with or endorsed by PHPBolt.
