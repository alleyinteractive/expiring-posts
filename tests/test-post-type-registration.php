<?php
namespace Expiring_Posts\Tests;

use Expiring_Posts\Expiring_Posts;
use InvalidArgumentException;
use Mantle\Testing\Framework_Test_Case;

class Test_Post_Type_Registration extends Framework_Test_Case {
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

	public function test_registering_post_type_defaults() {
		$this->assertTrue( post_type_exists( 'expiration-test' ) );

		expiring_posts_add_post_type( 'expiration-test' );

		$this->assertEquals(
			[
				'action'       => 'draft',
				'expire_after' => YEAR_IN_SECONDS,
			],
			Expiring_Posts::instance()->get_post_type( 'expiration-test' ),
		);

		expiring_posts_add_post_type(
			'expiration-test',
			[
				'action' => 'trash',
			],
		);

		$this->assertEquals(
			[
				'action'       => 'trash',
				'expire_after' => YEAR_IN_SECONDS,
			],
			Expiring_Posts::instance()->get_post_type( 'expiration-test' ),
		);
	}

	public function test_registering_post_type_bad_action() {
		$this->expectException( InvalidArgumentException::class );

		expiring_posts_add_post_type(
			'expiration-test',
			[
				'action' => 'invalid-action',
			],
		);
	}

	public function test_registering_post_type_bad_expire_after() {
		$this->expectException( InvalidArgumentException::class );

		expiring_posts_add_post_type(
			'expiration-test',
			[
				'expire_after' => 'unknown',
			],
		);
	}

	public function test_unregistering_post_type() {
		expiring_posts_add_post_type( 'expiration-test' );

		$this->assertNotEmpty(
			Expiring_Posts::instance()->get_post_type( 'expiration-test' ),
		);

		expiring_posts_remove_post_type( 'expiration-test' );

		$this->assertEmpty(
			Expiring_Posts::instance()->get_post_type( 'expiration-test' ),
		);
	}
}
