<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\Vendor;
use App\Models\Location;
use App\Models\Contact;
use App\Models\Network;
use Carbon\Carbon;
use Faker\Factory as Faker;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Asset Seeder...');
        $faker = Faker::create();

        DB::transaction(function () use ($faker) {
            $companies = Company::where('id', '>', 1)->get(); // Skip Nestogy Platform

            foreach ($companies as $company) {
                $this->command->info("Creating assets for company: {$company->name}");
                
                // Get vendors, locations, contacts, networks for this company
                $vendors = Vendor::where('company_id', $company->id)->pluck('id')->toArray();
                $networks = Network::where('company_id', $company->id)->pluck('id')->toArray();
                
                // Get clients for this company grouped by size
                $clients = Client::where('company_id', $company->id)
                    ->where('lead', false)
                    ->get();
                
                foreach ($clients as $client) {
                    $locations = Location::where('client_id', $client->id)->pluck('id')->toArray();
                    $contacts = Contact::where('client_id', $client->id)->pluck('id')->toArray();
                    
                    // Determine client size based on employee count
                    $employeeCount = $client->employee_count ?? 10;
                    
                    if ($employeeCount >= 100) {
                        // Enterprise client (100-150 assets)
                        $this->createEnterpriseAssets($client, $company, $faker, $vendors, $locations, $contacts, $networks);
                    } elseif ($employeeCount >= 20) {
                        // Mid-market client (20-50 assets)
                        $this->createMidMarketAssets($client, $company, $faker, $vendors, $locations, $contacts, $networks);
                    } else {
                        // Small business client (5-20 assets)
                        $this->createSmallBusinessAssets($client, $company, $faker, $vendors, $locations, $contacts, $networks);
                    }
                }
                
                $this->command->info("Completed assets for company: {$company->name}");
            }
        });

        $this->command->info('Asset Seeder completed!');
    }

    /**
     * Create assets for enterprise clients (100-150 assets)
     */
    private function createEnterpriseAssets($client, $company, $faker, $vendors, $locations, $contacts, $networks)
    {
        // 20-30 Servers
        $serverCount = $faker->numberBetween(20, 30);
        for ($i = 0; $i < $serverCount; $i++) {
            $this->createServer($client, $company, $faker, $vendors, $locations, $contacts, $networks, 'enterprise');
        }
        
        // 50-80 Desktops
        $desktopCount = $faker->numberBetween(50, 80);
        for ($i = 0; $i < $desktopCount; $i++) {
            $this->createDesktop($client, $company, $faker, $vendors, $locations, $contacts, $networks);
        }
        
        // 10-20 Laptops
        $laptopCount = $faker->numberBetween(10, 20);
        for ($i = 0; $i < $laptopCount; $i++) {
            $this->createLaptop($client, $company, $faker, $vendors, $locations, $contacts, $networks);
        }
        
        // 5-10 Routers/Switches
        $networkDeviceCount = $faker->numberBetween(5, 10);
        for ($i = 0; $i < $networkDeviceCount / 2; $i++) {
            $this->createNetworkDevice($client, $company, $faker, $vendors, $locations, $networks, 'Router');
        }
        for ($i = 0; $i < $networkDeviceCount / 2; $i++) {
            $this->createNetworkDevice($client, $company, $faker, $vendors, $locations, $networks, 'Switch');
        }
        
        // 10-15 Printers
        $printerCount = $faker->numberBetween(10, 15);
        for ($i = 0; $i < $printerCount; $i++) {
            $this->createPrinter($client, $company, $faker, $vendors, $locations, $contacts);
        }
        
        // 5-10 Firewalls
        $firewallCount = $faker->numberBetween(5, 10);
        for ($i = 0; $i < $firewallCount; $i++) {
            $this->createFirewall($client, $company, $faker, $vendors, $locations, $networks);
        }
        
        // Other devices (Access Points, Storage, etc.)
        $otherCount = $faker->numberBetween(5, 10);
        for ($i = 0; $i < $otherCount; $i++) {
            $this->createOtherDevice($client, $company, $faker, $vendors, $locations, $contacts, $networks);
        }
    }

    /**
     * Create assets for mid-market clients (20-50 assets)
     */
    private function createMidMarketAssets($client, $company, $faker, $vendors, $locations, $contacts, $networks)
    {
        // 2-5 Servers
        $serverCount = $faker->numberBetween(2, 5);
        for ($i = 0; $i < $serverCount; $i++) {
            $this->createServer($client, $company, $faker, $vendors, $locations, $contacts, $networks, 'midmarket');
        }
        
        // 15-30 Desktops
        $desktopCount = $faker->numberBetween(15, 30);
        for ($i = 0; $i < $desktopCount; $i++) {
            $this->createDesktop($client, $company, $faker, $vendors, $locations, $contacts, $networks);
        }
        
        // 5-10 Laptops
        $laptopCount = $faker->numberBetween(5, 10);
        for ($i = 0; $i < $laptopCount; $i++) {
            $this->createLaptop($client, $company, $faker, $vendors, $locations, $contacts, $networks);
        }
        
        // 3-5 Network devices
        $networkDeviceCount = $faker->numberBetween(3, 5);
        for ($i = 0; $i < $networkDeviceCount; $i++) {
            $type = $faker->randomElement(['Router', 'Switch', 'Firewall']);
            if ($type === 'Firewall') {
                $this->createFirewall($client, $company, $faker, $vendors, $locations, $networks);
            } else {
                $this->createNetworkDevice($client, $company, $faker, $vendors, $locations, $networks, $type);
            }
        }
        
        // 3-5 Printers
        $printerCount = $faker->numberBetween(3, 5);
        for ($i = 0; $i < $printerCount; $i++) {
            $this->createPrinter($client, $company, $faker, $vendors, $locations, $contacts);
        }
    }

    /**
     * Create assets for small business clients (5-20 assets)
     */
    private function createSmallBusinessAssets($client, $company, $faker, $vendors, $locations, $contacts, $networks)
    {
        // 0-1 Server
        if ($faker->boolean(60)) { // 60% chance of having a server
            $this->createServer($client, $company, $faker, $vendors, $locations, $contacts, $networks, 'small');
        }
        
        // 5-15 Desktops
        $desktopCount = $faker->numberBetween(5, 15);
        for ($i = 0; $i < $desktopCount; $i++) {
            $this->createDesktop($client, $company, $faker, $vendors, $locations, $contacts, $networks);
        }
        
        // 2-5 Laptops
        $laptopCount = $faker->numberBetween(2, 5);
        for ($i = 0; $i < $laptopCount; $i++) {
            $this->createLaptop($client, $company, $faker, $vendors, $locations, $contacts, $networks);
        }
        
        // 1-2 Network devices
        $networkDeviceCount = $faker->numberBetween(1, 2);
        for ($i = 0; $i < $networkDeviceCount; $i++) {
            $type = $faker->randomElement(['Router', 'Switch', 'Firewall']);
            if ($type === 'Firewall') {
                $this->createFirewall($client, $company, $faker, $vendors, $locations, $networks);
            } else {
                $this->createNetworkDevice($client, $company, $faker, $vendors, $locations, $networks, $type);
            }
        }
        
        // 1-2 Printers
        $printerCount = $faker->numberBetween(1, 2);
        for ($i = 0; $i < $printerCount; $i++) {
            $this->createPrinter($client, $company, $faker, $vendors, $locations, $contacts);
        }
    }

    /**
     * Create a server asset
     */
    private function createServer($client, $company, $faker, $vendors, $locations, $contacts, $networks, $size)
    {
        $osTypes = ['Windows Server 2022', 'Windows Server 2019', 'Ubuntu Server 22.04', 'CentOS 8', 'Red Hat Enterprise Linux 8'];
        $serverMakes = ['Dell', 'HP', 'Lenovo', 'Cisco', 'IBM'];
        $serverModels = [
            'Dell' => ['PowerEdge R740', 'PowerEdge R640', 'PowerEdge T440'],
            'HP' => ['ProLiant DL380 Gen10', 'ProLiant ML350 Gen10'],
            'Lenovo' => ['ThinkSystem SR650', 'ThinkSystem ST550'],
            'Cisco' => ['UCS C220 M5', 'UCS C240 M5'],
            'IBM' => ['Power System S922', 'Power System E950']
        ];
        
        $make = $faker->randomElement($serverMakes);
        $model = $faker->randomElement($serverModels[$make] ?? ['Generic Server']);
        
        $purchaseDate = $faker->dateTimeBetween('-5 years', '-6 months');
        $warrantyYears = $faker->randomElement([1, 3, 5]);
        
        Asset::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => 'Server',
            'name' => strtoupper($client->name_short ?? substr($client->name, 0, 3)) . '-SRV-' . $faker->unique()->numberBetween(1, 999),
            'description' => $size === 'enterprise' ? 'Production Server' : 'Main Server',
            'make' => $make,
            'model' => $model,
            'serial' => strtoupper($faker->bothify('??######')),
            'os' => $faker->randomElement($osTypes),
            'ip' => $faker->localIpv4(),
            'nat_ip' => $faker->boolean(30) ? $faker->ipv4() : null,
            'mac' => $faker->macAddress(),
            'status' => $faker->randomElement(['Deployed', 'Deployed', 'Deployed', 'Ready To Deploy']),
            'support_status' => $this->getRandomSupportStatus(),
            'support_level' => $faker->randomElement(['enterprise', 'premium', 'standard']),
            'purchase_date' => $purchaseDate,
            'warranty_expire' => Carbon::instance($purchaseDate)->addYears($warrantyYears),
            'install_date' => Carbon::instance($purchaseDate)->addDays($faker->numberBetween(1, 30)),
            'vendor_id' => !empty($vendors) ? $faker->randomElement($vendors) : null,
            'location_id' => !empty($locations) ? $faker->randomElement($locations) : null,
            'contact_id' => !empty($contacts) ? $faker->randomElement($contacts) : null,
            'network_id' => !empty($networks) ? $faker->randomElement($networks) : null,
            'notes' => $faker->optional(0.3)->sentence(),
        ]);
    }

    /**
     * Create a desktop asset
     */
    private function createDesktop($client, $company, $faker, $vendors, $locations, $contacts, $networks)
    {
        $desktopMakes = ['Dell', 'HP', 'Lenovo', 'Apple'];
        $desktopModels = [
            'Dell' => ['OptiPlex 7090', 'OptiPlex 5090', 'OptiPlex 3090'],
            'HP' => ['EliteDesk 800 G8', 'ProDesk 400 G7'],
            'Lenovo' => ['ThinkCentre M720', 'ThinkCentre M920'],
            'Apple' => ['iMac 24"', 'Mac mini M1', 'Mac Studio']
        ];
        
        $make = $faker->randomElement($desktopMakes);
        $model = $faker->randomElement($desktopModels[$make] ?? ['Generic Desktop']);
        $os = $make === 'Apple' ? 'macOS Ventura' : 'Windows 11 Pro';
        
        $purchaseDate = $faker->dateTimeBetween('-5 years', 'now');
        $warrantyYears = $faker->randomElement([1, 3]);
        
        Asset::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => 'Desktop',
            'name' => strtoupper($client->name_short ?? substr($client->name, 0, 3)) . '-WKS-' . $faker->unique()->numberBetween(1, 999),
            'description' => 'Desktop Workstation',
            'make' => $make,
            'model' => $model,
            'serial' => strtoupper($faker->bothify('??######')),
            'os' => $os,
            'ip' => $faker->localIpv4(),
            'mac' => $faker->macAddress(),
            'status' => $faker->randomElement(['Deployed', 'Deployed', 'Deployed', 'Ready To Deploy', 'Archived']),
            'support_status' => $this->getRandomSupportStatus(),
            'support_level' => $faker->randomElement(['standard', 'basic']),
            'purchase_date' => $purchaseDate,
            'warranty_expire' => Carbon::instance($purchaseDate)->addYears($warrantyYears),
            'install_date' => Carbon::instance($purchaseDate)->addDays($faker->numberBetween(1, 14)),
            'vendor_id' => !empty($vendors) ? $faker->randomElement($vendors) : null,
            'location_id' => !empty($locations) ? $faker->randomElement($locations) : null,
            'contact_id' => !empty($contacts) ? $faker->randomElement($contacts) : null,
            'network_id' => !empty($networks) ? $faker->randomElement($networks) : null,
            'notes' => $faker->optional(0.2)->sentence(),
        ]);
    }

    /**
     * Create a laptop asset
     */
    private function createLaptop($client, $company, $faker, $vendors, $locations, $contacts, $networks)
    {
        $laptopMakes = ['Dell', 'HP', 'Lenovo', 'Apple', 'Microsoft'];
        $laptopModels = [
            'Dell' => ['Latitude 5520', 'XPS 15', 'Precision 5560'],
            'HP' => ['EliteBook 850 G8', 'ProBook 450 G8'],
            'Lenovo' => ['ThinkPad X1 Carbon', 'ThinkPad T14'],
            'Apple' => ['MacBook Pro 14"', 'MacBook Pro 16"', 'MacBook Air M2'],
            'Microsoft' => ['Surface Laptop 4', 'Surface Book 3']
        ];
        
        $make = $faker->randomElement($laptopMakes);
        $model = $faker->randomElement($laptopModels[$make] ?? ['Generic Laptop']);
        $os = $make === 'Apple' ? 'macOS Ventura' : 'Windows 11 Pro';
        
        $purchaseDate = $faker->dateTimeBetween('-4 years', 'now');
        $warrantyYears = $faker->randomElement([1, 2, 3]);
        
        Asset::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => 'Laptop',
            'name' => strtoupper($client->name_short ?? substr($client->name, 0, 3)) . '-LTP-' . $faker->unique()->numberBetween(1, 999),
            'description' => 'Laptop Computer',
            'make' => $make,
            'model' => $model,
            'serial' => strtoupper($faker->bothify('??######')),
            'os' => $os,
            'ip' => $faker->optional(0.7)->localIpv4(),
            'mac' => $faker->macAddress(),
            'status' => $faker->randomElement(['Deployed', 'Deployed', 'Deployed', 'Ready To Deploy']),
            'support_status' => $this->getRandomSupportStatus(),
            'support_level' => $faker->randomElement(['standard', 'basic', 'premium']),
            'purchase_date' => $purchaseDate,
            'warranty_expire' => Carbon::instance($purchaseDate)->addYears($warrantyYears),
            'install_date' => Carbon::instance($purchaseDate)->addDays($faker->numberBetween(1, 7)),
            'vendor_id' => !empty($vendors) ? $faker->randomElement($vendors) : null,
            'location_id' => !empty($locations) ? $faker->optional(0.5)->randomElement($locations) : null,
            'contact_id' => !empty($contacts) ? $faker->randomElement($contacts) : null,
            'network_id' => !empty($networks) ? $faker->optional(0.7)->randomElement($networks) : null,
            'notes' => $faker->optional(0.2)->sentence(),
        ]);
    }

    /**
     * Create a network device (router/switch)
     */
    private function createNetworkDevice($client, $company, $faker, $vendors, $locations, $networks, $type)
    {
        $networkMakes = ['Cisco', 'Juniper', 'Aruba', 'Ubiquiti', 'Netgear'];
        $networkModels = [
            'Router' => [
                'Cisco' => ['ISR 4331', 'ISR 4321', 'ASR 1001-X'],
                'Juniper' => ['MX204', 'MX150'],
                'Ubiquiti' => ['Dream Machine Pro', 'EdgeRouter 4']
            ],
            'Switch' => [
                'Cisco' => ['Catalyst 9300', 'Catalyst 2960-X'],
                'Aruba' => ['2930F 48G', '2540 48G'],
                'Ubiquiti' => ['UniFi Switch 48', 'UniFi Switch 24 POE']
            ]
        ];
        
        $make = $faker->randomElement($networkMakes);
        $modelArray = $networkModels[$type][$make] ?? ['Generic ' . $type];
        $model = $faker->randomElement($modelArray);
        
        $purchaseDate = $faker->dateTimeBetween('-6 years', '-6 months');
        $warrantyYears = $faker->randomElement([1, 3, 5]);
        
        Asset::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => $type,
            'name' => strtoupper($client->name_short ?? substr($client->name, 0, 3)) . '-' . strtoupper(substr($type, 0, 3)) . '-' . $faker->unique()->numberBetween(1, 99),
            'description' => $type === 'Router' ? 'Network Router' : 'Network Switch',
            'make' => $make,
            'model' => $model,
            'serial' => strtoupper($faker->bothify('??######')),
            'ip' => $faker->localIpv4(),
            'mac' => $faker->macAddress(),
            'status' => 'Deployed',
            'support_status' => $this->getRandomSupportStatus(),
            'support_level' => $faker->randomElement(['standard', 'premium']),
            'purchase_date' => $purchaseDate,
            'warranty_expire' => Carbon::instance($purchaseDate)->addYears($warrantyYears),
            'install_date' => Carbon::instance($purchaseDate)->addDays($faker->numberBetween(1, 14)),
            'vendor_id' => !empty($vendors) ? $faker->randomElement($vendors) : null,
            'location_id' => !empty($locations) ? $faker->randomElement($locations) : null,
            'network_id' => !empty($networks) ? $faker->randomElement($networks) : null,
            'notes' => $faker->optional(0.2)->sentence(),
        ]);
    }

    /**
     * Create a firewall asset
     */
    private function createFirewall($client, $company, $faker, $vendors, $locations, $networks)
    {
        $firewallMakes = ['Fortinet', 'Palo Alto', 'SonicWall', 'WatchGuard', 'Cisco'];
        $firewallModels = [
            'Fortinet' => ['FortiGate 60F', 'FortiGate 100F', 'FortiGate 200F'],
            'Palo Alto' => ['PA-220', 'PA-850', 'PA-3220'],
            'SonicWall' => ['TZ670', 'NSa 2700', 'NSa 3700'],
            'WatchGuard' => ['Firebox T40', 'Firebox M370'],
            'Cisco' => ['ASA 5506-X', 'Firepower 1010']
        ];
        
        $make = $faker->randomElement($firewallMakes);
        $model = $faker->randomElement($firewallModels[$make] ?? ['Generic Firewall']);
        
        $purchaseDate = $faker->dateTimeBetween('-5 years', '-6 months');
        $warrantyYears = $faker->randomElement([1, 3, 5]);
        
        Asset::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => 'Firewall',
            'name' => strtoupper($client->name_short ?? substr($client->name, 0, 3)) . '-FW-' . $faker->unique()->numberBetween(1, 99),
            'description' => 'Network Firewall',
            'make' => $make,
            'model' => $model,
            'serial' => strtoupper($faker->bothify('??######')),
            'ip' => $faker->localIpv4(),
            'nat_ip' => $faker->ipv4(),
            'mac' => $faker->macAddress(),
            'uri' => 'https://' . $faker->localIpv4(),
            'status' => 'Deployed',
            'support_status' => $faker->randomElement(['supported', 'supported', 'supported', 'pending_assignment']),
            'support_level' => $faker->randomElement(['premium', 'enterprise']),
            'purchase_date' => $purchaseDate,
            'warranty_expire' => Carbon::instance($purchaseDate)->addYears($warrantyYears),
            'install_date' => Carbon::instance($purchaseDate)->addDays($faker->numberBetween(1, 14)),
            'vendor_id' => !empty($vendors) ? $faker->randomElement($vendors) : null,
            'location_id' => !empty($locations) ? $faker->randomElement($locations) : null,
            'network_id' => !empty($networks) ? $faker->randomElement($networks) : null,
            'notes' => $faker->optional(0.3)->sentence(),
        ]);
    }

    /**
     * Create a printer asset
     */
    private function createPrinter($client, $company, $faker, $vendors, $locations, $contacts)
    {
        $printerMakes = ['HP', 'Canon', 'Brother', 'Epson', 'Xerox'];
        $printerModels = [
            'HP' => ['LaserJet Pro M404n', 'Color LaserJet Pro MFP M479fdw', 'OfficeJet Pro 9025e'],
            'Canon' => ['imageCLASS MF644Cdw', 'PIXMA TR8620'],
            'Brother' => ['HL-L3270CDW', 'MFC-L3770CDW'],
            'Epson' => ['WorkForce Pro WF-4830', 'EcoTank ET-4760'],
            'Xerox' => ['VersaLink C405', 'WorkCentre 6515']
        ];
        
        $make = $faker->randomElement($printerMakes);
        $model = $faker->randomElement($printerModels[$make] ?? ['Generic Printer']);
        
        $purchaseDate = $faker->dateTimeBetween('-4 years', 'now');
        $warrantyYears = $faker->randomElement([1, 2]);
        
        Asset::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => 'Printer',
            'name' => strtoupper($client->name_short ?? substr($client->name, 0, 3)) . '-PRN-' . $faker->unique()->numberBetween(1, 99),
            'description' => $faker->randomElement(['Color Laser Printer', 'Multifunction Printer', 'Network Printer']),
            'make' => $make,
            'model' => $model,
            'serial' => strtoupper($faker->bothify('??######')),
            'ip' => $faker->optional(0.8)->localIpv4(),
            'mac' => $faker->optional(0.8)->macAddress(),
            'status' => $faker->randomElement(['Deployed', 'Deployed', 'Deployed', 'Out for Repair']),
            'support_status' => $this->getRandomSupportStatus(),
            'support_level' => $faker->randomElement(['basic', 'standard']),
            'purchase_date' => $purchaseDate,
            'warranty_expire' => Carbon::instance($purchaseDate)->addYears($warrantyYears),
            'install_date' => Carbon::instance($purchaseDate)->addDays($faker->numberBetween(1, 7)),
            'vendor_id' => !empty($vendors) ? $faker->randomElement($vendors) : null,
            'location_id' => !empty($locations) ? $faker->randomElement($locations) : null,
            'contact_id' => !empty($contacts) ? $faker->optional(0.3)->randomElement($contacts) : null,
            'notes' => $faker->optional(0.2)->sentence(),
        ]);
    }

    /**
     * Create other device types (Access Points, Storage, etc.)
     */
    private function createOtherDevice($client, $company, $faker, $vendors, $locations, $contacts, $networks)
    {
        $deviceTypes = ['Access Point', 'Storage', 'Tablet', 'Phone', 'Other'];
        $type = $faker->randomElement($deviceTypes);
        
        $deviceDetails = [
            'Access Point' => [
                'makes' => ['Ubiquiti', 'Cisco', 'Aruba', 'Meraki'],
                'models' => ['UniFi 6 Pro', 'Aironet 2800', 'AP-515', 'MR46'],
                'prefix' => 'AP'
            ],
            'Storage' => [
                'makes' => ['Synology', 'QNAP', 'NetApp', 'Dell EMC'],
                'models' => ['DS920+', 'TS-453D', 'FAS2720', 'Unity XT 380'],
                'prefix' => 'SAN'
            ],
            'Tablet' => [
                'makes' => ['Apple', 'Microsoft', 'Samsung'],
                'models' => ['iPad Pro 12.9"', 'Surface Pro 9', 'Galaxy Tab S8'],
                'prefix' => 'TAB'
            ],
            'Phone' => [
                'makes' => ['Cisco', 'Polycom', 'Yealink'],
                'models' => ['8841', 'VVX 450', 'T54W'],
                'prefix' => 'PHN'
            ],
            'Other' => [
                'makes' => ['Various', 'Generic'],
                'models' => ['Equipment', 'Device'],
                'prefix' => 'OTH'
            ]
        ];
        
        $details = $deviceDetails[$type];
        $make = $faker->randomElement($details['makes']);
        $model = $faker->randomElement($details['models']);
        
        $purchaseDate = $faker->dateTimeBetween('-4 years', 'now');
        $warrantyYears = $faker->randomElement([1, 2, 3]);
        
        Asset::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => $type,
            'name' => strtoupper($client->name_short ?? substr($client->name, 0, 3)) . '-' . $details['prefix'] . '-' . $faker->unique()->numberBetween(1, 99),
            'description' => $type . ' Device',
            'make' => $make,
            'model' => $model,
            'serial' => strtoupper($faker->bothify('??######')),
            'ip' => in_array($type, ['Access Point', 'Storage', 'Phone']) ? $faker->localIpv4() : null,
            'mac' => in_array($type, ['Access Point', 'Storage', 'Phone']) ? $faker->macAddress() : null,
            'status' => $faker->randomElement(['Deployed', 'Deployed', 'Ready To Deploy']),
            'support_status' => $this->getRandomSupportStatus(),
            'support_level' => $faker->randomElement(['basic', 'standard']),
            'purchase_date' => $purchaseDate,
            'warranty_expire' => Carbon::instance($purchaseDate)->addYears($warrantyYears),
            'install_date' => Carbon::instance($purchaseDate)->addDays($faker->numberBetween(1, 7)),
            'vendor_id' => !empty($vendors) ? $faker->randomElement($vendors) : null,
            'location_id' => !empty($locations) ? $faker->randomElement($locations) : null,
            'contact_id' => !empty($contacts) ? $faker->optional(0.5)->randomElement($contacts) : null,
            'network_id' => !empty($networks) && in_array($type, ['Access Point', 'Storage']) ? $faker->randomElement($networks) : null,
            'notes' => $faker->optional(0.2)->sentence(),
        ]);
    }

    /**
     * Get random support status with realistic distribution
     */
    private function getRandomSupportStatus()
    {
        $statuses = [
            'supported' => 70,        // 70% supported
            'unsupported' => 20,       // 20% unsupported  
            'pending_assignment' => 10  // 10% pending
        ];
        
        $rand = rand(1, 100);
        $cumulative = 0;
        
        foreach ($statuses as $status => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $status;
            }
        }
        
        return 'supported';
    }
}