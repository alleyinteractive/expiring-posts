<?php
namespace Expiring_Posts\Tests;

use Expiring_Posts\Expiring_Posts;
use Mantle\Testing\Framework_Test_Case;

class Test_Cron extends Framework_Test_Case {
	public function test_expiration_check_scheduled() {
		Expiring_Posts::instance();

		$this->assertInCronQueue( Expiring_Posts::CRON_HOOK );
	}
}
