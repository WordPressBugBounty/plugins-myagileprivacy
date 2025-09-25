<?php

if( !defined( "MAP_PLUGIN_NAME" ) )
{
	exit("Not allowed.");
}

/**
* Core definitions
* *
* @link       https://www.myagileprivacy.com/
* @package    MyAgilePrivacy
* @subpackage MyAgilePrivacy/includes
*/
/**
* Core plugin class.
*
*
* @package    MyAgilePrivacy
* @subpackage MyAgilePrivacy/includes
* @author     https://www.myagileprivacy.com/
*/
/**
* Helper class to find a shortcode across all Pages (including page builders' meta).
*
* Public API:
*   MyAgilePrivacyPoliciesHelper::find_shortcode_in_pages('map_test1', array(
*       'post_type'        => array('page'),
*       'post_status'      => array('publish','draft','private'),
*       'batch'            => 200,
*       'include_meta'     => true,
*       'max_decode_bytes' => 10485760,
*       'custom_pattern'   => null // optional PCRE to override default shortcode pattern
*   ));
*
* Returns:
*   array('check' => bool, 'ids' => array of IDs)
*
* Example for fixed texts:
*   $res = MyAgilePrivacyPoliciesHelper::find_shortcode_in_pages(
*       'myagileprivacy_fixed_text',
*       array(
*           'custom_pattern' => '/\[\s*myagileprivacy_fixed_text\b(?=[^\]]*\btext=(["\'])(?:cookie_policy|personal_data_policy)\1)[^\]]*\]/i'
*       )
*   );
*/
class MyAgilePrivacyPoliciesHelper
{
	/**
	* Find a shortcode in all Pages (or provided post types), including page builder meta.
	* Returns array('check' => bool, 'ids' => array of IDs)
	*/
	public static function find_shortcode_in_pages($shortcode, $options = array())
	{
		// Prevent timeouts on large sites
		@set_time_limit(0);

		$defaults = array(
			"post_type"        => array("page"),
			"post_status"      => array("publish"),
			"batch"            => 200, // WP_Query batch size
			"include_meta"     => true, // scan all postmeta as well
			"max_decode_bytes" => 10485760, // 10MB: limit JSON/serialized decoding
			"custom_pattern"   => null // optional PCRE to override default shortcode pattern
		);

		if (function_exists("wp_parse_args")) {
			$args = wp_parse_args($options, $defaults);
		} else {
			// Basic fallback (shouldn't be needed in WordPress)
			$args = $defaults;
			if (is_array($options)) {
				foreach ($options as $k => $v) {
					$args[$k] = $v;
				}
			}
		}

		// Use custom pattern if provided, otherwise build from shortcode tag
		$pattern =
			isset($args["custom_pattern"]) &&
			is_string($args["custom_pattern"]) &&
			$args["custom_pattern"] !== ""
			? $args["custom_pattern"]
			: self::build_shortcode_pattern($shortcode);

		$ids = array();
		$paged = 1;

		while (true) {
			$q = new WP_Query(array(
				"post_type"      => $args["post_type"],
				"post_status"    => $args["post_status"],
				"posts_per_page" => (int) $args["batch"],
				"paged"          => $paged,
				"fields"         => "ids",
				"no_found_rows"  => true
			));

			$posts =
				is_object($q) && isset($q->posts) && is_array($q->posts)
				? $q->posts
				: array();

			if (empty($posts)) {
				wp_reset_postdata();
				break;
			}

			foreach ($posts as $post_id) {
				$found = false;

				// 1) Main content
				$content = get_post_field("post_content", $post_id, "raw");
				if (
					is_string($content) &&
					$content !== "" &&
					preg_match($pattern, $content)
				) {
					$found = true;
				}

				// 2) Post meta (Elementor, Beaver Builder, Oxygen, etc.)
				if (!$found && !empty($args["include_meta"])) {
					$meta = get_post_meta($post_id);
					if (!empty($meta) && is_array($meta)) {
						foreach ($meta as $key => $values) {
							if (!is_array($values)) {
								continue;
							}
							foreach ($values as $val) {
								if (
									self::scan_value_for_pattern_safe(
										$val,
										$pattern,
										(int) $args["max_decode_bytes"]
									)
								) {
									$found = true;
									break 2; // found in this post
								}
							}
						}
					}
				}

				if ($found) {
					$ids[] = (int) $post_id;
				}
			}

			wp_reset_postdata();

			// Continue if this batch was full
			if (count($posts) < (int) $args["batch"]) {
				break;
			}
			$paged++;
		}

		return array(
			"check" => !empty($ids),
			"ids"   => $ids
		);
	}

	/**
	* Build a regex for matching the shortcode:
	* Matches [tag], [tag ...], [tag/], [/tag] — avoids false positives like [tag123]
	*/
	private static function build_shortcode_pattern($shortcode)
	{
		$tag = preg_quote(trim($shortcode), "/");
		return "/\[(?:\/)?" . $tag . "(?=[\s\/\]])(?:[^\]]*)\]/i";
	}

	/**
	* Scan value for the pattern in strings, JSON, serialized, arrays/objects (recursive) with guard rails
	*/
	private static function scan_value_for_pattern_safe(
		$value,
		$pattern,
		$max_bytes
	) {
		if (is_string($value)) {
			// Direct string check
			if ($value !== "" && preg_match($pattern, $value)) {
				return true;
			}

			// Skip huge strings to avoid heavy JSON/unserialize operations
			$len = strlen($value);
			if ($max_bytes > 0 && $len > $max_bytes) {
				return false;
			}

			// JSON (only if json_decode is available)
			if (function_exists("json_decode")) {
				$t = ltrim($value);
				if (
					$t !== "" &&
					(substr($t, 0, 1) === "{" || substr($t, 0, 1) === "[")
				) {
					$decoded = json_decode($value, true);
					if (function_exists("json_last_error")) {
						if (json_last_error() === JSON_ERROR_NONE) {
							if (self::deep_scan($decoded, $pattern)) {
								return true;
							}
						}
					} else {
						// Older PHP fallback: if json_last_error doesn't exist
						if (is_array($decoded) || is_object($decoded)) {
							if (self::deep_scan($decoded, $pattern)) {
								return true;
							}
						}
					}
				}
			}

			// Serialized: avoid objects (O: or C:) to prevent side effects
			if (self::is_serialized_without_objects($value)) {
				$decoded = @unserialize($value);
				if ($decoded !== false || $value === "b:0;") {
					if (self::deep_scan($decoded, $pattern)) {
						return true;
					}
				}
			}

			return false;
		}

		// Arrays/objects: deep scan
		if (is_array($value) || is_object($value)) {
			return self::deep_scan($value, $pattern);
		}

		return false;
	}

	/**
	* Recursive scan of arrays/objects for a regex match
	*/
	private static function deep_scan($data, $pattern)
	{
		if (is_string($data)) {
			return preg_match($pattern, $data) === 1;
		}
		if (is_array($data)) {
			foreach ($data as $v) {
				if (self::deep_scan($v, $pattern)) {
					return true;
				}
			}
			return false;
		}
		if (is_object($data)) {
			foreach (get_object_vars($data) as $v) {
				if (self::deep_scan($v, $pattern)) {
					return true;
				}
			}
			return false;
		}
		return false;
	}

	/**
	* Detect serialized strings that DO NOT contain objects (no O:/C:)
	* Uses WordPress is_serialized if available, plus an object guard.
	*/
	private static function is_serialized_without_objects($data)
	{
		if (!is_string($data)) {
			return false;
		}
		$data = trim($data);

		// Prevent unserializing objects
		if (preg_match("/^(O|C):\d+:/", $data)) {
			return false;
		}
		if (function_exists("is_serialized")) {
			return is_serialized($data);
		}
		// Basic fallback: a:, s:, i:, d:, b:, N; (but not O:/C:)
		if ($data === "N;") {
			return true;
		}
		return (bool) preg_match("/^(a|s|i|d|b):/", $data);
	}
}