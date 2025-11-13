# Code Coverage Setup

## Install Coverage Driver

To enable code coverage reporting, install the pcov extension:

```bash
# Debian/Ubuntu
sudo apt-get install php8.4-pcov

# Or via PECL (if available)
pecl install pcov

# Verify installation
php -m | grep pcov
```

## Run Tests with Coverage

```bash
# Text coverage report
composer test:unit -- --coverage-text

# HTML coverage report
composer test:unit -- --coverage-html .Build/coverage

# Clover XML for CI
composer test:unit -- --coverage-clover coverage.xml
```

## Expected Coverage

The extension should maintain >95% code coverage with:

- All parser methods covered
- All exception paths tested
- Security test scenarios verified
- Edge cases handled

## CI Integration

GitHub Actions workflow automatically:
- Runs tests with coverage on PHP 8.2
- Uploads coverage reports to Codecov
- Fails builds if coverage drops significantly

## Notes

- PHPUnit warnings from security tests are expected (XXE/DoS tests)
- The `failOnWarning="false"` setting prevents test failures from these intentional warnings
- Coverage metadata is required on all test methods (`#[CoversClass]` attribute)
