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
                'city' => GetMessage('ANMASLOV_PESHKARIKI_CONFIG_CITY_TITLE')
            ),

            'CONFIG' => array(
                //Settings api
                'HEADER_API_SETTINGS' => array(
                    'TYPE' => 'SECTION',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_TITLE_API_SECTION_HEADER'),
                    'GROUP' => 'delivery'
                ),
                'LOGIN' => array(
                    'TYPE' => 'STRING',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_SETTINGS_LOGIN'),
                    'DEFAULT' => '',
                    'GROUP' => 'delivery',
                    'SIZE' => '50'
                ),
                'PASSWORD' => array(
                    'TYPE' => 'STRING',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_SETTINGS_PASSWORD'),
                    'DEFAULT' => '',
                    'GROUP' => 'delivery',
                    'SIZE' => '50'
                ),

                
                'HEADER_DELIVERY_CITY_MOSCOW' => array(
                    'TYPE' => 'SECTION',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_SETTINGS_TITLE_DELIVERY_MOSCOW'),
                    'GROUP' => 'city'
                ),
                'MOSCOW_NAME' => array(
                    'TYPE' => 'STRING',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_SETTINGS_NAME'),
                    'GROUP' => 'city',
                ),
                'MOSCOW_PHONE' => array(
                    'TYPE' => 'STRING',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_SETTINGS_PHONE'),
                    'GROUP' => 'city',
                ),
                'MOSCOW_STREET' => array(
                    'TYPE' => 'STRING',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_SETTINGS_STREET'),
                    'GROUP' => 'city',
                ),
                'MOSCOW_BUILDING' => array(
                    'TYPE' => 'STRING',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_SETTINGS_BUILDING'),
                    'GROUP' => 'city',
                ),
                'MOSCOW_APARTAMENTS' => array(
                    'TYPE' => 'STRING',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_SETTINGS_APARTAMENTS'),
                    'GROUP' => 'city',
                ),
            ),
        );
        //AddMessage2Log($t, 'login');

        return $arConfig;
    }

    function Compability($arOrder, $arConfig)
    {
        $profile_list = array();

        if (true) {
            $profile_list[] = 'courier';
        }

        return $profile_list;
    }

    function Calculate($profile, $arConfig, $arOrder)
    {
        return array(
            'RESULT' => 'OK',
            'VALUE' => $response['BODY'][0],
            'TRANSIT' => $response['BODY'][1]
        );
        /*return array(
            'RESULT' => 'ERROR',
            'TEXT' => 'Не удалось рассчитать срок и стоимость доставки'
        );*/
    }
}