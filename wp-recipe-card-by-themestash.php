<?php
/**
 * Plugin Name: WP Recipe Card by ThemeStash
 * Plugin URI: https://wordpress.org/plugins/wp-recipe-card-by-themestash/
 * Description: A Plugin that adds a Recipe Card Gutenberg Block.
 * Author: ThemeStash
 * Author URI: https://themestash.com/
 * Version: 1.0.0
 * Text Domain: themestash-recipe-card
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package CGB
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

require_once plugin_dir_path(__FILE__) . 'src/init.php';
