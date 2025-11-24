 <?php
 $acf_available = function_exists('have_rows') && function_exists('get_sub_field');
$section_id    = '';

if (isset($block['anchor']) && $block['anchor'] !== '') {
    $section_id = sanitize_title($block['anchor']);
} elseif (isset($block['id']) && $block['id'] !== '') {
    $section_id = sanitize_title($block['id']);
}

if ($section_id === '') {
    $section_id = 'kelsie-review-list';
}
 
 if (! $acf_available) {
     ?>
     <div class="kelsie-review-block" role="region" aria-live="polite">
         <p class="kelsie-review-block__notice">
             <?php esc_html_e('Testimonials are unavailable because Advanced Custom Fields is inactive.', 'kelsie-review-block'); ?>
         </p>
     </div>
     <?php
     return;
 }
 
 if (! have_rows('client_testimonials')) {
     return;
 }
 ?>
-<section class="kelsie-review-block se-wpt" aria-label="<?php esc_attr_e('Client testimonials', 'kelsie-review-block'); ?>">
+<section id="<?php echo esc_attr($section_id); ?>" class="kelsie-review-block se-wpt" aria-label="<?php esc_attr_e('Client testimonials', 'kelsie-review-block'); ?>">
     <div class="kelsie-review-block__list" role="list">
         <?php
         while (have_rows('client_testimonials')) {
             the_row();
           $body   = trim((string) get_sub_field('review_body'));
          $title  = trim((string) get_sub_field('review_title'));
            $name   = trim((string) get_sub_field('reviewer_name'));
             $rating = get_sub_field('rating_number');
             $rating_value = is_numeric($rating) ? max(1, min(5, (float) $rating)) : null;
             $review_id = trim((string) get_sub_field('review_id'));
 
             if ($body === '' || $name === '') {
                 continue;
             }
 
             $review_slug = $review_id !== '' ? $review_id : sanitize_title($name . '-' . get_row_index());
             $review_slug = $review_slug !== '' ? $review_slug : uniqid('review-', false);
             ?>
             <article class="kelsie-review-block__item" id="<?php echo esc_attr($review_slug); ?>" role="listitem">
                 <blockquote class="wp-block-pullquote is-style-solid-color">
                     <?php if ($title !== '') : ?>
                         <p class="review-title"><strong><?php echo esc_html($title); ?></strong></p>
                     <?php endif; ?>
 
                     <p><?php echo esc_html($body); ?></p>
 
                     <cite>
                         <span class="kelsie-review-block__name"><?php echo esc_html($name); ?></span>
                         <?php if ($rating_value !== null) : ?>
                             <span class="kelsie-review-block__rating" aria-label="<?php echo esc_attr(sprintf(__('Rated %.1f out of 5', 'kelsie-review-block'), $rating_value)); ?>">
                                 – <?php echo esc_html(number_format_i18n($rating_value, 1)); ?>/5
                             </span>
