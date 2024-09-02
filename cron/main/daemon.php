<?php

namespace Twetech\Nestogy\Core;

use Twetech\Nestogy\Database;
use Twetech\Nestogy\Cron\Tasks\StartupTask;
use Twetech\Nestogy\Cron\Tasks\CleanUpTask;
use Twetech\Nestogy\Cron\Tasks\NotificationTask;
use Twetech\Nestogy\Cron\Tasks\SubscriptionTask;

class Daemon
{
    private $tasks = [];

    public function __construct($config, $cronKey)
    {
        $database = new Database($config['db']);
        $pdo = $database->getConnection();

        $this->tasks[] = new StartupTask($pdo, $cronKey, $config['cron']['enable']);
        $this->tasks[] = new CleanUpTask($pdo);
        
    }

    public function run(): void
    {
        foreach ($this->tasks as $task) {
            $task->execute();
        }
    }
}

