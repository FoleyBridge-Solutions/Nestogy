<table class="table table-striped">
    <thead>
        <tr>
            <th>Lead Name</th>
            <th>Meeting Date</th>
            <th>Created At</th>
            <th>Updated At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($meetings as $meeting) { ?>
            <tr>
                <td><?php echo $meeting['meeting_name']; ?></td>
            </tr>
        <?php } ?>
        <?php if (empty($meetings)) { ?>
            <tr>
                <td colspan="4" class="text-center">No meetings found</td>
            </tr>
        <?php } ?>
    </tbody>
</table>