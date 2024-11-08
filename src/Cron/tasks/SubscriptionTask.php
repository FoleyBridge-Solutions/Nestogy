<?php

namespace Twetech\Nestogy\Cron\Tasks;


use Twetech\Nestogy\Interfaces\TaskInterface;
use Twetech\Nestogy\Controller\SubscriptionsController;
use PDO;

// This class represents a single client's subscription
// and contains all the variables and functions related to the subscription.
class Subscription
{
    private $clientId;
    private $pdo;
    private $invoiceId;

    public function __construct(PDO $pdo, int $clientId)
    {
        $this->pdo = $pdo;
        $this->clientId = $clientId;
    }

    public function checkLastInvoice(): bool
    {
        // Logic to check when the last invoice was sent for this client.
        $query = "SELECT invoice_date FROM invoices WHERE client_id = :client_id ORDER BY invoice_date DESC LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['client_id' => $this->clientId]);
        $lastInvoice = $stmt->fetchColumn();

        if (!$lastInvoice) {
            // No invoice found, assume first invoice
            return true;
        }

        $lastInvoiceDate = new \DateTime($lastInvoice);
        $now = new \DateTime();

        // Check if a month has passed since the last invoice
        return $now->format('Y-m') !== $lastInvoiceDate->format('Y-m');
    }

    public function createInvoice(): void
    {
        // Logic to create a new invoice for this client
        $query = "INSERT INTO invoices (client_id, invoice_date) VALUES (:client_id, NOW())";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['client_id' => $this->clientId]);
        $this->invoiceId = $this->pdo->lastInsertId();
    }

    public function createInvoiceItems(): void
    {
        // Go though the subscription items and create corresponding invoice items from products
        $query = "SELECT subscription_product_id, subbscription_product_quantity FROM subscriptions WHERE subscription_client_id = :client_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['client_id' => $this->clientId]);
        $subscriptions = $stmt->fetchAll();
        foreach ($subscriptions as $subscription) {
            $query = "INSERT INTO invoice_items (invoice_id, product_id, quantity) VALUES (:invoice_id, :product_id, :quantity)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                'invoice_id' => $this->invoiceId,
                'product_id' => $subscription['product_id'],
                'quantity' => $subscription['quantity']
            ]);
        }
    }

    public function sendNotification(): void
    {
        // Logic to send the invoice to the user via email or other notification methods
        echo "Sending invoice notification to client {$this->clientId}\n";
    }
}

// This class is responsible for running the SubscriptionTask,
// which checks when the last invoice was sent to billing users,
// and sends a new invoice if needed.
class SubscriptionTask implements TaskInterface
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute(): void
    {
        $this->announce();

        // Fetch all clients that need to be billed
        $clients = $this->getClients();

        foreach ($clients as $clientId) {

            $subscription = new Subscription($this->pdo, $clientId);
            
            if ($subscription->checkLastInvoice()) {
                $subscription->createInvoice();
                $subscription->createInvoiceItems();
                $subscription->sendNotification();
            }
        }
    }

    private function announce(): void
    {
        echo "Running SubscriptionTask\n";
    }

    private function getClients(): array
    {
        // Fetch all client IDs from the database
        $query = "SELECT id FROM clients";
        $stmt = $this->pdo->query($query);

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}