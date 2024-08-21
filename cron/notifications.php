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
        echo("Time limit exceeded mid-function");
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
        echo("Error fetching notifications: " . mysqli_error($mysqli));
        exit;
    }

    $sentNotifications = [];
    $notifications = [];
    while ($row = mysqli_fetch_assoc($notificationsResult)) {
        $user_id = $row['notification_user_id'] ?? 0;
        $notification_id = $row['notification_id'];
        $sentNotifications[] = $notification_id;
        $notification_payload = json_encode([
            'title' => $row['notification_type'],
            'body' => $row['notification'],
            'url' => "https://portal.twe.tech/" . $row['notification_id']
        ]);

        if ($user_id == 0) {
            $subscriptionsSql = "SELECT * FROM notification_subscriptions";
        } else {
            $subscriptionsSql = "SELECT * FROM notification_subscriptions WHERE notification_subscription_user_id = $user_id";
        }
        echo("Subscriptions SQL: " . $subscriptionsSql);
        $subscriptionsResult = mysqli_query($mysqli, $subscriptionsSql);
        if (!$subscriptionsResult) {
            echo("Error fetching subscriptions for user $user_id: " . mysqli_error($mysqli));
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
                echo("Error creating subscription: " . $e->getMessage());
            }
            checkTime();
        }
    }

    $webPush = new WebPush(['VAPID' => $vapid]);
    echo("Notifications: " . json_encode($notifications)."\n\n");

    foreach ($notifications as $notification) {
        try {
            $webPush->queueNotification(
                $notification['subscription'],
                $notification['payload']
            );
        } catch (Exception $e) {
            echo("Error queuing notification: " . $e->getMessage());
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
            }
        }
    } catch (Exception $e) {
        echo("Error flushing notifications: " . $e->getMessage());
    }
}

function monitorResources($parentPid) {
    while (true) {
        // Check if the parent process is still running
        if (!posix_kill($parentPid, 0)) {
            echo "Parent process has terminated. Exiting child process.\n";
            exit;
        }

        $memoryUsage = memory_get_usage(true);
        echo "Memory Usage: " . $memoryUsage . " bytes\n";
        sleep(5); // Adjust the interval as needed
    }
}

$pid = pcntl_fork();

if ($pid == -1) {
    die('Could not fork');
} else if ($pid) {
    // Parent process
    $nextMinute = strtotime('+1 minute -1 second', strtotime(date('Y-m-d H:i:00')));

    while (microtime(true) < $nextMinute) {
        $nextSecond = ceil(microtime(true));
        while (microtime(true) < $nextSecond) {
            usleep(100);
        }
        $second_decimal = round(microtime(true) - $nextSecond, 3);
        echo date("Y-m-d H:i:s") . "." . $second_decimal . "\n";
        sendNotifications();
    }
} else {
    // Child process
    $parentPid = posix_getppid();
    monitorResources($parentPid);
}