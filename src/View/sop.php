<div class="row ">
    <div class="col-9">
        <div class="card mb-3">
            <div class="card-header">
                <h2><?php echo $sop['title']; ?> <br><span class="text-muted small"><?php echo $_GET['version'] ? 'Version ' . $_GET['version'] : 'Current Version'; ?></span></h3>
                <h4><?php echo $sop['description']; ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <div class="form-group">
                                <textarea id="sop_<?php echo $sop['id']; ?>" class="form-control tinymce" name="sop_content"
                                placeholder="Click here to open the SOP Editor"><?php echo $sop['content']; ?></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary" id="save_sop">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card mb-3">
            <div class="card-header">
                <h3>Versions</h3>
            </div>
            <div class="card-body">
                <?php
                // in a grid, show all versions of the SOP, and when the file was last updated
                $versions = scandir("/var/www/nestogy/uploads/sops/{$sop['file_path']}");
                foreach ($versions as $version) {
                    $last_updated = filemtime("/var/www/nestogy/uploads/sops/{$sop['file_path']}/{$version}");
                    if (strpos($version, 'v') === 0) {
                        echo '<a href="/public/?page=sop&id='.$sop['id'].'&version='.$version.'">'.$version.'</a><br>';
                        echo '<small>Last updated: '.date('F j, Y @ h:iA', $last_updated).'</small><br>';
                    }
                }
                ?>
            </div>
    </div>
</div>
<?php //render the SOP
?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>SOP Preview</h3>
            </div>
            <div class="card-body">
                <div id="sop_viewer" style="overflow: auto; padding: 10px; box-sizing: border-box;">
                    <h1><?php echo $sop['title']; ?></h1>
                    <h2 class="text-muted"><?php echo $sop['description']; ?></h2>
                    <?php echo $sop['content']; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    #sop_viewer img {
        max-width: 100%; /* Ensures images do not exceed the width of the container */
        height: auto;    /* Maintains aspect ratio */
    }
</style>
<script>
    document.getElementById('save_sop').addEventListener('click', function() {
        //value needs to be from tinymce
        var content = tinyMCE.activeEditor.getContent();
        $.ajax({
            url: '/post.php?save_sop=true&id=<?php echo $sop['id']; ?>',
            type: 'POST',
            data: {
                content: content
            },
            success: function(response) {
                // Change the button text to sent
                $('#save_sop').html('Saved');
                // Make the button green
                $('#save_sop').css('background-color', 'green');
                $('#save_sop').css('color', 'white');
                // Make the button disabled
                $('#save_sop').prop('disabled', true);
                setTimeout(function() {
                    location.reload();
                }, 250);
            }
        });
    });
</script>
