<?php
/**
 * Core plugin functionality.
 */

namespace Fluffy_Palm_Tree;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Primary plugin class.
 */
class Plugin {
    /**
     * Custom post type slug.
     *
     * @var string
     */
    const POST_TYPE = 'testimonial';

    /**
     * Custom taxonomy slug.
     *
     * @var string
     */
    const TAXONOMY = 'testimonial_category';

    /**
     * Register hooks.
     */
    public function run() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'init', [ $this, 'register_taxonomy' ] );
        add_action( 'init', [ $this, 'register_acf_fields' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_shortcode( 'fluffy_testimonials', [ $this, 'render_testimonials_shortcode' ] );
        add_filter( 'rank_math/sitemap/exclude_post_type', [ $this, 'allow_rank_math_sitemap' ], 10, 2 );
    }

    /**
     * Handle activation tasks.
     */
    public function activate() {
        $this->register_post_type();
        $this->register_taxonomy();
        flush_rewrite_rules();
    }

    /**
     * Register the testimonial custom post type.
     */
    public function register_post_type() {
        $labels = [
            'name'               => __( 'Testimonials', 'fluffy-palm-tree' ),
            'singular_name'      => __( 'Testimonial', 'fluffy-palm-tree' ),
            'add_new'            => __( 'Add New', 'fluffy-palm-tree' ),
            'add_new_item'       => __( 'Add New Testimonial', 'fluffy-palm-tree' ),
            'edit_item'          => __( 'Edit Testimonial', 'fluffy-palm-tree' ),
            'new_item'           => __( 'New Testimonial', 'fluffy-palm-tree' ),
            'view_item'          => __( 'View Testimonial', 'fluffy-palm-tree' ),
            'search_items'       => __( 'Search Testimonials', 'fluffy-palm-tree' ),
            'not_found'          => __( 'No testimonials found', 'fluffy-palm-tree' ),
            'not_found_in_trash' => __( 'No testimonials found in Trash', 'fluffy-palm-tree' ),
            'all_items'          => __( 'All Testimonials', 'fluffy-palm-tree' ),
        ];

        register_post_type(
            self::POST_TYPE,
            [
                'labels'              => $labels,
                'public'              => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_rest'        => true,
                'has_archive'         => true,
                'menu_icon'           => 'dashicons-format-quote',
                'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
                'exclude_from_search' => false,
                'rewrite'             => [ 'slug' => 'testimonials' ],
            ]
        );
    }

    /**
     * Register taxonomy for testimonials.
     */
    public function register_taxonomy() {
        $labels = [
            'name'          => __( 'Testimonial Categories', 'fluffy-palm-tree' ),
            'singular_name' => __( 'Testimonial Category', 'fluffy-palm-tree' ),
        ];

        register_taxonomy(
            self::TAXONOMY,
            self::POST_TYPE,
            [
                'labels'       => $labels,
                'public'       => true,
                'hierarchical' => true,
                'show_in_rest' => true,
                'rewrite'      => [ 'slug' => 'testimonial-category' ],
            ]
        );
    }

    /**
     * Register ACF fields when ACF is available.
     */
    public function register_acf_fields() {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        acf_add_local_field_group(
            [
                'key'    => 'group_fluffy_testimonial_details',
                'title'  => __( 'Testimonial Details', 'fluffy-palm-tree' ),
                'fields' => [
                    [
                        'key'          => 'field_fluffy_reviewer_name',
                        'label'        => __( 'Reviewer Name', 'fluffy-palm-tree' ),
                        'name'         => 'fluffy_reviewer_name',
                        'type'         => 'text',
                        'instructions' => __( 'The name of the person who left the review.', 'fluffy-palm-tree' ),
                        'wrapper'      => [ 'width' => '50' ],
                    ],
                    [
                        'key'          => 'field_fluffy_reviewer_role',
                        'label'        => __( 'Reviewer Role', 'fluffy-palm-tree' ),
                        'name'         => 'fluffy_reviewer_role',
                        'type'         => 'text',
                        'instructions' => __( 'Job title or context for the reviewer.', 'fluffy-palm-tree' ),
                        'wrapper'      => [ 'width' => '50' ],
                    ],
                    [
                        'key'          => 'field_fluffy_reviewer_rating',
                        'label'        => __( 'Rating', 'fluffy-palm-tree' ),
                        'name'         => 'fluffy_reviewer_rating',
                        'type'         => 'number',
                        'instructions' => __( 'Star rating from 1-5.', 'fluffy-palm-tree' ),
                        'min'          => 1,
                        'max'          => 5,
                        'step'         => 0.5,
                        'wrapper'      => [ 'width' => '50' ],
                    ],
                ],
                'location' => [
                    [
                        [
                            'param'    => 'post_type',
                            'operator' => '==',
                            'value'    => self::POST_TYPE,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        $version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : FLUFFY_PALM_TREE_VERSION;

        wp_register_style(
            'fluffy-palm-tree-frontend',
            FLUFFY_PALM_TREE_URL . 'assets/css/frontend.css',
            [],
            $version
        );

        wp_register_script(
            'fluffy-palm-tree-frontend',
            FLUFFY_PALM_TREE_URL . 'assets/js/frontend.js',
            [],
            $version,
            true
        );

        wp_enqueue_style( 'fluffy-palm-tree-frontend' );
        wp_enqueue_script( 'fluffy-palm-tree-frontend' );
    }

    /**
     * Shortcode callback for displaying testimonials.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    public function render_testimonials_shortcode( $atts ) {
        $atts = shortcode_atts(
            [
                'limit'    => 5,
                'order'    => 'DESC',
                'category' => '',
            ],
            $atts,
            'fluffy_testimonials'
        );

        $args = [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => absint( $atts['limit'] ),
            'order'          => in_array( strtoupper( $atts['order'] ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $atts['order'] ) : 'DESC',
            'orderby'        => 'date',
        ];

        if ( ! empty( $atts['category'] ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => self::TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $atts['category'] ),
                ],
            ];
        }

        $query = new WP_Query( $args );

        ob_start();

        $testimonials = $query->have_posts() ? $query->posts : [];
        $template     = $this->get_template_path( 'testimonials-loop.php' );

        if ( $template ) {
            require $template;
        }

        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Template resolver.
     *
     * @param string $template Template filename.
     *
     * @return string|false
     */
    protected function get_template_path( $template ) {
        $theme_path = locate_template( [ 'fluffy-palm-tree/' . $template ] );

        if ( $theme_path ) {
            return $theme_path;
        }

        $plugin_path = FLUFFY_PALM_TREE_PATH . 'templates/' . $template;

        if ( file_exists( $plugin_path ) ) {
            return $plugin_path;
        }

        return false;
    }

    /**
     * Ensure testimonials can be included in Rank Math sitemaps.
     *
     * @param bool   $exclude   Whether to exclude the post type.
     * @param string $post_type Post type name.
     *
     * @return bool
     */
    public function allow_rank_math_sitemap( $exclude, $post_type ) {
        if ( self::POST_TYPE === $post_type ) {
            return false;
        }

        return $exclude;
    }
}
