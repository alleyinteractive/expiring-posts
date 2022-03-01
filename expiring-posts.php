<?php
/**
 * Automatically expire posts after some time.
 *
 * Plugin Name: Expiring Posts
 * Plugin URI: https://github.com/alleyinteractive/expiring-posts
 * Description: Automatic expiration of posts.
 * Version: 0.1.0
 * Author: Alley
 *
 * @package Expiring_Posts
 */

// Require the main class.
require_once __DIR__ . '/inc/class-expiring-posts.php';

Expiring_Posts\Expiring_Posts::instance();

/**
 * Register a post type that should automatically expire.
 *
 * @param string $post_type Post type to register.
 * @param array  $args {
 *     Arguments to add the post type.
 *
 *     @type string $action       Action to apply to the post (draft/trash/delete)
 *     @type int    $expire_after Number of seconds for the post to be expired after,
 *                                defaults to a year.
 * }
 */
function expiring_posts_add_post_type( string $post_type, array $args = [] ) {
	Expiring_Posts\Expiring_Posts::instance()->add_post_type( $post_type, $args );
}

/**
 * Unregister a post type that should automatically expire.
 *
 * @param string $post_type Post type to register.
 */
function expiring_posts_remove_post_type( string $post_type ) {
	Expiring_Posts\Expiring_Posts::instance()->remove_post_type( $post_type );
}
