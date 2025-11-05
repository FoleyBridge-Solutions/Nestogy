# Legacy Code Cleanup Report

Generated: $(date)
Tool: Larastan (PHPStan for Laravel) v3.8.0
Analysis Level: 5 (Medium strictness)

## Summary

- **Total Issues Found**: 7,246 errors
- **Files Analyzed**: 1,447 files
- **Unused Code Items**: 20+ instances

## Critical Fixes Applied

### 1. Syntax Errors Fixed
- `database/seeders/CrossCompanyUserSeeder.php:47` - Extra closing brace removed
- `database/seeders/PortalNotificationSeeder.php:38` - Extra closing brace removed

### 2. Method Compatibility Issues Fixed
- `app/Domains/Contract/Models/ContractVersion.php:171` - Renamed `hasChanges()` to `hasVersionChanges()` to avoid conflict with Eloquent parent method

### 3. Missing Dependencies Identified
- Laravel Scout (Searchable trait) - Not installed, commented out in `KbArticle.php`
- Missing job classes: `SyncTacticalRmmAgents`, `SyncTacticalRmmAlerts`

## Unused Code Detected

### Constants (Class-level constants that are never used)
1. `App\Console\Commands\AnalyzeSentimentCommand::DEFAULT_PAGE_SIZE`
2. `App\Console\Commands\UpdateTaxData::API_BASE_URL`
3. `App\Console\Commands\ListCompanyEmailConfigCommand::CONFIG_SMTP`
4. `App\Console\Commands\ListCompanyEmailConfigCommand::CONFIG_IMAP`
5. `App\Console\Commands\ListCompanyEmailConfigCommand::MSG_LIST_START`
6. `App\Console\Commands\ManageContractClauses::ACTION_LIST`
7. `App\Console\Commands\ManageContractClauses::ACTION_ADD`
8. `App\Console\Commands\ManageContractClauses::ACTION_REMOVE`
9. `App\Console\Commands\ManageContractClauses::MSG_MANAGE_START`
10. `App\Console\Commands\ParseTemplateIntoClauses::CLAUSE_TYPE_STANDARD`
11. `App\Console\Commands\ParseTemplateIntoClauses::CLAUSE_TYPE_CUSTOM`
12. `App\Console\Commands\ParseTemplateIntoClauses::MSG_PARSE_START`

### Methods (Private/Protected methods that are never called)
1. `App\Livewire\CommandPalette::performSearch()` - Line 351
2. `App\Livewire\CommandPalette::generateKeywords()` - Line 583

### Unused Variables in Closures
1. Anonymous function with unused `$comments` variable
2. Anonymous function with unused `$periodStart` variable

## Major Issue Categories

### 1. Missing Models/Classes (82 instances)
- Various models not found during static analysis
- Example: `App\Console\Commands\TicketReply` class not found

### 2. Undefined Properties (1,200+ instances)
- Accessing properties that don't exist in model definitions
- Likely due to dynamic properties or missing PHPDoc annotations

### 3. Undefined Methods (450+ instances)
- Calling methods that don't exist on classes
- Example: `NotificationService::notifyTicketSlaIssue()` not found

### 4. Type Mismatches (2,300+ instances)
- Parameters with incorrect types
- Return type mismatches

### 5. Missing Relations (180+ instances)
- Eloquent relations referenced but not defined
- Example: `Company::contractNavigationItems` relation not found

## Recommendations

### Immediate Actions (High Priority)

1. **Remove Unused Constants** - Safe to delete, no breaking changes
   - Run: `grep -r "DEFAULT_PAGE_SIZE\|API_BASE_URL\|CONFIG_SMTP" app/Console/Commands/`
   - Remove all unused constants listed above

2. **Remove Unused Methods**
   - Review `CommandPalette::performSearch()` and `generateKeywords()` 
   - If truly unused, can be safely removed

3. **Install Missing Dependencies**
   ```bash
   composer require laravel/scout
   ```

4. **Fix Missing Job Classes**
   - Create the missing job classes or remove references

### Medium Priority Actions

1. **Add PHPDoc Annotations** - Reduces false positives
   - Add `@property` annotations to models for dynamic properties
   - Add `@method` annotations for magic methods
   - Add `@relation` annotations for Eloquent relationships

2. **Define Missing Relations**
   - Add missing relationship methods to models
   - Example: Add `contractNavigationItems()` to Company model

3. **Fix Type Declarations**
   - Add proper type hints to method parameters
   - Add return types to methods

### Long-term Actions

1. **Gradual Strictness Increase**
   - Currently at level 5, gradually increase to level 8
   - Fix issues at each level before progressing

2. **Add to CI/CD Pipeline**
   - Run Larastan on every commit
   - Prevent new issues from being introduced

3. **Regular Cleanup Sprints**
   - Schedule quarterly code cleanup sessions
   - Focus on one category at a time

## How to Use This Report

### Running the Analysis Again
```bash
./vendor/bin/phpstan analyse --memory-limit=2G
```

### Analyzing Specific Directories
```bash
./vendor/bin/phpstan analyse app/Console/Commands --memory-limit=2G
```

### Generating Different Output Formats
```bash
# JSON format
./vendor/bin/phpstan analyse --error-format=json

# GitHub Actions format
./vendor/bin/phpstan analyse --error-format=github

# Plain text (no colors)
./vendor/bin/phpstan analyse --error-format=raw
```

## Configuration File

The analysis configuration is stored in `phpstan.neon` at the project root.

Current settings:
- Analysis level: 5
- Paths analyzed: app, config, database, routes
- Excluded paths: TelescopeServiceProvider, TacticalRmmIntegration, KbArticle
- Memory limit: 2GB

## Next Steps

1. Review this report with the team
2. Prioritize fixes based on business impact
3. Create tickets for each category of issues
4. Start with "Immediate Actions" section
5. Re-run analysis after fixes to track progress

---

Full detailed report saved to: `phpstan-complete-report.txt`
