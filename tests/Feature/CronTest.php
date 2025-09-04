<?php
/**
 * Expiring Posts Tests: Cron Feature Test
 *
 * @package Expiring_Posts
 */

namespace Expiring_Posts\Tests\Feature;

use Expiring_Posts\Expiring_Posts;
use Expiring_Posts\Tests\TestCase;

/**
 * A test suite for cron functionality.
 */
class CronTest extends TestCase {
	public function test_expiration_check_scheduled() {
		Expiring_Posts::instance();

		$this->assertInCronQueue( Expiring_Posts::CRON_HOOK );
	}
}