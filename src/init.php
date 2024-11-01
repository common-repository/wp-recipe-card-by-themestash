<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package CGB
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * Assets enqueued:
 * 1. blocks.style.build.css - Frontend + Backend.
 * 2. blocks.build.js - Backend.
 * 3. blocks.editor.build.css - Backend.
 *
 * @uses {wp-blocks} for block type registration & related functions.
 * @uses {wp-element} for WP Element abstraction — structure of blocks.
 * @uses {wp-i18n} to internationalize the block's text.
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
function themestash_rct_block_assets()
{ // phpcs:ignore
	// Register block styles for both frontend + backend.
	wp_register_style(
		'themestash_rct_style_css', // Handle.
		plugins_url('dist/blocks.style.build.css', dirname(__FILE__)), // Block style CSS.
		array('wp-editor'), // Dependency to include the CSS after it.
		null // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.style.build.css' ) // Version: File modification time.
	);

	// Register block editor script for backend.
	wp_register_script(
		'themestash_rct_blocks_js', // Handle.
		plugins_url('/dist/blocks.build.js', dirname(__FILE__)), // Block.build.js: We register the block here. Built with Webpack.
		array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'), // Dependencies, defined above.
		null, // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
		true // Enqueue the script in the footer.
	);

	// Register block editor styles for backend.
	wp_register_style(
		'themestash_rct_blocks_css', // Handle.
		plugins_url('dist/blocks.editor.build.css', dirname(__FILE__)), // Block editor CSS.
		array('wp-edit-blocks'), // Dependency to include the CSS after it.
		null // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' ) // Version: File modification time.
	);

	/**
	 * Register Gutenberg block on server-side.
	 *
	 * Register the block on server-side to ensure that the block
	 * scripts and styles for both frontend and backend are
	 * enqueued when the editor loads.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type#enqueuing-block-scripts
	 * @since 1.16.0
	 */
	$attributes = array(
		'title' => array(
			'type' => 'array'
		),
		'mediaID' => array(
			'type' => 'integer'
		),
		'cookingTime' => array(
			'type' => 'string'
		),
		'preparationTime' => array(
			'type' => 'string'
		),
		'nutrition' => array(
			'type' => 'string'
		),
		'servings' => array(
			'type' => 'string'
		),
		'ingredients' => array(
			'type' => 'array',
			'items' => 'object'
		)
	);
	register_block_type(
		'themestash/recipe-card',
		array(
			// Enqueue blocks.style.build.css on both frontend & backend.
			'style'         => 'themestash_rct_style_css',
			// Enqueue blocks.build.js in the editor only.
			'editor_script' => 'themestash_rct_blocks_js',
			// Enqueue blocks.editor.build.css in the editor only.
			'editor_style'  => 'themestash_rct_blocks_css',
			'attributes' => $attributes,
			'render_callback' => 'themestash_rct_recipe_json',
		)
	);
}

// Hook: Block assets.
add_action('init', 'themestash_rct_block_assets');


// Function to enqueue the functions
function themestash_rct_enqueue_scripts()
{
	// Register block editor script for backend.
	wp_enqueue_script(
		'themestash_rct_functions', // Handle.
		plugins_url('/dist/functions.js', dirname(__FILE__)), // Block.build.js: We register the block here. Built with Webpack.
		array('jquery'), // Dependencies, defined above.
		null, // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
		true // Enqueue the script in the footer.
	);
}

add_action('wp_enqueue_scripts', 'themestash_rct_enqueue_scripts');



function themestash_rct_block_category($categories, $post)
{
	return array_merge(
		$categories,
		array(
			array(
				'slug' => 'themestash-blocks',
				'title' => __('ThemeStash Blocks', 'themestash-recipe-card'),
			),
		)
	);
}
add_filter('block_categories', 'themestash_rct_block_category', 10, 2);


function themestash_rct_recipe_json($attributes, $content)
{

	if (is_admin() || wp_doing_ajax()) return false;

	$post_ID = get_the_ID();

	$post_author = get_post_field('post_author', $post_ID);
	$post_author_name = get_the_author_meta('display_name', $post_author);
	$post_date = get_the_date('c');
	$recipe_media = isset($attributes['mediaID']) ? wp_get_attachment_image_url($attributes['mediaID'], 'full') : get_the_post_thumbnail($post_ID, 'full');
	$recipe_description = has_excerpt($post_ID) ? get_the_excerpt($post_ID) : substr(strip_tags(get_the_content()), 0, 300);
	$recipe_title = isset($attributes['title']) ? $attributes['title'] : get_the_title($post_ID);
	$recipe_cooking_time = isset($attributes['cookingTime']) ? $attributes['cookingTime'] : '';
	$recipe_preparation_time = isset($attributes['preparationTime']) ? $attributes['preparationTime'] : '';
	$recipe_total_time = themestash_rct_get_int_value($recipe_cooking_time) + themestash_rct_get_int_value($recipe_preparation_time);
	$recipe_servings = isset($attributes['servings']) ? $attributes['servings'] : '';
	$recipe_nutrition = isset($attributes['nutrition']) ? $attributes['nutrition'] : '';
	$recipe_categories = wp_get_post_terms($post_ID, 'category');
	$term_tags = wp_get_post_terms($post_ID, 'post_tag');
	$custom_logo_id = get_theme_mod('custom_logo');

	$recipe_tags = array();
	foreach ($term_tags as $tag) {
		$recipe_tags[] = $tag->name;
	}
	$recipe_tags = json_encode($recipe_tags);

	preg_match_all("/\<div class=\"sh-step-inner-content-inner\"\>(.*?)\<\/div\>/is", $content, $matches);
	$recipe_instructions = '';
	foreach ($matches[0] as $key => $step) {
		$recipe_instructions .= '{"@type":"HowToStep","text":"' . trim(strip_tags($step))	 . '"},';
	}
	$recipe_instructions = rtrim($recipe_instructions, ',');


	preg_match_all("#<li>(.*?)(.*?)</li>#i", $content, $ingredients);
	$ingredients = end($ingredients);
	$recipe_ingredients = json_encode($ingredients);

	$json = '<script type="application/ld+json">
	{
			"@context": "https://schema.org/",
			"@type": "Recipe",
			"mainEntityOfPage": "' . get_the_permalink($post_ID) . '",
			"name": "' . $recipe_title . '",
			"image": {
				"@type": "ImageObject",
				"url": "' . $recipe_media . '",
				"height": 200,
				"width": 200
			},
			"author": {
				"@type":"Person",
				"name":"' . $post_author_name . '"
			},
			"datePublished": "' . $post_date . '",
			"description": "' . $recipe_description . '",
			"prepTime": "' . themestash_rct_transform_time_period($recipe_cooking_time) . '",
			"cookTime": "' . themestash_rct_transform_time_period($recipe_preparation_time) . '",
			"totalTime": "' . themestash_rct_transform_time_period($recipe_total_time) . '",
			"recipeYield": "' . $recipe_servings . '",
			"recipeCuisine": "Global",
			"keywords": ' . $recipe_tags . ',
			"recipeCategory": "' . $recipe_categories[0]->name . '",
			"nutrition": {
				"@type": "NutritionInformation",
				"servingSize": "' .	$recipe_servings . '",
				"calories": "' . $recipe_nutrition . '"
			},
			"recipeIngredient": ' . 	trim($recipe_ingredients) . ',
			"recipeInstructions": [' . $recipe_instructions  . '],
			"publisher": {
				"@type": "Organization",
				"name": "' . get_bloginfo('name') . '",
				"logo": {
					"@type": "ImageObject",
					"url": "' . wp_get_attachment_image_url($custom_logo_id, 'full') . '",
					"width": 600,
					"height": 60
				}
			}
		}
	</script>';

	return $json . $content;
}


function themestash_rct_get_int_value($string)
{
	if (is_numeric($string)) {
		return $string;
	}

	$re = '/\d+/s';
	preg_match($re, $string, $matches);

	return isset($matches[0]) ? (int)$matches[0] : 0;
}
function themestash_rct_transform_time_period($value)
{
	$time = themestash_rct_get_int_value($value);
	$hours = floor($time / 60);
	$days = round($hours / 24);
	$minutes = ($time % 60);
	$period = 'PT';

	if ($days) {
		$hours = ($hours % 24);
		$period .= $days . 'D';
	}

	if ($hours) {
		$period .= $hours . 'H';
	}

	if ($minutes) {
		$period .= $minutes . 'M';
	}

	return $period;
}


/**
 * Load the plugin textdomain
 *
 * @since 1.0.0
 */
function themestash_rct_blocks_init()
{
	load_plugin_textdomain(
		'themestash-recipe-card',
		false,
		basename(dirname(__FILE__)) . '/languages'
	);
}
add_action('init', 'themestash_rct_blocks_init');
