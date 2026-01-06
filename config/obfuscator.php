<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PHPBolt Extension
    |--------------------------------------------------------------------------
    |
    | PHPBolt extension name. The bolt.so extension must be loaded in PHP.
    | Check with: php -m | grep bolt
    |
    */
    'extension_name' => 'bolt',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | The key used to encrypt your source code. Keep this secure and use
    | environment variables. This key will be needed at runtime to decrypt.
    | Minimum 6 characters recommended.
    |
    */
    'encryption_key' => env('PHPBOLT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Key Length
    |--------------------------------------------------------------------------
    |
    | Length of the encryption key. Default is 6.
    |
    */
    'key_length' => env('PHPBOLT_KEY_LENGTH', 6),

    /*
    |--------------------------------------------------------------------------
    | Output Directory
    |--------------------------------------------------------------------------
    |
    | Directory where obfuscated files will be stored. This path is relative
    | to your project root unless an absolute path is provided.
    |
    */
    'output_dir' => env('OBFUSCATOR_OUTPUT_DIR', 'build/obfuscated'),

    /*
    |--------------------------------------------------------------------------
    | Include Paths
    |--------------------------------------------------------------------------
    |
    | Array of directories/files to include in the obfuscation process.
    | Paths are relative to your project root.
    |
    */
    'include_paths' => [
        'app',
        'routes',
        'config',
        'database/migrations',
        'database/seeders',
        // Add more paths as needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Paths
    |--------------------------------------------------------------------------
    |
    | Array of directories/files to exclude from obfuscation.
    | These paths support wildcards (*, **).
    |
    */
    'exclude_paths' => [
        'tests',
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        'public',
        '*.blade.php',  // Exclude Blade templates
        '*.stub',
        '*.md',
        '*.json',
        '*.xml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Patterns
    |--------------------------------------------------------------------------
    |
    | Regular expression patterns for files to exclude from obfuscation.
    |
    */
    'exclude_patterns' => [
        '/\.env/',
        '/\.git/',
        '/\.gitignore/',
        '/composer\.(json|lock)/',
        '/package(-lock)?\.json/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Obfuscation Options
    |--------------------------------------------------------------------------
    |
    | Configure various obfuscation options for PHPBolt.
    |
    */
    'obfuscation' => [
        // Strip comments from the code
        'strip_comments' => true,

        // Strip whitespace to reduce file size
        'strip_whitespace' => true,

        // Note: The following options are applied via bolt_encrypt()
        // The bolt extension handles: string encoding, variable scrambling,
        // integrity checks, and encryption automatically
    ],

    /*
    |--------------------------------------------------------------------------
    | Copy Non-PHP Files
    |--------------------------------------------------------------------------
    |
    | Whether to copy non-PHP files to the output directory.
    |
    */
    'copy_non_php_files' => true,

    /*
    |--------------------------------------------------------------------------
    | Preserve Directory Structure
    |--------------------------------------------------------------------------
    |
    | Whether to preserve the original directory structure in the output.
    |
    */
    'preserve_structure' => true,

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Options for creating backups before obfuscation.
    |
    */
    'backup' => [
        // Enable automatic backups
        'enabled' => true,

        // Backup directory path
        'path' => env('OBFUSCATOR_BACKUP_DIR', 'backups/pre-obfuscation'),

        // Keep only the last N backups
        'keep_last' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for the obfuscation process.
    |
    */
    'logging' => [
        // Enable detailed logging
        'enabled' => true,

        // Log file path
        'path' => storage_path('logs/obfuscator.log'),

        // Log level (debug, info, warning, error)
        'level' => 'info',
    ],

    /*
    |--------------------------------------------------------------------------
    | CI/CD Mode
    |--------------------------------------------------------------------------
    |
    | Options specific to CI/CD pipeline execution.
    |
    */
    'ci_mode' => [
        // Fail the build on obfuscation errors
        'fail_on_error' => true,

        // Generate a report file
        'generate_report' => true,

        // Report output path
        'report_path' => 'build/obfuscation-report.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure performance-related options.
    |
    */
    'performance' => [
        // Number of parallel processes (0 = auto-detect)
        'parallel_processes' => 0,

        // Memory limit per process (e.g., '256M', '1G')
        'memory_limit' => '512M',

        // Timeout for each file obfuscation in seconds
        'timeout' => 60,
    ],
];

