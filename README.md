# Fluffy Palm Tree Testimonials

A lightweight testimonials plugin that registers a dedicated `testimonial` post type, optional Advanced Custom Fields (ACF) fields, and a shortcode for rendering reviews. Rank Math sitemaps stay enabled for the post type so the content remains indexable.

## Installation
1. Copy this folder to `wp-content/plugins/fluffy-palm-tree` inside your WordPress installation.
2. Activate **Fluffy Palm Tree Testimonials** from the Plugins screen.
3. (Optional) Activate ACF or ACF Pro to use the bundled reviewer name, role, and rating fields.

## Usage
- Create testimonials under the new **Testimonials** menu in the WordPress admin.
- Use the `[fluffy_testimonials]` shortcode in posts, pages, or template parts.

Shortcode attributes:
- `limit` (default `5`): maximum number of testimonials to display.
- `order` (default `DESC`): `ASC` or `DESC` ordering by date.
- `category`: filter by a testimonial category slug.

The plugin also loads a simple front-end stylesheet (`assets/css/frontend.css`) and JavaScript hover effect (`assets/js/frontend.js`). Copy `templates/testimonials-loop.php` into your active theme at `fluffy-palm-tree/testimonials-loop.php` to override the markup.
