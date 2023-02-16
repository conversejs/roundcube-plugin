<?php
// important variables in the following anonymous functions:
// $args['host'] : IMAP hostname
// $args['user'] : IMAP username
// $args['pass'] : IMAP password

// Hostname of BOSH endpoint, used for prebinding only (called by PHP),
// this must be an absolute URL
$rcmail_config['converse_xmpp_bosh_prebind_url']= function($args) {
	return 'http://localhost:5280/http-bind';
	// return sprintf('http://%s/http-bind', $_SERVER['HTTP_HOST']);
	// return sprintf('http://%s/http-bind', $args['host']);
};

// Hostname of BOSH endpoint, used by web browsers (called by Javascript code),
// this can be a relative URL
$rcmail_config['converse_xmpp_bosh_url']= function($args) {
	//return '/http-bind';
	return 'http://localhost:5280/http-bind';
};

// Hostname portion of XMPP username aka XMPP domain (bare JID), example: "example.net"
$rcmail_config['converse_xmpp_hostname']= function($args) {
	// return preg_replace('/^.*@/', '', $args['user']);
	return $args['host'];
};

// Username portion of XMPP username (bare JID), example: "user"
// if this contains @, this will only take the part before @,
// and the part after @ will replace the hostname definition above.
$rcmail_config['converse_xmpp_username']= function($args) {
	return $args['user'];
	// return preg_replace('/@.*$/', '', $args['user']);
};

// XMPP password
$rcmail_config['converse_xmpp_password']= function($args) {
	return $args['pass'];
};

// Additional converse.js option to use
// refer to https://conversejs.org/docs/html/index.html#configuration-variables
// some options are overriden: expose_rid_and_sid, bosh_service_url, debug,
// prebind, jid, sid, rid
$rcmail_config['converse_config'] = array(
	'blacklisted_plugins' => array(
        ),
	'show_message_avatar' => true,
	'show_send_button' => true,
	//'view_mode' => 'overlayed',
	'allow_dragresize' => true,
	'allow_contact_removal' => false,
	'allow_registration' => false,
	'allow_user_trust_override' => false,
	'auto_login' => true,
	'auto_reconnect' => true,
	'allow_logout' => false,
	'allow_contact_requests' => false,
	'show_client_info' => false,
	'visible_toolbar_buttons' => array(
		'spoiler' => true,
		'call' => false,
    	'emoji' => true,
    	'toggle_occupants' => true
	),
	'theme' => 'default',
	'dark_theme' => 'concord',
	'auto_away' => 300,
	'allow_adhoc_commands' => false,
	'omemo_default' => true,
	'singleton' => false //for helpdesk like chat
);

// Always embed chat even if prebinding is not configured or failed
$rcmail_config['converse_xmpp_enable_always'] = false;

// Enable debug mode
$rcmail_config['converse_xmpp_debug'] = false;

// Enable development mode
$rcmail_config['converse_xmpp_devel_mode'] = false;

// Configure XMPP resource prefix. XMPP resource is set to this variable
// appended with a unique id. Defaults to 'roundcube-'.
$rcmail_config['converse_xmpp_resource_prefix'] = 'roundcube-';

// URL prefix of the CDN where Converse.js is being included from.
// May point to a local path, if you use the same directory structure as
// on the CDN (dist/ + css/).
//$rcmail_config['converse_cdn'] = 'https://cdn.conversejs.org/5.0.5';
$rcmail_config['converse_cdn'] = 'https://cdn.conversejs.org/10.0.0';
