<?php
if (function_exists('have_rows') && have_rows('client_testimonials')) :
?>
  <section class="kelsie-review-block">
    <?php while (have_rows('client_testimonials')) : the_row();
      $body = get_sub_field('review_body');
      $title = get_sub_field('review_title');
      $name = get_sub_field('reviewer_name');
      $rating = get_sub_field('rating_number');
      $review_id = get_sub_field('review_id') ?: 'review-' . sanitize_title($name);
    ?>
      <blockquote id="<?php echo esc_attr($review_id); ?>" class="wp-block-pullquote is-style-solid-color">
        <?php if ($title): ?><p class="review-title"><strong><?php echo esc_html($title); ?></strong></p><?php endif; ?>
        <p><?php echo esc_html($body); ?></p>
        <cite><?php echo esc_html($name); ?><?php if ($rating): ?> – <?php echo esc_html($rating); ?>/5<?php endif; ?></cite>
      </blockquote>
    <?php endwhile; ?>
  </section>
<?php endif; ?>