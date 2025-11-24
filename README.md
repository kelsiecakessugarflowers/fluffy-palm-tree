# **Kelsie Review Block**

A lightweight, ACF-native testimonials plugin that provides the **Review List** block using an ACF Repeater field and automatic Rank Math review schema.
No custom post types. No admin screens. All content is managed directly inside the block.

## **Installation**

1. Copy this folder to `wp-content/plugins/kelsie-review-block` in your WordPress installation.
2. Activate **Kelsie Review Block** from the Plugins screen.
3. Ensure **ACF Pro** (recommended) or ACF Free is active. The block depends on ACF to provide and render its fields.

## **Usage**

* Add the **Review List** block (`kelsiecakes/review-list`) to any page or template.
* Add testimonial content using the ACF Repeater fields that appear in the block sidebar.
* The block renders dynamically using `render.php` and outputs matching Review Schema (ItemList + Review) via Rank Math.

If ACF is inactive, the block displays a simple fallback notice in both editor and front-end.

The plugin also loads custom front-end and editor styles and includes brand-style overrides via inline CSS.

## **Disabling the built-in ACF repeater**

If you maintain your own manual field group for the block, disable the plugin’s built-in **Client Testimonials** repeater to avoid duplicates in the editor:

```php
add_filter( 'kelsie_review_block_register_field_group', '__return_false' );
```

The filter runs before the local field group is registered, ensuring only one repeater appears for the Review List block.

## **ACF Required Field Structure**

Repeater: `client_testimonials`

* Fields inside repeater:

- `reviewer_name` (Text — required)
- `review_body` (Textarea — required)
- `review_title` (Text)
- `rating_number` (Number: 0–5, step 1)
- `review_id` (Text)
- `review_original_location` (URL)

Location rule: **Block** equals **kelsiecakes/review-list**

This ensures ACF passes all repeater data into `$block['data']` during rendering.

## **Front-end availability**

Front-end loading of the block, CSS, and schema logic is controlled by the
`kelsie_review_block_allowed_pages` filter.

### Default behavior:

* If specific page IDs/slugs are defined in the plugin's `$allowed_pages`, only those pages will load the block.
* If the list is empty, the plugin automatically enables itself for any singular post or page **that contains** a Review List block.

### To override allowed pages:

```php
add_filter( 'kelsie_review_block_allowed_pages', function( $allowed_pages ) {
    // Allow the block to render on the Reviews and About pages.
    return [ 123, 456 ];
} );
```

### To merge with existing values:

```php
add_filter( 'kelsie_review_block_allowed_pages', function( $allowed_pages ) {
    $allowed_pages[] = 'contact';
    return $allowed_pages;
} );
```

This selective-loading approach prevents unnecessary schema injection and avoids loading block assets across the entire site.

## **Schema Output**

For each Review List block rendered on a supported page, the plugin:

* Extracts repeater rows from `$block['data']`
* Normalizes them into valid Review objects
* Generates stable, unique `@id` fragments for each review
* Outputs an **ItemList** schema node containing all Reviews
* Injects schema via Rank Math’s `rank_math/json_ld` filter

Schema is only injected on the front-end for the exact pages where the block is present or has been explicitly allowed.
