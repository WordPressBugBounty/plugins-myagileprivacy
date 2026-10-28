/*!
* MyAgilePrivacy (https://www.myagileprivacy.com/)
* plain js version
*/

if( typeof map_full_config === 'undefined' ) { window.map_full_config = undefined; }
if( typeof map_ajax === 'undefined' )        { window.map_ajax = undefined; }

var MAP_SYS = {
	'plugin_version' 					: null,
	'parse_config_version_number' 		: null,
	'js_internal_version' 				: "3.0049",
	'cookie_shield_version' 			: null,
	'js_technology' 					: "plain",
	'maplog' 							: "\x1b[40m\x1b[97m[MyAgilePrivacy]\x1b[0m ",
	'map_initted' 						: false,
	'map_banner_ready_waiting' 			: false,
	'map_banner_ready_done' 			: false,
	'map_after_init_done' 				: false,
	'map_document_load' 				: false,
	'map_debug' 						: false,
	'map_cookie_expire' 				: 180,
	'map_skip_regexp' 					: [/.*oxygen_iframe=true.*$/, /.*ct_builder=true.*$/],
	'map_missing_cookie_shield' 		: null,
	'map_detectableKeys' 				: null,
	'map_detectedKeys' 					: null,
	'map_notify_title'					: false,
	'map_first_layer_branded'			: false,
	'enforce_youtube_privacy_v2'		: false,
	'in_iab_context' 					: false,
	'dependencies' 						: [],
	'microsoft_cmode' 					: null,
	'clarity_cmode' 					: null,
	'clarity_cmp_id' 					: "150",
	'cmode_v2' 							: null,
	'cmode_v2_implementation_type' 		: null,
	'cmode_v2_forced_off_ga4_advanced' 	: null,
	'cmode_v2_js_on_error'				: null,
	'starting_gconsent' 				: [],
	'current_gconsent'					: [],
	'starting_mconsent' 				: [],
	'current_mconsent'					: [],
	'starting_cconsent' 				: [],
	'current_cconsent'					: [],
	'map_accept_all_button'				: null,
	'map_reject_all_button'				: null,
	'user_uuid'							: null,
	'last_consent_modify_date'			: null,
	'some_positive_consent_given'		: false,
	'early_gcmode'						: false,
	'reset_postfix_applied'				: false,
	'frontend_regulation'				: null,
	'geo_enabled'						: false,
	'geo_sede_strong'					: false,
	'geo_force_optout'					: {},
	'geo_resolved'						: false,  // true once country+native are known
	'geo_country'						: null,    // ISO alpha-2 lowercase
	'geo_native'						: null,    // 'block' | 'noblock'
	'geo_eff_type'						: 'block', // effective regime after effType logic
	'geo_bar_deferred'					: false,   // true while toggleBar is waiting for geo resolution
	'send_ga4_event_on_consent_change'	: null,
	'allow_js_fast_callback'			: null,
	'cookie_domain'						: null,
	'allow_iab_disclosure_url'			: true,
	'iab_early_init_done'				: false,
	'iab_full_init_done'				: false,
	'iab_full_init_promise'				: null,
	'iab_lang'							: null,
	'iab_pending_consent'				: null,
};

// bof MapLogger
(function (global) {
    // Define only if not already defined
    if (typeof global.MapLogger !== 'undefined' && global.MapLogger) {
        return;
    }

    function noop() {}

    function makeNoopLogger() {
        return {
            log: noop,
            info: noop,
            warn: noop,
            error: noop,
            debug: noop,
            group: noop,
            trace: noop,
            clear: noop,
            dir: noop,
            dirxml: noop,
            table: noop,
            assert: noop,
            count: noop,
            countReset: noop,
            time: noop,
            timeLog: noop,
            profile: noop,
            profileEnd: noop,
            timeStamp: noop,
            groupCollapsed: noop,
            groupEnd: noop
        };
    }

    // Check verbose flag (defined and true)
    function isVerboseEnabled() {
        return (typeof global.MAP_VERBOSE_LOG !== 'undefined' && global.MAP_VERBOSE_LOG === true);
    }

    // If verbose is enabled, expose the native console (keeps correct caller line)
    if (isVerboseEnabled()) {
        global.MapLogger = global.console || makeNoopLogger();
        return;
    }

    // If verbose is disabled, expose a no-op logger (no output, no errors)
    global.MapLogger = makeNoopLogger();

})(window);
// eof MapLogger

// cookie name suffix with a single leading underscore, derived from the banner settings, null when they cannot be read
function map_reset_postfix( settings )
{
	try {
		var s = ( typeof settings === 'string' ) ? JSON.parse( settings ) : settings;
		if( !s || typeof s !== 'object' ) return null;
		var t = s.cookie_reset_timestamp ? String( s.cookie_reset_timestamp ).replace( /^_+/, '' ) : '';
		return t ? '_' + t : '';
	}
	catch( e )
	{
		return null;
	}
}

// cookie name suffix of earlier versions without the shield, '' when there is none
function map_legacy_reset_postfix( settings )
{
	try {
		var s = ( typeof settings === 'string' ) ? JSON.parse( settings ) : settings;
		if( !s || typeof s !== 'object' ) return '';
		return s.cookie_reset_timestamp ? '_' + s.cookie_reset_timestamp : '';
	}
	catch( e )
	{
		return '';
	}
}

function map_apply_reset_postfix( postfix )
{
	MAP_POSTFIX = postfix;
	MAP_ACCEPTED_ALL_COOKIE_NAME = MAP_ACCEPTED_ALL_COOKIE_NAME + postfix;
	MAP_ACCEPTED_SOMETHING_COOKIE_NAME = MAP_ACCEPTED_SOMETHING_COOKIE_NAME + postfix;
	MAP_CONSENT_STATUS = MAP_CONSENT_STATUS + postfix;
	MAP_MICROSOFT_CONSENT_STATUS = MAP_MICROSOFT_CONSENT_STATUS + postfix;
	MAP_CLARITY_CONSENT_STATUS = MAP_CLARITY_CONSENT_STATUS + postfix;
	MAP_USER_UUID = MAP_USER_UUID + postfix;
	MAP_LAST_CONSENT_MODIFY_DATE = MAP_LAST_CONSENT_MODIFY_DATE + postfix;
	MAP_SYS.reset_postfix_applied = true;
}

//name of the mark left by an implicit consent, with the current reset suffix
function map_implicit_consent_name()
{
	return 'map_implicit_consent' + ( ( typeof MAP_POSTFIX === 'string' ) ? MAP_POSTFIX : '' );
}

//true when the last recorded choice is the implicit consent: the mark carries the date of that choice
function map_implicit_consent_valid()
{
	try {
		if( typeof MAP_Cookie === 'undefined' ) return false;

		var mark = MAP_Cookie.read( map_implicit_consent_name() );

		if( !mark ) return false;

		// the date name carries the reset suffix without the shield and not with it
		var postfix = ( typeof MAP_POSTFIX === 'string' ) ? MAP_POSTFIX : '';
		var dates = [ MAP_Cookie.read( MAP_LAST_CONSENT_MODIFY_DATE ), MAP_Cookie.read( 'map_last_c_m_date' ), MAP_Cookie.read( 'map_last_c_m_date' + postfix ) ].filter( function( x ) { return !!x; } );

		if( dates.indexOf( mark ) !== -1 ) return true;

		// a removed date leaves the mark alone with the implicit consent
		return !dates.length && MAP_Cookie.read( MAP_ACCEPTED_ALL_COOKIE_NAME ) === '1';
	}
	catch( e )
	{
		return false;
	}
}

//why a stored implicit consent no longer holds: 'gpc', 'block' or ''
function map_implicit_consent_void()
{
	try {
		if( !map_implicit_consent_valid() ) return '';
		if( typeof navigator !== 'undefined' && navigator.globalPrivacyControl === true ) return 'gpc';

		var c = ( typeof map_full_config !== 'undefined' && map_full_config ) ? map_full_config : null;
		if( !c || !c.geo_enabled ) return '';

		var g = JSON.parse( MAP_Cookie.read( 'map_geo' ) || 'null' );
		if( !g || !g.country || !g.native ) return '';
		if( g.native === 'block' ) return 'block';
		if( c.geo_sede_strong && !( c.geo_force_optout && c.geo_force_optout[ g.country ] ) ) return 'block';

		return '';
	}
	catch( e )
	{
		return '';
	}
}

//a stored consent state read back after a refusal of everything never grants anything
function map_refusal_safe( consent )
{
	try {
		if( !consent || typeof consent !== 'object' ) return consent;
		if( MAP_Cookie.read( MAP_ACCEPTED_ALL_COOKIE_NAME ) !== '-1' || MAP_Cookie.read( MAP_ACCEPTED_SOMETHING_COOKIE_NAME ) !== '-1' ) return consent;

		// only the signals that the choices govern, the others are left as stored
		var governed = {};
		if( typeof MAP !== 'undefined' && MAP && MAP.consentModeServices )
		{
			Object.keys( MAP.consentModeServices ).forEach( function( v ) { MAP.consentModeServices[ v ].keys.forEach( function( k ) { governed[k] = true; } ); } );
		}

		var out = {};
		Object.keys( consent ).forEach( function( k ) { out[k] = ( consent[k] === 'granted' && governed[k] ) ? 'denied' : consent[k]; } );

		return out;
	}
	catch( e )
	{
		return consent;
	}
}

//true when a stored choice exists and still holds, the condition to restore a stored consent state
function map_stored_choice_holds()
{
	try {
		return ( MAP_Cookie.exists( MAP_ACCEPTED_ALL_COOKIE_NAME ) || MAP_Cookie.exists( MAP_ACCEPTED_SOMETHING_COOKIE_NAME ) ) &&
			!map_implicit_consent_void();
	}
	catch( e )
	{
		return false;
	}
}

if( !( typeof MAP_JSCOOKIE_SHIELD !== 'undefined' && MAP_JSCOOKIE_SHIELD ) )
{
	window.MAP_POSTFIX = '';
	window.MAP_ACCEPTED_ALL_COOKIE_NAME = 'map_accepted_all_cookie_policy';
	window.MAP_ACCEPTED_SOMETHING_COOKIE_NAME = 'map_accepted_something_cookie_policy';
	window.MAP_CONSENT_STATUS = 'map_consent_status';
	window.MAP_MICROSOFT_CONSENT_STATUS = 'map_microsoft_consent_status';
	window.MAP_CLARITY_CONSENT_STATUS = 'map_clarity_consent_status';
	window.MAP_USER_UUID = 'map_user_uuid';
	window.MAP_LAST_CONSENT_MODIFY_DATE = 'map_last_c_m_date';

	var map_eval_postfix = ( typeof map_cookiebar_settings !== 'undefined' ) ? map_reset_postfix( map_cookiebar_settings ) : null;
	if( map_eval_postfix !== null ) map_apply_reset_postfix( map_eval_postfix );

	MapLogger.debug( MAP_SYS.maplog + 'MAP_POSTFIX=' + MAP_POSTFIX );
}

window.MAP_TRANSPORT_COOKIE_NAME = 'map_tp';
window.MAP_GEO_TRANSPORT_COOKIE_NAME = 'map_geo_tp';

window.MAP_SVG_VIDEO = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='48' height='48' fill='currentColor' aria-hidden='true'><path fill-rule='evenodd' d='M4 5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H4zm6 3.5 6 3.5-6 3.5v-7z'/></svg>";
window.MAP_SVG_MAPS = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='48' height='48' fill='currentColor' aria-hidden='true'><path d='M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z'/></svg>";

if( typeof MAP_Cookie === 'undefined' )
{
	if( typeof MAP_UA_NO_COOKIE_SET === 'undefined' )
	{
		var MAP_UA_NO_COOKIE_SET = ["Cookiebot", "cookiebot.com", "CookieHubVerify", "TermlyBot"];
	}

	var MAP_Cookie = {
		_isNoSetResult: null,
		_isUserAgentNoCookieSet: function () {
			if (this._isNoSetResult !== null) return this._isNoSetResult;
			var userAgent = ( navigator?.userAgent || '' ).toLowerCase();
			this._isNoSetResult = MAP_UA_NO_COOKIE_SET.some(function(f){
				return userAgent.includes( f.toLowerCase() );
			});
			return this._isNoSetResult;
		},
		_cookie_cache: {},
		// bare and lower case, '' when there is none
		_effectiveDomain: function( domain ) {
			var e = domain || ( typeof MAP_SYS !== 'undefined' && MAP_SYS ? MAP_SYS.cookie_domain : null ) ||
				( typeof map_full_config !== 'undefined' && map_full_config ? map_full_config.cookie_domain : null );
			if( typeof e !== 'string' || e === '' ) return '';
			return ( e.charAt( 0 ) === '.' ? e.substring( 1 ) : e ).toLowerCase();
		},
		_domainApplies: function( domain ) {
			var bare = this._effectiveDomain( domain );
			var host = window.location.hostname;
			if( bare === '' ) return false;
			return host === bare || ( host.length > bare.length && host.slice( -( bare.length + 1 ) ) === '.' + bare );
		},
		// host-only when the page host is not under the domain
		_buildDomainStr: function( domain ) {
			return this._domainApplies( domain ) ? '; domain=.' + this._effectiveDomain( domain ) : '';
		},
		_cleanupAllowed: function( domain ) {
			return this._domainApplies( domain ) && typeof map_full_config !== 'undefined' && !!map_full_config && !!map_full_config.cookie_domain_tracked;
		},
		// only the names this plugin writes, with or without the reset suffix
		_isOwnName: function( name ) {
			return typeof name === 'string' && /^(map_(accepted_all_cookie_policy|accepted_something_cookie_policy|consent_status|microsoft_consent_status|clarity_consent_status|user_uuid|last_c_m_date|implicit_consent|cookie_\d+)(_\w+)?|map_geo|map_geo_tp|map_tp|map_gtm_gw_reported|map_banner_variant|MAP_GMTString(_\w+)?)$/.test( name );
		},
		// removes the legacy host-only variant of the same name, never through _buildDomainStr
		_killHostOnly: function( name ) {
			try {
				document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/" + this._buildSecurityStr();
				if( this._cookie_cache ) delete this._cookie_cache[name];
			}
			catch( e )
			{
				MapLogger.debug( e );
			}
		},
		dropHostOnlyDuplicates: function() {
			try {
				if( this._isUserAgentNoCookieSet() || !this._cleanupAllowed() ) return;
				var seen = Object.create( null );
				var list = this.getCookieNameList();
				for( var i = 0; i < list.length; i++ )
				{
					if( this._isOwnName( list[i] ) && seen[ list[i] ] ) this._killHostOnly( list[i] );
					seen[ list[i] ] = 1;
				}
			}
			catch( e )
			{
				MapLogger.debug( e );
			}
		},
		_buildSecurityStr: function() {
			var sameSite = '; SameSite=Lax';
			var secure   = ( window.location.protocol === 'https:' ) ? '; Secure' : '';
			return sameSite + secure;
		},
		set: function(name, value, days, domain = null ) {
			if( this._isUserAgentNoCookieSet() )
			{
				return null;
			}
			try {
				if (days) {
					var date = new Date();
					date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
					var expires = "; expires=" + date.toGMTString();
				} else
					var expires = "";
				if( this._cleanupAllowed( domain ) && this._isOwnName( name ) ) this._killHostOnly( name );
				document.cookie = name + "=" + ( "" + value ).replace( /[;\r\n]/g, "" ) + expires + "; path=/" + this._buildDomainStr( domain ) + this._buildSecurityStr();
				if( this._cookie_cache ) delete this._cookie_cache[name];
			}
			catch( e )
			{
				MapLogger.debug( e );
				return null;
			}
		},
		setGMTString: function( name, value, GMTString, domain = null ) {
			if( this._isUserAgentNoCookieSet() )
			{
				return null;
			}
			try {
				var expires = "; expires=" + GMTString;
				if( this._cleanupAllowed( domain ) && this._isOwnName( name ) ) this._killHostOnly( name );
				document.cookie = name + "=" + ( "" + value ).replace( /[;\r\n]/g, "" ) + expires + "; path=/" + this._buildDomainStr( domain ) + this._buildSecurityStr();
				if( this._cookie_cache ) delete this._cookie_cache[name];
			}
			catch( e )
			{
				MapLogger.debug( e );
				return null;
			}
		},
		read: function (name) {
			if( this._cookie_cache && name in this._cookie_cache ) return this._cookie_cache[name];
			try {
				var nameEQ = name + "=";
				var ca = document.cookie.split(';');
				for (var i = 0; i < ca.length; i++) {
					var c = ca[i];
					while (c.charAt(0) == ' ') {
						c = c.substring(1, c.length);
					}
					if (c.indexOf(nameEQ) === 0) {
						var val = c.substring(nameEQ.length, c.length);
						if( this._cookie_cache ) this._cookie_cache[name] = val;
						return val;
					}
				}
				if( this._cookie_cache ) this._cookie_cache[name] = null;
				return null;
			}
			catch( e )
			{
				MapLogger.debug( e );
				return null;
			}
		},
		exists: function (name) {
			return (this.read(name) !== null);
		},
		delete: function( name, domain ) {
			var expirationDate = new Date();
			expirationDate.setTime( expirationDate.getTime() - 3600000 ); // One hour ago
			if( this._cleanupAllowed( domain ) && this._isOwnName( name ) ) this._killHostOnly( name );
			document.cookie = name + "=; expires=" + expirationDate.toUTCString() + "; path=/" + this._buildDomainStr( domain ) + this._buildSecurityStr();
			if( this._cookie_cache ) delete this._cookie_cache[name];
		},
		getCookieNameList: function () {
			var cookies = document.cookie.split( ';' );
			var return_data = [];
			for( var i = 0; i < cookies.length; i++ )
			{
				var cookie = cookies[i].trim();
				var separatorIndex = cookie.indexOf( '=' );
				if( separatorIndex === -1 )
				{
					continue; // Skip if the cookie is malformed (doesn't contain '=')
				}
				var name = cookie.substring( 0, separatorIndex );
				return_data.push( name );
			}
			return return_data;
		}
	};
}

// before the first read, with this copy or the shield one
if( typeof MAP_Cookie.dropHostOnlyDuplicates === 'function' )
{
	MAP_Cookie.dropHostOnlyDuplicates();
}

// true when the item is active for this visitor, false when not, null when unknown.
window.MAP_isKeyAccepted = function( api_key )
{
	try {

		var cfg = ( typeof map_full_config !== 'undefined' && map_full_config ) ? map_full_config : null;
		var key_map = cfg ? cfg.cookie_api_key_remote_id_map_active : null;

		if( typeof api_key !== 'string' || !/^[A-Za-z0-9_-]+$/.test( api_key ) ) return null;
		if( !key_map || typeof key_map !== 'object' || typeof key_map[ api_key ] !== 'string' ) return null;

		// an implicit consent that no longer holds grants nothing
		var voided = !!map_implicit_consent_void();

		if( !voided && MAP_Cookie.read( key_map[ api_key ] ) == '1' ) return true;

		var postfix = cfg.cookie_reset_timestamp ? '_' + cfg.cookie_reset_timestamp : '';

		if( !voided && MAP_Cookie.read( 'map_accepted_all_cookie_policy' + postfix ) == '1' ) return true;

		if( api_key === 'google_analytics' &&
			Array.isArray( cfg.cookie_api_key_not_to_block ) &&
			cfg.cookie_api_key_not_to_block.includes( 'google_analytics' ) )
		{
			return true;
		}

		var $wrapper = document.querySelector( '.map_cookie_description_wrapper[data-cookie-api-key="' + api_key + '"]' );

		return !!( $wrapper && $wrapper.classList.contains( '_always_on' ) );
	}
	catch( error )
	{
		console.error( error );
		return null;
	}
};

var MAP =
{
	set: function( args )
	{
		try
		{
			if( this.initted )
			{
				MapLogger.debug( MAP_SYS.maplog + 'MAP already initted: exiting' );
				return;
			}

			var current_url = window.location.href;

			var isMatch = MAP_SYS.map_skip_regexp.some( function( rx ) {
				return rx.test( current_url );
			});

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'calling MAP set function' );

			if( typeof JSON.parse !== "function" )
			{
				console.error( MAP_SYS.maplog + 'Error: My Agile Privacy requires JSON.parse but your browser lacks this support' );
				return;
			}
			if( typeof args.settings !== 'object' )
			{
				this.settings = JSON.parse( args.settings );
			}
			else
			{
				this.settings = args.settings;
			}

			if( !!this?.settings?.plugin_version )
			{
				MAP_SYS.plugin_version = this?.settings?.plugin_version;
			}

			if( !!this?.settings?.parse_config_version_number )
			{
				MAP_SYS.parse_config_version_number = this?.settings?.parse_config_version_number;
			}

			if( !!this?.settings?.map_notify_title )
			{
				MAP_SYS.map_notify_title = this?.settings?.map_notify_title;
			}

			if( !!this?.settings?.map_first_layer_branded )
			{
				MAP_SYS.map_first_layer_branded = this?.settings?.map_first_layer_branded;
			}

			if( typeof map_full_config !== 'undefined' &&
				typeof map_full_config.frontend_regulation !== 'undefined'
			)
			{
				MAP_SYS.frontend_regulation = map_full_config.frontend_regulation;
			}

			if( typeof map_full_config !== 'undefined' &&
				typeof map_full_config.send_ga4_event_on_consent_change !== 'undefined'
			)
			{
				MAP_SYS.send_ga4_event_on_consent_change = map_full_config.send_ga4_event_on_consent_change;
			}

			if( typeof map_full_config !== 'undefined' &&
				typeof map_full_config.allow_js_fast_callback !== 'undefined'
			)
			{
				MAP_SYS.allow_js_fast_callback = map_full_config.allow_js_fast_callback;
			}

			if( typeof map_full_config !== 'undefined' &&
				typeof map_full_config.cookie_domain !== 'undefined'
			)
			{
				MAP_SYS.cookie_domain = map_full_config.cookie_domain;
			}

			if( typeof map_full_config !== 'undefined' &&
				typeof map_full_config.enforce_youtube_privacy_v2 !== 'undefined'
			)
			{
				MAP_SYS.enforce_youtube_privacy_v2 = map_full_config.enforce_youtube_privacy_v2;
			}

			//copy geo config from map_full_config into MAP_SYS
			if( typeof map_full_config !== 'undefined' && map_full_config.geo_enabled )
			{
				MAP_SYS.geo_enabled     = true;
				MAP_SYS.geo_sede_strong = !! map_full_config.geo_sede_strong;
				MAP_SYS.geo_force_optout = ( map_full_config.geo_force_optout && typeof map_full_config.geo_force_optout === 'object' )
					? map_full_config.geo_force_optout
					: {};
				MapLogger.log( 'MAP-GEO', 'config copied into MAP_SYS', {
					geo_enabled     : MAP_SYS.geo_enabled,
					geo_sede_strong : MAP_SYS.geo_sede_strong,
					geo_force_optout: MAP_SYS.geo_force_optout
				} );
			}
			else
			{
				MapLogger.log( 'MAP-GEO', 'geo disabled in config (map_full_config.geo_enabled falsy) - skipping geo init' );
			}

			if( typeof CookieShield !== 'undefined' &&
				CookieShield
			)
			{
				MAP_SYS.cookie_shield_version = CookieShield.getVersion();
			}

			if( ( !!this?.settings?.scan_mode &&
				this.settings.scan_mode == 'learning_mode' ) ||
				( !!this?.settings?.verbose_remote_log &&
				this.settings.verbose_remote_log )
			)
			{
				MAP_SYS.map_debug = true;
			}

			if( !( typeof MAP_JSCOOKIE_SHIELD !== 'undefined' && MAP_JSCOOKIE_SHIELD ) && !MAP_SYS.reset_postfix_applied )
			{
				var map_set_postfix = map_reset_postfix( this.settings );
				map_apply_reset_postfix( map_set_postfix === null ? '' : map_set_postfix );
				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'MAP_POSTFIX=' + MAP_POSTFIX );
			}

			if( MAP_SYS?.map_debug && !!this?.settings.cookie_reset_timestamp &&
				this.settings.cookie_reset_timestamp )
			{
				MapLogger.debug( MAP_SYS.maplog + 'using alt_accepted_all_cookie_name=' + MAP_ACCEPTED_ALL_COOKIE_NAME );
				MapLogger.debug( MAP_SYS.maplog + 'using alt_accepted_something_cookie_name=' + MAP_ACCEPTED_SOMETHING_COOKIE_NAME );
				MapLogger.debug( MAP_SYS.maplog + 'using alt_user_uuid_cookie_name=' + MAP_USER_UUID );
				MapLogger.debug( MAP_SYS.maplog + 'using alt_last_consent_modify_date_cookie_name=' + MAP_LAST_CONSENT_MODIFY_DATE );
			}

			this.blocked_friendly_name_string = null;

			this.blocked_content_notification_shown = false;

			this.bar_open = false;

			this.bar_blocked = false;

			this.hidden_help_return = null;

			this.settings_return = null;

			this.settings = args.settings;

			this.bar_elm = document.querySelector( this.settings.notify_div_id );
			this.showagain_elm = document.querySelector( "#" + this.settings.showagain_div_id );
			this.settingsModal = document.querySelector( '#mapSettingsPopup' );
			this.setupSettingsDialog();
			this.blocked_content_notification = document.querySelector( '#map-blocked-content-notification-area' );

			if( this.blocked_content_notification )
			{
				this.map_blocked_elems_desc = this.blocked_content_notification.querySelector('.map_blocked_elems_desc');
			}

			this.map_notification_message = this.bar_elm.querySelector( '.map_notification-message' );

			/* buttons */
			this.accept_button = this.bar_elm.querySelector( '.map-accept-button' );
			this.reject_button = this.bar_elm.querySelectorAll( '.map-reject-button' );
			this.customize_button = this.bar_elm.querySelector( '.map-customize-button' );
			this.show_consent_elems = document.querySelectorAll( '.showConsent, .showConsentAgain' );

			MAP_SYS.map_accept_all_button = this.accept_button;
			MAP_SYS.map_reject_all_button = this.reject_button[0];

			if( MAP_SYS?.map_debug )
			{
				MapLogger.groupCollapsed( MAP_SYS.maplog + 'settings:' );
				MapLogger.debug( this.settings );
				MapLogger.groupEnd();
			}

			if( typeof MAP_Cookie.dropHostOnlyDuplicates === 'function' ) MAP_Cookie.dropHostOnlyDuplicates();

			this.reconcileImplicitConsent();

			// consent records are kept only alongside a choice
			try {
				if( typeof MAP_Cookie.delete === 'function' &&
					!MAP_Cookie.exists( MAP_ACCEPTED_ALL_COOKIE_NAME ) &&
					!MAP_Cookie.exists( MAP_ACCEPTED_SOMETHING_COOKIE_NAME ) )
				{
					if( MAP_Cookie.exists( MAP_USER_UUID ) ) MAP_Cookie.delete( MAP_USER_UUID );
					if( MAP_Cookie.exists( MAP_LAST_CONSENT_MODIFY_DATE ) ) MAP_Cookie.delete( MAP_LAST_CONSENT_MODIFY_DATE );
				}
			}
			catch( e )
			{
				MapLogger.debug( e );
			}

			this.retrieveLastConsentDate();
			this.retrieveLastConsentUUID();
			this.loadDependencies();
			this.toggleBar();
			this.createInlineNotify();
			this.attachEvents();

			// Needs the bar references and the button handlers, both set up above.
			if( MAP_SYS.geo_enabled )
			{
				MAP.mapGeoInit();
			}

			this.initted = true;

			this.optimizeMobile();

			//for preserving scope
			var that = this;

			window.addEventListener( 'resize', function() {
				that.optimizeMobile();
			});

			//late stylesheets and fonts change the button widths without a resize
			if( typeof ResizeObserver !== 'undefined' )
			{
				var last_bar_width = -1;

				new ResizeObserver( function() {
					if( that.bar_open && !that.isBarRendered() )
					{
						that.markBarHiddenByOthers();
						return;
					}

					if( that.bar_open && that.bar_elm.offsetWidth != last_bar_width )
					{
						last_bar_width = that.bar_elm.offsetWidth;
						that.layoutConsentButtons();
						that.capNotificationMessage();
					}
				}).observe( this.bar_elm );
			}

			if( document.fonts && document.fonts.ready )
			{
				document.fonts.ready.then( function() {
					if( that.bar_open )
					{
						that.layoutConsentButtons();
						that.capNotificationMessage();
					}
				});
			}

			if( this.map_notification_message )
			{
				this.map_notification_message.addEventListener( 'scroll', function() {
					that.updateMessageScrollState();
				}, { passive: true } );

				//text written into the banner after it opened
				if( typeof MutationObserver !== 'undefined' )
				{
					new MutationObserver( function() {
						if( that.bar_open ) that.capNotificationMessage();
					}).observe( this.map_notification_message, { childList: true, subtree: true, characterData: true } );
				}
			}

			document.body.addEventListener( 'MAP_SHIELD_CONTENT_BLOCKED', function() {
				that.createInlineNotify();
			});

			this.setupIabTCF();
			this.updateSomeConsentGivenStatus();

			this.usabilityEvents();

		}
		catch( error )
		{
			console.error( error );
		}
	},

    //normalize spaces for safe comparisons
    normalizeCssValue: function( value )
    {
        value = (value === undefined || value === null) ? '' : (value + '');
        return value.replace(/\s+/g, ' ').trim();
    },

    //create a hidden test node for computed style checks
    createCssTestNode: function( testClassName )
    {
        var el = document.createElement('div');
        el.className = testClassName;

        //keep it out of view and out of layout impact
        el.style.position = 'absolute';
        el.style.left = '-99999px';
        el.style.top = '-99999px';
        el.style.width = '1px';
        el.style.height = '1px';
        el.style.overflow = 'hidden';
        el.style.visibility = 'hidden';

        return el;
    },

    //check if a css rule is active by reading computed style
    //testClassName: class that your css "signature" targets, without dot (e.g. "map-css-signature")
    //testProperty: css property in kebab-case (e.g. "display", "font-family", "--my-var")
    //expectedValue: expected computed value (e.g. "none", "700", "rgb(0, 0, 0)")
    isCssSignatureActive: function( testClassName, testProperty, expectedValue )
    {
        try
        {
            if (!document.body) return false;
            if (!testClassName || !testProperty) return false;

            var el = this.createCssTestNode(testClassName);
            document.body.appendChild(el);

            var computed = window.getComputedStyle ? window.getComputedStyle( el, null ) : null;
            var value = '';

            if( computed )
            {
                //supports normal properties and CSS variables
                value = computed.getPropertyValue( testProperty );
            } else if( el.currentStyle )
            {
                //old IE fallback (not really needed in modern env, but harmless)
                value = el.currentStyle[testProperty];
            }

            document.body.removeChild(el);

            value = this.normalizeCssValue(value);
            expectedValue = this.normalizeCssValue(expectedValue);

            return (value === expectedValue);
        }
        catch( error )
        {
            console.error( error );
            return false;
        }
    },

    //inject css async (via <link>) only if signature is not active yet
    //options = {
    //  src: "https://...",
    //  signatureClass: "map-css-signature",
    //  signatureProperty: "display",
    //  signatureValue: "none"
    //}
    //callback(isActiveBeforeLoad, isLoadedNow)
    injectCssBySignature: function( options, callback )
    {
		//for preserving scope
		var that = this;

        try {
            options = options || {};
            var src = options.src || '';
            var signatureClass = options.signatureClass || '';
            var signatureProperty = options.signatureProperty || '';
            var signatureValue = options.signatureValue || '';

            if( !src ) return;

            //if signature is already active, skip loading
            var active = this.isCssSignatureActive( signatureClass, signatureProperty, signatureValue );
            if( active )
            {
                if (MAP_SYS && MAP_SYS.map_debug)
                {
                    MapLogger.debug( MAP_SYS.maplog + 'css signature already active, skip load src=' + src);
                }

                if( callback ) callback( true, false );
                return;
            }

            if( MAP_SYS && MAP_SYS.map_debug )
            {
                MapLogger.debug( MAP_SYS.maplog + 'injectCssBySignature src=' + src) ;
            }

            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.type = 'text/css';
            link.href = src;

            link.onload = function() {
                //double-check after load
                var activeAfter = that.isCssSignatureActive( signatureClass, signatureProperty, signatureValue );

                if( MAP_SYS && MAP_SYS.map_debug )
                {
                    MapLogger.debug( MAP_SYS.maplog + 'loaded css src=' + src + ' signatureActive=' + activeAfter );
                }

                MAP_SYS.dependencies.push( src );

                if( callback ) callback( false, true );
            };

            link.onerror = function() {
                if (MAP_SYS && MAP_SYS.map_debug)
                {
                    MapLogger.debug( MAP_SYS.maplog + 'error loading css src=' + src );
                }

                if( callback ) callback( false, false );
            };

            (document.head || document.getElementsByTagName('head')[0]).appendChild(link);
        }
        catch( error )
        {
            console.error( error );
        }
    },

	//extract filename and map it to a signature class
	getCssSignatureClassFromUrl: function(url)
	{
	    try {
	        if (!url) return '';

	        //remove query string/hash
	        var cleanUrl = (url + '').split('#')[0].split('?')[0];

	        //extract file name
	        var parts = cleanUrl.split('/');
	        var fileName = parts.length ? parts[parts.length - 1] : '';

	        //derive a class from filename
	        //example: "my-agile-privacy-frontend.css" => "map-css-signature-my-agile-privacy-frontend"
	        fileName = fileName.replace(/\.min\.css$/i, '').replace(/\.css$/i, '');
	        fileName = fileName.toLowerCase().replace(/[^a-z0-9\-]+/g, '-');
	        return 'map-css-signature-' + fileName;
	    }
	    catch( error )
	    {
	        console.error( error );
	        return '';
	    }
	},

	//inject code async
	injectCode: function( src=null, callback=null )
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function injectCode src=' + src );

			if( src )
			{
				const script = document.createElement( 'script' );
				script.async = true;
				script.src = src;
				script.onload = function() {

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'loaded script src=' + src );

					if( callback )
					{
						callback();
					}
				};

				//fallback for missing document.body
				( document.body || document.head ).appendChild( script );
			}
		}
		catch( error )
		{
			console.error( error );
		}
	},
	//load frontend css (by signature check)
	loadFrontendCss: function()
	{
	    //for preserving scope
	    var that = this;

	    try
	    {
	        if( MAP_SYS && MAP_SYS.map_debug )
	        {
	            MapLogger.debug( MAP_SYS.maplog + 'internal function loadFrontendCss' );
	        }

	        if( typeof map_full_config === 'undefined' || !map_full_config) return;
	        if( !map_full_config.frontend_css || !map_full_config.frontend_css.length ) return;

	        for( var i = 0; i < map_full_config.frontend_css.length; i++ )
	        {
	            var src = map_full_config.frontend_css[i];
	            var signatureClass = that.getCssSignatureClassFromUrl( src );

	            if( !signatureClass ) continue;

	            that.injectCssBySignature({
	                src: src,
	                signatureClass: signatureClass,
	                signatureProperty: 'display',
	                signatureValue: 'none'
	            }, function( isActiveBeforeLoad, isLoadedNow )
	            {
	                if( MAP_SYS && MAP_SYS.map_debug )
	                {
	                    MapLogger.debug(
	                        MAP_SYS.maplog +
	                        'css result alreadyActive=' + isActiveBeforeLoad +
	                        ' loadedNow=' + isLoadedNow
	                    );
	                }
	            });
	        }
	    }
	    catch( error )
	    {
	        console.error(error);
	    }
	},

	//load dependencies
	loadDependencies: function()
	{
		//for preserving scope
		var that = this;

		setTimeout(function(){

			that.loadFrontendCss();

		}, 500 );

		try {

			var something_to_do = false;

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function loadDependencies' );

			//js shield
			if( !( typeof MAP_JSCOOKIE_SHIELD !== 'undefined' && MAP_JSCOOKIE_SHIELD ) &&
				typeof map_full_config !== 'undefined' && typeof map_full_config?.js_shield_url !== 'undefined' && map_full_config?.js_shield_url )
			{
				that.injectCode( map_full_config?.js_shield_url, function() {
				} );

				something_to_do = true;
				MAP_SYS.dependencies.push( map_full_config?.js_shield_url );
			}

			//IabTCF
			if( typeof map_full_config !== 'undefined' && typeof map_full_config?.load_iab_tcf !== 'undefined' && map_full_config?.load_iab_tcf &&
				typeof map_full_config?.iab_tcf_script_url !== 'undefined' &&
				typeof window?.initMyAgilePrivacyIabTCF === 'undefined' )
			{
				that.injectCode( map_full_config?.iab_tcf_script_url, function() {
				} );

				something_to_do = true;
				MAP_SYS.dependencies.push( map_full_config?.iab_tcf_script_url );
			}

			if( something_to_do )
			{
				if( MAP_SYS?.map_debug )
				{
					MapLogger.groupCollapsed( MAP_SYS.maplog + ' tried to load the following dependencies:' );
					MapLogger.log( MAP_SYS.dependencies );
				}
			}
			else
			{
				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + ' no dependencies to load.' );
			}

			MapLogger.groupEnd();

		}
		catch( error )
		{
			console.error( error );
		}
	},

	/**
	 * Return the full Google Consent Mode state object.
	 * Works for both 'native' and 'gtm' implementation types.
	 *
	 * Resolution order:
	 *   1. MAP_SYS.current_gconsent  (live in-memory state, source of truth)
	 *   2. Cookie-persisted state    (for visitors with previous consent)
	 *   3. Configured default        (cmode_v2_default_consent_obj, native fallback)
	 *
	 * Returns null if Consent Mode v2 is disabled or no state is available.
	 */
	getFullGoogleConsentStatus: function()
	{
	    try {
	        if( !MAP_SYS?.cmode_v2 )
	        {
	            return null;
	        }

	        // 1. In-memory state (covers both native and gtm after init)
	        if( MAP_SYS?.current_gconsent &&
	            typeof MAP_SYS.current_gconsent === 'object' )
	        {
	            var hasKeys = false;
	            for( var k in MAP_SYS.current_gconsent )
	            {
	                if( Object.prototype.hasOwnProperty.call( MAP_SYS.current_gconsent, k ) )
	                {
	                    hasKeys = true;
	                    break;
	                }
	            }
	            if( hasKeys )
	            {
	                return { ...MAP_SYS.current_gconsent };
	            }
	        }

	        // 2. Cookie fallback (gtm mode with returning visitor whose cookie
	        //    has not been parsed yet)
	        if( typeof this.exportGoogleConsentObjectFromCookie === 'function' )
	        {
	            var fromCookie = this.exportGoogleConsentObjectFromCookie();
	            if( fromCookie ) return fromCookie;
	        }

	        // 3. Configured default (native mode safety net; gtm mode typically
	        //    has no MAP-side default so this returns null and the caller
	        //    falls back to platform-level defaults - e.g. Shopify essential)
	        if( typeof map_full_config !== 'undefined' &&
	            map_full_config?.cmode_v2_default_consent_obj )
	        {
	            return { ...map_full_config.cmode_v2_default_consent_obj };
	        }

	        return null;
	    }
	    catch( error )
	    {
	        console.error( error );
	        return null;
	    }
	},

	//get consent status for consent mode (Clarity)
	getClarityConsentStatus: function( key )
	{
		try {
			return MAP_SYS?.current_cconsent[ key ];
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//get consent status for consent mode (Microsoft)
	getMicrosoftConsentStatus: function( key )
	{
		try {
			return MAP_SYS?.current_mconsent[ key ];
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//get consent status for consent mode (Google)
	getGoogleConsentStatus: function( key )
	{
		try {
			return MAP_SYS?.current_gconsent[ key ];
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//f for updating given consent (Clarity)
	updateClarityConsent: function( key, value, updateStatusCookie = false, isRestore = false )
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.clarity_cmode )
			{
				var currentCConsent = {...MAP_SYS?.current_cconsent};
				currentCConsent[key] = value;
				MAP_SYS.current_cconsent = currentCConsent;

				// Map keys: remove "clarity_" prefix and replace "_storage" with "_Storage"
				var to_send_consent = Object.fromEntries(
				  Object.entries(currentCConsent).map(([key, value]) => {
				    // Remove "clarity_" prefix if present
				    var newKey = key.startsWith('clarity_') ? key.slice('clarity_'.length) : key;
				    // Replace all occurrences of "_storage" with "_Storage"
				    newKey = newKey.replace(/_storage/g, '_Storage');
				    return [newKey, value];
				  })
				);

				//MapLogger.log( to_send_consent );

				if( typeof window.clarity !== 'undefined' )
				{
					window.clarity( 'consentv2', {
					  source: MAP_SYS.clarity_cmp_id,
					  ...to_send_consent
					});
				}

				if( updateStatusCookie )
				{
					that.saveClarityConsentStatusToCookie( MAP_SYS?.current_cconsent );
				}

				if( !isRestore ) that.updateLastConsentRecords();

				return true;
			}

			return false;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//f for updating given consent (Microsoft)
	updateMicrosoftConsent: function( key, value, updateStatusCookie = false, isRestore = false )
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.microsoft_cmode )
			{
				var newConsent = {};
				let key_fixed = key.startsWith( 'microsoft_' ) ? key.slice( 'microsoft_'.length ) : key;
				newConsent[ key_fixed ] = value;

				if( typeof window.uetq !== 'undefined' )
				{
					window.uetq.push( 'consent', 'update', newConsent );
				}

				var currentMConsent = {...MAP_SYS?.current_mconsent};
				currentMConsent[key] = value;
				MAP_SYS.current_mconsent = currentMConsent;

				if( updateStatusCookie )
				{
					that.saveMicrosoftConsentStatusToCookie( MAP_SYS?.current_mconsent );
				}

				if( !isRestore ) that.updateLastConsentRecords();

				return true;
			}

			return false;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//f. for consent events dispatch (GA4 and custom global event)
	dispatchConsentEvents: function( eventLabel, dataKey, value )
	{
		try {
			// Validate eventLabel (required)
			if( !eventLabel )
			{
				if( MAP_SYS?.map_debug )
				{
					MapLogger.debug( MAP_SYS.maplog + 'dispatchConsentEvents - Missing eventLabel' );
				}
				return;
			}

			if( MAP_SYS?.map_debug )
			{
				MapLogger.debug( MAP_SYS.maplog + 'dispatchConsentEvents - Event name: ' + eventLabel + (value || '') );
				MapLogger.debug( MAP_SYS.maplog + 'dispatchConsentEvents - Data key: ' + dataKey );
				MapLogger.debug( MAP_SYS.maplog + 'dispatchConsentEvents - Value: ' + value );
				if( dataKey && value )
				{
					MapLogger.debug( MAP_SYS.maplog + 'dispatchConsentEvents - Payload: ' + JSON.stringify({[dataKey]: value}) );
				}
			}

			// Prepare event detail/payload only if both dataKey and value are present
			var eventDetail = {};

			if( dataKey && value )
			{
				eventDetail = { [dataKey]: value };
			}

			// Send custom global event
			window.dispatchEvent( new CustomEvent( eventLabel + (value || ''), {
				detail: eventDetail
			} ) );

			if( MAP_SYS?.send_ga4_event_on_consent_change )
			{
				if( typeof window.gtag === 'function' && window.gtag )
				{
					// Send GA4 event
					gtag( 'event', eventLabel + (value || ''), eventDetail );
				}
			}
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//f for updating given consent by key / value (Google)
	updateGoogleConsentbyKeyValue: function( key, value, updateStatusCookie = false)
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.cmode_v2 )
			{
				if( MAP_SYS?.cmode_v2_implementation_type === 'native' )
				{
					var newConsent = {};
					newConsent[key] = value;

					if( typeof gtag === 'function' )
					{
						gtag( 'consent', 'update', newConsent );
					}
					else
					{
						MapLogger.warn( MAP_SYS.maplog + 'consent update LOST: gtag unavailable', newConsent );
					}

					//Dispatch events
					that.dispatchConsentEvents( 'myagileprivacy_gcmode_consent_', key + '_status', value );
				}

				if( MAP_SYS?.cmode_v2_implementation_type === 'gtm' )
				{
					var newConsent = {};
					newConsent[key] = value;

					//guard over empty array
					if( typeof window.gTagManagerConsentListeners !== 'undefined' &&
						Array.isArray( window.gTagManagerConsentListeners ) &&
						window.gTagManagerConsentListeners.length > 0 )
					{
						window.gTagManagerConsentListeners.forEach( ( callback ) => {
							callback( newConsent );
						});
					}
					else if( typeof gtag === 'function' )
					{
						//fallback
						gtag( 'consent', 'update', newConsent );
					}

					//Dispatch events
					that.dispatchConsentEvents( 'myagileprivacy_gcmode_consent_', key + '_status', value );
				}

				var currentGConsent = {...MAP_SYS?.current_gconsent};
				currentGConsent[key] = value;
				MAP_SYS.current_gconsent = currentGConsent;

				if( updateStatusCookie )
				{
					that.saveGoogleConsentStatusToCookie( MAP_SYS?.current_gconsent );
				}

				that.updateLastConsentRecords();

				return true;
			}

			return false;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//f for updating several given consents at once (Google)
	updateGoogleConsentbyKeyValues: function( newConsent, updateStatusCookie = false )
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.cmode_v2 && newConsent && typeof newConsent === 'object' && Object.keys( newConsent ).length > 0 )
			{
				var dispatchEvents = false;

				if( MAP_SYS?.cmode_v2_implementation_type === 'native' )
				{
					if( typeof gtag === 'function' )
					{
						gtag( 'consent', 'update', newConsent );
					}
					else
					{
						MapLogger.warn( MAP_SYS.maplog + 'consent update LOST: gtag unavailable', newConsent );
					}

					dispatchEvents = true;
				}

				if( MAP_SYS?.cmode_v2_implementation_type === 'gtm' )
				{
					//guard over empty array
					if( typeof window.gTagManagerConsentListeners !== 'undefined' &&
						Array.isArray( window.gTagManagerConsentListeners ) &&
						window.gTagManagerConsentListeners.length > 0 )
					{
						window.gTagManagerConsentListeners.forEach( ( callback ) => {
							callback( newConsent );
						});
					}
					else if( typeof gtag === 'function' )
					{
						//fallback
						gtag( 'consent', 'update', newConsent );
					}

					dispatchEvents = true;
				}

				var currentGConsent = {...MAP_SYS?.current_gconsent};

				Object.keys( newConsent ).forEach( function( key ) {

					currentGConsent[key] = newConsent[key];

					//Dispatch events
					if( dispatchEvents )
					{
						that.dispatchConsentEvents( 'myagileprivacy_gcmode_consent_', key + '_status', newConsent[key] );
					}
				});

				MAP_SYS.current_gconsent = currentGConsent;

				if( updateStatusCookie )
				{
					that.saveGoogleConsentStatusToCookie( MAP_SYS?.current_gconsent );
				}

				that.updateLastConsentRecords();

				return true;
			}

			return false;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//f for updating given consent by full object (Google)
	updateGoogleConsentbyObj: function( newConsent, updateStatusCookie = false, isRestore = false )
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.cmode_v2 )
			{
				if( MAP_SYS?.cmode_v2_implementation_type === 'native' )
				{
					if( typeof gtag === 'function' )
					{
						gtag( 'consent', 'update', newConsent );
					}
					else
					{
						MapLogger.warn( MAP_SYS.maplog + 'consent update LOST: gtag unavailable', newConsent );
					}
				}

				if( MAP_SYS?.cmode_v2_implementation_type === 'gtm' )
				{
					//guard over empty array
					if( typeof window.gTagManagerConsentListeners !== 'undefined' &&
						Array.isArray( window.gTagManagerConsentListeners ) &&
						window.gTagManagerConsentListeners.length > 0 )
					{
						window.gTagManagerConsentListeners.forEach( ( callback ) => {
							callback( newConsent );
						});
					}
					else if( typeof gtag === 'function' )
					{
						//fallback
						gtag( 'consent', 'update', newConsent );
					}
				}

				MAP_SYS.current_gconsent = newConsent;

				if( updateStatusCookie )
				{
					that.saveGoogleConsentStatusToCookie( MAP_SYS?.current_gconsent );
				}

				if( !isRestore ) that.updateLastConsentRecords();

				return true;
			}

			return false;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//from cookie value to object (Clarity)
	parseClarityConsentStatus: function( consentStatusValue )
	{
		return this.parseGoogleConsentStatus( consentStatusValue );
	},


	//from cookie value to object (Microsoft)
	parseMicrosoftConsentStatus: function( consentStatusValue )
	{
		return this.parseGoogleConsentStatus( consentStatusValue );
	},

	//from cookie value to object (Google)
	parseGoogleConsentStatus: function( consentStatusValue )
	{
		try {

			// Split the encoded string into individual key-value pairs
			var keyValuePairs = consentStatusValue.split('|');

			// Create an empty object to store the decoded values
			var decodedObject = {};

			// Iterate over the key-value pairs
			keyValuePairs.forEach( function( pair ) {
				// Split each pair into key and value
				var parts = pair.split(':');

				// Extract key and convert value to boolean
				var key = parts[0];
				var value = ( parts[1] === 'true' ) ? 'granted' : 'denied';

				decodedObject[key] = value;
			});

			return decodedObject;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//from consent object to cookie (Clarity)
	saveClarityConsentStatusToCookie : function( consentObject )
	{
		try {

			// Convert object values to a string
			var encodedString = Object.keys( consentObject )
				.map(function( key ) {
				return key + ':' + ( consentObject[key] === 'granted' );
				})
				.join('|');


			MAP_Cookie.set( MAP_CLARITY_CONSENT_STATUS, encodedString, MAP_SYS.map_cookie_expire );

			return true;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//from consent object to cookie (Microsoft)
	saveMicrosoftConsentStatusToCookie : function( consentObject )
	{
		try {

			// Convert object values to a string
			var encodedString = Object.keys( consentObject )
				.map(function( key ) {
				return key + ':' + ( consentObject[key] === 'granted' );
				})
				.join('|');


			MAP_Cookie.set( MAP_MICROSOFT_CONSENT_STATUS, encodedString, MAP_SYS.map_cookie_expire );

			return true;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//from consent object to cookie (Google)
	saveGoogleConsentStatusToCookie : function( consentObject )
	{
		try {

			// Convert object values to a string
			var encodedString = Object.keys( consentObject )
				.map(function( key ) {
				return key + ':' + ( consentObject[key] === 'granted' );
				})
				.join('|');


			MAP_Cookie.set( MAP_CONSENT_STATUS, encodedString, MAP_SYS.map_cookie_expire );

			return true;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//for google tag manager cookie parsing (gtm)
	exportGoogleConsentObjectFromCookie: function()
	{
		//for preserving scope
		var that = this;

		try {

			var cookieValue = MAP_Cookie.read( MAP_CONSENT_STATUS );

			if( cookieValue )
			{
				var this_gconsent = map_refusal_safe( that.parseGoogleConsentStatus( cookieValue ) );

				return this_gconsent;
			}

			return null;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//set initial consent from google tag manager (gtm)
	setFromGoogleTagManagerInitialConsent: function( gconsent )
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function setFromGoogleTagManagerInitialConsent' );

			if( gconsent )
			{
				//save starting consent
				MAP_SYS.starting_gconsent = {...gconsent};
				MAP_SYS.current_gconsent = {...gconsent};

				that.saveGoogleConsentStatusToCookie( gconsent );

				//init only status, not events
				that.userPreferenceInit( true );
			}

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//consent listener for google tag manager (gtm)
	googleTagManagerConsentListener: function( callback )
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function googleTagManagerConsentListener' );

    		window.gTagManagerConsentListeners = window.gTagManagerConsentListeners || [];
        	window.gTagManagerConsentListeners.push( callback );

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//init data for Google consent mode
	setupConsentModeV2 : function()
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function setupConsentModeV2' );

			if( typeof map_full_config === 'undefined' )
			{
				return false;
			}

			MAP_SYS.cmode_v2 = map_full_config?.enable_cmode_v2;
			MAP_SYS.cmode_v2_implementation_type = map_full_config?.cmode_v2_implementation_type;
			MAP_SYS.cmode_v2_forced_off_ga4_advanced = map_full_config?.cmode_v2_forced_off_ga4_advanced;
			MAP_SYS.cmode_v2_js_on_error = map_full_config?.cmode_v2_js_on_error;

			if( MAP_SYS.cmode_v2 && MAP_SYS.cmode_v2_implementation_type == 'gtm' )
			{
				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting default value for consent mode (gtm)' );

				var cookieValue = MAP_Cookie.read( MAP_CONSENT_STATUS );

				if( cookieValue )
				{
					var this_gconsent = that.maskedStoredConsent( 'google', cookieValue );
					//setting initial current_gconsent value (deep copy using spread operator)
					MAP_SYS.current_gconsent = {...this_gconsent};
				}
			}

			if( MAP_SYS.cmode_v2 && MAP_SYS.cmode_v2_implementation_type == 'native' )
			{
				var map_early_gcmode = ( typeof map_full_config.early_gcmode !== 'undefined' && map_full_config.early_gcmode );
				var map_early_ready = ( typeof early_starting_gconsent !== 'undefined' && early_starting_gconsent );

				if( map_early_gcmode && map_early_ready )
				{
					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting default value for consent mode (native) via early consent' );

					//save starting consent
					MAP_SYS.starting_gconsent = window.early_starting_gconsent;

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting default consent (native)' );

					//setting initial current_gconsent value (deep copy using spread operator)
					MAP_SYS.current_gconsent = { ...MAP_SYS.starting_gconsent };

					MAP_SYS.early_gcmode = true;
				}
				else
				{
					// no default was sent before this script
					if( map_early_gcmode )
					{
						window.dataLayer = window.dataLayer || [];
						if( typeof window.gtag !== 'function' ) window.gtag = function(){ window.dataLayer.push( arguments ); };
						window.gtag( 'set', 'developer_id.dY2ZhMm', true );
						if( map_full_config.enable_cmode_url_passthrough ) window.gtag( 'set', 'url_passthrough', true );
					}

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting default value for consent mode (native)' );

					//save starting consent
					MAP_SYS.starting_gconsent = map_full_config?.cmode_v2_default_consent_obj;

					//setting default consent value

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting default consent (native)' );

					//setting initial current_gconsent value (deep copy using spread operator)
					MAP_SYS.current_gconsent = { ...MAP_SYS.starting_gconsent };

					try {
						if( typeof gtag === 'function' )
						{
							gtag( 'consent', 'default', { ...MAP_SYS.starting_gconsent } );
						}
						else
						{
							MapLogger.warn( MAP_SYS.maplog + 'consent default LOST: gtag unavailable', MAP_SYS.starting_gconsent );
						}
					}
					catch( error )
					{
						console.error( error );
					}

					if( map_early_gcmode ) window.early_starting_gconsent = MAP_SYS.starting_gconsent;
				}

				var cookieValue = MAP_Cookie.read( MAP_CONSENT_STATUS );

				// the stored state is restored only alongside a choice that still holds
				if( cookieValue && map_stored_choice_holds() )
				{
					var this_gconsent = that.maskedStoredConsent( 'google', cookieValue );

					//setting initial current_gconsent value (deep copy using spread operator)
					MAP_SYS.current_gconsent = { ...this_gconsent };

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting consent with value=' + cookieValue );

					that.updateGoogleConsentbyObj( this_gconsent, false, true );
				}
				else
				{
					that.saveGoogleConsentStatusToCookie( MAP_SYS.current_gconsent );
				}
			}

			return true;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//the four signals of the Google switches set to one value, {} without Consent Mode
	googleConsentForAll: function( value )
	{
		var out = {};

		if( !MAP_SYS?.cmode_v2 ) return out;

		[ 'ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage' ].forEach( function( k ) { out[k] = value; } );

		return out;
	},

	//for each Consent Mode, the signals that an accepted service grants when the panel has no switches for it
	consentModeServices: {
		google: {
			flag: 'cmode_v2',
			cls: 'map-consent-google',
			keys: [ 'ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage' ],
			services: {
				google_analytics: [ 'analytics_storage' ],
				my_agile_pixel_ga: [ 'analytics_storage' ],
				google_tag_manager: [ 'ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage' ],
				stape: [ 'ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage' ]
			}
		},
		clarity: {
			flag: 'clarity_cmode',
			cls: 'map-consent-clarity',
			keys: [ 'clarity_ad_storage', 'clarity_analytics_storage' ],
			services: { microsoft_clarity: [ 'clarity_ad_storage', 'clarity_analytics_storage' ] }
		},
		microsoft: {
			flag: 'microsoft_cmode',
			cls: 'map-consent-microsoft',
			keys: [ 'microsoft_ad_storage' ],
			services: { microsoft_ads: [ 'microsoft_ad_storage' ] }
		}
	},

	//signals of a choice ('accept', 'reject', 'partial' on apiKey) without the switches of a Consent Mode: null with the switches, {} when no signal changes
	consentWithoutSwitches: function( vendor, choice, apiKey )
	{
		try {
			var rule = this.consentModeServices[ vendor ];

			if( !rule || !MAP_SYS?.[ rule.flag ] || !this.settingsModal ) return null;
			if( this.settingsModal.querySelector( '.map-consent-mode-preference-checkbox.' + rule.cls ) ) return null;

			var out = {};

			if( choice === 'reject' )
			{
				rule.keys.forEach( function( k ) { out[k] = 'denied'; } );
				return out;
			}

			// a partial choice on a service that grants none of these signals leaves them as they are
			if( choice === 'partial' && !Object.prototype.hasOwnProperty.call( rule.services, apiKey ) ) return out;

			var acceptedAll = ( choice === 'accept' ) || MAP_Cookie.read( MAP_ACCEPTED_ALL_COOKIE_NAME ) === '1';

			this.settingsModal.querySelectorAll( '.map_cookie_description_wrapper[data-cookie-api-key]' ).forEach( function( wrapper ) {
				var signals = rule.services[ wrapper.getAttribute( 'data-cookie-api-key' ) ];

				if( !signals ) return;

				var index = wrapper.getAttribute( 'data-cookie-baseindex' );
				// a necessary service always counts as accepted; a refusal has already returned above
				var accepted = acceptedAll || wrapper.classList.contains( '_always_on' ) || ( !!index && MAP_Cookie.read( 'map_cookie_' + index + MAP_POSTFIX ) === '1' );

				signals.forEach( function( k ) { if( out[k] !== 'granted' ) out[k] = accepted ? 'granted' : 'denied'; } );
			});

			return out;
		}
		catch( error )
		{
			console.error( error );
			return null;
		}
	},

	//applies a choice made without switches to the given Consent Modes, a partial one only where a signal changes
	applyConsentWithoutSwitches: function( choice, apiKey, isRestore, vendors )
	{
		var that = this;

		try {
			( vendors || [ 'google', 'clarity', 'microsoft' ] ).forEach( function( vendor ) {
				var consent = that.consentWithoutSwitches( vendor, choice, apiKey );

				if( !consent ) return;

				var current = { google: MAP_SYS.current_gconsent, clarity: MAP_SYS.current_cconsent, microsoft: MAP_SYS.current_mconsent }[ vendor ] || {};
				var keys = Object.keys( consent ).filter( function( k ) { return choice !== 'partial' || current[k] !== consent[k]; } );

				if( !keys.length ) return;

				if( vendor === 'google' )
				{
					var changed = {};
					keys.forEach( function( k ) { changed[k] = consent[k]; } );

					if( isRestore === true ) that.updateGoogleConsentbyObj( { ...current, ...changed }, true, true );
					else that.updateGoogleConsentbyKeyValues( changed, true );

					return;
				}

				keys.forEach( function( k ) {
					if( vendor === 'clarity' ) that.updateClarityConsent( k, consent[k], true, isRestore === true );
					else that.updateMicrosoftConsent( k, consent[k], true, isRestore === true );
				});
			});
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//removes a cookie, also with an earlier copy of MAP_Cookie
	dropCookie: function( name )
	{
		if( typeof MAP_Cookie.delete === 'function' ) MAP_Cookie.delete( name );
		else MAP_Cookie.set( name, '', -1 );
	},

	//checks a stored implicit consent against the privacy signal and the geo zone
	reconcileImplicitConsent: function()
	{
		try {
			if( this.isSplitPreview() ) return '';

			var why = map_implicit_consent_void();

			if( why === 'gpc' ) this.refuseImplicitConsent();
			if( why === 'block' ) this.revokeImplicitConsent();

			return why;
		}
		catch( error )
		{
			console.error( error );
			return '';
		}
	},

	//turns an implicit consent into a refusal, as no choice event
	refuseImplicitConsent: function()
	{
		var that = this;

		try {
			MapLogger.log( 'MAP-GEO', 'implicit consent with GPC → refusal' );

			var googleConsent = {};

			that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox' ).forEach( function( elem ) {
				elem.value = '0';
				MAP.mapSyncSlider( elem );

				var consent_key = elem.getAttribute( 'data-consent-key' );

				if( elem.classList.contains( 'map-consent-clarity' ) ) that.updateClarityConsent( consent_key, 'denied', true, true );
				if( elem.classList.contains( 'map-consent-microsoft' ) ) that.updateMicrosoftConsent( consent_key, 'denied', true, true );
				if( elem.classList.contains( 'map-consent-google' ) ) googleConsent[consent_key] = 'denied';
			});

			if( !Object.keys( googleConsent ).length ) googleConsent = that.consentWithoutSwitches( 'google', 'reject' ) || {};

			that.applyConsentWithoutSwitches( 'reject', null, true, [ 'clarity', 'microsoft' ] );

			// sent even when unchanged: an earlier restore may have granted
			if( Object.keys( googleConsent ).length ) that.updateGoogleConsentbyObj( { ...( MAP_SYS.current_gconsent || {} ), ...googleConsent }, true, true );

			that.settingsModal.querySelectorAll( '.map-user-preference-checkbox' ).forEach( function( elem ) {
				MAP_Cookie.set( 'map_cookie_' + elem.getAttribute( 'data-cookie-baseindex' ) + MAP_POSTFIX, '-1', MAP_SYS.map_cookie_expire );
				elem.value = '0';
				MAP.mapSyncSlider( elem );
			});

			MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );
			MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );

			if( typeof window.MAPUpdateGPP === 'function' ) window.MAPUpdateGPP( true );

			// last, so that a refusal stopped halfway is tried again on the next page
			that.dropCookie( map_implicit_consent_name() );

			if( typeof window.CookieShield?.resetAcceptedAllCache === 'function' ) window.CookieShield.resetAcceptedAllCache();

			(async() => {
				while( !MAP_SYS.map_document_load )
				{
					await new Promise( resolve => setTimeout( resolve, 10 ) );
				}

				if( typeof window.MAPIABTCF_denyAllConsent === 'function' )
				{
					window.MAPIABTCF_denyAllConsent( true );
					that.updateSomeConsentGivenStatus();
				}
			})();

			return true;
		}
		catch( error )
		{
			console.error( error );
			return false;
		}
	},

	//withdraws an implicit consent: no choice is left and the banner asks again
	revokeImplicitConsent: function()
	{
		var that = this;

		try {
			MapLogger.log( 'MAP-GEO', 'implicit consent outside a no-block zone → withdrawn' );

			[ [ MAP_SYS.starting_cconsent, 'updateClarityConsent' ], [ MAP_SYS.starting_mconsent, 'updateMicrosoftConsent' ] ].forEach( function( pair ) {
				if( pair[0] && typeof pair[0] === 'object' )
				{
					Object.keys( pair[0] ).forEach( function( k ) { that[ pair[1] ]( k, pair[0][k], true, true ); } );
				}
			});

			var def = ( MAP_SYS?.cmode_v2_implementation_type === 'native' && map_full_config?.cmode_v2_default_consent_obj && typeof map_full_config.cmode_v2_default_consent_obj === 'object' ) ?
				map_full_config.cmode_v2_default_consent_obj : that.googleConsentForAll( 'denied' );

			// sent even when unchanged: an earlier restore may have granted
			if( MAP_SYS?.cmode_v2 && def && Object.keys( def ).length ) that.updateGoogleConsentbyObj( { ...( MAP_SYS.current_gconsent || {} ), ...def }, true, true );

			var mine = new RegExp( '^map_cookie_\\d+' + String( MAP_POSTFIX || '' ).replace( /[^\w]/g, '\\$&' ) + '$' );
			var list = ( typeof MAP_Cookie.getCookieNameList === 'function' ) ? MAP_Cookie.getCookieNameList() : [];

			list.forEach( function( name ) { if( mine.test( name ) ) that.dropCookie( name ); } );

			[ MAP_ACCEPTED_ALL_COOKIE_NAME, MAP_ACCEPTED_SOMETHING_COOKIE_NAME, MAP_USER_UUID, MAP_LAST_CONSENT_MODIFY_DATE, map_implicit_consent_name() ].forEach( function( name ) {
				if( MAP_Cookie.exists( name ) ) that.dropCookie( name );
			});

			MAP_SYS.user_uuid = null;
			MAP_SYS.last_consent_modify_date = null;

			that.settingsModal.querySelectorAll( '.map-user-preference-checkbox, .map-consent-mode-preference-checkbox' ).forEach( function( elem ) {
				elem.value = '0';
				MAP.mapSyncSlider( elem );
			});

			if( typeof window.CookieShield?.resetAcceptedAllCache === 'function' ) window.CookieShield.resetAcceptedAllCache();

			that.updateSomeConsentGivenStatus();

			return true;
		}
		catch( error )
		{
			console.error( error );
			return false;
		}
	},

	//stored state of a Consent Mode read back for a restore: after a refusal of everything nothing granted, and the cookie follows
	maskedStoredConsent: function( vendor, cookieValue )
	{
		var parsed = ( vendor === 'clarity' ) ? this.parseClarityConsentStatus( cookieValue ) :
			( ( vendor === 'microsoft' ) ? this.parseMicrosoftConsentStatus( cookieValue ) : this.parseGoogleConsentStatus( cookieValue ) );
		var masked = map_refusal_safe( parsed );

		try {
			if( masked && JSON.stringify( masked ) !== JSON.stringify( parsed ) )
			{
				if( vendor === 'clarity' ) this.saveClarityConsentStatusToCookie( masked );
				else if( vendor === 'microsoft' ) this.saveMicrosoftConsentStatusToCookie( masked );
				else this.saveGoogleConsentStatusToCookie( masked );
			}
		}
		catch( error )
		{
			console.error( error );
		}

		return masked;
	},

	//brings every active Consent Mode to the signals of a refusal, sending only what changes and without recording a choice
	applyRefusalSignals: function()
	{
		var that = this;

		try {
			Object.keys( that.consentModeServices ).forEach( function( vendor ) {
				var rule = that.consentModeServices[ vendor ];

				if( !MAP_SYS?.[ rule.flag ] ) return;

				var current = { google: MAP_SYS.current_gconsent, clarity: MAP_SYS.current_cconsent, microsoft: MAP_SYS.current_mconsent }[ vendor ] || {};
				var keys = rule.keys.filter( function( k ) { return current[k] !== 'denied'; } );

				if( !keys.length ) return;

				if( vendor === 'google' )
				{
					var changed = {};
					keys.forEach( function( k ) { changed[k] = 'denied'; } );
					that.updateGoogleConsentbyObj( { ...current, ...changed }, true, true );
					return;
				}

				keys.forEach( function( k ) {
					if( vendor === 'clarity' ) that.updateClarityConsent( k, 'denied', true, true );
					else that.updateMicrosoftConsent( k, 'denied', true, true );
				});
			});

			return true;
		}
		catch( error )
		{
			console.error( error );
			return false;
		}
	},

	//init data for Clarity consent mode
	setupClarityConsentMode: function()
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function setupClarityConsentMode' );

			if( typeof map_full_config === 'undefined' )
			{
				return false;
			}

			MAP_SYS.clarity_cmode = map_full_config?.enable_clarity_cmode;

			if( MAP_SYS.clarity_cmode )
			{
				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting default value for consent mode (Clarity)' );

				//save starting consent
				MAP_SYS.starting_cconsent = map_full_config?.cmode_clarity_default_consent_obj;

				var cookieValue = MAP_Cookie.read( MAP_CLARITY_CONSENT_STATUS );

				// the stored state is restored only alongside a choice that still holds
				if( cookieValue && map_stored_choice_holds() )
				{
					var this_cconsent = that.maskedStoredConsent( 'clarity', cookieValue );

					//setting initial current_gconsent value (deep copy using spread operator)
					MAP_SYS.current_cconsent = { ...this_cconsent };

					try {

						// Map keys: remove "clarity_" prefix and replace "_storage" with "_Storage"
						var current_cconsent_fixed = Object.fromEntries(
						  Object.entries(this_cconsent).map(([key, value]) => {
						    // Remove "clarity_" prefix if present
						    var newKey = key.startsWith('clarity_') ? key.slice('clarity_'.length) : key;
						    // Replace all occurrences of "_storage" with "_Storage"
						    newKey = newKey.replace(/_storage/g, '_Storage');
						    return [newKey, value];
						  })
						);

						if( typeof window.clarity !== 'undefined' )
						{
							window.clarity( 'consentv2', {
							  source: MAP_SYS.clarity_cmp_id,
							  ...current_cconsent_fixed
							});
						}
					}
					catch( error )
					{
						console.error( error );
					}
				}
				else
				{
					//no cookie value case

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting default consent (Clarity)' );

					//setting initial current_gconsent value (deep copy using spread operator)
					MAP_SYS.current_cconsent = { ...MAP_SYS.starting_cconsent };

					try {

						// Map keys: remove "clarity_" prefix and replace "_storage" with "_Storage"
						var current_cconsent_fixed = Object.fromEntries(
						  Object.entries( MAP_SYS.starting_cconsent ).map(([key, value]) => {
						    // Remove "clarity_" prefix if present
						    var newKey = key.startsWith('clarity_') ? key.slice('clarity_'.length) : key;
						    // Replace all occurrences of "_storage" with "_Storage"
						    newKey = newKey.replace(/_storage/g, '_Storage');
						    return [newKey, value];
						  })
						);

						if( typeof window.clarity !== 'undefined' )
						{
							window.clarity( 'consentv2', {
							  source: MAP_SYS.clarity_cmp_id,
							  ...current_cconsent_fixed
							});
						}
					}
					catch( error )
					{
						console.error( error );
					}

					that.saveClarityConsentStatusToCookie( MAP_SYS.current_cconsent );
				}
			}

			return true;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//init data for Microsoft consent mode
	setupMicrosoftConsentMode: function()
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function setupMicrosoftConsentMode' );

			if( typeof map_full_config === 'undefined' )
			{
				return false;
			}

			MAP_SYS.microsoft_cmode = map_full_config?.enable_microsoft_cmode;

			if( MAP_SYS.microsoft_cmode )
			{
				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting default value for consent mode (Microsoft)' );

				//save starting consent
				MAP_SYS.starting_mconsent = map_full_config?.cmode_microsoft_default_consent_obj;

				var cookieValue = MAP_Cookie.read( MAP_MICROSOFT_CONSENT_STATUS );

				// the stored state is restored only alongside a choice that still holds
				if( cookieValue && map_stored_choice_holds() )
				{
					var this_mconsent = that.maskedStoredConsent( 'microsoft', cookieValue );

					//setting initial current_gconsent value (deep copy using spread operator)
					MAP_SYS.current_mconsent = { ...this_mconsent };

					try {

						var current_mconsent_fixed = Object.fromEntries(
						  Object.entries( this_mconsent ).map( ([key, value] ) => [
							key.startsWith( 'microsoft_' ) ? key.slice( 'microsoft_'.length) : key,
							value
						  ])
						);

						if( typeof window.uetq !== 'undefined' )
						{
							window.uetq.push( 'consent', 'default', { ...current_mconsent_fixed } );
						}
					}
					catch( error )
					{
						console.error( error );
					}
				}
				else
				{
					//no cookie value case

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting default consent (Microsoft)' );

					//setting initial current_gconsent value (deep copy using spread operator)
					MAP_SYS.current_mconsent = { ...MAP_SYS.starting_mconsent };

					try {

						var current_mconsent_fixed = Object.fromEntries(
						  Object.entries( MAP_SYS.starting_mconsent ).map( ([key, value] ) => [
							key.startsWith( 'microsoft_' ) ? key.slice( 'microsoft_'.length) : key,
							value
						  ])
						);

						if( typeof window.uetq !== 'undefined' )
						{
							window.uetq.push( 'consent', 'default', { ...current_mconsent_fixed } );
						}

					}
					catch( error )
					{
						console.error( error );
					}

					that.saveMicrosoftConsentStatusToCookie( MAP_SYS.current_mconsent );
				}
			}

			return true;
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//earlier suffix of this site's consent cookies, '' when there is none or it is the current one
	legacyResetPostfix : function()
	{
		var legacy = map_legacy_reset_postfix( this.settings );
		var current = ( typeof MAP_POSTFIX === 'string' ) ? MAP_POSTFIX : '';

		return ( legacy && legacy !== current ) ? legacy : '';
	},

	//true when the only choice is a refusal saved under the earlier cookie name
	legacyRefusal : function()
	{
		try {
			var settings = this.settings || ( ( typeof map_cookiebar_settings !== 'undefined' ) ? map_cookiebar_settings : null );
			var legacy = map_legacy_reset_postfix( settings );
			var current = ( typeof MAP_POSTFIX === 'string' ) ? MAP_POSTFIX : '';

			return !!legacy && legacy !== current &&
				!MAP_Cookie.exists( MAP_ACCEPTED_ALL_COOKIE_NAME ) &&
				!MAP_Cookie.exists( MAP_ACCEPTED_SOMETHING_COOKIE_NAME ) &&
				MAP_Cookie.read( 'map_accepted_all_cookie_policy' + legacy ) === '-1';
		}
		catch( e )
		{
			return false;
		}
	},

	//removes the consent cookies of this site named with an earlier suffix
	dropLegacyResetCookies : function()
	{
		try {
			if( typeof MAP_Cookie.getCookieNameList !== 'function' || typeof MAP_Cookie.delete !== 'function' ) return;

			var legacy = this.legacyResetPostfix();

			if( !legacy ) return;

			var list = MAP_Cookie.getCookieNameList();

			for( var i = 0; i < list.length; i++ )
			{
				var name = list[i];
				var base = ( name.length > legacy.length && name.slice( -legacy.length ) === legacy ) ? name.slice( 0, -legacy.length ) : '';

				if( /^map_(accepted_all_cookie_policy|accepted_something_cookie_policy|consent_status|microsoft_consent_status|clarity_consent_status|user_uuid|last_c_m_date|implicit_consent|cookie_\d+)$/.test( base ) )
				{
					MAP_Cookie.delete( name );
				}
			}
		}
		catch( e )
		{
			MapLogger.debug( e );
		}
	},

	//memorize last consent modification date, uuid generation eventually
	updateLastConsentRecords : function()
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'updateLastConsentRecords' );

			that.dropLegacyResetCookies();

			// a recorded choice replaces an implicit consent
			if( MAP_Cookie.exists( map_implicit_consent_name() ) ) that.dropCookie( map_implicit_consent_name() );

			let date = new Date();
			let dateString = date.toISOString();

			MAP_SYS.last_consent_modify_date = date;

			MAP_Cookie.set( MAP_LAST_CONSENT_MODIFY_DATE, dateString, MAP_SYS.map_cookie_expire );

			if( !MAP_Cookie.exists( MAP_USER_UUID ) )
			{
				let uuid = that.generateUUID();

				if( uuid )
				{
					MAP_SYS.user_uuid = uuid;

					MAP_Cookie.set( MAP_USER_UUID, uuid, MAP_SYS.map_cookie_expire );
				}
			}

			return true;
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//used on init
	retrieveLastConsentDate : function()
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_Cookie.exists( MAP_LAST_CONSENT_MODIFY_DATE ) )
			{
				let dateStringFromCookie = MAP_Cookie.read( MAP_LAST_CONSENT_MODIFY_DATE );
				let dateFromCookie = new Date (dateStringFromCookie );

				MAP_SYS.last_consent_modify_date = dateFromCookie;

				return dateFromCookie;
			}

			return null;
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//used on init
	retrieveLastConsentUUID : function()
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_Cookie.exists( MAP_USER_UUID ) )
			{
				let value = MAP_Cookie.read( MAP_USER_UUID );

				MAP_SYS.user_uuid = value;

				return value;
			}

			return null;
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//uuid generation
	generateUUID : function()
	{
		try {

			if( window.crypto && window.crypto.getRandomValues )
			{
				return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
			(c ^ window.crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
		);
			}

			return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, function( c ) {
				return ( c ^ Math.random() * 16 >> c / 4 ).toString( 16 );
			});
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//true when the page is rendered as a banner preview
	isSplitPreview: function()
	{
		return (
			typeof map_full_config !== 'undefined' &&
			map_full_config &&
			map_full_config.split_test &&
			map_full_config.split_test.preview === true
		);
	},

	//true when the page is the response to a form submission
	isPostMode: function()
	{
		return ( !!this?.settings && this.settings.post_mode == 1 );
	},

	toggleBar: function()
	{
		//for preserving scope
		var that = this;

		try
		{

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function toggleBar' );

			// -defer the show/hide decision until the geo zone is
			// resolved (first visit, before the async endpoint returns). The
			// .showConsent listeners below are still bound, so the manage-consent
			// widget works regardless. mapGeoApply() re-invokes toggleBar() once
			// resolved. Sites without geo are unaffected.
			var geoDefer = ( MAP_SYS.geo_enabled && ! MAP_SYS.geo_resolved ) && ! that.isSplitPreview();
			if( geoDefer )
			{
				MAP_SYS.geo_bar_deferred = true;
				MapLogger.log( 'MAP-GEO', 'toggleBar: deferred show/hide until geo resolved (listeners still bound)' );
			}

			// no-block zone hides the banner; GPC keeps it visible.
			var geoNoBlock = ( MAP_SYS.geo_enabled && MAP_SYS.geo_resolved && MAP_SYS.geo_eff_type === 'noblock' );

			if( geoNoBlock && typeof navigator !== 'undefined' && navigator.globalPrivacyControl === true )
			{
				geoNoBlock = false;
				MapLogger.log( 'MAP-GEO', 'toggleBar: GPC active in no-block zone → banner shown for explicit choice' );
			}

			if( ! geoDefer )
			{
			if( that.isSplitPreview() ||
				( ! geoNoBlock &&
				!MAP_Cookie.exists( MAP_ACCEPTED_ALL_COOKIE_NAME ) &&
				!MAP_Cookie.exists( MAP_ACCEPTED_SOMETHING_COOKIE_NAME ) )
				)
			{
				if( MAP_SYS.in_iab_context )
				{
					(async() => {

						while( !MAP_SYS.map_document_load )
						{
							//MapLogger.log( 'not defined yet' );
							await new Promise( resolve => setTimeout( resolve, 10 ) );
						}

						(async() => {
							//MapLogger.log("waiting for variable");
							while( typeof window.MAPIABTCF_brief_html_initted == 'function' &&
								!window.MAPIABTCF_brief_html_initted() )
							{
								//MapLogger.log( 'not defined yet' );
								await new Promise( resolve => setTimeout( resolve, 10 ) );
							}
							//MapLogger.log("variable is defined");
							that.displayBar();

							setTimeout( function() {
								var scroll_to_top = document.querySelector( '.map_notification-message.map-iab-context' );
								if( scroll_to_top && window.getComputedStyle( that.bar_elm ).position === 'fixed' ) {
									scroll_to_top.scrollIntoView( { behavior: 'smooth' } );
								}
							}, 400);

						})();

					})();
				}
				else
				{
					that.displayBar();
				}
			}
			else
			{
				that.hideBar();
			}
			} // end if( ! geoDefer )

			this.showagain_elm.querySelectorAll( '.showConsent' ).forEach( function( $this ) {
				// Bind once per node (toggleBar may run multiple times: defer + finalize).
				if( $this.getAttribute( 'data-map-sc-bound' ) === '1' ) { return; }
				$this.setAttribute( 'data-map-sc-bound', '1' );
				$this.addEventListener( 'click', function( e ) {
					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered showConsent' );

					e.preventDefault();
					e.stopImmediatePropagation();

					if( that.isBarHiddenByOthers() )
					{
						that.markBarHiddenByOthers();
						that.openHiddenBannerHelp( $this );
						return;
					}

					that.bar_blocked = false;

					if( window.MAPIABTCF_ui_guard === true && typeof window.MAPIABTCF_showCMPUI === 'function' ) { window.MAPIABTCF_showCMPUI(); }

					that.dispatchConsentEvents( 'myagileprivacy_resurface' );

					//animation
					var that_animation = MAP.bar_elm.getAttribute( 'data-animation' );
					var that_id_selector = '#' + MAP.bar_elm.getAttribute( 'id' );

					var animation_params = {
						targets: that_id_selector,
						easing: 'easeInOutQuad',
						duration: 1000
					};

					setTimeout( function() {
						MAP.showagain_elm.style.display = 'none';
						MAP.bar_elm.focus();
					}, 150 );

					map_animate({
						targets: MAP.showagain_elm,
						easing: 'easeInOutQuad',
						duration: 150,
						opacity: 0,
						complete: function( anim ) {
							MAP.showagain_elm.style.display = 'none';
							MAP.bar_elm.focus();
						}
					} );

					setTimeout( function() {

						try
						{

							switch( that_animation )
							{
								case 'slide':

									var y_value = '0vh';
									var x_value = '0vw';

									if( MAP.bar_elm.classList.contains( 'map_floating_banner' ) )
									{
										y_value = '3vh';
										x_value = '3vw';
									}

									var $this = MAP.bar_elm;

									if( MAP.bar_elm.className.includes( 'mapPositionBottom' ) ) {
										$this.style.bottom = '-100vh';
										animation_params['bottom'] = y_value;
									}
									else if( MAP.bar_elm.className.includes( 'mapPositionTop' ) ) {
										$this.style.top = '-100vh';
										animation_params['top'] = y_value;
									}
									else if( MAP.bar_elm.className.includes( 'mapPositionCenterLeft' ) ) {
										$this.style.left = '-100vw';
										animation_params['left'] = x_value;
									}
									else if( MAP.bar_elm.className.includes( 'mapPositionCenterCenter' ) ) {
										$this.style.top = '-100%';
										animation_params['top'] = '50%';
									}
									else if( MAP.bar_elm.className.includes( 'mapPositionCenterRight' ) ) {
										$this.style.right = '-100vw';
										animation_params['right'] = x_value;
									}

									MAP.bar_elm.style.display = 'block';
									map_animate(animation_params );
									break;

								case 'fade':
									document.querySelector( that_id_selector ).style.opacity = '0';
									MAP.bar_elm.style.display = 'block';

									animation_params['opacity'] = '1';
									animation_params['duration'] = '500';

									map_animate(animation_params );

									break;

								case 'zoom':
									document.querySelector( that_id_selector ).style.transform = 'scale(0)';
									MAP.bar_elm.style.display = 'block';

									animation_params['scale'] = '1';
									map_animate(animation_params );

									break;

								default: // no animation -> value = "none"
									MAP.bar_elm.style.display = 'block';
									break;
							}

							that.bar_open = true;
							that.dispatchConsentEvents( 'myagileprivacy_bar_shown' );
							that.bar_elm.focus();
							that.optimizeMobile();

						}
						catch( error )
						{
							console.error( error );
						}

					},100 );
				});
			});

			//bof user consent review trigger

			that.bar_elm.addEventListener( 'triggerShowAgainDisplay', function( e ) {
				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered triggerShowAgainDisplay' );

				e.preventDefault();
				e.stopImmediatePropagation();

				if( that.isBarHiddenByOthers() )
				{
					that.markBarHiddenByOthers();
					that.openHiddenBannerHelp();
					return;
				}

				that.bar_blocked = false;

				if( window.MAPIABTCF_ui_guard === true && typeof window.MAPIABTCF_showCMPUI === 'function' ) { window.MAPIABTCF_showCMPUI(); }

				//animation
				var that_animation = MAP.bar_elm.getAttribute('data-animation');
				var that_id_selector = '#' + MAP.bar_elm.getAttribute('id');

				var animation_params = {
					targets: that_id_selector,
					easing: 'easeInOutQuad',
					duration: 1000
				};

				setTimeout( function() {
					MAP.showagain_elm.style.display = 'none';
				}, 150 );

				map_animate({
					targets: MAP.showagain_elm,
					easing: 'easeInOutQuad',
					duration: 150,
					opacity: 0,
					complete: function(anim) {
						MAP.showagain_elm.style.display = 'none';
					}
				} );

				setTimeout( function() {

					try {

						switch( that_animation )
						{
							case 'slide':

								var y_value = '0vh';
								var x_value = '0vw';

								if( MAP.bar_elm.classList.contains( 'map_floating_banner' ) )
								{
									y_value = '3vh';
									x_value = '3vw';
								}

								var element = document.querySelector( that_id_selector );

								if( MAP.bar_elm.className.includes( 'mapPositionBottom' ) ) {
									element.style.bottom = '-100vh';
									animation_params['bottom'] = y_value;
								}
								else if( MAP.bar_elm.className.includes( 'mapPositionTop' ) ) {
									element.style.top = '-100vh';
									animation_params['top'] = y_value;
								}
								else if( MAP.bar_elm.className.includes( 'mapPositionCenterLeft' ) ) {
									element.style.left = '-100vw';
									animation_params['left'] = x_value;
								}
								else if( MAP.bar_elm.className.includes( 'mapPositionCenterCenter' ) ) {
									element.style.top = '-100%';
									animation_params['top'] = '50%';
								}
								else if( MAP.bar_elm.className.includes( 'mapPositionCenterRight') ) {
									element.style.right = '-100vw';
									animation_params['right'] = x_value;
								}

								MAP.bar_elm.style.display = 'block';
								map_animate(animation_params );
								break;

							case 'fade':
								document.querySelector( that_id_selector ).style.opacity = '0';
								MAP.bar_elm.style.display = 'block';

								animation_params['opacity'] = '1';
								animation_params['duration'] = '500';

								map_animate(animation_params );

								break;

							case 'zoom':
								document.querySelector( that_id_selector ).style.transform = 'scale(0)';
								MAP.bar_elm.style.display = 'block';

								animation_params['scale'] = '1';
								map_animate(animation_params );
								break;

							default: // no animation -> value = "none"
								MAP.bar_elm.style.display = 'block';
								break;
						}

						that.bar_open = true;
						that.dispatchConsentEvents( 'myagileprivacy_bar_shown' );
						that.bar_elm.focus();
						that.optimizeMobile();
					}
					catch( error )
					{
						console.error( error );
					}

				}, 100 );
			});

			document.body.addEventListener( 'click', function( e ) {

				var targetElement = e.target;
				var matchFound = false;
				var levelsToCheck = 2;

				while( levelsToCheck > 0 && targetElement )
				{
					if( targetElement.matches( '.showConsentAgain' ) )
					{
						matchFound = true;
						break;
					}
					targetElement = targetElement.parentElement;
					levelsToCheck--;
				}

				if( matchFound )
				{
					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered showConsentAgain' );
					e.preventDefault();
					e.stopImmediatePropagation();

					if( that.isBarHiddenByOthers() )
					{
						that.markBarHiddenByOthers();
						that.openHiddenBannerHelp( targetElement );
						return;
					}

					that.dispatchConsentEvents( 'myagileprivacy_resurface' );

					var event = new CustomEvent( 'triggerShowAgainDisplay' );
					that.bar_elm.dispatchEvent( event );
				}
			});

			//eof user consent review trigger

		}
		catch( error )
		{
			console.error( error );
		}
	},

	createInlineNotify: function()
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function createInlineNotify' );

			//for preserving scope
			var that = this;

			if( typeof CookieShield !== 'undefined' &&
				CookieShield )
			{
				var detectedKeys = CookieShield.getDetectedKeys();

				Object.keys( detectedKeys ).forEach( function( key ) {

					var api_key = detectedKeys[key];

					var $custom_ref = document.querySelector( '.map_custom_notify.map_api_key_' + api_key );

					if( $custom_ref )
					{
						$custom_ref.style.display = 'block';
						$custom_ref.classList.add( 'mapShowItem' );
					}
				});
			}

			var $map_blocked_content = document.querySelectorAll( '.map_blocked_content' );

			$map_blocked_content.forEach( function( $this) {

				var api_key = $this.getAttribute( 'data-cookie-api-key' );

				var $custom_ref = document.querySelectorAll( '.map_custom_notify.map_api_key_' + api_key );

				$custom_ref.forEach( function( customRef ) {
					if( customRef )
					{
						customRef.style.display = 'block';
						customRef.classList.add( 'mapShowItem' );
					}
				});

			});

			var $show_inline_notify = document.querySelectorAll( '.iframe_src_blocked.map_show_inline_notify:not(._is_activated):not(.map_inline_notify_done)' );

			$show_inline_notify.forEach( function( $this ) {

				var height = $this.offsetHeight;

				var width = $this.offsetWidth;

				var is_absolute = false;

				try {
					is_absolute = ( window.getComputedStyle( $this ).position === 'absolute' );
				}
				catch( e ) {}

				var api_key = $this.getAttribute( 'data-cookie-api-key' );

				var the_friendly_name = '';
				var friendly_name = $this.getAttribute( 'data-friendly_name' );

				if( friendly_name )
				{
					the_friendly_name = friendly_name;
				}

				$this.style.display = 'none';
				$this.classList.add( 'map_inline_notify_done' );

				var kind = $this.classList.contains( 'map_video_block' ) ? 'video'
						 : $this.classList.contains( 'map_maps_block' ) ? 'maps' : '';

				var html;

				if( kind === 'video' )
				{
					//legacy video placeholder, kept for reference:
					//html = "<div class='map_inline_notify map_inline_notify_video showConsentAgain' data-cookie-api-key='"+api_key+"'>"+MAP_SVG_VIDEO+"<span class='map_inline_notify_text'>"+that.settings.blocked_content_text+"</span></div>";

					//dedicated video text available, switch back if needed:
					//var video_text = that.settings.blocked_video_text || that.settings.blocked_content_text;
					var video_text = that.settings.blocked_content_text;

					html = "<div class='map_inline_notify map_inline_notify_video map_video_placeholder showConsentAgain' data-cookie-api-key='"+api_key+"'>"
						+ "<span class='map_video_play' aria-hidden='true'></span>"
						+ "<span class='map_inline_notify_text'>"+video_text+"</span>"
						+ "<span class='map_video_controls' aria-hidden='true'><span class='map_video_controls_play'></span><span class='map_video_controls_bar'><span class='map_video_controls_dot'></span></span><span class='map_video_controls_full'></span></span>"
						+ "</div>";
				}
				else if( kind === 'maps' )
				{
					//legacy maps placeholder, kept for reference:
					//html = "<div class='map_inline_notify map_inline_notify_maps showConsentAgain' data-cookie-api-key='"+api_key+"'>"+MAP_SVG_MAPS+"<span class='map_inline_notify_text'>"+that.settings.blocked_content_text+"</span></div>";

					//dedicated maps text available, switch back if needed:
					//var maps_text = that.settings.blocked_maps_text || that.settings.blocked_content_text;
					var maps_text = that.settings.blocked_content_text;

					html = "<div class='map_inline_notify map_inline_notify_maps map_maps_placeholder showConsentAgain' data-cookie-api-key='"+api_key+"'>"
						+ "<span class='map_maps_pin' aria-hidden='true'></span>"
						+ "<span class='map_inline_notify_text'>"+maps_text+"</span>"
						+ "<span class='map_maps_controls' aria-hidden='true'><span class='map_maps_controls_zoom_in'></span><span class='map_maps_controls_zoom_out'></span></span>"
						+ "</div>";
				}
				else
				{
					html = "<div class='map_inline_notify showConsentAgain' data-cookie-api-key='"+api_key+"'>"+that.settings.blocked_content_text+"<br>"+the_friendly_name+"</div>";
				}

				//temp div for inject via vanilla js
				var tempDiv = document.createElement( 'div' );
				tempDiv.innerHTML = html;

				var $injected = tempDiv.firstChild;
				$this.parentNode.insertBefore( $injected, $this.nextSibling );

				if( is_absolute )
				{
					$injected.style.position = 'absolute';
					$injected.style.top = '0';
					$injected.style.left = '0';
					$injected.style.width = '100%';
					$injected.style.height = '100%';
				}
				else
				{
					//fluid sizing: match the replaced embed, never exceed it,
					//shrink with the container below that size and keep the
					//original proportions via aspect-ratio
					$injected.style.width = '100%';

					if( width > 0 )
					{
						$injected.style.maxWidth = width + 'px';
					}

					if( width > 0 && height > 0 )
					{
						$injected.style.aspectRatio = width + ' / ' + height;
					}
					else if( height > 0 )
					{
						$injected.style.height = height + 'px';
					}
				}

				if( that.settings.inline_notify_color )
				{
					$injected.style.color = that.settings.inline_notify_color;
				}

				if( that.settings.inline_notify_background )
				{
					$injected.style.backgroundColor = that.settings.inline_notify_background;
				}

			});

		}
		catch( error )
		{
			console.error( error );
		}
	},

	setupIabTCF: function()
	{
		//for preserving scope
		var that = this;

		try {

			var $map_consent_extrawrapper = that.settingsModal.querySelector( '.map-consent-extrawrapper' );

			if( !!$map_consent_extrawrapper )
			{
				MAP_SYS.in_iab_context = true;

				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function setupIabTCF' );

				//first layer
				that.bar_elm.querySelectorAll( '.map-triggerGotoIABTCF' ).forEach( function( $this ) {
					$this.addEventListener( 'click', function( e ) {

						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-triggerGotoIABTCF click' );

						e.preventDefault();
						e.stopImmediatePropagation();

						// Kick off full init (vendor-list.json + DOM render) in the background
						// as soon as the user opens the second layer, so it is ready when
						// the IAB tab is clicked. The tab nav listener handles the .then().
						if( typeof window.MAPIABTCF_ensureFullInit === 'function' )
						{
							window.MAPIABTCF_ensureFullInit();
						}

						MAP.openSettingsPopup( $this );

						var mapSettingsMobile = document.querySelector( '.map-settings-mobile' );
						if( mapSettingsMobile && mapSettingsMobile.offsetWidth === 0 && mapSettingsMobile.offsetHeight === 0 )
						{
							var firstNavLink = MAP.settingsModal.querySelector( '.map-nav-link' );
							if( firstNavLink )
							{
								firstNavLink.click();
							}
						}

						$map_consent_extrawrapper.querySelectorAll( '.map-wrappertab-navigation li a[href="#map-privacy-iab-tcf-wrapper"]' ).forEach( function( $_this ) {
							$_this.click();
						});

						if( MAP.isIabPaneWithoutTabs() )
						{
							MAP.showIabPane();
						}

					});
				});

				that.bar_elm.querySelectorAll( '.map-triggerGotoIABTCFVendors' ).forEach( function( $this ) {
					$this.addEventListener( 'click', function( e ) {

						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-triggerGotoIABTCFVendors click' );

						e.preventDefault();
						e.stopImmediatePropagation();

						MAP.openSettingsPopup( $this );

						var mapSettingsMobile = document.querySelector( '.map-settings-mobile' );
						if( mapSettingsMobile && mapSettingsMobile.offsetWidth === 0 && mapSettingsMobile.offsetHeight === 0)
						{
							var firstNavLink = MAP.settingsModal.querySelector( '.map-nav-link' );
							if( firstNavLink )
							{
								firstNavLink.click();
							}
						}

						$map_consent_extrawrapper.querySelectorAll( '.map-wrappertab-navigation li a[href="#map-privacy-iab-tcf-wrapper"]' ).forEach( function( $_this ) {
							$_this.click();
						});

						if( MAP.isIabPaneWithoutTabs() )
						{
							MAP.showIabPane();
						}

						// Ensure full init (vendor DOM) is complete before trying to click
						// the vendor list button and scroll to it
						var _doVendorScroll = function() {
							try {

								var $vendor_list_button = document.querySelector( '#map-iab-tcf-vendor-list' );

								if( $vendor_list_button )
								{
									$vendor_list_button.click();

									setTimeout( function() {
										var $vendor_list_scrollto = document.querySelector( '#map-iab-tcf-vendor-list-scroll-here' );

										if( $vendor_list_scrollto )
										{
											$vendor_list_scrollto.scrollIntoView({ behavior: 'smooth' });
										}
									}, 200 );
								}
							}
							catch( error )
							{
								console.error( error );
							}
						};

						if( typeof window.MAPIABTCF_ensureFullInit === 'function' )
						{
							window.MAPIABTCF_ensureFullInit().then( function() {
								MAP.mapSyncAllSliders();
								setTimeout( _doVendorScroll, 200 );
							});
						}
						else
						{
							setTimeout( _doVendorScroll, 200 );
						}

					});
				});

				//bof nav part
				$map_consent_extrawrapper.querySelectorAll( '.map-wrappertab-navigation li a' ).forEach( function( $this ) {
					$this.addEventListener( 'click', function( e ) {

						e.preventDefault();

						var tabHref = $this.getAttribute( 'href' );
						var lastIndex = tabHref.lastIndexOf('#');
						if( lastIndex !== -1 )
						{
							tabHref = tabHref.substring( lastIndex )?.replace( '#', '' );
						}
						else
						{
							tabHref = 'map-privacy-iab-tcf-wrapper';
						}

						if( tabHref == 'map-privacy-iab-tcf-wrapper')
						{
							MAP.showIabPane();
						}
						else
						{
							(async() => {

								while( !MAP_SYS.map_document_load )
								{
									//MapLogger.log( 'not defined yet' );
									await new Promise( resolve => setTimeout( resolve, 10 ) );
								}

								if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
								{
									window.MAPIABTCF_hideCMPUI();
								}

							})();

						}

						$map_consent_extrawrapper.querySelectorAll( '.map-wrappertab-navigation li a' ).forEach( function( $_this ) {
							$_this.classList.remove( 'active-wrappertab-nav' );
						});

						$this.classList.add( 'active-wrappertab-nav' );

						$map_consent_extrawrapper.querySelectorAll( '.map-wrappertab' ).forEach( function( $_this ) {
							$_this.classList.remove( 'map-wrappertab-active' );
						});

						var activeTab = $map_consent_extrawrapper.querySelector( '.map-wrappertab.' + tabHref );
						if( activeTab )
						{
							activeTab.classList.add( 'map-wrappertab-active' );
						}

					});
				});

				//eof nav part

				//bof IAB TCF init and events
				(async() => {

					while( !MAP_SYS.map_document_load )
					{
						//MapLogger.log( 'not defined yet' );
						await new Promise( resolve => setTimeout( resolve, 10 ) );
					}

					if( typeof window.initMyAgilePrivacyIabTCF === 'function' )
					{
						var this_lang_code = null;

						if( typeof map_lang_code !== 'undefined' )
						{
							this_lang_code = map_lang_code;
						}

						if( typeof map_full_config?.map_lang_code !== 'undefined' )
						{
							this_lang_code = map_full_config?.map_lang_code;
						}

						window.initMyAgilePrivacyIabTCF( this_lang_code );
					}

				})();
				//eof IAB TCF init and events
			}

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//returns vendor list
	returnVendorsList: function()
	{
		//for preserving scope
		var that = this;

		try {

			var standard_vendors = [];
			var other_iab_and_google_vendors = [];
			var vendors_all = [];

			for ( const [key, value] of Object.entries( map_full_config?.cookie_api_key_remote_id_map_active || {} ) )
			{
				let item = {
					name: key,
					iabId: null
				}

				standard_vendors.push( item );
			}

			if( typeof window.MAPIABTCF_getVendorsList === 'function' )
			{
				other_iab_and_google_vendors = window.MAPIABTCF_getVendorsList();
			}

			vendors_all = [ ...standard_vendors, ...other_iab_and_google_vendors ];

			return vendors_all;
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//updating SYS_MAP some positive consent status
	updateSomeConsentGivenStatus: function()
	{
		//for preserving scope
		var that = this;

		try {

			let acceptedAll_status = MAP_Cookie.read( MAP_ACCEPTED_ALL_COOKIE_NAME );
			const atLeastSomeCModeV2ConsentGranted = Object.values( MAP_SYS?.current_gconsent ).some( value => value === 'granted' );

			if( acceptedAll_status == '1' )
			{
				MAP_SYS.some_positive_consent_given = true;
			}
			else if( atLeastSomeCModeV2ConsentGranted )
			{
				MAP_SYS.some_positive_consent_given = true;
			}
			else
			{
				let counter = 0;

				Object.entries( map_full_config?.cookie_api_key_remote_id_map_active || {} ).forEach( ([key, value] ) => {

					if( MAP_Cookie.read( value ) == '1')
					{
						counter++;
					}
				});

				if( typeof window.MAPIABTCF_getSomePositiveConsentStatus === 'function' )
				{
					if( window.MAPIABTCF_getSomePositiveConsentStatus() )
					{
						counter++;
					}
				}

				if( counter == 0 )
				{
					MAP_SYS.some_positive_consent_given = false;
				}
				else
				{
					MAP_SYS.some_positive_consent_given = true;
				}
			}
		}
		catch( error )
		{
			console.error( error );
		}

	},

	attachEvents: function()
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function attachEvents' );

			//for preserving scope
			var that = this;

			//click over pseudo-label
			that.settingsModal.addEventListener( 'click', function( e ) {

				if( e.target.matches( '.map-slider' ) )
				{
					// Find the class that starts with "map-for-"
					let classList = [...e.target.classList];
					let mapForClass = classList.find( cls => cls.startsWith( 'map-for-' ) );
					if( !mapForClass ) return;

					let for_attribute = mapForClass.replace( 'map-for-', '' );
					if( !for_attribute ) return;

					const final_target = document.getElementById( for_attribute );

					if( final_target )
					{
						// Toggle value for hidden input and sync slider appearance
						final_target.value = ( final_target.value === '1' ) ? '0' : '1';
						MAP.mapSyncSlider( final_target );

						//propagate event
						//propagate event via custom event (hidden inputs don't fire native click)
						final_target.dispatchEvent( new CustomEvent( 'map:toggle', { bubbles: true } ) );
					}
				}
			});

			that.accept_button.addEventListener( 'click', function( e ) {
				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-accept-button click' );

				e.preventDefault();
				e.stopImmediatePropagation();

				var $this = this;

				that.updateLastConsentRecords();

				that.dispatchConsentEvents( 'myagileprivacy_cookie_accept' );

				var googleConsent = {};

				//check consent-mode checkbox
				that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox' ).forEach( function( elem ) {
					elem.value = '1';
					MAP.mapSyncSlider( elem );

					var consent_key = elem.getAttribute( 'data-consent-key' );

					if( elem.classList.contains( 'map-consent-clarity' ) )
					{
						that.updateClarityConsent( consent_key, 'granted', true );
					}

					if( elem.classList.contains( 'map-consent-microsoft' ) )
					{
						that.updateMicrosoftConsent( consent_key, 'granted', true );
					}

					if( elem.classList.contains( 'map-consent-google' ) )
					{
						googleConsent[consent_key] = 'granted';
					}

				});

				// without switches in the panel the services of each Consent Mode decide
				if( !Object.keys( googleConsent ).length ) googleConsent = that.consentWithoutSwitches( 'google', 'accept' ) || {};
				that.applyConsentWithoutSwitches( 'accept', null, false, [ 'clarity', 'microsoft' ] );

				// one update for all the keys
				that.updateGoogleConsentbyKeyValues( googleConsent, true );

				//check user-preference checkbox
				that.settingsModal.querySelectorAll( '.map-user-preference-checkbox' ).forEach( function( elem ) {

					var cookieName = 'map_cookie_' + elem.getAttribute( 'data-cookie-baseindex' ) + MAP_POSTFIX;

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting 1 to cookieName=' + cookieName );

					MAP_Cookie.set( cookieName, '1', MAP_SYS.map_cookie_expire );

					elem.value = '1';
					MAP.mapSyncSlider( elem );
				});

				//check iab-preference checkbox
				that.settingsModal.querySelectorAll( '.map-user-iab-preference-checkbox' ).forEach( function( elem ) {

					elem.classList.remove( 'map-flags' ); // a choice on everything is never partial
					elem.value = '1';
					MAP.mapSyncSlider( elem );
				});

				(async() => {

					while( !MAP_SYS.map_document_load )
					{
						//MapLogger.log( 'not defined yet' );
						await new Promise( resolve => setTimeout( resolve, 10 ) );
					}

					if( typeof window.MAPIABTCF_acceptAllConsent === 'function' )
					{
						window.MAPIABTCF_doSetUserInteraction();
						window.MAPIABTCF_acceptAllConsent( true );

						that.updateSomeConsentGivenStatus();
					}

				})();

				MAP.accept_close();


			});


			that.reject_button.forEach( function( button ) {
				button.addEventListener( 'click', function( e ) {

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-reject-button click' );

					e.preventDefault();
					e.stopImmediatePropagation();

					var $this = this;

					that.updateLastConsentRecords();

					that.dispatchConsentEvents( 'myagileprivacy_cookie_reject' );

					var googleConsent = {};

					//uncheck consent-mode checkbox
					that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox' ).forEach( function( elem ) {

						elem.value = '0';
						MAP.mapSyncSlider( elem );

						var consent_key = elem.getAttribute( 'data-consent-key' );

						if( elem.classList.contains( 'map-consent-clarity' ) )
						{
							that.updateClarityConsent( consent_key, 'denied', true );
						}

						if( elem.classList.contains( 'map-consent-microsoft' ) )
						{
							that.updateMicrosoftConsent( consent_key, 'denied', true );
						}

						if( elem.classList.contains( 'map-consent-google' ) )
						{
							googleConsent[consent_key] = 'denied';
						}

					});

					// without switches in the panel the refusal covers every signal of each Consent Mode
					if( !Object.keys( googleConsent ).length ) googleConsent = that.consentWithoutSwitches( 'google', 'reject' ) || {};
					that.applyConsentWithoutSwitches( 'reject', null, false, [ 'clarity', 'microsoft' ] );

					// one update for all the keys
					that.updateGoogleConsentbyKeyValues( googleConsent, true );

					//uncheck user-preference checkbox
					that.settingsModal.querySelectorAll( '.map-user-preference-checkbox' ).forEach( function( elem ) {

						var cookieName = 'map_cookie_' + elem.getAttribute( 'data-cookie-baseindex' ) + MAP_POSTFIX;

						MAP_Cookie.set( cookieName, '-1', MAP_SYS.map_cookie_expire );

						elem.value = '0';
						MAP.mapSyncSlider( elem );
					});

					//uncheck iab-preference checkbox
					that.settingsModal.querySelectorAll( '.map-user-iab-preference-checkbox' ).forEach( function( elem ) {

						elem.classList.remove( 'map-flags' ); // a choice on everything is never partial
						elem.value = '0';
						MAP.mapSyncSlider( elem );
					});

					(async() => {

						while( !MAP_SYS.map_document_load )
						{
							//MapLogger.log( 'not defined yet' );
							await new Promise( resolve => setTimeout( resolve, 10 ) );
						}

						if( typeof window.MAPIABTCF_denyAllConsent === 'function' )
						{
							window.MAPIABTCF_doSetUserInteraction();
							window.MAPIABTCF_denyAllConsent( true );

							that.updateSomeConsentGivenStatus();
						}

					})();

					MAP.reject_close();
				});
			});


			that.customize_button.addEventListener( 'click', function( e ) {

				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-customize-button click' );

				e.preventDefault();
				e.stopImmediatePropagation();

				MAP.openSettingsPopup( that.customize_button );

				var mapSettingsMobile = document.querySelector( '.map-settings-mobile' );

				if( mapSettingsMobile && mapSettingsMobile.offsetWidth === 0 && mapSettingsMobile.offsetHeight === 0 )
				{
					var firstNavLink = MAP.settingsModal.querySelector( '.map-nav-link' );
					if( firstNavLink )
					{
						firstNavLink.click();
					}
				}

				var $map_consent_extrawrapper = that.settingsModal.querySelectorAll( '.map-consent-extrawrapper' );

				if( !!$map_consent_extrawrapper && $map_consent_extrawrapper?.length )
				{
					var triggerElement = $map_consent_extrawrapper[0].querySelector( '.map-wrappertab-navigation li a[href="#map-privacy-cookie-thirdypart-wrapper"]' );
					if( triggerElement )
					{
						triggerElement.click();
					}
				}

				if( MAP.isIabPaneWithoutTabs() )
				{
					MAP.showIabPane();
				}

				that.dispatchConsentEvents( 'myagileprivacy_cookie_customize' );

			});

			document.querySelector( '#mapModalClose' ).addEventListener( 'click', function( e ) {

				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered mapModalClose click' );

				e.preventDefault();
				e.stopImmediatePropagation();

				MAP.closeSettingsPopup( 'close' );
				MAP.hideBar();

			});

			that.setupAccordion();

			if( typeof map_ajax !== 'undefined' &&
				typeof map_ajax.cookie_process_delayed_mode !== 'undefined' &&
				map_ajax.cookie_process_delayed_mode == 1
				)
			{
				//wait for page load

				(async() => {

					while( !MAP_SYS.map_document_load )
					{
						//MapLogger.log( 'not defined yet' );
						await new Promise( resolve => setTimeout( resolve, 10 ) );
					}

					that.userPreferenceInit( false );

				})();

			}
			else
			{
				that.userPreferenceInit( false );
			}

		}
		catch( error )
		{
			console.error( error );
		}
	},

	usabilityEvents: function()
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function usabilityEvents' );

			//for preserving scope
			var that = this;

			//usability fix - clickable elements with keydown
			const usability_fix_items = [ that.accept_button,
				that.reject_button,
				that.customize_button,
				that.show_consent_elems,
				document.querySelectorAll( '.map-triggerGotoIABTCF' )
			];

			usability_fix_items.forEach( item => {
				if( !item ) return;

				// NodeList/HTMLCollection
				if( typeof item.forEach === 'function' )
				{
					item.forEach( el => {
						el.addEventListener( 'keydown', function( e ) {
							if( e.key === 'Enter' || e.key === ' ' )
							{
								e.preventDefault();
								this.click();
							}
						});
					});
				}

				//single element
				else if( item.addEventListener )
				{
					item.addEventListener( 'keydown', function( e ) {
						if( e.key === 'Enter' || e.key === ' ' )
						{
							e.preventDefault();
							this.click();
						}
					});
				}
			});

			that.settingsModal.addEventListener( 'keydown', function( e ) {

				if( e.target.matches( '.map_expandItem' ) ||
					e.target.matches( '.map-tab-navigation' ) ||
					e.target.matches( '.map-privacy-iab-button' )
				)
				{
					if( e.key === ' ' || e.key === 'Spacebar' || e.key === 'Enter' )
					{
						e.preventDefault();
						e.target.click();
					}
				}

				//usability fix - checkbox
				if( e.target.matches( '[role="checkbox"]' ) )
				{
					if( e.key === ' ' || e.key === 'Spacebar' || e.key === 'Enter' )
					{
						e.preventDefault();

						// Find the class that starts with "map-for-"
						let classList = [...e.target.classList];
						let mapForClass = classList.find( cls => cls.startsWith( 'map-for-' ) );
						if( !mapForClass ) return;

						let for_attribute = mapForClass.replace( 'map-for-', '' );
						if( !for_attribute ) return;

						const final_target = document.getElementById( for_attribute );

						if( final_target )
						{
							// Toggle value for hidden input and sync slider appearance
							final_target.value = ( final_target.value === '1' ) ? '0' : '1';
							MAP.mapSyncSlider( final_target );

							//propagate event via custom event (hidden inputs don't fire native click)
							final_target.dispatchEvent( new CustomEvent( 'map:toggle', { bubbles: true } ) );
						}
					}
				}
			});

		}
		catch( error )
		{
			console.error( error );
		}
	},

	checkBlockedContent: function()
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function checkBlockedContent' );

			//for preserving scope
			var that = this;

			var $map_blocked_content = document.querySelectorAll( '.map_blocked_content:not(._is_activated):not(._map_jq_wait)' );

			if( !!$map_blocked_content && $map_blocked_content?.length )
			{
				//calc data
				var blocked_friendly_name = [];

				$map_blocked_content.forEach( function( $this ) {

					var this_friendly_name = $this.getAttribute( 'data-friendly_name' );

					if( this_friendly_name )
					{
						blocked_friendly_name.push( this_friendly_name );
					}

				});

				if( typeof CookieShield !== 'undefined' &&
					CookieShield &&
					typeof CookieShield.getDetectedFriendlyNames !== 'undefined' )
				{
					var other_names = CookieShield.getDetectedFriendlyNames();

					other_names.forEach( function( v ) {

						if( v )
						{
							if( v?.desc )
							{
								blocked_friendly_name.push( v.desc );
							}
							else
							{
								blocked_friendly_name.push( v );
							}

						}
					});
				}

				var blocked_friendly_name_unique = blocked_friendly_name.filter((v, i, a) => a.indexOf(v) === i);

				that.blocked_friendly_name_string = blocked_friendly_name_unique.join( ', ' );

				//check if to show
				if( blocked_friendly_name_unique.length &&
					( !that.bar_open || that.settings.show_ntf_bar_on_not_yet_consent_choice ) )
				{
					if( that.blocked_content_notification && that.blocked_content_notification )
					{
						if( that.blocked_friendly_name_string )
						{
							that.map_blocked_elems_desc.innerHTML = that.blocked_friendly_name_string;
						}
						else
						{
							that.map_blocked_elems_desc.style.opacity = 0;
							that.map_blocked_elems_desc.style.display = 'block';

							map_animate({
								targets: that.map_blocked_elems_desc,
								easing: 'easeInOutQuad',
								duration: 150,
								opacity: 1,
								complete: function(anim) {
								}
							} );

						}

						that.blocked_content_notification_shown = true;

						var blocked_content_notify_auto_shutdown_time = 3000;

						if( that.settings.blocked_content_notify_auto_shutdown_time )
						{
							blocked_content_notify_auto_shutdown_time = that.settings.blocked_content_notify_auto_shutdown_time;
						}

						setTimeout( function() {

							try {

								that.blocked_content_notification.style.opacity = 0;
								that.blocked_content_notification.style.display = 'block';

								map_animate({
									targets: that.blocked_content_notification,
									easing: 'easeInOutQuad',
									duration: 150,
									opacity: 1,
									complete: function(anim) {
									}
								} );

								if( that.blocked_content_notification.classList.contains( 'autoShutDown' ) )
								{
									setTimeout( function() {


										setTimeout( function() {
											that.blocked_content_notification.style.display = 'none';
										}, 150 );

										map_animate({
											targets: that.blocked_content_notification,
											easing: 'easeInOutQuad',
											duration: 150,
											opacity: 0,
											complete: function(anim) {
												that.blocked_content_notification.style.display = 'none';
											}
										} );

									}, blocked_content_notify_auto_shutdown_time );
								}
							}
							catch( error )
							{
								console.error( error );
							}

						}, 1000 );
					}
				}
			}
			else
			{
				if( that.blocked_content_notification )
				{

					setTimeout( function() {
						that.blocked_content_notification.style.display = 'none';
					}, 150 );

					map_animate({
						targets: that.blocked_content_notification,
						easing: 'easeInOutQuad',
						duration: 150,
						opacity: 0,
						complete: function(anim) {
							that.blocked_content_notification.style.display = 'none';
						}
					} );
				}
			}

			this.administratorNotices();

		}
		catch( error )
		{
			console.error( error );
		}
	},

	displayBar: function()
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function displayBar' );

			//for preserving scope
			var that = this;

			if( that.isBarHiddenByOthers() )
			{
				that.markBarHiddenByOthers();
				return;
			}

			that.bar_blocked = false;

			//animation
			var that_animation = that.bar_elm.getAttribute( 'data-animation' );

			var that_id_selector = '#' + that.bar_elm.getAttribute( 'id' );

			var animation_params = {
				targets: that_id_selector,
				easing: 'easeInOutQuad',
				duration: 1000
			};

			var y_value = '0vh';
			var x_value = '0vw';

			if( that.bar_elm.classList.contains( 'map_floating_banner' ) )
			{
				y_value = '3vh';
				x_value = '3vw';
			}

			var element = document.querySelector( that_id_selector );

			switch( that_animation )
			{
				case 'slide':

					if( element.className.includes( 'mapPositionBottom' ) )
					{
						element.style.bottom = '-100vh';
						animation_params['bottom'] = y_value;
					}
					else if( element.className.includes( 'mapPositionTop' ) )
					{
						element.style.top = '-100vh';
						animation_params['top'] = y_value;
					}
					else if( element.className.includes( 'mapPositionCenterLeft' ) )
					{
						element.style.left = '-100vw';
						animation_params['left'] = x_value;
					}
					else if( element.className.includes( 'mapPositionCenterCenter' ) )
					{
						element.style.top = '-100%';
						animation_params['top'] = '50%';
					}
					else if( element.className.includes( 'mapPositionCenterRight' ) )
					{
						element.style.right = '-100vw';
						animation_params['right'] = x_value;
					}

					element.style.display = 'block';

					map_animate(animation_params );
					break;

				case 'fade':
					element.style.opacity = '0';

					element.style.display = 'block';

					animation_params['opacity'] = '1';
					animation_params['duration'] = '500';

					map_animate(animation_params );

					break;

				case 'zoom':
					element.style.transform = 'scale(0)';

					element.style.display = 'block';

					animation_params['scale'] = '1';
					map_animate(animation_params );

					break;

				default: // no animation -> value = "none"
					element.style.display = 'block';

					break;
			}

			setTimeout( function() {
				if( !that.bar_blocked ) that.showagain_elm.style.display = 'none';
			}, 150 );

			map_animate({
				targets: that.showagain_elm,
				easing: 'easeInOutQuad',
				duration: 150,
				opacity: 0,
				complete: function( anim ) {
					if( !that.bar_blocked ) that.showagain_elm.style.display = 'none';
				}
			} );

			that.bar_open = true;
			that.dispatchConsentEvents( 'myagileprivacy_bar_shown' );

			//no scroll while the stylesheet is not applied yet
			that.bar_elm.focus( window.getComputedStyle( that.bar_elm ).position !== 'fixed' ? { preventScroll: true } : undefined );

			that.optimizeMobile();

		}
		catch( error )
		{
			console.error( error );
		}
	},

	isBarRendered: function()
	{
		var content = this.bar_elm.querySelector( '#my-agile-privacy-notification-content' );

		return this.bar_elm.getClientRects().length > 0 &&
			( !content || content.getClientRects().length > 0 );
	},

	//probes the bar synchronously: no frame is painted in between
	isBarHiddenByOthers: function()
	{
		try {

			if( this.bar_open ) return !this.isBarRendered();

			var previous = this.bar_elm.style.display;

			this.bar_elm.style.display = 'block';

			var hidden = !this.isBarRendered();

			this.bar_elm.style.display = previous;

			return hidden;
		}
		catch( error )
		{
			console.error( error );
			return false;
		}
	},

	markBarHiddenByOthers: function()
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function markBarHiddenByOthers' );

			if( this.bar_blocked ) return;

			var was_open = this.bar_open;

			this.bar_blocked = true;
			this.bar_open = false;

			map_animate_remove(this.bar_elm );
			map_animate_remove(this.showagain_elm );

			this.bar_elm.style.display = 'none';

			var this_showagain_tab = Boolean( this.settings.showagain_tab );

			if( !this_showagain_tab &&
				!document.querySelector( '.showConsentAgain:not(.map_custom_notify):not(.map_inline_notify)' ) )
			{
				this_showagain_tab = true;
			}

			if( this_showagain_tab )
			{
				this.showagain_elm.style.opacity = 1;
				this.showagain_elm.style.display = 'block';
			}

			if( was_open )
			{
				this.dispatchConsentEvents( 'myagileprivacy_bar_hidden' );
			}
		}
		catch( error )
		{
			console.error( error );
		}
	},

	openHiddenBannerHelp: function( trigger )
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function openHiddenBannerHelp' );

			//for preserving scope
			var that = this;

			var help = document.getElementById( 'map-hidden-banner-help' );

			if( !help ) return false;

			var dialog = help.querySelector( '.map-hidden-banner-help-dialog' );

			if( help.getAttribute( 'data-map-bound' ) !== '1' )
			{
				help.setAttribute( 'data-map-bound', '1' );

				help.querySelectorAll( '.map-hidden-banner-help-close, .map-hidden-banner-help-backdrop' ).forEach( function( $this ) {
					$this.addEventListener( 'click', function( e ) {
						e.preventDefault();
						that.closeHiddenBannerHelp();
					});
				});

				help.addEventListener( 'keydown', function( e ) {

					if( e.key === 'Escape' || e.key === 'Esc' )
					{
						e.preventDefault();
						that.closeHiddenBannerHelp();
						return;
					}

					if( e.key !== 'Tab' ) return;

					var buttons = dialog.querySelectorAll( 'button' );

					if( !buttons.length ) return;

					var first = buttons[0];
					var last = buttons[ buttons.length - 1 ];

					if( e.shiftKey && ( document.activeElement === first || document.activeElement === dialog ) )
					{
						e.preventDefault();
						last.focus();
					}
					else if( !e.shiftKey && document.activeElement === last )
					{
						e.preventDefault();
						first.focus();
					}
				});
			}

			that.hidden_help_return = trigger || document.activeElement;

			help.classList.add( 'map-show' );

			dialog.focus();

			return true;
		}
		catch( error )
		{
			console.error( error );
			return false;
		}
	},

	closeHiddenBannerHelp: function()
	{
		try {

			var help = document.getElementById( 'map-hidden-banner-help' );

			if( help ) help.classList.remove( 'map-show' );

			if( this.hidden_help_return && typeof this.hidden_help_return.focus === 'function' )
			{
				this.hidden_help_return.focus();
			}

			this.hidden_help_return = null;
		}
		catch( error )
		{
			console.error( error );
		}
	},

	hideBar: function()
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function hideBar' );

			//for preserving scope
			var that = this;

			var this_showagain_tab = Boolean( this.settings.showagain_tab );

			if( !this_showagain_tab &&
				!document.querySelector( '.showConsentAgain:not(.map_custom_notify):not(.map_inline_notify)' ) )
			{
				this_showagain_tab = true;
			}

			if( this_showagain_tab )
			{
				//show
				that.showagain_elm.style.opacity = 0;
				that.showagain_elm.style.display = 'block';

				map_animate({
					targets: this.showagain_elm,
					easing: 'easeInOutQuad',
					duration: 150,
					opacity: 1,
					complete: function(anim) {
					}
				} );
			}
			else
			{
				setTimeout( function() {
					that.showagain_elm.style.display = 'none';
				}, 150 );

				//hide
				map_animate({
					targets: this.showagain_elm,
					easing: 'easeInOutQuad',
					duration: 150,
					opacity: 0,
					complete: function(anim) {
						that.showagain_elm.style.display = 'none';
					}
				});
			}

			//hide
			//animation
			var that_animation = this.bar_elm.getAttribute( 'data-animation' );

			var that_id_selector = '#' + this.bar_elm.getAttribute( 'id' );

			setTimeout( function() {
				MAP.bar_elm.style.display = 'none';
			}, 150 );

			var animation_params = {
				targets: that_id_selector,
				easing: 'easeInOutQuad',
				duration: 700,
				complete: function( anim ) {
					MAP.bar_elm.style.display = 'none';
				}
			};

			switch( that_animation )
			{
				case 'slide':

					if( this.bar_elm.className.includes( 'mapPositionBottom' ) )
					{
						animation_params['bottom'] = '-100vh';
					}
					else if( this.bar_elm.className.includes( 'mapPositionTop' ) )
					{
						animation_params['top'] = '-100vh';
					}
					else if( this.bar_elm.className.includes( 'mapPositionCenterLeft' ) )
					{
						animation_params['left'] = '-100vw';
					}
					else if( this.bar_elm.className.includes( 'mapPositionCenterCenter' ) )
					{
						animation_params['top'] = '-100%';
					}
					else if( this.bar_elm.className.includes( 'mapPositionCenterRight' ) )
					{
						animation_params['right'] = '-100vw';
					}

					map_animate(animation_params );
					break;

				case 'fade':

					animation_params['opacity'] = '0';
					animation_params['duration'] = '500';

					map_animate(animation_params );

					break;

				case 'zoom':

					animation_params['scale'] = '0';
					map_animate(animation_params );


					break;

				default: // no animation -> value = "none"
					this.bar_elm.style.display = 'none';

					break;
			}

			// a bar that was never displayed is not being hidden by anyone
			var was_open = this.bar_open;

			this.bar_open = false;

			if( was_open )
			{
				this.dispatchConsentEvents( 'myagileprivacy_bar_hidden' );
			}

			(async() => {

				while( !MAP_SYS.map_document_load )
				{
					//MapLogger.log( 'not defined yet' );
					await new Promise( resolve => setTimeout( resolve, 10 ) );
				}

				if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
				{
					window.MAPIABTCF_hideCMPUI();
				}

			})();

		}
		catch( error )
		{
			console.error( error );
		}
	},


	optimizeMobile: function()
	{
		//for preserving scope
		var that = this;
		var _optimize = function( relaunch ) {

			try
			{
				if( that.bar_open )
				{
					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'optimizing for mobile view' );
					if( that.isNarrowViewport() )
					{
						that.map_notification_message.classList.add( 'extraNarrow' );

						//map_flex and extraNarrow are tied attributes
						if( !that.map_notification_message.classList.contains( 'map-iab-context' ) )
						{
							that.map_notification_message.classList.remove( 'map_flex' );
						}
					}
					else
					{
						that.map_notification_message.classList.remove( 'extraNarrow' );

						if( !that.map_notification_message.classList.contains( 'map-iab-context' ) )
						{
							that.map_notification_message.classList.add( 'map_flex' );
						}
					}

					that.layoutConsentButtons();
					that.capNotificationMessage();
				}
				that.setOverflowMaxHeight();

				//second-layer image fix (wprocket)
				document.querySelectorAll( '.map-modal-body img' ).forEach( function( img ) {
					img.style.width = "unset!important";
					img.style.height = "unset!important";
				});

				if( relaunch )
				{
					setTimeout( function() { _optimize( false ); }, 500 );
				}
			}
			catch( error )
			{
				console.error( error );
			}
		};
		setTimeout( function() { _optimize( true ); }, 1 );
	},

	//three, two or one consent buttons per row on narrow screens, all of the same size
	layoutConsentButtons: function()
	{
		//for preserving scope
		var that = this;

		try
		{
			var container = that.bar_elm ? that.bar_elm.querySelector( '.map_notification_container' ) : null;

			if( !container || container.clientWidth == 0 ) return;

			var buttons = [];

			for( var i = 0; i < container.children.length; i++ )
			{
				if( container.children[i].tagName == 'A' ) buttons.push( container.children[i] );
			}

			container.classList.remove( 'map-buttons-two-rows', 'map-buttons-stacked' );

			if( !that.isNarrowViewport() || buttons.length != 3 ) return;

			var container_style = window.getComputedStyle( container );
			var gap = parseFloat( container_style.columnGap ) || 0;
			var available = container.clientWidth - ( parseFloat( container_style.paddingLeft ) || 0 ) - ( parseFloat( container_style.paddingRight ) || 0 );
			var widest = 0;

			buttons.forEach( function( button ) {
				button.style.setProperty( 'white-space', 'nowrap', 'important' );
				button.style.setProperty( 'width', 'max-content', 'important' );
				widest = Math.max( widest, button.offsetWidth );
				button.style.removeProperty( 'white-space' );
				button.style.removeProperty( 'width' );
			});

			if( widest > ( available - gap ) / 2 + 0.5 )
			{
				container.classList.add( 'map-buttons-stacked' );
			}
			else if( widest > ( available - 2 * gap ) / 3 + 0.5 )
			{
				container.classList.add( 'map-buttons-two-rows' );
			}

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'consent buttons layout: available=' + available + ', widest=' + widest + ', classes=' + container.className );
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//same threshold as the stylesheet
	isNarrowViewport: function()
	{
		return window.matchMedia ? window.matchMedia( '(max-width: 450px)' ).matches : ( window.innerWidth <= 450 );
	},

	//same threshold as the stylesheet
	isShortViewport: function()
	{
		return window.matchMedia ? window.matchMedia( '(max-height: 500px)' ).matches : ( window.innerHeight <= 500 );
	},

	//the banner fits the screen: on narrow or short screens the text shows four whole lines at most and scrolls, the policy links stay visible
	capNotificationMessage: function()
	{
		//for preserving scope
		var that = this;

		try
		{
			var message = that.map_notification_message;

			if( !message || !that.bar_elm ) return;

			var is_compact = that.isNarrowViewport() || that.isShortViewport();
			var links = that.bar_elm.querySelector( '.map-modal-cookie-policy-link' );

			if( links && is_compact && links.parentNode === message ) message.parentNode.insertBefore( links, message.nextSibling );
			if( links && !is_compact && links.parentNode !== message ) message.appendChild( links );

			var previous_scroll = message.scrollTop;

			that.bar_elm.style.removeProperty( 'max-height' );
			that.bar_elm.style.removeProperty( 'overflow-y' );
			message.style.removeProperty( 'max-height' );

			var message_style = window.getComputedStyle( message );
			var line = parseFloat( message_style.lineHeight );

			if( !( line > 0 ) ) line = ( parseFloat( message_style.fontSize ) || 15 ) * 1.4;

			var viewport_height = window.innerHeight;

			//a floating banner keeps 3vh from the edge
			var limit = Math.floor( viewport_height * ( that.bar_elm.classList.contains( 'map_floating_banner' ) ? 0.94 : 1 ) );
			var room = limit - ( that.bar_elm.offsetHeight - message.offsetHeight );
			var cap = null;

			if( is_compact )
			{
				cap = Math.min( 4 * line, viewport_height * 0.3, room );
			}
			else if( message.scrollHeight > room )
			{
				cap = room;
			}

			if( cap === null )
			{
				message.classList.remove( 'map-scroll' );
			}
			else
			{
				cap = Math.max( line, Math.floor( cap / line + 0.01 ) * line );
				message.style.setProperty( 'max-height', cap + 'px', 'important' );
				message.classList.add( 'map-scroll' );
			}

			if( that.message_cap !== cap )
			{
				message.scrollTop = 0;
				that.message_cap = cap;
			}
			else
			{
				message.scrollTop = previous_scroll;
			}

			//last resort: the whole banner scrolls rather than leave the screen
			if( that.bar_elm.offsetHeight > limit )
			{
				that.bar_elm.style.setProperty( 'max-height', limit + 'px', 'important' );
				that.bar_elm.style.setProperty( 'overflow-y', 'auto', 'important' );
			}

			that.updateMessageScrollState();

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'notification message cap: line=' + line + ', cap=' + cap + ', content=' + message.scrollHeight );
		}
		catch( error )
		{
			console.error( error );
		}
	},

	//keyboard access and the fade follow the text that is still hidden
	updateMessageScrollState: function()
	{
		var message = this.map_notification_message;

		if( !message ) return;

		if( !message.classList.contains( 'map-scroll' ) )
		{
			if( document.activeElement === message ) this.bar_elm.focus();

			message.removeAttribute( 'tabindex' );
			message.classList.remove( 'map-more' );

			return;
		}

		var scrolls = message.scrollHeight > message.clientHeight + 1;

		if( scrolls )
		{
			message.setAttribute( 'tabindex', '0' );
		}
		else
		{
			if( document.activeElement === message ) this.bar_elm.focus();

			message.removeAttribute( 'tabindex' );
		}

		message.classList.toggle( 'map-more', scrolls && message.scrollTop + message.clientHeight < message.scrollHeight - 2 );
	},

	accept_close: function()
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function accept_close' );

			MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );

			if( typeof window.CookieShield?.resetAcceptedAllCache === "function" )
			{
				window.CookieShield.resetAcceptedAllCache();
			}

			that.updateSomeConsentGivenStatus();

			//animation
			var that_animation = this.bar_elm.getAttribute( 'data-animation' );

			var that_id_selector = '#' + this.bar_elm.getAttribute( 'id' );

			var animation_params = {
				targets: that_id_selector,
				easing: 'easeInOutQuad',
				duration: 700,
				complete: function( anim ) {
					MAP.bar_elm.style.display = 'none';

				}
			};

			switch( that_animation )
			{
				case 'slide':
					if( this.bar_elm.className.includes( 'mapPositionBottom' ) ) {
						animation_params['bottom'] = '-100vh';
					}
					else if( this.bar_elm.className.includes( 'mapPositionTop' ) ) {
						animation_params['top'] = '-100vh';
					}
					else if( this.bar_elm.className.includes( 'mapPositionCenterLeft' ) ) {
						animation_params['left'] = '-100vw';
					}
					else if( this.bar_elm.className.includes( 'mapPositionCenterCenter' ) ) {
						animation_params['top'] = '-100%';
					}
					else if( this.bar_elm.className.includes( 'mapPositionCenterRight' ) ) {
						animation_params['right'] = '-100vw';
					}

					map_animate(animation_params );
				break;

				case 'fade':

					animation_params['opacity'] = '0';
					animation_params['duration'] = '500';

					map_animate(animation_params );

				break;

				case 'zoom':

					animation_params['scale'] = '0';
					map_animate(animation_params );

				break;

				default: // no animation -> value = "none"
					this.bar_elm.style.display = 'none';
				break;
			}

			that.bar_open = false;
			that.dispatchConsentEvents( 'myagileprivacy_bar_hidden' );

			var this_showagain_tab = Boolean( that.settings.showagain_tab );

			if( !this_showagain_tab &&
				!document.querySelector( '.showConsentAgain:not(.map_custom_notify):not(.map_inline_notify)' ) )
			{
				this_showagain_tab = true;
			}

			if( this_showagain_tab )
			{
				that.showagain_elm.style.opacity = 0;
				that.showagain_elm.style.display = 'block';

				map_animate({
					targets: that.showagain_elm,
					easing: 'easeInOutQuad',
					duration: 150,
					opacity: 1,
					complete: function(anim) {
					}
				} );
			}

			that.tryToUnblockScripts( true, null );

			(async() => {

				while( !MAP_SYS.map_document_load )
				{
					//MapLogger.log( 'not defined yet' );
					await new Promise( resolve => setTimeout( resolve, 10 ) );
				}

				if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
				{
					window.MAPIABTCF_hideCMPUI();
				}

			})();

			return false;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	reject_close: function()
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function reject_close' );

			MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );
			MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );

			if( typeof window.CookieShield?.resetAcceptedAllCache === "function" )
			{
				window.CookieShield.resetAcceptedAllCache();
			}

			that.updateSomeConsentGivenStatus();

			//animation
			var that_animation = that.bar_elm.getAttribute( 'data-animation' );

			var that_id_selector = '#' + that.bar_elm.getAttribute( 'id' );

			var animation_params = {
				targets: that_id_selector,
				easing: 'easeInOutQuad',
				duration: 700,
				complete: function( anim ) {
					MAP.bar_elm.style.display = 'none';
				}
			};

			switch( that_animation )
			{
				case 'slide':

					if( that.bar_elm.className.includes( 'mapPositionBottom' ) )
					{
						animation_params['bottom'] = '-100vh';
					}
					else if( that.bar_elm.className.includes( 'mapPositionTop' ) )
					{
						animation_params['top'] = '-100vh';
					}
					else if( that.bar_elm.className.includes( 'mapPositionCenterLeft' ) )
					{
						animation_params['left'] = '-100vw';
					}
					else if( that.bar_elm.className.includes( 'mapPositionCenterRight' ) )
					{
						animation_params['right'] = '-100vw';
					}

					map_animate(animation_params );
					break;

				case 'fade':

					animation_params['opacity'] = '0';
					animation_params['duration'] = '500';

					map_animate(animation_params );

					break;

				case 'zoom':

					animation_params['scale'] = '0';
					map_animate(animation_params );


					break;

				default: // no animation -> value = "none"
					that.bar_elm.style.display = 'none';

					break;
			}

			that.bar_open = false;
			that.dispatchConsentEvents( 'myagileprivacy_bar_hidden' );

			var this_showagain_tab = Boolean( that.settings.showagain_tab );

			if( !this_showagain_tab &&
				!document.querySelector( '.showConsentAgain:not(.map_custom_notify):not(.map_inline_notify)' ) )
			{
				this_showagain_tab = true;
			}

			if( this_showagain_tab )
			{
				that.showagain_elm.style.opacity = 0;
				that.showagain_elm.style.display = 'block';

				map_animate({
					targets: that.showagain_elm,
					easing: 'easeInOutQuad',
					duration: 150,
					opacity: 1,
					complete: function(anim) {
					}
				} );
			}

			that.tryToUnblockScripts( true, null );

			setTimeout( function() {
				that.checkBlockedContent();
			}, 200 );

			(async() => {

				while( !MAP_SYS.map_document_load )
				{
					//MapLogger.log( 'not defined yet' );
					await new Promise( resolve => setTimeout( resolve, 10 ) );
				}

				if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
				{
					window.MAPIABTCF_hideCMPUI();
				}

			})();

			return false;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	tryToUnblockScripts: function( from_user_interaction = false, cookieNameReference = null )
	{
		try {

			let need_reload = false;
			let do_calc_need_reload = false;

			if( from_user_interaction )
			{
				do_calc_need_reload = true;
			}

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + `tryToUnblockScripts from_user_interaction=${from_user_interaction}` );

			//for preserving scope
			var that = this;

			var once_functions_to_execute = [];

			var $map_cookie_description_wrapper = that.settingsModal.querySelectorAll( '.map_cookie_description_wrapper' );

			$map_cookie_description_wrapper.forEach( function( $this ) {

				var baseIndex = $this.getAttribute('data-cookie-baseindex');
				var cookieName = 'map_cookie_' + baseIndex + MAP_POSTFIX;
				var api_key = $this.getAttribute('data-cookie-api-key');

				var consent = that.entryConsent( $this );
				var cookieValue = consent.cookieValue;

				if( $this.classList &&
					do_calc_need_reload &&
					$this.classList.contains( 'map_page_reload_on_user_consent' ) &&
					cookieName == cookieNameReference &&
					MAP_Cookie.read( cookieName ) == '1' )
				{
					need_reload = true;
				}

				if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'debug ' + api_key + ' ' + cookieName + ' ' + cookieValue );

				var always_on = consent.always_on;
				var activate_anyway = consent.activate_anyway;
				var accepted_all = consent.accepted_all;

				if( consent.ok )
				{
					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + '-->activating api_key=' + api_key + ' cookieName=' + cookieName + ' cookieValue=' + cookieValue + ' always_on=' + always_on + ' accepted_all=' + accepted_all );

					if( activate_anyway )
					{
						$this.classList.add( '_is_activated_anyway' );
					}
					else
					{
						$this.classList.add( '_is_activated' );
					}

					var jq_pending = that.entryPendingScripts( $this, baseIndex, api_key );
					var jq_held = MAP_JQ_WAIT.held( jq_pending );
					var jq_hold = !jq_held && MAP_JQ_WAIT.needs( jq_pending );
					var run_scripts = !jq_held && !jq_hold;

					//only the service scripts that use the delayed jQuery wait for it
					var twin_inline = that.pixelTwinInline( api_key );
					var twin_jq = twin_inline.filter( function( n ) { return MAP_JQ_WAIT.needs( [ n ] ); } );
					var twin_now = twin_inline.filter( function( n ) { return twin_jq.indexOf( n ) < 0; } );

					if( run_scripts )
					{
						that.unblockEntryCode( $this, baseIndex );
					}

					if( !!api_key )
					{
						//bof custom notify
						var $custom_ref = document.querySelectorAll( '.map_custom_notify.map_api_key_' + api_key );

						$custom_ref.forEach( function( $_this ){

							if( $_this )
							{
								var on_unblock_remove_class = $_this.getAttribute( 'data-on-unblock-remove-class' );

								if( on_unblock_remove_class )
								{
									var $the_blocked_content = document.querySelectorAll( '.my_agile_privacy_activate[data-cookie-api-key="' + api_key + '"]' );

									$the_blocked_content.forEach( $thisent => $thisent.classList.remove( on_unblock_remove_class ) );
								}

								$_this.remove();
							}
						});

						//eof custom notify

						if( run_scripts )
						{
							that.unblockEntryScripts( api_key, once_functions_to_execute, twin_now );
						}
						else if( twin_now.length )
						{
							that.unblockEntryScripts( null, once_functions_to_execute, twin_now );
						}

						if( twin_jq.length )
						{
							that.holdTwinInline( $this, api_key, twin_jq );
						}

						var $iframe_src_blocked = document.querySelectorAll( 'iframe.my_agile_privacy_activate.autoscan_mode.iframe_src_blocked[data-cookie-api-key="'+api_key+'"]' );

						var $css_href_blocked = document.querySelectorAll( 'link.my_agile_privacy_activate.autoscan_mode.css_href_blocked[data-cookie-api-key="'+api_key+'"]' );

						var $img_src_blocked = document.querySelectorAll( 'img.my_agile_privacy_activate.autoscan_mode.img_src_blocked[data-cookie-api-key="'+api_key+'"], img[unblocked_src][data-cookie-api-key="'+api_key+'"]:not(._is_activated), img[unblocked_srcset][data-cookie-api-key="'+api_key+'"]:not(._is_activated)' );

						if( !!$iframe_src_blocked && $iframe_src_blocked?.length )
						{
							$iframe_src_blocked.forEach( function( $_this ) {

								if( !$_this.classList.contains( '_is_activated' ) )
								{
									$_this.classList.add( '_is_activated' );

									$_this.setAttribute( 'src', $_this.getAttribute( 'unblocked_src' ) );

									$_this.style.display = 'block';

									var $ref = document.querySelectorAll( '.map_inline_notify[data-cookie-api-key="' + api_key + '"]');

									if( !!$ref && $ref?.length )
									{
										$ref.forEach( function( $_ref ) {
											$_ref.remove();
										});
									}
								}
							});
						}

						if( !!$css_href_blocked && $css_href_blocked?.length )
						{
							$css_href_blocked.forEach( function( $_this ) {

								if( !$_this.classList.contains( '_is_activated' ) )
								{
									$_this.classList.add( '_is_activated' );

									$_this.setAttribute( 'href', $_this.getAttribute( 'unblocked_href' ) );
								}
							});
						}

						if( !!$img_src_blocked && $img_src_blocked?.length )
						{
							$img_src_blocked.forEach( function( $_this ) {

								if( !$_this.classList.contains( '_is_activated' ) )
								{
									$_this.classList.add( '_is_activated' );

									var $picture = $_this.parentElement;

									if( !!$picture && $picture.tagName === 'PICTURE' )
									{
										$picture.querySelectorAll( 'source[unblocked_srcset]' ).forEach( function( $_source ) {

											if( $_source.hasAttribute( 'unblocked_sizes' ) )
											{
												$_source.setAttribute( 'sizes', $_source.getAttribute( 'unblocked_sizes' ) );
											}

											$_source.setAttribute( 'srcset', $_source.getAttribute( 'unblocked_srcset' ) );
										});
									}

									if( $_this.hasAttribute( 'unblocked_sizes' ) )
									{
										$_this.setAttribute( 'sizes', $_this.getAttribute( 'unblocked_sizes' ) );
									}

									if( $_this.hasAttribute( 'unblocked_srcset' ) )
									{
										$_this.setAttribute( 'srcset', $_this.getAttribute( 'unblocked_srcset' ) );
									}

									if( $_this.getAttribute( 'unblocked_src' ) )
									{
										$_this.setAttribute( 'src', $_this.getAttribute( 'unblocked_src' ) );
									}
								}
							});
						}
					}

					if( jq_hold )
					{
						that.holdEntry( $this, baseIndex, api_key, jq_pending );
					}
				}
				else
				{
					$this.classList.remove( '_is_activated' );

					that.entryPendingScripts( $this, baseIndex, api_key ).forEach( function( n ) { n.classList.remove( '_map_jq_wait' ); } );
					that.pixelTwinInline( api_key, true ).forEach( function( n ) { n.classList.remove( '_map_jq_wait' ); } );
				}

			});

			if( need_reload )
			{
				//a reload would resubmit the form: navigate to the same address instead
				if( that.isPostMode() )
				{
					window.location.replace( window.location.href );
				}
				else
				{
					window.location.reload();
				}
			}

			//execution of once custom patch functions
			once_functions_to_execute.forEach( function(v) {
				setTimeout( function() {
					window[v]();
				}, 1000 );
			});

			setTimeout( function() {
				that.checkBlockedContent();
			}, 500 );

			setTimeout( function() {
				var event = new Event('MAP_PRIVACY_CHANGE');
				document.body.dispatchEvent(event);
			}, 100 );

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//consent of a preferences entry
	entryConsent: function( $this )
	{
		var baseIndex = $this.getAttribute('data-cookie-baseindex');
		var cookieName = 'map_cookie_' + baseIndex + MAP_POSTFIX;
		var api_key = $this.getAttribute('data-cookie-api-key');

		var cookieValue = MAP_Cookie.read( cookieName );

		var always_on = false;
		var activate_anyway = false;

		if( $this.classList.contains( '_always_on' ) )
		{
			always_on = true;
		}

		//advanced consent mode ga4
		if( api_key == 'google_analytics' &&
			typeof map_full_config !== 'undefined' &&
			typeof map_full_config.cookie_api_key_not_to_block !== 'undefined'
			&& map_full_config?.cookie_api_key_not_to_block?.includes( 'google_analytics' )
		)
		{
			activate_anyway = true;
		}

		var accepted_all = false;

		if( ( MAP_Cookie.exists( MAP_ACCEPTED_ALL_COOKIE_NAME ) && MAP_Cookie.read( MAP_ACCEPTED_ALL_COOKIE_NAME ) == '1' ) )
		{
			accepted_all = true;
		}

		return {
			ok: ( cookieValue == "1" || always_on || activate_anyway || accepted_all ),
			cookieValue: cookieValue,
			always_on: always_on,
			activate_anyway: activate_anyway,
			accepted_all: accepted_all
		};
	},

	//held script nodes of an entry not yet run
	entryPendingScripts: function( $this, baseIndex, api_key )
	{
		var list = [];

		try {
			var sel = [];

			if( $this.classList.contains( '_with_code' ) )
			{
				sel.push( 'script.my_agile_privacy_activate._js_noscript_type_mode[data-cookie-baseindex="'+baseIndex+'"]' );
				sel.push( 'textarea.my_agile_privacy_activate._raw_type_mode[data-cookie-baseindex="'+baseIndex+'"]' );
			}

			if( !!api_key )
			{
				sel.push( 'script.my_agile_privacy_activate.autoscan_mode.map_src_script_blocked[data-cookie-api-key="'+api_key+'"]' );
				sel.push( 'script.my_agile_privacy_activate.autoscan_mode.map_inline_script_blocked[data-cookie-api-key="'+api_key+'"]' );
			}

			if( sel.length )
			{
				document.querySelectorAll( sel.join( ', ' ) ).forEach( function( n ) {
					if( !n.classList.contains( '_is_activated' ) ) list.push( n );
				});
			}
		}
		catch( error )
		{
			console.debug( error );
		}

		return list;
	},

	//held inline scripts tied to a My Agile Pixel channel
	pixelTwinInline: function( api_key, with_queued )
	{
		var out = [];

		try {
			var twins = { 'my_agile_pixel_fbq': 'facebook_remarketing', 'my_agile_pixel_ga': 'google_analytics', 'my_agile_pixel_tiktok': 'tik_tok' };
			var twin = ( typeof api_key === 'string' && Object.prototype.hasOwnProperty.call( twins, api_key ) ) ? twins[ api_key ] : null;
			var cfg = ( typeof map_full_config !== 'undefined' && map_full_config && typeof map_full_config === 'object' ) ? map_full_config : null;

			if( !twin || !cfg ) return out;

			var silent = cfg.cookie_api_key_remote_id_map_blocked_without_notification;
			var active = cfg.cookie_api_key_remote_id_map_active;

			if( !silent || typeof silent !== 'object' || !silent[ twin ] ) return out;
			if( active && typeof active === 'object' && active[ twin ] ) return out;
			if( !document.querySelector( 'script.map_inline_script_blocked[data-cookie-api-key="' + api_key + '"]' ) ) return out;

			document.querySelectorAll( 'script.my_agile_privacy_activate.autoscan_mode.map_inline_script_blocked[data-cookie-api-key="' + twin + '"]' ).forEach( function( n ) {
				if( !n.classList.contains( '_is_activated' ) && ( with_queued || !n.classList.contains( '_map_jq_wait' ) ) ) out.push( n );
			});
		}
		catch( error )
		{
			console.debug( error );
		}

		return out;
	},

	//runs the service scripts of a My Agile Pixel channel when the page's jQuery is there
	holdTwinInline: function( $this, api_key, nodes )
	{
		var that = this;

		MAP_JQ_WAIT.hold( {
			nodes: nodes,
			consent: function() { return that.entryConsent( $this ).ok; },
			run: function() {
				var once_functions_to_execute = [];

				that.unblockEntryScripts( null, once_functions_to_execute, that.pixelTwinInline( api_key ) );

				once_functions_to_execute.forEach( function(v) {
					setTimeout( function() {
						window[v]();
					}, 1000 );
				});
			}
		} );
	},

	//runs an entry's scripts when the page's jQuery is there
	holdEntry: function( $this, baseIndex, api_key, nodes )
	{
		var that = this;

		MAP_JQ_WAIT.hold( {
			nodes: nodes,
			consent: function() { return that.entryConsent( $this ).ok; },
			run: function() {
				var once_functions_to_execute = [];

				that.unblockEntryCode( $this, baseIndex );

				if( !!api_key )
				{
					that.unblockEntryScripts( api_key, once_functions_to_execute, that.pixelTwinInline( api_key ) );
				}

				once_functions_to_execute.forEach( function(v) {
					setTimeout( function() {
						window[v]();
					}, 1000 );
				});
			},
			drop: function() { that.checkBlockedContent(); }
		} );
	},

	//custom code of an entry
	unblockEntryCode: function( $this, baseIndex )
	{
		if( $this.classList.contains( '_with_code' ) )
		{
			var $the_script = document.querySelector( 'script.my_agile_privacy_activate._js_noscript_type_mode[data-cookie-baseindex="'+baseIndex+'"]' );

			var $the_raw_script = document.querySelector( 'textarea.my_agile_privacy_activate._raw_type_mode[data-cookie-baseindex="'+baseIndex+'"]' );

			if( $the_script )
			{
				if( !$the_script.classList.contains( '_is_activated' ) )
				{
					$the_script.classList.add( '_is_activated' );

					var script = document.createElement( 'script' );
					script.className = '_is_activated';
					script.innerHTML = $the_script.innerHTML;

					if( MAP_SYS?.map_debug ) MapLogger.debug( script.innerHTML );

					MAP_WRITE_REPLAY.run( null, script, false, function() { document.head.appendChild( script ); } );
				}
			}

			if( $the_raw_script )
			{
				if( !$the_raw_script.classList.contains( '_is_activated' ) )
				{
					$the_raw_script.classList.add( '_is_activated' );

					var the_raw_script = $the_raw_script.value;

					if( MAP_SYS?.map_debug ) MapLogger.debug( the_raw_script );

					var range = document.createRange();
					range.selectNode( document.getElementsByTagName("body")[0] );
					var documentFragment = range.createContextualFragment( the_raw_script );
					MAP_WRITE_REPLAY.fragment( documentFragment, function() { document.body.appendChild( documentFragment ); } );
				}

			}
		}
	},

	//held scripts of a service key
	unblockEntryScripts: function( api_key, once_functions_to_execute, extra_inline )
	{
		var $map_src_script_blocked = api_key ? document.querySelectorAll( 'script.my_agile_privacy_activate.autoscan_mode.map_src_script_blocked[data-cookie-api-key="'+api_key+'"]' ) : [];

		var $map_inline_script_blocked = api_key ? Array.prototype.slice.call( document.querySelectorAll( 'script.my_agile_privacy_activate.autoscan_mode.map_inline_script_blocked[data-cookie-api-key="'+api_key+'"]' ) ) : [];

		var extra = Array.isArray( extra_inline ) ? extra_inline : [];

		//extra inline scripts run first
		var extra_wait = extra.some( function( n ) { return n.classList.contains( 'mapWait' ); } );

		$map_inline_script_blocked = extra.concat( $map_inline_script_blocked );

		//a clone keeps the browser's own record of whether the node already ran
		var runnable = function( n ) {
			if( extra.indexOf( n ) < 0 || ( typeof CookieShield === 'object' && CookieShield && typeof CookieShield.isHeldBeforeRun === 'function' && CookieShield.isHeldBeforeRun( n ) ) )
			{
				var s = document.createElement( 'script' );
				s.innerHTML = n.innerHTML;
				return s;
			}

			var c = n.cloneNode( true );
			while( c.attributes.length ) c.removeAttribute( c.attributes[ 0 ].name );
			return c;
		};

		var do_trigger_dom_content_loaded_event = false;

		if( !!$map_src_script_blocked && $map_src_script_blocked?.length )
		{
			$map_src_script_blocked.forEach( function( $_this ) {

				let old_script_type = $_this.getAttribute( 'data-map-held-type' ) || $_this.getAttribute( 'type' );
				let new_script_type = 'text/javascript';

				if( typeof old_script_type !== 'undefined' &&
					old_script_type &&
					old_script_type == 'module' )
				{
					new_script_type = 'module';
				}

				if( !$_this.classList.contains( '_is_activated' ) )
				{
					$_this.classList.add( '_is_activated' );

					if( $_this.classList.contains( 'custom_patch_apply' ) )
					{
						var classes = $_this.className.split(' ');

						classes.forEach( function(v) {
							if(v && v != '' && v.startsWith('map_trigger_custom_patch_') && typeof window[v] === 'function') {
								setTimeout( function() {
									window[v]();
								}, 4000 );
							}
						});
					}

					if( $_this.classList.contains( 'once_custom_patch_apply' ) )
					{
						var classes = $_this.className.split(' ');

						classes.forEach( function(v) {
							if(v && v != '' && v.startsWith('map_trigger_custom_patch_') && typeof window[v] === 'function') {
								if( !once_functions_to_execute.includes( v )) {
									once_functions_to_execute.push( v );
								}
							}
						});
					}

					if( $_this.classList.contains( 'mapWait' ) )
					{
						do_trigger_dom_content_loaded_event = true;

						setTimeout( function() {

							//prevent block using createElementBackup
							var script = ( typeof document.createElementBackup === 'function' )
							    ? document.createElementBackup( 'script' )
							    : document.createElement( 'script' );

							script.className = '_is_activated';
							script.setAttribute( 'type',new_script_type );

							script = cloneNodeAttributeToAnother( $_this, script );

							var blocked_src = $_this.getAttribute( 'unblocked_src' );

							if( blocked_src )
							{
								script.src = blocked_src;
							}

							MAP_WRITE_REPLAY.run( $_this, script, true, function() { $_this.insertAdjacentElement( 'afterend', script ); } );

							$_this.classList.add( '_is_activated' );

						}, 1000);
					}
					else
					{
						//prevent block using createElementBackup
						var script = ( typeof document.createElementBackup === 'function' )
						    ? document.createElementBackup( 'script' )
						    : document.createElement( 'script' );

						script.className = '_is_activated';
						script.setAttribute( 'type',new_script_type );

						script = cloneNodeAttributeToAnother( $_this, script );

						var blocked_src = $_this.getAttribute( 'unblocked_src' );

						if( blocked_src )
						{
							script.src = blocked_src;
						}

						MAP_WRITE_REPLAY.run( $_this, script, true, function() { $_this.insertAdjacentElement( 'afterend', script ); } );
					}
				}

			});

		}

		if( !!$map_inline_script_blocked && $map_inline_script_blocked?.length )
		{
			$map_inline_script_blocked.forEach( function( $_this ) {

				if( !$_this.classList.contains( '_is_activated' ) )
				{
					$_this.classList.add( '_is_activated' );

					if( $_this.classList.contains( 'custom_patch_apply' ) )
					{
						var classes = $_this.className.split(' ');

						classes.forEach( function(v) {
							if( v && v != '' && v.startsWith('map_trigger_custom_patch_') && typeof window[v] === 'function' )
							{
								setTimeout( function() {
									window[v]();
								}, 2200);
							}
						});
					}

					if( $_this.classList.contains( 'once_custom_patch_apply' ) )
					{
						var classes = $_this.className.split(' ');

						classes.forEach( function(v) {
							if( v && v != '' && v.startsWith('map_trigger_custom_patch_') && typeof window[v] === 'function' )
							{
								if(!once_functions_to_execute.includes( v )) {
									once_functions_to_execute.push( v );
								}
							}
						});
					}

					if( $_this.classList.contains( 'mapWait' ) || ( extra_wait && extra.indexOf( $_this ) < 0 ) )
					{
						if( $_this.classList.contains( 'mapWait' ) ) do_trigger_dom_content_loaded_event = true;

						setTimeout( function() {
							var script = runnable( $_this );
							script.className = '_is_activated';

							if( MAP_SYS?.map_debug ) MapLogger.debug( script.innerHTML );

							MAP_WRITE_REPLAY.run( $_this, script, false, function() { document.head.appendChild( script ); } );

							$_this.classList.add( '_is_activated' );

						}, 1000);
					}
					else
					{
						var script = runnable( $_this );
						script.className = '_is_activated';

						if( MAP_SYS?.map_debug ) MapLogger.debug( script.innerHTML );

						MAP_WRITE_REPLAY.run( $_this, script, false, function() { document.head.appendChild( script ); } );
					}
				}

			});

		}

		if( do_trigger_dom_content_loaded_event )
		{
			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggering do_trigger_dom_content_loaded_event' );

			var count = 0;
			var replica = MAP_DCL_REPLICA.arm();

			var timerId = setInterval(function () {
			  count++;
			  if( count >= 3 )
			  {
			    clearInterval( timerId );
			  }

			  if( replica.on && replica.active() )
			  {
			    try { replica.tick(); } catch( e ) { console.debug( e ); }
			  }
			  else
			  {
			    document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
			  }

			  if( count >= 3 )
			  {
			    replica.done();
			  }
			}, 1100);
		}
	},

	//init user preferences and bind events
	//with only_init_status==true events are not binded
	userPreferenceInit: function( only_init_status = false )
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'userPreferenceInit only_init_status='+only_init_status );

			//for preserving scope
			var that = this;

			//init part (cookie consent)
			that.settingsModal.querySelectorAll( '.map-user-preference-checkbox' ).forEach( function( $this ) {

				var cookieName = 'map_cookie_' + $this.getAttribute('data-cookie-baseindex') + MAP_POSTFIX;

				var cookieValue = MAP_Cookie.read( cookieName );
				if( cookieValue == null )
				{
					if( $this.value === '1' )
					{
						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting 1 to cookieName=' + cookieName );

						MAP_Cookie.set( cookieName, '1', MAP_SYS.map_cookie_expire );
					}
					else
					{
						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting -1 to cookieName' + cookieName );

						MAP_Cookie.set( cookieName, '-1', MAP_SYS.map_cookie_expire );
					}
				}
				else
				{
					if( cookieValue == "1" )
					{
						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting checked for cookieName' + cookieName );

						$this.value = '1';
						MAP.mapSyncSlider( $this );
					}
					else
					{
						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting unchecked for cookieName' + cookieName );

						$this.value = '0';
						MAP.mapSyncSlider( $this );
					}
				}
			});

			//click event
			if( only_init_status == false )
			{
				that.settingsModal.querySelectorAll( '.map-user-preference-checkbox' ).forEach(function( $this ) {
					$this.addEventListener( 'map:toggle', function( e ) {

						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-user-preference-checkbox map:toggle' );

						e.stopImmediatePropagation();

						that.updateLastConsentRecords();

						var cookieName = 'map_cookie_' + $this.getAttribute('data-cookie-baseindex') + MAP_POSTFIX;

						var currentToggleElm = that.settingsModal.querySelectorAll( '.map-user-preference-checkbox[data-cookie-baseindex="'+$this.getAttribute('data-cookie-baseindex')+'"]' );

						if( $this.value === '1' )
						{
							if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting 1 to cookieName=' + cookieName );

							MAP_Cookie.set( cookieName, '1', MAP_SYS.map_cookie_expire );
							currentToggleElm.forEach( function( elm ) { elm.value = '1'; MAP.mapSyncSlider( elm ); } );

							that.dispatchConsentEvents( 'myagileprivacy_single_cookie_accept' );
						}
						else
						{
							if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'setting -1 to cookieName' + cookieName );

							MAP_Cookie.set( cookieName, '-1', MAP_SYS.map_cookie_expire );
							currentToggleElm.forEach( function( elm ) { elm.value = '0'; MAP.mapSyncSlider( elm ); } );

							that.dispatchConsentEvents( 'myagileprivacy_single_cookie_reject' );
						}

						MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );
						MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );

						if( typeof window.CookieShield?.resetAcceptedAllCache === "function" )
						{
							window.CookieShield.resetAcceptedAllCache();
						}

						that.updateSomeConsentGivenStatus();

						var toggledWrapper = $this.closest( '.map_cookie_description_wrapper[data-cookie-api-key]' );
						that.applyConsentWithoutSwitches( 'partial', toggledWrapper ? toggledWrapper.getAttribute( 'data-cookie-api-key' ) : null );

						that.tryToUnblockScripts( true, cookieName );

					});
				});
			}

			//bof init part ( consent mode)
			that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox' ).forEach( function( elem ) {

				var consent_key = elem.getAttribute( 'data-consent-key' );

				var consentStatus = null;
				let consentVendor = '';


				if( elem.classList.contains( 'map-consent-clarity' ) )
				{
					consentStatus = that.getClarityConsentStatus( consent_key );
					consentVendor = '(Clarity)';
				}

				if( elem.classList.contains( 'map-consent-microsoft' ) )
				{
					consentStatus = that.getMicrosoftConsentStatus( consent_key );
					consentVendor = '(Microsoft)';
				}

				if( elem.classList.contains( 'map-consent-google' ) )
				{
					consentStatus = that.getGoogleConsentStatus( consent_key );
					consentVendor = '(Google)';
				}

				if( consentStatus === 'granted' )
				{
					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + `setting checked for consent_key=${consent_key} ${consentVendor}` );

					elem.value = '1';
					MAP.mapSyncSlider( elem );
				}
				else if( consentStatus === 'denied' )
				{
					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + `setting unchecked for consent_key=${consent_key} ${consentVendor}` );

					elem.value = '0';
					MAP.mapSyncSlider( elem );
				}

			});
			//eof init part ( consent mode)

			//bof consent mode - click event
			if( only_init_status == false )
			{
				that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox' ).forEach(function( elem ) {
					elem.addEventListener( 'map:toggle', function( e ) {

						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-consent-mode-preference-checkbox map:toggle' );

						e.stopImmediatePropagation();

						var consent_key = elem.getAttribute( 'data-consent-key' );

						var currentToggleElm = that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox[data-consent-key="' + consent_key + '"]' );

						if( elem.value === '1' )
						{
							let consentVendor = '';

							if( elem.classList.contains( 'map-consent-clarity' ) )
							{
								consentVendor = '(Clarity)';
								that.updateClarityConsent( consent_key, 'granted', true );
							}

							if( elem.classList.contains( 'map-consent-microsoft' ) )
							{
								consentVendor = '(Microsoft)';
								that.updateMicrosoftConsent( consent_key, 'granted', true );
							}

							if( elem.classList.contains( 'map-consent-google' ) )
							{
								consentVendor = '(Google)';
								that.updateGoogleConsentbyKeyValue( consent_key, 'granted', true );
							}

							if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + `setting granted to consent_key=${consent_key} ${consentVendor}` );

							currentToggleElm.forEach( function( elm ) { elm.value = '1'; MAP.mapSyncSlider( elm ); } );

						}
						else
						{
							let consentVendor = '';

							if( elem.classList.contains( 'map-consent-clarity' ) )
							{
								consentVendor = '(Clarity)';
								that.updateClarityConsent( consent_key, 'denied', true );
							}

							if( elem.classList.contains( 'map-consent-microsoft' ) )
							{
								consentVendor = '(Microsoft)';
								that.updateMicrosoftConsent( consent_key, 'denied', true );
							}

							if( elem.classList.contains( 'map-consent-google' ) )
							{
								consentVendor = '(Google)';
								that.updateGoogleConsentbyKeyValue( consent_key, 'denied', true );
							}

							if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + `setting denied to consent_key=${consent_key} ${consentVendor}` );

							currentToggleElm.forEach( function( elm ) { elm.value = '0'; MAP.mapSyncSlider( elm ); } );
						}

						MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );
						MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );

						if( typeof window.CookieShield?.resetAcceptedAllCache === "function" )
						{
							window.CookieShield.resetAcceptedAllCache();
						}

						that.updateSomeConsentGivenStatus();

					});
				});
			}
			//eof consent mode - click event

			if( only_init_status == false )
			{
				//bof iab part - map:toggle event
				that.settingsModal.addEventListener( 'map:toggle', function( e ) {

					var $this = e.target;

					if( $this.matches( '.map-user-iab-preference-checkbox' ) )
					{
						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-user-iab-preference-checkbox map:toggle' );

						e.stopImmediatePropagation();

						that.updateLastConsentRecords();

						var iab_category = $this.getAttribute( 'data-iab-category' );
						var iab_key = $this.getAttribute( 'data-iab-key' );

						// the master switch is all or none
						if( iab_category === 'googleVendorsAcceptAll' )
						{
							$this.classList.remove( 'map-flags' );
						}

						if( $this.value === '1' )
						{
							if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + `setting 1 to iab_category=${iab_category} , iab_key=${iab_key}` );

							that.dispatchConsentEvents( 'myagileprivacy_iab_item_accept' );

							(async() => {
								while (!MAP_SYS.map_document_load) {
									// MapLogger.log( 'not defined yet' );
									await new Promise(resolve => setTimeout(resolve, 10));
								}

								if( typeof window.MAPIABTCF_updateConsent === 'function' )
								{
									let updateHtml = false;
									if( iab_category === 'googleVendors' ) updateHtml = true;

									window.MAPIABTCF_doSetUserInteraction();
									window.MAPIABTCF_updateConsent( iab_category, parseInt( iab_key, 10 ),true, true, updateHtml );

									that.updateSomeConsentGivenStatus();
								}

							})();
						}
						else
						{
							if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + `setting 0 to iab_category=${iab_category} , iab_key=${iab_key}` );

							that.dispatchConsentEvents( 'myagileprivacy_iab_item_reject' );

							(async() => {
								while (!MAP_SYS.map_document_load) {
									// MapLogger.log( 'not defined yet' );
									await new Promise(resolve => setTimeout(resolve, 10));
								}

								if( typeof window.MAPIABTCF_updateConsent === 'function' )
								{
									let updateHtml = false;
									if( iab_category === 'googleVendors' ) updateHtml = true;

									window.MAPIABTCF_doSetUserInteraction();
									window.MAPIABTCF_updateConsent( iab_category, parseInt( iab_key, 10 ),false, true, updateHtml );

									that.updateSomeConsentGivenStatus();
								}

							})();
						}

						/**/
					}

				});
				//eof iab part - click event

				//bof iab part - deny all / accept all event
				that.settingsModal.addEventListener( 'click', function( e ) {
					var target = e.target;
					if( target.matches( '.map-privacy-iab-tcf-accept-all-button' ) )
					{
						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-privacy-iab-tcf-accept-all-button click' );

						e.preventDefault();
						e.stopImmediatePropagation();

						that.updateLastConsentRecords();

						that.dispatchConsentEvents( 'myagileprivacy_iab_accept_all' );

						MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );
						MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );

						if( typeof window.CookieShield?.resetAcceptedAllCache === "function" )
						{
							window.CookieShield.resetAcceptedAllCache();
						}

						// without switches in the panel it counts as the banner's «Accept»
						that.applyConsentWithoutSwitches( 'accept', null, false );

						that.settingsModal.querySelectorAll( '.map-user-iab-preference-checkbox ').forEach(function( $_this ) {
							$_this.classList.remove( 'map-flags' ); // a choice on everything is never partial
							$_this.value = '1';
							MAP.mapSyncSlider( $_this );
						});

						(async() => {
							while (!MAP_SYS.map_document_load) {
								// MapLogger.log( 'not defined yet' );
								await new Promise(resolve => setTimeout( resolve, 10 ));
							}

							if( typeof window.MAPIABTCF_acceptAllConsent === 'function' )
							{
								window.MAPIABTCF_doSetUserInteraction();
								window.MAPIABTCF_acceptAllConsent( true );

								that.updateSomeConsentGivenStatus();
							}

						})();
					}
				});

				that.settingsModal.addEventListener( 'click', function( e ) {
					var target = e.target;
					if( target.matches( '.map-privacy-iab-tcf-deny-all-button' ) ) {
						if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map-privacy-iab-tcf-deny-all-button' );

						e.preventDefault();
						e.stopImmediatePropagation();

						that.updateLastConsentRecords();

						that.dispatchConsentEvents( 'myagileprivacy_iab_reject_all' );

						MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );
						MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );

						if( typeof window.CookieShield?.resetAcceptedAllCache === "function" )
						{
							window.CookieShield.resetAcceptedAllCache();
						}

						// without switches in the panel it counts as the banner's «Reject»
						that.applyConsentWithoutSwitches( 'reject', null, false );

						that.settingsModal.querySelectorAll( '.map-user-iab-preference-checkbox' ).forEach( function( $elem ) {
							$elem.classList.remove( 'map-flags' ); // a choice on everything is never partial
							$elem.value = '0';
							MAP.mapSyncSlider( $elem );
						});

						(async() => {
							while (!MAP_SYS.map_document_load) {
								// MapLogger.log( 'not defined yet' );
								await new Promise(resolve => setTimeout(resolve, 10));
							}

							if( typeof window.MAPIABTCF_denyAllConsent === 'function' )
							{
								window.MAPIABTCF_doSetUserInteraction();
								window.MAPIABTCF_denyAllConsent( true );

								that.updateSomeConsentGivenStatus();
							}

						})();
					}
				});
				//eof iab part - deny all / accept all event
			}

			if( only_init_status == false )
			{
				that.tryToUnblockScripts( false, null );
			}

		}
		catch( error )
		{
			console.error( error );
		}
	},

	notice_i18n: {
		en: { prefix: "[MyAgilePrivacy admin-only notification]", close: "Close",
			detected: "The Cookie Shield has detected the following cookies so far: %s.",
			none: "The Cookie Shield has not detected any cookies.",
			off: "Cookie Shield is turned off. Enable it in order to block cookies.",
			bar_label: "Administrator notices", bar_close_all: "Close all",
			sr_line: "%s, administrator only" },
		it: { prefix: "[MyAgilePrivacy notifica solo per l'amministratore]", close: "Chiudi",
			detected: "Il Cookie Shield ha rilevato finora i seguenti cookie: %s.",
			none: "Il Cookie Shield non ha rilevato alcun cookie.",
			off: "Il Cookie Shield è disattivato. Attivalo per bloccare i cookie.",
			bar_label: "Notifiche per l'amministratore", bar_close_all: "Chiudi tutti",
			sr_line: "%s, solo per l'amministratore" },
		fr: { prefix: "[MyAgilePrivacy notification réservée à l'administrateur]", close: "Fermer",
			detected: "Le Cookie Shield a détecté jusqu'à présent les cookies suivants : %s.",
			none: "Le Cookie Shield n'a détecté aucun cookie.",
			off: "Le Cookie Shield est désactivé. Activez-le pour bloquer les cookies.",
			bar_label: "Notifications pour l'administrateur", bar_close_all: "Tout fermer",
			sr_line: "%s, réservé à l'administrateur" },
		de: { prefix: "[MyAgilePrivacy Hinweis nur für Administratoren]", close: "Schließen",
			detected: "Der Cookie Shield hat bisher die folgenden Cookies erkannt: %s.",
			none: "Der Cookie Shield hat keine Cookies erkannt.",
			off: "Der Cookie Shield ist deaktiviert. Aktivieren Sie ihn, um Cookies zu blockieren.",
			bar_label: "Hinweise für Administratoren", bar_close_all: "Alle schließen",
			sr_line: "%s, nur für Administratoren" },
		es: { prefix: "[MyAgilePrivacy aviso solo para el administrador]", close: "Cerrar",
			detected: "El Cookie Shield ha detectado hasta ahora las siguientes cookies: %s.",
			none: "El Cookie Shield no ha detectado ninguna cookie.",
			off: "El Cookie Shield está desactivado. Actívelo para bloquear las cookies.",
			bar_label: "Avisos para el administrador", bar_close_all: "Cerrar todo",
			sr_line: "%s, solo para el administrador" }
	},

	// Localized admin-notice text with English fallback.
	noticeText: function( key )
	{
		var lang = ( document.documentElement.getAttribute( 'lang' ) || 'en' ).toLowerCase().slice( 0, 2 );
		var table = this.notice_i18n[ lang ] || this.notice_i18n.en;
		return table[ key ] || this.notice_i18n.en[ key ] || '';
	},

	// Slot names are shared with the twin plugin, so each product owns its own.
	nb_prefix: 'map',

	// Card layout settings, read on every call because they are printed late.
	nbConfig: function()
	{
		if( typeof window.map_nb_config === 'undefined' ||
			!window.map_nb_config ||
			window.map_nb_config.style !== 'card' )
		{
			return null;
		}

		return window.map_nb_config;
	},

	// A message passed with a slot name owns its own line: a later call with the
	// same slot rewrites that line instead of adding another one, so re-running
	// the check updates the text rather than repeating it. Without a slot the
	// message is appended, which is what one-off events need.
	showNotificationBar: function( message = null, success = null, slot = null )
	{
		try {

			var that = this;
			var host = document.body || document.documentElement;

			if( !host )
			{
				document.addEventListener( 'DOMContentLoaded', function() {
					that.showNotificationBar( message, success, slot );
				}, { once: true } );

				return;
			}

			var config = this.nbConfig();
			var bar    = document.querySelector( '#mapx_notification_bar' );

			if( !bar )
			{
				bar = document.createElement( 'div' );
				bar.setAttribute( 'id','mapx_notification_bar' );

				if( !config )
				{
					bar.innerHTML = "<span class='mapx_close_notification_bar'>" + this.noticeText( 'close' ) + "</span>";
				}

				host.append( bar );
			}

			if( config )
			{
				this.nbPrepareBar( bar );
			}

			this.nbBindClose();

			var key  = slot ? this.nb_prefix + ':' + slot : null;
			var line = key ? bar.querySelector( '[data-map-notice="' + key + '"]' ) : null;

			if( config )
			{
				this.nbWriteCard( bar, line, key, message, success, config );
			}
			else
			{
				this.nbWriteLegacy( bar, line, key, message, success );
			}

			this.nbRefreshCloseAll( bar );

		}
		catch( error )
		{
			console.error( error );
		}
	},

	// Adopts a bar this product may not have created.
	nbPrepareBar: function( bar )
	{
		var that = this;

		if( !bar.classList.contains( 'mapx_nb--card' ) )
		{
			bar.classList.add( 'mapx_nb--card' );
			bar.setAttribute( 'role', 'region' );
			bar.setAttribute( 'aria-label', this.noticeText( 'bar_label' ) );
		}

		// The twin plugin can append rows without going through this code.
		if( bar.getAttribute( 'data-mapx-nb-watched' ) !== '1' &&
			typeof MutationObserver !== 'undefined' )
		{
			bar.setAttribute( 'data-mapx-nb-watched', '1' );

			new MutationObserver( function() {
				that.nbRefreshCloseAll( bar );
			} ).observe( bar, { childList: true } );
		}

		var tab = document.querySelector( '#' + this.nbConsentTabId() );

		bar.classList.toggle( 'mapx_nb--has-tab', !!( tab && tab.offsetParent !== null ) );
	},

	nbConsentTabId: function()
	{
		return ( this.settings && this.settings.showagain_div_id ) ? this.settings.showagain_div_id : 'my-agile-privacy-consent-again';
	},

	// One handler per product: the twin registers its own.
	nbBindClose: function()
	{
		var root = document.documentElement;
		var attr = 'data-mapx-nb-bound-' + this.nb_prefix;

		if( root.getAttribute( attr ) === '1' )
		{
			return;
		}

		root.setAttribute( attr, '1' );

		var that = this;

		document.addEventListener( 'click', function( event ) {

			var closer = that.nbCloserFrom( event.target );

			if( !closer )
			{
				return;
			}

			event.preventDefault();

			that.nbDismiss( closer );

		}, false );

		document.addEventListener( 'keydown', function( event ) {

			if( event.key !== 'Enter' && event.key !== ' ' && event.key !== 'Spacebar' )
			{
				return;
			}

			var closer = event.target && typeof event.target.closest === 'function'
				? event.target.closest( '.mapx_close_notification_bar' )
				: null;

			if( !closer )
			{
				return;
			}

			event.preventDefault();

			that.nbDismiss( closer );

		}, false );
	},

	nbCloserFrom: function( target )
	{
		if( !target || typeof target.closest !== 'function' )
		{
			return null;
		}

		return target.closest( '.mapx_nb_close, .mapx_nb_close_all, .mapx_close_notification_bar' );
	},

	nbDismiss: function( closer )
	{
		var bar = closer.closest( '#mapx_notification_bar' );

		if( !bar )
		{
			return;
		}

		if( closer.classList.contains( 'mapx_nb_close' ) )
		{
			var line = closer.closest( '.mapx_nb_line' );

			if( !line || !line.parentNode )
			{
				return;
			}

			this.nbMoveFocus( line, bar );
			line.remove();
			this.nbRefreshCloseAll( bar );

			if( !this.nbMessageCount( bar ) )
			{
				this.nbRemoveBar( bar );
			}

			return;
		}

		this.nbMoveFocus( bar, bar );
		this.nbRemoveBar( bar );
	},

	nbRemoveBar: function( bar )
	{
		if( bar.parentNode )
		{
			bar.remove();
		}
	},

	// The two overall close controls are not messages.
	nbMessageCount: function( bar )
	{
		var children = bar.children;
		var count    = 0;

		for( var i = 0; i < children.length; i++ )
		{
			if( children[ i ].classList.contains( 'mapx_nb_close_all' ) ||
				children[ i ].classList.contains( 'mapx_close_notification_bar' ) )
			{
				continue;
			}

			count++;
		}

		return count;
	},

	nbMoveFocus: function( removed, bar )
	{
		var active = document.activeElement;

		if( !active || !removed.contains( active ) )
		{
			return;
		}

		var buttons    = bar.querySelectorAll( '.mapx_nb_close, .mapx_nb_close_all, .mapx_close_notification_bar' );
		var candidates = [];

		for( var i = 0; i < buttons.length; i++ )
		{
			if( !removed.contains( buttons[ i ] ) )
			{
				candidates.push( buttons[ i ] );
			}
		}

		var next = null;

		for( var j = 0; j < candidates.length; j++ )
		{
			next = candidates[ j ];

			if( removed.compareDocumentPosition( candidates[ j ] ) & Node.DOCUMENT_POSITION_FOLLOWING )
			{
				break;
			}
		}

		if( next && typeof next.focus === 'function' )
		{
			next.focus();

			return;
		}

		this.nbFocusBody();
	},

	nbFocusBody: function()
	{
		var body = document.body;

		if( !body || typeof body.focus !== 'function' )
		{
			return;
		}

		body.setAttribute( 'tabindex', '-1' );
		body.focus();

		body.addEventListener( 'blur', function() {
			body.removeAttribute( 'tabindex' );
		}, { once: true } );
	},

	nbWriteCard: function( bar, line, key, message, success, config )
	{
		var text  = String( message || '' );
		var state = success == 1 ? 'success' : ( success == 2 ? 'error' : ( success == 3 ? 'warning' : 'info' ) );
		var stamp = state + '|' + text;

		if( line && line.querySelector( '.mapx_nb_text' ) )
		{
			if( line.getAttribute( 'data-mapx-nb-key' ) === stamp )
			{
				return;
			}
		}
		else
		{
			if( !line )
			{
				line = document.createElement( 'div' );

				if( key )
				{
					line.setAttribute( 'data-map-notice', key );
				}

				bar.append( line );
			}

			line.innerHTML = '';
			this.nbBuildLine( line, config );
		}

		line.className = 'mapx_nb_line mapx_nb_line--' + state;
		line.setAttribute( 'data-mapx-nb-key', stamp );
		line.querySelector( '.mapx_nb_text' ).textContent = text;

		this.nbSetBadge( line, success );
		this.nbAnnounce( config, text );
	},

	nbBuildLine: function( line, config )
	{
		var mascot = document.createElement( 'span' );
		mascot.className = 'mapx_nb_mascot' + ( config.shape === 'round' ? ' mapx_nb_mascot--round' : '' );

		var img = document.createElement( 'img' );
		img.src = String( config.mascot || '' );
		img.alt = '';
		img.setAttribute( 'aria-hidden', 'true' );
		mascot.appendChild( img );

		var card = document.createElement( 'div' );
		card.className = 'mapx_nb_card';

		var sr = document.createElement( 'span' );
		sr.className   = 'mapx_nb_sr';
		sr.textContent = this.nbSrText( config );
		card.appendChild( sr );

		var text = document.createElement( 'span' );
		text.className = 'mapx_nb_text';
		card.appendChild( text );

		var close = document.createElement( 'button' );
		close.type      = 'button';
		close.className = 'mapx_nb_close';
		close.setAttribute( 'aria-label', this.noticeText( 'close' ) );
		close.textContent = '✕';
		card.appendChild( close );

		line.appendChild( mascot );
		line.appendChild( card );
	},

	nbSrText: function( config )
	{
		return this.noticeText( 'sr_line' ).replace( '%s', String( config.product || '' ) );
	},

	nbSetBadge: function( line, success )
	{
		var card = line.querySelector( '.mapx_nb_card' );
		var old  = card.querySelector( '.mapx_nb_badge' );

		if( old )
		{
			old.remove();
		}

		if( success != 1 && success != 2 )
		{
			return;
		}

		var badge = document.createElement( 'span' );
		badge.className   = 'mapx_nb_badge ' + ( success == 1 ? 'mapx_proxification_success_true' : 'mapx_proxification_success_false' );
		badge.textContent = success == 1 ? 'OK!' : 'ERROR!';

		card.insertBefore( badge, card.querySelector( '.mapx_nb_close' ) );
	},

	// The announcing region lives outside the bar, which is removed when empty.
	nbAnnounce: function( config, text )
	{
		var status = document.getElementById( 'mapx_nb_status' );

		if( !status )
		{
			status = document.createElement( 'div' );
			status.setAttribute( 'id', 'mapx_nb_status' );
			status.setAttribute( 'role', 'status' );
			status.className = 'mapx_nb_sr';
			( document.body || document.documentElement ).append( status );
		}

		var spoken = this.nbSrText( config ) + '. ' + text;

		window.setTimeout( function() {

			var node = document.createElement( 'span' );
			node.textContent = spoken;
			status.appendChild( node );

			window.setTimeout( function() {
				if( node.parentNode )
				{
					node.remove();
				}
			}, 6000 );

		}, 60 );
	},

	nbWriteLegacy: function( bar, line, key, message, success )
	{
		var safe_message  = String( message || '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );
		var final_message = '<b>' + this.noticeText( 'prefix' ) + '</b> ' + safe_message;

		if( success == 1 )
		{
			final_message = final_message + '&nbsp;<span class="mapx_proxification_success_true">OK!</span>';
		}

		if( success == 2 )
		{
			final_message = final_message + '&nbsp;<span class="mapx_proxification_success_false">ERROR!</span>';
		}

		if( !line )
		{
			line = document.createElement( 'div' );

			if( key )
			{
				line.setAttribute( 'data-map-notice', key );
			}

			bar.append( line );
		}

		line.innerHTML = final_message;
	},

	// Rows left by an older twin have no close button of their own.
	nbRefreshCloseAll: function( bar )
	{
		if( !bar.classList.contains( 'mapx_nb--card' ) )
		{
			return;
		}

		var children = bar.children;
		var orphan   = false;

		for( var i = 0; i < children.length; i++ )
		{
			if( children[ i ].classList.contains( 'mapx_nb_close_all' ) ||
				children[ i ].classList.contains( 'mapx_close_notification_bar' ) )
			{
				continue;
			}

			if( !children[ i ].querySelector( '.mapx_nb_close' ) )
			{
				orphan = true;
				break;
			}
		}

		var legacy = bar.querySelector( ':scope > .mapx_close_notification_bar' );
		var mine   = bar.querySelector( ':scope > .mapx_nb_close_all' );

		if( legacy )
		{
			this.nbPromoteLegacyClose( legacy );
			legacy.hidden = !orphan;

			if( mine )
			{
				mine.remove();
			}

			return;
		}

		if( orphan && !mine )
		{
			mine = document.createElement( 'button' );
			mine.type        = 'button';
			mine.className   = 'mapx_nb_close_all';
			mine.textContent = this.noticeText( 'bar_close_all' );
			bar.append( mine );

			return;
		}

		if( !orphan && mine )
		{
			mine.remove();
		}
	},

	nbPromoteLegacyClose: function( span )
	{
		if( span.getAttribute( 'role' ) === 'button' )
		{
			return;
		}

		span.setAttribute( 'role', 'button' );
		span.setAttribute( 'tabindex', '0' );
		span.setAttribute( 'aria-label', this.noticeText( 'bar_close_all' ) );
	},

	administratorNotices: function()
	{
		try {

			if( typeof MAP.settings !== 'undefined' &&
				MAP.settings.internal_debug
				)
			{
				if( !!MAP?.settings.scan_mode &&
					MAP.settings.scan_mode == 'learning_mode' )
				{
					const this_blocked_friendly_name = [];

					if( map_full_config?.cookie_api_key_remote_id_map_active &&
						typeof map_full_config.cookie_api_key_remote_id_map_active === 'object')
					{
						Object.entries( map_full_config?.cookie_api_key_remote_id_map_active || {} ).
						forEach( ([key, value] ) => {

							const friendlyName = map_full_config?.cookie_api_key_friendly_name_map?.[key];

							if( friendlyName?.desc )
							{
								this_blocked_friendly_name.push( friendlyName?.desc );
							}
						});
					}

					// Include names from the current page load.
					if( typeof CookieShield !== 'undefined' && CookieShield &&
						typeof CookieShield.getDetectedFriendlyNames !== 'undefined' )
					{
						CookieShield.getDetectedFriendlyNames().forEach( function( v ) {
							if( v ) { this_blocked_friendly_name.push( v?.desc ? v.desc : v ); }
						});
					}

					const this_blocked_friendly_name_unique = this_blocked_friendly_name.filter((v, i, a) => a.indexOf(v) === i);

					if( this_blocked_friendly_name_unique.length )
					{
						// Decode HTML entities in names (e.g. titles stored as "&#8211;");
						// showNotificationBar re-escapes any real markup so this stays safe.
						var name_decoder = document.createElement( 'textarea' );
						name_decoder.innerHTML = this_blocked_friendly_name_unique.join( ', ' );

						this.showNotificationBar( this.noticeText( 'detected' ).replace( '%s', name_decoder.value ), null, 'admin' );
					}
					else
					{
						this.showNotificationBar( this.noticeText( 'none' ), null, 'admin' );
					}
				}

				if( !!MAP?.settings.scan_mode &&
					MAP.settings.scan_mode == 'turned_off' )
				{
					this.showNotificationBar( this.noticeText( 'off' ), null, 'admin' );
				}
			}

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//slideUp equivalent function (hide)
	slideUp: function( target, duration=500, callback=null )
	{
		try {

			target.style.transitionProperty = 'height, margin, padding';
			target.style.transitionDuration = duration + 'ms';
			target.style.boxSizing = 'border-box';
			target.style.height = target.scrollHeight + 'px';
			target.offsetHeight;
			target.style.overflow = 'hidden';
			target.style.height = 0;
			target.style.paddingTop = 0;
			target.style.paddingBottom = 0;
			target.style.marginTop = 0;
			target.style.marginBottom = 0;

			window.setTimeout( () => {

				try {

					target.style.display = 'none';
					target.style.removeProperty('height');
					target.style.removeProperty('padding-top');
					target.style.removeProperty('padding-bottom');
					target.style.removeProperty('margin-top');
					target.style.removeProperty('margin-bottom');
					target.style.removeProperty('overflow');
					target.style.removeProperty('transition-duration');
					target.style.removeProperty('transition-property');

				}
				catch( error )
				{
					console.error( error );
				}

			}, duration);

			if( callback )
			{

			}
		}
		catch( error )
		{
			console.error( error );
		}
	 },

	//slideDown equivalent function (show)
	slideDown: function( target, duration=500, callback=null )
	{
		try {

			target.style.removeProperty('display');
			let display = window.getComputedStyle(target).display;

			if( display === 'none' )
			{
				display = 'block';
			}

			target.style.display = display;
			let height = target.scrollHeight;

			target.style.overflow = 'hidden';
			target.style.height = 0;
			target.style.paddingTop = 0;
			target.style.paddingBottom = 0;
			target.style.marginTop = 0;
			target.style.marginBottom = 0;
			target.offsetHeight;
			target.style.boxSizing = 'border-box';
			target.style.transitionProperty = "height, margin, padding";
			target.style.transitionDuration = duration + 'ms';
			target.style.height = height + 'px';
			target.style.removeProperty('padding-top');
			target.style.removeProperty('padding-bottom');
			target.style.removeProperty('margin-top');
			target.style.removeProperty('margin-bottom');

			window.setTimeout( () => {

				try {

					target.style.removeProperty('height');
					target.style.removeProperty('overflow');
					target.style.removeProperty('transition-duration');
					target.style.removeProperty('transition-property');

				}
				catch( error )
				{
					console.error( error );
				}

			}, duration);

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//set overflow height
	setOverflowMaxHeight: function()
	{
		var that = this;

		try {

			var $overflow_container = document.querySelector( '.map-cookielist-overflow-container' );

			if( $overflow_container )
			{
				let $map_tab_container = $overflow_container.parentNode;
				let parentHeight = $map_tab_container.offsetHeight;

				let cookie_list_height = 0;

				Array.from( $overflow_container?.children ).forEach( child => {

					var style = window.getComputedStyle( child );
					var marginTop = parseInt( style.marginTop, 10 );
					var marginBottom = parseInt( style.marginBottom, 10 );
					cookie_list_height += child.offsetHeight + marginTop + marginBottom;

				});

				//add the minimum height of one expanded item
				cookie_list_height += 150;

				let siblingsHeight = 0;

				Array.from( $map_tab_container?.children ).forEach( child => {

					if( child !== $overflow_container )
					{
						var style = window.getComputedStyle( child );
						var marginTop = parseInt( style.marginTop, 10 );
						var marginBottom = parseInt( style.marginBottom, 10 );
						siblingsHeight += child.offsetHeight + marginTop + marginBottom;
					}

				});

				if( parentHeight > cookie_list_height )
				{
					// parent height is too high --> recalculate
					$map_tab_container.style.height = siblingsHeight + cookie_list_height + 'px';
				}
				else
				{
					// need overflow and cookie list height recalculate

					// set calculated max-height to .overflow-cookielist-container
					let maxHeight = parentHeight - siblingsHeight;

					$overflow_container.style.maxHeight = maxHeight + 'px';
				}
			}

		}
		catch( error )
		{
			console.error( error );
		}
	},

	setupAccordion: function()
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function setupAccordion' );

			that.setOverflowMaxHeight();

			that.settingsModal.addEventListener ('click', function( e ) {
				var $this = e.target;
				if( $this.matches( '.map_expandItem' ) )
				{
					var $parent = $this.parentElement;

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'triggered map_expandItem click' );

					e.preventDefault();
					e.stopImmediatePropagation();

					if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + '.map-tab-header click' );

					var $content = $parent.nextElementSibling;

					if( $parent.classList.contains( 'map-tab-active' ) )
					{
						$parent.classList.remove( 'map-tab-active' );
						that.slideUp( $content, 500 );
					}
					else
					{
						if( !$this.classList.contains( 'map-contextual-expansion' ) )
						{
							document.querySelectorAll( '.map-tab-header' ).forEach( function( $_this ) {

								$_this.classList.remove( 'map-tab-active' );
							});

							document.querySelectorAll( '.map-tab-content' ).forEach( function( $_this ) {
								if( $_this !== $content )
								{
									that.slideUp( $_this, 500 );
								}
							});
						}

						$parent.classList.add( 'map-tab-active' );
						that.slideDown( $content, 500 );
					}
				}
			});

		}
		catch( error )
		{
			console.error( error );
		}
	},

	setupSettingsDialog: function()
	{
		//for preserving scope
		var that = this;

		try {

			var modal = that.settingsModal;

			if( !modal ) return;

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function setupSettingsDialog' );

			// cached markup may carry older attributes: always overwrite
			modal.setAttribute( 'tabindex', '-1' );

			var title = modal.querySelector( '.map-privacy-overview .map-h4-heading' );

			if( title )
			{
				title.setAttribute( 'id', 'map-settings-title' );
				modal.setAttribute( 'aria-labelledby', 'map-settings-title' );
			}

			if( that.bar_elm )
			{
				that.bar_elm.querySelectorAll( '.map-accept-button, .map-reject-button, .map-customize-button' ).forEach( function( $_this ) {
					$_this.removeAttribute( 'aria-pressed' );
				});

				var customize = that.bar_elm.querySelector( '.map-customize-button' );

				if( customize )
				{
					customize.setAttribute( 'aria-haspopup', 'dialog' );
				}
			}

			if( !that.isSettingsPopupOpen() )
			{
				that.markSettingsPopupClosed();
			}

			if( modal.getAttribute( 'data-map-dialog-bound' ) === '1' ) return;

			modal.setAttribute( 'data-map-dialog-bound', '1' );

			modal.addEventListener( 'keydown', function( e ) {

				if( !that.isSettingsPopupOpen() ) return;

				if( e.key === 'Escape' || e.key === 'Esc' )
				{
					// the info overlay handles its own Escape
					if( that.isInfoOverlayShown() ) return;

					e.preventDefault();
					that.closeSettingsPopup( 'escape' );
					return;
				}

				if( e.key !== 'Tab' ) return;

				var ends = that.getFocusEnds( modal );

				if( !ends.first )
				{
					e.preventDefault();
					modal.focus();
					return;
				}

				if( e.shiftKey && ( document.activeElement === ends.first || document.activeElement === modal ) )
				{
					e.preventDefault();
					ends.last.focus();
				}
				else if( !e.shiftKey && document.activeElement === ends.last )
				{
					e.preventDefault();
					ends.first.focus();
				}
			});

			document.addEventListener( 'keydown', function( e ) {

				try {

					if( e.defaultPrevented || !that.isSettingsPopupOpen() ) return;

					var is_tab = ( e.key === 'Tab' );
					var is_escape = ( e.key === 'Escape' || e.key === 'Esc' );

					if( !is_tab && !is_escape ) return;

					var active = document.activeElement;
					var on_body = ( !active || active === document.body || active === document.documentElement );

					if( that.isInfoOverlayShown() )
					{
						if( !is_tab ) return;

						var overlay = document.getElementById( 'map-info-overlay' );

						if( on_body )
						{
							e.preventDefault();
							overlay.focus();
							return;
						}

						if( !overlay.contains( active ) ) return;

						var overlay_ends = that.getFocusEnds( overlay );

						if( e.shiftKey && active === overlay )
						{
							e.preventDefault();
							( overlay_ends.last || overlay ).focus();
						}
						else if( !e.shiftKey && ( !overlay_ends.last || active === overlay_ends.last ) )
						{
							e.preventDefault();
							overlay.focus();
						}

						return;
					}

					if( !on_body ) return;

					e.preventDefault();

					if( is_escape )
					{
						that.closeSettingsPopup( 'escape' );
						return;
					}

					var ends = that.getFocusEnds( modal );
					var target = e.shiftKey ? ends.last : ends.first;

					( target || modal ).focus();
				}
				catch( error )
				{
					console.error( error );
				}
			});

			document.addEventListener( 'focusin', function( e ) {

				try {

					if( !that.isSettingsPopupOpen() ) return;

					var overlay = document.getElementById( 'map-info-overlay' );
					var in_overlay = !!( overlay && overlay.contains( e.target ) );

					if( that.isInfoOverlayShown() )
					{
						if( !in_overlay ) overlay.focus();
						return;
					}

					if( in_overlay || modal.contains( e.target ) ) return;

					var ends = that.getFocusEnds( modal );

					// focus that left backwards lands before the dialog in document order
					var backwards = !!( modal.compareDocumentPosition( e.target ) & Node.DOCUMENT_POSITION_PRECEDING );

					( ( backwards ? ends.last : ends.first ) || modal ).focus();
				}
				catch( error )
				{
					console.error( error );
				}
			});

		}
		catch( error )
		{
			console.error( error );
		}
	},

	isSettingsPopupOpen: function()
	{
		return !!( this.settingsModal && this.settingsModal.classList.contains( 'map-show' ) );
	},

	isInfoOverlayShown: function()
	{
		var overlay = document.getElementById( 'map-info-overlay' );

		return !!( overlay && overlay.style.display === 'block' );
	},

	//first and last keyboard-reachable elements, measured on every call
	getFocusEnds: function( root )
	{
		var ends = { first: null, last: null };

		try {

			var nodes = root.querySelectorAll( 'a[href], area[href], button, input, select, textarea, iframe, [tabindex], [contenteditable="true"]' );

			var reachable = function( el ) {
				return el.tabIndex >= 0 &&
					!el.disabled &&
					el.type !== 'hidden' &&
					el.getClientRects().length > 0 &&
					window.getComputedStyle( el ).visibility !== 'hidden';
			};

			for( var i = 0; i < nodes.length; i++ )
			{
				if( reachable( nodes[ i ] ) )
				{
					ends.first = nodes[ i ];
					break;
				}
			}

			for( var j = nodes.length - 1; j >= 0; j-- )
			{
				if( reachable( nodes[ j ] ) )
				{
					ends.last = nodes[ j ];
					break;
				}
			}
		}
		catch( error )
		{
			console.error( error );
		}

		return ends;
	},

	openSettingsPopup: function( opener )
	{
		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function openSettingsPopup' );

			var modal = this.settingsModal;

			if( !modal ) return false;

			if( !this.isSettingsPopupOpen() )
			{
				this.settings_return = opener || document.activeElement;
			}

			// before any click() or focus() on its content
			modal.removeAttribute( 'inert' );
			modal.setAttribute( 'aria-modal', 'true' );

			modal.classList.add( 'map-show' );
			modal.style.opacity = 0;
			modal.offsetWidth; // Trigger a reflow to ensure the transition starts
			modal.style.transition = 'opacity 1s';
			modal.style.opacity = 1;

			modal.classList.remove( 'map-blowup', 'map-out' );
			modal.classList.add( 'map-blowup' );

			document.body.classList.add( 'map-modal-open' );

			var settings_overlay = document.querySelector( '.map-settings-overlay' );

			if( settings_overlay )
			{
				settings_overlay.classList.add( 'map-show' );
			}

			modal.focus();

			this.mapSyncAllSliders();

			return true;
		}
		catch( error )
		{
			console.error( error );
			return false;
		}
	},

	// second layer without tabs, with the IAB pane already active
	isIabPaneWithoutTabs: function()
	{
		var modal = this.settingsModal;

		return !!( modal &&
			modal.querySelector( '.map-consent-extrawrapper' ) &&
			!modal.querySelector( '.map-wrappertab-navigation' ) &&
			modal.querySelector( '#map-privacy-iab-tcf-wrapper.map-wrappertab-active' ) );
	},

	showIabPane: function()
	{
		(async() => {

			try {

				while( !MAP_SYS.map_document_load )
				{
					await new Promise( resolve => setTimeout( resolve, 10 ) );
				}

				// full vendor list rendered before the CMP UI is announced
				if( typeof window.MAPIABTCF_ensureFullInit === 'function' )
				{
					window.MAPIABTCF_ensureFullInit().then( function() {
						MAP.mapSyncAllSliders();

						// only if the IAB pane is still on screen when the load ends
						var iab_on_screen = MAP.isSettingsPopupOpen() &&
							!!document.querySelector( '#map-privacy-iab-tcf-wrapper.map-wrappertab-active' );

						if( iab_on_screen && typeof window.MAPIABTCF_showCMPUI === 'function' )
						{
							window.MAPIABTCF_showCMPUI();
						}
					});
				}
				else if( typeof window.MAPIABTCF_showCMPUI === 'function' )
				{
					window.MAPIABTCF_showCMPUI();
				}
			}
			catch( error )
			{
				console.error( error );
			}

		})();
	},

	markSettingsPopupClosed: function()
	{
		var modal = this.settingsModal;

		if( !modal || this.isSettingsPopupOpen() ) return;

		modal.setAttribute( 'inert', '' );
		modal.removeAttribute( 'aria-modal' );
	},

	closeSettingsPopup: function( why )
	{
		//for preserving scope
		var that = this;

		try {

			if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'internal function closeSettingsPopup' );

			this.settingsModal.classList.remove( 'map-show' );
			this.settingsModal.classList.add( 'map-out' );
			document.body.classList.remove( 'map-modal-open' );
			document.querySelectorAll( '.map-settings-overlay' ).forEach(function( $_this ) {
				$_this.classList.remove( 'map-show' );
			});

			var back = this.settings_return;

			this.settings_return = null;

			var visible = function( el ) {
				return !!( el && el.isConnected && typeof el.focus === 'function' && el.getClientRects().length > 0 );
			};

			// focus leaves the dialog before it becomes inert
			if( why === 'escape' )
			{
				var target = [ back, this.bar_elm ].find( visible );

				if( target )
				{
					target.focus();
				}

				this.markSettingsPopupClosed();

				return;
			}

			// after hideBar() has removed the bar, when the caller hides it
			setTimeout( function() {

				if( that.isSettingsPopupOpen() ) return;

				var widget = that.showagain_elm ? that.showagain_elm.querySelector( '.showConsent' ) : null;
				var target = [ back, widget, that.bar_elm ].find( visible );

				if( target )
				{
					target.focus();
				}
				else if( that.settingsModal.contains( document.activeElement ) )
				{
					document.activeElement.blur();
				}

				that.markSettingsPopupClosed();

			}, 200 );

			(async() => {

				while( !MAP_SYS.map_document_load )
				{
					//MapLogger.log( 'not defined yet' );
					await new Promise( resolve => setTimeout( resolve, 10 ) );
				}

				if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
				{
					window.MAPIABTCF_hideCMPUI();
				}

			})();

		}
		catch( error )
		{
			console.error( error );
		}
	},

	checkJsShield: function()
	{
		try {

			if( !( typeof map_ajax !== 'undefined' && map_ajax?.ajax_url && MAP?.settings ) )
			{
				MapLogger.error( MAP_SYS.maplog + 'Error: missing map_ajax variable running checkJsShield function' );
				return;
			}

			if( typeof CookieShield === 'undefined' ||
				(
					typeof cookie_api_key_remote_id_map_active === 'undefined' ||
					typeof map_full_config?.cookie_api_key_remote_id_map_active === 'undefined' )
			)
			{
				var data = {
					action: 'map_missing_cookie_shield',
					detected: 0,
				};

				MAP_SYS.map_missing_cookie_shield = 1;
			}
			else
			{
				var data = {
					action: 'map_missing_cookie_shield',
					detected: 1,
				};

				MAP_SYS.map_missing_cookie_shield = 0;
			}

			data['map_api_token'] = map_ajax?.api_token || '';

			fetch( map_ajax.ajax_url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: new URLSearchParams( data )
			})
			.then( response => response.text() )
			.then( responseText => {
				MapLogger.debug( MAP_SYS.maplog, responseText );
			})
			.catch( error => console.error( 'Error sending data running checkJsShield function:', error ) );

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//f. for checking Google Consent Mode
	checkConsentModeStatus: function()
	{
		try {

			if( !( typeof map_ajax !== 'undefined' && map_ajax?.ajax_url && MAP?.settings ) )
			{
				console.error( MAP_SYS.maplog + 'Error: missing map_ajax variable running checkConsentModeStatus function' );
				return;
			}

			const googleTagRegex = /^(G-|UA-|AW-)/;
			let is_consent_valid = false;
			let has_valid_google_tag = false;
			let error_motivation = '';
			let error_code = null;

			// Check if Consent Mode is enabled
			if( !MAP_SYS?.cmode_v2 )
			{
				error_motivation = 'Consent Mode V2 not enabled';
				error_code = 10;
			}
			// Check if dataLayer is defined and not null
			else if( typeof dataLayer === 'undefined' || dataLayer === null )
			{
				error_motivation = 'missing dataLayer';
				error_code = 20;
			}
			else
			{
				for( let i = 0; i < dataLayer.length; i++ )
				{
					const item = dataLayer[i];

					if( item && ( Array.isArray( item ) || typeof item === 'object' ) )
					{
						const firstArg = item[0];
						const secondArg = item[1];

						// Check for consent set
						if( firstArg === 'consent' && secondArg === 'default' )
						{
							is_consent_valid = true;
						}

						// Check for Google Tag config
						if( firstArg === 'config' && googleTagRegex.test( secondArg ) )
						{
							has_valid_google_tag = true;
						}

						// Validate that Google Tag configs aren't set before consent
						if( !is_consent_valid )
						{
							if( ( firstArg === 'config' && googleTagRegex.test( secondArg ) ) || firstArg === 'event' )
							{
								error_motivation = `Consent is set after Google tag ${secondArg} `;
								error_code = 30;
								break;
							}
						}
					}
				}

				// Check if consent was never set
				if( !is_consent_valid && error_code === null && MAP_SYS?.cmode_v2_implementation_type != 'gtm' )
				{
					error_motivation = 'No consent set before Google tags';
					error_code = 40;
				}

				// Check if no Google Tag was validated
				if( !has_valid_google_tag && MAP_SYS?.cmode_v2_implementation_type != 'gtm' )
				{
					error_motivation = 'A valid Google Tag seems missing';
					error_code = 50;
				}
			}

			const result = {
				isValid: ( error_code === null ) ? true : false,
				reason: error_motivation,
				code: error_code
			};

			if( result.isValid )
			{
				let the_message = 'The Consent Mode V2 is set up correctly.';

				MapLogger.log( MAP_SYS.maplog + the_message );

				//this.showNotificationBar( the_message, 1 );
			}
			else
			{
				let the_message = 'The sending of consent is not set up correctly - ' + result.reason + '.';

				MapLogger.log( MAP_SYS.maplog + the_message );

				//this.showNotificationBar( the_message, 2 );
			}

			// Prepare data to be sent to the server
			const data = {
				action: 'map_check_consent_mode_status',
				map_api_token: map_ajax?.api_token || '',
				is_consent_valid: ( result.isValid ) ? 1 : 0,
				error_motivation: result.reason,
				error_code: result.code
			};

			// Logic for sending data based on the conditions
			if( ( MAP_SYS.cmode_v2_js_on_error === true && result.isValid ) ||
				( MAP_SYS.cmode_v2_js_on_error === false && !result.isValid )
			)
			{
				fetch( map_ajax.ajax_url, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams(data)
					})
					.then(response => response.text())
					.then(responseText => {
						MapLogger.debug( MAP_SYS.maplog, responseText );
					})
					.catch( error => console.error( 'Error sending data running checkConsentModeStatus function:', error ) );
			}

		}
		catch( error )
		{
			console.error( error );
		}
	},

	sendDetectedKeys: function()
	{
		try {

			if( !( typeof map_ajax !== 'undefined' && map_ajax?.ajax_url && MAP?.settings ) )
			{
				console.error( MAP_SYS.maplog + 'Error: missing map_ajax variable running sendDetectedKeys function' );
				return;
			}

			if( typeof CookieShield !== 'undefined' &&
				CookieShield )
			{
				var detectableKeys = CookieShield.getDetectableKeys();
				var detectedKeys = CookieShield.getDetectedKeys();

				MAP_SYS.map_detectableKeys = detectableKeys;
				MAP_SYS.map_detectedKeys = detectedKeys;

				if( map_ajax )
				{
					var detectableKeys_to_send = null;

					if( detectableKeys && detectableKeys.length > 0 )
					{
						detectableKeys_to_send = detectableKeys.join( ',' );
					}

					var detectedKeys_to_send = null;

					if( detectedKeys && detectedKeys.length > 0 )
					{
						detectedKeys_to_send = detectedKeys.join( ',' );
					}

					var data = {
						action: 'map_save_detected_keys',
						map_api_token: map_ajax?.api_token || '',
						detectableKeys: detectableKeys_to_send,
						detectedKeys: detectedKeys_to_send
					};

					fetch(map_ajax.ajax_url, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams( data )
					})
					.then( response => response.text() )
					.then( responseText => {
						MapLogger.groupCollapsed( MAP_SYS.maplog + 'sendDetectedKeys detectableKeys=' + detectableKeys_to_send + ' , detectedKeys=' + detectedKeys_to_send + ' with response :' );
						MapLogger.debug( MAP_SYS.maplog, responseText );
						MapLogger.groupEnd();
					});
				}
			}

		}
		catch( error )
		{
			console.error( error );
		}
	},

	sendDiagnosticData: function( is_probe )
	{
	    var is_probe = !!is_probe;

	    try
	    {
	        if( !( typeof map_ajax !== 'undefined' && map_ajax?.api_url && map_ajax?.api_token && MAP?.settings ) )
	        {
	            console.error( MAP_SYS.maplog + 'Error: missing map_ajax variable running sendDiagnosticData function' );
	            return Promise.reject( 'missing map_ajax' );
	        }

	        // --- 1. Keys ---
	        var detectableKeys_to_send = null;
	        var detectedKeys_to_send   = null;
	        var send_detected_keys     = 0;

	        if(
	            MAP.settings &&
	            (
	                ( MAP.settings.scan_mode && MAP.settings.scan_mode == 'learning_mode' ) ||
	                ( map_ajax.force_js_learning_mode == 1 )
	            )
	        )
	        {
	            send_detected_keys = 1;

	            if( typeof CookieShield !== 'undefined' && CookieShield )
	            {
	                var detectableKeys = CookieShield.getDetectableKeys();
	                var detectedKeys   = CookieShield.getDetectedKeys();

	                MAP_SYS.map_detectableKeys = detectableKeys;
	                MAP_SYS.map_detectedKeys   = detectedKeys;

	                if( detectableKeys && detectableKeys.length > 0 )
	                    detectableKeys_to_send = detectableKeys.join(',');

	                if( detectedKeys && detectedKeys.length > 0 )
	                    detectedKeys_to_send = detectedKeys.join(',');
	            }
	        }

	        // --- 2. JS SHIELD ---
	        var cookie_shield_detected = 0;

	        if(
	            typeof CookieShield !== 'undefined' &&
	            CookieShield &&
	            typeof map_full_config?.cookie_api_key_remote_id_map_active !== 'undefined'
	        )
	        {
	            cookie_shield_detected            = 1;
	            MAP_SYS.map_missing_cookie_shield = 0;
	        }
	        else
	        {
	            MAP_SYS.map_missing_cookie_shield = 1;
	        }

	        // --- 3. CONSENT MODE ---
	        const googleTagRegex = /^(G-|UA-|AW-)/;
	        var is_consent_valid     = false;
	        var has_valid_google_tag = false;
	        var error_motivation     = '';
	        var error_code           = null;

	        if( !MAP_SYS?.cmode_v2 )
	        {
	            error_motivation = 'Consent Mode V2 not enabled';
	            error_code = 10;
	        }
	        else if( typeof dataLayer === 'undefined' || dataLayer === null )
	        {
	            error_motivation = 'missing dataLayer';
	            error_code = 20;
	        }
	        else
	        {
	            for( var i = 0; i < dataLayer.length; i++ )
	            {
	                var item = dataLayer[i];

	                if( item && ( Array.isArray( item ) || typeof item === 'object' ) )
	                {
	                    var firstArg  = item[0];
	                    var secondArg = item[1];

	                    if( firstArg === 'consent' && secondArg === 'default' )
	                        is_consent_valid = true;

	                    if( firstArg === 'config' && googleTagRegex.test( secondArg ) )
	                        has_valid_google_tag = true;

	                    if( !is_consent_valid )
	                    {
	                        if( ( firstArg === 'config' && googleTagRegex.test( secondArg ) ) || firstArg === 'event' )
	                        {
	                            error_motivation = 'Consent is set after Google tag ' + secondArg;
	                            error_code = 30;
	                            break;
	                        }
	                    }
	                }
	            }

	            if( !is_consent_valid && error_code === null && MAP_SYS?.cmode_v2_implementation_type != 'gtm' )
	            {
	                error_motivation = 'No consent set before Google tags';
	                error_code = 40;
	            }

	            if( !has_valid_google_tag && MAP_SYS?.cmode_v2_implementation_type != 'gtm' )
	            {
	                error_motivation = 'A valid Google Tag seems missing';
	                error_code = 50;
	            }
	        }

	        var consent_is_valid_int = ( error_code === null ) ? 1 : 0;

	        if( consent_is_valid_int )
	            MapLogger.log( MAP_SYS.maplog + 'The Consent Mode V2 is set up correctly.' );
	        else
	            MapLogger.log( MAP_SYS.maplog + 'The sending of consent is not set up correctly - ' + error_motivation + '.' );

	        // --- 4. PAYLOAD ---
	        var data = {
	            action             		: 'map_diagnostic_data',
	            map_api_token      		: map_ajax.api_token,
	            send_detected_keys 		: send_detected_keys,
	            detectableKeys     		: detectableKeys_to_send,
	            detectedKeys       		: detectedKeys_to_send,
	            cookie_shield_detected 	: cookie_shield_detected,
	            is_consent_valid   		: consent_is_valid_int,
	            error_motivation   		: error_motivation,
	            error_code         		: error_code !== null ? error_code : ''
	        };

	        return fetch( map_ajax.api_url, {
	            method: 'POST',
	            headers: {
	                'Content-Type': 'application/x-www-form-urlencoded'
	            },
	            body: new URLSearchParams( data )
	        })
	        .then( response => {
	            if( !response.ok )
	            {
	                throw new Error( 'HTTP error ' + response.status );
	            }
	            return response.text();
	        })
	        .then( responseText => {
	            MapLogger.groupCollapsed( MAP_SYS.maplog + 'sendDiagnosticData response:' );
	            MapLogger.debug( MAP_SYS.maplog, responseText );
	            MapLogger.groupEnd();
	        });

	    }
	    catch( error )
	    {
	        console.error( error );
	        return Promise.reject( error );
	    }
	},

	//inspect cookie script configuration
	debugCookieScripts: function()
	{
		try {

			var list = [];

			var $scripts = document.querySelectorAll( 'script.my_agile_privacy_activate._js_noscript_type_mode, textarea.my_agile_privacy_activate._raw_type_mode');

			$scripts.forEach(function($this) {

				var cookie_name = $this.getAttribute( 'data-cookie-name' );
				var cookie_api_key = $this.getAttribute( 'data-cookie-api-key' );

				var code = null;
				var mode = null;

				if( $this.classList.contains( '_js_noscript_type_mode' ) ) {
					mode = 'js_noscript';
					code = $this.innerHTML;
				}

				if( $this.classList.contains( '_raw_type_mode' ) ) {
					mode = 'raw';
					code = $this.firstChild.nodeValue;
				}

				var object = {
					'cookie_name': cookie_name,
					'cookie_api_key': cookie_api_key,
					'mode': mode,
					'code': code
				};

				list.push( object );

			});

			return list;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//get list of available cookies
	getAvailableCookieList: function()
	{
		try {

			var list = [];

			var $my_agile_privacy_activated = document.querySelectorAll( '.map_cookie_description_wrapper' );

			$my_agile_privacy_activated.forEach( function( $this ) {

				var cookie_name = $this.getAttribute( 'data-cookie-name' );
				var cookie_api_key = $this.getAttribute( 'data-cookie-api-key' );

				if( cookie_api_key )
				{
					list.push( cookie_api_key );
				}
				else
				{
					list.push( cookie_name );
				}

			});

			return list;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//get list of activated cookies
	getActivatedCookiesList: function()
	{
		try {

			var list = [];

			var $my_agile_privacy_activated = document.querySelectorAll( '.map_cookie_description_wrapper._is_activated' );

			$my_agile_privacy_activated.forEach(function( $this ) {

				var cookie_name = $this.getAttribute( 'data-cookie-name' );
				var cookie_api_key = $this.getAttribute( 'data-cookie-api-key' );

				if( cookie_api_key )
				{
					list.push( cookie_api_key );
				}
				else
				{
					list.push( cookie_name );
				}

			});

			return list;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	//get list of disactivated cookies
	getDisactivatedCookiesList: function()
	{
		try {

			var list = [];

			var $my_agile_privacy_activated = document.querySelectorAll( '.map_cookie_description_wrapper:not(._is_activated)' );

			$my_agile_privacy_activated.forEach( function( $this ) {

				var cookie_name = $this.getAttribute( 'data-cookie-name' );
				var cookie_api_key = $this.getAttribute( 'data-cookie-api-key' );

				if( cookie_api_key )
				{
					list.push( cookie_api_key );
				}
				else
				{
					list.push( cookie_name );
				}

			});

			return list;

		}
		catch( error )
		{
			console.error( error );
		}
	},

	// Sync .map-slider-on CSS class with hidden input value
	mapSyncSlider: function( input )
	{
		if( !input ) return;
		var slider = input.nextElementSibling;
		if( slider && slider.classList.contains( 'map-slider' ) )
		{
			if( input.value === '1' )
			{
				slider.classList.add( 'map-slider-on' );
			}
			else
			{
				slider.classList.remove( 'map-slider-on' );
			}

			MAP.mapSyncAria( input, slider );

			// the IAB bundle changes map-flags right after this call
			Promise.resolve().then( function() { MAP.mapSyncAria( input, slider ); } );
		}
	},

	// aria-checked from the hidden input value
	mapSyncAria: function( input, slider )
	{
		try {

			var state = 'false';

			if( input.value === '1' )
			{
				state = input.classList.contains( 'map-flags' ) ? 'mixed' : 'true';
			}

			if( slider.getAttribute( 'aria-checked' ) !== state )
			{
				slider.setAttribute( 'aria-checked', state );
			}
		}
		catch( error )
		{
			console.error( error );
		}
	},

	// aria-checked of every switch in the settings popup
	mapSyncAllSliders: function()
	{
		try {

			var modal = this.settingsModal;

			if( !modal ) return;

			modal.querySelectorAll( '.map-slider[role="checkbox"]' ).forEach( function( slider ) {

				var input = slider.previousElementSibling;

				if( !input || input.tagName !== 'INPUT' )
				{
					var mapForClass = [...slider.classList].find( cls => cls.startsWith( 'map-for-' ) );
					input = mapForClass ? document.getElementById( mapForClass.replace( 'map-for-', '' ) ) : null;
				}

				if( input )
				{
					MAP.mapSyncAria( input, slider );
				}
			});
		}
		catch( error )
		{
			console.error( error );
		}
	},

	// entry point called after geo config is copied into MAP_SYS

	/**
	 * Geo init: reads the geo cookie or resolves it, then applies the regime.
	 */
	mapGeoInit: function()
	{
		var that = this;

		try
		{
			MapLogger.log( 'MAP-GEO', 'mapGeoInit: start' );

			if( ! MAP_SYS.geo_enabled ) { MapLogger.log( 'MAP-GEO', 'mapGeoInit: geo_enabled=false → abort' ); return; }

			// optional geo re-resolution path.
			var testParam = that.mapGeoTestParam();
			if( testParam )
			{
				MapLogger.log( 'MAP-GEO', 'mapGeoInit: test override = ' + testParam + ' → bypass cookie, force resolve' );
				that.mapGeoResolve();
				return;
			}

			var cookieVal = MAP_Cookie.read( 'map_geo' );

			if( cookieVal )
			{
				var parsed = null;
				try { parsed = JSON.parse( cookieVal ); } catch(e) {}

				if( parsed && parsed.country && parsed.native )
				{
					MAP_SYS.geo_country  = parsed.country;
					MAP_SYS.geo_native   = parsed.native;
					MAP_SYS.geo_resolved = true;
					MapLogger.log( 'MAP-GEO', 'mapGeoInit: cookie present → use it', parsed );
					that.mapGeoApply();
					return;
				}
				MapLogger.log( 'MAP-GEO', 'mapGeoInit: cookie present but invalid → resolve via endpoint', cookieVal );
			}
			else
			{
				MapLogger.log( 'MAP-GEO', 'mapGeoInit: no cookie → resolve via endpoint' );
			}

			// No valid cookie: resolve.
			that.mapGeoResolve();
		}
		catch( error ) { MapLogger.log( 'MAP-GEO', 'mapGeoInit: ERROR', error ); console.error( error ); }
	},

	/**
	 * Returns the optional country override query parameter (uppercased), or '' when absent.
	 */
	mapGeoTestParam: function()
	{
		try {
			var m = window.location.search.match( /[?&]map_geo_test=([a-zA-Z]{2})\b/ );
			return m ? m[1].toUpperCase() : '';
		} catch( e ) { return ''; }
	},

	/**
	 * Applies the default regime and finalizes the banner.
	 */
	mapGeoFailClosed: function()
	{
		MAP_SYS.geo_country  = 'unknown';
		MAP_SYS.geo_native   = 'block';
		MAP_SYS.geo_eff_type = 'block';
		MAP_SYS.geo_resolved = true;
		MapLogger.log( 'MAP-GEO', 'mapGeoFailClosed: geo resolution failed → block (opt-in)' );

		this.mapGeoWithdrawImplicit();

		if( MAP_SYS.geo_bar_deferred )
		{
			MAP_SYS.geo_bar_deferred = false;
			MapLogger.log( 'MAP-GEO', 'mapGeoFailClosed: finalizing deferred banner (toggleBar → block)' );
			this.toggleBar();
		}
	},

	mapGeoResolve: function()
	{
		var that = this;

		if( ! ( typeof map_ajax !== 'undefined' && map_ajax && map_ajax.advanced_api_url && map_ajax.api_token ) )
		{
			MapLogger.log( 'MAP-GEO', 'mapGeoResolve: missing map_ajax config (advanced_api_url/api_token) → fail-closed (block)' );
			that.mapGeoFailClosed();
			return;
		}

		var test     = that.mapGeoTestParam();
		var testQs   = test ? ( 'map_geo_test=' + encodeURIComponent( test ) ) : '';
		var apiUrl   = test ? ( map_ajax.advanced_api_url + ( map_ajax.advanced_api_url.indexOf('?') === -1 ? '?' : '&' ) + testQs ) : map_ajax.advanced_api_url;

		var budgetMs = 3000;
		var apiMs    = 1500;

		// one outcome per page; a valid answer that arrives later is kept for the next page
		var settled    = false;
		var applied    = false;
		var late       = null;
		var lateSource = '';
		var guard      = null;

		var finish = function( fn )
		{
			if( settled ) { return; }
			settled = true;
			clearTimeout( guard );
			fn();
		};

		var failClosed = function()
		{
			finish( function() { that.mapGeoFailClosed(); } );
		};

		var keepLate = function( data, source )
		{
			if( ! ( data && data.country && data.native ) ) { MapLogger.log( 'MAP-GEO', 'mapGeoResolve: late ' + source + ' answer invalid → ignored', data ); return; }

			if( lateSource === 'wp-ajax' && source !== 'wp-ajax' ) { MapLogger.log( 'MAP-GEO', 'mapGeoResolve: late ' + source + ' answer ignored, wp-ajax answer already kept', data ); return; }

			MapLogger.log( 'MAP-GEO', 'mapGeoResolve: late ' + source + ' answer kept for the next page, not applied', data );

			if( late === null )
			{
				window.addEventListener( 'pagehide', function()
				{
					if( applied || ! late ) { return; }
					try { MAP_Cookie.set( 'map_geo', JSON.stringify( { country: late.country, native: late.native } ), 7 ); }
					catch( e ) { MapLogger.debug( e ); }
				} );
			}

			late       = data;
			lateSource = source;
		};

		var settle = function( data, source )
		{
			if( settled ) { keepLate( data, source ); return; }
			applied = !! ( data && data.country && data.native );
			finish( function() { that.mapGeoHandleResult( data ); } );
		};

		guard = setTimeout( function()
		{
			if( settled ) { return; }
			MapLogger.log( 'MAP-GEO', 'mapGeoResolve: timeout after ' + budgetMs + ' ms → fail-closed (block)' );
			failClosed();
		}, budgetMs );

		var viaAjax = function()
		{
			if( ! map_ajax.ajax_url ) { MapLogger.log( 'MAP-GEO', 'mapGeoResolve: no ajax_url → fail-closed (block)' ); failClosed(); return; }

			var ajaxUrl = test ? ( map_ajax.ajax_url + ( map_ajax.ajax_url.indexOf('?') === -1 ? '?' : '&' ) + testQs ) : map_ajax.ajax_url;

			MapLogger.log( 'MAP-GEO', 'mapGeoResolve: calling wp-ajax', { url: ajaxUrl, test: test || '(none)' } );

			fetch( ajaxUrl, {
				method  : 'POST',
				headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
				body    : new URLSearchParams( {
					action        : 'map_advanced_resolve_geo',
					map_api_token : map_ajax.api_token
				} )
			} )
			.then( function( resp ) { return resp.json(); } )
			.then( function( data ) { MapLogger.log( 'MAP-GEO', 'mapGeoResolve: wp-ajax OK', data ); settle( data, 'wp-ajax' ); } )
			.catch( function( err2 ) { MapLogger.log( 'MAP-GEO', 'mapGeoResolve: wp-ajax failed → fail-closed (block)', String( err2 ) ); failClosed(); } );
		};

		// the fast endpoint failed on an earlier page of this browser
		if( MAP_Cookie.read( MAP_GEO_TRANSPORT_COOKIE_NAME ) )
		{
			MapLogger.log( 'MAP-GEO', 'mapGeoResolve: fast endpoint marked as failing → wp-ajax' );
			viaAjax();
			return;
		}

		MapLogger.log( 'MAP-GEO', 'mapGeoResolve: calling advanced-api.php', { url: apiUrl, test: test || '(none)' } );

		var apiOver    = false;
		var apiTimer   = null;
		var controller = ( typeof AbortController !== 'undefined' ) ? new AbortController() : null;

		var apiFailed = function( why )
		{
			if( apiOver ) { return; }
			apiOver = true;
			clearTimeout( apiTimer );

			MapLogger.log( 'MAP-GEO', 'mapGeoResolve: advanced-api.php failed → fallback wp-ajax', why );

			// remembered in the browser, so the next pages go straight to the fallback route
			MAP_Cookie.set( MAP_GEO_TRANSPORT_COOKIE_NAME, '1', 7 );

			viaAjax();
		};

		apiTimer = setTimeout( function()
		{
			apiFailed( 'timeout after ' + apiMs + ' ms' );
			if( controller ) { try { controller.abort(); } catch( e ) { MapLogger.debug( e ); } }
		}, apiMs );

		var apiOptions = {
			method  : 'POST',
			headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
			body    : new URLSearchParams( {
				action        : 'map_resolve_geo',
				map_api_token : map_ajax.api_token
			} )
		};

		if( controller ) { apiOptions.signal = controller.signal; }

		fetch( apiUrl, apiOptions )
		.then( function( resp ) {
			if( ! resp.ok ) { throw new Error( 'advanced-api.php ' + resp.status ); }
			return resp.json();
		} )
		.then( function( data ) {
			if( apiOver ) { keepLate( data, 'advanced-api.php' ); return; }
			apiOver = true;
			clearTimeout( apiTimer );
			MapLogger.log( 'MAP-GEO', 'mapGeoResolve: advanced-api.php OK', data );
			settle( data, 'advanced-api.php' );
		} )
		.catch( function( err ) { apiFailed( String( err ) ); } );
	},

	/**
	 * Processes the endpoint response, writes the cookie, and applies the regime.
	 *
	 * @param {Object} data  { country: string, native: 'block'|'noblock' }
	 */
	mapGeoHandleResult: function( data )
	{
		try
		{
			if( ! data || ! data.country || ! data.native )
			{
				MapLogger.log( 'MAP-GEO', 'mapGeoHandleResult: invalid result → fail-closed (block, no cookie)', data );
				this.mapGeoFailClosed();
				return;
			}

			MAP_SYS.geo_country  = data.country;
			MAP_SYS.geo_native   = data.native;
			MAP_SYS.geo_resolved = true;

			// Write the geo cookie.
			MAP_Cookie.set( 'map_geo', JSON.stringify( { country: data.country, native: data.native } ), 7 );
			MapLogger.log( 'MAP-GEO', 'mapGeoHandleResult: cookie map_geo written', { country: data.country, native: data.native } );

			this.mapGeoApply();
		}
		catch( error ) { MapLogger.log( 'MAP-GEO', 'mapGeoHandleResult: ERROR', error ); console.error( error ); }
	},

	/**
	 * Computes effType and, when no-block, sets implicit consent + unblocks scripts.
	 * GPC and pre-existing opt-out take absolute precedence.
	 */
	mapGeoApply: function()
	{
		try
		{
			if( ! MAP_SYS.geo_resolved ) { MapLogger.log( 'MAP-GEO', 'mapGeoApply: not resolved yet → abort' ); return; }

			if( this.isSplitPreview() ) { MapLogger.log( 'MAP-GEO', 'mapGeoApply: preview → abort' ); return; }

			MAP_SYS.geo_eff_type = this.mapGeoEffType( MAP_SYS.geo_country, MAP_SYS.geo_native );

			MapLogger.log( 'MAP-GEO', 'mapGeoApply', {
				country     : MAP_SYS.geo_country,
				native      : MAP_SYS.geo_native,
				sede_strong : MAP_SYS.geo_sede_strong,
				force_optout: MAP_SYS.geo_force_optout,
				effType     : MAP_SYS.geo_eff_type
			} );

			if( MAP_SYS.geo_eff_type === 'noblock' )
			{
				// a refusal saved under the earlier cookie name counts as well
				var legacyOptOut = this.legacyRefusal();

				// Absolute precedence: respect pre-existing opt-out or GPC signal.
				if( MAP_Cookie.read( MAP_ACCEPTED_ALL_COOKIE_NAME ) === '-1' )
				{
					MapLogger.log( 'MAP-GEO', 'mapGeoApply: no-block but pre-existing opt-out (-1) → respect, no unblock' );
				}
				else if( legacyOptOut )
				{
					MapLogger.log( 'MAP-GEO', 'mapGeoApply: no-block but opt-out (-1) under the earlier name → respect, no unblock' );
					this.applyRefusalSignals();
				}
				else if( typeof navigator !== 'undefined' && navigator.globalPrivacyControl === true )
				{
					MapLogger.log( 'MAP-GEO', 'mapGeoApply: no-block but GPC active → respect opt-out, no unblock' );
				}
				else if( MAP_Cookie.read( MAP_ACCEPTED_ALL_COOKIE_NAME ) === '1' )
				{
					// Already in implicit-consent state (return visit) - nothing to do;
					// the UI/consent were set on the first visit.
					MapLogger.log( 'MAP-GEO', 'mapGeoApply: no-block, implicit consent already set (return visit) → no action' );
				}
				else
				{
					// opt-out zone: apply implicit accept-all via existing flow.
					var accepted = false;
					try {
						if( this.accept_button && typeof this.accept_button.click === 'function' )
						{
							MapLogger.log( 'MAP-GEO', 'mapGeoApply: no-block → implicit accept-all (triggering Accept button flow: granular consent + gtag update + unblock)' );
							this.accept_button.click();
							accepted = true;
							// accept_close() (run by the button) already finalized banner + widget.
							MAP_SYS.geo_bar_deferred = false;
						}
					}
					catch( e ) { MapLogger.log( 'MAP-GEO', 'mapGeoApply: accept button flow failed → fallback', e ); }

					if( ! accepted )
					{
						// Fallback: minimal implicit consent (no granular UI sync).
						MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );
						var implicitGConsent = this.consentWithoutSwitches( 'google', 'accept' );
						if( !implicitGConsent )
						{
							implicitGConsent = {
								ad_storage          : 'granted',
								ad_user_data        : 'granted',
								ad_personalization  : 'granted',
								analytics_storage   : 'granted'
							};
						}
						var recorded = false;
						if( Object.keys( implicitGConsent ).length )
						{
							implicitGConsent = { ...( MAP_SYS.current_gconsent || {} ), ...implicitGConsent };
							this.saveGoogleConsentStatusToCookie( implicitGConsent );
							recorded = this.updateGoogleConsentbyObj( { ...implicitGConsent }, false );
						}
						if( recorded !== true ) this.updateLastConsentRecords();
						MapLogger.log( 'MAP-GEO', 'mapGeoApply: no-block → implicit consent set (fallback minimal), unblocking scripts' );
						this.tryToUnblockScripts( true, null );
					}

					// marks the consent as implicit with the date of the choice just recorded
					var implicitDate = MAP_Cookie.read( MAP_LAST_CONSENT_MODIFY_DATE );
					if( implicitDate ) MAP_Cookie.set( map_implicit_consent_name(), implicitDate, MAP_SYS.map_cookie_expire );
				}
			}
			else
			{
				MapLogger.log( 'MAP-GEO', 'mapGeoApply: effType=block → prior blocking stays' );

				this.mapGeoWithdrawImplicit();
			}

			// Finalize the banner now that the geo zone is known. Only when toggleBar()
			// deferred during init (first visit) and the accept flow above did not already
			// handle it. On return visits the cookie is read synchronously before
			// toggleBar(), so init handles it and no re-call is needed.
			if( MAP_SYS.geo_bar_deferred )
			{
				MAP_SYS.geo_bar_deferred = false;
				MapLogger.log( 'MAP-GEO', 'mapGeoApply: finalizing deferred banner (toggleBar)' );
				this.toggleBar();
			}
		}
		catch( error ) { MapLogger.log( 'MAP-GEO', 'mapGeoApply: ERROR', error ); console.error( error ); }
	},

	// an implicit consent does not hold outside a no-block zone
	mapGeoWithdrawImplicit: function()
	{
		if( this.isSplitPreview() ) return;

		if( map_implicit_consent_valid() && this.revokeImplicitConsent() && ! MAP_SYS.geo_bar_deferred )
		{
			this.toggleBar();
		}
	},

	/**
	 * Derives the effective consent regime from the resolved geo data.
	 *
	 * @param {string} country  ISO alpha-2 lowercase
	 * @param {string} native   'block' | 'noblock'
	 * @returns {string}  'block' | 'noblock'
	 */
	mapGeoEffType: function( country, native )
	{
		var result;
		if( native === 'block' ) { result = 'block'; }
		else if( ! MAP_SYS.geo_sede_strong ) { result = 'noblock'; }
		else if( MAP_SYS.geo_force_optout && MAP_SYS.geo_force_optout[ country ] ) { result = 'noblock'; }
		else { result = 'block'; }
		MapLogger.log( 'MAP-GEO', 'mapGeoEffType(' + country + ', ' + native + ') → ' + result +
			' [sede_strong=' + MAP_SYS.geo_sede_strong + ', forced=' + !!( MAP_SYS.geo_force_optout && MAP_SYS.geo_force_optout[ country ] ) + ']' );
		return result;
	}
}

document.addEventListener( 'DOMContentLoaded', function() {

	try {

		MAP_DCL_REPLICA.start();

		if( typeof map_cookiebar_settings !== 'undefined' )
		{
			map_init_banner();
		}

		setTimeout( function() {

			if( !MAP_SYS.map_initted &&
				typeof map_cookiebar_settings !== 'undefined' )
			{
				try {

					map_init_banner();

				}
				catch( error )
				{
					console.error( error );
				}
			}

		}, 3000 );

		setTimeout( function() {

			MAP_SYS.map_document_load = true;

		}, 5000 );

	}
	catch( error )
	{
		console.error( error );
	}

});

//initializes the banner, waiting for the configuration to be settled
function map_init_banner()
{
	if( MAP_SYS.map_initted )              { return; }
	if( MAP_SYS.map_banner_ready_waiting ) { return; }

	var ready = window.map_banner_ready;

	if( ready && typeof ready.then === 'function' && !MAP_SYS.map_banner_ready_done )
	{
		MAP_SYS.map_banner_ready_waiting = true;

		var done = function() {

			try {

				MAP_SYS.map_banner_ready_waiting = false;
				MAP_SYS.map_banner_ready_done    = true;

				map_init_banner();

			}
			catch( error )
			{
				console.error( error );
			}
		};

		Promise.race( [ ready, new Promise( function( resolve ) { setTimeout( resolve, 1000 ); } ) ] ).then( done, done );

		return;
	}

	if( typeof map_cookiebar_settings === 'undefined' ) { return; }

	if( MAP_SYS?.map_debug ) MapLogger.debug( MAP_SYS.maplog + 'initting' );

	MAP.set({
		settings : map_cookiebar_settings
	});

	MAP_SYS.map_initted = true;

	if( MAP_SYS.map_document_load ) { map_after_init_tasks(); }
}

//tasks that need both the document loaded and the banner initted
function map_after_init_tasks()
{
	if( MAP_SYS.map_after_init_done ) { return; }

	MAP_SYS.map_after_init_done = true;

	if( MAP.isPostMode() ) { return; }

	try {

		if(
			map_ajax &&
			MAP.settings
		)
		{
			if( MAP_SYS?.allow_js_fast_callback && ! MAP_Cookie.read( MAP_TRANSPORT_COOKIE_NAME ) )
			{
			    setTimeout( function() {

			        MAP.sendDiagnosticData()
			            .catch( function() {

			                // Remembered in the browser, so the next page goes straight
			                // to the fallback route instead of trying again.
			                MAP_Cookie.set( MAP_TRANSPORT_COOKIE_NAME, '1', 7 );

			                // Fallback: send data the old way
			                if(
			                    (
			                        MAP?.settings?.scan_mode &&
			                        MAP?.settings?.scan_mode == 'learning_mode'
			                    ) ||
			                    (
			                        map_ajax?.force_js_learning_mode == 1
			                    )
			                )
			                {
			                    MAP.sendDetectedKeys();
			                    MAP.checkJsShield();
			                    MAP.checkConsentModeStatus();
			                }
			            });

			    }, 500 );
			}
			else
			{
				if(
					(
					 MAP?.settings?.scan_mode &&
					 MAP?.settings?.scan_mode == 'learning_mode'
					) ||
					(
						map_ajax?.force_js_learning_mode == 1
					)
				)
				{
					MAP.sendDetectedKeys();

					setTimeout( function() {
						MAP.checkJsShield();
						MAP.checkConsentModeStatus();
					}, 800 );
				}

				setTimeout( function() {
					// availability check of the API endpoint.
					MAP.sendDiagnosticData( true ).catch( function() { /* silent */ } );
				}, 800 );
			}
		}

		// Re-evaluate the admin notice once everything has loaded.
		MAP.administratorNotices();
	}
	catch( error )
	{
		console.error( error );
	}
}

//triggered on document load
function map_on_document_load_event()
{
	try {

		MAP_DCL_REPLICA.start();

		MAP_SYS.map_document_load = true;

		if( !MAP_SYS.map_initted &&
				typeof map_cookiebar_settings !== 'undefined' )
		{
			try {

				map_init_banner();

			}
			catch( error )
			{
				console.error( error );
			}
		}

		if( MAP_SYS.map_initted ) { map_after_init_tasks(); }

	}
	catch( error )
	{
		console.error( error );
	}
}

window.addEventListener( 'load', function() {
	if( typeof window.map_on_document_load_event === 'function' )
	{
	    window.map_on_document_load_event();
	}
});

//f. for cloning node attribute to another (only valid attributes)
function cloneNodeAttributeToAnother( $source, dest )
{
	//MapLogger.log( $source );
	//MapLogger.log( dest );

	var exclusion_list = [
		'type',
		'src',
		'unblocked_src',
		'class',
		'data-cookie-api-key',
		'data-friendly_name',
		'data-map-held-type',
		//a marker that belongs to the original node only
		'data-mapx-gads',
		'data-map-static-block-processed'
	];

	for( var att, i = 0, atts = $source.attributes, n = atts.length; i < n; i++ )
	{
		att = atts[i];

		if( typeof att.nodeName !== 'undefined' && !exclusion_list.includes( att.nodeName ) )
		{
			dest.setAttribute( att.nodeName, att.nodeValue );
		}
	}

	return dest;
}

//f. animation through anime when present, final state otherwise
function map_animate( params )
{
	if( typeof anime === 'function' ) return anime( params );

	try {
		var targets = params.targets;
		var elems = [];

		if( typeof targets === 'string' ) elems = document.querySelectorAll( targets );
		else if( targets && typeof targets.length === 'number' && !targets.nodeType ) elems = targets;
		else if( targets ) elems = [ targets ];

		for( var i = 0; i < elems.length; i++ )
		{
			var el = elems[ i ];
			if( !el || !el.style ) continue;

			[ 'opacity', 'top', 'left', 'right', 'bottom' ].forEach( function( prop ) {
				if( typeof params[ prop ] !== 'undefined' ) el.style[ prop ] = String( params[ prop ] );
			} );

			if( typeof params.scale !== 'undefined' ) el.style.transform = 'scale(' + params.scale + ')';
		}

		if( typeof params.complete === 'function' ) params.complete( null );
	}
	catch( e )
	{
		console.debug( e );
	}

	return null;
}

//f. stops running animations when anime is present
function map_animate_remove( targets )
{
	if( typeof anime === 'function' && typeof anime.remove === 'function' ) anime.remove( targets );
}

//replays the DOMContentLoaded calls of unblocked scripts to late registrants only
var MAP_DCL_REPLICA = ( function() {
	'use strict';

	var registry = [];
	var installed = false;
	var passthrough = false;
	var permanent = false;
	var tried = false;
	var open = 0;
	var mine = {};

	var marks = window.MAP_DCL_MARKS;
	if( !( marks && typeof marks === 'object' && marks.seen && typeof marks.seen === 'object' ) )
	{
		marks = window.MAP_DCL_MARKS = { seen: { doc: false, win: false } };
	}
	var seen = marks.seen;

	function hasOwn( o, k ) { return Object.prototype.hasOwnProperty.call( o, k ); }

	//original method, resolved up the target's prototype chain at call time
	function original( t, k )
	{
		var p = null;
		try {
			p = Object.getPrototypeOf( t );
			while( p && !hasOwn( p, k ) ) p = Object.getPrototypeOf( p );
		} catch( e ) { p = null; }
		return p ? p[ k ] : EventTarget.prototype[ k ];
	}

	function nativeAdd( t, type, l, o ) { return original( t, 'addEventListener' ).call( t, type, l, o ); }
	function nativeRemove( t, type, l, o ) { return original( t, 'removeEventListener' ).call( t, type, l, o ); }

	function captureOf( o )
	{
		if( o === null || o === undefined ) return false;
		if( typeof o === 'object' || typeof o === 'function' ) return !!o.capture;
		return !!o;
	}
	function isListener( l ) { return typeof l === 'function' || ( !!l && typeof l === 'object' ); }
	function aborted( r ) { return !!( r.signal && r.signal.aborted ); }
	function mark( t ) { if( t === document ) seen.doc = true; else if( t === window ) seen.win = true; }

	function report( err )
	{
		try {
			if( typeof window.reportError === 'function' ) { window.reportError( err ); return; }
		} catch( e ) {}
		setTimeout( function() { throw err; }, 0 );
	}

	//an entry leaves the registry with its abort listener and, when asked, its consumer
	function drop( r, withConsumer )
	{
		var i = registry.indexOf( r );
		if( i >= 0 ) registry.splice( i, 1 );
		if( withConsumer && r.consumer ) { try { nativeRemove( r.t, 'DOMContentLoaded', r.consumer, r.c ); } catch( e ) {} }
		if( r.onAbort ) { try { EventTarget.prototype.removeEventListener.call( r.signal, 'abort', r.onAbort ); } catch( e ) {} r.onAbort = null; }
	}

	function find( t, l, c )
	{
		for( var i = 0; i < registry.length; i++ )
		{
			var r = registry[ i ];
			if( r.t === t && r.l === l && r.c === c )
			{
				if( aborted( r ) ) { drop( r, true ); i--; continue; }
				return r;
			}
		}
		return null;
	}

	function makeAdd()
	{
		return function addEventListener( type, listener ) {
			var options = arguments[ 2 ];
			var target = ( this !== undefined && this !== null ) ? this : window;
			var entry = null;

			if( installed && !passthrough && type === 'DOMContentLoaded' && isListener( listener ) && ( target === document || target === window ) )
			{
				var c = captureOf( options );
				var signal = ( options && typeof options === 'object' && options.signal ) ? options.signal : null;

				if( !( target === window && !c ) && !( signal && signal.aborted ) && find( target, listener, c ) === null )
				{
					var ok = true;
					var dormant = false;

					if( document.readyState === 'loading' ) ok = false;
					else if( hasOwn( document, 'readyState' ) && document.readyState !== 'complete' )
					{
						if( target === document ? seen.doc : seen.win ) dormant = true;
						else ok = false;
					}

					if( ok )
					{
						entry = { t: target, l: listener, c: c, once: !!( options && typeof options === 'object' && options.once ), signal: signal, consumer: null, onAbort: null, dormant: dormant };

						if( entry.once )
						{
							var owner = entry;
							owner.consumer = function() { drop( owner, false ); };
							try { nativeAdd( target, 'DOMContentLoaded', owner.consumer, signal ? { capture: c, once: true, signal: signal } : { capture: c, once: true } ); }
							catch( e ) { owner.consumer = null; }
						}
					}
				}
			}

			var result = original( target, 'addEventListener' ).apply( target, arguments );

			if( entry )
			{
				registry.push( entry );

				if( entry.signal && typeof entry.signal.aborted === 'boolean' )
				{
					var aborting = entry;
					aborting.onAbort = function() { drop( aborting, false ); };
					try { EventTarget.prototype.addEventListener.call( aborting.signal, 'abort', aborting.onAbort, { once: true } ); }
					catch( e ) { aborting.onAbort = null; }
				}
			}

			return result;
		};
	}

	function makeRemove()
	{
		return function removeEventListener( type, listener ) {
			var options = arguments[ 2 ];
			var target = ( this !== undefined && this !== null ) ? this : window;
			var result = original( target, 'removeEventListener' ).apply( target, arguments );

			if( type === 'DOMContentLoaded' )
			{
				var r = find( target, listener, captureOf( options ) );
				if( r ) drop( r, true );
			}

			return result;
		};
	}

	function restore()
	{
		var clean = true;

		[ [ document, 'addEventListener', mine.da ], [ window, 'addEventListener', mine.wa ], [ document, 'removeEventListener', mine.dr ], [ window, 'removeEventListener', mine.wr ] ].forEach( function( x ) {
			try {
				if( x[ 2 ] && x[ 0 ][ x[ 1 ] ] === x[ 2 ] ) delete x[ 0 ][ x[ 1 ] ];
				else if( x[ 2 ] && hasOwn( x[ 0 ], x[ 1 ] ) ) clean = false;
			} catch( e ) { clean = false; }
		});

		registry.slice().forEach( function( r ) { drop( r, true ); } );
		installed = false;
		if( !clean ) passthrough = true;
	}

	function install()
	{
		if( hasOwn( document, 'addEventListener' ) || hasOwn( window, 'addEventListener' ) ||
			hasOwn( document, 'removeEventListener' ) || hasOwn( window, 'removeEventListener' ) ) return false;

		try {
			mine.da = makeAdd();
			mine.wa = makeAdd();
			mine.dr = makeRemove();
			mine.wr = makeRemove();
			document.addEventListener = mine.da;
			window.addEventListener = mine.wa;
			document.removeEventListener = mine.dr;
			window.removeEventListener = mine.wr;
			if( document.addEventListener !== mine.da || window.addEventListener !== mine.wa ||
				document.removeEventListener !== mine.dr || window.removeEventListener !== mine.wr ) { restore(); return false; }
		}
		catch( e )
		{
			try { restore(); } catch( e2 ) {}
			return false;
		}

		installed = true;
		return true;
	}

	function blockedWait()
	{
		try {
			return document.querySelectorAll( 'script.my_agile_privacy_activate.autoscan_mode.map_src_script_blocked.mapWait[data-cookie-api-key]:not(._is_activated), script.my_agile_privacy_activate.autoscan_mode.map_inline_script_blocked.mapWait[data-cookie-api-key]:not(._is_activated)' ).length > 0;
		} catch( e ) { return false; }
	}

	//a shield pattern still able to block a script as mapWait
	function configWait()
	{
		try {
			var cfg = ( typeof map_full_config !== 'undefined' && map_full_config ) ? map_full_config : null;
			var added = cfg ? cfg.shield_added_pattern : null;
			if( !added || typeof added !== 'object' ) return false;

			var key_map = ( cfg.cookie_api_key_remote_id_map_active && typeof cfg.cookie_api_key_remote_id_map_active === 'object' ) ? cfg.cookie_api_key_remote_id_map_active : {};
			var without_notification = ( cfg.cookie_api_key_remote_id_map_blocked_without_notification && typeof cfg.cookie_api_key_remote_id_map_blocked_without_notification === 'object' ) ? cfg.cookie_api_key_remote_id_map_blocked_without_notification : {};
			var not_to_block = Array.isArray( cfg.cookie_api_key_not_to_block ) ? cfg.cookie_api_key_not_to_block : [];
			var accepted_all = MAP_Cookie.read( MAP_ACCEPTED_ALL_COOKIE_NAME ) == '1';
			var lists = [ added.js_patterns_src, added.js_patterns_code ];

			for( var i = 0; i < lists.length; i++ )
			{
				if( !Array.isArray( lists[ i ] ) ) continue;

				for( var j = 0; j < lists[ i ].length; j++ )
				{
					var item = lists[ i ][ j ];
					if( !item || typeof item.on_block_add_classes !== 'string' ) continue;
					if( ( ' ' + item.on_block_add_classes + ' ' ).indexOf( ' mapWait ' ) < 0 ) continue;

					if( typeof item.key !== 'string' || not_to_block.indexOf( item.key ) >= 0 ) continue;

					//same outcome as the shield: blocked unless accepted, an unknown key only when blocked without notification
					if( typeof key_map[ item.key ] === 'string' )
					{
						if( !( MAP_Cookie.read( key_map[ item.key ] ) == '1' || accepted_all ) ) return true;
					}
					else if( without_notification[ item.key ] )
					{
						return true;
					}
				}
			}
		} catch( e ) {}

		return false;
	}

	function fixedEvent()
	{
		var ev = new Event( 'DOMContentLoaded' );
		var state = { current: null, immediate: false };
		var stopImmediate = ev.stopImmediatePropagation;

		Object.defineProperty( ev, 'target', { get: function() { return document; } } );
		Object.defineProperty( ev, 'srcElement', { get: function() { return document; } } );
		Object.defineProperty( ev, 'currentTarget', { get: function() { return state.current; } } );
		Object.defineProperty( ev, 'eventPhase', { get: function() { return state.current === window ? 1 : ( state.current ? 2 : 0 ); } } );
		Object.defineProperty( ev, 'stopImmediatePropagation', { value: function() { state.immediate = true; return stopImmediate.call( ev ); } } );

		return { ev: ev, state: state };
	}

	function live( r, awake )
	{
		if( r.dormant )
		{
			if( !awake ) return false;
			r.dormant = false;
		}
		return true;
	}

	function callOne( r, fixed )
	{
		if( registry.indexOf( r ) < 0 ) return;
		if( aborted( r ) ) { drop( r, true ); return; }

		if( r.once )
		{
			drop( r, true );
			try { nativeRemove( r.t, 'DOMContentLoaded', r.l, r.c ); } catch( e ) {}
		}

		fixed.state.current = r.t;

		try {
			if( typeof r.l === 'function' ) r.l.call( r.t, fixed.ev );
			else if( typeof r.l.handleEvent === 'function' ) r.l.handleEvent( fixed.ev );
			else throw new TypeError( 'handleEvent is not a function' );
		}
		catch( err ) { report( err ); }
	}

	//one instant: window capture, document capture, document bubble, each photographed when reached
	function tick()
	{
		var fixed = fixedEvent();
		var awake = document.readyState === 'complete';
		var phases = [
			function( r ) { return r.t === window && r.c; },
			function( r ) { return r.t === document && r.c; },
			function( r ) { return r.t === document && !r.c; }
		];

		for( var p = 0; p < phases.length; p++ )
		{
			if( p > 0 && fixed.ev.cancelBubble ) return;

			var list = registry.filter( function( r ) { return phases[ p ]( r ) && live( r, awake ); } );

			for( var i = 0; i < list.length; i++ )
			{
				callOne( list[ i ], fixed );
				if( fixed.state.immediate ) return;
			}
		}
	}

	function probe( e ) { if( e.type === 'DOMContentLoaded' ) return; mark( e.target ); }
	function annotate( e ) { mark( e.target ); }

	try {
		document.addEventListener( 'DOMContentLoaded', probe );
		window.addEventListener( 'DOMContentLoaded', probe );
	} catch( e ) {}

	try { nativeAdd( window, 'rocket-DOMContentLoaded', annotate, true ); } catch( e ) {}

	return {
		//once per page, at the start of the frontend
		start: function() {
			if( tried ) return;
			tried = true;
			try {
				var cfg = configWait();
				if( !cfg && !blockedWait() ) return;
				if( install() && cfg ) permanent = true;
			} catch( e ) { console.debug( e ); }
		},
		//the handle stays bound to this instance, even if the frontend is evaluated again
		arm: function() {
			var handle = {
				on: installed,
				active: function() { return installed; },
				tick: tick,
				done: function() {
					if( !handle.on ) return;
					handle.on = false;
					open--;
					if( open <= 0 && installed )
					{
						open = 0;
						if( !permanent && !blockedWait() ) restore();
					}
				}
			};
			if( installed ) open++;
			return handle;
		}
	};
} )();

//places what a re-executed script writes where the parser would have placed it
var MAP_WRITE_REPLAY = ( function() {
	'use strict';

	var HTML_NS = 'http://www.w3.org/1999/xhtml';
	var RAW = { script: 1, style: 1, textarea: 1, title: 1, iframe: 1, noscript: 1, xmp: 1, noembed: 1, noframes: 1, plaintext: 1 };
	var EMPTY = { area: 1, base: 1, br: 1, col: 1, embed: 1, hr: 1, img: 1, input: 1, link: 1, meta: 1, param: 1, source: 1, track: 1, wbr: 1, keygen: 1, basefont: 1, bgsound: 1, frame: 1, html: 1, head: 1, body: 1 };
	var SIBLING = { p: 1, li: 1, option: 1, optgroup: 1, dt: 1, dd: 1, tr: 1, td: 1, th: 1 };
	var WAIT_MS = 10000;
	var JS_TYPES = [ '', 'text/javascript', 'application/javascript', 'application/ecmascript', 'application/x-ecmascript', 'application/x-javascript', 'text/ecmascript', 'text/javascript1.0', 'text/javascript1.1', 'text/javascript1.2', 'text/javascript1.3', 'text/javascript1.4', 'text/javascript1.5', 'text/jscript', 'text/livescript', 'text/x-ecmascript', 'text/x-javascript' ];

	var contexts = ( typeof WeakMap === 'function' ) ? new WeakMap() : null;
	var installed = null;
	var open = 0;
	var headTail = null;
	var idle = function() {};

	function hasOwn( o, k ) { return Object.prototype.hasOwnProperty.call( o, k ); }
	function isSpace( c ) { return c === ' ' || c === '\t' || c === '\n' || c === '\r' || c === '\f'; }
	function isAlpha( c ) { return ( c >= 'a' && c <= 'z' ) || ( c >= 'A' && c <= 'Z' ); }

	//end of a tag from the first character after its name: { end, self }, or null while the tag is incomplete
	function tagEnd( s, j )
	{
		var n = s.length;
		var self = false;

		while( j < n )
		{
			var c = s.charAt( j );

			if( c === '>' ) return { end: j + 1, self: self };
			if( c === '/' ) { self = true; j++; continue; }

			self = false;

			if( isSpace( c ) ) { j++; continue; }

			j++;
			while( j < n && !isSpace( s.charAt( j ) ) && '/>='.indexOf( s.charAt( j ) ) < 0 ) j++;
			while( j < n && isSpace( s.charAt( j ) ) ) j++;

			if( j < n && s.charAt( j ) === '=' )
			{
				j++;
				while( j < n && isSpace( s.charAt( j ) ) ) j++;
				if( j >= n ) return null;

				var q = s.charAt( j );

				if( q === '"' || q === "'" )
				{
					var e = s.indexOf( q, j + 1 );
					if( e < 0 ) return null;
					j = e + 1;
				}
				else if( q !== '>' )
				{
					while( j < n && !isSpace( s.charAt( j ) ) && s.charAt( j ) !== '>' ) j++;
				}
			}
		}

		return null;
	}

	//end of the tag that closes a script whose text starts at i, through its escaped states: { end, self }, or null while still open
	function scriptShut( s, i )
	{
		var low = s.slice( i ).replace( /[A-Z]+/g, function( x ) { return x.toLowerCase(); } );
		var state = 0;
		var at = 0;

		while( true )
		{
			var lt = low.indexOf( '<', at );
			var dd = state ? low.indexOf( '-->', at ) : -1;

			if( dd >= 0 && ( lt < 0 || dd < lt ) ) { state = 0; at = dd + 3; continue; }
			if( lt < 0 ) return null;

			at = lt + 1;

			if( state === 0 && low.substr( lt, 4 ) === '<!--' ) { state = 1; at = lt + 2; continue; }

			var closing = low.charAt( lt + 1 ) === '/';
			var p = lt + ( closing ? 2 : 1 );

			if( low.substr( p, 6 ) !== 'script' ) continue;
			if( p + 6 >= low.length ) return null;

			var ac = low.charAt( p + 6 );

			if( !isSpace( ac ) && ac !== '/' && ac !== '>' ) continue;

			at = p + 6;

			if( !closing ) { if( state === 1 ) state = 2; continue; }
			if( state === 2 ) { state = 1; continue; }

			return tagEnd( s, i + p + 6 );
		}
	}

	//true when no tag, comment, raw text or non-void element is left open; the scan resumes from st
	function complete( s, st )
	{
		var stack = st.stack;
		var n = s.length;
		var i = st.pos;

		while( i < n )
		{
			var lt = s.indexOf( '<', i );
			if( lt < 0 ) { i = n; break; }

			st.pos = lt;
			i = lt + 1;
			if( i >= n ) return false;

			var c = s.charAt( i );

			if( c === '!' && s.substr( i + 1, 2 ) === '--' )
			{
				var ce = s.indexOf( '-->', i + 1 );
				if( ce < 0 ) return false;
				i = ce + 3;
				continue;
			}

			if( c === '!' || c === '?' )
			{
				var be = s.indexOf( '>', i );
				if( be < 0 ) return false;
				i = be + 1;
				continue;
			}

			var closing = false;

			if( c === '/' )
			{
				if( i + 1 >= n ) return false;

				if( !isAlpha( s.charAt( i + 1 ) ) )
				{
					var xe = s.indexOf( '>', i );
					if( xe < 0 ) return false;
					i = xe + 1;
					continue;
				}

				closing = true;
				i++;
			}
			else if( !isAlpha( c ) )
			{
				continue;
			}

			var j = i;
			while( j < n && !isSpace( s.charAt( j ) ) && s.charAt( j ) !== '/' && s.charAt( j ) !== '>' ) j++;
			if( j >= n ) return false;

			var name = s.slice( i, j ).toLowerCase();
			var t = tagEnd( s, j );
			if( !t ) return false;
			i = t.end;

			if( closing )
			{
				var k = stack.lastIndexOf( name );
				if( k >= 0 ) stack.length = k;
				continue;
			}

			if( name === 'script' )
			{
				var sc = scriptShut( s, i );
				if( !sc ) return false;
				i = sc.end;
				continue;
			}

			if( RAW[ name ] )
			{
				var tail = s.slice( i ).replace( /[A-Z]+/g, function( x ) { return x.toLowerCase(); } );
				var from = 0;
				var shut = null;

				while( shut === null )
				{
					var r = tail.indexOf( '</' + name, from );
					if( r < 0 ) return false;

					var after = i + r + 2 + name.length;
					if( after >= n ) return false;

					var ac = s.charAt( after );

					if( isSpace( ac ) || ac === '/' || ac === '>' )
					{
						shut = tagEnd( s, after );
						if( !shut ) return false;
					}
					else
					{
						from = r + 2 + name.length;
					}
				}

				i = shut.end;
				continue;
			}

			if( EMPTY[ name ] ) continue;
			if( t.self && ( name === 'svg' || name === 'math' || stack.indexOf( 'svg' ) >= 0 || stack.indexOf( 'math' ) >= 0 ) ) continue;
			if( SIBLING[ name ] && stack.length && stack[ stack.length - 1 ] === name ) continue;

			stack.push( name );
		}

		st.pos = i;
		return stack.length === 0;
	}

	//how the parser would run the script: 'classic', 'module' or null
	function kind( s )
	{
		var t = s.getAttribute( 'type' );
		var l = s.getAttribute( 'language' );

		if( t === null && l !== null && l !== '' ) t = ( 'text/' + l ).toLowerCase();
		else t = ( t === null ) ? '' : String( t ).toLowerCase();

		if( t === 'module' ) return 'module';
		if( s.getAttribute( 'type' ) !== null ) t = t.trim();
		if( JS_TYPES.indexOf( t ) < 0 || s.hasAttribute( 'nomodule' ) ) return null;

		if( s.hasAttribute( 'for' ) && s.hasAttribute( 'event' ) )
		{
			var f = String( s.getAttribute( 'for' ) ).trim().toLowerCase();
			var e = String( s.getAttribute( 'event' ) ).trim().toLowerCase();

			if( f !== 'window' || ( e !== 'onload' && e !== 'onload()' ) ) return null;
		}

		return 'classic';
	}

	//an external script that will fire load or error
	function runs( s )
	{
		return s.isConnected && s.hasAttribute( 'src' ) && kind( s ) !== null;
	}

	function blocking( s )
	{
		return runs( s ) && kind( s ) === 'classic' && !s.hasAttribute( 'async' );
	}

	//a script that never loads stops holding the queue
	function guard( ctx )
	{
		ctx.timer = setTimeout( function() { finish( ctx ); }, WAIT_MS );
	}

	function makeWrite( orig, ln )
	{
		return function write() {
			var cs = document.currentScript;
			var ctx = ( this === document && cs && contexts ) ? contexts.get( cs ) : null;

			if( !ctx || ctx.closed ) return orig.apply( this, arguments );

			ctx.buf += Array.prototype.map.call( arguments, String ).join( '' ) + ( ln ? '\n' : '' );

			if( complete( ctx.buf, ctx.scan ) ) flush( ctx );
		};
	}

	function uninstall()
	{
		var x = installed;
		if( !x ) return;

		installed = null;

		[ [ 'write', x.w ], [ 'writeln', x.wl ] ].forEach( function( p ) {
			try {
				if( !p[ 1 ].mine || document[ p[ 0 ] ] !== p[ 1 ].mine ) return;
				if( p[ 1 ].own ) document[ p[ 0 ] ] = p[ 1 ].orig;
				else delete document[ p[ 0 ] ];
			} catch( e ) {}
		});
	}

	//own properties on document only, never on the prototype
	function install()
	{
		if( installed ) return true;
		if( !contexts ) return false;

		try {
			var x = {
				w: { own: hasOwn( document, 'write' ), orig: document.write, mine: null },
				wl: { own: hasOwn( document, 'writeln' ), orig: document.writeln, mine: null }
			};

			if( typeof x.w.orig !== 'function' || typeof x.wl.orig !== 'function' ) return false;

			x.w.mine = makeWrite( x.w.orig, false );
			x.wl.mine = makeWrite( x.wl.orig, true );
			installed = x;

			document.write = x.w.mine;
			document.writeln = x.wl.mine;

			if( document.write !== x.w.mine || document.writeln !== x.wl.mine ) { uninstall(); return false; }
		}
		catch( e )
		{
			uninstall();
			return false;
		}

		return true;
	}

	//empty text node after the anchor; for an anchor in the head, at the start of the body
	function mark( anchor )
	{
		var body = document.body;
		var b = document.createTextNode( '' );

		if( anchor && anchor.parentNode && !( document.head && document.head.contains( anchor ) ) )
		{
			anchor.parentNode.insertBefore( b, anchor.nextSibling );
			return b;
		}

		if( !body ) return null;

		if( anchor && anchor.parentNode )
		{
			body.insertBefore( b, ( headTail && headTail.parentNode === body ) ? headTail.nextSibling : body.firstChild );
			headTail = b;
			return b;
		}

		body.appendChild( b );
		return b;
	}

	function make( script, parent, bookmark )
	{
		var ctx = { parent: parent, bookmark: bookmark, buf: '', scan: { pos: 0, stack: [] }, queue: [], waiting: null, ended: false, closed: false, draining: false, timer: null };

		contexts.set( script, ctx );
		open++;

		return ctx;
	}

	//parsed in the page's context, scripts taken out, inserted before the bookmark
	function flush( ctx )
	{
		var html = ctx.buf;
		ctx.buf = '';
		ctx.scan = { pos: 0, stack: [] };

		if( html === '' ) return;

		var b = ctx.bookmark;
		var at = ( b && b.isConnected ) ? b : null;
		var host = ( at && at.parentNode && at.parentNode.nodeType === 1 ) ? at.parentNode : document.body;

		if( !host ) return;

		var range = document.createRange();
		range.selectNodeContents( host );

		var frag = range.createContextualFragment( html );
		var found = frag.querySelectorAll( 'script' );

		for( var i = 0; i < found.length; i++ )
		{
			var ph = document.createComment( '' );
			found[ i ].parentNode.replaceChild( ph, found[ i ] );

			if( found[ i ].namespaceURI === HTML_NS ) ctx.queue.push( { ph: ph, el: found[ i ] } );
		}

		if( at ) at.parentNode.insertBefore( frag, at );
		else document.body.appendChild( frag );

		drain( ctx );
	}

	//a written script recreated through the page's createElement, in place of its placeholder
	function start( ctx, item )
	{
		var ph = item.ph;
		var el = item.el;

		if( !ph.parentNode ) return;

		var s = document.createElement( 'script' );
		var a = el.attributes;

		for( var i = 0; i < a.length; i++ )
		{
			try { s.setAttribute( a[ i ].name, a[ i ].value ); } catch( e ) {}
		}

		if( el.nonce && !s.nonce ) s.nonce = el.nonce;

		s.text = el.text;

		var child = make( s, ctx, document.createTextNode( '' ) );
		var done = function() { finish( child ); };

		ph.parentNode.insertBefore( child.bookmark, ph.nextSibling );
		s.addEventListener( 'load', done );
		s.addEventListener( 'error', done );

		ph.parentNode.replaceChild( s, ph );

		if( runs( s ) )
		{
			guard( child );
			if( blocking( s ) ) ctx.waiting = child;
		}
		else
		{
			//an inline script that has run is not taken up again
			if( !s.hasAttribute( 'src' ) && kind( s ) !== null ) s.classList.add( '_is_activated' );

			finish( child );
			if( !child.closed ) ctx.waiting = child;
		}
	}

	function drain( ctx )
	{
		if( ctx.draining ) return;

		ctx.draining = true;

		try {
			while( !ctx.waiting && ctx.queue.length )
			{
				try { start( ctx, ctx.queue.shift() ); }
				catch( e ) { console.error( e ); }
			}
		}
		finally {
			ctx.draining = false;
		}

		settle( ctx );
	}

	function finish( ctx )
	{
		if( ctx.ended ) return;

		ctx.ended = true;

		if( ctx.buf !== '' )
		{
			try { flush( ctx ); } catch( e ) { console.error( e ); }
		}

		settle( ctx );
	}

	function settle( ctx )
	{
		if( ctx.closed || !ctx.ended || ctx.waiting || ctx.queue.length || ctx.draining ) return;

		ctx.closed = true;

		if( ctx.timer ) clearTimeout( ctx.timer );

		var b = ctx.bookmark;

		if( b === headTail ) headTail = b.previousSibling;
		if( b && b.parentNode ) b.parentNode.removeChild( b );

		open--;

		var p = ctx.parent;

		if( p && p.waiting === ctx )
		{
			p.waiting = null;
			drain( p );
		}

		if( open <= 0 )
		{
			open = 0;
			uninstall();
		}
	}

	//called right before the script enters the page; the returned function marks its end
	function begin( anchor, script, external )
	{
		try {
			if( !script || !install() ) return idle;

			var b = mark( anchor );

			if( !b )
			{
				if( open <= 0 ) uninstall();
				return idle;
			}

			var ctx = make( script, null, b );

			if( external )
			{
				var done = function() { finish( ctx ); };
				script.addEventListener( 'load', done );
				script.addEventListener( 'error', done );
				guard( ctx );
			}

			return function() { finish( ctx ); };
		}
		catch( e )
		{
			console.debug( e );
			return idle;
		}
	}

	return {
		begin: begin,
		//true for a type attribute value the parser runs as a classic script
		jsType: function( t ) { return JS_TYPES.indexOf( String( t ).trim().toLowerCase() ) >= 0; },
		//inserts a re-executed script; an inline one ends with the insertion, an external one with its load or error
		run: function( anchor, script, external, insert ) {
			var end = begin( anchor, script, external );

			try { insert(); }
			catch( e ) { end(); throw e; }

			if( !external || !runs( script ) ) end();
		},
		//each script of the fragment is its own anchor
		fragment: function( frag, insert ) {
			var ends = [];

			try {
				var found = frag.querySelectorAll( 'script' );

				for( var i = 0; i < found.length; i++ )
				{
					if( found[ i ].namespaceURI !== HTML_NS ) continue;

					var ext = found[ i ].hasAttribute( 'src' );
					ends.push( { s: found[ i ], ext: ext, end: begin( found[ i ], found[ i ], ext ) } );
				}
			}
			catch( e ) { console.debug( e ); }

			try { insert(); }
			finally {
				ends.forEach( function( x ) { if( !x.ext || !runs( x.s ) ) x.end(); } );
			}
		}
	};
} )();

//holds the scripts of a service until the page's delayed jQuery has arrived
var MAP_JQ_WAIT = ( function() {
	'use strict';

	var AFTER_JQ_MS = 10000;
	var NO_JQ_MS = 10000;
	var POLL_MS = 100;
	var IDLE_POLL_MS = 500;
	var jsType = MAP_WRITE_REPLAY.jsType;
	var CORE = /(^|\/)jquery(-\d+(\.\d+)*)?(\.slim)?(\.min)?\.js([?#]|$)/i;
	var USES = /\bjQuery\b|(^|[^\w$.'"])\$\s*(\(|\.[A-Za-z_])/;
	var SRC_IN_CODE = /<script\b[^>]*?\bsrc\s*=\s*["']?([^"'\s>]+)/gi;
	var TAGS = 'script[id="jquery-core-js"], script[src*="jquery" i], script[data-src*="jquery" i], script[data-rocket-src*="jquery" i]';

	//shared by every evaluation of this file
	var st = window.MAP_JQ_WAIT_STATE;

	if( !( st && typeof st === 'object' && Array.isArray( st.queue ) ) )
	{
		st = window.MAP_JQ_WAIT_STATE = { queue: [], timer: null, listening: false, seq: 0, ends: { rocket: 0, ls: 0 }, seen: 0, gone: 0 };
	}

	//a full jQuery, not a stub that only queues ready callbacks
	function real()
	{
		try {
			var j = window.jQuery;
			return typeof j === 'function' && !!j.fn && typeof j.fn.jquery === 'string';
		} catch( e ) { return false; }
	}

	//the page's own jQuery tags, never one held by the plugin
	function tags()
	{
		var out = [];

		try {
			document.querySelectorAll( TAGS ).forEach( function( s ) {
				if( s.classList.contains( 'my_agile_privacy_activate' ) ) return;

				if( s.id === 'jquery-core-js' ||
					CORE.test( s.getAttribute( 'src' ) || '' ) ||
					CORE.test( s.getAttribute( 'data-src' ) || '' ) ||
					CORE.test( s.getAttribute( 'data-rocket-src' ) || '' ) ) out.push( s );
			});
		} catch( e ) {}

		return out;
	}

	//a tag an optimizer has not run yet
	function delayed( s )
	{
		if( !s.hasAttribute( 'src' ) && ( s.hasAttribute( 'data-src' ) || s.hasAttribute( 'data-rocket-src' ) ) ) return true;

		var t = s.getAttribute( 'type' );
		if( t === null ) return false;

		t = String( t ).trim().toLowerCase();
		return t !== 'module' && !jsType( t );
	}

	//remembers that the page's jQuery went through an optimizer's queue, and through LiteSpeed's
	function observe( ts )
	{
		try {
			if( real() ) return;
			( ts || tags() ).forEach( function( s ) {
				if( !delayed( s ) ) return;
				st.delayedSeen = true;
				var t = String( s.getAttribute( 'type' ) || '' ).trim().toLowerCase();
				if( t === 'litespeed/javascript' ) st.lsSeen = true;
				if( s.hasAttribute( 'data-rocket-src' ) || /rocketlazyloadscript$/.test( t ) ) st.rocketSeen = true;
			});
		} catch( e ) { console.debug( e ); }
	}

	function waiting()
	{
		if( real() ) return false;
		var ts = tags();
		observe( ts );
		return ts.length > 0;
	}

	//a path segment named after jQuery
	function usesFile( u )
	{
		if( typeof u !== 'string' || !u ) return false;

		var p = u.replace( /^([a-z][\w+.-]*:)?\/\/[^\/]*/i, '' ).split( /[?#]/ )[ 0 ].split( '/' );

		for( var i = 0; i < p.length; i++ )
		{
			var x = p[ i ].toLowerCase();
			if( x === 'jquery' || x.indexOf( 'jquery.' ) === 0 || x.indexOf( 'jquery-' ) === 0 ) return true;
		}

		return false;
	}

	function uses( n )
	{
		try {
			if( n.tagName === 'TEXTAREA' )
			{
				var code = String( n.value || '' );
				if( USES.test( code ) ) return true;

				SRC_IN_CODE.lastIndex = 0;
				var m;
				while( ( m = SRC_IN_CODE.exec( code ) ) !== null )
				{
					if( usesFile( m[ 1 ] ) ) return true;
				}
				return false;
			}

			if( n.classList.contains( 'map_src_script_blocked' ) )
			{
				return n.classList.contains( 'map_needs_jquery' ) || usesFile( n.getAttribute( 'unblocked_src' ) );
			}

			return USES.test( n.textContent || '' );
		} catch( e ) { return false; }
	}

	//Rocket's queue, only when it delayed jQuery
	function rocketQueue()
	{
		if( !st.rocketSeen ) return false;
		try { return !!document.querySelector( 'script[type$="rocketlazyloadscript"], script[data-rocket-status]' ); } catch( e ) { return false; }
	}

	//LiteSpeed's queue, only when it delayed jQuery and has not finished
	function liteSpeedQueue()
	{
		return typeof window.litespeed_load_delayed_js === 'function' && !!st.lsSeen && !st.ends.ls;
	}

	//jQuery has run, but the queue that delayed it has not finished yet
	function queueOpen()
	{
		if( !st.delayedSeen || st.stalled || !real() ) return false;
		return liteSpeedQueue() || ( !st.ends.rocket && rocketQueue() );
	}

	function stop()
	{
		if( st.timer !== null )
		{
			clearTimeout( st.timer );
			st.timer = null;
		}
	}

	function release( g )
	{
		g.nodes.forEach( function( n ) { try { n.classList.remove( '_map_jq_wait' ); } catch( e ) {} } );

		var ok = false;
		try { ok = !!g.consent(); } catch( e ) { console.debug( e ); }

		try {
			if( ok ) g.run();
			else if( typeof g.drop === 'function' ) g.drop();
		}
		catch( e ) { console.error( e ); }
	}

	//the head of the queue decides for the whole queue; true while a jQuery tag is still delayed
	function decide()
	{
		if( !st.queue.length ) { stop(); return false; }

		var now = Date.now();
		var j = real();
		var ts = j ? [] : tags();
		var held = false;

		for( var i = 0; i < ts.length; i++ )
		{
			if( delayed( ts[ i ] ) ) { held = true; break; }
		}

		if( held ) observe( ts );

		if( j ) { if( !st.seen ) st.seen = now; } else { st.seen = 0; }
		if( !j && !held ) { if( !st.gone ) st.gone = now; } else { st.gone = 0; }

		var h = st.queue[ 0 ];
		var finished = ( h.rocket && st.ends.rocket > h.seq ) ||
			( h.ls && st.ends.ls > h.seq );
		var late = j && now - st.seen >= AFTER_JQ_MS;
		var go = finished ||
			( j && !h.rocket && !h.ls ) ||
			late ||
			( !j && !held && now - st.gone >= NO_JQ_MS );

		if( !go ) return held;

		//a queue that did not finish within the margin is not waited for again
		if( late && !finished && ( h.rocket || h.ls ) ) st.stalled = true;

		stop();
		st.queue.splice( 0 ).forEach( release );
		return false;
	}

	//a failing check releases as without the wait, so the queue never stays held
	function check()
	{
		try { return decide(); }
		catch( e )
		{
			console.debug( e );
			stop();
			st.queue.splice( 0 ).forEach( release );
			return false;
		}
	}

	function tick()
	{
		st.timer = null;
		var held = check();
		if( st.queue.length && st.timer === null ) st.timer = setTimeout( tick, held ? IDLE_POLL_MS : POLL_MS );
	}

	//a group that cannot be queued runs as without the wait
	function hold( g )
	{
		try {
			if( !st.queue.length ) { st.seen = 0; st.gone = 0; }

			g.seq = ++st.seq;
			g.rocket = rocketQueue();
			g.ls = liteSpeedQueue();
			g.nodes.forEach( function( n ) { n.classList.add( '_map_jq_wait' ); } );

			st.queue.push( g );

			if( st.timer === null ) st.timer = setTimeout( tick, POLL_MS );
		}
		catch( e )
		{
			console.debug( e );
			if( st.queue.indexOf( g ) < 0 ) release( g );
		}
	}

	if( !st.listening )
	{
		st.listening = true;

		var ended = function( k ) {
			return function() {
				st.ends[ k ] = ++st.seq;
				if( st.queue.length ) setTimeout( check, 0 );
			};
		};

		try {
			window.addEventListener( 'rocket-allScriptsLoaded', ended( 'rocket' ) );
			window.addEventListener( 'DOMContentLiteSpeedLoaded', ended( 'ls' ) );
			document.addEventListener( 'DOMContentLiteSpeedLoaded', ended( 'ls' ) );
		} catch( e ) { console.debug( e ); }
	}

	//own guard: an older evaluation may have set listening without this
	if( !st.observing )
	{
		st.observing = true;
		document.addEventListener( 'DOMContentLoaded', function() { observe(); } );
	}

	observe();

	return {
		held: function( list ) {
			return list.some( function( n ) { return n.classList.contains( '_map_jq_wait' ); } );
		},
		//a failing check means no wait, as without it
		needs: function( list ) {
			try { return list.length > 0 && list.some( uses ) && ( waiting() || st.queue.length > 0 || queueOpen() ); }
			catch( e ) { console.debug( e ); return false; }
		},
		hold: hold
	};
} )();

//f. for dom element recreating and previous event removal
function internalRecreateNode( el, withChildren ){
	try {

		if(withChildren) {
			el.parentNode.replaceChild(el.cloneNode(true), el);
		}
		else
		{
			var newEl = el.cloneNode(false);
			while (el.hasChildNodes()) newEl.appendChild(el.firstChild);
			el.parentNode.replaceChild(newEl, el);
		}
	} catch( e )
	{
		console.debug( e );
	}
}

//f. swaps two nodes without the change event a browser fires on a focused field taken out of the page
function map_replace_quietly( fresh, old )
{
	var quiet = function( e ) { if( e.isTrusted ) e.stopImmediatePropagation(); };
	document.addEventListener( 'change', quiet, true );
	try {
		old.parentNode.replaceChild( fresh, old );
	}
	finally {
		document.removeEventListener( 'change', quiet, true );
	}
}

//f. puts a form's clone in its place, keeping what the visitor already entered and the focus
function map_carry_form_state( form, clone )
{
	var fields_selector = 'input, select, textarea, button';
	var from = form.querySelectorAll( fields_selector );
	var to = clone.querySelectorAll( fields_selector );
	var focus_index = -1;
	var selection = null;

	for( var i = 0; i < from.length && i < to.length; i++ )
	{
		try {
			if( from[ i ].tagName === 'SELECT' )
			{
				for( var j = 0; j < from[ i ].options.length && j < to[ i ].options.length; j++ )
				{
					to[ i ].options[ j ].selected = from[ i ].options[ j ].selected;
				}
			}
			else if( from[ i ].type === 'file' && from[ i ].files && from[ i ].files.length )
			{
				to[ i ].files = from[ i ].files;
			}

			if( from[ i ] === document.activeElement )
			{
				focus_index = i;
				if( typeof from[ i ].selectionStart === 'number' ) selection = [ from[ i ].selectionStart, from[ i ].selectionEnd ];
			}
		}
		catch( e ) {
			console.debug( e );
		}
	}

	map_replace_quietly( clone, form );

	if( focus_index >= 0 && to[ focus_index ] )
	{
		var target = to[ focus_index ];

		try {
			target.focus( { preventScroll: true } );
			if( selection )
			{
				target.setSelectionRange( selection[ 0 ], selection[ 1 ] );
			}
			else if( target.tagName === 'INPUT' && ( target.type === 'email' || target.type === 'number' ) && !( target.validity && target.validity.badInput ) )
			{
				//no caret api on these types: the caret goes back to the end
				var value = target.value;
				target.value = '';
				target.value = value;
			}
		}
		catch( e ) {
			console.debug( e );
		}
	}
}

//wpcf7
function map_trigger_custom_patch_1()
{
	MapLogger.debug( MAP_SYS.maplog + 'map_trigger_custom_patch_1' );

	var recreated = [];
	var focused = document.activeElement;

	try {
		if( typeof wpcf7 === 'object' && wpcf7 && typeof wpcf7.init === 'function' )
		{
			//every form recreated, without the spinners already added
			document.querySelectorAll( '.wpcf7 > form' ).forEach( function( form ) {
				var clone = form.cloneNode( true );
				clone.querySelectorAll( '.wpcf7-spinner' ).forEach( function( spinner ) { spinner.remove(); } );
				map_carry_form_state( form, clone );
				recreated.push( [ clone, form ] );
			});
		}
		else
		{
			internalRecreateNode( document.querySelector( 'form.wpcf7-form' ), true );
		}
	}
	catch ( e ) {
		console.debug( e );
	}

	const c = new CustomEvent("DOMContentLoaded", {
	});
	document.dispatchEvent(c);

	//a clone left uninitialized gets its original form back, which still holds what the visitor entered
	recreated.forEach( function( pair ) {
		try {
			if( !pair[ 0 ].wpcf7 && pair[ 0 ].parentNode )
			{
				var focus_back = pair[ 0 ].contains( document.activeElement );

				map_replace_quietly( pair[ 1 ], pair[ 0 ] );

				if( focus_back && focused && pair[ 1 ].contains( focused ) ) focused.focus( { preventScroll: true } );
			}
		}
		catch( e ) {
			console.debug( e );
		}
	});

	/*
	try {
		wpcf7.submit = null;
	} catch ( e ) {
		console.debug( e );
	}
	*/
}

//avia maps
function map_trigger_custom_patch_2()
{
	MapLogger.debug( MAP_SYS.maplog + 'map_trigger_custom_patch_2' );

	document.querySelectorAll( '.av_gmaps_confirm_link.av_text_confirm_link.av_text_confirm_link_visible' ).forEach( function( element ) {
		element.click();
	});

}

//octorate
function map_trigger_custom_patch_3()
{
	try {
		octorate.octobook.Widget.show();
	} catch ( e ) {
		setTimeout( map_trigger_custom_patch_3, 100 );
		console.debug( e );
	}
}

//paypal
function map_trigger_custom_patch_4()
{
	try {
		setInterval(function() {
			document.querySelectorAll( '.blockUI.blockOverlay' ).forEach(function( el ) {
				el.remove();
			});
		}, 500);

	} catch ( e ) {
		setTimeout( map_trigger_custom_patch_4, 100 );
		console.debug( e );
	}
}

window.gTagManagerConsentListeners = window.gTagManagerConsentListeners || [];

//Google consent mode early as possible initialization
if( typeof MAP !== 'undefined' && typeof MAP.setupConsentModeV2 !== 'undefined' )
{
	MAP.setupConsentModeV2();
}

//Microsoft consent mode early as possible initialization
if( typeof MAP !== 'undefined' && typeof MAP.setupMicrosoftConsentMode !== 'undefined' )
{
	MAP.setupMicrosoftConsentMode();
}

//Clarity consent mode early as possible initialization
if( typeof MAP !== 'undefined' && typeof MAP.setupClarityConsentMode !== 'undefined' )
{
	MAP.setupClarityConsentMode();
}


//GTM consent listener retry
if( typeof map_full_config !== 'undefined' &&
	map_full_config?.enable_cmode_v2 &&
	map_full_config?.cmode_v2_implementation_type === 'gtm' )
{
	(function() {

		var _retryCount   = 0;
		var _maxRetry     = 5;
		var _baseInterval = 200;

		var _retry = function() {

			if( _retryCount >= _maxRetry )
			{
				MapLogger.warn( MAP_SYS.maplog + 'GTM consent listener retry: max attempts reached' );
				return;
			}

			_retryCount++;

			var _listeners = window.gTagManagerConsentListeners;

			if( typeof _listeners === 'undefined' || _listeners.length === 0 )
			{
				if( typeof MAP !== 'undefined' &&
					typeof MAP.googleTagManagerConsentListener === 'function' )
				{
					// Register a listener.
					MAP.googleTagManagerConsentListener( function( consent ) {
						if( typeof gtag === 'function' )
						{
							gtag( 'consent', 'update', consent );
						}
					});

					// Also propagate current consent from cookie immediately.
					if( typeof MAP.exportGoogleConsentObjectFromCookie === 'function' )
					{
						var _initialConsent = MAP.exportGoogleConsentObjectFromCookie();
						if( _initialConsent && typeof gtag === 'function' )
						{
							gtag( 'consent', 'update', _initialConsent );
						}
					}

					MapLogger.debug( MAP_SYS.maplog + 'GTM consent listener retry: registered at attempt ' + _retryCount );
					return;
				}

				// MAP not ready yet - retry later.
				setTimeout( _retry, _baseInterval * Math.pow( 2, _retryCount - 1 ) );
				return;
			}

			// Listener already present - no action needed
			if( _listeners.length > 1 )
			{
				MapLogger.debug( MAP_SYS.maplog + 'GTM consent listener retry: ' + _listeners.length + ' listeners registered (bridge + GTM template?) - consent updates may appear duplicated in dataLayer' );
			}
			MapLogger.debug( MAP_SYS.maplog + 'GTM consent listener retry: listener already present, skipping' );
		};

		_retry();

	})();
}

// loading-priority report.
document.addEventListener( 'DOMContentLoaded', function () {
	try {
		if( window.map_gtm_pre_shield_detected !== true ) { return; }
		if( typeof map_ajax === 'undefined' || ! map_ajax || ! map_ajax.api_token ) { return; }

		var learning = ( typeof map_cookiebar_settings !== 'undefined' && map_cookiebar_settings && map_cookiebar_settings.scan_mode === 'learning_mode' ) ||
		               ( map_ajax.force_js_learning_mode == 1 );
		if( ! learning ) { return; }
		if( typeof map_cookiebar_settings !== 'undefined' && map_cookiebar_settings && map_cookiebar_settings.post_mode == 1 ) { return; }

		if( map_ajax.gtm_gateway_detection_disabled == 1 ) { return; }
		if( typeof MAP_Cookie === 'undefined' ) { return; }
		if( MAP_Cookie.read( 'map_gtm_gw_reported' ) === '1' ) { return; }
		MAP_Cookie.set( 'map_gtm_gw_reported', '1', 0.5 );

		var body = 'action=map_report_gtm_gateway&map_api_token=' + encodeURIComponent( map_ajax.api_token );

		var primary = function () {
			if( ! map_ajax.api_url ) { return Promise.reject(); }
			return fetch( map_ajax.api_url, {
				method  : 'POST',
				headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
				body    : body
			} ).then( function ( resp ) {
				if( ! resp.ok ) { throw new Error( 'api ' + resp.status ); }
			} );
		};

		primary().catch( function () {
			if( ! map_ajax.ajax_url ) { return; }
			fetch( map_ajax.ajax_url, {
				method  : 'POST',
				headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
				body    : body
			} ).catch( function () {} );
		} );
	} catch ( e ) {}
} );
