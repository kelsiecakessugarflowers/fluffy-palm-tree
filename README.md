# Fluffy Palm Tree Testimonials

A lightweight testimonials plugin that provides an ACF-powered review block and Rank Math schema output. Content is managed directly in the block (or via ACF fields) without any custom post types.

## Installation
1. Copy this folder to `wp-content/plugins/fluffy-palm-tree` inside your WordPress installation.
2. Activate **Fluffy Palm Tree Testimonials** from the Plugins screen.
3. Activate **ACF or ACF Pro**; the block relies on the ACF repeater fields to render testimonials. Without ACF, the block outputs a graceful notice indicating testimonials are unavailable.

## Usage
- Add the **Review List** block to a page or template and configure the ACF repeater fields (review body, title, reviewer name, and rating) for each testimonial.
- If ACF is missing or inactive, the block renders a fallback notice explaining that testimonials are unavailable.

The plugin also loads front-end and editor styles for the block and injects an ItemList schema via Rank Math based on the testimonials entered into the block.
