Roundcube Converse.js XMPP plugin 
=================================

This is XMPP plugin for Roundcube Webmail based on converse.js.
Actually this version of plugin is fully support CDN versions of conversejs from 4 to 10 with some bugs (use $rcmail_config['converse_cdn'] parameter in config.inc.php).
One is fully tested on 5.0.5, 6.0.1, 7.0.5, 8.0.1, 9.1.1 and 10.x.x
Also xmpp-prebind-php module is support php7 and up.

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

This plugin was using the same resource (both BOSH prebind and active), but this will changed in near future release.
Everytime you make one page active will result in close connection on the other page.
This version uses only login authentication method of conversejs (utilizes jid + password) which is not secure. There is a prebind method that utilizes rid, jid and sid from prebind_url in future plan (code for this implementation is commented now in converse.php). 
**New version will support BOSH connection method with and without prebind and websocket connection method (during my testings this method is way more stable and faster then BOSH) and many other fixes.**

Stay in touch. Fill free to make PRs ;)

Credits
-------
* Some code were stolen from https://gist.github.com/Zash/3681653
* [Converse.js](http://conversejs.org)
* [Candy Chat](http://candy-chat.github.io/candy/) for its prebinding library
* @devurandom for his attempts to actualize this pluging to 3.0 version of conversejs
