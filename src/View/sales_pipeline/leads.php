<table class="table table-striped">
    <thead>
        <tr>
            <th>Name</th>
            <th>Industry</th>
            <th>Client Size</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($leads as $lead) { ?>
            <tr>
                <td><?php echo $lead['client_name']; ?></td>
                <td><?php echo $lead['client_industry']; ?></td>
                <td><?php echo $lead['client_size']; ?></td>
            </tr>
        <?php } ?>
        <?php if (empty($leads)) { ?>
            <tr>
                <td colspan="3" class="text-center">No leads found</td>
            </tr>
        <?php } ?>
    </tbody>
</table>