<?php

/**
 * Automated Code Quality Checks for Nestogy Deduplication Framework
 * 
 * Validates that code follows the new patterns and identifies potential issues
 * before they reach production.
 * 
 * Usage: php code-quality-checks.php [--fix] [--domain=DomainName] [--strict]
 */

class NestogyCodeQualityChecker
{
    private bool $autoFix = false;
    private bool $strictMode = false;
    private ?string $targetDomain = null;
    private array $issues = [];
    private array $fixed = [];
    private array $rulesets = [];

    public function __construct(array $options = [])
    {
        $this->autoFix = $options['fix'] ?? false;
        $this->strictMode = $options['strict'] ?? false;
        $this->targetDomain = $options['domain'] ?? null;
        $this->initializeRulesets();
    }

    private function initializeRulesets(): void
    {
        $this->rulesets = [
            'security' => [
                'company_scoping' => 'Ensure all queries use company scoping',
                'authorization' => 'Verify authorization checks exist',
                'input_validation' => 'Check for proper input validation',
                'sql_injection' => 'Detect potential SQL injection vulnerabilities'
            ],
            'performance' => [
                'n_plus_one' => 'Identify N+1 query problems',
                'eager_loading' => 'Verify eager loading is used',
                'query_optimization' => 'Check for unoptimized queries',
                'memory_usage' => 'Detect potential memory leaks'
            ],
            'architecture' => [
                'base_classes' => 'Ensure controllers extend BaseController',
                'service_layer' => 'Verify business logic is in services',
                'trait_usage' => 'Check for proper trait usage',
                'naming_conventions' => 'Validate naming conventions'
            ],
            'maintainability' => [
                'code_duplication' => 'Detect remaining code duplication',
                'complexity' => 'Check method complexity',
                'documentation' => 'Verify adequate documentation',
                'test_coverage' => 'Check for test coverage'
            ]
        ];
    }

    public function run(): void
    {
        echo "🔍 Starting Nestogy Code Quality Analysis...\n";
        echo "Mode: " . ($this->autoFix ? "AUTO-FIX" : "ANALYSIS ONLY") . "\n";
        echo "Strictness: " . ($this->strictMode ? "STRICT" : "STANDARD") . "\n";
        echo "Target: " . ($this->targetDomain ?? "ALL DOMAINS") . "\n\n";

        $this->checkControllers();
        $this->checkServices();
        $this->checkModels();
        $this->checkFormRequests();
        $this->checkSecurityPatterns();
        $this->checkPerformancePatterns();
        $this->checkArchitecturalPatterns();

        $this->generateReport();
    }

    private function checkControllers(): void
    {
        echo "🎛️  Analyzing Controllers...\n";

        $pattern = $this->targetDomain 
            ? "app/Domains/{$this->targetDomain}/Controllers/*Controller.php"
            : "app/Domains/*/Controllers/*Controller.php";

        $controllers = glob($pattern);

        foreach ($controllers as $controllerPath) {
            if (strpos($controllerPath, 'Refactored.php') !== false) {
                continue; // Skip refactored examples
            }

            $this->analyzeController($controllerPath);
        }
    }

    private function analyzeController(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $className = basename($filePath, '.php');

        // Rule: Controllers should extend BaseController
        if (!str_contains($content, 'extends BaseController')) {
            $this->addIssue('architecture', 'base_classes', $filePath, 
                "Controller {$className} should extend BaseController instead of Controller");
        }

        // Rule: No business logic in controllers
        if ($this->hasBuisnessLogic($content)) {
            $this->addIssue('architecture', 'service_layer', $filePath,
                "Controller {$className} contains business logic that should be in a service");
        }

        // Rule: Proper authorization checks
        if ($this->hasControllerMethodsWithoutAuthorization($content)) {
            $this->addIssue('security', 'authorization', $filePath,
                "Controller {$className} has methods without authorization checks");
        }

        // Rule: No manual company scoping
        if ($this->hasManualCompanyScoping($content)) {
            $this->addIssue('security', 'company_scoping', $filePath,
                "Controller {$className} uses manual company scoping instead of trait-based");
            
            if ($this->autoFix) {
                $this->fixManualCompanyScoping($filePath, $content);
            }
        }

        // Rule: Efficient query patterns
        if ($this->hasNPlusOnePattern($content)) {
            $this->addIssue('performance', 'n_plus_one', $filePath,
                "Controller {$className} may have N+1 query problems");
        }

        // Rule: Use initializeController method
        if (str_contains($content, 'extends BaseController') && !str_contains($content, 'initializeController')) {
            $this->addIssue('architecture', 'base_classes', $filePath,
                "Controller {$className} extends BaseController but missing initializeController method");
        }
    }

    private function checkServices(): void
    {
        echo "⚙️  Analyzing Services...\n";

        $pattern = $this->targetDomain 
            ? "app/Domains/{$this->targetDomain}/Services/*Service.php"
            : "app/Domains/*/Services/*Service.php";

        $services = glob($pattern);

        foreach ($services as $servicePath) {
            if (strpos($servicePath, 'Refactored.php') !== false) {
                continue;
            }

            $this->analyzeService($servicePath);
        }
    }

    private function analyzeService(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $className = basename($filePath, '.php');

        // Rule: Services should extend BaseService
        if (!str_contains($content, 'extends BaseService')) {
            $this->addIssue('architecture', 'base_classes', $filePath,
                "Service {$className} should extend BaseService");
        }

        // Rule: Proper transaction usage
        if ($this->hasManualTransactions($content)) {
            $this->addIssue('performance', 'query_optimization', $filePath,
                "Service {$className} uses manual transactions instead of BaseService methods");
        }

        // Rule: Company scoping in services
        if ($this->hasManualCompanyScoping($content)) {
            $this->addIssue('security', 'company_scoping', $filePath,
                "Service {$className} uses manual company scoping");
        }

        // Rule: Use initializeService method
        if (str_contains($content, 'extends BaseService') && !str_contains($content, 'initializeService')) {
            $this->addIssue('architecture', 'base_classes', $filePath,
                "Service {$className} extends BaseService but missing initializeService method");
        }

        // Rule: High cyclomatic complexity
        if ($this->hasHighComplexity($content)) {
            $this->addIssue('maintainability', 'complexity', $filePath,
                "Service {$className} has methods with high cyclomatic complexity");
        }
    }

    private function checkModels(): void
    {
        echo "🏗️  Analyzing Models...\n";

        $pattern = $this->targetDomain 
            ? "app/Domains/{$this->targetDomain}/Models/*.php"
            : "app/Domains/*/Models/*.php";

        $models = array_merge(glob($pattern), glob('app/Models/*.php'));

        foreach ($models as $modelPath) {
            if (strpos($modelPath, 'Refactored.php') !== false) {
                continue;
            }

            $this->analyzeModel($modelPath);
        }
    }

    private function analyzeModel(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $className = basename($filePath, '.php');

        // Skip non-tenant models
        if (!str_contains($content, 'BelongsToCompany')) {
            return;
        }

        // Rule: Models should use new traits
        $requiredTraits = ['HasCompanyScope', 'HasSearch', 'HasFilters', 'HasArchiving', 'HasActivity'];
        foreach ($requiredTraits as $trait) {
            if (!str_contains($content, $trait)) {
                $this->addIssue('architecture', 'trait_usage', $filePath,
                    "Model {$className} should use {$trait} trait");
            }
        }

        // Rule: Define searchable fields
        if (str_contains($content, 'HasSearch') && !str_contains($content, 'searchableFields')) {
            $this->addIssue('architecture', 'trait_usage', $filePath,
                "Model {$className} uses HasSearch but doesn't define \$searchableFields");
        }

        // Rule: No manual company scoping in model
        if ($this->hasManualScopeInModel($content)) {
            $this->addIssue('security', 'company_scoping', $filePath,
                "Model {$className} has manual company scoping that should be removed");
        }

        // Rule: Proper fillable/guarded configuration
        if (!$this->hasProperMassAssignmentProtection($content)) {
            $this->addIssue('security', 'input_validation', $filePath,
                "Model {$className} lacks proper mass assignment protection");
        }
    }

    private function checkFormRequests(): void
    {
        echo "📝 Analyzing Form Requests...\n";

        $pattern = $this->targetDomain 
            ? "app/Domains/{$this->targetDomain}/Requests/*Request.php"
            : "app/Domains/*/Requests/*Request.php";

        $requests = glob($pattern);

        foreach ($requests as $requestPath) {
            $this->analyzeFormRequest($requestPath);
        }
    }

    private function analyzeFormRequest(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $className = basename($filePath, '.php');

        // Rule: Form requests should extend BaseFormRequest
        if (!str_contains($content, 'extends BaseFormRequest')) {
            $this->addIssue('architecture', 'base_classes', $filePath,
                "Form request {$className} should extend BaseFormRequest");
        }

        // Rule: Use getSpecificRules instead of rules
        if (str_contains($content, 'public function rules')) {
            $this->addIssue('architecture', 'base_classes', $filePath,
                "Form request {$className} should use getSpecificRules() instead of rules()");
        }

        // Rule: Proper validation rules
        if ($this->hasWeakValidation($content)) {
            $this->addIssue('security', 'input_validation', $filePath,
                "Form request {$className} has weak validation rules");
        }

        // Rule: Company-scoped relationship validation
        if ($this->hasUnscopedRelationshipValidation($content)) {
            $this->addIssue('security', 'company_scoping', $filePath,
                "Form request {$className} has relationship validation without company scoping");
        }
    }

    private function checkSecurityPatterns(): void
    {
        echo "🔒 Analyzing Security Patterns...\n";

        // Check for common security anti-patterns
        $this->checkForSqlInjection();
        $this->checkForXssVulnerabilities();
        $this->checkForMassAssignmentIssues();
        $this->checkForAuthorizationBypass();
    }

    private function checkForSqlInjection(): void
    {
        $files = array_merge(
            glob('app/Domains/*/Controllers/*.php'),
            glob('app/Domains/*/Services/*.php'),
            glob('app/Models/*.php')
        );

        foreach ($files as $filePath) {
            $content = file_get_contents($filePath);
            
            // Check for raw SQL with potential injection
            if (preg_match('/DB::(select|insert|update|delete)\s*\(\s*["\'].*\$/', $content)) {
                $this->addIssue('security', 'sql_injection', $filePath,
                    "Potential SQL injection vulnerability detected");
            }

            // Check for whereRaw with user input
            if (preg_match('/whereRaw\s*\(\s*["\'].*\$/', $content)) {
                $this->addIssue('security', 'sql_injection', $filePath,
                    "whereRaw with potential user input detected");
            }
        }
    }

    private function checkForXssVulnerabilities(): void
    {
        $viewFiles = glob('resources/views/**/*.blade.php');
        
        foreach ($viewFiles as $filePath) {
            $content = file_get_contents($filePath);
            
            // Check for unescaped output
            if (preg_match('/\{\!\!\s*\$[^}]+\!\!\}/', $content)) {
                $this->addIssue('security', 'input_validation', $filePath,
                    "Unescaped output detected - potential XSS vulnerability");
            }
        }
    }

    private function checkForMassAssignmentIssues(): void
    {
        $models = glob('app/Models/*.php');
        
        foreach ($models as $filePath) {
            $content = file_get_contents($filePath);
            
            // Check for models without fillable or guarded
            if (!str_contains($content, '$fillable') && !str_contains($content, '$guarded')) {
                $this->addIssue('security', 'input_validation', $filePath,
                    "Model lacks mass assignment protection (\$fillable or \$guarded)");
            }
        }
    }

    private function checkForAuthorizationBypass(): void
    {
        $controllers = glob('app/Domains/*/Controllers/*.php');
        
        foreach ($controllers as $filePath) {
            $content = file_get_contents($filePath);
            
            // Check for methods that modify data without authorization
            $methods = ['store', 'update', 'destroy'];
            foreach ($methods as $method) {
                if (preg_match("/public function {$method}\s*\([^}]+\{(?![^}]*authorize)[^}]*\}/s", $content)) {
                    $this->addIssue('security', 'authorization', $filePath,
                        "Method {$method} lacks authorization check");
                }
            }
        }
    }

    private function checkPerformancePatterns(): void
    {
        echo "⚡ Analyzing Performance Patterns...\n";

        $this->checkForEagerLoadingOpportunities();
        $this->checkForQueryOptimizations();
        $this->checkForMemoryLeaks();
    }

    private function checkForEagerLoadingOpportunities(): void
    {
        $files = array_merge(
            glob('app/Domains/*/Controllers/*.php'),
            glob('app/Domains/*/Services/*.php')
        );

        foreach ($files as $filePath) {
            $content = file_get_contents($filePath);
            
            // Check for potential N+1 queries
            if (preg_match('/foreach\s*\([^}]+\$\w+\s*->\s*\w+\s*\)/', $content)) {
                $this->addIssue('performance', 'n_plus_one', $filePath,
                    "Potential N+1 query detected - consider eager loading");
            }

            // Check for missing with() calls
            if (preg_match('/\$\w+\s*=\s*\w+::(?!with)\w+\(\)/', $content)) {
                $this->addIssue('performance', 'eager_loading', $filePath,
                    "Query without eager loading detected");
            }
        }
    }

    private function checkForQueryOptimizations(): void
    {
        $files = glob('app/Domains/*/Services/*.php');

        foreach ($files as $filePath) {
            $content = file_get_contents($filePath);
            
            // Check for select * queries
            if (preg_match('/select\s*\(\s*\*\s*\)/', $content)) {
                $this->addIssue('performance', 'query_optimization', $filePath,
                    "SELECT * query detected - specify needed columns");
            }

            // Check for missing indexes on searches
            if (preg_match('/where\s*\(\s*["\'](?!id|company_id)\w+["\']/', $content)) {
                $this->addIssue('performance', 'query_optimization', $filePath,
                    "Query on potentially unindexed column");
            }
        }
    }

    private function checkForMemoryLeaks(): void
    {
        $files = glob('app/Domains/*/Services/*.php');

        foreach ($files as $filePath) {
            $content = file_get_contents($filePath);
            
            // Check for large collections without chunking
            if (preg_match('/->get\(\).*foreach/', $content)) {
                $this->addIssue('performance', 'memory_usage', $filePath,
                    "Large collection processing - consider using chunk()");
            }
        }
    }

    private function checkArchitecturalPatterns(): void
    {
        echo "🏛️  Analyzing Architectural Patterns...\n";

        $this->checkNamingConventions();
        $this->checkCodeDuplication();
        $this->checkLayerSeparation();
    }

    private function checkNamingConventions(): void
    {
        // Check controller naming
        $controllers = glob('app/Domains/*/Controllers/*.php');
        foreach ($controllers as $filePath) {
            $fileName = basename($filePath, '.php');
            if (!str_ends_with($fileName, 'Controller')) {
                $this->addIssue('architecture', 'naming_conventions', $filePath,
                    "Controller {$fileName} should end with 'Controller'");
            }
        }

        // Check service naming
        $services = glob('app/Domains/*/Services/*.php');
        foreach ($services as $filePath) {
            $fileName = basename($filePath, '.php');
            if (!str_ends_with($fileName, 'Service')) {
                $this->addIssue('architecture', 'naming_conventions', $filePath,
                    "Service {$fileName} should end with 'Service'");
            }
        }
    }

    private function checkCodeDuplication(): void
    {
        // Use simple hashing to detect similar code blocks
        $codeBlocks = [];
        $files = array_merge(
            glob('app/Domains/*/Controllers/*.php'),
            glob('app/Domains/*/Services/*.php')
        );

        foreach ($files as $filePath) {
            $content = file_get_contents($filePath);
            $methods = $this->extractMethods($content);
            
            foreach ($methods as $method) {
                $hash = md5(preg_replace('/\s+/', '', $method['code']));
                if (isset($codeBlocks[$hash])) {
                    $this->addIssue('maintainability', 'code_duplication', $filePath,
                        "Duplicate code detected: method {$method['name']} is similar to {$codeBlocks[$hash]}");
                } else {
                    $codeBlocks[$hash] = "{$filePath}::{$method['name']}";
                }
            }
        }
    }

    private function checkLayerSeparation(): void
    {
        // Check that controllers don't have business logic
        $controllers = glob('app/Domains/*/Controllers/*.php');
        foreach ($controllers as $filePath) {
            $content = file_get_contents($filePath);
            
            if ($this->hasBuisnessLogic($content)) {
                $this->addIssue('architecture', 'service_layer', $filePath,
                    "Controller contains business logic that should be in service layer");
            }
        }
    }

    // Helper methods for pattern detection

    private function hasBuisnessLogic(string $content): bool
    {
        $businessLogicPatterns = [
            '/DB::transaction/',
            '/Log::info/',
            '/Mail::send/',
            '/Event::dispatch/',
            '/Cache::put/',
            'complex calculation',
            'data transformation'
        ];

        foreach ($businessLogicPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    private function hasControllerMethodsWithoutAuthorization(string $content): bool
    {
        $protectedMethods = ['store', 'update', 'destroy'];
        
        foreach ($protectedMethods as $method) {
            if (preg_match("/public function {$method}/", $content) && 
                !preg_match("/authorize\s*\(/", $content)) {
                return true;
            }
        }

        return false;
    }

    private function hasManualCompanyScoping(string $content): bool
    {
        $patterns = [
            '/where\s*\(\s*["\']company_id["\']/',
            '/->forCurrentCompany\(\)/',
            '/Auth::user\(\)->company_id/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    private function hasNPlusOnePattern(string $content): bool
    {
        return preg_match('/foreach\s*\([^}]+\$\w+\s*->\s*\w+/', $content);
    }

    private function hasManualTransactions(string $content): bool
    {
        return preg_match('/DB::beginTransaction|DB::commit|DB::rollback/', $content);
    }

    private function hasManualScopeInModel(string $content): bool
    {
        return preg_match('/scopeForCurrentCompany|scopeCompany/', $content);
    }

    private function hasProperMassAssignmentProtection(string $content): bool
    {
        return str_contains($content, '$fillable') || str_contains($content, '$guarded');
    }

    private function hasWeakValidation(string $content): bool
    {
        // Check for missing validation on sensitive fields
        $sensitiveFields = ['email', 'password', 'amount', 'price'];
        
        foreach ($sensitiveFields as $field) {
            if (str_contains($content, "'{$field}'") && 
                !preg_match("/{$field}.*required/", $content)) {
                return true;
            }
        }

        return false;
    }

    private function hasUnscopedRelationshipValidation(string $content): bool
    {
        return preg_match('/exists:\w+,id["\'](?![^}]*where)/', $content);
    }

    private function hasHighComplexity(string $content): bool
    {
        // Simple cyclomatic complexity check
        $complexityIndicators = ['if', 'else', 'elseif', 'switch', 'case', 'for', 'foreach', 'while', 'catch'];
        $complexity = 0;
        
        foreach ($complexityIndicators as $indicator) {
            $complexity += substr_count($content, $indicator);
        }

        return $complexity > 10; // Arbitrary threshold
    }

    private function extractMethods(string $content): array
    {
        $methods = [];
        preg_match_all('/public function (\w+)\([^{]*\{([^}]*\{[^}]*\}[^}]*)*[^}]*\}/s', $content, $matches);
        
        for ($i = 0; $i < count($matches[0]); $i++) {
            $methods[] = [
                'name' => $matches[1][$i],
                'code' => $matches[0][$i]
            ];
        }

        return $methods;
    }

    private function addIssue(string $category, string $rule, string $file, string $message): void
    {
        $this->issues[] = [
            'category' => $category,
            'rule' => $rule,
            'file' => $file,
            'message' => $message,
            'severity' => $this->getSeverity($category, $rule)
        ];
    }

    private function getSeverity(string $category, string $rule): string
    {
        $criticalRules = [
            'security.sql_injection',
            'security.authorization',
            'security.company_scoping'
        ];

        $warningRules = [
            'performance.n_plus_one',
            'architecture.base_classes'
        ];

        $ruleKey = "{$category}.{$rule}";

        if (in_array($ruleKey, $criticalRules)) {
            return 'critical';
        } elseif (in_array($ruleKey, $warningRules)) {
            return 'warning';
        } else {
            return 'info';
        }
    }

    private function fixManualCompanyScoping(string $filePath, string $content): void
    {
        // Simple fix: Replace manual company scoping with comments
        $patterns = [
            '/->where\s*\(\s*["\']company_id["\'][^)]*\)/' => '// Company scoping now automatic via HasCompanyScope trait',
            '/->forCurrentCompany\(\)/' => '// Company scoping now automatic via HasCompanyScope trait'
        ];

        $fixed = false;
        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
                $fixed = true;
            }
        }

        if ($fixed) {
            file_put_contents($filePath, $content);
            $this->fixed[] = $filePath;
        }
    }

    private function generateReport(): void
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "CODE QUALITY ANALYSIS REPORT\n";
        echo str_repeat("=", 70) . "\n\n";

        $issuesByCategory = [];
        $issuesBySeverity = ['critical' => 0, 'warning' => 0, 'info' => 0];

        foreach ($this->issues as $issue) {
            $issuesByCategory[$issue['category']][] = $issue;
            $issuesBySeverity[$issue['severity']]++;
        }

        // Summary
        echo "📊 Summary:\n";
        echo "  • Total Issues: " . count($this->issues) . "\n";
        echo "  • Critical: {$issuesBySeverity['critical']}\n";
        echo "  • Warnings: {$issuesBySeverity['warning']}\n";
        echo "  • Info: {$issuesBySeverity['info']}\n";
        
        if ($this->autoFix) {
            echo "  • Fixed: " . count($this->fixed) . "\n";
        }

        echo "\n";

        // Issues by category
        foreach ($issuesByCategory as $category => $issues) {
            echo "🔍 " . ucfirst($category) . " Issues (" . count($issues) . "):\n";
            
            foreach ($issues as $issue) {
                $icon = $this->getSeverityIcon($issue['severity']);
                $file = str_replace(getcwd() . '/', '', $issue['file']);
                echo "  {$icon} {$file}\n";
                echo "      {$issue['message']}\n";
            }
            echo "\n";
        }

        // Recommendations
        echo "💡 Recommendations:\n";
        
        if ($issuesBySeverity['critical'] > 0) {
            echo "  🚨 Address critical security issues immediately\n";
        }
        
        if ($issuesBySeverity['warning'] > 5) {
            echo "  ⚠️  Focus on performance and architecture warnings\n";
        }
        
        echo "  📈 Run automated refactoring script for common issues\n";
        echo "  🧪 Use --fix flag to automatically resolve simple issues\n";
        echo "  📚 Review training guide for best practices\n";

        // Export detailed report
        $reportData = [
            'timestamp' => date('c'),
            'target_domain' => $this->targetDomain,
            'mode' => $this->autoFix ? 'fix' : 'analysis',
            'strict' => $this->strictMode,
            'summary' => [
                'total_issues' => count($this->issues),
                'by_severity' => $issuesBySeverity,
                'by_category' => array_map('count', $issuesByCategory),
                'files_fixed' => count($this->fixed)
            ],
            'issues' => $this->issues,
            'fixed_files' => $this->fixed
        ];

        file_put_contents('code-quality-report.json', json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed report exported to: code-quality-report.json\n";

        // Exit code for CI/CD
        if ($issuesBySeverity['critical'] > 0) {
            exit(2); // Critical issues found
        } elseif ($issuesBySeverity['warning'] > 0) {
            exit(1); // Warnings found
        } else {
            exit(0); // All good
        }
    }

    private function getSeverityIcon(string $severity): string
    {
        return match($severity) {
            'critical' => '🚨',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => '•'
        };
    }
}

// Command line interface
function parseArguments(array $argv): array
{
    $options = [];
    
    foreach ($argv as $arg) {
        if ($arg === '--fix') {
            $options['fix'] = true;
        } elseif ($arg === '--strict') {
            $options['strict'] = true;
        } elseif (str_starts_with($arg, '--domain=')) {
            $options['domain'] = substr($arg, 9);
        }
    }
    
    return $options;
}

// Run the quality checks
if (php_sapi_name() === 'cli') {
    $options = parseArguments($argv);
    $checker = new NestogyCodeQualityChecker($options);
    $checker->run();
}