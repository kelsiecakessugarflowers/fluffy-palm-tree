<?php
/**
 * Plugin Name: Kelsie Review Block
 * Description: Custom testimonial block with ACF repeater + Rank Math schema.
 * Author: It Me
 * Version: 2.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

	/* -----------------------------------------------------------
	 *  BRAND DESIGN CONSTANTS
	 * ----------------------------------------------------------- */

define( 'KELSIE_BRAND', [

    // COLORS
    'color' => [
        'bg_light'       => '#FFF9FC', // blush background
        'border'         => '#E5B9D2', // Soft Orchid
        'text_dark'      => '#492C38', // deep plum
        'accent'         => '#A9A4E1', // the big quote mark
        'text_muted'     => '#6B7B65', // muted sage for reviewer name
    ],

    // TYPOGRAPHY
    'font' => [
        'serif'          => '"Source Serif 4", Georgia, serif',
        'sans'           => '"Raleway", sans-serif',
    ],

    // SPACING
    'space' => [
        'desktop_margin' => '4rem auto',
        'desktop_padding'=> '3rem clamp(1.75rem, 4vw, 3rem)',
        'mobile_margin'  => '3rem 1rem',
        'mobile_padding' => '2.25rem 1.5rem',
    ],

    // WIDTHS
    'width' => [
        'pullquote_max'  => '70rem',
        'text_max'       => '60rem',
    ]
] );


final class KelsieReviewBlock {

        // Restrict front-end registration to specific pages. Update this array to change defaults.
        private $allowed_pages = [ 11336 ];

	private $block_registered = false;

	public static function init() {
		$instance = new self();

		add_action( 'init', [ $instance, 'bootstrap' ] );
		add_filter( 'rank_math/json_ld', [ $instance, 'inject_schema' ], 10, 2 );
	}

	public function bootstrap() {
		if ( $this->should_load_in_admin() ) {
			$this->register_block();
			add_action( 'add_meta_boxes', [ $this, 'add_schema_metabox' ] );
		}

		add_action( 'wp', [ $this, 'maybe_register_frontend_block' ] );
	}

	private function get_allowed_pages() {
		$allowed = apply_filters( 'kelsie_review_block_allowed_pages', $this->allowed_pages );

		if ( ! is_array( $allowed ) ) {
			return [];
		}

		return array_values( array_filter( $allowed, static function ( $value ) {
			return ( is_string( $value ) || is_int( $value ) ) && $value !== '';
		} ) );
	}

	private function should_load_in_admin() {
		return is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax();
	}

        private function should_handle_request() {
                if ( $this->should_load_in_admin() ) {
                        return true;
                }

                $allowed = $this->get_allowed_pages();
                if ( empty( $allowed ) ) {
                        return true;
                }

                return is_page( $allowed );
        }

	public function maybe_register_frontend_block() {
		if ( ! $this->should_handle_request() ) {
			return;
		}

		$this->register_block();
	}

        /* -----------------------------------------------------------
         *  BLOCK REGISTRATION
         * ----------------------------------------------------------- */
        public function register_block() {
                if ( $this->block_registered ) {
                        return;
                }

                if ( ! $this->should_handle_request() ) {
                        return;
                }

                $this->block_registered = true;
                $plugin_url = plugin_dir_url( __FILE__ );
                $plugin_dir = plugin_dir_path( __FILE__ );

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

                register_block_type(
                        __DIR__,
                        [
                                'style'           => 'kelsie-review-block',
                                'editor_style'    => 'kelsie-review-block-editor',
                                'render_callback' => [ $this, 'render_block' ],
                        ]
                );
        }

        public function render_block( $attributes, $content, $block = [] ) {
                ob_start();
                include plugin_dir_path(__FILE__) . 'render.php';
                return ob_get_clean();
        }


	/* -----------------------------------------------------------
	 *  REVIEW EXTRACTION
	 * ----------------------------------------------------------- */

	private function extract_review_blocks( $content ) {

		if ( ! function_exists( 'parse_blocks' ) )
			return [];

		$blocks = parse_blocks( $content );
		$found  = [];
		$count  = 0;

		$walk = function( $items ) use ( &$walk, &$found, &$count ) {

			foreach ( $items as $block ) {

				if ( ! empty( $block['innerBlocks'] ) ) {
					$walk( $block['innerBlocks'] );
				}

				$block_name = $block['blockName'] ?? '';
				if ( $block_name !== 'kelsiecakes/review-list' &&
					 $block_name !== 'acf/kelsiecakes-review-list' ) {
					continue;
				}

				$data = $block['attrs']['data'] ?? [];
				$rows = $this->normalize_repeater_rows( $data );
				if ( empty( $rows ) )
					continue;

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

	private function normalize_repeater_rows( $data ) {
		$rows = [];

		if ( isset( $data['client_testimonials'] ) &&
			 is_array( $data['client_testimonials'] ) ) {

			$rows = $data['client_testimonials'];

		} else {
			foreach ( $data as $key => $value ) {
				if ( ! is_string( $key ) ) continue;
				if ( strpos( $key, 'client_testimonials_' ) !== 0 ) continue;
				if ( preg_match('/client_testimonials_(\d+)_(.+)/', $key, $match ) ) {
					$rows[ (int) $match[1] ][ $match[2] ] = $value;
				}
			}
			ksort( $rows );
			$rows = array_values( $rows );
		}

		$normalized = [];

		foreach ( $rows as $r ) {

			$body = trim( $r['review_body'] ?? '' );
			$name = trim( $r['reviewer_name'] ?? '' );

			if ( $body === '' || $name === '' )
				continue;

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

                if ( ! $this->should_handle_request() ) {
                        return $data;
                }

                $post = get_queried_object();
                if ( ! ( $post instanceof WP_Post ) )
                        return $data;

		$permalink = get_permalink( $post );
		if ( ! $permalink )
			return $data;

		$sets = $this->extract_review_blocks( $post->post_content );
		if ( empty( $sets ) )
			return $data;

		foreach ( $sets as $set ) {

			$item_list_id = $permalink . '#' . $set['anchor'];

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

				if ( $name === '' || $text === '' )
					continue;

				$slug = $id ?: sanitize_title( $name . '-' . ($i+1) );
				if ( $slug === '' ) $slug = uniqid('review-', false);

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

				// rating only added if 1–5
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

			if ( empty( $reviews ) ) continue;

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
	 *  SCHEMA PREVIEW
         * ----------------------------------------------------------- */

        public function add_schema_metabox() {
                if ( ! $this->should_handle_request() ) {
                        return;
                }

                add_meta_box(
                        'kelsie_review_schema_preview',
                        'Review Schema Preview (Auto-Detected)',
			[ $this, 'render_schema_metabox' ],
			['post', 'page'],
			'normal',
			'default'
		);
	}

	public function render_schema_metabox( $post ) {

		$data = apply_filters( 'rank_math/json_ld', [], [] );
		$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		echo '<p>This is the JSON-LD generated from your Review Block(s). It updates automatically based on the content of this post.</p>';
		echo '<pre style="background:#f7f7f7;padding:15px;border:1px solid #ddd;max-height:500px;overflow:auto;">';
		echo esc_html( $json );
		echo '</pre>';
	}
}

KelsieReviewBlock::init();
