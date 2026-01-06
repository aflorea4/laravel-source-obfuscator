# Quick Start Guide

Get started with Laravel Source Code Obfuscator in 5 minutes.

## Prerequisites

✅ PHP >= 7.4  
✅ Laravel >= 8.0 (8.x, 9.x, 10.x, 11.x, 12.x)  
✅ PHPBolt

## Installation

### Step 1: Install bolt.so Extension

```bash
# Download bolt.so for your PHP version
Download bolt.so file from https://phpbolt.com/ website. Then find the extension directory. You can find the extension directory by phpinfo() function. Then add extension=bolt.so in the php.ini file and restart your server.

# Install extension
sudo cp bolt.so $(php-config --extension-dir)/
echo "extension=bolt.so" | sudo tee /etc/php/$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')/mods-available/bolt.ini
sudo phpenmod bolt
sudo systemctl restart php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm

# Verify installation
php -m | grep bolt
# Should output: bolt
```

### Step 2: Install Package

```bash
composer require aflorea4/laravel-source-obfuscator
```

### Step 3: Publish Configuration

```bash
php artisan vendor:publish --provider="Aflorea4\LaravelSourceObfuscator\ObfuscatorServiceProvider" --tag="config"
```

### Step 4: Configure Environment

Add to `.env`:

```env
# Leave empty to auto-generate encryption key
PHPBOLT_KEY=

# Or set a specific key (min 6 characters)
# PHPBOLT_KEY=your-secret-key

OBFUSCATOR_OUTPUT_DIR=build/obfuscated
```

## Basic Usage

### Check Configuration

```bash
php artisan obfuscate:check
```

Expected output:

```
PHPBolt Extension Check:
  Extension Name: bolt
  Extension Loaded: Yes
  bolt_encrypt() Available: Yes
  Status: ✓ PHPBolt extension is properly loaded
```

### Test Run (Dry Run)

```bash
php artisan obfuscate:run --dry-run
```

This simulates obfuscation without modifying files.

### Run Obfuscation

```bash
php artisan obfuscate:run
```

**IMPORTANT:** Save the encryption key shown after obfuscation! You'll need it in production.

Your obfuscated code will be in `build/obfuscated/`.

### Configure Production Environment

After obfuscation, configure your production server:

```php
// In public/index.php (before Laravel bootstrap)
define('PHP_BOLT_KEY', 'the-key-from-obfuscation-output');

// Ensure bolt.so is loaded in production
// php -m | grep bolt
```

## Configuration

Edit `config/obfuscator.php`:

```php
'include_paths' => [
    'app',           // Your application code
    'routes',        // Route files
    'config',        // Configuration files
],

'exclude_paths' => [
    'tests',         // Exclude tests
    'vendor',        // Exclude dependencies
    '*.blade.php',   // Exclude Blade templates (important!)
],

'obfuscation' => [
    'strip_comments' => true,
    'encode_strings' => true,
    'scramble_variables' => true,
    'encrypt' => true,
],
```

## CI/CD Quick Setup

### GitHub Actions

Create `.github/workflows/obfuscate.yml`:

```yaml
name: Obfuscate

on:
    push:
        branches: [main]

jobs:
    obfuscate:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v3

            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: "8.2"

            - name: Install Dependencies
              run: composer install --no-dev

            - name: Install PHPBolt
              run: bash install-phpbolt.sh

            - name: Obfuscate
              run: php artisan obfuscate:run --force

            - name: Upload Artifacts
              uses: actions/upload-artifact@v3
              with:
                  name: obfuscated-app
                  path: build/obfuscated/
```

## Commands Cheat Sheet

```bash
# Check configuration and preview files
php artisan obfuscate:check --show-files

# Run obfuscation (with confirmation)
php artisan obfuscate:run

# Run obfuscation (no confirmation - for CI/CD)
php artisan obfuscate:run --force

# Dry run (test without actually obfuscating)
php artisan obfuscate:run --dry-run

# Override source paths (obfuscate only specific paths)
php artisan obfuscate:run --source=app --source=routes

# Override output directory
php artisan obfuscate:run --destination=build/production

# Combine options for custom obfuscation
php artisan obfuscate:run --source=app --destination=dist --force

# View status and last report
php artisan obfuscate:status --report

# Clear output directory
php artisan obfuscate:clear --output --force

# Clear backups
php artisan obfuscate:clear --backups --force
```

## Common Issues

### ❌ PHPBolt Extension Not Loaded

```
Error: PHPBolt extension (bolt.so) is not loaded
```

**Fix:**

```bash
# Check if extension is loaded
php -m | grep bolt

# If not loaded, install it
sudo cp bolt.so $(php-config --extension-dir)/
echo "extension=bolt.so" | sudo tee /etc/php/8.2/mods-available/bolt.ini
sudo phpenmod bolt
sudo systemctl restart php8.2-fpm
```

### ❌ Permission Denied

```
Error: Permission denied when creating output directory
```

**Fix:**

```bash
mkdir -p build/obfuscated backups
chmod -R 775 build backups
```

### ❌ Files Not Obfuscated

**Fix:** Check your include/exclude paths:

```bash
php artisan obfuscate:check --show-files
```

## Best Practices

### ✅ DO

-   ✅ Test with `--dry-run` first
-   ✅ Exclude Blade templates (`*.blade.php`)
-   ✅ Keep backups enabled
-   ✅ Review obfuscation reports
-   ✅ Test obfuscated code before deploying

### ❌ DON'T

-   ❌ Don't scramble function/class names initially
-   ❌ Don't obfuscate Blade templates
-   ❌ Don't commit PHPBolt license to git
-   ❌ Don't deploy without testing
-   ❌ Don't disable backups in production

## Example Workflow

### Development

```bash
# 1. Configure package
vim config/obfuscator.php

# 2. Check what will be obfuscated
php artisan obfuscate:check --show-files

# 3. Test with dry run
php artisan obfuscate:run --dry-run

# 4. Run actual obfuscation
php artisan obfuscate:run

# 5. Check results
ls -la build/obfuscated/
php artisan obfuscate:status --report
```

### Production Deployment

```bash
# 1. Verify configuration
php artisan obfuscate:check

# 2. Run obfuscation (non-interactive)
php artisan obfuscate:run --force

# 3. Verify success
php artisan obfuscate:status --report

# 4. Deploy obfuscated code
rsync -avz build/obfuscated/ user@server:/var/www/html/
```

## Next Steps

📖 [Read Full Documentation](README.md)  
🔧 [Installation Guide](docs/INSTALLATION.md)  
🚀 [CI/CD Examples](docs/CI-CD-EXAMPLES.md)  
🔒 [Security Guide](SECURITY.md)

## Need Help?

-   📫 [Open an Issue](https://github.com/alexandruflorea/laravel-source-obfuscator/issues)
-   📚 [Read the Docs](README.md)
-   💬 [Discussions](https://github.com/alexandruflorea/laravel-source-obfuscator/discussions)

---

**🎉 You're ready to obfuscate!** Start with a dry run and work your way up.
