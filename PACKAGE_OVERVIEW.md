# Laravel Source Code Obfuscator - Package Overview

## Package Structure

```
laravel-source-obfuscator/
├── config/
│   └── obfuscator.php              # Main configuration file
├── src/
│   ├── Console/
│   │   └── Commands/
│   │       ├── ObfuscateCommand.php          # Main obfuscation command
│   │       ├── ObfuscateCheckCommand.php     # Configuration check command
│   │       ├── ObfuscateClearCommand.php     # Clean up command
│   │       └── ObfuscateStatusCommand.php    # Status display command
│   ├── Services/
│   │   └── ObfuscationService.php            # Core obfuscation logic
│   ├── Utilities/
│   │   ├── FileScanner.php                   # File scanning utility
│   │   └── PathResolver.php                  # Path resolution utility
│   └── ObfuscatorServiceProvider.php         # Laravel service provider
├── tests/
│   ├── Feature/
│   │   └── ObfuscateCommandTest.php          # Command tests
│   ├── Unit/
│   │   ├── FileScannerTest.php               # File scanner tests
│   │   └── PathResolverTest.php              # Path resolver tests
│   └── TestCase.php                          # Base test case
├── docs/
│   ├── INSTALLATION.md                       # Installation guide
│   └── CI-CD-EXAMPLES.md                     # CI/CD integration examples
├── .github/
│   └── workflows/
│       └── tests.yml                         # GitHub Actions workflow
├── composer.json                             # Package dependencies
├── phpunit.xml                               # PHPUnit configuration
├── README.md                                 # Main documentation
├── CHANGELOG.md                              # Version history
├── CONTRIBUTING.md                           # Contribution guidelines
├── SECURITY.md                               # Security policy
├── LICENSE                                   # MIT License
└── .gitignore                                # Git ignore rules
```

## Core Components

### 1. ObfuscationService

**Location:** `src/Services/ObfuscationService.php`

**Responsibilities:**
- PHPBolt validation and interaction
- File scanning and processing
- Backup management
- Report generation
- Logging and error handling

**Key Methods:**
- `obfuscate(array $options)` - Main obfuscation process
- `validatePhpBolt()` - Validate PHPBolt installation
- `obfuscateFile()` - Obfuscate individual file
- `createBackup()` - Create pre-obfuscation backup
- `generateReport()` - Generate obfuscation report

### 2. FileScanner

**Location:** `src/Utilities/FileScanner.php`

**Responsibilities:**
- Scan directories for files to obfuscate
- Apply include/exclude patterns
- Support wildcard matching
- Provide file statistics

**Key Methods:**
- `scan(array $paths)` - Scan directories
- `shouldIncludeFile(string $filePath)` - Check if file should be included
- `getStatistics(array $paths)` - Get file statistics

### 3. PathResolver

**Location:** `src/Utilities/PathResolver.php`

**Responsibilities:**
- Resolve relative and absolute paths
- Normalize paths
- Handle cross-platform path differences
- Join path segments

**Key Methods:**
- `resolve(string $path)` - Resolve path
- `getRelativePath(string $path)` - Get relative path
- `normalize(string $path)` - Normalize path
- `join(...$segments)` - Join path segments

### 4. CLI Commands

#### ObfuscateCommand
**Usage:** `php artisan obfuscate:run [options]`

**Options:**
- `--dry-run` - Simulate obfuscation
- `--skip-backup` - Skip backup creation
- `--force` - Skip confirmation
- `--verbose` - Detailed output

#### ObfuscateCheckCommand
**Usage:** `php artisan obfuscate:check [options]`

**Options:**
- `--show-files` - Display file list

#### ObfuscateStatusCommand
**Usage:** `php artisan obfuscate:status [options]`

**Options:**
- `--report` - Show last obfuscation report

#### ObfuscateClearCommand
**Usage:** `php artisan obfuscate:clear [options]`

**Options:**
- `--output` - Clear output only
- `--backups` - Clear backups only
- `--force` - Skip confirmation

## Configuration Options

### PHPBolt Settings
- `phpbolt_path` - Path to phpbolt.so extension
- `phpbolt_binary` - Path to PHPBolt CLI binary

### Path Configuration
- `output_dir` - Obfuscated output directory
- `include_paths` - Directories/files to obfuscate
- `exclude_paths` - Directories/files to skip
- `exclude_patterns` - Regex patterns to exclude

### Obfuscation Options
- `strip_comments` - Remove comments
- `strip_whitespace` - Remove whitespace
- `encode_strings` - Encode string literals
- `scramble_variables` - Scramble variable names
- `scramble_functions` - Scramble function names
- `scramble_classes` - Scramble class names
- `add_integrity_check` - Add integrity verification
- `encrypt` - Encrypt code

### Backup Configuration
- `enabled` - Enable automatic backups
- `path` - Backup directory
- `keep_last` - Number of backups to retain

### CI/CD Options
- `fail_on_error` - Fail build on errors
- `generate_report` - Create JSON report
- `report_path` - Report file path

### Performance Settings
- `parallel_processes` - Number of parallel processes
- `memory_limit` - Memory limit per process
- `timeout` - Timeout per file

## Workflow

### Standard Obfuscation Process

1. **Validation**
   - Check PHPBolt installation
   - Validate configuration
   - Verify paths exist

2. **Backup** (optional)
   - Create timestamped backup
   - Copy source files
   - Clean old backups

3. **Scanning**
   - Scan include paths
   - Apply exclude patterns
   - Build file list

4. **Processing**
   - Prepare output directory
   - Obfuscate each file
   - Copy non-PHP files (optional)
   - Track progress and errors

5. **Reporting**
   - Generate statistics
   - Create JSON report
   - Log results

6. **Cleanup**
   - Remove temporary files
   - Archive artifacts

## Integration Points

### Laravel Integration
- Service Provider: Auto-registration
- Config: Publishable configuration
- Artisan: CLI commands
- Logging: Uses Laravel Log facade

### PHPBolt Integration
- CLI: Executes phpbolt binary
- Extension: Validates phpbolt.so
- License: Validates PHPBolt license
- Options: Maps config to PHPBolt flags

### CI/CD Integration
- Exit Codes: Proper status codes
- Reports: JSON output for parsing
- Artifacts: Obfuscated code output
- Logging: Detailed error messages

## Use Cases

### Development
```bash
# Check configuration
php artisan obfuscate:check

# Test with dry run
php artisan obfuscate:run --dry-run

# Run obfuscation
php artisan obfuscate:run
```

### CI/CD Pipeline
```bash
# Validate setup
php artisan obfuscate:check

# Run obfuscation (non-interactive)
php artisan obfuscate:run --force

# Check results
php artisan obfuscate:status --report
```

### Maintenance
```bash
# View status
php artisan obfuscate:status

# Clear output
php artisan obfuscate:clear --output --force

# Clear backups
php artisan obfuscate:clear --backups --force
```

## Extension Points

### Custom Obfuscation Logic

Extend `ObfuscationService`:

```php
use AlexandruFlorea\LaravelSourceObfuscator\Services\ObfuscationService;

class CustomObfuscationService extends ObfuscationService
{
    protected function obfuscateFile(string $inputFile, string $outputFile, array $options): bool
    {
        // Custom obfuscation logic
        return parent::obfuscateFile($inputFile, $outputFile, $options);
    }
}
```

### Custom File Scanner

Extend `FileScanner`:

```php
use AlexandruFlorea\LaravelSourceObfuscator\Utilities\FileScanner;

class CustomFileScanner extends FileScanner
{
    protected function shouldIncludeFile(string $filePath): bool
    {
        // Custom filtering logic
        return parent::shouldIncludeFile($filePath);
    }
}
```

### Custom Commands

Create additional commands:

```php
use AlexandruFlorea\LaravelSourceObfuscator\Console\Commands\ObfuscateCommand;

class CustomObfuscateCommand extends ObfuscateCommand
{
    protected $signature = 'obfuscate:custom';
    
    public function handle(): int
    {
        // Custom command logic
        return parent::handle();
    }
}
```

## Performance Considerations

### Large Codebases
- Enable parallel processing
- Increase memory limits
- Adjust timeout settings
- Use specific include paths

### CI/CD Optimization
- Cache PHPBolt installation
- Cache Composer dependencies
- Use specific file patterns
- Generate reports for monitoring

### Production Deployment
- Use `--force` for non-interactive
- Enable fail on error
- Generate reports
- Archive artifacts

## Security Considerations

### Source Code Protection
- Keep original source secure
- Use separate repositories
- Implement access control
- Never commit license keys

### Backup Security
- Secure backup directories
- Encrypt sensitive backups
- Implement retention policies
- Restrict access

### Deployment Security
- Deploy only obfuscated code
- Verify obfuscation success
- Implement integrity checks
- Monitor for unauthorized access

## Testing

### Unit Tests
- PathResolver functionality
- FileScanner filtering
- Configuration validation

### Feature Tests
- Command registration
- Command execution
- Configuration loading

### Integration Tests
- PHPBolt integration
- File processing
- Report generation

## Maintenance

### Regular Tasks
- Update PHPBolt
- Update package dependencies
- Review security advisories
- Clean old backups

### Monitoring
- Check obfuscation reports
- Monitor error logs
- Track performance metrics
- Audit access logs

## Support

### Getting Help
1. Check documentation
2. Review examples
3. Search GitHub issues
4. Open new issue with details

### Reporting Bugs
- Provide PHP version
- Include Laravel version
- Include PHPBolt version
- Share error messages
- Describe steps to reproduce

### Feature Requests
- Describe use case
- Explain expected behavior
- Provide examples
- Discuss alternatives

---

**Package Version:** 1.0.0  
**Last Updated:** 2026-01-05  
**Maintainer:** Alexandru Florea

