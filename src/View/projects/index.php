<div class="card">
    <div class="card-header header-elements">
        <h5 class="card-header-title">Projects</h5>
        <div class="card-header-elements ms-auto">
            <button type="button" class="btn btn-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="project_add_modal.php">Create Project</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive card-datatable" id="projectsTable">
            <table class="table table-striped table-bordered datatables-basic">
                <thead>
                    <tr>
                        <th>Name</th>
                        <?php if (!$client_page): ?>
                            <th>Client</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Manager</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><i class="<?= $project['project_icon'] ?? 'bx bxs-objects-vertical-center'; ?>"></i> <?php echo $project['project_name']; ?></td>
                            <?php if (!$client_page): ?>
                                <td><a href="?page=projects&client_id=<?= $project['client_id']; ?>"><?= $project['client_name'] ?? 'N/A'; ?></a></td>
                            <?php endif; ?>
                            <td><?php echo $project['project_status']; ?></td>
                            <td><?php echo $project['user_name'] ?? 'N/A'; ?></td>
                            <td data-order="<?php echo strtotime($project['project_due']); ?>"><?php echo date('M j, Y', strtotime($project['project_due'])); ?></td>
                            <td>
                                <a href="?page=project&project_id=<?= $project['project_id']; ?>" class="btn btn-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>            
        </div>
    </div>
</div>