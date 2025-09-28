<?php
/**
 * Plugin Name:       My All-in-One Reviews
 * Description:       A comprehensive plugin to display reviews from multiple sources with multiple layout options.
 * Version:           1.2.0
 * Author:            Shaheryar A.
 * Author URI:        https://shaheryar.tech/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-all-in-one-reviews
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Enqueue necessary scripts and styles
function maor_enqueue_scripts() {
    wp_enqueue_style('maor-style', plugin_dir_url(__FILE__) . 'css/maor-style.css');
    wp_enqueue_script('maor-carousel', plugin_dir_url(__FILE__) . 'js/maor-carousel.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'maor_enqueue_scripts');

// Enqueue admin styles
function maor_admin_assets($hook) {
    if ($hook != 'toplevel_page_my_all_in_one_reviews') {
        return;
    }
    wp_enqueue_style('maor-admin-style', plugin_dir_url(__FILE__) . 'css/admin-style.css');

    // enqueue the same ajax script used on the frontend
    wp_enqueue_script('maor-ajax', plugin_dir_url(__FILE__) . 'js/maor-ajax.js', array('jquery'), '1.0.0', true);
    wp_localize_script('maor-ajax', 'maor_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('maor_ajax_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'maor_admin_assets');


// Step 2: Create Admin Menu
function maor_add_admin_menu() {
    add_menu_page(
        'All-in-One Reviews',
        'Reviews',
        'manage_options',
        'my_all_in_one_reviews',
        'maor_settings_page_html',
        'dashicons-star-filled',
        20
    );
}
add_action('admin_menu', 'maor_add_admin_menu');

// Settings page HTML
// Settings page HTML
function maor_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap maor-admin-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('maor_options');
            do_settings_sections('my_all_in_one_reviews');
            submit_button('Save Settings');
            ?>
        </form>
        
        <div class="maor-preview-section">
            <h2>Preview</h2>
            <div class="maor-preview-container">
                <?php echo maor_display_reviews_shortcode(array()); ?>
            </div>
        </div>
        
        <div class="maor-cache-section">
            <h2>Cache Management</h2>
            <p>If you've changed your API settings but still see old reviews, clear the cache.</p>
            <form method="post">
                <input type="hidden" name="maor_clear_cache" value="1">
                <?php wp_nonce_field('maor_clear_cache_nonce', 'maor_nonce'); ?>
                <button type="submit" name="submit" id="submit" class="button button-secondary">Clear Reviews Cache</button>
            </form>
        </div>
    </div>
    <?php
}


// Add this to handle the cache clearing
function maor_handle_cache_clear() {
    if (isset($_POST['maor_clear_cache']) && current_user_can('manage_options')) {
        // Verify nonce for security
        if (!isset($_POST['maor_nonce']) || !wp_verify_nonce($_POST['maor_nonce'], 'maor_clear_cache_nonce')) {
            wp_die('Security check failed');
        }
        
        $options = get_option('maor_options_array');
        
        // Clear Google reviews cache
        if (isset($options['google_place_id'])) {
            $transient_key = 'maor_google_reviews_' . md5($options['google_place_id']);
            delete_transient($transient_key);
        }
        
        // Clear App Store reviews cache
        if (isset($options['appstore_app_id'])) {
            $transient_key = 'maor_appstore_reviews_' . md5($options['appstore_app_id']);
            delete_transient($transient_key);
        }
        
        // Show success message
        add_settings_error('maor_messages', 'maor_message', 
            'Reviews cache cleared successfully.', 'updated');
    }
}
add_action('admin_init', 'maor_handle_cache_clear');

// Register settings
function maor_settings_init() {
    register_setting('maor_options', 'maor_options_array');

    // Google Business Profile Section
    add_settings_section(
        'maor_google_section',
        'Google Business Profile Settings',
        'maor_google_section_callback',
        'my_all_in_one_reviews'
    );

    add_settings_field(
        'maor_google_api_key',
        'Google Places API Key',
        'maor_google_api_key_callback',
        'my_all_in_one_reviews',
        'maor_google_section'
    );

    add_settings_field(
        'maor_google_place_id',
        'Google Place ID',
        'maor_google_place_id_callback',
        'my_all_in_one_reviews',
        'maor_google_section'
    );
    
    // App Store Settings Section
    add_settings_section(
        'maor_appstore_section',
        'App Store Settings',
        'maor_appstore_section_callback',
        'my_all_in_one_reviews'
    );

    add_settings_field(
        'maor_appstore_app_id',
        'App Store App ID',
        'maor_appstore_app_id_callback',
        'my_all_in_one_reviews',
        'maor_appstore_section'
    );
    
    // Manual Reviews Section
    add_settings_section(
        'maor_manual_section',
        'Manual Reviews',
        'maor_manual_section_callback',
        'my_all_in_one_reviews'
    );

    add_settings_field(
        'maor_manual_reviews',
        'Manual Reviews JSON',
        'maor_manual_reviews_callback',
        'my_all_in_one_reviews',
        'maor_manual_section'
    );
    
    // Display Settings Section
    add_settings_section(
        'maor_display_section',
        'Display Settings',
        'maor_display_section_callback',
        'my_all_in_one_reviews'
    );
    
    add_settings_field(
        'maor_review_limit',
        'Number of Reviews to Show',
        'maor_review_limit_callback',
        'my_all_in_one_reviews',
        'maor_display_section'
    );
    
    add_settings_field(
        'maor_layout_style',
        'Layout Style',
        'maor_layout_style_callback',
        'my_all_in_one_reviews',
        'maor_display_section'
    );
    
    add_settings_field(
        'maor_show_avatars',
        'Show User Avatars',
        'maor_show_avatars_callback',
        'my_all_in_one_reviews',
        'maor_display_section'
    );
    
    add_settings_field(
        'maor_show_date',
        'Show Review Date',
        'maor_show_date_callback',
        'my_all_in_one_reviews',
        'maor_display_section'
    );
    
    add_settings_field(
        'maor_show_source',
        'Show Review Source',
        'maor_show_source_callback',
        'my_all_in_one_reviews',
        'maor_display_section'
    );
}
add_action('admin_init', 'maor_settings_init');

// Section callbacks
function maor_google_section_callback() {
    echo '<p>Enter your Google API details below. You need to get these from the Google Cloud Platform.</p>';
}

function maor_appstore_section_callback() {
    echo '<p>Enter your App Store app ID to fetch reviews.</p>';
}

function maor_manual_section_callback() {
    echo '<p>Add manual reviews in JSON format. Use the following structure: <code>[{"author_name": "John Doe", "rating": 5, "text": "Great product!", "time": "2023-10-15", "source": "Manual"}]</code></p>';
}

function maor_display_section_callback() {
    echo '<p>Customize how your reviews are displayed.</p>';
}

// Field callbacks
function maor_google_api_key_callback() {
    $options = get_option('maor_options_array');
    $api_key = isset($options['google_api_key']) ? $options['google_api_key'] : '';
    echo '<input type="text" id="maor_google_api_key" name="maor_options_array[google_api_key]" value="' . esc_attr($api_key) . '" size="50">';
}

function maor_google_place_id_callback() {
    $options = get_option('maor_options_array');
    $place_id = isset($options['google_place_id']) ? $options['google_place_id'] : '';
    echo '<input type="text" id="maor_google_place_id" name="maor_options_array[google_place_id]" value="' . esc_attr($place_id) . '" size="50">';
    echo '<p class="description">You can find your Place ID here: <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">Google Place ID Finder</a>.</p>';
}

function maor_appstore_app_id_callback() {
    $options = get_option('maor_options_array');
    $app_id = isset($options['appstore_app_id']) ? $options['appstore_app_id'] : '';
    echo '<input type="text" id="maor_appstore_app_id" name="maor_options_array[appstore_app_id]" value="' . esc_attr($app_id) . '" size="50">';
    echo '<p class="description">Enter your App Store application ID.</p>';
}

function maor_manual_reviews_callback() {
    $options = get_option('maor_options_array');
    $manual_reviews = isset($options['manual_reviews']) ? $options['manual_reviews'] : '';
    echo '<textarea id="maor_manual_reviews" name="maor_options_array[manual_reviews]" rows="10" cols="50">' . esc_textarea($manual_reviews) . '</textarea>';
}

function maor_review_limit_callback() {
    $options = get_option('maor_options_array');
    $limit = isset($options['review_limit']) ? $options['review_limit'] : '5';
    echo '<input type="number" id="maor_review_limit" name="maor_options_array[review_limit]" value="' . esc_attr($limit) . '" min="1" max="20">';
}

// Update the layout style callback function
function maor_layout_style_callback() {
    $options = get_option('maor_options_array');
    $style = isset($options['layout_style']) ? $options['layout_style'] : 'grid';
    echo '<select id="maor_layout_style" name="maor_options_array[layout_style]">
        <option value="grid" ' . selected($style, 'grid', false) . '>Grid</option>
        <option value="carousel" ' . selected($style, 'carousel', false) . '>Carousel</option>
        <option value="badge" ' . selected($style, 'badge', false) . '>Badge</option>
    </select>';
}

function maor_show_avatars_callback() {
    $options = get_option('maor_options_array');
    $show_avatars = isset($options['show_avatars']) ? $options['show_avatars'] : '1';
    echo '<input type="checkbox" id="maor_show_avatars" name="maor_options_array[show_avatars]" value="1" ' . checked($show_avatars, '1', false) . '>';
}

function maor_show_date_callback() {
    $options = get_option('maor_options_array');
    $show_date = isset($options['show_date']) ? $options['show_date'] : '1';
    echo '<input type="checkbox" id="maor_show_date" name="maor_options_array[show_date]" value="1" ' . checked($show_date, '1', false) . '>';
}

function maor_show_source_callback() {
    $options = get_option('maor_options_array');
    $show_source = isset($options['show_source']) ? $options['show_source'] : '1';
    echo '<input type="checkbox" id="maor_show_source" name="maor_options_array[show_source]" value="1" ' . checked($show_source, '1', false) . '>';
}

// Step 3: Create the Shortcode with parameters
function maor_enqueue_additional_scripts() {
    wp_enqueue_script('maor-ajax', plugin_dir_url(__FILE__) . 'js/maor-ajax.js', array('jquery'), '1.0.0', true);
    wp_localize_script('maor-ajax', 'maor_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('maor_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'maor_enqueue_additional_scripts');

// Create a new shortcode for the tabbed layout
function maor_tabbed_reviews_shortcode($atts) {
    $options = get_option('maor_options_array');
    $atts = shortcode_atts(array(
        'limit'        => isset($options['review_limit']) ? (int)$options['review_limit'] : 10,
        'layout'       => isset($options['layout_style']) ? $options['layout_style'] : 'grid',
        'show_avatars' => isset($options['show_avatars']) ? (bool)$options['show_avatars'] : true,
        'show_date'    => isset($options['show_date']) ? (bool)$options['show_date'] : true,
        'show_source'  => isset($options['show_source']) ? (bool)$options['show_source'] : true,
        'per_row'      => 5,
        'per_view'     => 5
    ), $atts);

    ob_start(); ?>
    <div class="maor-tabbed-reviews"
         data-limit="<?php echo esc_attr($atts['limit']); ?>"
         data-layout="<?php echo esc_attr($atts['layout']); ?>"
         data-show-avatars="<?php echo $atts['show_avatars'] ? '1' : '0'; ?>"
         data-show-date="<?php echo $atts['show_date'] ? '1' : '0'; ?>"
         data-show-source="<?php echo $atts['show_source'] ? '1' : '0'; ?>"
         data-per-row="<?php echo esc_attr($atts['per_row']); ?>"
         data-per-view="<?php echo esc_attr($atts['per_view']); ?>">

        <?php if ($atts['layout'] !== 'badge'): ?>
            <?php echo maor_generate_review_tabs(); ?>
        <?php endif; ?>

        <div class="maor-reviews-content">
            <?php
            echo maor_display_reviews_shortcode(array_merge(
                $atts,
                array(
                    'source' => 'all',
                    // tell the renderer to put tabs inside the drawer when badge layout
                    'tabs_in_drawer' => ($atts['layout'] === 'badge')
                )
            ));
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('my_tabbed_reviews', 'maor_tabbed_reviews_shortcode');



// Function to generate review tabs
function maor_generate_review_tabs() {
    $options = get_option('maor_options_array');
    $sources = array('all' => 'All Reviews');
    
    // Check which sources are available
    if (!empty($options['google_place_id']) && !empty($options['google_api_key'])) {
        $sources['google'] = 'Google';
    }
    
    if (!empty($options['appstore_app_id'])) {
        $sources['appstore'] = 'App Store';
    }
    
    if (!empty($options['manual_reviews'])) {
        $manual_reviews = json_decode($options['manual_reviews']);
        if (!empty($manual_reviews)) {
            $sources['manual'] = 'Manual';
        }
    }
    
    // Calculate average ratings for each source
    $average_ratings = array();
    foreach ($sources as $source_key => $source_name) {
        if ($source_key === 'all') {
            $reviews = maor_get_all_reviews('all');
        } else {
            $reviews = maor_get_all_reviews($source_key);
        }
        
        $total_rating = 0;
        $review_count = count($reviews);
        
        foreach ($reviews as $review) {
            $total_rating += $review->rating;
        }
        
        $average_ratings[$source_key] = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;
    }
    
    ob_start();
    ?>
    <div class="maor-tabs">
        <?php foreach ($sources as $source_key => $source_name): ?>
            <div class="maor-tab <?php echo $source_key === 'all' ? 'active' : ''; ?>" data-source="<?php echo $source_key; ?>">
                <?php if ($source_key === 'google'): ?>
                    <svg class="maor-tab-icon" width="16" height="16" viewBox="0 0 85 36" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#a-259)">
                            <path fill="#4285F4" d="M20.778 13.43h-9.862v2.927h6.994c-.345 4.104-3.76 5.854-6.982 5.854-4.123 0-7.72-3.244-7.72-7.791 0-4.43 3.429-7.841 7.73-7.841 3.317 0 5.272 2.115 5.272 2.115l2.049-2.122s-2.63-2.928-7.427-2.928C4.725 3.644 0 8.8 0 14.367c0 5.457 4.445 10.777 10.988 10.777 5.756 0 9.969-3.942 9.969-9.772 0-1.23-.179-1.941-.179-1.941Z"></path>
                            <path fill="#EA4335" d="M28.857 11.312c-4.047 0-6.947 3.163-6.947 6.853 0 3.744 2.813 6.966 6.994 6.966 3.786 0 6.887-2.893 6.887-6.886 0-4.576-3.607-6.933-6.934-6.933Zm.04 2.714c1.99 0 3.876 1.609 3.876 4.201 0 2.538-1.878 4.192-3.885 4.192-2.205 0-3.945-1.766-3.945-4.212 0-2.394 1.718-4.181 3.954-4.181Z"></path>
                            <path fill="#FBBC05" d="M43.965 11.312c-4.046 0-6.946 3.163-6.946 6.853 0 3.744 2.813 6.966 6.994 6.966 3.785 0 6.886-2.893 6.886-6.886 0-4.576-3.607-6.933-6.934-6.933Zm.04 2.714c1.99 0 3.876 1.609 3.876 4.201 0 2.538-1.877 4.192-3.885 4.192-2.205 0-3.945-1.766-3.945-4.212 0-2.394 1.718-4.181 3.955-4.181Z"></path>
                            <path fill="#4285F4" d="M58.783 11.319c-3.714 0-6.634 3.253-6.634 6.904 0 4.16 3.385 6.918 6.57 6.918 1.97 0 3.017-.782 3.79-1.68v1.363c0 2.384-1.448 3.812-3.633 3.812-2.11 0-3.169-1.57-3.537-2.46l-2.656 1.11c.943 1.992 2.839 4.07 6.215 4.07 3.693 0 6.508-2.327 6.508-7.205V11.734h-2.897v1.17c-.89-.96-2.109-1.585-3.726-1.585Zm.269 2.709c1.821 0 3.69 1.554 3.69 4.210 0 2.699-1.865 4.187-3.73 4.187-1.98 0-3.823-1.608-3.823-4.161 0-2.653 1.914-4.236 3.863-4.236Z"></path>
                            <path fill="#EA4335" d="M78.288 11.302c-3.504 0-6.446 2.788-6.446 6.901 0 4.353 3.28 6.934 6.782 6.934 2.924 0 4.718-1.6 5.789-3.032l-2.389-1.59c-.62.962-1.656 1.902-3.385 1.902-1.943 0-2.836-1.063-3.39-2.094l9.266-3.845-.48-1.126c-.896-2.207-2.984-4.05-5.747-4.05Zm.12 2.658c1.263 0 2.171.671 2.557 1.476l-6.187 2.586c-.267-2.002 1.63-4.062 3.630-4.062Z"></path>
                            <path fill="#34A853" d="M67.425 24.727h3.044V4.359h-3.044v20.368Z"></path>
                        </g>
                    </svg>
                <?php elseif ($source_key === 'appstore'): ?>
                    <svg class="maor-tab-icon" width="16" height="16" viewBox="0 0 121 36" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#a-287)">
                            <path fill="#111" d="M77.742 10.47c.734-.295 1.524-.47 2.318-.46 1.08-.052 2.186.12 3.158.61.6.311 1.144.745 1.527 1.307.445.62.673 1.386.672 2.148-.776.015-1.553.005-2.328.005-.046-.148-.067-.302-.103-.453a2 2 0 0 0-.775-1.083c-.56-.401-1.266-.551-1.946-.521-.72-.027-1.483.154-2.027.647a1.58 1.58 0 0 0-.545 1.296c-.011.457.255.883.627 1.132.6.405 1.319.571 2.012.738 1.307.310 2.682.524 3.83 1.27.648.408 1.163 1.046 1.352 1.796.329 1.250.098 2.692-.771 3.68-.63.732-1.52 1.195-2.442 1.44a8.62 8.62 0 0 1-2.596.232c-1.112-.055-2.244-.33-3.168-.973a3.806 3.806 0 0 1-1.644-2.587 6.077 6.077 0 0 0-.063-.51c.45-.04.905-.009 1.357-.018.349.006.7-.016 1.048.015.075.664.522 1.238 1.089 1.568.474.297 1.026.433 1.576.497.461.057.924.003 1.379-.077.538-.125 1.07-.36 1.452-.770.328-.367.51-.884.408-1.373-.053-.46-.365-.85-.752-1.086-.688-.43-1.496-.59-2.274-.777-1.102-.266-2.251-.468-3.235-1.063-.611-.350-1.134-.878-1.415-1.530-.424-1.023-.394-2.242.127-3.226.456-.862 1.260-1.502 2.152-1.874Zm-38.802-.237c.902-.034 1.806-.005 2.709-.015.093-.018.110.088.138.150 1.476 4.167 2.955 8.334 4.433 12.500.130.392.290.774.405 1.170-.864.010-1.728.006-2.592.003-.394-1.170-.770-2.347-1.160-3.518-.026-.085-.130-.054-.194-.063-1.552.003-3.103.001-4.655.001-.090 0-.178.005-.266.015-.387 1.189-.772 2.380-1.175 3.563-.847.002-1.694.020-2.540-.007 1.634-4.600 3.263-9.200 4.897-13.800Zm1.373 2.417c-.080.138-.128.290-.175.442-.590 1.822-1.210 3.635-1.783 5.462.390.020.780.005 1.170.010.909-.004 1.817.010 2.725-.007-.037-.184-.100-.360-.158-.538-.524-1.595-1.043-3.191-1.566-4.787-.067-.195-.115-.398-.214-.582Zm47.800-1.128c.376-.066.759-.020 1.138-.033.418.014.840-.035 1.254.037.011.787-.002 1.575.007 2.362.633.006 1.269-.015 1.902.011.004.610.002 1.221.001 1.832-.635.014-1.270 0-1.906.006.001 1.783-.003 3.565.003 5.348-.016.423.188.892.605 1.050.406.158.850.050 1.272.057.031.307.014.616.018.924-.005.300.017.600-.022.898-.714.125-1.444.060-2.162.016-.562-.114-1.187-.263-1.557-.740-.346-.390-.450-.920-.525-1.418-.040-.461-.016-.926-.023-1.390-.006-1.580.010-3.162-.008-4.743-.484-.014-.969.014-1.452-.016 0-.609-.030-1.222.016-1.830.478-.013.957-.001 1.436-.006.016-.788.008-1.577.003-2.365Zm8.692 2.463a4.714 4.714 0 0 1 1.324-.240c.696-.044 1.406.013 2.072.232.527.130.987.431 1.426.740.800.630 1.348 1.548 1.592 2.532.145.514.178 1.048.207 1.579.012 1.260-.239 2.581-1.019 3.600-.569.785-1.441 1.317-2.368 1.571-.250.078-.514.095-.767.160-.609.020-1.229.064-1.830-.062-1.097-.178-2.142-.765-2.807-1.665-.670-.878-.983-1.990-1.014-3.084-.065-1.210.133-2.475.788-3.514a4.72 4.72 0 0 1 .936-1.073c.445-.326.922-.633 1.460-.776Zm-.200 2.650a3.294 3.294 0 0 0-.492 1.310 6.162 6.162 0 0 0-.003 2.078c.140.703.439 1.429 1.037 1.863.281.220.627.352.983.387.605.074 1.273 0 1.763-.396.325-.226.551-.565.726-.915.292-.613.383-1.303.382-1.976.010-.916-.158-1.900-.773-2.616a2.098 2.098 0 0 0-1.737-.735 2.08 2.08 0 0 0-1.885 1Zm16.688-2.109c.907-.617 2.035-.865 3.121-.781 1.177.033 2.351.537 3.125 1.436.327.385.600.820.784 1.290.230.550.329 1.143.390 1.733.012.442.022.884.012 1.326-2.363.012-4.726-.005-7.088.009-.051.944.329 2.000 1.202 2.463.407.252.892.305 1.357.352.649.005 1.321-.196 1.792-.659.202-.186.319-.437.422-.687h2.216c-.107.855-.598 1.632-1.272 2.156a4.647 4.647 0 0 1-1.932.909c-.501.111-1.016.120-1.527.105-1.102-.006-2.225-.363-3.052-1.107-.816-.708-1.299-1.736-1.477-2.789-.183-1.218-.146-2.501.305-3.660.323-.836.882-1.586 1.622-2.096Zm1.746 1.283c-.832.373-1.347 1.265-1.385 2.160 1.580.007 3.160.010 4.740-.001-.022-.630-.237-1.268-.678-1.730-.678-.696-1.811-.833-2.677-.430Zm-52.654-.866c.775-.890 2.021-1.246 3.171-1.147.620.067 1.236.249 1.764.585.762.506 1.323 1.281 1.623 2.140.134.326.194.673.283 1.011.219 1.440.160 2.970-.460 4.309-.402.866-1.090 1.623-1.980 1.995-.540.213-1.120.322-1.700.302-.985.031-1.996-.360-2.650-1.109-.190-.200-.316-.448-.466-.678a.785.785 0 0 0-.030.190c.007 1.362 0 2.724.003 4.086-.003.252.010.505-.020.756-.780.014-1.562.005-2.343.005a2.146 2.146 0 0 1-.030-.349c.003-.723.001-1.445.002-2.168v-2.375c.023-1.084-.027-2.168.016-3.252-.034-.981-.008-1.963-.017-2.944.004-.553-.003-1.105.003-1.658.028-.250-.030-.501.027-.748.760-.002 1.521-.010 2.282.004.027.563-.010 1.128.020 1.691.216-.176.302-.456.502-.646Zm1.650.828c-.358.072-.713.206-1.002.436-.477.363-.764.920-.938 1.483a4.753 4.753 0 0 0 .122 2.921c.226.565.626 1.086 1.189 1.343.525.229 1.135.332 1.687.138.710-.207 1.245-.815 1.514-1.486.328-.847.370-1.790.190-2.675-.147-.668-.471-1.331-1.031-1.745a2.351 2.351 0 0 0-1.730-.415Zm45.742-1.991c.293-.017.585.028.874.077.006.715 0 1.432.003 2.148-.511-.126-1.051-.170-1.567-.054-.346.091-.694.238-.940.506-.422.436-.621 1.050-.616 1.650-.011 1.978.001 3.956-.006 5.933-.796.006-1.591.011-2.387-.002-.008-3.380-.007-6.762 0-10.143.759-.010 1.520 0 2.280-.004-.002.513 0 1.026-.001 1.540l.054.244c.113-.357.257-.712.496-1.006.429-.548 1.120-.865 1.810-.889Zm-58.896 1.167c.745-.859 1.935-1.228 3.048-1.157.900.056 1.807.394 2.450 1.043.867.840 1.292 2.042 1.407 3.222.079.567.056 1.143.027 1.712-.047.186-.055.378-.080.567-.150.803-.425 1.599-.915 2.260-.533.744-1.347 1.300-2.257 1.453-.440.117-.901.091-1.353.084-.870-.070-1.730-.460-2.300-1.134-.180-.193-.296-.433-.442-.650-.056.200-.017.409-.026.614-.008 1.473.016 2.947-.012 4.420-.787.015-1.576.011-2.363.002-.014-3.986-.002-7.973-.006-11.960.004-.508-.009-1.016.007-1.524a86.341 86.341 0 0 1 2.287-.003c.060.568-.009 1.142.039 1.710l.067-.078c.152-.185.248-.412.422-.581Zm1.644.825a2.446 2.446 0 0 0-.887.357c-.540.362-.864.965-1.047 1.574-.177.580-.181 1.192-.143 1.792.083.686.285 1.390.751 1.920.388.476 1.000.716 1.600.773.438.043.874-.091 1.258-.295.742-.466 1.172-1.308 1.280-2.161a4.927 4.927 0 0 0-.052-1.850c-.150-.643-.467-1.276-1.001-1.681a2.343 2.343 0 0 0-1.760-.429Z"></path>
                            <path fill="url(#b-288)" fill-rule="evenodd" d="M23.831 5.088a7.404 7.404 0 0 1 3.08 3.08l.074.14C27.648 9.592 28 10.941 28 14.446v7.108c0 3.633-.378 4.95-1.088 6.277a7.404 7.404 0 0 1-3.08 3.08l-.140.074C22.408 31.648 21.059 32 17.554 32h-7.108c-3.632 0-4.95-.378-6.277-1.088a7.404 7.404 0 0 1-3.08-3.08l-.074-.140C.365 26.432.013 25.108 0 21.743v-7.297c0-3.633.378-4.95 1.088-6.277a7.404 7.404 0 0 1 3.08-3.08l.140-.074c1.260-.65 2.584-1.002 5.949-1.015h7.297c3.633 0 4.95.378 6.277 1.088Z" clip-rule="evenodd"></path>
                            <path fill="#fff" fill-rule="evenodd" d="M15.78 13.516c.319.567.639 1.133.96 1.699.727 1.285 1.457 2.568 2.18 3.854.048.09.108.175.17.257.957-.014 1.915-.011 2.873-.001.566.007 1.084.497 1.1 1.08.035.584-.447 1.127-1.018 1.163-.565.044-1.134.03-1.7.01.33.603.678 1.197 1.014 1.798.205.338.328.767.173 1.151-.195.558-.87.889-1.413.655-.38-.137-.604-.502-.79-.844-.232-.41-.463-.822-.698-1.23-.206-.369-.413-.736-.62-1.103l-1.248-2.2c-.344-.616-.694-1.228-1.041-1.843-.225-.41-.49-.804-.626-1.256-.299-.92-.18-1.978.356-2.783.104-.14.217-.273.328-.407Zm-7.879 8.236c.889-.268 1.943-.034 2.518.727-.108.181-.214.364-.319.549-.316.53-.605 1.076-.937 1.597-.295.481-.916.762-1.464.574a1.28 1.28 0 0 1-.872-1.431c.243-.726.711-1.348 1.074-2.016ZM15.155 9.11c.486-.254 1.101-.045 1.408.396.246.388.231.894 0 1.287-.604 1.093-1.234 2.171-1.843 3.262-1.004 1.756-1.99 3.522-3.002 5.274 1.132-.003 2.263-.001 3.394 0 .602-.005 1.158.384 1.432.917.185.43.252.999-.02 1.394-3.332.01-6.664 0-9.997.005-.338-.007-.701-.005-.995-.202-.403-.245-.596-.756-.513-1.219a1.24 1.24 0 0 1 1.266-.896c.939-.004 1.879.004 2.818-.004.865-1.533 1.738-3.063 2.605-4.595.373-.65.738-1.306 1.113-1.955a84.02 84.02 0 0 1-1.134-1.997c-.221-.381-.237-.876 0-1.254.294-.453.918-.668 1.406-.416.539.253.727.875 1.03 1.348.322-.459.493-1.09 1.032-1.345Z" clip-rule="evenodd"></path>
                        </g>
                    </svg>
                <?php elseif ($source_key === 'manual'): ?>
                    <svg class="maor-tab-icon" width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-.001 5.5c1.104 0 2 .896 2 2s-.896 2-2 2-2-.896-2-2 .896-2 2-2zm.001 13.5c-3.111 0-5-2-5-4.5 0-1.5 2-2.5 5-2.5 3 0 5 1 5 2.5 0 2.5-1.889 4.5-5 4.5z"/>
                    </svg>
                <?php endif; ?>
                
                <span class="maor-tab-text"><?php echo $source_name; ?></span>
                
                <?php if (isset($average_ratings[$source_key]) && $average_ratings[$source_key] > 0): ?>
                    <span class="maor-tab-rating">(<?php echo $average_ratings[$source_key]; ?>)</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}


// Update the display function to include the new format
function maor_display_reviews_shortcode($atts) {
    $options = get_option('maor_options_array');

    $atts = shortcode_atts(array(
        'limit'        => isset($options['review_limit']) ? $options['review_limit'] : 10,
        'layout'       => isset($options['layout_style']) ? $options['layout_style'] : 'grid', // grid | carousel | badge
        'show_avatars' => isset($options['show_avatars']) ? (bool) $options['show_avatars'] : true,
        'show_date'    => isset($options['show_date']) ? (bool) $options['show_date'] : true,
        'show_source'  => isset($options['show_source']) ? (bool) $options['show_source'] : true,
        'source'       => 'all',
        'offset'       => 0,
        'load_more'    => false,
        'tabs_in_drawer' => false,
        // layout tuning
        'per_row'      => 5,   // grid: 5 cards per row
        'per_view'     => 5    // carousel: 5 cards visible per slide
    ), $atts);

    $reviews_all = maor_get_all_reviews($atts['source']);

    if (empty($reviews_all)) {
        return '<p class="maor-error">No reviews found. Please check your settings.</p>';
    }

    // Handle AJAX "load more" for grid
    if (!empty($atts['load_more'])) {
        $offset = max(0, intval($atts['offset']));
        $limit  = max(1, intval($atts['limit']));
        $chunk  = array_slice($reviews_all, $offset, $limit);

        if (empty($chunk)) {
            return 'no_more';
        }

        ob_start();
        foreach ($chunk as $review) {
            echo maor_generate_single_review_html($review, $atts);
        }
        return ob_get_clean();
    }

    // Non-AJAX initial render
    $total_reviews = count($reviews_all);
    $limit         = max(1, intval($atts['limit']));
    $initial_chunk = array_slice($reviews_all, 0, $limit);

    // Layout switch
    $layout = strtolower($atts['layout']);

    // CARD BADGE
    if ($layout === 'badge') {
        // top badge
        $badge_html = maor_generate_badge_layout($reviews_all, $atts);

        // slide-in drawer markup (left, one column list like grid but 1 per row)
        ob_start(); ?>
<div class="maor-drawer-overlay" style="display:none;"></div>
<aside class="maor-drawer" data-visible="false" aria-hidden="true">
  <div class="maor-drawer-header">
    <h3>Customer Reviews</h3>
    <button class="maor-drawer-close" type="button" aria-label="Close">✕</button>
  </div>

  <div class="maor-drawer-body">
    <div class="maor-tabbed-reviews"
         data-limit="<?php echo esc_attr($limit); ?>"
         data-layout="grid"
         data-show-avatars="<?php echo $atts['show_avatars'] ? '1' : '0'; ?>"
         data-show-date="<?php echo $atts['show_date'] ? '1' : '0'; ?>"
         data-show-source="<?php echo $atts['show_source'] ? '1' : '0'; ?>"
         data-per-row="1"
         data-per-view="1">

      <?php if (!empty($atts['tabs_in_drawer'])): ?>
        <?php echo maor_generate_review_tabs(); ?>
      <?php endif; ?>

      <div class="maor-reviews-content">
        <div class="maor-reviews-container maor-grid maor-one-col"
             data-source="<?php echo esc_attr($atts['source']); ?>"
             data-offset="<?php echo esc_attr($limit); ?>"
             data-total="<?php echo esc_attr($total_reviews); ?>"
             data-limit="<?php echo esc_attr($limit); ?>">
          <?php
          if (!empty($initial_chunk)) {
            foreach ($initial_chunk as $review) {
              echo maor_generate_single_review_html($review, $atts);
            }
            if ($total_reviews > $limit) {
              echo '<div class="maor-load-more-container"><button class="maor-load-more-btn">Load More Reviews</button></div>';
            }
          }
          ?>
        </div>
      </div>
    </div>
  </div>
</aside>
<script>
document.addEventListener('click', function(e){
  const badge = e.target.closest('.maor-reviews-container.maor-badge');
  const openBtn = e.target.closest('.maor-badge, .maor-badge-source, .maor-badge-rating, .maor-badge-stars, .maor-badge-count');
  if (badge && openBtn) {
    const overlay = document.querySelector('.maor-drawer-overlay');
    const drawer  = document.querySelector('.maor-drawer');
    if (overlay && drawer) {
      overlay.style.display = 'block';
      drawer.setAttribute('data-visible', 'true');
      drawer.setAttribute('aria-hidden', 'false');
    }
  }
  if (e.target.closest('.maor-drawer-close') || e.target.classList.contains('maor-drawer-overlay')) {
    const overlay = document.querySelector('.maor-drawer-overlay');
    const drawer  = document.querySelector('.maor-drawer');
    if (overlay && drawer) {
      overlay.style.display = 'none';
      drawer.setAttribute('data-visible', 'false');
      drawer.setAttribute('aria-hidden', 'true');
    }
  }
});
</script>
<?php
$drawer_html = ob_get_clean();

return $badge_html . $drawer_html;

    }

    // CAROUSEL
    if ($layout === 'carousel') {
        ob_start();
        ?>
        <div class="maor-carousel" data-per-view="<?php echo esc_attr(intval($atts['per_view'])); ?>">
            <button class="maor-carousel-nav maor-carousel-prev" type="button" aria-label="Previous">‹</button>
            <div class="maor-carousel-viewport">
                <div class="maor-carousel-track">
                    <?php foreach ($reviews_all as $review): ?>
                        <div class="maor-carousel-slide">
                            <?php echo maor_generate_single_review_html($review, $atts); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="maor-carousel-nav maor-carousel-next" type="button" aria-label="Next">›</button>
        </div>
        <?php
        return ob_get_clean();
    }

    // GRID (default)
    $html  = '<div class="maor-reviews-container maor-grid"';
    $html .= ' data-source="' . esc_attr($atts['source']) . '"';
    $html .= ' data-offset="' . esc_attr($limit) . '"';
    $html .= ' data-total="' . esc_attr($total_reviews) . '"';
    $html .= ' data-limit="' . esc_attr($limit) . '"';
    $html .= ' data-per-row="' . esc_attr(intval($atts['per_row'])) . '"';
    $html .= '>';

    if (!empty($initial_chunk)) {
        foreach ($initial_chunk as $review) {
            $html .= maor_generate_single_review_html($review, $atts);
        }
        if ($total_reviews > $limit) {
            $html .= '<div class="maor-load-more-container"><button class="maor-load-more-btn">Load More Reviews</button></div>';
        }
    } else {
        $html .= '<p>No reviews to display.</p>';
    }

    $html .= '</div>';
    return $html;
}

// add_shortcode('my_reviews', 'maor_display_reviews_shortcode');


// Function to generate single review HTML
function maor_generate_single_review_html($review, $atts) {
    $html = '<div class="maor-review">';
    
    // Review header with author info
    $html .= '<div class="maor-review-header">';
    
    if ($atts['show_avatars']) {
        $html .= '<div class="maor-avatar">';
        $html .= '<img src="' . esc_url($review->profile_photo_url ?? $review->avatar_url ?? plugin_dir_url(__FILE__) . 'images/default-avatar.png') . '" alt="' . esc_attr($review->author_name) . '">';
        $html .= '</div>';
    }
    
    $html .= '<div class="maor-author-info">';
    $html .= '<div class="maor-author-verified">';
    $html .= '<h4 class="maor-author">' . esc_html($review->author_name) . '</h4>';
    $html .= '<svg class="maor-verified-icon" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.757.236a.35.35 0 0 1 .486 0l1.106 1.07a.35.35 0 0 0 .329.089l1.493-.375a.35.35 0 0 1 .422.244l.422 1.48a.35.35 0 0 0 .24.24l1.481.423a.35.35 0 0 1 .244.422l-.375 1.493a.35.35 0 0 0 .088.329l1.071 1.106a.35.35 0 0 1 0 .486l-1.07 1.106a.35.35 0 0 0-.089.329l.375 1.493a.35.35 0 0 1-.244.422l-1.48.422a.35.35 0 0 0-.24.24l-.423 1.481a.35.35 0 0 1-.422.244l-1.493-.375a.35.35 0 0 0-.329.088l-1.106 1.071a.35.35 0 0 1-.486 0l-1.106-1.07a.35.35 0 0 0-.329-.089l-1.493.375a.35.35 0 0 1-.422-.244l-.422-1.48a.35.35 0 0 0-.24-.24l-1.481-.423a.35.35 0 0 1-.244-.422l.375-1.493a.35.35 0 0 0-.088-.329L.236 7.243a.35.35 0 0 1 0-.486l1.07-1.106a.35.35 0 0 0 .089-.329L1.02 3.829a.35.35 0 0 1 .244-.422l1.48-.422a.35.35 0 0 0 .24-.24l.423-1.481a.35.35 0 0 1 .422-.244l1.493.375a.35.35 0 0 0 .329-.088L6.757.236Z" fill="#197BFF"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M9.065 4.85a.644.644 0 0 1 .899 0 .615.615 0 0 1 .053.823l-.053.059L6.48 9.15a.645.645 0 0 1-.84.052l-.06-.052-1.66-1.527a.616.616 0 0 1 0-.882.645.645 0 0 1 .84-.052l.06.052 1.21 1.086 3.034-2.978Z" fill="#fff"></path></svg>';    
    $html .= '</div>';
    
    if ($atts['show_date'] && isset($review->time)) {
        $time = is_numeric($review->time) ? $review->time : strtotime($review->time);
        $html .= '<p class="maor-date">' . human_time_diff($time, current_time('timestamp')) . ' ago</p>';
    }
    
    $html .= '</div>'; // .maor-author-info
    $html .= '</div>'; // .maor-review-header
    
    // Rating stars
    $html .= '<div class="maor-rating">' . maor_generate_stars($review->rating) . '</div>';
    
    // Review title and text
    $html .= '<div class="maor-review-content">';
    
    // Extract first few words as title if no explicit title exists
    $review_text = $review->text;
    $words = explode(' ', $review_text);
    $title = '';
    
    if (count($words) > 5) {
        $title = implode(' ', array_slice($words, 0, 5)) . '...';
        $review_text = implode(' ', array_slice($words, 5));
    } else {
        $title = $review_text;
        $review_text = '';
    }
    
    $html .= '<h5 class="maor-review-title">' . esc_html($title) . '</h5>';
    
    if (!empty($review_text)) {
        $html .= '<p class="maor-text">' . esc_html($review_text) . '</p>';
    }
    
    $html .= '</div>'; // .maor-review-content
    
    // Review source
    if ($atts['show_source'] && isset($review->source)) {
        $html .= '<div class="maor-review-source">';
        
        if ($review->source === 'Google') {
            $html .= '<svg class="maor-source-icon" width="85" height="36" viewBox="0 0 85 36" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#a-259)">
                    <path fill="#4285F4" d="M20.778 13.43h-9.862v2.927h6.994c-.345 4.104-3.76 5.854-6.982 5.854-4.123 0-7.72-3.244-7.72-7.791 0-4.43 3.429-7.841 7.73-7.841 3.317 0 5.272 2.115 5.272 2.115l2.049-2.122s-2.63-2.928-7.427-2.928C4.725 3.644 0 8.8 0 14.367c0 5.457 4.445 10.777 10.988 10.777 5.756 0 9.969-3.942 9.969-9.772 0-1.23-.179-1.941-.179-1.941Z"></path>
                    <path fill="#EA4335" d="M28.857 11.312c-4.047 0-6.947 3.163-6.947 6.853 0 3.744 2.813 6.966 6.994 6.966 3.786 0 6.887-2.893 6.887-6.886 0-4.576-3.607-6.933-6.934-6.933Zm.04 2.714c1.99 0 3.876 1.609 3.876 4.201 0 2.538-1.878 4.192-3.885 4.192-2.205 0-3.945-1.766-3.945-4.212 0-2.394 1.718-4.181 3.954-4.181Z"></path>
                    <path fill="#FBBC05" d="M43.965 11.312c-4.046 0-6.946 3.163-6.946 6.853 0 3.744 2.813 6.966 6.994 6.966 3.785 0 6.886-2.893 6.886-6.886 0-4.576-3.607-6.933-6.934-6.933Zm.04 2.714c1.99 0 3.876 1.609 3.876 4.201 0 2.538-1.877 4.192-3.885 4.192-2.205 0-3.945-1.766-3.945-4.212 0-2.394 1.718-4.181 3.955-4.181Z"></path>
                    <path fill="#4285F4" d="M58.783 11.319c-3.714 0-6.634 3.253-6.634 6.904 0 4.16 3.385 6.918 6.57 6.918 1.97 0 3.017-.782 3.79-1.68v1.363c0 2.384-1.448 3.812-3.633 3.812-2.11 0-3.169-1.57-3.537-2.46l-2.656 1.11c.943 1.992 2.839 4.07 6.215 4.07 3.693 0 6.508-2.327 6.508-7.205V11.734h-2.897v1.17c-.89-.96-2.109-1.585-3.726-1.585Zm.269 2.709c1.821 0 3.69 1.554 3.69 4.210 0 2.699-1.865 4.187-3.73 4.187-1.98 0-3.823-1.608-3.823-4.161 0-2.653 1.914-4.236 3.863-4.236Z"></path>
                    <path fill="#EA4335" d="M78.288 11.302c-3.504 0-6.446 2.788-6.446 6.901 0 4.353 3.28 6.934 6.782 6.934 2.924 0 4.718-1.6 5.789-3.032l-2.389-1.59c-.62.962-1.656 1.902-3.385 1.902-1.943 0-2.836-1.063-3.39-2.094l9.266-3.845-.48-1.126c-.896-2.207-2.984-4.05-5.747-4.05Zm.12 2.658c1.263 0 2.171.671 2.557 1.476l-6.187 2.586c-.267-2.002 1.63-4.062 3.630-4.062Z"></path>
                    <path fill="#34A853" d="M67.425 24.727h3.044V4.359h-3.044v20.368Z"></path>
                </g>
            </svg>';
        } elseif ($review->source === 'App Store') {
            $html .= '<svg class="maor-source-icon" width="121" height="36" viewBox="0 0 121 36" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#a-287)">
                    <path fill="#111" d="M77.742 10.47c.734-.295 1.524-.47 2.318-.46 1.08-.052 2.186.12 3.158.61.6.311 1.144.745 1.527 1.307.445.62.673 1.386.672 2.148-.776.015-1.553.005-2.328.005-.046-.148-.067-.302-.103-.453a2 2 0 0 0-.775-1.083c-.56-.401-1.266-.551-1.946-.521-.72-.027-1.483.154-2.027.647a1.58 1.58 0 0 0-.545 1.296c-.011.457.255.883.627 1.132.6.405 1.319.571 2.012.738 1.307.310 2.682.524 3.83 1.27.648.408 1.163 1.046 1.352 1.796.329 1.250.098 2.692-.771 3.68-.63.732-1.52 1.195-2.442 1.44a8.62 8.62 0 0 1-2.596.232c-1.112-.055-2.244-.33-3.168-.973a3.806 3.806 0 0 1-1.644-2.587 6.077 6.077 0 0 0-.063-.51c.45-.04.905-.009 1.357-.018.349.006.7-.016 1.048.015.075.664.522 1.238 1.089 1.568.474.297 1.026.433 1.576.497.461.057.924.003 1.379-.077.538-.125 1.07-.36 1.452-.770.328-.367.51-.884.408-1.373-.053-.46-.365-.85-.752-1.086-.688-.43-1.496-.59-2.274-.777-1.102-.266-2.251-.468-3.235-1.063-.611-.350-1.134-.878-1.415-1.530-.424-1.023-.394-2.242.127-3.226.456-.862 1.260-1.502 2.152-1.874Zm-38.802-.237c.902-.034 1.806-.005 2.709-.015.093-.018.110.088.138.150 1.476 4.167 2.955 8.334 4.433 12.500.130.392.290.774.405 1.170-.864.010-1.728.006-2.592.003-.394-1.170-.770-2.347-1.160-3.518-.026-.085-.130-.054-.194-.063-1.552.003-3.103.001-4.655.001-.090 0-.178.005-.266.015-.387 1.189-.772 2.380-1.175 3.563-.847.002-1.694.020-2.540-.007 1.634-4.600 3.263-9.200 4.897-13.800Zm1.373 2.417c-.080.138-.128.290-.175.442-.590 1.822-1.210 3.635-1.783 5.462.390.020.780.005 1.170.010.909-.004 1.817.010 2.725-.007-.037-.184-.100-.360-.158-.538-.524-1.595-1.043-3.191-1.566-4.787-.067-.195-.115-.398-.214-.582Zm47.800-1.128c.376-.066.759-.020 1.138-.033.418.014.840-.035 1.254.037.011.787-.002 1.575.007 2.362.633.006 1.269-.015 1.902.011.004.610.002 1.221.001 1.832-.635.014-1.270 0-1.906.006.001 1.783-.003 3.565.003 5.348-.016.423.188.892.605 1.050.406.158.850.050 1.272.057.031.307.014.616.018.924-.005.300.017.600-.022.898-.714.125-1.444.060-2.162.016-.562-.114-1.187-.263-1.557-.740-.346-.390-.450-.920-.525-1.418-.040-.461-.016-.926-.023-1.390-.006-1.580.010-3.162-.008-4.743-.484-.014-.969.014-1.452-.016 0-.609-.030-1.222.016-1.830.478-.013.957-.001 1.436-.006.016-.788.008-1.577.003-2.365Zm8.692 2.463a4.714 4.714 0 0 1 1.324-.240c.696-.044 1.406.013 2.072.232.527.130.987.431 1.426.740.800.630 1.348 1.548 1.592 2.532.145.514.178 1.048.207 1.579.012 1.260-.239 2.581-1.019 3.600-.569.785-1.441 1.317-2.368 1.571-.250.078-.514.095-.767.160-.609.020-1.229.064-1.830-.062-1.097-.178-2.142-.765-2.807-1.665-.670-.878-.983-1.990-1.014-3.084-.065-1.210.133-2.475.788-3.514a4.72 4.72 0 0 1 .936-1.073c.445-.326.922-.633 1.460-.776Zm-.200 2.650a3.294 3.294 0 0 0-.492 1.310 6.162 6.162 0 0 0-.003 2.078c.140.703.439 1.429 1.037 1.863.281.220.627.352.983.387.605.074 1.273 0 1.763-.396.325-.226.551-.565.726-.915.292-.613.383-1.303.382-1.976.010-.916-.158-1.900-.773-2.616a2.098 2.098 0 0 0-1.737-.735 2.08 2.08 0 0 0-1.885 1Zm16.688-2.109c.907-.617 2.035-.865 3.121-.781 1.177.033 2.351.537 3.125 1.436.327.385.600.820.784 1.290.230.550.329 1.143.390 1.733.012.442.022.884.012 1.326-2.363.012-4.726-.005-7.088.009-.051.944.329 2.000 1.202 2.463.407.252.892.305 1.357.352.649.005 1.321-.196 1.792-.659.202-.186.319-.437.422-.687h2.216c-.107.855-.598 1.632-1.272 2.156a4.647 4.647 0 0 1-1.932.909c-.501.111-1.016.120-1.527.105-1.102-.006-2.225-.363-3.052-1.107-.816-.708-1.299-1.736-1.477-2.789-.183-1.218-.146-2.501.305-3.660.323-.836.882-1.586 1.622-2.096Zm1.746 1.283c-.832.373-1.347 1.265-1.385 2.160 1.580.007 3.160.010 4.740-.001-.022-.630-.237-1.268-.678-1.730-.678-.696-1.811-.833-2.677-.430Zm-52.654-.866c.775-.890 2.021-1.246 3.171-1.147.620.067 1.236.249 1.764.585.762.506 1.323 1.281 1.623 2.140.134.326.194.673.283 1.011.219 1.440.160 2.970-.460 4.309-.402.866-1.090 1.623-1.980 1.995-.540.213-1.120.322-1.700.302-.985.031-1.996-.360-2.650-1.109-.190-.200-.316-.448-.466-.678a.785.785 0 0 0-.030.190c.007 1.362 0 2.724.003 4.086-.003.252.010.505-.020.756-.780.014-1.562.005-2.343.005a2.146 2.146 0 0 1-.030-.349c.003-.723.001-1.445.002-2.168v-2.375c.023-1.084-.027-2.168.016-3.252-.034-.981-.008-1.963-.017-2.944.004-.553-.003-1.105.003-1.658.028-.250-.030-.501.027-.748.760-.002 1.521-.010 2.282.004.027.563-.010 1.128.020 1.691.216-.176.302-.456.502-.646Zm1.650.828c-.358.072-.713.206-1.002.436-.477.363-.764.920-.938 1.483a4.753 4.753 0 0 0 .122 2.921c.226.565.626 1.086 1.189 1.343.525.229 1.135.332 1.687.138.710-.207 1.245-.815 1.514-1.486.328-.847.370-1.790.190-2.675-.147-.668-.471-1.331-1.031-1.745a2.351 2.351 0 0 0-1.730-.415Zm45.742-1.991c.293-.017.585.028.874.077.006.715 0 1.432.003 2.148-.511-.126-1.051-.170-1.567-.054-.346.091-.694.238-.940.506-.422.436-.621 1.050-.616 1.650-.011 1.978.001 3.956-.006 5.933-.796.006-1.591.011-2.387-.002-.008-3.380-.007-6.762 0-10.143.759-.010 1.520 0 2.280-.004-.002.513 0 1.026-.001 1.540l.054.244c.113-.357.257-.712.496-1.006.429-.548 1.120-.865 1.810-.889Zm-58.896 1.167c.745-.859 1.935-1.228 3.048-1.157.900.056 1.807.394 2.450 1.043.867.840 1.292 2.042 1.407 3.222.079.567.056 1.143.027 1.712-.047.186-.055.378-.080.567-.150.803-.425 1.599-.915 2.260-.533.744-1.347 1.300-2.257 1.453-.440.117-.901.091-1.353.084-.870-.070-1.730-.460-2.300-1.134-.180-.193-.296-.433-.442-.650-.056.200-.017.409-.026.614-.008 1.473.016 2.947-.012 4.420-.787.015-1.576.011-2.363.002-.014-3.986-.002-7.973-.006-11.960.004-.508-.009-1.016.007-1.524a86.341 86.341 0 0 1 2.287-.003c.060.568-.009 1.142.039 1.710l.067-.078c.152-.185.248-.412.422-.581Zm1.644.825a2.446 2.446 0 0 0-.887.357c-.540.362-.864.965-1.047 1.574-.177.580-.181 1.192-.143 1.792.083.686.285 1.390.751 1.920.388.476 1.000.716 1.600.773.438.043.874-.091 1.258-.295.742-.466 1.172-1.308 1.280-2.161a4.927 4.927 0 0 0-.052-1.850c-.150-.643-.467-1.276-1.001-1.681a2.343 2.343 0 0 0-1.760-.429Z"></path>
                    <path fill="url(#b-288)" fill-rule="evenodd" d="M23.831 5.088a7.404 7.404 0 0 1 3.08 3.08l.074.140C27.648 9.592 28 10.941 28 14.446v7.108c0 3.633-.378 4.95-1.088 6.277a7.404 7.404 0 0 1-3.08 3.080l-.140.074C22.408 31.648 21.059 32 17.554 32h-7.108c-3.632 0-4.95-.378-6.277-1.088a7.404 7.404 0 0 1-3.080-3.080l-.074-.140C.365 26.432.013 25.108 0 21.743v-7.297c0-3.633.378-4.95 1.088-6.277a7.404 7.404 0 0 1 3.080-3.080l.140-.074c1.260-.650 2.584-1.002 5.949-1.015h7.297c3.633 0 4.95.378 6.277 1.088Z" clip-rule="evenodd"></path>
                    <path fill="#fff" fill-rule="evenodd" d="M15.78 13.516c.319.567.639 1.133.96 1.699.727 1.285 1.457 2.568 2.18 3.854.048.090.108.175.170.257.957-.014 1.915-.011 2.873-.001.566.007 1.084.497 1.1 1.08.035.584-.447 1.127-1.018 1.163-.565.044-1.134.03-1.7.01.33.603.678 1.197 1.014 1.798.205.338.328.767.173 1.151-.195.558-.87.889-1.413.655-.38-.137-.604-.502-.79-.844-.232-.41-.463-.822-.698-1.23-.206-.369-.413-.736-.62-1.103l-1.248-2.2c-.344-.616-.694-1.228-1.041-1.843-.225-.41-.49-.804-.626-1.256-.299-.92-.18-1.978.356-2.783.104-.14.217-.273.328-.407Zm-7.879 8.236c.889-.268 1.943-.034 2.518.727-.108.181-.214.364-.319.549-.316.53-.605 1.076-.937 1.597-.295.481-.916.762-1.464.574a1.28 1.28 0 0 1-.872-1.431c.243-.726.711-1.348 1.074-2.016ZM15.155 9.11c.486-.254 1.101-.045 1.408.396.246.388.231.894 0 1.287-.604 1.093-1.234 2.171-1.843 3.262-1.004 1.756-1.99 3.522-3.002 5.274 1.132-.003 2.263-.001 3.394 0 .602-.005 1.158.384 1.432.917.185.43.252.999-.02 1.394-3.332.01-6.664 0-9.997.005-.338-.007-.701-.005-.995-.202-.403-.245-.596-.756-.513-1.219a1.24 1.24 0 0 1 1.266-.896c.939-.004 1.879.004 2.818-.004.865-1.533 1.738-3.063 2.605-4.595.373-.65.738-1.306 1.113-1.955a84.02 84.02 0 0 1-1.134-1.997c-.221-.381-.237-.876 0-1.254.294-.453.918-.668 1.406-.416.539.253.727.875 1.03 1.348.322-.459.493-1.09 1.032-1.345Z" clip-rule="evenodd"></path>
                </g>
            </svg>';
        }
        
        $html .= '<span class="maor-source-text">' . esc_html($review->source) . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>'; // .maor-review
    
    return $html;
}

// AJAX handler for loading more reviews
function maor_load_more_reviews() {
    check_ajax_referer('maor_ajax_nonce', 'nonce');
    
    $source = sanitize_text_field($_POST['source']);
    $offset = intval($_POST['offset']);
    $limit = intval($_POST['limit']);
    
    $reviews_html = maor_display_reviews_shortcode(array(
        'source' => $source,
        'offset' => $offset,
        'limit' => $limit,
        'load_more' => true
    ));
    
    if ($reviews_html === 'no_more') {
        wp_die('no_more');
    }
    
    wp_die($reviews_html);
}
add_action('wp_ajax_maor_load_more', 'maor_load_more_reviews');
add_action('wp_ajax_nopriv_maor_load_more', 'maor_load_more_reviews');

function maor_filter_reviews() {
    check_ajax_referer('maor_ajax_nonce', 'nonce');

    $source = sanitize_text_field($_POST['source'] ?? 'all');

    $atts = array(
        'source'       => $source,
        'limit'        => isset($_POST['limit']) ? intval($_POST['limit']) : null,
        'layout'       => isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : null,
        'show_avatars' => isset($_POST['show_avatars']) ? (bool) intval($_POST['show_avatars']) : null,
        'show_date'    => isset($_POST['show_date']) ? (bool) intval($_POST['show_date']) : null,
        'show_source'  => isset($_POST['show_source']) ? (bool) intval($_POST['show_source']) : null,
        'per_row'      => isset($_POST['per_row']) ? intval($_POST['per_row']) : null,
        'per_view'     => isset($_POST['per_view']) ? intval($_POST['per_view']) : null,
    );

    // remove nulls so shortcode defaults still work
    $atts = array_filter($atts, function($v){ return $v !== null; });

    echo maor_display_reviews_shortcode($atts);
    wp_die();
}

add_action('wp_ajax_maor_filter_reviews', 'maor_filter_reviews');
add_action('wp_ajax_nopriv_maor_filter_reviews', 'maor_filter_reviews');

// Function to generate badge layout
function maor_generate_badge_layout($reviews, $atts) {
    // Calculate average rating
    $total_rating = 0;
    $review_count = count($reviews);
    
    foreach ($reviews as $review) {
        $total_rating += $review->rating;
    }
    
    $average_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;
    
    // Count reviews by source
    $source_counts = array();
    foreach ($reviews as $review) {
        $source = $review->source;
        if (!isset($source_counts[$source])) {
            $source_counts[$source] = 0;
        }
        $source_counts[$source]++;
    }
    
    $html = '<div class="maor-reviews-container maor-badge">';
    $html .= '<h3 class="maor-title">Customer Reviews</h3>';
    $html .= '<div class="maor-badge-rating">'
       . '<span class="maor-badge-score">' . esc_html($average_rating) . '</span>'
       . '</div>';
    $html .= '<div class="maor-badge-stars">' . maor_generate_stars($average_rating) . '</div>';
    $html .= '<div class="maor-badge-count">Based on ' . esc_html($review_count) . ' reviews</div>';
    
    // Source logos
    // Source chips (logo + count), no text label
if ($atts['show_source'] && !empty($source_counts)) {
    $plugin_url = plugin_dir_url(__FILE__);
    $html .= '<div class="maor-badge-chips">';
    foreach ($source_counts as $source => $count) {
        $logo = '';
        if ($source === 'App Store') {
            $logo = $plugin_url . 'images/appstore-full.png';
        } elseif ($source === 'Google') {
            $logo = $plugin_url . 'images/google-full.png';
        }
        if ($logo) {
            $html .= '<span class="maor-chip" aria-label="' . esc_attr($source) . '">'
                   . '<img class="maor-chip-logo" src="' . esc_url($logo) . '" alt="' . esc_attr($source) . '" loading="lazy" />'
                   . '<span class="maor-chip-count">' . intval($count) . '</span>'
                   . '</span>';
        }
    }
    $html .= '</div>';
}

    
    $html .= '</div>';
    
    return $html;
}


// Function to get reviews from all sources
function maor_get_all_reviews($source = 'all') {
    $reviews = array();
    $options = get_option('maor_options_array');
    
    // Get Google reviews
    if ($source === 'all' || $source === 'google') {
        $google_reviews = maor_get_google_reviews();
        if ($google_reviews) {
            $reviews = array_merge($reviews, $google_reviews);
        }
    }
    
    // Get App Store reviews (placeholder - you'll need to implement this)
    if ($source === 'all' || $source === 'appstore') {
        $appstore_reviews = maor_get_appstore_reviews();
        if ($appstore_reviews) {
            $reviews = array_merge($reviews, $appstore_reviews);
        }
    }
    
    // Get manual reviews
    if ($source === 'all' || $source === 'manual') {
        $manual_reviews = maor_get_manual_reviews();
        if ($manual_reviews) {
            $reviews = array_merge($reviews, $manual_reviews);
        }
    }
    
    // Shuffle reviews for a mixed display
    shuffle($reviews);
    
    return $reviews;
}

// Function to get Google reviews
function maor_get_google_reviews() {
    $options = get_option('maor_options_array');
    $api_key = isset($options['google_api_key']) ? $options['google_api_key'] : '';
    $place_id = isset($options['google_place_id']) ? $options['google_place_id'] : '';
    
    if (empty($api_key) || empty($place_id)) {
        return array();
    }

    // Check for cached reviews
    $transient_key = 'maor_google_reviews_' . md5($place_id);
    $cached_reviews = get_transient($transient_key);

    if (false !== $cached_reviews) {
        return $cached_reviews;
    }

    // Fetch reviews from Google
    $api_url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid=' . urlencode($place_id) . '&fields=name,rating,reviews,user_ratings_total&key=' . urlencode($api_key);
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return array();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (isset($data->result->reviews)) {
        $reviews = $data->result->reviews;
        
        // Add source information
        foreach ($reviews as $review) {
            $review->source = 'Google';
        }
        
        // Save the fresh reviews in our cache for 12 hours
        // set_transient($transient_key, $reviews, 12 * HOUR_IN_SECONDS);
        set_transient($transient_key, $reviews, 5 * MINUTE_IN_SECONDS);
        
        return $reviews;
    }
    
    return array();
}
// Add this function to clear cache when settings are updated
function maor_clear_cache_on_save($old_value, $value, $option) {
    if ($option === 'maor_options_array') {
        // Clear Google reviews cache
        if (isset($value['google_place_id'])) {
            $transient_key = 'maor_google_reviews_' . md5($value['google_place_id']);
            delete_transient($transient_key);
        }
        
        // Clear App Store reviews cache
        if (isset($value['appstore_app_id'])) {
            $transient_key = 'maor_appstore_reviews_' . md5($value['appstore_app_id']);
            delete_transient($transient_key);
        }
    }
}
add_action('update_option_maor_options_array', 'maor_clear_cache_on_save', 10, 3);


// Function to get App Store reviews (placeholder - needs implementation)
// Function to get real App Store reviews
function maor_get_appstore_reviews() {
    $options = get_option('maor_options_array');
    $app_id = isset($options['appstore_app_id']) ? $options['appstore_app_id'] : '';
    
    if (empty($app_id)) {
        return array();
    }
    
    // Check for cached reviews
    $transient_key = 'maor_appstore_reviews_' . md5($app_id);
    $cached_reviews = get_transient($transient_key);
    
    if (false !== $cached_reviews) {
        return $cached_reviews;
    }
    
    // Fetch reviews from App Store using iTunes API
    $country_code = 'us'; // You can make this configurable if needed
    $api_url = "https://itunes.apple.com/{$country_code}/rss/customerreviews/id={$app_id}/sortBy=mostRecent/json";
    
    $response = wp_remote_get($api_url);
    
    if (is_wp_error($response)) {
        return array();
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    
    $reviews = array();
    
    if (isset($data->feed->entry)) {
        // Skip the first entry as it's app info, not a review
        for ($i = 1; $i < count($data->feed->entry); $i++) {
            $entry = $data->feed->entry[$i];
            
            if (isset($entry->author->name) && isset($entry->{'im:rating'})) {
                $review = (object) array(
                    'author_name' => $entry->author->name->label,
                    'rating' => (int) $entry->{'im:rating'}->label,
                    'text' => isset($entry->content->label) ? $entry->content->label : '',
                    'time' => isset($entry->updated->label) ? strtotime($entry->updated->label) : time(),
                    'source' => 'App Store'
                );
                
                $reviews[] = $review;
            }
        }
        
        // Cache the reviews for 12 hours
        set_transient($transient_key, $reviews, 12 * HOUR_IN_SECONDS);
    }
    
    return $reviews;
}

// Function to get manual reviews
function maor_get_manual_reviews() {
    $options = get_option('maor_options_array');
    $manual_reviews_json = isset($options['manual_reviews']) ? $options['manual_reviews'] : '';
    
    if (empty($manual_reviews_json)) {
        return array();
    }
    
    $manual_reviews = json_decode($manual_reviews_json);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array();
    }
    
    return $manual_reviews;
}

// Helper function to generate star ratings
// Helper function to generate star ratings with custom SVG
function maor_generate_stars($rating) {
    $stars = '';
    $full_stars = floor($rating);
    $has_half_star = ($rating - $full_stars) >= 0.5;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full_stars) {
            // Full star
            $stars .= '<svg class="maor-star full" viewBox="0 0 14 14" width="14" height="14"><path d="M6.82617 11.442L3.54617 13.166C3.46353 13.2093 3.3704 13.2287 3.27732 13.2219C3.18425 13.2151 3.09494 13.1824 3.0195 13.1274C2.94406 13.0725 2.8855 12.9975 2.85045 12.911C2.8154 12.8245 2.80526 12.7299 2.82117 12.638L3.44817 8.98798C3.46192 8.908 3.456 8.82587 3.43091 8.74869C3.40582 8.67151 3.36232 8.6016 3.30417 8.54499L0.650168 5.95899C0.583317 5.89388 0.53602 5.81136 0.51363 5.72076C0.491239 5.63017 0.494647 5.53512 0.52347 5.44637C0.552292 5.35761 0.605378 5.27869 0.676721 5.21854C0.748065 5.15838 0.834818 5.1194 0.927168 5.10599L4.59317 4.57299C4.67344 4.56146 4.7497 4.53059 4.81537 4.48303C4.88105 4.43547 4.93418 4.37265 4.97017 4.29999L6.61017 0.977985C6.65153 0.894518 6.7154 0.824266 6.79455 0.775151C6.87371 0.726037 6.96501 0.700012 7.05817 0.700012C7.15132 0.700012 7.24263 0.726037 7.32178 0.775151C7.40094 0.824266 7.4648 0.894518 7.50617 0.977985L9.14717 4.29899C9.18307 4.37152 9.23604 4.43426 9.30153 4.48182C9.36702 4.52937 9.44308 4.56031 9.52317 4.57199L13.1892 5.10499C13.2815 5.1184 13.3683 5.15738 13.4396 5.21754C13.511 5.27769 13.564 5.35661 13.5929 5.44537C13.6217 5.53412 13.6251 5.62917 13.6027 5.71976C13.5803 5.81036 13.533 5.89288 13.4662 5.95798L10.8132 8.54398C10.7552 8.60049 10.7118 8.67024 10.6867 8.74723C10.6616 8.82422 10.6556 8.90616 10.6692 8.98598L11.2962 12.637C11.3122 12.7291 11.3021 12.8238 11.267 12.9105C11.232 12.9971 11.1733 13.0722 11.0977 13.1272C11.0221 13.1822 10.9326 13.2149 10.8393 13.2215C10.7461 13.2282 10.6528 13.2086 10.5702 13.165L7.29117 11.441C7.21946 11.4033 7.13967 11.3836 7.05867 11.3836C6.97767 11.3836 6.89788 11.4033 6.82617 11.441V11.442Z" fill="#ffc107"></path></svg>';
        } elseif ($has_half_star && $i === $full_stars + 1) {
            // Half star
            $stars .= '<svg class="maor-star half" viewBox="0 0 14 14" width="14" height="14"><path d="M6.82617 11.442L3.54617 13.166C3.46353 13.2093 3.3704 13.2287 3.27732 13.2219C3.18425 13.2151 3.09494 13.1824 3.0195 13.1274C2.94406 13.0725 2.8855 12.9975 2.85045 12.911C2.8154 12.8245 2.80526 12.7299 2.82117 12.638L3.44817 8.98798C3.46192 8.908 3.456 8.82587 3.43091 8.74869C3.40582 8.67151 3.36232 8.6016 3.30417 8.54499L0.650168 5.95899C0.583317 5.89388 0.53602 5.81136 0.51363 5.72076C0.491239 5.63017 0.494647 5.53512 0.52347 5.44637C0.552292 5.35761 0.605378 5.27869 0.676721 5.21854C0.748065 5.15838 0.834818 5.1194 0.927168 5.10599L4.59317 4.57299C4.67344 4.56146 4.7497 4.53059 4.81537 4.48303C4.88105 4.43547 4.93418 4.37265 4.97017 4.29999L6.61017 0.977985C6.65153 0.894518 6.7154 0.824266 6.79455 0.775151C6.87371 0.726037 6.96501 0.700012 7.05817 0.700012C7.15132 0.700012 7.24263 0.726037 7.32178 0.775151C7.40094 0.824266 7.4648 0.894518 7.50617 0.977985L9.14717 4.29899C9.18307 4.37152 9.23604 4.43426 9.30153 4.48182C9.36702 4.52937 9.44308 4.56031 9.52317 4.57199L13.1892 5.10499C13.2815 5.1184 13.3683 5.15738 13.4396 5.21754C13.511 5.27769 13.564 5.35661 13.5929 5.44537C13.6217 5.53412 13.6251 5.62917 13.6027 5.71976C13.5803 5.81036 13.533 5.89288 13.4662 5.95798L10.8132 8.54398C10.7552 8.60049 10.7118 8.67024 10.6867 8.74723C10.6616 8.82422 10.6556 8.90616 10.6692 8.98598L11.2962 12.637C11.3122 12.7291 11.3021 12.8238 11.267 12.9105C11.232 12.9971 11.1733 13.0722 11.0977 13.1272C11.0221 13.1822 10.9326 13.2149 10.8393 13.2215C10.7461 13.2282 10.6528 13.2086 10.5702 13.165L7.29117 11.441C7.21946 11.4033 7.13967 11.3836 7.05867 11.3836C6.97767 11.3836 6.89788 11.4033 6.82617 11.441V11.442Z" fill="#ffc107"></path></svg>';
        } else {
            // Empty star
            $stars .= '<svg class="maor-star empty" viewBox="0 0 14 14" width="14" height="14"><path d="M6.82617 11.442L3.54617 13.166C3.46353 13.2093 3.3704 13.2287 3.27732 13.2219C3.18425 13.2151 3.09494 13.1824 3.0195 13.1274C2.94406 13.0725 2.8855 12.9975 2.85045 12.911C2.8154 12.8245 2.80526 12.7299 2.82117 12.638L3.44817 8.98798C3.46192 8.908 3.456 8.82587 3.43091 8.74869C3.40582 8.67151 3.36232 8.6016 3.30417 8.54499L0.650168 5.95899C0.583317 5.89388 0.53602 5.81136 0.51363 5.72076C0.491239 5.63017 0.494647 5.53512 0.52347 5.44637C0.552292 5.35761 0.605378 5.27869 0.676721 5.21854C0.748065 5.15838 0.834818 5.1194 0.927168 5.10599L4.59317 4.57299C4.67344 4.56146 4.7497 4.53059 4.81537 4.48303C4.88105 4.43547 4.93418 4.37265 4.97017 4.29999L6.61017 0.977985C6.65153 0.894518 6.7154 0.824266 6.79455 0.775151C6.87371 0.726037 6.96501 0.700012 7.05817 0.700012C7.15132 0.700012 7.24263 0.726037 7.32178 0.775151C7.40094 0.824266 7.4648 0.894518 7.50617 0.977985L9.14717 4.29899C9.18307 4.37152 9.23604 4.43426 9.30153 4.48182C9.36702 4.52937 9.44308 4.56031 9.52317 4.57199L13.1892 5.10499C13.2815 5.1184 13.3683 5.15738 13.4396 5.21754C13.511 5.27769 13.564 5.35661 13.5929 5.44537C13.6217 5.53412 13.6251 5.62917 13.6027 5.71976C13.5803 5.81036 13.533 5.89288 13.4662 5.95798L10.8132 8.54398C10.7552 8.60049 10.7118 8.67024 10.6867 8.74723C10.6616 8.82422 10.6556 8.90616 10.6692 8.98598L11.2962 12.637C11.3122 12.7291 11.3021 12.8238 11.267 12.9105C11.232 12.9971 11.1733 13.0722 11.0977 13.1272C11.0221 13.1822 10.9326 13.2149 10.8393 13.2215C10.7461 13.2282 10.6528 13.2086 10.5702 13.165L7.29117 11.441C7.21946 11.4033 7.13967 11.3836 7.05867 11.3836C6.97767 11.3836 6.89788 11.4033 6.82617 11.441V11.442Z" fill="#ddd"></path></svg>';
        }
    }
    
    return '<div class="maor-stars">' . $stars . '</div>';
}

// Add a widget
class Maor_Reviews_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'maor_reviews_widget',
            'Multi-Source Reviews',
            array('description' => 'Display reviews from multiple sources')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $source = !empty($instance['source']) ? $instance['source'] : 'all';
        echo do_shortcode('[my_reviews limit="' . $instance['limit'] . '" source="' . $source . '"]');
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Customer Reviews';
        $limit = !empty($instance['limit']) ? $instance['limit'] : 3;
        $source = !empty($instance['source']) ? $instance['source'] : 'all';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>">Number of reviews to show:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>" 
                   name="<?php echo $this->get_field_name('limit'); ?>" type="number" 
                   value="<?php echo esc_attr($limit); ?>" min="1" max="10">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('source'); ?>">Review source:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('source'); ?>" 
                   name="<?php echo $this->get_field_name('source'); ?>">
                <option value="all" <?php selected($source, 'all'); ?>>All Sources</option>
                <option value="google" <?php selected($source, 'google'); ?>>Google Only</option>
                <option value="appstore" <?php selected($source, 'appstore'); ?>>App Store Only</option>
                <option value="manual" <?php selected($source, 'manual'); ?>>Manual Only</option>
            </select>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? absint($new_instance['limit']) : 3;
        $instance['source'] = (!empty($new_instance['source'])) ? strip_tags($new_instance['source']) : 'all';
        return $instance;
    }
}

function maor_register_widget() {
    register_widget('Maor_Reviews_Widget');
}
add_action('widgets_init', 'maor_register_widget');