<?php


function createProduct($product) {

    global $mysqli, $name, $ip, $user_agent, $user_id , $company_currency;

    $name = $product['product_name'];
    $description = $product['product_description'];
    $price = $product['product_price'];
    $cost = $product['product_cost'] ?? 0;
    $tax = $product['product_tax_id'];
    $category = $product['product_category_id'];
    $currency = $product['product_currency_code'] ?? $company_currency;


    mysqli_query($mysqli,"INSERT INTO products SET product_name = '$name', product_description = '$description', product_price = '$price', product_tax_id = $tax, product_cost = $cost, product_category_id = $category, product_currency_code = '$currency'");

    //Logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Product', log_action = 'Create', log_description = '$name', log_user_id = $user_id");

    //logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Product', log_action = 'Create', log_description = '$name created product $name', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id");

    $_SESSION['alert_message'] = "Product <strong>$name</strong> created";

    header("Location: " . $_SERVER["HTTP_REFERER"]);
}