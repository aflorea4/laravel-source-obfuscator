# Security Policy

## Supported Versions

We release patches for security vulnerabilities. Currently supported versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within Laravel Source Obfuscator, please send an email to Alexandru Florea at alexandru@aflorea.dev. All security vulnerabilities will be promptly addressed.

### What to Include

When reporting a vulnerability, please include:

1. **Description** - A clear description of the vulnerability
2. **Steps to Reproduce** - Detailed steps to reproduce the issue
3. **Impact** - What an attacker could achieve
4. **Affected Versions** - Which versions are affected
5. **Possible Solution** - If you have suggestions for fixing the issue

### Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Timeline**: Depends on severity (critical issues within 7-14 days)

## Security Best Practices

When using this package:

### 1. Secure PHPBolt License

- Store PHPBolt license keys securely
- Never commit license keys to version control
- Use environment variables for sensitive configuration

### 2. Protect Original Source Code

- Keep unobfuscated source code in secure repositories
- Implement proper access control
- Use separate repositories for obfuscated deployments

### 3. Backup Security

- Ensure backup directories are not web-accessible
- Implement proper file permissions (755 for directories, 644 for files)
- Regularly clean old backups
- Consider encrypting sensitive backups

### 4. Output Directory Protection

- Never serve obfuscated output directory directly
- Use proper deployment processes
- Implement .htaccess or nginx rules to prevent direct access

### 5. CI/CD Security

- Use secure environment variables for credentials
- Implement proper artifact handling
- Restrict access to obfuscation commands
- Use secure artifact storage

### 6. File Permissions

```bash
# Recommended permissions
chmod 755 build/
chmod 755 backups/
chmod 644 config/obfuscator.php
```

### 7. Environment Configuration

```env
# Use strong, unique values
PHPBOLT_PATH=/secure/path/to/phpbolt.so
PHPBOLT_BINARY=/usr/local/bin/phpbolt

# Restrict output paths
OBFUSCATOR_OUTPUT_DIR=build/obfuscated
```

### 8. Production Deployment

- Only deploy obfuscated code to production
- Verify obfuscation before deployment
- Implement integrity checks
- Monitor for unauthorized access

### 9. Access Control

- Restrict who can run obfuscation commands
- Implement role-based access control
- Audit obfuscation activities
- Use separate deployment accounts

### 10. Regular Updates

- Keep the package updated
- Monitor security advisories
- Update PHPBolt regularly
- Apply security patches promptly

## Known Security Considerations

### PHPBolt Dependency

This package depends on PHPBolt, a commercial obfuscation tool:

- Ensure PHPBolt is obtained from official sources
- Keep PHPBolt updated
- Follow PHPBolt security guidelines
- Validate PHPBolt integrity

### Source Code Exposure

During obfuscation:

- Temporary files may contain unobfuscated code
- Ensure temporary directories are secure
- Clean up temporary files after processing
- Monitor for unauthorized access

### Backup Management

Automatic backups contain unobfuscated source code:

- Implement secure backup storage
- Use encryption for sensitive backups
- Regularly audit backup access
- Implement retention policies

## Vulnerability Disclosure Policy

We follow coordinated vulnerability disclosure:

1. **Report** - Privately report security issues
2. **Acknowledge** - We acknowledge receipt within 48 hours
3. **Investigate** - We investigate and develop a fix
4. **Release** - We release a security update
5. **Disclose** - We publicly disclose after users have had time to update

### Public Disclosure Timeline

- **Critical**: 14 days after fix release
- **High**: 30 days after fix release
- **Medium**: 60 days after fix release
- **Low**: 90 days after fix release

## Security Hall of Fame

We recognize security researchers who help make this package more secure:

<!-- Contributors will be listed here -->

## Contact

For security concerns, contact:
- Email: alexandru@aflorea.dev
- GitHub: https://github.com/aflorea4

For general issues, use GitHub Issues.

---

**Note**: This security policy is subject to change. Please check regularly for updates.

Last Updated: 2026-01-05

