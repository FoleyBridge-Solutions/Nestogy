<?php require_once "/var/www/nestogy/includes/inc_all_modal.php"; 

$email_id = $_GET['email_id'];

$sql = "SELECT * FROM email_queue WHERE email_id = $email_id";
$result = mysqli_query($mysqli, $sql);
$email = mysqli_fetch_assoc($result);





?>
<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><?= $email['email_subject'] ?></h5>
        </div>
        <div class="modal-body">
            <p><?= $email['email_content'] ?></p>
            <p><?= date('F j, Y, g:i a', strtotime($email['email_sent_at'])) ?></p>
        </div>
    </div>
</div>