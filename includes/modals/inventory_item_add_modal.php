<?php 
require_once "/var/www/nestogy/includes/inc_all_modal.php";
$products_sql = "SELECT * FROM products ORDER BY product_name ASC";
$products = mysqli_fetch_all(mysqli_query($mysqli, $products_sql), MYSQLI_ASSOC);
?>

<div class="modal" id="addInventoryItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-fw fa-cart-plus mr-2"></i>New Inventory Item</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body bg-white">
                <div class="form-group">
                    <label for="product">Product</label>
                    <select class="form-control select2" id="product" name="product" required>
                        <?php
                        foreach ($products as $product) {
                            echo '<option value="' . $product['product_id'] . '">' . $product['product_name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <select class="form-control select2" id="location" name="location" required>
                        <?php
                        $locations_sql = "SELECT * FROM inventory_locations ORDER BY inventory_location_name ASC";
                        $locations = mysqli_fetch_all(mysqli_query($mysqli, $locations_sql), MYSQLI_ASSOC);
                        foreach ($locations as $location) {
                            echo '<option value="' . $location['inventory_location_id'] . '">' . $location['inventory_location_name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Add Item</button>
            </div>
        </div>
    </div>
</div>