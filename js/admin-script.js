jQuery(document).ready(function($) {
    // Layout preview buttons
    $('.maor-layout-preview-buttons button').on('click', function() {
        var layout = $(this).data('layout');
        
        // Update preview
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'maor_preview_layout',
                layout: layout,
                nonce: maor_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.maor-preview-container').html(response.data);
                }
            }
        });
    });
});