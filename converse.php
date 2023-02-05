<?php

/**
 * Converse.js based XMPP chat plugin plugin for Roundcube webmail
 *
 * @author Priyadi Iman Nurcahyo
 * @author Thomas Bruederli <thomas@roundcube.net>
 *
 * Copyright (C) 2013, Priyadi Iman Nurcahyo http://priyadi.net
 * Copyright (C) 2013, The Roundcube Dev Team <hello@roundcube.net>
 *
 * This software is published under the MIT license.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 */

class converse extends rcube_plugin
{
	public $task = '?(?!logout).*';
	public $noframe = false;
	public $noajax = false;
	private $debug = false;
	private $devel_mode = false;
	private $resource_prefix = "";
	private $converseconfig = array();
	private $converse_cdn = "";
	private $already_present = 0;

	// debug to console
	function debug_to_console($data) {
	    echo "<script>console.log('Debug Objects: " .json_encode($data) . "' );</script>";
	}

	function init() {
		$this->load_config();
		// we at least require a BOSH url in config
		if ($this->_config_get('converse_xmpp_bosh_url') || $this->_config_get('converse_xmpp_enable_always')) {
			$rcmail = rcube::get_instance();
			//$TEST = $rcmail->decrypt($_SESSION['password']);
			if (!$rcmail->output->ajax_call && empty($_REQUEST['_framed']) && $this->_config_get('converse_prebind', array(), 1) > 0) {
				$this->add_texts('localization/', false);
				$this->add_hook('render_page', array($this, 'render_page'));
				//samoilov tries to bind or prebind
				$this->add_hook('authenticate', array($this, 'authenticate'));
			}
		}

		$this->debug = $this->_config_get('converse_xmpp_debug', array(), false);
		$this->devel_mode = $this->_config_get('converse_xmpp_devel_mode', array(), false);
		$this->converse_cdn = $this->_config_get('converse_cdn', array(), array());
		$converseconfig = $this->_config_get('converse_config', array(), array());
		$this->converseconfig = array_merge($this->converseconfig, $converseconfig);
	}

	function render_page($event) {

		$rcmail = rcube::get_instance();

		// samoilov add exclusion managesieve plugin as there is some conflict with it causing error 500
                if ($this->already_present == 1 || $rcmail->task == 'login' ||  $rcmail->action == 'plugin.managesieve' || !empty($_REQUEST['_extwin']))
                        return;

		// map session language with converse.js locale
		$locale = 'en';
		$userlang = $rcmail->get_user_language();
		$userlang_ = substr($userlang, 0, 2);
		$locales = array(
			'af',
			'de',
			'en',
			'es',
			'fr',
			'he',
			'hu',
			'id',
			'it',
			'ja',
			'nl',
			'pt_BR',
			'ru',
			'zh'
		);
		if (in_array($userlang, $locales))
			$locale = $userlang;
		else if (in_array($userlang_, $locales))
			$locale = $userlang_;

		$converse_prop = array(
			'prebind' => false,
			'expose_rid_and_sid' => $this->_config_get('converse_xmpp_enable_always', array(), false),
			'bosh_service_url' => $this->_config_get('converse_xmpp_bosh_url', array(), '/http-bind'),
			'debug' => $this->debug,
		);

		$converse_prop = array_merge($this->converseconfig, $converse_prop);

		// samoilov prebind for check RC creds with xmpp server + login to auth with xmpp server
		require_once(__DIR__ . '/php/xmpp-prebind-php/lib/XmppPrebind.php');
		$args = $_SESSION['converse_xmpp_prebind'];

		if (strpos($args['user'], '@')) {
			list($args['user'], $args['host']) = preg_split('/@/', $args['user']);
		}

		$xsess = new XmppPrebind($this->_config_get('converse_xmpp_hostname'), $args['bosh_prebind_url'],$this->_config_get('converse_xmpp_resource_prefix').$args['user'], false, false);
		$success = true;
		try {
			$xsess->connect($args['user'], $rcmail->decrypt($args['pass']));
			$xsess->auth();
		} catch (Exception $e) {
			rcube::raise_error("Converse-XMPP: Prebind failure: " . $e->getMessage());
			$success = false;
		}
		// samoilov check if always bind enabled or prebind is successful
		if (($success)||($this->_config_get('converse_xmpp_enable_always'))) {

			// samoilov login method with password (NOT secured!)
			// TODO samoilov add resource_prefix uid for connection stability in multipages of RC (uniqid())?
			$converse_prop['jid'] = $args['user'].'@'.$this->_config_get('converse_xmpp_hostname').'/'.$this->_config_get('converse_xmpp_resource_prefix').$args['user']/*.'-'.uniqid()*/;
			$converse_prop['password'] = $rcmail->decrypt($args['pass']);
			$converse_prop['authentication'] = 'login';

			//TODO samoilov prebind method with rid, sid, jid (secured) - need to modify converse-bosh.js to operate rid, sid, jid instead of prebind_url
			/*$sinfo = json_encode($xsess->getSessionInfo()); // array containing sid, rid and jid converts to JSON
			$converse_prop['authentication'] = 'prebind';
			$converse_prop['bosh_service_url'] = $args['bosh_url'];
			$converse_prop['prebind_url'] = 'https://drlight.fun:8081/plugins/converse/prebind.php';
			$converse_prop['jid'] = $args['user'].'@'.$this->_config_get('converse_xmpp_hostname').'/'.$this->_config_get('converse_xmpp_resource_prefix').$args['user'];*/

			// samoilov initialize converse.js from CDN and + custom css to fix some bugs
			$this->include_script($this->converse_cdn . '/dist/converse.js');
			$this->include_stylesheet($this->converse_cdn . '/dist/converse.css');
			$this->include_stylesheet('css/custom.css');
			// TODO samoilov add omemo encryption support (no messages received, error)
			//$this->include_script('js/libsignal-protocol.js');
	    	echo '<script type="text/javascript">
	    		function rcmail_converse_init(converse, args)
					{
						//console.log("SID:" + args.sid);
						//console.log("args:" + JSON.stringify(args, null, 4));
						//console.log("converse:" + JSON.stringify(converse, null, 4));
						converse.initialize(args, function(e){ /* console.log(converse) */ });
						// log out of converse when logging out of roundcube
						rcmail.addEventListener("beforeswitch-task", converse.logout);
					}
	    	</script>';

				$this->api->output->add_script('
					var args = '.$rcmail->output->json_serialize($converse_prop).';
					args.locales_url = "' . $this->converse_cdn . '/locale/{{{locale}}}/LC_MESSAGES/converse.json";
					args.i18n = "'.$locale.'";
					rcmail_converse_init(converse, args);
				', 'foot');

			$this->already_present = 1;
		} else {
			$rcmail->session->remove('xmpp');
		}
	}


	function authenticate($args) {
		if ($prebind_url = $this->_config_get('converse_xmpp_bosh_prebind_url', $args)) {
			$rcmail = rcmail::get_instance();
			$xmpp_prebind = array(
				'bosh_prebind_url' => $prebind_url,
				'bosh_url' => $this->_config_get('converse_xmpp_bosh_url', $args, '/http-bind'),
				'host' => $this->_config_get('converse_xmpp_hostname', $args, $args['host']),
				'user' => $this->_config_get('converse_xmpp_username', $args, $args['user']),
				'pass' => $rcmail->encrypt($this->_config_get('converse_xmpp_password', $args, $args['pass'])),
			);
			$valid = true;
			foreach ($xmpp_prebind as $k => $val) {
				if (empty($val))
					$valid = false;
			}

			if ($valid)
				$_SESSION['converse_xmpp_prebind'] = $xmpp_prebind;
		}

		return $args;
	}

	function _config_get($opt, $args = array(), $default = null) {
		$rcmail = rcmail::get_instance();
		$value = $rcmail->config->get($opt, $default);
		if (is_callable($value))
			return $value($args);
		return $value;
	}

}
