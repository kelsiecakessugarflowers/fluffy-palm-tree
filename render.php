<?php
/**
 * Frontend render template for the Kelsie Review block.
 */

// Bail if no block context is provided.
if ( ! isset( $block ) || ! is_array( $block ) ) {
    return;
}

// Normalize rows from block attributes before checking ACF state.
$attributes_data = [];

if ( isset( $attributes['data'] ) && is_array( $attributes['data'] ) ) {
    $attributes_data = $attributes['data'];
} elseif ( isset( $block['attrs']['data'] ) && is_array( $block['attrs']['data'] ) ) {
    $attributes_data = $block['attrs']['data'];
}

$normalized_rows = [];

if ( method_exists( 'KelsieReviewBlock', 'normalize_repeater_rows' ) ) {
    $normalized_rows = KelsieReviewBlock::normalize_repeater_rows( $attributes_data );
}

// Ensure the template only runs for the expected block.
if ( ! isset( $block['name'] ) || 'kelsiecakes/review-list' !== $block['name'] ) {
    return;
}

// Ensure ACF helpers exist before calling them.
$acf_available = function_exists( 'acf' ) && function_exists( 'have_rows' ) && function_exists( 'get_sub_field' );
$is_editor     = is_admin();

// Determine section ID.
$section_id = '';

if ( ! empty( $block['anchor'] ) ) {
    $section_id = sanitize_title( $block['anchor'] );
} elseif ( ! empty( $block['id'] ) ) {
    $section_id = sanitize_title( $block['id'] );
}

if ( '' === $section_id ) {
    $section_id = 'kelsie-review-list';
}

$render_placeholder = static function ( $message ) use ( $section_id ) {
?>
    <section id="<?php echo esc_attr( $section_id ); ?>"
        class="kelsie-review-block se-wpt"
        aria-label="<?php esc_attr_e( 'Client testimonials', 'kelsie-review-block' ); ?>">

        <div class="kelsie-review-block__list" role="list">
            <article class="kelsie-review-block__item" role="listitem">
                <p class="kelsie-review-block__notice"><?php echo esc_html( $message ); ?></p>
            </article>
        </div>
    </section>
<?php
};

// If ACF is not available, render normalized rows or show notice on the frontend only.
if ( ! $acf_available && empty( $normalized_rows ) ) {
    if ( $is_editor ) {
        $render_placeholder( __( 'Testimonials will display here when ACF is active.', 'kelsie-review-block' ) );
    }

    return;
}

$has_acf_rows = $acf_available && have_rows( 'client_testimonials' );

// If neither ACF rows nor normalized rows exist, bail without rendering.
if ( ! $has_acf_rows && empty( $normalized_rows ) ) {
    if ( $is_editor ) {
        $render_placeholder( __( 'Add client testimonials to show this block.', 'kelsie-review-block' ) );
    }

    return;
}
?>

<section id="<?php echo esc_attr( $section_id ); ?>"
    class="kelsie-review-block se-wpt"
    aria-label="<?php esc_attr_e( 'Client testimonials', 'kelsie-review-block' ); ?>">

    <div class="kelsie-review-block__list" role="list">

        <?php if ( $has_acf_rows ) : ?>
            <?php while ( have_rows( 'client_testimonials' ) ) : the_row(); ?>
                <?php
                $body   = trim( (string) get_sub_field( 'review_body' ) );
                $title  = trim( (string) get_sub_field( 'review_title' ) );
                $name   = trim( (string) get_sub_field( 'reviewer_name' ) );
                $rating = get_sub_field( 'rating_number' );

                if ( '' === $body || '' === $name ) {
                    continue;
                }

                // Normalize rating.
                $rating_value = ( is_numeric( $rating ) && $rating > 0 )
                    ? max( 1, min( 5, (float) $rating ) )
                    : null;

                $review_id = trim( (string) get_sub_field( 'review_id' ) );
                $row_index = get_row_index();

                $review_slug = '' !== $review_id
                    ? $review_id
                    : sanitize_title( $name . '-' . $row_index );

                if ( '' === $review_slug ) {
                    $review_slug = uniqid( 'review-', false );
                }
                ?>

                <article class="kelsie-review-block__item"
                    id="<?php echo esc_attr( $review_slug ); ?>"
                    role="listitem">

                    <blockquote class="wp-block-pullquote is-style-solid-color">

                        <?php if ( '' !== $title ) : ?>
                            <p class="review-title"><strong><?php echo esc_html( $title ); ?></strong></p>
                        <?php endif; ?>

                        <p><?php echo esc_html( $body ); ?></p>

                        <cite>
                            <span class="kelsie-review-block__name">
                                <?php echo esc_html( $name ); ?>
                            </span>

                            <?php if ( null !== $rating_value ) : ?>
                                <span class="kelsie-review-block__rating"
                                    aria-label="<?php echo esc_attr( sprintf( __( 'Rated %.1f out of 5', 'kelsie-review-block' ), $rating_value ) ); ?>">
                                    – <?php echo esc_html( number_format_i18n( $rating_value, 1 ) ); ?>/5
                                </span>
                            <?php endif; ?>
                        </cite>

                    </blockquote>

                </article>

            <?php endwhile; ?>
        <?php else : ?>
            <?php foreach ( $normalized_rows as $index => $row ) : ?>
                <?php
                $body   = trim( (string) ( $row['review_body'] ?? '' ) );
                $title  = trim( (string) ( $row['review_title'] ?? '' ) );
                $name   = trim( (string) ( $row['reviewer_name'] ?? '' ) );
                $rating = $row['rating_number'] ?? null;

                if ( '' === $body || '' === $name ) {
                    continue;
                }

                $rating_value = ( is_numeric( $rating ) && $rating > 0 )
                    ? max( 1, min( 5, (float) $rating ) )
                    : null;

                $review_id  = trim( (string) ( $row['review_id'] ?? '' ) );
                $row_index  = $index + 1;
                $review_slug = '' !== $review_id
                    ? $review_id
                    : sanitize_title( $name . '-' . $row_index );

                if ( '' === $review_slug ) {
                    $review_slug = uniqid( 'review-', false );
                }
                ?>

                <article class="kelsie-review-block__item"
                    id="<?php echo esc_attr( $review_slug ); ?>"
                    role="listitem">

                    <blockquote class="wp-block-pullquote is-style-solid-color">

                        <?php if ( '' !== $title ) : ?>
                            <p class="review-title"><strong><?php echo esc_html( $title ); ?></strong></p>
                        <?php endif; ?>

                        <p><?php echo esc_html( $body ); ?></p>

                        <cite>
                            <span class="kelsie-review-block__name">
                                <?php echo esc_html( $name ); ?>
                            </span>

                            <?php if ( null !== $rating_value ) : ?>
                                <span class="kelsie-review-block__rating"
                                    aria-label="<?php echo esc_attr( sprintf( __( 'Rated %.1f out of 5', 'kelsie-review-block' ), $rating_value ) ); ?>">
                                    – <?php echo esc_html( number_format_i18n( $rating_value, 1 ) ); ?>/5
                                </span>
                            <?php endif; ?>
                        </cite>

                    </blockquote>

                </article>

            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</section>
