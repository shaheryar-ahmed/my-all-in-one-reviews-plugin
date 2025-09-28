<?php
// Default Layout Template (Grid, Carousel, List)
global $reviews, $atts;
?>
<div class="maor-reviews-container maor-<?php echo esc_attr($atts['layout']); ?>">
    <h3 class="maor-title">Customer Reviews</h3>
    
    <?php if (!empty($reviews)) : ?>
        <?php foreach ($reviews as $review) : ?>
            <div class="maor-review">
                <div class="maor-review-header">
                    <?php if ($atts['show_avatars']) : ?>
                        <div class="maor-avatar">
                            <img src="<?php echo esc_url($review->profile_photo_url ?? $review->avatar_url ?? plugin_dir_url(__FILE__) . '../images/default-avatar.png'); ?>" alt="<?php echo esc_attr($review->author_name); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="maor-review-info">
                        <h4 class="maor-author"><?php echo esc_html($review->author_name); ?></h4>
                        <div class="maor-rating"><?php echo maor_generate_stars($review->rating); ?></div>
                    </div>
                    
                    <?php if ($atts['show_source'] && isset($review->source)) : ?>
                        <div class="maor-source"><?php echo esc_html($review->source); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="maor-review-content">
                    <p class="maor-text"><?php echo esc_html($review->text); ?></p>
                    
                    <?php if ($atts['show_date'] && isset($review->time)) : ?>
                        <p class="maor-date"><?php echo date('F j, Y', is_numeric($review->time) ? $review->time : strtotime($review->time)); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p>No reviews to display.</p>
    <?php endif; ?>
</div>