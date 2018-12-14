<?php

/**
 * Common settings for all environments.
 *
 * See default.settings.php for extensive documentation about each setting.
 */

// Database settings:
$databases = [];

// Location of the site configuration files.
$config_directories['sync'] = 'config_fdf332dsfcdsfWEFwefsWERew23fdSDFCscsdFDSFwerSDFGHYTjnMFGb3reytrddfGGd-dfwA/sync';

// Load services definition file.
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

// Salt for one-time login links, cancel links, form tokens, etc.
$settings['hash_salt'] = 'dwfwOtVEups45QFXuLQT-7NG-EAXSpvI6TehZWhfqX_0RNGfPl1zYVygqSUCtB1Gudrx_f6dfz';

// Access control for update.php script.
$settings['update_free_access'] = FALSE;

// The default list of directories that will be ignored by Drupal's file API.
$settings['file_scan_ignore_directories'][] = 'node_modules';
$settings['file_scan_ignore_directories'][] = 'bower_components';

// The default number of entities to update in a batch process.
$settings['entity_update_batch_size'] = 50;

// Disable cache.
// @todo Fix any problems so that normal caching can be used in production.
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

// -------------------------------------------------------------------------- //

// Deployment identifier.
# $settings['deployment_identifier'] = \Drupal::VERSION;

// External access proxy settings:
# $settings['http_client_config']['proxy']['http'] = 'http://proxy_user:proxy_pass@example.com:8080';
# $settings['http_client_config']['proxy']['https'] = 'http://proxy_user:proxy_pass@example.com:8080';
# $settings['http_client_config']['proxy']['no'] = ['127.0.0.1', 'localhost'];

// Reverse Proxy Configuration:
# $settings['reverse_proxy'] = TRUE;
# $settings['reverse_proxy_addresses'] = ['a.b.c.d', ...];
# $settings['reverse_proxy_header'] = 'X_CLUSTER_CLIENT_IP';
# $settings['reverse_proxy_proto_header'] = 'X_FORWARDED_PROTO';
# $settings['reverse_proxy_host_header'] = 'X_FORWARDED_HOST';
# $settings['reverse_proxy_port_header'] = 'X_FORWARDED_PORT';
# $settings['reverse_proxy_forwarded_header'] = 'FORWARDED';

// Page caching:
# $settings['omit_vary_cookie'] = TRUE;
# $settings['cache_ttl_4xx'] = 3600;
# $settings['form_cache_expiration'] = 21600;

// Class Loader.
# $settings['class_loader_auto_detect'] = FALSE;

// Authorized file system operations:
# $settings['allow_authorize_operations'] = FALSE;

// Default mode for directories and files written by Drupal.
# $settings['file_chmod_directory'] = 0775;
# $settings['file_chmod_file'] = 0664;

// Public file base URL:
# $settings['file_public_base_url'] = 'http://downloads.example.com/files';

// Public file path:
# $settings['file_public_path'] = 'sites/default/files';

// Private file path:
# $settings['file_private_path'] = '';

// Session write interval:
# $settings['session_write_interval'] = 180;

// String overrides:
# $settings['locale_custom_strings_en'][''] = [
#   'forum'      => 'Discussion board',
#   '@count min' => '@count minutes',
# ];

// A custom theme for the offline page:
# $settings['maintenance_theme'] = 'bartik';

// PHP settings:
# ini_set('pcre.backtrack_limit', 200000);
# ini_set('pcre.recursion_limit', 200000);

// Active configuration settings.
# $settings['bootstrap_config_storage'] = ['Drupal\Core\Config\BootstrapConfigStorageFactory', 'getFileStorage'];

// Configuration overrides.
# $config['system.file']['path']['temporary'] = '/tmp';
# $config['system.site']['name'] = 'My Drupal site';
# $config['system.theme']['default'] = 'stark';
# $config['user.settings']['anonymous'] = 'Visitor';

// Fast 404 pages:
# $config['system.performance']['fast_404']['exclude_paths'] = '/\/(?:styles)|(?:system\/files)\//';
# $config['system.performance']['fast_404']['paths'] = '/\.(?:txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
# $config['system.performance']['fast_404']['html'] = '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';

// Override the default service container class.
# $settings['container_base_class'] = '\Drupal\Core\DependencyInjection\Container';

// Override the default yaml parser class.
# $settings['yaml_parser_class'] = NULL;

// Trusted host configuration.
# $settings['trusted_host_patterns'] = array(
#   '^example\.com$',
#   '^.+\.example\.com$',
#   '^example\.org$',
#   '^.+\.example\.org$',
# );

// -------------------------------------------------------------------------- //

// Load local development override configuration, if available.
// Keep this code block at the end of this file to take full effect.
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}


// <DDSETTINGS>
// Please don't edit anything between <DDSETTINGS> tags.
// This section is autogenerated by Acquia Dev Desktop.
if (isset($_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR']) && file_exists($_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR'] . '/loc_bcct_dd.inc')) {
  require $_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR'] . '/loc_bcct_dd.inc';
}
// </DDSETTINGS>
