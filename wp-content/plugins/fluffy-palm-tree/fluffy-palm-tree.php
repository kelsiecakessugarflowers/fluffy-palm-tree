<?php
/**
 * Plugin Name:       Fluffy Palm Tree Testimonials
 * Plugin URI:        https://example.com/fluffy-palm-tree
 * Description:       Review testimonial plugin with Rank Math and ACF Pro compatibility.
 * Version:           1.0.0
 * Author:            Fluffy Palm Tree
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fluffy-palm-tree
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'FLUFFY_PALM_TREE_VERSION' ) ) {
    define( 'FLUFFY_PALM_TREE_VERSION', '1.0.0' );
}

if ( ! defined( 'FLUFFY_PALM_TREE_PATH' ) ) {
    define( 'FLUFFY_PALM_TREE_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'FLUFFY_PALM_TREE_URL' ) ) {
    define( 'FLUFFY_PALM_TREE_URL', plugin_dir_url( __FILE__ ) );
}

require_once FLUFFY_PALM_TREE_PATH . 'includes/class-fluffy-palm-tree.php';

/**
 * Initialize plugin services.
 */
function fluffy_palm_tree_init() {
    $plugin = new Fluffy_Palm_Tree\Plugin();
    $plugin->run();
}
add_action( 'plugins_loaded', 'fluffy_palm_tree_init' );

/**
 * Handle activation tasks.
 */
function fluffy_palm_tree_activate() {
    $plugin = new Fluffy_Palm_Tree\Plugin();
    $plugin->activate();
}
register_activation_hook( __FILE__, 'fluffy_palm_tree_activate' );

/**
 * Handle deactivation tasks.
 */
function fluffy_palm_tree_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'fluffy_palm_tree_deactivate' );
