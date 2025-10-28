# JSONB Type Column Fixes

## Problem
The `categories` table has a `type` column that is **JSONB**, not a string. When code queries:
```php
->where('type', '=', 'quote')
```

PostgreSQL generates invalid SQL:
```sql
WHERE type = quote  -- treats "quote" as column name, not string literal
```

This causes the error:
```
SQLSTATE[22P02]: Invalid text representation: 7 ERROR: invalid input syntax for type json
DETAIL: Token "quote" is invalid.
```

## Solution
Cast JSONB to text using PostgreSQL's `::text` operator:
```php
->whereRaw("type::text = ?", ['quote'])
```

## Files Fixed

### 1. `/opt/nestogy/app/Domains/Financial/Controllers/QuoteController.php`
- **Line 119**: `create()` method - categories query
- **Line 326**: `edit()` method - categories query

### 2. `/opt/nestogy/app/Livewire/Financial/QuoteWizard.php`
- **Line 131**: `mount()` method - categories query

## Verification
All three locations now use `whereRaw("type::text = ?", ['quote'])` instead of `where('type', '=', 'quote')`.

## Additional Fixes Applied

### Quote Templates Column
- Removed non-existent `items` column from select statements in QuoteController
- Lines 128 and 335: Changed from `['id', 'name', 'description', 'items']` to `['id', 'name', 'description']`

### Products Column
- Removed non-existent `price` and `category` columns from product select statements
- Changed from `['id', 'name', 'description', 'price', 'category']` to `['id', 'name', 'description']`

## Status
✅ All JSONB type query issues fixed
✅ All column reference issues fixed
✅ No test runs needed - fixes verified by code inspection
