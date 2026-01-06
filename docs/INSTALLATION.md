# Installation Guide

This guide will walk you through installing and configuring the Laravel Source Code Obfuscator package.

## Prerequisites

Before installing this package, ensure you have:

-   PHP >= 8.0
-   Laravel >= 9.0
-   Composer
-   PHPBolt license and installation files

## Step-by-Step Installation

### 1. Install PHPBolt

PHPBolt is a commercial PHP obfuscator that must be installed separately.

#### For Ubuntu/Debian:

```bash
# Download PHPBolt (replace with your actual download link)
wget https://your-phpbolt-url/phpbolt-linux.tar.gz

# Extract
tar -xzf phpbolt-linux.tar.gz

# Run installer
sudo bash phpbolt-installer.sh

# Verify installation
phpbolt --version
```

#### For CentOS/RHEL:

```bash
# Download and extract PHPBolt
wget https://your-phpbolt-url/phpbolt-linux.tar.gz
tar -xzf phpbolt-linux.tar.gz

# Install
sudo bash phpbolt-installer.sh

# Verify
phpbolt --version
```

#### For macOS:

```bash
# Download PHPBolt for macOS
wget https://your-phpbolt-url/phpbolt-macos.tar.gz

# Extract and install
tar -xzf phpbolt-macos.tar.gz
sudo bash phpbolt-installer.sh

# Verify
phpbolt --version
```

#### Manual Installation:

If the installer doesn't work, manually install:

```bash
# Copy phpbolt.so to PHP extensions directory
sudo cp phpbolt.so $(php-config --extension-dir)/

# Copy CLI binary
sudo cp phpbolt /usr/local/bin/
sudo chmod +x /usr/local/bin/phpbolt

# Add to php.ini
echo "extension=phpbolt.so" | sudo tee -a $(php-config --ini-dir)/20-phpbolt.ini

# Restart PHP-FPM (if using)
sudo systemctl restart php-fpm
```

### 2. Install the Package

Install via Composer:

```bash
composer require aflorea4/laravel-source-obfuscator
```

### 3. Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Aflorea4\LaravelSourceObfuscator\ObfuscatorServiceProvider" --tag="config"
```

This creates `config/obfuscator.php`.

### 4. Configure Environment

Add to your `.env` file:

```env
# PHPBolt paths
PHPBOLT_PATH=/usr/lib/php/extensions/phpbolt.so
PHPBOLT_BINARY=/usr/bin/phpbolt

# Obfuscation output
OBFUSCATOR_OUTPUT_DIR=build/obfuscated

# Backup configuration
OBFUSCATOR_BACKUP_DIR=backups/pre-obfuscation
```

**Important:** Adjust paths according to your actual PHPBolt installation.

### 5. Verify Installation

Check if everything is configured correctly:

```bash
php artisan obfuscate:check
```

This command will:

-   Verify PHPBolt installation
-   Show configuration summary
-   Display files that will be obfuscated

### 6. Configure Include/Exclude Paths

Edit `config/obfuscator.php` to customize which files to obfuscate:

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
    'bootstrap/cache',
    'public',
    '*.blade.php',  // Important: exclude Blade templates
],
```

### 7. Test with Dry Run

Before running actual obfuscation, test with a dry run:

```bash
php artisan obfuscate:run --dry-run
```

This will show you what would happen without actually modifying files.

## Docker Installation

If you're using Docker, create a custom image with PHPBolt:

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHPBolt
COPY phpbolt.so /usr/local/lib/php/extensions/no-debug-non-zts-20220829/
COPY phpbolt /usr/local/bin/
RUN chmod +x /usr/local/bin/phpbolt && \
    echo "extension=phpbolt.so" > /usr/local/etc/php/conf.d/phpbolt.ini

# Set working directory
WORKDIR /var/www/html

# Install Laravel and the obfuscator package
RUN composer require aflorea4/laravel-source-obfuscator
```

## Verifying the Installation

### Check PHPBolt Extension

```bash
php -m | grep phpbolt
```

Should output: `phpbolt`

### Check PHPBolt CLI

```bash
phpbolt --version
```

Should display PHPBolt version information.

### Run Configuration Check

```bash
php artisan obfuscate:check
```

Expected output:

```
PHPBolt Configuration Check:
  Binary Path: /usr/bin/phpbolt
  Status: ✓ PHPBolt is properly configured

Configuration Summary:
...
```

## Troubleshooting

### PHPBolt Not Found

**Problem:** `PHPBolt binary not found`

**Solution:**

```bash
# Find where phpbolt is installed
which phpbolt

# Update .env with correct path
PHPBOLT_BINARY=/path/to/phpbolt
```

### Extension Not Loaded

**Problem:** `PHP Warning: PHP Startup: Unable to load dynamic library 'phpbolt.so'`

**Solution:**

```bash
# Check if phpbolt.so exists
ls -l $(php-config --extension-dir)/phpbolt.so

# If not found, copy it
sudo cp /path/to/phpbolt.so $(php-config --extension-dir)/

# Restart PHP
sudo systemctl restart php-fpm
```

### Permission Denied

**Problem:** `Permission denied when creating output directory`

**Solution:**

```bash
# Create directories with proper permissions
mkdir -p build/obfuscated
mkdir -p backups/pre-obfuscation
chmod -R 775 build backups

# Ensure Laravel can write
sudo chown -R www-data:www-data build backups
```

### PHPBolt License Issues

**Problem:** `PHPBolt license validation failed`

**Solution:**

```bash
# Activate PHPBolt license
phpbolt --activate YOUR_LICENSE_KEY

# Verify license
phpbolt --check-license
```

## Next Steps

After successful installation:

1. **Customize Configuration** - Edit `config/obfuscator.php` to suit your needs
2. **Test Obfuscation** - Run `php artisan obfuscate:run --dry-run`
3. **Review Output** - Check the output directory for obfuscated files
4. **Integrate CI/CD** - Set up obfuscation in your deployment pipeline
5. **Monitor Performance** - Adjust performance settings if needed

## Updating

To update the package:

```bash
composer update alexandruflorea/laravel-source-obfuscator
```

After updating, republish the configuration if there are new options:

```bash
php artisan vendor:publish --provider="Aflorea4\LaravelSourceObfuscator\ObfuscatorServiceProvider" --tag="config" --force
```

## Uninstallation

To uninstall the package:

```bash
# Remove package
composer remove alexandruflorea/laravel-source-obfuscator

# Remove configuration
rm config/obfuscator.php

# Clean up directories
rm -rf build/obfuscated backups/pre-obfuscation
```

## Support

If you encounter issues during installation:

1. Check the [Troubleshooting](#troubleshooting) section
2. Review PHPBolt documentation
3. Open an issue on GitHub with:
    - PHP version (`php -v`)
    - Laravel version
    - PHPBolt version
    - Error messages
    - Installation steps you followed

---

**Need Help?** Open an issue on [GitHub](https://github.com/alexandruflorea/laravel-source-obfuscator/issues)
