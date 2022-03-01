<?php
namespace Expiring_Posts\Tests;

use Mantle\Testing\Framework_Test_Case;

class Test_Expiring_Posts extends Framework_Test_Case {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		register_post_type( 'expiration-test', [
			'public' => true,
			'label' => 'Expiration Test',
		] );
	}

	public function test_registering_post_type() {
		$this->assertTrue( post_type_exists( 'expiration-test' ) );
	}
}
