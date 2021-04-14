<?php
require_once($_SERVER['DOCUMENT_ROOT']. "/bitrix/modules/main/include/prolog_before.php");

if(CModule::includeModule("db.push")){
    if(isset($_GET["getNotification"])){
        $notification = db\push\Notification::getAll();
        $notification = json_encode($notification);
        echo $notification;
    }
}
?>