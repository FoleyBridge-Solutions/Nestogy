<?php

global $mysqli, $name, $ip, $user_agent, $user_id;


if (isset($_POST["add_subscription"])) {
    $subscription_product_id = $_POST['subscription_product_id'];
    $subscription_client_id = $_POST['subscription_client_id'];
    $subscription_product_quantity = $_POST['subscription_product_quantity'];

    $mysqli->query("INSERT INTO subscriptions (subscription_product_id, subscription_client_id, subscription_product_quantity) VALUES ($subscription_product_id, $subscription_client_id, $subscription_product_quantity)");

    referWithAlert("Subscription added successfully", "success");
}