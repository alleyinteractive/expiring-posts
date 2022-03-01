<?php
namespace Expiring_Posts\Tests;

use Expiring_Posts\Expiring_Posts;
use Mantle\Testing\Framework_Test_Case;

class Test_Run extends Framework_Test_Case {
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

		// Create a post that should not be expired.
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

		// Create a post that should not be expired.
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
}
