# Production Deployment Guide

## Overview

The `--production-ready` flag creates a deployment-ready Laravel bundle with obfuscated source code. This guide shows you how to create and deploy the bundle to your production server.

## What's Included/Excluded

### ✅ Included in Bundle

- **Source code** (obfuscated based on `--source` flags)
- **Application structure**: `app/`, `routes/`, `config/`, `database/`, `resources/`, `public/`
- **Dependency manifests**: `composer.json`, `package.json`
- **Entry points**: `artisan`, `public/index.php`, `server.php`
- **Public assets**: CSS, JS, images in `public/`
- **Views and templates**: Blade files, React/Vue components

### ❌ Excluded from Bundle

- **Dependencies**: `vendor/`, `node_modules/` (install on server)
- **Environment files**: `.env*` (configure on server)
- **Version control**: `.git/`, `.github/`
- **Development tools**: `tests/`, `phpunit.xml`, `.editorconfig`
- **Lock files**: `composer.lock`, `package-lock.json` (generated during install)
- **Cache/logs**: `storage/logs/`, `bootstrap/cache/`

## Step 1: Create Production Bundle

### Basic Usage

```bash
# Obfuscate all configured paths (from config/obfuscator.php)
php artisan obfuscate:run --production-ready

# Obfuscate specific directories only
php artisan obfuscate:run --production-ready --source=app --source=routes

# Custom destination
php artisan obfuscate:run --production-ready --destination=deploy/production

# Non-interactive (for CI/CD)
php artisan obfuscate:run --production-ready --force
```

### Example: Full Production Build

```bash
# Create production bundle with obfuscated app and routes
php artisan obfuscate:run \
  --production-ready \
  --destination=deploy/$(date +%Y%m%d-%H%M%S) \
  --source=app \
  --source=routes \
  --force

# Save the encryption key shown in the output!
# Example: Encryption Key: ABC123XYZ
```

**Important**: Save the encryption key! You'll need it in Step 4.

## Step 2: Package the Bundle

```bash
# Navigate to your bundle directory
cd deploy/20260107-150000

# Create a tarball (recommended for deployment)
tar -czf ../production-bundle.tar.gz .

# Or create a zip file
zip -r ../production-bundle.zip .
```

## Step 3: Upload to Server

### Option A: Direct Upload

```bash
# Using SCP
scp production-bundle.tar.gz user@server:/var/www/html/

# Using rsync
rsync -avz --exclude='.git' deploy/20260107-150000/ user@server:/var/www/html/
```

### Option B: Via CI/CD

See [CI-CD-EXAMPLES.md](./CI-CD-EXAMPLES.md) for GitHub Actions, GitLab CI, and Jenkins examples.

## Step 4: Server Setup

### 1. Extract Bundle

```bash
ssh user@server

cd /var/www/html
tar -xzf production-bundle.tar.gz
rm production-bundle.tar.gz
```

### 2. Install Dependencies

```bash
# Install PHP dependencies (production only, optimized)
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies (if needed)
npm install --production

# Or using yarn
yarn install --production
```

### 3. Configure Environment

```bash
# Create environment file
cp .env.example .env

# Edit environment variables
nano .env
```

**Critical**: Set the encryption key from Step 1:

```env
# In .env file
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-laravel-app-key-here

# PHPBolt encryption key (from obfuscation output)
PHPBOLT_KEY=ABC123XYZ
```

### 4. Set Up Encryption Key in Code

The bolt extension needs the key defined as a constant. Add this to `public/index.php` **before** the Laravel bootstrap:

```php
<?php

// Define PHPBolt decryption key (BEFORE Laravel bootstrap)
define('PHP_BOLT_KEY', env('PHPBOLT_KEY', 'your-key-here'));

// Laravel bootstrap continues...
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
// ... rest of index.php
```

### 5. Set Permissions

```bash
# Set ownership
chown -R www-data:www-data /var/www/html

# Set directory permissions
find /var/www/html -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/html -type f -exec chmod 644 {} \;

# Set storage and cache permissions
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
```

### 6. Optimize Laravel

```bash
# Generate application key (if not set)
php artisan key:generate

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 7. Restart Services

```bash
# PHP-FPM
sudo systemctl restart php8.2-fpm

# Nginx
sudo systemctl restart nginx

# Or Apache
sudo systemctl restart apache2
```

## Step 5: Verify Deployment

### Check Application

```bash
# Test artisan commands
php artisan about

# Check routes
php artisan route:list

# Test queue workers (if used)
php artisan queue:work --once
```

### Check Web Access

```bash
# Test with curl
curl -I https://your-domain.com

# Or visit in browser
https://your-domain.com
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Deploy Production

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
          php-version: 8.2
          extensions: bolt
      
      - name: Install dependencies
        run: composer install --no-dev
      
      - name: Create production bundle
        run: |
          php artisan obfuscate:run \
            --production-ready \
            --destination=deploy/prod \
            --source=app \
            --source=routes \
            --force
          echo "BOLT_KEY=$(grep 'Encryption Key:' output.log | awk '{print $3}')" >> $GITHUB_ENV
      
      - name: Deploy to server
        run: |
          rsync -avz deploy/prod/ ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }}:/var/www/html/
      
      - name: Setup production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_KEY }}
          script: |
            cd /var/www/html
            composer install --no-dev --optimize-autoloader
            echo "PHPBOLT_KEY=${{ env.BOLT_KEY }}" >> .env
            php artisan config:cache
            php artisan route:cache
            sudo systemctl restart php8.2-fpm
```

## Troubleshooting

### Issue: "bolt_decrypt() function not found"

**Solution**: Install bolt.so extension on production server:

```bash
# Download bolt extension
wget https://github.com/arshidkv12/phpBolt/releases/latest/download/bolt.so

# Install extension
sudo cp bolt.so $(php-config --extension-dir)/
echo "extension=bolt.so" | sudo tee /etc/php/8.2/mods-available/bolt.ini
sudo phpenmod bolt

# Restart PHP
sudo systemctl restart php8.2-fpm

# Verify
php -m | grep bolt
```

### Issue: "Undefined constant PHP_BOLT_KEY"

**Solution**: Define the constant in `public/index.php` before Laravel bootstrap (see Step 4.4 above).

### Issue: "Class not found" errors

**Solution**: Clear and regenerate autoloader:

```bash
composer dump-autoload --optimize
php artisan config:clear
php artisan cache:clear
```

### Issue: Permission denied errors

**Solution**: Fix file permissions (see Step 4.5 above).

## Best Practices

1. **Always test** the bundle in a staging environment first
2. **Save encryption keys** securely (use secrets management)
3. **Use --dry-run** to preview what will be obfuscated
4. **Version your bundles** using timestamps or version numbers
5. **Keep backups** of previous deployments
6. **Monitor logs** after deployment for any decryption issues
7. **Use --source** to obfuscate only what's needed (not views, not config)

## Security Notes

- **Never commit** `.env` files or encryption keys to version control
- **Rotate keys** periodically in production
- **Restrict access** to deployment scripts and keys
- **Use HTTPS** for all deployment transfers
- **Audit deployments** regularly

## Support

- **Documentation**: [README.md](../README.md)
- **CI/CD Examples**: [CI-CD-EXAMPLES.md](./CI-CD-EXAMPLES.md)
- **Issues**: https://github.com/aflorea4/laravel-source-obfuscator/issues

