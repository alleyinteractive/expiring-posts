<?php
namespace Expiring_Posts\Tests;

use Expiring_Posts\Expiring_Posts;
use Mantle\Testkit\Test_Case;

class Test_Cron extends Test_Case {
	public function test_expiration_check_scheduled() {
		Expiring_Posts::instance();

		$this->assertInCronQueue( Expiring_Posts::CRON_HOOK );
	}
}
