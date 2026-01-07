---
name: Bug report
about: Create a report to help us improve
title: '[BUG] '
labels: bug
assignees: ''
---

**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Run command '...'
2. Configure '....'
3. Execute '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Error Messages**
```
Paste any error messages here
```

**Environment (please complete the following information):**
- PHP Version: [e.g. 8.2]
- Laravel Version: [e.g. 10.x]
- Package Version: [e.g. 1.0.0]
- bolt.so Installed: [Yes/No]
- bolt.so Version: [if applicable]
- Operating System: [e.g. Ubuntu 22.04]

**Configuration**
```php
// Relevant parts of config/obfuscator.php
```

**Additional context**
Add any other context about the problem here.

**Obfuscation Command Used**
```bash
php artisan obfuscate:...
```

**Have you checked?**
- [ ] bolt.so extension is loaded (`php -m | grep bolt`)
- [ ] bolt_encrypt() function exists (`php -r "var_dump(function_exists('bolt_encrypt'));"`)
- [ ] Configuration is published
- [ ] Ran `php artisan obfuscate:check`


