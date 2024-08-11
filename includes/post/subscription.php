<?php

global $mysqli, $session_name, $session_ip, $session_user_agent, $session_user_id;


if (isset($_POST["add_subscription"])) {
    $subscription_product_id = $_POST['subscription_product_id'];
    $subscription_client_id = $_POST['subscription_client_id'];
    $subscription_product_quantity = $_POST['subscription_product_quantity'];

    $mysqli->query("INSERT INTO subscriptions (subscription_product_id, subscription_client_id, subscription_product_quantity) VALUES ($subscription_product_id, $subscription_client_id, $subscription_product_quantity)");

    referWithAlert("Subscription added successfully", "success");
}