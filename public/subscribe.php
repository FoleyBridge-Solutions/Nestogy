<?php
$config = require '/var/www/nestogy/config.php';

require_once '/var/www/nestogy/includes/config/config.php';
require_once '/var/www/nestogy/includes/functions/functions.php';
require_once '/var/www/nestogy/includes/check_login.php';


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Push Notifications Setup</title>
    <link rel="manifest" href="/manifest.json">
</head>
<body>
    <h1>Subscribe to Push Notifications</h1>
    <button id="subscribeButton">Subscribe</button>
    <p id="status"></p>


    <script>
        const subscribeButton = document.getElementById('subscribeButton');
        const status = document.getElementById('status');

        function initializePush() {
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                navigator.serviceWorker.register('/service-worker.js')
                .then(function(registration) {
                    console.log('Service Worker registered with scope:', registration.scope);
                    checkSubscription(registration);
                })
                .catch(function(error) {
                    console.error('Service Worker registration failed:', error);
                    status.textContent = 'Service Worker registration failed.';
                });
            } else {
                console.warn('Push messaging is not supported');
                status.textContent = 'Push messaging is not supported in your browser.';
                subscribeButton.disabled = true;
            }
        }

        function checkSubscription(registration) {
            registration.pushManager.getSubscription()
            .then(function(subscription) {
                if (subscription) {
                    console.log('User is already subscribed:', subscription);
                    status.textContent = 'You are already subscribed to push notifications.';
                    subscribeButton.disabled = true;
                } else {
                    status.textContent = 'Service Worker registered. Click the button to subscribe.';
                }
            })
            .catch(function(error) {
                console.error('Failed to get subscription:', error);
                status.textContent = 'Failed to get subscription status.';
            });
        }

        function subscribeUser() {
            navigator.serviceWorker.ready.then(function(registration) {
                const options = {
                    userVisibleOnly: true,
                    applicationServerKey: urlB64ToUint8Array('<?= $config['vapid']['public_key'] ?>')
                };
                return registration.pushManager.subscribe(options);
            })
            .then(function(subscription) {
                console.log('User is subscribed:', subscription);
                saveSubscriptionToServer(subscription);
                status.textContent = 'Subscription successful!';
            })
            .catch(function(error) {
                console.error('Failed to subscribe the user:', error);
                status.textContent = 'Failed to subscribe: ' + error;
            });
        }

        function urlB64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        function saveSubscriptionToServer(subscription) {
            fetch('/ajax/ajax.php?save_subscription', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(subscription),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => console.log('Subscription saved:', data))
            .catch(error => {
                console.error('Failed to save subscription:', error);
                status.textContent = 'Failed to save subscription.';
            });
        }

        if (navigator.userAgent.toLowerCase().includes('android')) {
            const intent = 'intent://#Intent;action=android.settings.IGNORE_BATTERY_OPTIMIZATION_SETTINGS;end';
            window.location.href = intent;
        }

        subscribeButton.addEventListener('click', subscribeUser);
        initializePush();
    </script>
</body>
</html>