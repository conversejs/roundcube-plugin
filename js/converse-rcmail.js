
/**
 * Converse XMPP client integration script for Roundcube webmail
 */
function rcmail_converse_init(converse, args)
{
    // get last RID from local storage
    if (args.sid && !args.rid && window.localStorage) {
        args.rid = rcmail.local_storage_get_item('converse.rid');
    }
    
    // full converse API is only visible to plugins, so let's create a plugin
    converse.plugins.add('rcmail', {
	initialize: function () {
	    // hook into login event and keep XMPP session in Roundcube's session
	    this._converse.api.listen.on('onReady', function(e){
		if (!args.sid && e.target.bare_jid)
		rcmail.http_post('plugin.converse_bind', { jid:e.target.bare_jid, sid:_converse.tokens.get('sid') });
	    });

	    //store _converse in a variable for use in our callback functions
	    var __converse = this._converse;

	    // log out of converse when logging out of roundcube
	    converse.logout = function(e) {
		if(e == 'logout'){
		    __converse.api.user.logout();
		}
	    };

	    // store last RID for continuing session after page reload
	    converse.storeRID = function(){
		//expose RID and SID so we can store them
		__converse.expose_rid_and_sid = true;
		if (window.localStorage && window.rcmail && __converse.api.tokens.get('sid')) {
		    rcmail.local_storage_set_item('converse.rid', __converse.api.tokens.get('rid'));
		}
		//hide RID and SID again
		__converse.expose_rid_and_sid = false;
	    };
	}
    });
    
    //whitelist plugin, so converse doesn't refuse to load it
    if(args.whitelisted_plugins instanceof Array){
	args.whitelisted_plugins.push('rcmail');
    }else{
	args.whitelisted_plugins=['rcmail'];
    }

    //initialize converse
    converse.initialize(args, function(e){ /* console.log(converse) */ });

    // store last RID for continuing session after page reload
    $(window).on('unload', converse.storeRID);

    // log out of converse when logging out of roundcube
    rcmail.addEventListener('beforeswitch-task', converse.logout);
}