
<div class="row">
    <div class="col">
        <div class="card mb-2">
            <div class="card-header py-3">
                <h3 class="card-title"><i class="fas fa-fw fa-credit-card mr-2"></i>Edit Product</h3>
            </div>
            <div class="card-body">
                <form action="?page=product&product_id=<?= $product['product_id'] ?>" method="post">
                    <div class="form-group">
                        <label for="product_name">Name</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" value="<?= $product['product_name'] ?>">
                    </div>
                    <div class="form-group">
                        <label for="product_description">Description</label>
                        <textarea class="form-control" id="product_description" name="product_description"><?= $product['product_description'] ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="product_price">Price</label>
                        <input type="text" class="form-control" id="product_price" name="product_price" value="<?= $product['product_price'] ?>">
                    </div>
                    <div class="form-group">
                        <label for="product_cost">Cost</label>
                        <input type="text" class="form-control" id="product_cost" name="product_cost" value="<?= $product['product_cost'] ?>">
                    </div>
                    <div class="form-group">
                        <label for="product_tax_id">Tax</label>
                        <select class="form-control" id="product_tax_id" name="product_tax_id">
                            <?php foreach ($taxes as $tax): ?>
                                <option value="<?= $tax['tax_id'] ?>" <?= $product['product_tax_id'] == $tax['tax_id'] ? 'selected' : '' ?>><?= $tax['tax_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product_category_id">Category</label>
                        <select class="form-control" id="product_category_id" name="product_category_id">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>" <?= $product['product_category_id'] == $category['category_id'] ? 'selected' : '' ?>><?= $category['category_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product_is_service">Is Service</label>
                        <input type="checkbox" id="product_is_service" name="product_is_service" value="<?= $product['product_is_service'] ?>" <?= $product['product_is_service'] ? 'checked' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label for="product_subscription">Is Subscription</label>
                        <input type="checkbox" id="product_subscription" name="product_subscription" value="<?= $product['product_subscription'] ?>" <?= $product['product_subscription'] ? 'checked' : '' ?>>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
