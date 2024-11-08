<table class="table table-striped">
    <thead>
        <tr>
            <th>Location Name</th>
            <th>Location User</th>
            <th>Location Address</th>
        </tr>
    </thead>
    <tbody>
<?php
foreach ($locations as $location) {
?>
        <tr>
            <td><a href="?page=inventory&location_id=<?php echo $location['inventory_location_id']; ?>"><?php echo $location['inventory_location_name']; ?></a></td>
            <td><?php echo $location['user_name']; ?></td>
            <td><?php echo $location['inventory_location_address'] . ' ' . $location['inventory_location_city'] . ' ' . $location['inventory_location_state'] . ' ' . $location['inventory_location_zip']; ?></td>
        </tr>
<?php
}
?>
    </tbody>
</table>