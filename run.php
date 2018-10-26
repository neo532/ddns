<?php
$dirpath = dirname(__FILE__);
$confList = require_once($dirpath.'/conf.php');
require_once($dirpath.'/ddns.php');
foreach($confList as $conf) {
    $msg = (new Ddns($conf))->upgradeIp();
    if( false!=$msg ){
        echo $msg;
    }
}
