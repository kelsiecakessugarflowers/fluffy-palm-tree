<?php
/**
 * Frontend render template for the Kelsie Review Block.
 *
 * This file assumes the block is registered through ACF and that
 * repeater data comes from ACF’s block data API ($block['data']).
 */

// Bail if no block context exists.
if ( ! isset( $block ) || ! is_array( $block ) ) {
    return;
}

$block_name = $block['name'] ?? '';
$valid_names = [ 'kelsiecakes/review-list', 'acf/kelsiecakes-review-list' ];

// Only render for the right block.
if ( ! in_array( $block_name, $valid_names, true ) ) {
    return;
}

// Detect if ACF functions are present.
$acf_available = function_exists( 'acf' ) && function_exists( 'have_rows' );
$is_editor     = is_admin();

// Determine section ID (anchor > id > fallback).
$section_id = '';

if ( ! empty( $block['anchor'] ) ) {
    $section_id = sanitize_title( $block['anchor'] );
} elseif ( ! empty( $block['id'] ) ) {
    $section_id = sanitize_title( $block['id'] );
}

if ( '' === $section_id ) {
    $section_id = 'kelsie-review-list';
}

// Placeholder function for empty or missing ACF.
$render_placeholder = static function( $message ) use ( $section_id ) {
    ?>
    <section id="<?php echo esc_attr( $section_id ); ?>"
        class="kelsie-review-block se-wpt"
        aria-label="<?php esc_attr_e( 'Client testimonials', 'kelsie-review-block' ); ?>">

        <div class="kelsie-review-block__list" role="list">
            <article class="kelsie-review-block__item" role="listitem">
                <p class="kelsie-review-block__notice">
                    <?php echo esc_html( $message ); ?>
                </p>
            </article>
        </div>
    </section>
    <?php
};

// Try using true ACF repeater rows first.
$has_acf_rows = $acf_available && have_rows( 'client_testimonials' );

// If ACF isn’t available or has zero rows, attempt fallback to $block['data'].
$normalized_rows = [];

if ( ! $has_acf_rows && isset( $block['data'] ) && is_array( $block['data'] ) ) {
    if ( method_exists( 'KelsieReviewBlock', 'normalize_repeater_rows' ) ) {
        $normalized_rows = KelsieReviewBlock::normalize_repeater_rows( $block['data'] );
    }
}

// If absolutely no rows exist, show placeholder in editor; silence on frontend.
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

                $rating_value = ( is_numeric( $rating ) && $rating > 0 )
                    ? max( 1, min( 5, (float) $rating ) )
                    : null;

                $review_id  = trim( (string) get_sub_field( 'review_id' ) );
                $row_index  = get_row_index();

                $review_slug = $review_id !== ''
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

                        <?php if ( $title !== '' ) : ?>
                            <p class="review-title"><strong><?php echo esc_html( $title ); ?></strong></p>
                        <?php endif; ?>

                        <p><?php echo esc_html( $body ); ?></p>

                        <cite>
                            <span class="kelsie-review-block__name">
                                <?php echo esc_html( $name ); ?>
                            </span>

                            <?php if ( $rating_value !== null ) : ?>
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

                $review_id   = trim( (string) ( $row['review_id'] ?? '' ) );
                $row_index   = $index + 1;

                $review_slug = $review_id !== ''
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

                        <?php if ( $title !== '' ) : ?>
                            <p class="review-title"><strong><?php echo esc_html( $title ); ?></strong></p>
                        <?php endif; ?>

                        <p><?php echo esc_html( $body ); ?></p>

                        <cite>
                            <span class="kelsie-review-block__name">
                                <?php echo esc_html( $name ); ?>
                            </span>

                            <?php if ( $rating_value !== null ) : ?>
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
