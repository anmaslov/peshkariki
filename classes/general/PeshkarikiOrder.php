<?php

class COrderAnmaslovPeshkariki{

    const MODULE_ID = 'anmaslov.peshkariki';


    public static function addOrder($id, $arFields)
    {
        //AddMessage2Log($id, "id"); orderId

        $propMakeOrder = COption::GetOptionString(self::MODULE_ID, "PROPERTY_MAKE_ORDER", 'N');
        $propOrderStatus = COption::GetOptionString(self::MODULE_ID, "PROPERTY_ORDER_STATUS", 'F');

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
            COption::GetOptionString(self::MODULE_ID, "PROPERTY_LOGIN", ''),
            COption::GetOptionString(self::MODULE_ID, "PROPERTY_PASSWORD", '') );

        $token = $pa->login();
        if ($token['SUCCESS'] == false)
            return;

        $price = $pa->addOrder($arData);
        var_dump($price);
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
            'name' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_NAME$cityKey", ''),
            'phone' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_PHONE$cityKey", ''),
            'street' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_STREET$cityKey", ''),
            'building' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_BUILDING$cityKey", ''),
            'apartments' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_APARTMENTS$cityKey", ''),
            'time_from' => date('Y-m-d', strtotime('+1 day')) . ' 09:00:00',
            'time_to' => date('Y-m-d', strtotime('+2 day')) . ' 18:00:00',
            'items' => array(),
        );

        if (strlen($arrFrom['name'].$arrFrom['phone'].$arrFrom['street'].$arrFrom['building']) == 0)
            return false;

        $arOrder = array(
            'inner_id' => $arData['ORDER_ID'],
            'comment' => COption::GetOptionString(self::MODULE_ID, "PROPERTY_ORDER_COMMENT", ''),
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
                    $arProp['name'] = $arVals['VALUE'];
                    break;
                case 'PHONE':
                    $arProp['phone'] = $arVals['VALUE'];
                    break;
                case 'LOCATION':
                    $location = CSaleLocation::GetByID($arVals['VALUE'], LANGUAGE_ID);
                    $arrCity = PeshkarikiApi::getCityList();
                    $cityKey = array_search($location['CITY_NAME'], $arrCity);
                    $arProp['city'] = $cityKey;
                    break;
                case 'ADDRESS':
                    $arProp['street'] = $arVals['VALUE'];
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
        while ($arItems = $dbItems->Fetch()) {
            $arRouteItems[] = array(
                'name' => $arItems['NAME'],
                'price' => round($arItems['PRICE']),
                'weight' => (intval($arItems['WEIGHT']) > 0 ? round($arItems['WEIGHT']) : '1'),
                'quant' => $arItems['QUANTITY'],
            );
        }
        return $arRouteItems;
    }
}