<?php

/**
 * Local settings for development environments.
 *
 * Follow these steps to use this file:
 *   1. Copy this file and name it 'settings.local.php'
 *   2. Adjust database settings
 *   3. See bcct.services.yml and follow the instructions there
 *
 * See default.settings.php and example.settings.local.php for extensive
 * documentation about each setting.
 */

// Database settings:
$databases['default']['default'] = array (
    'database' => 'drupal',
    'username' => 'drupal',
    'password' => 'drupal',
    'prefix' => '',
    'host' => 'localhost',
    'port' => '3306',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
);

// Enable local development services.
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.local.yml';

// Show all error messages, with backtrace information.
$config['system.logging']['error_level'] = 'verbose';

// Disable CSS and JS aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Enable access to rebuild.php.
$settings['rebuild_access'] = TRUE;

// Skip file system permissions hardening.
$settings['skip_permissions_hardening'] = TRUE;

// Assertions.
assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

// Allow test modules and themes to be installed.
# $settings['extension_discovery_scan_tests'] = TRUE;
