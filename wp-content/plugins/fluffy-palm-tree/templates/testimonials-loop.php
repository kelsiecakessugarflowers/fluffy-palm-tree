<?php
/**
 * Testimonials loop template.
 *
 * Available variables: $testimonials (array of WP_Post objects)
 */

if ( empty( $testimonials ) ) {
    echo '<div class="fluffy-testimonials__empty">' . esc_html__( 'No testimonials available right now.', 'fluffy-palm-tree' ) . '</div>';
    return;
}
?>
<div class="fluffy-testimonials">
    <?php foreach ( $testimonials as $post ) : setup_postdata( $post ); ?>
        <?php
        $reviewer_name   = get_post_meta( $post->ID, 'fluffy_reviewer_name', true );
        $reviewer_role   = get_post_meta( $post->ID, 'fluffy_reviewer_role', true );
        $reviewer_rating = get_post_meta( $post->ID, 'fluffy_reviewer_rating', true );
        ?>
        <article class="fluffy-testimonial" itemscope itemtype="https://schema.org/Review">
            <div class="fluffy-testimonial__content" itemprop="reviewBody">
                <?php the_excerpt(); ?>
            </div>
            <div class="fluffy-testimonial__footer">
                <div class="fluffy-testimonial__author" itemprop="author" itemscope itemtype="https://schema.org/Person">
                    <span class="fluffy-testimonial__name" itemprop="name"><?php echo esc_html( $reviewer_name ? $reviewer_name : get_the_title() ); ?></span>
                    <?php if ( $reviewer_role ) : ?>
                        <span class="fluffy-testimonial__role"><?php echo esc_html( $reviewer_role ); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( $reviewer_rating ) : ?>
                    <div class="fluffy-testimonial__rating" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                        <meta itemprop="worstRating" content="1" />
                        <meta itemprop="bestRating" content="5" />
                        <span class="fluffy-testimonial__stars" aria-label="<?php echo esc_attr( sprintf( __( '%s star rating', 'fluffy-palm-tree' ), $reviewer_rating ) ); ?>">
                            <?php echo str_repeat( '★', (int) $reviewer_rating ); ?>
                        </span>
                        <span class="screen-reader-text" itemprop="ratingValue"><?php echo esc_html( $reviewer_rating ); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
