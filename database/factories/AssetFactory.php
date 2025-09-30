<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['desktop', 'laptop', 'server', 'printer', 'network', 'mobile', 'software'];
        $type = $this->faker->randomElement($types);

        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'name' => $this->generateAssetName($type),
            'type' => $type,
            'description' => $this->faker->optional()->sentence(),
            'serial' => strtoupper($this->faker->bothify('??###??###')),
            'make' => $this->getManufacturer($type),
            'model' => $this->getModel($type),
            'status' => $this->faker->randomElement(['active', 'inactive', 'retired', 'repair']),
            'purchase_date' => $this->faker->dateTimeBetween('-5 years', '-6 months'),
            'warranty_expire' => $this->faker->optional()->dateTimeBetween('now', '+3 years'),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Generate asset name based on type.
     */
    private function generateAssetName(string $type): string
    {
        $names = [
            'desktop' => ['Workstation', 'Desktop PC', 'Office Computer'],
            'laptop' => ['Laptop', 'Notebook', 'Mobile Workstation'],
            'server' => ['File Server', 'Domain Controller', 'Application Server', 'Database Server'],
            'printer' => ['Office Printer', 'Network Printer', 'MFP Device'],
            'network' => ['Switch', 'Router', 'Firewall', 'Access Point'],
            'mobile' => ['iPhone', 'iPad', 'Android Phone', 'Tablet'],
            'software' => ['Office Suite', 'Antivirus', 'Backup Software', 'Database License'],
        ];

        return $this->faker->randomElement($names[$type] ?? ['Asset']).' - '.$this->faker->userName;
    }

    /**
     * Get manufacturer based on type.
     */
    private function getManufacturer(string $type): string
    {
        $manufacturers = [
            'desktop' => ['Dell', 'HP', 'Lenovo', 'ASUS'],
            'laptop' => ['Dell', 'HP', 'Lenovo', 'Apple', 'Microsoft'],
            'server' => ['Dell', 'HP', 'IBM', 'Cisco'],
            'printer' => ['HP', 'Canon', 'Epson', 'Brother'],
            'network' => ['Cisco', 'Juniper', 'Aruba', 'Ubiquiti'],
            'mobile' => ['Apple', 'Samsung', 'Google', 'Microsoft'],
            'software' => ['Microsoft', 'Adobe', 'Autodesk', 'Oracle'],
        ];

        return $this->faker->randomElement($manufacturers[$type] ?? ['Generic']);
    }

    /**
     * Get model based on type.
     */
    private function getModel(string $type): string
    {
        $models = [
            'desktop' => ['OptiPlex 7090', 'ProDesk 600', 'ThinkCentre M90', 'VivoPC'],
            'laptop' => ['Latitude 5520', 'EliteBook 850', 'ThinkPad X1', 'MacBook Pro', 'Surface Laptop'],
            'server' => ['PowerEdge R750', 'ProLiant DL380', 'System x3650', 'UCS C240'],
            'printer' => ['LaserJet Pro M404', 'imageRUNNER C5535', 'WorkForce Pro', 'MFC-L8900CDW'],
            'network' => ['Catalyst 9300', 'EX4300', 'Instant On 1960', 'UniFi Dream Machine'],
            'mobile' => ['iPhone 14 Pro', 'Galaxy S23', 'Pixel 7', 'Surface Duo'],
            'software' => ['Office 365', 'Creative Cloud', 'AutoCAD 2023', 'Database 19c'],
        ];

        return $this->faker->randomElement($models[$type] ?? ['Model X']);
    }

    /**
     * Indicate that the asset is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create an asset for a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'company_id' => $client->company_id,
        ]);
    }
}
