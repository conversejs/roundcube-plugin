Roundcube Converse.js XMPP plugin 
=================================

This is XMPP plugin for Roundcube Webmail based on converse.js.
Actually this version of plugin is fully support CDN versions of conversejs from 4 to 10 with some bugs (use $rcmail_config['converse_cdn'] parameter in config.inc.php).
One is fully tested on 5.0.5, 6.0.1, 7.0.5, 8.0.1, 9.1.1 and 10.x.x

Requirements
------------
* BOSH support in XMPP server or BOSH connection manager
* (optional) BOSH proxy in web server, to avoid crossdomain issues
* (recommended) XMPP server set to broadcast incoming messages to all resources. See notes below.

Installation
------------
* `cd your_roundcube_dir/plugins`
* `git clone https://github.com/drlight17/roundcube-converse.js-xmpp-plugin converse`
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

This plugin using the same resource (both BOSH prebind and active) in order to fix stability, but there is no support for multiple web pages with one jid's s session. Everytime you make one page active will result in close connection on the other page.


Credits
-------
* Some code were stolen from https://gist.github.com/Zash/3681653
* [Converse.js](http://conversejs.org)
* [Candy Chat](http://candy-chat.github.io/candy/) for its prebinding library
