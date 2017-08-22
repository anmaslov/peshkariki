<?php
/**
 * Created by PhpStorm.
 * User: Maslov Alexey
 * Date: 21.08.17
 * Time: 12:10
 */
IncludeModuleLangFile(__FILE__);

Class CDeliveryAnmaslovPeshkariki
{
    const MODULE_ID = "anmaslov.peshkariki";

    function Init()
    {
        if ($arCurrency = CCurrency::GetByID('RUR')):
            $base_currency = 'RUR';
        else:
            $base_currency = 'RUB';
        endif;

        return array(
            "SID" => "anmaslov_peshkariki", // unique string identifier
            "NAME" => GetMessage('ANMASLOV_PESHKARIKI_MODULE_NAME'), // services public title
            "DESCRIPTION" => GetMessage('ANMASLOV_PESHKARIKI_MODULE_DESCRIPTION'), // services public dedcription
            "DESCRIPTION_INNER" => GetMessage('ANMASLOV_PESHKARIKI_MODULE_DESCRIPTION_INNER'), // services private description for admin panel
            "BASE_CURRENCY" => $base_currency, // services base currency

            "HANDLER" => __FILE__, // services path

            "COMPABILITY" => array("CDeliveryAnmaslovPeshkariki", "Compability"),
            "CALCULATOR" => array("CDeliveryAnmaslovPeshkariki", "Calculate"),

            "DBGETSETTINGS" => array("CDeliveryAnmaslovPeshkariki", "GetSettings"),
            "DBSETSETTINGS" => array("CDeliveryAnmaslovPeshkariki", "SetSettings"),

            "GETCONFIG" => array("CDeliveryAnmaslovPeshkariki", "GetConfig"),

            "PROFILES" => array(
                "courier" => array(
                    "TITLE" => GetMessage("ANMASLOV_PESHKARIKI_COURIER_TITLE"),
                    "DESCRIPTION" => GetMessage("ANMASLOV_PESHKARIKI_COURIER_DESCRIPTION"),

                    "RESTRICTIONS_WEIGHT" => array(0),
                    "RESTRICTIONS_SUM" => array(0),
                )
            )
        );
    }

    function SetSettings($arSettings)
    {
        return serialize($arSettings);
    }

    function GetSettings($strSettings)
    {
        return unserialize($strSettings);
    }

    function GetConfig()
    {
        $arConfig = array(
            'CONFIG_GROUPS' => array(
                'delivery' => GetMessage('ANMASLOV_PESHKARIKI_CONFIG_DELIVERY_TITLE'),
            ),

            'CONFIG' => array(
                'HEADER_API_SETTINGS' => array(
                    'TYPE' => 'SECTION',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_TITLE_API_SECTION_HEADER'),
                    'GROUP' => 'delivery'
                )
            ),
        );
        return $arConfig;
    }

    function Compability($arOrder, $arConfig)
    {
        /*AddMessage2Log($arOrder, 'arOrder');
        AddMessage2Log($arConfig, 'arConfig');*/

        $profile_list = array();

        if (true) {
            $profile_list[] = 'courier';
        }

        return $profile_list;
    }

    function Calculate($profile, $arConfig, $arOrder)
    {
        $arrData = self::prepare($arOrder);
        AddMessage2Log($arrData, "arrData");

        return array(
            'RESULT' => 'OK',
            'VALUE' => '4',//$response['BODY'][0],
            'TRANSIT' => '55' //$response['BODY'][1]
        );
        /*return array(
            'RESULT' => 'ERROR',
            'TEXT' => 'Не удалось рассчитать срок и стоимость доставки'
        );*/
    }

    function prepare($arOrder)
    {
        $location = CSaleLocation::GetByID($arOrder['LOCATION_TO'], LANGUAGE_ID);
        //$location['CITY_NAME']

        $arrCity = PeshkarikiApi::getCityList();
        $cityKey = array_search($location['CITY_NAME'], $arrCity);
        if ($cityKey == false)
            return false;

        $arrFrom = array(
            'name' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_NAME$cityKey", ''),
            'phone' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_NAME$cityKey", ''),
            'street' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_NAME$cityKey", ''),
            'building' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_NAME$cityKey", ''),
            'apartments' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_NAME$cityKey", ''),
            'time_from' => date('Y-m-d H:i:s'),
            'time_to' => date('Y-m-d 18:00:00', strtotime('+1 day')),
            'items' => array(),
        );

        if (strlen($arrFrom['name'].$arrFrom['phone'].$arrFrom['street'].$arrFrom['building']) == 0)
            return false;

        $arrTo = $arrFrom;
        $arrTo['items'] = array(
            'name' => 'price item',
            'price' => '100',
            'weight' => '100',
            'quant' => 1,
        );

        $arOrder = array(
            'inner_id' => uniqid(),
            'comment' => 'check price',
            'cash' => 0,
            'clearing' => 0,
            'ewalletType' => 0,
            'city_id' => $cityKey,
            'order_type_id' => 1,
            'route' => array($arrFrom, $arrTo)
        );

        return $arOrder;
    }
}