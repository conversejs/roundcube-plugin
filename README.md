Roundcube Converse.js XMPP plugin 
=================================

This is XMPP plugin for Roundcube Webmail based on converse.js.

It fully supports CDN versions of conversejs from 4 to 10 with some bugs.

It is fully tested on 5.0.5, 6.0.1, 7.0.5, 8.0.1, 9.1.1 and 10.x.x

Xmpp-prebind-php module supports php7 and newer


Requirements
------------
* BOSH support in XMPP server or BOSH connection manager
* (optional) BOSH proxy in web server, to avoid crossdomain issues
* (recommended) XMPP server set to broadcast incoming messages to all resources.
* (recommended) XMPP server with websockets connection support

Installation
------------
* `cd your_roundcube_dir/plugins`
* `git clone https://github.com/conversejs/roundcube-plugin converse`
* `cd converse`
* `cp config.inc.php.dist config.inc.php`
* `vi config.inc.php` (make necessary adjustments)
* `cd your_roundcube_dir/`
* `vi config/main.inc.php` (add 'converse' to $rcmail_config['plugins'])
* done!

If you are already logged on to Roundcube, you need to log out and log back in
for the plugin to initialize correctly. You can also remove all your PHP
session files to force log out all of your users.

Notes
-----
This version supports BOSH connection method with login and prebind auth methods and websocket connection method with login auth method. The last one is recommended as more stable and faster then BOSH.

Stay in touch. Fill free to make PRs ;)

Credits
-------
* Some code were stolen from https://gist.github.com/Zash/3681653
* [Converse.js](http://conversejs.org)
* [Candy Chat](http://candy-chat.github.io/candy/) for its prebinding library
* @devurandom for his efforts to actualize this pluging to 3.0 version of conversejs
