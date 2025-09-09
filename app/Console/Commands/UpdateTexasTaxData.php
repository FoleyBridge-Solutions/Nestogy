<?php

namespace App\Console\Commands;

use App\Services\TaxEngine\TexasComptrollerDataService;
use App\Notifications\TexasTaxDataUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class UpdateTexasTaxData extends Command
{
    private const MAX_RETRIES = 3;
    private const DEFAULT_BATCH_SIZE = 100;

    // Class constants to reduce duplication
    private const API_BASE_URL = 'https://api.example.com';
    private const MSG_UPDATE_START = 'Starting Texas tax data update...';
    private const MSG_UPDATE_COMPLETE = 'Texas tax data update completed';

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nestogy:update-texas-tax-data
                          {--quarter= : Specific quarter to download (e.g., 2025Q3)}
                          {--force : Force update even if data exists for this quarter}
                          {--counties= : Comma-separated list of county FIPS codes to download}
                          {--all-counties : Download address data for ALL 254 Texas counties}
                          {--addresses : Also download and process address data}
                          {--parallel=10 : Number of counties to process in parallel (default: 10)}';

    /**
     * The console command description.
     */
    protected $description = 'Download and process official Texas Comptroller tax data quarterly';

    protected TexasComptrollerDataService $service;

    public function __construct(TexasComptrollerDataService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏛️ Texas Comptroller Tax Data Update');
        $this->info('====================================');

        try {
            // Determine quarter
            $quarter = $this->option('quarter') ?: $this->getCurrentQuarter();
            $this->info("📅 Processing quarter: {$quarter}");

            // Check if we should update
            if (!$this->option('force') && $this->isQuarterAlreadyProcessed($quarter)) {
                $this->warn("⚠️ Data for {$quarter} already exists. Use --force to update anyway.");
                return Command::SUCCESS;
            }

            $this->newLine();

            // Step 1: Download and process tax jurisdiction rates
            $this->info('📊 Step 1: Processing tax jurisdiction rates...');
            $taxRatesResult = $this->processTaxRates($quarter);

            if (!$taxRatesResult['success']) {
                $this->error("❌ Failed to process tax rates: " . $taxRatesResult['error']);
                return Command::FAILURE;
            }

            $this->info("✅ Imported {$taxRatesResult['count']} tax jurisdiction rates");
            $this->newLine();

            // Step 2: Download address data if requested
            if ($this->option('addresses')) {
                $this->info('🏠 Step 2: Processing address data...');
                $addressResult = $this->processAddressData($quarter);

                if (!$addressResult['success']) {
                    $errorMessage = $addressResult['error'] ?? 'Unknown error occurred';
                    $this->warn("⚠️ Address data processing failed: " . $errorMessage);
                } else {
                    $this->info("✅ Processed address data for {$addressResult['counties']} counties");
                }
                $this->newLine();
            }

            // Step self::MAX_RETRIES: Update system metadata
            $this->updateSystemMetadata($quarter);
            $this->info("✅ Updated system metadata for {$quarter}");

            // Step 4: Send notifications
            if (config('texas-tax.automation.send_notifications')) {
                $this->sendUpdateNotifications($quarter, $taxRatesResult, $addressResult ?? null);
            }

            // Step 5: Log success
            $this->logUpdate($quarter, $taxRatesResult);

            $this->info('🎯 Tax data update completed successfully!');
            $this->info('💰 Using FREE official Texas Comptroller data instead of paid services');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Update failed: " . $e->getMessage());
            Log::error('Texas tax data update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Process tax jurisdiction rates
     */
    protected function processTaxRates(string $quarter): array
    {
        try {
            // Try to download from API first
            $this->line("Downloading tax rates from Texas Comptroller API...");

            $download = $this->service->downloadTaxRatesFile();

            if ($download['success']) {
                $this->info("✅ Downloaded from API");
                $content = $download['content'];
            } else {
                // Fallback to local file if API fails
                $this->warn("⚠️ API download failed, checking for local file...");
                $localPath = base_path('tax_jurisdiction_rates-' . $quarter . '.csv');

                if (file_exists($localPath)) {
                    $this->info("✅ Using local file: {$localPath}");
                    $content = file_get_contents($localPath);
                } else {
                    return [
                        'success' => false,
                        'error' => 'No API access and no local file found: ' . $localPath
                    ];
                }
            }

            // Parse the content
            $this->line("Parsing tax jurisdiction rates...");
            $parseResult = $this->service->parseTaxRatesFile($content);

            if (!$parseResult['success']) {
                return $parseResult;
            }

            // Import into database
            $this->line("Importing {$parseResult['count']} jurisdictions into database...");
            $progressBar = $this->output->createProgressBar($parseResult['count']);
            $progressBar->start();

            $updateResult = $this->service->updateDatabaseWithTexasRates($parseResult['jurisdictions']);

            $progressBar->finish();
            $this->newLine();

            return [
                'success' => $updateResult['success'],
                'count' => $updateResult['inserted'] ?? 0,
                'error' => $updateResult['error'] ?? null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process address data for specified counties
     */
    protected function processAddressData(string $quarter): array
    {
        try {
            // Determine which counties to process
            if ($this->option('all-counties')) {
                $counties = $this->getAllTexasCounties();
                $this->info("🏛️ FULL TEXAS STATEWIDE IMPORT: Processing ALL 254 counties");
                $this->info("📊 Estimated records: 15-25 million addresses");
                $this->info("⏱️ Estimated time: 6-12 hours");
                $this->newLine();
            } elseif ($this->option('counties')) {
                $counties = explode(',', $this->option('counties'));
            } else {
                $counties = $this->getHighPriorityCounties();
            }

            $totalCounties = count($counties);
            $parallelLimit = (int)$this->option('parallel');

            $this->line("Processing address data for {$totalCounties} counties...");
            $this->line("Parallel processing: {$parallelLimit} counties at a time");
            $this->newLine();

            $processedCounties = 0;
            $totalAddresses = 0;
            $errors = [];
            $startTime = microtime(true);

            // Create progress bar for overall progress
            $progressBar = $this->output->createProgressBar($totalCounties);
            $progressBar->setFormat('County Progress: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
            $progressBar->start();

            // Process counties in parallel batches
            $countyBatches = array_chunk($counties, $parallelLimit);

            foreach ($countyBatches as $batchIndex => $batch) {
                $batchResults = $this->processBatchParallel($quarter, $batch, $batchIndex + 1, count($countyBatches));

                foreach ($batchResults as $countyFips => $result) {
                    $progressBar->advance();

                    if ($result['success']) {
                        $processedCounties++;
                        $totalAddresses += $result['addresses'];
                    } else {
                        $errors[] = "{$countyFips}: " . $result['error'];
                    }
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            $totalTime = microtime(true) - $startTime;
            $averagePerCounty = $totalTime / max($processedCounties, 1);

            $this->info("✅ Address Import Complete!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Counties Processed', number_format($processedCounties) . '/' . number_format($totalCounties)],
                    ['Total Addresses', number_format($totalAddresses)],
                    ['Processing Time', gmdate('H:i:s', $totalTime)],
                    ['Average per County', number_format($averagePerCounty, 2) . 's'],
                    ['Addresses per Second', number_format($totalAddresses / max($totalTime, 1), 0)],
                    ['Errors', count($errors)]
                ]
            );

            if (!empty($errors)) {
                $this->newLine();
                $this->warn("⚠️ Counties with errors:");
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->line("   • {$error}");
                }
                if (count($errors) > 10) {
                    $this->line("   ... and " . (count($errors) - 10) . " more");
                }
            }

            return [
                'success' => $processedCounties > 0,
                'counties' => $processedCounties,
                'addresses' => $totalAddresses,
                'errors' => $errors,
                'processing_time' => $totalTime
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process a batch of counties in parallel using Laravel's process pools
     */
    protected function processBatchParallel(string $quarter, array $counties, int $batchNumber, int $totalBatches): array
    {
        $results = [];

        $this->newLine();
        $this->line("📦 Processing batch {$batchNumber}/{$totalBatches} (" . count($counties) . " counties)");

        // For now, process sequentially but prepare for parallel execution
        // In the future, this could use PHP parallel processing or queue jobs
        foreach ($counties as $countyFips) {
            $countyFips = trim($countyFips);

            try {
                $startTime = microtime(true);
                $result = $this->processCountyAddressData($quarter, $countyFips);
                $processingTime = microtime(true) - $startTime;

                if ($result['success']) {
                    $this->line("   ✅ {$countyFips}: " . number_format($result['addresses']) . " addresses (" . number_format($processingTime, 1) . "s)");
                } else {
                    $this->line("   ❌ {$countyFips}: " . $result['error']);
                }

                $results[$countyFips] = $result;

            } catch (\Exception $e) {
                $this->line("   ❌ {$countyFips}: " . $e->getMessage());
                $results[$countyFips] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'addresses' => 0
                ];
            }

            // Add small delay to prevent overwhelming the API
            usleep(100000); // 0.1 second delay between counties
        }

        return $results;
    }

    /**
     * Process address data for a specific county
     */
    protected function processCountyAddressData(string $quarter, string $countyFips): array
    {
        return $this->service->processCountyAddressData($countyFips, $quarter);
    }

    /**
     * Get high-priority counties for address processing
     */
    protected function getHighPriorityCounties(): array
    {
        // Major Texas counties by population/business activity
        return [
            '201', // Harris County (Houston)
            '113', // Dallas County
            '029', // Bexar County (San Antonio)
            '453', // Travis County (Austin)
            '439', // Tarrant County (Fort Worth)
            '157', // Fort Bend County
            '085', // Collin County
            '121', // Denton County
        ];
    }

    /**
     * Get ALL 254 Texas county FIPS codes for complete statewide coverage
     */
    protected function getAllTexasCounties(): array
    {
        // All 254 Texas counties by FIPS code
        return [
            '001', '003', '005', '007', '009', '011', '013', '015', '017', '019', // 001-019
            '021', '023', '025', '027', '029', '031', '033', '035', '037', '039', // 021-039
            '041', '043', '045', '047', '049', '051', '053', '055', '057', '059', // 041-059
            '061', '063', '065', '067', '069', '071', '073', '075', '077', '079', // 061-079
            '081', '083', '085', '087', '089', '091', '093', '095', '097', '099', // 081-099
            '101', '103', '105', '107', '109', '111', '113', '115', '117', '119', // 101-119
            '121', '123', '125', '127', '129', '131', '133', '135', '137', '139', // 121-139
            '141', '143', '145', '147', '149', '151', '153', '155', '157', '159', // 141-159
            '161', '163', '165', '167', '169', '171', '173', '175', '177', '179', // 161-179
            '181', '183', '185', '187', '189', '191', '193', '195', '197', '199', // 181-199
            '201', '203', '205', '207', '209', '211', '213', '215', '217', '219', // 201-219
            '221', '223', '225', '227', '229', '231', '233', '235', '237', '239', // 221-239
            '241', '243', '245', '247', '249', '251', '253', '255', '257', '259', // 241-259
            '261', '263', '265', '267', '269', '271', '273', '275', '277', '279', // 261-279
            '281', '283', '285', '287', '289', '291', '293', '295', '297', '299', // 281-299
            '301', '303', '305', '307', '309', '311', '313', '315', '317', '319', // 301-319
            '321', '323', '325', '327', '329', '331', '333', '335', '337', '339', // 321-339
            '341', '343', '345', '347', '349', '351', '353', '355', '357', '359', // 341-359
            '361', '363', '365', '367', '369', '371', '373', '375', '377', '379', // 361-379
            '381', '383', '385', '387', '389', '391', '393', '395', '397', '399', // 381-399
            '401', '403', '405', '407', '409', '411', '413', '415', '417', '419', // 401-419
            '421', '423', '425', '427', '429', '431', '433', '435', '437', '439', // 421-439
            '441', '443', '445', '447', '449', '451', '453', '455', '457', '459', // 441-459
            '461', '463', '465', '467', '469', '471', '473', '475', '477', '479', // 461-479
            '481', '483', '485', '487', '489', '491', '493', '495', '497', '499', // 481-499
            '501', '503', '505', '507'  // 501-507
        ];
    }

    /**
     * Get current quarter
     */
    protected function getCurrentQuarter(): string
    {
        $year = date('Y');
        $quarter = 'Q' . ceil(date('n') / self::MAX_RETRIES);
        return $year . $quarter;
    }

    /**
     * Check if quarter is already processed
     */
    protected function isQuarterAlreadyProcessed(string $quarter): bool
    {
        return DB::table('service_tax_rates')
            ->where('source', 'texas_comptroller')
            ->whereRaw("JSON_EXTRACT(metadata, '$.quarter') = ?", [$quarter])
            ->exists();
    }

    /**
     * Update system metadata
     */
    protected function updateSystemMetadata(string $quarter): void
    {
        $metadata = [
            'last_updated' => now()->toISOString(),
            'quarter' => $quarter,
            'source' => 'texas_comptroller_official',
            'version' => '1.0',
            'cost' => 'FREE'
        ];

        // Store in cache for quick access
        cache(['texas_tax_data_metadata' => $metadata], now()->addMonths(self::MAX_RETRIES));

        // Also store in database if table exists
        try {
            if (DB::getSchemaBuilder()->hasTable('system_settings')) {
                DB::table('system_settings')->updateOrInsert(
                    ['key' => 'texas_tax_data_metadata'],
                    [
                        'value' => json_encode($metadata),
                        'updated_at' => now()
                    ]
                );
            }
        } catch (\Exception $e) {
            // Silently continue if system_settings table doesn't exist
            Log::debug('System settings table not available for metadata storage', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send update notifications
     */
    protected function sendUpdateNotifications(string $quarter, array $taxRatesResult, ?array $addressResult = null): void
    {
        try {
            $updateData = [
                'quarter' => $quarter,
                'tax_rates_count' => $taxRatesResult['count'] ?? 0,
                'address_counties' => $addressResult['counties'] ?? 0,
                'updated_at' => now()->toISOString(),
            ];

            // Get notification emails from config
            $emails = config('texas-tax.automation.notification_emails', []);
            $emails = array_filter($emails); // Remove empty values

            if (!empty($emails)) {
                $this->line("Sending notifications to " . count($emails) . " recipients...");

                Notification::route('mail', $emails)
                    ->notify(new TexasTaxDataUpdated($updateData));

                $this->info("✅ Notifications sent successfully");
            } else {
                $this->warn("⚠️ No notification emails configured");
            }

        } catch (\Exception $e) {
            $this->warn("⚠️ Failed to send notifications: " . $e->getMessage());
            Log::warning('Failed to send Texas tax data notifications', [
                'error' => $e->getMessage(),
                'quarter' => $quarter
            ]);
        }
    }

    /**
     * Log the update
     */
    protected function logUpdate(string $quarter, array $taxRatesResult): void
    {
        Log::info('Texas tax data updated successfully', [
            'quarter' => $quarter,
            'tax_rates_imported' => $taxRatesResult['count'] ?? 0,
            'source' => 'texas_comptroller_official',
            'command' => 'nestogy:update-texas-tax-data'
        ]);
    }
}
