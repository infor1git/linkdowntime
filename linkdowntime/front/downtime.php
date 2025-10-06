<?php
include ('../../../inc/includes.php');

Session::checkRight(PluginLinkdowntimeDowntime::$rightname, READ);

Html::header(__('Link Downtime Manager', 'linkdowntime'), $_SERVER['PHP_SELF'], "tools", "pluginlinkdowntimemenu", "downtime");

Search::show('PluginLinkdowntimeDowntime');

Html::footer();
