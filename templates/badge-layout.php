<?php
// Badge Layout Template
global $reviews, $atts;
$options = get_option('maor_options_array');
$average_rating = 0;
$total_reviews = count($reviews);

if ($total_reviews > 0) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review->rating;
    }
    $average_rating = round($total_rating / $total_reviews, 1);
}
?>
<div class="maor-reviews-badge">
    <div class="maor-badge-header">
        <div class="maor-average-rating">
            <span class="maor-average-number"><?php echo $average_rating; ?></span>
            <div class="maor-average-stars"><?php echo maor_generate_stars($average_rating); ?></div>
            <span class="maor-total-reviews">Based on <?php echo $total_reviews; ?> reviews</span>
        </div>
    </div>
    <div class="maor-badge-sources">
        <?php
        $sources = array();
        foreach ($reviews as $review) {
            if (isset($review->source)) {
                $sources[$review->source] = isset($sources[$review->source]) ? $sources[$review->source] + 1 : 1;
            }
        }
        
        foreach ($sources as $source => $count) {
            echo '<span class="maor-source-badge">' . $source . ' (' . $count . ')</span>';
        }
        ?>
    </div>
</div>