<table class="table table-striped">
    <thead>
        <tr>
            <th>Contact Name</th>
            <th>Industry</th>
            <th>Location</th>
            <th>Created At</th>
            <th>Updated At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($contacts as $contact) { ?>
            <tr>
                <td><?php echo $contact['contact_name']; ?></td>
            </tr>
        <?php } ?>
        <?php if (empty($contacts)) { ?>
            <tr>
                <td colspan="5" class="text-center">No contacts found</td>
            </tr>
        <?php } ?>
    </tbody>
</table>