<?php
/**
 * Expiring Posts Tests: Bootstrap
 *
 * @package Expiring_Posts
 */

/**
 * Visit {@see https://mantle.alley.com/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	// Rsync the plugin to plugins/expiring-posts when testing.
	->maybe_rsync_plugin()
	// Load the main file of the plugin.
	->loaded( fn () => require_once __DIR__ . '/../expiring-posts.php' )
	->install();
