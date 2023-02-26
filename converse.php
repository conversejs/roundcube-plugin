<?php

/**
 * Converse.js based XMPP chat plugin plugin for Roundcube webmail
 * @version 0.9.1-alpha
 * @license MIT
 * @author Priyadi Iman Nurcahyo
 * @author Thomas Bruederli <thomas@roundcube.net>
 * @author Yuri Samoilov <root@drlight.fun>
 *
 * Copyright (C) 2013, Priyadi Iman Nurcahyo http://priyadi.net
 * Copyright (C) 2013, The Roundcube Dev Team <hello@roundcube.net>
 * Copyright (C) 2023, Samoilov Yuri Olegovich https://drlight.fun
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
		$rcmail = rcube::get_instance();

		$dont_show_error = true;
		$error_mes = "";
		//  check converse_xmpp_conn_method, converse_xmpp_auth_method, converse_xmpp_hostname and add errors
		if (($this->_config_get('converse_xmpp_conn_method')!=="bosh") && (($this->_config_get('converse_xmpp_conn_method'))!=='websocket')) {
			$dont_show_error = false;
			$error_mes .= " converse_xmpp_conn_method";
		}
		if (($this->_config_get('converse_xmpp_auth_method')!=="login") && (($this->_config_get('converse_xmpp_auth_method'))!=='prebind')) {
			$dont_show_error = false;
			$error_mes .= " converse_xmpp_auth_method";
		}
		// dont mess prebind with websocket!
		if (($this->_config_get('converse_xmpp_conn_method')!=="bosh") && (($this->_config_get('converse_xmpp_auth_method'))=='prebind')) {
			$dont_show_error = false;
			$error_mes .= " converse_xmpp_auth_method and converse_xmpp_conn_method";
		}
		// if you specify prebind auth method you must set converse_xmpp_bosh_prebind_url!
		if (((($this->_config_get('converse_xmpp_bosh_prebind_url')=="") || (is_null($this->_config_get('converse_xmpp_bosh_prebind_url'))))) && (($this->_config_get('converse_xmpp_auth_method'))=='prebind')) {
			$dont_show_error = false;
			$error_mes .= " converse_xmpp_bosh_prebind_url and converse_xmpp_auth_method";
		}
		if (($this->_config_get('converse_xmpp_hostname')=="") || (is_null($this->_config_get('converse_xmpp_hostname')))) {
			$dont_show_error = false;
			$error_mes .= " converse_xmpp_hostname";
			$this->debug_to_console ($error_mes);
		}
		if ($dont_show_error) {
			$args = $_SESSION['converse_xmpp_prebind'];

			if (strpos($args['user'], '@')) {
					list($args['user'], $args['host']) = preg_split('/@/', $args['user']);
			}
			// get jid and password by get request to converse plugin URL
			if ($_REQUEST['_type'] == 'login') {
				$creds_arr = array('jid' => $args['user'].'@'.$this->_config_get('converse_xmpp_hostname'), 'password' => $rcmail->decrypt($args['pass']));
				echo json_encode($creds_arr);
				exit();
			}
			//  get jid, sid, and rid by get request to converse plugin URL
			if ($_REQUEST['_type'] == 'prebind') {
				require_once(__DIR__ . '/php/xmpp-prebind-php/lib/XmppPrebind.php');
				$xsess = new XmppPrebind($this->_config_get('converse_xmpp_hostname'), $args['bosh_prebind_url'],'converse.js-'.uniqid(), false, false);
				$success = true;
				try {
					$xsess->connect($args['user'], $rcmail->decrypt($args['pass']));
					$xsess->auth();
				} catch (Exception $e) {
					rcube::raise_error("Converse-XMPP: Prebind failure: " . $e->getMessage());
					//$rcmail->output->command('display_message', $this->gettext('passwordforbidden'), 'error');
					$success = false;
				}
				if ($success) {
					echo json_encode($xsess->getSessionInfo());
				} else {
					echo json_encode(array("jid"=>$args['user'].'@'.$this->_config_get('converse_xmpp_hostname'),"rid"=>0,"sid"=>0));
				}
				exit();
			}

			if (!$rcmail->output->ajax_call && empty($_REQUEST['_framed']) && $this->_config_get('converse_prebind', array(), 1) > 0) {
				$this->add_texts('localization/', false);
				// try to authenticate
				$this->add_hook('authenticate', array($this, 'authenticate'));
				$this->add_hook('render_page', array($this, 'render_page'));
			}
		} else {
			$this->debug_to_console("Converse failure! Check".$error_mes." variable(s) in config.inc.php! All mentioned variables MUST be filled with proper values!");
			$rcmail->output->command('display_message', 'Converse failure! Check browser debug log!', 'error');
			//rcube::raise_error("Converse failure ");
			return;
		}
		

		$this->debug = $this->_config_get('converse_xmpp_debug', array(), false);
		$this->devel_mode = $this->_config_get('converse_xmpp_devel_mode', array(), false);
		$this->converse_cdn = $this->_config_get('converse_cdn', array(), array());
		$converseconfig = $this->_config_get('converse_config', array(), array());
		$this->converseconfig = array_merge($this->converseconfig, $converseconfig);
	}

	function render_page($event) {

		$rcmail = rcube::get_instance();

		if ($this->already_present == 1 || $rcmail->task == 'login' || !empty($_REQUEST['_extwin']))
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

		$args = $_SESSION['converse_xmpp_prebind'];
		
		if (strpos($args['user'], '@')) {
			list($args['user'], $args['host']) = preg_split('/@/', $args['user']);
		}

		// get server protocol
		if (isset($_SERVER['HTTPS']) &&
		    ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
		    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
		    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
		  $protocol = 'https://';
		}
		else {
		  $protocol = 'http://';
		}
		$converse_prop['jid'] = $args['user'].'@'.$this->_config_get('converse_xmpp_hostname'); // add unique resource id
		$converse_prop['conn_method'] = $this->_config_get('converse_xmpp_conn_method');
		$converse_prop['domain'] = $this->_config_get('converse_xmpp_hostname');
		// save converse CDN version for theme switch function check in converse-rcmail.js
		$converse_prop['cdn_version'] = explode('.', explode('/', $this->_config_get('converse_cdn'))[3])[0];
			//  check configured connection method from config.inc.php
			if ($this->_config_get('converse_xmpp_conn_method') == 'bosh') {
				//  check configured auth method from config.inc.php
				if ($this->_config_get('converse_xmpp_auth_method') == 'login') {
					$converse_prop['authentication'] = 'login';
					$converse_prop['credentials_url'] = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'/?_action=plugin.converse&_type='.$converse_prop['authentication'];
				} else if ($this->_config_get('converse_xmpp_auth_method') == 'prebind') {
					// prebind method with rid, sid, jid (secured)
					$converse_prop['authentication'] = 'prebind';
					$converse_prop['bosh_service_url'] = $args['bosh_url'];
					$converse_prop['prebind_url'] = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'/?_action=plugin.converse&_type='.$converse_prop['authentication'];
				} else {
					$rcmail->session->remove('xmpp');
					return;
				}
			} else if ($this->_config_get('converse_xmpp_conn_method') == 'websocket') {
				$converse_prop['websocket_url'] = $this->_config_get('converse_xmpp_websocket_url');
				$converse_prop['authentication'] = 'login';
				$converse_prop['credentials_url'] = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'/?_action=plugin.converse&_type='.$converse_prop['authentication'];

			} else {
				$rcmail->session->remove('xmpp');
				$rcmail->output->command('display_message', 'Converse failure! There is no appropriate connection method (bosh or websocket) configured!', 'error');
				return;
			}

			//  initialize converse.js from CDN and + custom css to fix some bugs
			$this->include_script($this->converse_cdn . '/dist/converse.js');
			$this->include_stylesheet($this->converse_cdn . '/dist/converse.css');
			$this->include_stylesheet('css/custom.css');
			// TODO  add omemo encryption support (no messages received, error for now)
			//$this->include_script('https://cdn.conversejs.org/3rdparty/libsignal-protocol.min.js');
			// add converse init script
			$this->include_script('js/converse-rcmail.js');
			$this->api->output->add_script('
				var always = '.json_encode($this->_config_get("converse_xmpp_enable_always")).';
				var args = '.$rcmail->output->json_serialize($converse_prop).';
				args.locales_url = "' . $this->converse_cdn . '/locale/{{{locale}}}/LC_MESSAGES/converse.json";
				args.i18n = "'.$locale.'";
				rcmail_converse_init(converse, args, always);
			', 'foot');

			$this->already_present = 1;
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
