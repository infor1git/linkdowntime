<?php

include ('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight('profile', READ);

if (isset($_POST['profile_id'])) {
    $profile = new PluginLinkdowntimeProfile();
    $profile->showForm($_POST['profile_id']);
}
?>
