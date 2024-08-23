<?php

// Invoice Item Related Functions


function createInvoiceItem(
    $type,
    $item
) {
    // Access global variables
    global $mysqli;

    $name = $item['item_name'];
    $description = $item['item_description'];
    $qty = $item['item_qty'];
    $price = $item['item_price'];
    $tax_id = $item['item_tax_id'];
    $item_order = $item['item_order'] ?? 0;
    $invoice_id = $item['item_invoice_id'];
    $category_id = $item['item_category_id'];
    $discount = $item['item_discount'];
    $product_id = $item['item_product_id'];
    $subtotal = $price * $qty;

    // if discount ends in %, calculate discount amount
    if (substr($discount, -1) == '%') {
        $discount = substr($discount, 0, -1);
        $discount = floatval($discount);

        $discount = $subtotal * $discount / 100;
    } else {
        $discount = floatval($discount);
    }
    $subtotal = $price * $qty - $discount;

    if ($type == 'recurring'){
        $recurring_id = $invoice_id;
    
        if ($tax_id > 0) {
            $sql = mysqli_query($mysqli,"SELECT * FROM taxes WHERE tax_id = $tax_id");
            $row = mysqli_fetch_array($sql);
            $tax_percent = floatval($row['tax_percent']);
            $tax_amount = $subtotal * $tax_percent / 100;
        } else {
            $tax_amount = 0;
        }

        $total = $subtotal + $tax_amount;

        mysqli_query($mysqli,
        "INSERT INTO invoice_items SET
            item_name = '$name',
            item_description = '$description',
            item_quantity = $qty,
            item_price = $price,
            item_subtotal = $subtotal,
            item_tax = $tax_amount,
            item_total = $total,
            item_tax_id = $tax_id,
            item_order = $item_order,
            item_recurring_id = $recurring_id
        ");

        //Get Discount

        $sql = mysqli_query($mysqli,"SELECT * FROM recurring WHERE recurring_id = $recurring_id");
        $row = mysqli_fetch_array($sql);
        $recurring_discount = floatval($row['recurring_discount_amount']);

        //add up all the items
        $sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE item_recurring_id = $recurring_id");
        $recurring_amount = 0;
        while($row = mysqli_fetch_array($sql)) {
            $item_total = floatval($row['item_total']);
            $recurring_amount = $recurring_amount + $item_total;
        }
        $recurring_amount = $recurring_amount - $recurring_discount;

        mysqli_query($mysqli,
        "UPDATE recurring SET
            recurring_amount = $recurring_amount
            WHERE recurring_id = $recurring_id
        ");
    } elseif ($type == 'invoice') {

        if ($tax_id > 0) {
            $sql = mysqli_query($mysqli,"SELECT * FROM taxes WHERE tax_id = $tax_id");
            $row = mysqli_fetch_array($sql);
            $tax_percent = floatval($row['tax_percent']);
            $tax_amount = $subtotal * $tax_percent / 100;
        } else {
            $tax_amount = 0;
        }

        $total = $subtotal + $tax_amount;

        mysqli_query($mysqli,"INSERT INTO invoice_items SET item_name = '$name', item_description = '$description', item_quantity = $qty, item_product_id = $product_id, item_price = $price, item_discount = $discount, item_order = $item_order, item_tax_id = $tax_id, item_invoice_id = $invoice_id, item_category_id = $category_id");
    }
}

function readInvoiceItem(
    $type,
    $item_id
) {
    // Access global variables
    global $mysqli;

    switch($type) {
        case "invoice":
            $sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE item_id = $item_id");
            $row = mysqli_fetch_array($sql);
            return $row;
            break;
        
        case "recurring":
            $sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE item_id = $item_id");
            $row = mysqli_fetch_array($sql);
            return $row;
            break;
    }
}

function updateInvoiceItem(
    $item
) {
    // Access global variables
    global $mysqli;

    $item_id = intval($item['item_id']);
    $name = sanitizeInput($item['name']);
    $description = sanitizeInput($item['description']);
    $qty = floatval($item['qty']);
    $price = floatval($item['price']);
    $tax_id = intval($item['tax_id']);
    $invoice_id = intval($item['invoice_id'] ?? 0);
    $recurring_id = intval($item['recurring_id'] ?? 0);
    $discount = floatval($item['discount']);
    $quote_id = intval($item['quote_id']);
    $category_id = intval($item['category_id']);
    $product_id = intval($item['product_id']);

    $subtotal = $price * $qty;

    // if discount ends in %, calculate discount amount
    if (substr($discount, -1) == '%') {
        $discount = substr($discount, 0, -1);
        $discount = floatval($discount);

        $discount = $subtotal * $discount / 100;
    } else {
        $discount = floatval($discount);
    }

    mysqli_query($mysqli,"UPDATE invoice_items SET item_name = '$name', item_category_id = $category_id, item_product_id = $product_id, item_description = '$description', item_quantity = $qty, item_price = $price, item_tax_id = $tax_id, item_discount = $discount WHERE item_id = $item_id");
}

function deleteInvoiceItem(
    $type,
    $item_id
) {
    // Access global variables
    global $mysqli, $user_id, $ip, $user_agent;

    switch($type) {
        case "invoice":
            $sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE item_id = $item_id");
            $row = mysqli_fetch_array($sql);
            $invoice_id = intval($row['item_invoice_id']);
            $item_total = floatval($row['item_total']);
                
        
            mysqli_query($mysqli,"DELETE FROM invoice_items WHERE item_id = $item_id");
        
            //Logging
            mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Invoice Item', log_action = 'Delete', log_description = '$item_id', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id");
        
            break;
        
        case "recurring":
            $sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE item_id = $item_id");
            $row = mysqli_fetch_array($sql);
            $recurring_id = intval($row['item_recurring_id']);
            $item_total = floatval($row['item_total']);
        
            $sql = mysqli_query($mysqli,"SELECT * FROM recurring WHERE recurring_id = $recurring_id");
            $row = mysqli_fetch_array($sql);
        
            $new_recurring_amount = floatval($row['recurring_amount']) - $item_total;
        
            mysqli_query($mysqli,"UPDATE recurring SET recurring_amount = $new_recurring_amount WHERE recurring_id = $recurring_id");
        
            mysqli_query($mysqli,"DELETE FROM invoice_items WHERE item_id = $item_id");
        
            //Logging
            mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Recurring Item', log_action = 'Delete', log_description = 'Item ID $item_id from Recurring ID $recurring_id', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id");
            break;
    }
}

function updateItemOrder(
    $type,
    $item_id,
    $item_recurring_id,
    $update_direction
) {
    // Access global variables
    global $mysqli;

    if ($type == "invoice") {
        $sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE item_id = $item_id");
        $row = mysqli_fetch_array($sql);
        $current_order = intval($row['item_order']);
    
        switch ($update_direction)
        {
            case 'up':
                $new_order = $current_order - 1;
                break;
            case 'down':
                $new_order = $current_order + 1;
                break;
        }
    
        //Find item_id of current item in $new_order
        $other_sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE item_recurring_id = $item_recurring_id AND item_order = $new_order");
        $other_row = mysqli_fetch_array($other_sql);
        $other_item_id = intval($other_row['item_id']);
    
        mysqli_query($mysqli,"UPDATE invoice_items SET item_order = $new_order WHERE item_id = $item_id");
    
        mysqli_query($mysqli,"UPDATE invoice_items SET item_order = $current_order WHERE item_id = $other_item_id");
    } elseif ($type == "recurring") {
        $sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE item_id = $item_id");
        $row = mysqli_fetch_array($sql);
        $current_order = intval($row['item_order']);
        $item_invoice_id = intval($row['item_invoice_id']);
    
        switch ($update_direction)
        {
            case 'up':
                $new_order = $current_order - 1;
                break;
            case 'down':
                $new_order = $current_order + 1;
                break;
        }
    
        //Find item_id of current item in $new_order
        $other_sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE item_invoice_id = $item_invoice_id AND item_order = $new_order");
        $other_row = mysqli_fetch_array($other_sql);
        $other_item_id = intval($other_row['item_id']);
    
        mysqli_query($mysqli,"UPDATE invoice_items SET item_order = $new_order WHERE item_id = $item_id");
    
        mysqli_query($mysqli,"UPDATE invoice_items SET item_order = $current_order WHERE item_id = $other_item_id");
        }
}