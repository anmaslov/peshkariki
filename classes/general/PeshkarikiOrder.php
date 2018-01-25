<?php

IncludeModuleLangFile(__FILE__);

class COrderAnmaslovPeshkariki{

    const MODULE_ID = 'anmaslov.peshkariki';

    public static function addOrder($id, $arFields)
    {

        $propMakeOrder = CUtilsPeshkariki::getConfig('PROPERTY_MAKE_ORDER', 'N');
        $propOrderStatus = CUtilsPeshkariki::getConfig('PROPERTY_ORDER_STATUS', 'F');

        if ($propMakeOrder == 'N' || $propOrderStatus != $arFields)
            return;

        $arOrder = CSaleOrder::GetById($id);

        if ($arOrder['DELIVERY_ID'] != 'anmaslov_peshkariki:courier')
            return;

        $arProp = self::getOrderProps($id);
        if ($arProp['city'] == false)
            return;

        $arRouteItems = self::getItems($id);

        $arPrepare = array(
            'ORDER_ID' => $id,
            'ORDER' => $arProp,
            'ITEMS' => $arRouteItems,
        );

        $arData = self::prepareData($arPrepare);
        if (!$arData)
            return;

        $pa = new PeshkarikiApi(
            CUtilsPeshkariki::getCurrentClient(),
            CUtilsPeshkariki::getConfig('PROPERTY_LOGIN'),
            CUtilsPeshkariki::getConfig('PROPERTY_PASSWORD')
        );

        $token = $pa->login();
        if ($token['SUCCESS'] == false)
            return;

        $price = $pa->addOrder($arData);
        CUtilsPeshkariki::addLog($price, 'add_new_order', 'INFO');
        return $price;
    }

    public static function prepareData($arData)
    {
        if (!$cityKey = $arData['ORDER']['city'])
            return false;

        unset($arData['ORDER']['city']);

        $arrTo = $arData['ORDER'];
        $arrTo['time_from'] = date('Y-m-d', strtotime('+1 day')) . ' 09:00:00';
        $arrTo['time_to'] = date('Y-m-d', strtotime('+2 day')) . ' 18:00:00';
        $arrTo['items'] = $arData["ITEMS"];

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

        $arOrder = array(
            'inner_id' => $arData['ORDER_ID'],
            'comment' => CUtilsPeshkariki::getConfig('PROPERTY_ORDER_COMMENT'),
            "calculate" => 0,
            'cash' => 0,
            'clearing' => 0,
            'ewalletType' => 0,
            'city_id' => $cityKey,
            'order_type_id' => 1,
            'route' => array($arrFrom, $arrTo)
        );

        return $arOrder;
    }

    public static function getOrderProps($orderId)
    {
        $dbProp = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $orderId));

        $arProp = array();
        while ($arVals = $dbProp->GetNext()) {
            switch ($arVals['CODE']) {
                case 'FIO':
                    $arProp['name'] = CUtilsPeshkariki::toUtf($arVals['VALUE']);
                    break;
                case 'CONTACT_PERSON':
                    $arProp['name'] = CUtilsPeshkariki::toUtf($arVals['VALUE']);
                    break;
                case 'PHONE':
                    $arProp['phone'] = CUtilsPeshkariki::toUtf($arVals['VALUE']);
                    break;
                case 'LOCATION':
                    $location = CSaleLocation::GetByID($arVals['VALUE'], LANGUAGE_ID);
                    $arrCity = PeshkarikiApi::getCityList();
                    $cityKey = array_search($location['CITY_NAME'], $arrCity);
                    $arProp['city'] = $cityKey;
                    break;
                case 'CITY':
					$location = CSaleLocation::GetByID($arVals['VALUE'], LANGUAGE_ID);
                    $arrCity = PeshkarikiApi::getCityList();
                    $cityKey = array_search($location['CITY_NAME'], $arrCity);
                    $arProp['city'] = $cityKey;
                    break;
                case 'ADDRESS':
                    $arProp['street'] = CUtilsPeshkariki::toUtf($arVals['VALUE']);
                    break;
            }
        }
        return $arProp;
    }

    public static function getItems($orderId)
    {
        $dbItems = CSaleBasket::GetList(
            array(),
            array("ORDER_ID" => $orderId), false, false,
            array("ID", "NAME", "PRICE", "WEIGHT", "QUANTITY")
        );

        $arRouteItems = array();
        $defaultWeight = CUtilsPeshkariki::getConfig("PROPERTY_WEIGHT", 10);
        while ($arItems = $dbItems->Fetch()) {
            $arRouteItems[] = array(
                'name' => CUtilsPeshkariki::toUtf($arItems['NAME']),
                'price' => round($arItems['PRICE']),
                'weight' => (intval($arItems['WEIGHT']) > 10 ? round($arItems['WEIGHT']) : $defaultWeight),
                'quant' => $arItems['QUANTITY'],
            );
        }
        return $arRouteItems;
    }
}
