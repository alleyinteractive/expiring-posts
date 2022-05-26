<?php
namespace Expiring_Posts\Tests;

use Expiring_Posts\Expiring_Posts;
use Mantle\Testkit\Test_Case;

class Test_Run extends Test_Case {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		register_post_type( 'expiration-test', [
			'public' => true,
			'label' => 'Expiration Test',
		] );
	}

	protected function setUp(): void {
		parent::setUp();

		Expiring_Posts::instance()->clear_post_types();
	}

	public function test_expiration_run_draft() {
		$this->expectApplied( 'expiring_posts_expired' )->times( 5 );
		$this->expectApplied( 'expiring_posts_is_post_expired' )->times( 5 );

		expiring_posts_add_post_type(
			'expiration-test',
			[
				'expire_after' => DAY_IN_SECONDS,
			],
		);

		$expiration_date = strtotime( '1 month ago' );

		// Setup some posts that should be expired.
		$expired = static::factory()->post->create_many( 5, [
			'post_type' => 'expiration-test',
			'post_date' => date( 'Y-m-d H:i:s', $expiration_date ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $expiration_date ),
			'post_modified' => date( 'Y-m-d H:i:s', $expiration_date ),
			'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', $expiration_date ),
		] );

		// Create posts that should not be expired.
		$non_expired = static::factory()->post->create_many( 5, [
			'post_type' => 'expiration-test',
		] );

		foreach ( $expired as $post_id ) {
			$this->assertEquals( 'publish', get_post_status( $post_id ) );
		}

		Expiring_Posts::instance()->run_expiration_check();

		foreach ( $expired as $post_id ) {
			$this->assertEquals( 'draft', get_post_status( $post_id ), 'Expired post should be drafted.' );
		}

		foreach ( $non_expired as $post_id ) {
			$this->assertEquals( 'publish', get_post_status( $post_id ), 'Non-expired post should be published.' );
		}
	}

	public function test_expiration_run_trash() {
		$this->expectApplied( 'expiring_posts_expired' )->times( 5 );

		expiring_posts_add_post_type(
			'expiration-test',
			[
				'action'       => 'trash',
				'expire_after' => DAY_IN_SECONDS,
			],
		);

		$expiration_date = strtotime( '1 month ago' );

		// Setup some posts that should be expired.
		$expired = static::factory()->post->create_many( 5, [
			'post_type' => 'expiration-test',
			'post_date' => date( 'Y-m-d H:i:s', $expiration_date ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $expiration_date ),
			'post_modified' => date( 'Y-m-d H:i:s', $expiration_date ),
			'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', $expiration_date ),
		] );

		// Create posts that should not be expired.
		$non_expired = static::factory()->post->create_many( 5, [
			'post_type' => 'expiration-test',
		] );

		foreach ( $expired as $post_id ) {
			$this->assertEquals( 'publish', get_post_status( $post_id ) );
		}

		Expiring_Posts::instance()->run_expiration_check();

		foreach ( $expired as $post_id ) {
			$this->assertEquals( 'trash', get_post_status( $post_id ), 'Expired post should be trashed.' );
		}

		foreach ( $non_expired as $post_id ) {
			$this->assertEquals( 'publish', get_post_status( $post_id ), 'Non-expired post should be published.' );
		}
	}

	public function test_expiration_double_check() {
		$this->expectApplied( 'expiring_posts_expired' )->never();
		$this->expectApplied( 'expiring_posts_is_post_expired' )->times( 5 );

		expiring_posts_add_post_type( 'expiration-test', [
			'expire_after' => DAY_IN_SECONDS,
		] );

		// Create posts that should not be expired.
		$non_expired = static::factory()->post->create_many( 5, [
			'post_type' => 'expiration-test',
		] );

		// Force the non-expired posts into the query result.
		add_filter(
			'posts_pre_query',
			function () use ( $non_expired ) {
				// Ensure it is only applied once.
				remove_all_filters( 'posts_pre_query' );

				return $non_expired;
			},
		);

		Expiring_Posts::instance()->run_expiration_check();

		foreach ( $non_expired as $post_id ) {
			$this->assertEquals( 'publish', get_post_status( $post_id ), 'Non-expired post should be published.' );
		}
	}

	public function test_expiration_run_override() {
		$this->expectApplied( 'expiring_posts_expired' )->never();
		$this->expectAdded( 'expiring_posts_is_post_expired' )->times( 5 );

		expiring_posts_add_post_type(
			'expiration-test',
			[
				'action'       => 'trash',
				'expire_after' => DAY_IN_SECONDS,
			],
		);

		$expiration_date = strtotime( '1 month ago' );

		$expired = static::factory()->post->create_many( 5, [
			'post_type' => 'expiration-test',
			'post_date' => date( 'Y-m-d H:i:s', $expiration_date ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $expiration_date ),
			'post_modified' => date( 'Y-m-d H:i:s', $expiration_date ),
			'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', $expiration_date ),
		] );

		add_filter( 'expiring_posts_is_post_expired', fn () => false );

		Expiring_Posts::instance()->run_expiration_check();

		foreach ( $expired as $post_id ) {
			$this->assertEquals( 'publish', get_post_status( $post_id ), 'Overridden expired post should not be trashed.' );
		}
	}
}
