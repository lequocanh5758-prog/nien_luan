# AGENTS.md - Agent Coding Guidelines

This project is a PHP e-commerce application (custom framework, not Laravel). All agents must follow these guidelines.

---

## 1. Build & Test Commands

### Setup
```bash
composer install --prefer-dist --no-progress
cp .env.example .env
```

### Linting & Static Analysis
```bash
# PHPStan (static analysis)
composer analyse

# PHP CodeSniffer (code style)
composer cs-check
```

### Testing
```bash
# Run all tests with coverage
composer analyse && vendor/bin/phpunit

# Run single test file
./vendor/bin/phpunit --filter TestClassName tests/path/to/TestFile.php

# Run single test method
./vendor/bin/phpunit --filter testMethodName tests/path/to/TestFile.php
```

### Database (requires MySQL)
```bash
# Environment variables needed:
DB_HOST=127.0.0.1
DB_DATABASE=sales_management_test
DB_USERNAME=root
DB_PASSWORD=root
```

---

## 2. Code Style Guidelines

### PHP Conventions
- Use PSR-4 autoloading (`App\` namespace maps to `lequocanh/app/`)
- Follow PSR-12 coding standard
- Use strict typing (`declare(strict_types=1);`)
- Always use parentheses for control structures

### Naming Conventions
- Classes: `PascalCase` (e.g., `OrderController`)
- Methods: `camelCase` (e.g., `get Orders()`)
- Variables: `camelCase` (e.g., `$orderTotal`)
- Constants: `UPPER_CASE` (e.g., `MAX_RETRY_COUNT`)
- Files: `snake_case.php` (e.g., `order_controller.php`)

### Error Handling
- Never suppress errors with `@`
- Use custom exception classes for domain errors
- Log all errors with context: `Log::error('message', ['context' => $data])`
- Return appropriate HTTP status codes (4xx for client errors, 5xx for server errors)

### Database Queries
- Use parameterized queries to prevent SQL injection
- Always use transactions for multi-step operations
- Implement soft deletes where appropriate

### Security
- Sanitize all user input
- Use CSRF tokens for all forms (`csrf_token()`)
- Validate file uploads rigorously
- Never expose sensitive data in responses

### JavaScript/Frontend
- Use ES6+ syntax
- Implement proper error handling with try/catch
- Use async/await for API calls

---

## 3. Cursor Rules

This project includes Cursor rules. See `.gitnexus-setup/.cursorrules`.

---

## 4. GitNexus Code Intelligence

This codebase is indexed by GitNexus. Use MCP tools for code navigation.

### Required Tools
- **Before any code change**: Run `gitnexus_impact()` to check blast radius
- **For debugging**: Use `gitnexus_query({query: "error description"})`
- **Before refactoring**: Use `gitnexus_context({name: "symbolName"})`

### Running Analysis
```bash
# Update index after changes
npx gitnexus analyze
```

---

## 5. Project Structure

```
lequocanh/           - Main application code
  app/                - App namespace (PSR-4)
    Controllers/      - Request handlers
    Models/           - Data models (Product, ProductImage, ProductReview, Banner, BaseModel)
    Services/         - Business logic
    Views/            - Templates (Admin, Components, Frontend, Layouts)
    autoload.php      - PSR-4 autoloader
  includes/            - Shared utilities
  payment/             - Payment integrations
  database/            - DB scripts
  public_files/        - JS/CSS assets
  cache/               - Page caching
  administrator/       - Admin panel
    elements_LQA/mod/  - Legacy wrappers (hanghoaCls.php)
```

### MVC Architecture (After Migration)

```
App\Models\Product          ← CRUD, search, filter, status, references
App\Models\ProductImage     ← Image CRUD, relations, diagnostics
App\Models\ProductReview    ← Rating/review queries
App\Models\Banner           ← Banner management
App\Models\BaseModel        ← ORM foundation
hanghoaCls.php              ← Delegation wrapper (backward compat)
```

### Migration Status (2026-05-12)
- **Coverage:** ~85% MVC
- **Files migrated:** 28 files
- **Remaining:** ~5 files using wrapper
- **Deprecated:** hanghoaStatusExtension.php (removed)
- **Report:** See `MIGRATION_REPORT.md`
- **Tests:** PHPStan 0 errors, PHPUnit 14/14 pass

---

## 6. Key Dependencies

- `tecnickcom/tcpdf` - PDF generation
- `phpoffice/phpspreadsheet` - Excel handling
- `phpmailer/phpmailer` - Email sending

---

## 7. Never Do

- NEVER commit without running static analysis first (`composer analyse`)
- NEVER use `SELECT *` in queries - explicitly list columns
- NEVER skip CSRF validation on forms
- NEVER commit secrets or credentials to git