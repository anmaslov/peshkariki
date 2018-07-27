<?php

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
                'price' => GetMessage('ANMASLOV_PESHKARIKI_CONFIG_DELIVERY_PRICE_TITLE'),
            ),

            'CONFIG' => array(
                'HEADER_API_SETTINGS' => array(
                    'TYPE' => 'SECTION',
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_TITLE_API_SECTION_HEADER'),
                    'GROUP' => 'delivery'
                ),
                'DELIVERY_MIN_PRICE' => array(
                    'TYPE' => 'STRING',
                    'DEFAULT' => 0,
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_TITLE_DELIVERY_MIN_PRICE'),
                    'GROUP' => 'price'
                ),
                'DELIVERY_FIX_PRICE' => array(
                    'TYPE' => 'STRING',
                    'DEFAULT' => 0,
                    'TITLE' => GetMessage('ANMASLOV_PESHKARIKI_TITLE_DELIVERY_FIX_PRICE'),
                    'GROUP' => 'price'
                ),
            ),
        );
        return $arConfig;
    }

    function Compability($arOrder, $arConfig)
    {
        $response = self::__calc($arOrder, $arConfig);

        $profile_list = array();
        if ($response['RESULT'] == 'OK') {
            $profile_list[] = 'courier';
        }

        return $profile_list;
    }

    function Calculate($profile, $arConfig, $arOrder)
    {
        $response = self::__calc($arOrder, $arConfig);

        if ($response['RESULT'] == 'OK') {
            return array(
                'RESULT' => 'OK',
                'VALUE' => $response['TEXT']
            );
        }

        return array(
            'RESULT' => 'ERROR',
            'TEXT' => $response['TEXT']
        );
    }

    function __calc($arOrder, $arConfig)
    {
        $result = array('RESULT' => 'ERROR', 'TEXT' => GetMessage('ANMASLOV_PESHKARIKI_TEXT_ERROR'));
        $arData = self::prepare($arOrder);

        if(!$arData)
            return $result;

        $obCache = new CPHPCache();
        $cache_time = 10*60;
        $arItemsId = array_column($arOrder["ITEMS"], 'ID');
        $cache_id = 'ANMASLOV_PESHKARIKI_RUS|'.$arOrder['LOCATION_TO'].'|'.$arOrder['WEIGHT'].'|'.serialize($arItemsId);

        if ($obCache->InitCache($cache_time, $cache_id, "/")){
            $cache_data = $obCache->GetVars();
            return $cache_data['VALUE'];
        }

        $pa = new PeshkarikiApi(
            CUtilsPeshkariki::getCurrentClient(),
            CUtilsPeshkariki::getConfig("PROPERTY_LOGIN"),
            CUtilsPeshkariki::getConfig("PROPERTY_PASSWORD")
        );

        //get token
        $token = $pa->login();
        if ($token['SUCCESS'] == false)
        {
            CUtilsPeshkariki::addLog($token, 'login', 'ERROR');
            $result['TEXT'] = $token['DATA'];
            return $result;
        }

        //get price
        $price = $pa->addOrder($arData, $pa::CALCULATE);
        if ($price['SUCCESS'] == false){
            CUtilsPeshkariki::addLog($price, 'get_price', 'ERROR');
            $result['TEXT'] = $price['DATA'];
            return $result;
        }

        if (($arConfig['DELIVERY_MIN_PRICE']['VALUE'] > 0) 
            && ($arConfig['DELIVERY_MIN_PRICE']['VALUE'] > $price['DATA']))
            $price['DATA'] = $arConfig['DELIVERY_MIN_PRICE']['VALUE'];

        if ($arConfig['DELIVERY_FIX_PRICE']['VALUE'] > 0)
            $price['DATA'] = $arConfig['DELIVERY_FIX_PRICE']['VALUE'];

        $result['RESULT'] = 'OK';
        $result['TEXT'] = $price['DATA'];

        $obCache->StartDataCache($cache_time, $cache_id);
        $obCache->EndDataCache(array('VALUE' => $result));

        return $result;
    }

    function prepare($arOrder)
    {
        $location = CSaleLocation::GetByID($arOrder['LOCATION_TO'], LANGUAGE_ID);

        $arrCity = PeshkarikiApi::getCityList();
        $cityKey = array_search($location['CITY_NAME'], $arrCity);

        if ($cityKey == false){
            return false;
            CUtilsPeshkariki::addLog('City key not found', 'get_cityKey', 'ERROR');
        }


        $arrFrom = array(
            'name' => CUtilsPeshkariki::getConfig("PROPERTY_NAME$cityKey"),
            'phone' => CUtilsPeshkariki::getConfig("PROPERTY_PHONE$cityKey"),
            'street' => CUtilsPeshkariki::getConfig("PROPERTY_STREET$cityKey"),
            'building' => CUtilsPeshkariki::getConfig("PROPERTY_BUILDING$cityKey"),
            'apartments' => CUtilsPeshkariki::getConfig("PROPERTY_APARTMENTS$cityKey"),
            'time_from' => date('Y-m-d', strtotime('+1 day')) . ' 09:00:00',
            'time_to' => date('Y-m-d', strtotime('+2 day')) . ' 18:00:00',
            'items' => array(),
        );

        if (strlen($arrFrom['name'].$arrFrom['phone'].$arrFrom['street'].$arrFrom['building']) == 0)
            return false;

        $arrTo = $arrFrom;

        $defaultWeight = CUtilsPeshkariki::getConfig("PROPERTY_WEIGHT", 10);
        foreach($arOrder["ITEMS"] as $item) {
            $arrTo['items'][] = array(
                "name" => CUtilsPeshkariki::toUtf($item["NAME"]),
                "price" => round($item["PRICE"]),
                "weight" => (intval($item["WEIGHT"])>10) ? round($item["WEIGHT"]) : $defaultWeight,
                "quant" => $item["QUANTITY"],
            );
        }

        $arOrder = array(
            'inner_id' => uniqid(),
            'comment' => 'check price',
            "calculate" => 1,
            'cash' => 0,
            'clearing' => COption::GetOptionString(CUtilsPeshkariki::MODULE_ID, 'PROPERTY_CLEARING', 0),
            'ewalletType' => 0,
            'city_id' => $cityKey,
            'order_type_id' => 1,
            'route' => array($arrFrom, $arrTo)
        );

        return $arOrder;
    }
}