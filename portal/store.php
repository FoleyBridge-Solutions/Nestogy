<?php
/*
 * Client Portal
 * Storefront for clients
 */



require_once "/var/www/portal.twe.tech/includes/inc_portal.php";

$products_sql = mysqli_query($mysqli, "SELECT * FROM products WHERE product_public = 1");
$products = [];
while ($product = mysqli_fetch_assoc($products_sql)) {
    $products[] = $product;
}
?>
<div class="row">
    <div class="col-12">
        <h1>Store</h1>
    </div>
    <div class="col-12">
        <div class="row">
            <?php foreach ($products as $product) { ?>
                <div class="col-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= $product['product_name']; ?></h5>
                            <img src="<?= $product['product_image']; ?>" class="img-fluid" alt="<?= $product['product_name']; ?>">
                            <p class="card-text"><?= $product['product_description']; ?></p>
                            <p class="card-text"><?= $product['product_price']; ?></p>
                            <a href="/portal_post.php?add_to_cart=<?= $product['product_id']; ?>" class="btn btn-primary">Add to Cart</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php
require_once "portal_footer.php";
