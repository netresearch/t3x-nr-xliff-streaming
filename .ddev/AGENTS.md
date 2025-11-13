<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-11-13 -->

# .ddev/ — DDEV Development Environment

DDEV local development environment configuration for TYPO3 extension.

## 0. Skill Usage (DDEV Environment)

**ALWAYS invoke the typo3-ddev skill when working with DDEV configuration:**

```
When to invoke: Setting up DDEV, modifying config.yaml, environment issues
Command: Skill("netresearch-skills-bundle:typo3-ddev")
Purpose: Automate DDEV environment setup for TYPO3 extension development
```

**The skill provides:**
- ✅ DDEV configuration for TYPO3 extensions
- ✅ Multi-version TYPO3 testing environments (v12/v13)
- ✅ PHP version management (8.2, 8.3, 8.4)
- ✅ Database setup (MariaDB/MySQL)
- ✅ Development workflow optimization
- ✅ Troubleshooting DDEV issues

**Workflow pattern:**
1. Invoke typo3-ddev skill (`Skill("netresearch-skills-bundle:typo3-ddev")`)
2. Follow skill guidance for DDEV setup/configuration
3. Test environment with `ddev start && ddev composer install`
4. Verify extension functionality in DDEV context

## 1. Overview

This directory contains DDEV configuration for local development:
- **config.yaml** - Main DDEV configuration
- Project type: `typo3`
- PHP version: 8.2
- Database: MariaDB 10.11
- Web server: Apache-FPM
- Auto-runs `composer install` on start

**DDEV Goals:**
- Isolated development environment
- Consistent setup across team
- Quick start for new developers
- TYPO3 extension testing

## 2. Setup & environment

### Prerequisites
- DDEV installed (https://ddev.readthedocs.io/)
- Docker Desktop or Colima
- Basic understanding of DDEV commands

### First-time setup
```bash
# Start DDEV (auto-runs composer install)
ddev start

# Verify environment
ddev describe

# SSH into container
ddev ssh

# Access database
ddev mysql
```

### Current configuration
```yaml
name: nr-xliff-streaming
type: typo3
docroot: .Build/web
php_version: "8.2"
webserver_type: apache-fpm
database:
  type: mariadb
  version: "10.11"
composer_version: "2"
nodejs_version: "20"

hooks:
  post-start:
    - exec: composer install
```

## 3. Build & tests

### File-scoped commands (run from project root)
```bash
# Start environment
ddev start

# Stop environment
ddev stop

# Restart environment
ddev restart

# Run composer commands
ddev composer install
ddev composer update
ddev composer test
ddev composer lint
ddev composer fix
ddev composer analyse

# Run tests in DDEV
ddev composer test:unit
ddev composer test:functional

# SSH into container
ddev ssh

# View logs
ddev logs

# Describe environment
ddev describe
```

## 4. Code style & conventions

### config.yaml structure
```yaml
# Project identification
name: project-name        # Lowercase, hyphen-separated
type: typo3              # Project type (typo3, php, etc.)
docroot: .Build/web      # TYPO3 web root

# PHP configuration
php_version: "8.2"       # PHP version (8.2, 8.3, 8.4)
webserver_type: apache-fpm  # Web server type

# Database configuration
database:
  type: mariadb          # Database type (mariadb, mysql, postgres)
  version: "10.11"       # Database version

# Additional settings
composer_version: "2"    # Composer version
nodejs_version: "20"     # Node.js version (if needed)

# Hooks
hooks:
  post-start:            # Run after ddev start
    - exec: composer install
```

### Rules
- **Project name**: Lowercase with hyphens, matching composer package name
- **PHP version**: Match composer.json requirements
- **Database**: Use MariaDB for TYPO3 (better compatibility)
- **Hooks**: Automate setup tasks (composer install, migrations, etc.)
- **No secrets**: Never commit credentials or API keys in config.yaml

## 5. Security & safety

### DDEV security practices
- **Never commit `.ddev/config.local.yaml`** - May contain local credentials
- **Use environment variables** for sensitive data (not hardcoded values)
- **Gitignore patterns** - Ensure `.ddev/.gitignore` excludes local files
- **Database dumps** - Don't commit database dumps with PII
- **HTTPS** - DDEV provides automatic HTTPS for local development

### Safe practices
```bash
# Use environment variables for secrets
ddev exec export MY_API_KEY="secret"

# Don't hardcode in config.yaml
❌ Wrong: MY_API_KEY: "hardcoded-secret"
✅ Correct: Use .ddev/config.local.yaml (gitignored) or ddev config

# Database dumps (without sensitive data)
ddev export-db --gzip=false > dump.sql
# Review before committing
```

## 6. PR/commit checklist

Before committing DDEV configuration:

### Configuration Checks
- [ ] config.yaml has correct PHP version (matches composer.json)
- [ ] config.yaml has correct database type/version
- [ ] Project name matches composer package name
- [ ] No hardcoded secrets or credentials
- [ ] Hooks are functional and necessary

### Testing
- [ ] Run `ddev start` - environment starts successfully
- [ ] Run `ddev composer install` - dependencies install
- [ ] Run `ddev composer test` - tests pass in DDEV
- [ ] Verify web access to TYPO3 (if applicable)

### Gitignore
- [ ] `.ddev/.gitignore` includes local files
- [ ] `.ddev/config.local.yaml` gitignored
- [ ] Database dumps not committed
- [ ] SSH keys not committed

### Documentation
- [ ] README.md includes DDEV setup instructions
- [ ] Any custom commands documented
- [ ] Team members can replicate environment

## 7. Good vs. bad examples

### ✅ Good: Complete config.yaml
```yaml
name: nr-xliff-streaming
type: typo3
docroot: .Build/web
php_version: "8.2"
webserver_type: apache-fpm
xdebug_enabled: false

database:
  type: mariadb
  version: "10.11"

use_dns_when_possible: true
composer_version: "2"

hooks:
  post-start:
    - exec: composer install

nodejs_version: "20"
```

### ❌ Bad: Minimal config without hooks
```yaml
name: my-project
type: typo3
```

### ✅ Good: Multi-version PHP testing
```yaml
# For testing extension with multiple PHP versions
# Create separate configs or use DDEV snapshots

# PHP 8.2 (default)
php_version: "8.2"

# Switch to PHP 8.3
# ddev config --php-version=8.3
# ddev restart

# Switch to PHP 8.4
# ddev config --php-version=8.4
# ddev restart
```

### ❌ Bad: Hardcoded secrets
```yaml
web_environment:
  - API_KEY=hardcoded-secret-key  # Wrong!
```

### ✅ Good: Environment variables (local)
```yaml
# Use .ddev/config.local.yaml (gitignored)
web_environment:
  - API_KEY=${API_KEY}  # Loaded from env
```

### ✅ Good: Post-start hook
```yaml
hooks:
  post-start:
    - exec: composer install
    - exec: "echo 'DDEV environment ready!'"
```

### ❌ Bad: No automation
```yaml
# Missing hooks - manual setup required every time
# Developers must remember to run composer install
```

## 8. When stuck

### Invoke typo3-ddev skill FIRST
```
Skill("netresearch-skills-bundle:typo3-ddev")
```
The skill provides comprehensive DDEV setup and troubleshooting guidance.

### Resources
1. **typo3-ddev skill** - Complete DDEV environment guidance
2. **DDEV Documentation**: https://ddev.readthedocs.io/
3. **DDEV TYPO3 Quickstart**: https://ddev.readthedocs.io/en/stable/users/quickstart/#typo3
4. **DDEV Commands**: https://ddev.readthedocs.io/en/stable/users/usage/commands/

### Common issues

**Environment won't start:**
```bash
# Check Docker is running
docker ps

# Check DDEV status
ddev poweroff
ddev start

# View detailed errors
ddev start -v
```

**Composer install fails:**
```bash
# SSH into container and debug
ddev ssh
composer install -vvv

# Check PHP version matches composer.json
ddev exec php -v
```

**Database connection issues:**
```bash
# Verify database is running
ddev mysql

# Check database credentials
ddev describe

# Restart database
ddev restart
```

**Port conflicts:**
```bash
# Check what's using ports
ddev poweroff

# Change ports in config.yaml
router_http_port: "8080"
router_https_port: "8443"

ddev start
```

**Xdebug needed:**
```bash
# Enable Xdebug
ddev xdebug on

# Disable Xdebug (faster)
ddev xdebug off

# Or set in config.yaml
xdebug_enabled: true
```

## 9. House Rules

### PHP Version Strategy
- **Default**: 8.2 (current stable for TYPO3 13)
- **Testing**: Use `ddev config --php-version=X.Y` to test multiple versions
- **Match composer.json**: PHP version must be within composer requirements
- **Team consistency**: All developers use same PHP version by default

### Database Standards
- **Use MariaDB** for TYPO3 (better compatibility than MySQL)
- **Version 10.11** (TYPO3 13 compatible)
- **Don't commit dumps** with sensitive data
- **Use snapshots** for testing (`ddev snapshot`)

### Hook Automation
Required hooks:
- ✅ `post-start: composer install` - Auto-install dependencies
- ✅ Echo statements for clarity
- ❌ Don't run heavy migrations automatically (ask first)

### Environment Isolation
- Each extension has its own DDEV environment
- No shared databases between projects
- Clean start: `ddev delete -O && ddev start`

### Local Overrides
Use `.ddev/config.local.yaml` (gitignored) for:
- Personal Xdebug settings
- Custom environment variables
- Port overrides
- Performance tuning

```yaml
# .ddev/config.local.yaml (not committed)
xdebug_enabled: true
router_http_port: "8080"
web_environment:
  - MY_LOCAL_VAR=value
```

### Multi-Version Testing
For TYPO3 extensions supporting v12 and v13:
1. Create snapshots for each version
2. Use `ddev config --php-version=X.Y` to switch
3. Document test matrix in README.md

### Performance Optimization
```yaml
# Disable Xdebug by default (faster)
xdebug_enabled: false

# Enable only when debugging
# ddev xdebug on

# Use Mutagen for better file sync (Mac/Windows)
# See: https://ddev.readthedocs.io/en/stable/users/install/performance/
```

### Team Onboarding
New team members should:
1. Install DDEV (https://ddev.readthedocs.io/en/stable/users/install/)
2. Clone repository
3. Run `ddev start` (auto-installs dependencies)
4. Run `ddev composer test` to verify setup
5. Access https://nr-xliff-streaming.ddev.site (if web interface exists)

**First-time setup should take < 5 minutes.**
