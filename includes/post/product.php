<?php

global $mysqli, $name, $ip, $user_agent, $user_id;


/*
 * ITFlow - GET/POST request handler for products
 */

// Products
if (isset($_POST['add_product'])) {

    global $mysqli, $company_currency, $name, $ip, $user_agent, $user_id;

    require_once '/var/www/nestogy/includes/post/models/product_model.php';


    mysqli_query($mysqli,"INSERT INTO products SET product_name = '$name', product_description = '$description', product_price = '$price', product_cost = $cost, product_currency_code = '$company_currency', product_tax_id = $tax, product_category_id = $category");

    //logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Product', log_action = 'Create', log_description = '$name created product $name', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id");

    $_SESSION['alert_message'] = "Product <strong>$name</strong> created";

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if (isset($_POST['edit_product'])) {

    require_once '/var/www/nestogy/includes/post/models/product_model.php';


    $product_id = intval($_POST['product_id']);
    $name = sanitizeInput($_POST['product_name']);
    $description = sanitizeInput($_POST['product_description']);
    $price = sanitizeInput($_POST['product_price']);
    $tax = intval($_POST['product_tax_id']);
    $cost = sanitizeInput($_POST['product_cost']);
    $category = intval($_POST['product_category_id']);
    $subscription = isset($_POST['product_subscription']) ? 1 : 0;
    $service = isset($_POST['product_is_service']) ? 1 : 0;

    mysqli_query($mysqli,"UPDATE products
        SET product_name = '$name',
            product_description = '$description',
            product_price = '$price',
            product_tax_id = $tax,
            product_cost = $cost,
            product_category_id = $category,
            product_subscription = $subscription,
            product_is_service = $service
        WHERE product_id = $product_id");

    //Logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Product', log_action = 'Modify', log_description = '$name', log_user_id = $user_id");

    //logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Product', log_action = 'Modify', log_description = '$name modified product $name', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id");

    $_SESSION['alert_message'] = "Product <strong>$name</strong> modified, subscription: $subscription, service: $service";

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if (isset($_GET['archive_product'])) {

    validateTechRole();

    $product_id = intval($_GET['archive_product']);

    // Get Contact Name and Client ID for logging and alert message
    $sql = mysqli_query($mysqli,"SELECT product_name FROM products WHERE product_id = $product_id");
    $row = mysqli_fetch_array($sql);
    $product_name = sanitizeInput($row['product_name']);

    mysqli_query($mysqli,"UPDATE products SET product_archived_at = NOW() WHERE product_id = $product_id");

    //logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Product', log_action = 'Archive', log_description = '$name archived product $product_name', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id, log_entity_id = $product_id");

    $_SESSION['alert_type'] = "error";
    $_SESSION['alert_message'] = "Product <strong>$product_name</strong> archived";

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if (isset($_GET['delete_product'])) {
    $product_id = intval($_GET['delete_product']);

    //Get Product Name
    $sql = mysqli_query($mysqli,"SELECT * FROM products WHERE product_id = $product_id");
    $row = mysqli_fetch_array($sql);
    $product_name = sanitizeInput($row['product_name']);

    mysqli_query($mysqli,"DELETE FROM products WHERE product_id = $product_id");

    //logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Product', log_action = 'Delete', log_description = '$name deleted product $name', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id");

    $_SESSION['alert_type'] = "error";
    $_SESSION['alert_message'] = "Product <strong>$product_name</strong> deleted";

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}
