<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\Knowledge\Models\KbCategory;
use App\Domains\Knowledge\Models\KbArticle;
use App\Domains\Knowledge\Models\KbArticleView;
use App\Domains\Knowledge\Models\KbArticleFeedback;
use App\Models\User;
use Faker\Factory as Faker;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get first company for seeding
        $companyId = 1;
        
        // Get a sample user for authoring articles
        $author = User::where('company_id', $companyId)->first();
        if (!$author) {
            $this->command->warn('No users found for company ' . $companyId . '. Skipping KB seeder.');
            return;
        }

        $this->command->info('Creating Knowledge Base categories...');

        // Create main categories
        $categories = [
            [
                'name' => 'Network & Infrastructure',
                'description' => 'Network setup, troubleshooting, and infrastructure guides',
                'icon' => 'network-wired',
                'children' => [
                    'Router Configuration',
                    'Switch Setup', 
                    'Firewall Rules',
                    'VPN Setup',
                    'WiFi Troubleshooting'
                ]
            ],
            [
                'name' => 'Software & Applications',
                'description' => 'Software installation, configuration, and troubleshooting',
                'icon' => 'desktop',
                'children' => [
                    'Microsoft Office',
                    'Email Configuration',
                    'Antivirus Solutions',
                    'Remote Desktop',
                    'Software Installation'
                ]
            ],
            [
                'name' => 'Hardware',
                'description' => 'Computer hardware, printers, and device support',
                'icon' => 'microchip',
                'children' => [
                    'Desktop Computers',
                    'Laptops',
                    'Printers & Scanners',
                    'Mobile Devices',
                    'Server Hardware'
                ]
            ],
            [
                'name' => 'Security',
                'description' => 'Cybersecurity, policies, and best practices',
                'icon' => 'shield-alt',
                'children' => [
                    'Password Policies',
                    'Phishing Protection',
                    'Data Backup',
                    'Incident Response',
                    'Compliance'
                ]
            ],
            [
                'name' => 'Cloud Services',
                'description' => 'Cloud platforms, migration, and management',
                'icon' => 'cloud',
                'children' => [
                    'Office 365',
                    'AWS Services',
                    'Google Workspace',
                    'Cloud Storage',
                    'Migration Guides'
                ]
            ]
        ];

        $createdCategories = [];
        
        foreach ($categories as $categoryData) {
            $category = KbCategory::create([
                'company_id' => $companyId,
                'name' => $categoryData['name'],
                'slug' => \Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
                'icon' => $categoryData['icon'],
                'sort_order' => array_search($categoryData, $categories) + 1,
                'is_active' => true,
            ]);

            $createdCategories[] = $category;

            // Create subcategories
            foreach ($categoryData['children'] as $index => $childName) {
                KbCategory::create([
                    'company_id' => $companyId,
                    'parent_id' => $category->id,
                    'name' => $childName,
                    'slug' => \Str::slug($childName),
                    'description' => "Articles related to {$childName}",
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Creating Knowledge Base articles...');

        // Sample articles for each category
        $sampleArticles = [
            // Network & Infrastructure
            [
                'title' => 'How to Configure Router Port Forwarding',
                'content' => $this->getNetworkArticleContent(),
                'excerpt' => 'Step-by-step guide to configure port forwarding on common router models.',
                'tags' => ['router', 'port-forwarding', 'network', 'configuration'],
                'visibility' => 'public',
                'category' => 'Router Configuration'
            ],
            [
                'title' => 'Troubleshooting WiFi Connection Issues',
                'content' => $this->getWifiTroubleshootingContent(),
                'excerpt' => 'Common WiFi problems and their solutions for end users.',
                'tags' => ['wifi', 'troubleshooting', 'connectivity', 'wireless'],
                'visibility' => 'public',
                'category' => 'WiFi Troubleshooting'
            ],

            // Software & Applications
            [
                'title' => 'Setting Up Outlook Email Account',
                'content' => $this->getEmailSetupContent(),
                'excerpt' => 'Complete guide to configure email accounts in Microsoft Outlook.',
                'tags' => ['outlook', 'email', 'setup', 'office365'],
                'visibility' => 'client',
                'category' => 'Email Configuration'
            ],
            [
                'title' => 'Microsoft Office Installation Guide',
                'content' => $this->getOfficeInstallContent(),
                'excerpt' => 'How to install and activate Microsoft Office on various devices.',
                'tags' => ['office', 'installation', 'microsoft', 'activation'],
                'visibility' => 'public',
                'category' => 'Microsoft Office'
            ],

            // Hardware
            [
                'title' => 'Printer Driver Installation and Setup',
                'content' => $this->getPrinterSetupContent(),
                'excerpt' => 'Installing printer drivers and configuring network printers.',
                'tags' => ['printer', 'drivers', 'installation', 'network'],
                'visibility' => 'public',
                'category' => 'Printers & Scanners'
            ],

            // Security
            [
                'title' => 'Creating Strong Passwords: Best Practices',
                'content' => $this->getPasswordPolicyContent(),
                'excerpt' => 'Guidelines for creating and managing secure passwords.',
                'tags' => ['password', 'security', 'best-practices', 'policy'],
                'visibility' => 'public',
                'category' => 'Password Policies'
            ],
            [
                'title' => 'Identifying and Avoiding Phishing Emails',
                'content' => $this->getPhishingContent(),
                'excerpt' => 'How to recognize and protect against phishing attempts.',
                'tags' => ['phishing', 'email', 'security', 'awareness'],
                'visibility' => 'public',
                'category' => 'Phishing Protection'
            ],

            // Cloud Services
            [
                'title' => 'Office 365 Account Setup and Configuration',
                'content' => $this->getOffice365SetupContent(),
                'excerpt' => 'Complete guide to setting up Office 365 for new users.',
                'tags' => ['office365', 'setup', 'cloud', 'microsoft'],
                'visibility' => 'internal',
                'category' => 'Office 365'
            ],
        ];

        foreach ($sampleArticles as $articleData) {
            // Find the category
            $category = KbCategory::where('company_id', $companyId)
                ->where('name', $articleData['category'])
                ->first();

            if (!$category) {
                continue;
            }

            $article = KbArticle::create([
                'company_id' => $companyId,
                'category_id' => $category->id,
                'author_id' => $author->id,
                'title' => $articleData['title'],
                'slug' => \Str::slug($articleData['title']),
                'content' => $articleData['content'],
                'excerpt' => $articleData['excerpt'],
                'status' => KbArticle::STATUS_PUBLISHED,
                'visibility' => $articleData['visibility'],
                'tags' => $articleData['tags'],
                'published_at' => $faker->dateTimeBetween('-3 months', 'now'),
            ]);

            // Generate fake views and feedback
            $viewCount = $faker->numberBetween(10, 500);
            for ($i = 0; $i < $viewCount; $i++) {
                KbArticleView::create([
                    'company_id' => $companyId,
                    'article_id' => $article->id,
                    'viewer_type' => $faker->randomElement(['anonymous', 'user', 'client']),
                    'ip_address' => $faker->ipv4,
                    'user_agent' => $faker->userAgent,
                    'search_query' => $faker->optional(0.3)->words(3, true),
                    'time_spent_seconds' => $faker->numberBetween(30, 600),
                    'led_to_ticket' => $faker->boolean(20), // 20% lead to tickets
                    'viewed_at' => $faker->dateTimeBetween($article->published_at, 'now'),
                ]);
            }

            // Generate feedback
            $feedbackCount = $faker->numberBetween(1, 20);
            for ($i = 0; $i < $feedbackCount; $i++) {
                KbArticleFeedback::create([
                    'company_id' => $companyId,
                    'article_id' => $article->id,
                    'is_helpful' => $faker->boolean(75), // 75% helpful
                    'feedback_text' => $faker->optional(0.3)->sentence(),
                    'ip_address' => $faker->ipv4,
                ]);
            }

            // Update article counters
            $article->update([
                'views_count' => $viewCount,
                'helpful_count' => KbArticleFeedback::where('article_id', $article->id)->where('is_helpful', true)->count(),
                'not_helpful_count' => KbArticleFeedback::where('article_id', $article->id)->where('is_helpful', false)->count(),
            ]);
        }

        $this->command->info('Knowledge Base seeded successfully!');
    }

    private function getNetworkArticleContent(): string
    {
        return <<<HTML
<h2>Overview</h2>
<p>Port forwarding allows external devices to connect to services running on your internal network. This guide covers the most common router configurations.</p>

<h2>Prerequisites</h2>
<ul>
    <li>Administrative access to your router</li>
    <li>Knowledge of the device's internal IP address</li>
    <li>The specific port numbers needed</li>
</ul>

<h2>Step-by-Step Instructions</h2>

<h3>1. Access Router Configuration</h3>
<ol>
    <li>Open your web browser</li>
    <li>Navigate to your router's IP address (usually 192.168.1.1 or 192.168.0.1)</li>
    <li>Login with your administrator credentials</li>
</ol>

<h3>2. Locate Port Forwarding Settings</h3>
<p>Look for one of these menu options:</p>
<ul>
    <li>Port Forwarding</li>
    <li>Virtual Server</li>
    <li>Applications & Gaming</li>
    <li>Advanced > NAT</li>
</ul>

<h3>3. Configure the Rule</h3>
<p>Enter the following information:</p>
<ul>
    <li><strong>Service Name:</strong> Descriptive name for the rule</li>
    <li><strong>External Port:</strong> Port number from outside network</li>
    <li><strong>Internal Port:</strong> Port number on local device</li>
    <li><strong>Internal IP:</strong> IP address of target device</li>
    <li><strong>Protocol:</strong> TCP, UDP, or Both</li>
</ul>

<h2>Common Issues</h2>
<ul>
    <li>Double-check internal IP addresses</li>
    <li>Ensure the target device is running the service</li>
    <li>Check firewall settings on both router and target device</li>
    <li>Some ISPs block certain ports</li>
</ul>

<h2>Security Considerations</h2>
<p><strong>Warning:</strong> Port forwarding can expose your network to security risks. Only forward ports that are absolutely necessary and ensure the target service is properly secured.</p>
HTML;
    }

    private function getWifiTroubleshootingContent(): string
    {
        return <<<HTML
<h2>Common WiFi Issues and Solutions</h2>
<p>This guide helps diagnose and resolve the most common WiFi connectivity problems.</p>

<h2>Issue 1: Can't Connect to WiFi</h2>
<h3>Symptoms:</h3>
<ul>
    <li>WiFi network not appearing in available networks</li>
    <li>"Wrong password" error when connecting</li>
    <li>Connection attempts time out</li>
</ul>

<h3>Solutions:</h3>
<ol>
    <li><strong>Check Password:</strong> Ensure you're using the correct network password</li>
    <li><strong>Restart Device:</strong> Turn WiFi off and on, or restart your device</li>
    <li><strong>Forget and Reconnect:</strong> Remove the network from saved networks and reconnect</li>
    <li><strong>Check Network Visibility:</strong> Ensure the network is broadcasting (SSID visible)</li>
</ol>

<h2>Issue 2: Slow WiFi Speeds</h2>
<h3>Solutions:</h3>
<ol>
    <li><strong>Check Signal Strength:</strong> Move closer to the router</li>
    <li><strong>Restart Router:</strong> Unplug for 30 seconds, then reconnect</li>
    <li><strong>Check for Interference:</strong> Move away from microwaves, baby monitors</li>
    <li><strong>Update Drivers:</strong> Ensure WiFi adapter drivers are current</li>
    <li><strong>Switch Channels:</strong> Change router channel (1, 6, or 11 for 2.4GHz)</li>
</ol>

<h2>Issue 3: Frequent Disconnections</h2>
<h3>Solutions:</h3>
<ol>
    <li><strong>Power Management:</strong> Disable WiFi adapter power saving</li>
    <li><strong>Update Drivers:</strong> Install latest network adapter drivers</li>
    <li><strong>Router Firmware:</strong> Update router firmware</li>
    <li><strong>Channel Width:</strong> Try different channel widths (20MHz vs 40MHz)</li>
</ol>

<h2>Advanced Troubleshooting</h2>
<h3>Network Reset (Windows):</h3>
<ol>
    <li>Open Command Prompt as Administrator</li>
    <li>Run: <code>netsh winsock reset</code></li>
    <li>Run: <code>netsh int ip reset</code></li>
    <li>Restart computer</li>
</ol>

<h3>Check Network Status:</h3>
<p>Use these commands to diagnose:</p>
<ul>
    <li><code>ipconfig /all</code> - View network configuration</li>
    <li><code>ping google.com</code> - Test internet connectivity</li>
    <li><code>nslookup google.com</code> - Test DNS resolution</li>
</ul>
HTML;
    }

    private function getEmailSetupContent(): string
    {
        return <<<HTML
<h2>Setting Up Email in Microsoft Outlook</h2>
<p>This guide walks you through configuring your email account in Outlook 2016, 2019, and Office 365.</p>

<h2>Automatic Setup (Recommended)</h2>
<ol>
    <li>Open Microsoft Outlook</li>
    <li>Click <strong>File</strong> > <strong>Add Account</strong></li>
    <li>Enter your email address</li>
    <li>Click <strong>Connect</strong></li>
    <li>Enter your password when prompted</li>
    <li>Click <strong>OK</strong> when setup is complete</li>
</ol>

<h2>Manual Setup</h2>
<p>If automatic setup fails, use these manual settings:</p>

<h3>IMAP Settings (Recommended):</h3>
<ul>
    <li><strong>Incoming Server:</strong> imap.gmail.com (for Gmail)</li>
    <li><strong>Port:</strong> 993</li>
    <li><strong>Encryption:</strong> SSL/TLS</li>
    <li><strong>Outgoing Server:</strong> smtp.gmail.com</li>
    <li><strong>Port:</strong> 587</li>
    <li><strong>Encryption:</strong> STARTTLS</li>
</ul>

<h3>Exchange/Office 365:</h3>
<ul>
    <li><strong>Server:</strong> outlook.office365.com</li>
    <li><strong>Port:</strong> 443</li>
    <li><strong>Encryption:</strong> SSL</li>
</ul>

<h2>Troubleshooting</h2>
<h3>Authentication Issues:</h3>
<ul>
    <li>Enable "Less secure app access" (Gmail)</li>
    <li>Use App Passwords for 2FA accounts</li>
    <li>Check with IT for Exchange server settings</li>
</ul>

<h3>Sending Issues:</h3>
<ul>
    <li>Verify SMTP authentication is enabled</li>
    <li>Check outgoing server port and encryption</li>
    <li>Some ISPs block port 25</li>
</ul>

<h2>Additional Configuration</h2>
<h3>Signature Setup:</h3>
<ol>
    <li>Go to <strong>File</strong> > <strong>Options</strong> > <strong>Mail</strong></li>
    <li>Click <strong>Signatures</strong></li>
    <li>Create and format your signature</li>
    <li>Set default signatures for new and reply messages</li>
</ol>

<h3>Auto-Reply (Out of Office):</h3>
<ol>
    <li>Click <strong>File</strong> > <strong>Automatic Replies</strong></li>
    <li>Select <strong>Send automatic replies</strong></li>
    <li>Set date range and compose messages</li>
    <li>Configure separate messages for internal and external senders</li>
</ol>
HTML;
    }

    private function getOfficeInstallContent(): string
    {
        return <<<HTML
<h2>Microsoft Office Installation Guide</h2>
<p>Complete instructions for installing Microsoft Office on Windows and Mac computers.</p>

<h2>System Requirements</h2>
<h3>Windows:</h3>
<ul>
    <li>Windows 10 or Windows 11</li>
    <li>4 GB RAM minimum</li>
    <li>4 GB available disk space</li>
    <li>1280 x 768 screen resolution</li>
</ul>

<h3>Mac:</h3>
<ul>
    <li>macOS 10.15 or later</li>
    <li>4 GB RAM minimum</li>
    <li>10 GB available disk space</li>
</ul>

<h2>Installation Steps</h2>

<h3>Method 1: Microsoft Account Portal</h3>
<ol>
    <li>Go to <a href="https://account.microsoft.com">account.microsoft.com</a></li>
    <li>Sign in with your Microsoft account</li>
    <li>Click <strong>Install Office</strong></li>
    <li>Click <strong>Office 365 apps</strong></li>
    <li>Run the downloaded installer</li>
    <li>Follow the installation wizard</li>
</ol>

<h3>Method 2: Office.com</h3>
<ol>
    <li>Visit <a href="https://office.com">office.com</a></li>
    <li>Sign in to your account</li>
    <li>Click <strong>Install Office</strong> in the top right</li>
    <li>Select <strong>Office 365 apps</strong></li>
    <li>Save and run the installer</li>
</ol>

<h2>Activation</h2>
<p>Office should activate automatically when you sign in. If not:</p>
<ol>
    <li>Open any Office application</li>
    <li>Click <strong>File</strong> > <strong>Account</strong></li>
    <li>Click <strong>Sign In</strong></li>
    <li>Enter your Microsoft account credentials</li>
</ol>

<h2>Troubleshooting</h2>
<h3>Installation Fails:</h3>
<ul>
    <li>Use the Office Support and Recovery Assistant</li>
    <li>Disable antivirus temporarily during installation</li>
    <li>Ensure Windows is up to date</li>
    <li>Run installer as Administrator</li>
</ul>

<h3>Activation Issues:</h3>
<ul>
    <li>Check internet connection</li>
    <li>Verify correct Microsoft account</li>
    <li>Contact IT for volume license keys</li>
    <li>Use phone activation if internet activation fails</li>
</ul>

<h2>Multiple Installations</h2>
<p>Office 365 allows installation on:</p>
<ul>
    <li>5 PCs or Macs</li>
    <li>5 tablets</li>
    <li>5 smartphones</li>
</ul>

<p>To manage installations:</p>
<ol>
    <li>Go to <strong>account.microsoft.com</strong></li>
    <li>Click <strong>Services & subscriptions</strong></li>
    <li>Find your Office subscription</li>
    <li>Click <strong>Install</strong> to see device list</li>
</ol>
HTML;
    }

    private function getPrinterSetupContent(): string
    {
        return <<<HTML
<h2>Printer Setup and Driver Installation</h2>
<p>Step-by-step guide for installing printers and resolving common driver issues.</p>

<h2>Automatic Installation (Windows 10/11)</h2>
<ol>
    <li>Connect printer via USB or ensure it's on the same network</li>
    <li>Go to <strong>Settings</strong> > <strong>Devices</strong> > <strong>Printers & scanners</strong></li>
    <li>Click <strong>Add a printer or scanner</strong></li>
    <li>Select your printer from the list</li>
    <li>Follow the setup wizard</li>
</ol>

<h2>Manual Driver Installation</h2>
<h3>Download from Manufacturer:</h3>
<ol>
    <li>Visit the printer manufacturer's website</li>
    <li>Navigate to support/downloads section</li>
    <li>Search for your printer model</li>
    <li>Download the latest driver for your OS</li>
    <li>Run the installer as Administrator</li>
</ol>

<h3>Using Device Manager:</h3>
<ol>
    <li>Right-click <strong>Start</strong> > <strong>Device Manager</strong></li>
    <li>Find your printer under <strong>Print queues</strong> or <strong>Other devices</strong></li>
    <li>Right-click > <strong>Update driver</strong></li>
    <li>Choose <strong>Search automatically for drivers</strong></li>
</ol>

<h2>Network Printer Setup</h2>
<h3>By IP Address:</h3>
<ol>
    <li>Go to <strong>Control Panel</strong> > <strong>Devices and Printers</strong></li>
    <li>Click <strong>Add a printer</strong></li>
    <li>Select <strong>Add a local printer</strong></li>
    <li>Choose <strong>Create a new port</strong> > <strong>Standard TCP/IP Port</strong></li>
    <li>Enter the printer's IP address</li>
    <li>Install appropriate drivers</li>
</ol>

<h3>Shared Network Printer:</h3>
<ol>
    <li>Open <strong>Run</strong> dialog (Win + R)</li>
    <li>Type: <code>\\computername\printername</code></li>
    <li>Or browse: <strong>Network</strong> > <strong>Computer</strong> > <strong>Printers</strong></li>
    <li>Double-click to install</li>
</ol>

<h2>Common Issues</h2>
<h3>Driver Not Found:</h3>
<ul>
    <li>Try Windows Update for generic drivers</li>
    <li>Use manufacturer's universal driver</li>
    <li>For old printers, try compatibility mode</li>
    <li>Contact IT for enterprise printer drivers</li>
</ul>

<h3>Print Queue Stuck:</h3>
<ol>
    <li>Open <strong>Services</strong> (services.msc)</li>
    <li>Stop <strong>Print Spooler</strong> service</li>
    <li>Delete files in C:\Windows\System32\spool\PRINTERS\</li>
    <li>Start <strong>Print Spooler</strong> service</li>
</ol>

<h3>Network Printer Offline:</h3>
<ul>
    <li>Check printer network connection</li>
    <li>Verify IP address hasn't changed</li>
    <li>Ping the printer IP address</li>
    <li>Restart print spooler service</li>
    <li>Remove and re-add the printer</li>
</ul>

<h2>Testing and Maintenance</h2>
<ul>
    <li>Print a test page to verify installation</li>
    <li>Configure default print settings</li>
    <li>Set up print monitoring alerts</li>
    <li>Schedule regular driver updates</li>
</ul>
HTML;
    }

    private function getPasswordPolicyContent(): string
    {
        return <<<HTML
<h2>Creating Strong Passwords: Best Practices</h2>
<p>Learn how to create and manage secure passwords to protect your accounts and data.</p>

<h2>Password Requirements</h2>
<h3>Minimum Standards:</h3>
<ul>
    <li><strong>Length:</strong> At least 12 characters (longer is better)</li>
    <li><strong>Complexity:</strong> Mix of uppercase, lowercase, numbers, and symbols</li>
    <li><strong>Uniqueness:</strong> Different password for each account</li>
    <li><strong>Unpredictability:</strong> Avoid personal information and common patterns</li>
</ul>

<h2>Password Creation Strategies</h2>

<h3>1. Passphrase Method:</h3>
<p>Use a memorable phrase with modifications:</p>
<ul>
    <li>Base phrase: "My coffee shop opens at 7am"</li>
    <li>Modified: "MyC0ff33Sh0p_0p3ns@7am!"</li>
</ul>

<h3>2. Acronym Method:</h3>
<p>Create acronym from memorable sentence:</p>
<ul>
    <li>Sentence: "I went to New York in 2019 and it was amazing!"</li>
    <li>Password: "IwtNYi2019aiwa!"</li>
</ul>

<h3>3. Random Generation:</h3>
<p>Use password managers to generate truly random passwords:</p>
<ul>
    <li>Example: "Kp9#mL$nQ2wE*vR7"</li>
    <li>Store in password manager</li>
    <li>Use unique password for each account</li>
</ul>

<h2>What to Avoid</h2>
<h3>Never Use:</h3>
<ul>
    <li>Personal information (birthdate, SSN, names)</li>
    <li>Dictionary words</li>
    <li>Common patterns (123456, password, qwerty)</li>
    <li>Previous passwords with minor changes</li>
    <li>Same password across multiple accounts</li>
</ul>

<h3>Weak Examples:</h3>
<ul>
    <li>password123</li>
    <li>john1985</li>
    <li>companyname2023</li>
    <li>admin</li>
</ul>

<h2>Password Management</h2>
<h3>Password Managers (Recommended):</h3>
<ul>
    <li><strong>LastPass:</strong> Cross-platform, business plans available</li>
    <li><strong>Bitwarden:</strong> Open source, free tier available</li>
    <li><strong>1Password:</strong> Family and business plans</li>
    <li><strong>Dashlane:</strong> User-friendly interface</li>
</ul>

<h3>Benefits of Password Managers:</h3>
<ul>
    <li>Generate strong, unique passwords</li>
    <li>Automatically fill login forms</li>
    <li>Sync across all devices</li>
    <li>Secure password sharing</li>
    <li>Breach monitoring</li>
</ul>

<h2>Two-Factor Authentication (2FA)</h2>
<p>Enable 2FA whenever possible:</p>
<ul>
    <li><strong>SMS:</strong> Text message codes (least secure)</li>
    <li><strong>App-based:</strong> Google Authenticator, Authy (more secure)</li>
    <li><strong>Hardware keys:</strong> YubiKey, Titan Security Key (most secure)</li>
</ul>

<h2>Regular Maintenance</h2>
<ul>
    <li>Change passwords if service reports a breach</li>
    <li>Review and update passwords annually</li>
    <li>Remove unused accounts</li>
    <li>Monitor for suspicious login attempts</li>
    <li>Keep recovery information updated</li>
</ul>

<h2>Company Policy</h2>
<ul>
    <li>Never share passwords with colleagues</li>
    <li>Don't write passwords on sticky notes</li>
    <li>Use company-approved password managers</li>
    <li>Report suspected password compromises immediately</li>
    <li>Follow company guidelines for password complexity</li>
</ul>
HTML;
    }

    private function getPhishingContent(): string
    {
        return <<<HTML
<h2>Identifying and Avoiding Phishing Emails</h2>
<p>Learn to recognize phishing attempts and protect yourself from email-based attacks.</p>

<h2>What is Phishing?</h2>
<p>Phishing is a cyber attack where criminals impersonate legitimate organizations to steal sensitive information like passwords, credit card numbers, or personal data.</p>

<h2>Common Phishing Indicators</h2>

<h3>Sender Red Flags:</h3>
<ul>
    <li><strong>Suspicious email addresses:</strong> amaz0n.com instead of amazon.com</li>
    <li><strong>Generic greetings:</strong> "Dear Customer" instead of your name</li>
    <li><strong>Urgent or threatening language:</strong> "Account will be closed!"</li>
    <li><strong>Unexpected attachments</strong> or links</li>
</ul>

<h3>Content Warning Signs:</h3>
<ul>
    <li>Poor grammar and spelling errors</li>
    <li>Mismatched logos or branding</li>
    <li>Requests for sensitive information via email</li>
    <li>"Act now" or time-pressure tactics</li>
    <li>Too-good-to-be-true offers</li>
</ul>

<h3>Technical Indicators:</h3>
<ul>
    <li><strong>Hover over links</strong> - URL doesn't match the claimed destination</li>
    <li><strong>Shortened URLs</strong> that hide the real destination</li>
    <li><strong>Suspicious attachments</strong> (.exe, .zip, .scr files)</li>
    <li><strong>Unexpected file types</strong> (.pdf.exe, .doc.scr)</li>
</ul>

<h2>Common Phishing Scenarios</h2>

<h3>1. Bank/Financial Phishing:</h3>
<blockquote>
"Your account has been compromised. Click here to verify your identity immediately or your account will be locked."
</blockquote>
<p><strong>Reality:</strong> Banks never ask for credentials via email.</p>

<h3>2. Tech Support Scams:</h3>
<blockquote>
"We've detected suspicious activity on your computer. Download this tool to remove malware."
</blockquote>
<p><strong>Reality:</strong> Legitimate companies don't send unsolicited security alerts.</p>

<h3>3. Package Delivery:</h3>
<blockquote>
"Your package delivery failed. Click to reschedule delivery and provide updated payment information."
</blockquote>
<p><strong>Reality:</strong> Check tracking directly on the carrier's website.</p>

<h3>4. COVID-19/Current Events:</h3>
<blockquote>
"Get your free COVID test kit by providing your personal information."
</blockquote>
<p><strong>Reality:</strong> Scammers exploit current events and fears.</p>

<h2>How to Verify Suspicious Emails</h2>
<ol>
    <li><strong>Don't click anything</strong> in the suspicious email</li>
    <li><strong>Check the sender's email address</strong> carefully</li>
    <li><strong>Go directly to the company's website</strong> in a new browser tab</li>
    <li><strong>Contact the company</strong> using official phone numbers</li>
    <li><strong>Check your account</strong> by logging in directly (not through email links)</li>
</ol>

<h2>Safe Email Practices</h2>

<h3>Before Clicking:</h3>
<ul>
    <li>Hover over links to see the real URL</li>
    <li>Look for HTTPS and correct domain names</li>
    <li>Be suspicious of shortened URLs</li>
    <li>Don't download unexpected attachments</li>
</ul>

<h3>If You're Unsure:</h3>
<ul>
    <li>Forward suspicious emails to IT security</li>
    <li>Call the sender using a known phone number</li>
    <li>Ask a colleague for a second opinion</li>
    <li>When in doubt, don't click</li>
</ul>

<h2>What to Do If You've Been Phished</h2>
<ol>
    <li><strong>Don't panic</strong> - quick action can limit damage</li>
    <li><strong>Change passwords immediately</strong> for affected accounts</li>
    <li><strong>Contact IT security</strong> and report the incident</li>
    <li><strong>Monitor accounts</strong> for suspicious activity</li>
    <li><strong>Run antivirus scan</strong> if you downloaded anything</li>
    <li><strong>Report to relevant authorities</strong> (FTC, FBI IC3)</li>
</ol>

<h2>Reporting Phishing</h2>
<ul>
    <li><strong>Forward phishing emails to:</strong> phishing@company.com</li>
    <li><strong>Report to Anti-Phishing Working Group:</strong> reportphishing@apwg.org</li>
    <li><strong>FTC:</strong> consumer.ftc.gov</li>
    <li><strong>Microsoft:</strong> junk@office365.microsoft.com</li>
    <li><strong>Google:</strong> phishing@gmail.com</li>
</ul>

<h2>Advanced Protection</h2>
<ul>
    <li><strong>Email filtering:</strong> Use advanced threat protection</li>
    <li><strong>Two-factor authentication:</strong> Enable on all accounts</li>
    <li><strong>Regular training:</strong> Stay updated on new phishing techniques</li>
    <li><strong>Password manager:</strong> Prevents credential entry on fake sites</li>
    <li><strong>Keep software updated:</strong> Patches prevent exploitation</li>
</ul>
HTML;
    }

    private function getOffice365SetupContent(): string
    {
        return <<<HTML
<h2>Office 365 Account Setup and Configuration</h2>
<p>Complete guide for IT administrators to set up and configure Office 365 for new users.</p>

<h2>Prerequisites</h2>
<ul>
    <li>Office 365 admin credentials</li>
    <li>User's basic information (name, email, department)</li>
    <li>License availability</li>
    <li>Security group assignments</li>
</ul>

<h2>Step 1: Create User Account</h2>
<ol>
    <li>Sign in to <strong>admin.microsoft.com</strong></li>
    <li>Go to <strong>Users</strong> > <strong>Active users</strong></li>
    <li>Click <strong>Add a user</strong></li>
    <li>Fill in basic information:
        <ul>
            <li>First and last name</li>
            <li>Display name</li>
            <li>Username (email prefix)</li>
            <li>Domain selection</li>
        </ul>
    </li>
    <li>Set password options:
        <ul>
            <li>Auto-generated password</li>
            <li>Custom password</li>
            <li>Require password change on first sign-in</li>
        </ul>
    </li>
</ol>

<h2>Step 2: Assign Licenses</h2>
<ol>
    <li>In the user creation wizard, click <strong>Next</strong></li>
    <li>Select appropriate licenses:
        <ul>
            <li><strong>Office 365 Business Premium</strong></li>
            <li><strong>Microsoft 365 E3/E5</strong></li>
            <li><strong>Exchange Online</strong></li>
            <li><strong>Teams</strong></li>
        </ul>
    </li>
    <li>Configure service plans (optional)</li>
    <li>Click <strong>Next</strong></li>
</ol>

<h2>Step 3: Optional Settings</h2>
<h3>Profile Information:</h3>
<ul>
    <li>Job title and department</li>
    <li>Manager assignment</li>
    <li>Contact information</li>
    <li>Office location</li>
</ul>

<h3>Security Groups:</h3>
<ul>
    <li>Add to relevant security groups</li>
    <li>Department-based groups</li>
    <li>Role-based access groups</li>
    <li>Distribution lists</li>
</ul>

<h2>Step 4: Email Configuration</h2>
<h3>Exchange Online Setup:</h3>
<ol>
    <li>Go to <strong>Exchange admin center</strong></li>
    <li>Verify mailbox creation</li>
    <li>Set mailbox size limits</li>
    <li>Configure retention policies</li>
    <li>Set up email forwarding (if needed)</li>
</ol>

<h3>Email Client Configuration:</h3>
<ul>
    <li><strong>Server:</strong> outlook.office365.com</li>
    <li><strong>Port:</strong> 443 (HTTPS) or 993 (IMAP)</li>
    <li><strong>Authentication:</strong> Modern Authentication</li>
    <li><strong>Autodiscover:</strong> Enabled</li>
</ul>

<h2>Step 5: Teams Setup</h2>
<ol>
    <li>Go to <strong>Teams admin center</strong></li>
    <li>Add user to appropriate teams</li>
    <li>Configure calling policies</li>
    <li>Set meeting policies</li>
    <li>Configure app permissions</li>
</ol>

<h2>Step 6: SharePoint and OneDrive</h2>
<ol>
    <li>Verify OneDrive provisioning</li>
    <li>Set storage quotas</li>
    <li>Configure sharing policies</li>
    <li>Add to SharePoint sites</li>
    <li>Set permissions levels</li>
</ol>

<h2>Step 7: Security Configuration</h2>
<h3>Multi-Factor Authentication:</h3>
<ol>
    <li>Go to <strong>Azure AD admin center</strong></li>
    <li>Select <strong>Users</strong> > <strong>Multi-Factor Authentication</strong></li>
    <li>Enable MFA for the user</li>
    <li>Configure allowed methods</li>
</ol>

<h3>Conditional Access:</h3>
<ul>
    <li>Apply location-based policies</li>
    <li>Device compliance requirements</li>
    <li>App protection policies</li>
    <li>Sign-in risk policies</li>
</ul>

<h2>Step 8: Application Access</h2>
<h3>Office Applications:</h3>
<ul>
    <li>Word, Excel, PowerPoint Online</li>
    <li>Desktop application downloads</li>
    <li>Mobile app configuration</li>
    <li>Version management</li>
</ul>

<h3>Third-party Integrations:</h3>
<ul>
    <li>CRM system connections</li>
    <li>ERP integrations</li>
    <li>Custom applications</li>
    <li>API permissions</li>
</ul>

<h2>Step 9: User Communication</h2>
<h3>Welcome Email Template:</h3>
<blockquote>
Subject: Welcome to Office 365<br><br>
Dear [Name],<br><br>
Your Office 365 account has been created. Here are your login details:<br><br>
Email: [email@company.com]<br>
Temporary Password: [password]<br>
Login URL: https://portal.office.com<br><br>
You will be required to change your password on first login.<br><br>
Next steps:<br>
1. Download Office apps<br>
2. Set up mobile devices<br>
3. Complete security setup<br><br>
For support, contact IT at [contact info]
</blockquote>

<h2>Step 10: Validation and Testing</h2>
<ol>
    <li><strong>Login Test:</strong> Verify user can sign in</li>
    <li><strong>Email Test:</strong> Send and receive emails</li>
    <li><strong>Teams Test:</strong> Join meetings and chat</li>
    <li><strong>File Access:</strong> OneDrive and SharePoint access</li>
    <li><strong>App Installation:</strong> Download and activate Office</li>
</ol>

<h2>Common Issues and Solutions</h2>
<h3>Login Problems:</h3>
<ul>
    <li>Check license assignment</li>
    <li>Verify password requirements</li>
    <li>Clear browser cache</li>
    <li>Check conditional access policies</li>
</ul>

<h3>Email Issues:</h3>
<ul>
    <li>Wait for mailbox provisioning (up to 24 hours)</li>
    <li>Check MX records</li>
    <li>Verify autodiscover settings</li>
    <li>Test with OWA first</li>
</ul>

<h2>Automation Options</h2>
<h3>PowerShell Scripts:</h3>
<ul>
    <li>Bulk user creation</li>
    <li>License assignment automation</li>
    <li>Group membership management</li>
    <li>Reporting and auditing</li>
</ul>

<h3>Azure AD Connect:</h3>
<ul>
    <li>On-premises AD synchronization</li>
    <li>Password hash sync</li>
    <li>Single sign-on</li>
    <li>Hybrid identity management</li>
</ul>
HTML;
    }
}