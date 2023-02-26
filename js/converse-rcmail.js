function rcmail_converse_init(converse, args, always)
{

	// init delay function
	const delay = ms => new Promise(res => setTimeout(res, ms));

	// fix of conversejs ui boxes resizing on the pages with iframes
	async function resize_fix() {
		await delay (1000); // TODO we need to use some _converse.api (start****Resize is not suitable as there are the same code rows and bugs) to check when to bind events to conversejs controlbox instead of 1000 ms delay =)
		$(".dragresize").mousedown(function () {
			$('iframe').css('pointer-events', 'none');
	    });
	    $(".dragresize").mouseup(function () {
	    	$('iframe').css('pointer-events', 'initial');
	    });
	}
	// function to show conn method and auth method
	/*function debug_converse (){
		console.log("Auth method: "+args.authentication);
		console.log("Conn method: "+args.conn_method);
	}*/

	// function to save creds depend on auth type (for prebind - rid, sid and jid, for login - password and jid)
	function save_creds_to_storage (connection){
		window.localStorage.setItem('saved_jid', connection.bare_jid);
		if (args.authentication == 'prebind') {
			if (connection.connection._proto.sid != "0") {
				window.localStorage.setItem('saved_sid', connection.connection._proto.sid);
			}
			if (connection.connection._proto.rid != "1") {
				window.localStorage.setItem('saved_rid', connection.connection._proto.rid);
			}
		} else {
			if (window.localStorage.getItem('saved_pass') == null) {
				window.localStorage.setItem('saved_pass', connection.connection.pass);
			}
			
		}
	}

	// conversejs init function
	async function conversejs_init(args) {
		//debug_converse ();
		converse.initialize(args, function(e){});
		converse.plugins.add('converse-rcmail', {
			initialize: function () {
				plugin_registered = true;
				__converse_api = this._converse.api;
				rc_logged_out = false;
				// if always is on dont hide conversejs
				//console.log (args);
				__converse_api.listen.once('connectionInitialized', async function(e){
					if (!(args.show)) {
						$('#conversejs').hide();
					}
					// check for autologin failure; if connection fails turn allow_logout to true 
					// TODO async function waiting for connection with 2s delay - maybe use some api?
					await delay(2000);
					//console.log (this);
					//return;
					
					if ((this.connection != null)||(window.localStorage.getItem('prebind_autologin_failed') !== null )) {
						// TODO bosh prebind autologin failure in v > 5 - works because of js error as this.connection.connected is undefined
						// TODO in <= 5 - no login password promt, only disconnected status
						
						if (!(this.connection.connected)) {
							//console.log("Connection failed!");
							if ((args.cdn_version <6)&&(window.localStorage.getItem('prebind_autologin_failed') == null )) {
								if (args.conn_method == "websocket") { // bosh login exit fix?
									window.localStorage.setItem('prebind_autologin_failed', true);
									//__converse_api.user.logout();
								}
							}
							$('#toggle-controlbox').removeClass('hidden');
							$('#conversejs').show();
						} else {
							if (this.connection.authenticated) {
								$('#conversejs').show();
							}
						}
					} else {
						//console.log("Prebind connection failed!");
						window.localStorage.setItem('prebind_autologin_failed', true);
						location.reload();
					}
				});
				// listen to bind resize_fix actions to all conversejs dragresize events
				__converse_api.listen.on('controlBoxOpened', function(e){
					resize_fix();
				});
				__converse_api.listen.on('chatBoxInitialized', function(e){
					resize_fix();
				});
				__converse_api.listen.on('chatRoomInitialized', function(e){
					resize_fix();
				});
				// clear creds
				__converse_api.listen.once('logout', function(e){
					if (!(rc_logged_out)) {
						window.localStorage.setItem('logged_out', true);
					}
					clear_saved_creds();
					location.reload(); // to show logon form
				});
				__converse_api.listen.once('discoInitialized', function(e){
					if (this.connection.authenticated) {
						save_creds_to_storage(this);
						window.localStorage.removeItem('logged_out');
						window.localStorage.removeItem('prebind_autologin_failed');
						if (args.cdn_version > 5) {
							$('#toggle-controlbox').removeClass('hidden');
						}
						$('#conversejs').show(); //works only with bosh - prebind

					}
				});
				// bind to RC logout button to logout from conversejs
				$( ".logout" ).on( "click", function() {
					rc_logged_out = true;
					__converse_api.connection.disconnect();
					window.localStorage.clear();
				});
			}
		});
	}

	function clear_saved_creds() {
		window.localStorage.removeItem('saved_pass');
		window.localStorage.removeItem('saved_rid');
		window.localStorage.removeItem('saved_sid');
		window.localStorage.removeItem('saved_jid');
	}

	
	function theme_sync () {
		// set default dark theme to concord if dark_theme var is not defined or defined with not proper value (for now they are dracula and concord) in config.inc.php
		if (typeof args.dark_theme == 'undefined') {
			if (typeof args.theme == 'undefined') {
				args.dark_theme = 'concord';
			} else {
				if ((args.theme == 'dracula'||args.theme == 'concord')) {
					args.dark_theme = args.theme;
				} else {
					args.dark_theme = 'concord';
				}
			}
		}

		if (typeof args.theme == 'undefined'){
			// bind to dark/light mode RC button to switch conversejs theme after click. if not set default dark-theme is hardcoded to concord. to change - see 6 and 11 code rows
			$( ".theme" ).on( "click", function() {
				if (document.cookie.indexOf('colorMode=light') > -1) {
					//console.log("RC theme is dark.");
					if ((typeof args.dark_theme !== 'undefined')&&(args.dark_theme !== 'default')) {
						$('#conversejs').toggleClass('theme-default theme-'+args.dark_theme);
					} else {
						$('#conversejs').toggleClass('theme-default theme-'+args.dark_theme_saved);
					}
				} else {
					//console.log("RC theme is light.");
					if ((typeof args.dark_theme !== 'undefined')&&(args.dark_theme !== 'default')) {
						$('#conversejs').toggleClass('theme-'+args.dark_theme+' theme-default');
					} else {
						$('#conversejs').toggleClass('theme-'+args.dark_theme_saved+' theme-default');
					}
				}
				
			});

			// change conversejs theme based on rc mode at initialization
			args.dark_theme_saved = args.dark_theme;

			if (document.cookie.indexOf('colorMode=dark')==document.cookie.indexOf('colorMode=light'))
			{
				//console.log("RC theme is not set. Force setting it to system default!");
				if (window.matchMedia('(prefers-color-scheme: light)').matches) {
					document.cookie = "colorMode=light";
				} else {
					document.cookie = "colorMode=dark";
				}
			}

			if (!(document.cookie.indexOf('colorMode=dark') > -1)) {
				//console.log("RC theme is light.");
				args.dark_theme = 'default';
			}
			args.theme = args.dark_theme;
		} else {
			console.log ("You have fixed conversejs theme to browser theme by setting theme="+args['theme']+" parameter in config.inc.php. Current dark_theme parameter is set to "+args['dark_theme']+". Comment it out if you want to sync conversejs theme with RC theme.");
		};
	}

	//whitelist plugin, so converse doesn't refuse to load it
    if (args.whitelisted_plugins instanceof Array) {
		args.whitelisted_plugins.push('converse-rcmail');
    } else {
		args.whitelisted_plugins=['converse-rcmail'];
    }
	
    if (args.cdn_version > 5) {
    	theme_sync ();
    } else {
    	console.log ("You use too old conversejs CDN in settings for theming support.");
    }


	// hide conversejs ui until authenticated except for always embed enabled

	if (always == true) {
		//console.log("Always is on.");
		args.show=true;
	} else {
		console.log("Always is off. In case of auth failure conversejs UI will not appear!");
		args.show=false;
	}
	// check for saved creds
	if ((window.localStorage.getItem('saved_jid') !== null) && (window.localStorage.getItem('saved_rid') !== null) && (window.localStorage.getItem('saved_sid') !== null)) {
		//console.log("Found saved rid, sid and jid.");
		args.jid = window.localStorage.getItem('saved_jid');
		if ((window.localStorage.getItem('saved_sid')=="0") || (window.localStorage.getItem('saved_rid')=="1")) {
			//console.log("Bad saved sid or rid. Using saved pass.");
			//args.jid = '@'+args.domain;
			// clean up rid and sid
			window.localStorage.removeItem('saved_rid');
			window.localStorage.removeItem('saved_sid');
			args.authentication = 'login';
			args.password = window.localStorage.getItem('saved_pass');
		} else {
			args.sid = window.localStorage.getItem('saved_sid');
			args.rid = window.localStorage.getItem('saved_rid');
		}


	} else if ((window.localStorage.getItem('saved_jid') !== null) && (window.localStorage.getItem('saved_pass') !== null)) {
		//console.log("Found saved jid and password.");
		args.jid = window.localStorage.getItem('saved_jid');
		args.password = window.localStorage.getItem('saved_pass');
		args.authentication = 'login'; // need this to not save rid and sid
	}

	// check if logged out by button
	if(window.localStorage.getItem('logged_out')==='true'){
		//console.log("Logged out by button! Now you need to relogin into RC!");
		args.credentials_url ='';
	}

	// check if auth method is prebind
	if (args.authentication=='prebind') {
	//if (args.conn_method=='bosh') {
		args.auto_login = true;
		args.allow_logout = false; // turn off logout button for prebind method as it will try to login again
	} else {
		args.auto_login = false;
	}

	// check if prebind autologin failed

	if (window.localStorage.getItem('prebind_autologin_failed')=='true') {
		//console.log("Cant't auto login using prebind method! Switched to login method.");
		args.prebind_url='';
		args.credentials_url=window.location.href+'/?_action=plugin.converse&_type=login';
		args.authentication = 'login';
		args.auto_login = true;
		args.allow_logout = true; // turn on logout button for prebind_autologin_failed case
	}

	args.auto_reconnect = false;

	conversejs_init(args);
}