<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once "/var/www/portal.twe.tech/includes/config/config.php";
require_once "/var/www/portal.twe.tech/includes/functions/functions.php";

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$start_time = microtime(true);

function checkTime() {
    global $start_time;
    if (microtime(true) - $start_time > 59) {
        echo("Time limit exceeded mid-function". PHP_EOL);
        exit;
    }
}

function markNotificationsAsSent($sentNotifications) {
    global $mysqli;
    foreach ($sentNotifications as $notification_id) {
        mysqli_query($mysqli, "UPDATE notifications SET notification_sent = 1 WHERE notification_id = $notification_id");
    }
}

function sendNotifications() {
    global $mysqli;

    $vapid = [
        'subject' => 'mailto:andrew.m@twe.tech',
        'publicKey' => 'BJD7CGPuqeEF4_OxS33pioe2JMEJn6BAtrjy33GtiUBeM3QS0Qq88S22k8X85SFZaFvSGXG0LbqYovAalZ2-F2I',
        'privateKey' => 'exmY_xwChwXdQeg9LaDq8WLUtaJr7fUR7tO0P9sQ2Ig',
    ];

    $notificationsSql = "SELECT * FROM notifications WHERE notification_sent = 0 AND notification_is_webpush = 1";
    $notificationsResult = mysqli_query($mysqli, $notificationsSql);
    if (!$notificationsResult) {
        echo("Error fetching notifications: " . mysqli_error($mysqli) . PHP_EOL);
        exit;
    }

    $sentNotifications = [];
    $notifications = [];
    while ($row = mysqli_fetch_assoc($notificationsResult)) {
        $user_id = $row['notification_user_id'] ?? 0;
        $notification_id = $row['notification_id'];
        $notification_action = $row['notification_action'];
        $sentNotifications[] = $notification_id;
        $notification_payload = json_encode([
            'title' => $row['notification_type'],
            'body' => $row['notification'],
            'url' => "https://portal.twe.tech/".$notification_action
        ]);

        if ($user_id == 0) {
            $subscriptionsSql = "SELECT * FROM notification_subscriptions";
        } else {
            $subscriptionsSql = "SELECT * FROM notification_subscriptions WHERE notification_subscription_user_id = $user_id";
        }
        echo("Subscriptions SQL: " . $subscriptionsSql);
        $subscriptionsResult = mysqli_query($mysqli, $subscriptionsSql);
        if (!$subscriptionsResult) {
            echo("Error fetching subscriptions for user $user_id: " . mysqli_error($mysqli) . PHP_EOL);
            continue;
        }
        while ($row = mysqli_fetch_assoc($subscriptionsResult)) {
            $subscriptionData = [
                'endpoint' => $row['notification_subscription_endpoint'],
                'keys' => [
                    'p256dh' => $row['notification_subscription_public_key'],
                    'auth' => $row['notification_subscription_auth_key']
                ]
            ];

            try {
                $subscription = Subscription::create($subscriptionData);
                if (empty($subscription->getEndpoint()) || empty($subscription->getPublicKey()) || empty($subscription->getAuthToken())) {
                    throw new Exception("Subscription object is empty or invalid.");
                }
                echo("Subscription: " . json_encode($subscription));
                $notifications[] = [
                    'subscription' => $subscription,
                    'payload' => $notification_payload
                ];
            
            } catch (Exception $e) {
                echo("Error creating subscription: " . $e->getMessage() . PHP_EOL);
            }
            checkTime();
        }
    }

    $webPush = new WebPush(['VAPID' => $vapid]);

    foreach ($notifications as $notification) {
        try {
            $webPush->queueNotification(
                $notification['subscription'],
                $notification['payload']
            );
        } catch (Exception $e) {
            echo("Error queuing notification: " . $e->getMessage() . PHP_EOL);
            echo("Notification details: " . json_encode($notification));
        }
    }

    try {
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                echo("Notification sent successfully to " . $report->getEndpoint());
                markNotificationsAsSent($sentNotifications);
            } else {
                echo("Notification failed to " . $report->getEndpoint() . ": " . $report->getReason());
                handleNotificationFailure($report);
            }
        }
    } catch (Exception $e) {
        echo("Error flushing notifications: " . $e->getMessage() . PHP_EOL);
    }
}

function handleNotificationFailure($report) {
    global $mysqli;

    $endpoint = $report->getEndpoint();
    $reason = $report->getReason();
    if (strpos($reason, 'Gone') !== false) {
        // Handle expired or unsubscribed endpoints
        echo("Removing expired or unsubscribed endpoint: $endpoint". PHP_EOL);
        mysqli_query($mysqli, "DELETE FROM notification_subscriptions WHERE notification_subscription_endpoint = '$endpoint'");
    } else {
        echo("Unhandled notification failure reason: $reason". PHP_EOL);
    }
}

$nextMinute = strtotime('+1 minute -1 second', strtotime(date('Y-m-d H:i:00')));

while (microtime(true) < $nextMinute) {
    $nextSecond = ceil(microtime(true));
    while (microtime(true) < $nextSecond) {
        usleep(100);
    }
    $second_decimal = round(microtime(true) - $nextSecond, 3);
    sendNotifications();
}