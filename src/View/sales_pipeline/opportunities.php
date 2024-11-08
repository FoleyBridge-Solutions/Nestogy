<table class="table table-striped">
    <thead>
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Updated At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($opportunities as $opportunity) { ?>
            <tr>
                <td><?php echo $opportunity['opportunity_name']; ?></td>
                <td><?php echo $opportunity['opportunity_status']; ?></td>
                <td><?php echo $opportunity['opportunity_created_at']; ?></td>
                <td><?php echo $opportunity['opportunity_updated_at']; ?></td>
            </tr>
        <?php } ?>
        <?php if (empty($opportunities)) { ?>
            <tr>
                <td colspan="4" class="text-center">No opportunities found</td>
            </tr>
        <?php } ?>
    </tbody>
</table>