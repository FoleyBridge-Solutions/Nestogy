<?php

/**
 * Automated Refactoring Script for Nestogy Deduplication
 * 
 * This script helps migrate existing controllers, services, and form requests
 * to use the new base classes and traits automatically.
 * 
 * Usage: php refactor-automation.php [--dry-run] [--domain=DomainName] [--type=controller|service|request]
 */

class NestogyRefactorAutomation
{
    private bool $dryRun = false;
    private ?string $targetDomain = null;
    private ?string $targetType = null;
    private array $refactoredFiles = [];
    private array $errors = [];

    public function __construct(array $options = [])
    {
        $this->dryRun = $options['dry-run'] ?? false;
        $this->targetDomain = $options['domain'] ?? null;
        $this->targetType = $options['type'] ?? null;
    }

    public function run(): void
    {
        echo "🔄 Starting Nestogy Deduplication Refactoring...\n";
        echo $this->dryRun ? "🧪 DRY RUN MODE - No files will be modified\n" : "✏️  LIVE MODE - Files will be modified\n";
        echo "\n";

        if ($this->targetType === null || $this->targetType === 'controller') {
            $this->refactorControllers();
        }

        if ($this->targetType === null || $this->targetType === 'service') {
            $this->refactorServices();
        }

        if ($this->targetType === null || $this->targetType === 'request') {
            $this->refactorFormRequests();
        }

        if ($this->targetType === null || $this->targetType === 'model') {
            $this->refactorModels();
        }

        $this->generateReport();
    }

    private function refactorControllers(): void
    {
        echo "🎛️  Refactoring Controllers...\n";

        $pattern = $this->targetDomain 
            ? "app/Domains/{$this->targetDomain}/Controllers/*Controller.php"
            : "app/Domains/*/Controllers/*Controller.php";

        $controllers = glob($pattern);

        foreach ($controllers as $controllerPath) {
            if (strpos($controllerPath, 'Refactored.php') !== false) {
                continue; // Skip already refactored examples
            }

            try {
                $this->refactorController($controllerPath);
            } catch (Exception $e) {
                $this->errors[] = "Controller {$controllerPath}: " . $e->getMessage();
            }
        }
    }

    private function refactorController(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Extract class information
        if (!preg_match('/class\s+(\w+)\s+extends\s+Controller/', $content, $matches)) {
            throw new Exception("Could not find controller class declaration");
        }

        $className = $matches[1];
        $domainPath = dirname(dirname($filePath));
        $domain = basename($domainPath);

        // Determine model and service classes
        $modelName = str_replace('Controller', '', $className);
        if ($modelName === 'Clients') $modelName = 'Client'; // Handle plural controller names
        
        $resourceName = strtolower(str_replace('Controller', '', $className));
        if (!str_ends_with($resourceName, 's')) {
            $resourceName .= 's'; // Ensure plural for resource name
        }

        // Step 1: Change extends clause
        $content = str_replace('extends Controller', 'extends BaseController', $content);

        // Step 2: Add BaseController import
        if (!str_contains($content, 'use App\\Http\\Controllers\\BaseController;')) {
            $content = preg_replace(
                '/(use [^;]+Controller[^;]*;)/',
                "$1\nuse App\\Http\\Controllers\\BaseController;",
                $content,
                1
            );
        }

        // Step 3: Add initializeController method
        $initMethod = $this->generateInitializeControllerMethod($modelName, $domain, $resourceName);
        
        // Find the class body and add the method
        $content = preg_replace(
            '/(class\s+\w+\s+extends\s+BaseController\s*\{)/',
            "$1\n" . $initMethod,
            $content
        );

        // Step 4: Remove or simplify standard CRUD methods
        $content = $this->removeStandardCrudMethods($content);

        // Step 5: Convert filtering logic to protected methods
        $content = $this->convertToFilterMethods($content);

        if ($content !== $originalContent) {
            $backupPath = $filePath . '.backup';
            
            if (!$this->dryRun) {
                // Create backup
                copy($filePath, $backupPath);
                
                // Write refactored content
                file_put_contents($filePath, $content);
            }

            $this->refactoredFiles[] = [
                'type' => 'controller',
                'file' => $filePath,
                'backup' => $backupPath,
                'lines_reduced' => substr_count($originalContent, "\n") - substr_count($content, "\n")
            ];

            echo "  ✅ {$className}: " . (substr_count($originalContent, "\n") - substr_count($content, "\n")) . " lines reduced\n";
        }
    }

    private function generateInitializeControllerMethod(string $modelName, string $domain, string $resourceName): string
    {
        return "
    protected function initializeController(): void
    {
        \$this->modelClass = \\App\\Domains\\{$domain}\\Models\\{$modelName}::class;
        \$this->serviceClass = \\App\\Domains\\{$domain}\\Services\\{$modelName}Service::class;
        \$this->resourceName = '{$resourceName}';
        \$this->viewPrefix = '{$resourceName}';
        \$this->eagerLoadRelations = []; // TODO: Add relationships to eager load
    }
";
    }

    private function removeStandardCrudMethods(string $content): string
    {
        // Remove standard index method if it's just basic listing
        $content = preg_replace(
            '/public function index\([^}]+\{[^}]*\$query\s*=.*?return view\([^}]+\}/s',
            '// index() method now handled by BaseController',
            $content
        );

        // Remove standard store method
        $content = preg_replace(
            '/public function store\([^}]+\{.*?return redirect\([^}]+\}/s',
            '// store() method now handled by BaseController',
            $content
        );

        // Remove standard show method
        $content = preg_replace(
            '/public function show\([^}]+\{[^}]*return view\([^}]+\}/s',
            '// show() method now handled by BaseController',
            $content
        );

        // Remove standard edit method
        $content = preg_replace(
            '/public function edit\([^}]+\{[^}]*return view\([^}]+\}/s',
            '// edit() method now handled by BaseController',
            $content
        );

        // Remove standard update method
        $content = preg_replace(
            '/public function update\([^}]+\{.*?return redirect\([^}]+\}/s',
            '// update() method now handled by BaseController',
            $content
        );

        // Remove standard destroy method
        $content = preg_replace(
            '/public function destroy\([^}]+\{.*?return redirect\([^}]+\}/s',
            '// destroy() method now handled by BaseController',
            $content
        );

        return $content;
    }

    private function convertToFilterMethods(string $content): string
    {
        // Look for filtering patterns and convert them
        if (preg_match('/\$query.*?->where\(/', $content)) {
            $filterMethod = "
    protected function getFilters(Request \$request): array
    {
        return \$request->only(['search', 'status', 'type']); // TODO: Customize filter fields
    }

    protected function applyCustomFilters(\$query, Request \$request)
    {
        // TODO: Move custom filtering logic here
        return \$query;
    }
";
            
            // Add the methods before the last closing brace
            $content = preg_replace(
                '/(\}\s*)$/',
                $filterMethod . "\n$1",
                $content
            );
        }

        return $content;
    }

    private function refactorServices(): void
    {
        echo "⚙️  Refactoring Services...\n";

        $pattern = $this->targetDomain 
            ? "app/Domains/{$this->targetDomain}/Services/*Service.php"
            : "app/Domains/*/Services/*Service.php";

        $services = glob($pattern);

        foreach ($services as $servicePath) {
            if (strpos($servicePath, 'Refactored.php') !== false) {
                continue;
            }

            try {
                $this->refactorService($servicePath);
            } catch (Exception $e) {
                $this->errors[] = "Service {$servicePath}: " . $e->getMessage();
            }
        }
    }

    private function refactorService(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Extract class information
        if (!preg_match('/class\s+(\w+Service)/', $content, $matches)) {
            throw new Exception("Could not find service class declaration");
        }

        $className = $matches[1];
        $modelName = str_replace('Service', '', $className);

        // Add BaseService import and extend
        if (!str_contains($content, 'use App\\Services\\BaseService;')) {
            $content = preg_replace(
                '/(namespace[^;]+;)/',
                "$1\n\nuse App\\Services\\BaseService;",
                $content
            );
        }

        // Change class declaration
        $content = preg_replace(
            '/class\s+\w+Service\s*\{/',
            "class {$className} extends BaseService\n{",
            $content
        );

        // Add initializeService method
        $initMethod = $this->generateInitializeServiceMethod($modelName);
        $content = preg_replace(
            '/(class\s+\w+Service\s+extends\s+BaseService\s*\{)/',
            "$1\n" . $initMethod,
            $content
        );

        // Convert standard methods to hooks
        $content = $this->convertServiceMethodsToHooks($content);

        if ($content !== $originalContent) {
            $backupPath = $filePath . '.backup';
            
            if (!$this->dryRun) {
                copy($filePath, $backupPath);
                file_put_contents($filePath, $content);
            }

            $this->refactoredFiles[] = [
                'type' => 'service',
                'file' => $filePath,
                'backup' => $backupPath,
                'lines_reduced' => substr_count($originalContent, "\n") - substr_count($content, "\n")
            ];

            echo "  ✅ {$className}: " . (substr_count($originalContent, "\n") - substr_count($content, "\n")) . " lines reduced\n";
        }
    }

    private function generateInitializeServiceMethod(string $modelName): string
    {
        return "
    protected function initializeService(): void
    {
        \$this->modelClass = {$modelName}::class;
        \$this->searchableFields = ['name']; // TODO: Customize searchable fields
    }
";
    }

    private function convertServiceMethodsToHooks(string $content): string
    {
        // Convert create method to afterCreate hook
        $content = preg_replace(
            '/public function create\(array \$data\)[^{]*\{.*?\}/s',
            'protected function afterCreate(Model $model, array $data): void
    {
        // TODO: Move post-creation logic here
    }',
            $content
        );

        return $content;
    }

    private function refactorFormRequests(): void
    {
        echo "📝 Refactoring Form Requests...\n";

        $pattern = $this->targetDomain 
            ? "app/Domains/{$this->targetDomain}/Requests/*Request.php"
            : "app/Domains/*/Requests/*Request.php";

        $requests = glob($pattern);

        foreach ($requests as $requestPath) {
            try {
                $this->refactorFormRequest($requestPath);
            } catch (Exception $e) {
                $this->errors[] = "Form Request {$requestPath}: " . $e->getMessage();
            }
        }
    }

    private function refactorFormRequest(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Add BaseFormRequest import and extend
        if (!str_contains($content, 'use App\\Http\\Requests\\BaseFormRequest;')) {
            $content = preg_replace(
                '/(namespace[^;]+;)/',
                "$1\n\nuse App\\Http\\Requests\\BaseFormRequest;",
                $content
            );
        }

        // Change extends clause
        $content = str_replace('extends FormRequest', 'extends BaseFormRequest', $content);

        // Convert rules method to getSpecificRules
        $content = preg_replace(
            '/public function rules\(\): array/',
            'protected function getSpecificRules(): array',
            $content
        );

        // Add initializeRequest method
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
            $modelName = preg_replace('/(Store|Update)(\w+)Request/', '$2', $className);
            
            $initMethod = "
    protected function initializeRequest(): void
    {
        \$this->modelClass = {$modelName}::class;
    }
";
            
            $content = preg_replace(
                '/(class\s+\w+\s+extends\s+BaseFormRequest\s*\{)/',
                "$1\n" . $initMethod,
                $content
            );
        }

        if ($content !== $originalContent) {
            $backupPath = $filePath . '.backup';
            
            if (!$this->dryRun) {
                copy($filePath, $backupPath);
                file_put_contents($filePath, $content);
            }

            $this->refactoredFiles[] = [
                'type' => 'request',
                'file' => $filePath,
                'backup' => $backupPath,
                'lines_reduced' => substr_count($originalContent, "\n") - substr_count($content, "\n")
            ];

            echo "  ✅ " . basename($filePath) . ": " . (substr_count($originalContent, "\n") - substr_count($content, "\n")) . " lines reduced\n";
        }
    }

    private function refactorModels(): void
    {
        echo "🏗️  Refactoring Models...\n";

        $pattern = $this->targetDomain 
            ? "app/Domains/{$this->targetDomain}/Models/*.php"
            : "app/Domains/*/Models/*.php";

        $models = array_merge(glob($pattern), glob('app/Models/*.php'));

        foreach ($models as $modelPath) {
            if (strpos($modelPath, 'Refactored.php') !== false) {
                continue;
            }

            try {
                $this->refactorModel($modelPath);
            } catch (Exception $e) {
                $this->errors[] = "Model {$modelPath}: " . $e->getMessage();
            }
        }
    }

    private function refactorModel(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Check if already has BelongsToCompany trait
        if (!str_contains($content, 'BelongsToCompany')) {
            return; // Skip models that aren't multi-tenant
        }

        // Add trait imports
        $traitsToAdd = [
            'HasCompanyScope',
            'HasSearch',
            'HasFilters',
            'HasArchiving',
            'HasActivity'
        ];

        foreach ($traitsToAdd as $trait) {
            if (!str_contains($content, "use App\\Traits\\{$trait};")) {
                $content = preg_replace(
                    '/(use App\\Traits\\BelongsToCompany;)/',
                    "$1\nuse App\\Traits\\{$trait};",
                    $content
                );
            }
        }

        // Add traits to use statement
        $traitsList = implode(',\n        ', $traitsToAdd);
        $content = preg_replace(
            '/(use HasFactory[^;]*BelongsToCompany);/',
            "$1,\n        {$traitsList};",
            $content
        );

        // Add searchableFields property
        if (!str_contains($content, 'searchableFields')) {
            $searchableProperty = "
    protected array \$searchableFields = [
        'name' // TODO: Customize searchable fields
    ];
";
            $content = preg_replace(
                '/(\$fillable\s*=\s*\[[^\]]+\];)/',
                "$1\n" . $searchableProperty,
                $content
            );
        }

        if ($content !== $originalContent) {
            $backupPath = $filePath . '.backup';
            
            if (!$this->dryRun) {
                copy($filePath, $backupPath);
                file_put_contents($filePath, $content);
            }

            $this->refactoredFiles[] = [
                'type' => 'model',
                'file' => $filePath,
                'backup' => $backupPath,
                'lines_reduced' => 0 // Models don't typically reduce lines, they add functionality
            ];

            echo "  ✅ " . basename($filePath) . ": Enhanced with new traits\n";
        }
    }

    private function generateReport(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "REFACTORING REPORT\n";
        echo str_repeat("=", 60) . "\n\n";

        $totalFilesRefactored = count($this->refactoredFiles);
        $totalLinesReduced = array_sum(array_column($this->refactoredFiles, 'lines_reduced'));

        echo "📊 Summary:\n";
        echo "  • Files refactored: {$totalFilesRefactored}\n";
        echo "  • Total lines reduced: " . number_format($totalLinesReduced) . "\n";
        echo "  • Errors encountered: " . count($this->errors) . "\n";

        if ($this->dryRun) {
            echo "\n🧪 This was a dry run - no files were actually modified\n";
        } else {
            echo "\n✅ Files have been refactored and backups created (.backup extension)\n";
        }

        // Group by type
        $byType = [];
        foreach ($this->refactoredFiles as $file) {
            $byType[$file['type']][] = $file;
        }

        foreach ($byType as $type => $files) {
            echo "\n📁 " . ucfirst($type) . "s ({" . count($files) . "}):\n";
            foreach ($files as $file) {
                $lines = $file['lines_reduced'] > 0 ? " (-{$file['lines_reduced']} lines)" : "";
                echo "  • " . basename($file['file']) . $lines . "\n";
            }
        }

        if (!empty($this->errors)) {
            echo "\n❌ Errors:\n";
            foreach ($this->errors as $error) {
                echo "  • {$error}\n";
            }
        }

        echo "\n📋 Next Steps:\n";
        echo "  1. Review refactored files for TODO comments\n";
        echo "  2. Test functionality in development environment\n";
        echo "  3. Customize filter methods and searchable fields\n";
        echo "  4. Remove backup files once satisfied\n";
        echo "  5. Update tests to work with new base classes\n";
    }
}

// Command line interface
function parseArguments(array $argv): array
{
    $options = [];
    
    foreach ($argv as $arg) {
        if ($arg === '--dry-run') {
            $options['dry-run'] = true;
        } elseif (str_starts_with($arg, '--domain=')) {
            $options['domain'] = substr($arg, 9);
        } elseif (str_starts_with($arg, '--type=')) {
            $options['type'] = substr($arg, 7);
        }
    }
    
    return $options;
}

// Run the script
if (php_sapi_name() === 'cli') {
    $options = parseArguments($argv);
    $refactorer = new NestogyRefactorAutomation($options);
    $refactorer->run();
}