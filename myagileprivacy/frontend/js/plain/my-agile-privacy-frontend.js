/*!
* MyAgilePrivacy (https://www.myagileprivacy.com/)
* plain version
*/

var MAP_SYS = {
	'plugin_version' 					: null,
	'internal_version' 					: "2.0010",
	'cookie_shield_version' 			: null,
	'technology' 						: "plain",
	'maplog' 							: "\x1b[40m\x1b[37m[MyAgilePrivacy]\x1b[0m ",
	'map_initted' 						: false,
	'map_document_load' 				: false,
	'map_debug' 						: false,
	'map_cookie_expire' 				: 180,
	'map_skip_regexp' 					: [/.*oxygen_iframe=true.*$/, /.*ct_builder=true.*$/],
	'map_missing_cookie_shield' 		: null,
	'map_detectableKeys' 				: null,
	'map_detectedKeys' 					: null,
	'in_iab_context' 					: false,
	'dependencies' 						: [],
	'cmode_v2' 							: null,
	'cmode_v2_implementation_type' 		: null,
	'cmode_v2_forced_off_ga4_advanced' 	: null,
	'cmode_v2_js_on_error'				: null,
	'starting_gconsent' 				: [],
	'current_gconsent'					: [],
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
				if(days) {
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
					if(c.indexOf(nameEQ) === 0) {
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
			console.debug( MAP_SYS.maplog + 'using alt_accepted_all_cookie_name=' + MAP_ACCEPTED_ALL_COOKIE_NAME );
			console.debug( MAP_SYS.maplog + 'using alt_accepted_something_cookie_name=' + MAP_ACCEPTED_SOMETHING_COOKIE_NAME );
		}

		this.blocked_friendly_name_string = null;

		this.blocked_content_notification_shown = false;

		this.bar_open = false;

		this.settings = args.settings;

		this.bar_elm = document.querySelector( this.settings.notify_div_id );
		this.showagain_elm = document.querySelector( "#" + this.settings.showagain_div_id );
		this.settingsModal = document.querySelector( '#mapSettingsPopup' );
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

		if( MAP_SYS?.map_debug )
		{
			console.groupCollapsed( MAP_SYS.maplog + 'settings:' );
			console.debug( this.settings );
			console.groupEnd();
		}

		this.loadDependencies();

		this.toggleBar();
		this.createInlineNotify();
		this.attachEvents();

		this.attachAnimations();

		this.initted = true;

		this.optimizeMobile();

		//for preserving scope
		var that = this;

		window.addEventListener('resize', function() {
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

		try{

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
		}
		catch( error )
		{
			console.error( error );
		}
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
					    	//console.log( 'not defined yet' );
					        await new Promise( resolve => setTimeout( resolve, 10 ) );
					    }
					    //console.log("variable is defined");
					    that.displayBar();

						setTimeout(function(){
							var scroll_to_top = document.querySelector( '.map_notification-message.map-iab-context' );
							if(scroll_to_top) {
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

		this.showagain_elm.querySelectorAll( 'a.showConsent' ).forEach(function( $this ) {
			$this.addEventListener('click', function(e) {
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered showConsent' );

				e.preventDefault();
				e.stopImmediatePropagation();

				//animation
				var that_animation = MAP.bar_elm.getAttribute( 'data-animation' );
				var that_id_selector = '#' + MAP.bar_elm.getAttribute( 'id' );


				var animation_params = {
					targets: that_id_selector,
					easing: 'easeInOutQuad',
					duration: 1000
				};

				anime( {
					targets: MAP.showagain_elm,
					easing: 'easeInOutQuad',
					duration: 150,
					opacity: 0,
					complete: function(anim) {
						MAP.showagain_elm.style.display = 'none';
					}
				} );

				setTimeout( function(){
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
							anime( animation_params );
							break;

						case 'fade':
							document.querySelector( that_id_selector ).style.opacity = '0';
            				MAP.bar_elm.style.display = 'block';

							animation_params['opacity'] = '1';
							animation_params['duration'] = '500';

							anime( animation_params );

							break;

						case 'zoom':
							document.querySelector( that_id_selector ).style.transform = 'scale(0)';
            				MAP.bar_elm.style.display = 'block';

							animation_params['scale'] = '1';
							anime( animation_params );

							break;

						default: // no animation -> value = "none"
							MAP.bar_elm.style.display = 'block';
							break;
					}

					that.bar_open = true;
					that.optimizeMobile();
				},100);

				
			});
		});

		//bof user consent review trigger
		
		that.bar_elm.addEventListener( 'triggerShowAgainDisplay', function(e) {
			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered triggerShowAgainDisplay' );

			e.preventDefault();
			e.stopImmediatePropagation();

			//animation
			var that_animation = MAP.bar_elm.getAttribute('data-animation');
			var that_id_selector = '#' + MAP.bar_elm.getAttribute('id');

			var animation_params = {
				targets: that_id_selector,
				easing: 'easeInOutQuad',
				duration: 1000
			};
			
			anime( {
				targets: MAP.showagain_elm,
				easing: 'easeInOutQuad',
				duration: 150,
				opacity: 0,
				complete: function(anim) {
					MAP.showagain_elm.style.display = 'none';
				}
			} );
			

			setTimeout(function(){
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
						anime( animation_params );
						break;

					case 'fade':
						document.querySelector( that_id_selector ).style.opacity = '0';
						MAP.bar_elm.style.display = 'block';

						animation_params['opacity'] = '1';
						animation_params['duration'] = '500';

						anime( animation_params );

						break;

					case 'zoom':
						document.querySelector( that_id_selector ).style.transform = 'scale(0)';
						MAP.bar_elm.style.display = 'block';

						animation_params['scale'] = '1';
						anime( animation_params );
						break;

					default: // no animation -> value = "none"
						MAP.bar_elm.style.display = 'block';
						break;
				}

				that.bar_open = true;
				that.optimizeMobile();

			}, 100 );
		});

		document.body.addEventListener( 'click', function( e ) {
			if(e.target.matches('.showConsentAgain') )
			{
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered showConsentAgain' );
				e.preventDefault();
				e.stopImmediatePropagation();

				var event = new CustomEvent('triggerShowAgainDisplay');
        		that.bar_elm.dispatchEvent( event );
			}

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

			Object.keys( detectedKeys ).forEach( function( key ) {

				var api_key = detectedKeys[key];

				var $custom_ref = document.querySelector('.map_custom_notify.map_api_key_' + api_key);

				if( $custom_ref )
				{
					// $custom_ref.show();
					$custom_ref.style.display = 'block';
				}
			});
		}

		var $map_blocked_content = document.querySelectorAll( '.map_blocked_content' );

		// $map_blocked_content.each(function(){
		$map_blocked_content.forEach( function( $this) {

			var api_key = $this.getAttribute( 'data-cookie-api-key' );

			var $custom_ref = document.querySelectorAll( '.map_custom_notify.map_api_key_' + api_key );

			$custom_ref.forEach(function( customRef ) {
				if( customRef )
				{
					customRef.style.display = 'block';
				}
			});

		});

		var $show_inline_notify = document.querySelectorAll( '.iframe_src_blocked.map_show_inline_notify' );

		// $show_inline_notify.each(function(){
		$show_inline_notify.forEach( function( $this ) {

			// var height = $this.height();
			var height = $this.offsetHeight;

			// var width = $this.width();
			var width = $this.offsetWidth;

			// var api_key = $this.attr( 'data-cookie-api-key' );
			var api_key = $this.getAttribute('data-cookie-api-key');

			var the_friendly_name = '';
			// var friendly_name = $this.attr( 'data-friendly_name' );
    		var friendly_name = $this.getAttribute('data-friendly-name');

			if( friendly_name )
			{
				the_friendly_name = friendly_name;
			}

			// $this.hide();
			$this.style.display = 'none';

			var html = "<div class='map_inline_notify showConsentAgain' data-cookie-api-key='"+api_key+"'>"+that.settings.blocked_content_text+"<br>"+the_friendly_name+"</div>";

			//temp div for inject via vanilla js
			var tempDiv = document.createElement( 'div' );
			tempDiv.innerHTML = html;

			var $injected = tempDiv.firstChild;
			$this.parentNode.insertBefore( $injected, $this.nextSibling );

			if( height > 0 )
			{
				$injected.style.height = height + 'px';
			}

			if( width > 0 )
			{
				$injected.style.width = width + 'px';
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
	},
	
	setupIabTCF: function()
	{
		//for preserving scope
		var that = this;

		var $map_consent_extrawrapper = that.settingsModal.querySelector( '.map-consent-extrawrapper' );

		if( !!$map_consent_extrawrapper )
		{
			MAP_SYS.in_iab_context = true;

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function setupIabTCF' );

			//first layer
			that.bar_elm.querySelectorAll( '.map-triggerGotoIABTCF' ).forEach( function( $this ) {
				$this.addEventListener( 'click', function( e ) {

					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-triggerGotoIABTCF click' );

					e.preventDefault();
					e.stopImmediatePropagation();

					MAP.settingsModal.classList.add( 'map-show' );
					MAP.settingsModal.style.opacity = 0;
					MAP.settingsModal.offsetWidth; // Trigger a reflow to ensure the transition starts
					MAP.settingsModal.style.transition = 'opacity 1s';
					MAP.settingsModal.style.opacity = 1;

					MAP.settingsModal.classList.remove( 'map-blowup', 'map-out' );
					MAP.settingsModal.classList.add( 'map-blowup' );

					document.body.classList.add( 'map-modal-open' );

					document.querySelector( '.map-settings-overlay' ).classList.add( 'map-show' );

					if( !document.querySelector( '.map-settings-mobile' ).offsetWidth )
					{
						// MAP.settingsModal.find( '.map-nav-link:eq(0)' ).click();
						MAP.settingsModal.querySelector('.map-nav-link').click();
					}

					$map_consent_extrawrapper.querySelectorAll( '.map-wrappertab-navigation li a[href="#map-privacy-iab-tcf-wrapper"]' ).forEach( function( $_this ) {
						$_this.click();
					});

				});
			});

			that.bar_elm.querySelectorAll( '.map-triggerGotoIABTCFVendors' ).forEach( function( $this ) {
				$this.addEventListener('click', function(e) {

					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-triggerGotoIABTCFVendors click' );

					e.preventDefault();
					e.stopImmediatePropagation();

					MAP.settingsModal.classList.add('map-show');
					MAP.settingsModal.style.opacity = 0;

					MAP.settingsModal.offsetWidth; // Trigger a reflow to ensure the transition starts
					MAP.settingsModal.style.transition = 'opacity 1s';
					MAP.settingsModal.style.opacity = 1;

					MAP.settingsModal.classList.remove( 'map-blowup', 'map-out' );
					MAP.settingsModal.classList.add( 'map-blowup' );

					document.body.classList.add( 'map-modal-open' );

					document.querySelector( '.map-settings-overlay' ).classList.add( 'map-show' );

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

					setTimeout(function(){

						var $vendor_list_button = document.querySelector( '#map-iab-tcf-vendor-list' );

						if( $vendor_list_button )
						{
							$vendor_list_button.click();

							setTimeout(function(){
								var $vendor_list_scrollto = document.querySelector( '#map-iab-tcf-vendor-list-scroll-here' );

								if( $vendor_list_scrollto )
								{
									$vendor_list_scrollto.scrollIntoView({ behavior: 'smooth' });
								}
							}, 200 );
						}
					}, 200);
				});
			});

			//bof nav part
			$map_consent_extrawrapper.querySelectorAll( '.map-wrappertab-navigation li a' ).forEach( function( $this ) {
				$this.addEventListener( 'click', function(e) {

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
						(async() => {

							while( !MAP_SYS.map_document_load )
							{
								//console.log( 'not defined yet' );
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
								//console.log( 'not defined yet' );
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
			    	//console.log( 'not defined yet' );
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

		that.accept_button.addEventListener( 'click', function( e ) {
			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-accept-button click' );

			e.preventDefault();
			e.stopImmediatePropagation();

			var $this = this;

			//check consent-mode checkbox
			that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox' ).forEach( function( elem ) {
				elem.checked = true;

				var consent_key = elem.getAttribute( 'data-consent-key' );

				that.updateGoogleConsent( consent_key, 'granted', true );
			});

			//check user-preference checkbox
			that.settingsModal.querySelectorAll( '.map-user-preference-checkbox' ).forEach(function( elem ) {

				var cookieName = 'map_cookie_' + elem.getAttribute( 'data-cookie-baseindex' ) + MAP_POSTFIX;

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting 1 to cookieName=' + cookieName );

				MAP_Cookie.set( cookieName, '1', MAP_SYS.map_cookie_expire );

				elem.checked = true;
			});

			//check iab-preference checkbox
			that.settingsModal.querySelectorAll( '.map-user-iab-preference-checkbox' ).forEach( function( elem ) {
				elem.checked = true;
			});

			(async() => {

			    while( !MAP_SYS.map_document_load )
			    {
			    	//console.log( 'not defined yet' );
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

		
		that.reject_button.forEach(function( button ) {
			button.addEventListener('click', function(e) {
				
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-reject-button click' );
	
				e.preventDefault();
				e.stopImmediatePropagation();
	
				var $this = this;
	
				//uncheck consent-mode checkbox
				that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox' ).forEach( function( elem ) {
					elem.checked = false;

					var consent_key = elem.getAttribute( 'data-consent-key' );

					that.updateGoogleConsent( consent_key, 'denied', true );
				});

				//uncheck user-preference checkbox
				that.settingsModal.querySelectorAll( '.map-user-preference-checkbox' ).forEach( function( elem ) {

					var cookieName = 'map_cookie_' + elem.getAttribute( 'data-cookie-baseindex' ) + MAP_POSTFIX;

					MAP_Cookie.set( cookieName, '-1', MAP_SYS.map_cookie_expire );

					elem.checked = false;
				});

				//uncheck iab-preference checkbox
				that.settingsModal.querySelectorAll( '.map-user-iab-preference-checkbox' ).forEach( function( elem ) {
					elem.checked = false;
				});
	
				(async() => {

					while( !MAP_SYS.map_document_load )
					{
						//console.log( 'not defined yet' );
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
		});


		that.customize_button.addEventListener( 'click', function(e) {

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-customize-button click' );

			e.preventDefault();
			e.stopImmediatePropagation();

			MAP.settingsModal.classList.add( 'map-show' );
			MAP.settingsModal.style.opacity = 0;
			MAP.settingsModal.offsetWidth; // Trigger a reflow to ensure the transition starts
			MAP.settingsModal.style.transition = 'opacity 1s';
			MAP.settingsModal.style.opacity = 1;

			MAP.settingsModal.classList.remove( 'map-blowup', 'map-out' );
			MAP.settingsModal.classList.add( 'map-blowup' );
			
			document.body.classList.add( 'map-modal-open' );

			document.querySelector( '.map-settings-overlay' ).classList.add( 'map-show' );

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

		});

		document.querySelector('#mapModalClose').addEventListener('click', function(e) {

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
			    	//console.log( 'not defined yet' );
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

		if( that.bar_elm.querySelector( '#map-accept-button' ).getAttribute( 'data-animate' ) == 'true' )
		{
			var acceptButton = document.querySelector( '#map-accept-button' );
			
			var animation = acceptButton.getAttribute( 'data-animation-effect' );
			
			var delay = parseInt(acceptButton.getAttribute( 'data-animation-delay') ) * 1000;
			
			var repeat = parseInt(acceptButton.getAttribute( 'data-animation-repeat' ) );
			
			var all_animation_classes = ['animate__animated', animation] ;

			var iteration_counter = 0;

			var animInterval = setInterval( function(){
				if(iteration_counter == repeat)
				{
					clearInterval( animInterval );
				}
				else
				{
					acceptButton.classList.add( ...all_animation_classes );

					setTimeout( function() {
						acceptButton.classList.remove( ...all_animation_classes );
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

		var $map_blocked_content = document.querySelectorAll( '.map_blocked_content:not(._is_activated)' );

		if( !!$map_blocked_content && $map_blocked_content?.length )
		{
			//calc data
			var blocked_friendly_name = [];

			$map_blocked_content.forEach( function( $this ) {
				
				var this_friendly_name = $this.getAttribute('data-friendly_name');

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

						anime( {
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

					setTimeout( function(){

						that.blocked_content_notification.style.opacity = 0;
						that.blocked_content_notification.style.display = 'block';

						anime( {
							targets: that.blocked_content_notification,
							easing: 'easeInOutQuad',
							duration: 150,
							opacity: 1,
							complete: function(anim) {
							}
						} );
						
						if( that.blocked_content_notification.classList.contains( 'autoShutDown' ) )
						{
							setTimeout(function(){
								
								anime( {
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

					}, 1000 );
				}
			}
		}
		else
		{
			if( that.blocked_content_notification )
			{
				anime( {
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

	},

	displayBar: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function displayBar' );

		//for preserving scope
		var that = this;

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

				anime( animation_params );
				break;

			case 'fade':
				element.style.opacity = '0';

				element.style.display = 'block';

				animation_params['opacity'] = '1';
				animation_params['duration'] = '500';

				anime( animation_params );

				break;

			case 'zoom':
				element.style.transform = 'scale(0)';

				element.style.display = 'block';

				animation_params['scale'] = '1';
				anime( animation_params );

				break;

			default: // no animation -> value = "none"
				element.style.display = 'block';
				
				break;
		}

		anime( {
			targets: that.showagain_elm,
			easing: 'easeInOutQuad',
			duration: 150,
			opacity: 0,
			complete: function( anim ) {
				that.showagain_elm.style.display = 'none';
			}
		} );

		that.bar_open = true;

		that.optimizeMobile();
	},

	hideBar: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function hideBar' );

		//for preserving scope
		var that = this;

		if( Boolean( this.settings.showagain_tab ) )
		{
			//show
			that.showagain_elm.style.opacity = 0;
			that.showagain_elm.style.display = 'block';

			anime( {
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
			//hide
			anime( {
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
				this.bar_elm.style.display = 'none';

				break;
		}

		this.bar_open = false;

		(async() => {

		    while( !MAP_SYS.map_document_load )
		    {
		    	//console.log( 'not defined yet' );
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

		setTimeout( function(){
			if( that.bar_open )
			{	
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'optimizing for mobile view' );

				var viewport_width = window.innerWidth;

				if( viewport_width <= 450 )
				{
					var viewport_height = window.innerHeight;
					var internal_height = viewport_height - 250;

					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'map mobile optimizing: viewport_width=' + viewport_width + ' , internal_height=' + internal_height );

					that.map_notification_message.classList.add( 'extraNarrow' );
					that.map_notification_message.style.maxHeight = internal_height + 'px';
				}
				else
				{
					that.map_notification_message.classList.remove( 'extraNarrow' );
					that.map_notification_message.style.maxHeight = '';
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
				this.bar_elm.style.display = 'none';
			break;
		}

		if( Boolean( that.settings.showagain_tab ) )
		{
			that.showagain_elm.style.opacity = 0;
			that.showagain_elm.style.display = 'block';

			anime( {
				targets: that.showagain_elm,
				easing: 'easeInOutQuad',
				duration: 150,
				opacity: 1,
				complete: function(anim) {
				}
			} );
		}

		that.tryToUnblockScripts( true );

		(async() => {

		    while( !MAP_SYS.map_document_load )
		    {
		    	//console.log( 'not defined yet' );
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
				that.bar_elm.style.display = 'none';

				break;
		}

		that.bar_open = false;

		if( Boolean( that.settings.showagain_tab ) )
		{
			that.showagain_elm.style.opacity = 0;
			that.showagain_elm.style.display = 'block';

			anime( {
				targets: that.showagain_elm,
				easing: 'easeInOutQuad',
				duration: 150,
				opacity: 1,
				complete: function(anim) {
				}
			} );
		}

		that.tryToUnblockScripts( true );

		setTimeout( function(){
			that.checkBlockedContent();
		},200);

		(async() => {

		    while( !MAP_SYS.map_document_load )
		    {
		    	//console.log( 'not defined yet' );
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

		console.log( dataLayer );

		var once_functions_to_execute = [];

		var $map_cookie_description_wrapper = that.settingsModal.querySelectorAll( '.map_cookie_description_wrapper' );

		$map_cookie_description_wrapper.forEach( function( $this ) {

			var baseIndex = $this.getAttribute('data-cookie-baseindex');
			var cookieName = 'map_cookie_' + baseIndex + MAP_POSTFIX;
			var api_key = $this.getAttribute('data-cookie-api-key');

			if( do_calc_need_reload && $this.classList.contains( 'map_page_reload_on_user_consent' ) )
			{
				need_reload = true;
			}

			var cookieValue = MAP_Cookie.read( cookieName );

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'debug ' + api_key + ' ' + cookieName + ' ' + cookieValue );

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
					$this.classList.add( '_is_activated_anyway' );
				}
				else
				{
					$this.classList.add( '_is_activated' );
				}

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

							if( MAP_SYS?.map_debug ) console.debug( script.innerHTML );

							document.head.appendChild( script );
						}
					}

					if( $the_raw_script )
					{
						if( !$the_raw_script.classList.contains( '_is_activated' ) )
						{
							$the_raw_script.classList.add( '_is_activated' );

							var the_raw_script = $the_raw_script.value;

							if( MAP_SYS?.map_debug ) console.debug( the_raw_script );

							var range = document.createRange();
							range.selectNode( document.getElementsByTagName("body")[0] );
							var documentFragment = range.createContextualFragment( the_raw_script );
							document.body.appendChild( documentFragment );
						}

					}
				}

				if( !!api_key )
				{
					//bof custom notify
					var $custom_ref = document.querySelectorAll( '.map_custom_notify.map_api_key_' + api_key );

					$custom_ref.forEach(function( $_this ){

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

					var $map_src_script_blocked = document.querySelectorAll( 'script.my_agile_privacy_activate.autoscan_mode.map_src_script_blocked[data-cookie-api-key="'+api_key+'"]' );

					var $map_inline_script_blocked = document.querySelectorAll( 'script.my_agile_privacy_activate.autoscan_mode.map_inline_script_blocked[data-cookie-api-key="'+api_key+'"]' );

					var $iframe_src_blocked = document.querySelectorAll( 'iframe.my_agile_privacy_activate.autoscan_mode.iframe_src_blocked[data-cookie-api-key="'+api_key+'"]' );

					var $css_href_blocked = document.querySelectorAll( 'link.my_agile_privacy_activate.autoscan_mode.css_href_blocked[data-cookie-api-key="'+api_key+'"]' );

					var $img_src_blocked = document.querySelectorAll( 'img.my_agile_privacy_activate.autoscan_mode.img_src_blocked[data-cookie-api-key="'+api_key+'"]' );

					if( !!$map_src_script_blocked && $map_src_script_blocked?.length )
					{
						$map_src_script_blocked.forEach( function( $_this ) {

							if( !$_this.classList.contains( '_is_activated' ) )
							{
								if( !$_this.classList.contains( 'mapWait' ) )
								{
									$_this.classList.add( '_is_activated' );
								}

								if( $_this.classList.contains( 'custom_patch_apply' ) )
								{
									var classes = $_this.className.split(' ');

									classes.forEach(function(v) {
										if(v && v != '' && v.startsWith('map_trigger_custom_patch_') && typeof window[v] === 'function') {
											setTimeout(function() {
												window[v]();
											}, 4000 );
										}
									});
								}

								if( $_this.classList.contains( 'once_custom_patch_apply' ) )
								{
									var classes = $_this.className.split(' ');

									classes.forEach(function(v) {
										if(v && v != '' && v.startsWith('map_trigger_custom_patch_') && typeof window[v] === 'function') {
											if( !once_functions_to_execute.includes( v )) {
												once_functions_to_execute.push( v );
											}
										}
									});
								}

								if( $_this.classList.contains( 'mapWait' ) )
								{
									setTimeout(function() {
										var script = document.createElement( 'script' );
										script.className = '_is_activated';

										script = cloneNodeAttributeToAnother( $_this, script );

										var blocked_src = $_this.getAttribute( 'unblocked_src' );

										if( blocked_src )
										{
											script.src = blocked_src;
										}

										$_this.insertAdjacentElement( 'afterend', script );

										$_this.classList.add( '_is_activated' );

									}, 2000);
								}
								else
								{
									var script = document.createElement( 'script' );
									script.className = '_is_activated';

									script = cloneNodeAttributeToAnother( $_this, script );

									var blocked_src = $_this.getAttribute( 'unblocked_src' );

									if(blocked_src) {
										script.src = blocked_src;
									}

									$_this.insertAdjacentElement( 'afterend', script );
								}
							}

						});

					}

					if( !!$map_inline_script_blocked && $map_inline_script_blocked?.length )
					{
						$map_inline_script_blocked.forEach( function( $_this ) {

							if( !$_this.classList.contains( '_is_activated' ) )
							{
								if( !$_this.classList.contains('mapWait') )
								{
									$_this.classList.add( '_is_activated' );
								}

								if( $_this.classList.contains( 'custom_patch_apply' ) )
								{
									var classes = $_this.className.split(' ');

									classes.forEach( function(v) {
										if( v && v != '' && v.startsWith('map_trigger_custom_patch_') && typeof window[v] === 'function' )
										{
											setTimeout(function() {
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

								if( $_this.classList.contains( 'mapWait' ) )
								{
									setTimeout( function() {
										var script = document.createElement( 'script' );
										script.className = '_is_activated';
										script.innerHTML = $_this.innerHTML;

										if( MAP_SYS?.map_debug ) console.debug( script.innerHTML );

										document.head.appendChild( script );

										$_this.classList.add( '_is_activated' );

									}, 2200);
								}
								else
								{
									var script = document.createElement( 'script' );
									script.className = '_is_activated';
									script.innerHTML = $_this.innerHTML;

									if( MAP_SYS?.map_debug ) console.debug( script.innerHTML );

									document.head.appendChild( script );
								}
							}

						});

					}

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

								$_this.setAttribute( 'src', $_this.getAttribute( 'unblocked_src' ) );
							}
						});
					}
				}
			}
			else
			{
				$this.classList.remove( '_is_activated' );
			}

		});

		if( need_reload )
		{
			window.location.reload();
		}

		//execution of once custom patch functions
		once_functions_to_execute.forEach( function(v) {
			setTimeout(function() {
				window[v]();
			}, 1000 );
		});

		setTimeout( function(){
			that.checkBlockedContent();
		}, 500 );

		setTimeout( function(){
			var event = new Event('MAP_PRIVACY_CHANGE');
			document.body.dispatchEvent(event);
		}, 100 );
	},

	//init user preferences and bind events
	//with only_init_status==true events are not binded
	userPreferenceInit: function( only_init_status = false )
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'userPreferenceInit only_init_status='+only_init_status );

		//for preserving scope
		var that = this;

		//init part (cookie consent)
		that.settingsModal.querySelectorAll( '.map-user-preference-checkbox' ).forEach( function( $this ) {

			var cookieName = 'map_cookie_' + $this.getAttribute('data-cookie-baseindex') + MAP_POSTFIX;

			var cookieValue = MAP_Cookie.read( cookieName );
			if( cookieValue == null )
			{
				if( $this.checked )
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting 1 to cookieName=' + cookieName );

					MAP_Cookie.set( cookieName, '1', MAP_SYS.map_cookie_expire );
				}
				else
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

					$this.checked = true;
				}
				else
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting unchecked for cookieName' + cookieName );

					$this.checked = false;
				}
			}
		});


		//click event
		if( only_init_status == false )
		{
			that.settingsModal.querySelectorAll( '.map-user-preference-checkbox' ).forEach(function($this) {
				$this.addEventListener('click', function(e) {

					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-user-preference-checkbox click' );

					e.stopImmediatePropagation();

					var cookieName = 'map_cookie_' + $this.getAttribute('data-cookie-baseindex') + MAP_POSTFIX;

					var currentToggleElm = that.settingsModal.querySelectorAll( '.map-user-preference-checkbox[data-cookie-baseindex="'+$this.getAttribute('data-cookie-baseindex')+'"]' );

					if( $this.checked )
					{
						if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting 1 to cookieName=' + cookieName );

						MAP_Cookie.set( cookieName, '1', MAP_SYS.map_cookie_expire );
						currentToggleElm.forEach( elm => elm.checked = true );
					}
					else
					{
						if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting -1 to cookieName' + cookieName );

						MAP_Cookie.set( cookieName, '-1', MAP_SYS.map_cookie_expire );
						currentToggleElm.forEach( elm => elm.checked = false );
					}

					MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );
					MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );

					that.tryToUnblockScripts( true );

				});
			});
		}
	

		//bof init part ( consent mode)
		that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox' ).forEach( function( $this ) {

			var consent_key = $this.getAttribute( 'data-consent-key' );

			var consentStatus = that.getGoogleConsentStatus( consent_key );

			if( consentStatus === 'granted' )
			{
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting checked for consent_key' + consent_key );

				$this.checked = true;
			}
			else if( consentStatus === 'denied' )
			{
				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'setting unchecked for consent_key' + consent_key );

				$this.checked = false;
			}
		});
		//eof init part ( consent mode)


		//bof consent mode - click event
		if( only_init_status == false )
		{
			that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox' ).forEach(function($this) {
				$this.addEventListener('click', function(e) {

					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-consent-mode-preference-checkbox click' );

					e.stopImmediatePropagation();

					var consent_key = $this.getAttribute( 'data-consent-key' );

					var currentToggleElm = that.settingsModal.querySelectorAll( '.map-consent-mode-preference-checkbox[data-consent-key="' + consent_key + '"]' );

					if( $this.checked )
					{
						if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + `setting granted to consent_key=${consent_key}` );

						that.updateGoogleConsent( consent_key, 'granted', true );

						currentToggleElm.forEach( elm => elm.checked = true );
					}
					else
					{
						if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + `setting denied to consent_key=${consent_key}` );

						that.updateGoogleConsent( consent_key, 'denied', true );

						currentToggleElm.forEach( elm => elm.checked = false );
					}

					MAP_Cookie.set( MAP_ACCEPTED_SOMETHING_COOKIE_NAME, '1', MAP_SYS.map_cookie_expire );
					MAP_Cookie.set( MAP_ACCEPTED_ALL_COOKIE_NAME, '-1', MAP_SYS.map_cookie_expire );


				});
			});
		}
		//eof consent mode - click event


		if( only_init_status == false )
		{
			//bof iab part - click event
			that.settingsModal.addEventListener( 'click', function( e ) {

				var $this = e.target;

				if( $this.matches( '.map-user-iab-preference-checkbox' ) )
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-user-iab-preference-checkbox click' );

					e.stopImmediatePropagation();

					var iab_category = $this.getAttribute( 'data-iab-category' );
					var iab_key = $this.getAttribute( 'data-iab-key' );
					var currentToggleElm = that.settingsModal.querySelectorAll( '.map-user-preference-checkbox[data-cookie-baseindex='+ $this.getAttribute('data-cookie-baseindex') + ']' );

					if( $this.checked )
					{

						if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + `setting 1 to iab_category=${iab_category} , iab_key=${iab_key}` );

						(async() => {
							while (!MAP_SYS.map_document_load) {
								// console.log( 'not defined yet' );
								await new Promise(resolve => setTimeout(resolve, 10));
							}

							if( typeof window.MAPIABTCF_updateConsent === 'function' )
							{
								let updateHtml = false;
								if( iab_category === 'googleVendors' ) updateHtml = true;

								window.MAPIABTCF_doSetUserInteraction();
								window.MAPIABTCF_updateConsent( iab_category, parseInt( iab_key ), true, true, updateHtml );
							}

						})();

						currentToggleElm.forEach(elm => elm.checked = true);
					}
					else
					{

						if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + `setting 0 to iab_category=${iab_category} , iab_key=${iab_key}` );

						(async() => {
							while (!MAP_SYS.map_document_load) {
								// console.log( 'not defined yet' );
								await new Promise(resolve => setTimeout(resolve, 10));
							}

							if( typeof window.MAPIABTCF_updateConsent === 'function' )
							{
								let updateHtml = false;
								if(iab_category === 'googleVendors') updateHtml = true;

								window.MAPIABTCF_doSetUserInteraction();
								window.MAPIABTCF_updateConsent( iab_category, parseInt( iab_key ), false, true, updateHtml );
							}

						})();

						currentToggleElm.forEach(elm => elm.checked = false);
					}
				}

			});
			//eof iab part - click event

			//bof iab part - deny all / accept all event
			that.settingsModal.addEventListener( 'click', function( e ) {
				var target = e.target;
				if( target.matches( '.map-privacy-iab-tcf-accept-all-button' ) )
				{
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-privacy-iab-tcf-accept-all-button click' );

					e.stopImmediatePropagation();

					that.settingsModal.querySelectorAll( '.map-user-iab-preference-checkbox ').forEach(function( $_this ) {
						$_this.checked = true;
					});

					(async() => {
						while (!MAP_SYS.map_document_load) {
							// console.log( 'not defined yet' );
							await new Promise(resolve => setTimeout( resolve, 10 ));
						}

						if( typeof window.MAPIABTCF_acceptAllConsent === 'function' )
						{
							window.MAPIABTCF_doSetUserInteraction();
							window.MAPIABTCF_acceptAllConsent( true );
						}

					})();
				}
			});

			that.settingsModal.addEventListener( 'click', function( e ) {
				var target = e.target;
				if( target.matches( '.map-privacy-iab-tcf-deny-all-button' ) ) {
					if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map-privacy-iab-tcf-deny-all-button' );

					e.stopImmediatePropagation();

					that.settingsModal.querySelectorAll( '.map-user-iab-preference-checkbox' ).forEach( function( $elem ) {
						$elem.checked = false;
					});

					(async() => {
						while (!MAP_SYS.map_document_load) {
							// console.log( 'not defined yet' );
							await new Promise(resolve => setTimeout(resolve, 10));
						}

						if( typeof window.MAPIABTCF_denyAllConsent === 'function' )
						{
							window.MAPIABTCF_doSetUserInteraction();
							window.MAPIABTCF_denyAllConsent( true );
						}

					})();
				}
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
				if(!event.target.matches( '.mapx_close_notification_bar' )) return;

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
				const this_blocked_friendly_name = [];

				if( map_full_config?.cookie_api_key_remote_id_map_active &&
					typeof map_full_config.cookie_api_key_remote_id_map_active === 'object')
				{
				    Object.entries( map_full_config.cookie_api_key_remote_id_map_active).
				    forEach( ([key, value] ) => {

				        const friendlyName = map_full_config?.cookie_api_key_friendly_name_map?.[key];

				        if( friendlyName?.desc )
				        {
				        	this_blocked_friendly_name.push( friendlyName?.desc );
				        }
				    });
				}

				const this_blocked_friendly_name_unique = this_blocked_friendly_name.filter((v, i, a) => a.indexOf(v) === i);

				if( this_blocked_friendly_name_unique.length )
				{
					this.showNotificationBar( 'The Cookie Shield has detected the following cookies so far: ' +this_blocked_friendly_name_unique.join( ', ' ) + '.', null );
				}
				else
				{
					this.showNotificationBar( 'The Cookie Shield has not detected any cookies.', null );
				}
			}

			if( !!MAP?.settings.scan_mode &&
				MAP.settings.scan_mode == 'turned_off' )
			{
				this.showNotificationBar( 'Cookie Shield is turned off. Enable it in order to block cookies.', null );
			}
		}
	},

	//slideUp equivalent function (hide)
	slideUp: function( target, duration=500, callback=null )
	{
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
	      target.style.display = 'none';
	      target.style.removeProperty('height');
	      target.style.removeProperty('padding-top');
	      target.style.removeProperty('padding-bottom');
	      target.style.removeProperty('margin-top');
	      target.style.removeProperty('margin-bottom');
	      target.style.removeProperty('overflow');
	      target.style.removeProperty('transition-duration');
	      target.style.removeProperty('transition-property');
	    }, duration);

	    if( callback )
	    {

	    }

	 },
	//slideDown equivalent function (show)
	slideDown: function( target, duration=500, callback=null )
	{
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
	      target.style.removeProperty('height');
	      target.style.removeProperty('overflow');
	      target.style.removeProperty('transition-duration');
	      target.style.removeProperty('transition-property');
	    }, duration);
  	},

  	//set overflow height
	setOverflowMaxHeight: function()
	{
		var that = this;

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
	},

	setupAccordion: function()
	{
		//for preserving scope
		var that = this;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function setupAccordion' );
		
		that.setOverflowMaxHeight();

		that.settingsModal.addEventListener ('click', function( e ) {
			var $this = e.target;
			if( $this.matches( '.map_expandItem' ) )
			{
				var $parent = $this.parentElement;

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'triggered map_expandItem click' );

				e.preventDefault();
				e.stopImmediatePropagation();

				if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + '.map-tab-header click' );

				var $content = $parent.nextElementSibling;

				if( $parent.classList.contains( 'map-tab-active' )  )
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

	},

	closeSettingsPopup: function()
	{
		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'internal function closeSettingsPopup' );

		this.settingsModal.classList.remove( 'map-show' );
		this.settingsModal.classList.add( 'map-out' );
		document.body.classList.remove( 'map-modal-open' );
		document.querySelectorAll( '.map-settings-overlay' ).forEach(function( $_this ) {
			$_this.classList.remove( 'map-show' );
		});

		(async() => {

		    while( !MAP_SYS.map_document_load )
		    {
		    	//console.log( 'not defined yet' );
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
		if( !( typeof map_ajax !== 'undefined' && map_ajax?.ajax_url ) )
		{
			console.error( MAP_SYS.maplog + 'Error: missing map_ajax variable running checkJsShield function' );
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

		fetch( map_ajax.ajax_url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: new URLSearchParams( data )
		})
		.then( response => response.text() )
		.then( responseText => {
			console.debug( MAP_SYS.maplog, responseText );
		})
		.catch( error => console.error( 'Error sending data running checkJsShield function:', error ) );
	},

	checkConsentModeStatus: function()
	{
		if( !( typeof map_ajax !== 'undefined' && map_ajax?.ajax_url ) )
		{
			console.error( MAP_SYS.maplog + 'Error: missing map_ajax variable running checkConsentModeStatus function' );
			return;
		}

		const googleTagRegex = /^G-/;
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
				error_motivation = 'Google Tag seems missing';
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

			console.log( MAP_SYS.maplog + the_message  );

			//this.showNotificationBar( the_message, 1 );
		}
		else
		{
			let the_message = 'The sending of consent is not set up correctly - ' + result.reason + '.';

			console.error( MAP_SYS.maplog + the_message );

			//this.showNotificationBar( the_message, 2 );
		}

		// Prepare data to be sent to the server
		const data = {
			action: 'map_check_consent_mode_status',
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
					console.debug( MAP_SYS.maplog, responseText );
				})
				.catch( error => console.error( 'Error sending data running checkConsentModeStatus function:', error ) );
		}
	},

	sendDetectedKeys: function( key )
	{
		if( !( typeof map_ajax !== 'undefined' && map_ajax?.ajax_url ) )
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

				fetch(map_ajax.ajax_url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: new URLSearchParams( data )
				})
				.then( response => response.text() )
				.then( responseText => {
					console.groupCollapsed( MAP_SYS.maplog + 'sendDetectedKeys detectableKeys=' + detectableKeys_to_send + ' , detectedKeys=' + detectedKeys_to_send + ' with response :' );
					console.debug( MAP_SYS.maplog, responseText );
					console.groupEnd();
				});
			}
		}
	},

	//inspect cookie script configuration
	debugCookieScripts: function()
	{
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
	},

	//get list of available cookies
	getAvailableCookieList: function()
	{
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
	},

	//get list of activated cookies
	getActivatedCookiesList: function()
	{
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
	},

	//get list of disactivated cookies
	getDisactivatedCookiesList: function()
	{
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
}

document.addEventListener('DOMContentLoaded', function() {
	
	if( !window === window.parent )
	{
		console.debug( MAP_SYS.maplog + 'prevent run on iframe' );
		return false;
	}

	if( typeof map_cookiebar_settings !== 'undefined' )
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

		if( !MAP_SYS.map_initted &&
			typeof map_cookiebar_settings !== 'undefined' )
		{
			MAP_SYS.map_initted = true;

			if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'initting' );

			try {
				MAP.set({
				  settings : map_cookiebar_settings
				});

			}
			catch( error )
			{
				console.error( error );
			}
		}

	}, 3000 );

	setTimeout(function(){

		MAP_SYS.map_document_load = true;

	}, 5000 );

});

window.addEventListener('load', function() {

	MAP_SYS.map_document_load = true;

	if( !window === window.parent )
	{
		console.debug( MAP_SYS.maplog + 'prevent run on iframe' );
		return false;
	}

	if( !MAP_SYS.map_initted &&
			typeof map_cookiebar_settings !== 'undefined' )
	{
		MAP_SYS.map_initted = true;

		if( MAP_SYS?.map_debug ) console.debug( MAP_SYS.maplog + 'initting' );

		try {
			MAP.set({
			  settings : map_cookiebar_settings
			});

		}
		catch( error )
		{
			console.error( error );
		}
	}

	if(
		map_ajax &&
		MAP.settings
	)
	{
		MAP.checkJsShield();

		setTimeout( function(){
			MAP.checkConsentModeStatus();
		}, 800 );
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
	//console.log( $source );
	//console.log( dest );

	var exclusion_list = [
		'type',
		'src',
		'unblocked_src',
		'class',
		'data-cookie-api-key',
		'data-friendly_name'
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

//f. for dom element recreating and previous event removal
function internalRecreateNode( el, withChildren ){
  try {
	  if(withChildren) {
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

	document.querySelectorAll( '.av_gmaps_confirm_link.av_text_confirm_link.av_text_confirm_link_visible' ).forEach( function( element ) {
		element.click();
	});

}

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

//consent mode early as possible initialization
if( typeof MAP !== 'undefined' && typeof MAP.setupConsentModeV2 !== 'undefined' )
{
	MAP.setupConsentModeV2();
}

