<?php
/**
 * Plugin Name: Kelsie Review Block
 * Description: A custom testimonial block using ACF + Rank Math schema.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// Register block + scripts
add_action('init', function() {
  // Register styles
  wp_register_style('kelsie-review-block', plugin_dir_url(__FILE__) . 'style.css');
  wp_register_style('kelsie-review-block-editor', plugin_dir_url(__FILE__) . 'editor.css');

  // Register block
  register_block_type(__DIR__, [
    'style' => 'kelsie-review-block',
    'editor_style' => 'kelsie-review-block-editor',
    'render_callback' => function($attributes, $content) {
      ob_start();
      include plugin_dir_path(__FILE__) . 'render.php';
      return ob_get_clean();
    }
  ]);
});

// Inject schema via Rank Math
add_filter('rank_math/json_ld', function($data, $jsonld) {
  if (!function_exists('have_rows') || !have_rows('client_testimonials')) {
    return $data;
  }

  $reviews = [];

  while (have_rows('client_testimonials')) {
    the_row();

    $name = get_sub_field('reviewer_name');
    $text = get_sub_field('review_body');
    $rating = get_sub_field('rating_number');
    $review_id = get_sub_field('review_id') ?: 'review-' . sanitize_title($name);
    $sameAs = get_sub_field('review_original_location');

    $review = [
      '@type' => 'Review',
      '@id' => get_permalink() . '#' . $review_id,
      'reviewBody' => $text,
      'reviewRating' => [
        '@type' => 'Rating',
        'ratingValue' => (string) $rating,
        'bestRating' => '5'
      ],
      'author' => [
        '@type' => 'Person',
        'name' => $name
      ]
    ];

    if ($sameAs) {
      $review['sameAs'] = esc_url($sameAs);
    }

    $reviews[] = $review;
  }

  $data[] = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => $reviews
  ];

  return $data;
}, 10, 2);