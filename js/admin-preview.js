jQuery(document).ready(function($) {
    // Update preview when settings change
    $('#maor_google_api_key, #maor_google_place_id, #maor_review_limit, #maor_layout_style, #maor_show_avatars, #maor_show_date, #maor_show_google_logo').on('change', function() {
        // This would ideally make an AJAX call to refresh the preview
        // For simplicity, we'll just show a message
        $('.maor-preview-container').html('<div class="notice notice-info"><p>Save settings to update preview</p></div>');
    });
});