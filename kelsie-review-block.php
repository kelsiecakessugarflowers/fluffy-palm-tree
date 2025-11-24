<?php
/**
 * Plugin Name: Kelsie Review Block
 * Description: Custom testimonial block using ACF repeater fields + automatic Review Schema via Rank Math.
 * Author: It Me
 * Version: 3.0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'KELSIE_REVIEW_BLOCK_VERSION', '3.0.1' );

/* -----------------------------------------------------------
 *  BRAND DESIGN CONSTANTS
 * ----------------------------------------------------------- */

define( 'KELSIE_BRAND', [

    'color' => [
        'bg_light'   => '#FFF9FC',
        'border'     => '#E5B9D2',
        'text_dark'  => '#492C38',
        'accent'     => '#A9A4E1',
        'text_muted' => '#6B7B65',
    ],

    'font' => [
        'serif' => '"Source Serif 4", Georgia, serif',
        'sans'  => '"Raleway", sans-serif',
    ],

    'space' => [
        'desktop_margin'  => '4rem auto',
        'desktop_padding' => '3rem clamp(1.75rem, 4vw, 3rem)',
        'mobile_margin'   => '3rem 1rem',
        'mobile_padding'  => '2.25rem 1.5rem',
    ],

    'width' => [
        'pullquote_max' => '70rem',
        'text_max'      => '60rem',
    ]
] );


/**
 * Main Plugin Class
 */
final class KelsieReviewBlock {

    private $allowed_pages = [ 11336 ];
    private $block_registered = false;

    public static function init() {
        $instance = new self();

        // Register block through ACF, not core.
        add_action( 'acf/init', [ $instance, 'register_acf_block' ] );

        // Schema output.
        add_filter( 'rank_math/json_ld', [ $instance, 'inject_schema' ], 10, 2 );

        // Admin schema preview metabox.
        add_action( 'add_meta_boxes', [ $instance, 'maybe_add_schema_metabox' ] );
    }

    /* -----------------------------------------------------------
     *  BLOCK REGISTRATION (ACF)
     * ----------------------------------------------------------- */

    public function register_acf_block() {
        if ( ! function_exists( 'acf_register_block_type' ) ) {
            return; // ACF missing.
        }

        if ( $this->block_registered ) {
            return;
        }

        $this->block_registered = true;

        $plugin_url = plugin_dir_url( __FILE__ );
        $plugin_dir = plugin_dir_path( __FILE__ );

        // Register CSS handles (used by block.json style & editorStyle).
        wp_register_style(
            'kelsie-review-block',
            $plugin_url . 'style.css',
            [],
            filemtime( $plugin_dir . 'style.css' )
        );

        wp_register_style(
            'kelsie-review-block-editor',
            $plugin_url . 'editor.css',
            [],
            filemtime( $plugin_dir . 'editor.css' )
        );

        // Add brand inline styles.
        $brand_styles = $this->get_brand_style_css();
        if ( $brand_styles ) {
            wp_add_inline_style( 'kelsie-review-block', $brand_styles );
        }

        // ACF block registration.
        acf_register_block_type([
            'name'            => 'kelsiecakes-review-list',
            'title'           => __('Review List', 'kelsie'),
            'description'     => __('Dynamic testimonial list using ACF repeater + Review Schema.', 'kelsie'),
            'mode'            => 'preview',
            'render_callback' => [ $this, 'render_block' ],
            'category'        => 'widgets',
            'supports'        => [
                'align' => true,
            ],
        ]);
    }

    public function render_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'render.php';
        return ob_get_clean();
    }

    /* -----------------------------------------------------------
     *  BRAND STYLE CSS
     * ----------------------------------------------------------- */

    private function get_brand_style_css() {
        $brand = KELSIE_BRAND;

        $color = $brand['color'];
        $font  = $brand['font'];
        $space = $brand['space'];
        $width = $brand['width'];

        $css = <<<CSS
.kelsie-review-block .wp-block-pullquote {
    background-color: {$color['bg_light']};
    border: 1px solid {$color['border']};
    border-radius: 18px;
    box-shadow: 0 10px 40px rgba(73, 44, 56, 0.06);
    color: {$color['text_dark']};
    margin: {$space['desktop_margin']};
    max-width: {$width['pullquote_max']};
    padding: {$space['desktop_padding']};
    text-align: left;
}

.kelsie-review-block .wp-block-pullquote p,
.kelsie-review-block .wp-block-pullquote blockquote,
.kelsie-review-block .wp-block-pullquote cite {
    margin: 0;
    color: inherit;
}

.kelsie-review-block .wp-block-pullquote p {
    font-family: {$font['serif']};
    font-size: 1.3rem;
    line-height: 1.7;
    max-width: {$width['text_max']};
}

.kelsie-review-block .wp-block-pullquote p.review-title {
    font-family: {$font['sans']};
    font-size: 1.05rem;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    margin-bottom: 0.75rem;
}

.kelsie-review-block .wp-block-pullquote blockquote::before {
    content: "“";
    display: block;
    font-family: {$font['serif']};
    font-size: 3.2rem;
    line-height: 1;
    margin-bottom: 1rem;
    color: {$color['accent']};
}

.kelsie-review-block .wp-block-pullquote cite {
    display: flex;
    gap: 0.35rem;
    align-items: baseline;
    margin-top: 1.5rem;
    font-family: {$font['sans']};
    font-size: 1rem;
    letter-spacing: 0.06em;
    color: {$color['text_muted']};
}

.kelsie-review-block .kelsie-review-block__name {
    font-weight: 700;
    color: {$color['text_dark']};
}

.kelsie-review-block .kelsie-review-block__rating {
    font-weight: 600;
}

@media (max-width: 768px) {
    .kelsie-review-block .wp-block-pullquote {
        margin: {$space['mobile_margin']};
        padding: {$space['mobile_padding']};
    }

    .kelsie-review-block .wp-block-pullquote p {
        font-size: 1.12rem;
        line-height: 1.65;
        max-width: 100%;
    }
}
CSS;

        return trim($css);
    }

    /* -----------------------------------------------------------
     *  REVIEW EXTRACTION (ACF-NATIVE)
     * ----------------------------------------------------------- */

    private function extract_review_blocks( $content ) {

        if ( ! function_exists( 'parse_blocks' ) ) {
            return [];
        }

        $blocks = parse_blocks( $content );
        $found  = [];
        $count  = 0;

        $walk = function( $items ) use ( &$walk, &$found, &$count ) {

            foreach ( $items as $block ) {

                if ( ! empty( $block['innerBlocks'] ) ) {
                    $walk( $block['innerBlocks'] );
                }

                $name = $block['blockName'] ?? '';

                if ( $name !== 'kelsiecakes/review-list' &&
                     $name !== 'acf/kelsiecakes-review-list' ) {
                    continue;
                }

                // ACF stores repeater values under attrs.data
                $data = $block['attrs']['data'] ?? [];

                $rows = self::normalize_repeater_rows( $data );
                if ( empty( $rows ) ) {
                    continue;
                }

                $anchor = $block['attrs']['anchor']
                    ?? $block['attrs']['id']
                    ?? '';

                if ( $anchor === '' ) {
                    $count++;
                    $anchor = "kelsie-review-list-$count";
                }

                $found[] = [
                    'anchor' => sanitize_title( $anchor ),
                    'rows'   => $rows,
                ];
            }
        };

        $walk( $blocks );

        return $found;
    }


    public static function normalize_repeater_rows( $data ) {

        // For ACF blocks, repeater maps directly under this key:
        if ( isset( $data['client_testimonials'] ) &&
             is_array( $data['client_testimonials'] ) ) {
            $rows = $data['client_testimonials'];
        } else {
            $rows = [];
        }

        $normalized = [];

        foreach ( $rows as $r ) {

            $body = trim( $r['review_body'] ?? '' );
            $name = trim( $r['reviewer_name'] ?? '' );

            if ( $body === '' || $name === '' ) {
                continue;
            }

            $normalized[] = [
                'review_body'              => $body,
                'reviewer_name'            => $name,
                'review_title'             => trim( $r['review_title'] ?? '' ),
                'rating_number'            => is_numeric( $r['rating_number'] ?? null ) ? (float) $r['rating_number'] : null,
                'review_id'                => trim( $r['review_id'] ?? '' ),
                'review_original_location' => trim( $r['review_original_location'] ?? '' ),
            ];
        }

        return $normalized;
    }

    /* -----------------------------------------------------------
     *  SCHEMA INJECTION
     * ----------------------------------------------------------- */

    public function inject_schema( $data, $jsonld ) {

        if ( is_admin() ) {
            return $data;
        }

        if ( ! $this->should_handle_request() ) {
            return $data;
        }

        $post = get_queried_object();
        if ( ! ( $post instanceof WP_Post ) ) {
            return $data;
        }

        $permalink = get_permalink( $post );
        if ( ! $permalink ) {
            return $data;
        }

        $sets = $this->extract_review_blocks( $post->post_content );
        if ( empty( $sets ) ) {
            return $data;
        }

        foreach ( $sets as $set ) {

            $item_list_id = $permalink . '#' . $set['anchor'];

            // Prevent duplicates.
            foreach ( $data as $entry ) {
                if ( is_array($entry) && ($entry['@id'] ?? '') === $item_list_id ) {
                    continue 2;
                }
            }

            $reviews = [];

            foreach ( $set['rows'] as $i => $row ) {

                $name  = $row['reviewer_name'];
                $text  = $row['review_body'];
                $title = $row['review_title'];
                $rate  = $row['rating_number'];
                $id    = $row['review_id'];
                $same  = $row['review_original_location'];

                if ( $name === '' || $text === '' ) {
                    continue;
                }

                $slug = $id ?: sanitize_title( $name . '-' . ($i+1) );
                if ( $slug === '' ) {
                    $slug = uniqid('review-', false);
                }

                $review = [
                    '@type'      => 'Review',
                    '@id'        => $permalink . '#' . $slug,
                    'reviewBody' => $text,
                    'author'     => [
                        '@type' => 'Person',
                        'name'  => $name,
                    ],
                ];

                if ( $title !== '' ) {
                    $review['name'] = $title;
                }

                if ( $rate !== null && $rate > 0 ) {
                    $rate = max(1, min(5, (float) $rate));
                    $review['reviewRating'] = [
                        '@type'       => 'Rating',
                        'ratingValue' => (string) $rate,
                        'bestRating'  => '5',
                    ];
                }

                if ( ! empty( $same ) ) {
                    $review['sameAs'] = esc_url_raw( $same );
                }

                $reviews[] = $review;
            }

            if ( empty( $reviews ) ) {
                continue;
            }

            $data[] = [
                '@context'        => 'https://schema.org',
                '@type'           => 'ItemList',
                '@id'             => $item_list_id,
                'itemListElement' => $reviews,
            ];
        }

        return $data;
    }

    /* -----------------------------------------------------------
     *  CONDITIONAL FRONT-END LOADING
     * ----------------------------------------------------------- */

    private function should_handle_request() {

        if ( is_admin() || ( defined('REST_REQUEST') && REST_REQUEST ) || wp_doing_ajax() ) {
            return true;
        }

        if ( ! is_singular() ) {
            return false;
        }

        $allowed = apply_filters( 'kelsie_review_block_allowed_pages', $this->allowed_pages );

        if ( is_array( $allowed ) && ! empty( $allowed ) ) {
            return is_page( $allowed );
        }

        $post = get_queried_object();
        if ( ! ( $post instanceof WP_Post ) ) {
            return false;
        }

        return has_block( 'kelsiecakes/review-list', $post->post_content ) ||
               has_block( 'acf/kelsiecakes-review-list', $post->post_content );
    }


    /* -----------------------------------------------------------
     *  ADMIN SCHEMA PREVIEW
     * ----------------------------------------------------------- */

    public function maybe_add_schema_metabox() {
        if ( ! $this->should_handle_request() ) {
            return;
        }

        add_meta_box(
            'kelsie_review_schema_preview',
            'Review Schema Preview (Auto-Detected)',
            [ $this, 'render_schema_metabox' ],
            ['post','page'],
            'normal',
            'default'
        );
    }

    public function render_schema_metabox( $post ) {

        $jsonld = $this->get_rank_math_jsonld_instance();

        if ( $jsonld && method_exists( $jsonld, 'can_add_global_entities' ) ) {
            $data = apply_filters( 'rank_math/json_ld', [], $jsonld );
        } else {
            $data = [];
        }

        $json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

        echo '<p>This is the JSON-LD generated from your Review List blocks. It updates automatically as you edit.</p>';
        echo '<pre style="background:#f7f7f7;padding:15px;border:1px solid #ddd;max-height:500px;overflow:auto;">';
        echo esc_html( $json );
        echo '</pre>';
    }

    private function get_rank_math_jsonld_instance() {
        if ( ! function_exists( 'rank_math' ) ) {
            return null;
        }

        $plugin = rank_math();

        if ( ! is_object( $plugin ) || ! isset( $plugin->json_ld ) ) {
            return null;
        }

        return $plugin->json_ld;
    }
}

KelsieReviewBlock::init();
