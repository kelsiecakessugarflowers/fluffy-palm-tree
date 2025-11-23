<?php
/**
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$testimonials = get_posts(
    [
        'post_type'      => 'testimonial',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ]
);

foreach ( $testimonials as $testimonial_id ) {
    wp_delete_post( $testimonial_id, true );
}

if ( taxonomy_exists( 'testimonial_category' ) ) {
    $terms = get_terms(
        [
            'taxonomy'   => 'testimonial_category',
            'hide_empty' => false,
        ]
    );

    foreach ( $terms as $term ) {
        wp_delete_term( $term->term_id, 'testimonial_category' );
    }
}
