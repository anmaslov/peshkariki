<?php

IncludeModuleLangFile(__FILE__);

Class anmaslov_peshkariki extends CModule
{
    var $MODULE_ID = "anmaslov.peshkariki";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;
    var $NEED_MODULES = array("sale");

    function anmaslov_peshkariki()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));

        include($path . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = GetMessage('PESHKARIKI_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('PESHKARIKI_MODULE_DESCRIPTION');

        $this->PARTNER_NAME = GetMessage('PESHKARIKI_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('PESHKARIKI_PARTNER_URI');;
    }

    function InstallEvents()
    {
        RegisterModuleDependences("sale", "onSaleDeliveryHandlersBuildList",
            $this->MODULE_ID, "CDeliveryAnmaslovPeshkariki", "Init");
        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("sale", "onSaleDeliveryHandlersBuildList",
            $this->MODULE_ID, "CDeliveryAnmaslovPeshkariki", "Init");
        return true;
    }

    function DoInstall()
    {
        $this->InstallEvents();
        RegisterModule($this->MODULE_ID);
    }

    function DoUninstall()
    {
        $this->UnInstallEvents();
        UnRegisterModule($this->MODULE_ID);
    }
}
?>