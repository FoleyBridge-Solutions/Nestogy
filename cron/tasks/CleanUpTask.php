<?php

namespace Twetech\Nestogy\Tasks;

use Twetech\Nestogy\Interfaces\TaskInterface;
use PDO;

class CleanUpTask implements TaskInterface
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute(): void
    {
        $this->Announce();
        $this->cleanUpTicketViews();
        $this->cleanUpSharedItems();
    }

    private function Announce(): void
    {
        echo "Running CleanUpTask\n";
    }

    private function cleanUpTicketViews(): void
    {
        $stmt = $this->pdo->prepare("TRUNCATE TABLE ticket_views");
        $stmt->execute();
    }

    private function cleanUpSharedItems(): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM shared_items WHERE item_views = item_view_limit");
        $stmt->execute();
        
        $stmt = $this->pdo->prepare("DELETE FROM shared_items WHERE item_expire_at < NOW()");
        $stmt->execute();
    }

}

