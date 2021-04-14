<?php

use Bitrix\Main\Localization\Loc;

if(!check_bitrix_sessid()){
    return;
}

$module_id = pathinfo(dirname(__DIR__))["basename"];

Loc::LoadMessages(__FILE__);
?>
<form action="<?php $APPLICATION->GetCurPage() ?>">
    <?=bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?php echo(LANG); ?>">
    <input type="hidden" name="id" value="<?php echo $module_id; ?>">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <?php echo CAdminMessage::ShowMessage(Loc::getMessage("DB_PUSH_UNINST_WARN")); ?>
    <p><?php echo Loc::getMessage("DB_PUSH_UNINST_SAVE"); ?></p>
    <p><input type="checkbox" name="save_data" id="save_data" value="Y" checked><label for="save_data"><?php echo Loc::getMessage("DB_PUSH_SAVE_TABLES"); ?></label></p>
    <input type="submit" value="<?php echo(Loc::getMessage("DB_PUSH_UNINST_DEL")); ?>">
</form>