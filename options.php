<?php

$module_id = 'anmaslov.peshkariki';

IncludeModuleLangFile(__FILE__);
if (!$USER->CanDoOperation($module_id))
{
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

CModule::IncludeModule($module_id);
CModule::IncludeModule("iblock");
CModule::IncludeModule("sale");

$aTabs = array(
    array("DIV" => "edit1", "TAB" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_PROP'), "TITLE" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_PROP_TITLE')),
    array("DIV" => "edit2", "TAB" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_CITY'), "TITLE" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_CITY_TITLE')),
    array("DIV" => "edit3", "TAB" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_ORDER'), "TITLE" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_ORDER_TITLE')),
    array("DIV" => "edit4", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$rsStatus = CSaleStatus::GetList(array(), array("LID" => LANGUAGE_ID));
$arrStatus = array();
while ($arStatus = $rsStatus->Fetch())
{
    $arrStatus[$arStatus["ID"]] = $arStatus["NAME"];
}

$arAllOptions = Array(
    array("PROPERTY_LOGIN", GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_LOGIN"), array("text", 50)),
    array("PROPERTY_PASSWORD", GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_PASSWORD"), array("pwd", 50)),
    array("PROPERTY_STATUS_ORDER", GetMessage("ANMASLOV_PESHKARIKI_OPT_STATUS_ORDER"), array("select", $arrStatus)),

   /* Array("property_manager_email", GetMessage('STALL_OPT_PROP_MANAGER_EMAIL'), array("text"),
        COption::GetOptionString("main","email_from","admin@site.com")),*/

);

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
{
    if (strlen($RestoreDefaults) > 0)
    {
        COption::RemoveOption($module_id);
        $z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
        while($zr = $z->Fetch())
            $APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
    }
    else
    {
        foreach($arAllOptions as $arOption)
        {
            $name = $arOption[0];
            $val = $_POST[$name];
            if ($arOption[2][0] == "checkbox" && $val != "Y")
                $val = "N";
            COption::SetOptionString($module_id, $name, $val, $arOption[1]);
        }
        COption::SetOptionString($module_id, 'message', $_POST['message']);
        COption::SetOptionInt($module_id, 'iblock_id', $_POST['iblock_id']);
    }
}

$message = COption::GetOptionString($module_id,'message', GetMessage('STALL_OPT_MESSAGE_DEFAULT'));
$IBLOCK_ID = COption::GetOptionInt($module_id,'iblock_id', 0);

$tabControl->Begin();
?>
<form method="POST"
      action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>"
      name="anmaslov.stall_settings">

    <?=bitrix_sessid_post();?>
    <?
    $tabControl->BeginNextTab();
    foreach($arAllOptions as $arOption):
        $val = COption::GetOptionString($module_id, $arOption[0], $arOption[3]);
        $type = $arOption[2];
        ?>
        <tr>
            <td valign="top" width="50%">
                <? echo $arOption[1];?>:
            </td>
            <td valign="top" width="50%">
                <input type="text" size="<?echo $type[1]?>" maxlength="255"
                       value="<?echo htmlspecialcharsbx($val)?>"
                       name="<?echo htmlspecialcharsbx($arOption[0])?>" />
            </td>
        </tr>
        <?
    endforeach;
    $tabControl->BeginNextTab();
    ?>

    <tr>
        <td width="40%"><?echo GetMessage("STALL_OPT_CHOOSE_IBLOCK") ?></td>
        <td width="60%">
            <?echo GetIBlockDropDownListEx(
                $IBLOCK_ID,
                'iblock_type_id',
                'iblock_id',
                array(
                    "MIN_PERMISSION" => "X",
                    "OPERATION" => "iblock_export",
                ),
                '',
                '',
                'class="adm-detail-iblock-types"',
                'class="adm-detail-iblock-list"'
            );?>
        </td>
    </tr>

    <tr>
        <td width="30%" valign="top"><?echo GetMessage('STALL_OPT_MESSAGE')?>: </td>
        <td width="70%"><textarea cols="50" rows="7" name="message"><?echo htmlspecialcharsbx($message)?></textarea></td>
    </tr>
    <?
    $tabControl->BeginNextTab();
    ?>
    <tr>
        <td width="30%">checkbox to add order</td>
        <td>Checbox and combobox</td>
    </tr>
    <?
    $tabControl->BeginNextTab();
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");
    $tabControl->Buttons();?>
    <script language="JavaScript">
        function confirmRestoreDefaults()
        {
            return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>');
        }
    </script>
    <input type="submit" name="Update" value="<?echo GetMessage("MAIN_SAVE")?>">
    <input type="hidden" name="Update" value="Y">
    <input type="reset" name="reset" value="<?echo GetMessage("MAIN_RESET")?>">
    <input type="submit" name="RestoreDefaults"
           title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>"
           OnClick="return confirmRestoreDefaults();"
           value="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>">

    <?$tabControl->End();?>
</form>