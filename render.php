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

// Collect repeater rows from available sources.
$normalized_rows = [];

if ( $acf_available && function_exists( 'get_field' ) ) {
    $acf_rows = get_field( 'client_testimonials' );

    if ( method_exists( 'KelsieReviewBlock', 'normalize_repeater_rows' ) ) {
        $normalized_rows = KelsieReviewBlock::normalize_repeater_rows(
            [ 'client_testimonials' => $acf_rows ]
        );
    }
}

if ( empty( $normalized_rows ) && $acf_available && have_rows( 'client_testimonials' ) ) {
    // Fallback to the_row() cursor if direct field access fails.
    $normalized_rows = [];

    while ( have_rows( 'client_testimonials' ) ) {
        the_row();

        $normalized_rows[] = [
            'review_body'              => (string) get_sub_field( 'review_body' ),
            'reviewer_name'            => (string) get_sub_field( 'reviewer_name' ),
            'review_title'             => (string) get_sub_field( 'review_title' ),
            'rating_number'            => get_sub_field( 'rating_number' ),
            'review_id'                => (string) get_sub_field( 'review_id' ),
            'review_original_location' => (string) get_sub_field( 'review_original_location' ),
        ];
    }

    // Normalize values (trim, validate, etc.).
    if ( method_exists( 'KelsieReviewBlock', 'normalize_repeater_rows' ) ) {
        $normalized_rows = KelsieReviewBlock::normalize_repeater_rows(
            [ 'client_testimonials' => $normalized_rows ]
        );
    }
}

if ( empty( $normalized_rows ) && isset( $block['data'] ) && is_array( $block['data'] ) ) {
    if ( method_exists( 'KelsieReviewBlock', 'normalize_repeater_rows' ) ) {
        $normalized_rows = KelsieReviewBlock::normalize_repeater_rows( $block['data'] );
    }
}

// If absolutely no rows exist, show placeholder in editor; silence on frontend.
if ( empty( $normalized_rows ) ) {
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

    </div>

</section>
