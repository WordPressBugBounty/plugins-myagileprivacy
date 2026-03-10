<?php

/**
 * Core definitions
 * *
 * @link       https://www.myagileprivacy.com/
 *
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/includes
 */

define( 'MAP_PLUGIN_VERSION', '3.3.1' );
define( 'MAP_PLUGIN_NAME', 'my-agile-privacy' );
define( 'MAP_PLUGIN_SLUG', 'myagileprivacy' );
define( 'MAP_PLUGIN_FILENAME', realpath( dirname( __FILE__ ) . '/../my-agile-privacy.php' ) );
define( 'MAP_USE_MINIFIED_FILES', true );
define( 'MAP_DEV_MODE', false );
define( 'MAP_SOFTWARE_KEY', 'map_wp' );
define( 'MAP_PLUGIN_TEXTDOMAIN', 'MAP_txt' );
define( 'MAP_PLUGIN_DB_KEY_PREFIX', 'MyAgilePrivacy-' );
define( 'MAP_PLUGIN_SETTINGS_FIELD', MAP_PLUGIN_DB_KEY_PREFIX . '1.0.0' );
define( 'MAP_PLUGIN_JS_DETECTED_FIELDS', MAP_PLUGIN_DB_KEY_PREFIX . '_detected_fields' );
define( 'MAP_PLUGIN_RCONFIG', MAP_PLUGIN_DB_KEY_PREFIX . '_rconfig' );
define( 'MAP_PLUGIN_L_ALLOWED', MAP_PLUGIN_DB_KEY_PREFIX . '_l_allowed' );
define( 'MAP_PLUGIN_COMPLIANCE_REPORT', MAP_PLUGIN_DB_KEY_PREFIX . '_compliance_report' );
define( 'MAP_PLUGIN_STATS', MAP_PLUGIN_DB_KEY_PREFIX . 'stats' );
define( 'MAP_PLUGIN_DO_SYNC_NOW', MAP_PLUGIN_DB_KEY_PREFIX . 'do_sync_now' );
define( 'MAP_PLUGIN_DO_SYNC_LAST_EXECUTION', MAP_PLUGIN_DB_KEY_PREFIX . 'do_sync_last_execution' );
define( 'MAP_PLUGIN_VALIDATION_TIMESTAMP', MAP_PLUGIN_DB_KEY_PREFIX . 'validation_timestamp' );
define( 'MAP_PLUGIN_DB_VERSION', MAP_PLUGIN_DB_KEY_PREFIX . 'db_version_number' );
define( 'MAP_PLUGIN_DB_VERSION_NUMBER', 1 );
define( 'MAP_PLUGIN_SYNC_IN_PROGRESS', MAP_PLUGIN_DB_KEY_PREFIX . 'sync_in_progress' );
define( 'MAP_MANIFEST_ASSOC', MAP_PLUGIN_DB_KEY_PREFIX . 'manifest' );
define( 'MAP_PLUGIN_COUNTRIES', MAP_PLUGIN_DB_KEY_PREFIX . '_countries' );
define( 'MAP_POST_TYPE_COOKIES', 'my-agile-privacy-c' );
define( 'MAP_POST_TYPE_POLICY', 'my-agile-privacy-p' );
define( 'MAP_PAGE_SLUG', 'my-agile-privacy' );
define( 'MAP_API_ENDPOINT', 'https://auth.myagileprivacy.com/wp_api' );
define( 'MAP_INLINE_SCRIPT_EXTRA_ATTRS', 'data-no-minify="1" data-no-optimize="1" data-no-defer="1" consent-skip-blocker="1" nowprocket data-cfasync="false"' );
define( 'MAP_LEGIT_SYNC_TRESHOLD', 10800 );
define( 'MAP_AUTORESET_SYNC_TRESHOLD', 259200 ); // 3 days
define( 'MAP_PLUGIN_ACTIVATION_DATE', MAP_PLUGIN_DB_KEY_PREFIX.'-activation_date' );
define( 'MAP_REVIEW_STATUS', MAP_PLUGIN_DB_KEY_PREFIX.'-review_status' );
define( 'MAP_NOTICE_LAST_SHOW_TIME', MAP_PLUGIN_DB_KEY_PREFIX.'-notice_last_show_time' );
define( 'MAP_BYPASS_LICENSE_TRESHOLD', 86400 ); // 1 day: 24 * 60 * 60
define( 'MAP_NOTICE_FIRST_TRESHOLD', 604800 ); // 7 days: 7 * 24 * 60 * 60
define( 'MAP_NOTICE_SECOND_TRESHOLD', 12960000 ); // 5 months: 5 * 30 * 24 * 60 * 60
define( 'MAP_ASSETS_EXCLUSION_PATTERNS', array(
	'plugins/myagileprivacy/',
	'wp-content/local-cache/'
) );
define( 'MAP_DB_PATCH_2_DONE', MAP_PLUGIN_DB_KEY_PREFIX.'_patch_2_done' );
define( 'MAP_DB_PATCH_3_DONE', MAP_PLUGIN_DB_KEY_PREFIX.'_patch_3_done' );
define( 'MAP_EXPORT_FORMAT_VERSION', '2.0.0' );
define( 'MAP_SUMMARY_VERSION', '2.0.0' );
define( 'MAP_INTEGRITY_CHECK_VERSION', '2.0.0' );