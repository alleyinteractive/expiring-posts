<?php
/**
 * Expiring Posts Tests: Base Test Class
 *
 * @package Expiring_Posts
 */

namespace Expiring_Posts\Tests;

use Mantle\Testing\Concerns\Prevent_Remote_Requests;
use Mantle\Testkit\Test_Case as TestkitTest_Case;

/**
 * Expiring Posts Base Test Case
 */
abstract class TestCase extends TestkitTest_Case {
	use Prevent_Remote_Requests;
}