# Kelsie Review Block

A lightweight testimonials plugin that provides the **Review List** block with ACF-powered fields and Rank Math schema output. Content is managed directly in the block (or via ACF fields) without any custom post types.

## Installation
1. Copy this folder to `wp-content/plugins/kelsie-review-block` inside your WordPress installation.
2. Activate **Kelsie Review Block** from the Plugins screen.
3. (Optional) Activate ACF or ACF Pro to use the bundled reviewer name, role, and rating fields.

## Usage
- Add the **Review List** block to a page or template and configure the ACF repeater fields (review body, title, reviewer name, and rating) for each testimonial.
- If ACF is missing or inactive, the block renders a fallback notice explaining that testimonials are unavailable.

The plugin also loads front-end and editor styles for the block and injects an ItemList schema via Rank Math based on the testimonials entered into the block.

## Front-end availability
Front-end registration of the block is gated by the `kelsie_review_block_allowed_pages` filter. If no allowed pages are provided, the block will not register on the front end, and nothing will render for site visitors.

Enable the block on specific pages by returning a list of page IDs (or slugs) from the filter, for example in your theme's `functions.php`:

```php
add_filter( 'kelsie_review_block_allowed_pages', function( $allowed_pages ) {
    // Allow the block to render on the Reviews and About pages.
    return [ 123, 456 ];
} );
```

You can also merge with existing values if you need to add to an existing configuration:

```php
add_filter( 'kelsie_review_block_allowed_pages', function( $allowed_pages ) {
    $allowed_pages[] = 'contact';
    return $allowed_pages;
} );
```
