<?php

function getNetwork($network_id) {
    global $mysqli;
    $query = $mysqli->prepare("SELECT * FROM networks WHERE network_id = ?");
    $query->bind_param("i", $network_id);
    $query->execute();
    $result = $query->get_result();
    return $result->fetch_assoc();
}

?>