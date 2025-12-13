# Secure SQL Guidelines for DevFlow Pro
## Developer Quick Reference

**Last Updated:** December 13, 2025
**Mandatory for:** All developers
**Review Frequency:** Quarterly

---

## Golden Rule

> **NEVER interpolate user input directly into raw SQL queries**

---

## Safe vs Unsafe Patterns

### ❌ UNSAFE - DO NOT USE

```php
// Direct interpolation - SQL INJECTION RISK!
DB::statement("CREATE DATABASE {$userInput}");
DB::select("SELECT * FROM users WHERE name = '{$name}'");
$query->whereRaw("status = '{$userStatus}'");
$query->orderByRaw("FIELD(id, {$userIds})");

// String concatenation - DANGEROUS!
$sql = "DELETE FROM " . $table . " WHERE id = " . $id;
DB::statement($sql);
```

### ✅ SAFE - USE THESE PATTERNS

```php
// Parameterized queries (BEST)
DB::select('SELECT * FROM users WHERE name = ?', [$name]);
$query->whereRaw('status = ?', [$userStatus]);
$query->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active', ['active']);

// Query builder (RECOMMENDED)
DB::table('users')->where('name', $name)->get();
User::where('status', $status)->first();

// Static SQL only (SAFE when no user input)
$query->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'inactive' THEN 2 END");
DB::raw('COUNT(*) as total');
```

---

## Common Scenarios

### 1. Dynamic WHERE Clauses

```php
// ❌ UNSAFE
$query->whereRaw("created_at > '{$date}'");

// ✅ SAFE - Use query builder
$query->where('created_at', '>', $date);

// ✅ SAFE - Parameterized
$query->whereRaw('created_at > ?', [$date]);
```

### 2. Aggregation with Conditions

```php
// ❌ UNSAFE
$query->selectRaw("SUM(CASE WHEN status = '{$status}' THEN amount ELSE 0 END)");

// ✅ SAFE - Parameterized
$query->selectRaw('SUM(CASE WHEN status = ? THEN amount ELSE 0 END)', [$status]);

// ✅ SAFE - Query builder
$query->when($status, function($q) use ($status) {
    return $q->where('status', $status);
})->sum('amount');
```

### 3. Dynamic Sorting

```php
// ❌ UNSAFE
$query->orderByRaw("{$column} {$direction}");

// ✅ SAFE - Whitelist validation
$allowedColumns = ['name', 'email', 'created_at'];
$allowedDirections = ['asc', 'desc'];

if (in_array($column, $allowedColumns) && in_array($direction, $allowedDirections)) {
    $query->orderBy($column, $direction);
}

// ✅ SAFE - Use enum/constants
$query->orderBy(
    match($sortField) {
        'name' => 'users.name',
        'date' => 'created_at',
        default => 'id'
    },
    $direction === 'desc' ? 'desc' : 'asc'
);
```

### 4. Database/Table Names

```php
// ❌ UNSAFE - CRITICAL VULNERABILITY
DB::statement("CREATE DATABASE {$dbName}");
DB::statement("DROP TABLE {$tableName}");

// ✅ SAFE - Validate and sanitize
function sanitizeDatabaseName(string $name): string {
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);

    if (empty($sanitized)) {
        throw new InvalidArgumentException('Invalid database name');
    }

    if (is_numeric($sanitized[0])) {
        throw new InvalidArgumentException('Database name cannot start with number');
    }

    if (strlen($sanitized) > 64) {
        throw new InvalidArgumentException('Database name too long');
    }

    return $sanitized;
}

$safeName = sanitizeDatabaseName($userInput);
DB::statement("CREATE DATABASE `{$safeName}`");
```

### 5. Full-Text Search

```php
// ❌ UNSAFE
$query->whereRaw("MATCH(content) AGAINST('{$searchTerm}')");

// ✅ SAFE - Parameterized
$query->whereRaw('MATCH(content) AGAINST(?)', [$searchTerm]);

// ✅ SAFE - Laravel Scout (recommended for full-text)
Product::search($searchTerm)->get();
```

### 6. JSON Queries

```php
// ❌ UNSAFE
$query->whereRaw("JSON_EXTRACT(metadata, '$.{$key}') = '{$value}'");

// ✅ SAFE - Parameterized
$query->whereRaw("JSON_EXTRACT(metadata, ?) = ?", ['$.' . $key, $value]);

// ✅ SAFE - Laravel's JSON methods
$query->where("metadata->{$key}", $value);
```

---

## Validation Checklist

Before using raw SQL, ask yourself:

1. ✅ **Is user input involved?**
   - Yes → Use parameterized queries or query builder
   - No → Static SQL is safe

2. ✅ **Can I use query builder instead?**
   - Use `where()`, `select()`, `orderBy()` when possible
   - Only use raw SQL for complex queries

3. ✅ **Are parameters bound separately?**
   - Use `?` placeholders
   - Pass values in array: `->whereRaw('column = ?', [$value])`

4. ✅ **Have I validated/sanitized all inputs?**
   - Whitelist allowed values
   - Sanitize special characters
   - Validate data types

5. ✅ **Does it pass PHPStan Level 8?**
   - Run: `vendor/bin/phpstan analyze --level=8`

---

## Input Validation Patterns

### Whitelist Validation (Preferred)

```php
// Validate against allowed values
$allowedStatuses = ['active', 'inactive', 'pending'];
if (!in_array($status, $allowedStatuses)) {
    throw new InvalidArgumentException('Invalid status');
}
$query->where('status', $status);

// Validate column names
$allowedColumns = ['id', 'name', 'email', 'created_at'];
if (!in_array($column, $allowedColumns)) {
    throw new InvalidArgumentException('Invalid column');
}
$query->orderBy($column);
```

### Type Validation

```php
// Ensure correct types
$id = (int) $input; // Force integer
$email = filter_var($input, FILTER_VALIDATE_EMAIL);
$date = Carbon::parse($input); // Throws on invalid date

// Use Laravel validation
$validated = $request->validate([
    'status' => 'required|in:active,inactive',
    'sort' => 'nullable|in:name,date,status',
]);
```

### Sanitization

```php
// Remove dangerous characters
$sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $input);

// Use Laravel's Str helpers
$slug = Str::slug($input);
$safe = Str::ascii($input);

// Database-specific escaping (last resort)
$escaped = addslashes($input); // NOT recommended, use parameterization
```

---

## Code Review Red Flags

Watch for these patterns in code reviews:

```php
// 🚩 String concatenation in SQL
"SELECT * FROM users WHERE " . $condition

// 🚩 Variable interpolation in raw SQL
DB::raw("COUNT({$column})")
->whereRaw("status = '{$status}'")

// 🚩 User input in database/table names
DB::statement("DROP TABLE {$tableName}")
"SHOW TABLES LIKE '{$pattern}'"

// 🚩 Dynamic query building without validation
$sql = "SELECT * FROM {$_GET['table']}"

// 🚩 Missing parameterization
->whereRaw("date > '{$date}'")
```

---

## Testing for SQL Injection

### Manual Testing

Try these test inputs:

```
' OR '1'='1
'; DROP TABLE users; --
`; DELETE FROM projects; --
test' UNION SELECT password FROM users--
1' AND 1=1--
admin'--
' OR 'x'='x
```

**Expected Result:** All should be rejected or safely escaped

### Automated Testing

```php
// PHPUnit test example
public function test_prevents_sql_injection_in_tenant_creation()
{
    $maliciousInputs = [
        "test'; DROP TABLE tenants; --",
        "test`; DELETE FROM users; --",
        "test' OR '1'='1",
        "test'; /**/UNION/**/SELECT/**/*/**/FROM/**/users--",
    ];

    foreach ($maliciousInputs as $input) {
        $this->expectException(InvalidArgumentException::class);

        $tenant = new Tenant();
        $tenant->database = $input; // Should throw exception
    }
}
```

### PHPStan Integration

```bash
# Run on every commit
vendor/bin/phpstan analyze --level=8

# Add to CI/CD pipeline
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyze --error-format=github
```

---

## Security Best Practices

### 1. Defense in Depth

Implement multiple validation layers:

```
User Input
    ↓
[1] Form Validation (Livewire/Request)
    ↓
[2] Model Mutators (Eloquent)
    ↓
[3] Service-Level Validation
    ↓
[4] Parameterized Query
    ↓
Database
```

### 2. Least Privilege

```php
// Separate database users for different operations
config(['database.connections.readonly' => [
    'username' => env('DB_READONLY_USER'),
    'password' => env('DB_READONLY_PASS'),
    'read_only' => true,
]]);

// Use readonly connection for queries
DB::connection('readonly')->table('users')->get();
```

### 3. Audit Logging

```php
// Log sensitive database operations
Log::info('Database operation', [
    'operation' => 'CREATE_DATABASE',
    'database' => $sanitizedName,
    'user' => auth()->id(),
    'ip' => request()->ip(),
]);
```

### 4. Rate Limiting

```php
// Prevent brute force SQL injection attempts
RateLimiter::for('database-operations', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id);
});
```

---

## Common Mistakes

### Mistake 1: Trusting Internal Data

```php
// ❌ WRONG - Even internal data should be validated
$config = json_decode($project->config);
DB::statement("USE {$config->database}"); // Still vulnerable!

// ✅ CORRECT
$database = $this->sanitizeDatabaseName($config->database);
DB::statement("USE `{$database}`");
```

### Mistake 2: Sanitizing Too Late

```php
// ❌ WRONG - Sanitize before storage
$tenant->database = $userInput;
$tenant->save();
// ... later ...
$sanitized = sanitize($tenant->database); // Too late!

// ✅ CORRECT - Sanitize at model level
public function setDatabaseAttribute(string $value): void {
    $this->attributes['database'] = $this->sanitize($value);
}
```

### Mistake 3: Incomplete Validation

```php
// ❌ WRONG - Only validates one attack vector
if (strpos($input, "'") === false) {
    DB::statement("CREATE DATABASE {$input}"); // Still vulnerable!
}

// ✅ CORRECT - Whitelist approach
$safe = preg_replace('/[^a-zA-Z0-9_]/', '', $input);
if ($safe === $input && !empty($safe)) {
    DB::statement("CREATE DATABASE `{$safe}`");
}
```

---

## Resources

### Laravel Documentation

- [Database: Query Builder](https://laravel.com/docs/queries)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [Validation](https://laravel.com/docs/validation)

### Security References

- [OWASP SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)
- [CWE-89: SQL Injection](https://cwe.mitre.org/data/definitions/89.html)
- [PHP Security Guide](https://www.php.net/manual/en/security.database.sql-injection.php)

### Tools

- [PHPStan](https://phpstan.org/)
- [Psalm](https://psalm.dev/)
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)

---

## Quick Reference Card

| Operation | Unsafe | Safe |
|-----------|--------|------|
| WHERE clause | `whereRaw("col = '{$val}'")` | `where('col', $val)` |
| SELECT | `DB::raw("COUNT({$col})")` | `DB::raw('COUNT(column)')` |
| Parameter | `whereRaw("x = '{$y}'")` | `whereRaw('x = ?', [$y])` |
| ORDER BY | `orderByRaw("{$col}")` | `orderBy($validatedCol)` |
| Database | `"CREATE DB {$name}"` | `"CREATE DB `{$sanitized}`"` |

---

**Remember:** When in doubt, use the query builder. If you must use raw SQL, always parameterize!

---

**Document Version:** 1.0
**Maintained By:** DevFlow Pro Security Team
**Review Date:** March 13, 2026
