# Contributing to Laravel Source Obfuscator

First off, thank you for considering contributing to Laravel Source Obfuscator! It's people like you that make this package better for everyone.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** to demonstrate the steps
- **Describe the behavior you observed** and what you expected to see
- **Include screenshots or error messages** if applicable
- **Specify your environment**:
  - PHP version
  - Laravel version
  - PHPBolt version
  - Operating system

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide a detailed description** of the suggested enhancement
- **Explain why this enhancement would be useful**
- **List some examples** of how it would be used

### Pull Requests

1. Fork the repository
2. Create a new branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests to ensure nothing breaks
5. Commit your changes (`git commit -m 'Add some amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

#### Pull Request Guidelines

- **Follow the existing code style**
- **Write clear commit messages**
- **Include tests** for new features
- **Update documentation** as needed
- **Keep pull requests focused** - one feature/fix per PR
- **Ensure all tests pass**

## Development Setup

### Prerequisites

- PHP >= 8.0
- Composer
- Laravel >= 9.0
- PHPBolt (for testing actual obfuscation)

### Setup Steps

1. Clone the repository:
```bash
git clone https://github.com/aflorea4/laravel-source-obfuscator.git
cd laravel-source-obfuscator
```

2. Install dependencies:
```bash
composer install
```

3. Run tests:
```bash
composer test
```

## Coding Standards

### PHP

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use type hints where applicable
- Write PHPDoc comments for all methods
- Keep methods focused and single-purpose

### Example

```php
/**
 * Obfuscate a single file.
 *
 * @param string $inputFile Path to the input file
 * @param string $outputFile Path to the output file
 * @param array $options Additional options
 * @return bool True if successful, false otherwise
 */
protected function obfuscateFile(string $inputFile, string $outputFile, array $options): bool
{
    // Implementation
}
```

## Testing

- Write tests for all new features
- Ensure existing tests pass
- Aim for high code coverage
- Use descriptive test names

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage
```

## Documentation

- Update README.md for new features
- Add examples for new functionality
- Keep documentation clear and concise
- Update CHANGELOG.md

## Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code style changes (formatting, etc.)
- `refactor:` Code refactoring
- `test:` Adding or updating tests
- `chore:` Maintenance tasks

### Examples

```
feat: add support for custom obfuscation engines
fix: resolve issue with backup directory creation
docs: update CI/CD integration examples
```

## Questions?

Feel free to open an issue with your question or reach out to the maintainers.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing! 🎉

