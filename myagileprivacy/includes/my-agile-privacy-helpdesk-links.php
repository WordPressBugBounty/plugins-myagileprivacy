<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

/**
 * MAP_Helpdesk_Links
 *
 * Single source of truth for every helpdesk / support URL rendered in wp-admin.
 *
 * Locale rule (product decision, do not "fix"): admin locales whose prefix is
 * 'it' (it_IT, it_CH, ...) get the Italian URL; EVERY other locale (including
 * fr_FR, es_ES, de_DE) gets the English URL. The online helpdesk only exists
 * in Italian and English.
 *
 * Keys are stable identifiers, values are canonical slugs (direct 200, with
 * trailing slash). Site-side 301 redirects cover any
 * future slug rename; update this map only when a canonical URL changes.
 *
 * Entries whose value is '#' are placeholders for guides not published
 * yet: they render as inert stubs (see map_render_helpdesk_link()) and
 * skip UTM/anchor handling. Replacing '#' with the canonical URLs is the
 * only change needed to light up every call-site at once.
 *
 * Every public URL returned by get() carries UTM parameters so that
 * plugin-originated visits can be told apart in analytics ('utm_content'
 * carries the key). The private area app is a login, not a content page,
 * and stays untagged. See the 'map_helpdesk_utm_args' filter.
 */
final class MAP_Helpdesk_Links
{
	/**
	 * Full map: key => array( 'it' => URL, 'en' => URL ).
	 *
	 * @return array
	 */
	private static function get_map()
	{
		return array(
			'install' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-installare-myagileprivacy-sul-tuo-sito-web/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-install-myagileprivacy-on-your-website/',
			),
			'options_guide' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/guida-alle-opzioni-di-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/my-agile-privacy-options-guide/',
			),
			'customize_banner' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-personalizzare-il-cookie-banner-di-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-customise-the-my-agile-privacy-cookie-banner/',
			),
			'post_install_faq' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/domande-frequenti-post-installazione/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/post-installation-frequently-asked-questions/',
			),
			'private_area_guide' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/guida-allutilizzo-dellarea-privata/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/guide-to-using-the-private-area/',
			),
			// The private area web application itself (not a guide). One domain per language.
			'private_area_app' => array(
				'it' => 'https://areaprivata.myagileprivacy.com/',
				'en' => 'https://privatearea.myagileprivacy.com/',
			),
			'cookie_shield' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-rilevare-e-bloccare-i-cookie-automaticamente-con-il-cookie-shield/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-automatically-detect-and-block-cookies-with-cookie-shield/',
			),
			'contact_forms' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-mettere-a-norma-gdpr-i-moduli-di-contatto/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-make-contact-forms-gdpr-compliant/',
			),
			'policy_assistant' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/guida-personalizzazione-policy-gdpr-lpd-ccpa-pipeda-lgpd-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/guide-to-using-the-my-agile-privacy-policy-assistant/',
			),
			'multilanguage' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-configurare-my-agile-privacy-per-siti-web-multi-lingue/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-configure-my-agile-privacy-for-multi-language-websites/',
			),
			'cache_wprocket' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-configurare-wp-rocket-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-configure-wp-rocket-with-my-agile-privacy/',
			),
			'cache_siteground' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-configurare-siteground-optimizer-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-configure-siteground-optimizer-with-my-agile-privacy/',
			),
			'cache_optimizepress' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-configurare-optimize-press-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-configure-optimize-press-with-my-agile-privacy/',
			),
			'cache_w3tc' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-configurare-w3-total-cache-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-configure-w3-total-cache-with-my-agile-privacy/',
			),
			'cache_speedycache' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-configurare-speedy-cache-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-configure-speedy-cache-with-my-agile-privacy/',
			),
			'cache_litespeed' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-configurare-litespeed-cache-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-configure-litespeed-cache-with-my-agile-privacy/',
			),
			'cache_generic' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/utilizzi-un-altro-plugin-di-cache/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/do-you-use-another-cache-plugin/',
			),
			'consent_mode_v2' => array(
				'it' => 'https://www.myagileprivacy.com/supporto-alla-consent-mode-v2-cose-e-come-implementarla-a-norma-gdpr-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/supporting-consent-mode-v2-what-it-is-and-how-to-implement-it-gdpr-compliant-with-my-agile-privacy/',
			),
			'microsoft_cmode' => array(
				'it' => 'https://www.myagileprivacy.com/come-implementare-microsoft-consent-mode-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/how-to-implement-microsoft-consent-mode-with-my-agile-privacy/',
			),
			'clarity_cmode' => array(
				'it' => 'https://www.myagileprivacy.com/come-implementare-clarity-consent-mode-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/how-to-implement-clarity-consent-mode-with-my-agile-privacy/',
			),
			'gtg' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/google-tag-gateway-e-consenso-cosa-cambia-per-i-tuoi-tag-e-cosa-fare/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/google-tag-gateway-and-consent-what-changes-for-your-tags-and-what-you-should-do/',
			),
			// Standalone page, intentionally outside /helpdesk/.
			'third_party_cookies' => array(
				'it' => 'https://www.myagileprivacy.com/guida-installazione-cookie-software-di-terze-parti/',
				'en' => 'https://www.myagileprivacy.com/en/cookie-installation-guide-third-party-software/',
			),
			'iab' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-attivare-lo-iab-tcf-con-my-agile-privacy/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-activate-the-iab-tcf-with-my-agile-privacy/',
			),
			'backup_restore' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/come-fare-backup-e-ripristino-dei-cookie/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/how-to-backup-and-restore-your-cookies/',
			),
			'helpdesk_index' => array(
				'it' => 'https://www.myagileprivacy.com/helpdesk/',
				'en' => 'https://www.myagileprivacy.com/en/helpdesk/',
			),
			'contact' => array(
				'it' => 'https://www.myagileprivacy.com/contattaci/',
				'en' => 'https://www.myagileprivacy.com/en/contact-us/',
			),
			'technical_sheet' => array(
				'it' => 'https://www.myagileprivacy.com/scheda-e-informazioni-tecniche-myagileprivacy/',
				'en' => 'https://www.myagileprivacy.com/en/technical-sheet/',
			),
		);
	}

	/**
	 * Resolves the current admin locale to a map language key.
	 *
	 * @return string 'it' or 'en'
	 */
	public static function get_language_key()
	{
		$locale = method_exists( 'MyAgilePrivacy', 'get_locale' ) ? MyAgilePrivacy::get_locale() : ( function_exists( 'get_locale' ) ? get_locale() : 'en_US' );

		if( is_string( $locale ) && 0 === stripos( $locale, 'it' ) )
		{
			return 'it';
		}

		return 'en';
	}

	/**
	 * Returns the localized URL for the given key.
	 *
	 * Unknown keys fall back to the helpdesk index of the current language
	 * (never an empty href). The fallback also goes through the filter.
	 *
	 * Anchors: pages published in Italian and English have different heading
	 * ids, so a fragment can be passed either as a plain string (same id in
	 * both languages) or as array( 'it' => ..., 'en' => ... ). The fragment
	 * is appended after the trailing slash ("#" is added automatically) and
	 * is dropped when the anchor is missing for the resolved language: a
	 * working page-level link beats a broken fragment.
	 *
	 * @param string            $key    Stable identifier, e.g. 'cookie_shield'.
	 * @param string|array|null $anchor Optional fragment, without the '#'.
	 * @return string
	 */
	public static function get( $key, $anchor = null )
	{
		$map = self::get_map();
		$lang = self::get_language_key();

		if( isset( $map[ $key ] ) && isset( $map[ $key ][ $lang ] ) )
		{
			$url = $map[ $key ][ $lang ];
		}
		else
		{
			// Unknown key: fall back to the helpdesk index, and surface the
			// typo in development (the fallback would otherwise mask it).
			if( defined( 'MAP_DEV_MODE' ) && MAP_DEV_MODE )
			{
				MyAgilePrivacy::write_log( 'MAP_Helpdesk_Links: unknown key "' . $key . '", falling back to helpdesk index.' );
			}

			$url = $map['helpdesk_index'][ $lang ];
			$anchor = null;
		}

		// Placeholder entries ('#') stand for guides not published yet:
		// they must stay a bare '#' - no tracking, no fragment.
		if( 0 !== strpos( $url, 'http' ) )
		{
			return apply_filters( 'map_helpdesk_url', $url, $key, $lang );
		}

		// Tag plugin-originated visits for analytics. 'utm_content'
		// carries the key, so every call-site can be told apart. The
		// private area is a login app, not a content page: it stays
		// untagged. Return an empty array from the filter to disable.
		if( 'private_area_app' !== $key && function_exists( 'add_query_arg' ) )
		{
			$utm_args = apply_filters( 'map_helpdesk_utm_args', array(
				'utm_source'  => 'myagileprivacy-plugin',
				'utm_medium'  => 'online-help',
				'utm_content' => $key,
			), $key, $lang );

			if( is_array( $utm_args ) && count( $utm_args ) > 0 )
			{
				$url = add_query_arg( $utm_args, $url );
			}
		}

		if( null !== $anchor )
		{
			$fragment = '';

			if( is_array( $anchor ) )
			{
				if( isset( $anchor[ $lang ] ) && is_string( $anchor[ $lang ] ) )
				{
					$fragment = $anchor[ $lang ];
				}
			}
			elseif( is_string( $anchor ) )
			{
				$fragment = $anchor;
			}

			if( '' !== $fragment )
			{
				$url .= '#' . ltrim( $fragment, '#' );
			}
		}

		return apply_filters( 'map_helpdesk_url', $url, $key, $lang );
	}
}

if( !function_exists( 'map_render_help_fox' ) )
{
	/**
	 * Renders the "help fox" - experimental alternative to the section-level
	 * helpdesk link. A small fox sits on the bottom-right corner of the box
	 * (the box needs position:relative); hovering it opens a speech bubble
	 * that links to the online guide. On its first viewport entry the fox
	 * pops: grows, gains color, then settles slightly larger than it started
	 * (IntersectionObserver adds .is-seen, CSS does the rest).
	 *
	 * @param string            $key    Key known to MAP_Helpdesk_Links::get().
	 * @param string|array|null $anchor Optional page fragment.
	 * @param bool              $echo   Echo the markup (default) or return it.
	 * @return string The component HTML.
	 */
	function map_render_help_fox( $key, $anchor = null, $echo = true )
	{
		static $script_done = false;

		$url = MAP_Helpdesk_Links::get( $key, $anchor );
		$img = plugins_url( 'admin/img/fox-profile.png', MAP_PLUGIN_FILENAME );
		$label = esc_html__( 'Need help? Click me.', 'MAP_txt' );

		// Placeholder guides resolve to '#': render as a same-page stub
		// (no new tab) until the real URL lands in the map.
		$is_external = ( 0 === strpos( $url, 'http' ) );

		$html = '<a href="' . esc_url( $url ) . '" class="map-help-fox"' . ( $is_external ? ' target="_blank" rel="noopener"' : '' ) . ' aria-label="' . esc_attr( $label ) . '">'
			. '<span class="map-help-fox-bubble">' . $label . '</span>'
			. '<img class="map-help-fox-img" src="' . esc_url( $img ) . '" alt="">'
			. '</a>';

		if( !$script_done )
		{
			$script_done = true;

			$html .= '<script>'
				. '(function(){'
				. 'function mapFoxInit(){'
				. 'var items = document.querySelectorAll( ".map-help-fox" );'
				. 'if( !( "IntersectionObserver" in window ) ){ for( var i = 0; i < items.length; i++ ){ items[ i ].classList.add( "is-seen" ); } return; }'
				. 'var io = new IntersectionObserver( function( entries ){'
				. 'for( var k = 0; k < entries.length; k++ ){ if( entries[ k ].isIntersecting ){ entries[ k ].target.classList.add( "is-seen" ); io.unobserve( entries[ k ].target ); } }'
				. '}, { threshold: 0.4 } );'
				. 'for( var j = 0; j < items.length; j++ ){ io.observe( items[ j ] ); }'
				. '}'
				. 'if( document.readyState === "loading" ){ document.addEventListener( "DOMContentLoaded", mapFoxInit ); } else { mapFoxInit(); }'
				. '})();'
				. '</script>';
		}

		if( $echo )
		{
			echo $html;
		}

		return $html;
	}
}

if( !function_exists( 'map_render_helpdesk_link' ) )
{
	/**
	 * Renders (or returns) an anchor pointing to a helpdesk resource.
	 *
	 * "Ghost link" style: book icon (= online guide, distinct from the
	 * info-circle tooltips) + always-visible label + external-link mark.
	 * Section-level links are placed right OUTSIDE the box they refer to.
	 *
	 * @param string            $key        Key known to MAP_Helpdesk_Links::get().
	 * @param string|null       $label_html Optional label (may contain safe HTML).
	 *                                      Defaults to the "Online Help" label.
	 * @param bool              $echo       Echo the anchor (default) or return it.
	 * @param string|array|null $anchor     Optional page fragment - see MAP_Helpdesk_Links::get().
	 * @return string The anchor HTML.
	 */
	function map_render_helpdesk_link( $key, $label_html = null, $echo = true, $anchor = null )
	{
		$url = MAP_Helpdesk_Links::get( $key, $anchor );

		if( null === $label_html )
		{
			$label_html = esc_html__( 'Online Help', 'MAP_txt' );
		}
		else
		{
			$label_html = wp_kses_post( $label_html );
		}

		// Placeholder targets ('#') render as a same-page stub: no new
		// tab and no external-link mark. The link upgrades itself once
		// the real URL replaces '#' in the map.
		$is_external = ( 0 === strpos( $url, 'http' ) );

		$html = '<a href="' . esc_url( $url ) . '" class="map-help-link"'
			. ( $is_external ? ' target="_blank" rel="noopener"' : '' ) . '>'
			. '<i class="fa-regular fa-book-open" aria-hidden="true"></i>'
			. $label_html;

		if( $is_external )
		{
			$html .= '<i class="fa-regular fa-arrow-up-right-from-square map-help-ext" aria-hidden="true"></i>';
		}

		$html .= '</a>';

		if( $echo )
		{
			echo $html;
		}

		return $html;
	}
}
