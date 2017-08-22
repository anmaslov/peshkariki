<?php

$module_id = 'anmaslov.peshkariki';

IncludeModuleLangFile(__FILE__);
if (!$USER->CanDoOperation($module_id))
{
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

CModule::IncludeModule($module_id);
CModule::IncludeModule("sale");

$aTabs = array(
    array("DIV" => "edit1", "TAB" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_PROP'), "TITLE" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_PROP_TITLE')),
    array("DIV" => "edit2", "TAB" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_CITY'), "TITLE" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_CITY_TITLE')),
    array("DIV" => "edit3", "TAB" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_ORDER'), "TITLE" => GetMessage('ANMASLOV_PESHKARIKI_OPT_TAB_ORDER_TITLE')),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

//get statuses
$rsStatus = CSaleStatus::GetList(array(), array("LID" => LANGUAGE_ID));
while ($arStatus = $rsStatus->Fetch())
{
    $arStatuses[] = array(
        'ID' => $arStatus["ID"],
        'NAME' => $arStatus["NAME"]
    );
}

$arAllOptions = Array(
    array("PROPERTY_NAME", GetMessage("ANMASLOV_PESHKARIKI_SETTINGS_NAME")),
    array("PROPERTY_PHONE", GetMessage("ANMASLOV_PESHKARIKI_SETTINGS_PHONE")),
    array("PROPERTY_STREET", GetMessage("ANMASLOV_PESHKARIKI_SETTINGS_STREET")),
    array("PROPERTY_BUILDING", GetMessage("ANMASLOV_PESHKARIKI_SETTINGS_BUILDING")),
    array("PROPERTY_APARTAMENTS", GetMessage("ANMASLOV_PESHKARIKI_SETTINGS_APARTAMENTS")),
);

$arCity = PeshkarikiApi::getCityList();

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
        foreach ($arCity as $key => $city)
        {
            foreach ($arAllOptions as $option)
            {
                $name = $option[0].$key;
                $val = $_POST[$name];
                COption::SetOptionString($module_id, $name, $val, $key. ' '. $option[1]);
            }
        }
        COption::SetOptionString($module_id, 'PROPERTY_LOGIN', $_POST['PROPERTY_LOGIN'], GetMessage('ANMASLOV_PESHKARIKI_OPT_PROP_LOGIN'));
        COption::SetOptionString($module_id, 'PROPERTY_PASSWORD', $_POST['PROPERTY_PASSWORD'], GetMessage('ANMASLOV_PESHKARIKI_OPT_PROP_PASSWORD'));

        $chBx = ($_POST['PROPERTY_MAKE_ORDER'] == 'Y' ? 'Y' : 'N');
        COption::SetOptionString($module_id, 'PROPERTY_MAKE_ORDER', $chBx, GetMessage('ANMASLOV_PESHKARIKI_OPT_MAKE_ORDER'));
        COption::SetOptionString($module_id, 'PROPERTY_ORDER_STATUS', $_POST['PROPERTY_ORDER_STATUS'], GetMessage('ANMASLOV_PESHKARIKI_OPT_ORDER_STATUS'));
    }
}

$tabControl->Begin();
?>
<form method="POST"
      action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>"
      name="anmaslov.peshkariki_settings">

    <?=bitrix_sessid_post();?>
    <?

    $tabControl->BeginNextTab();
    ?>

    <tr>
        <td width="30%" valign="top">
            <label for="PROPERTY_LOGIN"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_LOGIN")?>:</label>
        </td>
        <td width="70%">
            <? $val = COption::GetOptionString($module_id,'PROPERTY_LOGIN', '');?>
            <input type="text" size="30" maxlength="255" id="PROPERTY_LOGIN"
                   value="<?=htmlspecialcharsbx($val)?>"
                   name="PROPERTY_LOGIN" />
        </td>
    </tr>

    <tr>
        <td width="30%" valign="top">
            <label for="PROPERTY_PASSWORD"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_PASSWORD")?>:</label>
        </td>
        <td width="70%">
            <? $val = COption::GetOptionString($module_id,'PROPERTY_PASSWORD', '');?>
            <input type="password" size="30" maxlength="255" id="PROPERTY_PASSWORD"
                   value="<?=htmlspecialcharsbx($val)?>"
                   name="PROPERTY_PASSWORD" />
        </td>
    </tr>

    <?
    $tabControl->BeginNextTab();
    ?>
    <?foreach ($arCity as $key => $city):?>

        <tr class="heading">
            <td colspan="2"><b><?=$city?></b></td>
        </tr>

        <?foreach ($arAllOptions as $arOption):
            $val = COption::GetOptionString($module_id, $arOption[0].$key, '');
            ?>
            <tr>
                <td width="30%">
                    <label for="<?=$arOption[0].$key?>">
                        <?=$arOption[1]?>:
                    </label>
                </td>
                <td>
                    <input type="text" size="50" maxlength="255" id="<?=$arOption[0].$key?>"
                           value="<?=$val?>"
                           name="<?=$arOption[0].$key?>" />
                </td>
            </tr>
        <?endforeach;?>

    <?endforeach;?>

    <?
    $tabControl->BeginNextTab();
    ?>

    <tr class="heading">
        <td colspan="2"><b><? echo GetMessage("ANMASLOV_PESHKARIKI_OPT_TAB_ORDER_TITLE") ?></b></td>
    </tr>

    <tr>
        <td width="30%">
            <label for="make_order"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_MAKE_ORDER") ?></label>
        </td>
        <td>
            <? $val = COption::GetOptionString($module_id,'PROPERTY_MAKE_ORDER', '');?>
            <input type="checkbox" name="PROPERTY_MAKE_ORDER" id="make_order" value="Y" <?=($val == 'Y' ?' checked':'')?>>
        </td>
    </tr>

    <tr>
        <td width="30%">
            <label for="ORDER_STATUS">
                <?=GetMessage("ANMASLOV_PESHKARIKI_OPT_ORDER_STATUS") ?>
            </label>
        </td>
        <td>
            <? $val = COption::GetOptionString($module_id,'PROPERTY_ORDER_STATUS', '');?>
            <select name="PROPERTY_ORDER_STATUS" id="ORDER_STATUS">
                <?foreach ($arStatuses as $arStatus):?>
                    <?='<option value="' . htmlspecialcharsbx($arStatus['ID']) . '" ' . (($arStatus['ID'] == htmlspecialcharsbx($val)) ? 'selected="selected"' : '') . '>' . htmlspecialcharsbx($arStatus['NAME']) . '</option>'?>
                <?endforeach;?>
            </select>
        </td>
    </tr>

    <?
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