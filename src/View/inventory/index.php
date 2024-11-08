<div class="row mb-3">
    <div class="col">
        <div class="card">
            <div class="card-header header-elements">
                <h5 class="card-header-title">Inventory</h5>
                <div class="card-header-elements ms-auto">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-plus"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="inventory_item_add_modal.php"> Add Item</a></li>
                        <li><a class="dropdown-item loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="inventory_location_add_modal.php">Add Location</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="nav-align-left">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-align-inventory-locations" aria-controls="navs-left-align-inventory-locations" aria-selected="true">
                                Inventory Locations
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-align-inventory-items" aria-controls="navs-left-align-inventory-items" aria-selected="false">
                                Inventory Items
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-align-inventory-categories" aria-controls="navs-left-align-inventory-categories" aria-selected="false">
                                Inventory Categories
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="navs-left-align-inventory-locations">
                            <?php include 'locations.php'; ?>
                        </div>
                        <div class="tab-pane fade" id="navs-left-align-inventory-items">
                            <?php include 'items.php'; ?>
                        </div>
                        <div class="tab-pane fade" id="navs-left-align-inventory-categories">
                            <?php include 'categories.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>