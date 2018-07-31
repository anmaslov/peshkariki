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

//get pay systems
$rsPaySystems = CSalePaySystemAction::GetList(array(), array("PS_ACTIVE" => "Y"));
while ($arPaySystem = $rsPaySystems->Fetch())
{
    $arPaySystems[] = array(
        'ID' => $arPaySystem['ID'],
        'NAME' => '[' . $arPaySystem['ID'] .'] '. $arPaySystem['NAME'],
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

        $chBxLog = ($_POST['PROPERTY_MAKE_LOG'] == 'Y' ? 'Y' : 'N');
        COption::SetOptionString($module_id, 'PROPERTY_MAKE_LOG', $chBxLog, GetMessage('ANMASLOV_PESHKARIKI_OPT_MAKE_LOG'));

        $weight = (intval($_POST['PROPERTY_WEIGHT']) < 10 ? 10 : intval($_POST['PROPERTY_WEIGHT']));
        COption::SetOptionInt($module_id, 'PROPERTY_WEIGHT', $weight, GetMessage('ANMASLOV_PESHKARIKI_OPT_PROP_WEIGHT'));

        COption::SetOptionString($module_id, 'PROPERTY_CLIENT', $_POST['PROPERTY_CLIENT'], GetMessage('ANMASLOV_PESHKARIKI_OPT_PROP_CLIENT'));

        COption::SetOptionString($module_id, 'PROPERTY_CLEARING', $_POST['PROPERTY_CLEARING'], GetMessage('ANMASLOV_PESHKARIKI_OPT_PROP_CLEARING'));

        $chBx = ($_POST['PROPERTY_MAKE_ORDER'] == 'Y' ? 'Y' : 'N');
        COption::SetOptionString($module_id, 'PROPERTY_MAKE_ORDER', $chBx, GetMessage('ANMASLOV_PESHKARIKI_OPT_MAKE_ORDER'));
        $chBx = ($_POST['PROPERTY_CANCEL_ORDER'] == 'Y' ? 'Y' : 'N');
        COption::SetOptionString($module_id, 'PROPERTY_CANCEL_ORDER', $chBx, GetMessage('ANMASLOV_PESHKARIKI_OPT_CANCEL_ORDER'));

        COption::SetOptionString($module_id, 'PROPERTY_ORDER_STATUS', $_POST['PROPERTY_ORDER_STATUS'], GetMessage('ANMASLOV_PESHKARIKI_OPT_ORDER_STATUS'));

        COption::SetOptionString($module_id, 'PROPERTY_PAYMENT_METHOD', $_POST['PROPERTY_PAYMENT_METHOD'], GetMessage('ANMASLOV_PESHKARIKI_OPT_PAYMENT_METHOD'));
        COption::SetOptionString($module_id, 'PROPERTY_CACH_RETURN_METHOD', $_POST['PROPERTY_CACH_RETURN_METHOD'], GetMessage('ANMASLOV_PESHKARIKI_OPT_CACH_RETURN_METHOD'));
        COption::SetOptionString($module_id, 'PROPERTY_RETURN_CONTACTS', $_POST['PROPERTY_RETURN_CONTACTS'], GetMessage('ANMASLOV_PESHKARIKI_OPT_RETURN_CONTACTS'));   

        COption::SetOptionString($module_id, 'PROPERTY_ORDER_COMMENT', $_POST['PROPERTY_ORDER_COMMENT'], GetMessage('ANMASLOV_PESHKARIKI_OPT_ORDER_COMMENT'));
        
        $pay_method_sel = $_POST['PROPERTY_PAYMENT_METHOD_SEL'];
        $errPaysIntersect = array_intersect($pay_method_sel['PAYED'], $pay_method_sel['CURIER']);
        
        if (count($errPaysIntersect)) {
            CAdminMessage::ShowMessage(GetMessage('ANMASLOV_PESHKARIKI_PAYMENT_INTERSECT_ERR'));
        } else {
            COption::SetOptionString($module_id, 'PROPERTY_PAYMENT_METHOD_PAYED', 
                                    implode($pay_method_sel['PAYED'], ','), 
                                    GetMessage('ANMASLOV_PESHKARIKI_PROPERTY_PAYMENT_METHOD_PAYED'));

            COption::SetOptionString($module_id, 'PROPERTY_PAYMENT_METHOD_CURIER', 
                                    implode($pay_method_sel['CURIER'], ','), 
                                    GetMessage('ANMASLOV_PESHKARIKI_PROPERTY_PAYMENT_METHOD_CURIER'));
        }

    }
}

//CAdminMessage::ShowNote('Show simple notification');

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

    <tr>
        <td width="30%">
            <label for="make_log"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_MAKE_LOG") ?></label>
        </td>
        <td width="70%">
            <? $val = COption::GetOptionString($module_id,'PROPERTY_MAKE_LOG', 'N');?>
            <input type="checkbox" name="PROPERTY_MAKE_LOG" id="make_log" value="Y" <?=($val == 'Y' ?' checked':'')?>> -
            <a href="/bitrix/admin/event_log.php?set_filter=Y&find_type=audit_type_id&find_audit_type%5B0%5D=PESHKARIKI_TYPE" target="_blank"><?=GetMessage('ANMASLOV_PESHKARIKI_OPT_LOG_HREF')?></a>
        </td>
    </tr>

    <tr>
        <td width="30%" valign="top">
            <label for="PROPERTY_WEIGH"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_WEIGHT")?>:</label>
        </td>
        <td width="70%">
            <? $val = COption::GetOptionInt($module_id,'PROPERTY_WEIGHT', 10);?>
            <input type="text" size="30" maxlength="255" id="PROPERTY_WEIGH"
                   value="<?=htmlspecialcharsbx($val)?>"
                   name="PROPERTY_WEIGHT" />
        </td>
    </tr>

    <tr>
        <td width="30%" valign="top">
            <label for="PROPERTY_CLIENT"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_CLIENT")?>:</label>
        </td>
        <td width="70%">

            <? $val = COption::GetOptionString($module_id, 'PROPERTY_CLIENT', 'BITRIX');?>
            <select name="PROPERTY_CLIENT" id="PROPERTY_CLIENT">
                <option value="BITRIX" <?=((htmlspecialcharsbx($val) == 'BITRIX') ? 'selected="selected"' : '')?>>Bitrix API client</option>
                <option value="CURL"  <?=((htmlspecialcharsbx($val) == 'CURL') ? 'selected="selected"' : '')?>>Curl</option>
            </select>

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
            <label for="cancel_order"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_CANCEL_ORDER") ?></label>
        </td>
        <td>
            <? $val = COption::GetOptionString($module_id,'PROPERTY_CANCEL_ORDER', '');?>
            <input type="checkbox" name="PROPERTY_CANCEL_ORDER" id="cancel_order" value="Y" <?=($val == 'Y' ?' checked':'')?>>
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

    <tr>
        <td width="30%" valign="top">
            <label for="PROPERTY_CLEARING"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_CLEARING")?>:</label>
        </td>
        <td width="70%">
            <? $val = COption::GetOptionString($module_id, 'PROPERTY_CLEARING', 0);?>
            <select name="PROPERTY_CLEARING" id="PROPERTY_CLEARING">
                <option value="0" <?=((htmlspecialcharsbx($val) == 0) ? 'selected="selected"' : '')?>><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_CLEARING_0")?></option>
                <option value="1"  <?=((htmlspecialcharsbx($val) == 1) ? 'selected="selected"' : '')?>><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_CLEARING_1")?></option>
            </select>
            <br /><strong><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_CLEARING_DESC")?>.</strong>
        </td>
    </tr>

    <tr>
        <td width="30%" valign="top">
            <label for="PROPERTY_PAYMENT_METHOD"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PAYMENT_METHOD")?>:</label>
        </td>
        <td width="70%">
            <? $payMethod = COption::GetOptionString($module_id, 'PROPERTY_PAYMENT_METHOD', 0);?>
            <select name="PROPERTY_PAYMENT_METHOD" id="PROPERTY_PAYMENT_METHOD">
                <option value="0" <?=((htmlspecialcharsbx($payMethod) == 0) ? 'selected="selected"' : '')?>><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_PM_0")?></option>
                <option value="1"  <?=((htmlspecialcharsbx($payMethod) == 1) ? 'selected="selected"' : '')?>><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_PM_1")?></option>
            </select>  
        </td>
    </tr>

    <tr id="payment_more">
        <td width="30%" valign="top">
            <label for="PROPERTY_PAYMENT_METHOD_SEL"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PAYMENT_FOR")?>:</label>
        </td>
        <td width="70%">
            <table>
                <tr>
                    <td width="50%">
                        <?=GetMessage("ANMASLOV_PESHKARIKI_PROPERTY_PAYMENT_METHOD_PAYED")?>:<br>
                        <?$payVal = explode(',', COption::GetOptionString($module_id, 'PROPERTY_PAYMENT_METHOD_PAYED', ''));?>
                        <select name="PROPERTY_PAYMENT_METHOD_SEL[PAYED][]" multiple="">
                            <?foreach($arPaySystems as $paySystem):?>
                                <option value="<?=$paySystem['ID']?>" <?=(in_array($paySystem['ID'], $payVal) ? ' selected=""' :'')?>>
                                    <?=$paySystem['NAME']?>
                                </option>
                            <?endforeach?>
                        </select>        
                    </td>
                    <td  width="50%">
                        <?=GetMessage("ANMASLOV_PESHKARIKI_PROPERTY_PAYMENT_METHOD_CURIER")?>:<br>
                        <?$payVal = explode(',', COption::GetOptionString($module_id, 'PROPERTY_PAYMENT_METHOD_CURIER', ''));?>
                        <select name="PROPERTY_PAYMENT_METHOD_SEL[CURIER][]" multiple="">
                        <?foreach($arPaySystems as $paySystem):?>
                            <option value="<?=$paySystem['ID']?>" <?=(in_array($paySystem['ID'], $payVal) ? ' selected=""' :'')?>>
                                <?=$paySystem['NAME']?>
                            </option>
                        <?endforeach?>
                        </select>        
                    </td>
                </tr>
            </table>

            <i><?=GetMessage("ANMASLOV_PESHKARIKI_PAYMENT_METHOD_DESCRIPTION")?></i>

        </td>
    </tr>

    <tr id="cach_method">
        <td width="30%" valign="top">
            <label for="PROPERTY_CACH_RETURN_METHOD"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_CACH_RETURN_METHOD")?>:</label>
        </td>
        <td width="70%">
            <? $val = COption::GetOptionString($module_id, 'PROPERTY_CACH_RETURN_METHOD', 0);?>
            <select name="PROPERTY_CACH_RETURN_METHOD" id="PROPERTY_CACH_RETURN_METHOD">
                <option value="0" <?=((htmlspecialcharsbx($val) == 0) ? 'selected="selected"' : '')?>><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_CRM_0")?></option>
                <option value="1"  <?=((htmlspecialcharsbx($val) == 1) ? 'selected="selected"' : '')?>><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_CRM_1")?></option>
                <option value="2"  <?=((htmlspecialcharsbx($val) == 2) ? 'selected="selected"' : '')?>><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_PROP_CRM_2")?></option>
            </select>  
            <? $val = COption::GetOptionString($module_id, 'PROPERTY_RETURN_CONTACTS', '');?>
            <input id="PROPERTY_RETURN_CONTACTS" 
                name="PROPERTY_RETURN_CONTACTS" 
                placeholder="<?=GetMessage("ANMASLOV_PESHKARIKI_OPT_RETURN_CONTACTS")?>" 
                value="<?=htmlspecialcharsbx($val)?>"
                type="text" autocomplete="off">
                <br>
                <b><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_CACH_RETURN_METHOD_DESC")?></b>
        </td>
    </tr>
        
    <tr>
        <td width="30%">
            <label for="order_comment"><?=GetMessage("ANMASLOV_PESHKARIKI_OPT_ORDER_COMMENT") ?></label>
        </td>
        <td>
            <? $val = COption::GetOptionString($module_id,'PROPERTY_ORDER_COMMENT', '');?>
            <input type="text" size="30" maxlength="255" id="order_comment"
                   value="<?=htmlspecialcharsbx($val)?>"
                   name="PROPERTY_ORDER_COMMENT" />
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