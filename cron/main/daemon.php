<?php

namespace Twetech\Nestogy\Core;

use Twetech\Nestogy\Database;
use Twetech\Nestogy\Tasks\StartupTask;
use Twetech\Nestogy\Tasks\CleanUpTask;
use Twetech\Nestogy\Tasks\NotificationTask;

class Daemon
{
    private $tasks = [];

    public function __construct($config, $cronKey)
    {
        $database = new Database($config['db']);
        $pdo = $database->getConnection();

        $this->tasks[] = new StartupTask($pdo, $cronKey, $config['cron']['enable']);
        $this->tasks[] = new CleanUpTask($pdo);
        $this->tasks[] = new NotificationTask();


    }

    public function run(): void
    {
        foreach ($this->tasks as $task) {
            $task->execute();
        }
    }
}

