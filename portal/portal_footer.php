<?php
/*
 * Client Portal
 * HTML Footer
 */
?>

<!-- Close container -->
</div>

<br>
<hr>

<p class="text-center"><?= nullable_htmlentities($company_name); ?></p>

<?php require_once "/var/www/nestogy/includes/inc_confirm_modal.php"; ?>


<!-- jQuery -->
<script src="/includes/plugins/jquery/jquery.min.js"></script>

<!-- Bootstrap 4 -->
<script src="/includes/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/2.0.3/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.3/js/dataTables.bootstrap5.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.1/js/dataTables.responsive.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.1/js/responsive.bootstrap5.js"></script>

<!--- TinyMCE -->
<script src="/includes/plugins/tinymce/tinymce.min.js" referrerpolicy="origin"></script>

<script>
    
    // Initialize TinyMCE
    tinymce.init({
        selector: '.tinymce',
        browser_spellcheck: true,
        resize: true,
        min_height: 300,
        max_height: 600,
        promotion: false,
        branding: false,
        menubar: false,
        statusbar: false,
        toolbar: [
            { name: 'styles', items: [ 'styles' ] },
            { name: 'formatting', items: [ 'bold', 'italic', 'forecolor' ] },
            { name: 'lists', items: [ 'bullist', 'numlist' ] },
            { name: 'alignment', items: [ 'alignleft', 'aligncenter', 'alignright', 'alignjustify' ] },
            { name: 'indentation', items: [ 'outdent', 'indent' ] },
            { name: 'table', items: [ 'table' ] },
            { name: 'extra', items: [ 'fullscreen' ] }
        ],
        mobile: {
        menubar: false,
        plugins: 'autosave lists autolink',
        toolbar: 'undo bold italic styles'
    },
        plugins: 'link image lists table code codesample fullscreen autoresize',
    });
    
    // Initialize DataTables
    $(document).ready(function() {
        $('.datatables-basic').DataTable({
            responsive: true
        });
    });
</script>

<script src="/includes/js/confirm_modal.js"></script>