<?php
/**
 * Expiring_Posts class file
 *
 * @package Expiring_Posts
 */

namespace Expiring_Posts;

use InvalidArgumentException;
use WP_Post;

/**
 * Expiring Posts Manager
 */
class Expiring_Posts {
	/**
	 * Cron hook to run expiration check.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'expiring_posts_check_expiration';

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
		add_action( static::CRON_HOOK, [ $this, 'run_expiration_check' ] );
		add_action( 'init', [ $this, 'schedule_next_run' ] );
	}

	/**
	 * Register a post type that should automatically expire.
	 *
	 * @throws InvalidArgumentException Thrown on invalid action.
	 * @throws InvalidArgumentException Thrown on invalid expire_after.
	 *
	 * @param string $post_type Post type to register.
	 * @param array  $args {
	 *     Arguments to add the post type.
	 *
	 *     @type string          $action       Action to apply to the post upon expiration: update/draft/trash/delete.
	 *     @type int             $expire_after Number of seconds for the post to be expired after,
	 *                                         defaults to a year.
	 *     @type array|callable  $update_args  Arguments to update the post with upon expiration.
	 * }
	 */
	public function add_post_type( string $post_type, array $args ) {
		if ( ! post_type_exists( $post_type ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: 1: post type */
					esc_html__( 'Post type does not exist for expiration: %s', 'expiring-posts' ),
					esc_html( $post_type ),
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

		// Validate the arguments for the post type.
		if ( ! in_array( $args['action'], [ 'delete', 'draft', 'trash', 'update' ], true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: 1: action */
					__( 'Invalid action for expiration (expected delete/draft/trash/update): %s', 'expiring-posts' ),
					$args['action'],
				),
			);
		}

		// Validate the expire after value.
		if ( ! is_int( $args['expire_after'] ) || $args['expire_after'] <= 0 ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: 1: expire after value */
					__( 'Invalid expire after value for expiration (expected integer): %s', 'expiring-posts' ),
					$args['expire_after'],
				),
			);
		}

		// Throw an exception if no update arguments are provided.
		if ( 'update' === $args['action'] && empty( $args['update_args'] ) ) {
			throw new InvalidArgumentException(
				__( 'Update action requires update_args', 'expiring-posts' ),
			);
		}

		$this->post_types[ $post_type ] = $args;
	}

	/**
	 * Retrieve the settings for a registered post type.
	 *
	 * @param string $post_type Post type to retrieve settings for.
	 * @return array|null
	 */
	public function get_post_type( string $post_type ): ?array {
		return $this->post_types[ $post_type ] ?? null;
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

	/**
	 * Run the expiration check.
	 */
	public function run_expiration_check() {
		/**
		 * Filter the number of posts to check per page.
		 *
		 * @param int $posts_per_page Number of posts to check per page.
		 */
		$posts_per_page = (int) apply_filters( 'expiring_posts_posts_per_page', 1000 );

		$now = time();

		foreach ( $this->post_types as $post_type => $settings ) {
			$page = 1;

			while ( true ) {
				$threshold = $now - $settings['expire_after'];

				/**
				 * Filters the arguments used to query for expired posts.
				 *
				 * @param array $posts_to_expire_args Query arguments.
				 * @param int   $now                  Current timestamp.
				 */
				$posts_to_expire_args = apply_filters(
					'expiring_posts_query_args',
					[
						'ignore_sticky_posts' => true,
						'date_query'          => [
							[
								'before' => gmdate( 'c', $threshold ),
								'column' => 'post_modified_gmt',
							],
						],
					],
					$now,
				);

				// Enforce some arguments.
				$posts_to_expire_args = array_merge(
					$posts_to_expire_args,
					[
						'fields'                 => 'ids',
						'paged'                  => $page++,
						'post_type'              => $post_type,
						'posts_per_page'         => $posts_per_page,
						'suppress_filters'       => false,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					],
				);

				$posts_to_expire = get_posts( $posts_to_expire_args ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts

				if ( empty( $posts_to_expire ) ) {
					break;
				}

				foreach ( $posts_to_expire as $post_id ) {
					$post = get_post( $post_id );

					if ( ! $post ) {
						continue;
					}

					// Check if the post is actually expired.
					if ( ! $this->is_post_expired( $post, $threshold ) ) {
						continue;
					}

					// Run the action against the expired post.
					switch ( $settings['action'] ) {
						case 'draft':
							wp_update_post(
								[
									'ID'          => $post_id,
									'post_status' => 'draft',
								]
							);
							break;

						case 'delete':
							wp_delete_post( $post_id, true );
							break;

						case 'trash':
							wp_trash_post( $post_id );
							break;

						case 'update':
							wp_update_post(
								array_merge(
									[
										'ID' => $post_id,
									],
									is_callable( $settings['update_args'] ) ? $settings['update_args']( $post ) : $settings['update_args'],
								)
							);
							break;
					}

					/**
					 * Fired when a post is expired.
					 *
					 * @param int     $post_id Post ID.
					 * @param WP_Post $post Post object.
					 */
					do_action( 'expiring_posts_expired', $post_id, $post );
				}
			}
		}

		$this->schedule_next_run();
	}

	/**
	 * Determine if a post is expired.
	 *
	 * @param WP_Post $post Post to check.
	 * @param int     $threshold Threshold to check against.
	 * @return bool
	 */
	public function is_post_expired( WP_Post $post, int $threshold ): bool {
		$is_expired = get_the_date( 'U', $post->ID ) < $threshold
			&& get_the_modified_date( 'U', $post->ID ) < $threshold;

		/**
		 * Determine if a post is expired.
		 *
		 * @param bool    $is_expired Whether the post is expired.
		 * @param WP_Post $post Post to check.
		 * @param int    $threshold Threshold to check against (unix timestamp).
		 */
		return (bool) apply_filters(
			'expiring_posts_is_post_expired',
			$is_expired,
			$post,
			$threshold,
		);
	}

	/**
	 * Schedule the next run of the expiration check.
	 */
	public function schedule_next_run() {
		if ( ! wp_next_scheduled( static::CRON_HOOK ) ) {
			/**
			 * Filter the interval between expiration checks. A minimum of one
			 * minute is enforced.
			 *
			 * @param int $interval Interval in seconds (defaults to 1 hour).
			 */
			$next_run_interval = (int) apply_filters(
				'expiring_posts_cron_interval',
				HOUR_IN_SECONDS,
			);

			wp_schedule_single_event(
				time() + max( MINUTE_IN_SECONDS, $next_run_interval ),
				static::CRON_HOOK,
			);
		}
	}
}
