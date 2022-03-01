<?php
/**
 * Expiring_Posts class file
 *
 * @package Expiring_Posts
 */

namespace Expiring_Posts;

/**
 * Expiring Posts Manager
 */
class Expiring_Posts {
	/**
	 * Instance of the manager.
	 *
	 * @var static
	 */
	protected static $instance;

	/**
	 * Post types that are registered for expiration.
	 *
	 * @var array<string, array>
	 */
	protected array $post_types = [];

	/**
	 * Get the instance of this singleton
	 *
	 * @return static
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		// var_dump('aaa');exit;
	}

	/**
	 * Register a post type that should automatically expire.
	 *
	 * @param string $post_type Post type to register.
	 * @param array $args Arguments for the expiration.
	 */
	public function add_post_type( string $post_type, array $args ) {
		if ( ! post_type_exists( $post_type ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					__( 'Post type does not exist for expiration: %s', 'expiring-posts' ),
					$post_type,
				),
				'0.1.0',
			);

			return;
		}

		$args = wp_parse_args(
			$args,
			[
				// Action to apply to the post after it is expired (draft/trash).
				'action'       => 'draft',
				// Number of seconds for the post to be expired after.
				'expire_after' => YEAR_IN_SECONDS,
			],
		);

		$this->post_types[ $post_type ] = $args;
	}

	/**
	 * Unregister a post type that should automatically expire.
	 *
	 * @param string $post_type Post type to unregister.
	 */
	public function remove_post_type( string $post_type ) {
		unset( $this->post_types[ $post_type ] );
	}

	/**
	 * Clear the post types that are registered for expiration.
	 */
	public function clear_post_types() {
		$this->post_types = [];
	}
}
