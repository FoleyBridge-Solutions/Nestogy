<table class="table table-striped">
    <thead>
        <tr>
            <th>Name</th>
            <th>Qualified By</th>
            <th>Reason for qualification</th>
            <th>Created At</th>
            <th>Updated At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($qualified_leads as $qualified_lead) { ?>
            <tr>
                <td><?php echo $qualified_lead['client_name']; ?></td>
            </tr>
        <?php } ?>
        <?php if (empty($qualified_leads)) { ?>
            <tr>
                <td colspan="5" class="text-center">No qualified leads found</td>
            </tr>
        <?php } ?>
    </tbody>
</table>