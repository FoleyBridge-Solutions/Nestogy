<?php require_once "/var/www/portal.twe.tech/includes/inc_all_modal.php";

if (isset($_GET['client_id'])) {
    $client_id = $_GET['client_id'];
} else {
    $client_id = 0;
}

$clients = mysqli_query($mysqli, "SELECT * FROM clients");

$products = mysqli_query($mysqli, "SELECT * FROM products WHERE product_subscription = 1");

?>

<div class="modal-header">
    <h5 class="modal-title">Add Subscription</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
        <?php if ($client_id > 0) { ?>
            <input type="hidden" name="subscription_client_id" value="<?php echo $client_id; ?>">
        <?php } else { ?>
            <div class="mb-3">
                <label for="subscription_client_id" class="form-label">Client</label>
                <select class="form-select select2" name="subscription_client_id">
                    <option value="">Select a client</option>
                <?php while ($client = mysqli_fetch_assoc($clients)) : ?>
                        <option value="<?php echo $client['client_id']; ?>"><?php echo $client['client_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        <?php } ?>
        <div class="mb-3">
            <label for="subscription_product_id" class="form-label">Product</label>
            <select class="form-select select2" name="subscription_product_id">
                <option value="">Select a product</option>
                <?php while ($product = mysqli_fetch_assoc($products)) : ?>
                    <option value="<?php echo $product['product_id']; ?>"><?php echo $product['product_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="subscription_product_quantity" class="form-label">Quantity</label>
            <input type="number" class="form-control" name="subscription_product_quantity" value="1">
        </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary" id="add_subscription" name="add_subscription">Add</button>
</div>

