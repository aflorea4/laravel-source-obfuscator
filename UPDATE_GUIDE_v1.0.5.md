# Update Guide - v1.0.5

## 🎉 What's New in v1.0.5

### BREAKING CHANGES

This release includes important changes to default behavior:

#### 1. **New Default Output Directory**
- **Before:** `build/obfuscated`
- **After:** `production/obfuscated`

#### 2. **Backups Now Opt-In (Instead of Opt-Out)**
- **Before:** Backups were enabled by default, use `--skip-backup` to disable
- **After:** Backups are disabled by default, use `--backup` to enable

### Why These Changes?

These changes make the package more suitable for CI/CD pipelines where:
- Output typically goes to a `production` folder for deployment
- Backups are usually not needed in automated builds
- Simpler commands = fewer errors

---

## 📦 How to Update

### In Your Laravel Project

```bash
cd ~/Workspace/your-project

# Update to v1.0.5
composer update aflorea4/laravel-source-obfuscator

# Verify the update
composer show aflorea4/laravel-source-obfuscator | grep versions
```

### Update Your Configuration (Optional)

If you had custom configuration in `.env`, update it:

**Old:**
```env
OBFUSCATOR_OUTPUT_DIR=build/obfuscated
```

**New (or keep old if you prefer):**
```env
OBFUSCATOR_OUTPUT_DIR=production/obfuscated
```

---

## 🔄 Migration Guide

### If You Were Using Defaults

**Before v1.0.5:**
```bash
# Output went to: build/obfuscated/
# Backup was created automatically
php artisan obfuscate:run
```

**After v1.0.5:**
```bash
# Output goes to: production/obfuscated/
# No backup is created
php artisan obfuscate:run

# To create a backup (like before):
php artisan obfuscate:run --backup
```

### If You Were Using `--skip-backup`

**Before v1.0.5:**
```bash
php artisan obfuscate:run --skip-backup
```

**After v1.0.5:**
```bash
# Just remove --skip-backup (backups are now disabled by default)
php artisan obfuscate:run
```

### If You Want the Old Behavior

**Option 1: Command-line flags**
```bash
php artisan obfuscate:run --destination=build/obfuscated --backup
```

**Option 2: Update config file**

Edit `config/obfuscator.php`:
```php
'output_dir' => env('OBFUSCATOR_OUTPUT_DIR', 'build/obfuscated'),
'backup' => [
    'enabled' => true,  // Change from false to true
    // ...
],
```

---

## 📝 Updated Commands

### Basic Usage (New Defaults)
```bash
# Obfuscate to production/obfuscated, no backup
php artisan obfuscate:run

# Same, but with backup
php artisan obfuscate:run --backup

# Dry run to preview
php artisan obfuscate:run --dry-run

# CI/CD mode (non-interactive)
php artisan obfuscate:run --force
```

### Custom Output
```bash
# Custom destination
php artisan obfuscate:run --destination=build/production

# Custom sources
php artisan obfuscate:run --source=app --source=routes

# Both
php artisan obfuscate:run --source=app --destination=dist --backup --force
```

---

## 🚀 Update Your CI/CD Pipelines

### GitHub Actions

**Before:**
```yaml
- name: Obfuscate
  run: php artisan obfuscate:run --skip-backup --force
```

**After (simpler):**
```yaml
- name: Obfuscate
  run: php artisan obfuscate:run --force
```

**Update artifact paths:**
```yaml
- uses: actions/upload-artifact@v3
  with:
    name: obfuscated-code
    path: production/obfuscated/  # Changed from build/obfuscated/
```

### GitLab CI

**Update `.gitlab-ci.yml`:**
```yaml
artifacts:
  paths:
    - production/obfuscated/  # Changed from build/obfuscated/
```

### Jenkins

**Update Jenkinsfile:**
```groovy
archiveArtifacts artifacts: 'production/obfuscated/**/*'
```

---

## 🔍 What Hasn't Changed

- PHPBolt extension requirements
- Configuration file location (`config/obfuscator.php`)
- All other commands (`obfuscate:check`, `obfuscate:status`, `obfuscate:clear`)
- Include/exclude path configuration
- Encryption key handling
- Laravel version support (8.x - 12.x)

---

## 📚 Need Help?

- **Documentation:** [README.md](./README.md)
- **Quick Start:** [QUICK_START.md](./QUICK_START.md)
- **CI/CD Examples:** [docs/CI-CD-EXAMPLES.md](./docs/CI-CD-EXAMPLES.md)
- **Issues:** https://github.com/aflorea4/laravel-source-obfuscator/issues

---

## ✅ Verification Checklist

After updating, verify everything works:

- [ ] Package updated: `composer show aflorea4/laravel-source-obfuscator`
- [ ] Config cleared: `php artisan config:clear`
- [ ] Dry run works: `php artisan obfuscate:run --dry-run`
- [ ] Check output directory: Should show `production/obfuscated`
- [ ] Backup shows "No": Unless you use `--backup` flag
- [ ] CI/CD pipeline updated (if applicable)

---

**Version:** 1.0.5  
**Release Date:** January 7, 2026  
**Author:** Alexandru Florea (@aflorea4)

