# AGENTS.md - Coding Guidelines for Inversiones Rojas

## Overview

This is a PHP-based POS/inventory system for a motorcycle and parts business in Venezuela. The codebase includes:
- PHP 8.x backend with PostgreSQL database
- Vanilla JavaScript frontend (no framework)
- Dual currency support (USD/VES)
- Chart.js for data visualization
- Font Awesome for icons

**IMPORTANT**: This codebase has accumulated technical debt. Be extra careful when modifying:
- JavaScript initialization code in PHP files (multiple DOMContentLoaded handlers, duplicate code)
- API endpoints that return HTML instead of JSON (check with browser devtools)
- Hardcoded paths that assume local development structure

---

## Build/Lint/Test Commands

### PHP Syntax Check
```bash
# Check single file
php -l path/to/file.php

# Check all PHP files recursively
find . -name "*.php" -exec php -l {} \;
```

### Running Tests
```bash
# XAMPP (Windows) - run from htdocs/inversiones-rojas
php tests/test_moneda.php
php tests/check_db.php
php tests/check_tables.php
php tests/check_data.php
```

### Development Server
```bash
# PHP built-in (API testing only)
cd C:\xampp\htdocs\inversiones-rojas
php -S localhost:8000 -t .
```

---

## Code Style Guidelines

### PHP File Structure

```php
<?php
// api/add_product.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Include dependencies (use __DIR__ for relative paths)
require_once __DIR__ . '/../app/models/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify authentication
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

// Main logic
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    // ... validation, inserts, etc. ...
    
    echo json_encode(['ok' => true, 'id' => $newId]);
} catch (Exception $e) {
    error_log('ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
exit;
?>
```

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Functions | snake_case | `get_product_by_id()` |
| Classes | PascalCase | `Database`, `ProductController` |
| Constants | UPPER_SNAKE_CASE | `TASA_CAMBIO` |
| Variables | snake_case | `$product_data` |
| Tables/Columns | snake_case | `productos`, `codigo_interno` |
| JavaScript | camelCase | `fetchInventory()` |

### File Organization
```
api/                  # POST endpoints (add_*.php, update_*.php)
app/models/            # Database classes
app/views/layouts/       # Page views (inventario.php, ventas.php)
app/helpers/           # Helper functions
public/css/layouts/     # Page-specific styles
public/js/             # JavaScript
tests/                # Test scripts
config/               # Configuration
```

---

## JavaScript Guidelines

### CRITICAL: Dynamic Base URLs

**ALWAYS use dynamic APP_BASE for API calls**:

```javascript
// ❌ WRONG - breaks on different servers
const response = await fetch('/inversiones-rojas/api/add_product.php', ...);

// ✅ CORRECT - works everywhere
const apiUrl = (window.APP_BASE || '') + '/api/add_product.php';
const response = await fetch(apiUrl, { method: 'POST', body: formData });
```

The window.APP_BASE is set in PHP:
```php
<script>
    var APP_BASE = '<?php echo $base_url; ?>';
    var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;
</script>
```

### JavaScript Error Handling
```javascript
try {
    const response = await fetch(apiUrl, options);
    
    // Check response status FIRST
    if (!response.ok) {
        const text = await response.text();
        console.error('Error response:', text);
        throw new Error(`HTTP ${response.status}`);
    }
    
    // Check content-type
    const contentType = response.headers.get('content-type');
    if (!contentType?.includes('application/json')) {
        const text = await response.text();
        throw new Error(`Expected JSON, got: ${text.substring(0, 200)}`);
    }
    
    const data = await response.json();
    if (!data.ok) {
        throw new Error(data.error || 'Unknown error');
    }
    // Success
} catch (error) {
    console.error('Error:', error);
    Toast.error(error.message);
}
```

### DOMContentLoaded - Best Practice

If you need multiple event listeners, consolidate them:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('myModal');
    const btn = document.getElementById('myBtn');
    const input = document.getElementById('myInput');
    
    if (btn) {
        btn.addEventListener('click', handleClick);
    }
    if (input) {
        input.addEventListener('input', handleInput);
    }
});
```

**Common bug**: Variables defined inside DOMContentLoaded being used outside. Either:
1. Define globals at script top: `let myModal = null;`
2. Or get element inside the function that uses it

---

## API Endpoint Guidelines

### Image Upload Paths

**CORRECT** - Use relative paths, not DOCUMENT_ROOT:

```php
// ❌ WRONG - fails on different servers
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/inversiones-rojas/public/img/products/';

// ✅ CORRECT - works everywhere
$upload_dir = dirname(__DIR__) . '/public/img/products/';
```

### Image URL Storage

```php
$url = '/inversiones-rojas/public/img/products/' . $filename;
// Or dynamic:
$url = '/public/img/products/' . $filename;
```

### Checking API Responses

When debugging, check browser Network tab:
1. Status code (200 = good, 401 = auth fail, 500 = server error)
2. Response is JSON, not HTML error page
3. Check error.log at: `C:\xampp\htdocs\inversiones-rojas\api\php_errors.log`

---

## CSS Guidelines

### Currency Display
- USD: Green (#1F9166), display ABOVE
- BS: Gray (#6c757d), display BELOW

```css
.moneda-usd {
    color: #1F9166;
    font-weight: 600;
}
.moneda-bs {
    color: #6c757d;
    font-size: 0.9em;
}
```

### Primary Color
- Use Venezuelan identity green: `#1F9166`

---

## Security

1. **Auth**: Always check `$_SESSION['user_id']`
2. **SQL**: Use prepared statements ONLY
3. **CSRF**: Use form tokens for POST
4. **XSS**: Escape with `htmlspecialchars()`
5. **Files**: Validate MIME types for uploads

---

## Database

- PostgreSQL with PDO
- Use transactions for multi-step operations
- Error logging: `error_log('Message')`
- Rollback on failure

---

## Common Issues to Avoid

### 1. JSON vs HTML responses
API must return JSON, not HTML. Check:
- Browser Network tab shows `<!DOCTYPE html>` instead of `{"ok":true}`
- Fix: Check PHP for premature output or errors

### 2. JavaScript ReferenceError
Often caused by:
- Function defined after DOMContentLoaded but called before
- Function not exposed to window: Add `window.fnName = fnName;`

### 3. Image upload fails on production
- Use `dirname(__DIR__)` not `$_SERVER['DOCUMENT_ROOT']`
- Check directory permissions

### 4. Path issues in production
- All API URLs should use `(window.APP_BASE || '') + '/api/...'`
- Never hardcode `/inversiones-rojas` in JavaScript

---

## Git Commit Style

```bash
fix: corregir error al guardar producto
feat: agregar exportación de reportes a PDF
refactor: unificar estilos de tablas
docs: actualizar instrucciones
```