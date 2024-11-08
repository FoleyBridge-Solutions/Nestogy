<?php 
if (isset($_POST['sop_add_modal_submit'])) {
    // Define the base directory
    $base_dir = "/var/www/portal.twe.tech/uploads/sops/";
    
    // Create new folder in the base directory with a random name
    $folder_name = bin2hex(random_bytes(16));
    $folder_path = $base_dir . $folder_name;
    
    mkdir($folder_path, 0777, true);

    // Create new file in the folder with the name of v1.1
    $file_name = "v1.1";
    $file_path = "$folder_path/$file_name";
    file_put_contents($file_path, "");
    
    // Use prepared statements to save the folder name to the database
    $stmt = $mysqli->prepare("INSERT INTO sops (title, description, version, file_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $_POST['title'], $_POST['description'], $file_name, $folder_name);
    $stmt->execute();

    // Save the clients to the database
    $sop_id = $mysqli->insert_id;
    $stmt = $mysqli->prepare("INSERT INTO sop_clients (sop_id, client_id) VALUES (?, ?)");
    foreach ($_POST['client_id'] as $client_id) {
        $stmt->bind_param("ii", $sop_id, $client_id);
        $stmt->execute();
    }
    header("Location: /public/?page=sop&id=$sop_id");
}

if (isset($_GET['save_sop'])) {
        // Get the SOP
        $sop_id = $_GET['id'];
        $sop_content = $_POST['content'];
        echo $sop_content;
        $sop_sql = "SELECT * FROM sops WHERE id = $sop_id";
        $sop = $mysqli->query($sop_sql)->fetch_assoc();

        $sop_file_path = $sop['file_path'];
        // Get all versions of the SOP to find the next version number (assume versioning by v1.1, v1.2, v1.3 ... v1.10, v1.11, etc.)
        $versions = scandir("/var/www/portal.twe.tech/uploads/sops/{$sop_file_path}");
        $latest_version = 0;
        foreach ($versions as $version) {
            if (strpos($version, 'v') === 0) {
                $version_number = floatval(substr($version, 1));
                if ($version_number > $latest_version) {
                    $latest_version = $version_number;
                }
            }
        }
        $latest_version = $latest_version + 0.1;
        $version = "v" . $latest_version;
        // Save the content to the file
        file_put_contents("/var/www/portal.twe.tech/uploads/sops/{$sop_file_path}/{$version}", $sop_content);
}
?>