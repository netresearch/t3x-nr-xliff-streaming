.PHONY: help install test lint fix analyse quality ci clean

## Display this help message
help:
	@echo 'TYPO3 Extension: XLIFF Streaming Parser'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## Install dependencies
install:
	composer install

## Run all tests
test:
	composer test

## Run unit tests only
test-unit:
	composer test:unit

## Run functional tests only
test-functional:
	composer test:functional

## Run PHP linter
lint:
	composer lint

## Fix code style issues
fix:
	composer fix

## Run static analysis (PHPStan)
analyse:
	composer analyse

## Run all quality checks (lint + analyse + test)
quality: lint analyse test
	@echo 'All quality checks passed!'

## Run CI pipeline locally
ci: quality
	@echo 'CI checks complete!'

## Clean build artifacts
clean:
	rm -rf .Build/
	rm -f .php-cs-fixer.cache
	rm -f composer.lock
	@echo 'Cleaned build artifacts'
