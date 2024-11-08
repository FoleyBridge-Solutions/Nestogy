<table class="table table-striped">
    <thead>
        <tr>
            <th>Landing Page</th>
            <th>Industry</th>
            <th>Created At</th>
            <th>Updated At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($landings as $landing) { ?>
            <tr>
                <td><?php echo $landing['landing_url']; ?></td>
                <td><?php echo $landing['landing_industry']; ?></td>
                <td><?php echo $landing['landing_created_at']; ?></td>
                <td><?php echo $landing['landing_updated_at']; ?></td>
            </tr>
        <?php } ?>
        <?php if (empty($landings)) { ?>
            <tr>
                <td colspan="4" class="text-center">No landings found</td>
            </tr>
        <?php } ?>
    </tbody>
</table>