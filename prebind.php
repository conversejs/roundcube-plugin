<?php

require_once(__DIR__ . '/php/xmpp-prebind-php/lib/XmppPrebind.php');

//  debug
function debug_to_console($data) {
    echo "<script>console.log('Debug Objects: " .json_encode($data) . "' );</script>";
}
    
    $password = "password";
    $domain = "domain";
    $prefix = "roundcube-";
    $bosh_url = "/http-bind";
    $username = "username";
    /*$username = $_REQUEST['username'];
    $password = $_REQUEST['password'];
    $domain = $_REQUEST['domain'];
    $prefix = $_REQUEST['prefix'];
    $bosh_url = $_REQUEST['bosh_url'];*/


    //debug_to_console("Session_id:".session_id());

    $xmppPrebind = new XmppPrebind($domain, $bosh_url, $prefix.$username, false, false);
    $xmppPrebind->connect($username, $password);
    $xmppPrebind->auth();
    $sessionInfo = $xmppPrebind->getSessionInfo(); // array containing sid, rid and jid
    echo json_encode($sessionInfo);
?>