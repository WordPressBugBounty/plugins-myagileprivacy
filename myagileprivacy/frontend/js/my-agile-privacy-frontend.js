/*!
* MyAgilePrivacy (https://www.myagileprivacy.com/)
* jQuery version
*/

var MAP_SYS = {
	'internal_version' : "2.0006",
	'technology' : "jQuery",
	'maplog' : "\x1b[40m\x1b[37m[MyAgilePrivacy]\x1b[0m ",
	'map_initted' : false,
	'map_document_load' : false,
	'map_debug' : false,
	'map_cookie_expire' : 180,
	'map_skip_regexp' : [/.*oxygen_iframe=true.*$/, /.*ct_builder=true.*$/],
	'map_missing_cookie_shield' : null,
	'map_detectableKeys' : null,
	'map_detectedKeys' : null,
	'in_iab_context' : false,
	'dependencies' : [],
	'cmode_v2' : null,
	'cmode_v2_implementation_type' : null,
	'cmode_v2_forced_off_ga4_advanced' : null,
	'starting_gconsent' : [],
	'current_gconsent': [],
};

if( !( typeof MAP_JSCOOKIE_SHIELD !== 'undefined' && MAP_JSCOOKIE_SHIELD ) )
{
	MAP_POSTFIX = '';
	MAP_ACCEPTED_ALL_COOKIE_NAME = 'map_accepted_all_cookie_policy';
	MAP_ACCEPTED_SOMETHING_COOKIE_NAME = 'map_accepted_something_cookie_policy';
	MAP_CONSENT_STATUS = 'map_consent_status';

	console.debug( MAP_SYS.maplog + 'MAP_POSTFIX=' + MAP_POSTFIX );
}

if( !MAP_Cookie )
{
	var MAP_Cookie = {
		set: function (name, value, days) {
			try {
				if (days) {
					var date = new Date();
					date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
					var expires = "; expires=" + date.toGMTString();
				} else
					var expires = "";
				document.cookie = name + "=" + value + expires + "; path=/";
			}
			catch( e )
			{
				console.debug( e );
				return null;
			}
		},
		setGMTString: function (name, value, GMTString) {
			try {

				var expires = "; expires=" + GMTString;
				document.cookie = name + "=" + value + expires + "; path=/";
			}
			catch( e )
			{
				console.debug( e );
				return null;
			}
		},
		read: function (name) {
			try {
				var nameEQ = name + "=";
				var ca = document.cookie.split( ';' );
				for (var i = 0; i < ca.length; i++) {
					var c = ca[i];
					while (c.charAt(0) == ' ' ) {
						c = c.substring(1, c.length);
					}
					if (c.indexOf(nameEQ) === 0) {
						return c.substring(nameEQ.length, c.length);
					}
				}
				return null;
			}
			catch( e )
			{
				console.debug( e );
				return null;
			}
		},
		exists: function (name) {
			return (this.read(name) !== null);
		}
	};
}

var MAP =
{
	map_showagain_config:{},

	set: function( args )
	{
		if( this.initted )
		{
			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'MAP already initted: exiting' );
			return;
		}

		var current_url = window.location.href;

		var isMatch = MAP_SYS.map_skip_regexp.some( function( rx ) {
			return rx.test( current_url );
		});


		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'calling MAP set function' );

		if( typeof JSON.parse !== "function" )
		{
			console.error(MAP_SYS.maplog+'Error: My Agile Privacy requires JSON.parse but your browser lacks this support');
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

		if( ( !!this?.settings?.scan_mode &&
			this.settings.scan_mode == 'learning_mode' ) ||
			( !!this?.settings?.verbose_remote_log &&
			this.settings.verbose_remote_log )
		)
		{
			MAP_SYS.map_debug = true;
		}

		if( !!this?.settings?.cookie_reset_timestamp &&
			this.settings.cookie_reset_timestamp )
		{
			if(!( typeof MAP_JSCOOKIE_SHIELD !== 'undefined' && MAP_JSCOOKIE_SHIELD ) )
			{
				MAP_POSTFIX = '_' + this.settings.cookie_reset_timestamp;
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'MAP_POSTFIX=' + MAP_POSTFIX );
			}
		}

		if(!( typeof MAP_JSCOOKIE_SHIELD !== 'undefined' && MAP_JSCOOKIE_SHIELD ) )
		{
			MAP_ACCEPTED_ALL_COOKIE_NAME = MAP_ACCEPTED_ALL_COOKIE_NAME + MAP_POSTFIX;
			MAP_ACCEPTED_SOMETHING_COOKIE_NAME = MAP_ACCEPTED_SOMETHING_COOKIE_NAME + MAP_POSTFIX;
		}

		if( MAP_SYS?.map_debug && !!this?.settings.cookie_reset_timestamp &&
			this.settings.cookie_reset_timestamp )
		{
			console.debug( MAP_SYS.maplog + 'using alt_accepted_all_cookie_name='+MAP_ACCEPTED_ALL_COOKIE_NAME );
			console.debug( MAP_SYS.maplog + 'using alt_accepted_something_cookie_name='+MAP_ACCEPTED_SOMETHING_COOKIE_NAME );
		}

		this.blocked_friendly_name_string = null;

		this.blocked_content_notification_shown = false;

		this.bar_open = false;

		this.settings = args.settings;
		this.bar_elm = jQuery( this.settings.notify_div_id );
		this.showagain_elm = jQuery( "#" + this.settings.showagain_div_id );
		this.settingsModal = jQuery( '#mapSettingsPopup' );
		this.blocked_content_notification = jQuery( '#map-blocked-content-notification-area' );
		this.map_blocked_elems_desc = jQuery( '.map_blocked_elems_desc', this.blocked_content_notification );
		this.map_notification_message = jQuery( '.map_notification-message', this.bar_elm );

		/* buttons */
		this.accept_button = jQuery( '.map-accept-button', this.bar_elm );
		this.reject_button = jQuery( '.map-reject-button', this.bar_elm );
		this.customize_button = jQuery( '.map-customize-button', this.bar_elm );

		if( MAP_SYS?.map_debug )
		{
			console.groupCollapsed( MAP_SYS.maplog + 'settings:' );
			console.debug( this.settings );
			console.groupEnd();
		}

		this.loadDependencies();

		this.setupConsentModeV2();

		this.toggleBar();
		this.createInlineNotify();
		this.attachEvents();

		this.attachAnimations();

		this.initted = true;

		//for preserving scope
		var that = this;

		that.optimizeMobile();

		jQuery( window ).resize(function() {

			that.optimizeMobile();
		});

		this.setupIabTCF();
	},

	//inject code async
	injectCode: function( src=null, callback=null )
	{
		//for preserving scope
		var that = this;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function injectCode src=' + src );

		if( src )
		{
			const script = document.createElement( 'script' );
			script.async = true;
			script.src = src;
			script.onload = function() {

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'loaded script src=' + src );

				if( callback )
				{
					callback();
				}
			}
			document.body.append( script );
		}
	},

	//load dependencies
	loadDependencies: function()
	{
		//for preserving scope
		var that = this;

		var something_to_do = false;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function loadDependencies' );

		//js shield
		if( !( typeof MAP_JSCOOKIE_SHIELD !== 'undefined' && MAP_JSCOOKIE_SHIELD ) &&
			typeof map_full_config !== 'undefined' && typeof map_full_config?.js_shield_url !== 'undefined' && map_full_config?.js_shield_url )
		{
			that.injectCode( map_full_config?.js_shield_url, function(){
			} );

			something_to_do = true;
			MAP_SYS.dependencies.push( map_full_config?.js_shield_url );
		}

		//IabTCF
		if( typeof map_full_config !== 'undefined' && typeof map_full_config?.load_iab_tcf !== 'undefined' && map_full_config?.load_iab_tcf &&
			typeof map_full_config?.iab_tcf_script_url !== 'undefined' &&
			typeof window?.initMyAgilePrivacyIabTCF === 'undefined' )
		{
			that.injectCode( map_full_config?.iab_tcf_script_url, function(){
			} );

			something_to_do = true;
			MAP_SYS.dependencies.push( map_full_config?.iab_tcf_script_url );
		}

		if( something_to_do )
		{
			if( MAP_SYS?.map_debug )
			{
				console.groupCollapsed( MAP_SYS.maplog + ' tried to load the following dependencies:' );
				console.log( MAP_SYS.dependencies );
			}
		}
		else
		{
			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + ' no dependencies to load.' );
		}

		console.groupEnd()
	},

	//get consent status for consent mode
	getGoogleConsentStatus: function( key )
	{
		return MAP_SYS?.current_gconsent[ key ];
	},

	//f for updating given consent
	updateGoogleConsent: function( key, value, updateStatusCookie = false)
	{
		//for preserving scope
		var that = this;

		if( MAP_SYS?.cmode_v2  )
		{
			if( MAP_SYS?.cmode_v2_implementation_type === 'native' )
			{
				var newConsent = {};
				newConsent[key] = value;

		    	gtag('consent', 'update', newConsent );
			}

			if( MAP_SYS?.cmode_v2_implementation_type === 'gtm' )
			{
				var newConsent = {};
				newConsent[key] = value;

				gTagManagerConsentListeners.forEach( ( callback ) => {
					callback( newConsent );
				});
			}

    		var currentGConsent = {...MAP_SYS?.current_gconsent};
    		currentGConsent[key] = value;
    		MAP_SYS.current_gconsent = currentGConsent;

	    	if( updateStatusCookie )
	    	{
	    		that.saveGoogleConsentStatusToCookie( MAP_SYS?.current_gconsent );
	    	}

			return true;
		}

		return false;

   	},

	//from cookie value to object
	parseGoogleConsentStatus: function( consentStatusValue )
	{
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
	},

	//from consent object to cookie
	saveGoogleConsentStatusToCookie : function( consentObject )
	{
		// Convert object values to a string
		var encodedString = Object.keys( consentObject )
		  .map(function( key ) {
		    return key + ':' + ( consentObject[key] === 'granted' );
		  })
		  .join('|');


		MAP_Cookie.set( MAP_CONSENT_STATUS, encodedString, MAP_SYS.map_cookie_expire );

		return true;
	},

	//for google tag manager cookie parsing (gtm)
	exportGoogleConsentObjectFromCookie: function()
	{
		//for preserving scope
		var that = this;

		var cookieValue = MAP_Cookie.read( MAP_CONSENT_STATUS );

		if( cookieValue )
		{
			var this_gconsent = that.parseGoogleConsentStatus( cookieValue );

			return this_gconsent;
		}

		return null;
	},

	//set initial consent from google tag manager (gtm)
	setFromGoogleTagManagerInitialConsent: function( gconsent )
	{
		//for preserving scope
		var that = this;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function googleTagManagerConsentListener' );

		if( gconsent )
		{
			//save starting consent
			MAP_SYS.starting_gconsent = {...gconsent};
			MAP_SYS.current_gconsent = {...gconsent};

			that.saveGoogleConsentStatusToCookie( gconsent );

			//init only status, not events
			that.userPreferenceInit( true );
		}
	},

	//consent listener for gootle tag manager (gtm)
	googleTagManagerConsentListener: function( callback )
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function googleTagManagerConsentListener' );

		gTagManagerConsentListeners.push( callback );
	},

	//init data for consent mode
	setupConsentModeV2 : function()
	{
		//for preserving scope
		var that = this;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function setupConsentModeV2' );

		if( typeof map_full_config === 'undefined' )
		{
			return false;
		}

		MAP_SYS.cmode_v2 = map_full_config?.enable_cmode_v2;
		MAP_SYS.cmode_v2_implementation_type = map_full_config?.cmode_v2_implementation_type;
		MAP_SYS.cmode_v2_forced_off_ga4_advanced = map_full_config?.cmode_v2_forced_off_ga4_advanced;

		if( MAP_SYS.cmode_v2 && MAP_SYS.cmode_v2_implementation_type == 'gtm' )
		{
			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting default value for consent mode (gtm)' );

			var cookieValue = MAP_Cookie.read( MAP_CONSENT_STATUS );

			if( cookieValue )
			{
				var this_gconsent = that.parseGoogleConsentStatus( cookieValue );

				//setting initial current_gconsent value (deep copy using spread operator)
				MAP_SYS.current_gconsent = {...this_gconsent};
			}
		}

		if( MAP_SYS.cmode_v2 && MAP_SYS.cmode_v2_implementation_type == 'native' )
		{
			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting default value for consent mode (native)' );

			//save starting consent
			MAP_SYS.starting_gconsent = map_full_config?.cmode_v2_default_consent_obj;

			var cookieValue = MAP_Cookie.read( MAP_CONSENT_STATUS );

			if( cookieValue )
			{
				var this_gconsent = that.parseGoogleConsentStatus( cookieValue );

				//setting initial current_gconsent value (deep copy using spread operator)
				MAP_SYS.current_gconsent = { ...this_gconsent };

				try {
					gtag( 'consent', 'default', { ...MAP_SYS.current_gconsent } );
				}
				catch( error )
				{
				  console.error( error );
				}

			}
			else
			{
				//no cookie value case

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting default consent (native)' );

				//setting initial current_gconsent value (deep copy using spread operator)
				MAP_SYS.current_gconsent = { ...MAP_SYS.starting_gconsent };

				try {
					gtag( 'consent', 'default', { ...MAP_SYS.starting_gconsent } );

				}
				catch( error )
				{
				  console.error( error );
				}


				that.saveGoogleConsentStatusToCookie( MAP_SYS.current_gconsent );
			}
		}

		return true;
	},

	toggleBar: function()
	{
		//for preserving scope
		var that = this;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function toggleBar' );

		if( !MAP_Cookie.exists( MAP_ACCEPTED_ALL_COOKIE_NAME ) &&
			!MAP_Cookie.exists( MAP_ACCEPTED_SOMETHING_COOKIE_NAME )
			)
		{
			if( MAP_SYS.in_iab_context )
			{
				(async() => {

				    while( !MAP_SYS.map_document_load )
				    {
				    	//console.log( 'not defined yet' );
				        await new Promise( resolve => setTimeout( resolve, 10 ) );
				    }

					(async() => {
					    //console.log("waiting for variable");
					    while( typeof window.MAPIABTCF_brief_html_initted == 'function' &&
					    	!window.MAPIABTCF_brief_html_initted() )
					    {
					    	//console.log( 'not yet defined' );
					        await new Promise( resolve => setTimeout( resolve, 10 ) );
					    }
					    //console.log("variable is defined");
					    that.displayBar();

						setTimeout(function(){
							var $scroll_to_top = jQuery( '.map_notification-message.map-iab-context' );
							$scroll_to_top[0].scrollIntoView({ behavior: 'smooth' });
						}, 400 );

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

		jQuery( 'a.showConsent', that.showagain_elm ).click( function( e ){

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered showConsent' );

			e.preventDefault();
			e.stopImmediatePropagation();

			//animation
			var that_animation = MAP.bar_elm.attr( 'data-animation' );
			var that_id_selector = '#' + MAP.bar_elm.attr( 'id' );

			var animation_params = {
				targets: that_id_selector,
				easing: 'easeInOutQuad',
				duration: 1000
			};

			MAP.showagain_elm.slideUp( 100, function(){

				switch( that_animation )
				{
					case 'slide':
						var y_value = '0vh';
						var x_value = '0vw';

						if( MAP.bar_elm.is( '.map_floating_banner' ) )
						{
							y_value = '3vh';
							x_value = '3vw';
						}

						if( MAP.bar_elm.is( '[class*="mapPositionBottom"]' ) )
						{
							jQuery( that_id_selector ).css( 'bottom', '-100vh' );
							animation_params['bottom'] = y_value;
						}
						else if( MAP.bar_elm.is( '[class*="mapPositionTop"]' ) )
						{
							jQuery( that_id_selector ).css( 'top', '-100vh' );
							animation_params['top'] = y_value;
						}
						else if( MAP.bar_elm.is( '[class*="mapPositionCenterLeft"]' ) )
						{
							jQuery( that_id_selector ).css( 'left', '-100vw' );
							animation_params['left'] = x_value;
						}
						else if( MAP.bar_elm.is( '[class*="mapPositionCenterCenter"]' ) )
						{
							jQuery( that_id_selector ).css( 'top', '-100%' );
							animation_params['top'] = '50%';
						}
						else if( MAP.bar_elm.is( '[class*="mapPositionCenterRight"]' ) )
						{
							jQuery( that_id_selector ).css( 'right', '-100vw' );
							animation_params['right'] = x_value;
						}
						MAP.bar_elm.show();
						anime( animation_params );
						break;

					case 'fade':
						jQuery( that_id_selector ).css( 'opacity', '0' );
						MAP.bar_elm.show();

						animation_params['opacity'] = '1';
						animation_params['duration'] = '500';

						anime( animation_params );

						break;

					case 'zoom':
						jQuery( that_id_selector ).css( 'transform', 'scale(0)' );
						MAP.bar_elm.show();

						animation_params['scale'] = '1';
						anime( animation_params );

						break;

					default: // no animation -> value = "none"
						MAP.bar_elm.show();
						break;
				}

				that.bar_open = true;
				that.optimizeMobile();

			});
		});

		//bof user consent review trigger

		that.bar_elm.bind( 'triggerShowAgainDisplay', function( e ){

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered triggerShowAgainDisplay' );

			e.preventDefault();
			e.stopImmediatePropagation();

			//animation
			var that_animation = MAP.bar_elm.attr( 'data-animation' );
			var that_id_selector = '#' + MAP.bar_elm.attr( 'id' );

			var animation_params = {
				targets: that_id_selector,
				easing: 'easeInOutQuad',
				duration: 1000
			};

			MAP.showagain_elm.slideUp( 100, function(){
				switch( that_animation )
				{
					case 'slide':
						var y_value = '0vh';
						var x_value = '0vw';

						if( MAP.bar_elm.is( '.map_floating_banner' ) )
						{
							y_value = '3vh';
							x_value = '3vw';
						}

						if( MAP.bar_elm.is( '[class*="mapPositionBottom"]' ) )
						{
							jQuery( that_id_selector ).css( 'bottom', '-100vh' );
							animation_params['bottom'] = y_value;
						}
						else if( MAP.bar_elm.is( '[class*="mapPositionTop"]' ) )
						{
							jQuery( that_id_selector ).css( 'top', '-100vh' );
							animation_params['top'] = y_value;
						}
						else if( MAP.bar_elm.is( '[class*="mapPositionCenterLeft"]' ) )
						{
							jQuery( that_id_selector ).css( 'left', '-100vw' );
							animation_params['left'] = x_value;
						}
						else if( MAP.bar_elm.is( '[class*="mapPositionCenterCenter"]' ) )
						{
							jQuery( that_id_selector ).css( 'top', '-100%' );
							animation_params['top'] = '50%';
						}
						else if( MAP.bar_elm.is( '[class*="mapPositionCenterRight"]' ) )
						{
							jQuery( that_id_selector ).css( 'right', '-100vw' );
							animation_params['right'] = x_value;
						}

						MAP.bar_elm.show();
						anime( animation_params );
						break;

					case 'fade':
						jQuery( that_id_selector ).css( 'opacity', '0' );
						MAP.bar_elm.show();

						animation_params['opacity'] = '1';
						animation_params['duration'] = '500';

						anime( animation_params );

						break;

					case 'zoom':
						jQuery( that_id_selector ).css( 'transform', 'scale(0)' );
						MAP.bar_elm.show();

						animation_params['scale'] = '1';
						anime( animation_params );
						break;

					default: // no animation -> value = "none"
						MAP.bar_elm.show();
						break;
				}

				that.bar_open = true;
				that.optimizeMobile();

			});
		});

		jQuery( 'body' ).on( 'click', '.showConsentAgain', function( e ){
			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered showConsentAgain' );

			e.preventDefault();
			e.stopImmediatePropagation();

			that.bar_elm.trigger( 'triggerShowAgainDisplay' );
		});

		//eof user consent review trigger
	},

	createInlineNotify: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function createInlineNotify' );

		//for preserving scope
		var that = this;

		if( typeof CookieShield !== 'undefined' &&
			CookieShield )
		{
			var detectedKeys = CookieShield.getDetectedKeys();

			jQuery.each( detectedKeys, function( key, value ) {

				var api_key = value;

				var $custom_ref = jQuery( '.map_custom_notify.map_api_key_' + api_key );

				if( $custom_ref.length )
				{
					$custom_ref.show();
				}
			});
		}

		var $map_blocked_content = jQuery( '.map_blocked_content' );

		$map_blocked_content.each(function(){

			var $this = jQuery( this );
			var api_key = $this.attr( 'data-cookie-api-key' );

			var $custom_ref = jQuery( '.map_custom_notify.map_api_key_' + api_key );

			if( $custom_ref.length )
			{
				$custom_ref.show();
			}

		});

		var $show_inline_notify = jQuery( '.iframe_src_blocked.map_show_inline_notify' );

		$show_inline_notify.each(function(){

			var $this = jQuery( this );
			var height = $this.height();
			var width = $this.width();
			var api_key = $this.attr( 'data-cookie-api-key' );

			var the_friendly_name = '';
			var friendly_name = $this.attr( 'data-friendly_name' );

			if( friendly_name )
			{
				the_friendly_name = friendly_name;
			}

			$this.hide();

			var html = "<div class='map_inline_notify showConsentAgain' data-cookie-api-key='"+api_key+"'>"+that.settings.blocked_content_text+"<br>"+the_friendly_name+"</div>";
			var $injected = jQuery( html ).insertAfter( $this );

			if( height > 0 )
			{
				$injected.height( height );
			}

			if( width > 0 )
			{
				$injected.width( width );
			}

			if( that.settings.inline_notify_color )
			{
				$injected.css({'color':that.settings.inline_notify_color});
			}

			if( that.settings.inline_notify_background )
			{
				$injected.css({'background':that.settings.inline_notify_background});
			}

		});
	},

	setupIabTCF: function()
	{
		//for preserving scope
		var that = this;

		var $map_consent_extrawrapper = jQuery( '.map-consent-extrawrapper', that.settingsModal );

		if( $map_consent_extrawrapper.length )
		{
			MAP_SYS.in_iab_context = true;

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function setupIabTCF' );

			//first layer
			jQuery( '.map-triggerGotoIABTCF', that.bar_elm ).bind( 'click', function( e ){

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-triggerGotoIABTCF click' );

				e.preventDefault();
				e.stopImmediatePropagation();

				MAP.settingsModal.addClass( 'map-show' ).css({'opacity':0}).animate({'opacity':1});
				MAP.settingsModal.removeClass( 'map-blowup map-out' ).addClass("map-blowup");
				jQuery( 'body' ).addClass( 'map-modal-open' );
				jQuery( '.map-settings-overlay' ).addClass( 'map-show' );
				if(!jQuery( '.map-settings-mobile' ).is( ':visible' ))
				{
					MAP.settingsModal.find( '.map-nav-link:eq(0)' ).click();
				}

				jQuery( '.map-wrappertab-navigation li a[href="#map-privacy-iab-tcf-wrapper"]', $map_consent_extrawrapper ).trigger( 'click' );

			});

			jQuery( '.map-triggerGotoIABTCFVendors', that.bar_elm ).bind( 'click', function( e ){

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-triggerGotoIABTCFVendors click' );

				e.preventDefault();
				e.stopImmediatePropagation();

				MAP.settingsModal.addClass( 'map-show' ).css({'opacity':0}).animate({'opacity':1});
				MAP.settingsModal.removeClass( 'map-blowup map-out' ).addClass("map-blowup");
				jQuery( 'body' ).addClass( 'map-modal-open' );
				jQuery( '.map-settings-overlay' ).addClass( 'map-show' );
				if(!jQuery( '.map-settings-mobile' ).is( ':visible' ))
				{
					MAP.settingsModal.find( '.map-nav-link:eq(0)' ).click();
				}

				jQuery( '.map-wrappertab-navigation li a[href="#map-privacy-iab-tcf-wrapper"]', $map_consent_extrawrapper ).trigger( 'click' );

				setTimeout(function(){

					var $vendor_list_button = jQuery( '#map-iab-tcf-vendor-list' );

					$vendor_list_button.trigger( 'click' );

					setTimeout(function(){
						var $vendor_list_scrollto = jQuery( '#map-iab-tcf-vendor-list-scroll-here' );
						$vendor_list_scrollto[0].scrollIntoView({ behavior: 'smooth' });
					}, 200 );


				}, 200);

			});

			//bof nav part
			jQuery( '.map-wrappertab-navigation li a', $map_consent_extrawrapper ).bind( 'click', function(e){

				e.preventDefault();

				var $this = jQuery( this );
				var tabHref = $this.attr( 'href' );
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
					(async() => {

					    while( !MAP_SYS.map_document_load )
					    {
					    	//console.log( 'not yet defined' );
					        await new Promise( resolve => setTimeout( resolve, 10 ) );
					    }

						if( typeof window.MAPIABTCF_showCMPUI === 'function' )
						{
							window.MAPIABTCF_showCMPUI();
						}

					})();
				}
				else
				{
					(async() => {

					    while( !MAP_SYS.map_document_load )
					    {
					    	//console.log( 'not yet defined' );
					        await new Promise( resolve => setTimeout( resolve, 10 ) );
					    }

						if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
						{
							window.MAPIABTCF_hideCMPUI();
						}

					})();
				}

				jQuery( '.map-wrappertab-navigation li a', $map_consent_extrawrapper ).removeClass( 'active-wrappertab-nav' ) ;
				$this.addClass( 'active-wrappertab-nav' );

				jQuery( '.map-wrappertab', $map_consent_extrawrapper ).removeClass( 'map-wrappertab-active' );
				jQuery( '.map-wrappertab.' + tabHref, $map_consent_extrawrapper ).addClass( 'map-wrappertab-active' ) ;

			});

			//eof nav part

			//bof IAB TCF init and events
			(async() => {

			    while( !MAP_SYS.map_document_load )
			    {
			    	//console.log( 'not yet defined' );
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

	},

	attachEvents: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function attachEvents' );

		//for preserving scope
		var that = this;

		that.accept_button.click( function( e ){

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-accept-button click' );

			e.preventDefault();
			e.stopImmediatePropagation();

			var $this = jQuery( this );

			//check consent-mode checkbox
			jQuery( '.map-consent-mode-preference-checkbox', that.settingsModal ).each( function(){
				var $elem = jQuery( this );
				$elem.prop( 'checked', true );

				var consent_key = $elem.attr( 'data-consent-key' );
				that.updateGoogleConsent( consent_key, 'granted', true );
			});

			//check user-preference checkbox
			jQuery( '.map-user-preference-checkbox', that.settingsModal ).each( function(){

				var $this = jQuery( this );

				var cookieName = 'map_cookie_' + $this.attr( 'data-cookie-baseindex' ) + MAP_POSTFIX;

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting 1 to cookieName=' + cookieName );

				MAP_Cookie.set( cookieName, '1', MAP_SYS.map_cookie_expire );

				$this.prop( 'checked', true );
			});


			//check iab-preference checkbox
			jQuery( '.map-user-iab-preference-checkbox', that.settingsModal ).each( function(){
				var $elem = jQuery( this );
				$elem.prop( 'checked', true );
			});

			(async() => {

			    while( !MAP_SYS.map_document_load )
			    {
			    	//console.log( 'not yet defined' );
			        await new Promise( resolve => setTimeout( resolve, 10 ) );
			    }

				if( typeof window.MAPIABTCF_acceptAllConsent === 'function' )
				{
					window.MAPIABTCF_doSetUserInteraction();
					window.MAPIABTCF_acceptAllConsent( true );
				}

			})();

			MAP.accept_close();
		});

		that.reject_button.click( function( e ){

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-reject-button click' );

			e.preventDefault();
			e.stopImmediatePropagation();

			var $this = jQuery( this );

			//uncheck consent-mode checkbox
			jQuery( '.map-consent-mode-preference-checkbox', that.settingsModal ).each( function(){
				var $elem = jQuery( this );
				$elem.prop( 'checked', false );

				var consent_key = $elem.attr( 'data-consent-key' );
				that.updateGoogleConsent( consent_key, 'denied', true );
			});

			//uncheck user-preference checkbox

			jQuery( '.map-user-preference-checkbox', that.settingsModal ).each( function(){

				var $this = jQuery( this );

				var cookieName = 'map_cookie_' + $this.attr( 'data-cookie-baseindex' ) + MAP_POSTFIX;

				MAP_Cookie.set( cookieName, '-1', MAP_SYS.map_cookie_expire );

				$this.prop( 'checked', false );

			});

			//uncheck iab-preference checkbox
			jQuery( '.map-user-iab-preference-checkbox', that.settingsModal ).each( function(){
				var $elem = jQuery( this );
				$elem.prop( 'checked', false );
			});

			(async() => {

			    while( !MAP_SYS.map_document_load )
			    {
			    	//console.log( 'not yet defined' );
			        await new Promise( resolve => setTimeout( resolve, 10 ) );
			    }

				if( typeof window.MAPIABTCF_denyAllConsent === 'function' )
				{
					window.MAPIABTCF_doSetUserInteraction();
					window.MAPIABTCF_denyAllConsent( true );
				}

			})();

			MAP.reject_close();
		});


		that.customize_button.bind( 'click', function( e ){

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-customize-button click' );

			e.preventDefault();
			e.stopImmediatePropagation();

			MAP.settingsModal.addClass( 'map-show' ).css({'opacity':0}).animate({'opacity':1});
			MAP.settingsModal.removeClass( 'map-blowup map-out' ).addClass("map-blowup");
			jQuery( 'body' ).addClass( 'map-modal-open' );
			jQuery( '.map-settings-overlay' ).addClass( 'map-show' );
			if(!jQuery( '.map-settings-mobile' ).is( ':visible' ))
			{
				MAP.settingsModal.find( '.map-nav-link:eq(0)' ).click();
			}

			var $map_consent_extrawrapper = jQuery( '.map-consent-extrawrapper', that.settingsModal );

			if( $map_consent_extrawrapper.length )
			{
				jQuery( '.map-wrappertab-navigation li a[href="#map-privacy-cookie-thirdypart-wrapper"]', $map_consent_extrawrapper ).trigger( 'click' );
			}
		});

		jQuery( '#mapModalClose' ).click( function( e ){

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered mapModalClose click' );

			e.preventDefault();
			e.stopImmediatePropagation();

			MAP.closeSettingsPopup();
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
			    	//console.log( 'not yet defined' );
			        await new Promise( resolve => setTimeout( resolve, 10 ) );
			    }

			   that.userPreferenceInit( false );

			})();

		}
		else
		{
			that.userPreferenceInit( false );
		}
	},

	attachAnimations: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function attachAnimations' );

		//for preserving scope
		var that = this;

		if( jQuery( '#map-accept-button', that.bar_elm ).attr( 'data-animate' ) == 'true' )
		{
			var animation = jQuery( '#map-accept-button' ).attr( 'data-animation-effect' );
			var delay = parseInt(jQuery( '#map-accept-button' ).attr( 'data-animation-delay' ))*1000;
			var repeat = parseInt(jQuery( '#map-accept-button' ).attr( 'data-animation-repeat' ));
			var all_animation_classes = ['animate__animated', animation];

			var iteration_counter = 0;

			var animInterval =  setInterval(function()
			{
				if(iteration_counter == repeat)
				{
					clearInterval(animInterval);
				}
				else
				{
					jQuery( '#map-accept-button' ).addClass(all_animation_classes);

					setTimeout(function()
					{
						jQuery( '#map-accept-button' ).removeClass(all_animation_classes);
						iteration_counter ++;

					}, 900 );
				}

			}, delay );
		}
	},

	checkBlockedContent: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function checkBlockedContent' );

		//for preserving scope
		var that = this;

		var $map_blocked_content = jQuery( '.map_blocked_content:not(._is_activated)' );

		if( $map_blocked_content.length )
		{
			//calc data
			var blocked_friendly_name = [];

			$map_blocked_content.each( function(){

				var this_friendly_name = jQuery( this ).attr( 'data-friendly_name' );

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

				jQuery.each( other_names, function( i, v ){

					if( v )
					{
						blocked_friendly_name.push( v );
					}
				});
			}

			var blocked_friendly_name_unique = blocked_friendly_name.filter((v, i, a) => a.indexOf(v) === i);

			that.blocked_friendly_name_string = blocked_friendly_name_unique.join( ', ' );

			//check if to show
			if( blocked_friendly_name_unique.length &&
				( !that.bar_open || that.settings.show_ntf_bar_on_not_yet_consent_choice ) )
			{
				if( that.blocked_content_notification.length )
				{
					if( that.blocked_friendly_name_string )
					{
						that.map_blocked_elems_desc.html( that.blocked_friendly_name_string );
					}
					else
					{
						that.map_blocked_elems_desc.slideDown( 100 );
					}

					that.blocked_content_notification_shown = true;

					var blocked_content_notify_auto_shutdown_time = 3000;

					//console.debug( that.settings.blocked_content_notify_auto_shutdown_time );

					if( that.settings.blocked_content_notify_auto_shutdown_time )
					{
						blocked_content_notify_auto_shutdown_time = that.settings.blocked_content_notify_auto_shutdown_time;
					}

					setTimeout(function(){
						that.blocked_content_notification.slideDown( 100 );

						if( that.blocked_content_notification.hasClass( 'autoShutDown' ) )
						{
							setTimeout(function(){
								that.blocked_content_notification.slideUp( 100 );
							}, blocked_content_notify_auto_shutdown_time );
						}

					}, 1000 );
				}
			}
		}
		else
		{
			if( that.blocked_content_notification.length )
			{
				this.blocked_content_notification.slideUp( 100 );
			}
		}

		this.administratorNotices();

	},

	displayBar: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function displayBar' );

		//for preserving scope
		var that = this;

		//animation
		var that_animation = that.bar_elm.attr( 'data-animation' );
		var that_id_selector = '#' + that.bar_elm.attr( 'id' );

		var animation_params = {
			targets: that_id_selector,
			easing: 'easeInOutQuad',
			duration: 1000
		};

		var y_value = '0vh';
		var x_value = '0vw';

		if( that.bar_elm.is( '.map_floating_banner' ) )
		{
			y_value = '3vh';
			x_value = '3vw';
		}

		switch( that_animation )
		{
			case 'slide':

				if( that.bar_elm.is( '[class*="mapPositionBottom"]' ) )
				{
					jQuery( that_id_selector ).css( 'bottom', '-100vh' );
					animation_params['bottom'] = y_value;
				}
				else if( that.bar_elm.is( '[class*="mapPositionTop"]' ) )
				{
					jQuery( that_id_selector ).css( 'top', '-100vh' );
					animation_params['top'] = y_value;
				}
				else if( that.bar_elm.is( '[class*="mapPositionCenterLeft"]' ) )
				{
					jQuery( that_id_selector ).css( 'left', '-100vw' );
					animation_params['left'] = x_value;
				}
				else if( that.bar_elm.is( '[class*="mapPositionCenterCenter"]' ) )
				{
					jQuery( that_id_selector ).css( 'top', '-100%' );
					animation_params['top'] = '50%';
				}
				else if( that.bar_elm.is( '[class*="mapPositionCenterRight"]' ) )
				{
					jQuery( that_id_selector ).css( 'right', '-100vw' );
					animation_params['right'] = x_value;
				}

				that.bar_elm.show();

				anime( animation_params );
				break;

			case 'fade':
				jQuery( that_id_selector ).css( 'opacity', '0' );

				that.bar_elm.show();

				animation_params['opacity'] = '1';
				animation_params['duration'] = '500';

				anime( animation_params );

				break;

			case 'zoom':
				jQuery( that_id_selector ).css( 'transform', 'scale(0)' );

				that.bar_elm.show();

				animation_params['scale'] = '1';
				anime( animation_params );

				break;

			default: // no animation -> value = "none"
				that.bar_elm.show();
				break;
		}

		that.showagain_elm.slideUp( 100 );

		that.bar_open = true;

		that.optimizeMobile();
	},

	hideBar: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function hideBar' );

		if( Boolean( this.settings.showagain_tab ) )
		{
			//show
			this.showagain_elm.slideDown( 100 );
		}
		else
		{
			//hide
			this.showagain_elm.slideUp( 100 );
		}

		//hide
		//animation
		var that_animation = this.bar_elm.attr( 'data-animation' );
		var that_id_selector = '#' + this.bar_elm.attr( 'id' );

		var animation_params = {
			targets: that_id_selector,
			easing: 'easeInOutQuad',
			duration: 700,
			complete: function(anim) {
				MAP.bar_elm.hide();
			}
		};

		switch( that_animation )
		{
			case 'slide':

				if( this.bar_elm.is( '[class*="mapPositionBottom"]' ) )
				{
					animation_params['bottom'] = '-100vh';
				}
				else if( this.bar_elm.is( '[class*="mapPositionTop"]' ) )
				{
					animation_params['top'] = '-100vh';
				}
				else if( this.bar_elm.is( '[class*="mapPositionCenterLeft"]' ) )
				{
					animation_params['left'] = '-100vw';
				}
				else if( this.bar_elm.is( '[class*="mapPositionCenterCenter"]' ) )
				{
					animation_params['top'] = '-100%';
				}
				else if( this.bar_elm.is( '[class*="mapPositionCenterRight"]' ) )
				{
					animation_params['right'] = '-100vw';
				}

				anime( animation_params );
				break;

			case 'fade':

				animation_params['opacity'] = '0';
				animation_params['duration'] = '500';

				anime( animation_params );

				break;

			case 'zoom':

				animation_params['scale'] = '0';
				anime( animation_params );


				break;

			default: // no animation -> value = "none"
				this.bar_elm.hide();
				break;
		}

		this.bar_open = false;

		(async() => {

		    while( !MAP_SYS.map_document_load )
		    {
		    	//console.log( 'not yet defined' );
		        await new Promise( resolve => setTimeout( resolve, 10 ) );
		    }

			if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
			{
				window.MAPIABTCF_hideCMPUI();
			}

		})();
	},

	optimizeMobile: function()
	{
		//for preserving scope
		var that = this;

		setTimeout(function(){
			if( that.bar_open )
			{
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'optimizing for mobile view' );

				var viewport_width = window.innerWidth;

				if( viewport_width <= 450 )
				{
					var viewport_height = window.innerHeight;
					var internal_height = viewport_height - 250;

					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'map mobile optimizing: viewport_width=' + viewport_width + ' , internal_height=' + internal_height );

					that.map_notification_message.addClass( 'extraNarrow' ).css( 'max-height', internal_height + 'px' );
				}
				else
				{
					that.map_notification_message.removeClass( 'extraNarrow' ).css( 'max-height', '' );
				}
			}

			that.setOverflowMaxHeight();

		}, 400 );
	},

	accept_close: function()
	{
		//for preserving scope
		var that = this;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function accept_close' );

		MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );

		//animation
		var that_animation = this.bar_elm.attr( 'data-animation' );
		var that_id_selector = '#' + this.bar_elm.attr( 'id' );

		var animation_params = {
			targets: that_id_selector,
			easing: 'easeInOutQuad',
			duration: 700,
			complete: function(anim) {
				MAP.bar_elm.hide();
			}
		};

		switch( that_animation )
		{
			case 'slide':

				if( this.bar_elm.is( '[class*="mapPositionBottom"]' ) )
				{
					animation_params['bottom'] = '-100vh';
				}
				else if( this.bar_elm.is( '[class*="mapPositionTop"]' ) )
				{
					animation_params['top'] = '-100vh';
				}
				else if( this.bar_elm.is( '[class*="mapPositionCenterLeft"]' ) )
				{
					animation_params['left'] = '-100vw';
				}
				else if( this.bar_elm.is( '[class*="mapPositionCenterCenter"]' ) )
				{
					animation_params['left'] = '-100%';
				}
				else if( this.bar_elm.is( '[class*="mapPositionCenterRight"]' ) )
				{
					animation_params['right'] = '-100vw';
				}

				anime(animation_params);
			break;

			case 'fade':

				animation_params['opacity'] = '0';
				animation_params['duration'] = '500';

				anime(animation_params);

			break;

			case 'zoom':

				animation_params['scale'] = '0';
				anime(animation_params);

			break;

			default: // no animation -> value = "none"
				this.bar_elm.hide();
			break;
		}

		if( Boolean( that.settings.showagain_tab ) )
		{
			that.showagain_elm.slideDown( 100 );
		}

		that.tryToUnblockScripts( true );

		(async() => {

		    while( !MAP_SYS.map_document_load )
		    {
		    	//console.log( 'not yet defined' );
		        await new Promise( resolve => setTimeout( resolve, 10 ) );
		    }

			if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
			{
				window.MAPIABTCF_hideCMPUI();
			}

		})();

		return false;
	},

	reject_close: function()
	{
		//for preserving scope
		var that = this;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function reject_close' );

		MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );

		//animation
		var that_animation = that.bar_elm.attr( 'data-animation' );
		var that_id_selector = '#' + that.bar_elm.attr( 'id' );

		var animation_params = {
			targets: that_id_selector,
			easing: 'easeInOutQuad',
			duration: 700,
			complete: function(anim) {
				MAP.bar_elm.hide();
			}
		};

		switch( that_animation )
		{
			case 'slide':

				if( that.bar_elm.is( '[class*="mapPositionBottom"]' ) )
				{
					animation_params['bottom'] = '-100vh';
				}
				else if( that.bar_elm.is( '[class*="mapPositionTop"]' ) )
				{
					animation_params['top'] = '-100vh';
				}
				else if( that.bar_elm.is( '[class*="mapPositionCenterLeft"]' ) )
				{
					animation_params['left'] = '-100vw';
				}
				else if( that.bar_elm.is( '[class*="mapPositionCenterRight"]' ) )
				{
					animation_params['right'] = '-100vw';
				}

				anime( animation_params );
				break;

			case 'fade':

				animation_params['opacity'] = '0';
				animation_params['duration'] = '500';

				anime( animation_params );

				break;

			case 'zoom':

				animation_params['scale'] = '0';
				anime( animation_params );


				break;

			default: // no animation -> value = "none"
				that.bar_elm.hide();
				break;
		}

		this.bar_open = false;

		if( Boolean( that.settings.showagain_tab ) )
		{
			that.showagain_elm.slideDown(that.settings.animate_speed_show);
		}

		that.tryToUnblockScripts( true );

		setTimeout(function(){
			that.checkBlockedContent();
		},200);

		(async() => {

		    while( !MAP_SYS.map_document_load )
		    {
		    	//console.log( 'not yet defined' );
		        await new Promise( resolve => setTimeout( resolve, 10 ) );
		    }

			if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
			{
				window.MAPIABTCF_hideCMPUI();
			}

		})();

		return false;
	},

	tryToUnblockScripts: function( from_user_interaction = false )
	{
		let need_reload = false;
		let do_calc_need_reload = false;

		if( from_user_interaction )
		{
			do_calc_need_reload = true;
		}

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + `tryToUnblockScripts from_user_interaction=${from_user_interaction}` );

		//for preserving scope
		var that = this;

		var once_functions_to_execute = [];

		var $map_cookie_description_wrapper = jQuery( '.map_cookie_description_wrapper', that.settingsModal );

		$map_cookie_description_wrapper.each( function(){

			var $this = jQuery( this );

			var baseIndex = $this.attr( 'data-cookie-baseindex' );
			var cookieName = 'map_cookie_' + baseIndex + MAP_POSTFIX;
			var api_key = $this.attr( 'data-cookie-api-key' );

			//console.debug( MAP_SYS.maplog + 'try to process ' + api_key );

			if( do_calc_need_reload && $this.hasClass( 'map_page_reload_on_user_consent' ) )
			{
				need_reload = true;
			}

			var cookieValue = MAP_Cookie.read( cookieName );

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'debug ' + api_key + ' ' + cookieName + ' ' + cookieValue );

			var always_on = false;
			var activate_anyway = false;

			if( $this.hasClass( '_always_on' ) )
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

			if( ( MAP_Cookie.exists( MAP_ACCEPTED_ALL_COOKIE_NAME ) && MAP_Cookie.read( cookieName ) == '1' ) )
			{
				accepted_all = true;
			}

			if( cookieValue == "1" ||
				always_on ||
				activate_anyway ||
				accepted_all )
			{
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + '-->activating api_key=' + api_key + ' cookieName=' + cookieName + ' cookieValue=' + cookieValue + ' always_on=' + always_on  + ' accepted_all=' + accepted_all );

				if( activate_anyway )
				{
					$this.addClass( '_is_activated_anyway' );
				}
				else
				{
					$this.addClass( '_is_activated' );
				}

				if( $this.hasClass( '_with_code' ) )
				{
					var $the_script = jQuery( 'script.my_agile_privacy_activate._js_noscript_type_mode[data-cookie-baseindex="'+baseIndex+'"]' );

					var $the_raw_script = jQuery( 'textarea.my_agile_privacy_activate._raw_type_mode[data-cookie-baseindex="'+baseIndex+'"]' );

					if( $the_script.length )
					{
						if( !$the_script.hasClass( '_is_activated' ) )
						{
							$the_script.addClass( '_is_activated' );

							var script = document.createElement( 'script' );
							script.className = '_is_activated';
							script.innerHTML = $the_script[0].innerHTML;

							if( MAP_SYS?.map_debug ) console.debug( script.innerHTML );

							document.head.appendChild( script );
						}
					}

					if( $the_raw_script.length )
					{
						if( !$the_raw_script.hasClass( '_is_activated' ) )
						{
							$the_raw_script.addClass( '_is_activated' );

							var the_raw_script = $the_raw_script.val();

							if( MAP_SYS?.map_debug ) console.debug( the_raw_script );

							jQuery( "body" ).append( the_raw_script );
						}
					}
				}

				if( api_key )
				{
					//bof custom notify
					var $custom_ref = jQuery( '.map_custom_notify.map_api_key_' + api_key );
					if( $custom_ref.length )
					{
						var on_unblock_remove_class = $custom_ref.attr( 'data-on-unblock-remove-class' );

						if( on_unblock_remove_class )
						{
							var $the_blocked_content = jQuery( '.my_agile_privacy_activate[data-cookie-api-key="'+api_key+'"]' );

							$the_blocked_content.removeClass( on_unblock_remove_class );
						}

						$custom_ref.remove();
					}
					//eof custom notify

					var $map_src_script_blocked = jQuery( 'script.my_agile_privacy_activate.autoscan_mode.map_src_script_blocked[data-cookie-api-key="'+api_key+'"]' );

					var $map_inline_script_blocked = jQuery( 'script.my_agile_privacy_activate.autoscan_mode.map_inline_script_blocked[data-cookie-api-key="'+api_key+'"]' );

					var $iframe_src_blocked = jQuery( 'iframe.my_agile_privacy_activate.autoscan_mode.iframe_src_blocked[data-cookie-api-key="'+api_key+'"]' );

					var $css_href_blocked = jQuery( 'link.my_agile_privacy_activate.autoscan_mode.css_href_blocked[data-cookie-api-key="'+api_key+'"]' );

					var $img_src_blocked = jQuery( 'img.my_agile_privacy_activate.autoscan_mode.img_src_blocked[data-cookie-api-key="'+api_key+'"]' );


					if( $map_src_script_blocked && $map_src_script_blocked.length )
					{
						$map_src_script_blocked.each( function(){

							var $_this = jQuery( this );

							if( !$_this.hasClass( '_is_activated' ) )
							{
								$_this.addClass( '_is_activated' );

								if( $_this.hasClass( 'custom_patch_apply' ) )
								{
									var classes = $_this.attr( 'class' ).split( ' ' );

									jQuery.each( classes, function( i, v ){

										if( v &&
											v != '' &&
											v.startsWith( 'map_trigger_custom_patch_' ) &&
											typeof window[ v ]  === 'function' )
										{
											setTimeout(function(){
												window[ v ]();
											}, 4000);
										}

									});
								}

								if( $_this.hasClass( 'once_custom_patch_apply' ) )
								{
									var classes = $_this.attr( 'class' ).split( ' ' );

									jQuery.each( classes, function( i, v ){

										if( v &&
											v != '' &&
											v.startsWith( 'map_trigger_custom_patch_' ) &&
											typeof window[ v ]  === 'function' )
										{
											if( !once_functions_to_execute.includes( v ) )
											{
												once_functions_to_execute.push( v );
											}
										}
									});
								}

								if( $_this.hasClass( 'mapWait' ) )
								{
									setTimeout(function(){
										var script = document.createElement( 'script' );
										script.className = '_is_activated';

										script = cloneNodeAttributeToAnother( $_this, script );

										var blocked_src = $_this.attr( 'unblocked_src' );

										if( blocked_src )
										{
											script.src = blocked_src;
										}

										$_this[0].insertAdjacentElement( 'afterend', script );

									}, 2000);
								}
								else
								{
									var script = document.createElement( 'script' );
									script.className = '_is_activated';

									script = cloneNodeAttributeToAnother( $_this, script );

									var blocked_src = $_this.attr( 'unblocked_src' );

									if( blocked_src )
									{
										script.src = blocked_src;
									}

									$_this[0].insertAdjacentElement( 'afterend', script );
								}
							}
						});
					}

					if( $map_inline_script_blocked && $map_inline_script_blocked.length )
					{
						$map_inline_script_blocked.each( function(){

							var $_this = jQuery( this );

							if( !$_this.hasClass( '_is_activated' ) )
							{
								$_this.addClass( '_is_activated' );

								if( $_this.hasClass( 'custom_patch_apply' ) )
								{
									var classes = $_this.attr( 'class' ).split( ' ' );

									jQuery.each( classes, function( i, v ){

										if( v &&
											v != '' &&
											v.startsWith( 'map_trigger_custom_patch_' ) &&
											typeof window[ v ]  === 'function' )
										{
											setTimeout(function(){
												window[ v ]();
											}, 2000);
										}
									});
								}

								if( $_this.hasClass( 'once_custom_patch_apply' ) )
								{
									var classes = $_this.attr( 'class' ).split( ' ' );

									jQuery.each( classes, function( i, v ){

										if( v &&
											v != '' &&
											v.startsWith( 'map_trigger_custom_patch_' ) &&
											typeof window[ v ]  === 'function' )
										{
											if( !once_functions_to_execute.includes( v ) )
											{
												once_functions_to_execute.push( v );
											}
										}
									});
								}

								if( $_this.hasClass( 'mapWait' ) )
								{
									setTimeout(function(){
										var script = document.createElement( 'script' );
										script.className = '_is_activated';
										script.innerHTML = $_this[0].innerHTML;

										if( MAP_SYS?.map_debug ) console.debug( script.innerHTML );

										document.head.appendChild( script );
									}, 2000);
								}
								else
								{
									var script = document.createElement( 'script' );
									script.className = '_is_activated';
									script.innerHTML = $_this[0].innerHTML;

									if( MAP_SYS?.map_debug ) console.debug( script.innerHTML );

									document.head.appendChild( script );
								}
							}
						});
					}

					if( $iframe_src_blocked && $iframe_src_blocked.length )
					{
						$iframe_src_blocked.each( function(){

							var $_this = jQuery( this );

							if( !$_this.hasClass( '_is_activated' ) )
							{
								$_this.addClass( '_is_activated' );

								$_this.attr( 'src', $_this.attr( 'unblocked_src' ) );

								$_this.show();

								var $ref = jQuery( '.map_inline_notify[data-cookie-api-key="'+api_key+'"]' );

								if( $ref.length )
								{
									$ref.remove();
								}
							}
						});
					}

					if( $css_href_blocked && $css_href_blocked.length )
					{
						$css_href_blocked.each( function(){

							var $_this = jQuery( this );

							if( !$_this.hasClass( '_is_activated' ) )
							{
								$_this.addClass( '_is_activated' );

								$_this.attr( 'href', $_this.attr( 'unblocked_href' ) );
							}
						});
					}

					if( $img_src_blocked && $img_src_blocked.length )
					{
						$img_src_blocked.each( function(){

							var $_this = jQuery( this );

							if( !$_this.hasClass( '_is_activated' ) )
							{
								$_this.addClass( '_is_activated' );

								$_this.attr( 'src', $_this.attr( 'unblocked_src' ) );
							}
						});
					}
				}
			}
			else
			{
				$this.removeClass( '_is_activated' );
			}


		});

		if( need_reload )
		{
			window.location.reload();
		}

		//execution of once custom patch functions
		jQuery.each( once_functions_to_execute, function( i, v ){
			setTimeout(function(){
				window[ v ]();

			}, 1000);
		});

		setTimeout( function(){
			that.checkBlockedContent();
		},500);


		setTimeout( function(){
			jQuery( 'body' ).trigger( 'MAP_PRIVACY_CHANGE' );
		},100);
	},

	//init user preferences and bind events
	//with only_init_status==true events are not binded
	userPreferenceInit: function( only_init_status = false )
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'userPreferenceInit only_init_status='+only_init_status );

		//for preserving scope
		var that = this;

		//init part (cookie consent)
		jQuery( '.map-user-preference-checkbox', that.settingsModal ).each( function(){

			var $this = jQuery( this );

			var cookieName = 'map_cookie_' + $this.attr( 'data-cookie-baseindex' ) + MAP_POSTFIX;

			var cookieValue = MAP_Cookie.read( cookieName );
			if( cookieValue == null )
			{
				if( jQuery( this ).is( ':checked' ) )
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting 1 to cookieName=' + cookieName );

					MAP_Cookie.set( cookieName, '1', MAP_SYS.map_cookie_expire );
				}else
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting -1 to cookieName' + cookieName );

					MAP_Cookie.set( cookieName, '-1', MAP_SYS.map_cookie_expire );
				}
			}
			else
			{
				if( cookieValue == "1" )
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting checked for cookieName' + cookieName );

					$this.prop( 'checked', true );
				}
				else
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting unchecked for cookieName' + cookieName );

					$this.prop( 'checked', false );
				}
			}

		});

		//click event
		if( only_init_status == false )
		{
			jQuery( '.map-user-preference-checkbox', that.settingsModal ).click( function( e ){

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-user-preference-checkbox click' );

				e.stopImmediatePropagation();

				var $this = jQuery( this );

				var cookieName = 'map_cookie_' + $this.attr( 'data-cookie-baseindex' ) + MAP_POSTFIX;

				var dataID = jQuery( this ).attr( 'data-id' );
				var currentToggleElm = jQuery( '.map-user-preference-checkbox[data-cookie-baseindex='+$this.attr( 'data-cookie-baseindex' )+']' );

				if( $this.is( ':checked' ) )
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting 1 to cookieName=' + cookieName );

					MAP_Cookie.set( cookieName, '1', MAP_SYS.map_cookie_expire );
					currentToggleElm.prop( 'checked', true );
				}
				else
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting -1 to cookieName' + cookieName );

					MAP_Cookie.set( cookieName, '-1', MAP_SYS.map_cookie_expire );
					currentToggleElm.prop( 'checked', false );
				}

				MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );
				MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );

				that.tryToUnblockScripts( true );

			});
		}

		//bof init part ( consent mode )
		jQuery( '.map-consent-mode-preference-checkbox', that.settingsModal ).each( function(){

			var $this = jQuery( this );

			var consent_key = $this.attr( 'data-consent-key' );
			var consentStatus = that.getGoogleConsentStatus( consent_key );

			if( consentStatus === 'granted' )
			{
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting checked for consent_key' + consent_key );

				$this.prop( 'checked', true );
			}
			else if( consentStatus === 'denied' )
			{
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting unchecked for consent_key' + consent_key );

				$this.prop( 'checked', false );
			}

		});

		//eof init part ( consent mode )

		//bof consent mode - click event
		if( only_init_status == false )
		{
			jQuery( '.map-consent-mode-preference-checkbox', that.settingsModal ).click( function( e ){

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-consent-mode-preference-checkbox click' );

				e.stopImmediatePropagation();

				var $this = jQuery( this );

				var consent_key = $this.attr( 'data-consent-key' );

				var currentToggleElm = jQuery( '.map-consent-mode-preference-checkbox[data-consent-key='+consent_key+']' );

				if( $this.is( ':checked' ) )
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + `setting granted to consent_key=${consent_key}` );

					that.updateGoogleConsent( consent_key, 'granted', true );

					currentToggleElm.prop( 'checked', true );
				}
				else
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + `setting denied to consent_key=${consent_key}` );

					that.updateGoogleConsent( consent_key, 'denied', true );

					currentToggleElm.prop( 'checked', false );
				}

				MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );
				MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );
			});
		}
		//eof consent mode - click event


		if( only_init_status == false )
		{
			//bof iab part - click event
			that.settingsModal.on( 'click', '.map-user-iab-preference-checkbox', function( e ){

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-user-iab-preference-checkbox click' );

				e.stopImmediatePropagation();

				var $this = jQuery( this );

				var iab_category = $this.attr( 'data-iab-category' );
				var iab_key = $this.attr( 'data-iab-key' );
				var currentToggleElm = jQuery( '.map-user-preference-checkbox[data-cookie-baseindex='+$this.attr( 'data-cookie-baseindex' )+']' );

				if( $this.is( ':checked' ) )
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + `setting 1 to iab_category=${iab_category} , iab_key=${iab_key}` );

					(async() => {

					    while( !MAP_SYS.map_document_load )
					    {
					    	//console.log( 'not yet defined' );
					        await new Promise( resolve => setTimeout( resolve, 10 ) );
					    }

					    if( typeof window.MAPIABTCF_updateConsent === 'function' )
					    {
							let updateHtml = false;
							if( iab_category == 'googleVendors') updateHtml = true;

							window.MAPIABTCF_doSetUserInteraction();
							window.MAPIABTCF_updateConsent( iab_category, parseInt( iab_key ), true, true, updateHtml );
					    }

					})();

					$this.prop( 'checked', true );
				}
				else
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + `setting 0 to iab_category=${iab_category} , iab_key=${iab_key}` );

					(async() => {

					    while( !MAP_SYS.map_document_load )
					    {
					    	//console.log( 'not yet defined' );
					        await new Promise( resolve => setTimeout( resolve, 10 ) );
					    }

					    if( typeof window.MAPIABTCF_updateConsent === 'function' )
					    {
							let updateHtml = false;
							if( iab_category == 'googleVendors') updateHtml = true;

							window.MAPIABTCF_doSetUserInteraction();
							window.MAPIABTCF_updateConsent( iab_category, parseInt( iab_key ), false, true, updateHtml );
					    }

					})();

					$this.prop( 'checked', false );
				}
			});
			//eof iab part - click event

			//bof iab part - deny all / accept all event

			that.settingsModal.on( 'click', '.map-privacy-iab-tcf-accept-all-button', function( e ){

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-privacy-iab-tcf-accept-all-button click' );

				e.stopImmediatePropagation();

				jQuery( '.map-user-iab-preference-checkbox', that.settingsModal ).each( function(){
					var $elem = jQuery( this );
					$elem.prop( 'checked', true );
				});

				(async() => {

				    while( !MAP_SYS.map_document_load )
				    {
				    	//console.log( 'not yet defined' );
				        await new Promise( resolve => setTimeout( resolve, 10 ) );
				    }

					if( typeof window.MAPIABTCF_acceptAllConsent === 'function' )
					{
						window.MAPIABTCF_doSetUserInteraction();
						window.MAPIABTCF_acceptAllConsent( true );
					}

				})();
			});

			that.settingsModal.on( 'click', '.map-privacy-iab-tcf-deny-all-button', function( e ){

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-privacy-iab-tcf-deny-all-button' );

				e.stopImmediatePropagation();

				jQuery( '.map-user-iab-preference-checkbox', that.settingsModal ).each( function(){
					var $elem = jQuery( this );
					$elem.prop( 'checked', false );
				});

				(async() => {

				    while( !MAP_SYS.map_document_load )
				    {
				    	//console.log( 'not yet defined' );
				        await new Promise( resolve => setTimeout( resolve, 10 ) );
				    }

					if( typeof window.MAPIABTCF_denyAllConsent === 'function' )
					{
						window.MAPIABTCF_doSetUserInteraction();
						window.MAPIABTCF_denyAllConsent( true );
					}

				})();
			});

			//eof iab part - deny all / accept all event
		}

		if( only_init_status == false )
		{
			that.tryToUnblockScripts( false );
		}
	},

	showNotificationBar: function( message = null, success = null )
	{
		var body = document.querySelector( 'body' );
		var bar  = document.querySelector( '#mapx_notification_bar' );

		var prev_message = "<span class='mapx_close_notification_bar'>Close</span>";

		if( bar )
		{
			prev_message = bar.innerHTML  + "<br>";
		}
		else
		{
			bar = document.createElement( 'div' );
			bar.setAttribute( 'id','mapx_notification_bar' );
			body.append( bar );


			document.addEventListener( 'click', function (event) {
				if (!event.target.matches( '.mapx_close_notification_bar' )) return;

				event.preventDefault();

				bar.parentNode.removeChild( bar );

			}, false);

		}

		var final_message = prev_message + '<b>[MyAgilePrivacy admin-only notification]</b> ' + message;

		if( success == 1 )
		{
			final_message = final_message + '&nbsp;<span class="mapx_proxification_success_true">OK!</span>';
		}

		if( success == 2 )
		{
			final_message = final_message + '&nbsp;<span class="mapx_proxification_success_false">ERROR!</span>';
		}

		if( success == null )
		{
			final_message = final_message;
		}

		bar.innerHTML = final_message;

	},

	administratorNotices: function()
	{
		if( typeof MAP.settings !== 'undefined' &&
			MAP.settings.internal_debug
			)
		{
			if( !!MAP?.settings.scan_mode &&
				MAP.settings.scan_mode == 'learning_mode' )
			{

				if( this.blocked_friendly_name_string )
				{
					this.showNotificationBar( 'The Cookie Shield has detected the following cookies on this page: ' + this.blocked_friendly_name_string + '.', null );
				}
				else
				{
					this.showNotificationBar( 'The Cookie Shield has not detected new cookies on this page.', null );
				}
			}

			if( !!MAP?.settings.scan_mode &&
				MAP.settings.scan_mode == 'turned_off' )
			{
				this.showNotificationBar( 'Cookie Shield is turned off. Enable it in order to block cookies.', null );
			}
		}
	},

	//set overflow height
	setOverflowMaxHeight: function() {
		var that = this;
	
		var $overflow_container = jQuery('.map-cookielist-overflow-container');
		
		if( $overflow_container.length )
		{
			var $map_tab_container = $overflow_container.parent();
			var parentHeight = $map_tab_container.outerHeight();
	
			var cookie_list_height = 0;
	
			$overflow_container.children().each( function() {

				var marginTop = parseInt( jQuery( this ).css( 'marginTop' ), 10);
				var marginBottom = parseInt( jQuery(this ).css( 'marginBottom' ), 10);
				cookie_list_height += jQuery( this ).outerHeight() + marginTop + marginBottom;

			});
	
			//add the minimum height of one expanded item
			cookie_list_height += 150;

			var siblingsHeight = 0;

			$map_tab_container.children().each(function() {

				if( this !== $overflow_container.get( 0 ) )
				{
					var marginTop = parseInt( jQuery( this ).css( 'marginTop' ), 10 );
					var marginBottom = parseInt( jQuery( this ).css( 'marginBottom' ), 10);
					siblingsHeight += jQuery( this ).outerHeight() + marginTop + marginBottom;
				}

			});

			if( parentHeight > cookie_list_height )
			{
				// parent height is too high --> recalculate
				$map_tab_container.css( 'height', siblingsHeight + cookie_list_height + 'px' );
			}
			else
			{
				// need overflow and cookie list height recalculate
	
				// set calculated max-height to .overflow-cookielist-container
				var maxHeight = parentHeight - siblingsHeight;
				$overflow_container.css( 'maxHeight', maxHeight + 'px' );
			}
		}
	},

	setupAccordion: function()
	{
		//for preserving scope
		var that = this;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function setupAccordion' );

		that.setOverflowMaxHeight();

		that.settingsModal.on( 'click', '.map_expandItem', function( e ){

			var $this = jQuery( this );
			var $parent = $this.parent();

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map_expandItem click' );

			e.preventDefault();
			e.stopImmediatePropagation();

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + '.map-tab-header click' );


			if( $parent.hasClass( 'map-tab-active' ) )
			{
				$parent.removeClass( 'map-tab-active' );
				$parent.next( '.map-tab-content' ).slideUp( 200 );
			}
			else
			{
				if( !$this.hasClass( 'map-contextual-expansion' ) )
				{
					jQuery( '.map-tab-header' ).removeClass( 'map-tab-active' );
					jQuery( '.map-tab-content' ).slideUp( 200 );
				}

				$parent.addClass( 'map-tab-active' );
				$parent.next( '.map-tab-content' ).slideDown( 200 );
			}

		});
	},

	closeSettingsPopup: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function closeSettingsPopup' );

		this.settingsModal.removeClass( 'map-show' );
		this.settingsModal.addClass( 'map-out' );
		jQuery( 'body' ).removeClass( 'map-modal-open' );
		jQuery( '.map-settings-overlay' ).removeClass( 'map-show' );

		(async() => {

		    while( !MAP_SYS.map_document_load )
		    {
		    	//console.log( 'not yet defined' );
		        await new Promise( resolve => setTimeout( resolve, 10 ) );
		    }
		    if( typeof window.MAPIABTCF_hideCMPUI === 'function' )
		    {
				window.MAPIABTCF_hideCMPUI();
		    }

		})();
	},

	checkJsShield: function()
	{
		if( typeof CookieShield === 'undefined' ||
			typeof cookie_api_key_remote_id_map_active === 'undefined' )
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

		jQuery.post( map_ajax.ajax_url, data, function( response ) {
			console.debug( MAP_SYS.maplog , response );
		});
	},

	sendDetectedKeys: function( key )
	{
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

				if( key )
				{
					var data = {
						action: 'map_remote_save_detected_keys',
						key : key,
						detectableKeys: detectableKeys_to_send,
						detectedKeys: detectedKeys_to_send
					};
				}
				else
				{
					var data = {
						action: 'map_save_detected_keys',
						detectableKeys: detectableKeys_to_send,
						detectedKeys: detectedKeys_to_send
					};
				}

				jQuery.post( map_ajax.ajax_url, data, function( response ) {
					console.groupCollapsed( MAP_SYS.maplog + 'sendDetectedKeys detectableKeys='+detectableKeys_to_send+' , detectedKeys='+detectedKeys_to_send+' with response :' );
					console.debug( MAP_SYS.maplog , response );
					console.groupEnd();
				});
			}
		}
	},

	//inspect cookie script configuration
	debugCookieScripts: function()
	{
		var list = [];

		var $scripts = jQuery( 'script.my_agile_privacy_activate._js_noscript_type_mode, textarea.my_agile_privacy_activate._raw_type_mode' );

		$scripts.each( function(){

			var $this = jQuery( this );
			var cookie_name = $this.attr( 'data-cookie-name' );
			var cookie_api_key = $this.attr( 'data-cookie-api-key' );

			var code = null;
			var mode = null;

			if( $this.hasClass( '_js_noscript_type_mode' ) )
			{
				mode = 'js_noscript';
				code = $this[0].innerHTML;
			}

			if( $this.hasClass( '_raw_type_mode' ) )
			{
				mode = 'raw';
				code = $this[0].firstChild.nodeValue;
			}

			var object = {
				'cookie_name' 		: cookie_name,
				'cookie_api_key' 	: cookie_api_key,
				'mode' 				: mode,
				'code' 				: code
			};

			list.push( object );

		});

		return list;
	},

	//get list of available cookies
	getAvailableCookieList: function()
	{
		var list = [];

		var $my_agile_privacy_activated = jQuery( '.map_cookie_description_wrapper' );
		$my_agile_privacy_activated.each( function(){

			var $this = jQuery( this );
			var cookie_name = $this.attr( 'data-cookie-name' );
			var cookie_api_key = $this.attr( 'data-cookie-api-key' );

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
	},

	//get list of activated cookies
	getActivatedCookiesList: function()
	{
		var list = [];

		var $my_agile_privacy_activated = jQuery( '.map_cookie_description_wrapper._is_activated' );
		$my_agile_privacy_activated.each( function(){

			var $this = jQuery( this );
			var cookie_name = $this.attr( 'data-cookie-name' );
			var cookie_api_key = $this.attr( 'data-cookie-api-key' );

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
	},

	//get list of disactivated cookies
	getDisactivatedCookiesList: function()
	{
		var list = [];

		var $my_agile_privacy_activated = jQuery( '.map_cookie_description_wrapper:not(._is_activated' );
		$my_agile_privacy_activated.each( function(){

			var $this = jQuery( this );
			var cookie_name = $this.attr( 'data-cookie-name' );
			var cookie_api_key = $this.attr( 'data-cookie-api-key' );

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
}

jQuery( document ).ready( function() {

	if( !window === window.parent )
	{
		console.debug( MAP_SYS.maplog + 'prevent run on iframe' );
		return false;
	}

	if( typeof map_cookiebar_settings !== undefined )
	{
		MAP_SYS.map_initted = true;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'initting' );

		MAP.set({
		  settings : map_cookiebar_settings
		});
	}

	setTimeout(function(){

		if( !window === window.parent )
		{
			console.debug( MAP_SYS.maplog + 'prevent run on iframe' );
			return false;
		}

		if( !MAP_SYS.map_initted )
		{
			MAP_SYS.map_initted = true;

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'initting' );

			try {
				MAP.set({
				  settings : map_cookiebar_settings
				});

			}
			catch (error)
			{
			  console.error(error);
			}
		}

	}, 3000 );




	setTimeout(function(){

		MAP_SYS.map_document_load = true;

	}, 5000 );

});

jQuery( window ).on( 'load', function() {

	MAP_SYS.map_document_load = true;

	if( !window === window.parent )
	{
		console.debug( MAP_SYS.maplog + 'prevent run on iframe' );
		return false;
	}

	if( !MAP_SYS.map_initted )
	{
		MAP_SYS.map_initted = true;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'initting' );

		try {
			MAP.set({
			  settings : map_cookiebar_settings
			});

		}
		catch (error)
		{
		  console.error(error);
		}
	}

	if(
		map_ajax &&
		MAP.settings
	)
	{
		MAP.checkJsShield();
	}

	if( typeof CookieShield !== 'undefined' &&
		CookieShield &&
		MAP
	)
	{
		if(
			map_ajax &&
			MAP.settings &&
			(
				(
				 MAP.settings.scan_mode &&
				 MAP.settings.scan_mode == 'learning_mode'
				) ||
				(
					map_ajax.force_js_learning_mode == 1
				)
			)
		)
		{
			MAP.sendDetectedKeys( null );
		}


		if( typeof URLSearchParams !== 'undefined' &&
			URLSearchParams )
		{
			var queryString = window.location.search;

			if( queryString )
			{
				var urlParams = new URLSearchParams( queryString );

				var auto_activate_cookies_with_key = urlParams.get( 'auto_activate_cookies_with_key' )
				if( auto_activate_cookies_with_key )
				{
					MAP.sendDetectedKeys( auto_activate_cookies_with_key );
				}
			}
		}
	}
});

//f. for cloning node attribute to another (only valid attributes)
function cloneNodeAttributeToAnother( $source, dest )
{
	var exclusion_list = [
		'type',
		'src',
		'unblocked_src',
		'class',
		'data-cookie-api-key',
		'data-friendly_name'
	];

	for( var att, i = 0, atts = $source[0].attributes, n = atts.length; i < n; i++ )
	{
		att = atts[i];

		if( typeof att.nodeName !== 'undefined' && !exclusion_list.includes( att.nodeName ) )
		{
			dest.setAttribute( att.nodeName, att.nodeValue );
		}
	}

	return dest;
}

//f. for dom element recreating and previous event removal
function internalRecreateNode( el, withChildren ){
  try {
	  if (withChildren) {
		el.parentNode.replaceChild(el.cloneNode(true), el);
	  }
	  else {
		var newEl = el.cloneNode(false);
		while (el.hasChildNodes()) newEl.appendChild(el.firstChild);
		el.parentNode.replaceChild(newEl, el);
	  }
	} catch (e) {
		console.debug( e );
	}
}

//wpcf7
function map_trigger_custom_patch_1()
{
	console.debug( MAP_SYS.maplog + 'map_trigger_custom_patch_1' );

	try {
		internalRecreateNode( document.querySelector( 'form.wpcf7-form' ), true );
	}
	catch (e) {
		console.debug( e );
	}

	const c = new CustomEvent("DOMContentLoaded", {
	});
	document.dispatchEvent(c);

	try {
		wpcf7.submit = null;
	} catch (e) {
		console.debug( e );
	}
}

//avia maps
function map_trigger_custom_patch_2()
{
	console.debug( MAP_SYS.maplog + 'map_trigger_custom_patch_2' );

	jQuery( '.av_gmaps_confirm_link.av_text_confirm_link.av_text_confirm_link_visible' ).trigger( 'click' );
}

//page reload
/*
function map_trigger_custom_patch_3()
{
	console.debug( 'map_trigger_custom_patch_3' );

	location.reload();
}
*/

//octorate
function map_trigger_custom_patch_3()
{
	try {
		octorate.octobook.Widget.show();
	} catch (e) {
		setTimeout( map_trigger_custom_patch_3, 100 );
		console.debug( e );
	}
}

