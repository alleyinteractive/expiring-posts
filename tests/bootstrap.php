<?php
/**
 * Testing using Mantle Framework
 */

namespace Expiring_Posts\Tests;

use function Mantle\Testing\tests_add_filter;

require_once __DIR__ . '/../vendor/wordpress-autoload.php';

\Mantle\Testing\install( function() {
	tests_add_filter(
		'muplugins_loaded',
		function () {
			require_once __DIR__ . '/../expiring-posts.php';
		}
	);
} );
