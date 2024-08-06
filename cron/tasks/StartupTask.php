<?php

namespace Twetech\Nestogy\Tasks;

use Twetech\Nestogy\Interfaces\TaskInterface;
use PDO;

class StartupTask implements TaskInterface
{
    private $pdo;
    private $cronKey;
    private $enableCron;

    public function __construct(PDO $pdo, string $cronKey, int $enableCron)
    {
        $this->pdo = $pdo;
        $this->cronKey = $cronKey;
        $this->enableCron = $enableCron;
    }

    public function execute(): void
    {
        $this->checkCronEnabled();
        $this->checkCronKey();
        $this->logCronStart();
        $this->Announce();
    }

    private function Announce(): void
    {
        echo "Running StartupTask\n";
    }

    private function checkCronEnabled(): void
    {
        if ($this->enableCron === 0) {
            exit("Cron: is not enabled -- Quitting..");
        }
    }

    private function checkCronKey(): void
    {
        global $argv; // Use global to access command-line arguments
        if (count($argv) < 2 || $argv[1] !== $this->cronKey) {
            exit("Cron Key invalid -- Quitting..");
        }
    }

    private function logCronStart(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO logs SET log_type = 'Cron', log_action = 'Started', log_description = 'Cron Started'");
        $stmt->execute();
    }
}

