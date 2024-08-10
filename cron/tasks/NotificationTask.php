<?php

namespace Twetech\Nestogy\Cron\Tasks;

use Twetech\Nestogy\Interfaces\TaskInterface;
use PDO;

class NotificationTask implements TaskInterface
{

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function execute(): void
    {
        $this->announce();
        $this->ticketNotification();
        $this->domainNotification();
        $this->certificateNotification();
        $this->warrantyNotification();
        $this->licenseNotification();
        $this->subscriptionNotification();
        $this->maintenanceNotification();
    }

    private function addEmailToQueue($recipient, $sender, $subject, $message, $recipient_name = null, $sender_name = null, $email_cal_string = null): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO email_queue
                SET email_recipient = :recipient,
                email_recipient_name = :recipient_name,
                email_sender = :sender,
                email_sender_name = :sender_name,
                email_subject = :subject,
                email_message = :message,
                email_cal_string = :email_cal_string
            "
        );
        $stmt->execute([
            'recipient' => $recipient,
            'recipient_name' => $recipient_name,
            'sender' => $sender,
            'sender_name' => $sender_name,
            'subject' => $subject,
            'message' => $message,
            'email_cal_string' => $email_cal_string
        ]);
    }

    private function addNotificationToQueue($type, $message, $action = null, $client_id = null, $user_id = null, $entity_id = null): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications
                SET notification_type = :type,
                notification_message = :message,
                notification_action = :action,
                notification_client_id = :client_id,
                notification_user_id = :user_id,
                notification_entity_id = :entity_id"
        );
        $stmt->execute([
            'type' => $type,
            'message' => $message,
            'action' => $action,
            'client_id' => $client_id,
            'user_id' => $user_id,
            'entity_id' => $entity_id
        ]);
    }

    private function announce(): void
    {
        echo "Running NotificationTask\n";
    }

    private function ticketNotification(): void
    {
        echo "Running ticketNotification\n";
    } 

    private function domainNotification(): void
    {
        echo "Running domainNotification\n";
    }

    private function certificateNotification(): void
    {
        echo "Running certificateNotification\n";
    }

    private function warrantyNotification(): void
    {
        echo "Running warrantyNotification\n";

        $stmt = $this->pdo->prepare("SELECT * FROM warranties WHERE warranty_expire_at < NOW()");
        $stmt->execute();

        $warranties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function licenseNotification(): void
    {
        echo "Running licenseNotification\n";
    }

    private function subscriptionNotification(): void
    {
        echo "Running subscriptionNotification\n";
    }

    private function maintenanceNotification(): void
    {
        echo "Running maintenanceNotification\n";
    }
}
