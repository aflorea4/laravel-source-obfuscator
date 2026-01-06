# PHPBolt Implementation Fix - CRITICAL

## Problem Discovered

Our implementation incorrectly assumed PHPBolt works as a CLI tool. After analyzing the source code of:
- [jaydeepukani/Laravel-Source-Obfuscator](https://github.com/jaydeepukani/Laravel-Source-Obfuscator)
- [arshidkv12/phpBolt](https://github.com/arshidkv12/phpBolt)

We discovered that PHPBolt is a **PHP extension**, not a CLI binary!

## Wrong Implementation (Ours)

```php
// ❌ WRONG - There is NO phpbolt CLI binary!
$process = new Process([
    '/usr/bin/phpbolt',
    '--encrypt',
    '-i', $inputFile,
    '-o', $outputFile
]);
$process->run();
```

## Correct Implementation

### 1. Check Extension

```php
// ✅ CORRECT - Extension is called 'bolt', not 'phpbolt'
if (!extension_loaded('bolt')) {
    throw new \RuntimeException('PHPBolt extension (bolt.so) is not loaded');
}
```

### 2. Encrypt Files

```php
// ✅ CORRECT - Use PHP function directly
public function obfuscateFile(string $inputFile, string $outputFile, string $key): bool
{
    // Read source file
    $contents = file_get_contents($inputFile);
    
    // Remove opening PHP tag if present
    $contents = preg_replace('/^\<\?php\s*/', '', $contents);
    
    // Encrypt using PHPBolt function
    $cipher = bolt_encrypt($contents, $key);
    
    // Prepare decrypt header
    $header = '<?php bolt_decrypt(__FILE__, PHP_BOLT_KEY); return 0;';
    $separator = '##!##';
    
    // Write encrypted file
    file_put_contents($outputFile, $header . $separator . $cipher);
    
    return true;
}
```

### 3. Configuration

```php
// ✅ CORRECT configuration
'phpbolt' => [
    'extension' => 'bolt',  // Extension name
    'key' => env('PHPBOLT_KEY', 'your-secret-key'),  // Encryption key
],
```

### 4. Runtime Requirements

For the obfuscated code to run:
1. `bolt.so` extension must be loaded in production
2. `PHP_BOLT_KEY` constant must be defined (usually in extension config)
3. No CLI binary is needed!

## Key Differences from Our Implementation

| Aspect | Our (Wrong) Implementation | Correct Implementation |
|--------|---------------------------|------------------------|
| **Extension Name** | 'phpbolt' | 'bolt' |
| **Binary Path** | '/usr/bin/phpbolt' (doesn't exist!) | No binary needed |
| **Encryption Method** | CLI process | PHP function `bolt_encrypt()` |
| **Extension Check** | `extension_loaded('phpbolt')` | `extension_loaded('bolt')` |
| **File Processing** | Execute external binary | Direct PHP function call |
| **Command-line Args** | `--encrypt`, `-i`, `-o` | No args - just function call |

## What Needs to Change

### Files to Update

1. **src/Services/ObfuscationService.php**
   - Remove all Process/Symfony Process code
   - Use `bolt_encrypt()` directly
   - Check for 'bolt' extension, not 'phpbolt'
   - Remove CLI binary validation
   - Add encryption key management

2. **config/obfuscator.php**
   - Remove `phpbolt_binary` config
   - Change `phpbolt_path` to just `extension` check
   - Add `encryption_key` config
   - Update documentation comments

3. **README.md & Documentation**
   - Remove all CLI binary references
   - Explain it's a PHP extension
   - Update installation instructions
   - Explain runtime requirements

4. **INSTALLATION.md**
   - Remove CLI binary installation steps
   - Focus only on extension installation
   - Add extension verification steps

## Installation Instructions (Corrected)

### Install bolt.so Extension

```bash
# Download bolt.so for your PHP version
wget https://phpbolt.com/downloads/bolt-php8.2.so

# Copy to extensions directory
sudo cp bolt-php8.2.so $(php-config --extension-dir)/bolt.so

# Enable extension
echo "extension=bolt.so" | sudo tee /etc/php/8.2/mods-available/bolt.ini
sudo phpenmod bolt

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Verify installation
php -m | grep bolt
```

### No CLI Binary Needed!

There is **no** `/usr/bin/phpbolt` binary. The extension provides PHP functions only.

## Correct Usage Example

### Obfuscation (Build Time)

```php
// Load extension (bolt.so must be loaded)
if (!extension_loaded('bolt')) {
    die('bolt extension not loaded');
}

// Define encryption key
$key = 'your-secret-encryption-key';

// Read source file
$source = file_get_contents('app/MyClass.php');

// Remove opening tag
$source = preg_replace('/^\<\?php\s*/', '', $source);

// Encrypt
$encrypted = bolt_encrypt($source, $key);

// Write with decrypt header
$output = '<?php bolt_decrypt(__FILE__, PHP_BOLT_KEY); return 0;##!##' . $encrypted;
file_put_contents('encrypted/MyClass.php', $output);
```

### Runtime (Production)

```php
// Extension must be loaded
// When PHP executes the encrypted file:
// 1. Reads: <?php bolt_decrypt(__FILE__, PHP_BOLT_KEY); return 0;##!##[encrypted]
// 2. bolt_decrypt() decrypts the content using PHP_BOLT_KEY constant
// 3. Executes decrypted code
// 4. Original source is never exposed
```

## References

- **Official Repository**: https://github.com/arshidkv12/phpBolt
- **Encryption Example**: https://github.com/arshidkv12/phpBolt/blob/master/encryption.php
- **Working Laravel Package**: https://github.com/jaydeepukani/Laravel-Source-Obfuscator

## Action Items

- [ ] Rewrite `ObfuscationService` to use `bolt_encrypt()` directly
- [ ] Remove all Symfony Process code
- [ ] Update extension check from 'phpbolt' to 'bolt'
- [ ] Remove CLI binary validation
- [ ] Add encryption key management
- [ ] Update configuration file
- [ ] Update all documentation
- [ ] Add proper extension validation
- [ ] Test with actual bolt.so extension

## Severity

**🔴 CRITICAL** - Our current implementation will not work at all because:
1. We're looking for a binary that doesn't exist
2. We're checking for wrong extension name
3. We're trying to execute non-existent commands

The package needs to be **completely rewritten** for the ObfuscationService class.

---

**Date:** January 5, 2026  
**Discovered by:** Code review of working implementations  
**Impact:** Complete rewrite needed for core obfuscation functionality

