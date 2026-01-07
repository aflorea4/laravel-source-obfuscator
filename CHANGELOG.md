# Changelog

All notable changes to `laravel-source-obfuscator` will be documented in this file.

## [1.0.5] - 2026-01-07

### Changed
- **BREAKING**: Changed default output directory from `build/obfuscated` to `production/obfuscated`
- **BREAKING**: Backups are now disabled by default (opt-in instead of opt-out)
- Changed `--skip-backup` flag to `--backup` flag (opt-in behavior)
- Updated configuration: `backup.enabled` now defaults to `false`

### Improved
- Simplified workflow: No backup unless explicitly requested
- Better CI/CD integration with cleaner defaults
- Clearer command-line interface

## [1.0.4] - 2026-01-07

### Fixed
- Fixed option name mismatch: `skip_backup` vs `skip-backup` in ObfuscateCommand

## [1.0.3] - 2026-01-07

### Fixed
- Removed references to removed config keys (`encode_strings`, `encrypt`) from display configuration

## [1.0.2] - 2026-01-07

### Fixed
- Removed custom `--verbose` option to avoid conflict with Laravel's built-in verbosity
- Users can now use Laravel's native `-v`, `-vv`, `-vvv` options

## [1.0.1] - 2026-01-07

### Added
- Support for Laravel 12
- Support for Laravel 8
- Support for PHP 7.4

### Changed
- Updated dependencies to support Laravel 8-12
- Updated PHPUnit and Orchestra Testbench for broader compatibility

## [1.0.0] - 2026-01-05

### IMPORTANT - Implementation Corrected

After reviewing working PHPBolt implementations, we discovered and fixed a critical implementation error:
- **Corrected**: PHPBolt is a PHP extension (`bolt.so`), not a CLI binary
- **Changed**: Now uses `bolt_encrypt()` PHP function directly instead of external processes
- **Fixed**: Extension name is `'bolt'` not `'phpbolt'`
- **Removed**: Symfony Process dependency (no longer needed)
- **Simplified**: More efficient direct PHP function calls

### Added
- Initial release
- PHPBolt extension integration for source code obfuscation
- Comprehensive configuration system with include/exclude paths
- Four CLI commands:
  - `obfuscate:run` - Execute obfuscation process
    - Command-line source path override (`--source` option)
    - Command-line destination override (`--destination` option)
  - `obfuscate:check` - Verify configuration and preview files
  - `obfuscate:status` - Display obfuscation status
  - `obfuscate:clear` - Clean output and backup directories
- Automatic backup system with rotation
- CI/CD integration support
- Detailed reporting and logging
- Performance optimization with parallel processing support
- Dry-run mode for safe testing
- File scanner with pattern matching
- Path resolution utilities
- Smart file inclusion/exclusion system
- Comprehensive documentation

### Credits & Inspiration
- PHPBolt extension usage inspired by [jaydeepukani/laravel-source-obfuscator](https://github.com/jaydeepukani/Laravel-Source-Obfuscator)
- Uses [arshidkv12/phpBolt](https://github.com/arshidkv12/phpBolt) free PHP extension
- Command-line path overrides inspired by jaydeepukani's implementation
- Significantly expanded with enterprise features, backup system, CI/CD support, and comprehensive documentation

### Features
- Strip comments
- Strip whitespace
- Encode string literals
- Scramble variables
- Scramble functions (optional)
- Scramble classes (optional)
- Add integrity checks
- Code encryption
- Non-PHP file copying
- Directory structure preservation

### Security
- Automatic backup before obfuscation
- Validation checks before processing
- Safe failure handling
- Error reporting

### Performance
- Parallel processing support
- Configurable memory limits
- Per-file timeout settings
- Optimized file scanning

## [Unreleased]

### Planned
- Support for additional obfuscation engines
- Web-based configuration UI
- Real-time obfuscation monitoring
- Laravel Forge integration
- Docker support
- Advanced scheduling options
- Cache warming for obfuscated code
- Incremental obfuscation support

