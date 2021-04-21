<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Page\Asset;

Loc::loadMessages(__FILE__);

class db_push extends CModule{
    private $addTemplateString;
    private $bitrixTemplate = '/bitrix/templates/bitrix24/header.php';

    public function __construct(){
        if(file_exists(__DIR__."/version.php")){
            $arModuleVersion = array();

            include_once(__DIR__."/version.php");

            $this->MODULE_ID = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

            $this->MODULE_NAME = Loc::getMessage("DB_PUSH_MODULE_NAME");
            $this->MODULE_DESCRIPTION = Loc::getMessage("DB_PUSH_MODULE_DESCRIPTION");
            $this->PARTNER_NAME = Loc::getMessage("DB_PUSH_PARTNER_NAME");
            $this->PARTNER_URI = Loc::getMessage("DB_PUSH_PARTNER_URI");
            $this->$addTemplateString = '<?php global $USER; if($USER->IsAuthorized() && CModule::includeModule("'.$this->MODULE_ID.'")) $APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/db_push/db_push.js", true); ?>';

            return false;
        }
    }

    public function DoInstall(){
        global $APPLICATION;

        if(CheckVersion(ModuleManager::getVersion("main"), "14.00.00")){
            $this->InstallFiles();
            $this->InstallDB();

            ModuleManager::registerModule($this->MODULE_ID);
            file_put_contents($_SERVER["DOCUMENT_ROOT"].$this->bitrixTemplate, $this->addTemplateString, FILE_APPEND);

            $this->InstallEvents();
        }else{

            $APPLICATION->ThrowException(
                Loc::getMessage("DB_PUSH_BITRIX_VERSION_ERROR")
            );
        }

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("DB_PUSH_INSTALL_TITLE")." \"".Loc::getMessage("DB_PUSH_MODULE_NAME")."\"",__DIR__."/step.php"
        );

        return false;
    }
    
    public function InstallEvents(){
        return false;
    }

    public function InstallFiles(){
        CopyDirFiles(dirname(__FILE__)."/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/bitrix24/".get_class($this), true, true);
        CopyDirFiles(dirname(__FILE__)."/ajax", $_SERVER["DOCUMENT_ROOT"]."/ajax", true, true);
        CopyDirFiles(dirname(__FILE__)."/img", $_SERVER["DOCUMENT_ROOT"]."/upload/".get_class($this), true, true);

        return false;
    }

    public function DoUninstall(){
        global $APPLICATION;

        if($_REQUEST["step"] < 2){
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage("DB_PUSH_UNINSTALL_TITLE")." \"".Loc::getMessage("DB_PUSH_MODULE_NAME")."\"",__DIR__."/unstep1.php"
            );
        }else if($_REQUEST["step"] == 2){
            $this->UnInstallFiles();
            $this->UnInstallDB();
            $this->UnInstallEvents();

            ModuleManager::unRegisterModule($this->MODULE_ID);

            self::deleteStringFromTemplate();

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage("DB_PUSH_UNINSTALL_TITLE")." \"".Loc::getMessage("DB_PUSH_MODULE_NAME")."\"",__DIR__."/unstep2.php"
            );
        }
        return false;
    }

    public function UnInstallFiles(){
        $dirName = str_replace(".", "/", $this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/templates/bitrix24/".get_class($this));
        DeleteDirFilesEx("/ajax/".$dirName);
        DeleteDirFilesEx("/upload/".get_class($this));

        return false;
    }

    public function UnInstallEvents(){
        return false;
    }

    public function UnInstallDB(){
        global $DB, $DBType;

        if($_REQUEST["save_data"] != "Y"){
            Option::delete($this->MODULE_ID);
            $DB->RunSQLBatch(__DIR__."/db/".strtolower($DBType)."/uninstall.sql");
        }

        return false;
    }

    public function InstallDB(){
        global $DB, $DBType;

        $DB->RunSQLBatch(__DIR__."/db/".strtolower($DBType)."/install.sql");

        return false;
    }

    public function deleteStringFromTemplate(){
       $data = file($_SERVER["DOCUMENT_ROOT"].$this->bitrixTemplate);
       
        $out = array();
       
        foreach($data as $line) {
            if(trim($line) != $this->addTemplateString) {
                $out[] = $line;
            }
        }
       
        $fp = fopen($_SERVER["DOCUMENT_ROOT"].$this->bitrixTemplate, "w+");
        flock($fp, LOCK_EX);
        foreach($out as $line) {
            fwrite($fp, $line);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
?>